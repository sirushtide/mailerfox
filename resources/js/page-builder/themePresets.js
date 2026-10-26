export const THEME_PROMPT_MARKERS = {
    start: '[MAILPURSE_THEME_START]',
    end: '[MAILPURSE_THEME_END]',
};

export const THEME_PRESETS = [
    {
        id: 'default',
        name: 'Default',
        description: 'Luxury, geometric, editorial',
        swatches: ['#6200EE', '#03DAC6', '#FFFFFF', '#EDE9FE'],
        palette: {
            primary: '#6200EE',
            secondary: '#03DAC6',
            background: '#FFFFFF',
            foreground: '#1A1A1A',
            muted: '#EDE9FE',
            mutedForeground: '#6B6B6B',
            border: '#956ad2',
            card: '#FFFFFF',
            accent: '#03DAC6',
        },
        typography: {
            label: 'Cormorant Garamond + Inter',
            fontFamily: '"Cormorant Garamond", Georgia, serif',
            heading: "'Cormorant Garamond', Georgia, serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Cormorant+Garamond:600,700', 'Inter:400,500,600,700'],
        },
        radius: 'rounded-none',
        keywords: ['art deco', 'luxury', 'geometric', 'editorial', 'gold', 'black', 'ivory'],
        prompt: 'Use an Art Deco theme with black, ivory, and gold tones. Favor geometric accents, symmetrical layouts, thin borders, and elegant serif headings paired with a refined sans-serif body.',
    },
    {
        id: 'art_deco',
        name: 'Art Deco',
        description: 'Luxury, geometric, editorial',
        swatches: ['#111111', '#C7A14B', '#F7F1E8', '#b79b57'],
        palette: {
            primary: '#C7A14B',
            secondary: '#111111',
            background: '#F7F1E8',
            foreground: '#111111',
            muted: '#E6D2A2',
            mutedForeground: '#5C5340',
            border: '#b79b57',
            card: '#FFFFFF',
            accent: '#C7A14B',
        },
        typography: {
            label: 'Cormorant Garamond + Inter',
            fontFamily: '"Cormorant Garamond", Georgia, serif',
            heading: "'Cormorant Garamond', Georgia, serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Cormorant+Garamond:600,700', 'Inter:400,500,600,700'],
        },
        radius: 'rounded-none',
        keywords: ['art deco', 'luxury', 'geometric', 'editorial', 'gold', 'black', 'ivory'],
        prompt: 'Use an Art Deco theme with black, ivory, and gold tones. Favor geometric accents, symmetrical layouts, thin borders, and elegant serif headings paired with a refined sans-serif body.',
    },
    {
        id: 'caffeine',
        name: 'Caffeine',
        description: 'Warm, cozy, coffee-shop inspired',
        swatches: ['#2B1D17', '#8B5E3C', '#b48b65', '#FFF7ED'],
        palette: {
            primary: '#8B5E3C',
            secondary: '#2B1D17',
            background: '#FFF7ED',
            foreground: '#2B1D17',
            muted: '#E7D3C0',
            mutedForeground: '#6B5D52',
            border: '#b48b65',
            card: '#FFFFFF',
            accent: '#8B5E3C',
        },
        typography: {
            label: 'Merriweather + Inter',
            fontFamily: '"Merriweather", Georgia, serif',
            heading: "'Merriweather', Georgia, serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Merriweather:700,900', 'Inter:400,500,600,700'],
        },
        radius: 'rounded-2xl',
        keywords: ['caffeine', 'warm', 'cozy', 'coffee', 'espresso', 'brown', 'caramel'],
        prompt: 'Use a warm caffeine-inspired theme with espresso browns, oat creams, and caramel highlights. Make the layout feel cozy, premium, and welcoming with soft contrast and rounded surfaces.',
    },
    {
        id: 'claude',
        name: 'Claude',
        description: 'Smart, soft, premium AI',
        swatches: ['#202123', '#6B4EFF', '#F7F6F3', '#D7D3FF'],
        palette: {
            primary: '#6B4EFF',
            secondary: '#202123',
            background: '#F7F6F3',
            foreground: '#202123',
            muted: '#D7D3FF',
            mutedForeground: '#4A4A50',
            border: '#7587E1',
            card: '#FFFFFF',
            accent: '#6B4EFF',
        },
        typography: {
            label: 'Inter + ui-sans-serif',
            fontFamily: '"Inter", system-ui, sans-serif',
            heading: "'Inter', sans-serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Inter:400,500,600,700,800'],
        },
        radius: 'rounded-xl',
        keywords: ['claude', 'smart', 'soft', 'premium', 'ai', 'lavender', 'charcoal'],
        prompt: 'Use a Claude-inspired theme with a smart, soft, premium AI look. Keep the palette restrained with deep charcoal, muted lavender accents, gentle spacing, and polished surfaces.',
    },
    {
        id: 'clean_slate',
        name: 'Clean Slate',
        description: 'Minimal, airy, content-first',
        swatches: ['#0F172A', '#18a285', '#FFFFFF', '#F1F5F9'],
        palette: {
            primary: '#18a285',
            secondary: '#0F172A',
            background: '#FFFFFF',
            foreground: '#0F172A',
            muted: '#F1F5F9',
            mutedForeground: '#64748B',
            border: '#54c7be',
            card: '#FFFFFF',
            accent: '#71e3d3',
        },
        typography: {
            label: 'Inter + ui-sans-serif',
            fontFamily: '"Inter", system-ui, sans-serif',
            heading: "'Inter', sans-serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Inter:400,500,600,700,800'],
        },
        radius: 'rounded-lg',
        keywords: ['clean', 'minimal', 'airy', 'content-first', 'white space', 'light'],
        prompt: 'Use a Clean Slate theme with lots of white space, crisp typography, subtle borders, and a modern content-first layout. Keep the interface light, calm, and highly readable.',
    },
    {
        id: 'corporate',
        name: 'Corporate',
        description: 'Professional, trustworthy, structured',
        swatches: ['#0F172A', '#1D4ED8', '#F8FAFC', '#7cb1f2'],
        palette: {
            primary: '#1D4ED8',
            secondary: '#0F172A',
            background: '#F8FAFC',
            foreground: '#0F172A',
            muted: '#CBD5E1',
            mutedForeground: '#475569',
            border: '#7cb1f2',
            card: '#FFFFFF',
            accent: '#1D4ED8',
        },
        typography: {
            label: 'IBM Plex Sans + Inter',
            fontFamily: '"IBM Plex Sans", "Inter", system-ui, sans-serif',
            heading: "'IBM Plex Sans', sans-serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['IBM+Plex+Sans:400,500,600,700', 'Inter:400,500,600,700'],
        },
        radius: 'rounded-md',
        keywords: ['corporate', 'professional', 'trustworthy', 'enterprise', 'navy', 'blue', 'slate'],
        prompt: 'Use a Corporate theme with navy, blue, and slate tones. Prioritize strong hierarchy, structured sections, polished cards, and a dependable enterprise feel.',
    },
    {
        id: 'elegant_luxury',
        name: 'Elegant Luxury',
        description: 'Sophisticated, high-end, refined',
        swatches: ['#7CB1F2', '#A57C4A', '#F8F1E7', '#B78E59'],
        palette: {
            primary: '#A57C4A',
            secondary: '#1C1B1F',
            background: '#F8F1E7',
            foreground: '#0b284c',
            muted: '#E9DCCB',
            mutedForeground: '#5C5340',
            border: '#B78E59',
            card: '#FFFFFF',
            accent: '#A57C4A',
        },
        typography: {
            label: 'Playfair Display + Inter',
            fontFamily: '"Playfair Display", "Inter", system-ui, sans-serif',
            heading: "'Playfair Display', Georgia, serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Playfair+Display:700,800', 'Inter:400,500,600,700'],
        },
        radius: 'rounded-xl',
        keywords: ['elegant', 'luxury', 'sophisticated', 'high-end', 'refined', 'gold', 'charcoal', 'ivory'],
        prompt: 'Use an Elegant Luxury theme with deep charcoal, champagne gold, and soft ivory. Make the design feel high-end with graceful typography, premium spacing, subtle gradients, and refined borders.',
    },
    {
        id: 'ghibli_studio',
        name: 'Ghibli Studio',
        description: 'Whimsical, warm, hand-crafted',
        swatches: ['#5B8C51', '#F4C27A', '#FFF7E8', '#5cb173'],
        palette: {
            primary: '#5B8C51',
            secondary: '#F4C27A',
            background: '#FFF7E8',
            foreground: '#3A5A33',
            muted: '#C7E8D0',
            mutedForeground: '#5A6B50',
            border: '#5cb173',
            card: '#FFFFFF',
            accent: '#F4C27A',
        },
        typography: {
            label: 'Nunito + Inter',
            fontFamily: '"Nunito", "Inter", system-ui, sans-serif',
            heading: "'Nunito', sans-serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Nunito:400,600,700,800', 'Inter:400,500,600,700'],
        },
        radius: 'rounded-2xl',
        keywords: ['ghibli', 'whimsical', 'warm', 'hand-crafted', 'natural', 'green', 'storybook'],
        prompt: 'Use a Ghibli Studio-inspired theme with soft natural greens, warm sunlight tones, gentle curves, and a hand-crafted whimsical feel. Keep it charming, cozy, and storybook-like.',
    },
    {
        id: 'marshmallow',
        name: 'Marshmallow',
        description: 'Soft, friendly, pastel',
        swatches: ['#FF7AA2', '#e48195', '#FFF1F6', '#ffcff5'],
        palette: {
            primary: '#FF7AA2',
            secondary: '#FDB5C4',
            background: '#FFF1F6',
            foreground: '#c04f71',
            muted: '#ffcff5',
            mutedForeground: '#7A6B72',
            border: '#e48195',
            card: '#FFFFFF',
            accent: '#FF7AA2',
        },
        typography: {
            label: 'Quicksand + Inter',
            fontFamily: '"Quicksand", "Inter", system-ui, sans-serif',
            heading: "'Quicksand', sans-serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Quicksand:400,500,600,700', 'Inter:400,500,600,700'],
        },
        radius: 'rounded-full',
        keywords: ['marshmallow', 'soft', 'friendly', 'pastel', 'pink', 'playful', 'rounded'],
        prompt: 'Use a Marshmallow theme with pastel pinks, airy whites, and soft blue accents. Make everything feel light, friendly, playful, and rounded without becoming childish.',
    },
    {
        id: 'marvel',
        name: 'Marvel',
        description: 'Bold, cinematic, energetic',
        swatches: ['#E11D48', '#0F172A', '#FFFFFF', '#FDE68A'],
        palette: {
            primary: '#E11D48',
            secondary: '#0F172A',
            background: '#FFFFFF',
            foreground: '#0F172A',
            muted: '#FDE68A',
            mutedForeground: '#64748B',
            border: '#f07171',
            card: '#FFFFFF',
            accent: '#E11D48',
        },
        typography: {
            label: 'Oswald + Inter',
            fontFamily: '"Oswald", "Inter", system-ui, sans-serif',
            heading: "'Oswald', sans-serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Oswald:500,600,700', 'Inter:400,500,600,700'],
        },
        radius: 'rounded-lg',
        keywords: ['marvel', 'bold', 'cinematic', 'energetic', 'red', 'dynamic', 'powerful'],
        prompt: 'Use a Marvel-inspired theme with bold red accents, dark contrast, cinematic energy, and punchy section headings. Make the layout feel powerful and dynamic.',
    },
    {
        id: 'material_design',
        name: 'Material Design',
        description: 'Layered, modern, tactile',
        swatches: ['#6200EE', '#03DAC6', '#FFFFFF', '#EDE9FE'],
        palette: {
            primary: '#6200EE',
            secondary: '#03DAC6',
            background: '#FFFFFF',
            foreground: '#1A1A1A',
            muted: '#EDE9FE',
            mutedForeground: '#6B6B6B',
            border: '#E0E0E0',
            card: '#FFFFFF',
            accent: '#03DAC6',
        },
        typography: {
            label: 'Roboto + Inter',
            fontFamily: '"Roboto", "Inter", system-ui, sans-serif',
            heading: "'Roboto', sans-serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Roboto:400,500,700', 'Inter:400,500,600,700'],
        },
        radius: 'rounded-xl',
        keywords: ['material', 'design', 'layered', 'modern', 'tactile', 'elevation'],
        prompt: 'Use a Material Design theme with layered surfaces, clear elevation, rounded cards, and vivid primary/accent colors. Keep the visual hierarchy strong and tactile.',
    },
    {
        id: 'midnight_bloom',
        name: 'Midnight Bloom',
        description: 'Dark, elegant, glowing accent',
        swatches: ['#111827', '#6989b4', '#0F172A', '#F8FAFC'],
        palette: {
            primary: '#8B5CF6',
            secondary: '#111827',
            background: '#0F172A',
            foreground: '#F8FAFC',
            muted: '#1E293B',
            mutedForeground: '#94A3B8',
            border: '#6989b4',
            card: '#1E293B',
            accent: '#8B5CF6',
        },
        typography: {
            label: 'Space Grotesk + Inter',
            fontFamily: '"Space Grotesk", "Inter", system-ui, sans-serif',
            heading: "'Space Grotesk', sans-serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Space+Grotesk:400,500,600,700', 'Inter:400,500,600,700'],
        },
        radius: 'rounded-xl',
        keywords: ['midnight', 'bloom', 'dark', 'elegant', 'glowing', 'purple', 'violet', 'noir'],
        prompt: 'Use a Midnight Bloom theme with a dark base, vivid floral or purple accents, subtle glows, and elegant contrast. Keep it atmospheric, premium, and modern.',
    },
    {
        id: 'modern_minimal',
        name: 'Modern Minimal',
        description: 'Simple, sharp, contemporary',
        swatches: ['#111827', '#2563EB', '#FFFFFF', '#E2E8F0'],
        palette: {
            primary: '#2563EB',
            secondary: '#111827',
            background: '#FFFFFF',
            foreground: '#111827',
            muted: '#E2E8F0',
            mutedForeground: '#64748B',
            border: '#E2E8F0',
            card: '#FFFFFF',
            accent: '#2563EB',
        },
        typography: {
            label: 'Inter + ui-sans-serif',
            fontFamily: '"Inter", system-ui, sans-serif',
            heading: "'Inter', sans-serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Inter:400,500,600,700,800'],
        },
        radius: 'rounded-lg',
        keywords: ['modern', 'minimal', 'simple', 'sharp', 'contemporary', 'clean'],
        prompt: 'Use a Modern Minimal theme with sharp typography, restrained color usage, slim borders, and a very clean contemporary layout. Avoid decorative clutter.',
    },
    {
        id: 'nature',
        name: 'Nature',
        description: 'Organic, fresh, green-forward',
        swatches: ['#166534', '#84CC16', '#F7FDF4', '#DCFCE7'],
        palette: {
            primary: '#166534',
            secondary: '#84CC16',
            background: '#F7FDF4',
            foreground: '#14532D',
            muted: '#DCFCE7',
            mutedForeground: '#4A6741',
            border: '#2fab59',
            card: '#FFFFFF',
            accent: '#84CC16',
        },
        typography: {
            label: 'Manrope + Inter',
            fontFamily: '"Manrope", "Inter", system-ui, sans-serif',
            heading: "'Manrope', sans-serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Manrope:400,500,600,700,800', 'Inter:400,500,600,700'],
        },
        radius: 'rounded-xl',
        keywords: ['nature', 'organic', 'fresh', 'green', 'earthy', 'natural', 'healthy'],
        prompt: 'Use a Nature theme with green-forward colors, organic spacing, earthy warmth, and soft natural contrast. Make it feel fresh, healthy, and grounded.',
    },
    {
        id: 'neo_brutalism',
        name: 'Neo Brutalism',
        description: 'Bold, playful, high-contrast',
        swatches: ['#acacac', '#FF2D55', '#FFEA00', '#FFFFFF'],
        palette: {
            primary: '#FF2D55',
            secondary: '#111111',
            background: '#FFFFFF',
            foreground: '#111111',
            muted: '#FFEA00',
            mutedForeground: '#4A4A4A',
            border: '#acacac',
            card: '#FFFFFF',
            accent: '#FF2D55',
        },
        typography: {
            label: 'Space Grotesk + Inter',
            fontFamily: '"Space Grotesk", "Inter", system-ui, sans-serif',
            heading: "'Space Grotesk', sans-serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Space+Grotesk:400,500,600,700', 'Inter:400,500,600,700'],
        },
        radius: 'rounded-none',
        keywords: ['neo brutalism', 'bold', 'playful', 'high contrast', 'hard edges', 'outlines', 'loud'],
        prompt: 'Use a Neo Brutalism theme with hard edges, bold outlines, strong contrast, oversized shadows, and playful bright highlights. Make the design feel intentionally loud and memorable.',
    },
    {
        id: 'pastel_dreams',
        name: 'Pastel Dreams',
        description: 'Soft pastel, dreamy, airy',
        swatches: ['#C4B5FD', '#F9A8D4', '#BFDBFE', '#FEF3C7'],
        palette: {
            primary: '#C4B5FD',
            secondary: '#F9A8D4',
            background: '#FEF3C7',
            foreground: '#FFFFFF',
            muted: '#BFDBFE',
            mutedForeground: '#6B6B7B',
            border: '#B09DFA',
            card: '#FFFFFF',
            accent: '#F9A8D4',
        },
        typography: {
            label: 'Poppins + Inter',
            fontFamily: '"Poppins", "Inter", system-ui, sans-serif',
            heading: "'Poppins', sans-serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Poppins:400,500,600,700', 'Inter:400,500,600,700'],
        },
        radius: 'rounded-2xl',
        keywords: ['pastel', 'dreams', 'soft', 'dreamy', 'airy', 'lavender', 'pink', 'sky', 'cream'],
        prompt: 'Use a Pastel Dreams theme with lavender, pink, sky, and cream tones. Keep the design airy, gentle, dreamy, and softly layered.',
    },
];

export function isNoTheme(themeId) {
    return themeId === null || themeId === undefined || themeId === '' || themeId === 'none';
}

export function getThemePreset(themeId) {
    if (isNoTheme(themeId)) return null;
    const theme = THEME_PRESETS.find((t) => t.id === themeId) || null;
    if (!theme) {
        return null;
    }
    return {
        ...theme,
        typography: getThemeTypography(themeId),
    };
}

export function getThemeTypography(themeId) {
    if (isNoTheme(themeId)) return null;
    const theme = THEME_PRESETS.find((t) => t.id === themeId) || null;
    if (!theme || !theme.typography) {
        return null;
    }
    return {
        label: theme.typography.label || '',
        fontFamily: theme.typography.fontFamily || '',
        heading: theme.typography.heading || '',
        body: theme.typography.body || '',
        googleFonts: theme.typography.googleFonts || [],
    };
}

export function buildThemePrompt(themeId) {
    const theme = getThemePreset(themeId);
    if (!theme) return '';
    const typography = getThemeTypography(themeId);

    const swatchLine = Array.isArray(theme.swatches) && theme.swatches.length > 0
        ? `Palette: ${theme.swatches.join(', ')}`
        : '';

    return [
        `Theme preset: ${theme.name}`,
        theme.description ? `Style: ${theme.description}` : '',
        typography?.label ? `Typography: ${typography.label}` : '',
        swatchLine,
        theme.prompt,
    ].filter(Boolean).join('\n');
}

export function stripThemePrompt(prompt) {
    const source = String(prompt || '');
    const startIndex = source.indexOf(THEME_PROMPT_MARKERS.start);
    const endIndex = source.indexOf(THEME_PROMPT_MARKERS.end);

    if (startIndex === -1 || endIndex === -1 || endIndex < startIndex) {
        return source.trim();
    }

    const before = source.slice(0, startIndex).trim();
    const after = source.slice(endIndex + THEME_PROMPT_MARKERS.end.length).trim();
    return [before, after].filter(Boolean).join('\n\n').trim();
}

export function stripVisibleThemePrompt(prompt) {
    let source = stripThemePrompt(prompt).trim();
    if (!source) {
        return '';
    }

    const normalizedSource = source.replace(/\r\n/g, '\n');

    for (const theme of THEME_PRESETS) {
        const themePrompt = String(theme?.prompt || '').trim();
        if (!themePrompt) {
            continue;
        }

        const normalizedThemePrompt = themePrompt.replace(/\r\n/g, '\n');
        if (normalizedSource === normalizedThemePrompt) {
            return '';
        }

        if (normalizedSource.startsWith(normalizedThemePrompt + '\n') || normalizedSource.startsWith(normalizedThemePrompt + '\r\n')) {
            return normalizedSource.slice(normalizedThemePrompt.length).trimStart().trim();
        }

        const fullThemeBlock = buildThemePrompt(theme.id).replace(/\r\n/g, '\n');
        if (fullThemeBlock && normalizedSource.startsWith(fullThemeBlock)) {
            const remainder = normalizedSource.slice(fullThemeBlock.length).trimStart();
            return remainder.trim();
        }
    }

    return source;
}

export function mergeThemePromptIntoPrompt(prompt, themeId) {
    const body = stripVisibleThemePrompt(prompt);
    const theme = getThemePreset(themeId);
    const themePrompt = String(theme?.prompt || '').trim();

    if (!themePrompt) {
        return body;
    }

    return [themePrompt, body].filter(Boolean).join('\n\n').trim();
}

export function applyThemePrompt(prompt, themeId) {
    const body = stripVisibleThemePrompt(prompt);
    const themePrompt = buildThemePrompt(themeId);

    if (!themePrompt) {
        return body;
    }

    return [themePrompt, body].filter(Boolean).join('\n\n').trim();
}

export function composePromptWithTheme(prompt, themeId) {
    const body = stripVisibleThemePrompt(prompt);
    const themePrompt = buildThemePrompt(themeId);

    if (!themePrompt) {
        return body;
    }

    return [themePrompt, body].filter(Boolean).join('\n\n').trim();
}

function radiusValue(radius) {
    const map = {
        'rounded-none': '0px',
        'rounded-sm': '0.125rem',
        'rounded': '0.25rem',
        'rounded-md': '0.375rem',
        'rounded-lg': '0.5rem',
        'rounded-xl': '0.75rem',
        'rounded-2xl': '1rem',
        'rounded-3xl': '1.5rem',
        'rounded-full': '50px',
    };
    return map[radius] || '0.625rem';
}

function fontFamilyName(cssFontValue) {
    if (!cssFontValue) return 'Inter';
    const match = cssFontValue.match(/['"]([^'"]+)['"]/);
    if (match) return match[1];
    const parts = cssFontValue.split(',').map((s) => s.trim());
    return parts[0] || 'Inter';
}

function quoteFontValue(name) {
    const clean = (name || 'Inter').trim();
    return `'${clean}', system-ui, sans-serif`;
}

function getContrastColor(hex) {
    const normalized = (hex || '#000000').replace(/^#/, '');
    const r = parseInt(normalized.substring(0, 2), 16);
    const g = parseInt(normalized.substring(2, 4), 16);
    const b = parseInt(normalized.substring(4, 6), 16);
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
    return luminance > 0.5 ? '#000000' : '#FFFFFF';
}

export function applyPresetToFiles(projectFiles, preset) {
    const pal = preset?.palette || {};
    const primaryFg = getContrastColor(pal.primary || '#000000');
    const secondaryFg = getContrastColor(pal.secondary || '#000000');
    const radius = radiusValue(preset?.radius || 'rounded-md');
    const sansFont = fontFamilyName(preset?.typography?.body || "'Inter', system-ui, sans-serif");
    const headingFont = fontFamilyName(preset?.typography?.heading || "'Inter', sans-serif");

    const cssReplacements = {
        '--ds-primary': pal.primary || '',
        '--ds-secondary': pal.secondary || '',
        '--ds-background': pal.background || '',
        '--ds-foreground': pal.foreground || '',
        '--ds-muted': pal.muted || '',
        '--ds-muted-foreground': pal.mutedForeground || '',
        '--ds-border': pal.border || '',
        '--ds-card': pal.card || '',
        '--ds-card-foreground': pal.foreground || '',
        '--ds-accent': pal.accent || pal.primary || '',
        '--ds-accent-foreground': primaryFg,
        '--ds-primary-foreground': primaryFg,
        '--ds-secondary-foreground': secondaryFg,
        '--ds-ring': pal.primary || '',
        '--ds-radius': radius,
        '--ds-radius-md': radius,
        '--ds-radius-lg': radius,
        '--ds-radius-pill': radius,
        '--ds-font-sans': quoteFontValue(sansFont),
        '--ds-font-heading': quoteFontValue(headingFont),
        '--brand-primary': pal.primary || '',
        '--brand-secondary': pal.secondary || '',
        '--brand-bg': pal.background || '',
        '--brand-text': pal.foreground || '',
    };

    const googleFonts = preset?.typography?.googleFonts || [];
    let fontLinks = [];
    if (googleFonts.length > 0) {
        const families = googleFonts.map((f) => f.replace(/ /g, '+'));
        fontLinks = [
            '<link rel="preconnect" href="https://fonts.googleapis.com">',
            '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>',
            `<link href="https://fonts.googleapis.com/css2?family=${families.join('&family=')}&display=swap" rel="stylesheet">`,
        ];
    }

    return projectFiles.map((file) => {
        const path = file.path || '';
        const content = file.content || '';
        if (typeof path !== 'string' || typeof content !== 'string') {
            return file;
        }

        if (path === 'src/styles.css') {
            let updated = content;
            for (const [key, value] of Object.entries(cssReplacements)) {
                const regex = new RegExp(
                    key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\s*:\\s*[^;]+;',
                    'g'
                );
                updated = updated.replace(regex, `${key}: ${value};`);
            }
            return { path, content: updated };
        }

        if (path === 'index.html' && fontLinks.length > 0 && !content.includes('fonts.googleapis.com')) {
            const links = '    ' + fontLinks.join('\n    ') + '\n';
            const updated = content.replace(/<\/head>/i, links + '</head>');
            return { path, content: updated };
        }

        return file;
    });
}

export function applyThemeToFiles(projectFiles, themeId) {
    const theme = getThemePreset(themeId);
    if (!theme) return projectFiles;
    return applyPresetToFiles(projectFiles, theme);
}
