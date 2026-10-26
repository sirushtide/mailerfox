// Shared helpers for turning editor/AI typography props into CSS values.
// Normalizes pt -> px (1pt = 96/72px) and lets line-height stay a unitless multiplier.

const UNIT_RE = /^([-+]?\d+(?:\.\d+)?)\s*([a-z%]*)$/i;

export function round(n, decimals = 2) {
    const f = 10 ** decimals;
    return Math.round(n * f) / f;
}

/**
 * Normalize a raw prop value into a CSS length string, or null when unsupported.
 * options.lineHeight — treat unitless numbers below LINE_HEIGHT_MAX_UNITLESS as multipliers.
 */
export function cssLengthToCss(value, { lineHeight = false } = {}) {
    if (value === null || value === undefined || value === '') return null;
    if (value && typeof value === 'object' && !Array.isArray(value)) {
        const u = typeof value.unit === 'string' ? value.unit : '';
        const v = value.value;
        if (v === '' || v === null || v === undefined) return null;
        if (u === 'auto') return 'auto';
        const n = Number(v);
        if (Number.isFinite(n)) {
            return cssLengthToCss(`${n}${u}`, { lineHeight });
        }
        return null;
    }
    if (typeof value === 'number') {
        if (lineHeight && value > 0 && value < 6) return String(value);
        return `${value}px`;
    }
    if (typeof value !== 'string') return null;

    const raw = String(value).trim();
    const match = raw.match(UNIT_RE);
    if (!match) return null;

    const num = parseFloat(match[1]);
    if (!Number.isFinite(num)) return null;
    const unit = match[2].toLowerCase();

    if (unit === 'pt') {
        return `${round(num * 4 / 3)}px`;
    }
    if (unit === 'px') {
        return `${round(num)}px`;
    }
    if (unit === '' || unit === 'rem' || unit === 'em' || unit === '%') {
        if (unit === '') {
            if (lineHeight && num > 0 && num < 6) return String(num);
            return `${num}px`;
        }
        if (lineHeight && num > 0 && num < 6) return String(num);
        return `${num}${unit}`;
    }

    return null;
}

/**
 * Normalize a raw prop value into a px number for the editor inputs (pt -> px,
 * px -> number, unitless -> number). Returns null when not a number.
 */
export function pxNumber(value) {
    if (value === null || value === undefined || value === '') return null;
    if (typeof value === 'number') return Number.isFinite(value) ? value : null;

    if (typeof value === 'string') {
        const match = String(value).trim().match(UNIT_RE);
        if (!match) return null;
        const num = parseFloat(match[1]);
        if (!Number.isFinite(num)) return null;
        return match[2].toLowerCase() === 'pt' ? round(num * 4 / 3) : num;
    }

    return null;
}

/** Strip tags (and decode basic entities) for plain-text extraction, e.g. heading html -> text. */
export function stripHtml(input) {
    if (input === null || input === undefined) return '';
    const raw = String(input);
    if (!/<\/?[a-z][^>]*>/i.test(raw)) return raw;
    return raw
        .replace(/<[^>]*>/g, ' ')
        .replace(/&nbsp;/gi, ' ')
        .replace(/&amp;/gi, '&')
        .replace(/&lt;/gi, '<')
        .replace(/&gt;/gi, '>')
        .replace(/&quot;/gi, '"')
        .replace(/&#39;/gi, "'")
        .replace(/\s+/g, ' ')
        .trim();
}