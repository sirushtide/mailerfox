import React from 'react';
import * as CONTROL from '../controls/types.js';
import { registerElement } from '../elementRegistry.js';

function finiteNumber(value) {
    return typeof value === 'number' && Number.isFinite(value) ? value : undefined;
}

const FAQ_ICONS = {
    chevron: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>',
    plus: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>',
    arrow: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>',
};

function boxValue(props, linkedKey, topKey, rightKey, bottomKey, leftKey, fallbackKey, fallbackValue) {
    const linked = finiteNumber(props[linkedKey]);
    if (linked !== undefined) return `${linked}px`;

    const top = finiteNumber(props[topKey]);
    const right = finiteNumber(props[rightKey]);
    const bottom = finiteNumber(props[bottomKey]);
    const left = finiteNumber(props[leftKey]);
    if ([top, right, bottom, left].some((v) => v !== undefined)) {
        return `${top ?? 0}px ${right ?? 0}px ${bottom ?? 0}px ${left ?? 0}px`;
    }

    if (fallbackKey) {
        const fallback = finiteNumber(props[fallbackKey]);
        if (fallback !== undefined) return `${fallback}px`;
    }

    return fallbackValue;
}

function FaqAccordion({ items, align, gap, openFirst, itemStyle, summaryStyle, answerStyle, hasIcon, iconPosition, iconClass, iconStyle, iconSvg }) {
    const [openIndex, setOpenIndex] = React.useState(() => (openFirst ? 0 : -1));

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: `${gap}px` }}>
            {items.map((it, idx) => {
                const q = it && typeof it === 'object' && typeof it.question === 'string' ? it.question : 'Question';
                const rawAnswer = it && typeof it === 'object' && typeof it.answer === 'string' ? it.answer : '<p>Answer</p>';
                const answerHtml = /<[a-z][\s\S]*>/i.test(rawAnswer) ? rawAnswer : rawAnswer.replace(/\n/g, '<br/>');
                const isOpen = idx === openIndex;
                const iconEl = hasIcon ? (
                    <span className={iconClass} style={iconStyle} aria-hidden dangerouslySetInnerHTML={{ __html: iconSvg }} />
                ) : null;

                return (
                    <div key={idx} className="mp-faqs__item" data-open={isOpen ? 'true' : 'false'} style={itemStyle}>
                        <button
                            type="button"
                            aria-expanded={isOpen}
                            style={{ ...summaryStyle, background: 'none', border: 'none', padding: 0, width: '100%', font: 'inherit', color: 'inherit', textAlign: align }}
                            onClick={() => setOpenIndex(isOpen ? -1 : idx)}
                        >
                            {iconEl && iconPosition === 'left' ? iconEl : null}
                            <span style={hasIcon ? { flex: 1 } : undefined}>{q}</span>
                            {iconEl && iconPosition !== 'left' ? iconEl : null}
                        </button>
                        <div style={{ display: 'grid', gridTemplateRows: isOpen ? '1fr' : '0fr', opacity: isOpen ? 1 : 0, transition: 'grid-template-rows 0.3s ease, opacity 0.3s ease' }}>
                            <div style={{ overflow: 'hidden', minHeight: 0 }}>
                                <div style={answerStyle} dangerouslySetInnerHTML={{ __html: answerHtml }} />
                            </div>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

export function renderFaqsPreview(widget) {
    const w = widget;
    const props = (w && w.props && typeof w.props === 'object') ? w.props : {};

    const items = Array.isArray(props.items) ? props.items : [];
    const openFirst = props.openFirst === true;
    const align = (props.align === 'center' || props.align === 'right' || props.align === 'left') ? props.align : 'left';

    const wrapperStyle = {};

    if (typeof props.paddingTop === 'number' && Number.isFinite(props.paddingTop)) wrapperStyle.paddingTop = `${props.paddingTop}px`;
    if (typeof props.paddingRight === 'number' && Number.isFinite(props.paddingRight)) wrapperStyle.paddingRight = `${props.paddingRight}px`;
    if (typeof props.paddingBottom === 'number' && Number.isFinite(props.paddingBottom)) wrapperStyle.paddingBottom = `${props.paddingBottom}px`;
    if (typeof props.paddingLeft === 'number' && Number.isFinite(props.paddingLeft)) wrapperStyle.paddingLeft = `${props.paddingLeft}px`;

    if (typeof props.marginTop === 'number' && Number.isFinite(props.marginTop)) wrapperStyle.marginTop = `${props.marginTop}px`;
    if (typeof props.marginRight === 'number' && Number.isFinite(props.marginRight)) wrapperStyle.marginRight = `${props.marginRight}px`;
    if (typeof props.marginBottom === 'number' && Number.isFinite(props.marginBottom)) wrapperStyle.marginBottom = `${props.marginBottom}px`;
    if (typeof props.marginLeft === 'number' && Number.isFinite(props.marginLeft)) wrapperStyle.marginLeft = `${props.marginLeft}px`;

    const bgType = typeof props.backgroundType === 'string' ? props.backgroundType : 'classic';
    if (bgType === 'gradient') {
        const from = typeof props.gradientFrom === 'string' && props.gradientFrom.trim() ? props.gradientFrom.trim() : '#111827';
        const to = typeof props.gradientTo === 'string' && props.gradientTo.trim() ? props.gradientTo.trim() : '#3b82f6';
        const fromLoc = typeof props.gradientFromLocation === 'number' && Number.isFinite(props.gradientFromLocation) ? props.gradientFromLocation : 0;
        const toLoc = typeof props.gradientToLocation === 'number' && Number.isFinite(props.gradientToLocation) ? props.gradientToLocation : 100;
        const type = typeof props.gradientType === 'string' ? props.gradientType : 'linear';
        const position = typeof props.gradientPosition === 'string' && props.gradientPosition.trim()
            ? props.gradientPosition.trim()
            : 'top center';

        if (type === 'radial') {
            wrapperStyle.backgroundImage = `radial-gradient(at ${position}, ${from} ${fromLoc}%, ${to} ${toLoc}%)`;
        } else {
            wrapperStyle.backgroundImage = `linear-gradient(135deg, ${from} ${fromLoc}%, ${to} ${toLoc}%)`;
        }
    } else {
        if (typeof props.backgroundColor === 'string' && props.backgroundColor.trim()) {
            wrapperStyle.backgroundColor = props.backgroundColor;
        }
    }

    const wrapperBackdropBlur = finiteNumber(props.backdropBlur);
    if (wrapperBackdropBlur && wrapperBackdropBlur > 0) {
        wrapperStyle.backdropFilter = `blur(${wrapperBackdropBlur}px)`;
        wrapperStyle.WebkitBackdropFilter = `blur(${wrapperBackdropBlur}px)`;
    }

    const gap = (typeof props.gap === 'number' && Number.isFinite(props.gap)) ? props.gap : 10;

    const itemStyle = {};

    const itemBgType = props.itemBackgroundType === 'gradient' ? 'gradient' : 'color';
    if (itemBgType === 'gradient') {
        const from = typeof props.itemGradientFrom === 'string' && props.itemGradientFrom.trim() ? props.itemGradientFrom.trim() : '#111827';
        const to = typeof props.itemGradientTo === 'string' && props.itemGradientTo.trim() ? props.itemGradientTo.trim() : '#3b82f6';
        const fromLoc = finiteNumber(props.itemGradientFromLocation) ?? 0;
        const toLoc = finiteNumber(props.itemGradientToLocation) ?? 100;
        const type = props.itemGradientType === 'radial' ? 'radial' : 'linear';
        const position = typeof props.itemGradientPosition === 'string' && props.itemGradientPosition.trim() ? props.itemGradientPosition.trim() : 'top center';
        const angle = finiteNumber(props.itemGradientAngle) ?? 135;
        if (type === 'radial') {
            itemStyle.backgroundImage = `radial-gradient(at ${position}, ${from} ${fromLoc}%, ${to} ${toLoc}%)`;
        } else {
            itemStyle.backgroundImage = `linear-gradient(${angle}deg, ${from} ${fromLoc}%, ${to} ${toLoc}%)`;
        }
    } else {
        itemStyle.backgroundColor = (typeof props.itemBackgroundColor === 'string' && props.itemBackgroundColor.trim()) ? props.itemBackgroundColor : '#ffffff';
    }

    const itemBackdropBlur = finiteNumber(props.itemBackdropBlur);
    if (itemBackdropBlur && itemBackdropBlur > 0) {
        itemStyle.backdropFilter = `blur(${itemBackdropBlur}px)`;
        itemStyle.WebkitBackdropFilter = `blur(${itemBackdropBlur}px)`;
    }

    itemStyle.borderStyle = (typeof props.itemBorderStyle === 'string' && props.itemBorderStyle.trim()) ? props.itemBorderStyle : 'solid';
    itemStyle.borderColor = (typeof props.itemBorderColor === 'string' && props.itemBorderColor.trim()) ? props.itemBorderColor : '#e5e7eb';
    itemStyle.borderWidth = boxValue(props, 'itemBorderWidthLinked', 'itemBorderWidthTop', 'itemBorderWidthRight', 'itemBorderWidthBottom', 'itemBorderWidthLeft', 'itemBorderWidth', '1px');
    itemStyle.borderRadius = (typeof props.itemBorderRadius === 'number' && Number.isFinite(props.itemBorderRadius)) ? `${props.itemBorderRadius}px` : '10px';

    const itemPaddingLinked = finiteNumber(props.itemPaddingLinked);
    const itemPaddingTop = finiteNumber(props.itemPaddingTop);
    const itemPaddingRight = finiteNumber(props.itemPaddingRight);
    const itemPaddingBottom = finiteNumber(props.itemPaddingBottom);
    const itemPaddingLeft = finiteNumber(props.itemPaddingLeft);
    const itemPaddingX = finiteNumber(props.itemPaddingX) ?? 14;
    const itemPaddingY = finiteNumber(props.itemPaddingY) ?? 12;
    if (itemPaddingLinked !== undefined) {
        itemStyle.padding = `${itemPaddingLinked}px`;
    } else if ([itemPaddingTop, itemPaddingRight, itemPaddingBottom, itemPaddingLeft].some((v) => v !== undefined)) {
        itemStyle.padding = `${itemPaddingTop ?? itemPaddingY}px ${itemPaddingRight ?? itemPaddingX}px ${itemPaddingBottom ?? itemPaddingY}px ${itemPaddingLeft ?? itemPaddingX}px`;
    } else {
        itemStyle.padding = `${itemPaddingY}px ${itemPaddingX}px`;
    }

    const itemMargin = boxValue(props, 'itemMarginLinked', 'itemMarginTop', 'itemMarginRight', 'itemMarginBottom', 'itemMarginLeft', null, undefined);
    if (itemMargin) itemStyle.margin = itemMargin;

    const questionStyle = {};
    if (typeof props.questionColor === 'string' && props.questionColor.trim()) questionStyle.color = props.questionColor;
    if (typeof props.questionFontFamily === 'string' && props.questionFontFamily.trim()) questionStyle.fontFamily = props.questionFontFamily;
    if (typeof props.questionFontSize === 'number' && Number.isFinite(props.questionFontSize)) questionStyle.fontSize = `${props.questionFontSize}px`;
    if (typeof props.questionFontWeight === 'string' && props.questionFontWeight.trim()) questionStyle.fontWeight = props.questionFontWeight;
    if (typeof props.questionLineHeight === 'number' && Number.isFinite(props.questionLineHeight)) questionStyle.lineHeight = `${props.questionLineHeight}px`;
    if (typeof props.questionLetterSpacing === 'number' && Number.isFinite(props.questionLetterSpacing)) questionStyle.letterSpacing = `${props.questionLetterSpacing}px`;
    if (typeof props.questionWordSpacing === 'number' && Number.isFinite(props.questionWordSpacing)) questionStyle.wordSpacing = `${props.questionWordSpacing}px`;

    const questionBorderStyle = typeof props.questionBorderStyle === 'string' && props.questionBorderStyle.trim() ? props.questionBorderStyle : '';
    if (questionBorderStyle) questionStyle.borderStyle = questionBorderStyle;
    if (typeof props.questionBorderColor === 'string' && props.questionBorderColor.trim()) questionStyle.borderColor = props.questionBorderColor;
    const questionBorderWidth = boxValue(props, 'questionBorderWidthLinked', 'questionBorderWidthTop', 'questionBorderWidthRight', 'questionBorderWidthBottom', 'questionBorderWidthLeft', null, undefined);
    if (questionBorderWidth) questionStyle.borderWidth = questionBorderWidth;

    const answerStyle = {};
    if (typeof props.answerColor === 'string' && props.answerColor.trim()) answerStyle.color = props.answerColor;
    if (typeof props.answerFontFamily === 'string' && props.answerFontFamily.trim()) answerStyle.fontFamily = props.answerFontFamily;
    if (typeof props.answerFontSize === 'number' && Number.isFinite(props.answerFontSize)) answerStyle.fontSize = `${props.answerFontSize}px`;
    if (typeof props.answerFontWeight === 'string' && props.answerFontWeight.trim()) answerStyle.fontWeight = props.answerFontWeight;
    if (typeof props.answerLineHeight === 'number' && Number.isFinite(props.answerLineHeight)) answerStyle.lineHeight = `${props.answerLineHeight}px`;
    if (typeof props.answerLetterSpacing === 'number' && Number.isFinite(props.answerLetterSpacing)) answerStyle.letterSpacing = `${props.answerLetterSpacing}px`;
    if (typeof props.answerWordSpacing === 'number' && Number.isFinite(props.answerWordSpacing)) answerStyle.wordSpacing = `${props.answerWordSpacing}px`;

    const alignClasses = {
        left: 'text-left',
        center: 'text-center',
        right: 'text-right',
    };

    const iconType = ['chevron', 'plus', 'arrow'].includes(props.iconType) ? props.iconType : 'none';
    const iconPosition = props.iconPosition === 'left' ? 'left' : 'right';
    const hasIcon = iconType !== 'none';
    const iconSvg = FAQ_ICONS[iconType] || '';

    const summaryBaseStyle = {
        ...questionStyle,
        cursor: 'pointer',
        fontWeight: questionStyle.fontWeight || 600,
        listStyle: 'none',
    };
    if (hasIcon) {
        summaryBaseStyle.display = 'flex';
        summaryBaseStyle.alignItems = 'center';
        summaryBaseStyle.gap = '8px';
    }

    const iconStyle = {
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        flexShrink: 0,
        transition: 'transform 0.3s ease',
    };

    const list = items.length ? items : [{ question: 'Question', answer: '<p>Answer</p>' }];

    return (
        <div className={alignClasses[align] || 'text-left'} style={Object.keys(wrapperStyle).length ? wrapperStyle : undefined}>
            {hasIcon ? (
                <style>{`
                    .mp-faqs__item[data-open="true"] .mp-faqs__icon { transform: rotate(180deg); }
                    .mp-faqs__item[data-open="true"] .mp-faqs__icon--plus { transform: rotate(45deg); }
                `}</style>
            ) : null}
            <FaqAccordion
                items={list}
                align={align}
                gap={gap}
                openFirst={openFirst}
                itemStyle={itemStyle}
                summaryStyle={summaryBaseStyle}
                answerStyle={{ ...answerStyle, marginTop: '8px' }}
                hasIcon={hasIcon}
                iconPosition={iconPosition}
                iconClass={`mp-faqs__icon${iconType === 'plus' ? ' mp-faqs__icon--plus' : ''}`}
                iconStyle={iconStyle}
                iconSvg={iconSvg}
            />
        </div>
    );
}

const faqsSchema = {
    content: [
        { type: CONTROL.CONTROL_GROUP, kind: 'repeater', label: 'FAQ Items', bind: 'props.items', itemSchema: {
            itemLabel: 'FAQ',
            accordion: true,
            defaultItem: () => ({ question: 'Question', answer: '<p>Answer</p>' }),
            fields: [
                { name: 'question', label: 'Question' },
                { name: 'answer', label: 'Answer', type: 'wysiwyg' },
            ],
        }},
        { type: CONTROL.CONTROL_SWITCHER, name: 'openFirst', label: 'Open First', bind: 'props.openFirst' },
        { type: CONTROL.CONTROL_SELECT, name: 'align', label: 'Align', bind: 'props.align', options: [
            { value: 'left', label: 'Left' },
            { value: 'center', label: 'Center' },
            { value: 'right', label: 'Right' },
        ]},
        { type: CONTROL.CONTROL_SELECT, name: 'iconType', label: 'Icon', bind: 'props.iconType', options: [
            { value: 'none', label: 'None' },
            { value: 'chevron', label: 'Chevron' },
            { value: 'plus', label: 'Plus / Minus' },
            { value: 'arrow', label: 'Arrow' },
        ]},
        { type: CONTROL.CONTROL_SELECT, name: 'iconPosition', label: 'Icon Position', bind: 'props.iconPosition', options: [
            { value: 'right', label: 'Right' },
            { value: 'left', label: 'Left' },
        ], condition: [{ iconType: { operator: '!=', value: 'none' } }] },
    ],
    style: [
        {
            title: 'Wrapper',
            defaultOpen: true,
            children: [
                { type: CONTROL.CONTROL_DIMENSIONS, name: 'margin', label: 'Margin', bind: { linked: 'props.marginLinked', t: 'props.marginTop', r: 'props.marginRight', b: 'props.marginBottom', l: 'props.marginLeft' } },
                { type: CONTROL.CONTROL_DIMENSIONS, name: 'padding', label: 'Padding', bind: { linked: 'props.paddingLinked', t: 'props.paddingTop', r: 'props.paddingRight', b: 'props.paddingBottom', l: 'props.paddingLeft' } },
                { type: CONTROL.CONTROL_SLIDER, name: 'gap', label: 'Inner Gap', bind: 'props.gap', kind: 'spacing', units: ['px'], defaultUnit: 'px', min: 0, max: 80, step: 1 },
                { type: CONTROL.CONTROL_GROUP, kind: 'background', label: 'Background', bind: {
                    type: 'props.backgroundType', color: 'props.backgroundColor', from: 'props.gradientFrom', to: 'props.gradientTo',
                    fromLocation: 'props.gradientFromLocation', toLocation: 'props.gradientToLocation', gradientType: 'props.gradientType', position: 'props.gradientPosition', backdropBlur: 'props.backdropBlur',
                }},
            ],
        },
        {
            title: 'FAQ Item',
            defaultOpen: true,
            children: [
                { type: CONTROL.CONTROL_GROUP, kind: 'border', label: 'Border', bind: {
                    styleKey: 'props.itemBorderStyle',
                    colorKey: 'props.itemBorderColor',
                    widthKeys: { linked: 'props.itemBorderWidthLinked', t: 'props.itemBorderWidthTop', r: 'props.itemBorderWidthRight', b: 'props.itemBorderWidthBottom', l: 'props.itemBorderWidthLeft' },
                }, includeRadius: true, radiusBind: 'props.itemBorderRadius' },
                { type: CONTROL.CONTROL_GROUP, kind: 'background', label: 'Background', bind: {
                    type: 'props.itemBackgroundType', color: 'props.itemBackgroundColor', from: 'props.itemGradientFrom', to: 'props.itemGradientTo',
                    fromLocation: 'props.itemGradientFromLocation', toLocation: 'props.itemGradientToLocation', gradientType: 'props.itemGradientType', position: 'props.itemGradientPosition', angle: 'props.itemGradientAngle', backdropBlur: 'props.itemBackdropBlur',
                }},
                { type: CONTROL.CONTROL_DIMENSIONS, name: 'itemPadding', label: 'Padding', bind: { linked: 'props.itemPaddingLinked', t: 'props.itemPaddingTop', r: 'props.itemPaddingRight', b: 'props.itemPaddingBottom', l: 'props.itemPaddingLeft' } },
                { type: CONTROL.CONTROL_DIMENSIONS, name: 'itemMargin', label: 'Margin', bind: { linked: 'props.itemMarginLinked', t: 'props.itemMarginTop', r: 'props.itemMarginRight', b: 'props.itemMarginBottom', l: 'props.itemMarginLeft' } },
            ],
        },
        {
            title: 'Question',
            defaultOpen: true,
            children: [
                { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Typography', bind: { fontFamily: 'props.questionFontFamily', fontSize: 'props.questionFontSize', fontWeight: 'props.questionFontWeight', lineHeight: 'props.questionLineHeight', letterSpacing: 'props.questionLetterSpacing', wordSpacing: 'props.questionWordSpacing' } },
                { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'questionColor', label: 'Color', bind: 'props.questionColor' },
                { type: CONTROL.CONTROL_GROUP, kind: 'border', label: 'Border', bind: {
                    styleKey: 'props.questionBorderStyle',
                    colorKey: 'props.questionBorderColor',
                    widthKeys: { linked: 'props.questionBorderWidthLinked', t: 'props.questionBorderWidthTop', r: 'props.questionBorderWidthRight', b: 'props.questionBorderWidthBottom', l: 'props.questionBorderWidthLeft' },
                }, includeRadius: false },
            ],
        },
        {
            title: 'Answer',
            defaultOpen: true,
            children: [
                { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Typography', bind: { fontFamily: 'props.answerFontFamily', fontSize: 'props.answerFontSize', fontWeight: 'props.answerFontWeight', lineHeight: 'props.answerLineHeight', letterSpacing: 'props.answerLetterSpacing', wordSpacing: 'props.answerWordSpacing' } },
                { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'answerColor', label: 'Color', bind: 'props.answerColor' },
            ],
        },
    ],
    advanced: [],
};

registerElement({
    type: 'faqs',
    label: 'FAQs',
    group: 'Basic',
    defaultProps: { items: [{ question: 'Question', answer: '<p>Answer</p>' }], openFirst: false, align: 'left', iconType: 'none', iconPosition: 'right', gap: 10, itemBorderStyle: 'solid', itemBorderWidth: 1, itemBorderWidthTop: 1, itemBorderWidthRight: 1, itemBorderWidthBottom: 1, itemBorderWidthLeft: 1, itemBorderRadius: 10, itemBorderColor: '#e5e7eb', itemBackgroundColor: '#ffffff' },
    render: renderFaqsPreview,
    schema: faqsSchema,
});
