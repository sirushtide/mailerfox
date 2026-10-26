export const SLIDER_DEFAULTS = {
    'font-size': { min: 8, max: 200, step: 1, units: ['px', 'em', 'rem', '%'], defaultUnit: 'px' },
    'font-weight': { min: 100, max: 900, step: 100, units: [''], defaultUnit: '' },
    'line-height': { min: 0.5, max: 3, step: 0.05, units: ['', 'px', 'em'], defaultUnit: '' },
    'letter-spacing': { min: -5, max: 20, step: 0.1, units: ['px', 'em', 'rem'], defaultUnit: 'px' },
    'word-spacing': { min: -5, max: 50, step: 0.1, units: ['px', 'em', 'rem'], defaultUnit: 'px' },
    spacing: { min: 0, max: 200, step: 1, units: ['px', 'em', 'rem', '%'], defaultUnit: 'px' },
    'border-radius': { min: 0, max: 100, step: 1, units: ['px', 'em', 'rem', '%'], defaultUnit: 'px' },
    'border-width': { min: 0, max: 20, step: 0.5, units: ['px', 'em', 'rem'], defaultUnit: 'px' },
    'shadow-blur': { min: 0, max: 100, step: 1, units: ['px'], defaultUnit: 'px' },
    'shadow-spread': { min: -50, max: 50, step: 1, units: ['px'], defaultUnit: 'px' },
    width: { min: 0, max: 1920, step: 1, units: ['px', 'em', 'rem', '%', 'vw', 'vh', 'auto'], defaultUnit: 'px' },
    height: { min: 0, max: 1920, step: 1, units: ['px', 'em', 'rem', '%', 'vw', 'vh', 'auto'], defaultUnit: 'px' },
    gap: { min: 0, max: 100, step: 1, units: ['px', 'em', 'rem'], defaultUnit: 'px' },
    opacity: { min: 0, max: 1, step: 0.01, units: [''], defaultUnit: '' },
    angle: { min: 0, max: 360, step: 1, units: ['deg'], defaultUnit: 'deg' },
    offset: { min: -500, max: 500, step: 1, units: ['px', '%'], defaultUnit: 'px' },
    duration: { min: 0, max: 5, step: 0.1, units: ['s'], defaultUnit: 's' },
    'z-index': { min: -100, max: 1000, step: 1, units: [''], defaultUnit: '', plainNumber: true },
    count: { min: 0, max: 100, step: 1, units: [''], defaultUnit: '', plainNumber: true },
    default: { min: 0, max: 100, step: 1, units: ['px'], defaultUnit: 'px' },
};

export function getSliderDefaults(kind) {
    return SLIDER_DEFAULTS[kind] || SLIDER_DEFAULTS.default;
}
