<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\UniboxAccount;
use App\Models\UniboxContact;
use App\Models\UniboxConversation;
use App\Models\UniboxMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UniboxService
{
    protected ?UniboxAccount $account;

    public function __construct(?UniboxAccount $account = null)
    {
        $this->account = $account;
    }

    protected function safeDecrypt(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        try {
            return decrypt($value);
        } catch (\Throwable $e) {
            $custom = UniboxEncryptionService::decrypt($value);
            if ($custom !== null) {
                return $custom;
            }
            if ($this->looksLikeLaravelEncryptedPayload($value)) {
                Log::warning('UniBox password appears to be encrypted with an old APP_KEY.', ['value_length' => strlen($value)]);
                return null;
            }
            return $value;
        }
    }

    protected function looksLikeLaravelEncryptedPayload(string $value): bool
    {
        $decoded = base64_decode($value, true);
        if (!is_string($decoded) || $decoded === '') {
            return false;
        }

        $payload = json_decode($decoded, true);
        if (!is_array($payload)) {
            return false;
        }

        return isset($payload['iv'], $payload['value'], $payload['mac']);
    }

    public function syncAccount(UniboxAccount $account): array
    {
        if ($account->status === 'paused') {
            return ['synced' => 0, 'errors' => []];
        }

        if ($account->type === 'pop3') {
            return ['synced' => 0, 'errors' => ['POP3 accounts are not supported for UniBox sync.']];
        }

        if ($account->oauth_refresh_token) {
            return $this->syncGmailApi($account);
        }

        $synced = 0;
        $errors = [];

        try {
            if (!function_exists('imap_open')) {
                throw new \RuntimeException('PHP IMAP extension is not installed.');
            }

            $encMap = ['ssl' => '/ssl', 'tls' => '/tls', 'none' => '/notls'];
            $enc = $encMap[$account->imap_encryption] ?? '/ssl';
            $mailbox = "{{$account->imap_host}:{$account->imap_port}/imap{$enc}}INBOX";

            $password = $this->safeDecrypt($account->imap_password);

            if ($password === null) {
                throw new \RuntimeException('IMAP password is missing or invalid. Please re-enter the password in account settings.');
            }

            imap_timeout(IMAP_OPENTIMEOUT, 15);
            $conn = @imap_open($mailbox, $account->imap_username, $password, 0, 1);

            // Fallback: some providers (e.g. Hostinger) require /novalidate-cert with PHP's imap_open().
            if (!$conn) {
                $mailbox = "{{$account->imap_host}:{$account->imap_port}/imap{$enc}/novalidate-cert}INBOX";
                $conn = @imap_open($mailbox, $account->imap_username, $password, 0, 1);
            }

            if (!$conn) {
                throw new \RuntimeException('IMAP connection failed: ' . imap_last_error());
            }

            imap_timeout(IMAP_READTIMEOUT, 15);

            $since = $account->last_synced_at
                ? $account->last_synced_at->format('d-M-Y')
                : now()->subDays(30)->format('d-M-Y');

            $uids = imap_search($conn, "SINCE \"{$since}\"", SE_UID);

            if ($uids === false) {
                $uids = $this->fallbackSearchUids($conn, $since);
            }

            if ($uids) {
                $maxPerSync = (int) (Setting::get('unibox_max_messages_per_sync') ?? 50);
                $uids = array_slice(array_reverse($uids), 0, $maxPerSync);

                foreach ($uids as $uid) {
                    try {
                        $this->processMessage($conn, $uid, $account);
                        $synced++;
                    } catch (\Throwable $e) {
                        $errors[] = "UID {$uid}: " . $e->getMessage();
                        Log::warning("UniBox sync error for account #{$account->id}", ['uid' => $uid, 'error' => $e->getMessage()]);
                    }
                }
            }

            imap_close($conn);

            $account->update([
                'last_synced_at' => now(),
                'last_error' => null,
                'status' => 'active',
            ]);
        } catch (\Throwable $e) {
            $account->update(['status' => 'error', 'last_error' => $e->getMessage()]);
            $errors[] = $e->getMessage();
            Log::error("UniBox account sync failed #{$account->id}", ['error' => $e->getMessage()]);
        }

        return ['synced' => $synced, 'errors' => $errors];
    }

    protected function fallbackSearchUids($conn, string $since): array
    {
        Log::warning('IMAP SINCE search returned false, falling back to ALL search', ['since' => $since]);

        $uids = imap_search($conn, 'ALL', SE_UID);
        if ($uids !== false) {
            return $uids;
        }

        Log::warning('IMAP ALL search also returned false, falling back to fetch overview', ['since' => $since]);

        $check = imap_check($conn);
        $n = $check->Nmsgs ?? 0;
        if ($n <= 0) {
            return [];
        }

        $result = [];
        $sinceTimestamp = strtotime($since);
        $batchSize = 100;

        for ($start = 1; $start <= $n; $start += $batchSize) {
            $end = min($start + $batchSize - 1, $n);
            $range = "{$start}:{$end}";
            $overviews = imap_fetch_overview($conn, $range);

            if (!$overviews) {
                continue;
            }

            foreach ($overviews as $overview) {
                $msgDate = strtotime($overview->date);
                if ($msgDate !== false && $msgDate >= $sinceTimestamp) {
                    $result[] = $overview->uid;
                }
            }
        }

        return $result;
    }

    protected function processMessage($conn, int $uid, UniboxAccount $account): void
    {
        $header = imap_rfc822_parse_headers(imap_fetchheader($conn, $uid, FT_UID));
        $messageId = trim($header->message_id ?? '');
        $inReplyTo = trim($header->in_reply_to ?? '');

        // Scope deduplication to this account so the same physical email
        // (e.g. a recipient's reply) can live in multiple UniBox accounts.
        if ($messageId && UniboxMessage::where('message_id', $messageId)
            ->whereHas('conversation', fn ($q) => $q->where('account_id', $account->id))
            ->exists()) {
            return;
        }

        $fromAddr = $header->from[0] ?? null;
        $fromEmail = $fromAddr ? strtolower($fromAddr->mailbox . '@' . $fromAddr->host) : '';
        $fromName = isset($fromAddr->personal) ? imap_utf8($fromAddr->personal) : null;
        $subject = imap_utf8($header->subject ?? '(no subject)');

        $body = $this->fetchBody($conn, $uid);

        // Detect sent messages: when the sender is the connected account itself,
        // the contact should be the recipient (mirrors Gmail API behaviour).
        $accountEmails = array_unique(array_filter([
            strtolower($account->email ?? ''),
            strtolower($account->imap_username ?? ''),
        ]));
        $isSent = in_array($fromEmail, $accountEmails, true);

        if ($isSent) {
            $toAddr = $header->to[0] ?? null;
            $contactEmail = $toAddr ? strtolower($toAddr->mailbox . '@' . $toAddr->host) : '';
            $contactName = isset($toAddr->personal) ? imap_utf8($toAddr->personal) : null;
        } else {
            $contactEmail = $fromEmail;
            $contactName = $fromName;
        }

        if (!$contactEmail) {
            $contactEmail = $fromEmail ?: 'unknown@unknown';
            $contactName = $fromName;
        }

        $contact = UniboxContact::firstOrCreate(
            ['customer_id' => $account->customer_id, 'email' => $contactEmail],
            ['name' => $contactName, 'first_seen_at' => now()]
        );
        $contact->increment('conversation_count', 0);
        $contact->update(['last_reply_at' => now()]);

        // Find or create conversation
        $threadId = $this->resolveThreadId($messageId, $inReplyTo, $account->customer_id, $account->id);
        $conversation = UniboxConversation::where('customer_id', $account->customer_id)
            ->where('thread_id', $threadId)
            ->first();

        if (!$conversation) {
            $conversation = UniboxConversation::create([
                'customer_id' => $account->customer_id,
                'account_id' => $account->id,
                'contact_id' => $contact->id,
                'subject' => $subject,
                'thread_id' => $threadId,
                'folder' => $isSent ? 'sent' : 'inbox',
                'status' => 'open',
                'is_read' => $isSent ? true : false,
                'last_message_at' => now(),
                'sla_deadline' => now()->addHours(2),
            ]);
            $contact->increment('conversation_count');
        } else {
            $update = [
                'last_message_at' => now(),
                'account_id' => $account->id,
            ];
            if (!$isSent) {
                $update['is_read'] = false;
            }
            $conversation->update($update);
        }

        $to = $this->parseAddresses($header->to ?? []);
        $cc = $this->parseAddresses($header->cc ?? []);

        UniboxMessage::create([
            'conversation_id' => $conversation->id,
            'customer_id' => $account->customer_id,
            'message_id' => $messageId,
            'from_email' => $fromEmail,
            'from_name' => $fromName,
            'to' => $to,
            'cc' => $cc,
            'subject' => $subject,
            'body_html' => $body['html'],
            'body_text' => $body['text'],
            'type' => $isSent ? 'outbound' : 'inbound',
            'status' => $isSent ? 'sent' : 'received',
            'in_reply_to' => $inReplyTo,
            'received_at' => $isSent ? null : now(),
            'sent_at' => $isSent ? now() : null,
        ]);

        $conversation->increment('message_count');
    }

    protected function resolveThreadId(string $messageId, string $inReplyTo, int $customerId, int $accountId): string
    {
        if ($inReplyTo) {
            $parent = UniboxMessage::where('message_id', $inReplyTo)
                ->whereHas('conversation', fn ($q) => $q->where('account_id', $accountId))
                ->first();
            if ($parent && $parent->conversation) {
                return $parent->conversation->thread_id;
            }
        }
        return $messageId ?: Str::uuid()->toString();
    }

    protected function fetchBody($conn, int $uid): array
    {
        $html = '';
        $text = '';

        $structure = imap_fetchstructure($conn, $uid, FT_UID);

        if (!isset($structure->parts)) {
            $body = imap_fetchbody($conn, $uid, '1', FT_UID);
            $encoding = $structure->encoding ?? 0;
            $decoded = $this->decodeBody($body, $encoding);
            if ($structure->subtype === 'HTML') {
                $html = $decoded;
            } else {
                $text = $decoded;
            }
        } else {
            foreach ($structure->parts as $idx => $part) {
                $partNum = $idx + 1;
                $body = imap_fetchbody($conn, $uid, (string) $partNum, FT_UID);
                $decoded = $this->decodeBody($body, $part->encoding ?? 0);
                if (strtoupper($part->subtype ?? '') === 'HTML') {
                    $html = $decoded;
                } elseif (strtoupper($part->subtype ?? '') === 'PLAIN') {
                    $text = $decoded;
                }
            }
        }

        return ['html' => $html, 'text' => $text];
    }

    protected function decodeBody(string $body, int $encoding): string
    {
        return match ($encoding) {
            3 => base64_decode($body),
            4 => quoted_printable_decode($body),
            default => $body,
        };
    }

    protected function parseAddresses(array $addresses): array
    {
        return array_map(fn($a) => [
            'email' => strtolower(($a->mailbox ?? '') . '@' . ($a->host ?? '')),
            'name' => isset($a->personal) ? imap_utf8($a->personal) : null,
        ], $addresses);
    }

    protected function validateSmtpHost(?string $host): void
    {
        $host = trim((string) $host);
        if ($host === '') {
            throw new \RuntimeException('SMTP host is not configured for this account. Please check the account settings.');
        }
        if (str_contains($host, ' ') || !preg_match('/^[a-zA-Z0-9._-]+$/', $host)) {
            throw new \RuntimeException('SMTP host "' . $host . '" is not a valid hostname. Please update the account SMTP host.');
        }
    }

    public function sendReply(UniboxConversation $conversation, array $data): UniboxMessage
    {
        $account = $conversation->account;

        // Use the latest message in the thread (regardless of direction) so multi-turn
        // replies stay threaded — not only the latest inbound one.
        $lastMessage = $conversation->messages()->whereNotNull('message_id')->latest()->first();
        $toEmail = $data['to'] ?? ($conversation->contact->email ?? '');
        $subject = 'Re: ' . preg_replace('/^(re:\s*)+/i', '', (string) $conversation->subject);
        $html = $data['body'] ?? '';
        $text = strip_tags($html);
        $inReplyTo = $lastMessage?->message_id ?: null;

        // Build References chain from prior In-Reply-To plus this parent.
        $referencesParts = [];
        if ($lastMessage && $lastMessage->headers && isset($lastMessage->headers['references'])) {
            $referencesParts[] = trim((string) $lastMessage->headers['references']);
        }
        if ($inReplyTo) {
            $referencesParts[] = $inReplyTo;
        }
        $references = trim(implode(' ', array_filter($referencesParts)));

        // Generate our own RFC Message-ID so we can persist it and match incoming replies later.
        $host = parse_url(config('app.url') ?: '', PHP_URL_HOST) ?: 'mailpurse.local';
        $localMessageId = '<' . bin2hex(random_bytes(16)) . '@' . $host . '>';

        $email = (new \Symfony\Component\Mime\Email())
            ->from(new \Symfony\Component\Mime\Address($account->email, $account->name ?? ''))
            ->to($toEmail)
            ->subject($subject)
            ->html($html)
            ->text($text);

        $attachments = $data['attachments'] ?? [];
        foreach ($attachments as $att) {
            $filePath = \Illuminate\Support\Facades\Storage::disk('local')->path($att['path']);
            if (file_exists($filePath)) {
                $email->attachFromPath($filePath, $att['name'], $att['mime'] ?? null);
            }
        }

        // Set Message-ID explicitly so it survives the transport.
        $email->getHeaders()->remove('Message-ID');
        $email->getHeaders()->addIdHeader('Message-ID', trim($localMessageId, '<>'));

        if ($inReplyTo) {
            $email->getHeaders()->addTextHeader('In-Reply-To', $inReplyTo);
            $email->getHeaders()->addTextHeader('References', $references ?: $inReplyTo);
        }

        // Gmail OAuth account: send via the Gmail API (no SMTP password available).
        if ($account->oauth_refresh_token) {
            // Only pass a Gmail threadId (hex string). RFC Message-IDs (contain < > or @)
            // from legacy conversations aren't valid Gmail thread IDs.
            $threadId = $conversation->thread_id;
            $isGmailThreadId = $threadId && !preg_match('/[<>@]/', $threadId);
            $this->sendViaGmailApi($account, $email, $isGmailThreadId ? $threadId : null);
        } else {
            $this->validateSmtpHost($account->smtp_host);
            $password = $this->safeDecrypt($account->smtp_password);

            if ($password === null) {
                throw new \RuntimeException('SMTP password is missing or invalid. Please re-enter the password in account settings.');
            }

            // EsmtpTransport $tls = true means implicit TLS (SSL, usually port 465).
            // STARTTLS (port 587) auto-negotiates when $tls = false.
            $useTls = $account->smtp_encryption === 'ssl';

            $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
                $account->smtp_host,
                $account->smtp_port,
                $useTls
            );
            $transport->setUsername($account->smtp_username);
            $transport->setPassword($password);

            (new \Symfony\Component\Mailer\Mailer($transport))->send($email);
        }

        $message = UniboxMessage::create([
            'conversation_id' => $conversation->id,
            'customer_id' => $conversation->customer_id,
            'message_id' => $localMessageId,
            'from_email' => $account->email,
            'from_name' => $account->name,
            'to' => [['email' => $toEmail, 'name' => null]],
            'subject' => $subject,
            'body_html' => $html,
            'body_text' => $text,
            'type' => 'outbound',
            'status' => 'sent',
            'in_reply_to' => $inReplyTo,
            'headers' => $references ? ['references' => $references] : null,
            'attachments' => !empty($attachments) ? $attachments : null,
            'sent_at' => now(),
        ]);

        $conversation->update(['last_message_at' => now()]);
        $conversation->increment('message_count');

        return $message;
    }

    protected function sendViaGmailApi(UniboxAccount $account, \Symfony\Component\Mime\Email $email, ?string $threadId = null): void
    {
        $accessToken = $this->getGmailAccessToken($account);
        $rawMime = $email->toString();
        $encoded = rtrim(strtr(base64_encode($rawMime), '+/', '-_'), '=');

        $payload = ['raw' => $encoded];
        if ($threadId) {
            $payload['threadId'] = $threadId;
        }

        $response = Http::withToken($accessToken)
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('Gmail send failed: ' . $response->body());
        }
    }

    public function sendEmail(array $data): void
    {
        $account  = $this->account ?? null;
        if (! $account) {
            throw new \RuntimeException('No account set on UniboxService.');
        }

        $email = (new \Symfony\Component\Mime\Email())
            ->from(new \Symfony\Component\Mime\Address($account->email, $account->name ?? ''))
            ->subject((string) ($data['subject'] ?? ''))
            ->html((string) ($data['body'] ?? ''))
            ->text(strip_tags((string) ($data['body'] ?? '')));

        foreach ((array) ($data['to'] ?? []) as $to) {
            $email->addTo($to);
        }
        foreach ((array) ($data['cc'] ?? []) as $cc) {
            $email->addCc($cc);
        }
        foreach ((array) ($data['bcc'] ?? []) as $bcc) {
            $email->addBcc($bcc);
        }

        foreach ((array) ($data['attachments'] ?? []) as $attachment) {
            if ($attachment instanceof UploadedFile) {
                $realPath = $attachment->getRealPath();
                if ($realPath && file_exists($realPath)) {
                    $email->attachFromPath($realPath, $attachment->getClientOriginalName(), $attachment->getMimeType());
                }
                continue;
            }

            if (is_array($attachment) && isset($attachment['path'])) {
                $filePath = Storage::disk('local')->path($attachment['path']);
                if (file_exists($filePath)) {
                    $email->attachFromPath($filePath, $attachment['name'] ?? basename($filePath), $attachment['mime'] ?? null);
                }
            }
        }

        if ($account->oauth_refresh_token) {
            $this->sendViaGmailApi($account, $email);
            return;
        }

        $this->validateSmtpHost($account->smtp_host);
        $password = $this->safeDecrypt($account->smtp_password);

        $useTls = $account->smtp_encryption === 'ssl';
        $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
            $account->smtp_host,
            $account->smtp_port,
            $useTls
        );
        $transport->setUsername($account->smtp_username);
        $transport->setPassword($password);

        (new \Symfony\Component\Mailer\Mailer($transport))->send($email);
    }

    protected function syncGmailApi(UniboxAccount $account): array
    {
        if ($account->status === 'paused') {
            return ['synced' => 0, 'errors' => []];
        }

        $synced = 0;
        $errors = [];

        try {
            $accessToken = $this->getGmailAccessToken($account);

            // Gmail's `after:` operator is reliable with Unix timestamps, but we step back
            // a couple of minutes to catch in-flight messages from the previous sync.
            $sinceTs = $account->last_synced_at
                ? $account->last_synced_at->subMinutes(5)->timestamp
                : now()->subDays(30)->timestamp;

            // Include inbox + sent + any other labeled items (excluding chats/spam/trash).
            $query = 'after:' . $sinceTs . ' -in:chats -in:spam -in:trash';

            $pageToken = null;
            $messageIds = [];
            $pages = 0;

            do {
                $params = ['q' => $query, 'maxResults' => 100];
                if ($pageToken) {
                    $params['pageToken'] = $pageToken;
                }

                $listResponse = Http::withToken($accessToken)
                    ->get('https://gmail.googleapis.com/gmail/v1/users/me/messages', $params);

                if (!$listResponse->successful()) {
                    throw new \RuntimeException('Gmail list failed: ' . $listResponse->body());
                }

                foreach ($listResponse->json('messages') ?? [] as $m) {
                    $messageIds[] = $m['id'];
                }

                $pageToken = $listResponse->json('nextPageToken');
                $pages++;
            } while ($pageToken && $pages < 10); // hard cap at ~1000 messages per sync

            foreach ($messageIds as $msgId) {
                try {
                    $this->processGmailMessage($accessToken, $msgId, $account);
                    $synced++;
                } catch (\Throwable $e) {
                    $errors[] = "Gmail msg {$msgId}: " . $e->getMessage();
                    Log::warning("UniBox Gmail sync error #{$account->id}", ['id' => $msgId, 'error' => $e->getMessage()]);
                }
            }

            $account->update([
                'last_synced_at' => now(),
                'last_error' => null,
                'status' => 'active',
            ]);
        } catch (\Throwable $e) {
            $account->update(['status' => 'error', 'last_error' => $e->getMessage()]);
            $errors[] = $e->getMessage();
            Log::error("UniBox Gmail API sync failed #{$account->id}", ['error' => $e->getMessage()]);
        }

        return ['synced' => $synced, 'errors' => $errors];
    }

    protected function getGmailAccessToken(UniboxAccount $account): string
    {
        if ($account->oauth_token && $account->oauth_expires_at && $account->oauth_expires_at->isFuture()) {
            try {
                return decrypt($account->oauth_token);
            } catch (\Throwable $e) {
                // fall through to refresh
            }
        }

        $refreshToken = $this->safeDecrypt($account->oauth_refresh_token);
        $clientId = Setting::get('google_client_id');
        $clientSecret = Setting::get('google_client_secret');

        if (!$clientId || !$clientSecret) {
            throw new \RuntimeException('Gmail OAuth is not configured (missing client credentials).');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Gmail token refresh failed: ' . $response->body());
        }

        $data = $response->json();
        $accessToken = $data['access_token'] ?? null;
        $expiresIn = (int) ($data['expires_in'] ?? 3600);

        if (!$accessToken) {
            throw new \RuntimeException('Gmail token refresh returned no access_token.');
        }

        $account->update([
            'oauth_token' => encrypt($accessToken),
            'oauth_expires_at' => now()->addSeconds(max(60, $expiresIn - 60)),
        ]);

        return $accessToken;
    }

    protected function processGmailMessage(string $accessToken, string $id, UniboxAccount $account): void
    {
        $response = Http::withToken($accessToken)
            ->get("https://gmail.googleapis.com/gmail/v1/users/me/messages/{$id}", [
                'format' => 'full',
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Gmail fetch failed: ' . $response->body());
        }

        $data = $response->json();
        $payload = $data['payload'] ?? [];
        $labelIds = $data['labelIds'] ?? [];
        $category = $this->mapGmailCategory($labelIds);
        $isSent = in_array('SENT', $labelIds, true);
        $isInbox = in_array('INBOX', $labelIds, true);
        $isUnread = in_array('UNREAD', $labelIds, true);
        $headers = collect($payload['headers'] ?? [])->keyBy(fn($h) => strtolower($h['name']));

        $messageId = trim($headers->get('message-id')['value'] ?? '');
        $inReplyTo = trim($headers->get('in-reply-to')['value'] ?? '');
        $gmailMessageId = $data['id'] ?? null;
        $gmailThreadId = $data['threadId'] ?? null;

        // Dedup against either the Gmail id or the RFC Message-ID — when we send a reply we
        // store our locally-generated RFC Message-ID, and Gmail later returns the same message
        // with that same RFC Message-ID, so we must check both forms.
        $existing = UniboxMessage::query()
            ->where(function ($q) use ($gmailMessageId, $messageId) {
                if ($gmailMessageId) {
                    $q->orWhere('message_id', $gmailMessageId);
                }
                if ($messageId) {
                    $q->orWhere('message_id', $messageId);
                }
            })
            ->exists();
        if ($existing) {
            return;
        }

        // Prefer the RFC Message-ID for storage so reply-resolution by In-Reply-To works.
        $dedupId = $messageId ?: $gmailMessageId;

        $fromHeader = $headers->get('from')['value'] ?? '';
        $toHeaderRaw = $headers->get('to')['value'] ?? '';
        [$fromEmail, $fromName] = $this->parseAddressHeader($fromHeader);
        $subject = $headers->get('subject')['value'] ?? '(no subject)';

        $body = $this->extractGmailBody($payload);

        // Received timestamp from Gmail internalDate (epoch ms), otherwise now.
        $receivedAt = isset($data['internalDate'])
            ? \Carbon\Carbon::createFromTimestampMs((int) $data['internalDate'])
            : now();

        // For the conversation's contact we use the "other side" of the conversation.
        // For inbound messages that's the sender; for sent messages it's the first recipient.
        if ($isSent) {
            [$contactEmail, $contactName] = $this->parseAddressHeader(explode(',', $toHeaderRaw)[0] ?? '');
        } else {
            $contactEmail = $fromEmail;
            $contactName = $fromName;
        }

        if (!$contactEmail) {
            $contactEmail = $fromEmail ?: 'unknown@unknown';
        }

        $contact = UniboxContact::firstOrCreate(
            ['customer_id' => $account->customer_id, 'email' => $contactEmail],
            ['name' => $contactName, 'first_seen_at' => now()]
        );
        $contact->update(['last_reply_at' => $receivedAt]);

        // Prefer Gmail's threadId for robust grouping.
        $threadId = $gmailThreadId ?: $this->resolveThreadId($messageId, $inReplyTo, $account->customer_id, $account->id);
        $conversation = UniboxConversation::where('customer_id', $account->customer_id)
            ->where('thread_id', $threadId)
            ->first();

        $folder = $isSent && !$isInbox ? 'sent' : 'inbox';

        if (!$conversation) {
            $conversation = UniboxConversation::create([
                'customer_id' => $account->customer_id,
                'account_id' => $account->id,
                'contact_id' => $contact->id,
                'subject' => $subject,
                'thread_id' => $threadId,
                'folder' => $folder,
                'category' => $category,
                'status' => 'open',
                'is_read' => $isSent ? true : !$isUnread,
                'last_message_at' => $receivedAt,
                'sla_deadline' => now()->addHours(2),
            ]);
            $contact->increment('conversation_count');
        } else {
            $update = [
                'last_message_at' => $receivedAt,
                'category' => $category ?: $conversation->category,
                'account_id' => $account->id,
            ];
            if (!$isSent) {
                $update['is_read'] = !$isUnread;
                // Only move back to inbox if the message is still in inbox.
                if ($isInbox && $conversation->folder === 'sent') {
                    $update['folder'] = 'inbox';
                }
            }
            $conversation->update($update);
        }

        $to = $this->parseAddressListHeader($toHeaderRaw);
        $cc = $this->parseAddressListHeader($headers->get('cc')['value'] ?? '');

        UniboxMessage::create([
            'conversation_id' => $conversation->id,
            'customer_id' => $account->customer_id,
            'message_id' => $dedupId,
            'from_email' => $fromEmail,
            'from_name' => $fromName,
            'to' => $to,
            'cc' => $cc,
            'subject' => $subject,
            'body_html' => $body['html'],
            'body_text' => $body['text'],
            'type' => $isSent ? 'outbound' : 'inbound',
            'status' => $isSent ? 'sent' : 'received',
            'in_reply_to' => $inReplyTo,
            'received_at' => $isSent ? null : $receivedAt,
            'sent_at' => $isSent ? $receivedAt : null,
        ]);

        $conversation->increment('message_count');
    }

    protected function mapGmailCategory(array $labelIds): ?string
    {
        $map = [
            'CATEGORY_PERSONAL'   => 'primary',
            'CATEGORY_PROMOTIONS' => 'promotions',
            'CATEGORY_UPDATES'    => 'updates',
            'CATEGORY_SOCIAL'     => 'social',
            'CATEGORY_FORUMS'     => 'social',
        ];
        foreach ($labelIds as $label) {
            if (isset($map[$label])) {
                return $map[$label];
            }
        }
        return null;
    }

    protected function extractGmailBody(array $payload): array
    {
        $html = '';
        $text = '';

        $walk = function ($part) use (&$walk, &$html, &$text) {
            $mime = $part['mimeType'] ?? '';
            $data = $part['body']['data'] ?? null;

            if ($data) {
                $decoded = base64_decode(strtr($data, '-_', '+/'));
                if ($mime === 'text/html' && $html === '') {
                    $html = $decoded;
                } elseif ($mime === 'text/plain' && $text === '') {
                    $text = $decoded;
                }
            }

            foreach ($part['parts'] ?? [] as $child) {
                $walk($child);
            }
        };

        $walk($payload);

        return ['html' => $html, 'text' => $text];
    }

    protected function parseAddressHeader(string $header): array
    {
        $header = trim($header);
        if ($header === '') {
            return ['', null];
        }

        if (preg_match('/^(.*)<([^>]+)>$/', $header, $m)) {
            return [strtolower(trim($m[2])), trim($m[1], " \t\"")];
        }

        return [strtolower($header), null];
    }

    protected function parseAddressListHeader(string $header): array
    {
        if (trim($header) === '') {
            return [];
        }

        return array_map(function ($part) {
            [$email, $name] = $this->parseAddressHeader($part);
            return ['email' => $email, 'name' => $name];
        }, array_filter(array_map('trim', explode(',', $header))));
    }

    public function testImapConnection(array $config): bool
    {
        if (!function_exists('imap_open')) {
            throw new \RuntimeException('PHP IMAP extension is not installed.');
        }

        $encMap = ['ssl' => '/ssl', 'tls' => '/tls', 'none' => '/notls'];
        $enc = $encMap[$config['imap_encryption']] ?? '/ssl';
        $mailbox = "{{$config['imap_host']}:{$config['imap_port']}/imap{$enc}}INBOX";

        $conn = @imap_open($mailbox, $config['imap_username'], $config['imap_password'], 0, 1);
        if (!$conn) {
            throw new \RuntimeException('Connection failed: ' . imap_last_error());
        }
        imap_close($conn);
        return true;
    }
}
