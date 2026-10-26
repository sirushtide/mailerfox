/**
 * Post-assembly design fine-tuning presets.
 * These are NOT generation-time themes; they are applied after a page is built
 * to unify colors, typography, buttons, eyebrow/subheading styles, borders and
 * section backgrounds across every section.
 */

export const FINE_TUNING_PRESETS = [
    {
        id: 'lime_pop',
        name: 'Lime Pop',
        description: 'High-contrast lime accent on clean black and off-white',
        swatches: ['#D9FF03', '#000000', '#F2EFEB', '#A6A8AB'],
        palette: {
            primary: '#D9FF03',
            secondary: '#000000',
            background: '#F2EFEB',
            foreground: '#000000',
            muted: '#A6A8AB',
            mutedForeground: '#4B4D4F',
            border: '#000000',
            card: '#FFFFFF',
            accent: '#D9FF03',
        },
        typography: {
            label: 'Space Grotesk + Inter',
            heading: "'Space Grotesk', sans-serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Space Grotesk:700', 'Inter:400,500,600,700'],
        },
        buttonStyle: {
            radius: 'rounded-none',
            primary: 'bg-black text-lime-300 border-2 border-black hover:bg-neutral-900',
            secondary: 'bg-lime-300 text-black border-2 border-black hover:bg-lime-400',
            outline: 'border-2 border-black bg-transparent text-black hover:bg-black hover:text-lime-300',
        },
        eyebrowStyle: {
            classes: 'inline-block px-3 py-1 text-[10px] font-bold uppercase tracking-[0.2em] bg-lime-300 text-black border-2 border-black',
        },
        sectionBackgroundStyle: {
            pattern: 'alternating',
            odd: 'bg-[#F2EFEB]',
            even: 'bg-black text-white',
            cta: 'bg-lime-300 text-black',
        },
    },
    {
        id: 'cyan_storm',
        name: 'Cyan Storm',
        description: 'Dark teal base with vivid cyan accents and rounded edges',
        swatches: ['#0F282F', '#02EFF0', '#FFFFFF', '#2A3F45'],
        palette: {
            primary: '#02EFF0',
            secondary: '#0F282F',
            background: '#0F282F',
            foreground: '#FFFFFF',
            muted: '#2A3F45',
            mutedForeground: '#9FB0B5',
            border: '#02EFF0',
            card: '#14343C',
            accent: '#02EFF0',
        },
        typography: {
            label: 'Inter + JetBrains Mono',
            heading: "'Inter', sans-serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Inter:400,500,600,700,800', 'JetBrains+Mono:400,500'],
        },
        buttonStyle: {
            radius: 'rounded-full',
            primary: 'bg-[#02EFF0] text-[#0F282F] font-semibold hover:bg-[#5DF2F3]',
            secondary: 'bg-transparent text-[#02EFF0] border border-[#02EFF0] hover:bg-[#02EFF0] hover:text-[#0F282F]',
            outline: 'border border-[#02EFF0] text-[#02EFF0] hover:bg-[#02EFF0] hover:text-[#0F282F]',
        },
        eyebrowStyle: {
            classes: 'text-[#02EFF0] text-xs font-medium uppercase tracking-[0.2em] font-mono',
        },
        sectionBackgroundStyle: {
            pattern: 'gradient',
            base: 'bg-[#0F282F]',
            gradient: 'bg-gradient-to-br from-[#0F282F] to-[#14343C]',
            cta: 'bg-[#02EFF0] text-[#0F282F]',
        },
    },
    {
        id: 'cream_manela',
        name: 'Cream Manela',
        description: 'Warm cream with soft purple and deep navy accents',
        swatches: ['#FFFCEE', '#C9B6FF', '#152A32', '#FFFFFF'],
        palette: {
            primary: '#152A32',
            secondary: '#C9B6FF',
            background: '#FFFCEE',
            foreground: '#152A32',
            muted: '#F3ECD4',
            mutedForeground: '#5A6A72',
            border: '#E6DFC8',
            card: '#FFFFFF',
            accent: '#C9B6FF',
        },
        typography: {
            label: 'Playfair Display + Inter',
            heading: "'Playfair Display', Georgia, serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Playfair+Display:700,800', 'Inter:400,500,600,700'],
        },
        buttonStyle: {
            radius: 'rounded-2xl',
            primary: 'bg-[#152A32] text-[#FFFCEE] hover:bg-[#1F3A46]',
            secondary: 'bg-[#C9B6FF] text-[#152A32] hover:bg-[#BBA2F5]',
            outline: 'border-2 border-[#152A32] text-[#152A32] bg-transparent hover:bg-[#152A32] hover:text-[#FFFCEE]',
        },
        eyebrowStyle: {
            classes: 'inline-flex items-center gap-2 text-[#152A32] text-xs font-semibold uppercase tracking-widest before:w-2 before:h-2 before:rounded-full before:bg-[#C9B6FF]',
        },
        sectionBackgroundStyle: {
            pattern: 'alternating',
            odd: 'bg-[#FFFCEE]',
            even: 'bg-white',
            cta: 'bg-[#152A32] text-[#FFFCEE]',
        },
    },
    {
        id: 'bright_fresh',
        name: 'Bright Fresh',
        description: 'Clean white with energetic green gradients',
        swatches: ['#00D47F', '#91FF00', '#FFFFFF', '#E6F9F1'],
        palette: {
            primary: '#00D47F',
            secondary: '#91FF00',
            background: '#FFFFFF',
            foreground: '#0F172A',
            muted: '#E6F9F1',
            mutedForeground: '#475569',
            border: '#00D47F',
            card: '#FFFFFF',
            accent: '#91FF00',
        },
        typography: {
            label: 'Manrope + Inter',
            heading: "'Manrope', sans-serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Manrope:400,500,600,700,800', 'Inter:400,500,600,700'],
        },
        buttonStyle: {
            radius: 'rounded-full',
            primary: 'bg-gradient-to-r from-[#00D47F] to-[#91FF00] text-[#0F172A] font-semibold hover:opacity-90',
            secondary: 'bg-white text-[#00D47F] border border-[#00D47F] hover:bg-[#E6F9F1]',
            outline: 'border border-[#00D47F] text-[#00D47F] hover:bg-[#E6F9F1]',
        },
        eyebrowStyle: {
            classes: 'inline-flex items-center gap-2 text-[#00D47F] text-xs font-bold uppercase tracking-widest before:w-1.5 before:h-1.5 before:rounded-full before:bg-[#91FF00]',
        },
        sectionBackgroundStyle: {
            pattern: 'alternating',
            odd: 'bg-white',
            even: 'bg-[#E6F9F1]',
            cta: 'bg-gradient-to-r from-[#00D47F] to-[#91FF00] text-[#0F172A]',
        },
    },
    {
        id: 'charcoal_citrus',
        name: 'Charcoal Citrus',
        description: 'Dark charcoal alternating with warm orange energy',
        swatches: ['#201F1F', '#FD572A', '#F2EFEB', '#000000'],
        palette: {
            primary: '#FD572A',
            secondary: '#201F1F',
            background: '#F2EFEB',
            foreground: '#201F1F',
            muted: '#E8E3DE',
            mutedForeground: '#6B6864',
            border: '#201F1F',
            card: '#FFFFFF',
            accent: '#FD572A',
        },
        typography: {
            label: 'Oswald + Inter',
            heading: "'Oswald', sans-serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Oswald:500,600,700', 'Inter:400,500,600,700'],
        },
        buttonStyle: {
            radius: 'rounded-lg',
            primary: 'bg-[#FD572A] text-white font-semibold hover:bg-[#E64D24]',
            secondary: 'bg-[#201F1F] text-white hover:bg-black',
            outline: 'border-2 border-[#201F1F] text-[#201F1F] hover:bg-[#201F1F] hover:text-white',
        },
        eyebrowStyle: {
            classes: 'text-[#FD572A] text-xs font-bold uppercase tracking-[0.18em]',
        },
        sectionBackgroundStyle: {
            pattern: 'alternating',
            odd: 'bg-[#F2EFEB]',
            even: 'bg-[#201F1F] text-white',
            cta: 'bg-[#FD572A] text-white',
        },
    },
    {
        id: 'violet_noir',
        name: 'Violet Noir',
        description: 'Purple accents on deep black with editorial contrast',
        swatches: ['#695AC6', '#000000', '#F2EFEB', '#201F1F'],
        palette: {
            primary: '#695AC6',
            secondary: '#000000',
            background: '#000000',
            foreground: '#F2EFEB',
            muted: '#201F1F',
            mutedForeground: '#A6A8AB',
            border: '#695AC6',
            card: '#201F1F',
            accent: '#695AC6',
        },
        typography: {
            label: 'Space Grotesk + Inter',
            heading: "'Space Grotesk', sans-serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Space+Grotesk:400,500,600,700', 'Inter:400,500,600,700'],
        },
        buttonStyle: {
            radius: 'rounded-none',
            primary: 'bg-[#695AC6] text-white font-semibold hover:bg-[#5A4DB5]',
            secondary: 'bg-[#201F1F] text-[#F2EFEB] border border-[#695AC6] hover:bg-[#695AC6] hover:text-white',
            outline: 'border border-[#695AC6] text-[#695AC6] hover:bg-[#695AC6] hover:text-white',
        },
        eyebrowStyle: {
            classes: 'inline-block px-3 py-1 border border-[#695AC6] text-[#695AC6] text-[10px] font-bold uppercase tracking-[0.2em]',
        },
        sectionBackgroundStyle: {
            pattern: 'alternating',
            odd: 'bg-black',
            even: 'bg-[#201F1F]',
            cta: 'bg-[#695AC6] text-white',
        },
    },
    {
        id: 'teal_minimal',
        name: 'Teal Minimal',
        description: 'Ultra-clean white with teal and cyan details',
        swatches: ['#0F282F', '#02EFF0', '#FFFFFF', '#F1F5F9'],
        palette: {
            primary: '#0F282F',
            secondary: '#02EFF0',
            background: '#FFFFFF',
            foreground: '#0F282F',
            muted: '#F1F5F9',
            mutedForeground: '#64748B',
            border: '#E2E8F0',
            card: '#FFFFFF',
            accent: '#02EFF0',
        },
        typography: {
            label: 'Inter + system sans',
            heading: "'Inter', sans-serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Inter:400,500,600,700,800'],
        },
        buttonStyle: {
            radius: 'rounded-xl',
            primary: 'bg-[#0F282F] text-white hover:bg-[#163840]',
            secondary: 'bg-[#02EFF0] text-[#0F282F] hover:bg-[#33F2F3]',
            outline: 'border-2 border-[#0F282F] text-[#0F282F] hover:bg-[#0F282F] hover:text-white',
        },
        eyebrowStyle: {
            classes: 'text-[#0F282F] text-xs font-semibold uppercase tracking-[0.15em]',
        },
        sectionBackgroundStyle: {
            pattern: 'alternating',
            odd: 'bg-white',
            even: 'bg-[#F1F5F9]',
            cta: 'bg-[#0F282F] text-white',
        },
    },
    {
        id: 'sunset_warmth',
        name: 'Sunset Warmth',
        description: 'Warm cream and yellow with soft purple highlights',
        swatches: ['#FFF0B5', '#C9B6FF', '#152A32', '#FFFFFF'],
        palette: {
            primary: '#152A32',
            secondary: '#C9B6FF',
            background: '#FFF0B5',
            foreground: '#152A32',
            muted: '#FDF3D1',
            mutedForeground: '#6B6B6B',
            border: '#E6D9A8',
            card: '#FFFFFF',
            accent: '#C9B6FF',
        },
        typography: {
            label: 'Merriweather + Inter',
            heading: "'Merriweather', Georgia, serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Merriweather:700,900', 'Inter:400,500,600,700'],
        },
        buttonStyle: {
            radius: 'rounded-2xl',
            primary: 'bg-[#152A32] text-[#FFF0B5] hover:bg-[#1F3A46]',
            secondary: 'bg-[#C9B6FF] text-[#152A32] hover:bg-[#BBA2F5]',
            outline: 'border-2 border-[#152A32] text-[#152A32] hover:bg-[#152A32] hover:text-[#FFF0B5]',
        },
        eyebrowStyle: {
            classes: 'inline-block px-3 py-1 bg-[#C9B6FF] text-[#152A32] text-[10px] font-bold uppercase tracking-widest rounded-full',
        },
        sectionBackgroundStyle: {
            pattern: 'alternating',
            odd: 'bg-[#FFF0B5]',
            even: 'bg-white',
            cta: 'bg-[#152A32] text-[#FFF0B5]',
        },
    },
    {
        id: 'electric_contrast',
        name: 'Electric Contrast',
        description: 'Bold multi-color blocks with black and white anchors',
        swatches: ['#D9FF03', '#FD572A', '#695AC6', '#000000'],
        palette: {
            primary: '#D9FF03',
            secondary: '#FD572A',
            background: '#000000',
            foreground: '#FFFFFF',
            muted: '#201F1F',
            mutedForeground: '#A6A8AB',
            border: '#FFFFFF',
            card: '#201F1F',
            accent: '#695AC6',
        },
        typography: {
            label: 'Oswald + Inter',
            heading: "'Oswald', sans-serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Oswald:500,600,700', 'Inter:400,500,600,700'],
        },
        buttonStyle: {
            radius: 'rounded-none',
            primary: 'bg-[#D9FF03] text-black font-bold hover:bg-[#C8EB00]',
            secondary: 'bg-[#FD572A] text-white font-bold hover:bg-[#E64D24]',
            outline: 'border-2 border-white text-white hover:bg-white hover:text-black',
        },
        eyebrowStyle: {
            classes: 'inline-block px-3 py-1 bg-[#FD572A] text-white text-[10px] font-bold uppercase tracking-[0.15em]',
        },
        sectionBackgroundStyle: {
            pattern: 'alternating',
            odd: 'bg-black',
            even: 'bg-[#695AC6]',
            cta: 'bg-[#D9FF03] text-black',
        },
    },
    {
        id: 'soft_premium',
        name: 'Soft Premium',
        description: 'Muted warm neutrals with navy authority',
        swatches: ['#FFFCEE', '#FFF0B5', '#152A32', '#FFFFFF'],
        palette: {
            primary: '#152A32',
            secondary: '#FFF0B5',
            background: '#FFFCEE',
            foreground: '#152A32',
            muted: '#F5F0DD',
            mutedForeground: '#5E686D',
            border: '#E6DFC8',
            card: '#FFFFFF',
            accent: '#FFF0B5',
        },
        typography: {
            label: 'Cormorant Garamond + Inter',
            heading: "'Cormorant Garamond', Georgia, serif",
            body: "'Inter', system-ui, sans-serif",
            googleFonts: ['Cormorant+Garamond:600,700', 'Inter:400,500,600,700'],
        },
        buttonStyle: {
            radius: 'rounded-xl',
            primary: 'bg-[#152A32] text-[#FFFCEE] hover:bg-[#1F3A46]',
            secondary: 'bg-[#FFF0B5] text-[#152A32] hover:bg-[#F5E6A8]',
            outline: 'border border-[#152A32] text-[#152A32] hover:bg-[#152A32] hover:text-[#FFFCEE]',
        },
        eyebrowStyle: {
            classes: 'text-[#152A32] text-xs font-semibold uppercase tracking-[0.15em]',
        },
        sectionBackgroundStyle: {
            pattern: 'alternating',
            odd: 'bg-[#FFFCEE]',
            even: 'bg-white',
            cta: 'bg-[#152A32] text-[#FFFCEE]',
        },
    },
];

export function getFineTuningPreset(id) {
    return FINE_TUNING_PRESETS.find((preset) => preset.id === id) || null;
}

export function pickFineTuningPreset(text = '') {
    const normalized = String(text || '').toLowerCase();
    const keywordMap = [
        ['lime_pop', ['lime', 'neon', 'high contrast', 'off-white', 'black and white', 'bold', 'graphic']],
        ['cyan_storm', ['cyan', 'teal', 'storm', 'dark', 'tech', 'rounded']],
        ['cream_manela', ['cream', 'purple', 'navy', 'editorial', 'warm', 'soft']],
        ['bright_fresh', ['fresh', 'green', 'clean', 'light', 'startup', 'airy']],
        ['charcoal_citrus', ['charcoal', 'orange', 'citrus', 'energy', 'bold', 'contrast']],
        ['violet_noir', ['violet', 'noir', 'dark', 'editorial', 'purple', 'luxury']],
        ['teal_minimal', ['teal', 'minimal', 'clean', 'system', 'simple', 'modern']],
        ['sunset_warmth', ['sunset', 'warm', 'soft', 'premium', 'gold', 'calm']],
        ['electric_contrast', ['electric', 'contrast', 'multi-color', 'multi color', 'bold', 'graphic']],
        ['soft_premium', ['premium', 'neutral', 'soft', 'balanced', 'elevated', 'classic']],
    ];

    let bestPreset = FINE_TUNING_PRESETS[0] || null;
    let bestScore = -1;

    keywordMap.forEach(([presetId, keywords]) => {
        const preset = getFineTuningPreset(presetId);
        if (!preset) {
            return;
        }

        const score = keywords.reduce((total, keyword) => total + (normalized.includes(keyword) ? 1 : 0), 0);
        if (score > bestScore) {
            bestScore = score;
            bestPreset = preset;
        }
    });

    if (bestScore > 0 && bestPreset) {
        return bestPreset;
    }

    if (FINE_TUNING_PRESETS.length === 0) {
        return null;
    }

    const seed = normalized.trim();
    if (!seed) {
        return FINE_TUNING_PRESETS[Math.floor(Math.random() * FINE_TUNING_PRESETS.length)];
    }

    let hash = 0;
    for (let i = 0; i < seed.length; i += 1) {
        hash = ((hash << 5) - hash) + seed.charCodeAt(i);
        hash |= 0;
    }

    const index = Math.abs(hash) % FINE_TUNING_PRESETS.length;
    return FINE_TUNING_PRESETS[index];
}

const RADIUS_MAP = {
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

function cssVariableValue(key, value) {
    return `${key}: ${value};`;
}

export function applyFineTuneStylesToFiles(projectFiles, preset) {
    if (!preset || !Array.isArray(projectFiles) || projectFiles.length === 0) {
        return projectFiles;
    }

    const pal = preset.palette;
    const primaryFg = getContrastColor(pal.primary);
    const secondaryFg = getContrastColor(pal.secondary);
    const radiusValue = RADIUS_MAP[preset.buttonStyle?.radius] ?? '0.625rem';
    const sansFont = fontFamilyName(preset.typography.body);
    const headingFont = fontFamilyName(preset.typography.heading);

    const cssReplacements = {
        '--ds-primary': pal.primary,
        '--ds-secondary': pal.secondary,
        '--ds-background': pal.background,
        '--ds-foreground': pal.foreground,
        '--ds-muted': pal.muted,
        '--ds-muted-foreground': pal.mutedForeground,
        '--ds-border': pal.border,
        '--ds-card': pal.card,
        '--ds-card-foreground': pal.foreground,
        '--ds-accent': pal.accent,
        '--ds-accent-foreground': primaryFg,
        '--ds-primary-foreground': primaryFg,
        '--ds-secondary-foreground': secondaryFg,
        '--ds-ring': pal.primary,
        '--ds-radius': radiusValue,
        '--ds-font-sans': quoteFontValue(sansFont),
        '--ds-font-heading': quoteFontValue(headingFont),
        '--brand-primary': pal.primary,
        '--brand-secondary': pal.secondary,
        '--brand-bg': pal.background,
        '--brand-text': pal.foreground,
    };

    const googleFonts = preset.typography.googleFonts || [];
    const fontLinks = googleFonts.length
        ? [
            '<link rel="preconnect" href="https://fonts.googleapis.com">',
            '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>',
            `<link href="https://fonts.googleapis.com/css2?family=${googleFonts.map((f) => f.replace(/ /g, '+')).join('&family=')}&display=swap" rel="stylesheet">`,
        ].join('\n')
        : null;

    return projectFiles.map((file) => {
        const { path, content } = file;
        if (!path || typeof content !== 'string') {
            return file;
        }

        if (path === 'src/styles.css') {
            let updated = content;
            Object.entries(cssReplacements).forEach(([key, value]) => {
                const regex = new RegExp(`(${escapeRegex(key)}\\s*:\\s*)[^;]+;`, 'g');
                updated = updated.replace(regex, `$1${value};`);
            });
            return { ...file, content: updated };
        }

        if (path === 'index.html' && fontLinks) {
            let updated = content;
            if (!updated.includes('fonts.googleapis.com')) {
                updated = updated.replace(/<\/head>/i, `    ${fontLinks}\n  </head>`);
            }
            return { ...file, content: updated };
        }

        return file;
    });
}

function escapeRegex(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Build a focused AI iteration prompt for the parts fine-tuning cannot do
 * deterministically (eyebrow/subheading style and section background rhythm).
 * Colors, fonts, and radius are already updated in src/styles.css by
 * applyFineTuneStylesToFiles before this prompt is sent.
 */
export function buildFineTunePrompt(preset) {
    if (!preset) {
        return '';
    }

    const p = preset;
    const bg = p.sectionBackgroundStyle;

    const sections = [];

    sections.push('FINE-TUNE ONLY — do NOT change content, layout, structure, images, copy, or component logic. Preserve every existing section and every existing prop value. Colors, fonts, and border radius have already been updated in src/styles.css; do not modify src/styles.css or index.html.');

    sections.push(`1. BUTTON RADIUS CONSISTENCY: For any primary or secondary CTA that does not already use ${p.buttonStyle.radius}, add the class ${p.buttonStyle.radius} to the Button's className. Do NOT add arbitrary color classes; colors come from the CSS variables.`);

    sections.push(`2. EYEBROW / SUBHEADING CONSISTENCY: Every small eyebrow label above a heading must use these exact classes: ${p.eyebrowStyle.classes}. Preserve the eyebrow text content. If a section does not have an eyebrow, do not add one.`);

    if (bg.pattern === 'alternating') {
        sections.push(`3. SECTION BACKGROUND CONSISTENCY: Alternate section backgrounds: odd sections use ${bg.odd}; even sections use ${bg.even}. CTA/final band sections use ${bg.cta}. Only change the background className on the outermost section wrapper; do not touch inner content. Ensure text remains readable on each background.`);
    } else if (bg.pattern === 'gradient') {
        sections.push(`3. SECTION BACKGROUND CONSISTENCY: Use ${bg.base} as the default page background. Wrap alternate/hero sections in ${bg.gradient}. CTA/final band sections use ${bg.cta}. Only change the background className on the outermost section wrapper; do not touch inner content.`);
    }

    sections.push('4. Return ONLY the section component files you actually changed. Do not return src/styles.css, index.html, or unchanged files.');

    return sections.join('\n\n');
}

function fontFamilyName(cssFontValue) {
    if (!cssFontValue) {
        return 'Inter';
    }
    const match = cssFontValue.match(/['"]([^'"]+)['"]/);
    return match ? match[1] : cssFontValue.split(',')[0].trim();
}

function quoteFontValue(name) {
    return `"${String(name || 'Inter').trim()}"`;
}

function getContrastColor(hex) {
    const normalized = hex.replace('#', '');
    const r = parseInt(normalized.substring(0, 2), 16);
    const g = parseInt(normalized.substring(2, 4), 16);
    const b = parseInt(normalized.substring(4, 6), 16);
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
    return luminance > 0.5 ? '#000000' : '#FFFFFF';
}
