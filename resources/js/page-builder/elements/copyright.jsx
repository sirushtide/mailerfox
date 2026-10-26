import React from 'react';
import * as CONTROL from '../controls/types.js';
import { registerElement } from '../elementRegistry.js';
import { resolveColorToken } from '../controls/bind.js';
import { cssLengthToCss } from '../lib/typographyUtils.js';
import { pickResponsive } from '../reusable/builderViewport.js';

function decodeEntities(value) {
    const str = String(value ?? '');
    if (typeof document === 'undefined') {
        return str.replace(/&copy;/gi, '©').replace(/&reg;/gi, '®').replace(/&trade;/gi, '™').replace(/&amp;/gi, '&');
    }
    const el = document.createElement('textarea');
    el.innerHTML = str;
    return el.value;
}

function len(value, fallback) {
    if (value === undefined || value === null || value === '') return fallback;
    const css = cssLengthToCss(value);
    return css || fallback;
}

export function renderCopyrightPreview(widget, helpers = {}) {
    const w = widget;
    const props = w.props || {};
    const globals = helpers.globals || {};

    const rawText = typeof props.text === 'string' && props.text !== '' ? props.text : '&copy; {{year}} {{site_name}}';
    const align = ['left', 'center', 'right'].includes(props.align) ? props.align : 'center';
    const year = new Date().getFullYear();
    const siteName = 'MailPurse';
    const displayText = decodeEntities(rawText.replace(/{{year}}/g, year).replace(/{{site_name}}/g, siteName));

    const style = { textAlign: align, margin: 0 };

    const color = resolveColorToken(props.color, globals);
    if (typeof color === 'string' && color.trim()) style.color = color;
    if (props.fontFamily) style.fontFamily = props.fontFamily;
    const fontSize = pickResponsive(props.fontSize, props.fontSizeTablet, props.fontSizeMobile);
    if (fontSize !== undefined && fontSize !== '') style.fontSize = cssLengthToCss(fontSize);
    if (props.fontWeight) style.fontWeight = props.fontWeight;
    const lineHeight = pickResponsive(props.lineHeight, props.lineHeightTablet, props.lineHeightMobile);
    if (lineHeight !== undefined && lineHeight !== '') style.lineHeight = len(lineHeight, undefined);
    const letterSpacing = pickResponsive(props.letterSpacing, props.letterSpacingTablet, props.letterSpacingMobile);
    if (letterSpacing !== undefined && letterSpacing !== '') style.letterSpacing = cssLengthToCss(letterSpacing);
    const wordSpacing = pickResponsive(props.wordSpacing, props.wordSpacingTablet, props.wordSpacingMobile);
    if (wordSpacing !== undefined && wordSpacing !== '') style.wordSpacing = cssLengthToCss(wordSpacing);

    if (props.paddingTop !== undefined) style.paddingTop = len(props.paddingTop, '0px');
    if (props.paddingRight !== undefined) style.paddingRight = len(props.paddingRight, '0px');
    if (props.paddingBottom !== undefined) style.paddingBottom = len(props.paddingBottom, '0px');
    if (props.paddingLeft !== undefined) style.paddingLeft = len(props.paddingLeft, '0px');
    if (props.marginTop !== undefined) style.marginTop = len(props.marginTop, '0px');
    if (props.marginRight !== undefined) style.marginRight = len(props.marginRight, '0px');
    if (props.marginBottom !== undefined) style.marginBottom = len(props.marginBottom, '0px');
    if (props.marginLeft !== undefined) style.marginLeft = len(props.marginLeft, '0px');

    const bgType = props.backgroundType === 'gradient' ? 'gradient' : 'color';
    if (bgType === 'gradient') {
        const from = resolveColorToken(props.gradientFrom, globals) || '#111827';
        const to = resolveColorToken(props.gradientTo, globals) || '#3b82f6';
        const angle = Number.isFinite(Number(props.gradientAngle)) ? Number(props.gradientAngle) : 135;
        style.backgroundImage = `linear-gradient(${angle}deg, ${from}, ${to})`;
    } else if (props.backgroundColor) {
        const bg = resolveColorToken(props.backgroundColor, globals);
        if (typeof bg === 'string' && bg.trim()) style.backgroundColor = bg;
    }

    return (
        <p className="mp-copyright" style={style}>
            {displayText}
        </p>
    );
}

const copyrightSchema = {
    content: [
        { type: CONTROL.CONTROL_TEXT, name: 'text', label: 'Copyright Text', bind: 'props.text' },
        { type: CONTROL.CONTROL_SELECT, name: 'align', label: 'Align', bind: 'props.align', options: [
            { value: 'left', label: 'Left' },
            { value: 'center', label: 'Center' },
            { value: 'right', label: 'Right' },
        ]},
    ],
    style: [
        {
            title: 'Copyright',
            defaultOpen: true,
            children: [
                { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Typography', bind: {
                    fontFamily: 'props.fontFamily',
                    fontSize: 'props.fontSize',
                    fontWeight: 'props.fontWeight',
                    align: 'props.align',
                    lineHeight: 'props.lineHeight',
                    letterSpacing: 'props.letterSpacing',
                    wordSpacing: 'props.wordSpacing',
                } },
                { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'color', label: 'Text Color', bind: 'props.color' },
                { type: CONTROL.CONTROL_GROUP, kind: 'spacing', label: 'Spacing', bind: {
                    padding: { linked: 'props.paddingLinked', t: 'props.paddingTop', r: 'props.paddingRight', b: 'props.paddingBottom', l: 'props.paddingLeft' },
                    margin: { linked: 'props.marginLinked', t: 'props.marginTop', r: 'props.marginRight', b: 'props.marginBottom', l: 'props.marginLeft' },
                } },
                { type: CONTROL.CONTROL_GROUP, kind: 'background', label: 'Background', bind: {
                    type: 'props.backgroundType', color: 'props.backgroundColor', from: 'props.gradientFrom', to: 'props.gradientTo',
                    fromLocation: 'props.gradientFromLocation', toLocation: 'props.gradientToLocation', gradientType: 'props.gradientType', position: 'props.gradientPosition', angle: 'props.gradientAngle',
                } },
            ],
        },
    ],
    advanced: [],
};

registerElement({
    type: 'copyright',
    label: 'Copyright',
    group: 'Basic',
    defaultProps: { text: '&copy; {{year}} {{site_name}}', align: 'center' },
    render: renderCopyrightPreview,
    schema: copyrightSchema,
});
