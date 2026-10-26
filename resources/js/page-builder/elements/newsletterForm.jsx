import React from 'react';
import * as CONTROL from '../controls/types.js';
import { registerElement } from '../elementRegistry.js';
import { renderButtonPreview } from './button.jsx';
import { makePrefixedProps } from '../reusable/prefixedWidget.js';
import {
    resolveStyleColor,
    typographyStyleFromProps,
    backgroundStyleFromProps,
    borderStyleFromProps,
    spacingStyleFromProps,
} from './loginForm.jsx';

export function renderNewsletterFormPreview(widget, helpers = {}) {
    const w = widget;
    const globals = helpers.globals || w.globals || {};
    const props = w.props || {};
    const buttonText = props.buttonText || 'Subscribe';
    const placeholder = props.placeholder || 'Enter your email';
    const listId = props.listId || props.list_id || '';

    const inputProps = makePrefixedProps(props, 'input_');

    const labelStyle = typographyStyleFromProps(inputProps, 'label', globals);
    const labelColor = resolveStyleColor(inputProps.labelColor, globals);
    if (labelColor) labelStyle.color = labelColor;

    const placeholderStyle = typographyStyleFromProps(inputProps, 'placeholder', globals);
    const placeholderColor = resolveStyleColor(inputProps.placeholderColor, globals);
    if (placeholderColor) placeholderStyle.color = placeholderColor;

    const inputStyle = {
        ...backgroundStyleFromProps(inputProps, '', globals),
        ...borderStyleFromProps(inputProps, '', globals),
        ...spacingStyleFromProps(inputProps, ''),
        ...typographyStyleFromProps(inputProps, '', globals),
    };
    const inputTextColor = resolveStyleColor(inputProps.textColor, globals);
    if (inputTextColor) inputStyle.color = inputTextColor;

    const hoverBorderColor = resolveStyleColor(inputProps.borderHoverColor, globals);
    const focusBorderColor = resolveStyleColor(inputProps.borderFocusColor, globals);

    const widgetId = w.id || helpers.widgetId || 'unknown';
    const scopeClass = `mp-newsletter-form--${String(widgetId).replace(/[^a-zA-Z0-9_-]/g, '-')}`;
    const themePrefix = ':is(html:not(.dark), html.dark) :is(.admin-theme, .customer-theme)';

    const placeholderCss = Object.keys(placeholderStyle).length
        ? `.mp-newsletter-form.${scopeClass} input::placeholder{${Object.entries(placeholderStyle).map(([key, value]) => `${key.replace(/[A-Z]/g, (m) => `-${m.toLowerCase()}`)}:${value}`).join(';')}}`
        : '';

    const stateCss = [
        hoverBorderColor ? `.mp-newsletter-form.${scopeClass} input:hover{border-color:${hoverBorderColor} !important;}` : '',
        focusBorderColor ? `.mp-newsletter-form.${scopeClass} input:focus{border-color:${focusBorderColor} !important;outline:none;}` : '',
    ].filter(Boolean).join('');

    const colorCss = [
        inputTextColor ? `${themePrefix} .mp-newsletter-form.${scopeClass} input[type="email"]{color:${inputTextColor} !important;-webkit-text-fill-color:${inputTextColor} !important;}` : '',
        placeholderColor ? `${themePrefix} .mp-newsletter-form.${scopeClass} input[type="email"]::placeholder{color:${placeholderColor} !important;opacity:1 !important;}` : '',
    ].filter(Boolean).join('');

    const buttonProps = makePrefixedProps(props, 'button_');
    buttonProps.text = typeof buttonProps.text === 'string' && buttonProps.text.trim() ? buttonProps.text : buttonText;
    buttonProps.align = typeof buttonProps.align === 'string' && buttonProps.align.trim() ? buttonProps.align : 'left';
    const buttonWidget = { ...w, props: buttonProps };
    const buttonPreview = renderButtonPreview(buttonWidget, { globals });

    const wrapperStyle = { display: 'flex', flexWrap: 'wrap', gap: '8px', alignItems: 'center' };

    return (
        <div className={`mp-newsletter-form ${scopeClass}`} style={wrapperStyle}>
            {(placeholderCss || stateCss || colorCss) ? <style>{[placeholderCss, stateCss, colorCss].filter(Boolean).join('')}</style> : null}
            <input
                type="email"
                placeholder={placeholder}
                disabled
                className="flex-1 min-w-0 text-sm"
                style={inputStyle}
            />
            {buttonPreview}
        </div>
    );
}

const newsletterFormSchema = {
    content: [
        { type: CONTROL.CONTROL_TEXT, name: 'buttonText', label: 'Button Text', bind: 'props.buttonText' },
        { type: CONTROL.CONTROL_TEXT, name: 'placeholder', label: 'Placeholder', bind: 'props.placeholder' },
        { type: CONTROL.CONTROL_TEXT, name: 'successMessage', label: 'Success Message', bind: 'props.successMessage' },
        { type: CONTROL.CONTROL_TEXT, name: 'listId', label: 'List ID', bind: 'props.listId' },
    ],
    style: [],
    advanced: [],
};

registerElement({
    type: 'newsletter_form',
    label: 'Newsletter Form',
    group: 'Basic',
    defaultProps: { buttonText: 'Subscribe', placeholder: 'Enter your email', successMessage: 'Thanks for subscribing!', listId: '' },
    render: renderNewsletterFormPreview,
    schema: newsletterFormSchema,
});
