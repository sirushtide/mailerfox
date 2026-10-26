import React from 'react';
import { registerElement } from '../elementRegistry.js';
import * as CONTROL from '../controls/types.js';
import { resolveColorToken } from '../controls/bind.js';
import { cssLengthToCss } from '../lib/typographyUtils.js';

function splitHighlightTokens(input) {
    const s = String(input || '');
    const out = [];
    let i = 0;
    while (i < s.length) {
        const start = s.indexOf('{{', i);
        if (start === -1) {
            out.push({ type: 'text', value: s.slice(i) });
            break;
        }
        if (start > i) {
            out.push({ type: 'text', value: s.slice(i, start) });
        }
        const end = s.indexOf('}}', start + 2);
        if (end === -1) {
            out.push({ type: 'text', value: s.slice(start) });
            break;
        }
        out.push({ type: 'highlight', value: s.slice(start + 2, end) });
        i = end + 2;
    }
    return out;
}

function highlightStyleFromProps(props) {
    const p = props && typeof props === 'object' ? props : {};
    const mode = typeof p.highlightMode === 'string' ? p.highlightMode : 'none';
    if (!['none', 'color', 'gradient'].includes(mode)) return { mode: 'none' };

    const typo = {};
    if (typeof p.highlightFontFamily === 'string' && p.highlightFontFamily.trim()) typo.fontFamily = p.highlightFontFamily;
    if (typeof p.highlightFontWeight === 'string' && p.highlightFontWeight.trim()) typo.fontWeight = p.highlightFontWeight;
    const hFontSize = cssLengthToCss(p.highlightFontSize);
    if (hFontSize) typo.fontSize = hFontSize;
    const hLineHeight = cssLengthToCss(p.highlightLineHeight, { lineHeight: true });
    if (hLineHeight) typo.lineHeight = hLineHeight;
    const hLetterSpacing = cssLengthToCss(p.highlightLetterSpacing);
    if (hLetterSpacing) typo.letterSpacing = hLetterSpacing;
    const hWordSpacing = cssLengthToCss(p.highlightWordSpacing);
    if (hWordSpacing) typo.wordSpacing = hWordSpacing;

    if (mode === 'color') {
        const c = typeof p.highlightColor === 'string' ? p.highlightColor.trim() : '';
        const style = c ? { color: c, ...typo } : Object.keys(typo).length ? { ...typo } : undefined;
        return { mode: 'color', style, className: 'mp-text-highlight' };
    }

    if (mode === 'gradient') {
        const from = typeof p.highlightGradientFrom === 'string' && p.highlightGradientFrom.trim() ? p.highlightGradientFrom.trim() : '#111827';
        const to = typeof p.highlightGradientTo === 'string' && p.highlightGradientTo.trim() ? p.highlightGradientTo.trim() : '#3b82f6';
        const animate = p.highlightAnimate === true;
        const dur = Number.isFinite(Number(p.highlightAnimationDuration)) ? Math.max(1, Math.min(60, Number(p.highlightAnimationDuration))) : 6;

        const style = {
            backgroundImage: `linear-gradient(90deg, ${from}, ${to})`,
            WebkitBackgroundClip: 'text',
            backgroundClip: 'text',
            color: 'transparent',
            WebkitTextFillColor: 'transparent',
            ...(animate ? { backgroundSize: '200% 200%', animation: `mp-highlight-gradient ${dur}s linear infinite` } : null),
            ...typo,
        };

        return {
            mode: 'gradient',
            style,
            className: animate ? 'mp-text-highlight mp-text-highlight--animate' : 'mp-text-highlight',
        };
    }

    return { mode: 'none' };
}

function applyHighlightTokensToHtml(html, highlight) {
    const raw = String(html || '');
    if (!raw.includes('{{')) return raw;
    const effective = (!highlight || highlight.mode === 'none')
        ? { mode: 'color', style: { color: '#2563eb' }, className: 'mp-text-highlight' }
        : highlight;
    const escapeAttr = (value) => String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    const styleObjToCss = (obj) => {
        if (!obj || typeof obj !== 'object') return '';
        return Object.entries(obj)
            .map(([k, v]) => {
                const key = k.replace(/[A-Z]/g, (m) => `-${m.toLowerCase()}`);
                return `${key}:${v}`;
            })
            .join(';');
    };

    const className = typeof effective.className === 'string' ? effective.className : '';
    const css = styleObjToCss(effective.style);
    const open = `<span${className ? ` class=\"${escapeAttr(className)}\"` : ''}${css ? ` style=\"${escapeAttr(css)}\"` : ''}>`;
    const close = '</span>';

    return raw.replace(/\{\{([\s\S]*?)\}\}/g, (_m, inner) => {
        const content = String(inner || '');
        if (content === '') return '';
        return `${open}${content}${close}`;
    });
}

export function renderHeadingPreview(widget, helpers = {}) {
    const w = widget;
    const { typographyStyleFromProps, globals } = helpers;

    const resolveColor = (val) => {
        const resolved = resolveColorToken(val, globals);
        return typeof resolved === 'string' && resolved.trim() ? resolved : '';
    };

    const html = (w.props && typeof w.props.html === 'string') ? String(w.props.html) : '';
    const text = (w.props && w.props.text) ? String(w.props.text) : 'Heading';
    const levelRaw = w.props && Number.isFinite(Number(w.props.level)) ? Number(w.props.level) : 2;
    const level = Math.max(1, Math.min(6, Math.round(levelRaw)));
    const style = typeof typographyStyleFromProps === 'function' ? typographyStyleFromProps(w.props) : undefined;
    const boxStyle = { ...(style || {}) };
    const resolvedColor = w.props ? resolveColor(w.props.color) : '';
    if (resolvedColor) {
        boxStyle.color = resolvedColor;
    }

    if (w.props) {
        if (typeof w.props.paddingTop === 'number' && Number.isFinite(w.props.paddingTop)) boxStyle.paddingTop = `${w.props.paddingTop}px`;
        if (typeof w.props.paddingRight === 'number' && Number.isFinite(w.props.paddingRight)) boxStyle.paddingRight = `${w.props.paddingRight}px`;
        if (typeof w.props.paddingBottom === 'number' && Number.isFinite(w.props.paddingBottom)) boxStyle.paddingBottom = `${w.props.paddingBottom}px`;
        if (typeof w.props.paddingLeft === 'number' && Number.isFinite(w.props.paddingLeft)) boxStyle.paddingLeft = `${w.props.paddingLeft}px`;

        if (typeof w.props.marginTop === 'number' && Number.isFinite(w.props.marginTop)) boxStyle.marginTop = `${w.props.marginTop}px`;
        if (typeof w.props.marginRight === 'number' && Number.isFinite(w.props.marginRight)) boxStyle.marginRight = `${w.props.marginRight}px`;
        if (typeof w.props.marginBottom === 'number' && Number.isFinite(w.props.marginBottom)) boxStyle.marginBottom = `${w.props.marginBottom}px`;
        if (typeof w.props.marginLeft === 'number' && Number.isFinite(w.props.marginLeft)) boxStyle.marginLeft = `${w.props.marginLeft}px`;

        const bgType = typeof w.props.backgroundType === 'string' ? w.props.backgroundType : 'classic';
        if (bgType === 'gradient') {
            const from = resolveColor(w.props.gradientFrom) || '#111827';
            const to = resolveColor(w.props.gradientTo) || '#3b82f6';
            const fromLoc = typeof w.props.gradientFromLocation === 'number' && Number.isFinite(w.props.gradientFromLocation) ? w.props.gradientFromLocation : 0;
            const toLoc = typeof w.props.gradientToLocation === 'number' && Number.isFinite(w.props.gradientToLocation) ? w.props.gradientToLocation : 100;
            const type = typeof w.props.gradientType === 'string' ? w.props.gradientType : 'linear';
            const position = typeof w.props.gradientPosition === 'string' && w.props.gradientPosition.trim()
                ? w.props.gradientPosition.trim()
                : 'top center';

            if (type === 'radial') {
                boxStyle.backgroundImage = `radial-gradient(at ${position}, ${from} ${fromLoc}%, ${to} ${toLoc}%)`;
            } else {
                boxStyle.backgroundImage = `linear-gradient(135deg, ${from} ${fromLoc}%, ${to} ${toLoc}%)`;
            }
        } else {
            const resolvedBg = resolveColor(w.props.backgroundColor);
            if (resolvedBg) {
                boxStyle.backgroundColor = resolvedBg;
            }
        }

        if (typeof w.props.borderStyle === 'string' && w.props.borderStyle && w.props.borderStyle !== 'default') {
            boxStyle.borderStyle = w.props.borderStyle;
        }
        const resolvedBorderColor = resolveColor(w.props.borderColor);
        if (resolvedBorderColor) {
            boxStyle.borderColor = resolvedBorderColor;
        }
        if (typeof w.props.borderWidthTop === 'number' && Number.isFinite(w.props.borderWidthTop)) boxStyle.borderTopWidth = `${w.props.borderWidthTop}px`;
        if (typeof w.props.borderWidthRight === 'number' && Number.isFinite(w.props.borderWidthRight)) boxStyle.borderRightWidth = `${w.props.borderWidthRight}px`;
        if (typeof w.props.borderWidthBottom === 'number' && Number.isFinite(w.props.borderWidthBottom)) boxStyle.borderBottomWidth = `${w.props.borderWidthBottom}px`;
        if (typeof w.props.borderWidthLeft === 'number' && Number.isFinite(w.props.borderWidthLeft)) boxStyle.borderLeftWidth = `${w.props.borderWidthLeft}px`;
        if (typeof w.props.borderRadiusTop === 'number' && Number.isFinite(w.props.borderRadiusTop)) boxStyle.borderTopLeftRadius = `${w.props.borderRadiusTop}px`;
        if (typeof w.props.borderRadiusRight === 'number' && Number.isFinite(w.props.borderRadiusRight)) boxStyle.borderTopRightRadius = `${w.props.borderRadiusRight}px`;
        if (typeof w.props.borderRadiusBottom === 'number' && Number.isFinite(w.props.borderRadiusBottom)) boxStyle.borderBottomRightRadius = `${w.props.borderRadiusBottom}px`;
        if (typeof w.props.borderRadiusLeft === 'number' && Number.isFinite(w.props.borderRadiusLeft)) boxStyle.borderBottomLeftRadius = `${w.props.borderRadiusLeft}px`;
    }

    const Tag = `h${level}`;
    const highlight = highlightStyleFromProps(w.props);
    const align = (w.props && typeof w.props.align === 'string' && w.props.align.trim()) ? w.props.align.trim() : 'left';

    const defaultFontSizeByLevel = { 1: 44, 2: 36, 3: 30, 4: 24, 5: 20, 6: 18 };
    const defaultFontSize = defaultFontSizeByLevel[level] || 18;

    if (!boxStyle.fontSize) boxStyle.fontSize = `${defaultFontSize}px`;
    if (!boxStyle.fontWeight) boxStyle.fontWeight = 600;
    if (!boxStyle.lineHeight) boxStyle.lineHeight = '1.15';
    if (!boxStyle.textAlign) boxStyle.textAlign = align;

    if (html && html.trim()) {
        const nextHtml = applyHighlightTokensToHtml(html, highlight);
        return <Tag className="mp-heading" style={boxStyle} dangerouslySetInnerHTML={{ __html: nextHtml }} />;
    }

    const segs = splitHighlightTokens(text || 'Heading');
    const effectiveHighlight = (!highlight || highlight.mode === 'none')
        ? { mode: 'color', style: { color: '#2563eb' }, className: 'mp-text-highlight' }
        : highlight;
    return (
        <Tag className="mp-heading" style={boxStyle}>
            {segs.map((seg, idx) => {
                if (!seg.value) return null;
                if (seg.type === 'highlight') {
                    return (
                        <span key={idx} className={effectiveHighlight.className} style={effectiveHighlight.style}>
                            {seg.value}
                        </span>
                    );
                }
                return <React.Fragment key={idx}>{seg.value}</React.Fragment>;
            })}
        </Tag>
    );
}

const headingSchema = {
    content: [
        { type: CONTROL.CONTROL_TEXT, name: 'text', label: 'Text', bind: 'props.text' },
        { type: CONTROL.CONTROL_WYSIWYG, name: 'html', label: 'HTML (optional)', bind: 'props.html' },
        { type: CONTROL.CONTROL_SELECT, name: 'level', label: 'Level', bind: 'props.level', options: [1, 2, 3, 4, 5, 6].map((n) => ({ value: String(n), label: `H${n}` })) },
    ],
    style: [
        {
            title: 'Heading',
            defaultOpen: true,
            children: [
                { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Typography', bind: { fontFamily: 'props.fontFamily', fontSize: 'props.fontSize', fontWeight: 'props.fontWeight', align: 'props.align', lineHeight: 'props.lineHeight', letterSpacing: 'props.letterSpacing', wordSpacing: 'props.wordSpacing' } },
                { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'color', label: 'Text Color', bind: 'props.color' },
                { type: CONTROL.CONTROL_GROUP, kind: 'text_shadow', label: 'Text Shadow', bind: { color: 'props.textShadowColor', offsetX: 'props.textShadowX', offsetY: 'props.textShadowY', blur: 'props.textShadowBlur' } },
                { type: CONTROL.CONTROL_GROUP, kind: 'spacing', label: 'Spacing', bind: { padding: { linked: 'props.paddingLinked', t: 'props.paddingTop', r: 'props.paddingRight', b: 'props.paddingBottom', l: 'props.paddingLeft' }, margin: { linked: 'props.marginLinked', t: 'props.marginTop', r: 'props.marginRight', b: 'props.marginBottom', l: 'props.marginLeft' } } },
                { type: CONTROL.CONTROL_GROUP, kind: 'background', label: 'Background', bind: { type: 'props.backgroundType', color: 'props.backgroundColor', from: 'props.gradientFrom', to: 'props.gradientTo', fromLocation: 'props.gradientFromLocation', toLocation: 'props.gradientToLocation', gradientType: 'props.gradientType', position: 'props.gradientPosition' } },
                { type: CONTROL.CONTROL_GROUP, kind: 'border', label: 'Border', bind: { styleKey: 'props.borderStyle', colorKey: 'props.borderColor', widthKeys: { linked: 'props.borderWidthLinked', t: 'props.borderWidthTop', r: 'props.borderWidthRight', b: 'props.borderWidthBottom', l: 'props.borderWidthLeft' } }, includeRadius: false },
                { type: CONTROL.CONTROL_DIMENSIONS, name: 'borderRadius', label: 'Border Radius', bind: { linked: 'props.borderRadiusLinked', t: 'props.borderRadiusTop', r: 'props.borderRadiusRight', b: 'props.borderRadiusBottom', l: 'props.borderRadiusLeft' }, kind: 'border-radius' },
            ],
        },
        {
            title: 'Highlight',
            defaultOpen: false,
            children: [
                { type: CONTROL.CONTROL_GROUP, kind: 'highlight', label: 'Highlight', bind: { mode: 'props.highlightMode', color: 'props.highlightColor', from: 'props.highlightGradientFrom', to: 'props.highlightGradientTo', animate: 'props.highlightAnimate', duration: 'props.highlightAnimationDuration' } },
                { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Highlight Typography', bind: { fontFamily: 'props.highlightFontFamily', fontSize: 'props.highlightFontSize', fontWeight: 'props.highlightFontWeight', align: 'props.highlightAlign', lineHeight: 'props.highlightLineHeight', letterSpacing: 'props.highlightLetterSpacing', wordSpacing: 'props.highlightWordSpacing' }, condition: { highlightMode: { operator: 'in', value: ['color', 'gradient'] } } },
            ],
        },
    ],
    advanced: [],
};

registerElement({
    type: 'heading',
    label: 'Heading',
    group: 'Basic',
    defaultProps: { text: 'Heading', level: 2 },
    render: renderHeadingPreview,
    schema: headingSchema,
});
