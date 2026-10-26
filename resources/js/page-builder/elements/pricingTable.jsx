import React from 'react';
import * as CONTROL from '../controls/types.js';
import { registerElement } from '../elementRegistry.js';
import { borderEffectClassName, borderEffectStyleVars } from '../reusable/borderEffect.jsx';
import { cssLengthToCss } from '../lib/typographyUtils.js';

export const PricingAnnualContext = React.createContext({ annual: false, setAnnual: () => {} });

const STYLES = ['default', 'classic', 'dark', 'header', 'minimal', 'split', 'gradient'];

function getSinglePlan(props) {
    if (props && typeof props.plan === 'object' && props.plan !== null) {
        return props.plan;
    }

    if (props && Array.isArray(props.plans)) {
        const legacy = props.plans.filter((p) => p && typeof p === 'object');
        const first = legacy[0];
        const second = legacy[1];
        if (first) {
            return {
                title: first.title,
                description: first.description,
                monthlyPrice: first.price,
                monthlyPeriod: first.period,
                yearlyPrice: second ? second.price : first.price,
                yearlyPeriod: second ? second.period : '/yr',
                features: first.features,
                buttonText: first.buttonText,
                buttonUrl: first.buttonUrl,
                buttonTarget: first.buttonTarget,
                featured: first.featured,
            };
        }
    }

    return null;
}

function normalizePlan(plan) {
    return {
        title: typeof plan?.title === 'string' ? plan.title : 'Plan',
        description: typeof plan?.description === 'string' ? plan.description : '',
        monthlyPrice: typeof plan?.monthlyPrice === 'string' ? plan.monthlyPrice : '$0',
        monthlyPeriod: typeof plan?.monthlyPeriod === 'string' ? plan.monthlyPeriod : '/mo',
        yearlyPrice: typeof plan?.yearlyPrice === 'string' ? plan.yearlyPrice : '$0',
        yearlyPeriod: typeof plan?.yearlyPeriod === 'string' ? plan.yearlyPeriod : '/yr',
        features: Array.isArray(plan?.features) ? plan.features : [],
        buttonText: typeof plan?.buttonText === 'string' ? plan.buttonText : 'Get started',
        buttonUrl: typeof plan?.buttonUrl === 'string' ? plan.buttonUrl : '#',
        buttonTarget: typeof plan?.buttonTarget === 'string' ? plan.buttonTarget : '_self',
        featured: Boolean(plan?.featured),
        badge: typeof plan?.badge === 'string' ? plan.badge : '',
        icon: typeof plan?.icon === 'string' ? plan.icon : '',
        iconType: typeof plan?.iconType === 'string' ? plan.iconType : '',
        iconUrl: typeof plan?.iconUrl === 'string' ? plan.iconUrl : '',
        featuresTitle: typeof plan?.featuresTitle === 'string' ? plan.featuresTitle : '',
        discount: typeof plan?.discount === 'string' ? plan.discount : '',
        buttonIconType: typeof plan?.buttonIconType === 'string' ? plan.buttonIconType : '',
        buttonIcon: typeof plan?.buttonIcon === 'string' ? plan.buttonIcon : '',
        buttonIconUrl: typeof plan?.buttonIconUrl === 'string' ? plan.buttonIconUrl : '',
        buttonIconPosition: typeof plan?.buttonIconPosition === 'string' ? plan.buttonIconPosition : 'left',
        buttonHoverTextEffect: typeof plan?.buttonHoverTextEffect === 'string' ? plan.buttonHoverTextEffect : 'none',
    };
}

function buttonIconPath(name) {
    const n = String(name || '').trim();
    if (n === 'arrow-right') return 'M13 7l5 5m0 0l-5 5m5-5H6';
    if (n === 'arrow-left') return 'M11 17l-5-5m0 0l5-5m-5 5h12';
    if (n === 'download') return 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4';
    if (n === 'external') return 'M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14';
    if (n === 'check') return 'M5 13l4 4L19 7';
    if (n === 'plus') return 'M12 4v16m8-8H4';
    if (n === 'heart') return 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z';
    if (n === 'star') return 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z';
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

function PricingTableButton({ plan, buttonStyle, className }) {
    const text = plan.buttonText || 'Get started';
    const iconType = typeof plan.buttonIconType === 'string' ? plan.buttonIconType : '';
    const icon = typeof plan.buttonIcon === 'string' ? plan.buttonIcon : '';
    const iconUrl = typeof plan.buttonIconUrl === 'string' ? plan.buttonIconUrl : '';
    const iconPosition = typeof plan.buttonIconPosition === 'string' && plan.buttonIconPosition === 'right' ? 'right' : 'left';
    const hoverTextEffect = typeof plan.buttonHoverTextEffect === 'string' ? plan.buttonHoverTextEffect : 'none';

    const derivedIconType = ['none', 'preset', 'custom'].includes(iconType) ? iconType : (iconUrl ? 'custom' : icon ? 'preset' : 'none');
    const iconEl = derivedIconType === 'custom' ? <CustomButtonIcon url={iconUrl} /> : derivedIconType === 'preset' ? <ButtonIcon name={icon} /> : null;
    const hasIcon = Boolean(iconEl);

    const applyHoverEffect = (btnEl, isHover) => {
        if (hoverTextEffect === 'swap-up') {
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
        if (hoverTextEffect === 'swap-icon') {
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
        if (hoverTextEffect === 'slide-icon-right') {
            const slideEl = btnEl.querySelector('.mp-btn-icon-slide');
            if (slideEl) {
                slideEl.style.transform = isHover ? 'translateX(4px)' : 'translateX(0)';
            }
        }
    };

    return (
        <button
            type="button"
            className={`inline-flex items-center justify-center font-semibold ${hasIcon ? 'gap-2' : ''} ${className || ''}`}
            style={buttonStyle}
            onMouseEnter={(e) => applyHoverEffect(e.currentTarget, true)}
            onMouseLeave={(e) => applyHoverEffect(e.currentTarget, false)}
        >
            {hasIcon && iconPosition === 'left' ? (
                hoverTextEffect === 'swap-icon' ? (
                    <span style={{ position: 'relative', display: 'inline-block', overflow: 'hidden', verticalAlign: 'middle' }}>
                        <span className="mp-btn-icon-normal" style={{ display: 'block', transition: 'transform 0.3s ease, opacity 0.3s ease' }}>{iconEl}</span>
                        <span className="mp-btn-icon-hover" style={{ position: 'absolute', top: 0, left: 0, width: '100%', display: 'block', transition: 'transform 0.3s ease, opacity 0.3s ease', transform: 'translateX(-100%)', opacity: 0 }}>{iconEl}</span>
                    </span>
                ) : hoverTextEffect === 'slide-icon-right' ? (
                    <span className="mp-btn-icon-slide" style={{ display: 'inline-block', transition: 'transform 0.3s ease' }}>{iconEl}</span>
                ) : iconEl
            ) : null}
            {hoverTextEffect === 'swap-up' ? (
                <span style={{ position: 'relative', display: 'inline-block', overflow: 'hidden', verticalAlign: 'middle' }}>
                    <span className="mp-btn-text-normal" style={{ display: 'block', transition: 'transform 0.3s ease, opacity 0.3s ease' }}>{text}</span>
                    <span className="mp-btn-text-hover" style={{ position: 'absolute', top: 0, left: 0, width: '100%', display: 'block', transition: 'transform 0.3s ease, opacity 0.3s ease', transform: 'translateY(100%)', opacity: 0 }}>{text}</span>
                </span>
            ) : (
                <span>{text}</span>
            )}
            {hasIcon && iconPosition === 'right' ? (
                hoverTextEffect === 'swap-icon' ? (
                    <span style={{ position: 'relative', display: 'inline-block', overflow: 'hidden', verticalAlign: 'middle' }}>
                        <span className="mp-btn-icon-normal" style={{ display: 'block', transition: 'transform 0.3s ease, opacity 0.3s ease' }}>{iconEl}</span>
                        <span className="mp-btn-icon-hover" style={{ position: 'absolute', top: 0, left: 0, width: '100%', display: 'block', transition: 'transform 0.3s ease, opacity 0.3s ease', transform: 'translateX(-100%)', opacity: 0 }}>{iconEl}</span>
                    </span>
                ) : hoverTextEffect === 'slide-icon-right' ? (
                    <span className="mp-btn-icon-slide" style={{ display: 'inline-block', transition: 'transform 0.3s ease' }}>{iconEl}</span>
                ) : iconEl
            ) : null}
        </button>
    );
}

function PlanIcon({ plan, className, color }) {
    if (plan.iconType === 'custom' && typeof plan.iconUrl === 'string' && plan.iconUrl.trim()) {
        return (
            <img
                src={plan.iconUrl.trim()}
                alt=""
                className={className || ''}
                style={{ width: '4rem', height: '4rem', flexShrink: 0, objectFit: 'contain' }}
            />
        );
    }
    if ((plan.iconType === 'text' || !plan.iconType) && typeof plan.icon === 'string' && plan.icon.trim()) {
        return <div className={`text-4xl ${className || ''}`} style={{ color: color || undefined }}>{plan.icon}</div>;
    }
    return null;
}

function typographyStyleFromProps(props, prefix) {
    const p = props || {};
    const style = {};
    const fontFamily = p[`${prefix}FontFamily`];
    const fontSize = p[`${prefix}FontSize`];
    const fontWeight = p[`${prefix}FontWeight`];
    const lineHeight = p[`${prefix}LineHeight`];
    const letterSpacing = p[`${prefix}LetterSpacing`];
    const wordSpacing = p[`${prefix}WordSpacing`];

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

function textStyleFromProps(props, prefix, color) {
    const style = typographyStyleFromProps(props, prefix);
    if (typeof color === 'string' && color.trim()) style.color = color;
    return style;
}

function parseFeature(text) {
    if (typeof text !== 'string') return { text: '', included: true };
    const t = text.trim();
    if (t.startsWith('!') || t.startsWith('-')) {
        return { text: t.slice(1).trim(), included: false };
    }
    return { text: t, included: true };
}

function buildCardBackground(props) {
    const type = typeof props.cardBackgroundType === 'string' ? props.cardBackgroundType : 'color';
    if (type === 'image') {
        const url = typeof props.cardBackgroundImageUrl === 'string' && props.cardBackgroundImageUrl.trim() ? props.cardBackgroundImageUrl.trim() : '';
        if (!url) return { backgroundColor: '#ffffff' };
        return {
            backgroundColor: '#ffffff',
            backgroundImage: `url("${url}")`,
            backgroundSize: 'cover',
            backgroundPosition: 'center',
            backgroundRepeat: 'no-repeat',
        };
    }
    if (type === 'gradient') {
        const from = typeof props.cardBackgroundGradientFrom === 'string' && props.cardBackgroundGradientFrom.trim() ? props.cardBackgroundGradientFrom.trim() : '#111827';
        const to = typeof props.cardBackgroundGradientTo === 'string' && props.cardBackgroundGradientTo.trim() ? props.cardBackgroundGradientTo.trim() : '#3b82f6';
        const gradientType = typeof props.cardBackgroundGradientType === 'string' ? props.cardBackgroundGradientType : 'linear';
        const position = typeof props.cardBackgroundGradientPosition === 'string' && props.cardBackgroundGradientPosition.trim() ? props.cardBackgroundGradientPosition.trim() : 'top center';
        if (gradientType === 'radial') {
            return { backgroundImage: `radial-gradient(at ${position}, ${from}, ${to})` };
        }
        return { backgroundImage: `linear-gradient(135deg, ${from}, ${to})` };
    }
    return { backgroundColor: typeof props.cardBackgroundColor === 'string' && props.cardBackgroundColor.trim() ? props.cardBackgroundColor : '#ffffff' };
}

function labelTypographyFromStyle(style) {
    const s = style || {};
    const css = {};
    if (typeof s.labelFontFamily === 'string' && s.labelFontFamily.trim()) css.fontFamily = s.labelFontFamily.trim();
    if (Number.isFinite(Number(s.labelFontSize))) css.fontSize = `${Number(s.labelFontSize)}px`;
    if (typeof s.labelFontWeight === 'string' && s.labelFontWeight.trim()) css.fontWeight = s.labelFontWeight.trim();
    if (Number.isFinite(Number(s.labelLineHeight))) css.lineHeight = `${Number(s.labelLineHeight)}px`;
    else if (typeof s.labelLineHeight === 'string' && s.labelLineHeight.trim()) css.lineHeight = s.labelLineHeight.trim();
    if (Number.isFinite(Number(s.labelLetterSpacing))) css.letterSpacing = `${Number(s.labelLetterSpacing)}px`;
    if (Number.isFinite(Number(s.labelWordSpacing))) css.wordSpacing = `${Number(s.labelWordSpacing)}px`;
    return css;
}

function Toggle({ annual, setAnnual, pstyle }) {
    const s = pstyle || {};
    const active = s.switcherActiveLabelColor || '#2563eb';
    const inactive = s.switcherLabelColor || '#4b5563';
    const typo = labelTypographyFromStyle(s);
    const leftLabel = typeof s.switcherLeftLabel === 'string' && s.switcherLeftLabel.trim() ? s.switcherLeftLabel.trim() : 'Monthly';
    const rightLabel = typeof s.switcherRightLabel === 'string' && s.switcherRightLabel.trim() ? s.switcherRightLabel.trim() : 'Yearly';
    const leftSub = typeof s.switcherLeftSubLabel === 'string' ? s.switcherLeftSubLabel.trim() : '';
    const rightSub = typeof s.switcherRightSubLabel === 'string' ? s.switcherRightSubLabel.trim() : '';
    const sideStyle = (isActive) => ({ color: isActive ? active : inactive, display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '2px', ...typo });
    return (
        <div className="flex items-center justify-center gap-4 mb-6">
            <button type="button" onClick={() => setAnnual(false)} className="text-sm font-medium cursor-pointer bg-transparent border-0 p-0" style={sideStyle(!annual)}>
                <span>{leftLabel}</span>
                {leftSub ? <span style={{ fontSize: '11px', fontWeight: 400, opacity: 0.8 }}>{leftSub}</span> : null}
            </button>
            <button
                type="button"
                onClick={() => setAnnual((v) => !v)}
                className="relative w-14 h-7 rounded-full transition-colors"
                style={{ background: s.switcherTrackColor || '#2563eb' }}
                aria-label="Toggle yearly billing"
            >
                <span
                    className="absolute top-1 left-1 w-5 h-5 rounded-full transition-transform"
                    style={{ background: s.switcherDotColor || '#ffffff', transform: annual ? 'translateX(28px)' : 'translateX(0)' }}
                />
            </button>
            <button type="button" onClick={() => setAnnual(true)} className="text-sm font-medium cursor-pointer bg-transparent border-0 p-0" style={sideStyle(Boolean(annual))}>
                <span>{rightLabel}</span>
                {rightSub ? <span style={{ fontSize: '11px', fontWeight: 400, opacity: 0.8 }}>{rightSub}</span> : null}
            </button>
        </div>
    );
}

function widgetTreeContainsPricingTable(node, seen = new WeakSet()) {
    if (!node || typeof node !== 'object') return false;
    if (seen.has(node)) return false;
    seen.add(node);

    if (Array.isArray(node)) {
        for (const item of node) {
            if (widgetTreeContainsPricingTable(item, seen)) {
                return true;
            }
        }
        return false;
    }

    if (node.type === 'pricing_table') {
        return true;
    }

    for (const key of Object.keys(node)) {
        const value = node[key];
        if (!value || typeof value !== 'object') {
            continue;
        }
        if (widgetTreeContainsPricingTable(value, seen)) {
            return true;
        }
    }

    return false;
}

export function sectionHasPricingTable(section) {
    // Pricing tables anywhere in the section, including inside grids.
    return widgetTreeContainsPricingTable(section);
}

export function sectionHasStandalonePricingTable(section) {
    if (!section || typeof section !== 'object') return false;
    return pricingOutsideGrids(section.columns);
}

function PricingTabs({ tabs, activeIndex, setActiveIndex, pstyle }) {
    if (!tabs || tabs.length === 0) return null;
    const s = pstyle || {};
    const typo = labelTypographyFromStyle(s);
    return (
        <div className="flex items-center justify-center flex-wrap gap-2 mb-6">
            {tabs.map((tab, idx) => {
                const text = typeof tab.text === 'string' ? tab.text : `Tab ${idx + 1}`;
                const badge = typeof tab.badge === 'string' ? tab.badge : '';
                const isActive = idx === activeIndex;
                return (
                    <button
                        key={tab.id || idx}
                        type="button"
                        onClick={() => setActiveIndex(idx)}
                        className="relative px-4 py-2 rounded-full text-sm font-medium transition-colors"
                        style={{
                            background: isActive ? (s.tabActiveBackgroundColor || '#2563eb') : (s.tabBackgroundColor || '#f3f4f6'),
                            color: isActive ? (s.tabActiveTextColor || '#ffffff') : (s.tabTextColor || '#374151'),
                            ...typo,
                        }}
                    >
                        <span>{text}</span>
                        {badge ? (
                            <span
                                className="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold"
                                style={{
                                    background: isActive ? (s.tabBadgeActiveBackgroundColor || 'rgba(255,255,255,0.2)') : (s.tabBadgeBackgroundColor || '#dbeafe'),
                                    color: isActive ? (s.tabBadgeActiveTextColor || '#ffffff') : (s.tabBadgeTextColor || '#1d4ed8'),
                                }}
                            >
                                {badge}
                            </span>
                        ) : null}
                    </button>
                );
            })}
        </div>
    );
}

function resolvePricingMode(config) {
    const tabs = Array.isArray(config.tabs) && config.tabs.length > 0 ? config.tabs : null;
    const mode = ['switcher', 'tabs'].includes(config.mode)
        ? config.mode
        : (tabs ? 'tabs' : 'switcher');
    return { mode, tabs };
}

function PricingHeaderControl({ config, annual, setAnnual, setActiveIndex }) {
    const { mode, tabs } = resolvePricingMode(config);
    const pstyle = config.style || {};

    if (mode === 'tabs' && tabs) {
        return <PricingTabs tabs={tabs} activeIndex={annual ? 1 : 0} setActiveIndex={setActiveIndex} pstyle={pstyle} />;
    }
    return <Toggle annual={annual} setAnnual={setAnnual} pstyle={pstyle} />;
}

export function pricingOutsideGrids(node, seen = new WeakSet()) {
    if (!node || typeof node !== 'object') return false;
    if (seen.has(node)) return false;
    seen.add(node);
    if (Array.isArray(node)) {
        for (const item of node) {
            if (pricingOutsideGrids(item, seen)) return true;
        }
        return false;
    }
    if (node.type === 'pricing_table') return true;
    if (node.type === 'grid') return false;
    for (const key of Object.keys(node)) {
        const value = node[key];
        if (value && typeof value === 'object' && pricingOutsideGrids(value, seen)) return true;
    }
    return false;
}

export function gridHasPricingTable(widget) {
    if (!widget || widget.type !== 'grid') return false;
    const cols = widget.props && Array.isArray(widget.props.columns) ? widget.props.columns : [];
    return pricingOutsideGrids(cols);
}

export function GridPricingScope({ widget, children }) {
    const props = widget && widget.props && typeof widget.props === 'object' ? widget.props : {};

    const hasPricing = gridHasPricingTable(widget);

    const [activeIndex, setActiveIndexRaw] = React.useState(0);
    const annual = activeIndex > 0;

    if (!hasPricing) return children;

    const setAnnual = (v) => {
        const nextAnnual = typeof v === 'function' ? v(annual) : v;
        setActiveIndexRaw(Boolean(nextAnnual) ? 1 : 0);
    };

    const config = {
        mode: props.pricingMode,
        tabs: Array.isArray(props.pricingTabs) && props.pricingTabs.length > 0 ? props.pricingTabs : null,
        style: props.pricingStyle && typeof props.pricingStyle === 'object' ? props.pricingStyle : {},
    };

    return (
        <PricingAnnualContext.Provider value={{ annual, setAnnual }}>
            <PricingHeaderControl config={config} annual={annual} setAnnual={setAnnual} setActiveIndex={setActiveIndexRaw} />
            {children}
        </PricingAnnualContext.Provider>
    );
}

export function PricingSectionProvider({ section, children }) {
    const [activeIndex, setActiveIndex] = React.useState(0);
    const annual = activeIndex > 0;
    const hasStandalonePricing = sectionHasStandalonePricingTable(section);

    const config = {
        mode: section?.pricingMode,
        tabs: Array.isArray(section?.pricingTabs) && section.pricingTabs.length > 0 ? section.pricingTabs : null,
        style: section?.pricingStyle && typeof section.pricingStyle === 'object' ? section.pricingStyle : {},
    };

    if (!hasStandalonePricing) return children;

    const setAnnual = (v) => {
        const nextAnnual = typeof v === 'function' ? v(annual) : v;
        setActiveIndex(Boolean(nextAnnual) ? 1 : 0);
    };

    return (
        <PricingAnnualContext.Provider value={{ annual, setAnnual }}>
            <PricingHeaderControl config={config} annual={annual} setAnnual={setAnnual} setActiveIndex={setActiveIndex} />
            {children}
        </PricingAnnualContext.Provider>
    );
}

function FeatureList({ features, iconType, iconUrl, includedColor = '#10b981', excludedColor = '#ef4444', featureTextColor, excludedFeatureTextColor, featureIconColor, excludedFeatureIconColor, featureTypography, excludedFeatureTypography }) {
    const items = features
        .filter((x) => typeof x === 'string' && x.trim() !== '')
        .slice(0, 10)
        .map(parseFeature);

    if (!items.length) return null;

    return (
        <ul className="mt-4 space-y-2">
            {items.map((f, fi) => (
                <li key={fi} className="flex items-start gap-2 text-sm" style={{ ...(f.included ? featureTypography : excludedFeatureTypography), color: f.included ? (featureTextColor || '#374151') : (excludedFeatureTextColor || '#6b7280') }}>
                    {iconType === 'custom' && iconUrl ? (
                        <img
                            src={iconUrl}
                            alt=""
                            aria-hidden
                            style={{ width: '16px', height: '16px', flexShrink: 0, objectFit: 'contain', marginTop: '1px', color: f.included ? (featureIconColor || includedColor) : (excludedFeatureIconColor || excludedColor) }}
                        />
                    ) : (
                        <span aria-hidden style={{ color: f.included ? (featureIconColor || includedColor) : (excludedFeatureIconColor || excludedColor), marginTop: '1px' }}>
                            {f.included ? '✓' : '✕'}
                        </span>
                    )}
                    <span>{f.text}</span>
                </li>
            ))}
        </ul>
    );
}

function DefaultPreview({ props, plan, annual, featureIconType, featureIconUrl, cardBaseStyle, featuredCardStyle, cardBfxClass, buttonStyle, align }) {
    const price = annual ? plan.yearlyPrice : plan.monthlyPrice;
    const period = annual ? plan.yearlyPeriod : plan.monthlyPeriod;
    const justifyClass = align === 'left' ? 'justify-start' : align === 'right' ? 'justify-end' : align === 'justify' ? 'justify-between' : 'justify-center';
    const alignClass = align === 'left' ? 'text-left' : align === 'right' ? 'text-right' : align === 'justify' ? 'text-justify' : 'text-center';
    const buttonPosition = props.buttonPosition === 'center' ? 'center' : 'default';
    const iconColor = typeof props.iconColor === 'string' && props.iconColor.trim() ? props.iconColor : '';
    const priceColor = typeof props.priceColor === 'string' && props.priceColor.trim() ? props.priceColor : '';
    const descriptionColor = typeof props.descriptionColor === 'string' && props.descriptionColor.trim() ? props.descriptionColor : '';
    const titleStyle = textStyleFromProps(props, 'title', props.titleColor);
    const priceTypo = typographyStyleFromProps(props, 'price');
    const descriptionTypo = typographyStyleFromProps(props, 'description');

    return (
        <div className="max-w-sm mx-auto">
            <div className={cardBfxClass || undefined} style={plan.featured ? featuredCardStyle : cardBaseStyle}>
                <div className={alignClass}>
                    <PlanIcon plan={plan} className="mb-4" color={iconColor} />
                    <div className="text-sm font-semibold text-gray-900" style={titleStyle}>{plan.title}</div>
                    <div className={`mt-2 flex items-end ${justifyClass} gap-1`}>
                        <div className="text-3xl font-bold text-gray-900" style={{ ...priceTypo, color: priceColor || undefined }}>{price}</div>
                        <div className="text-sm text-gray-600" style={{ color: priceColor || undefined }}>{period}</div>
                    </div>
                    {plan.description ? <div className="mt-2 text-sm text-gray-600" style={{ ...descriptionTypo, color: descriptionColor || undefined }}>{plan.description}</div> : null}
                </div>

                {buttonPosition === 'center' ? (
                    <PricingTableButton plan={plan} buttonStyle={buttonStyle} className="mt-5 w-full px-4 py-2" />
                ) : null}

                <FeatureList
                    features={plan.features}
                    iconType={featureIconType}
                    iconUrl={featureIconUrl}
                    featureTextColor={props.featureTextColor}
                    excludedFeatureTextColor={props.excludedFeatureTextColor}
                    featureIconColor={props.featureIconColor}
                    excludedFeatureIconColor={props.excludedFeatureIconColor}
                    featureTypography={typographyStyleFromProps(props, 'feature')}
                    excludedFeatureTypography={typographyStyleFromProps(props, 'excludedFeature')}
                />

                {buttonPosition !== 'center' ? (
                    <PricingTableButton plan={plan} buttonStyle={buttonStyle} className="mt-5 w-full px-4 py-2" />
                ) : null}
            </div>
        </div>
    );
}

function ClassicPreview({ props, plan, annual, featureIconType, featureIconUrl, cardBaseStyle, featuredCardStyle, cardBfxClass, buttonStyle, align }) {
    const price = annual ? plan.yearlyPrice : plan.monthlyPrice;
    const period = annual ? plan.yearlyPeriod : plan.monthlyPeriod;
    const alignClass = align === 'left' ? 'text-left' : align === 'right' ? 'text-right' : align === 'justify' ? 'text-justify' : 'text-center';
    const buttonPosition = props.buttonPosition === 'center' ? 'center' : 'default';
    const iconColor = typeof props.iconColor === 'string' && props.iconColor.trim() ? props.iconColor : '';
    const priceColor = typeof props.priceColor === 'string' && props.priceColor.trim() ? props.priceColor : '';
    const descriptionColor = typeof props.descriptionColor === 'string' && props.descriptionColor.trim() ? props.descriptionColor : '';
    const titleStyle = textStyleFromProps(props, 'title', props.titleColor);
    const priceTypo = typographyStyleFromProps(props, 'price');
    const descriptionTypo = typographyStyleFromProps(props, 'description');

    const button = <PricingTableButton plan={plan} buttonStyle={buttonStyle} className="mt-5 w-full px-4 py-2" />;

    return (
        <div className="max-w-sm mx-auto">
            <div className={cardBfxClass || undefined} style={plan.featured ? featuredCardStyle : cardBaseStyle}>
                <div className={alignClass}>
                    <PlanIcon plan={plan} className="mb-4" color={iconColor} />
                    <div className="text-xl font-bold text-gray-900" style={titleStyle}>{plan.title}</div>
                    {plan.description ? <div className="mt-2 text-sm text-gray-600" style={{ ...descriptionTypo, color: descriptionColor || undefined }}>{plan.description}</div> : null}
                    <div className={`mt-4 flex items-end justify-center gap-1 ${align === 'left' ? 'justify-start' : align === 'right' ? 'justify-end' : 'justify-center'}`}>
                        <div className="text-4xl font-bold text-gray-900" style={{ ...priceTypo, color: priceColor || undefined }}>{price}</div>
                        <div className="text-sm text-gray-600" style={{ color: priceColor || undefined }}>{period}</div>
                    </div>
                </div>

                {buttonPosition === 'center' ? button : null}

                <FeatureList
                    features={plan.features}
                    iconType={featureIconType}
                    iconUrl={featureIconUrl}
                    featureTextColor={props.featureTextColor}
                    excludedFeatureTextColor={props.excludedFeatureTextColor}
                    featureIconColor={props.featureIconColor}
                    excludedFeatureIconColor={props.excludedFeatureIconColor}
                    featureTypography={typographyStyleFromProps(props, 'feature')}
                    excludedFeatureTypography={typographyStyleFromProps(props, 'excludedFeature')}
                />

                {buttonPosition !== 'center' ? button : null}
            </div>
        </div>
    );
}

function DarkPreview({ props, plan, annual, featureIconType, featureIconUrl, cardBaseStyle, featuredCardStyle, cardBfxClass, buttonStyle, align }) {
    const price = annual ? plan.yearlyPrice : plan.monthlyPrice;
    const period = annual ? plan.yearlyPeriod : plan.monthlyPeriod;
    const darkBg = typeof props.darkBackgroundColor === 'string' && props.darkBackgroundColor.trim() ? props.darkBackgroundColor : '#0f172a';
    const darkText = typeof props.darkTextColor === 'string' && props.darkTextColor.trim() ? props.darkTextColor : '#f8fafc';
    const badgeBg = typeof props.badgeBackgroundColor === 'string' && props.badgeBackgroundColor.trim() ? props.badgeBackgroundColor : '#334155';
    const badgeText = typeof props.badgeTextColor === 'string' && props.badgeTextColor.trim() ? props.badgeTextColor : '#ffffff';
    const alignClass = align === 'left' ? 'text-left' : align === 'right' ? 'text-right' : align === 'justify' ? 'text-justify' : 'text-center';
    const justifyClass = align === 'left' ? 'justify-start' : align === 'right' ? 'justify-end' : align === 'justify' ? 'justify-between' : 'justify-center';
    const buttonPosition = props.buttonPosition === 'center' ? 'center' : 'default';
    const iconColor = typeof props.iconColor === 'string' && props.iconColor.trim() ? props.iconColor : '';
    const priceColor = typeof props.priceColor === 'string' && props.priceColor.trim() ? props.priceColor : '';
    const descriptionColor = typeof props.descriptionColor === 'string' && props.descriptionColor.trim() ? props.descriptionColor : '';
    const titleStyle = textStyleFromProps(props, 'title', props.titleColor || darkText);
    const priceTypo = typographyStyleFromProps(props, 'price');
    const descriptionTypo = typographyStyleFromProps(props, 'description');

    const cardStyle = {
        ...cardBaseStyle,
        color: darkText,
        borderColor: typeof props.cardBorderColor === 'string' && props.cardBorderColor.trim() ? props.cardBorderColor : '#1e293b',
    };
    if ((props.cardBackgroundType || 'color') === 'color') {
        cardStyle.backgroundColor = darkBg;
        delete cardStyle.backgroundImage;
        delete cardStyle.backgroundSize;
        delete cardStyle.backgroundPosition;
        delete cardStyle.backgroundRepeat;
    }

    return (
        <div className="max-w-sm mx-auto">
            <div className={cardBfxClass || undefined} style={cardStyle}>
                <div className={alignClass}>
                    <PlanIcon plan={plan} className="mb-4" color={iconColor} />
                    <div className={`flex items-center gap-2 ${justifyClass}`}>
                        <div className="text-xl font-bold" style={titleStyle}>{plan.title}</div>
                        {plan.badge ? <span className="text-xs px-2 py-1 rounded-full" style={{ backgroundColor: badgeBg, color: badgeText }}>{plan.badge}</span> : null}
                    </div>
                    {plan.description ? <div className="mt-2 text-sm opacity-80" style={{ ...descriptionTypo, color: descriptionColor || undefined }}>{plan.description}</div> : null}
                    <div className={`mt-4 flex items-end ${justifyClass} gap-1`}>
                        <div className="text-4xl font-bold" style={{ ...priceTypo, color: priceColor || darkText }}>{price}</div>
                        <div className="text-sm opacity-70" style={{ color: priceColor || undefined }}>{period}</div>
                    </div>
                </div>

                {buttonPosition === 'center' ? (
                    <PricingTableButton plan={plan} buttonStyle={buttonStyle} className="mt-5 w-full px-4 py-2" />
                ) : null}

                <FeatureList
                    features={plan.features}
                    iconType={featureIconType}
                    iconUrl={featureIconUrl}
                    includedColor="#22c55e"
                    excludedColor="#ef4444"
                    featureTextColor={props.featureTextColor}
                    excludedFeatureTextColor={props.excludedFeatureTextColor}
                    featureIconColor={props.featureIconColor}
                    excludedFeatureIconColor={props.excludedFeatureIconColor}
                    featureTypography={typographyStyleFromProps(props, 'feature')}
                    excludedFeatureTypography={typographyStyleFromProps(props, 'excludedFeature')}
                />

                {buttonPosition !== 'center' ? (
                    <PricingTableButton plan={plan} buttonStyle={buttonStyle} className="mt-5 w-full px-4 py-2" />
                ) : null}
            </div>
        </div>
    );
}

function HeaderPreview({ props, plan, annual, featureIconType, featureIconUrl, cardBaseStyle, featuredCardStyle, cardBfxClass, buttonStyle, align }) {
    const price = annual ? plan.yearlyPrice : plan.monthlyPrice;
    const period = annual ? plan.yearlyPeriod : plan.monthlyPeriod;
    const headerBg = typeof props.headerBackgroundColor === 'string' && props.headerBackgroundColor.trim() ? props.headerBackgroundColor : '#3b82f6';
    const headerText = typeof props.headerTextColor === 'string' && props.headerTextColor.trim() ? props.headerTextColor : '#ffffff';
    const badgeBg = typeof props.badgeBackgroundColor === 'string' && props.badgeBackgroundColor.trim() ? props.badgeBackgroundColor : '#dbeafe';
    const badgeText = typeof props.badgeTextColor === 'string' && props.badgeTextColor.trim() ? props.badgeTextColor : '#1d4ed8';
    const alignClass = align === 'left' ? 'text-left' : align === 'right' ? 'text-right' : align === 'justify' ? 'text-justify' : 'text-center';
    const justifyClass = align === 'left' ? 'justify-start' : align === 'right' ? 'justify-end' : align === 'justify' ? 'justify-between' : 'justify-center';
    const buttonPosition = props.buttonPosition === 'center' ? 'center' : 'default';
    const iconColor = typeof props.iconColor === 'string' && props.iconColor.trim() ? props.iconColor : '';
    const priceColor = typeof props.priceColor === 'string' && props.priceColor.trim() ? props.priceColor : '';
    const descriptionColor = typeof props.descriptionColor === 'string' && props.descriptionColor.trim() ? props.descriptionColor : '';
    const titleStyle = textStyleFromProps(props, 'title', props.titleColor);
    const priceTypo = typographyStyleFromProps(props, 'price');
    const descriptionTypo = typographyStyleFromProps(props, 'description');

    const cardStyle = {
        ...cardBaseStyle,
        padding: 0,
        overflow: 'hidden',
    };

    return (
        <div className="max-w-sm mx-auto">
            <div className={cardBfxClass || undefined} style={cardStyle}>
                {plan.badge ? (
                    <div className="text-center py-2 text-sm font-semibold" style={{ backgroundColor: headerBg, color: headerText }}>
                        {plan.badge}
                    </div>
                ) : null}
                <div className="p-6">
                    <div className={alignClass}>
                        <PlanIcon plan={plan} className="mb-4" color={iconColor} />
                        <div className={`flex items-center gap-2 ${justifyClass}`}>
                            <div className="text-xl font-bold text-gray-900" style={titleStyle}>{plan.title}</div>
                            {plan.discount ? <span className="text-xs px-2 py-1 rounded-full" style={{ backgroundColor: badgeBg, color: badgeText }}>{plan.discount}</span> : null}
                        </div>
                        {plan.description ? <div className="mt-2 text-sm text-gray-600" style={{ ...descriptionTypo, color: descriptionColor || undefined }}>{plan.description}</div> : null}
                        <div className={`mt-4 flex items-end ${justifyClass} gap-1`}>
                            <div className="text-4xl font-bold text-gray-900" style={{ ...priceTypo, color: priceColor || undefined }}>{price}</div>
                            <div className="text-sm text-gray-600" style={{ color: priceColor || undefined }}>{period}</div>
                        </div>
                    </div>

                    {buttonPosition === 'center' ? (
                        <PricingTableButton plan={plan} buttonStyle={buttonStyle} className="mt-5 w-full px-4 py-2" />
                    ) : null}

                    <FeatureList
                        features={plan.features}
                        iconType={featureIconType}
                        iconUrl={featureIconUrl}
                        featureTextColor={props.featureTextColor}
                        excludedFeatureTextColor={props.excludedFeatureTextColor}
                        featureIconColor={props.featureIconColor}
                        excludedFeatureIconColor={props.excludedFeatureIconColor}
                        featureTypography={typographyStyleFromProps(props, 'feature')}
                        excludedFeatureTypography={typographyStyleFromProps(props, 'excludedFeature')}
                    />

                    {buttonPosition !== 'center' ? (
                        <PricingTableButton plan={plan} buttonStyle={buttonStyle} className="mt-5 w-full px-4 py-2" />
                    ) : null}
                </div>
            </div>
        </div>
    );
}

function MinimalPreview({ props, plan, annual, featureIconType, featureIconUrl, cardBaseStyle, featuredCardStyle, cardBfxClass, buttonStyle, align }) {
    const price = annual ? plan.yearlyPrice : plan.monthlyPrice;
    const period = annual ? plan.yearlyPeriod : plan.monthlyPeriod;
    const headerBg = typeof props.headerBackgroundColor === 'string' && props.headerBackgroundColor.trim() ? props.headerBackgroundColor : '#0f172a';
    const headerText = typeof props.headerTextColor === 'string' && props.headerTextColor.trim() ? props.headerTextColor : '#ffffff';
    const alignClass = align === 'left' ? 'text-left' : align === 'right' ? 'text-right' : 'text-center';
    const iconColor = typeof props.iconColor === 'string' && props.iconColor.trim() ? props.iconColor : '';
    const priceColor = typeof props.priceColor === 'string' && props.priceColor.trim() ? props.priceColor : '';
    const descriptionColor = typeof props.descriptionColor === 'string' && props.descriptionColor.trim() ? props.descriptionColor : '';
    const titleStyle = textStyleFromProps(props, 'title', props.titleColor || headerText);
    const priceTypo = typographyStyleFromProps(props, 'price');

    const cardStyle = {
        ...cardBaseStyle,
        padding: 0,
        overflow: 'hidden',
    };

    const items = plan.features
        .filter((x) => typeof x === 'string' && x.trim() !== '')
        .slice(0, 10)
        .map(parseFeature);

    return (
        <div className="max-w-sm mx-auto">
            <div className={cardBfxClass || undefined} style={cardStyle}>
                <div className="py-6 px-6" style={{ backgroundColor: headerBg, color: headerText }}>
                    <div className={alignClass}>
                        <PlanIcon plan={plan} className="mb-2" color={iconColor} />
                        <div className="text-sm font-semibold opacity-80" style={titleStyle}>{plan.badge || plan.title}</div>
                        <div className="mt-2 flex items-end justify-center gap-1">
                            <div className="text-4xl font-bold" style={{ ...priceTypo, color: priceColor || headerText }}>{price}</div>
                            <div className="text-sm opacity-80">{period}</div>
                        </div>
                    </div>
                </div>
                <div className="py-4">
                    {items.length ? (
                        <ul className="divide-y divide-gray-100 dark:divide-gray-800">
                            {items.map((f, fi) => (
                                <li key={fi} className="flex items-center justify-center gap-3 py-3 px-6 text-sm" style={{ ...(f.included ? typographyStyleFromProps(props, 'feature') : typographyStyleFromProps(props, 'excludedFeature')), color: f.included ? (props.featureTextColor || '#374151') : (props.excludedFeatureTextColor || '#6b7280') }}>
                                    <span aria-hidden style={{ color: f.included ? (props.featureIconColor || '#10b981') : (props.excludedFeatureIconColor || '#ef4444') }}>
                                        {f.included ? '✓' : '✕'}
                                    </span>
                                    <span>{f.text}</span>
                                </li>
                            ))}
                        </ul>
                    ) : null}
                    <div className="px-6 mt-4">
                        <PricingTableButton plan={plan} buttonStyle={buttonStyle} className="w-full px-4 py-2" />
                    </div>
                </div>
            </div>
        </div>
    );
}

function SplitPreview({ props, plan, annual, featureIconType, featureIconUrl, cardBaseStyle, featuredCardStyle, cardBfxClass, buttonStyle, align }) {
    const price = annual ? plan.yearlyPrice : plan.monthlyPrice;
    const period = annual ? plan.yearlyPeriod : plan.monthlyPeriod;
    const badgeBg = typeof props.badgeBackgroundColor === 'string' && props.badgeBackgroundColor.trim() ? props.badgeBackgroundColor : '#111827';
    const badgeText = typeof props.badgeTextColor === 'string' && props.badgeTextColor.trim() ? props.badgeTextColor : '#ffffff';
    const leftBg = typeof props.featuredBackgroundColor === 'string' && props.featuredBackgroundColor.trim() ? props.featuredBackgroundColor : '#ccfbf1';
    const iconColor = typeof props.iconColor === 'string' && props.iconColor.trim() ? props.iconColor : '';
    const priceColor = typeof props.priceColor === 'string' && props.priceColor.trim() ? props.priceColor : '';
    const descriptionColor = typeof props.descriptionColor === 'string' && props.descriptionColor.trim() ? props.descriptionColor : '';
    const titleStyle = textStyleFromProps(props, 'title', props.titleColor);
    const priceTypo = typographyStyleFromProps(props, 'price');
    const descriptionTypo = typographyStyleFromProps(props, 'description');

    const cardStyle = {
        ...cardBaseStyle,
        padding: 0,
        overflow: 'hidden',
    };

    return (
        <div className="max-w-2xl mx-auto">
            <div className={cardBfxClass || undefined} style={cardStyle}>
                <div className="grid grid-cols-1 md:grid-cols-2">
                    <div className="p-6 md:p-8" style={{ backgroundColor: leftBg }}>
                        {plan.badge ? <span className="inline-block text-xs px-3 py-1 rounded-full mb-4" style={{ backgroundColor: badgeBg, color: badgeText }}>{plan.badge}</span> : null}
                        <PlanIcon plan={plan} className="mb-4" color={iconColor} />
                        <div className="text-xl font-bold text-gray-900" style={titleStyle}>{plan.title}</div>
                        {plan.description ? <div className="mt-2 text-sm text-gray-600" style={{ ...descriptionTypo, color: descriptionColor || undefined }}>{plan.description}</div> : null}
                        <div className="mt-4 flex items-end gap-1">
                            <div className="text-4xl font-bold text-gray-900" style={{ ...priceTypo, color: priceColor || undefined }}>{price}</div>
                            <div className="text-sm text-gray-600" style={{ color: priceColor || undefined }}>{period}</div>
                        </div>
                        <PricingTableButton plan={plan} buttonStyle={buttonStyle} className="mt-6 w-full px-4 py-2" />
                    </div>
                    <div className="p-6 md:p-8 bg-white dark:bg-gray-900">
                        {plan.featuresTitle ? <div className="text-lg font-bold text-gray-900 mb-4">{plan.featuresTitle}</div> : null}
                        <FeatureList
                            features={plan.features}
                            iconType={featureIconType}
                            iconUrl={featureIconUrl}
                            featureTextColor={props.featureTextColor}
                            excludedFeatureTextColor={props.excludedFeatureTextColor}
                            featureIconColor={props.featureIconColor}
                            excludedFeatureIconColor={props.excludedFeatureIconColor}
                            featureTypography={typographyStyleFromProps(props, 'feature')}
                            excludedFeatureTypography={typographyStyleFromProps(props, 'excludedFeature')}
                        />
                    </div>
                </div>
            </div>
        </div>
    );
}

function GradientPreview({ props, plan, annual, featureIconType, featureIconUrl, cardBaseStyle, featuredCardStyle, cardBfxClass, buttonStyle, align }) {
    const price = annual ? plan.yearlyPrice : plan.monthlyPrice;
    const period = annual ? plan.yearlyPeriod : plan.monthlyPeriod;
    const from = typeof props.gradientFrom === 'string' && props.gradientFrom.trim() ? props.gradientFrom : '#8b5cf6';
    const to = typeof props.gradientTo === 'string' && props.gradientTo.trim() ? props.gradientTo : '#f59e0b';
    const badgeBg = typeof props.badgeBackgroundColor === 'string' && props.badgeBackgroundColor.trim() ? props.badgeBackgroundColor : '#111827';
    const badgeText = typeof props.badgeTextColor === 'string' && props.badgeTextColor.trim() ? props.badgeTextColor : '#ffffff';
    const alignClass = align === 'left' ? 'text-left' : align === 'right' ? 'text-right' : align === 'justify' ? 'text-justify' : 'text-center';
    const justifyClass = align === 'left' ? 'justify-start' : align === 'right' ? 'justify-end' : align === 'justify' ? 'justify-between' : 'justify-center';
    const buttonPosition = props.buttonPosition === 'center' ? 'center' : 'default';
    const iconColor = typeof props.iconColor === 'string' && props.iconColor.trim() ? props.iconColor : '';
    const priceColor = typeof props.priceColor === 'string' && props.priceColor.trim() ? props.priceColor : '';
    const descriptionColor = typeof props.descriptionColor === 'string' && props.descriptionColor.trim() ? props.descriptionColor : '';
    const titleStyle = textStyleFromProps(props, 'title', props.titleColor);
    const priceTypo = typographyStyleFromProps(props, 'price');
    const descriptionTypo = typographyStyleFromProps(props, 'description');

    const wrapperStyle = {
        padding: '2px',
        borderRadius: cardBaseStyle.borderRadius,
        background: `linear-gradient(135deg, ${from}, ${to})`,
    };

    const innerStyle = {
        ...buildCardBackground(props),
        borderRadius: `${Math.max(0, Number(cardBaseStyle.borderRadius) - 2)}px`,
        padding: cardBaseStyle.padding,
    };

    return (
        <div className="max-w-sm mx-auto" style={wrapperStyle}>
            <div style={innerStyle}>
                <div className={alignClass}>
                    <PlanIcon plan={plan} className="mb-4" color={iconColor} />
                    <div className={`flex items-center gap-2 ${justifyClass}`}>
                        <div className="text-xl font-bold text-gray-900" style={titleStyle}>{plan.title}</div>
                        {plan.badge ? <span className="text-xs px-2 py-1 rounded-full" style={{ backgroundColor: badgeBg, color: badgeText }}>{plan.badge}</span> : null}
                    </div>
                    {plan.description ? <div className="mt-2 text-sm text-gray-600" style={{ ...descriptionTypo, color: descriptionColor || undefined }}>{plan.description}</div> : null}
                    <div className={`mt-4 flex items-end ${justifyClass} gap-1`}>
                        <div className="text-4xl font-bold text-gray-900" style={{ ...priceTypo, color: priceColor || undefined }}>{price}</div>
                        <div className="text-sm text-gray-600" style={{ color: priceColor || undefined }}>{period}</div>
                    </div>
                </div>

                {buttonPosition === 'center' ? (
                    <PricingTableButton plan={plan} buttonStyle={buttonStyle} className="mt-5 w-full px-4 py-2" />
                ) : null}

                <FeatureList
                    features={plan.features}
                    iconType={featureIconType}
                    iconUrl={featureIconUrl}
                    featureTextColor={props.featureTextColor}
                    excludedFeatureTextColor={props.excludedFeatureTextColor}
                    featureIconColor={props.featureIconColor}
                    excludedFeatureIconColor={props.excludedFeatureIconColor}
                    featureTypography={typographyStyleFromProps(props, 'feature')}
                    excludedFeatureTypography={typographyStyleFromProps(props, 'excludedFeature')}
                />

                {buttonPosition !== 'center' ? (
                    <PricingTableButton plan={plan} buttonStyle={buttonStyle} className="mt-5 w-full px-4 py-2" />
                ) : null}
            </div>
        </div>
    );
}

function PricingTablePreview({ widget }) {
    const w = widget;
    const props = (w && w.props && typeof w.props === 'object') ? w.props : {};
    const ctx = React.useContext(PricingAnnualContext);
    const hasContext = ctx && typeof ctx.setAnnual === 'function';
    const [localAnnual, setLocalAnnual] = React.useState(false);
    const annual = hasContext ? ctx.annual : localAnnual;
    const setAnnual = hasContext ? ctx.setAnnual : setLocalAnnual;

    const style = STYLES.includes(props.style) ? props.style : 'default';

    const featureIconType = ['check', 'custom'].includes(props.featureIconType)
        ? props.featureIconType
        : (typeof props.featureIconUrl === 'string' && props.featureIconUrl.trim() ? 'custom' : 'check');
    const featureIconUrl = typeof props.featureIconUrl === 'string' ? props.featureIconUrl.trim() : '';

    const align = ['left', 'center', 'right'].includes(props.align) ? props.align : 'center';
    const plan = normalizePlan(getSinglePlan(props));

    const wrapperStyle = {};
    if (Number.isFinite(Number(props.marginTop))) wrapperStyle.marginTop = `${Number(props.marginTop)}px`;
    if (Number.isFinite(Number(props.marginRight))) wrapperStyle.marginRight = `${Number(props.marginRight)}px`;
    if (Number.isFinite(Number(props.marginBottom))) wrapperStyle.marginBottom = `${Number(props.marginBottom)}px`;
    if (Number.isFinite(Number(props.marginLeft))) wrapperStyle.marginLeft = `${Number(props.marginLeft)}px`;
    if (Number.isFinite(Number(props.paddingTop))) wrapperStyle.paddingTop = `${Number(props.paddingTop)}px`;
    if (Number.isFinite(Number(props.paddingRight))) wrapperStyle.paddingRight = `${Number(props.paddingRight)}px`;
    if (Number.isFinite(Number(props.paddingBottom))) wrapperStyle.paddingBottom = `${Number(props.paddingBottom)}px`;
    if (Number.isFinite(Number(props.paddingLeft))) wrapperStyle.paddingLeft = `${Number(props.paddingLeft)}px`;

    const cardBorderWidth = props.cardBorderWidthLinked !== false
        ? `${Math.max(0, Math.round(Number(props.cardBorderWidth) || 0))}px`
        : `${Math.max(0, Math.round(Number(props.cardBorderWidthTop) || 0))}px ${Math.max(0, Math.round(Number(props.cardBorderWidthRight) || 0))}px ${Math.max(0, Math.round(Number(props.cardBorderWidthBottom) || 0))}px ${Math.max(0, Math.round(Number(props.cardBorderWidthLeft) || 0))}px`;

    const cardBorderRadius = props.cardBorderRadiusLinked !== false
        ? `${Math.max(0, Math.round(Number(props.cardBorderRadius) || 0))}px`
        : `${Math.max(0, Math.round(Number(props.cardBorderRadiusTopLeft) || 0))}px ${Math.max(0, Math.round(Number(props.cardBorderRadiusTopRight) || 0))}px ${Math.max(0, Math.round(Number(props.cardBorderRadiusBottomRight) || 0))}px ${Math.max(0, Math.round(Number(props.cardBorderRadiusBottomLeft) || 0))}px`;

    const cardPadding = props.cardPaddingLinked !== false
        ? `${Math.max(0, Math.round(Number(props.cardPadding) || 0))}px`
        : `${Math.max(0, Math.round(Number(props.cardPaddingTop) || 0))}px ${Math.max(0, Math.round(Number(props.cardPaddingRight) || 0))}px ${Math.max(0, Math.round(Number(props.cardPaddingBottom) || 0))}px ${Math.max(0, Math.round(Number(props.cardPaddingLeft) || 0))}px`;

    const cardBaseStyle = {
        ...buildCardBackground(props),
        borderStyle: 'solid',
        borderColor: typeof props.cardBorderColor === 'string' && props.cardBorderColor.trim() ? props.cardBorderColor : '#e5e7eb',
        borderWidth: cardBorderWidth,
        borderRadius: cardBorderRadius,
        padding: cardPadding,
    };

    const cardBfxClass = borderEffectClassName(props, {
        effect: 'cardBorderEffect',
        color: 'cardBorderEffectColor',
        color2: 'cardBorderEffectColor2',
        speed: 'cardBorderEffectSpeed',
        thickness: 'cardBorderEffectThickness',
    });
    const cardBfxVars = borderEffectStyleVars(props, {
        effect: 'cardBorderEffect',
        color: 'cardBorderEffectColor',
        color2: 'cardBorderEffectColor2',
        speed: 'cardBorderEffectSpeed',
        thickness: 'cardBorderEffectThickness',
    });
    if (cardBfxClass) {
        Object.assign(cardBaseStyle, cardBfxVars);
    }

    const featuredBackgroundColor = typeof props.featuredBackgroundColor === 'string' && props.featuredBackgroundColor.trim() ? props.featuredBackgroundColor : '';
    const featuredCardStyle = {
        ...cardBaseStyle,
        borderColor:
            typeof props.featuredBorderColor === 'string' && props.featuredBorderColor.trim()
                ? props.featuredBorderColor
                : cardBaseStyle.borderColor,
    };
    if (featuredBackgroundColor) {
        delete featuredCardStyle.backgroundImage;
        delete featuredCardStyle.backgroundSize;
        delete featuredCardStyle.backgroundPosition;
        delete featuredCardStyle.backgroundRepeat;
        featuredCardStyle.backgroundColor = featuredBackgroundColor;
    }

    const buttonStyle = {
        ...typographyStyleFromProps(props, 'button'),
        backgroundColor: typeof props.buttonBackgroundColor === 'string' && props.buttonBackgroundColor.trim() ? props.buttonBackgroundColor : '#2563eb',
        color: typeof props.buttonTextColor === 'string' && props.buttonTextColor.trim() ? props.buttonTextColor : '#ffffff',
        borderRadius: Number.isFinite(Number(props.buttonBorderRadius)) ? `${Math.max(0, Math.round(Number(props.buttonBorderRadius)))}px` : '10px',
    };

    const commonProps = {
        props,
        plan,
        annual,
        featureIconType,
        featureIconUrl,
        cardBaseStyle,
        featuredCardStyle,
        cardBfxClass,
        buttonStyle,
        align,
    };

    const renderers = {
        default: DefaultPreview,
        classic: ClassicPreview,
        dark: DarkPreview,
        header: HeaderPreview,
        minimal: MinimalPreview,
        split: SplitPreview,
        gradient: GradientPreview,
    };

    const Renderer = renderers[style] || DefaultPreview;

    return (
        <div style={Object.keys(wrapperStyle).length ? wrapperStyle : undefined}>
            {!hasContext ? <Toggle annual={annual} setAnnual={setAnnual} /> : null}
            <Renderer {...commonProps} />
        </div>
    );
}

export function renderPricingTablePreview(widget) {
    return <PricingTablePreview widget={widget} />;
}

const positionOptions = [
    { value: 'top left', label: 'Top Left' },
    { value: 'top center', label: 'Top Center' },
    { value: 'top right', label: 'Top Right' },
    { value: 'center left', label: 'Center Left' },
    { value: 'center center', label: 'Center' },
    { value: 'center right', label: 'Center Right' },
    { value: 'bottom left', label: 'Bottom Left' },
    { value: 'bottom center', label: 'Bottom Center' },
    { value: 'bottom right', label: 'Bottom Right' },
];

const pricingTableContentSchema = [
    { type: CONTROL.CONTROL_SELECT, name: 'style', label: 'Style', bind: 'props.style', options: [
        { value: 'default', label: 'Default Card' },
        { value: 'classic', label: 'Classic Icon' },
        { value: 'dark', label: 'Dark Card' },
        { value: 'header', label: 'Header Bar' },
        { value: 'minimal', label: 'Minimal Table' },
        { value: 'split', label: 'Split Layout' },
        { value: 'gradient', label: 'Gradient Border' },
    ]},
    { type: CONTROL.CONTROL_SELECT, name: 'align', label: 'Align', bind: 'props.align', options: [
        { value: 'left', label: 'Left' },
        { value: 'center', label: 'Center' },
        { value: 'right', label: 'Right' },
    ]},
    { type: CONTROL.CONTROL_SELECT, name: 'featureIconType', label: 'Feature Icon', bind: 'props.featureIconType', options: [
        { value: 'check', label: 'Check' },
        { value: 'custom', label: 'Custom Upload' },
    ]},
    { type: CONTROL.CONTROL_MEDIA, name: 'featureIconUrl', label: 'Feature Icon URL', bind: 'props.featureIconUrl', condition: [{ name: 'featureIconType', value: 'custom' }] },
    { type: CONTROL.CONTROL_GROUP, kind: 'object', label: 'Plan', bind: 'props.plan', schema: [
        { type: CONTROL.CONTROL_TEXT, name: 'title', label: 'Title', bind: 'title' },
        { type: CONTROL.CONTROL_TEXT, name: 'badge', label: 'Badge', bind: 'badge' },
        { type: CONTROL.CONTROL_TEXT, name: 'icon', label: 'Icon', bind: 'icon' },
        { type: CONTROL.CONTROL_TEXT, name: 'discount', label: 'Discount Badge', bind: 'discount' },
        { type: CONTROL.CONTROL_TEXT, name: 'monthlyPrice', label: 'Monthly Price', bind: 'monthlyPrice' },
        { type: CONTROL.CONTROL_TEXT, name: 'monthlyPeriod', label: 'Monthly Period', bind: 'monthlyPeriod' },
        { type: CONTROL.CONTROL_TEXT, name: 'yearlyPrice', label: 'Yearly Price', bind: 'yearlyPrice' },
        { type: CONTROL.CONTROL_TEXT, name: 'yearlyPeriod', label: 'Yearly Period', bind: 'yearlyPeriod' },
        { type: CONTROL.CONTROL_TEXTAREA, name: 'description', label: 'Description', bind: 'description' },
        { type: CONTROL.CONTROL_GROUP, kind: 'repeater', label: 'Features', bind: 'features', itemSchema: {
            itemLabel: 'Feature', defaultItem: () => 'Feature', fields: [{ name: 'text', label: 'Feature (prefix ! or - to exclude)' }],
        }},
        { type: CONTROL.CONTROL_TEXT, name: 'featuresTitle', label: 'Features Title', bind: 'featuresTitle' },
        { type: CONTROL.CONTROL_TEXT, name: 'buttonText', label: 'Button Text', bind: 'buttonText' },
        { type: CONTROL.CONTROL_TEXT, name: 'buttonUrl', label: 'Button URL', bind: 'buttonUrl' },
        { type: CONTROL.CONTROL_SELECT, name: 'buttonTarget', label: 'Button Target', bind: 'buttonTarget', options: [
            { value: '_self', label: 'Same Tab' },
            { value: '_blank', label: 'New Tab' },
        ]},
        { type: CONTROL.CONTROL_SWITCHER, name: 'featured', label: 'Featured', bind: 'featured' },
    ]},
];

const pricingTableStyleSchema = [
    { type: CONTROL.CONTROL_SELECT, name: 'buttonPosition', label: 'Button Position', bind: 'props.buttonPosition', options: [
        { value: 'default', label: 'Default (Button last)' },
        { value: 'center', label: 'Button Center (before features)' },
    ]},
    { type: CONTROL.CONTROL_SELECT, name: 'cardBackgroundType', label: 'Card Background', bind: 'props.cardBackgroundType', options: [
        { value: 'color', label: 'Color' },
        { value: 'gradient', label: 'Gradient' },
        { value: 'image', label: 'Image' },
    ]},
    { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'cardBackgroundColor', label: 'Card Background Color', bind: 'props.cardBackgroundColor', condition: [{ name: 'cardBackgroundType', value: 'color' }] },
    { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'cardBackgroundGradientFrom', label: 'Gradient From', bind: 'props.cardBackgroundGradientFrom', condition: [{ name: 'cardBackgroundType', value: 'gradient' }] },
    { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'cardBackgroundGradientTo', label: 'Gradient To', bind: 'props.cardBackgroundGradientTo', condition: [{ name: 'cardBackgroundType', value: 'gradient' }] },
    { type: CONTROL.CONTROL_SELECT, name: 'cardBackgroundGradientType', label: 'Gradient Type', bind: 'props.cardBackgroundGradientType', options: [
        { value: 'linear', label: 'Linear' },
        { value: 'radial', label: 'Radial' },
    ], condition: [{ name: 'cardBackgroundType', value: 'gradient' }] },
    { type: CONTROL.CONTROL_SELECT, name: 'cardBackgroundGradientPosition', label: 'Gradient Position', bind: 'props.cardBackgroundGradientPosition', options: positionOptions, condition: [{ name: 'cardBackgroundType', value: 'gradient' }, { name: 'cardBackgroundGradientType', value: 'radial' }] },
    { type: CONTROL.CONTROL_MEDIA, name: 'cardBackgroundImageUrl', label: 'Card Background Image', bind: 'props.cardBackgroundImageUrl', condition: [{ name: 'cardBackgroundType', value: 'image' }] },
    { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'cardBorderColor', label: 'Card Border Color', bind: 'props.cardBorderColor' },
    { type: CONTROL.CONTROL_TEXT, name: 'cardBorderWidth', label: 'Card Border Width (px)', bind: 'props.cardBorderWidth' },
    { type: CONTROL.CONTROL_TEXT, name: 'cardBorderRadius', label: 'Card Border Radius (px)', bind: 'props.cardBorderRadius' },
    { type: CONTROL.CONTROL_TEXT, name: 'cardPadding', label: 'Card Padding (px)', bind: 'props.cardPadding' },
    { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'featuredBackgroundColor', label: 'Featured Background', bind: 'props.featuredBackgroundColor' },
    { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'featuredBorderColor', label: 'Featured Border', bind: 'props.featuredBorderColor' },
    { type: CONTROL.CONTROL_GROUP, kind: 'border_effect', label: 'Animated Border', bind: {
        effect: 'props.cardBorderEffect', color: 'props.cardBorderEffectColor', color2: 'props.cardBorderEffectColor2', speed: 'props.cardBorderEffectSpeed', thickness: 'props.cardBorderEffectThickness',
    }},
    { type: CONTROL.CONTROL_HEADING, label: 'Button' },
    { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'buttonBackgroundColor', label: 'Button Background', bind: 'props.buttonBackgroundColor' },
    { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'buttonTextColor', label: 'Button Text', bind: 'props.buttonTextColor' },
    { type: CONTROL.CONTROL_TEXT, name: 'buttonBorderRadius', label: 'Button Radius (px)', bind: 'props.buttonBorderRadius' },
    { type: CONTROL.CONTROL_GROUP, kind: 'spacing', label: 'Spacing', bind: {
        padding: { linked: 'props.paddingLinked', t: 'props.paddingTop', r: 'props.paddingRight', b: 'props.paddingBottom', l: 'props.paddingLeft' },
        margin: { linked: 'props.marginLinked', t: 'props.marginTop', r: 'props.marginRight', b: 'props.marginBottom', l: 'props.marginLeft' },
    }},
];

const pricingTableSchema = {
    content: pricingTableContentSchema,
    style: pricingTableStyleSchema,
    advanced: [],
};

registerElement({
    type: 'pricing_table',
    label: 'Pricing Table',
    group: 'Basic',
    defaultProps: {
        style: 'default',
        align: 'center',
        featureIconType: 'check',
        featureIconUrl: '',
        plan: {
            title: 'Plan',
            description: '',
            monthlyPrice: '$0',
            monthlyPeriod: '/mo',
            yearlyPrice: '$0',
            yearlyPeriod: '/yr',
            features: [],
            buttonText: 'Get started',
            buttonUrl: '#',
            buttonTarget: '_self',
            featured: false,
            badge: '',
            icon: '',
            featuresTitle: '',
            discount: '',
        },
        cardBackgroundType: 'color',
        cardBackgroundColor: '#ffffff',
        cardBorderColor: '#e5e7eb',
        cardBorderWidth: 1,
        cardBorderRadius: 14,
        cardPadding: 20,
        buttonBackgroundColor: '#2563eb',
        buttonTextColor: '#ffffff',
        buttonBorderRadius: 10,
        buttonPosition: 'default',
    },
    render: renderPricingTablePreview,
    schema: pricingTableSchema,
});
