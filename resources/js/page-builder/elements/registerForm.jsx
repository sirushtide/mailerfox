import React, { useState } from 'react';
import * as CONTROL from '../controls/types.js';
import { registerElement } from '../elementRegistry.js';
import { resolveColorToken } from '../controls/bind.js';
import { cssLengthToCss } from '../lib/typographyUtils.js';

function buttonIconPath(name) {
    const n = String(name || '').trim();
    if (n === 'arrow-right') return 'M13 7l5 5m0 0l-5 5m5-5H6';
    if (n === 'plus') return 'M12 4v16m8-8H4';
    if (n === 'user') return 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z';
    if (n === 'register') return 'M12 5v14m-7-7h14';
    return '';
}

function sanitizeSvg(svg) {
    if (typeof svg !== 'string') return '';
    return svg
        .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
        .replace(/\s+on\w+\s*=\s*["'][^"']*["']/gi, '');
}

function CustomSvgIcon({ svg }) {
    const safe = sanitizeSvg(svg);
    if (!safe) return null;
    if (safe.trim().startsWith('<svg')) {
        return <span dangerouslySetInnerHTML={{ __html: safe }} style={{ display: 'inline-flex', flexShrink: 0 }} />;
    }
    return (
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" style={{ flexShrink: 0 }}>
            <path d={safe.trim()} strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" />
        </svg>
    );
}

function ButtonIcon({ type, name, svg }) {
    if (type === 'custom') return <CustomSvgIcon svg={svg} />;
    const d = buttonIconPath(name);
    if (!d) return null;
    return (
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" style={{ flexShrink: 0 }}>
            <path d={d} strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" />
        </svg>
    );
}

function PasswordInput({ className, style, placeholder, disabled }) {
    const [visible, setVisible] = useState(false);
    return (
        <div className="relative">
            <input
                type={visible ? 'text' : 'password'}
                disabled={disabled}
                placeholder={placeholder}
                className={className}
                style={{ ...style, paddingRight: 40 }}
            />
            <button
                type="button"
                onClick={() => setVisible((v) => !v)}
                className="absolute right-2 top-1/2 -translate-y-1/2 p-1 bg-transparent border-0 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 cursor-pointer flex items-center justify-center"
                aria-label="Toggle password visibility"
            >
                {visible ? (
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                        <line x1="1" y1="1" x2="23" y2="23" />
                    </svg>
                ) : (
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                )}
            </button>
        </div>
    );
}

function makeTypographyBind(prefix) {
    return {
        fontFamily: `props.${prefix}FontFamily`,
        fontSize: `props.${prefix}FontSize`,
        fontWeight: `props.${prefix}FontWeight`,
        lineHeight: `props.${prefix}LineHeight`,
        letterSpacing: `props.${prefix}LetterSpacing`,
        wordSpacing: `props.${prefix}WordSpacing`,
    };
}

function makeBorderBind(prefix) {
    return {
        styleKey: `props.${prefix}BorderStyle`,
        colorKey: `props.${prefix}BorderColor`,
        widthKeys: {
            linked: `props.${prefix}BorderWidthLinked`,
            t: `props.${prefix}BorderWidthTop`,
            r: `props.${prefix}BorderWidthRight`,
            b: `props.${prefix}BorderWidthBottom`,
            l: `props.${prefix}BorderWidthLeft`,
        },
    };
}

function makeSpacingBind(prefix) {
    return {
        padding: {
            linked: `props.${prefix}PaddingLinked`,
            t: `props.${prefix}PaddingTop`,
            r: `props.${prefix}PaddingRight`,
            b: `props.${prefix}PaddingBottom`,
            l: `props.${prefix}PaddingLeft`,
        },
        margin: {
            linked: `props.${prefix}MarginLinked`,
            t: `props.${prefix}MarginTop`,
            r: `props.${prefix}MarginRight`,
            b: `props.${prefix}MarginBottom`,
            l: `props.${prefix}MarginLeft`,
        },
    };
}

function resolveStyleColor(value, globals) {
    const resolved = resolveColorToken(value, globals);
    return typeof resolved === 'string' ? resolved : '';
}

function typographyStyleFromProps(props, prefix, globals) {
    const p = props || {};
    const style = {};
    const color = resolveStyleColor(p[`${prefix}Color`], globals);
    const fontFamily = p[`${prefix}FontFamily`];
    const fontSize = p[`${prefix}FontSize`];
    const fontWeight = p[`${prefix}FontWeight`];
    const lineHeight = p[`${prefix}LineHeight`];
    const letterSpacing = p[`${prefix}LetterSpacing`];
    const wordSpacing = p[`${prefix}WordSpacing`];

    if (color) style.color = color;
    if (typeof fontFamily === 'string' && fontFamily.trim()) style.fontFamily = fontFamily.trim();
    const fSize = cssLengthToCss(fontSize);
    if (fSize) style.fontSize = fSize;
    if (typeof fontWeight === 'string' && fontWeight.trim()) style.fontWeight = fontWeight;
    const fLineHeight = cssLengthToCss(lineHeight, { lineHeight: true });
    if (fLineHeight) style.lineHeight = fLineHeight;
    const fLetterSpacing = cssLengthToCss(letterSpacing);
    if (fLetterSpacing) style.letterSpacing = fLetterSpacing;
    const fWordSpacing = cssLengthToCss(wordSpacing);
    if (fWordSpacing) style.wordSpacing = fWordSpacing;

    return style;
}

function backgroundStyleFromProps(props, prefix, globals) {
    const p = props || {};
    const backgroundType = p[`${prefix}BackgroundType`] === 'gradient' ? 'gradient' : 'color';
    const color = resolveStyleColor(p[`${prefix}BackgroundColor`], globals);
    const from = resolveStyleColor(p[`${prefix}GradientFrom`], globals);
    const to = resolveStyleColor(p[`${prefix}GradientTo`], globals);
    const fromLocation = Number.isFinite(Number(p[`${prefix}GradientFromLocation`])) ? Number(p[`${prefix}GradientFromLocation`]) : 0;
    const toLocation = Number.isFinite(Number(p[`${prefix}GradientToLocation`])) ? Number(p[`${prefix}GradientToLocation`]) : 100;
    const gradientType = p[`${prefix}GradientType`] === 'radial' ? 'radial' : 'linear';
    const gradientPosition = typeof p[`${prefix}GradientPosition`] === 'string' && p[`${prefix}GradientPosition`].trim() ? p[`${prefix}GradientPosition`].trim() : 'center';
    const gradientAngle = Number.isFinite(Number(p[`${prefix}GradientAngle`])) ? Number(p[`${prefix}GradientAngle`]) : 135;

    if (backgroundType === 'gradient' && (from || to)) {
        return {
            backgroundImage: gradientType === 'radial'
                ? `radial-gradient(circle at ${gradientPosition}, ${from || color || '#ffffff'} ${fromLocation}%, ${to || from || '#ffffff'} ${toLocation}%)`
                : `linear-gradient(${gradientAngle}deg, ${from || color || '#ffffff'} ${fromLocation}%, ${to || from || '#ffffff'} ${toLocation}%)`,
        };
    }

    return color ? { backgroundColor: color } : {};
}

function borderStyleFromProps(props, prefix, globals) {
    const p = props || {};
    const style = {};
    const borderType = p[`${prefix}BorderStyle`];
    const borderColor = resolveStyleColor(p[`${prefix}BorderColor`], globals);
    const linked = p[`${prefix}BorderWidthLinked`];
    const top = p[`${prefix}BorderWidthTop`];
    const right = p[`${prefix}BorderWidthRight`];
    const bottom = p[`${prefix}BorderWidthBottom`];
    const left = p[`${prefix}BorderWidthLeft`];
    const radius = p[`${prefix}BorderRadius`];
    const legacyWidth = p[`${prefix}BorderWidth`];

    if (borderType) style.borderStyle = borderType;
    if (borderColor) style.borderColor = borderColor;
    if (typeof linked === 'number' && Number.isFinite(linked)) {
        style.borderWidth = `${linked}px`;
    } else if ([top, right, bottom, left].some((v) => typeof v === 'number' && Number.isFinite(v))) {
        style.borderWidth = `${Number.isFinite(Number(top)) ? Number(top) : 0}px ${Number.isFinite(Number(right)) ? Number(right) : 0}px ${Number.isFinite(Number(bottom)) ? Number(bottom) : 0}px ${Number.isFinite(Number(left)) ? Number(left) : 0}px`;
    } else if (typeof legacyWidth === 'number' && Number.isFinite(legacyWidth)) {
        style.borderWidth = `${legacyWidth}px`;
    }
    if (typeof radius === 'number' && Number.isFinite(radius)) style.borderRadius = `${radius}px`;
    return style;
}

function spacingStyleFromProps(props, prefix) {
    const p = props || {};
    const style = {};
    const paddingLinked = p[`${prefix}PaddingLinked`];
    const paddingTop = p[`${prefix}PaddingTop`];
    const paddingRight = p[`${prefix}PaddingRight`];
    const paddingBottom = p[`${prefix}PaddingBottom`];
    const paddingLeft = p[`${prefix}PaddingLeft`];
    const marginLinked = p[`${prefix}MarginLinked`];
    const marginTop = p[`${prefix}MarginTop`];
    const marginRight = p[`${prefix}MarginRight`];
    const marginBottom = p[`${prefix}MarginBottom`];
    const marginLeft = p[`${prefix}MarginLeft`];
    const legacyPaddingY = p[`${prefix}PaddingY`];
    const legacyPaddingX = p[`${prefix}PaddingX`];

    if (typeof paddingLinked === 'number' && Number.isFinite(paddingLinked)) {
        style.padding = `${paddingLinked}px`;
    } else if ([paddingTop, paddingRight, paddingBottom, paddingLeft].some((v) => typeof v === 'number' && Number.isFinite(v))) {
        style.padding = `${Number.isFinite(Number(paddingTop)) ? Number(paddingTop) : 0}px ${Number.isFinite(Number(paddingRight)) ? Number(paddingRight) : 0}px ${Number.isFinite(Number(paddingBottom)) ? Number(paddingBottom) : 0}px ${Number.isFinite(Number(paddingLeft)) ? Number(paddingLeft) : 0}px`;
    } else if (typeof legacyPaddingY === 'number' && Number.isFinite(legacyPaddingY)) {
        style.padding = `${legacyPaddingY}px ${Number.isFinite(Number(legacyPaddingX)) ? Number(legacyPaddingX) : legacyPaddingY}px`;
    }

    if (typeof marginLinked === 'number' && Number.isFinite(marginLinked)) {
        style.margin = `${marginLinked}px`;
    } else if ([marginTop, marginRight, marginBottom, marginLeft].some((v) => typeof v === 'number' && Number.isFinite(v))) {
        style.margin = `${Number.isFinite(Number(marginTop)) ? Number(marginTop) : 0}px ${Number.isFinite(Number(marginRight)) ? Number(marginRight) : 0}px ${Number.isFinite(Number(marginBottom)) ? Number(marginBottom) : 0}px ${Number.isFinite(Number(marginLeft)) ? Number(marginLeft) : 0}px`;
    }

    return style;
}

export function renderRegisterFormPreview(widget, helpers = {}) {
    const p = widget.props || {};
    const globals = helpers.globals || {};
    const emailLabel = p.emailLabel || 'Email';
    const emailPlaceholder = p.emailPlaceholder || 'you@example.com';
    const passwordLabel = p.passwordLabel || 'Password';
    const passwordPlaceholder = p.passwordPlaceholder || '••••••••';
    const confirmPasswordLabel = p.confirmPasswordLabel || 'Confirm Password';
    const confirmPasswordPlaceholder = p.confirmPasswordPlaceholder || '••••••••';
    const buttonText = p.buttonText || 'Create Account';
    const showLoginLink = p.showLoginLink !== false;
    const loginPrefix = p.loginPrefix || 'Already have an account?';
    const loginText = p.loginText || 'Sign in';
    const iconEl = p.buttonIconType === 'none' || !p.buttonIconType ? null : <ButtonIcon type={p.buttonIconType} name={p.buttonIcon} svg={p.buttonIconSvg} />;
    const iconPosition = p.buttonIconPosition || 'left';
    const placeholderStyle = typographyStyleFromProps(p, 'placeholder', globals);
    const labelStyle = typographyStyleFromProps(p, 'label', globals);
    const explicitLabelColor = resolveStyleColor(p.labelColor, globals);
    if (explicitLabelColor) labelStyle.color = explicitLabelColor;
    const explicitPlaceholderColor = resolveStyleColor(p.placeholderColor, globals);
    if (explicitPlaceholderColor) placeholderStyle.color = explicitPlaceholderColor;
    const inputStyle = { ...backgroundStyleFromProps(p, 'input', globals), ...borderStyleFromProps(p, 'input', globals), ...spacingStyleFromProps(p, 'input'), ...typographyStyleFromProps(p, 'input', globals) };
    const inputTextColor = resolveStyleColor(p.inputTextColor, globals);
    if (inputTextColor) inputStyle.color = inputTextColor;

    const buttonStyle = { ...backgroundStyleFromProps(p, 'button', globals), ...borderStyleFromProps(p, 'button', globals), ...spacingStyleFromProps(p, 'button'), ...typographyStyleFromProps(p, 'button', globals) };
    const buttonTextColor = resolveStyleColor(p.buttonTextColor, globals);
    if (buttonTextColor) buttonStyle.color = buttonTextColor;
    const linkStyle = typographyStyleFromProps(p, 'link', globals);
    const explicitLinkColor = resolveStyleColor(p.linkColor, globals);
    if (explicitLinkColor) linkStyle.color = explicitLinkColor;
    const footerTextStyle = typographyStyleFromProps(p, 'footerText', globals);
    const explicitFooterTextColor = resolveStyleColor(p.footerTextColor, globals);
    if (explicitFooterTextColor) footerTextStyle.color = explicitFooterTextColor;
    const placeholderCss = Object.keys(placeholderStyle).length
        ? `.mp-register-form input::placeholder{${Object.entries(placeholderStyle).map(([key, value]) => `${key.replace(/[A-Z]/g, (m) => `-${m.toLowerCase()}`)}:${value}`).join(';')}}`
        : '';
    const hoverBorderColor = resolveStyleColor(p.inputBorderHoverColor, globals);
    const focusBorderColor = resolveStyleColor(p.inputBorderFocusColor, globals);
    const stateCss = [
        hoverBorderColor ? `.mp-register-form input:hover{border-color:${hoverBorderColor} !important;}` : '',
        focusBorderColor ? `.mp-register-form input:focus{border-color:${focusBorderColor} !important;outline:none;}` : '',
    ].filter(Boolean).join('');
    const widgetId = widget.id || helpers.widgetId || 'unknown';
    const scopeClass = `mp-register-form--${String(widgetId).replace(/[^a-zA-Z0-9_-]/g, '-')}`;
    const themePrefix = ':is(html:not(.dark), html.dark) :is(.admin-theme, .customer-theme)';
    const colorCss = [
        explicitLabelColor ? `${themePrefix} .mp-register-form.${scopeClass} label{color:${explicitLabelColor} !important;}` : '',
        inputTextColor ? `${themePrefix} .mp-register-form.${scopeClass} input[type="email"],${themePrefix} .mp-register-form.${scopeClass} input[type="password"]{color:${inputTextColor} !important;-webkit-text-fill-color:${inputTextColor} !important;}` : '',
        explicitPlaceholderColor ? `${themePrefix} .mp-register-form.${scopeClass} input[type="email"]::placeholder,${themePrefix} .mp-register-form.${scopeClass} input[type="password"]::placeholder{color:${explicitPlaceholderColor} !important;opacity:1 !important;}` : '',
    ].filter(Boolean).join('');

    const wrapperStyle = {};
    if (p.maxWidth) wrapperStyle.maxWidth = `${p.maxWidth}px`;
    if (p.formBackgroundColor) wrapperStyle.backgroundColor = p.formBackgroundColor;
    if (p.formPadding) wrapperStyle.padding = `${p.formPadding}px`;
    if (p.formBorderRadius !== undefined) wrapperStyle.borderRadius = `${p.formBorderRadius}px`;

    return (
        <div className={`mp-register-form ${scopeClass} space-y-3 w-full mx-auto`} style={wrapperStyle}>
            {(placeholderCss || stateCss || colorCss) ? <style>{[placeholderCss, stateCss, colorCss].filter(Boolean).join('')}</style> : null}
            <div>
                <label className="block text-xs font-medium mb-1" style={labelStyle}>{emailLabel}</label>
                <input type="email" disabled placeholder={emailPlaceholder} className="w-full border rounded-md" style={inputStyle} />
            </div>
            <div>
                <label className="block text-xs font-medium mb-1" style={labelStyle}>{passwordLabel}</label>
                <PasswordInput disabled placeholder={passwordPlaceholder} className="w-full border rounded-md" style={inputStyle} />
            </div>
            <div>
                <label className="block text-xs font-medium mb-1" style={labelStyle}>{confirmPasswordLabel}</label>
                <PasswordInput disabled placeholder={confirmPasswordPlaceholder} className="w-full border rounded-md" style={inputStyle} />
            </div>
            <button type="button" className="w-full px-4 py-2 text-sm font-medium flex items-center justify-center gap-2 rounded-md" style={buttonStyle}>
                {iconEl && iconPosition === 'left' ? iconEl : null}
                {buttonText}
                {iconEl && iconPosition === 'right' ? iconEl : null}
            </button>
            {showLoginLink ? <div className="text-xs text-center"><span style={footerTextStyle}>{loginPrefix} </span><span style={linkStyle}>{loginText}</span></div> : null}
        </div>
    );
}

const registerFormSchema = {
    content: [
        {
            title: 'Email Field',
            defaultOpen: true,
            children: [
                { type: CONTROL.CONTROL_TEXT, name: 'emailLabel', label: 'Email Label', bind: 'props.emailLabel' },
                { type: CONTROL.CONTROL_TEXT, name: 'emailPlaceholder', label: 'Email Placeholder', bind: 'props.emailPlaceholder' },
            ],
        },
        {
            title: 'Password Field',
            defaultOpen: true,
            children: [
                { type: CONTROL.CONTROL_TEXT, name: 'passwordLabel', label: 'Password Label', bind: 'props.passwordLabel' },
                { type: CONTROL.CONTROL_TEXT, name: 'passwordPlaceholder', label: 'Password Placeholder', bind: 'props.passwordPlaceholder' },
            ],
        },
        {
            title: 'Confirm Password',
            defaultOpen: true,
            children: [
                { type: CONTROL.CONTROL_TEXT, name: 'confirmPasswordLabel', label: 'Confirm Password Label', bind: 'props.confirmPasswordLabel' },
                { type: CONTROL.CONTROL_TEXT, name: 'confirmPasswordPlaceholder', label: 'Confirm Password Placeholder', bind: 'props.confirmPasswordPlaceholder' },
            ],
        },
        {
            title: 'Submit Button',
            defaultOpen: true,
            children: [
                { type: CONTROL.CONTROL_TEXT, name: 'buttonText', label: 'Button Text', bind: 'props.buttonText' },
                { type: CONTROL.CONTROL_SELECT, name: 'buttonIconType', label: 'Button Icon', bind: 'props.buttonIconType', options: [
                    { value: 'none', label: 'None' },
                    { value: 'preset', label: 'Preset' },
                    { value: 'custom', label: 'Custom SVG' },
                ]},
                { type: CONTROL.CONTROL_SELECT, name: 'buttonIcon', label: 'Icon', bind: 'props.buttonIcon', options: [
                    { value: 'arrow-right', label: 'Arrow Right' },
                    { value: 'plus', label: 'Plus' },
                    { value: 'user', label: 'User' },
                    { value: 'register', label: 'Register' },
                ], condition: { buttonIconType: 'preset' } },
                { type: CONTROL.CONTROL_TEXTAREA, name: 'buttonIconSvg', label: 'Custom SVG', bind: 'props.buttonIconSvg', placeholder: 'Paste SVG markup or the path d attribute', rows: 4, condition: { buttonIconType: 'custom' } },
                { type: CONTROL.CONTROL_SELECT, name: 'buttonIconPosition', label: 'Icon Position', bind: 'props.buttonIconPosition', options: [
                    { value: 'left', label: 'Left' },
                    { value: 'right', label: 'Right' },
                ], condition: { buttonIconType: { operator: '!=', value: 'none' } } },
            ],
        },
        {
            title: 'Options',
            defaultOpen: false,
            children: [
                { type: CONTROL.CONTROL_SWITCHER, name: 'showLoginLink', label: 'Show Login Link', bind: 'props.showLoginLink' },
                { type: CONTROL.CONTROL_TEXT, name: 'loginPrefix', label: 'Login Prefix Text', bind: 'props.loginPrefix', condition: { showLoginLink: true } },
                { type: CONTROL.CONTROL_TEXT, name: 'loginText', label: 'Login Link Text', bind: 'props.loginText', condition: { showLoginLink: true } },
            ],
        },
    ],
    style: [
        {
            title: 'Input',
            defaultOpen: true,
            children: [
                { type: CONTROL.CONTROL_HEADING, label: 'Label' },
                { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Label Typography', bind: makeTypographyBind('label') },
                { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'labelColor', label: 'Label Color', bind: 'props.labelColor' },
                { type: CONTROL.CONTROL_HEADING, label: 'Placeholder' },
                { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Placeholder Typography', bind: makeTypographyBind('placeholder') },
                { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'placeholderColor', label: 'Placeholder Color', bind: 'props.placeholderColor' },
                { type: CONTROL.CONTROL_HEADING, label: 'Input Text' },
                { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Input Text Typography', bind: makeTypographyBind('input') },
                { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'inputTextColor', label: 'Input Text Color', bind: 'props.inputTextColor' },
                { type: CONTROL.CONTROL_GROUP, kind: 'background', label: 'Background', bind: {
                    type: 'props.inputBackgroundType',
                    color: 'props.inputBackgroundColor',
                    from: 'props.inputGradientFrom',
                    to: 'props.inputGradientTo',
                    fromLocation: 'props.inputGradientFromLocation',
                    toLocation: 'props.inputGradientToLocation',
                    gradientType: 'props.inputGradientType',
                    position: 'props.inputGradientPosition',
                    angle: 'props.inputGradientAngle',
                } },
                { type: CONTROL.CONTROL_GROUP, kind: 'border', label: 'Border', bind: makeBorderBind('input'), includeRadius: true, radiusBind: 'props.inputBorderRadius' },
                { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'inputBorderHoverColor', label: 'Border Hover Color', bind: 'props.inputBorderHoverColor' },
                { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'inputBorderFocusColor', label: 'Border Focus Color', bind: 'props.inputBorderFocusColor' },
                { type: CONTROL.CONTROL_GROUP, kind: 'spacing', label: 'Margin / Padding', bind: makeSpacingBind('input') },
            ],
        },
        {
            title: 'Buttons',
            defaultOpen: true,
            children: [
                { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Typography', bind: makeTypographyBind('button') },
                { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'buttonTextColor', label: 'Text Color', bind: 'props.buttonTextColor' },
                { type: CONTROL.CONTROL_GROUP, kind: 'background', label: 'Background', bind: {
                    type: 'props.buttonBackgroundType',
                    color: 'props.buttonBackgroundColor',
                    from: 'props.buttonGradientFrom',
                    to: 'props.buttonGradientTo',
                    fromLocation: 'props.buttonGradientFromLocation',
                    toLocation: 'props.buttonGradientToLocation',
                    gradientType: 'props.buttonGradientType',
                    position: 'props.buttonGradientPosition',
                    angle: 'props.buttonGradientAngle',
                } },
                { type: CONTROL.CONTROL_GROUP, kind: 'border', label: 'Border', bind: makeBorderBind('button'), includeRadius: true, radiusBind: 'props.buttonBorderRadius' },
            ],
        },
        {
            title: 'Links',
            defaultOpen: false,
            children: [
                { type: CONTROL.CONTROL_HEADING, label: 'Link' },
                { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Link Typography', bind: makeTypographyBind('link') },
                { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'linkColor', label: 'Link Color', bind: 'props.linkColor' },
                { type: CONTROL.CONTROL_HEADING, label: 'Text' },
                { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Text Typography', bind: makeTypographyBind('footerText') },
                { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'footerTextColor', label: 'Text Color', bind: 'props.footerTextColor' },
            ],
        },
    ],
    advanced: [],
};

registerElement({
    type: 'register_form',
    label: 'Register Form',
    group: 'Form',
    defaultProps: {
        buttonText: 'Create Account',
        showLoginLink: true,
        emailLabel: 'Email',
        emailPlaceholder: 'you@example.com',
        passwordLabel: 'Password',
        passwordPlaceholder: '••••••••',
        confirmPasswordLabel: 'Confirm Password',
        confirmPasswordPlaceholder: '••••••••',
        loginPrefix: 'Already have an account?',
        loginText: 'Sign in',
        buttonIconType: 'none',
        buttonIcon: 'arrow-right',
        buttonIconPosition: 'right',
        buttonIconSvg: '',
        inputGradientAngle: 135,
        buttonGradientAngle: 135,
    },
    render: renderRegisterFormPreview,
    schema: registerFormSchema,
});
