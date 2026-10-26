export function sanitizeSvg(svg) {
    return String(svg || '')
        .replace(/<\s*script\b[^>]*>[\s\S]*?<\s*\/\s*script\s*>/gi, '')
        .replace(/<\s*script\b[^>]*>/gi, '')
        .replace(/\son\w+\s*=\s*("[^"]*"|'[^']*'|[^\s>]+)/gi, '')
        .replace(/javascript\s*:/gi, '')
        .trim();
}

export function svgWithWidth(svg, width, fallback = '180px') {
    const clean = sanitizeSvg(svg);
    if (!clean) return '';
    const w = width || fallback;
    const style = `display:inline-block;max-width:100%;width:${w};height:auto;`;
    if (/<svg\b[^>]*\bstyle\s*=/i.test(clean)) {
        return clean.replace(/(<svg\b[^>]*\bstyle\s*=\s*)(["'])([\s\S]*?)\2/i, (m, pre, q, existing) => `${pre}${q}${existing};${style}${q}`);
    }
    return clean.replace(/<svg\b/i, `<svg style="${style}"`);
}
