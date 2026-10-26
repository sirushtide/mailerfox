import * as CONTROL from '../controls/types.js';

const SIDES = ['Top', 'Right', 'Bottom', 'Left'];

function num(value) {
    const n = Number(value);
    return Number.isFinite(n) ? n : null;
}

function cssColor(value) {
    return typeof value === 'string' && value.trim() ? value.trim() : '';
}

// Scoped `:hover` CSS for an element container (background / border / shadow).
export function buildHoverCss(props, scopeClass) {
    const p = props || {};
    if (!scopeClass) return '';
    const out = [];

    if (p.hoverBackgroundType === 'gradient') {
        const from = cssColor(p.hoverGradientFrom) || '#111827';
        const to = cssColor(p.hoverGradientTo) || '#3b82f6';
        out.push(`background:linear-gradient(135deg,${from},${to})`);
    } else {
        const color = cssColor(p.hoverBackgroundColor);
        if (color) out.push(`background:${color}`);
    }

    const borderStyle = typeof p.hoverBorderStyle === 'string' && p.hoverBorderStyle && p.hoverBorderStyle !== 'default' ? p.hoverBorderStyle : '';
    if (borderStyle) out.push(`border-style:${borderStyle}`);
    const borderColor = cssColor(p.hoverBorderColor);
    if (borderColor) out.push(`border-color:${borderColor}`);
    for (const side of SIDES) {
        const w = num(p[`hoverBorderWidth${side}`]);
        if (w !== null) out.push(`border-${side.toLowerCase()}-width:${w}px`);
    }
    for (const [side, prop] of [['Top', 'border-top-left-radius'], ['Right', 'border-top-right-radius'], ['Bottom', 'border-bottom-right-radius'], ['Left', 'border-bottom-left-radius']]) {
        const r = num(p[`hoverBorderRadius${side}`]);
        if (r !== null) out.push(`${prop}:${r}px`);
    }

    const shadowColor = cssColor(p.hoverShadowColor);
    if (shadowColor) {
        const x = num(p.hoverShadowX) ?? 0;
        const y = num(p.hoverShadowY) ?? 0;
        const blur = num(p.hoverShadowBlur) ?? 0;
        const spread = num(p.hoverShadowSpread) ?? 0;
        out.push(`box-shadow:${x}px ${y}px ${blur}px ${spread}px ${shadowColor}`);
    }
    if (out.length === 0) return '';
    const important = out.map((decl) => `${decl} !important`);
    const transition = `.${scopeClass}{transition:background-color .25s ease, background .25s ease, border-color .25s ease, border-width .25s ease, border-radius .25s ease, box-shadow .25s ease;}`;
    return transition + `.${scopeClass}:hover{${important.join(';')};}`;
}

// STYLE-tab accordion with Background, Shadow and Border for the hover state.
export function hoverStyleAccordion() {
    return {
        title: 'Hover',
        defaultOpen: false,
        children: [
            {
                type: CONTROL.CONTROL_GROUP,
                kind: 'background',
                label: 'Background',
                bind: { type: 'props.hoverBackgroundType', color: 'props.hoverBackgroundColor', from: 'props.hoverGradientFrom', to: 'props.hoverGradientTo', gradientType: 'props.hoverGradientType', position: 'props.hoverGradientPosition' },
            },
            {
                type: CONTROL.CONTROL_GROUP,
                kind: 'box_shadow',
                label: 'Shadow',
                bind: { color: 'props.hoverShadowColor', offsetX: 'props.hoverShadowX', offsetY: 'props.hoverShadowY', blur: 'props.hoverShadowBlur', spread: 'props.hoverShadowSpread' },
            },
            {
                type: CONTROL.CONTROL_GROUP,
                kind: 'border',
                label: 'Border',
                includeRadius: true,
                radiusBind: { linked: 'props.hoverBorderRadiusLinked', t: 'props.hoverBorderRadiusTop', r: 'props.hoverBorderRadiusRight', b: 'props.hoverBorderRadiusBottom', l: 'props.hoverBorderRadiusLeft' },
                bind: { styleKey: 'props.hoverBorderStyle', colorKey: 'props.hoverBorderColor', widthKeys: { linked: 'props.hoverBorderWidthLinked', t: 'props.hoverBorderWidthTop', r: 'props.hoverBorderWidthRight', b: 'props.hoverBorderWidthBottom', l: 'props.hoverBorderWidthLeft' } },
            },
        ],
    };
}
