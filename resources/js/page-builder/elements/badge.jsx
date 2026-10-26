import React from 'react';
import { registerElement } from '../elementRegistry.js';
import * as CONTROL from '../controls/types.js';
import { borderEffectClassName, borderEffectStyleVars } from '../reusable/borderEffect.jsx';
import { injectSvgColor } from './icon.jsx';

function num(v, fallback) {
    return typeof v === 'number' && Number.isFinite(v) ? v : fallback;
}

function str(v, fallback) {
    return typeof v === 'string' && v.trim() ? v : fallback;
}

function getLeadingPaddingLeft(leading) {
    if (leading === 'label') return 6;
    if (leading === 'icon' || leading === 'dot') return 10;
    return 14;
}

export function renderBadgePreview(widget) {
    const p = (widget && widget.props) || {};

    const text = typeof p.text === 'string' ? p.text : 'Announcing ShadcnSpace 1.0';
    const leading = ['none', 'label', 'dot', 'icon'].includes(p.leading) ? p.leading : 'label';
    const align = ['left', 'center', 'right'].includes(p.align) ? p.align : 'left';
    const gap = num(p.gap, 8);

    // Typography / text
    const inner = {
        display: 'inline-flex',
        alignItems: 'center',
        gap: `${gap}px`,
        boxSizing: 'border-box',
        paddingTop: `${num(p.paddingTop, 6)}px`,
        paddingRight: `${num(p.paddingRight, 14)}px`,
        paddingBottom: `${num(p.paddingBottom, 6)}px`,
        paddingLeft: `${num(p.paddingLeft, getLeadingPaddingLeft(leading))}px`,
        color: str(p.textColor, '#111827'),
    };

    if (str(p.fontFamily, '')) inner.fontFamily = p.fontFamily;
    inner.fontSize = `${num(p.fontSize, 14)}px`;
    if (str(p.fontWeight, '')) inner.fontWeight = p.fontWeight;
    if (num(p.lineHeight, null) != null) inner.lineHeight = `${p.lineHeight}px`;
    if (num(p.letterSpacing, null) != null) inner.letterSpacing = `${p.letterSpacing}px`;
    if (num(p.wordSpacing, null) != null) inner.wordSpacing = `${p.wordSpacing}px`;

    // Background
    const bgType = str(p.backgroundType, 'classic');
    if (bgType === 'gradient') {
        const from = str(p.gradientFrom, '#111827');
        const to = str(p.gradientTo, '#3b82f6');
        const fromLoc = num(p.gradientFromLocation, 0);
        const toLoc = num(p.gradientToLocation, 100);
        const gType = str(p.gradientType, 'linear');
        const position = str(p.gradientPosition, 'top center');
        inner.backgroundImage = gType === 'radial'
            ? `radial-gradient(at ${position}, ${from} ${fromLoc}%, ${to} ${toLoc}%)`
            : `linear-gradient(135deg, ${from} ${fromLoc}%, ${to} ${toLoc}%)`;
    } else {
        inner.backgroundColor = str(p.backgroundColor, '#ffffff');
    }

    // Radius (full pill by default)
    const rT = num(p.borderRadiusTop, 9999);
    const rR = num(p.borderRadiusRight, 9999);
    const rB = num(p.borderRadiusBottom, 9999);
    const rL = num(p.borderRadiusLeft, 9999);
    inner.borderTopLeftRadius = `${rT}px`;
    inner.borderTopRightRadius = `${rR}px`;
    inner.borderBottomRightRadius = `${rB}px`;
    inner.borderBottomLeftRadius = `${rL}px`;

    const bfxClass = borderEffectClassName(p);
    const bfxVars = borderEffectStyleVars(p);

    const useGradientBorder = p.useGradientBorder === true && !bfxClass;
    let borderWrapperStyle = null;

    if (useGradientBorder) {
        const bw = num(p.borderWidthTop, 1.5);
        const gFrom = str(p.borderGradientFrom, '#3b82f6');
        const gTo = str(p.borderGradientTo, '#a855f7');
        borderWrapperStyle = {
            display: 'inline-flex',
            padding: `${bw}px`,
            backgroundImage: `linear-gradient(135deg, ${gFrom}, ${gTo})`,
            borderTopLeftRadius: `${rT}px`,
            borderTopRightRadius: `${rR}px`,
            borderBottomRightRadius: `${rB}px`,
            borderBottomLeftRadius: `${rL}px`,
        };
    } else {
        const bs = str(p.borderStyle, 'solid');
        if (bs !== 'default' && bs !== 'none') {
            inner.borderStyle = bs;
            inner.borderColor = str(p.borderColor, '#e5e7eb');
            inner.borderTopWidth = `${num(p.borderWidthTop, 1)}px`;
            inner.borderRightWidth = `${num(p.borderWidthRight, 1)}px`;
            inner.borderBottomWidth = `${num(p.borderWidthBottom, 1)}px`;
            inner.borderLeftWidth = `${num(p.borderWidthLeft, 1)}px`;
        }
    }

    if (bfxClass) {
        Object.assign(inner, bfxVars);
    }

    // Leading chip / dot / icon
    let leadingEl = null;
    if (leading === 'label') {
        const chipStyle = {
            display: 'inline-flex',
            alignItems: 'center',
            justifyContent: 'center',
            padding: '2px 10px',
            borderRadius: '9999px',
            fontSize: `${num(p.labelFontSize, Math.max(11, num(p.fontSize, 14) - 1))}px`,
            fontWeight: str(p.labelFontWeight, '600'),
            color: str(p.labelColor, '#ffffff'),
            lineHeight: 1.2,
            whiteSpace: 'nowrap',
        };
        if (p.labelUseGradient === true) {
            chipStyle.backgroundImage = `linear-gradient(135deg, ${str(p.labelGradientFrom, '#3b82f6')}, ${str(p.labelGradientTo, '#1d4ed8')})`;
        } else {
            chipStyle.backgroundColor = str(p.labelBg, '#2563eb');
        }
        leadingEl = <span className="mp-badge__chip" style={chipStyle}>{typeof p.labelText === 'string' ? p.labelText : 'New'}</span>;
    } else if (leading === 'dot') {
        const dotSize = num(p.dotSize, 8);
        const dotStyle = {
            display: 'inline-block',
            width: `${dotSize}px`,
            height: `${dotSize}px`,
            borderRadius: '9999px',
            backgroundColor: str(p.dotColor, '#22c55e'),
            flexShrink: 0,
        };
        leadingEl = <span className="mp-badge__dot" style={dotStyle} />;
    } else if (leading === 'icon') {
        const iconSize = num(p.iconSize, 16);
        const iconColor = str(p.iconColor, inner.color);
        const iconUrl = str(p.iconUrl, '');
        const iconSvg = str(p.iconSvg, '');
        const iconStyle = {
            display: 'inline-flex',
            alignItems: 'center',
            justifyContent: 'center',
            width: `${iconSize}px`,
            height: `${iconSize}px`,
            flexShrink: 0,
            color: iconColor,
        };
        if (iconUrl) {
            leadingEl = (
                <span className="mp-badge__icon" style={iconStyle}>
                    <img src={iconUrl} alt="" style={{ width: '100%', height: '100%', objectFit: 'contain' }} />
                </span>
            );
        } else if (iconSvg) {
            const processedSvg = injectSvgColor(iconSvg, iconColor);
            leadingEl = <span className="mp-badge__icon" style={iconStyle} dangerouslySetInnerHTML={{ __html: processedSvg }} />;
        } else {
            leadingEl = (
                <span className="mp-badge__icon" style={iconStyle}>
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        style={{ width: '100%', height: '100%' }}
                    >
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 8v8" />
                        <path d="M8 12h8" />
                    </svg>
                </span>
            );
        }
    }

    const innerEl = (
        <span className={`mp-badge${bfxClass ? ' ' + bfxClass : ''}`} style={inner}>
            {leadingEl}
            <span className="mp-badge__text">{text}</span>
        </span>
    );

    const wrapperStyle = { textAlign: align };
    if (num(p.marginTop, null) != null) wrapperStyle.marginTop = `${p.marginTop}px`;
    if (num(p.marginRight, null) != null) wrapperStyle.marginRight = `${p.marginRight}px`;
    if (num(p.marginBottom, null) != null) wrapperStyle.marginBottom = `${p.marginBottom}px`;
    if (num(p.marginLeft, null) != null) wrapperStyle.marginLeft = `${p.marginLeft}px`;

    return (
        <div className="mp-badge-wrap" style={wrapperStyle}>
            {borderWrapperStyle ? <span style={borderWrapperStyle}>{innerEl}</span> : innerEl}
        </div>
    );
}

const badgeSchema = {
    content: [
        { type: CONTROL.CONTROL_TEXT, name: 'text', label: 'Text', bind: 'props.text' },
        { type: CONTROL.CONTROL_SELECT, name: 'leading', label: 'Leading', bind: 'props.leading', options: [{ value: 'label', label: 'Label Chip' }, { value: 'dot', label: 'Status Dot' }, { value: 'icon', label: 'Icon' }, { value: 'none', label: 'None' }] },
        { type: CONTROL.CONTROL_TEXT, name: 'labelText', label: 'Label Text', bind: 'props.labelText', condition: { leading: 'label' } },
        { type: CONTROL.CONTROL_MEDIA, name: 'iconUrl', label: 'Icon URL', bind: 'props.iconUrl', condition: { leading: 'icon' } },
        { type: CONTROL.CONTROL_TEXTAREA, name: 'iconSvg', label: 'Icon SVG', bind: 'props.iconSvg', rows: 4, condition: { leading: 'icon' } },
        { type: CONTROL.CONTROL_SELECT, name: 'align', label: 'Align', bind: 'props.align', options: [{ value: 'left', label: 'Left' }, { value: 'center', label: 'Center' }, { value: 'right', label: 'Right' }] },
    ],
    style: [
        { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Typography', bind: { fontFamily: 'props.fontFamily', fontSize: 'props.fontSize', fontWeight: 'props.fontWeight', lineHeight: 'props.lineHeight', letterSpacing: 'props.letterSpacing', wordSpacing: 'props.wordSpacing' } },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'textColor', label: 'Text Color', bind: 'props.textColor' },
        { type: CONTROL.CONTROL_SLIDER, name: 'gap', label: 'Gap', bind: 'props.gap', kind: 'gap' },
        { type: CONTROL.CONTROL_GROUP, kind: 'background', label: 'Background', bind: { type: 'props.backgroundType', color: 'props.backgroundColor', from: 'props.gradientFrom', to: 'props.gradientTo', fromLocation: 'props.gradientFromLocation', toLocation: 'props.gradientToLocation', gradientType: 'props.gradientType', position: 'props.gradientPosition' } },
        { type: CONTROL.CONTROL_SWITCHER, name: 'useGradientBorder', label: 'Gradient Border', bind: 'props.useGradientBorder' },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'borderGradientFrom', label: 'From', bind: 'props.borderGradientFrom', condition: { useGradientBorder: true } },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'borderGradientTo', label: 'To', bind: 'props.borderGradientTo', condition: { useGradientBorder: true } },
        { type: CONTROL.CONTROL_SLIDER, name: 'gradientBorderWidth', label: 'Width', bind: 'props.borderWidthTop', kind: 'border-width', condition: { useGradientBorder: true } },
        { type: CONTROL.CONTROL_GROUP, kind: 'border', label: 'Border', bind: { styleKey: 'props.borderStyle', colorKey: 'props.borderColor', widthKeys: { linked: 'props.borderWidthLinked', t: 'props.borderWidthTop', r: 'props.borderWidthRight', b: 'props.borderWidthBottom', l: 'props.borderWidthLeft' } }, includeRadius: false, condition: { useGradientBorder: false } },
        { type: CONTROL.CONTROL_DIMENSIONS, name: 'borderRadius', label: 'Border Radius', bind: { linked: 'props.borderRadiusLinked', t: 'props.borderRadiusTop', r: 'props.borderRadiusRight', b: 'props.borderRadiusBottom', l: 'props.borderRadiusLeft' }, kind: 'border-radius' },
        { type: CONTROL.CONTROL_GROUP, kind: 'border_effect', label: 'Animated Border', bind: { effect: 'props.borderEffect', color: 'props.borderEffectColor', color2: 'props.borderEffectColor2', speed: 'props.borderEffectSpeed', thickness: 'props.borderEffectThickness' } },
        { type: CONTROL.CONTROL_GROUP, kind: 'spacing', label: 'Spacing', bind: { padding: { linked: 'props.paddingLinked', t: 'props.paddingTop', r: 'props.paddingRight', b: 'props.paddingBottom', l: 'props.paddingLeft' }, margin: { linked: 'props.marginLinked', t: 'props.marginTop', r: 'props.marginRight', b: 'props.marginBottom', l: 'props.marginLeft' } } },
        { type: CONTROL.CONTROL_HEADING, label: 'Label Chip', condition: { leading: 'label' } },
        { type: CONTROL.CONTROL_SWITCHER, name: 'labelUseGradient', label: 'Use Gradient', bind: 'props.labelUseGradient', condition: { leading: 'label' } },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'labelGradientFrom', label: 'Chip From', bind: 'props.labelGradientFrom', condition: { leading: 'label', labelUseGradient: true } },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'labelGradientTo', label: 'Chip To', bind: 'props.labelGradientTo', condition: { leading: 'label', labelUseGradient: true } },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'labelBg', label: 'Chip Background', bind: 'props.labelBg', condition: { leading: 'label', labelUseGradient: false } },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'labelColor', label: 'Chip Text Color', bind: 'props.labelColor', condition: { leading: 'label' } },
        { type: CONTROL.CONTROL_HEADING, label: 'Status Dot', condition: { leading: 'dot' } },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'dotColor', label: 'Dot Color', bind: 'props.dotColor', condition: { leading: 'dot' } },
        { type: CONTROL.CONTROL_SLIDER, name: 'dotSize', label: 'Dot Size', bind: 'props.dotSize', kind: 'spacing', condition: { leading: 'dot' } },
        { type: CONTROL.CONTROL_HEADING, label: 'Icon', condition: { leading: 'icon' } },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'iconColor', label: 'Icon Color', bind: 'props.iconColor', condition: { leading: 'icon' } },
        { type: CONTROL.CONTROL_SLIDER, name: 'iconSize', label: 'Icon Size', bind: 'props.iconSize', kind: 'font-size', condition: { leading: 'icon' } },
    ],
    advanced: [],
};

registerElement({
    type: 'badge',
    label: 'Badge',
    group: 'Basic',
    defaultProps: { text: 'Announcing ShadcnSpace 1.0', leading: 'label' },
    render: renderBadgePreview,
    schema: badgeSchema,
});
