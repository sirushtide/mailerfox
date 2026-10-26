import React from 'react';
import { renderButtonPreview } from './button.jsx';
import { registerElement } from '../elementRegistry.js';

const BUTTON_KEYS = [
    'text', 'url', 'target', 'style', 'size', 'align',
    'iconType', 'icon', 'iconUrl', 'iconPosition', 'hoverTextEffect',
];

/**
 * Legacy/AI shape: props.buttons = [{text,url,style,...}].
 * Expand it into the per-button prefixed props (btn1_/btn2_…) the editor uses,
 * but keep existing prefixed props when present (the user may have edited them).
 */
export function expandButtonGroupProps(props) {
    if (!props || typeof props !== 'object') return props || {};
    if (props.btn1_text !== undefined || !Array.isArray(props.buttons)) {
        return props;
    }

    const next = { ...props };
    delete next.buttons;
    props.buttons.forEach((button, index) => {
        if (!button || typeof button !== 'object') return;
        const prefix = `btn${index + 1}_`;
        for (const key of BUTTON_KEYS) {
            if (button[key] !== undefined && button[key] !== null) {
                next[prefix + key] = button[key];
            }
        }
    });
    return next;
}

function makePrefixedWidget(widget, prefix) {
    const props = widget.props || {};
    const prefixedProps = {};
    for (const [k, v] of Object.entries(props)) {
        if (k.startsWith(prefix)) {
            prefixedProps[k.slice(prefix.length)] = v;
        }
    }
    return { ...widget, props: prefixedProps };
}

export function renderButtonGroupPreview(widget, helpers = {}) {
    const w = widget;
    const props = expandButtonGroupProps(w.props || {});

    const align = props.align || 'left';
    const gap = typeof props.gap === 'number' && Number.isFinite(props.gap) ? props.gap : 16;
    const direction = props.direction || 'horizontal';

    const containerStyle = {
        display: 'flex',
        gap: `${gap}px`,
        flexDirection: direction === 'vertical' ? 'column' : 'row',
        justifyContent: align === 'center' ? 'center' : align === 'right' ? 'flex-end' : 'flex-start',
        alignItems: direction === 'vertical' ? 'stretch' : 'center',
    };

    const btn1Widget = makePrefixedWidget({ ...widget, props }, 'btn1_');
    const btn2Widget = makePrefixedWidget({ ...widget, props }, 'btn2_');

    const btn1Preview = renderButtonPreview(btn1Widget, helpers);
    const btn2Preview = renderButtonPreview(btn2Widget, helpers);

    return (
        <div style={containerStyle}>
            {btn1Preview}
            {btn2Preview}
        </div>
    );
}

registerElement({
    type: 'button_group',
    label: 'Button Group',
    group: 'Basic',
    defaultProps: { align: 'left', direction: 'horizontal', gap: 16 },
    render: renderButtonGroupPreview,
    schema: { content: [], style: [], advanced: [] },
});
