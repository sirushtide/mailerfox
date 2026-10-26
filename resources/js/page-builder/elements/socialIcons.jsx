import React from 'react';
import * as CONTROL from '../controls/types.js';
import { registerElement } from '../elementRegistry.js';

function resolveColor(value, globals) {
    if (!value) return '';
    const palette = globals?.colors || {};
    const v = String(value).trim();
    return palette[v] || v;
}

function buildIconStyle(props, state, globals) {
    const prefix = state === 'hover' ? 'iconHover' : 'icon';
    const style = {};
    const bgType = props[`${prefix}BgType`] || 'color';

    if (bgType === 'gradient') {
        const from = resolveColor(props[`${prefix}BgGradientFrom`], globals);
        const to = resolveColor(props[`${prefix}BgGradientTo`], globals);
        if (from && to) {
            const gradientType = props[`${prefix}BgGradientType`] || 'linear';
            if (gradientType === 'radial') {
                const position = props[`${prefix}BgGradientPosition`] || 'center';
                style.backgroundImage = `radial-gradient(circle at ${position}, ${from}, ${to})`;
            } else {
                const angle = Number.isFinite(Number(props[`${prefix}BgGradientAngle`])) ? Number(props[`${prefix}BgGradientAngle`]) : 135;
                style.backgroundImage = `linear-gradient(${angle}deg, ${from}, ${to})`;
            }
        }
    } else {
        const color = resolveColor(props[`${prefix}BgColor`], globals);
        if (color) style.backgroundColor = color;
    }

    const borderStyle = props[`${prefix}BorderStyle`];
    const borderColor = resolveColor(props[`${prefix}BorderColor`], globals);
    const borderWidth = Number.isFinite(Number(props[`${prefix}BorderWidth`])) ? Number(props[`${prefix}BorderWidth`]) : 0;
    if (borderStyle && borderStyle !== '' && borderColor && borderWidth > 0) {
        style.borderStyle = borderStyle;
        style.borderColor = borderColor;
        style.borderWidth = `${borderWidth}px`;
    }

    const borderRadius = Number.isFinite(Number(props[`${prefix}BorderRadius`])) ? Number(props[`${prefix}BorderRadius`]) : null;
    if (borderRadius !== null && borderRadius >= 0) {
        style.borderRadius = `${borderRadius}px`;
    }

    const iconColor = resolveColor(props[`${prefix}Color`], globals);
    if (iconColor) style.color = iconColor;

    return style;
}

function styleToCss(style) {
    return Object.entries(style)
        .filter(([, v]) => v !== '' && v !== undefined && v !== null)
        .map(([k, v]) => `${k.replace(/[A-Z]/g, (m) => '-' + m.toLowerCase())}:${v}`)
        .join(';');
}

export function renderSocialIconsPreview(widget, helpers = {}) {
    const w = widget;
    const globals = helpers.globals || w.globals || {};
    const icons = Array.isArray(w.props?.icons) ? w.props.icons : [];
    const size = w.props?.size || '20';
    const gap = w.props?.gap || '16';
    const align = w.props?.align || 'left';
    const props = w.props || {};

    const advancedJustify = w.advanced?.justifyContent;
    const justify = ['flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'].includes(advancedJustify)
        ? advancedJustify
        : (align === 'center' ? 'center' : align === 'right' ? 'flex-end' : 'flex-start');
    const scopeClass = `mp-sci-${String(w.id || '0').replace(/[^a-zA-Z0-9]/g, '-')}`;
    const normalStyle = buildIconStyle(props, 'normal', globals);
    const hoverStyle = buildIconStyle(props, 'hover', globals);
    const normalCss = styleToCss(normalStyle);
    const hoverCss = styleToCss(hoverStyle);

    const css = `.${scopeClass} .mp-social-icon { display:inline-flex;align-items:center;justify-content:center;width:${Number(size) + 8}px;height:${Number(size) + 8}px;${normalCss ? ';' + normalCss : ''} }
.${scopeClass} .mp-social-icon svg { width:${size}px;height:${size}px;display:block;fill:currentColor; }
.${scopeClass} .mp-social-icon:hover { ${hoverCss} }`;

    return (
        <div className={`mp-social-icons ${scopeClass}`} style={{ display: 'flex', width: '100%', maxWidth: '100%', flexWrap: 'wrap', alignItems: 'center', justifyContent: justify, gap: `${gap}px` }}>
            {icons.length > 0 && <style>{css}</style>}
            {icons.length === 0 ? (
                <span className="text-sm text-gray-400">Social Icons (empty)</span>
            ) : (
                icons.map((icon, idx) => {
                    const svg = typeof icon?.svg === 'string' ? icon.svg.trim() : '';
                    const url = typeof icon?.url === 'string' ? icon.url.trim() : '';
                    const target = ['_self', '_parent', '_top'].includes(icon?.target) ? icon.target : '_blank';
                    if (!svg && !url) {
                        return null;
                    }
                    const content = svg ? (
                        <span className="mp-social-icon rounded-full bg-gray-100 text-gray-600 p-1" dangerouslySetInnerHTML={{ __html: svg }} />
                    ) : (
                        <span className="mp-social-icon rounded-full bg-gray-100 text-gray-600" style={{ fontSize: `${size}px` }}>?</span>
                    );
                    return url ? (
                        <a key={idx} href={url} target={target} rel={target === '_blank' ? 'noopener noreferrer' : undefined} className="mp-social-icon-link">
                            {content}
                        </a>
                    ) : (
                        <span key={idx}>{content}</span>
                    );
                })
            )}
        </div>
    );
}

const socialIconsSchema = {
    content: [
        { type: CONTROL.CONTROL_TEXT, name: 'size', label: 'Icon Size (px)', bind: 'props.size' },
        { type: CONTROL.CONTROL_TEXT, name: 'gap', label: 'Gap (px)', bind: 'props.gap' },
        { type: CONTROL.CONTROL_SELECT, name: 'align', label: 'Align', bind: 'props.align', options: [
            { value: 'left', label: 'Left' },
            { value: 'center', label: 'Center' },
            { value: 'right', label: 'Right' },
        ]},
        { type: CONTROL.CONTROL_GROUP, kind: 'repeater', label: 'Icons', bind: 'props.icons', itemSchema: {
            itemLabel: 'Icon',
            defaultItem: () => ({ svg: '', url: '', target: '_blank' }),
            fields: [
                { name: 'svg', label: 'SVG' },
                { name: 'url', label: 'URL' },
                { name: 'target', label: 'Target' },
            ],
        }},
    ],
    style: [],
    advanced: [],
};

registerElement({
    type: 'social_icons',
    label: 'Social Icons',
    group: 'Basic',
    defaultProps: { size: '20', gap: '16', align: 'left', icons: [] },
    render: renderSocialIconsPreview,
    schema: socialIconsSchema,
});
