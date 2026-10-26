// Shared active-builder-viewport state so element renderers can resolve
// responsive values in the canvas (which is a fixed-width preview, not the
// real viewport, so media queries do not apply while editing).
let current = 'desktop';

export function setBuilderViewport(value) {
    current = value === 'tablet' || value === 'mobile' ? value : 'desktop';
}

export function getBuilderViewport() {
    return current;
}

export function pickResponsive(base, tablet, mobile) {
    const has = (v) => v !== undefined && v !== null && v !== '';
    if (current === 'mobile') return has(mobile) ? mobile : has(tablet) ? tablet : base;
    if (current === 'tablet') return has(tablet) ? tablet : base;
    return base;
}
