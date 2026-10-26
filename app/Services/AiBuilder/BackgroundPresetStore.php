<?php

namespace App\Services\AiBuilder;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class BackgroundPresetStore
{
    private const FILENAME = 'ai-builder/background-presets.json';

    public static function path(): string
    {
        return Storage::disk('local')->path(self::FILENAME);
    }

    public static function all(): array
    {
        $path = self::path();

        if (! File::exists($path)) {
            return [];
        }

        $contents = File::get($path);
        $decoded = json_decode($contents, true);

        return is_array($decoded) ? ($decoded['presets'] ?? []) : [];
    }

    public static function active(): array
    {
        return array_values(array_filter(self::all(), fn ($p) => $p['is_active'] ?? true));
    }

    public static function find(string $presetId): ?array
    {
        foreach (self::all() as $preset) {
            if (($preset['preset_id'] ?? null) === $presetId) {
                return $preset;
            }
        }

        return null;
    }

    public static function save(array $presets): void
    {
        File::ensureDirectoryExists(dirname(self::path()));
        File::put(self::path(), json_encode(['presets' => array_values($presets)], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public static function update(array $preset): void
    {
        $presets = self::all();
        $id = $preset['preset_id'] ?? null;
        $found = false;

        foreach ($presets as $index => $existing) {
            if (($existing['preset_id'] ?? null) === $id) {
                $presets[$index] = $preset;
                $found = true;
                break;
            }
        }

        if (! $found) {
            $presets[] = $preset;
        }

        self::save($presets);
    }

    public static function delete(string $presetId): void
    {
        $presets = array_values(array_filter(self::all(), fn ($p) => ($p['preset_id'] ?? null) !== $presetId));
        self::save($presets);
    }

    public static function catalogForPrompt(): string
    {
        $presets = self::active();

        $list = array_map(fn ($p) => [
            'preset_id' => $p['preset_id'] ?? null,
            'name' => $p['name'] ?? null,
            'category' => $p['category'] ?? null,
            'description' => $p['description'] ?? null,
            'background' => $p['config'] ?? null,
        ], $presets);

        return json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
