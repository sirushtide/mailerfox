import React from 'react';
import { registerElement } from '../elementRegistry.js';
import * as CONTROL from '../controls/types.js';
import { resolveUnit } from '../controls/bind.js';
import { svgWithWidth } from '../lib/svg.js';

function px(val) {
    if (typeof val === 'number' && Number.isFinite(val)) return `${val}px`;
    return resolveUnit(val, 'px');
}

export function renderImagePreview(widget) {
    const w = widget;
    const p = w.props || {};

    const url = p.url ? String(p.url) : '';
    const svgMarkup = p.svg ? String(p.svg) : '';
    const source = p.source === 'svg' || p.source === 'url' ? p.source : '';
    const useSvg = svgMarkup !== '' && (source === 'svg' || url === '');
    const widthCss = resolveUnit(p.width, 'px');
    const heightCss = resolveUnit(p.height, 'px');
    const align = (p.align === 'left' || p.align === 'center' || p.align === 'right') ? p.align : 'left';
    const allowedJustify = ['flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly', 'start', 'end', 'left', 'right'];
    const advancedJc = w.advanced && typeof w.advanced.justifyContent === 'string' && allowedJustify.includes(w.advanced.justifyContent)
        ? w.advanced.justifyContent
        : '';
    const justifyContent = advancedJc || (align === 'right' ? 'flex-end' : align === 'center' ? 'center' : 'flex-start');

    const imgStyle = {};
    if (widthCss) imgStyle.width = widthCss;
    if (heightCss) imgStyle.height = heightCss;
    imgStyle.display = 'block';
    if (['cover', 'contain', 'fill', 'none', 'scale-down'].includes(p.objectFit)) {
        imgStyle.objectFit = p.objectFit;
    }

    if (p.marginTop !== undefined) imgStyle.marginTop = px(p.marginTop);
    if (p.marginRight !== undefined) imgStyle.marginRight = px(p.marginRight);
    if (p.marginBottom !== undefined) imgStyle.marginBottom = px(p.marginBottom);
    if (p.marginLeft !== undefined) imgStyle.marginLeft = px(p.marginLeft);

    if (p.paddingTop !== undefined) imgStyle.paddingTop = px(p.paddingTop);
    if (p.paddingRight !== undefined) imgStyle.paddingRight = px(p.paddingRight);
    if (p.paddingBottom !== undefined) imgStyle.paddingBottom = px(p.paddingBottom);
    if (p.paddingLeft !== undefined) imgStyle.paddingLeft = px(p.paddingLeft);

    const borderWidth = resolveUnit(p.borderWidth, 'px');
    const borderWidthTop = resolveUnit(p.borderWidthTop, 'px');
    const borderWidthRight = resolveUnit(p.borderWidthRight, 'px');
    const borderWidthBottom = resolveUnit(p.borderWidthBottom, 'px');
    const borderWidthLeft = resolveUnit(p.borderWidthLeft, 'px');

    if (borderWidth || borderWidthTop || borderWidthRight || borderWidthBottom || borderWidthLeft) {
        imgStyle.borderStyle = 'solid';
        if (borderWidth) imgStyle.borderWidth = borderWidth;
        if (borderWidthTop) imgStyle.borderTopWidth = borderWidthTop;
        if (borderWidthRight) imgStyle.borderRightWidth = borderWidthRight;
        if (borderWidthBottom) imgStyle.borderBottomWidth = borderWidthBottom;
        if (borderWidthLeft) imgStyle.borderLeftWidth = borderWidthLeft;
    }

    if (typeof p.borderColor === 'string' && p.borderColor) {
        imgStyle.borderColor = p.borderColor;
    }

    const borderRadius = resolveUnit(p.borderRadius, 'px');
    const borderRadiusTop = resolveUnit(p.borderRadiusTop, 'px');
    const borderRadiusRight = resolveUnit(p.borderRadiusRight, 'px');
    const borderRadiusBottom = resolveUnit(p.borderRadiusBottom, 'px');
    const borderRadiusLeft = resolveUnit(p.borderRadiusLeft, 'px');

    if (borderRadius !== undefined) {
        imgStyle.borderRadius = borderRadius;
    }
    if (borderRadiusTop !== undefined) {
        imgStyle.borderTopLeftRadius = borderRadiusTop;
    }
    if (borderRadiusRight !== undefined) {
        imgStyle.borderTopRightRadius = borderRadiusRight;
    }
    if (borderRadiusBottom !== undefined) {
        imgStyle.borderBottomRightRadius = borderRadiusBottom;
    }
    if (borderRadiusLeft !== undefined) {
        imgStyle.borderBottomLeftRadius = borderRadiusLeft;
    }

    const boxShadowColor = typeof p.boxShadowColor === 'string' ? p.boxShadowColor.trim() : '';
    if (boxShadowColor) {
        const bsX = Number.isFinite(Number(p.boxShadowX)) ? Number(p.boxShadowX) : 0;
        const bsY = Number.isFinite(Number(p.boxShadowY)) ? Number(p.boxShadowY) : 0;
        const bsBlur = Number.isFinite(Number(p.boxShadowBlur)) ? Number(p.boxShadowBlur) : 0;
        const bsSpread = Number.isFinite(Number(p.boxShadowSpread)) ? Number(p.boxShadowSpread) : 0;
        imgStyle.boxShadow = `${bsX}px ${bsY}px ${bsBlur}px ${bsSpread}px ${boxShadowColor}`;
    }

    return (
        <div style={{ display: 'flex', justifyContent, width: '100%' }}>
            {useSvg ? (
                <div
                    className="max-w-full"
                    style={Object.keys(imgStyle).length ? { ...imgStyle, height: 'auto' } : undefined}
                    dangerouslySetInnerHTML={{ __html: svgWithWidth(svgMarkup, widthCss, '100%') }}
                />
            ) : url ? (
                <img
                    src={url}
                    alt={typeof p.alt === 'string' ? p.alt : ''}
                    className="max-w-full h-auto"
                    style={Object.keys(imgStyle).length ? imgStyle : undefined}
                />
            ) : (
                <div
                    className="border-2 border-dashed border-gray-200 bg-gray-50 text-gray-300 flex items-center justify-center"
                    style={Object.keys(imgStyle).length ? { ...imgStyle } : { width: '100%', minHeight: 120 }}
                >
                    <svg width="56" height="56" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path
                            d="M21 19V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2Z"
                            stroke="currentColor"
                            strokeWidth="1.5"
                        />
                        <path
                            d="M8.5 10.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"
                            stroke="currentColor"
                            strokeWidth="1.5"
                        />
                        <path
                            d="M21 16l-5.5-5.5a2 2 0 0 0-2.8 0L6 17"
                            stroke="currentColor"
                            strokeWidth="1.5"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                    </svg>
                </div>
            )}
        </div>
    );
}

const imageSchema = {
    content: [
        {
            type: CONTROL.CONTROL_MEDIA_SOURCES,
            name: 'source',
            label: 'Image',
            bind: { url: 'props.url', svg: 'props.svg', source: 'props.source' },
        },
        { type: CONTROL.CONTROL_TEXT, name: 'alt', label: 'Alt Text', bind: 'props.alt' },
        { type: CONTROL.CONTROL_URL, name: 'link', label: 'Link', bind: 'props.link' },
        { type: CONTROL.CONTROL_SELECT, name: 'target', label: 'Target', bind: 'props.target', options: [{ value: '_self', label: 'Same Tab' }, { value: '_blank', label: 'New Tab' }] },
        { type: CONTROL.CONTROL_SELECT, name: 'align', label: 'Align', bind: 'props.align', options: [{ value: 'left', label: 'Left' }, { value: 'center', label: 'Center' }, { value: 'right', label: 'Right' }] },
        { type: CONTROL.CONTROL_SLIDER, name: 'width', label: 'Width', bind: 'props.width', kind: 'width', units: ['px', '%', 'vw', 'auto'], defaultUnit: 'px' },
        { type: CONTROL.CONTROL_SLIDER, name: 'height', label: 'Height', bind: 'props.height', kind: 'height', units: ['px', '%', 'vh', 'auto'], defaultUnit: 'px' },
        { type: CONTROL.CONTROL_SELECT, name: 'objectFit', label: 'Object Fit', bind: 'props.objectFit', options: [{ value: '', label: 'Default' }, { value: 'cover', label: 'Cover' }, { value: 'contain', label: 'Contain' }, { value: 'fill', label: 'Fill' }, { value: 'none', label: 'None' }, { value: 'scale-down', label: 'Scale Down' }] },
    ],
    style: [
        { type: CONTROL.CONTROL_GROUP, kind: 'spacing', label: 'Spacing', bind: { padding: { linked: 'props.paddingLinked', t: 'props.paddingTop', r: 'props.paddingRight', b: 'props.paddingBottom', l: 'props.paddingLeft' }, margin: { linked: 'props.marginLinked', t: 'props.marginTop', r: 'props.marginRight', b: 'props.marginBottom', l: 'props.marginLeft' } } },
        { type: CONTROL.CONTROL_GROUP, kind: 'border', label: 'Border', bind: { styleKey: 'props.borderStyle', colorKey: 'props.borderColor', widthKeys: { linked: 'props.borderWidthLinked', t: 'props.borderWidthTop', r: 'props.borderWidthRight', b: 'props.borderWidthBottom', l: 'props.borderWidthLeft' } }, includeRadius: false },
        { type: CONTROL.CONTROL_DIMENSIONS, name: 'borderRadius', label: 'Border Radius', bind: { linked: 'props.borderRadiusLinked', t: 'props.borderRadiusTop', r: 'props.borderRadiusRight', b: 'props.borderRadiusBottom', l: 'props.borderRadiusLeft' }, kind: 'border-radius' },
        { type: CONTROL.CONTROL_GROUP, kind: 'box_shadow', label: 'Shadow', bind: { color: 'props.boxShadowColor', offsetX: 'props.boxShadowX', offsetY: 'props.boxShadowY', blur: 'props.boxShadowBlur', spread: 'props.boxShadowSpread' } },
    ],
    advanced: [],
};

registerElement({
    type: 'image',
    label: 'Image',
    group: 'Basic',
    defaultProps: { url: '', svg: '', source: 'url' },
    render: renderImagePreview,
    schema: imageSchema,
});
