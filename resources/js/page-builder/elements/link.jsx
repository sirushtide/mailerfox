import React from 'react';
import * as CONTROL from '../controls/types.js';
import { registerElement } from '../elementRegistry.js';
import { cssLengthToCss } from '../lib/typographyUtils.js';
import { HOVER_EFFECT_OPTIONS, hoverEffectCss, hoverEffectNeedsMagnetic } from '../lib/hoverEffects.js';

function linkStyle(props) {
    const style = {
        textDecoration: 'none',
        color: props.color || '#2563eb',
        display: 'inline-block',
    };
    if (props.fontFamily) style.fontFamily = props.fontFamily;
    if (props.fontSize !== undefined && props.fontSize !== null && props.fontSize !== '') style.fontSize = cssLengthToCss(props.fontSize);
    if (props.fontWeight) style.fontWeight = props.fontWeight;
    if (props.lineHeight !== undefined && props.lineHeight !== null && props.lineHeight !== '') style.lineHeight = String(props.lineHeight);
    if (props.letterSpacing !== undefined && props.letterSpacing !== null && props.letterSpacing !== '') style.letterSpacing = cssLengthToCss(props.letterSpacing);
    return style;
}

export function renderLinkPreview(widget) {
    const props = widget?.props || {};
    const items = Array.isArray(props.items) ? props.items : [];
    const gap = Number.isFinite(Number(props.gap)) ? Number(props.gap) : 12;
    const separator = typeof props.separator === 'string' ? props.separator : '·';
    const align = ['left', 'center', 'right'].includes(props.align) ? props.align : 'left';
    const advancedJustify = widget?.advanced?.justifyContent;
    const justify = ['flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'].includes(advancedJustify)
        ? advancedJustify
        : (align === 'center' ? 'center' : align === 'right' ? 'flex-end' : 'flex-start');
    const hoverColor = typeof props.hoverColor === 'string' ? props.hoverColor.trim() : '';
    const scopeClass = `mp-links-${String(widget?.id || '0').replace(/[^a-zA-Z0-9]/g, '-')}`;
    const isVertical = props.direction === 'vertical';

    const hoverEffect = HOVER_EFFECT_OPTIONS.some((o) => o.value && o.value === props.hoverEffect) ? props.hoverEffect : '';
    const effectCss = hoverEffectCss(hoverEffect, `.${scopeClass}`, '.mp-link');
    const magnetic = hoverEffectNeedsMagnetic(hoverEffect);

    const handleMag = (e) => {
        if (!magnetic) return;
        const r = e.currentTarget.getBoundingClientRect();
        if (!r.width) return;
        const x = ((e.clientX - r.left) / r.width) * 100;
        e.currentTarget.style.setProperty('--mp-mag-origin', `${Math.max(0, Math.min(100, x))}%`);
    };

    const sepStyle = {
        color: props.color ? props.color : '#9ca3af',
        opacity: 0.7,
        fontFamily: props.fontFamily || undefined,
        fontSize: props.fontSize !== undefined && props.fontSize !== null && props.fontSize !== '' ? cssLengthToCss(props.fontSize) : undefined,
    };

    return (
        <div
            className={`mp-links ${scopeClass}`}
            style={{
                display: 'flex',
                width: '100%',
                maxWidth: '100%',
                flexDirection: isVertical ? 'column' : 'row',
                flexWrap: isVertical ? 'nowrap' : 'wrap',
                alignItems: isVertical ? (align === 'center' ? 'center' : align === 'right' ? 'flex-end' : 'flex-start') : 'center',
                justifyContent: isVertical ? 'flex-start' : justify,
                gap: `${gap}px`,
            }}
        >
            {hoverColor ? <style>{`.${scopeClass} .mp-link:hover{color:${hoverColor} !important;}`}</style> : null}
            {effectCss ? <style>{effectCss}</style> : null}
            {items.length === 0 ? (
                <span className="text-sm text-gray-400">Links (empty)</span>
            ) : (
                items.map((item, idx) => (
                    <React.Fragment key={idx}>
                        {!isVertical && idx > 0 && separator ? <span className="mp-link-sep" style={sepStyle}>{separator}</span> : null}
                        {(() => {
                            const label = item?.label || item?.url || 'Link';
                            return (
                                <a
                                    href={item?.url || '#'}
                                    target={item?.target || '_self'}
                                    rel={(item?.target || '_self') === '_blank' ? 'noopener noreferrer' : undefined}
                                    className="mp-link"
                                    style={linkStyle(props)}
                                    data-text={label}
                                    onMouseMove={magnetic ? handleMag : undefined}
                                >
                                    <span className="mp-link-text">{label}</span>
                                </a>
                            );
                        })()}
                    </React.Fragment>
                ))
            )}
        </div>
    );
}

const linkSchema = {
    content: [
        {
            type: CONTROL.CONTROL_GROUP,
            kind: 'repeater',
            label: 'Links',
            bind: 'props.items',
            itemSchema: {
                itemLabel: 'Link',
                defaultItem: () => ({ label: 'Link', url: '#', target: '_self' }),
                fields: [
                    { name: 'label', label: 'Label' },
                    { name: 'url', label: 'URL' },
                ],
            },
        },
        { type: CONTROL.CONTROL_SELECT, name: 'direction', label: 'Direction', bind: 'props.direction', options: [
            { value: 'horizontal', label: 'Horizontal' },
            { value: 'vertical', label: 'Vertical' },
        ]},
        { type: CONTROL.CONTROL_SLIDER, name: 'gap', label: 'Gap (px)', bind: 'props.gap', min: 0, max: 80, step: 1, units: ['px'], defaultUnit: 'px' },
        { type: CONTROL.CONTROL_TEXT, name: 'separator', label: 'Gap Symbol', bind: 'props.separator', condition: { direction: { operator: '!=', value: 'vertical' } } },
        { type: CONTROL.CONTROL_SELECT, name: 'hoverEffect', label: 'Hover Effect', bind: 'props.hoverEffect', options: HOVER_EFFECT_OPTIONS },
    ],
    style: [
        {
            title: 'Link',
            defaultOpen: true,
            children: [
                {
                    type: CONTROL.CONTROL_GROUP,
                    kind: 'typography',
                    label: 'Typography',
                    bind: {
                        fontFamily: 'props.fontFamily',
                        fontSize: 'props.fontSize',
                        fontWeight: 'props.fontWeight',
                        align: 'props.align',
                        lineHeight: 'props.lineHeight',
                        letterSpacing: 'props.letterSpacing',
                        wordSpacing: 'props.wordSpacing',
                    },
                },
                { type: CONTROL.CONTROL_COLOR, name: 'color', label: 'Text Color', bind: 'props.color' },
                { type: CONTROL.CONTROL_COLOR, name: 'hoverColor', label: 'Text Hover Color', bind: 'props.hoverColor' },
            ],
        },
    ],
    advanced: [],
};

registerElement({
    type: 'link',
    label: 'Link',
    group: 'Basic',
    defaultProps: {
        items: [
            { label: 'Link one', url: '#', target: '_self' },
            { label: 'Link two', url: '#', target: '_self' },
        ],
        gap: 12,
        separator: '·',
        align: 'left',
        direction: 'horizontal',
        hoverEffect: '',
        color: '#2563eb',
        hoverColor: '#1d4ed8',
    },
    render: renderLinkPreview,
    schema: linkSchema,
});
