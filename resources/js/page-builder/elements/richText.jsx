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

function applyLinkColorToHtml(html, color) {
    const c = typeof color === 'string' ? color.trim() : '';
    if (!c) return String(html || '');
    return String(html || '').replace(/<a\b([^>]*)>/gi, (match, attrs) => {
        const styleRe = /\bstyle\s*=\s*(?:"([^"]*)"|'([^']*)')/i;
        const m = attrs.match(styleRe);
        if (m) {
            const existing = m[1] !== undefined ? m[1] : m[2] || '';
            if (/(^|;|\s)color\s*:/i.test(existing)) return match;
            const merged = existing === '' || /;\s*$/.test(existing)
                ? `${existing}color:${c};`
                : `${existing};color:${c};`;
            const esc = merged.replace(/&/g, '&amp;').replace(/"/g, '&quot;');
            return `<a${attrs.replace(styleRe, `style="${esc}"`)}>`;
        }
        const esc = c.replace(/&/g, '&amp;').replace(/"/g, '&quot;');
        return `<a${attrs} style="color:${esc};">`;
    });
}

export function renderRichTextPreview(widget, helpers = {}) {
    const w = widget;
    const { typographyStyleFromProps, globals } = helpers;

    const resolveColor = (val) => {
        const resolved = resolveColorToken(val, globals);
        return typeof resolved === 'string' && resolved.trim() ? resolved : '';
    };

    const html = (w.props && typeof w.props.html === 'string') ? w.props.html : '<p></p>';
    const highlight = highlightStyleFromProps(w.props);
    let nextHtml = applyHighlightTokensToHtml(html, highlight);
    const linkColorVal = w.props ? resolveColor(w.props.linkColor) : '';
    if (linkColorVal) {
        nextHtml = applyLinkColorToHtml(nextHtml, linkColorVal);
    }
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

    return <div className="mp-richtext prose max-w-none" style={{ ...boxStyle, width: '100%', maxWidth: '100%' }} dangerouslySetInnerHTML={{ __html: nextHtml }} />;
}

const richTextSchema = {
    content: [
        { type: CONTROL.CONTROL_CHOOSE, name: 'align', label: 'Alignment', bind: 'props.align', options: [
            { value: 'left', label: 'Left', icon: 'text-left', tooltip: 'Align text left' },
            { value: 'center', label: 'Center', icon: 'text-center', tooltip: 'Align text center' },
            { value: 'right', label: 'Right', icon: 'text-right', tooltip: 'Align text right' },
            { value: 'justify', label: 'Justify', icon: 'text-justify', tooltip: 'Justify text' },
        ] },
        { type: CONTROL.CONTROL_WYSIWYG, name: 'html', label: 'Content', bind: 'props.html' },
    ],
    style: [
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'color', label: 'Text Color', bind: 'props.color' },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'linkColor', label: 'Link Color', bind: 'props.linkColor' },
        { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Typography', bind: { fontFamily: 'props.fontFamily', fontSize: 'props.fontSize', fontWeight: 'props.fontWeight', align: 'props.align', lineHeight: 'props.lineHeight', letterSpacing: 'props.letterSpacing', wordSpacing: 'props.wordSpacing' } },
        { type: CONTROL.CONTROL_GROUP, kind: 'spacing', label: 'Spacing', bind: { padding: { linked: 'props.paddingLinked', t: 'props.paddingTop', r: 'props.paddingRight', b: 'props.paddingBottom', l: 'props.paddingLeft' }, margin: { linked: 'props.marginLinked', t: 'props.marginTop', r: 'props.marginRight', b: 'props.marginBottom', l: 'props.marginLeft' } } },
        { type: CONTROL.CONTROL_GROUP, kind: 'background', label: 'Background', bind: { type: 'props.backgroundType', color: 'props.backgroundColor', from: 'props.gradientFrom', to: 'props.gradientTo', fromLocation: 'props.gradientFromLocation', toLocation: 'props.gradientToLocation', gradientType: 'props.gradientType', position: 'props.gradientPosition' } },
        { type: CONTROL.CONTROL_GROUP, kind: 'border', label: 'Border', bind: { styleKey: 'props.borderStyle', colorKey: 'props.borderColor', widthKeys: { linked: 'props.borderWidthLinked', t: 'props.borderWidthTop', r: 'props.borderWidthRight', b: 'props.borderWidthBottom', l: 'props.borderWidthLeft' } }, includeRadius: false },
        { type: CONTROL.CONTROL_DIMENSIONS, name: 'borderRadius', label: 'Border Radius', bind: { linked: 'props.borderRadiusLinked', t: 'props.borderRadiusTop', r: 'props.borderRadiusRight', b: 'props.borderRadiusBottom', l: 'props.borderRadiusLeft' }, kind: 'border-radius' },
    ],
    advanced: [],
};

registerElement({
    type: 'rich_text',
    label: 'Rich Text',
    group: 'Basic',
    defaultProps: { html: '<p></p>' },
    render: renderRichTextPreview,
    schema: richTextSchema,
});
