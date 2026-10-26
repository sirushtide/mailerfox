import React from 'react';
import * as CONTROL from '../controls/types.js';
import { registerElement } from '../elementRegistry.js';
import { resolveColorToken } from '../controls/bind.js';
import { svgWithWidth } from '../lib/svg.js';
import { buildHoverCss, hoverStyleAccordion } from '../reusable/elementHover.js';
import { pickResponsive } from '../reusable/builderViewport.js';

function Stars({ value, color, size }) {
    const rating = Math.max(0, Math.min(5, Number(value) || 0));
    return (
        <div style={{ display: 'flex', gap: 2 }}>
            {[0, 1, 2, 3, 4].map((i) => (
                <svg key={i} width={size} height={size} viewBox="0 0 24 24" fill={i < Math.round(rating) ? color : 'none'} stroke={color} strokeWidth="1.5" style={{ flexShrink: 0 }}>
                    <path d="M12 2.5l2.9 5.88 6.49.94-4.7 4.58 1.11 6.46L12 17.3l-5.8 3.06 1.11-6.46-4.7-4.58 6.49-.94z" />
                </svg>
            ))}
        </div>
    );
}

function len(value, fallback) {
    if (value && typeof value === 'object') {
        const n = Number(value.value);
        return Number.isFinite(n) ? `${n}${value.unit || 'px'}` : fallback;
    }
    const n = Number(value);
    return Number.isFinite(n) ? `${n}px` : fallback;
}

// Shared card renderer used by both the single "Testimonial" element and each
// item of the "Testimonial Carousel". Content comes from `item`; styling comes
// from the element props.
export function renderTestimonialCard(props, item, globals = {}, scopeId = '') {
    const p = props || {};
    const it = item || {};
    const resolveColor = (val, fallback) => {
        const resolved = resolveColorToken(val, globals);
        return typeof resolved === 'string' && resolved.trim() ? resolved : fallback;
    };

    const rating = Number.isFinite(Number(it.rating)) ? Math.max(0, Math.min(5, Number(it.rating))) : 5;
    const starColor = resolveColor(p.starColor, '#F59E0B');
    const starSize = Number.isFinite(Number(p.starSize)) ? Number(p.starSize) : 18;
    const quote = typeof it.quote === 'string' ? it.quote : '';
    const quoteColor = resolveColor(p.quoteColor, '#111827');
    const name = typeof it.name === 'string' ? it.name : '';
    const role = typeof it.role === 'string' ? it.role : '';
    const nameColor = resolveColor(p.nameColor, '#111827');
    const roleColor = resolveColor(p.roleColor, '#6b7280');
    const avatarSize = Number.isFinite(Number(p.avatarSize)) ? Number(p.avatarSize) : 44;

    const avatarUrl = typeof it.avatarUrl === 'string' ? it.avatarUrl : '';
    const avatarSvg = typeof it.avatarSvg === 'string' ? it.avatarSvg : '';
    const avatarSource = it.avatarSource === 'svg' || it.avatarSource === 'url' ? it.avatarSource : '';
    const useSvg = avatarSvg !== '' && (avatarSource === 'svg' || avatarUrl === '');

    const containerStyle = {
        display: 'flex',
        flexDirection: 'column',
        gap: Number.isFinite(Number(p.cardGap)) ? Number(p.cardGap) : 12,
        boxSizing: 'border-box',
        height: '100%',
    };

    const bgType = p.backgroundType === 'gradient' ? 'gradient' : 'color';
    if (bgType === 'gradient') {
        const from = resolveColor(p.gradientFrom, '#111827');
        const to = resolveColor(p.gradientTo, '#3b82f6');
        containerStyle.background = `linear-gradient(135deg, ${from}, ${to})`;
    } else if (p.backgroundColor) {
        containerStyle.background = resolveColor(p.backgroundColor, '');
    }

    if (p.paddingTop !== undefined) containerStyle.paddingTop = len(p.paddingTop, '24px');
    if (p.paddingRight !== undefined) containerStyle.paddingRight = len(p.paddingRight, '24px');
    if (p.paddingBottom !== undefined) containerStyle.paddingBottom = len(p.paddingBottom, '24px');
    if (p.paddingLeft !== undefined) containerStyle.paddingLeft = len(p.paddingLeft, '24px');
    if (p.marginTop !== undefined) containerStyle.marginTop = len(p.marginTop, '0px');
    if (p.marginRight !== undefined) containerStyle.marginRight = len(p.marginRight, '0px');
    if (p.marginBottom !== undefined) containerStyle.marginBottom = len(p.marginBottom, '0px');
    if (p.marginLeft !== undefined) containerStyle.marginLeft = len(p.marginLeft, '0px');

    if (p.borderStyle && p.borderStyle !== 'default') containerStyle.borderStyle = p.borderStyle;
    if (p.borderColor) containerStyle.borderColor = resolveColor(p.borderColor, '');
    if (p.borderWidthTop !== undefined) containerStyle.borderTopWidth = len(p.borderWidthTop, '0px');
    if (p.borderWidthRight !== undefined) containerStyle.borderRightWidth = len(p.borderWidthRight, '0px');
    if (p.borderWidthBottom !== undefined) containerStyle.borderBottomWidth = len(p.borderWidthBottom, '0px');
    if (p.borderWidthLeft !== undefined) containerStyle.borderLeftWidth = len(p.borderWidthLeft, '0px');
    if (p.borderRadiusTop !== undefined) containerStyle.borderTopLeftRadius = len(p.borderRadiusTop, '12px');
    if (p.borderRadiusRight !== undefined) containerStyle.borderTopRightRadius = len(p.borderRadiusRight, '12px');
    if (p.borderRadiusBottom !== undefined) containerStyle.borderBottomRightRadius = len(p.borderRadiusBottom, '12px');
    if (p.borderRadiusLeft !== undefined) containerStyle.borderBottomLeftRadius = len(p.borderRadiusLeft, '12px');

    const quoteStyle = {
        margin: 0,
        color: quoteColor,
        fontSize: len(pickResponsive(p.quoteFontSize, p.quoteFontSizeTablet, p.quoteFontSizeMobile), '16px'),
        lineHeight: 1.6,
    };
    if (p.quoteFontFamily) quoteStyle.fontFamily = p.quoteFontFamily;
    if (p.quoteFontWeight) quoteStyle.fontWeight = p.quoteFontWeight;
    const quoteLh = pickResponsive(p.quoteLineHeight, p.quoteLineHeightTablet, p.quoteLineHeightMobile);
    if (quoteLh !== undefined && Number.isFinite(Number(quoteLh))) quoteStyle.lineHeight = Number(quoteLh);
    if (p.quoteLetterSpacing !== undefined) quoteStyle.letterSpacing = len(p.quoteLetterSpacing, '0px');
    if (p.quoteWordSpacing !== undefined) quoteStyle.wordSpacing = len(p.quoteWordSpacing, '0px');

    const nameStyle = {
        color: nameColor,
        fontWeight: p.nameFontWeight || 600,
        fontSize: len(pickResponsive(p.nameFontSize, p.nameFontSizeTablet, p.nameFontSizeMobile), '15px'),
    };
    if (p.nameFontFamily) nameStyle.fontFamily = p.nameFontFamily;
    if (p.nameLetterSpacing !== undefined) nameStyle.letterSpacing = len(p.nameLetterSpacing, '0px');

    const avatarStyle = {
        width: `${avatarSize}px`,
        height: `${avatarSize}px`,
        borderRadius: '999px',
        objectFit: 'cover',
        flexShrink: 0,
    };

    let avatarEl = null;
    if (useSvg) {
        avatarEl = <div style={{ width: `${avatarSize}px`, height: `${avatarSize}px`, flexShrink: 0 }} dangerouslySetInnerHTML={{ __html: svgWithWidth(avatarSvg, `${avatarSize}px`, `${avatarSize}px`) }} />;
    } else if (avatarUrl) {
        avatarEl = <img src={avatarUrl} alt={name} style={avatarStyle} />;
    } else {
        avatarEl = (
            <div style={{ ...avatarStyle, background: '#e2e8f0', color: '#64748b', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 600 }}>
                {(name || '?').trim().charAt(0).toUpperCase()}
            </div>
        );
    }

    const hoverScope = scopeId ? `mp-testimonial-hover-${String(scopeId).replace(/[^a-zA-Z0-9_-]/g, '')}` : '';
    const hoverCss = hoverScope ? buildHoverCss(p, hoverScope) : '';

    return (
        <div className={`mp-testimonial ${hoverScope}`} style={containerStyle}>
            {hoverCss ? <style dangerouslySetInnerHTML={{ __html: hoverCss }} /> : null}
            <Stars value={rating} color={starColor} size={starSize} />
            {quote ? <div style={quoteStyle} dangerouslySetInnerHTML={{ __html: quote }} /> : null}
            <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginTop: 'auto' }}>
                {avatarEl}
                <div style={{ minWidth: 0 }}>
                    {name ? <div style={nameStyle}>{name}</div> : null}
                    {role ? <div style={{ color: roleColor, fontSize: 13 }}>{role}</div> : null}
                </div>
            </div>
        </div>
    );
}

export function renderTestimonialPreview(widget, helpers = {}) {
    const p = widget.props || {};
    const item = {
        avatarUrl: p.avatarUrl,
        avatarSvg: p.avatarSvg,
        avatarSource: p.avatarSource,
        rating: p.rating,
        quote: p.quote,
        name: p.name,
        role: p.role,
    };
    return renderTestimonialCard(p, item, helpers.globals || {}, widget.id || '');
}

// Style controls shared by the single element and the carousel.
export const testimonialStyleSchema = [
    {
        title: 'Container',
        defaultOpen: true,
        children: [
            { type: CONTROL.CONTROL_GROUP, kind: 'border', label: 'Border', bind: { styleKey: 'props.borderStyle', colorKey: 'props.borderColor', widthKeys: { linked: 'props.borderWidthLinked', t: 'props.borderWidthTop', r: 'props.borderWidthRight', b: 'props.borderWidthBottom', l: 'props.borderWidthLeft' } }, includeRadius: true, radiusBind: { linked: 'props.borderRadiusLinked', t: 'props.borderRadiusTop', r: 'props.borderRadiusRight', b: 'props.borderRadiusBottom', l: 'props.borderRadiusLeft' } },
            { type: CONTROL.CONTROL_GROUP, kind: 'background', label: 'Background', bind: { type: 'props.backgroundType', color: 'props.backgroundColor', from: 'props.gradientFrom', to: 'props.gradientTo', fromLocation: 'props.gradientFromLocation', toLocation: 'props.gradientToLocation', gradientType: 'props.gradientType', position: 'props.gradientPosition', angle: 'props.gradientAngle' } },
            { type: CONTROL.CONTROL_GROUP, kind: 'spacing', label: 'Spacing', bind: { padding: { linked: 'props.paddingLinked', t: 'props.paddingTop', r: 'props.paddingRight', b: 'props.paddingBottom', l: 'props.paddingLeft' }, margin: { linked: 'props.marginLinked', t: 'props.marginTop', r: 'props.marginRight', b: 'props.marginBottom', l: 'props.marginLeft' } } },
            { type: CONTROL.CONTROL_SLIDER, name: 'cardGap', label: 'Item Gap', bind: 'props.cardGap', kind: 'gap' },
        ],
    },
    {
        title: 'Rating',
        defaultOpen: false,
        children: [
            { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'starColor', label: 'Color', bind: 'props.starColor' },
            { type: CONTROL.CONTROL_SLIDER, name: 'starSize', label: 'Size', bind: 'props.starSize', kind: 'font-size' },
        ],
    },
    {
        title: 'Quote',
        defaultOpen: false,
        children: [
            { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Typography', bind: { fontFamily: 'props.quoteFontFamily', fontSize: 'props.quoteFontSize', fontWeight: 'props.quoteFontWeight', lineHeight: 'props.quoteLineHeight', letterSpacing: 'props.quoteLetterSpacing', wordSpacing: 'props.quoteWordSpacing' } },
            { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'quoteColor', label: 'Color', bind: 'props.quoteColor' },
        ],
    },
    {
        title: 'Avatar',
        defaultOpen: false,
        children: [
            { type: CONTROL.CONTROL_SLIDER, name: 'avatarSize', label: 'Image Size', bind: 'props.avatarSize', kind: 'width' },
            { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Name Typography', bind: { fontFamily: 'props.nameFontFamily', fontSize: 'props.nameFontSize', fontWeight: 'props.nameFontWeight', lineHeight: 'props.nameLineHeight', letterSpacing: 'props.nameLetterSpacing', wordSpacing: 'props.nameWordSpacing' } },
            { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'nameColor', label: 'Name Color', bind: 'props.nameColor' },
            { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'roleColor', label: 'Title Color', bind: 'props.roleColor' },
        ],
    },
    hoverStyleAccordion(),
];

export const testimonialStyleDefaults = {
    starColor: '#F59E0B',
    starSize: 18,
    cardGap: 12,
    quoteColor: '#111827',
    quoteFontSize: 16,
    quoteFontFamily: '',
    quoteFontWeight: '',
    quoteLineHeight: undefined,
    nameColor: '#111827',
    nameFontSize: 15,
    nameFontWeight: '',
    nameFontFamily: '',
    roleColor: '#6b7280',
    avatarSize: 44,
    backgroundType: 'color',
    backgroundColor: '',
    borderRadiusTop: 12,
    borderRadiusRight: 12,
    borderRadiusBottom: 12,
    borderRadiusLeft: 12,
    paddingTop: 24,
    paddingRight: 24,
    paddingBottom: 24,
    paddingLeft: 24,
    marginTop: 0,
    marginRight: 0,
    marginBottom: 0,
    marginLeft: 0,
};

const testimonialSchema = {
    content: [
        {
            title: 'Content',
            defaultOpen: true,
            children: [
                { type: CONTROL.CONTROL_MEDIA_SOURCES, name: 'avatarSources', label: 'Avatar Image', bind: { url: 'props.avatarUrl', svg: 'props.avatarSvg', source: 'props.avatarSource' } },
                { type: CONTROL.CONTROL_NUMBER, name: 'rating', label: 'Rating (0-5)', bind: 'props.rating', min: 0, max: 5 },
                { type: CONTROL.CONTROL_WYSIWYG, name: 'quote', label: 'Quote', bind: 'props.quote' },
                { type: CONTROL.CONTROL_TEXT, name: 'name', label: 'Name', bind: 'props.name' },
                { type: CONTROL.CONTROL_TEXT, name: 'role', label: 'Role', bind: 'props.role' },
            ],
        },
    ],
    style: testimonialStyleSchema,
    advanced: [],
};

registerElement({
    type: 'testimonial',
    label: 'Testimonial',
    group: 'Basic',
    defaultProps: {
        avatarUrl: '',
        avatarSvg: '',
        avatarSource: 'url',
        rating: 5,
        quote: 'Amazing product and support!',
        name: 'Jane Doe',
        role: 'CEO, Acme',
        ...testimonialStyleDefaults,
    },
    render: renderTestimonialPreview,
    schema: testimonialSchema,
});
