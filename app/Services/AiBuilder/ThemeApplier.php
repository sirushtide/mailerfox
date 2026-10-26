<?php

namespace App\Services\AiBuilder;

/**
 * Random prebuilt theme applier.
 *
 * Replaces the old keyword-based FineTuner. It picks one of the built-in
 * style presets at random and applies its palette/typography to the Vite
 * sandbox files before build.
 */
class ThemeApplier
{
    private static array $presets = [
        [
            'id' => 'lime_pop',
            'keywords' => ['lime', 'neon', 'high contrast', 'off-white', 'black and white', 'bold', 'graphic'],
            'palette' => [
                'primary' => '#D9FF03',
                'secondary' => '#000000',
                'background' => '#F2EFEB',
                'foreground' => '#000000',
                'muted' => '#A6A8AB',
                'mutedForeground' => '#4B4D4F',
                'border' => '#000000',
                'card' => '#FFFFFF',
                'accent' => '#D9FF03',
            ],
            'typography' => [
                'heading' => "'Space Grotesk', sans-serif",
                'body' => "'Inter', system-ui, sans-serif",
                'googleFonts' => ['Space Grotesk:700', 'Inter:400,500,600,700'],
            ],
            'radius' => 'rounded-none',
            'buttonStyle' => ['radius' => 'rounded-none'],
        ],
        [
            'id' => 'cyan_storm',
            'keywords' => ['cyan', 'teal', 'storm', 'dark', 'tech', 'rounded'],
            'palette' => [
                'primary' => '#02EFF0',
                'secondary' => '#0F282F',
                'background' => '#0F282F',
                'foreground' => '#FFFFFF',
                'muted' => '#2A3F45',
                'mutedForeground' => '#9FB0B5',
                'border' => '#02EFF0',
                'card' => '#14343C',
                'accent' => '#02EFF0',
            ],
            'typography' => [
                'heading' => "'Inter', sans-serif",
                'body' => "'Inter', system-ui, sans-serif",
                'googleFonts' => ['Inter:400,500,600,700,800', 'JetBrains+Mono:400,500'],
            ],
            'radius' => 'rounded-full',
            'buttonStyle' => ['radius' => 'rounded-full'],
        ],
        [
            'id' => 'cream_manela',
            'keywords' => ['cream', 'purple', 'navy', 'editorial', 'warm', 'soft'],
            'palette' => [
                'primary' => '#152A32',
                'secondary' => '#C9B6FF',
                'background' => '#FFFCEE',
                'foreground' => '#152A32',
                'muted' => '#F3ECD4',
                'mutedForeground' => '#5A6A72',
                'border' => '#E6DFC8',
                'card' => '#FFFFFF',
                'accent' => '#C9B6FF',
            ],
            'typography' => [
                'heading' => "'Playfair Display', Georgia, serif",
                'body' => "'Inter', system-ui, sans-serif",
                'googleFonts' => ['Playfair+Display:700,800', 'Inter:400,500,600,700'],
            ],
            'radius' => 'rounded-2xl',
            'buttonStyle' => ['radius' => 'rounded-2xl'],
        ],
        [
            'id' => 'bright_fresh',
            'keywords' => ['fresh', 'green', 'clean', 'light', 'startup', 'airy'],
            'palette' => [
                'primary' => '#00D47F',
                'secondary' => '#91FF00',
                'background' => '#FFFFFF',
                'foreground' => '#0F172A',
                'muted' => '#E6F9F1',
                'mutedForeground' => '#475569',
                'border' => '#00D47F',
                'card' => '#FFFFFF',
                'accent' => '#91FF00',
            ],
            'typography' => [
                'heading' => "'Manrope', sans-serif",
                'body' => "'Inter', system-ui, sans-serif",
                'googleFonts' => ['Manrope:400,500,600,700,800', 'Inter:400,500,600,700'],
            ],
            'radius' => 'rounded-full',
            'buttonStyle' => ['radius' => 'rounded-full'],
        ],
        [
            'id' => 'charcoal_citrus',
            'keywords' => ['charcoal', 'orange', 'citrus', 'energy', 'bold', 'contrast'],
            'palette' => [
                'primary' => '#FD572A',
                'secondary' => '#201F1F',
                'background' => '#F2EFEB',
                'foreground' => '#201F1F',
                'muted' => '#E8E3DE',
                'mutedForeground' => '#6B6864',
                'border' => '#201F1F',
                'card' => '#FFFFFF',
                'accent' => '#FD572A',
            ],
            'typography' => [
                'heading' => "'Oswald', sans-serif",
                'body' => "'Inter', system-ui, sans-serif",
                'googleFonts' => ['Oswald:500,600,700', 'Inter:400,500,600,700'],
            ],
            'radius' => 'rounded-lg',
            'buttonStyle' => ['radius' => 'rounded-lg'],
        ],
        [
            'id' => 'violet_noir',
            'keywords' => ['violet', 'noir', 'dark', 'editorial', 'purple', 'luxury'],
            'palette' => [
                'primary' => '#695AC6',
                'secondary' => '#000000',
                'background' => '#000000',
                'foreground' => '#F2EFEB',
                'muted' => '#201F1F',
                'mutedForeground' => '#A6A8AB',
                'border' => '#695AC6',
                'card' => '#201F1F',
                'accent' => '#695AC6',
            ],
            'typography' => [
                'heading' => "'Space Grotesk', sans-serif",
                'body' => "'Inter', system-ui, sans-serif",
                'googleFonts' => ['Space+Grotesk:400,500,600,700', 'Inter:400,500,600,700'],
            ],
            'radius' => 'rounded-none',
            'buttonStyle' => ['radius' => 'rounded-none'],
        ],
        [
            'id' => 'teal_minimal',
            'keywords' => ['teal', 'minimal', 'clean', 'system', 'simple', 'modern'],
            'palette' => [
                'primary' => '#0F282F',
                'secondary' => '#02EFF0',
                'background' => '#FFFFFF',
                'foreground' => '#0F282F',
                'muted' => '#F1F5F9',
                'mutedForeground' => '#64748B',
                'border' => '#E2E8F0',
                'card' => '#FFFFFF',
                'accent' => '#02EFF0',
            ],
            'typography' => [
                'heading' => "'Inter', sans-serif",
                'body' => "'Inter', system-ui, sans-serif",
                'googleFonts' => ['Inter:400,500,600,700,800'],
            ],
            'radius' => 'rounded-xl',
            'buttonStyle' => ['radius' => 'rounded-xl'],
        ],
        [
            'id' => 'sunset_warmth',
            'keywords' => ['sunset', 'warm', 'soft', 'premium', 'gold', 'calm'],
            'palette' => [
                'primary' => '#152A32',
                'secondary' => '#C9B6FF',
                'background' => '#FFF0B5',
                'foreground' => '#152A32',
                'muted' => '#FDF3D1',
                'mutedForeground' => '#6B6B6B',
                'border' => '#E6D9A8',
                'card' => '#FFFFFF',
                'accent' => '#C9B6FF',
            ],
            'typography' => [
                'heading' => "'Merriweather', Georgia, serif",
                'body' => "'Inter', system-ui, sans-serif",
                'googleFonts' => ['Merriweather:700,900', 'Inter:400,500,600,700'],
            ],
            'buttonStyle' => ['radius' => 'rounded-0'],
        ],
        [
            'id' => 'electric_contrast',
            'keywords' => ['electric', 'contrast', 'multi-color', 'multi color', 'bold', 'graphic'],
            'palette' => [
                'primary' => '#D9FF03',
                'secondary' => '#FD572A',
                'background' => '#000000',
                'foreground' => '#FFFFFF',
                'muted' => '#201F1F',
                'mutedForeground' => '#A6A8AB',
                'border' => '#FFFFFF',
                'card' => '#201F1F',
                'accent' => '#695AC6',
            ],
            'typography' => [
                'heading' => "'Oswald', sans-serif",
                'body' => "'Inter', system-ui, sans-serif",
                'googleFonts' => ['Oswald:500,600,700', 'Inter:400,500,600,700'],
            ],
            'radius' => 'rounded-none',
            'buttonStyle' => ['radius' => 'rounded-none'],
        ],
        [
            'id' => 'soft_premium',
            'keywords' => ['premium', 'neutral', 'soft', 'balanced', 'elevated', 'classic'],
            'palette' => [
                'primary' => '#152A32',
                'secondary' => '#FFF0B5',
                'background' => '#FFFCEE',
                'foreground' => '#152A32',
                'muted' => '#F5F0DD',
                'mutedForeground' => '#5E686D',
                'border' => '#E6DFC8',
                'card' => '#FFFFFF',
                'accent' => '#FFF0B5',
            ],
            'typography' => [
                'heading' => "'Cormorant Garamond', Georgia, serif",
                'body' => "'Inter', system-ui, sans-serif",
                'googleFonts' => ['Cormorant+Garamond:600,700', 'Inter:400,500,600,700'],
            ],
            'radius' => 'rounded-xl',
            'buttonStyle' => ['radius' => 'rounded-xl'],
        ],
    ];

    public function applyRandomTheme(array $projectFiles): array
    {
        $preset = self::randomPreset();
        if ($preset === null) {
            return $projectFiles;
        }

        return $this->applyPresetToFiles($projectFiles, $preset);
    }

    public function applyPreset(array $projectFiles, array $preset): array
    {
        return $this->applyPresetToFiles($projectFiles, $preset);
    }

    public function presetFromFrontendTheme(array $frontendTheme): ?array
    {
        $id = (string) ($frontendTheme['id'] ?? 'custom');

        $palette = is_array($frontendTheme['palette'] ?? null) ? $frontendTheme['palette'] : null;
        $typography = is_array($frontendTheme['typography'] ?? null) ? $frontendTheme['typography'] : null;
        $buttonStyle = is_array($frontendTheme['buttonStyle'] ?? null) ? $frontendTheme['buttonStyle'] : null;

        if ($palette !== null && $typography !== null) {
            $pal = $palette;
            $pal['mutedForeground'] = $pal['mutedForeground'] ?? $this->getContrastColor($pal['muted'] ?? '#FFFFFF');
            $pal['card'] = $pal['card'] ?? $pal['background'] ?? '#FFFFFF';
            $pal['accent'] = $pal['accent'] ?? $pal['primary'] ?? '#000000';

            $heading = (string) ($typography['heading'] ?? "'Inter', sans-serif");
            $body = (string) ($typography['body'] ?? "'Inter', system-ui, sans-serif");
            $googleFonts = is_array($typography['googleFonts'] ?? null) ? $typography['googleFonts'] : [];

            if (empty($googleFonts)) {
                $fonts = $this->extractFontNames($heading);
                $bodyFonts = $this->extractFontNames($body);
                $allFonts = array_values(array_unique(array_filter(
                    array_merge($fonts, $bodyFonts),
                    fn ($f) => ! $this->isGenericFont($f)
                )));
                if (empty($allFonts)) {
                    $allFonts = ['Inter'];
                }
                $googleFonts = array_map(fn ($f) => str_replace(' ', '+', $f) . ':400,500,600,700', $allFonts);
            }

            return [
                'id' => $id,
                'palette' => $pal,
                'typography' => [
                    'heading' => $heading,
                    'body' => $body,
                    'googleFonts' => $googleFonts,
                ],
                'radius' => (string) ($frontendTheme['radius'] ?? $buttonStyle['radius'] ?? 'rounded-xl'),
                'buttonStyle' => $buttonStyle ?? ['radius' => 'rounded-xl'],
            ];
        }

        $swatches = is_array($frontendTheme['swatches'] ?? null) ? $frontendTheme['swatches'] : [];
        if (count($swatches) < 3) {
            return null;
        }

        $name = strtolower((string) ($frontendTheme['name'] ?? ''));
        $description = strtolower((string) ($frontendTheme['description'] ?? ''));
        $isDark = str_contains($name, 'dark')
            || str_contains($description, 'dark')
            || str_contains($name, 'midnight')
            || str_contains($description, 'midnight')
            || str_contains($name, 'noir');

        $lums = [];
        foreach ($swatches as $i => $hex) {
            $lums[$i] = $this->luminance($hex);
        }
        asort($lums);
        $darkestIndex = array_key_first($lums);
        $lightestIndex = array_key_last($lums);

        $backgroundIndex = $isDark ? $darkestIndex : $lightestIndex;
        $foregroundIndex = $isDark ? $lightestIndex : $darkestIndex;

        $used = [$backgroundIndex, $foregroundIndex];
        $primaryIndex = 1;
        if (in_array($primaryIndex, $used, true) && count($swatches) > 2) {
            foreach ($swatches as $i => $_) {
                if (! in_array($i, $used, true)) {
                    $primaryIndex = $i;
                    break;
                }
            }
        }

        $secondaryIndex = 3;
        if (in_array($secondaryIndex, $used, true) || $secondaryIndex >= count($swatches)) {
            $secondaryIndex = $primaryIndex === 1 ? 0 : 1;
        }

        $primary = $swatches[$primaryIndex];
        $secondary = $swatches[$secondaryIndex];
        $background = $swatches[$backgroundIndex];
        $foreground = $swatches[$foregroundIndex];
        $muted = $swatches[3] ?? $swatches[count($swatches) - 1];
        $border = $swatches[3] ?? $swatches[count($swatches) - 1];

        $palette = [
            'primary' => $primary,
            'secondary' => $secondary,
            'background' => $background,
            'foreground' => $foreground,
            'muted' => $muted,
            'mutedForeground' => $this->getContrastColor($muted),
            'border' => $border,
            'card' => $background,
            'accent' => $primary,
        ];

        $fontFamily = (string) ($frontendTheme['typography']['fontFamily'] ?? "'Inter', system-ui, sans-serif");
        $fonts = $this->extractFontNames($fontFamily);
        $headingFont = $fonts[0] ?? 'Inter';
        $bodyFont = $fonts[1] ?? ($fonts[0] ?? 'Inter');

        $googleFonts = array_values(array_unique(array_filter([$headingFont, $bodyFont], fn ($f) => ! $this->isGenericFont($f))));
        if (empty($googleFonts)) {
            $googleFonts = ['Inter'];
        }

        $typography = [
            'heading' => $this->quoteFontValue($headingFont),
            'body' => $this->quoteFontValue($bodyFont),
            'googleFonts' => array_map(fn ($f) => str_replace(' ', '+', $f) . ':400,500,600,700', $googleFonts),
        ];

        return [
            'id' => $id,
            'palette' => $palette,
            'typography' => $typography,
            'radius' => (string) ($frontendTheme['radius'] ?? $buttonStyle['radius'] ?? 'rounded-xl'),
            'buttonStyle' => $buttonStyle ?? ['radius' => 'rounded-xl'],
        ];
    }

    private function luminance(string $hex): float
    {
        $normalized = ltrim($hex, '#');
        $r = hexdec(substr($normalized, 0, 2)) / 255;
        $g = hexdec(substr($normalized, 2, 2)) / 255;
        $b = hexdec(substr($normalized, 4, 2)) / 255;

        $transform = static function ($c) {
            return $c <= 0.03928 ? $c / 12.92 : pow(($c + 0.055) / 1.055, 2.4);
        };

        return 0.2126 * $transform($r) + 0.7152 * $transform($g) + 0.0722 * $transform($b);
    }

    /**
     * Blend two hex colors. $weightB is the fraction of $b in the result
     * (0..1); used to lighten borders toward white while keeping the hue.
     */
    private function mixHex(string $a, string $b, float $weightB): string
    {
        $hexOf = static function (string $hex): ?array {
            $normalized = ltrim(strtolower($hex), '#');
            if (strlen($normalized) === 3) {
                $normalized = $normalized[0] . $normalized[0]
                    . $normalized[1] . $normalized[1]
                    . $normalized[2] . $normalized[2];
            }
            if (strlen($normalized) !== 6) {
                return null;
            }

            return [
                hexdec(substr($normalized, 0, 2)),
                hexdec(substr($normalized, 2, 2)),
                hexdec(substr($normalized, 4, 2)),
            ];
        };

        $ca = $hexOf($a);
        $cb = $hexOf($b);
        if ($ca === null || $cb === null) {
            return $a;
        }

        $weightB = max(0.0, min(1.0, $weightB));
        $channels = [];
        foreach ([0, 1, 2] as $i) {
            $channels[$i] = (int) round($ca[$i] * (1 - $weightB) + $cb[$i] * $weightB);
        }

        return sprintf('#%02X%02X%02X', $channels[0], $channels[1], $channels[2]);
    }

    private function hexToHslTriplet(string $hex): string
    {
        $normalized = ltrim(strtolower((string) $hex), '#');
        if (strlen($normalized) === 3) {
            $normalized = $normalized[0] . $normalized[0]
                . $normalized[1] . $normalized[1]
                . $normalized[2] . $normalized[2];
        }
        if (strlen($normalized) !== 6) {
            return '0 0% 100%';
        }

        $r = hexdec(substr($normalized, 0, 2)) / 255;
        $g = hexdec(substr($normalized, 2, 2)) / 255;
        $b = hexdec(substr($normalized, 4, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        $h = 0;
        $s = 0;
        $d = $max - $min;
        if ($d > 0) {
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            if ($max === $r) {
                $h = 60 * fmod((($g - $b) / $d), 6);
            } elseif ($max === $g) {
                $h = 60 * (($b - $r) / $d + 2);
            } else {
                $h = 60 * (($r - $g) / $d + 4);
            }
            if ($h < 0) {
                $h += 360;
            }
        }

        return sprintf('%.1f %.1f%% %.1f%%', round($h, 1), round($s * 100, 1), round($l * 100, 1));
    }

    private function extractFontNames(string $cssFontValue): array
    {
        $parts = array_map('trim', explode(',', $cssFontValue));
        $names = [];
        foreach ($parts as $part) {
            $part = trim($part, "'\"");
            if ($part === '' || $this->isGenericFont($part)) {
                continue;
            }
            $names[] = $part;
        }

        return $names ?: ['Inter'];
    }

    private function isGenericFont(string $name): bool
    {
        $generic = ['system-ui', 'sans-serif', 'serif', 'monospace', 'cursive', 'fantasy', 'ui-sans-serif', 'ui-serif', 'ui-monospace', 'ui-rounded', 'georgia', 'times new roman', 'arial', 'helvetica', 'verdana', 'courier new', 'impact', 'trebuchet ms', 'palatino', 'garamond', 'bookman', 'comic sans ms'];

        return in_array(strtolower($name), $generic, true);
    }

    public static function randomPreset(): ?array
    {
        if (empty(self::$presets)) {
            return null;
        }

        return self::$presets[array_rand(self::$presets)];
    }

    private function applyPresetToFiles(array $projectFiles, array $preset): array
    {
        $pal = $preset['palette'];
        $primaryFg = $this->getContrastColor($pal['foreground']);
        $secondaryFg = $this->getContrastColor($pal['secondary']);
        $radiusValue = $this->radiusValue($preset['radius'] ?? $preset['buttonStyle']['radius'] ?? 'rounded-md');
        $sansFont = $this->fontFamilyName($preset['typography']['body'] ?? "'Inter', system-ui, sans-serif");
        $headingFont = $this->fontFamilyName($preset['typography']['heading'] ?? "'Inter', sans-serif");

        // Borders should read as a soft, light outline. On light themes, blend
        // the theme border color toward white so it stays a subtle brand tint
        // (lightness ~90%) instead of a mid-tone. Dark themes keep their border.
        $borderColor = $pal['border'];
        if (($this->luminance($pal['background'] ?? '#ffffff')) > 0.5) {
            $borderColor = $this->mixHex($borderColor, '#ffffff', 0.8);
        }

        $hslOf = fn (string $hex): string => $this->hexToHslTriplet($hex);
        $cssReplacements = [
            '--ds-primary' => $pal['primary'],
            '--ds-primary-hsl' => $hslOf($pal['primary']),
            '--ds-secondary' => $pal['secondary'],
            '--ds-secondary-hsl' => $hslOf($pal['secondary']),
            '--ds-background' => $pal['background'],
            '--ds-background-hsl' => $hslOf($pal['background']),
            '--ds-foreground' => $pal['foreground'],
            '--ds-foreground-hsl' => $hslOf($pal['foreground']),
            '--ds-muted' => $pal['muted'],
            '--ds-muted-hsl' => $hslOf($pal['muted']),
            '--ds-muted-foreground' => $pal['mutedForeground'],
            '--ds-muted-foreground-hsl' => $hslOf($pal['mutedForeground']),
            '--ds-border' => $borderColor,
            '--ds-border-hsl' => $hslOf($borderColor),
            '--ds-card' => $pal['card'],
            '--ds-card-hsl' => $hslOf($pal['card']),
            '--ds-card-foreground' => $pal['foreground'],
            '--ds-card-foreground-hsl' => $hslOf($pal['foreground']),
            '--ds-accent' => $pal['accent'],
            '--ds-accent-hsl' => $hslOf($pal['accent']),
            '--ds-accent-foreground' => $primaryFg,
            '--ds-accent-foreground-hsl' => $hslOf($primaryFg),
            '--ds-primary-foreground' => $primaryFg,
            '--ds-primary-foreground-hsl' => $hslOf($primaryFg),
            '--ds-secondary-foreground' => $secondaryFg,
            '--ds-secondary-foreground-hsl' => $hslOf($secondaryFg),
            '--ds-ring' => $pal['primary'],
            '--ds-ring-hsl' => $hslOf($pal['primary']),
            '--ds-radius' => $radiusValue,
            '--ds-font-sans' => $this->quoteFontValue($sansFont),
            '--ds-font-heading' => $this->quoteFontValue($headingFont),
            '--brand-primary' => $pal['primary'],
            '--brand-secondary' => $pal['secondary'],
            '--brand-bg' => $pal['background'],
            '--brand-text' => $pal['foreground'],
        ];

        $googleFonts = $preset['typography']['googleFonts'] ?? [];
        $fontLinks = [];
        if (!empty($googleFonts)) {
            $families = array_map(fn ($f) => str_replace(' ', '+', $f), $googleFonts);
            $fontLinks = [
                '<link rel="preconnect" href="https://fonts.googleapis.com">',
                '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>',
                '<link href="https://fonts.googleapis.com/css2?family=' . implode('&family=', $families) . '&display=swap" rel="stylesheet">',
            ];
        }

        $out = [];
        foreach ($projectFiles as $file) {
            $path = $file['path'] ?? '';
            $content = $file['content'] ?? '';
            if (!is_string($path) || !is_string($content)) {
                $out[] = $file;
                continue;
            }

            if ($path === 'src/styles.css') {
                $updated = $content;
                // Only rewrite tokens inside the :root block. Scoped dark-mode
                // overrides (.section-theme-dark) redeclare the same --ds-* names
                // with light-on-dark values; a blanket replacement across the whole
                // sheet would clobber them with the light theme colors and make
                // text render dark-on-dark inside dark sections.
                $updated = preg_replace_callback(
                    '/:root\s*\{(.*?)\}/s',
                    function ($m) use ($cssReplacements) {
                        $block = $m[1];
                        foreach ($cssReplacements as $key => $value) {
                            $block = preg_replace(
                                '/' . preg_quote($key, '/') . '\s*:\s*[^;]+;/',
                                $key . ': ' . $value . ';',
                                $block
                            );
                        }
                        return ':root {' . $block . '}';
                    },
                    $updated,
                    1
                );
                $out[] = ['path' => $path, 'content' => $updated];
                continue;
            }

            if ($path === 'index.html' && !empty($fontLinks) && !str_contains($content, 'fonts.googleapis.com')) {
                $links = "    " . implode("\n    ", $fontLinks) . "\n";
                $updated = str_ireplace('</head>', $links . "</head>", $content);
                $out[] = ['path' => $path, 'content' => $updated];
                continue;
            }

            $out[] = $file;
        }

        return $out;
    }

    private function radiusValue(string $radius): string
    {
        return match ($radius) {
            'rounded-none' => '0px',
            'rounded-sm' => '0.125rem',
            'rounded' => '0.25rem',
            'rounded-md' => '0.375rem',
            'rounded-lg' => '0.5rem',
            'rounded-xl' => '0.75rem',
            'rounded-2xl' => '1rem',
            'rounded-3xl' => '1.5rem',
            'rounded-full' => '50px',
            default => '0.625rem',
        };
    }

    private function fontFamilyName(string $cssFontValue): string
    {
        if (preg_match('/[\'\"]([^\'\"]+)[\'\"]/', $cssFontValue, $m)) {
            return $m[1];
        }
        $parts = array_map('trim', explode(',', $cssFontValue));
        return $parts[0] ?: 'Inter';
    }

    private function quoteFontValue(string $name): string
    {
        $clean = trim($name ?: 'Inter');
        return "'{$clean}', system-ui, sans-serif";
    }

    private function getContrastColor(string $hex): string
    {
        $normalized = ltrim($hex, '#');
        $r = hexdec(substr($normalized, 0, 2));
        $g = hexdec(substr($normalized, 2, 2));
        $b = hexdec(substr($normalized, 4, 2));
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        return $luminance > 0.5 ? '#000000' : '#FFFFFF';
    }
}
