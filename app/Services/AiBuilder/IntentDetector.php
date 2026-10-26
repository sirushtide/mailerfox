<?php

namespace App\Services\AiBuilder;

use App\Services\AI\AiProviderRouter;

class IntentDetector
{
    private const BUILD_RE = '/\b(build|make|create|generate|design|develop|construct|update|change|edit|adjust|modify|replace|remove|delete|fix|add|insert|redesign|revamp|improve|polish)\b|landing\s*page|website|web\s*page|dashboard|portfolio|blog|pricing\s*page|resume|ecommerce|e-commerce|storefront|online\s*store|hero\s*section|faq\s*section|contact\s*section|about\s*section|team\s*section|testimonial\s*section|navbar|footer/i';

    private const GREETING_RE = '/^(hi|hello|hey|hola|yo|sup|salam|assalamualaikum|assalamu\s*alaikum|namaste|greetings|good\s+(morning|afternoon|evening|night))\b/i';

    private const IDENTITY_RE = '/\b(which|what)\s+(model|ai|llm)\b|what\s+can\s+you\s+do\b|who\s+are\s+you\b|are\s+you\s+(a|an)?\s*(human|ai|robot|gpt|bot|claude|agent)\b|how\s+(do\s+you\s+work|are\s+you\s+built)\b/i';

    private const GUIDANCE_RE = '/^(how\s+(do|to|can|would|should|could)|where\s+can\s+i\s+(find|get|see|locate)|what\s+(is|are|does|do)\b|explain\b|tell\s+me\b|why\b)/i';

    public function __construct(
        protected AiProviderRouter $router,
    ) {
    }

    /**
     * Classify a builder prompt as 'build' or 'chat'.
     *
     * @return array{intent: 'build'|'chat', message: ?string}
     */
    public function detect(string $prompt, ?string $modelOverride = null, ?string $resolvedModel = null): array
    {
        $text = trim($prompt);
        if ($text === '') {
            return ['intent' => 'build', 'message' => null];
        }

        $rule = $this->deterministic($text);
        if ($rule !== null) {
            return $rule;
        }

        // Ambiguous input — let the model decide.
        $model = $resolvedModel ?: ModelRouter::resolve('worker', $modelOverride);
        try {
            if ($this->router->hasCredentials($model)) {
                $system = implode("\n", [
                    'You classify whether a website builder user request is a BUILD request or a CHAT request.',
                    '',
                    'BUILD: the user explicitly asks to build, create, generate, design, add, update, change, or style a page, section, layout, component, or website.',
                    'CHAT: greetings, identity questions ("which model are you?"), "how do I...", "where can I find...", explanations, questions about the current project, help requests, or anything that is not a concrete build or edit instruction.',
                    '',
                    'Respond ONLY with JSON: {"intent": "build"} or {"intent": "chat"}',
                ]);
                $result = $this->router->generateJsonObject($model, $system, 'User request: ' . $text, 200, 'Intent Classifier');
                $json = is_array($result['json'] ?? null) ? $result['json'] : null;
                $intent = is_string($json['intent'] ?? null) ? strtolower(trim($json['intent'])) : '';
                if ($intent === 'chat') {
                    return ['intent' => 'chat', 'message' => null];
                }
            }
        } catch (\Throwable $e) {
            // fall through to default build
        }

        return ['intent' => 'build', 'message' => null];
    }

    /**
     * Generate a conversational reply for a chat intent. Uses the AI model so the
     * response is natural and contextual; falls back to a short static reply only
     * when the primary model call fails or is unavailable.
     */
    public function chatReply(string $prompt, ?string $model = null, bool $supabaseConnected = false): string
    {
        $text = trim($prompt);
        $model = $model ?: ModelRouter::resolve('worker');

        try {
            if ($this->router->hasCredentials($model)) {
                $system = implode("\n", [
                    'You are the conversational AI assistant inside a website builder, powered by the ' . $model . ' model.',
                    'You help users plan, build, and understand their websites.',
                    'Be warm, friendly, and concise (2-4 sentences).',
                    'If the user greets you, greet them back naturally and ask what they would like to build.',
                    'If they ask about you or the model, answer naturally.',
                    'If they ask how to do something in the builder, give brief, practical guidance.',
                    $supabaseConnected
                        ? 'Supabase is already connected for this app. If they ask how to connect Supabase, say it is already connected and ask whether they want to integrate their app data with Supabase. Do NOT recommend npm install, .env files, Vite configuration, or manual source-code edits.'
                        : 'MailPurse has a built-in Supabase integration. If they ask how to connect Supabase, do NOT recommend npm install, .env files, Vite configuration, or manual source-code edits. Tell them to open the app dashboard, go to Settings → Supabase, enter their Supabase Project URL and publishable/anon key, and connect it there. Mention that the builder injects the credentials into the generated app automatically.',
                    'If they ask about Google or Apple login setup, tell them to open the Auth tab after connecting Supabase, enable the provider, enter the provider credentials, save the configuration, and also enable the provider in Supabase Authentication → Providers because Supabase controls the OAuth provider.',
                    'Do NOT invent project files, generate code, or claim that changes were made.',
                ]);

                $result = $this->router->generateChat(
                    $model,
                    [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $text],
                    ],
                    300
                );

                $reply = is_array($result) && ($result['success'] ?? false) && is_string($result['text'] ?? null)
                    ? trim($result['text'])
                    : '';

                if ($reply !== '') {
                    return $reply;
                }
            }
        } catch (\Throwable $e) {
            // fall back to the static reply below
        }

        return $this->staticReply($text, $model, $supabaseConnected);
    }

    private function staticReply(string $text, ?string $model = null, bool $supabaseConnected = false): string
    {
        if ($supabaseConnected && preg_match('/\b(connect|setup|set up|configure|use)\b.*\bsupabase\b|\bsupabase\b.*\b(connect|setup|set up|configure)\b/i', $text)) {
            return 'Supabase is already connected to this app. Do you want to integrate your app data with Supabase?';
        }

        if (preg_match('/\b(connect|setup|set up|configure|use)\b.*\bsupabase\b|\bsupabase\b.*\b(connect|setup|set up|configure)\b/i', $text)) {
            return 'Open the app dashboard and go to Settings → Supabase. Enter your Supabase Project URL and publishable/anon key, then click Connect. You do not need to install npm packages or create a .env file; MailPurse injects the connected credentials into the generated app automatically.';
        }

        if (preg_match('/\b(google|apple)\b.*\b(login|oauth|auth|sign in|signin)\b|\b(login|oauth|auth|sign in|signin)\b.*\b(google|apple)\b/i', $text)) {
            return 'First connect Supabase from the app dashboard under Settings → Supabase. Then open the Auth tab, enable Google or Apple, enter the provider details, and save. You must also enable and configure that provider in Supabase under Authentication → Providers.';
        }

        if ($model && preg_match('/\b(which|what)\s+(model|ai|llm)\b/i', $text)) {
            return 'I\'m powered by the ' . $model . ' model. I can generate landing pages, build new sections, update existing code, and answer questions about your project. What would you like to work on?';
        }

        if (preg_match('/\bwho\s+are\s+you\b/i', $text)) {
            return 'I\'m your AI website builder. I can generate full landing pages, add or edit sections, explain the generated code, and help you plan improvements. What would you like to do?';
        }

        return 'Got it — I\'m here to help you build and refine your website. I can generate a landing page, add or update sections, explain how the code works, or plan a new feature. Just tell me what you\'d like to do.';
    }

    private function deterministic(string $text): ?array
    {
        $build = (bool) preg_match(self::BUILD_RE, $text);
        $greeting = (bool) preg_match(self::GREETING_RE, $text);
        $identity = (bool) preg_match(self::IDENTITY_RE, $text);
        $guidance = (bool) preg_match(self::GUIDANCE_RE, $text);
        $question = str_contains($text, '?') || (bool) preg_match('/^(how|what|where|when|which|why|who|can|could|would|should|do|does|is|are|will|shall)\b/i', $text);
        $wordCount = count(preg_split('/\s+/', $text));

        if ($guidance) {
            return ['intent' => 'chat', 'message' => null];
        }
        if (($greeting || $identity) && ! $build) {
            return ['intent' => 'chat', 'message' => null];
        }
        if ($question && ! $build) {
            return ['intent' => 'chat', 'message' => null];
        }
        if ($wordCount <= 3 && ! $build) {
            return ['intent' => 'chat', 'message' => null];
        }
        if ($build) {
            return ['intent' => 'build', 'message' => null];
        }
        if ($wordCount <= 8) {
            return ['intent' => 'chat', 'message' => null];
        }

        return null;
    }
}
