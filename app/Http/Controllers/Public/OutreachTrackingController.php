<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\OutreachLead;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class OutreachTrackingController extends Controller
{
    /**
     * 1×1 transparent GIF returned regardless of outcome so missing/invalid
     * tracking IDs never break email rendering.
     */
    private const PIXEL_GIF_BASE64 = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    public function open(Request $request, string $uuid): Response
    {
        try {
            /** @var OutreachLead|null $lead */
            $lead = OutreachLead::where('uuid', $uuid)->first();

            if ($lead && !in_array($lead->status, ['unsubscribed', 'bounced'], true)) {
                $update = ['last_activity_at' => Carbon::now()];

                // Only escalate the status the first time we see an open. Don't
                // overwrite stronger signals (clicked / replied).
                if (!in_array($lead->status, ['opened', 'clicked', 'replied'], true)) {
                    $update['status'] = 'opened';
                }

                $lead->forceFill($update)->save();
            }
        } catch (\Throwable $e) {
            Log::warning('[Outreach] open tracking failed', ['uuid' => $uuid, 'error' => $e->getMessage()]);
        }

        return response(base64_decode(self::PIXEL_GIF_BASE64), 200)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function click(Request $request, string $uuid, string $url): RedirectResponse
    {
        $originalUrl = $this->decodeRedirectUrl($url);

        try {
            /** @var OutreachLead|null $lead */
            $lead = OutreachLead::where('uuid', $uuid)->first();

            if ($lead && !in_array($lead->status, ['unsubscribed', 'bounced'], true)) {
                $update = ['last_activity_at' => Carbon::now()];
                if (!in_array($lead->status, ['clicked', 'replied'], true)) {
                    $update['status'] = 'clicked';
                }
                $lead->forceFill($update)->save();
            }
        } catch (\Throwable $e) {
            Log::warning('[Outreach] click tracking failed', ['uuid' => $uuid, 'error' => $e->getMessage()]);
        }

        return redirect($originalUrl ?: url('/'));
    }

    public function showUnsubscribe(string $uuid)
    {
        $lead = OutreachLead::where('uuid', $uuid)->first();

        return response()->view('public.outreach.unsubscribe', [
            'lead'         => $lead,
            'alreadyDone'  => $lead && $lead->status === 'unsubscribed',
        ]);
    }

    public function unsubscribe(Request $request, string $uuid)
    {
        try {
            $lead = OutreachLead::where('uuid', $uuid)->first();
            if ($lead && $lead->status !== 'unsubscribed') {
                $lead->forceFill([
                    'status'                => 'unsubscribed',
                    'next_send_at'          => null,
                    'sequence_completed_at' => Carbon::now(),
                    'last_activity_at'      => Carbon::now(),
                ])->save();
            }
        } catch (\Throwable $e) {
            Log::warning('[Outreach] unsubscribe failed', ['uuid' => $uuid, 'error' => $e->getMessage()]);
        }

        return response()->view('public.outreach.unsubscribe', [
            'lead'        => $lead ?? null,
            'alreadyDone' => true,
        ]);
    }

    private function decodeRedirectUrl(?string $encoded): ?string
    {
        if (!$encoded) {
            return null;
        }

        $encoded = strtr($encoded, '-_', '+/');
        $encoded = str_pad($encoded, (int) ceil(strlen($encoded) / 4) * 4, '=', STR_PAD_RIGHT);

        $decoded = base64_decode($encoded, true);
        if (!$decoded) {
            return null;
        }

        $decoded = filter_var($decoded, FILTER_VALIDATE_URL);
        if (!$decoded) {
            return null;
        }

        $scheme = parse_url($decoded, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        return $decoded;
    }
}
