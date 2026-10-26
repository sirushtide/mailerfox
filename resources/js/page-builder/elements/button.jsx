import React from 'react';
import * as CONTROL from '../controls/types.js';
import { registerElement } from '../elementRegistry.js';
import { borderEffectClassName, borderEffectStyleVars } from '../reusable/borderEffect.jsx';
import { resolveColorToken } from '../controls/bind.js';
import { cssLengthToCss } from '../lib/typographyUtils.js';

function buttonIconPath(name) {
    const n = String(name || '').trim();
    if (n === 'arrow-right') return 'M13 7l5 5m0 0l-5 5m5-5H6';
    if (n === 'arrow-left') return 'M11 17l-5-5m0 0l5-5m-5 5h12';
    if (n === 'download') return 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4';
    if (n === 'external') return 'M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14';
    if (n === 'check') return 'M5 13l4 4L19 7';
    if (n === 'plus') return 'M12 4v16m8-8H4';
    if (n === 'heart') return 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z';
    if (n === 'star')
        return 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z';
    return '';
}

function CustomButtonIcon({ url }) {
    const src = typeof url === 'string' ? url.trim() : '';
    if (!src) return null;
    return (
        <img
            src={src}
            alt=""
            style={{ width: '1.25rem', height: '1.25rem', flexShrink: 0, objectFit: 'contain' }}
        />
    );
}

function ButtonIcon({ name }) {
    const d = buttonIconPath(name);
    if (!d) return null;
    return (
        <svg
            aria-hidden="true"
            viewBox="0 0 24 24"
            width="20"
            height="20"
            fill="none"
            stroke="currentColor"
            style={{ width: '1.25rem', height: '1.25rem', flexShrink: 0 }}
        >
            <path d={d} strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" />
        </svg>
    );
}

export function renderButtonPreview(widget, helpers = {}) {
    const w = widget;
    const globals = helpers.globals || w.globals || {};
    const firstString = (...keys) => {
        for (const key of keys) {
            const value = w.props?.[key];
            const resolved = resolveColorToken(value, globals);
            if (typeof resolved === 'string' && resolved.trim()) {
                return resolved.trim();
            }
            if (typeof value === 'string' && value.trim()) {
                return w.props[key];
            }
        }
        return '';
    };

    const applyHoverEffect = (btnEl, isHover) => {
        const effect = w.props?.hoverTextEffect;
        if (effect === 'swap-up') {
            const normalEl = btnEl.querySelector('.mp-btn-text-normal');
            const hoverEl = btnEl.querySelector('.mp-btn-text-hover');
            if (normalEl) {
                normalEl.style.transform = isHover ? 'translateY(-100%)' : 'translateY(0)';
                normalEl.style.opacity = isHover ? '0' : '1';
            }
            if (hoverEl) {
                hoverEl.style.transform = isHover ? 'translateY(0)' : 'translateY(100%)';
                hoverEl.style.opacity = isHover ? '1' : '0';
            }
        }
        if (effect === 'swap-icon') {
            const normalEl = btnEl.querySelector('.mp-btn-icon-normal');
            const hoverEl = btnEl.querySelector('.mp-btn-icon-hover');
            if (normalEl) {
                normalEl.style.transform = isHover ? 'translateX(100%)' : 'translateX(0)';
                normalEl.style.opacity = isHover ? '0' : '1';
            }
            if (hoverEl) {
                hoverEl.style.transform = isHover ? 'translateX(0)' : 'translateX(-100%)';
                hoverEl.style.opacity = isHover ? '1' : '0';
            }
        }
        if (effect === 'slide-icon-right') {
            const slideEl = btnEl.querySelector('.mp-btn-icon-slide');
            if (slideEl) {
                slideEl.style.transform = isHover ? 'translateX(4px)' : 'translateX(0)';
            }
        }
    };

    const text = (w.props && w.props.text) ? String(w.props.text) : 'Click Me';
    const buttonStyle = (w.props && w.props.style) ? String(w.props.style) : 'primary';
    const size = (w.props && w.props.size) ? String(w.props.size) : 'md';
    const fullWidth = (w.props && w.props.fullWidth) ? true : false;
    const align = (w.props && w.props.align) ? String(w.props.align) : 'left';
    const iconType = (w.props && typeof w.props.iconType === 'string') ? w.props.iconType : '';
    const icon = (w.props && typeof w.props.icon === 'string') ? w.props.icon : '';
    const iconUrl = (w.props && typeof w.props.iconUrl === 'string') ? w.props.iconUrl : '';
    const iconPosition = (w.props && typeof w.props.iconPosition === 'string') ? w.props.iconPosition : 'left';

    let boxShadow;
    if (w.props && typeof w.props.boxShadowColor === 'string' && w.props.boxShadowColor.trim()) {
        const x = typeof w.props.boxShadowX === 'number' && Number.isFinite(w.props.boxShadowX) ? w.props.boxShadowX : 0;
        const y = typeof w.props.boxShadowY === 'number' && Number.isFinite(w.props.boxShadowY) ? w.props.boxShadowY : 0;
        const blur = typeof w.props.boxShadowBlur === 'number' && Number.isFinite(w.props.boxShadowBlur) ? w.props.boxShadowBlur : 0;
        const spread = typeof w.props.boxShadowSpread === 'number' && Number.isFinite(w.props.boxShadowSpread)
            ? w.props.boxShadowSpread
            : 0;
        boxShadow = `${x}px ${y}px ${blur}px ${spread}px ${w.props.boxShadowColor}`;
    }

    const inlineStyle = {};
    if (boxShadow) inlineStyle.boxShadow = boxShadow;

    const wrapperStyle = {};

    if (w.props) {
        if (typeof w.props.fontFamily === 'string' && w.props.fontFamily.trim()) inlineStyle.fontFamily = w.props.fontFamily;
        const fontSize = cssLengthToCss(w.props.fontSize);
        if (fontSize) inlineStyle.fontSize = fontSize;
        if (typeof w.props.fontWeight === 'string' && w.props.fontWeight.trim()) inlineStyle.fontWeight = w.props.fontWeight;
        else if (typeof w.props.fontWeight === 'number' && Number.isFinite(w.props.fontWeight)) inlineStyle.fontWeight = String(w.props.fontWeight);
        const lineHeight = cssLengthToCss(w.props.lineHeight, { lineHeight: true });
        if (lineHeight) inlineStyle.lineHeight = lineHeight;
        const letterSpacing = cssLengthToCss(w.props.letterSpacing);
        if (letterSpacing) inlineStyle.letterSpacing = letterSpacing;
        const wordSpacing = cssLengthToCss(w.props.wordSpacing);
        if (wordSpacing) inlineStyle.wordSpacing = wordSpacing;

        if (typeof w.props.paddingTop === 'number' && Number.isFinite(w.props.paddingTop)) inlineStyle.paddingTop = `${w.props.paddingTop}px`;
        if (typeof w.props.paddingRight === 'number' && Number.isFinite(w.props.paddingRight)) inlineStyle.paddingRight = `${w.props.paddingRight}px`;
        if (typeof w.props.paddingBottom === 'number' && Number.isFinite(w.props.paddingBottom)) inlineStyle.paddingBottom = `${w.props.paddingBottom}px`;
        if (typeof w.props.paddingLeft === 'number' && Number.isFinite(w.props.paddingLeft)) inlineStyle.paddingLeft = `${w.props.paddingLeft}px`;

        if (!fullWidth) {
            const wCss = cssLengthToCss(w.props.width);
            if (wCss) inlineStyle.width = wCss;
        }
        const hCss = cssLengthToCss(w.props.height);
        if (hCss) inlineStyle.height = hCss;

        const resolvedTextColor = firstString('textColor', 'buttonTextColor');
        if (resolvedTextColor) {
            inlineStyle.color = resolvedTextColor;
        }

        if (typeof w.props.marginTop === 'number' && Number.isFinite(w.props.marginTop)) wrapperStyle.marginTop = `${w.props.marginTop}px`;
        if (typeof w.props.marginRight === 'number' && Number.isFinite(w.props.marginRight)) wrapperStyle.marginRight = `${w.props.marginRight}px`;
        if (typeof w.props.marginBottom === 'number' && Number.isFinite(w.props.marginBottom)) wrapperStyle.marginBottom = `${w.props.marginBottom}px`;
        if (typeof w.props.marginLeft === 'number' && Number.isFinite(w.props.marginLeft)) wrapperStyle.marginLeft = `${w.props.marginLeft}px`;

        const bgType = typeof w.props.backgroundType === 'string'
            ? w.props.backgroundType
            : (typeof w.props.buttonBackgroundType === 'string' ? w.props.buttonBackgroundType : 'classic');
        if (bgType === 'gradient') {
            const from = firstString('gradientFrom', 'buttonGradientFrom') || '#111827';
            const to = firstString('gradientTo', 'buttonGradientTo') || '#3b82f6';
            const fromLoc = typeof w.props.gradientFromLocation === 'number' && Number.isFinite(w.props.gradientFromLocation) ? w.props.gradientFromLocation : 0;
            const toLoc = typeof w.props.gradientToLocation === 'number' && Number.isFinite(w.props.gradientToLocation) ? w.props.gradientToLocation : 100;
            const type = typeof w.props.gradientType === 'string'
                ? w.props.gradientType
                : (typeof w.props.buttonGradientType === 'string' ? w.props.buttonGradientType : 'linear');
            const position = firstString('gradientPosition', 'buttonGradientPosition') || 'top center';

            if (type === 'radial') {
                inlineStyle.backgroundImage = `radial-gradient(at ${position}, ${from} ${fromLoc}%, ${to} ${toLoc}%)`;
            } else {
                inlineStyle.backgroundImage = `linear-gradient(135deg, ${from} ${fromLoc}%, ${to} ${toLoc}%)`;
            }
        } else {
            const resolvedBackground = firstString('backgroundColor', 'buttonBackgroundColor');
            if (resolvedBackground) {
                inlineStyle.backgroundColor = resolvedBackground;
            }
        }

        if (typeof w.props.borderStyle === 'string' && w.props.borderStyle && w.props.borderStyle !== 'default') {
            inlineStyle.borderStyle = w.props.borderStyle;
        }
        if (typeof w.props.borderColor === 'string' && w.props.borderColor.trim()) {
            inlineStyle.borderColor = w.props.borderColor;
        }
        const bwTop = cssLengthToCss(w.props.borderWidthTop ?? w.props.borderWidth);
        const bwRight = cssLengthToCss(w.props.borderWidthRight ?? w.props.borderWidth);
        const bwBottom = cssLengthToCss(w.props.borderWidthBottom ?? w.props.borderWidth);
        const bwLeft = cssLengthToCss(w.props.borderWidthLeft ?? w.props.borderWidth);
        if (bwTop || bwRight || bwBottom || bwLeft) {
            if (bwTop) inlineStyle.borderTopWidth = bwTop;
            if (bwRight) inlineStyle.borderRightWidth = bwRight;
            if (bwBottom) inlineStyle.borderBottomWidth = bwBottom;
            if (bwLeft) inlineStyle.borderLeftWidth = bwLeft;
        }

        const brTop = cssLengthToCss(w.props.borderRadiusTop ?? w.props.borderRadius);
        const brRight = cssLengthToCss(w.props.borderRadiusRight ?? w.props.borderRadius);
        const brBottom = cssLengthToCss(w.props.borderRadiusBottom ?? w.props.borderRadius);
        const brLeft = cssLengthToCss(w.props.borderRadiusLeft ?? w.props.borderRadius);
        if (brTop) inlineStyle.borderTopLeftRadius = brTop;
        if (brRight) inlineStyle.borderTopRightRadius = brRight;
        if (brBottom) inlineStyle.borderBottomRightRadius = brBottom;
        if (brLeft) inlineStyle.borderBottomLeftRadius = brLeft;
    }

    const sizeClasses = {
        xs: 'px-2.5 py-1.5 text-xs',
        sm: 'px-3 py-2 text-sm',
        md: 'px-4 py-2 text-base',
        lg: 'px-5 py-3 text-lg',
        xl: 'px-6 py-3.5 text-xl',
    };

    const styleClasses = {
        primary: 'bg-blue-600 text-white hover:bg-blue-700 border-transparent',
        secondary: 'bg-gray-600 text-white hover:bg-gray-700 border-transparent',
        outline: 'bg-transparent text-blue-600 hover:bg-blue-50 border-blue-600',
        ghost: 'bg-transparent text-gray-700 hover:bg-gray-100 border-transparent',
    };

    const alignClasses = {
        left: 'text-left',
        center: 'text-center',
        right: 'text-right',
    };

    const derivedIconType = ['none', 'preset', 'custom'].includes(iconType) ? iconType : (iconUrl ? 'custom' : icon ? 'preset' : 'none');
    const iconEl = derivedIconType === 'custom' ? <CustomButtonIcon url={iconUrl} /> : derivedIconType === 'preset' ? <ButtonIcon name={icon} /> : null;
    const hasIcon = Boolean(iconEl);

    const bfxClass = borderEffectClassName(w.props || {});
    Object.assign(inlineStyle, borderEffectStyleVars(w.props || {}));

    const url = (w.props && typeof w.props.url === 'string') ? w.props.url.trim() : '';
    const isLink = url !== '';
    const target = (w.props && typeof w.props.target === 'string') ? w.props.target.trim() : '_self';
    const Tag = isLink ? 'a' : 'button';

    return (
        <div className={alignClasses[align] || 'text-left'} style={Object.keys(wrapperStyle).length ? wrapperStyle : undefined}>
            <Tag
                type={isLink ? undefined : 'button'}
                href={isLink ? url : undefined}
                target={isLink && target === '_blank' ? '_blank' : undefined}
                rel={isLink && target === '_blank' ? 'noopener noreferrer' : undefined}
                className={`inline-flex items-center justify-center font-semibold rounded-lg border transition-colors ${hasIcon ? 'gap-2' : ''} ${sizeClasses[size] || sizeClasses.md} ${styleClasses[buttonStyle] || styleClasses.primary} ${fullWidth ? 'w-full' : ''} ${bfxClass}`}
                style={{
                    ...(Object.keys(inlineStyle).length ? inlineStyle : undefined),
                    ...(isLink ? { textDecoration: 'none' } : {}),
                }}
                onMouseEnter={(e) => {
                    const el = e.currentTarget;
                    if (!w.props) return;

                    if (el.dataset.mpOrigColor === undefined) {
                        el.dataset.mpOrigColor = el.style.color || '';
                    }
                    if (el.dataset.mpOrigBg === undefined) {
                        el.dataset.mpOrigBg = el.style.backgroundColor || '';
                    }
                    if (el.dataset.mpOrigBgImg === undefined) {
                        el.dataset.mpOrigBgImg = el.style.backgroundImage || '';
                    }

                    applyHoverEffect(el, true);

                    const hoverTextColor = firstString('hoverTextColor', 'buttonHoverTextColor');
                    if (hoverTextColor) {
                        el.style.color = hoverTextColor;
                    }
                    const hoverBgType = typeof w.props.hoverBackgroundType === 'string'
                        ? w.props.hoverBackgroundType
                        : (typeof w.props.buttonHoverBackgroundType === 'string' ? w.props.buttonHoverBackgroundType : 'classic');
                    if (hoverBgType === 'gradient') {
                        const from = firstString('hoverGradientFrom', 'buttonHoverGradientFrom') || '#111827';
                        const to = firstString('hoverGradientTo', 'buttonHoverGradientTo') || '#3b82f6';
                        const fromLoc = typeof w.props.hoverGradientFromLocation === 'number' && Number.isFinite(w.props.hoverGradientFromLocation) ? w.props.hoverGradientFromLocation : 0;
                        const toLoc = typeof w.props.hoverGradientToLocation === 'number' && Number.isFinite(w.props.hoverGradientToLocation) ? w.props.hoverGradientToLocation : 100;
                        const type = typeof w.props.hoverGradientType === 'string'
                            ? w.props.hoverGradientType
                            : (typeof w.props.buttonHoverGradientType === 'string' ? w.props.buttonHoverGradientType : 'linear');
                        const position = firstString('hoverGradientPosition', 'buttonHoverGradientPosition') || 'top center';

                        el.style.backgroundColor = '';
                        if (type === 'radial') {
                            el.style.backgroundImage = `radial-gradient(at ${position}, ${from} ${fromLoc}%, ${to} ${toLoc}%)`;
                        } else {
                            el.style.backgroundImage = `linear-gradient(135deg, ${from} ${fromLoc}%, ${to} ${toLoc}%)`;
                        }
                    } else if (firstString('hoverBackgroundColor', 'buttonHoverBackgroundColor')) {
                        el.style.backgroundImage = '';
                        el.style.backgroundColor = firstString('hoverBackgroundColor', 'buttonHoverBackgroundColor');
                    }
                }}
                onMouseLeave={(e) => {
                    const el = e.currentTarget;
                    if (typeof document !== 'undefined' && document.activeElement === el) return;
                    const origColor = el.dataset.mpOrigColor;
                    const origBg = el.dataset.mpOrigBg;
                    const origBgImg = el.dataset.mpOrigBgImg;

                    applyHoverEffect(el, false);

                    if (origColor !== undefined) el.style.color = origColor;
                    if (origBg !== undefined) el.style.backgroundColor = origBg;
                    if (origBgImg !== undefined) el.style.backgroundImage = origBgImg;
                }}
                onFocus={(e) => {
                    const el = e.currentTarget;
                    if (!w.props) return;

                    if (el.dataset.mpOrigColor === undefined) {
                        el.dataset.mpOrigColor = el.style.color || '';
                    }
                    if (el.dataset.mpOrigBg === undefined) {
                        el.dataset.mpOrigBg = el.style.backgroundColor || '';
                    }
                    if (el.dataset.mpOrigBgImg === undefined) {
                        el.dataset.mpOrigBgImg = el.style.backgroundImage || '';
                    }

                    const focusTextColor = firstString('focusTextColor', 'buttonFocusTextColor');
                    if (focusTextColor) {
                        el.style.color = focusTextColor;
                    }

                    const focusBgType = typeof w.props.focusBackgroundType === 'string'
                        ? w.props.focusBackgroundType
                        : (typeof w.props.buttonFocusBackgroundType === 'string' ? w.props.buttonFocusBackgroundType : 'classic');
                    if (focusBgType === 'gradient') {
                        const from = firstString('focusGradientFrom', 'buttonFocusGradientFrom') || '#111827';
                        const to = firstString('focusGradientTo', 'buttonFocusGradientTo') || '#3b82f6';
                        const fromLoc = typeof w.props.focusGradientFromLocation === 'number' && Number.isFinite(w.props.focusGradientFromLocation) ? w.props.focusGradientFromLocation : 0;
                        const toLoc = typeof w.props.focusGradientToLocation === 'number' && Number.isFinite(w.props.focusGradientToLocation) ? w.props.focusGradientToLocation : 100;
                        const type = typeof w.props.focusGradientType === 'string'
                            ? w.props.focusGradientType
                            : (typeof w.props.buttonFocusGradientType === 'string' ? w.props.buttonFocusGradientType : 'linear');
                        const position = firstString('focusGradientPosition', 'buttonFocusGradientPosition') || 'top center';

                        el.style.backgroundColor = '';
                        if (type === 'radial') {
                            el.style.backgroundImage = `radial-gradient(at ${position}, ${from} ${fromLoc}%, ${to} ${toLoc}%)`;
                        } else {
                            el.style.backgroundImage = `linear-gradient(135deg, ${from} ${fromLoc}%, ${to} ${toLoc}%)`;
                        }
                    } else if (firstString('focusBackgroundColor', 'buttonFocusBackgroundColor')) {
                        el.style.backgroundImage = '';
                        el.style.backgroundColor = firstString('focusBackgroundColor', 'buttonFocusBackgroundColor');
                    }
                }}
                onBlur={(e) => {
                    const el = e.currentTarget;
                    const origColor = el.dataset.mpOrigColor;
                    const origBg = el.dataset.mpOrigBg;
                    const origBgImg = el.dataset.mpOrigBgImg;

                    if (origColor !== undefined) el.style.color = origColor;
                    if (origBg !== undefined) el.style.backgroundColor = origBg;
                    if (origBgImg !== undefined) el.style.backgroundImage = origBgImg;
                }}
            >
                {hasIcon && iconPosition === 'left' ? (
                    w.props?.hoverTextEffect === 'swap-icon' ? (
                        <span style={{ position: 'relative', display: 'inline-block', overflow: 'hidden', verticalAlign: 'middle' }}>
                            <span className="mp-btn-icon-normal" style={{ display: 'block', transition: 'transform 0.3s ease, opacity 0.3s ease' }}>{iconEl}</span>
                            <span className="mp-btn-icon-hover" style={{ position: 'absolute', top: 0, left: 0, width: '100%', display: 'block', transition: 'transform 0.3s ease, opacity 0.3s ease', transform: 'translateX(-100%)', opacity: 0 }}>{iconEl}</span>
                        </span>
                    ) : w.props?.hoverTextEffect === 'slide-icon-right' ? (
                        <span className="mp-btn-icon-slide" style={{ display: 'inline-block', transition: 'transform 0.3s ease' }}>{iconEl}</span>
                    ) : iconEl
                ) : null}
                {w.props?.hoverTextEffect === 'swap-up' ? (
                    <span style={{ position: 'relative', display: 'inline-block', overflow: 'hidden', verticalAlign: 'middle' }}>
                        <span className="mp-btn-text-normal" style={{ display: 'block', transition: 'transform 0.3s ease, opacity 0.3s ease' }}>{text}</span>
                        <span className="mp-btn-text-hover" style={{ position: 'absolute', top: 0, left: 0, width: '100%', display: 'block', transition: 'transform 0.3s ease, opacity 0.3s ease', transform: 'translateY(100%)', opacity: 0 }}>{text}</span>
                    </span>
                ) : (
                    <span>{text}</span>
                )}
                {hasIcon && iconPosition === 'right' ? (
                    w.props?.hoverTextEffect === 'swap-icon' ? (
                        <span style={{ position: 'relative', display: 'inline-block', overflow: 'hidden', verticalAlign: 'middle' }}>
                            <span className="mp-btn-icon-normal" style={{ display: 'block', transition: 'transform 0.3s ease, opacity 0.3s ease' }}>{iconEl}</span>
                            <span className="mp-btn-icon-hover" style={{ position: 'absolute', top: 0, left: 0, width: '100%', display: 'block', transition: 'transform 0.3s ease, opacity 0.3s ease', transform: 'translateX(-100%)', opacity: 0 }}>{iconEl}</span>
                        </span>
                    ) : w.props?.hoverTextEffect === 'slide-icon-right' ? (
                        <span className="mp-btn-icon-slide" style={{ display: 'inline-block', transition: 'transform 0.3s ease' }}>{iconEl}</span>
                    ) : iconEl
                ) : null}
            </Tag>
        </div>
    );
}

const buttonSchema = {
    content: [
        { type: CONTROL.CONTROL_TEXT, name: 'text', label: 'Text', bind: 'props.text' },
        { type: CONTROL.CONTROL_SELECT, name: 'iconType', label: 'Icon Type', bind: 'props.iconType', options: [
            { value: 'none', label: 'None' },
            { value: 'preset', label: 'Preset' },
            { value: 'custom', label: 'Custom Upload' },
        ]},
        { type: CONTROL.CONTROL_SELECT, name: 'icon', label: 'Icon', bind: 'props.icon', options: [
            { value: 'arrow-right', label: 'Arrow Right' },
            { value: 'arrow-left', label: 'Arrow Left' },
            { value: 'download', label: 'Download' },
            { value: 'external', label: 'External' },
            { value: 'check', label: 'Check' },
            { value: 'plus', label: 'Plus' },
            { value: 'heart', label: 'Heart' },
            { value: 'star', label: 'Star' },
        ], condition: { iconType: 'preset' } },
        { type: CONTROL.CONTROL_MEDIA, name: 'iconUrl', label: 'Icon URL', bind: 'props.iconUrl', condition: { iconType: 'custom' } },
        { type: CONTROL.CONTROL_SELECT, name: 'iconPosition', label: 'Icon Position', bind: 'props.iconPosition', options: [
            { value: 'left', label: 'Left' },
            { value: 'right', label: 'Right' },
        ], condition: { iconType: { operator: '!in', value: ['none'] } } },
        { type: CONTROL.CONTROL_URL, name: 'url', label: 'Link', bind: 'props.url' },
        { type: CONTROL.CONTROL_SELECT, name: 'target', label: 'Target', bind: 'props.target', options: [
            { value: '_self', label: 'Same Tab' },
            { value: '_blank', label: 'New Tab' },
        ]},
        { type: CONTROL.CONTROL_SELECT, name: 'align', label: 'Align', bind: 'props.align', options: [
            { value: 'left', label: 'Left' },
            { value: 'center', label: 'Center' },
            { value: 'right', label: 'Right' },
        ]},
        { type: CONTROL.CONTROL_SELECT, name: 'hoverTextEffect', label: 'Hover Text Effect', bind: 'props.hoverTextEffect', options: [
            { value: 'none', label: 'None' },
            { value: 'swap-up', label: 'Swap Up' },
            { value: 'swap-icon', label: 'Swap Icon' },
            { value: 'slide-icon-right', label: 'Slide Icon Right' },
        ]},
    ],
    style: [
        { type: CONTROL.CONTROL_SWITCHER, name: 'fullWidth', label: 'Full Width', bind: 'props.fullWidth' },
        { type: CONTROL.CONTROL_GROUP, kind: 'dimensions', label: 'Size', bind: { width: 'props.width', height: 'props.height' }, condition: { fullWidth: false } },
        { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Typography', bind: { fontFamily: 'props.fontFamily', fontSize: 'props.fontSize', fontWeight: 'props.fontWeight', lineHeight: 'props.lineHeight', letterSpacing: 'props.letterSpacing', wordSpacing: 'props.wordSpacing' } },
        { type: CONTROL.CONTROL_GROUP, kind: 'states', label: 'State Colors', modes: ['page'], tabs: ['normal', 'hover', 'focus'], children: [
            { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'textColor', label: 'Text Color', bind: {
                normal: 'props.textColor',
                hover: 'props.hoverTextColor',
                focus: 'props.focusTextColor',
            }},
            { type: CONTROL.CONTROL_GROUP, kind: 'background', label: 'Background', bind: {
                normal: { type: 'props.backgroundType', color: 'props.backgroundColor', from: 'props.gradientFrom', to: 'props.gradientTo', fromLocation: 'props.gradientFromLocation', toLocation: 'props.gradientToLocation', gradientType: 'props.gradientType', position: 'props.gradientPosition' },
                hover: { type: 'props.hoverBackgroundType', color: 'props.hoverBackgroundColor', from: 'props.hoverGradientFrom', to: 'props.hoverGradientTo', fromLocation: 'props.hoverGradientFromLocation', toLocation: 'props.hoverGradientToLocation', gradientType: 'props.hoverGradientType', position: 'props.hoverGradientPosition' },
                focus: { type: 'props.focusBackgroundType', color: 'props.focusBackgroundColor', from: 'props.focusGradientFrom', to: 'props.focusGradientTo', fromLocation: 'props.focusGradientFromLocation', toLocation: 'props.focusGradientToLocation', gradientType: 'props.focusGradientType', position: 'props.focusGradientPosition' },
            }},
        ]},
        { type: CONTROL.CONTROL_GROUP, kind: 'border', label: 'Border', bind: { styleKey: 'props.borderStyle', colorKey: 'props.borderColor', widthKeys: { linked: 'props.borderWidthLinked', t: 'props.borderWidthTop', r: 'props.borderWidthRight', b: 'props.borderWidthBottom', l: 'props.borderWidthLeft' } }, includeRadius: false },
        { type: CONTROL.CONTROL_DIMENSIONS, name: 'borderRadius', label: 'Border Radius', bind: { linked: 'props.borderRadiusLinked', t: 'props.borderRadiusTop', r: 'props.borderRadiusRight', b: 'props.borderRadiusBottom', l: 'props.borderRadiusLeft' }, kind: 'border-radius' },
        { type: CONTROL.CONTROL_GROUP, kind: 'border_effect', label: 'Animated Border', modes: ['page'], bind: { effect: 'props.borderEffect', color: 'props.borderEffectColor', color2: 'props.borderEffectColor2', speed: 'props.borderEffectSpeed', thickness: 'props.borderEffectThickness' } },
        { type: CONTROL.CONTROL_GROUP, kind: 'box_shadow', label: 'Shadow', bind: { color: 'props.boxShadowColor', offsetX: 'props.boxShadowX', offsetY: 'props.boxShadowY', blur: 'props.boxShadowBlur', spread: 'props.boxShadowSpread' } },
        { type: CONTROL.CONTROL_GROUP, kind: 'spacing', label: 'Spacing', bind: { padding: { linked: 'props.paddingLinked', t: 'props.paddingTop', r: 'props.paddingRight', b: 'props.paddingBottom', l: 'props.paddingLeft' }, margin: { linked: 'props.marginLinked', t: 'props.marginTop', r: 'props.marginRight', b: 'props.marginBottom', l: 'props.marginLeft' } } },
    ],
    advanced: [],
};

registerElement({
    type: 'button',
    label: 'Button',
    group: 'Basic',
    defaultProps: { text: 'Click Me', style: 'primary', size: 'md' },
    render: renderButtonPreview,
    schema: buttonSchema,
});
