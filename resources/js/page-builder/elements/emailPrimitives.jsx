import React from 'react';
import * as CONTROL from '../controls/types.js';
import { registerElement } from '../elementRegistry.js';
import { resolveUnit } from '../controls/bind.js';
import { svgWithWidth } from '../lib/svg.js';

function cssLength(value, fallback) {
    if (value === undefined || value === null || value === '') return fallback;
    if (typeof value === 'number') return Number.isFinite(value) ? `${value}px` : fallback;
    if (typeof value === 'string') return value;
    if (typeof value === 'object' && typeof value.unit === 'string' && value.value !== '' && value.value !== undefined) {
        return `${value.value}${value.unit}`;
    }
    return fallback;
}

function dividerBarStyle(widget) {
    const props = widget?.props || {};
    const color = (typeof props.backgroundColor === 'string' && props.backgroundColor.trim() !== '')
        ? props.backgroundColor
        : (typeof props.borderColor === 'string' && props.borderColor.trim() !== '' ? props.borderColor : '#e5e7eb');

    return {
        display: 'inline-block',
        verticalAlign: 'middle',
        height: cssLength(props.height, '1px'),
        width: cssLength(props.width, '100%'),
        maxWidth: '100%',
        backgroundColor: color,
    };
}

function renderDivider(widget) {
    // A full-width wrapper isolates the bar's width from the surrounding
    // flex layout so changing it never resizes siblings (links, text, ...).
    return (
        <div
            aria-hidden
            style={{ display: 'block', width: '100%', maxWidth: '100%', textAlign: 'center', lineHeight: 0 }}
        >
            <div style={dividerBarStyle(widget)} />
        </div>
    );
}

registerElement({
    type: 'divider',
    label: 'Divider',
    group: 'Basic',
    defaultProps: { backgroundColor: '#e5e7eb', height: '1px', width: '100%', borderTop: true, borderColor: '#e5e7eb' },
    render: renderDivider,
    schema: {
        content: [
            { type: CONTROL.CONTROL_SLIDER, name: 'width', label: 'Width', bind: 'props.width', kind: 'width' },
            { type: CONTROL.CONTROL_SLIDER, name: 'height', label: 'Height', bind: 'props.height', kind: 'height' },
        ],
        style: [
            { type: CONTROL.CONTROL_COLOR, name: 'backgroundColor', label: 'Color', bind: 'props.backgroundColor' },
        ],
        advanced: [],
    },
});

registerElement({
    type: 'spacer',
    label: 'Spacer',
    group: 'Basic',
    defaultProps: { height: '24px' },
    render: (widget) => <div aria-hidden style={{ height: widget?.props?.height || '24px' }} />,
    schema: {
        content: [{ type: CONTROL.CONTROL_TEXT, name: 'height', label: 'Height', bind: 'props.height' }],
        style: [],
        advanced: [],
    },
});

registerElement({
    type: 'logo',
    label: 'Logo',
    group: 'Basic',
    defaultProps: { url: '', svg: '', source: 'url', alt: 'Logo', width: 180, align: 'left' },
    render: (widget) => {
        const props = widget?.props || {};
        const align = props.align || 'left';
        const widthCss = resolveUnit(props.width, 'px') || '180px';
        const svg = svgWithWidth(props.svg, widthCss);
        const hasUrl = Boolean(props.url);
        const useSvg = Boolean(svg) && (props.source === 'svg' || !hasUrl);
        if (useSvg) {
            return (
                <div style={{ textAlign: align }} dangerouslySetInnerHTML={{ __html: svg }} />
            );
        }
        if (!props.url) {
            return <div className="text-xs text-gray-400">Add a logo image or paste SVG code</div>;
        }
        return <div style={{ textAlign: align }}><img src={props.url} alt={props.alt || ''} style={{ display: 'inline-block', maxWidth: '100%', width: widthCss, height: 'auto' }} /></div>;
    },
    schema: {
        content: [
            {
                type: CONTROL.CONTROL_MEDIA_SOURCES,
                name: 'source',
                label: 'Logo',
                bind: { url: 'props.url', svg: 'props.svg', source: 'props.source' },
            },
            { type: CONTROL.CONTROL_TEXT, name: 'alt', label: 'Alt text', bind: 'props.alt' },
            { type: CONTROL.CONTROL_SLIDER, name: 'width', label: 'Width', bind: 'props.width', kind: 'width', units: ['px', '%', 'vw', 'auto'], defaultUnit: 'px' },
            { type: CONTROL.CONTROL_SELECT, name: 'align', label: 'Align', bind: 'props.align', options: [{ value: 'left', label: 'Left' }, { value: 'center', label: 'Center' }, { value: 'right', label: 'Right' }] },
        ],
        style: [],
        advanced: [],
    },
});
