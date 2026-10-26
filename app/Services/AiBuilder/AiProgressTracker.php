<?php

namespace App\Services\AiBuilder;

use Illuminate\Support\Facades\Cache;

class AiProgressTracker
{
    private const PREFIX = 'ai_job:';
    private const TTL_SECONDS = 3600;

    public static function set(string $jobId, array $data): void
    {
        $key = self::PREFIX . $jobId;
        $existing = Cache::get($key, []);

        // Accumulate distinct progress steps so the frontend can show each one
        if (isset($data['message']) && ($data['message'] !== '')) {
            $lastMessage = $existing['message'] ?? '';
            $lastFile = $existing['file'] ?? null;
            $lastDescription = $existing['description'] ?? null;
            $newMessage = $data['message'];
            $newFile = $data['file'] ?? null;
            $newDescription = $data['description'] ?? null;
            if ($newMessage !== $lastMessage || $newFile !== $lastFile || $newDescription !== $lastDescription) {
                $steps = $existing['steps'] ?? [];
                $steps[] = [
                    'message' => $newMessage,
                    'file' => $newFile,
                    'description' => $newDescription,
                    'action' => $data['action'] ?? 'working',
                    'step' => $data['step'] ?? ($existing['step'] ?? 0),
                    'total' => $data['total'] ?? ($existing['total'] ?? 0),
                    'at' => now()->toDateTimeString(),
                ];
                $data['steps'] = $steps;
            }
        }

        Cache::put($key, array_merge($existing, $data), self::TTL_SECONDS);
    }

    public static function get(string $jobId): array
    {
        return Cache::get(self::PREFIX . $jobId, []);
    }

    /**
     * Return only blocks added after a given count, plus the current status
     * snapshot. Used by SSE consumers to efficiently poll for new blocks
     * without transferring the full accumulated array each time.
     */
    public static function getSince(string $jobId, int $lastBlockCount): array
    {
        $data = Cache::get(self::PREFIX . $jobId, []);
        $blocks = is_array($data['blocks'] ?? null) ? $data['blocks'] : [];

        return [
            'status' => $data['status'] ?? 'unknown',
            'step' => $data['step'] ?? 0,
            'total' => $data['total'] ?? 0,
            'message' => $data['message'] ?? '',
            'file' => $data['file'] ?? null,
            'action' => $data['action'] ?? 'working',
            'description' => $data['description'] ?? null,
            'blocks' => array_slice($blocks, $lastBlockCount),
            'block_count' => count($blocks),
            'result' => is_array($data['result'] ?? null) ? $data['result'] : null,
        ];
    }

    /**
     * Append a typed block (thought | narration | tool | summary) to the job's
     * block stream. This powers the Claude Code–style activity view on the
     * frontend; the flat $steps[] accumulation above is kept for compatibility.
     */
    public static function pushBlock(string $jobId, array $block): void
    {
        if ($jobId === '') {
            return;
        }

        $key = self::PREFIX . $jobId;
        $existing = Cache::get($key, []);
        $blocks = is_array($existing['blocks'] ?? null) ? $existing['blocks'] : [];

        $blocks[] = array_merge([
            'type'     => 'narration',
            'content'  => '',
            'action'   => null,
            'file'     => null,
            'detail'   => null,
            'duration' => null,
            'at'       => now()->toDateTimeString(),
        ], array_filter($block, static fn ($value) => $value !== null));

        Cache::put($key, array_merge($existing, ['blocks' => $blocks]), self::TTL_SECONDS);
    }

    /**
     * Start a fresh block stream for a job so stale blocks never bleed into a
     * new generation that reuses the same session id.
     */
    public static function resetBlocks(string $jobId): void
    {
        if ($jobId === '') {
            return;
        }

        $key = self::PREFIX . $jobId;
        $existing = Cache::get($key, []);
        Cache::put($key, array_merge($existing, ['blocks' => []]), self::TTL_SECONDS);
    }

    public static function forget(string $jobId): void
    {
        Cache::forget(self::PREFIX . $jobId);
    }

    public static function cancel(string $jobId): void
    {
        self::set($jobId, ['cancelled' => true]);
    }

    public static function isCancelled(string $jobId): bool
    {
        return (bool) (self::get($jobId)['cancelled'] ?? false);
    }

    public static function status(string $jobId): string
    {
        return (string) (self::get($jobId)['status'] ?? 'unknown');
    }

    public static function message(string $jobId): string
    {
        return (string) (self::get($jobId)['message'] ?? '');
    }

    public static function result(string $jobId): ?array
    {
        $data = self::get($jobId);
        return is_array($data['result'] ?? null) ? $data['result'] : null;
    }

    public static function setStatus(string $jobId, string $status, string $message = ''): void
    {
        self::set($jobId, [
            'status' => $status,
            'message' => $message,
            'updated_at' => now()->toDateTimeString(),
        ]);
    }

    public static function setDone(string $jobId, array $result): void
    {
        self::set($jobId, [
            'status' => 'done',
            'message' => 'Done',
            'result' => $result,
            'updated_at' => now()->toDateTimeString(),
        ]);
    }

    public static function setError(string $jobId, string $error): void
    {
        self::set($jobId, [
            'status' => 'error',
            'message' => $error ?: 'Something went wrong.',
            'file' => null,
            'updated_at' => now()->toDateTimeString(),
        ]);
    }

    public static function progressMessages(): array
    {
        return [
            'queued' => 'Planning your design…',
            'enhancing' => 'Thinking…',
            'orchestrating' => 'Mapping sections…',
            'generating' => 'Designing layout…',
            'validating' => 'Polishing details…',
            'assembling' => 'Assembling page…',
            'done' => 'Done',
            'error' => 'Something went wrong.',
        ];
    }

    /**
     * Honest progress messages that match actual AI work (single-shot generation).
     */
    public static function realisticMessages(): array
    {
        return [
            ['icon' => '🧠', 'title' => 'Understanding your request…', 'desc' => 'Reading your prompt and screenshot to understand intent.'],
            ['icon' => '🎨', 'title' => 'Designing your page…', 'desc' => 'Building layout, content, and visual elements.'],
            ['icon' => '⚡', 'title' => 'Finalizing output…', 'desc' => 'Polishing HTML and preparing for preview.'],
        ];
    }
}
