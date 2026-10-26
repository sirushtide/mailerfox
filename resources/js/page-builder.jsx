import React, { useEffect, useLayoutEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { uid } from './page-builder/lib/uid.js';
import { mergeClassNames } from './page-builder/lib/classnames.js';
import { cssLengthToCss } from './page-builder/lib/typographyUtils.js';
import { inferLinked4, toFiniteNumberOrUndefined } from './page-builder/lib/boxModel.js';
import { Button, Input, Range, Select } from './page-builder/ui/primitives.jsx';
import { SliderInput } from './page-builder/controls/SliderInput.jsx';
import { resolveUnit, parseUnit } from './page-builder/controls/bind.js';
import { svgWithWidth } from './page-builder/lib/svg.js';
import { ColorInput } from './page-builder/ui/color.jsx';
import { Popover, PopoverTriggerButton } from './page-builder/ui/popover.jsx';
import { TypographyPopover, loadGoogleFont } from './page-builder/reusable/typography.jsx';
import { ShadowPopover } from './page-builder/reusable/shadow.jsx';
import { BorderControls, FourSideLinkedControl } from './page-builder/reusable/boxControls.jsx';
import { BackgroundControls } from './page-builder/reusable/backgroundControls.jsx';
import {
    SectionBackgroundPresetInspector,
    SectionBackgroundPresetOverlay,
    getPresetAnimationClass,
    getPresetCanvasStyle,
    getPresetKeyframes,
    getPresetOverlayMarkup,
    getPresetByKey,
} from './page-builder/reusable/sectionBackgroundPresets.jsx';
import { SpacingControls } from './page-builder/reusable/spacingControls.jsx';
import { DimensionsControls } from './page-builder/reusable/dimensionsControls.jsx';
import { ImageUrlUploadControl, MultiImageUploadControl, UploadButton } from './page-builder/reusable/imageUploadControl.jsx';
import { renderElementPreview } from './page-builder/elements/index.js';
import { CAROUSEL_PROP_DEFAULTS, CAROUSEL_CONTENT_KEYS } from './page-builder/reusable/carousel/config.js';
import { setBuilderViewport, pickResponsive } from './page-builder/reusable/builderViewport.js';
import { getElement, getElementDefaults, listPalette } from './page-builder/elementRegistry.js';
import { ControlsRenderer } from './page-builder/controls/renderer.jsx';
import { BuilderModeContext, BuilderViewportContext } from './page-builder/controls/builderMode.js';
import { StyleAccordionItem } from './page-builder/controls/accordion.jsx';
import { DeviceSwitcher, DeviceIcons } from './page-builder/controls/base/ui.jsx';
import { useGlobals } from './page-builder/globals/globals.js';
import { ColorPresetsProvider } from './page-builder/ui/color.jsx';
import { sectionSchema } from './page-builder/schemas/section.js';
import { columnSchema, gridColumnSchema, gridContentSchema } from './page-builder/schemas/column.js';
import { pageSchema, headerSettingsControls } from './page-builder/schemas/page.js';
import {
    MOBILE_MENU_DEFAULTS,
    mobileMenuContentSchema,
    mobileMenuStyleSchema,
    MobileMenuCanvas,
} from './page-builder/elements/mobileMenu.jsx';
import { SchemaInspector } from './page-builder/SchemaInspector.jsx';
import { GlobalOverridesPanel } from './page-builder/GlobalOverridesPanel.jsx';
import { GlobalSettingsPanel } from './page-builder/GlobalSettingsPanel.jsx';
import { createPageBuilderRuntime } from './page-builder/main/init.js';
import { htmlToSections } from './page-builder/importHtml.js';
import {
    ButtonGroupStyleInspector,
    ButtonGroupContentInspector,
} from './page-builder/elements/buttonGroupInspectors.jsx';
import { ButtonContentInspector, ButtonStyleInspector } from './page-builder/elements/buttonInspectors.jsx';
import { NewsletterFormContentInspector, NewsletterFormStyleInspector } from './page-builder/elements/newsletterFormInspectors.jsx';
import { SocialIconsContentInspector, SocialIconsStyleInspector } from './page-builder/elements/socialIconsInspectors.jsx';
import { PricingSectionProvider, sectionHasStandalonePricingTable, gridHasPricingTable, GridPricingScope } from './page-builder/elements/pricingTable.jsx';
import { PricingSectionTabsInspector, PricingTableContentInspector, PricingTableStyleInspector } from './page-builder/elements/pricingTableInspectors.jsx';
import { MarqueeContentInspector, MarqueeStyleInspector } from './page-builder/elements/marqueeInspectors.jsx';
import { SocialProofContentInspector, SocialProofStyleInspector } from './page-builder/elements/socialProofInspectors.jsx';
import { TabsStyleInspector, TabsContentInspector } from './page-builder/elements/tabsInspectors.jsx';
import { NavMenuStyleInspector, NavMenuContentInspector } from './page-builder/elements/navMenuInspectors.jsx';
import { TextTimelineContentInspector } from './page-builder/elements/textTimelineInspectors.jsx';
import { AiBuilderPanel } from './page-builder/AiBuilderPanel.jsx';
import { useAiBuilder } from './page-builder/useAiBuilder.js';

function deepClone(val) {
    if (Array.isArray(val)) {
        return val.map((v) => deepClone(v));
    }

    if (val && typeof val === 'object') {
        const out = {};
        for (const k in val) {
            out[k] = deepClone(val[k]);
        }
        return out;
    }
    return val;
}

function stableStringify(val) {
    if (val === null || val === undefined) return String(val);

    const t = typeof val;
    if (t === 'string') return JSON.stringify(val);
    if (t === 'number' || t === 'boolean') return String(val);

    if (Array.isArray(val)) {
        return `[${val.map((v) => stableStringify(v)).join(',')}]`;
    }

    if (t === 'object') {
        const keys = Object.keys(val).sort();
        const parts = keys.map((k) => `${JSON.stringify(k)}:${stableStringify(val[k])}`);
        return `{${parts.join(',')}}`;
    }

    return JSON.stringify(String(val));
}

function humanizeWidgetType(type) {
    if (!type || typeof type !== 'string') return '';
    return type
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (l) => l.toUpperCase());
}

function ElementPaletteIcon({ name, className }) {
    const common = {
        className: className || 'h-5 w-5 text-gray-700',
        viewBox: '0 0 24 24',
        fill: 'none',
        xmlns: 'http://www.w3.org/2000/svg',
        'aria-hidden': true,
    };

    if (name === 'container') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-box-icon lucide-box`}><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
        );
    }

    if (name === 'grid') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-layout-panel-left-icon lucide-layout-panel-left`}><rect width="7" height="18" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/></svg>
        );
    }

    if (name === 'mobile_menu') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-smartphone-icon lucide-smartphone`}><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>
        );
    }

    if (name === 'heading') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-case-sensitive-icon lucide-case-sensitive`}><path d="m2 16 4.039-9.69a.5.5 0 0 1 .923 0L11 16"/><path d="M22 9v7"/><path d="M3.304 13h6.392"/><circle cx="18.5" cy="12.5" r="3.5"/></svg>
        );
    }

    if (name === 'rich_text') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-text-initial-icon lucide-text-initial`}><path d="M15 5h6"/><path d="M15 12h6"/><path d="M3 19h18"/><path d="m3 12 3.553-7.724a.5.5 0 0 1 .894 0L11 12"/><path d="M3.92 10h6.16"/></svg>
        );
    }

    if (name === 'image') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-image-up-icon lucide-image-up`}><path d="M10.3 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10l-3.1-3.1a2 2 0 0 0-2.814.014L6 21"/><path d="m14 19.5 3-3 3 3"/><path d="M17 22v-5.5"/><circle cx="9" cy="9" r="2"/></svg>
        );
    }

    if (name === 'button') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-square-mouse-pointer-icon lucide-square-mouse-pointer`}><path d="M12.034 12.681a.498.498 0 0 1 .647-.647l9 3.5a.5.5 0 0 1-.033.943l-3.444 1.068a1 1 0 0 0-.66.66l-1.067 3.443a.5.5 0 0 1-.943.033z"/><path d="M21 11V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h6"/></svg>
        );
    }

    if (name === 'button_group') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-columns-3-icon lucide-columns-3`}><rect width="7" height="18" x="3" y="3" rx="1"/><rect width="7" height="18" x="14" y="3" rx="1"/></svg>
        );
    }

    if (name === 'badge') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-badge-icon lucide-badge`}><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/></svg>
        );
    }

    if (name === 'icon') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-clock4-icon lucide-clock-4`}><path d="M12 6v6l4 2"/><circle cx="12" cy="12" r="10"/></svg>
        );
    }

    if (name === 'pricing_table') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-between-horizontal-end-icon lucide-between-horizontal-end`}><rect width="13" height="7" x="3" y="3" rx="1"/><path d="m22 15-3-3 3-3"/><rect width="13" height="7" x="3" y="14" rx="1"/></svg>
        );
    }

    if (name === 'faqs') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-list-collapse-icon lucide-list-collapse`}><path d="M10 5h11"/><path d="M10 12h11"/><path d="M10 19h11"/><path d="m3 10 3-3-3-3"/><path d="m3 20 3-3-3-3"/></svg>
        );
    }

    if (name === 'tabs') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-folder-kanban-icon lucide-folder-kanban`}><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/><path d="M8 10v4"/><path d="M12 10v2"/><path d="M16 10v6"/></svg>
        );
    }

    if (name === 'slider') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-settings2-icon lucide-settings-2`}><path d="M14 17H5"/><path d="M19 7h-9"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></svg>
        );
    }

    if (name === 'marquee') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-gallery-horizontal-end-icon lucide-gallery-horizontal-end`}><path d="M2 7v10"/><path d="M6 5v14"/><rect width="12" height="18" x="10" y="3" rx="2"/></svg>
        );
    }

    if (name === 'nav_menu') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-menu-icon lucide-menu`}><path d="M4 5h16"/><path d="M4 12h16"/><path d="M4 19h10"/></svg>
        );
    }

    if (name === 'link') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-link-icon lucide-link`}><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        );
    }

    if (name === 'social_icons') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-share-2-icon lucide-share-2`}><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.59 13.51 15.42 17.49"/><path d="M15.41 6.51 8.59 10.49"/></svg>
        );
    }

    if (name === 'social_proof') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-badge-check-icon lucide-badge-check`}><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/></svg>
        );
    }

    if (name === 'newsletter_form') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-newspaper preview-icon`}><path d="M15 18h-5"/><path d="M18 14h-8"/><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-4 0v-9a2 2 0 0 1 2-2h2"/><rect width="8" height="4" x="10" y="6" rx="1"/></svg>
        );
    }

    if (name === 'register_form') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-user-plus preview-icon`}><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
        );
    }

    if (name === 'login_form') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-user-round-arrow-left preview-icon`}><path d="m19 16-3 3"/><path d="M2 21a8 8 0 0 1 12.664-6.5"/><path d="M22 19h-6l3 3"/><circle cx="10" cy="8" r="5"/></svg>
        );
    }

    if (name === 'copyright') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-copyright-icon lucide-copyright`}><circle cx="12" cy="12" r="10"/><path d="M14.83 14.83a4 4 0 1 1 0-5.66"/></svg>
        );
    }

    if (name === 'divider') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-minus-icon lucide-minus`}><path d="M5 12h14"/></svg>
        );
    }

    if (name === 'spacer') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-move-vertical-icon lucide-move-vertical`}><path d="M12 2v20"/><path d="m8 6 4-4 4 4"/><path d="m8 18 4 4 4-4"/></svg>
        );
    }

    if (name === 'logo') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-image-icon lucide-image`}><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
        );
    }

    if (name === 'rating') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-star-icon lucide-star`}><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        );
    }

    if (name === 'testimonials') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-quote`}><path d="M16 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 1 2 2v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z"/><path d="M5 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 1 2 2v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z"/></svg>
        );
    }

    if (name === 'testimonial') {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`${common.className} lucide lucide-message-square-quote`}><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 12a2 2 0 0 0 2-2V8H8"/><path d="M14 12a2 2 0 0 0 2-2V8h-2"/></svg>
        );
    }

    return (
        <svg {...common}>
            <path d="M12 6v12M6 12h12" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
        </svg>
    );
}

function ImageStyleInspector({ widget, onChange }) {
    const props = widget.props || {};

    const paddingKeys = {
        linked: 'paddingLinked',
        t: 'paddingTop',
        r: 'paddingRight',
        b: 'paddingBottom',
        l: 'paddingLeft',
        legacyX: 'paddingX',
        legacyY: 'paddingY',
    };

    const marginKeys = {
        linked: 'marginLinked',
        t: 'marginTop',
        r: 'marginRight',
        b: 'marginBottom',
        l: 'marginLeft',
    };

    const borderRadiusKeys = {
        linked: 'borderRadiusLinked',
        t: 'borderRadiusTop',
        r: 'borderRadiusRight',
        b: 'borderRadiusBottom',
        l: 'borderRadiusLeft',
        legacy: 'borderRadius',
    };

    const borderWidthKeys = {
        linked: 'borderWidthLinked',
        t: 'borderWidthTop',
        r: 'borderWidthRight',
        b: 'borderWidthBottom',
        l: 'borderWidthLeft',
        legacy: 'borderWidth',
    };

    return (
        <div className="space-y-3">
            <DimensionsControls
                value={props}
                onChange={(nextProps) => onChange({ props: nextProps })}
                widthLabel="Width (px)"
                heightLabel="Height (px)"
                widthPlaceholder="Auto"
                heightPlaceholder="Auto"
                useSliders={true}
                widthMin={0}
                widthMax={1200}
                widthStep={1}
                heightMin={0}
                heightMax={800}
                heightStep={1}
            />

            <div className="space-y-3">
                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Border</div>
                <BorderControls
                    value={props}
                    onChange={(nextProps) => onChange({ props: nextProps })}
                    widthKeys={borderWidthKeys}
                    widthFallbackValue={props.borderWidthTop ?? props.borderWidth}
                />
                <FourSideLinkedControl
                    label="Border Radius"
                    value={props}
                    keys={borderRadiusKeys}
                    fallbackValue={props.borderRadiusTop ?? props.borderRadius}
                    onChange={(nextProps) => onChange({ props: nextProps })}
                />
            </div>

            <div className="space-y-3">
                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Spacing</div>
                <FourSideLinkedControl
                    label="Padding"
                    value={props}
                    keys={paddingKeys}
                    fallbackValue={props.paddingTop ?? props.paddingY ?? props.paddingX}
                    onChange={(nextProps) => onChange({ props: nextProps })}
                />
                <FourSideLinkedControl
                    label="Margin"
                    value={props}
                    keys={marginKeys}
                    fallbackValue={props.marginTop ?? props.marginRight ?? props.marginBottom ?? props.marginLeft}
                    onChange={(nextProps) => onChange({ props: nextProps })}
                />
            </div>
        </div>
    );
}

function computeWrapPerRow(style) {
    const s = style && typeof style === 'object' ? style : {};
    const n = Number(pickResponsive(s.columnsPerRow, s.columnsPerRowTablet, s.columnsPerRowMobile));
    if (!Number.isFinite(n) || n < 1) return null;
    const gapX = Math.max(0, Number.isFinite(Number(s.gapX)) ? Number(s.gapX) : (Number.isFinite(Number(s.gap)) ? Number(s.gap) : 0));
    const gapY = Math.max(0, Number.isFinite(Number(s.gapY)) ? Number(s.gapY) : (Number.isFinite(Number(s.gap)) ? Number(s.gap) : 0));
    return { n: Math.max(1, Math.min(6, Math.round(n))), gapX, gapY };
}

function MotionInspector({ value, onChange, targetType, advanced, onAdvancedChange, backgroundPreset = null, pricingTabs = null, headerSettings = null, hideMotion = false }) {
    const style = value && typeof value === 'object' ? value : {};
    const setStyle = (patch) => onChange(patch);
    const adv = normalizeAdvanced(advanced);
    const setAdvanced = (patch) => onAdvancedChange({ ...adv, ...patch });
    const viewportCtx = React.useContext(BuilderViewportContext);
    const device = viewportCtx && typeof viewportCtx.viewport === 'string' ? viewportCtx.viewport : 'desktop';
    const deviceSuffix = device === 'tablet' ? 'Tablet' : device === 'mobile' ? 'Mobile' : '';
    const displayKey = `display${deviceSuffix}`;
    const isTextTarget = targetType === 'heading' || targetType === 'rich_text';
    const isContainerTarget = targetType === 'container' || targetType === 'grid';
    const [customAnimTab, setCustomAnimTab] = useState('from');

    const paddingKeys = {
        linked: 'paddingLinked',
        t: 'paddingTop',
        r: 'paddingRight',
        b: 'paddingBottom',
        l: 'paddingLeft',
        legacyX: 'paddingX',
        legacyY: 'paddingY',
    };

    const marginKeys = {
        linked: 'marginLinked',
        t: 'marginTop',
        r: 'marginRight',
        b: 'marginBottom',
        l: 'marginLeft',
    };

    const borderWidthKeys = {
        linked: 'borderWidthLinked',
        t: 'borderWidthTop',
        r: 'borderWidthRight',
        b: 'borderWidthBottom',
        l: 'borderWidthLeft',
        legacy: 'borderWidth',
    };

    const borderRadiusKeys = {
        linked: 'borderRadiusLinked',
        t: 'borderRadiusTop',
        r: 'borderRadiusRight',
        b: 'borderRadiusBottom',
        l: 'borderRadiusLeft',
        legacy: 'borderRadius',
    };

    const toValue = {
        tx: Number.isFinite(Number(style.animationToTranslateX)) ? Number(style.animationToTranslateX) : Number(style.transformTranslateX ?? 0),
        ty: Number.isFinite(Number(style.animationToTranslateY)) ? Number(style.animationToTranslateY) : Number(style.transformTranslateY ?? 0),
        rot: Number.isFinite(Number(style.animationToRotate)) ? Number(style.animationToRotate) : Number(style.transformRotate ?? 0),
        scale: Number.isFinite(Number(style.animationToScale)) ? Number(style.animationToScale) : Number(style.transformScale ?? 1),
    };

    return (
        <div>
            {headerSettings}
            <StyleAccordionItem title="General" defaultOpen={true}>
                <div className="space-y-4 px-4">
                    <div className="flex items-center justify-between gap-3">
                        <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Position</label>
                        <div className="w-36">
                            <Select
                                value={adv.position || ''}
                                onChange={(v) => setAdvanced({ position: v })}
                                options={[
                                    { value: '', label: 'Default' },
                                    { value: 'static', label: 'Static' },
                                    { value: 'relative', label: 'Relative' },
                                    { value: 'absolute', label: 'Absolute' },
                                    { value: 'fixed', label: 'Fixed' },
                                    { value: 'sticky', label: 'Sticky' },
                                ]}
                            />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <div className="flex items-center justify-between gap-3">
                            <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Display</label>
                            <DeviceIcons />
                        </div>
                        <Select
                            value={adv[displayKey] || ''}
                            onChange={(v) => setAdvanced({ [displayKey]: v })}
                            options={[
                                { value: '', label: 'Default' },
                                { value: 'block', label: 'Block' },
                                { value: 'inline', label: 'Inline' },
                                { value: 'inline-block', label: 'Inline Block' },
                                { value: 'flex', label: 'Flex' },
                                { value: 'inline-flex', label: 'Inline Flex' },
                                { value: 'grid', label: 'Grid' },
                                { value: 'inline-grid', label: 'Inline Grid' },
                                { value: 'none', label: 'None' },
                            ]}
                        />
                    </div>

                    {['flex', 'inline-flex', 'grid', 'inline-grid'].includes(adv.display) ? (
                        <div className="flex items-center justify-between gap-3">
                            <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Align Items</label>
                            <div className="w-36">
                                <Select
                                    value={adv.alignItems || ''}
                                    onChange={(v) => setAdvanced({ alignItems: v })}
                                    options={[
                                        { value: '', label: 'Default' },
                                        { value: 'stretch', label: 'Stretch' },
                                        { value: 'flex-start', label: 'Flex Start' },
                                        { value: 'center', label: 'Center' },
                                        { value: 'flex-end', label: 'Flex End' },
                                        { value: 'baseline', label: 'Baseline' },
                                        { value: 'start', label: 'Start' },
                                        { value: 'end', label: 'End' },
                                    ]}
                                />
                            </div>
                        </div>
                    ) : null}

                    {['flex', 'inline-flex', 'grid', 'inline-grid'].includes(adv.display) ? (
                        <div className="flex items-center justify-between gap-3">
                            <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Justify Content</label>
                            <div className="w-36">
                                <Select
                                    value={adv.justifyContent || ''}
                                    onChange={(v) => setAdvanced({ justifyContent: v })}
                                    options={[
                                        { value: '', label: 'Default' },
                                        { value: 'flex-start', label: 'Flex Start' },
                                        { value: 'center', label: 'Center' },
                                        { value: 'flex-end', label: 'Flex End' },
                                        { value: 'space-between', label: 'Space Between' },
                                        { value: 'space-around', label: 'Space Around' },
                                        { value: 'space-evenly', label: 'Space Evenly' },
                                        { value: 'start', label: 'Start' },
                                        { value: 'end', label: 'End' },
                                        { value: 'left', label: 'Left' },
                                        { value: 'right', label: 'Right' },
                                    ]}
                                />
                            </div>
                        </div>
                    ) : null}

                    <div className="flex items-center justify-between gap-3">
                        <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Text align</label>
                        <div className="w-36">
                            <Select
                                value={adv.textAlign || ''}
                                onChange={(v) => setAdvanced({ textAlign: v })}
                                options={[
                                    { value: '', label: 'Default' },
                                    { value: 'left', label: 'Left' },
                                    { value: 'center', label: 'Center' },
                                    { value: 'right', label: 'Right' },
                                    { value: 'justify', label: 'Justify' },
                                ]}
                            />
                        </div>
                    </div>

                    <div className="flex items-center justify-between gap-3">
                        <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Overflow X</label>
                        <div className="w-36">
                            <Select
                                value={adv.overflowX || ''}
                                onChange={(v) => setAdvanced({ overflowX: v })}
                                options={[
                                    { value: '', label: 'Default' },
                                    { value: 'visible', label: 'Visible' },
                                    { value: 'hidden', label: 'Hidden' },
                                    { value: 'scroll', label: 'Scroll' },
                                    { value: 'auto', label: 'Auto' },
                                    { value: 'clip', label: 'Clip' },
                                ]}
                            />
                        </div>
                    </div>

                    <div className="flex items-center justify-between gap-3">
                        <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Overflow Y</label>
                        <div className="w-36">
                            <Select
                                value={adv.overflowY || ''}
                                onChange={(v) => setAdvanced({ overflowY: v })}
                                options={[
                                    { value: '', label: 'Default' },
                                    { value: 'visible', label: 'Visible' },
                                    { value: 'hidden', label: 'Hidden' },
                                    { value: 'scroll', label: 'Scroll' },
                                    { value: 'auto', label: 'Auto' },
                                    { value: 'clip', label: 'Clip' },
                                ]}
                            />
                        </div>
                    </div>

                    {adv.position === 'absolute' ? (
                        <div className="space-y-4">
                            <div className="flex items-center justify-between gap-3">
                                <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Horizontal Orientation</label>
                                <div className="w-36">
                                    <div className="inline-flex rounded-md border border-gray-200 dark:border-gray-700 overflow-hidden">
                                        <button
                                            type="button"
                                            onClick={() => setAdvanced({ absXSide: 'left' })}
                                            className={`px-3 py-2 text-xs font-semibold ${
                                                adv.absXSide === 'left'
                                                    ? 'bg-blue-600 text-white'
                                                    : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200'
                                            }`}
                                        >
                                            Left
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => setAdvanced({ absXSide: 'right' })}
                                            className={`px-3 py-2 text-xs font-semibold border-l border-gray-200 dark:border-gray-700 ${
                                                adv.absXSide === 'right'
                                                    ? 'bg-blue-600 text-white'
                                                    : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200'
                                            }`}
                                        >
                                            Right
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div className="flex items-center justify-between gap-3">
                                <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Horizontal Offset (px)</label>
                                <div className="w-44">
                                    <SliderInput
                                        value={adv.absXOffset === null ? '' : adv.absXOffset}
                                        units={['px']}
                                        defaultUnit="px"
                                        min={0}
                                        max={200}
                                        step={1}
                                        onChange={(v) => {
                                            if (v === '' || v === null || v === undefined) {
                                                setAdvanced({ absXOffset: null });
                                                return;
                                            }
                                            if (v && typeof v === 'object') {
                                                const n = Number(v.value);
                                                setAdvanced({ absXOffset: Number.isFinite(n) && v.value !== '' ? Math.max(0, Math.min(9999, Math.round(n))) : null });
                                                return;
                                            }
                                            const n = Number(v);
                                            setAdvanced({ absXOffset: Number.isFinite(n) ? Math.max(0, Math.min(9999, Math.round(n))) : null });
                                        }}
                                    />
                                </div>
                            </div>

                            <div className="flex items-center justify-between gap-3">
                                <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Vertical Orientation</label>
                                <div className="w-36">
                                    <div className="inline-flex rounded-md border border-gray-200 dark:border-gray-700 overflow-hidden">
                                        <button
                                            type="button"
                                            onClick={() => setAdvanced({ absYSide: 'top' })}
                                            className={`px-3 py-2 text-xs font-semibold ${
                                                adv.absYSide === 'top'
                                                    ? 'bg-blue-600 text-white'
                                                    : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200'
                                            }`}
                                        >
                                            Top
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => setAdvanced({ absYSide: 'bottom' })}
                                            className={`px-3 py-2 text-xs font-semibold border-l border-gray-200 dark:border-gray-700 ${
                                                adv.absYSide === 'bottom'
                                                    ? 'bg-blue-600 text-white'
                                                    : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200'
                                            }`}
                                        >
                                            Bottom
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div className="flex items-center justify-between gap-3">
                                <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Vertical Offset (px)</label>
                                <div className="w-44">
                                    <SliderInput
                                        value={adv.absYOffset === null ? '' : adv.absYOffset}
                                        units={['px']}
                                        defaultUnit="px"
                                        min={0}
                                        max={800}
                                        step={1}
                                        onChange={(v) => {
                                            if (v === '' || v === null || v === undefined) {
                                                setAdvanced({ absYOffset: null });
                                                return;
                                            }
                                            if (v && typeof v === 'object') {
                                                const n = Number(v.value);
                                                setAdvanced({ absYOffset: Number.isFinite(n) && v.value !== '' ? Math.max(0, Math.min(9999, Math.round(n))) : null });
                                                return;
                                            }
                                            const n = Number(v);
                                            setAdvanced({ absYOffset: Number.isFinite(n) ? Math.max(0, Math.min(9999, Math.round(n))) : null });
                                        }}
                                    />
                                </div>
                            </div>
                        </div>
                    ) : null}

                    <div className="flex items-center justify-between gap-3">
                        <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Z-Index</label>
                        <div className="w-36">
                            <Input
                                type="number"
                                value={adv.zIndex === null ? '' : String(adv.zIndex)}
                                onChange={(v) => {
                                    if (v === '' || v === null || v === undefined) {
                                        setAdvanced({ zIndex: null });
                                        return;
                                    }
                                    const n = Number(v);
                                    setAdvanced({ zIndex: Number.isFinite(n) ? Math.round(n) : null });
                                }}
                            />
                        </div>
                    </div>

                    <div className="flex items-center justify-between gap-3">
                        <label className="text-xs font-medium text-gray-700 dark:text-gray-300">CSS ID</label>
                        <div className="w-36">
                            <Input value={adv.cssId || ''} onChange={(v) => setAdvanced({ cssId: v })} />
                        </div>
                    </div>

                    <div className="flex items-center justify-between gap-3">
                        <label className="text-xs font-medium text-gray-700 dark:text-gray-300">CSS Classes</label>
                        <div className="w-36">
                            <Input value={adv.cssClasses || ''} onChange={(v) => setAdvanced({ cssClasses: v })} />
                        </div>
                    </div>

                    <div className="flex items-center justify-between gap-3">
                        <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Cache Settings</label>
                        <div className="w-36">
                            <Select
                                value={adv.cache || 'default'}
                                onChange={(v) => setAdvanced({ cache: v })}
                                options={[
                                    { value: 'default', label: 'Default' },
                                    { value: 'no_cache', label: 'No cache' },
                                ]}
                            />
                        </div>
                    </div>

                    <DimensionsControls
                        value={style}
                        onChange={(nextProps) => setStyle(nextProps)}
                        widthLabel="Width (px)"
                        heightLabel="Height (px)"
                        widthPlaceholder="Auto"
                        heightPlaceholder="Auto"
                        useSliders={true}
                        widthMin={0}
                        widthMax={1200}
                        widthStep={1}
                        heightMin={0}
                        heightMax={800}
                        heightStep={1}
                    />

                    <div className="flex items-center justify-between gap-3">
                        <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Border</label>
                        <Popover
                            width={320}
                            trigger={
                                <PopoverTriggerButton
                                    active={Boolean(
                                        style.borderStyle ||
                                        (style.borderColor && style.borderColor.trim()) ||
                                        style.borderWidthTop ||
                                        style.borderWidthRight ||
                                        style.borderWidthBottom ||
                                        style.borderWidthLeft ||
                                        style.borderWidth ||
                                        style.borderRadiusTop ||
                                        style.borderRadiusRight ||
                                        style.borderRadiusBottom ||
                                        style.borderRadiusLeft ||
                                        style.borderRadius
                                    )}
                                    title="Edit border"
                                    icon={
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                    }
                                />
                            }
                        >
                            <div className="space-y-3">
                                <BorderControls
                                    value={style}
                                    onChange={(nextProps) => setStyle(nextProps)}
                                    widthKeys={borderWidthKeys}
                                    widthFallbackValue={style.borderWidthTop ?? style.borderWidth}
                                />
                                <FourSideLinkedControl
                                    label="Border Radius"
                                    value={style}
                                    keys={borderRadiusKeys}
                                    fallbackValue={style.borderRadiusTop ?? style.borderRadius}
                                    onChange={(nextProps) => setStyle(nextProps)}
                                />
                            </div>
                        </Popover>
                    </div>

                    <div className="space-y-3">
                        <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Spacing</div>
                        <FourSideLinkedControl
                            label="Padding"
                            value={style}
                            keys={paddingKeys}
                            fallbackValue={style.paddingTop ?? style.paddingY ?? style.paddingX}
                            onChange={(nextProps) => setStyle(nextProps)}
                        />
                        <FourSideLinkedControl
                            label="Margin"
                            value={style}
                            keys={marginKeys}
                            fallbackValue={style.marginTop ?? style.marginRight ?? style.marginBottom ?? style.marginLeft}
                            onChange={(nextProps) => setStyle(nextProps)}
                        />
                    </div>
                </div>
            </StyleAccordionItem>

            {pricingTabs}

            {backgroundPreset}

            <StyleAccordionItem title="Transform" defaultOpen={false}>
                <div className="space-y-4 px-4">
                    <div>
                        <div className="flex items-center justify-between">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Translate X</label>
                            <span className="text-xs text-gray-500">{String(style.transformTranslateX ?? 0)}px</span>
                        </div>
                        <Range
                            min={-300}
                            max={300}
                            step={1}
                            value={String(style.transformTranslateX ?? 0)}
                            onChange={(e) => setStyle({ transformTranslateX: Number(e.target.value) })}
                            className="mt-2"
                        />
                    </div>

                    <div>
                        <div className="flex items-center justify-between">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Translate Y</label>
                            <span className="text-xs text-gray-500">{String(style.transformTranslateY ?? 0)}px</span>
                        </div>
                        <Range
                            min={-300}
                            max={300}
                            step={1}
                            value={String(style.transformTranslateY ?? 0)}
                            onChange={(e) => setStyle({ transformTranslateY: Number(e.target.value) })}
                            className="mt-2"
                        />
                    </div>

                    <div>
                        <div className="flex items-center justify-between">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Rotate</label>
                            <span className="text-xs text-gray-500">{String(style.transformRotate ?? 0)}deg</span>
                        </div>
                        <Range
                            min={-180}
                            max={180}
                            step={1}
                            value={String(style.transformRotate ?? 0)}
                            onChange={(e) => setStyle({ transformRotate: Number(e.target.value) })}
                            className="mt-2"
                        />
                    </div>

                    <div>
                        <div className="flex items-center justify-between">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Scale</label>
                            <span className="text-xs text-gray-500">{String(style.transformScale ?? 1)}</span>
                        </div>
                        <Range
                            min={0.1}
                            max={3}
                            step={0.01}
                            value={String(style.transformScale ?? 1)}
                            onChange={(e) => setStyle({ transformScale: Number(e.target.value) })}
                            className="mt-2"
                        />
                    </div>

                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Origin</label>
                        <Select
                            value={style.transformOrigin || 'center center'}
                            onChange={(v) => setStyle({ transformOrigin: v })}
                            options={[
                                { value: 'center center', label: 'Center' },
                                { value: 'top left', label: 'Top Left' },
                                { value: 'top center', label: 'Top Center' },
                                { value: 'top right', label: 'Top Right' },
                                { value: 'center left', label: 'Center Left' },
                                { value: 'center right', label: 'Center Right' },
                                { value: 'bottom left', label: 'Bottom Left' },
                                { value: 'bottom center', label: 'Bottom Center' },
                                { value: 'bottom right', label: 'Bottom Right' },
                            ]}
                        />
                    </div>
                </div>
            </StyleAccordionItem>

            {!hideMotion ? (
            <StyleAccordionItem title="Animation" defaultOpen={false}>
                <div className="space-y-4 px-4">
                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Preset</label>
                        <Select
                            value={style.animationPreset || ''}
                            onChange={(v) => setStyle({ animationPreset: v })}
                            options={[
                                { value: '', label: 'None' },
                                { value: 'mp-fade-in', label: 'Fade In' },
                                { value: 'mp-fade-up', label: 'Fade Up' },
                                { value: 'mp-scale-in', label: 'Scale In' },
                                { value: 'mp-slide-left', label: 'Slide Left' },
                                { value: 'mp-slide-right', label: 'Slide Right' },
                                { value: 'custom', label: 'Custom (From → To)' },
                            ]}
                        />
                    </div>

                    {style.animationPreset ? (
                        <>
                            {style.animationPreset === 'custom' ? (
                                <div className="space-y-4">
                                    <div className="flex items-center gap-2">
                                        <button
                                            type="button"
                                            onClick={() => setCustomAnimTab('from')}
                                            className={
                                                customAnimTab === 'from'
                                                    ? 'px-3 py-1 rounded-md text-xs font-semibold bg-blue-600 text-white'
                                                    : 'px-3 py-1 rounded-md text-xs font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200'
                                            }
                                        >
                                            From
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => setCustomAnimTab('to')}
                                            className={
                                                customAnimTab === 'to'
                                                    ? 'px-3 py-1 rounded-md text-xs font-semibold bg-blue-600 text-white'
                                                    : 'px-3 py-1 rounded-md text-xs font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200'
                                            }
                                        >
                                            To
                                        </button>
                                    </div>

                                    <label className="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                        <input
                                            type="checkbox"
                                            checked={style.animationPingPong === true}
                                            onChange={(e) =>
                                                setStyle({
                                                    animationPingPong: e.target.checked,
                                                    animationIterationCount:
                                                        e.target.checked && String(style.animationIterationCount ?? 1) === '1'
                                                            ? 2
                                                            : (style.animationIterationCount ?? 1),
                                                })
                                            }
                                        />
                                        <span>Ping-pong (From ↔ To)</span>
                                    </label>

                                    <div>
                                        <div className="flex items-center justify-between">
                                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                {customAnimTab === 'from' ? 'From: Translate X' : 'To: Translate X'}
                                            </label>
                                            <span className="text-xs text-gray-500">
                                                {customAnimTab === 'from'
                                                    ? `${String(style.animationFromTranslateX ?? 0)}px`
                                                    : `${String(toValue.tx)}px`}
                                            </span>
                                        </div>
                                        <Range
                                            min={-300}
                                            max={300}
                                            step={1}
                                            value={
                                                customAnimTab === 'from'
                                                    ? String(style.animationFromTranslateX ?? 0)
                                                    : String(toValue.tx)
                                            }
                                            onChange={(e) =>
                                                setStyle(
                                                    customAnimTab === 'from'
                                                        ? { animationFromTranslateX: Number(e.target.value) }
                                                        : { animationToTranslateX: Number(e.target.value) }
                                                )
                                            }
                                            className="mt-2"
                                        />
                                    </div>

                                    <div>
                                        <div className="flex items-center justify-between">
                                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                {customAnimTab === 'from' ? 'From: Translate Y' : 'To: Translate Y'}
                                            </label>
                                            <span className="text-xs text-gray-500">
                                                {customAnimTab === 'from'
                                                    ? `${String(style.animationFromTranslateY ?? 0)}px`
                                                    : `${String(toValue.ty)}px`}
                                            </span>
                                        </div>
                                        <Range
                                            min={-300}
                                            max={300}
                                            step={1}
                                            value={
                                                customAnimTab === 'from'
                                                    ? String(style.animationFromTranslateY ?? 0)
                                                    : String(toValue.ty)
                                            }
                                            onChange={(e) =>
                                                setStyle(
                                                    customAnimTab === 'from'
                                                        ? { animationFromTranslateY: Number(e.target.value) }
                                                        : { animationToTranslateY: Number(e.target.value) }
                                                )
                                            }
                                            className="mt-2"
                                        />
                                    </div>

                                    <div>
                                        <div className="flex items-center justify-between">
                                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                {customAnimTab === 'from' ? 'From: Rotate' : 'To: Rotate'}
                                            </label>
                                            <span className="text-xs text-gray-500">
                                                {customAnimTab === 'from'
                                                    ? `${String(style.animationFromRotate ?? 0)}deg`
                                                    : `${String(toValue.rot)}deg`}
                                            </span>
                                        </div>
                                        <Range
                                            min={-180}
                                            max={180}
                                            step={1}
                                            value={
                                                customAnimTab === 'from'
                                                    ? String(style.animationFromRotate ?? 0)
                                                    : String(toValue.rot)
                                            }
                                            onChange={(e) =>
                                                setStyle(
                                                    customAnimTab === 'from'
                                                        ? { animationFromRotate: Number(e.target.value) }
                                                        : { animationToRotate: Number(e.target.value) }
                                                )
                                            }
                                            className="mt-2"
                                        />
                                    </div>

                                    <div>
                                        <div className="flex items-center justify-between">
                                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                {customAnimTab === 'from' ? 'From: Scale' : 'To: Scale'}
                                            </label>
                                            <span className="text-xs text-gray-500">
                                                {customAnimTab === 'from'
                                                    ? String(style.animationFromScale ?? 1)
                                                    : String(toValue.scale)}
                                            </span>
                                        </div>
                                        <Range
                                            min={0.1}
                                            max={3}
                                            step={0.01}
                                            value={
                                                customAnimTab === 'from'
                                                    ? String(style.animationFromScale ?? 1)
                                                    : String(toValue.scale)
                                            }
                                            onChange={(e) =>
                                                setStyle(
                                                    customAnimTab === 'from'
                                                        ? { animationFromScale: Number(e.target.value) }
                                                        : { animationToScale: Number(e.target.value) }
                                                )
                                            }
                                            className="mt-2"
                                        />
                                    </div>
                                </div>
                            ) : null}

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Duration (s)</label>
                                    <Input
                                        type="number"
                                        value={String(style.animationDuration ?? 0.8)}
                                        onChange={(v) => setStyle({ animationDuration: Number(v) })}
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Delay (s)</label>
                                    <Input
                                        type="number"
                                        value={String(style.animationDelay ?? 0)}
                                        onChange={(v) => setStyle({ animationDelay: Number(v) })}
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Easing</label>
                                <Input value={style.animationEasing || 'ease'} onChange={(v) => setStyle({ animationEasing: v })} />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Iterations</label>
                                    <Select
                                        value={style.animationIterationCount === 'infinite' ? 'infinite' : String(style.animationIterationCount ?? 1)}
                                        onChange={(v) =>
                                            setStyle({
                                                animationIterationCount:
                                                    v === 'infinite' ? 'infinite' : Math.max(1, Math.round(Number(v) || 1)),
                                            })
                                        }
                                        options={[
                                            { value: '1', label: '1' },
                                            { value: '2', label: '2' },
                                            { value: '3', label: '3' },
                                            { value: 'infinite', label: 'Infinite' },
                                        ]}
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Fill mode</label>
                                    <Select
                                        value={style.animationFillMode || 'both'}
                                        onChange={(v) => setStyle({ animationFillMode: v })}
                                        options={[
                                            { value: 'none', label: 'None' },
                                            { value: 'both', label: 'Both' },
                                            { value: 'forwards', label: 'Forwards' },
                                            { value: 'backwards', label: 'Backwards' },
                                        ]}
                                    />
                                </div>
                            </div>
                        </>
                    ) : null}
                </div>
            </StyleAccordionItem>
            ) : null}

            {!hideMotion ? (
            <StyleAccordionItem title="GSAP" defaultOpen={false}>
                <div className="space-y-4 px-4">
                    <label className="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input
                            type="checkbox"
                            checked={style.gsapEnabled === true}
                            onChange={(e) => setStyle({ gsapEnabled: e.target.checked })}
                        />
                        <span>Enable GSAP</span>
                    </label>

                    {style.gsapEnabled ? (
                        <>
                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Preset</label>
                                <Select
                                    value={style.gsapPreset || 'fade-up'}
                                    onChange={(v) => setStyle({ gsapPreset: v })}
                                    options={[
                                        { value: 'fade-up', label: 'Fade Up' },
                                        { value: 'fade-in', label: 'Fade In' },
                                        { value: 'scale-in', label: 'Scale In' },
                                        { value: 'slide-left', label: 'Slide Left' },
                                        { value: 'slide-right', label: 'Slide Right' },
                                        { value: 'blurred-in', label: 'Blurred In' },
                                        { value: 'from-x', label: 'From X' },
                                        { value: 'from-y', label: 'From Y' },
                                    ]}
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Trigger</label>
                                <Select
                                    value={style.gsapTrigger || 'scroll'}
                                    onChange={(v) => setStyle({ gsapTrigger: v })}
                                    options={[
                                        { value: 'scroll', label: 'Scroll' },
                                        { value: 'load', label: 'On Load' },
                                    ]}
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Duration (s)</label>
                                    <Input
                                        type="number"
                                        value={String(style.gsapDuration ?? 0.8)}
                                        onChange={(v) => setStyle({ gsapDuration: Number(v) })}
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Delay (s)</label>
                                    <Input
                                        type="number"
                                        value={String(style.gsapDelay ?? 0)}
                                        onChange={(v) => setStyle({ gsapDelay: Number(v) })}
                                    />
                                </div>
                            </div>

                            {isTextTarget ? (
                                <>
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Split</label>
                                        <Select
                                            value={style.gsapSplit || ''}
                                            onChange={(v) => setStyle({ gsapSplit: v })}
                                            options={[
                                                { value: '', label: 'None' },
                                                { value: 'chars', label: 'Letters' },
                                                { value: 'words', label: 'Words' },
                                                { value: 'lines', label: 'Lines' },
                                            ]}
                                        />
                                    </div>

                                    {style.gsapSplit ? (
                                        <div>
                                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Split Stagger (s)</label>
                                            <Input
                                                type="number"
                                                step="0.01"
                                                value={String(style.gsapSplitStagger ?? 0.04)}
                                                onChange={(v) => setStyle({ gsapSplitStagger: Number(v) })}
                                            />
                                        </div>
                                    ) : null}
                                </>
                            ) : isContainerTarget ? (
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Stagger (s)</label>
                                    <Input
                                        type="number"
                                        step="0.05"
                                        value={String(style.gsapStagger ?? 0)}
                                        onChange={(v) => setStyle({ gsapStagger: Number(v) })}
                                    />
                                    <div className="mt-1 text-xs text-gray-500">If &gt; 0, the parent container will stagger-animate its inner columns.</div>
                                </div>
                            ) : null}

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Ease</label>
                                {(() => {
                                    const commonEases = [
                                        { value: 'power1.out', label: 'Power1 Out' },
                                        { value: 'power2.out', label: 'Power2 Out' },
                                        { value: 'power3.out', label: 'Power3 Out' },
                                        { value: 'power4.out', label: 'Power4 Out' },
                                        { value: 'sine.out', label: 'Sine Out' },
                                        { value: 'expo.out', label: 'Expo Out' },
                                        { value: 'circ.out', label: 'Circ Out' },
                                        { value: 'back.out(1.7)', label: 'Back Out' },
                                        { value: 'bounce.out', label: 'Bounce Out' },
                                        { value: 'elastic.out(1, 0.3)', label: 'Elastic Out' },
                                        { value: 'none', label: 'Linear (none)' },
                                    ];

                                    const easeValue = typeof style.gsapEase === 'string' && style.gsapEase.trim() ? style.gsapEase.trim() : 'power2.out';
                                    const isCommon = commonEases.some((x) => x.value === easeValue);
                                    const selectValue = isCommon ? easeValue : '__custom__';

                                    return (
                                        <div className="space-y-2">
                                            <Select
                                                value={selectValue}
                                                onChange={(v) => {
                                                    if (v === '__custom__') return;
                                                    setStyle({ gsapEase: v });
                                                }}
                                                options={[
                                                    ...commonEases,
                                                    { value: '__custom__', label: 'Custom…' },
                                                ]}
                                            />

                                            {selectValue === '__custom__' ? (
                                                <Input
                                                    value={easeValue}
                                                    onChange={(v) => setStyle({ gsapEase: v })}
                                                    placeholder="e.g. power2.out"
                                                />
                                            ) : null}
                                        </div>
                                    );
                                })()}
                            </div>

                            {style.gsapTrigger === 'scroll' ? (
                                <div>
                                    <div className="flex items-center justify-between">
                                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Start</label>
                                        <span className="text-xs text-gray-500">{String(style.gsapStart ?? 80)}%</span>
                                    </div>
                                    <Range
                                        min={0}
                                        max={100}
                                        step={1}
                                        value={String(style.gsapStart ?? 80)}
                                        onChange={(e) => setStyle({ gsapStart: Number(e.target.value) })}
                                        className="mt-2"
                                    />
                                </div>
                            ) : null}

                            <label className="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input
                                    type="checkbox"
                                    checked={style.gsapOnce !== false}
                                    onChange={(e) => setStyle({ gsapOnce: e.target.checked })}
                                />
                                <span>Once</span>
                            </label>
                        </>
                    ) : null}
                </div>
            </StyleAccordionItem>
            ) : null}
        </div>
    );
}

function ContextMenu({ menu, onClose, onAction }) {
    const menuRef = useRef(null);

    useEffect(() => {
        const handleClickOutside = (e) => {
            if (menuRef.current && !menuRef.current.contains(e.target)) {
                onClose();
            }
        };

        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                onClose();
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        document.addEventListener('keydown', handleEscape);

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            document.removeEventListener('keydown', handleEscape);
        };
    }, [onClose]);

    if (!menu) return null;

    const { x, y, target } = menu;

    const getElementLabel = () => {
        if (target.kind === 'section') return 'Section';
        if (target.kind === 'column') return 'Column';
        if (target.kind === 'grid_column') return 'Grid Column';
        if (target.kind === 'tab_item') return 'Tab Item';
        if (target.kind === 'widget' || target.kind === 'grid_widget' || target.kind === 'tab_widget') {
            const widgetName = (target.widget && typeof target.widget.name === 'string') ? target.widget.name.trim() : '';
            if (widgetName !== '') return widgetName;
            return target.widget?.type?.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase()) || 'Widget';
        }
        return 'Element';
    };

    const isWidgetTarget = target.kind === 'widget' || target.kind === 'grid_widget' || target.kind === 'tab_widget';

    const menuItems = [
        { id: 'edit', label: `Edit ${getElementLabel()}`, shortcut: null, action: 'edit' },
        { id: 'duplicate', label: 'Duplicate', shortcut: '⌘+D', action: 'duplicate', divider: true },
        { id: 'copy', label: 'Copy', shortcut: '⌘+C', action: 'copy' },
        { id: 'paste', label: 'Paste', shortcut: '⌘+V', action: 'paste', disabled: !menu.hasClipboard },
        { id: 'paste-style', label: 'Paste style', shortcut: '⌘+⇧+V', action: 'paste-style', disabled: !menu.hasStyleClipboard },
        { id: 'paste-typography', label: 'Paste typography', shortcut: '⌘+⌥+V', action: 'paste-typography', disabled: !menu.hasTypoClipboard || !isWidgetTarget, divider: true },
        { id: 'reset-style', label: 'Reset style', shortcut: null, action: 'reset-style', divider: target.kind !== 'section' },
        ...(target.kind === 'section' ? [
            { id: 'export-json', label: 'Export as JSON', shortcut: null, action: 'export-json' },
            { id: 'copy-json', label: 'Copy JSON', shortcut: null, action: 'copy-json', divider: true },
        ] : []),
        { id: 'delete', label: 'Delete', shortcut: null, action: 'delete', danger: true },
    ];

    return createPortal(
        <div
            ref={menuRef}
            className="fixed bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 py-1 min-w-[220px] z-[10000]"
            style={{
                left: `${x}px`,
                top: `${y}px`,
            }}
        >
            {menuItems.map((item, idx) => (
                <React.Fragment key={item.id}>
                    <button
                        type="button"
                        disabled={item.disabled}
                        onClick={() => {
                            if (item.disabled) return;
                            onAction(item.action);
                            onClose();
                        }}
                        className={`w-full px-3 py-2 text-left text-sm flex items-center justify-between gap-4 transition-colors ${
                            item.disabled
                                ? 'text-gray-400 dark:text-gray-600 cursor-not-allowed'
                                : item.danger
                                    ? 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20'
                                    : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700'
                        }`}
                    >
                        <span>{item.label}</span>
                        {item.shortcut ? <span className="text-xs text-gray-400 dark:text-gray-500">{item.shortcut}</span> : null}
                    </button>
                    {item.divider && idx < menuItems.length - 1 ? <div className="h-px bg-gray-200 dark:bg-gray-700 my-1" /> : null}
                </React.Fragment>
            ))}
        </div>,
        document.body
    );
}


function parseTemplateJsonToSections(jsonCode) {
    let data = jsonCode;
    if (typeof data === 'string') {
        try {
            data = JSON.parse(data);
        } catch (e) {
            return [];
        }
    }
    if (!data || typeof data !== 'object') return [];

    if (Array.isArray(data)) {
        return data.filter((x) => x && typeof x === 'object' && Array.isArray(x.columns));
    }
    if (Array.isArray(data.sections)) {
        return data.sections.filter((x) => x && typeof x === 'object');
    }
    if (data.page && Array.isArray(data.page.sections)) {
        return data.page.sections.filter((x) => x && typeof x === 'object');
    }
    if (Array.isArray(data.columns)) {
        return [data];
    }
    if (data.section && typeof data.section === 'object') {
        return [data.section];
    }
    return [];
}

const PAGE_TEMPLATE_TABS = [
    { key: 'pages', label: 'Page Templates', resourceType: 'Page' },
    { key: 'sections', label: 'Sections', resourceType: 'sectionblocks' },
];

const EMAIL_TEMPLATE_TABS = [
    { key: 'templates', label: 'Templates', resourceType: 'emailtemplates' },
    { key: 'blocks', label: 'Blocks', resourceType: 'emailblocks' },
];

function normalizeGalleryCategories(raw) {
    return (Array.isArray(raw) ? raw : [])
        .map((c) => {
            if (typeof c === 'string') return { value: c, label: c };
            if (!c || typeof c !== 'object') return null;
            const value = String(c.slug ?? c.id ?? c.value ?? c.term_id ?? c.term ?? '').trim();
            const label = String(c.name ?? c.label ?? c.title ?? value).trim();
            return value ? { value, label } : null;
        })
        .filter(Boolean);
}

function InsertSectionModal({ open, onClose, onInsertBlank, onInsertTemplate, onInsertTemplateJson, mode = 'all', builderMode = 'page', templatesApi = null }) {
    const tabs = builderMode === 'email' ? EMAIL_TEMPLATE_TABS : PAGE_TEMPLATE_TABS;
    const [activeTab, setActiveTab] = useState(tabs[0].key);
    const [templatesByKey, setTemplatesByKey] = useState({});
    const [loadingByKey, setLoadingByKey] = useState({});
    const [errorByKey, setErrorByKey] = useState({});
    const [categoriesByTab, setCategoriesByTab] = useState({});
    const [category, setCategory] = useState('');
    const [search, setSearch] = useState('');
    const [insertingId, setInsertingId] = useState(null);
    const [previewTemplate, setPreviewTemplate] = useState(null);

    const cacheKey = `${activeTab}:${category}`;
    const templates = templatesByKey[cacheKey] || [];
    const loading = Boolean(loadingByKey[cacheKey]);
    const error = errorByKey[cacheKey] || '';
    const categories = categoriesByTab[activeTab] || [];

    useEffect(() => {
        if (!open) return;
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                if (previewTemplate) {
                    setPreviewTemplate(null);
                    return;
                }
                onClose();
            }
        };
        document.addEventListener('keydown', handleEscape);
        return () => document.removeEventListener('keydown', handleEscape);
    }, [open, onClose, previewTemplate]);

    useEffect(() => {
        setActiveTab(tabs[0].key);
        setCategory('');
        setSearch('');
        setPreviewTemplate(null);
    }, [builderMode]);

    useEffect(() => {
        if (!open) setPreviewTemplate(null);
    }, [open]);

    useEffect(() => {
        setCategory('');
        setSearch('');
    }, [activeTab]);

    useEffect(() => {
        if (!open) return;
        if (!templatesApi || !templatesApi.listUrl) return;
        if (templatesByKey[cacheKey] || loadingByKey[cacheKey]) return;

        const tab = tabs.find((t) => t.key === activeTab);
        if (!tab) return;

        const controller = new AbortController();
        const listBase = String(templatesApi.listUrl).replace(/\/+$/, '');
        const params = new URLSearchParams();
        params.set('per_page', '100');
        if (category) params.set('category', category);

        setLoadingByKey((s) => ({ ...s, [cacheKey]: true }));
        setErrorByKey((s) => ({ ...s, [cacheKey]: '' }));

        fetch(`${listBase}/${encodeURIComponent(tab.resourceType)}?${params.toString()}`, {
            method: 'GET',
            signal: controller.signal,
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((res) => {
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.json();
            })
            .then((json) => {
                const list = Array.isArray(json && json.templates)
                    ? json.templates
                    : Array.isArray(json && json.data)
                    ? json.data
                    : [];
                setTemplatesByKey((s) => ({ ...s, [cacheKey]: list }));

                const rawCategories = json && json.meta && json.meta.categories
                    ? json.meta.categories
                    : json && json.categories
                    ? json.categories
                    : null;
                if (rawCategories) {
                    setCategoriesByTab((s) => ({ ...s, [activeTab]: normalizeGalleryCategories(rawCategories) }));
                }
            })
            .catch((e) => {
                if (e.name === 'AbortError') return;
                setErrorByKey((s) => ({ ...s, [cacheKey]: 'Could not load templates from the gallery. Please try again.' }));
                setTemplatesByKey((s) => ({ ...s, [cacheKey]: [] }));
            })
            .finally(() => {
                setLoadingByKey((s) => ({ ...s, [cacheKey]: false }));
            });

        return () => controller.abort();
    }, [open, activeTab, category, builderMode, templatesApi && templatesApi.listUrl]);

    if (!open) return null;

    const filteredTemplates = templates.filter((t) => {
        if (!search.trim()) return true;
        const q = search.trim().toLowerCase();
        return (
            String(t.name || '').toLowerCase().includes(q) ||
            String(t.description || '').toLowerCase().includes(q)
        );
    });

    const handlePickTemplate = async (tpl) => {
        if (!tpl || !tpl.id) return;
        if (!templatesApi || !templatesApi.fetchUrl) return;
        setInsertingId(tpl.id);
        try {
            const fetchBase = String(templatesApi.fetchUrl).replace(/\/+$/, '');
            const csrf =
                (typeof document !== 'undefined' &&
                    document.querySelector('meta[name="csrf-token"]') &&
                    document.querySelector('meta[name="csrf-token"]').getAttribute('content')) ||
                '';

            const res = await fetch(`${fetchBase}/${encodeURIComponent(tpl.id)}`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf,
                },
                body: '',
            });
            if (!res.ok) {
                const txt = await res.text().catch(() => '');
                throw new Error(txt || `HTTP ${res.status}`);
            }
            const data = await res.json();
            const jsonCode = data && (data.json_code !== undefined ? data.json_code : data.data && data.data.json_code);
            if (jsonCode === undefined || jsonCode === null || jsonCode === '') {
                throw new Error('Template returned empty content.');
            }
            if (typeof onInsertTemplateJson === 'function') {
                onInsertTemplateJson(jsonCode);
            }
        } catch (e) {
            setError(typeof e === 'object' && e && e.message ? e.message : 'Could not insert this template.');
        } finally {
            setInsertingId(null);
        }
    };

    return createPortal(
        <>
        <div className="fixed inset-0 z-[10001]">
            <div className="absolute inset-0 bg-black/40" onMouseDown={onClose} />
            <div className="absolute inset-0 flex items-center justify-center p-3">
                <div
                    className="rounded-xl bg-white shadow-2xl border border-gray-200 flex flex-col"
                    style={{ width: '95vw', height: '95vh', maxWidth: '95vw', maxHeight: '95vh' }}
                    onMouseDown={(e) => e.stopPropagation()}
                >
                    <div className="px-5 pt-4 pb-0 border-b border-gray-200 shrink-0">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="text-base font-semibold text-gray-900">Insert Section</div>
                                {templatesApi && templatesApi.listUrl ? (
                                    <div className="text-xs text-gray-400">
                                        {loading ? 'Loading…' : `${filteredTemplates.length} item${filteredTemplates.length === 1 ? '' : 's'}`}
                                    </div>
                                ) : null}
                            </div>
                            <div className="flex items-center gap-2">
                                {templatesApi && templatesApi.listUrl ? (
                                    <>
                                        {categories.length > 0 ? (
                                            <select
                                                value={category}
                                                onChange={(e) => setCategory(e.target.value)}
                                                className="h-9 px-3 rounded-md border border-gray-200 text-sm w-48 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            >
                                                <option value="">All categories</option>
                                                {categories.map((c) => (
                                                    <option key={c.value} value={c.value}>{c.label}</option>
                                                ))}
                                            </select>
                                        ) : null}
                                        <input
                                            type="text"
                                            value={search}
                                            onChange={(e) => setSearch(e.target.value)}
                                            placeholder="Search…"
                                            className="h-9 px-3 rounded-md border border-gray-200 text-sm w-64 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        />
                                    </>
                                ) : null}
                                <button
                                    type="button"
                                    onClick={onClose}
                                    className="h-9 w-9 inline-flex items-center justify-center rounded-md hover:bg-gray-50 text-gray-600"
                                    aria-label="Close"
                                >
                                    <span aria-hidden>×</span>
                                </button>
                            </div>
                        </div>

                        {templatesApi && templatesApi.listUrl ? (
                            <div className="flex items-stretch gap-1 mt-3 -mb-px">
                                {tabs.map((t) => {
                                    const isActive = t.key === activeTab;
                                    return (
                                        <button
                                            key={t.key}
                                            type="button"
                                            onClick={() => setActiveTab(t.key)}
                                            className={
                                                'px-4 py-2 text-center text-sm font-medium border-b-2 transition-colors ' +
                                                (isActive
                                                    ? '!border-blue-600 text-blue-600'
                                                    : '!border-transparent text-gray-500 hover:text-gray-700')
                                            }
                                        >
                                            {t.label}
                                        </button>
                                    );
                                })}
                            </div>
                        ) : null}
                    </div>

                    <div className="flex-1 overflow-y-auto px-5 py-5 space-y-5">
                        {mode !== 'templates' ? (
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <button
                                    type="button"
                                    onClick={onInsertBlank}
                                    className="rounded-lg border border-gray-200 p-4 text-left hover:bg-gray-50"
                                >
                                    <div className="text-sm font-semibold text-gray-900">Blank Section</div>
                                    <div className="text-xs text-gray-500 mt-1">Start from an empty container</div>
                                </button>

                                <button
                                    type="button"
                                    onClick={() => onInsertTemplate('two_columns')}
                                    className="rounded-lg border border-gray-200 p-4 text-left hover:bg-gray-50"
                                >
                                    <div className="text-sm font-semibold text-gray-900">2 Columns</div>
                                    <div className="text-xs text-gray-500 mt-1">A simple two-column layout</div>
                                </button>
                            </div>
                        ) : null}

                        <div>
                            {error ? (
                                <div className="text-xs text-red-600 mb-3">{error}</div>
                            ) : null}

                            {loading ? (
                                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                                    {Array.from({ length: 10 }).map((_, i) => (
                                        <div key={i} className="rounded-lg border border-gray-200 overflow-hidden animate-pulse">
                                            <div className="aspect-[4/3] bg-gray-100" />
                                            <div className="p-3 space-y-2">
                                                <div className="h-3 bg-gray-100 rounded w-2/3" />
                                                <div className="h-2 bg-gray-100 rounded w-1/2" />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : filteredTemplates.length === 0 ? (
                                <div className="rounded-lg border border-dashed border-gray-200 p-10 text-center text-sm text-gray-500">
                                    {templates.length === 0
                                        ? 'No templates available right now.'
                                        : 'No templates match your search.'}
                                </div>
                            ) : (
                                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                                    {filteredTemplates.map((tpl) => {
                                        const previewImage = tpl.preview_image || '';
                                        const previewUrl = tpl.preview_url || '';
                                        const categories = Array.isArray(tpl.categories) ? tpl.categories : [];
                                        const isInserting = insertingId === tpl.id;
                                        return (
                                            <div
                                                key={tpl.id}
                                                className="group rounded-lg border border-gray-200 overflow-hidden bg-white hover:shadow-md transition-shadow flex flex-col"
                                            >
                                                <button
                                                    type="button"
                                                    onClick={() => handlePickTemplate(tpl)}
                                                    disabled={isInserting}
                                                    className="block w-full aspect-[4/3] bg-gray-50 overflow-hidden text-left disabled:opacity-60"
                                                    title={`Insert ${tpl.name || 'template'}`}
                                                >
                                                    {previewImage ? (
                                                        <img
                                                            src={previewImage}
                                                            alt={tpl.name || 'Template'}
                                                            className="w-full h-full object-cover group-hover:scale-[1.02] transition-transform"
                                                            loading="lazy"
                                                        />
                                                    ) : (
                                                        <div className="w-full h-full flex items-center justify-center text-gray-300 text-xs">
                                                            No preview
                                                        </div>
                                                    )}
                                                </button>
                                                <div className="p-3 flex-1 flex flex-col gap-2">
                                                    <div className="flex items-start justify-between gap-2">
                                                        <div className="text-sm font-semibold text-gray-900 truncate" title={tpl.name || ''}>
                                                            {tpl.name || 'Untitled'}
                                                        </div>
                                                    </div>
                                                    {categories.length ? (
                                                        <div className="flex flex-wrap gap-1">
                                                            {categories.slice(0, 3).map((c) => (
                                                                <span
                                                                    key={c.id || c.name}
                                                                    className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] bg-gray-100 text-gray-600"
                                                                >
                                                                    {c.name}
                                                                </span>
                                                            ))}
                                                        </div>
                                                    ) : null}
                                                    <div className="mt-auto flex items-center gap-2">
                                                        <button
                                                            type="button"
                                                            onClick={() => handlePickTemplate(tpl)}
                                                            disabled={isInserting}
                                                            className="flex-1 h-8 px-3 rounded-md bg-black hover:bg-gray-800 text-white text-xs font-medium inline-flex items-center justify-center gap-1.5 disabled:opacity-60"
                                                        >
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                                                <path d="M12 5v14" />
                                                                <path d="M5 12h14" />
                                                            </svg>
                                                            <span>{isInserting ? 'Inserting…' : 'Insert'}</span>
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => setPreviewTemplate(tpl)}
                                                            disabled={!previewImage && !previewUrl}
                                                            className="flex-1 h-8 px-3 rounded-md bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-medium inline-flex items-center justify-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed"
                                                        >
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                                                                <circle cx="12" cy="12" r="3" />
                                                            </svg>
                                                            <span>Preview</span>
                                                        </button>
                                                     </div>
                                                 </div>
                                             </div>
                                         );
                                                    })}
                                             </div>
                                         )}
                         </div>
                     </div>
                 </div>
             </div>
         </div>
        {previewTemplate ? (
            <div className="fixed inset-0 z-[10002]">
                <div className="absolute inset-0 bg-black/60" onMouseDown={() => setPreviewTemplate(null)} />
                <div className="absolute inset-0 flex items-center justify-center p-4">
                    <div
                        className="relative rounded-xl bg-white shadow-2xl border border-gray-200 flex flex-col"
                        style={{ width: '90vw', height: '90vh', maxWidth: '90vw', maxHeight: '90vh' }}
                        onMouseDown={(e) => e.stopPropagation()}
                    >
                        <div className="flex items-center justify-between gap-3 px-5 py-3 border-b border-gray-200 shrink-0">
                            <div className="min-w-0 truncate text-base font-semibold text-gray-900">
                                {previewTemplate.name || 'Template preview'}
                            </div>
                            <div className="flex items-center gap-2 shrink-0">
                                {previewTemplate.preview_url ? (
                                    <a
                                        href={previewTemplate.preview_url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="h-8 px-3 inline-flex items-center rounded-md border border-gray-200 hover:bg-gray-50 text-xs font-medium text-gray-700"
                                    >
                                        Open in new tab
                                    </a>
                                ) : null}
                                <button
                                    type="button"
                                    onClick={() => setPreviewTemplate(null)}
                                    className="h-8 w-8 inline-flex items-center justify-center rounded-md border border-gray-200 hover:bg-gray-50 text-gray-600"
                                    title="Close preview"
                                    aria-label="Close preview"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                        <path d="M18 6 6 18" />
                                        <path d="m6 6 12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div className="flex-1 min-h-0 overflow-auto bg-gray-100 flex items-start justify-center p-6">
                            {previewTemplate.preview_image ? (
                                <img
                                    src={previewTemplate.preview_image}
                                    alt={previewTemplate.name || 'Template preview'}
                                    className="max-w-full h-auto rounded-md shadow-lg bg-white"
                                />
                            ) : (
                                <div className="text-sm text-gray-400 self-center">No preview image available.</div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        ) : null}
        </>,
        document.body
    );
}

function normalizeAdvanced(raw) {
    const a = raw && typeof raw === 'object' ? raw : {};

    const position = ['static', 'relative', 'absolute', 'fixed', 'sticky'].includes(a.position) ? a.position : '';

    const display = ['block', 'inline', 'inline-block', 'flex', 'inline-flex', 'grid', 'inline-grid', 'none'].includes(a.display)
        ? a.display
        : '';

    const displayTablet = ['block', 'inline', 'inline-block', 'flex', 'inline-flex', 'grid', 'inline-grid', 'none'].includes(a.displayTablet)
        ? a.displayTablet
        : '';

    const displayMobile = ['block', 'inline', 'inline-block', 'flex', 'inline-flex', 'grid', 'inline-grid', 'none'].includes(a.displayMobile)
        ? a.displayMobile
        : '';

    const textAlign = ['left', 'center', 'right', 'justify', 'start', 'end'].includes(a.textAlign) ? a.textAlign : '';

    const overflowX = ['visible', 'hidden', 'scroll', 'auto', 'clip'].includes(a.overflowX) ? a.overflowX : '';
    const overflowY = ['visible', 'hidden', 'scroll', 'auto', 'clip'].includes(a.overflowY) ? a.overflowY : '';

    const alignItems = ['stretch', 'flex-start', 'center', 'flex-end', 'baseline', 'start', 'end', 'self-start', 'self-end'].includes(a.alignItems) ? a.alignItems : '';
    const justifyContent = ['flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly', 'start', 'end', 'left', 'right'].includes(a.justifyContent) ? a.justifyContent : '';

    const absXSide = ['left', 'right'].includes(a.absXSide) ? a.absXSide : 'left';
    const absYSide = ['top', 'bottom'].includes(a.absYSide) ? a.absYSide : 'top';

    let absXOffset = null;
    if (a.absXOffset !== null && a.absXOffset !== undefined && a.absXOffset !== '') {
        const n = Number(a.absXOffset);
        if (Number.isFinite(n)) {
            absXOffset = Math.max(-9999, Math.min(9999, Math.round(n)));
        }
    }

    let absYOffset = null;
    if (a.absYOffset !== null && a.absYOffset !== undefined && a.absYOffset !== '') {
        const n = Number(a.absYOffset);
        if (Number.isFinite(n)) {
            absYOffset = Math.max(-9999, Math.min(9999, Math.round(n)));
        }
    }

    let zIndex = null;
    if (a.zIndex !== null && a.zIndex !== undefined && a.zIndex !== '') {
        const n = Number(a.zIndex);
        if (Number.isFinite(n)) {
            zIndex = Math.round(n);
        }
    }

    let width = undefined;
    if (a.width !== null && a.width !== undefined && a.width !== '') {
        const n = Number(a.width);
        if (Number.isFinite(n)) {
            width = Math.max(0, Math.round(n));
        }
    }

    let height = undefined;
    if (a.height !== null && a.height !== undefined && a.height !== '') {
        const n = Number(a.height);
        if (Number.isFinite(n)) {
            height = Math.max(0, Math.round(n));
        }
    }

    const cache = a.cache === 'no_cache' ? 'no_cache' : 'default';

    return {
        cssId: typeof a.cssId === 'string' ? a.cssId : '',
        cssClasses: typeof a.cssClasses === 'string' ? a.cssClasses : '',
        position,
        display,
        displayTablet,
        displayMobile,
        textAlign,
        overflowX,
        overflowY,
        alignItems,
        justifyContent,
        absXSide,
        absXOffset,
        absYSide,
        absYOffset,
        zIndex,
        width,
        height,
        cache,
    };
}

function advancedReactAttrs(raw) {
    const adv = normalizeAdvanced(raw);

    const id = adv.cssId && adv.cssId.trim() ? adv.cssId.trim() : undefined;
    const className = adv.cssClasses && adv.cssClasses.trim() ? adv.cssClasses.trim() : '';
    const style = {};
    const wrapperStyle = {};
    const takesOutOfFlow = adv.position === 'absolute' || adv.position === 'fixed';
    if (adv.position) {
        if (takesOutOfFlow) {
            wrapperStyle.position = adv.position;
            wrapperStyle.width = 'auto';
            wrapperStyle.maxWidth = '100%';
        } else {
            style.position = adv.position;
        }
    }
    const effectiveDisplay = pickResponsive(adv.display, adv.displayTablet, adv.displayMobile);
    if (effectiveDisplay) {
        style.display = effectiveDisplay;
        if (effectiveDisplay === 'flex' || effectiveDisplay === 'inline-flex') {
            style.minHeight = '100%';
        }
    }
    if (adv.textAlign) style.textAlign = adv.textAlign;
    if (adv.overflowX) style.overflowX = adv.overflowX;
    if (adv.overflowY) style.overflowY = adv.overflowY;
    if (adv.alignItems) style.alignItems = adv.alignItems;
    if (adv.justifyContent) style.justifyContent = adv.justifyContent;
    if (adv.zIndex !== null) {
        if (takesOutOfFlow) wrapperStyle.zIndex = adv.zIndex;
        else style.zIndex = adv.zIndex;
    }
    if (adv.width !== undefined) style.width = `${adv.width}px`;
    if (adv.height !== undefined) style.height = `${adv.height}px`;

    if (adv.position === 'absolute') {
        if (adv.absXOffset !== null) {
            if (adv.absXSide === 'right') wrapperStyle.right = `${adv.absXOffset}px`;
            else wrapperStyle.left = `${adv.absXOffset}px`;
        }
        if (adv.absYOffset !== null) {
            if (adv.absYSide === 'bottom') wrapperStyle.bottom = `${adv.absYOffset}px`;
            else wrapperStyle.top = `${adv.absYOffset}px`;
        }
    }

    const cacheAttr = adv.cache === 'no_cache' ? { 'data-mp-cache': 'no_cache' } : {};

    return { id, className, style, wrapperStyle, cacheAttr };
}

function normalizeMotionProps(props) {
    const p = props && typeof props === 'object' ? props : {};
    return {
        transformTranslateX: Number.isFinite(Number(p.transformTranslateX)) ? Number(p.transformTranslateX) : 0,
        transformTranslateY: Number.isFinite(Number(p.transformTranslateY)) ? Number(p.transformTranslateY) : 0,
        transformRotate: Number.isFinite(Number(p.transformRotate)) ? Number(p.transformRotate) : 0,
        transformScale: Number.isFinite(Number(p.transformScale)) ? Number(p.transformScale) : 1,
        transformOrigin: typeof p.transformOrigin === 'string' ? p.transformOrigin : 'center center',

        animationPreset: typeof p.animationPreset === 'string' ? p.animationPreset : '',
        animationFromTranslateX: Number.isFinite(Number(p.animationFromTranslateX)) ? Number(p.animationFromTranslateX) : 0,
        animationFromTranslateY: Number.isFinite(Number(p.animationFromTranslateY)) ? Number(p.animationFromTranslateY) : 0,
        animationFromRotate: Number.isFinite(Number(p.animationFromRotate)) ? Number(p.animationFromRotate) : 0,
        animationFromScale: Number.isFinite(Number(p.animationFromScale)) ? Number(p.animationFromScale) : 1,
        animationToTranslateX: Number.isFinite(Number(p.animationToTranslateX))
            ? Number(p.animationToTranslateX)
            : (Number.isFinite(Number(p.transformTranslateX)) ? Number(p.transformTranslateX) : 0),
        animationToTranslateY: Number.isFinite(Number(p.animationToTranslateY))
            ? Number(p.animationToTranslateY)
            : (Number.isFinite(Number(p.transformTranslateY)) ? Number(p.transformTranslateY) : 0),
        animationToRotate: Number.isFinite(Number(p.animationToRotate))
            ? Number(p.animationToRotate)
            : (Number.isFinite(Number(p.transformRotate)) ? Number(p.transformRotate) : 0),
        animationToScale: Number.isFinite(Number(p.animationToScale))
            ? Number(p.animationToScale)
            : (Number.isFinite(Number(p.transformScale)) ? Number(p.transformScale) : 1),
        animationPingPong: p.animationPingPong === true,
        animationDuration: Number.isFinite(Number(p.animationDuration)) ? Number(p.animationDuration) : 0.8,
        animationDelay: Number.isFinite(Number(p.animationDelay)) ? Number(p.animationDelay) : 0,
        animationEasing: typeof p.animationEasing === 'string' ? p.animationEasing : 'ease',
        animationIterationCount:
            p.animationIterationCount === 'infinite'
                ? 'infinite'
                : (Number.isFinite(Number(p.animationIterationCount)) ? Math.max(1, Math.round(Number(p.animationIterationCount))) : 1),
        animationFillMode: typeof p.animationFillMode === 'string' ? p.animationFillMode : 'both',

        gsapEnabled: p.gsapEnabled === true,
        gsapPreset: typeof p.gsapPreset === 'string' ? p.gsapPreset : 'fade-up',
        gsapTrigger: typeof p.gsapTrigger === 'string' ? p.gsapTrigger : 'scroll',
        gsapDuration: Number.isFinite(Number(p.gsapDuration)) ? Number(p.gsapDuration) : 0.8,
        gsapDelay: Number.isFinite(Number(p.gsapDelay)) ? Number(p.gsapDelay) : 0,
        gsapStagger: Number.isFinite(Number(p.gsapStagger)) ? Math.max(0, Math.min(2, Number(p.gsapStagger))) : 0,
        gsapEase: typeof p.gsapEase === 'string' ? p.gsapEase : 'power2.out',
        gsapStart: Number.isFinite(Number(p.gsapStart)) ? Math.max(0, Math.min(100, Math.round(Number(p.gsapStart)))) : 80,
        gsapOnce: p.gsapOnce !== false,

        gsapSplit: typeof p.gsapSplit === 'string' ? p.gsapSplit : '',
        gsapSplitStagger: Number.isFinite(Number(p.gsapSplitStagger)) ? Number(p.gsapSplitStagger) : 0.04,
    };
}

const DRAG_MIME = 'application/x-mailpurse-builder';

let activeDragData = null;

function setDragData(e, data) {
    activeDragData = data;
    try {
        e.dataTransfer.setData(DRAG_MIME, JSON.stringify(data));
        e.dataTransfer.effectAllowed = 'copyMove';
    } catch (err) {
        // ignore
    }
}

function getDragData(e) {
    try {
        const raw = e.dataTransfer.getData(DRAG_MIME) || '';
        if (raw) return JSON.parse(raw);
    } catch (err) {
        // ignore
    }
    // During `dragover` browsers hide custom MIME payloads, so fall back to
    // the in-memory copy recorded at dragstart.
    return activeDragData;
}

if (typeof document !== 'undefined') {
    document.addEventListener('dragend', () => {
        activeDragData = null;
    });
}

function findDropPosition(containerEl, clientY) {
    if (!containerEl) return { index: 0, top: 0 };
    const children = Array.from(containerEl.querySelectorAll('[data-widget-id]'));
    for (let i = 0; i < children.length; i++) {
        const rect = children[i].getBoundingClientRect();
        const mid = rect.top + rect.height / 2;
        if (clientY < mid) {
            return { index: i, top: rect.top - containerEl.getBoundingClientRect().top };
        }
    }
    if (children.length > 0) {
        const lastRect = children[children.length - 1].getBoundingClientRect();
        const containerRect = containerEl.getBoundingClientRect();
        return { index: children.length, top: lastRect.bottom - containerRect.top };
    }
    return { index: 0, top: 0 };
}

function clampSpan(v) {
    const n = Number(v);
    if (!Number.isFinite(n)) return 12;
    return Math.min(12, Math.max(1, Math.round(n)));
}

function QuickAddMenu({ onAdd }) {
    const items = [
        { type: 'heading', label: 'Heading' },
        { type: 'rich_text', label: 'Text' },
        { type: 'button', label: 'Button' },
        { type: 'icon', label: 'Icon Box' },
        { type: 'pricing_table', label: 'Pricing Table' },
        { type: 'faqs', label: 'FAQs' },
        { type: 'tabs', label: 'Tabs' },
        { type: 'image', label: 'Image' },
        { type: 'slider', label: 'Slider' },
        { type: 'marquee', label: 'Marquee' },
    ];

    return (
        <div className="mt-3 inline-flex rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
            {items.map((it) => (
                <button
                    key={it.type}
                    type="button"
                    onClick={() => onAdd(it.type)}
                    className="px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                >
                    {it.label}
                </button>
            ))}
        </div>
    );
}

function defaultSectionStyle(mode = 'page') {
    const email = mode === 'email';
    return {
        backgroundType: 'color',
        backgroundColor: '#ffffff',
        paddingY: 48,
        gap: 24,
        gapX: 24,
        gapY: 24,
        gapLinked: true,
        itemsDirection: 'row',
        itemsJustify: 'flex-start',
        itemsAlign: 'stretch',
        ...(email
            ? { maxWidthType: 'full', innerMaxWidth: 540 }
            : { maxWidth: '6xl' }),
    };
}

function defaultPage(mode = 'page') {
    return {
        builder: 'mailpurse',
        version: 1,
        meta: {},
        style: {},
        customCss: '',
        sections: [
            {
                id: uid('sec'),
                name: 'Hero',
                style: defaultSectionStyle(mode),
                columns: [
                    {
                        id: uid('col'),
                        name: 'Column 1',
                        spans: { base: 12, md: 12, lg: 12 },
                        widgets: [
                            {
                                id: uid('w'),
                                type: 'heading',
                                name: 'Heading',
                                props: { text: 'Heading', level: 2, align: 'left' },
                            },
                            {
                                id: uid('w'),
                                type: 'rich_text',
                                name: 'Text',
                                props: { html: '<p>Start writing...</p>', align: 'left' },
                            },
                        ],
                    },
                ],
            },
        ],
    };
}

function normalizePage(data, mode = 'page') {
    const normalizeFallback = () => {
        const d = defaultPage(mode);
        const sections = Array.isArray(d.sections) ? d.sections : [];
        return {
            builder: 'mailpurse',
            version: 1,
            meta: {},
            style: {},
            customCss: '',
            sections: sections.map((s) => normalizeSection(s)).filter(Boolean),
        };
    };

    if (!data || typeof data !== 'object') return normalizeFallback();
    if (data.builder !== 'mailpurse') return normalizeFallback();
    if (Number(data.version || 0) !== 1) return normalizeFallback();

    const sections = Array.isArray(data.sections) ? data.sections : [];
    const style = data.style && typeof data.style === 'object' ? data.style : {};
    const meta = data.meta && typeof data.meta === 'object' ? data.meta : {};
    const customCss = typeof data.customCss === 'string' ? data.customCss : '';
    const globals = data.globals && typeof data.globals === 'object' ? data.globals : undefined;

    return {
        builder: 'mailpurse',
        version: 1,
        meta: {
            title: typeof meta.title === 'string' ? meta.title : undefined,
            description: typeof meta.description === 'string' ? meta.description : undefined,
            keywords: typeof meta.keywords === 'string' ? meta.keywords : undefined,
            featuredImage: typeof meta.featuredImage === 'string' ? meta.featuredImage : undefined,
        },
        style: {
            marginTop: Number.isFinite(Number(style.marginTop)) ? Number(style.marginTop) : undefined,
            marginRight: Number.isFinite(Number(style.marginRight)) ? Number(style.marginRight) : undefined,
            marginBottom: Number.isFinite(Number(style.marginBottom)) ? Number(style.marginBottom) : undefined,
            marginLeft: Number.isFinite(Number(style.marginLeft)) ? Number(style.marginLeft) : undefined,
            marginLinked: typeof style.marginLinked === 'boolean' ? style.marginLinked : undefined,
            paddingTop: Number.isFinite(Number(style.paddingTop)) ? Number(style.paddingTop) : undefined,
            paddingRight: Number.isFinite(Number(style.paddingRight)) ? Number(style.paddingRight) : undefined,
            paddingBottom: Number.isFinite(Number(style.paddingBottom)) ? Number(style.paddingBottom) : undefined,
            paddingLeft: Number.isFinite(Number(style.paddingLeft)) ? Number(style.paddingLeft) : undefined,
            paddingLinked: typeof style.paddingLinked === 'boolean' ? style.paddingLinked : undefined,
            backgroundType: ['color', 'gradient', 'image'].includes(style.backgroundType) ? style.backgroundType : undefined,
            backgroundColor: typeof style.backgroundColor === 'string' ? style.backgroundColor : undefined,
            gradientFrom: typeof style.gradientFrom === 'string' ? style.gradientFrom : undefined,
            gradientTo: typeof style.gradientTo === 'string' ? style.gradientTo : undefined,
            gradientAngle: Number.isFinite(Number(style.gradientAngle)) ? Number(style.gradientAngle) : undefined,
            gradientType: ['linear', 'radial'].includes(style.gradientType) ? style.gradientType : undefined,
            gradientPosition: typeof style.gradientPosition === 'string' ? style.gradientPosition : undefined,
            backgroundImage:
                style.backgroundImage && typeof style.backgroundImage === 'object'
                    ? { url: String(style.backgroundImage.url || '') }
                    : undefined,
            backgroundSize: typeof style.backgroundSize === 'string' ? style.backgroundSize : undefined,
            backgroundPosition: typeof style.backgroundPosition === 'string' ? style.backgroundPosition : undefined,
            backgroundRepeat: typeof style.backgroundRepeat === 'string' ? style.backgroundRepeat : undefined,
            headerHeight: style.headerHeight !== undefined && style.headerHeight !== null ? style.headerHeight : undefined,
            headerSticky: typeof style.headerSticky === 'boolean' ? style.headerSticky : undefined,
            stickyHeight: style.stickyHeight !== undefined && style.stickyHeight !== null ? style.stickyHeight : undefined,
            stickyWidth: style.stickyWidth !== undefined && style.stickyWidth !== null ? style.stickyWidth : undefined,
            stickyBackgroundType: ['color', 'gradient'].includes(style.stickyBackgroundType) ? style.stickyBackgroundType : undefined,
            stickyBackgroundColor: typeof style.stickyBackgroundColor === 'string' ? style.stickyBackgroundColor : undefined,
            stickyGradientFrom: typeof style.stickyGradientFrom === 'string' ? style.stickyGradientFrom : undefined,
            stickyGradientTo: typeof style.stickyGradientTo === 'string' ? style.stickyGradientTo : undefined,
            stickyGradientFromLocation: Number.isFinite(Number(style.stickyGradientFromLocation)) ? Number(style.stickyGradientFromLocation) : undefined,
            stickyGradientToLocation: Number.isFinite(Number(style.stickyGradientToLocation)) ? Number(style.stickyGradientToLocation) : undefined,
            stickyGradientType: ['linear', 'radial'].includes(style.stickyGradientType) ? style.stickyGradientType : undefined,
            stickyGradientPosition: typeof style.stickyGradientPosition === 'string' ? style.stickyGradientPosition : undefined,
            stickyBackdropBlur: Number.isFinite(Number(style.stickyBackdropBlur)) ? Number(style.stickyBackdropBlur) : undefined,
            stickyBorderStyle: typeof style.stickyBorderStyle === 'string' ? style.stickyBorderStyle : undefined,
            stickyBorderColor: typeof style.stickyBorderColor === 'string' ? style.stickyBorderColor : undefined,
            stickyBorderWidthLinked: typeof style.stickyBorderWidthLinked === 'boolean' ? style.stickyBorderWidthLinked : undefined,
            stickyBorderWidthTop: Number.isFinite(Number(style.stickyBorderWidthTop)) ? Number(style.stickyBorderWidthTop) : undefined,
            stickyBorderWidthRight: Number.isFinite(Number(style.stickyBorderWidthRight)) ? Number(style.stickyBorderWidthRight) : undefined,
            stickyBorderWidthBottom: Number.isFinite(Number(style.stickyBorderWidthBottom)) ? Number(style.stickyBorderWidthBottom) : undefined,
            stickyBorderWidthLeft: Number.isFinite(Number(style.stickyBorderWidthLeft)) ? Number(style.stickyBorderWidthLeft) : undefined,
            stickyBorderRadiusLinked: typeof style.stickyBorderRadiusLinked === 'boolean' ? style.stickyBorderRadiusLinked : undefined,
            stickyBorderRadiusTop: Number.isFinite(Number(style.stickyBorderRadiusTop)) ? Number(style.stickyBorderRadiusTop) : undefined,
            stickyBorderRadiusRight: Number.isFinite(Number(style.stickyBorderRadiusRight)) ? Number(style.stickyBorderRadiusRight) : undefined,
            stickyBorderRadiusBottom: Number.isFinite(Number(style.stickyBorderRadiusBottom)) ? Number(style.stickyBorderRadiusBottom) : undefined,
            stickyBorderRadiusLeft: Number.isFinite(Number(style.stickyBorderRadiusLeft)) ? Number(style.stickyBorderRadiusLeft) : undefined,
            stickyTextColor: typeof style.stickyTextColor === 'string' ? style.stickyTextColor : undefined,
            stickyLinkColor: typeof style.stickyLinkColor === 'string' ? style.stickyLinkColor : undefined,
            stickyMarginLinked: typeof style.stickyMarginLinked === 'boolean' ? style.stickyMarginLinked : undefined,
            stickyMarginTop: Number.isFinite(Number(style.stickyMarginTop)) ? Number(style.stickyMarginTop) : undefined,
            stickyMarginRight: Number.isFinite(Number(style.stickyMarginRight)) ? Number(style.stickyMarginRight) : undefined,
            stickyMarginBottom: Number.isFinite(Number(style.stickyMarginBottom)) ? Number(style.stickyMarginBottom) : undefined,
            stickyMarginLeft: Number.isFinite(Number(style.stickyMarginLeft)) ? Number(style.stickyMarginLeft) : undefined,
        },
        customCss,
        globals,
        sections: sections.map((s) => normalizeSection(s)).filter(Boolean),
    };
}

function normalizeLengthValue(value, fallback) {
    if (typeof value === 'number' && Number.isFinite(value)) return value;
    if (typeof value === 'string') {
        const trimmed = value.trim();
        if (trimmed === '') return fallback;
        const n = Number(trimmed);
        if (Number.isFinite(n)) return n;
        const parsed = parseUnit(trimmed);
        if (Number.isFinite(parsed.value)) {
            return parsed.unit === 'px' ? parsed.value : { value: parsed.value, unit: parsed.unit };
        }
        return trimmed;
    }
    if (value && typeof value === 'object' && !Array.isArray(value)) {
        const n = Number(value.value);
        const unit = typeof value.unit === 'string' ? value.unit : '';
        if (Number.isFinite(n)) {
            return unit === 'px' || unit === '' ? n : { value: n, unit };
        }
        if (value.value === '' && unit === 'auto') return { value: '', unit: 'auto' };
    }
    return fallback;
}

function normalizeSection(s) {
    if (!s || typeof s !== 'object') return null;
    const style = s.style && typeof s.style === 'object' ? s.style : {};
    const columns = Array.isArray(s.columns) ? s.columns : [];
    const responsiveStyle = {};
    for (const key of Object.keys(style)) {
        if (/Tablet$|Mobile$/.test(key)) responsiveStyle[key] = style[key];
    }

    const paddingTop = Number.isFinite(Number(style.paddingTop))
        ? Number(style.paddingTop)
        : Number.isFinite(Number(style.paddingY))
          ? Number(style.paddingY)
          : 48;
    const paddingRight = Number.isFinite(Number(style.paddingRight))
        ? Number(style.paddingRight)
        : Number.isFinite(Number(style.paddingX))
          ? Number(style.paddingX)
          : 0;
    const paddingBottom = Number.isFinite(Number(style.paddingBottom))
        ? Number(style.paddingBottom)
        : Number.isFinite(Number(style.paddingY))
          ? Number(style.paddingY)
          : 48;
    const paddingLeft = Number.isFinite(Number(style.paddingLeft))
        ? Number(style.paddingLeft)
        : Number.isFinite(Number(style.paddingX))
          ? Number(style.paddingX)
          : 0;
    const paddingLinked = typeof style.paddingLinked === 'boolean'
        ? style.paddingLinked
        : inferLinked4(
            toFiniteNumberOrUndefined(paddingTop),
            toFiniteNumberOrUndefined(paddingRight),
            toFiniteNumberOrUndefined(paddingBottom),
            toFiniteNumberOrUndefined(paddingLeft),
            true
        );

    return {
        id: String(s.id || uid('sec')),
        name: typeof s.name === 'string' ? s.name : 'Section',
        advanced: normalizeAdvanced(s.advanced),
        ...(s.pricingMode === 'switcher' || s.pricingMode === 'tabs' ? { pricingMode: s.pricingMode } : {}),
        ...(Array.isArray(s.pricingTabs) ? {
            pricingTabs: s.pricingTabs
                .filter((t) => t && typeof t === 'object')
                .map((t) => ({
                    id: typeof t.id === 'string' ? t.id : uid('pt'),
                    text: typeof t.text === 'string' ? t.text : '',
                    badge: typeof t.badge === 'string' ? t.badge : '',
                })),
        } : {}),
        ...(s.pricingStyle && typeof s.pricingStyle === 'object' ? { pricingStyle: s.pricingStyle } : {}),
        style: {
            backgroundType: ['color', 'gradient', 'image'].includes(style.backgroundType) ? style.backgroundType : 'color',
            backgroundColor: typeof style.backgroundColor === 'string' ? style.backgroundColor : '#ffffff',
            darkMode: typeof style.darkMode === 'boolean' ? style.darkMode : false,
            gradientFrom: typeof style.gradientFrom === 'string' ? style.gradientFrom : '#111827',
            gradientTo: typeof style.gradientTo === 'string' ? style.gradientTo : '#3b82f6',
            gradientAngle: Number.isFinite(Number(style.gradientAngle)) ? Number(style.gradientAngle) : 135,
            gradientType: ['linear', 'radial'].includes(style.gradientType) ? style.gradientType : 'linear',
            gradientPosition: typeof style.gradientPosition === 'string' && style.gradientPosition ? style.gradientPosition : 'top center',
            backdropBlur: Number.isFinite(Number(style.backdropBlur)) ? Number(style.backdropBlur) : 0,
            backgroundImage: style.backgroundImage && typeof style.backgroundImage === 'object' ? { url: String(style.backgroundImage.url || '') } : { url: '' },
            backgroundSize: typeof style.backgroundSize === 'string' && style.backgroundSize ? style.backgroundSize : 'cover',
            backgroundPosition: typeof style.backgroundPosition === 'string' && style.backgroundPosition ? style.backgroundPosition : 'center',
            backgroundRepeat: typeof style.backgroundRepeat === 'string' && style.backgroundRepeat ? style.backgroundRepeat : 'no-repeat',
            backgroundPreset: style.backgroundPreset && typeof style.backgroundPreset === 'object' ? {
                key: typeof style.backgroundPreset.key === 'string' ? style.backgroundPreset.key : 'css-none',
                category: typeof style.backgroundPreset.category === 'string' ? style.backgroundPreset.category : 'css',
                params: style.backgroundPreset.params && typeof style.backgroundPreset.params === 'object' ? style.backgroundPreset.params : {},
            } : { key: 'css-none', category: 'css', params: {} },
            backgroundVideoType: ['youtube', 'mp4'].includes(style.backgroundVideoType) ? style.backgroundVideoType : '',
            youtubeId: typeof style.youtubeId === 'string' ? style.youtubeId : '',
            mp4Url: typeof style.mp4Url === 'string' ? style.mp4Url : '',
            paddingTop,
            paddingRight,
            paddingBottom,
            paddingLeft,
            paddingLinked,
            paddingY: Number.isFinite(Number(style.paddingY)) ? Number(style.paddingY) : undefined,
            paddingX: Number.isFinite(Number(style.paddingX)) ? Number(style.paddingX) : undefined,
            gap: Number.isFinite(Number(style.gap)) ? Number(style.gap) : 24,
            gapX: Number.isFinite(Number(style.gapX)) ? Number(style.gapX) : (Number.isFinite(Number(style.gap)) ? Number(style.gap) : 24),
            gapY: Number.isFinite(Number(style.gapY)) ? Number(style.gapY) : (Number.isFinite(Number(style.gap)) ? Number(style.gap) : 24),
            gapLinked: typeof style.gapLinked === 'boolean' ? style.gapLinked : true,
            itemsDirection: ['row', 'column', 'row-reverse', 'column-reverse'].includes(style.itemsDirection)
                ? style.itemsDirection
                : 'row',
            itemsJustify: ['flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'].includes(style.itemsJustify)
                ? style.itemsJustify
                : 'flex-start',
            itemsAlign: ['stretch', 'flex-start', 'center', 'flex-end', 'baseline'].includes(style.itemsAlign) ? style.itemsAlign : 'stretch',
            columnsPerRow: Number.isFinite(Number(style.columnsPerRow)) ? Math.max(1, Math.min(6, Math.round(Number(style.columnsPerRow)))) : undefined,
            maxWidthType: style.maxWidthType === 'full' ? 'full' : (style.maxWidth === 'full' ? 'full' : 'boxed'),
            boxedWidth: normalizeLengthValue(style.boxedWidth, style.maxWidth === '7xl' ? 1280 : 1152),
            innerMaxWidth: normalizeLengthValue(style.innerMaxWidth, undefined),
            marginTop: Number.isFinite(Number(style.marginTop)) ? Number(style.marginTop) : 0,
            marginRight: Number.isFinite(Number(style.marginRight)) ? Number(style.marginRight) : 0,
            marginBottom: Number.isFinite(Number(style.marginBottom)) ? Number(style.marginBottom) : 0,
            marginLeft: Number.isFinite(Number(style.marginLeft)) ? Number(style.marginLeft) : 0,
            marginLinked: typeof style.marginLinked === 'boolean' ? style.marginLinked : false,

            transformTranslateX: Number.isFinite(Number(style.transformTranslateX)) ? Number(style.transformTranslateX) : 0,
            transformTranslateY: Number.isFinite(Number(style.transformTranslateY)) ? Number(style.transformTranslateY) : 0,
            transformRotate: Number.isFinite(Number(style.transformRotate)) ? Number(style.transformRotate) : 0,
            transformScale: Number.isFinite(Number(style.transformScale)) ? Number(style.transformScale) : 1,
            transformOrigin: typeof style.transformOrigin === 'string' ? style.transformOrigin : 'center center',

            animationPreset: typeof style.animationPreset === 'string' ? style.animationPreset : '',
            animationFromTranslateX: Number.isFinite(Number(style.animationFromTranslateX)) ? Number(style.animationFromTranslateX) : 0,
            animationFromTranslateY: Number.isFinite(Number(style.animationFromTranslateY)) ? Number(style.animationFromTranslateY) : 0,
            animationFromRotate: Number.isFinite(Number(style.animationFromRotate)) ? Number(style.animationFromRotate) : 0,
            animationFromScale: Number.isFinite(Number(style.animationFromScale)) ? Number(style.animationFromScale) : 1,
            animationToTranslateX: Number.isFinite(Number(style.animationToTranslateX))
                ? Number(style.animationToTranslateX)
                : (Number.isFinite(Number(style.transformTranslateX)) ? Number(style.transformTranslateX) : 0),
            animationToTranslateY: Number.isFinite(Number(style.animationToTranslateY))
                ? Number(style.animationToTranslateY)
                : (Number.isFinite(Number(style.transformTranslateY)) ? Number(style.transformTranslateY) : 0),
            animationToRotate: Number.isFinite(Number(style.animationToRotate))
                ? Number(style.animationToRotate)
                : (Number.isFinite(Number(style.transformRotate)) ? Number(style.transformRotate) : 0),
            animationToScale: Number.isFinite(Number(style.animationToScale))
                ? Number(style.animationToScale)
                : (Number.isFinite(Number(style.transformScale)) ? Number(style.transformScale) : 1),
            animationPingPong: style.animationPingPong === true,
            animationDuration: Number.isFinite(Number(style.animationDuration)) ? Number(style.animationDuration) : 0.8,
            animationDelay: Number.isFinite(Number(style.animationDelay)) ? Number(style.animationDelay) : 0,
            animationEasing: typeof style.animationEasing === 'string' ? style.animationEasing : 'ease',
            animationIterationCount:
                style.animationIterationCount === 'infinite'
                    ? 'infinite'
                    : (Number.isFinite(Number(style.animationIterationCount)) ? Math.max(1, Math.round(Number(style.animationIterationCount))) : 1),
            animationFillMode: typeof style.animationFillMode === 'string' ? style.animationFillMode : 'both',

            gsapEnabled: style.gsapEnabled === true,
            gsapPreset: typeof style.gsapPreset === 'string' ? style.gsapPreset : 'fade-up',
            gsapTrigger: typeof style.gsapTrigger === 'string' ? style.gsapTrigger : 'scroll',
            gsapDuration: Number.isFinite(Number(style.gsapDuration)) ? Number(style.gsapDuration) : 0.8,
            gsapDelay: Number.isFinite(Number(style.gsapDelay)) ? Number(style.gsapDelay) : 0,
            gsapEase: typeof style.gsapEase === 'string' ? style.gsapEase : 'power2.out',
            gsapStart: Number.isFinite(Number(style.gsapStart)) ? Math.max(0, Math.min(100, Math.round(Number(style.gsapStart)))) : 80,
            gsapOnce: style.gsapOnce !== false,
            gsapStagger: Number.isFinite(Number(style.gsapStagger)) ? Math.max(0, Math.min(2, Number(style.gsapStagger))) : 0,
            overflowX: ['visible', 'hidden', 'scroll', 'auto', 'clip'].includes(style.overflowX) ? style.overflowX : '',
            overflowY: ['visible', 'hidden', 'scroll', 'auto', 'clip'].includes(style.overflowY) ? style.overflowY : '',

            height: Number.isFinite(Number(style.height)) ? Number(style.height) : (Number.isFinite(Number(style.minHeight)) ? Number(style.minHeight) : undefined),

            borderStyle: ['solid', 'dashed', 'dotted'].includes(style.borderStyle) ? style.borderStyle : '',
            borderColor: typeof style.borderColor === 'string' ? style.borderColor : '',
            borderWidthTop: Number.isFinite(Number(style.borderWidthTop)) ? Number(style.borderWidthTop) : 0,
            borderWidthRight: Number.isFinite(Number(style.borderWidthRight)) ? Number(style.borderWidthRight) : 0,
            borderWidthBottom: Number.isFinite(Number(style.borderWidthBottom)) ? Number(style.borderWidthBottom) : 0,
            borderWidthLeft: Number.isFinite(Number(style.borderWidthLeft)) ? Number(style.borderWidthLeft) : 0,
            borderWidthLinked: typeof style.borderWidthLinked === 'boolean' ? style.borderWidthLinked : true,
            borderRadius: Number.isFinite(Number(style.borderRadius)) ? Number(style.borderRadius) : undefined,
            borderRadiusTop: Number.isFinite(Number(style.borderRadiusTop)) ? Number(style.borderRadiusTop) : undefined,
            borderRadiusRight: Number.isFinite(Number(style.borderRadiusRight)) ? Number(style.borderRadiusRight) : undefined,
            borderRadiusBottom: Number.isFinite(Number(style.borderRadiusBottom)) ? Number(style.borderRadiusBottom) : undefined,
            borderRadiusLeft: Number.isFinite(Number(style.borderRadiusLeft)) ? Number(style.borderRadiusLeft) : undefined,
            borderRadiusLinked: typeof style.borderRadiusLinked === 'boolean' ? style.borderRadiusLinked : undefined,

            stickyBehavior: ['scroll', 'up-scroll'].includes(style.stickyBehavior) ? style.stickyBehavior : 'none',
            stickyOffset: Number.isFinite(Number(style.stickyOffset)) ? Number(style.stickyOffset) : 0,
            stickyBackgroundColor: typeof style.stickyBackgroundColor === 'string' ? style.stickyBackgroundColor : '',
            stickyBorderColor: typeof style.stickyBorderColor === 'string' ? style.stickyBorderColor : '',
            stickyTextColor: typeof style.stickyTextColor === 'string' ? style.stickyTextColor : '',

            stackedSticky: style.stackedSticky === true,
            stackedZIndex: Number.isFinite(Number(style.stackedZIndex)) ? Math.max(0, Math.round(Number(style.stackedZIndex))) : 0,

            gradientAnimate: style.gradientAnimate === true,
            gridOverlay: style.gridOverlay === true,
            gridSize: Number.isFinite(Number(style.gridSize)) ? Math.max(8, Math.min(200, Number(style.gridSize))) : 40,
            gridColor: typeof style.gridColor === 'string' ? style.gridColor : 'rgba(0,0,0,0.15)',
            gridOpacity: Number.isFinite(Number(style.gridOpacity)) ? Math.max(0, Math.min(1, Number(style.gridOpacity))) : 0.5,
            ...responsiveStyle,
        },
        columns: columns.map((c) => normalizeColumn(c)).filter(Boolean),
    };
}

function normalizeColumn(c) {
    if (!c || typeof c !== 'object') return null;
    const spans = c.spans && typeof c.spans === 'object' ? c.spans : {};
    const widgets = Array.isArray(c.widgets) ? c.widgets : [];

    const style = c.style && typeof c.style === 'object' ? c.style : undefined;

    return {
        id: String(c.id || uid('col')),
        name: typeof c.name === 'string' ? c.name : 'Column',
        advanced: normalizeAdvanced(c.advanced),
        spans: {
            base: clampSpan(spans.base ?? 12),
            md: clampSpan(spans.md ?? 12),
            lg: clampSpan(spans.lg ?? 12),
        },
        ...(style ? { style } : {}),
        widgets: widgets.map((w) => normalizeWidget(w)).filter(Boolean),
    };
}

function normalizeWidgetCore(w) {
    if (!w || typeof w !== 'object') return null;
    const type = String(w.type || '');
    const props = w.props && typeof w.props === 'object' ? w.props : {};
    const name = typeof w.name === 'string' ? w.name : '';
    const advanced = normalizeAdvanced(w.advanced);

    const cssLenOr = (v, fallback) => {
        const ok = (x) => {
            if (x === null || x === undefined || x === '' || typeof x === 'boolean') return undefined;
            if (typeof x === 'object' && !Array.isArray(x)) {
                const n = Number(x.value);
                return Number.isFinite(n) && typeof x.unit === 'string' && x.unit !== '' ? x : undefined;
            }
            const n = Number(x);
            if (Number.isFinite(n)) return n;
            if (typeof x === 'string' && /^\d+(?:\.\d+)?(px|em|rem|%)$/.test(x.trim())) return x.trim();
            return undefined;
        };
        const a = ok(v);
        return a !== undefined ? a : ok(fallback);
    };
    const motion = normalizeMotionProps(props);

    if (!['heading', 'rich_text', 'image', 'slider', 'marquee', 'grid', 'button', 'button_group', 'badge', 'icon', 'pricing_table', 'faqs', 'tabs', 'nav_menu', 'link', 'divider', 'spacer', 'logo', 'rating', 'text_timeline', 'social_icons', 'social_proof', 'newsletter_form', 'login_form', 'register_form', 'copyright', 'form_text', 'form_email', 'form_textarea', 'form_select', 'form_checkbox', 'form_radio', 'form_submit', 'ai_prompt_input', 'testimonials', 'testimonial'].includes(type)) {
        return null;
    }

    if (type === 'heading') {
        const html = typeof props.html === 'string' ? props.html : '';
        const paddingTop = Number.isFinite(Number(props.paddingTop))
            ? Number(props.paddingTop)
            : Number.isFinite(Number(props.paddingY))
              ? Number(props.paddingY)
              : undefined;
        const paddingRight = Number.isFinite(Number(props.paddingRight))
            ? Number(props.paddingRight)
            : Number.isFinite(Number(props.paddingX))
              ? Number(props.paddingX)
              : undefined;
        const paddingBottom = Number.isFinite(Number(props.paddingBottom))
            ? Number(props.paddingBottom)
            : Number.isFinite(Number(props.paddingY))
              ? Number(props.paddingY)
              : undefined;
        const paddingLeft = Number.isFinite(Number(props.paddingLeft))
            ? Number(props.paddingLeft)
            : Number.isFinite(Number(props.paddingX))
              ? Number(props.paddingX)
              : undefined;

        const paddingLinked = typeof props.paddingLinked === 'boolean'
            ? props.paddingLinked
            : inferLinked4(
                toFiniteNumberOrUndefined(paddingTop),
                toFiniteNumberOrUndefined(paddingRight),
                toFiniteNumberOrUndefined(paddingBottom),
                toFiniteNumberOrUndefined(paddingLeft),
                true
            );

        const backgroundType = ['color', 'classic', 'gradient'].includes(props.backgroundType)
            ? props.backgroundType
            : (['color', 'classic', 'gradient'].includes(props.buttonBackgroundType) ? props.buttonBackgroundType : 'classic');
        const gradientType = ['linear', 'radial'].includes(props.gradientType)
            ? props.gradientType
            : (['linear', 'radial'].includes(props.buttonGradientType) ? props.buttonGradientType : 'linear');
        const gradientPosition = typeof props.gradientPosition === 'string'
            ? props.gradientPosition
            : (typeof props.buttonGradientPosition === 'string' ? props.buttonGradientPosition : '');
        const gradientFromLocation = Number.isFinite(Number(props.gradientFromLocation)) ? Number(props.gradientFromLocation) : undefined;
        const gradientToLocation = Number.isFinite(Number(props.gradientToLocation)) ? Number(props.gradientToLocation) : undefined;

        const itemPaddingX = Number.isFinite(Number(props.itemPaddingX)) ? Number(props.itemPaddingX) : undefined;
        const itemPaddingY = Number.isFinite(Number(props.itemPaddingY)) ? Number(props.itemPaddingY) : undefined;

        const itemPaddingTop = Number.isFinite(Number(props.itemPaddingTop))
            ? Number(props.itemPaddingTop)
            : (Number.isFinite(Number(props.itemPaddingY)) ? Number(props.itemPaddingY) : undefined);
        const itemPaddingRight = Number.isFinite(Number(props.itemPaddingRight))
            ? Number(props.itemPaddingRight)
            : (Number.isFinite(Number(props.itemPaddingX)) ? Number(props.itemPaddingX) : undefined);
        const itemPaddingBottom = Number.isFinite(Number(props.itemPaddingBottom))
            ? Number(props.itemPaddingBottom)
            : (Number.isFinite(Number(props.itemPaddingY)) ? Number(props.itemPaddingY) : undefined);
        const itemPaddingLeft = Number.isFinite(Number(props.itemPaddingLeft))
            ? Number(props.itemPaddingLeft)
            : (Number.isFinite(Number(props.itemPaddingX)) ? Number(props.itemPaddingX) : undefined);

        const itemPaddingLinked = typeof props.itemPaddingLinked === 'boolean'
            ? props.itemPaddingLinked
            : inferLinked4(
                toFiniteNumberOrUndefined(itemPaddingTop),
                toFiniteNumberOrUndefined(itemPaddingRight),
                toFiniteNumberOrUndefined(itemPaddingBottom),
                toFiniteNumberOrUndefined(itemPaddingLeft),
                true
            );

        const marginTop = Number.isFinite(Number(props.marginTop)) ? Number(props.marginTop) : undefined;
        const marginRight = Number.isFinite(Number(props.marginRight)) ? Number(props.marginRight) : undefined;
        const marginBottom = Number.isFinite(Number(props.marginBottom)) ? Number(props.marginBottom) : undefined;
        const marginLeft = Number.isFinite(Number(props.marginLeft)) ? Number(props.marginLeft) : undefined;

        const width = Number.isFinite(Number(props.width)) ? Number(props.width) : undefined;
        const height = Number.isFinite(Number(props.height)) ? Number(props.height) : undefined;

        const borderWidthTop = Number.isFinite(Number(props.borderWidthTop))
            ? Number(props.borderWidthTop)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;
        const borderWidthRight = Number.isFinite(Number(props.borderWidthRight))
            ? Number(props.borderWidthRight)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;
        const borderWidthBottom = Number.isFinite(Number(props.borderWidthBottom))
            ? Number(props.borderWidthBottom)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;
        const borderWidthLeft = Number.isFinite(Number(props.borderWidthLeft))
            ? Number(props.borderWidthLeft)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;

        const borderRadiusTop = Number.isFinite(Number(props.borderRadiusTop))
            ? Number(props.borderRadiusTop)
            : Number.isFinite(Number(props.borderRadius))
              ? Number(props.borderRadius)
              : undefined;
        const borderRadiusRight = Number.isFinite(Number(props.borderRadiusRight))
            ? Number(props.borderRadiusRight)
            : Number.isFinite(Number(props.borderRadius))
              ? Number(props.borderRadius)
              : undefined;
        const borderRadiusBottom = Number.isFinite(Number(props.borderRadiusBottom))
            ? Number(props.borderRadiusBottom)
            : Number.isFinite(Number(props.borderRadius))
              ? Number(props.borderRadius)
              : undefined;
        const borderRadiusLeft = Number.isFinite(Number(props.borderRadiusLeft))
            ? Number(props.borderRadiusLeft)
            : Number.isFinite(Number(props.borderRadius))
              ? Number(props.borderRadius)
              : undefined;

        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Heading',
            advanced,
            props: {
                html,
                text: typeof props.text === 'string' ? props.text : (html && html.trim() ? '' : 'Heading'),
                level: Number.isFinite(Number(props.level)) ? Number(props.level) : 2,
                align: ['left', 'center', 'right', 'justify'].includes(props.align) ? props.align : 'left',
                color: typeof props.color === 'string' ? props.color : '',
                highlightMode: ['none', 'color', 'gradient'].includes(props.highlightMode) ? props.highlightMode : 'none',
                highlightColor: typeof props.highlightColor === 'string' ? props.highlightColor : '',
                highlightGradientFrom: typeof props.highlightGradientFrom === 'string' ? props.highlightGradientFrom : '',
                highlightGradientTo: typeof props.highlightGradientTo === 'string' ? props.highlightGradientTo : '',
                highlightAnimate: typeof props.highlightAnimate === 'boolean' ? props.highlightAnimate : false,
                highlightAnimationDuration: Number.isFinite(Number(props.highlightAnimationDuration)) ? Number(props.highlightAnimationDuration) : undefined,
                highlightFontFamily: typeof props.highlightFontFamily === 'string' ? props.highlightFontFamily : '',
                highlightFontSize: Number.isFinite(Number(props.highlightFontSize)) ? Number(props.highlightFontSize) : undefined,
                highlightFontWeight: typeof props.highlightFontWeight === 'string' ? props.highlightFontWeight : '',
                highlightLineHeight: Number.isFinite(Number(props.highlightLineHeight)) ? Number(props.highlightLineHeight) : undefined,
                highlightLetterSpacing: Number.isFinite(Number(props.highlightLetterSpacing)) ? Number(props.highlightLetterSpacing) : undefined,
                highlightWordSpacing: Number.isFinite(Number(props.highlightWordSpacing)) ? Number(props.highlightWordSpacing) : undefined,
                fontFamily: typeof props.fontFamily === 'string' ? props.fontFamily : '',
                fontSize: Number.isFinite(Number(props.fontSize)) ? Number(props.fontSize) : undefined,
                fontWeight: typeof props.fontWeight === 'string' ? props.fontWeight : '',
                lineHeight: Number.isFinite(Number(props.lineHeight)) ? Number(props.lineHeight) : undefined,
                letterSpacing: Number.isFinite(Number(props.letterSpacing)) ? Number(props.letterSpacing) : undefined,
                wordSpacing: Number.isFinite(Number(props.wordSpacing)) ? Number(props.wordSpacing) : undefined,
                textShadowColor: typeof props.textShadowColor === 'string' ? props.textShadowColor : '',
                textShadowX: Number.isFinite(Number(props.textShadowX)) ? Number(props.textShadowX) : undefined,
                textShadowY: Number.isFinite(Number(props.textShadowY)) ? Number(props.textShadowY) : undefined,
                textShadowBlur: Number.isFinite(Number(props.textShadowBlur)) ? Number(props.textShadowBlur) : undefined,
                paddingLinked,
                paddingTop,
                paddingRight,
                paddingBottom,
                paddingLeft,
                marginLinked: typeof props.marginLinked === 'boolean'
                    ? props.marginLinked
                    : inferLinked4(
                        toFiniteNumberOrUndefined(marginTop),
                        toFiniteNumberOrUndefined(marginRight),
                        toFiniteNumberOrUndefined(marginBottom),
                        toFiniteNumberOrUndefined(marginLeft),
                        true
                    ),
                marginTop,
                marginRight,
                marginBottom,
                marginLeft,
                borderStyle: typeof props.borderStyle === 'string' ? props.borderStyle : '',
                borderColor: typeof props.borderColor === 'string' ? props.borderColor : '',
                borderWidthLinked: typeof props.borderWidthLinked === 'boolean'
                    ? props.borderWidthLinked
                    : inferLinked4(
                        toFiniteNumberOrUndefined(borderWidthTop),
                        toFiniteNumberOrUndefined(borderWidthRight),
                        toFiniteNumberOrUndefined(borderWidthBottom),
                        toFiniteNumberOrUndefined(borderWidthLeft),
                        true
                    ),
                borderWidthTop,
                borderWidthRight,
                borderWidthBottom,
                borderWidthLeft,
                borderRadiusLinked: typeof props.borderRadiusLinked === 'boolean'
                    ? props.borderRadiusLinked
                    : inferLinked4(
                        toFiniteNumberOrUndefined(borderRadiusTop),
                        toFiniteNumberOrUndefined(borderRadiusRight),
                        toFiniteNumberOrUndefined(borderRadiusBottom),
                        toFiniteNumberOrUndefined(borderRadiusLeft),
                        true
                    ),
                borderRadiusTop,
                borderRadiusRight,
                borderRadiusBottom,
                borderRadiusLeft,
                backgroundType,
                backgroundColor: typeof props.backgroundColor === 'string' ? props.backgroundColor : '',
                gradientFrom: typeof props.gradientFrom === 'string' ? props.gradientFrom : '',
                gradientTo: typeof props.gradientTo === 'string' ? props.gradientTo : '',
                gradientFromLocation,
                gradientToLocation,
                gradientType,
                gradientPosition,
                width,
                height,
                ...motion,
            },
        };
    }

    if (type === 'rich_text') {
        const paddingTop = Number.isFinite(Number(props.paddingTop))
            ? Number(props.paddingTop)
            : Number.isFinite(Number(props.paddingY))
              ? Number(props.paddingY)
              : undefined;
        const paddingRight = Number.isFinite(Number(props.paddingRight))
            ? Number(props.paddingRight)
            : Number.isFinite(Number(props.paddingX))
              ? Number(props.paddingX)
              : undefined;
        const paddingBottom = Number.isFinite(Number(props.paddingBottom))
            ? Number(props.paddingBottom)
            : Number.isFinite(Number(props.paddingY))
              ? Number(props.paddingY)
              : undefined;
        const paddingLeft = Number.isFinite(Number(props.paddingLeft))
            ? Number(props.paddingLeft)
            : Number.isFinite(Number(props.paddingX))
              ? Number(props.paddingX)
              : undefined;

        const paddingLinked = typeof props.paddingLinked === 'boolean'
            ? props.paddingLinked
            : inferLinked4(
                toFiniteNumberOrUndefined(paddingTop),
                toFiniteNumberOrUndefined(paddingRight),
                toFiniteNumberOrUndefined(paddingBottom),
                toFiniteNumberOrUndefined(paddingLeft),
                true
            );

        const itemPaddingX = Number.isFinite(Number(props.itemPaddingX)) ? Number(props.itemPaddingX) : undefined;
        const itemPaddingY = Number.isFinite(Number(props.itemPaddingY)) ? Number(props.itemPaddingY) : undefined;

        const itemPaddingTop = Number.isFinite(Number(props.itemPaddingTop))
            ? Number(props.itemPaddingTop)
            : itemPaddingY;
        const itemPaddingRight = Number.isFinite(Number(props.itemPaddingRight))
            ? Number(props.itemPaddingRight)
            : itemPaddingX;
        const itemPaddingBottom = Number.isFinite(Number(props.itemPaddingBottom))
            ? Number(props.itemPaddingBottom)
            : itemPaddingY;
        const itemPaddingLeft = Number.isFinite(Number(props.itemPaddingLeft))
            ? Number(props.itemPaddingLeft)
            : itemPaddingX;

        const itemPaddingLinked = typeof props.itemPaddingLinked === 'boolean'
            ? props.itemPaddingLinked
            : inferLinked4(
                toFiniteNumberOrUndefined(itemPaddingTop),
                toFiniteNumberOrUndefined(itemPaddingRight),
                toFiniteNumberOrUndefined(itemPaddingBottom),
                toFiniteNumberOrUndefined(itemPaddingLeft),
                true
            );

        const backgroundType = ['color', 'classic', 'gradient'].includes(props.backgroundType) ? props.backgroundType : 'classic';
        const gradientType = ['linear', 'radial'].includes(props.gradientType) ? props.gradientType : 'linear';
        const gradientPosition = typeof props.gradientPosition === 'string' ? props.gradientPosition : '';
        const gradientFromLocation = Number.isFinite(Number(props.gradientFromLocation)) ? Number(props.gradientFromLocation) : undefined;
        const gradientToLocation = Number.isFinite(Number(props.gradientToLocation)) ? Number(props.gradientToLocation) : undefined;

        const marginTop = Number.isFinite(Number(props.marginTop)) ? Number(props.marginTop) : undefined;
        const marginRight = Number.isFinite(Number(props.marginRight)) ? Number(props.marginRight) : undefined;
        const marginBottom = Number.isFinite(Number(props.marginBottom)) ? Number(props.marginBottom) : undefined;
        const marginLeft = Number.isFinite(Number(props.marginLeft)) ? Number(props.marginLeft) : undefined;

        const borderWidthTop = Number.isFinite(Number(props.borderWidthTop))
            ? Number(props.borderWidthTop)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;
        const borderWidthRight = Number.isFinite(Number(props.borderWidthRight))
            ? Number(props.borderWidthRight)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;
        const borderWidthBottom = Number.isFinite(Number(props.borderWidthBottom))
            ? Number(props.borderWidthBottom)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;
        const borderWidthLeft = Number.isFinite(Number(props.borderWidthLeft))
            ? Number(props.borderWidthLeft)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;

        const borderRadiusTop = Number.isFinite(Number(props.borderRadiusTop))
            ? Number(props.borderRadiusTop)
            : Number.isFinite(Number(props.borderRadius))
              ? Number(props.borderRadius)
              : undefined;
        const borderRadiusRight = Number.isFinite(Number(props.borderRadiusRight))
            ? Number(props.borderRadiusRight)
            : Number.isFinite(Number(props.borderRadius))
              ? Number(props.borderRadius)
              : undefined;
        const borderRadiusBottom = Number.isFinite(Number(props.borderRadiusBottom))
            ? Number(props.borderRadiusBottom)
            : Number.isFinite(Number(props.borderRadius))
              ? Number(props.borderRadius)
              : undefined;
        const borderRadiusLeft = Number.isFinite(Number(props.borderRadiusLeft))
            ? Number(props.borderRadiusLeft)
            : Number.isFinite(Number(props.borderRadius))
              ? Number(props.borderRadius)
              : undefined;

        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Text',
            advanced,
            props: {
                html: typeof props.html === 'string' ? props.html : '<p></p>',
                align: ['left', 'center', 'right', 'justify'].includes(props.align) ? props.align : 'left',
                color: typeof props.color === 'string' ? props.color : '',
                linkColor: typeof props.linkColor === 'string' ? props.linkColor : '',
                highlightMode: ['none', 'color', 'gradient'].includes(props.highlightMode) ? props.highlightMode : 'none',
                highlightColor: typeof props.highlightColor === 'string' ? props.highlightColor : '',
                highlightGradientFrom: typeof props.highlightGradientFrom === 'string' ? props.highlightGradientFrom : '',
                highlightGradientTo: typeof props.highlightGradientTo === 'string' ? props.highlightGradientTo : '',
                highlightAnimate: typeof props.highlightAnimate === 'boolean' ? props.highlightAnimate : false,
                highlightAnimationDuration: Number.isFinite(Number(props.highlightAnimationDuration)) ? Number(props.highlightAnimationDuration) : undefined,
                highlightFontFamily: typeof props.highlightFontFamily === 'string' ? props.highlightFontFamily : '',
                highlightFontSize: Number.isFinite(Number(props.highlightFontSize)) ? Number(props.highlightFontSize) : undefined,
                highlightFontWeight: typeof props.highlightFontWeight === 'string' ? props.highlightFontWeight : '',
                highlightLineHeight: Number.isFinite(Number(props.highlightLineHeight)) ? Number(props.highlightLineHeight) : undefined,
                highlightLetterSpacing: Number.isFinite(Number(props.highlightLetterSpacing)) ? Number(props.highlightLetterSpacing) : undefined,
                highlightWordSpacing: Number.isFinite(Number(props.highlightWordSpacing)) ? Number(props.highlightWordSpacing) : undefined,
                fontFamily: typeof props.fontFamily === 'string' ? props.fontFamily : '',
                fontSize: Number.isFinite(Number(props.fontSize)) ? Number(props.fontSize) : undefined,
                fontWeight: typeof props.fontWeight === 'string' ? props.fontWeight : '',
                lineHeight: Number.isFinite(Number(props.lineHeight)) ? Number(props.lineHeight) : undefined,
                letterSpacing: Number.isFinite(Number(props.letterSpacing)) ? Number(props.letterSpacing) : undefined,
                wordSpacing: Number.isFinite(Number(props.wordSpacing)) ? Number(props.wordSpacing) : undefined,
                textShadowColor: typeof props.textShadowColor === 'string' ? props.textShadowColor : '',
                textShadowX: Number.isFinite(Number(props.textShadowX)) ? Number(props.textShadowX) : undefined,
                textShadowY: Number.isFinite(Number(props.textShadowY)) ? Number(props.textShadowY) : undefined,
                textShadowBlur: Number.isFinite(Number(props.textShadowBlur)) ? Number(props.textShadowBlur) : undefined,
                paddingLinked,
                paddingTop,
                paddingRight,
                paddingBottom,
                paddingLeft,
                marginLinked: typeof props.marginLinked === 'boolean'
                    ? props.marginLinked
                    : inferLinked4(
                        toFiniteNumberOrUndefined(marginTop),
                        toFiniteNumberOrUndefined(marginRight),
                        toFiniteNumberOrUndefined(marginBottom),
                        toFiniteNumberOrUndefined(marginLeft),
                        true
                    ),
                marginTop,
                marginRight,
                marginBottom,
                marginLeft,
                borderStyle: typeof props.borderStyle === 'string' ? props.borderStyle : '',
                borderColor: typeof props.borderColor === 'string' ? props.borderColor : '',
                borderWidthLinked: typeof props.borderWidthLinked === 'boolean'
                    ? props.borderWidthLinked
                    : inferLinked4(
                        toFiniteNumberOrUndefined(borderWidthTop),
                        toFiniteNumberOrUndefined(borderWidthRight),
                        toFiniteNumberOrUndefined(borderWidthBottom),
                        toFiniteNumberOrUndefined(borderWidthLeft),
                        true
                    ),
                borderWidthTop,
                borderWidthRight,
                borderWidthBottom,
                borderWidthLeft,
                borderRadiusLinked: typeof props.borderRadiusLinked === 'boolean'
                    ? props.borderRadiusLinked
                    : inferLinked4(
                        toFiniteNumberOrUndefined(borderRadiusTop),
                        toFiniteNumberOrUndefined(borderRadiusRight),
                        toFiniteNumberOrUndefined(borderRadiusBottom),
                        toFiniteNumberOrUndefined(borderRadiusLeft),
                        true
                    ),
                borderRadiusTop,
                borderRadiusRight,
                borderRadiusBottom,
                borderRadiusLeft,
                backgroundType,
                backgroundColor: typeof props.backgroundColor === 'string' ? props.backgroundColor : '',
                gradientFrom: typeof props.gradientFrom === 'string' ? props.gradientFrom : '',
                gradientTo: typeof props.gradientTo === 'string' ? props.gradientTo : '',
                gradientFromLocation,
                gradientToLocation,
                gradientType,
                gradientPosition,
                ...motion,
            },
        };
    }

    if (type === 'image') {
        const width = normalizeLengthValue(props.width, undefined);
        const height = normalizeLengthValue(props.height, undefined);

        const paddingTop = Number.isFinite(Number(props.paddingTop))
            ? Number(props.paddingTop)
            : Number.isFinite(Number(props.paddingY))
              ? Number(props.paddingY)
              : undefined;
        const paddingRight = Number.isFinite(Number(props.paddingRight))
            ? Number(props.paddingRight)
            : Number.isFinite(Number(props.paddingX))
              ? Number(props.paddingX)
              : undefined;
        const paddingBottom = Number.isFinite(Number(props.paddingBottom))
            ? Number(props.paddingBottom)
            : Number.isFinite(Number(props.paddingY))
              ? Number(props.paddingY)
              : undefined;
        const paddingLeft = Number.isFinite(Number(props.paddingLeft))
            ? Number(props.paddingLeft)
            : Number.isFinite(Number(props.paddingX))
              ? Number(props.paddingX)
              : undefined;
        const paddingLinked = typeof props.paddingLinked === 'boolean'
            ? props.paddingLinked
            : inferLinked4(
                toFiniteNumberOrUndefined(paddingTop),
                toFiniteNumberOrUndefined(paddingRight),
                toFiniteNumberOrUndefined(paddingBottom),
                toFiniteNumberOrUndefined(paddingLeft),
                true
            );

        const marginTop = Number.isFinite(Number(props.marginTop)) ? Number(props.marginTop) : undefined;
        const marginRight = Number.isFinite(Number(props.marginRight)) ? Number(props.marginRight) : undefined;
        const marginBottom = Number.isFinite(Number(props.marginBottom)) ? Number(props.marginBottom) : undefined;
        const marginLeft = Number.isFinite(Number(props.marginLeft)) ? Number(props.marginLeft) : undefined;
        const marginLinked = typeof props.marginLinked === 'boolean'
            ? props.marginLinked
            : inferLinked4(
                toFiniteNumberOrUndefined(marginTop),
                toFiniteNumberOrUndefined(marginRight),
                toFiniteNumberOrUndefined(marginBottom),
                toFiniteNumberOrUndefined(marginLeft),
                true
            );

        const borderWidthTop = Number.isFinite(Number(props.borderWidthTop))
            ? Number(props.borderWidthTop)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;
        const borderWidthRight = Number.isFinite(Number(props.borderWidthRight))
            ? Number(props.borderWidthRight)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;
        const borderWidthBottom = Number.isFinite(Number(props.borderWidthBottom))
            ? Number(props.borderWidthBottom)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;
        const borderWidthLeft = Number.isFinite(Number(props.borderWidthLeft))
            ? Number(props.borderWidthLeft)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;
        const borderWidthLinked = typeof props.borderWidthLinked === 'boolean'
            ? props.borderWidthLinked
            : inferLinked4(
                toFiniteNumberOrUndefined(borderWidthTop),
                toFiniteNumberOrUndefined(borderWidthRight),
                toFiniteNumberOrUndefined(borderWidthBottom),
                toFiniteNumberOrUndefined(borderWidthLeft),
                true
            );

        const borderRadiusTop = Number.isFinite(Number(props.borderRadiusTop))
            ? Number(props.borderRadiusTop)
            : Number.isFinite(Number(props.borderRadius))
              ? Number(props.borderRadius)
              : undefined;
        const borderRadiusRight = Number.isFinite(Number(props.borderRadiusRight))
            ? Number(props.borderRadiusRight)
            : Number.isFinite(Number(props.borderRadius))
              ? Number(props.borderRadius)
              : undefined;
        const borderRadiusBottom = Number.isFinite(Number(props.borderRadiusBottom))
            ? Number(props.borderRadiusBottom)
            : Number.isFinite(Number(props.borderRadius))
              ? Number(props.borderRadius)
              : undefined;
        const borderRadiusLeft = Number.isFinite(Number(props.borderRadiusLeft))
            ? Number(props.borderRadiusLeft)
            : Number.isFinite(Number(props.borderRadius))
              ? Number(props.borderRadius)
              : undefined;
        const borderRadiusLinked = typeof props.borderRadiusLinked === 'boolean'
            ? props.borderRadiusLinked
            : inferLinked4(
                toFiniteNumberOrUndefined(borderRadiusTop),
                toFiniteNumberOrUndefined(borderRadiusRight),
                toFiniteNumberOrUndefined(borderRadiusBottom),
                toFiniteNumberOrUndefined(borderRadiusLeft),
                true
            );

        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Image',
            advanced,
            props: {
                url: typeof props.url === 'string' ? props.url : '',
                svg: typeof props.svg === 'string' ? props.svg : '',
                source: props.source === 'svg' ? 'svg' : 'url',
                alt: typeof props.alt === 'string' ? props.alt : '',
                link: typeof props.link === 'string' ? props.link : '',
                target: props.target === '_blank' ? '_blank' : '_self',
                align: ['left', 'center', 'right'].includes(props.align) ? props.align : 'left',
                objectFit: ['cover', 'contain', 'fill', 'none', 'scale-down'].includes(props.objectFit) ? props.objectFit : '',
                width,
                height,
                paddingLinked,
                paddingTop,
                paddingRight,
                paddingBottom,
                paddingLeft,
                marginLinked,
                marginTop,
                marginRight,
                marginBottom,
                marginLeft,
                borderStyle: typeof props.borderStyle === 'string' ? props.borderStyle : '',
                borderColor: typeof props.borderColor === 'string' ? props.borderColor : '',
                borderWidthLinked,
                borderWidthTop,
                borderWidthRight,
                borderWidthBottom,
                borderWidthLeft,
                borderRadiusLinked,
                borderRadiusTop,
                borderRadiusRight,
                borderRadiusBottom,
                borderRadiusLeft,
                boxShadowColor: typeof props.boxShadowColor === 'string' ? props.boxShadowColor : '',
                boxShadowX: Number.isFinite(Number(props.boxShadowX)) ? Number(props.boxShadowX) : undefined,
                boxShadowY: Number.isFinite(Number(props.boxShadowY)) ? Number(props.boxShadowY) : undefined,
                boxShadowBlur: Number.isFinite(Number(props.boxShadowBlur)) ? Number(props.boxShadowBlur) : undefined,
                boxShadowSpread: Number.isFinite(Number(props.boxShadowSpread)) ? Number(props.boxShadowSpread) : undefined,
                ...motion,
            },
        };
    }

    if (type === 'button') {
        const firstColorValue = (...keys) => {
            for (const key of keys) {
                const value = props[key];
                if (value && typeof value === 'object' && typeof value.global === 'string' && value.global.trim()) {
                    return { global: value.global };
                }
                if (typeof value === 'string' && value.trim()) {
                    return value.trim();
                }
            }
            return '';
        };

        const paddingTop = Number.isFinite(Number(props.paddingTop))
            ? Number(props.paddingTop)
            : Number.isFinite(Number(props.paddingY))
              ? Number(props.paddingY)
              : undefined;
        const paddingRight = Number.isFinite(Number(props.paddingRight))
            ? Number(props.paddingRight)
            : Number.isFinite(Number(props.paddingX))
              ? Number(props.paddingX)
              : undefined;
        const paddingBottom = Number.isFinite(Number(props.paddingBottom))
            ? Number(props.paddingBottom)
            : Number.isFinite(Number(props.paddingY))
              ? Number(props.paddingY)
              : undefined;
        const paddingLeft = Number.isFinite(Number(props.paddingLeft))
            ? Number(props.paddingLeft)
            : Number.isFinite(Number(props.paddingX))
              ? Number(props.paddingX)
              : undefined;

        const borderWidthTop = Number.isFinite(Number(props.borderWidthTop))
            ? Number(props.borderWidthTop)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;
        const borderWidthRight = Number.isFinite(Number(props.borderWidthRight))
            ? Number(props.borderWidthRight)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;
        const borderWidthBottom = Number.isFinite(Number(props.borderWidthBottom))
            ? Number(props.borderWidthBottom)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;
        const borderWidthLeft = Number.isFinite(Number(props.borderWidthLeft))
            ? Number(props.borderWidthLeft)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;

        const borderRadiusTop = cssLenOr(props.borderRadiusTop, props.borderRadius);
        const borderRadiusRight = cssLenOr(props.borderRadiusRight, props.borderRadius);
        const borderRadiusBottom = cssLenOr(props.borderRadiusBottom, props.borderRadius);
        const borderRadiusLeft = cssLenOr(props.borderRadiusLeft, props.borderRadius);

        const backgroundType = ['color', 'classic', 'gradient'].includes(props.backgroundType) ? props.backgroundType : 'classic';
        const gradientType = ['linear', 'radial'].includes(props.gradientType) ? props.gradientType : 'linear';
        const gradientPosition = typeof props.gradientPosition === 'string' ? props.gradientPosition : '';
        const gradientFromLocation = Number.isFinite(Number(props.gradientFromLocation)) ? Number(props.gradientFromLocation) : undefined;
        const gradientToLocation = Number.isFinite(Number(props.gradientToLocation)) ? Number(props.gradientToLocation) : undefined;

        const marginTop = Number.isFinite(Number(props.marginTop)) ? Number(props.marginTop) : undefined;
        const marginRight = Number.isFinite(Number(props.marginRight)) ? Number(props.marginRight) : undefined;
        const marginBottom = Number.isFinite(Number(props.marginBottom)) ? Number(props.marginBottom) : undefined;
        const marginLeft = Number.isFinite(Number(props.marginLeft)) ? Number(props.marginLeft) : undefined;

        const width = Number.isFinite(Number(props.width)) ? Number(props.width) : undefined;
        const height = Number.isFinite(Number(props.height)) ? Number(props.height) : undefined;

        const hoverBackgroundType = ['color', 'classic', 'gradient'].includes(props.hoverBackgroundType)
            ? props.hoverBackgroundType
            : (['color', 'classic', 'gradient'].includes(props.buttonHoverBackgroundType) ? props.buttonHoverBackgroundType : 'classic');
        const hoverGradientType = ['linear', 'radial'].includes(props.hoverGradientType)
            ? props.hoverGradientType
            : (['linear', 'radial'].includes(props.buttonHoverGradientType) ? props.buttonHoverGradientType : 'linear');
        const hoverGradientPosition = typeof props.hoverGradientPosition === 'string'
            ? props.hoverGradientPosition
            : (typeof props.buttonHoverGradientPosition === 'string' ? props.buttonHoverGradientPosition : '');
        const hoverGradientFromLocation = Number.isFinite(Number(props.hoverGradientFromLocation)) ? Number(props.hoverGradientFromLocation) : undefined;
        const hoverGradientToLocation = Number.isFinite(Number(props.hoverGradientToLocation)) ? Number(props.hoverGradientToLocation) : undefined;

        const focusBackgroundType = ['color', 'classic', 'gradient'].includes(props.focusBackgroundType)
            ? props.focusBackgroundType
            : (['color', 'classic', 'gradient'].includes(props.buttonFocusBackgroundType) ? props.buttonFocusBackgroundType : 'classic');
        const focusGradientType = ['linear', 'radial'].includes(props.focusGradientType)
            ? props.focusGradientType
            : (['linear', 'radial'].includes(props.buttonFocusGradientType) ? props.buttonFocusGradientType : 'linear');
        const focusGradientPosition = typeof props.focusGradientPosition === 'string'
            ? props.focusGradientPosition
            : (typeof props.buttonFocusGradientPosition === 'string' ? props.buttonFocusGradientPosition : '');
        const focusGradientFromLocation = Number.isFinite(Number(props.focusGradientFromLocation)) ? Number(props.focusGradientFromLocation) : undefined;
        const focusGradientToLocation = Number.isFinite(Number(props.focusGradientToLocation)) ? Number(props.focusGradientToLocation) : undefined;

        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Button',
            advanced,
            props: {
                text: typeof props.text === 'string' ? props.text : 'Click Me',
                url: typeof props.url === 'string' ? props.url : '',
                target: ['_self', '_blank'].includes(props.target) ? props.target : '_self',
                style: ['primary', 'secondary', 'outline', 'ghost'].includes(props.style) ? props.style : 'primary',
                size: ['xs', 'sm', 'md', 'lg', 'xl'].includes(props.size) ? props.size : 'md',
                fullWidth: typeof props.fullWidth === 'boolean' ? props.fullWidth : false,
                align: ['left', 'center', 'right'].includes(props.align) ? props.align : 'left',
                iconType: ['none', 'preset', 'custom'].includes(props.iconType)
                    ? props.iconType
                    : typeof props.iconUrl === 'string' && props.iconUrl.trim()
                      ? 'custom'
                      : typeof props.icon === 'string' && props.icon.trim()
                        ? 'preset'
                        : 'none',
                icon: typeof props.icon === 'string' ? props.icon : '',
                iconUrl: typeof props.iconUrl === 'string' ? props.iconUrl : '',
                iconPosition: ['left', 'right'].includes(props.iconPosition) ? props.iconPosition : 'left',
                boxShadowColor: typeof props.boxShadowColor === 'string' ? props.boxShadowColor : '',
                boxShadowX: Number.isFinite(Number(props.boxShadowX)) ? Number(props.boxShadowX) : undefined,
                boxShadowY: Number.isFinite(Number(props.boxShadowY)) ? Number(props.boxShadowY) : undefined,
                boxShadowBlur: Number.isFinite(Number(props.boxShadowBlur)) ? Number(props.boxShadowBlur) : undefined,
                boxShadowSpread: Number.isFinite(Number(props.boxShadowSpread)) ? Number(props.boxShadowSpread) : undefined,
                paddingLinked: typeof props.paddingLinked === 'boolean'
                    ? props.paddingLinked
                    : inferLinked4(
                        toFiniteNumberOrUndefined(paddingTop),
                        toFiniteNumberOrUndefined(paddingRight),
                        toFiniteNumberOrUndefined(paddingBottom),
                        toFiniteNumberOrUndefined(paddingLeft),
                        true
                    ),
                paddingTop,
                paddingRight,
                paddingBottom,
                paddingLeft,
                marginLinked: typeof props.marginLinked === 'boolean'
                    ? props.marginLinked
                    : inferLinked4(
                        toFiniteNumberOrUndefined(marginTop),
                        toFiniteNumberOrUndefined(marginRight),
                        toFiniteNumberOrUndefined(marginBottom),
                        toFiniteNumberOrUndefined(marginLeft),
                        true
                    ),
                borderStyle: typeof props.borderStyle === 'string' ? props.borderStyle : '',
                borderColor: typeof props.borderColor === 'string' ? props.borderColor : '',
                borderWidthLinked: typeof props.borderWidthLinked === 'boolean'
                    ? props.borderWidthLinked
                    : inferLinked4(
                        toFiniteNumberOrUndefined(borderWidthTop),
                        toFiniteNumberOrUndefined(borderWidthRight),
                        toFiniteNumberOrUndefined(borderWidthBottom),
                        toFiniteNumberOrUndefined(borderWidthLeft),
                        true
                    ),
                borderWidthTop,
                borderWidthRight,
                borderWidthBottom,
                borderWidthLeft,
                borderRadiusLinked: typeof props.borderRadiusLinked === 'boolean'
                    ? props.borderRadiusLinked
                    : inferLinked4(
                        toFiniteNumberOrUndefined(borderRadiusTop),
                        toFiniteNumberOrUndefined(borderRadiusRight),
                        toFiniteNumberOrUndefined(borderRadiusBottom),
                        toFiniteNumberOrUndefined(borderRadiusLeft),
                        true
                    ),
                borderRadiusTop,
                borderRadiusRight,
                borderRadiusBottom,
                borderRadiusLeft,
                borderRadius: cssLenOr(props.borderRadius, undefined),
                backgroundType,
                backgroundColor: firstColorValue('backgroundColor', 'buttonBackgroundColor'),
                buttonBackgroundColor: firstColorValue('backgroundColor', 'buttonBackgroundColor'),
                gradientFrom: firstColorValue('gradientFrom', 'buttonGradientFrom'),
                buttonGradientFrom: firstColorValue('gradientFrom', 'buttonGradientFrom'),
                gradientTo: firstColorValue('gradientTo', 'buttonGradientTo'),
                buttonGradientTo: firstColorValue('gradientTo', 'buttonGradientTo'),
                gradientFromLocation,
                gradientToLocation,
                gradientType,
                gradientPosition,
                fontFamily: typeof props.fontFamily === 'string' ? props.fontFamily : '',
                fontSize: Number.isFinite(Number(props.fontSize)) ? Number(props.fontSize) : undefined,
                fontWeight: typeof props.fontWeight === 'string' ? props.fontWeight : '',
                lineHeight: Number.isFinite(Number(props.lineHeight)) ? Number(props.lineHeight) : undefined,
                letterSpacing: Number.isFinite(Number(props.letterSpacing)) ? Number(props.letterSpacing) : undefined,
                wordSpacing: Number.isFinite(Number(props.wordSpacing)) ? Number(props.wordSpacing) : undefined,
                textColor: firstColorValue('textColor', 'buttonTextColor'),
                buttonTextColor: firstColorValue('textColor', 'buttonTextColor'),
                hoverTextColor: firstColorValue('hoverTextColor', 'buttonHoverTextColor'),
                buttonHoverTextColor: firstColorValue('hoverTextColor', 'buttonHoverTextColor'),
                hoverBackgroundType,
                hoverBackgroundColor: firstColorValue('hoverBackgroundColor', 'buttonHoverBackgroundColor'),
                buttonHoverBackgroundColor: firstColorValue('hoverBackgroundColor', 'buttonHoverBackgroundColor'),
                hoverGradientFrom: firstColorValue('hoverGradientFrom', 'buttonHoverGradientFrom'),
                buttonHoverGradientFrom: firstColorValue('hoverGradientFrom', 'buttonHoverGradientFrom'),
                hoverGradientTo: firstColorValue('hoverGradientTo', 'buttonHoverGradientTo'),
                buttonHoverGradientTo: firstColorValue('hoverGradientTo', 'buttonHoverGradientTo'),
                hoverGradientFromLocation,
                hoverGradientToLocation,
                hoverGradientType,
                buttonHoverGradientType: hoverGradientType,
                hoverGradientPosition,
                buttonHoverGradientPosition: hoverGradientPosition,
                focusTextColor: firstColorValue('focusTextColor', 'buttonFocusTextColor'),
                buttonFocusTextColor: firstColorValue('focusTextColor', 'buttonFocusTextColor'),
                focusBackgroundType,
                focusBackgroundColor: firstColorValue('focusBackgroundColor', 'buttonFocusBackgroundColor'),
                buttonFocusBackgroundColor: firstColorValue('focusBackgroundColor', 'buttonFocusBackgroundColor'),
                focusGradientFrom: firstColorValue('focusGradientFrom', 'buttonFocusGradientFrom'),
                buttonFocusGradientFrom: firstColorValue('focusGradientFrom', 'buttonFocusGradientFrom'),
                focusGradientTo: firstColorValue('focusGradientTo', 'buttonFocusGradientTo'),
                buttonFocusGradientTo: firstColorValue('focusGradientTo', 'buttonFocusGradientTo'),
                focusGradientFromLocation,
                focusGradientToLocation,
                focusGradientType,
                buttonFocusGradientType: focusGradientType,
                focusGradientPosition,
                buttonFocusGradientPosition: focusGradientPosition,
                hoverTextEffect: ['none', 'swap-up', 'swap-icon', 'slide-icon-right'].includes(props.hoverTextEffect) ? props.hoverTextEffect : 'none',
                marginTop,
                marginRight,
                marginBottom,
                marginLeft,
                width,
                height,
                borderEffect: typeof props.borderEffect === 'string' ? props.borderEffect : '',
                borderEffectColor: typeof props.borderEffectColor === 'string' ? props.borderEffectColor : '',
                borderEffectColor2: typeof props.borderEffectColor2 === 'string' ? props.borderEffectColor2 : '',
                borderEffectSpeed: Number.isFinite(Number(props.borderEffectSpeed)) ? Number(props.borderEffectSpeed) : undefined,
                borderEffectThickness: Number.isFinite(Number(props.borderEffectThickness)) ? Number(props.borderEffectThickness) : undefined,
                ...motion,
            },
        };
    }

    if (type === 'slider') {
        const images = Array.isArray(props.images) ? props.images : [];

        const perPageRaw = Number(props.carouselPerPage);
        const carouselPerPage = Number.isFinite(perPageRaw) ? Math.max(1, Math.min(6, Math.round(perPageRaw))) : undefined;

        const gapRaw = Number(props.carouselGap);
        const carouselGap = Number.isFinite(gapRaw) ? Math.max(0, Math.min(120, Math.round(gapRaw))) : undefined;

        const intervalRaw = Number(props.carouselInterval);
        const carouselInterval = Number.isFinite(intervalRaw) ? Math.max(1, Math.min(60, intervalRaw)) : undefined;

        const easeRaw = typeof props.carouselEase === 'string' ? props.carouselEase : '';
        const carouselEase = ['linear', 'ease', 'ease-in', 'ease-out', 'ease-in-out'].includes(easeRaw) ? easeRaw : undefined;

        const carouselAutoplay = props.carouselAutoplay === false ? false : undefined;
        const carouselArrows = props.carouselArrows === false ? false : undefined;

        const arrowSizeRaw = Number(props.arrowSize);
        const arrowSize = Number.isFinite(arrowSizeRaw) ? Math.max(24, Math.min(72, Math.round(arrowSizeRaw))) : undefined;

        const arrowRadiusRaw = Number(props.arrowRadius);
        const arrowRadius = Number.isFinite(arrowRadiusRaw) ? Math.max(0, Math.min(999, Math.round(arrowRadiusRaw))) : undefined;

        const arrowBgColor = typeof props.arrowBgColor === 'string' ? props.arrowBgColor : undefined;
        const arrowColor = typeof props.arrowColor === 'string' ? props.arrowColor : undefined;
        const arrowBorderColor = typeof props.arrowBorderColor === 'string' ? props.arrowBorderColor : undefined;

        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Slider',
            advanced,
            props: {
                width: Number.isFinite(Number(props.width)) ? Number(props.width) : undefined,
                height: Number.isFinite(Number(props.height)) ? Number(props.height) : 360,
                images: images
                    .filter((i) => i && typeof i === 'object')
                    .map((i) => ({ url: String(i.url || '') }))
                    .filter((i) => i.url),
                carouselPerPage,
                carouselGap,
                carouselAutoplay,
                carouselInterval,
                carouselArrows,
                carouselEase,
                arrowSize,
                arrowRadius,
                arrowBgColor,
                arrowColor,
                arrowBorderColor,
                ...motion,
            },
        };
    }

    if (type === 'grid') {
        const countRaw = Number(props.count);
        const count = Number.isFinite(countRaw) ? Math.max(1, Math.min(4, Math.round(countRaw))) : 2;

        const rawCols = Array.isArray(props.columns) ? props.columns : [];
        const cols = rawCols
            .filter((c) => c && typeof c === 'object')
            .slice(0, 6)
            .map((c, idx) =>
                normalizeColumn({
                    id: c.id || uid('gcol'),
                    name: typeof c.name === 'string' ? c.name : `Column ${idx + 1}`,
                    advanced: c.advanced && typeof c.advanced === 'object' ? c.advanced : undefined,
                    spans: c.spans && typeof c.spans === 'object' ? c.spans : { base: 12, md: 12, lg: 12 },
                    style: c.style && typeof c.style === 'object' ? c.style : undefined,
                    widgets: Array.isArray(c.widgets) ? c.widgets : [],
                })
            )
            .filter(Boolean);

        while (cols.length < count) {
            cols.push(
                normalizeColumn({
                    id: uid('gcol'),
                    name: `Column ${cols.length + 1}`,
                    spans: { base: 12, md: 12, lg: 12 },
                    widgets: [],
                })
            );
        }
        while (cols.length > count) {
            cols.pop();
        }

        const even = Math.floor(12 / count);
        const remainder = 12 - even * count;
        const mdSpans = Array.from({ length: count }, (_, idx) => even + (idx === count - 1 ? remainder : 0));
        // Tablet shows at most 2 grid columns: split 12 across min(count, 2).
        const tabletCols = Math.min(count, 2);
        const tEven = Math.floor(12 / tabletCols);
        const tRem = 12 - tEven * tabletCols;
        const tabletSpans = Array.from({ length: count }, (_, idx) => (idx % tabletCols === tabletCols - 1 || idx === count - 1 ? tEven + (idx === count - 1 ? tRem : 0) : tEven));
        const finalCols = cols.map((c, idx) => ({
            ...c,
            spans: {
                base: 12,
                md: tabletSpans[idx] || 12,
                lg: mdSpans[idx] || 12,
            },
        }));

        const style = w.style && typeof w.style === 'object' ? w.style : {};
        const gapRaw = Number(props.gap);
        const gap = Number.isFinite(gapRaw) ? Math.max(0, Math.round(gapRaw)) : 0;
        return {
            id: String(w.id || uid('w')),
            type,
            name: name || (props.mobileMenu === true ? 'Mobile Menu' : 'Grid'),
            advanced,
            style,
            props: {
                count,
                gap,
                columns: finalCols,
                ...(props.pricingMode === 'switcher' || props.pricingMode === 'tabs' ? { pricingMode: props.pricingMode } : {}),
                ...(Array.isArray(props.pricingTabs) ? { pricingTabs: props.pricingTabs } : {}),
                ...(props.pricingStyle && typeof props.pricingStyle === 'object' ? { pricingStyle: props.pricingStyle } : {}),
                ...(props.mobileMenu === true ? {
                    mobileMenu: true,
                    mobileMenuToggleIconUrl: typeof props.mobileMenuToggleIconUrl === 'string' ? props.mobileMenuToggleIconUrl : '',
                    mobileMenuToggleIconSvg: typeof props.mobileMenuToggleIconSvg === 'string' ? props.mobileMenuToggleIconSvg : '',
                    mobileMenuToggleIconSource: props.mobileMenuToggleIconSource === 'svg' || props.mobileMenuToggleIconSource === 'url' ? props.mobileMenuToggleIconSource : 'url',
                    mobileMenuToggleIconSize: Number.isFinite(Number(props.mobileMenuToggleIconSize)) ? Number(props.mobileMenuToggleIconSize) : 28,
                    mobileMenuDirection: props.mobileMenuDirection === 'left' ? 'left' : 'right',
                    mobileMenuAnimation: ['slide', 'fade', 'scale', 'none'].includes(props.mobileMenuAnimation) ? props.mobileMenuAnimation : 'slide',
                    mobileMenuOverlayColor: typeof props.mobileMenuOverlayColor === 'string' ? props.mobileMenuOverlayColor : 'rgba(17, 24, 39, 0.5)',
                    mobileMenuCloseColor: typeof props.mobileMenuCloseColor === 'string' ? props.mobileMenuCloseColor : '',
                    mobileMenuBackgroundType: ['color', 'gradient'].includes(props.mobileMenuBackgroundType) ? props.mobileMenuBackgroundType : 'color',
                    mobileMenuBackgroundColor: typeof props.mobileMenuBackgroundColor === 'string' ? props.mobileMenuBackgroundColor : '#ffffff',
                    mobileMenuGradientFrom: typeof props.mobileMenuGradientFrom === 'string' ? props.mobileMenuGradientFrom : '#111827',
                    mobileMenuGradientTo: typeof props.mobileMenuGradientTo === 'string' ? props.mobileMenuGradientTo : '#3b82f6',
                    mobileMenuGradientType: props.mobileMenuGradientType === 'radial' ? 'radial' : 'linear',
                    mobileMenuGradientPosition: typeof props.mobileMenuGradientPosition === 'string' ? props.mobileMenuGradientPosition : 'top center',
                    mobileMenuGradientAngle: Number.isFinite(Number(props.mobileMenuGradientAngle)) ? Number(props.mobileMenuGradientAngle) : 135,
                    mobileMenuBorderStyle: ['solid', 'dashed', 'dotted'].includes(props.mobileMenuBorderStyle) ? props.mobileMenuBorderStyle : 'solid',
                    mobileMenuBorderColor: typeof props.mobileMenuBorderColor === 'string' ? props.mobileMenuBorderColor : '#e5e7eb',
                    mobileMenuBorderWidthTop: Number.isFinite(Number(props.mobileMenuBorderWidthTop)) ? Number(props.mobileMenuBorderWidthTop) : 0,
                    mobileMenuBorderWidthRight: Number.isFinite(Number(props.mobileMenuBorderWidthRight)) ? Number(props.mobileMenuBorderWidthRight) : 0,
                    mobileMenuBorderWidthBottom: Number.isFinite(Number(props.mobileMenuBorderWidthBottom)) ? Number(props.mobileMenuBorderWidthBottom) : 0,
                    mobileMenuBorderWidthLeft: Number.isFinite(Number(props.mobileMenuBorderWidthLeft)) ? Number(props.mobileMenuBorderWidthLeft) : 0,
                    mobileMenuBorderWidthLinked: typeof props.mobileMenuBorderWidthLinked === 'boolean' ? props.mobileMenuBorderWidthLinked : true,
                    mobileMenuBorderRadiusTop: Number.isFinite(Number(props.mobileMenuBorderRadiusTop)) ? Number(props.mobileMenuBorderRadiusTop) : 0,
                    mobileMenuBorderRadiusRight: Number.isFinite(Number(props.mobileMenuBorderRadiusRight)) ? Number(props.mobileMenuBorderRadiusRight) : 0,
                    mobileMenuBorderRadiusBottom: Number.isFinite(Number(props.mobileMenuBorderRadiusBottom)) ? Number(props.mobileMenuBorderRadiusBottom) : 0,
                    mobileMenuBorderRadiusLeft: Number.isFinite(Number(props.mobileMenuBorderRadiusLeft)) ? Number(props.mobileMenuBorderRadiusLeft) : 0,
                    mobileMenuBorderRadiusLinked: typeof props.mobileMenuBorderRadiusLinked === 'boolean' ? props.mobileMenuBorderRadiusLinked : true,
                    mobileMenuPaddingTop: Number.isFinite(Number(props.mobileMenuPaddingTop)) ? Number(props.mobileMenuPaddingTop) : 24,
                    mobileMenuPaddingRight: Number.isFinite(Number(props.mobileMenuPaddingRight)) ? Number(props.mobileMenuPaddingRight) : 24,
                    mobileMenuPaddingBottom: Number.isFinite(Number(props.mobileMenuPaddingBottom)) ? Number(props.mobileMenuPaddingBottom) : 24,
                    mobileMenuPaddingLeft: Number.isFinite(Number(props.mobileMenuPaddingLeft)) ? Number(props.mobileMenuPaddingLeft) : 24,
                    mobileMenuPaddingLinked: typeof props.mobileMenuPaddingLinked === 'boolean' ? props.mobileMenuPaddingLinked : true,
                } : {}),
                ...motion,
            },
        };
    }

    if (type === 'icon') {
        const borderWidthTop = Number.isFinite(Number(props.borderWidthTop))
            ? Number(props.borderWidthTop)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;
        const borderWidthRight = Number.isFinite(Number(props.borderWidthRight))
            ? Number(props.borderWidthRight)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;
        const borderWidthBottom = Number.isFinite(Number(props.borderWidthBottom))
            ? Number(props.borderWidthBottom)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;
        const borderWidthLeft = Number.isFinite(Number(props.borderWidthLeft))
            ? Number(props.borderWidthLeft)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;

        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Icon Box',
            advanced,
            props: {
                iconSvg: typeof props.iconSvg === 'string' ? props.iconSvg : '',
                iconUrl: typeof props.iconUrl === 'string' ? props.iconUrl : '',
                iconSource: props.iconSource === 'svg' || props.iconSource === 'url' ? props.iconSource : 'url',
                iconColor: typeof props.iconColor === 'string' ? props.iconColor : '',
                iconBackgroundColor: typeof props.iconBackgroundColor === 'string' ? props.iconBackgroundColor : '',
                iconBackgroundType: ['color', 'gradient'].includes(props.iconBackgroundType) ? props.iconBackgroundType : 'color',
                iconGradientFrom: typeof props.iconGradientFrom === 'string' ? props.iconGradientFrom : '',
                iconGradientTo: typeof props.iconGradientTo === 'string' ? props.iconGradientTo : '',
                iconGradientType: (props.iconGradientType === 'radial' || props.iconGradientType === 'linear') ? props.iconGradientType : 'linear',
                iconGradientAngle: Number.isFinite(Number(props.iconGradientAngle)) ? Number(props.iconGradientAngle) : 135,
                iconGradientPosition: typeof props.iconGradientPosition === 'string' ? props.iconGradientPosition : 'top center',
                iconPaddingTop: Number.isFinite(Number(props.iconPaddingTop)) ? Number(props.iconPaddingTop) : undefined,
                iconPaddingRight: Number.isFinite(Number(props.iconPaddingRight)) ? Number(props.iconPaddingRight) : undefined,
                iconPaddingBottom: Number.isFinite(Number(props.iconPaddingBottom)) ? Number(props.iconPaddingBottom) : undefined,
                iconPaddingLeft: Number.isFinite(Number(props.iconPaddingLeft)) ? Number(props.iconPaddingLeft) : undefined,
                iconPaddingLinked: typeof props.iconPaddingLinked === 'boolean' ? props.iconPaddingLinked : true,
                iconBorderRadius: Number.isFinite(Number(props.iconBorderRadius)) ? Number(props.iconBorderRadius) : undefined,
                iconBorderRadiusLinked: typeof props.iconBorderRadiusLinked === 'boolean'
                    ? props.iconBorderRadiusLinked
                    : inferLinked4(
                        toFiniteNumberOrUndefined(props.iconBorderRadiusTop),
                        toFiniteNumberOrUndefined(props.iconBorderRadiusRight),
                        toFiniteNumberOrUndefined(props.iconBorderRadiusBottom),
                        toFiniteNumberOrUndefined(props.iconBorderRadiusLeft),
                        true
                    ),
                iconBorderRadiusTop: Number.isFinite(Number(props.iconBorderRadiusTop))
                    ? Number(props.iconBorderRadiusTop)
                    : Number.isFinite(Number(props.iconBorderRadius))
                      ? Number(props.iconBorderRadius)
                      : undefined,
                iconBorderRadiusRight: Number.isFinite(Number(props.iconBorderRadiusRight))
                    ? Number(props.iconBorderRadiusRight)
                    : Number.isFinite(Number(props.iconBorderRadius))
                      ? Number(props.iconBorderRadius)
                      : undefined,
                iconBorderRadiusBottom: Number.isFinite(Number(props.iconBorderRadiusBottom))
                    ? Number(props.iconBorderRadiusBottom)
                    : Number.isFinite(Number(props.iconBorderRadius))
                      ? Number(props.iconBorderRadius)
                      : undefined,
                iconBorderRadiusLeft: Number.isFinite(Number(props.iconBorderRadiusLeft))
                    ? Number(props.iconBorderRadiusLeft)
                    : Number.isFinite(Number(props.iconBorderRadius))
                      ? Number(props.iconBorderRadius)
                      : undefined,
                iconBorderWidthTop: Number.isFinite(Number(props.iconBorderWidthTop)) ? Number(props.iconBorderWidthTop) : undefined,
                iconBorderWidthRight: Number.isFinite(Number(props.iconBorderWidthRight)) ? Number(props.iconBorderWidthRight) : undefined,
                iconBorderWidthBottom: Number.isFinite(Number(props.iconBorderWidthBottom)) ? Number(props.iconBorderWidthBottom) : undefined,
                iconBorderWidthLeft: Number.isFinite(Number(props.iconBorderWidthLeft)) ? Number(props.iconBorderWidthLeft) : undefined,
                iconBorderWidthLinked: typeof props.iconBorderWidthLinked === 'boolean' ? props.iconBorderWidthLinked : true,
                iconBorderColor: typeof props.iconBorderColor === 'string' ? props.iconBorderColor : '',
                heading: typeof props.heading === 'string' ? props.heading : 'Heading',
                subtext: typeof props.subtext === 'string' ? props.subtext : 'Subtext',
                iconPosition: ['top', 'left', 'right'].includes(props.iconPosition) ? props.iconPosition : 'top',
                iconSize: Number.isFinite(Number(props.iconSize)) ? Number(props.iconSize) : 64,
                iconGap: Number.isFinite(Number(props.iconGap)) ? Number(props.iconGap) : 16,
                align: ['left', 'center', 'right'].includes(props.align) ? props.align : 'center',
                headingColor: typeof props.headingColor === 'string' ? props.headingColor : '',
                subtextColor: typeof props.subtextColor === 'string' ? props.subtextColor : '',
                headingFontFamily: typeof props.headingFontFamily === 'string' ? props.headingFontFamily : '',
                headingFontSize: Number.isFinite(Number(props.headingFontSize)) ? Number(props.headingFontSize) : undefined,
                headingFontWeight: typeof props.headingFontWeight === 'string' ? props.headingFontWeight : '',
                headingLineHeight: Number.isFinite(Number(props.headingLineHeight)) ? Number(props.headingLineHeight) : undefined,
                headingLetterSpacing: Number.isFinite(Number(props.headingLetterSpacing)) ? Number(props.headingLetterSpacing) : undefined,
                headingWordSpacing: Number.isFinite(Number(props.headingWordSpacing)) ? Number(props.headingWordSpacing) : undefined,
                headingMarginTop: Number.isFinite(Number(props.headingMarginTop)) ? Number(props.headingMarginTop) : undefined,
                headingMarginRight: Number.isFinite(Number(props.headingMarginRight)) ? Number(props.headingMarginRight) : undefined,
                headingMarginBottom: Number.isFinite(Number(props.headingMarginBottom)) ? Number(props.headingMarginBottom) : undefined,
                headingMarginLeft: Number.isFinite(Number(props.headingMarginLeft)) ? Number(props.headingMarginLeft) : undefined,
                headingMarginLinked: typeof props.headingMarginLinked === 'boolean'
                    ? props.headingMarginLinked
                    : inferLinked4(
                        toFiniteNumberOrUndefined(props.headingMarginTop),
                        toFiniteNumberOrUndefined(props.headingMarginRight),
                        toFiniteNumberOrUndefined(props.headingMarginBottom),
                        toFiniteNumberOrUndefined(props.headingMarginLeft),
                        true
                    ),
                subtextFontFamily: typeof props.subtextFontFamily === 'string' ? props.subtextFontFamily : '',
                subtextFontSize: Number.isFinite(Number(props.subtextFontSize)) ? Number(props.subtextFontSize) : undefined,
                subtextFontWeight: typeof props.subtextFontWeight === 'string' ? props.subtextFontWeight : '',
                subtextLineHeight: Number.isFinite(Number(props.subtextLineHeight)) ? Number(props.subtextLineHeight) : undefined,
                subtextLetterSpacing: Number.isFinite(Number(props.subtextLetterSpacing)) ? Number(props.subtextLetterSpacing) : undefined,
                subtextWordSpacing: Number.isFinite(Number(props.subtextWordSpacing)) ? Number(props.subtextWordSpacing) : undefined,
                subtextMarginTop: Number.isFinite(Number(props.subtextMarginTop)) ? Number(props.subtextMarginTop) : undefined,
                subtextMarginRight: Number.isFinite(Number(props.subtextMarginRight)) ? Number(props.subtextMarginRight) : undefined,
                subtextMarginBottom: Number.isFinite(Number(props.subtextMarginBottom)) ? Number(props.subtextMarginBottom) : undefined,
                subtextMarginLeft: Number.isFinite(Number(props.subtextMarginLeft)) ? Number(props.subtextMarginLeft) : undefined,
                subtextMarginLinked: typeof props.subtextMarginLinked === 'boolean'
                    ? props.subtextMarginLinked
                    : inferLinked4(
                        toFiniteNumberOrUndefined(props.subtextMarginTop),
                        toFiniteNumberOrUndefined(props.subtextMarginRight),
                        toFiniteNumberOrUndefined(props.subtextMarginBottom),
                        toFiniteNumberOrUndefined(props.subtextMarginLeft),
                        true
                    ),
                shadowColor: typeof props.shadowColor === 'string' ? props.shadowColor : '',
                shadowBlur: Number.isFinite(Number(props.shadowBlur)) ? Number(props.shadowBlur) : undefined,
                shadowX: Number.isFinite(Number(props.shadowX)) ? Number(props.shadowX) : undefined,
                shadowY: Number.isFinite(Number(props.shadowY)) ? Number(props.shadowY) : undefined,
                paddingTop: Number.isFinite(Number(props.paddingTop)) ? Number(props.paddingTop) : undefined,
                paddingRight: Number.isFinite(Number(props.paddingRight)) ? Number(props.paddingRight) : undefined,
                paddingBottom: Number.isFinite(Number(props.paddingBottom)) ? Number(props.paddingBottom) : undefined,
                paddingLeft: Number.isFinite(Number(props.paddingLeft)) ? Number(props.paddingLeft) : undefined,
                paddingLinked: typeof props.paddingLinked === 'boolean' ? props.paddingLinked : true,
                marginTop: Number.isFinite(Number(props.marginTop)) ? Number(props.marginTop) : undefined,
                marginBottom: Number.isFinite(Number(props.marginBottom)) ? Number(props.marginBottom) : undefined,
                marginRight: Number.isFinite(Number(props.marginRight)) ? Number(props.marginRight) : undefined,
                marginLeft: Number.isFinite(Number(props.marginLeft)) ? Number(props.marginLeft) : undefined,
                marginLinked: typeof props.marginLinked === 'boolean'
                    ? props.marginLinked
                    : inferLinked4(
                        toFiniteNumberOrUndefined(props.marginTop),
                        toFiniteNumberOrUndefined(props.marginRight),
                        toFiniteNumberOrUndefined(props.marginBottom),
                        toFiniteNumberOrUndefined(props.marginLeft),
                        true
                    ),
                backgroundColor: typeof props.backgroundColor === 'string' ? props.backgroundColor : '',
                borderStyle: typeof props.borderStyle === 'string' ? props.borderStyle : '',
                borderColor: typeof props.borderColor === 'string' ? props.borderColor : '',
                borderWidthLinked: typeof props.borderWidthLinked === 'boolean'
                    ? props.borderWidthLinked
                    : inferLinked4(
                        toFiniteNumberOrUndefined(borderWidthTop),
                        toFiniteNumberOrUndefined(borderWidthRight),
                        toFiniteNumberOrUndefined(borderWidthBottom),
                        toFiniteNumberOrUndefined(borderWidthLeft),
                        true
                    ),
                borderWidthTop,
                borderWidthRight,
                borderWidthBottom,
                borderWidthLeft,
                borderRadius: Number.isFinite(Number(props.borderRadius)) ? Number(props.borderRadius) : undefined,
                borderRadiusLinked: typeof props.borderRadiusLinked === 'boolean'
                    ? props.borderRadiusLinked
                    : inferLinked4(
                        toFiniteNumberOrUndefined(props.borderRadiusTop),
                        toFiniteNumberOrUndefined(props.borderRadiusRight),
                        toFiniteNumberOrUndefined(props.borderRadiusBottom),
                        toFiniteNumberOrUndefined(props.borderRadiusLeft),
                        true
                    ),
                borderRadiusTop: Number.isFinite(Number(props.borderRadiusTop))
                    ? Number(props.borderRadiusTop)
                    : Number.isFinite(Number(props.borderRadius))
                      ? Number(props.borderRadius)
                      : undefined,
                borderRadiusRight: Number.isFinite(Number(props.borderRadiusRight))
                    ? Number(props.borderRadiusRight)
                    : Number.isFinite(Number(props.borderRadius))
                      ? Number(props.borderRadius)
                      : undefined,
                borderRadiusBottom: Number.isFinite(Number(props.borderRadiusBottom))
                    ? Number(props.borderRadiusBottom)
                    : Number.isFinite(Number(props.borderRadius))
                      ? Number(props.borderRadius)
                      : undefined,
                borderRadiusLeft: Number.isFinite(Number(props.borderRadiusLeft))
                    ? Number(props.borderRadiusLeft)
                    : Number.isFinite(Number(props.borderRadius))
                      ? Number(props.borderRadius)
                      : undefined,
                ...motion,
            },
        };
    }

    if (type === 'pricing_table') {
        const normalizePlan = (p) => {
            if (!p || typeof p !== 'object') {
                return null;
            }
            const features = Array.isArray(p.features)
                ? p.features
                      .filter((x) => typeof x === 'string')
                      .map((x) => String(x))
                      .slice(0, 10)
                : [];

            return {
                title: typeof p.title === 'string' ? p.title : 'Plan',
                description: typeof p.description === 'string' ? p.description : '',
                monthlyPrice: typeof p.monthlyPrice === 'string' ? p.monthlyPrice : '$0',
                monthlyPeriod: typeof p.monthlyPeriod === 'string' ? p.monthlyPeriod : '/mo',
                yearlyPrice: typeof p.yearlyPrice === 'string' ? p.yearlyPrice : '$0',
                yearlyPeriod: typeof p.yearlyPeriod === 'string' ? p.yearlyPeriod : '/yr',
                features,
                buttonText: typeof p.buttonText === 'string' ? p.buttonText : 'Get started',
                buttonUrl: typeof p.buttonUrl === 'string' ? p.buttonUrl : '#',
                buttonTarget: ['_self', '_blank'].includes(p.buttonTarget) ? p.buttonTarget : '_self',
                featured: typeof p.featured === 'boolean' ? p.featured : false,
                badge: typeof p.badge === 'string' ? p.badge : '',
                icon: typeof p.icon === 'string' ? p.icon : '',
                featuresTitle: typeof p.featuresTitle === 'string' ? p.featuresTitle : '',
                discount: typeof p.discount === 'string' ? p.discount : '',
            };
        };

        let plan = normalizePlan(props.plan);
        if (!plan && Array.isArray(props.plans)) {
            const legacy = props.plans.filter((p) => p && typeof p === 'object');
            const first = legacy[0];
            const second = legacy[1];
            if (first) {
                plan = normalizePlan({
                    ...first,
                    monthlyPrice: typeof first.price === 'string' ? first.price : '$0',
                    monthlyPeriod: typeof first.period === 'string' ? first.period : '/mo',
                    yearlyPrice: second && typeof second.price === 'string' ? second.price : first.price,
                    yearlyPeriod: second && typeof second.period === 'string' ? second.period : '/yr',
                });
            }
        }

        const gapRaw = Number(props.gap);
        const gap = Number.isFinite(gapRaw) ? Math.max(0, Math.round(gapRaw)) : 24;

        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Pricing Table',
            advanced,
            props: {
                align: ['left', 'center', 'right'].includes(props.align) ? props.align : 'center',
                gap,
                featureIconType: ['check', 'custom'].includes(props.featureIconType)
                    ? props.featureIconType
                    : (typeof props.featureIconUrl === 'string' && props.featureIconUrl.trim() ? 'custom' : 'check'),
                featureIconUrl: typeof props.featureIconUrl === 'string' ? props.featureIconUrl : '',
                plan: plan || {
                    title: 'Pro',
                    description: '',
                    monthlyPrice: '$29',
                    monthlyPeriod: '/mo',
                    yearlyPrice: '$290',
                    yearlyPeriod: '/yr',
                    features: ['Feature 1', 'Feature 2', 'Feature 3', 'Feature 4'],
                    buttonText: 'Get started',
                    buttonUrl: '#',
                    buttonTarget: '_self',
                    featured: true,
                    badge: '',
                    icon: '',
                    featuresTitle: '',
                    discount: '',
                },
                style: ['default', 'classic', 'dark', 'header', 'minimal', 'split', 'gradient'].includes(props.style)
                    ? props.style
                    : 'default',
                cardBackgroundColor: typeof props.cardBackgroundColor === 'string' ? props.cardBackgroundColor : '',
                cardBackgroundType: ['color', 'gradient', 'image'].includes(props.cardBackgroundType) ? props.cardBackgroundType : 'color',
                cardBackgroundGradientFrom: typeof props.cardBackgroundGradientFrom === 'string' ? props.cardBackgroundGradientFrom : '',
                cardBackgroundGradientTo: typeof props.cardBackgroundGradientTo === 'string' ? props.cardBackgroundGradientTo : '',
                cardBackgroundGradientType: ['linear', 'radial'].includes(props.cardBackgroundGradientType) ? props.cardBackgroundGradientType : 'linear',
                cardBackgroundGradientPosition: typeof props.cardBackgroundGradientPosition === 'string' ? props.cardBackgroundGradientPosition : 'top center',
                cardBackgroundImageUrl: typeof props.cardBackgroundImageUrl === 'string' ? props.cardBackgroundImageUrl : '',
                headerBackgroundColor: typeof props.headerBackgroundColor === 'string' ? props.headerBackgroundColor : '',
                headerTextColor: typeof props.headerTextColor === 'string' ? props.headerTextColor : '',
                badgeBackgroundColor: typeof props.badgeBackgroundColor === 'string' ? props.badgeBackgroundColor : '',
                badgeTextColor: typeof props.badgeTextColor === 'string' ? props.badgeTextColor : '',
                darkBackgroundColor: typeof props.darkBackgroundColor === 'string' ? props.darkBackgroundColor : '',
                darkTextColor: typeof props.darkTextColor === 'string' ? props.darkTextColor : '',
                gradientFrom: typeof props.gradientFrom === 'string' ? props.gradientFrom : '',
                gradientTo: typeof props.gradientTo === 'string' ? props.gradientTo : '',
                cardBorderColor: typeof props.cardBorderColor === 'string' ? props.cardBorderColor : '',
                cardBorderWidth: Number.isFinite(Number(props.cardBorderWidth)) ? Number(props.cardBorderWidth) : undefined,
                cardBorderRadius: Number.isFinite(Number(props.cardBorderRadius)) ? Number(props.cardBorderRadius) : undefined,
                cardPadding: Number.isFinite(Number(props.cardPadding)) ? Number(props.cardPadding) : undefined,
                featuredBackgroundColor: typeof props.featuredBackgroundColor === 'string' ? props.featuredBackgroundColor : '',
                featuredBorderColor: typeof props.featuredBorderColor === 'string' ? props.featuredBorderColor : '',
                buttonBackgroundColor: typeof props.buttonBackgroundColor === 'string' ? props.buttonBackgroundColor : '',
                buttonTextColor: typeof props.buttonTextColor === 'string' ? props.buttonTextColor : '',
                buttonBorderRadius: Number.isFinite(Number(props.buttonBorderRadius)) ? Number(props.buttonBorderRadius) : undefined,
                buttonPosition: ['default', 'center'].includes(props.buttonPosition) ? props.buttonPosition : 'default',
                iconColor: typeof props.iconColor === 'string' ? props.iconColor : '',
                titleColor: typeof props.titleColor === 'string' ? props.titleColor : '',
                priceColor: typeof props.priceColor === 'string' ? props.priceColor : '',
                descriptionColor: typeof props.descriptionColor === 'string' ? props.descriptionColor : '',
                featureTextColor: typeof props.featureTextColor === 'string' ? props.featureTextColor : '',
                excludedFeatureTextColor: typeof props.excludedFeatureTextColor === 'string' ? props.excludedFeatureTextColor : '',
                featureIconColor: typeof props.featureIconColor === 'string' ? props.featureIconColor : '',
                excludedFeatureIconColor: typeof props.excludedFeatureIconColor === 'string' ? props.excludedFeatureIconColor : '',
                titleFontFamily: typeof props.titleFontFamily === 'string' ? props.titleFontFamily : '',
                titleFontSize: props.titleFontSize === undefined || props.titleFontSize === '' ? undefined : props.titleFontSize,
                titleFontWeight: typeof props.titleFontWeight === 'string' ? props.titleFontWeight : '',
                titleLineHeight: props.titleLineHeight === undefined || props.titleLineHeight === '' ? undefined : props.titleLineHeight,
                titleLetterSpacing: props.titleLetterSpacing === undefined || props.titleLetterSpacing === '' ? undefined : props.titleLetterSpacing,
                titleWordSpacing: props.titleWordSpacing === undefined || props.titleWordSpacing === '' ? undefined : props.titleWordSpacing,
                priceFontFamily: typeof props.priceFontFamily === 'string' ? props.priceFontFamily : '',
                priceFontSize: props.priceFontSize === undefined || props.priceFontSize === '' ? undefined : props.priceFontSize,
                priceFontWeight: typeof props.priceFontWeight === 'string' ? props.priceFontWeight : '',
                priceLineHeight: props.priceLineHeight === undefined || props.priceLineHeight === '' ? undefined : props.priceLineHeight,
                priceLetterSpacing: props.priceLetterSpacing === undefined || props.priceLetterSpacing === '' ? undefined : props.priceLetterSpacing,
                priceWordSpacing: props.priceWordSpacing === undefined || props.priceWordSpacing === '' ? undefined : props.priceWordSpacing,
                descriptionFontFamily: typeof props.descriptionFontFamily === 'string' ? props.descriptionFontFamily : '',
                descriptionFontSize: props.descriptionFontSize === undefined || props.descriptionFontSize === '' ? undefined : props.descriptionFontSize,
                descriptionFontWeight: typeof props.descriptionFontWeight === 'string' ? props.descriptionFontWeight : '',
                descriptionLineHeight: props.descriptionLineHeight === undefined || props.descriptionLineHeight === '' ? undefined : props.descriptionLineHeight,
                descriptionLetterSpacing: props.descriptionLetterSpacing === undefined || props.descriptionLetterSpacing === '' ? undefined : props.descriptionLetterSpacing,
                descriptionWordSpacing: props.descriptionWordSpacing === undefined || props.descriptionWordSpacing === '' ? undefined : props.descriptionWordSpacing,
                featureFontFamily: typeof props.featureFontFamily === 'string' ? props.featureFontFamily : '',
                featureFontSize: props.featureFontSize === undefined || props.featureFontSize === '' ? undefined : props.featureFontSize,
                featureFontWeight: typeof props.featureFontWeight === 'string' ? props.featureFontWeight : '',
                featureLineHeight: props.featureLineHeight === undefined || props.featureLineHeight === '' ? undefined : props.featureLineHeight,
                featureLetterSpacing: props.featureLetterSpacing === undefined || props.featureLetterSpacing === '' ? undefined : props.featureLetterSpacing,
                featureWordSpacing: props.featureWordSpacing === undefined || props.featureWordSpacing === '' ? undefined : props.featureWordSpacing,
                excludedFeatureFontFamily: typeof props.excludedFeatureFontFamily === 'string' ? props.excludedFeatureFontFamily : '',
                excludedFeatureFontSize: props.excludedFeatureFontSize === undefined || props.excludedFeatureFontSize === '' ? undefined : props.excludedFeatureFontSize,
                excludedFeatureFontWeight: typeof props.excludedFeatureFontWeight === 'string' ? props.excludedFeatureFontWeight : '',
                excludedFeatureLineHeight: props.excludedFeatureLineHeight === undefined || props.excludedFeatureLineHeight === '' ? undefined : props.excludedFeatureLineHeight,
                excludedFeatureLetterSpacing: props.excludedFeatureLetterSpacing === undefined || props.excludedFeatureLetterSpacing === '' ? undefined : props.excludedFeatureLetterSpacing,
                excludedFeatureWordSpacing: props.excludedFeatureWordSpacing === undefined || props.excludedFeatureWordSpacing === '' ? undefined : props.excludedFeatureWordSpacing,
                buttonFontFamily: typeof props.buttonFontFamily === 'string' ? props.buttonFontFamily : '',
                buttonFontSize: props.buttonFontSize === undefined || props.buttonFontSize === '' ? undefined : props.buttonFontSize,
                buttonFontWeight: typeof props.buttonFontWeight === 'string' ? props.buttonFontWeight : '',
                buttonLineHeight: props.buttonLineHeight === undefined || props.buttonLineHeight === '' ? undefined : props.buttonLineHeight,
                buttonLetterSpacing: props.buttonLetterSpacing === undefined || props.buttonLetterSpacing === '' ? undefined : props.buttonLetterSpacing,
                buttonWordSpacing: props.buttonWordSpacing === undefined || props.buttonWordSpacing === '' ? undefined : props.buttonWordSpacing,
                cardBorderWidthLinked: typeof props.cardBorderWidthLinked === 'boolean' ? props.cardBorderWidthLinked : true,
                cardBorderWidthTop: Number.isFinite(Number(props.cardBorderWidthTop)) ? Number(props.cardBorderWidthTop) : undefined,
                cardBorderWidthRight: Number.isFinite(Number(props.cardBorderWidthRight)) ? Number(props.cardBorderWidthRight) : undefined,
                cardBorderWidthBottom: Number.isFinite(Number(props.cardBorderWidthBottom)) ? Number(props.cardBorderWidthBottom) : undefined,
                cardBorderWidthLeft: Number.isFinite(Number(props.cardBorderWidthLeft)) ? Number(props.cardBorderWidthLeft) : undefined,
                cardBorderRadiusLinked: typeof props.cardBorderRadiusLinked === 'boolean' ? props.cardBorderRadiusLinked : true,
                cardBorderRadiusTopLeft: Number.isFinite(Number(props.cardBorderRadiusTopLeft)) ? Number(props.cardBorderRadiusTopLeft) : undefined,
                cardBorderRadiusTopRight: Number.isFinite(Number(props.cardBorderRadiusTopRight)) ? Number(props.cardBorderRadiusTopRight) : undefined,
                cardBorderRadiusBottomRight: Number.isFinite(Number(props.cardBorderRadiusBottomRight)) ? Number(props.cardBorderRadiusBottomRight) : undefined,
                cardBorderRadiusBottomLeft: Number.isFinite(Number(props.cardBorderRadiusBottomLeft)) ? Number(props.cardBorderRadiusBottomLeft) : undefined,
                cardPaddingLinked: typeof props.cardPaddingLinked === 'boolean' ? props.cardPaddingLinked : true,
                cardPaddingTop: Number.isFinite(Number(props.cardPaddingTop)) ? Number(props.cardPaddingTop) : undefined,
                cardPaddingRight: Number.isFinite(Number(props.cardPaddingRight)) ? Number(props.cardPaddingRight) : undefined,
                cardPaddingBottom: Number.isFinite(Number(props.cardPaddingBottom)) ? Number(props.cardPaddingBottom) : undefined,
                cardPaddingLeft: Number.isFinite(Number(props.cardPaddingLeft)) ? Number(props.cardPaddingLeft) : undefined,
                paddingTop: Number.isFinite(Number(props.paddingTop)) ? Number(props.paddingTop) : undefined,
                paddingRight: Number.isFinite(Number(props.paddingRight)) ? Number(props.paddingRight) : undefined,
                paddingBottom: Number.isFinite(Number(props.paddingBottom)) ? Number(props.paddingBottom) : undefined,
                paddingLeft: Number.isFinite(Number(props.paddingLeft)) ? Number(props.paddingLeft) : undefined,
                paddingLinked: typeof props.paddingLinked === 'boolean' ? props.paddingLinked : true,
                marginTop: Number.isFinite(Number(props.marginTop)) ? Number(props.marginTop) : undefined,
                marginBottom: Number.isFinite(Number(props.marginBottom)) ? Number(props.marginBottom) : undefined,
                marginRight: Number.isFinite(Number(props.marginRight)) ? Number(props.marginRight) : undefined,
                marginLeft: Number.isFinite(Number(props.marginLeft)) ? Number(props.marginLeft) : undefined,
                marginLinked: typeof props.marginLinked === 'boolean'
                    ? props.marginLinked
                    : inferLinked4(
                        toFiniteNumberOrUndefined(props.marginTop),
                        toFiniteNumberOrUndefined(props.marginRight),
                        toFiniteNumberOrUndefined(props.marginBottom),
                        toFiniteNumberOrUndefined(props.marginLeft),
                        true
                    ),
                cardBorderEffect: typeof props.cardBorderEffect === 'string' ? props.cardBorderEffect : '',
                cardBorderEffectColor: typeof props.cardBorderEffectColor === 'string' ? props.cardBorderEffectColor : '',
                cardBorderEffectColor2: typeof props.cardBorderEffectColor2 === 'string' ? props.cardBorderEffectColor2 : '',
                cardBorderEffectSpeed: Number.isFinite(Number(props.cardBorderEffectSpeed)) ? Number(props.cardBorderEffectSpeed) : undefined,
                cardBorderEffectThickness: Number.isFinite(Number(props.cardBorderEffectThickness)) ? Number(props.cardBorderEffectThickness) : undefined,
                ...motion,
            },
        };
    }

    if (type === 'faqs') {
        const items = Array.isArray(props.items) ? props.items : [];
        const normalizedItems = items
            .filter((it) => it && typeof it === 'object')
            .slice(0, 50)
            .map((it) => ({
                question: typeof it.question === 'string' ? it.question : 'Question',
                answer: typeof it.answer === 'string' ? it.answer : 'Answer',
            }));

        const marginTop = Number.isFinite(Number(props.marginTop)) ? Number(props.marginTop) : undefined;
        const marginRight = Number.isFinite(Number(props.marginRight)) ? Number(props.marginRight) : undefined;
        const marginBottom = Number.isFinite(Number(props.marginBottom)) ? Number(props.marginBottom) : undefined;
        const marginLeft = Number.isFinite(Number(props.marginLeft)) ? Number(props.marginLeft) : undefined;

        const marginLinked = typeof props.marginLinked === 'boolean'
            ? props.marginLinked
            : inferLinked4(
                toFiniteNumberOrUndefined(marginTop),
                toFiniteNumberOrUndefined(marginRight),
                toFiniteNumberOrUndefined(marginBottom),
                toFiniteNumberOrUndefined(marginLeft),
                true
            );

        const paddingTop = Number.isFinite(Number(props.paddingTop))
            ? Number(props.paddingTop)
            : Number.isFinite(Number(props.paddingY))
              ? Number(props.paddingY)
              : undefined;
        const paddingRight = Number.isFinite(Number(props.paddingRight))
            ? Number(props.paddingRight)
            : Number.isFinite(Number(props.paddingX))
              ? Number(props.paddingX)
              : undefined;
        const paddingBottom = Number.isFinite(Number(props.paddingBottom))
            ? Number(props.paddingBottom)
            : Number.isFinite(Number(props.paddingY))
              ? Number(props.paddingY)
              : undefined;
        const paddingLeft = Number.isFinite(Number(props.paddingLeft))
            ? Number(props.paddingLeft)
            : Number.isFinite(Number(props.paddingX))
              ? Number(props.paddingX)
              : undefined;

        const paddingLinked = typeof props.paddingLinked === 'boolean'
            ? props.paddingLinked
            : inferLinked4(
                toFiniteNumberOrUndefined(paddingTop),
                toFiniteNumberOrUndefined(paddingRight),
                toFiniteNumberOrUndefined(paddingBottom),
                toFiniteNumberOrUndefined(paddingLeft),
                true
            );

        const backgroundType = ['color', 'classic', 'gradient'].includes(props.backgroundType) ? props.backgroundType : 'classic';
        const gradientType = ['linear', 'radial'].includes(props.gradientType) ? props.gradientType : 'linear';
        const gradientPosition = typeof props.gradientPosition === 'string' ? props.gradientPosition : '';
        const gradientFromLocation = Number.isFinite(Number(props.gradientFromLocation)) ? Number(props.gradientFromLocation) : undefined;
        const gradientToLocation = Number.isFinite(Number(props.gradientToLocation)) ? Number(props.gradientToLocation) : undefined;

        const itemPaddingX = Number.isFinite(Number(props.itemPaddingX)) ? Number(props.itemPaddingX) : undefined;
        const itemPaddingY = Number.isFinite(Number(props.itemPaddingY)) ? Number(props.itemPaddingY) : undefined;

        const itemPaddingTop = Number.isFinite(Number(props.itemPaddingTop))
            ? Number(props.itemPaddingTop)
            : Number.isFinite(Number(props.itemPaddingY))
              ? Number(props.itemPaddingY)
              : undefined;
        const itemPaddingRight = Number.isFinite(Number(props.itemPaddingRight))
            ? Number(props.itemPaddingRight)
            : Number.isFinite(Number(props.itemPaddingX))
              ? Number(props.itemPaddingX)
              : undefined;
        const itemPaddingBottom = Number.isFinite(Number(props.itemPaddingBottom))
            ? Number(props.itemPaddingBottom)
            : Number.isFinite(Number(props.itemPaddingY))
              ? Number(props.itemPaddingY)
              : undefined;
        const itemPaddingLeft = Number.isFinite(Number(props.itemPaddingLeft))
            ? Number(props.itemPaddingLeft)
            : Number.isFinite(Number(props.itemPaddingX))
              ? Number(props.itemPaddingX)
              : undefined;

        const itemPaddingLinked = typeof props.itemPaddingLinked === 'boolean'
            ? props.itemPaddingLinked
            : inferLinked4(
                toFiniteNumberOrUndefined(itemPaddingTop),
                toFiniteNumberOrUndefined(itemPaddingRight),
                toFiniteNumberOrUndefined(itemPaddingBottom),
                toFiniteNumberOrUndefined(itemPaddingLeft),
                true
            );

        const gapRaw = Number(props.gap);
        const gap = Number.isFinite(gapRaw) ? Math.max(0, Math.round(gapRaw)) : 10;

        const carouselEnabled = props.carouselEnabled === true;
        const carouselSpeedRaw = Number(props.carouselSpeed);
        const carouselSpeed = Number.isFinite(carouselSpeedRaw) ? Math.max(1, Math.min(60, carouselSpeedRaw)) : undefined;
        const carouselArrows = typeof props.carouselArrows === 'boolean' ? props.carouselArrows : true;
        const carouselArrowPosition = ['inside', 'outside'].includes(props.carouselArrowPosition)
            ? props.carouselArrowPosition
            : 'inside';
        const carouselEase = ['linear', 'ease', 'ease-in', 'ease-out', 'ease-in-out'].includes(props.carouselEase)
            ? props.carouselEase
            : 'ease';

        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'FAQs',
            advanced,
            props: {
                items: normalizedItems.length ? normalizedItems : [{ question: 'Question', answer: 'Answer' }],
                openFirst: typeof props.openFirst === 'boolean' ? props.openFirst : false,
                align: ['left', 'center', 'right'].includes(props.align) ? props.align : 'left',
                iconType: ['none', 'chevron', 'plus', 'arrow'].includes(props.iconType) ? props.iconType : 'none',
                iconPosition: props.iconPosition === 'left' ? 'left' : 'right',
                gap,

                carouselEnabled,
                carouselSpeed,
                carouselArrows,
                carouselArrowPosition,
                carouselEase,

                paddingLinked,
                paddingTop,
                paddingRight,
                paddingBottom,
                paddingLeft,
                marginLinked,
                marginTop,
                marginRight,
                marginBottom,
                marginLeft,

                backgroundType,
                backgroundColor: typeof props.backgroundColor === 'string' ? props.backgroundColor : '',
                gradientFrom: typeof props.gradientFrom === 'string' ? props.gradientFrom : '',
                gradientTo: typeof props.gradientTo === 'string' ? props.gradientTo : '',
                gradientFromLocation,
                gradientToLocation,
                gradientType,
                gradientPosition,

                itemBackgroundColor: typeof props.itemBackgroundColor === 'string' ? props.itemBackgroundColor : '',
                itemBackgroundType: ['color', 'classic', 'gradient'].includes(props.itemBackgroundType) ? props.itemBackgroundType : 'classic',
                itemGradientFrom: typeof props.itemGradientFrom === 'string' ? props.itemGradientFrom : '',
                itemGradientTo: typeof props.itemGradientTo === 'string' ? props.itemGradientTo : '',
                itemGradientFromLocation: Number.isFinite(Number(props.itemGradientFromLocation)) ? Number(props.itemGradientFromLocation) : undefined,
                itemGradientToLocation: Number.isFinite(Number(props.itemGradientToLocation)) ? Number(props.itemGradientToLocation) : undefined,
                itemGradientType: ['linear', 'radial'].includes(props.itemGradientType) ? props.itemGradientType : 'linear',
                itemGradientPosition: typeof props.itemGradientPosition === 'string' ? props.itemGradientPosition : '',
                itemGradientAngle: Number.isFinite(Number(props.itemGradientAngle)) ? Number(props.itemGradientAngle) : undefined,
                itemBackdropBlur: Number.isFinite(Number(props.itemBackdropBlur)) ? Number(props.itemBackdropBlur) : undefined,
                itemBorderStyle: typeof props.itemBorderStyle === 'string' ? props.itemBorderStyle : '',
                itemBorderColor: typeof props.itemBorderColor === 'string' ? props.itemBorderColor : '',
                itemBorderWidth: Number.isFinite(Number(props.itemBorderWidth)) ? Number(props.itemBorderWidth) : undefined,
                itemBorderWidthLinked: typeof props.itemBorderWidthLinked === 'boolean' ? props.itemBorderWidthLinked : undefined,
                itemBorderWidthTop: Number.isFinite(Number(props.itemBorderWidthTop)) ? Number(props.itemBorderWidthTop) : undefined,
                itemBorderWidthRight: Number.isFinite(Number(props.itemBorderWidthRight)) ? Number(props.itemBorderWidthRight) : undefined,
                itemBorderWidthBottom: Number.isFinite(Number(props.itemBorderWidthBottom)) ? Number(props.itemBorderWidthBottom) : undefined,
                itemBorderWidthLeft: Number.isFinite(Number(props.itemBorderWidthLeft)) ? Number(props.itemBorderWidthLeft) : undefined,
                itemBorderRadius: Number.isFinite(Number(props.itemBorderRadius)) ? Number(props.itemBorderRadius) : undefined,
                itemMarginLinked: typeof props.itemMarginLinked === 'boolean' ? props.itemMarginLinked : undefined,
                itemMarginTop: Number.isFinite(Number(props.itemMarginTop)) ? Number(props.itemMarginTop) : undefined,
                itemMarginRight: Number.isFinite(Number(props.itemMarginRight)) ? Number(props.itemMarginRight) : undefined,
                itemMarginBottom: Number.isFinite(Number(props.itemMarginBottom)) ? Number(props.itemMarginBottom) : undefined,
                itemMarginLeft: Number.isFinite(Number(props.itemMarginLeft)) ? Number(props.itemMarginLeft) : undefined,
                itemPaddingLinked,
                itemPaddingTop,
                itemPaddingRight,
                itemPaddingBottom,
                itemPaddingLeft,
                itemPaddingX,
                itemPaddingY,

                questionColor: typeof props.questionColor === 'string' ? props.questionColor : '',
                questionFontFamily: typeof props.questionFontFamily === 'string' ? props.questionFontFamily : '',
                questionFontSize: Number.isFinite(Number(props.questionFontSize)) ? Number(props.questionFontSize) : undefined,
                questionFontWeight: typeof props.questionFontWeight === 'string' ? props.questionFontWeight : '',
                questionLineHeight: Number.isFinite(Number(props.questionLineHeight)) ? Number(props.questionLineHeight) : undefined,
                questionLetterSpacing: Number.isFinite(Number(props.questionLetterSpacing)) ? Number(props.questionLetterSpacing) : undefined,
                questionWordSpacing: Number.isFinite(Number(props.questionWordSpacing)) ? Number(props.questionWordSpacing) : undefined,
                questionBorderStyle: typeof props.questionBorderStyle === 'string' ? props.questionBorderStyle : '',
                questionBorderColor: typeof props.questionBorderColor === 'string' ? props.questionBorderColor : '',
                questionBorderWidthLinked: typeof props.questionBorderWidthLinked === 'boolean' ? props.questionBorderWidthLinked : undefined,
                questionBorderWidthTop: Number.isFinite(Number(props.questionBorderWidthTop)) ? Number(props.questionBorderWidthTop) : undefined,
                questionBorderWidthRight: Number.isFinite(Number(props.questionBorderWidthRight)) ? Number(props.questionBorderWidthRight) : undefined,
                questionBorderWidthBottom: Number.isFinite(Number(props.questionBorderWidthBottom)) ? Number(props.questionBorderWidthBottom) : undefined,
                questionBorderWidthLeft: Number.isFinite(Number(props.questionBorderWidthLeft)) ? Number(props.questionBorderWidthLeft) : undefined,

                answerColor: typeof props.answerColor === 'string' ? props.answerColor : '',
                answerFontFamily: typeof props.answerFontFamily === 'string' ? props.answerFontFamily : '',
                answerFontSize: Number.isFinite(Number(props.answerFontSize)) ? Number(props.answerFontSize) : undefined,
                answerFontWeight: typeof props.answerFontWeight === 'string' ? props.answerFontWeight : '',
                answerLineHeight: Number.isFinite(Number(props.answerLineHeight)) ? Number(props.answerLineHeight) : undefined,
                answerLetterSpacing: Number.isFinite(Number(props.answerLetterSpacing)) ? Number(props.answerLetterSpacing) : undefined,
                answerWordSpacing: Number.isFinite(Number(props.answerWordSpacing)) ? Number(props.answerWordSpacing) : undefined,
                ...motion,
            },
        };
    }

    if (type === 'tabs') {
        const rawItems = Array.isArray(props.items) ? props.items : [];
        const normalizedItems = rawItems
            .filter((it) => it && typeof it === 'object')
            .map((it) => {
                const base = {
                    title: typeof it.title === 'string' ? it.title : 'Tab',
                    id: String(it.id || uid('tab')),
                };
                if (Array.isArray(it.widgets)) {
                    base.widgets = it.widgets
                        .filter((iw) => iw && typeof iw === 'object')
                        .map((iw) => normalizeWidget(iw))
                        .filter(Boolean);
                } else if (typeof it.content === 'string' && it.content.trim()) {
                    base.widgets = [
                        normalizeWidget({ id: uid('w'), type: 'rich_text', name: '', props: { html: it.content } }),
                    ].filter(Boolean);
                } else {
                    base.widgets = [];
                }
                return base;
            });

        const fallbackItems = [
            { title: 'Tab 1', id: uid('tab'), widgets: [] },
            { title: 'Tab 2', id: uid('tab'), widgets: [] },
        ];

        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Tabs',
            advanced,
            props: {
                ...props,
                ...motion,
                items: normalizedItems.length ? normalizedItems : fallbackItems,
                orientation: ['horizontal', 'vertical', 'timeline'].includes(props.orientation) ? props.orientation : 'horizontal',
                activeIndex: Number.isFinite(Number(props.activeIndex)) ? Math.max(0, Math.round(Number(props.activeIndex))) : 0,
            },
        };
    }

    if (type === 'nav_menu') {
        const rawItems = Array.isArray(props.items) ? props.items : [];
        const resolveItem = (it) => {
            if (!it || typeof it !== 'object') return null;
            const rawType = it.type || 'link';
            let itemType = ['link', 'dropdown', 'megamenu'].includes(rawType) ? rawType : 'link';
            let url = typeof it.url === 'string' ? it.url : '#';
            let label = typeof it.label === 'string' ? it.label : 'Link';

            // Resolve route/page types from site menu system
            if (rawType === 'route' && it.route) {
                itemType = 'link';
                url = '/' + it.route.replace(/\./g, '/');
                if (!label || label === 'Link') label = it.route;
            }
            if (rawType === 'page' && it.page_id) {
                itemType = 'link';
                url = '/page-' + it.page_id;
            }

            const base = { type: itemType, label, url };
            if (itemType === 'dropdown') {
                base.children = Array.isArray(it.children)
                    ? it.children.map(resolveItem).filter(Boolean)
                    : [];
            }
            if (itemType === 'megamenu') {
                base.mega_menu_id = (typeof it.mega_menu_id === 'number' || typeof it.mega_menu_id === 'string') ? Number(it.mega_menu_id) : null;
                base.panel_width = ['full', 'boxed'].includes(it.panel_width) ? it.panel_width : 'full';
            }
            return base;
        };
        const normalizedItems = rawItems.map(resolveItem).filter(Boolean);

        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Nav Menu',
            advanced,
            props: {
                items: normalizedItems,
                nav_menu_id: props.nav_menu_id != null ? Number(props.nav_menu_id) : null,
                align: ['left', 'center', 'right'].includes(props.align) ? props.align : 'left',
                gap: Number.isFinite(Number(props.gap)) ? Math.max(0, Math.round(Number(props.gap))) : 24,
                color: typeof props.color === 'string' ? props.color : '',
                fontFamily: typeof props.fontFamily === 'string' ? props.fontFamily : '',
                fontSize: Number.isFinite(Number(props.fontSize)) ? Number(props.fontSize) : undefined,
                fontWeight: typeof props.fontWeight === 'string' ? props.fontWeight : '',
                lineHeight: Number.isFinite(Number(props.lineHeight)) ? Number(props.lineHeight) : undefined,
                letterSpacing: Number.isFinite(Number(props.letterSpacing)) ? Number(props.letterSpacing) : undefined,
                wordSpacing: Number.isFinite(Number(props.wordSpacing)) ? Number(props.wordSpacing) : undefined,
                backgroundColor: typeof props.backgroundColor === 'string' ? props.backgroundColor : '',
                paddingTop: Number.isFinite(Number(props.paddingTop)) ? Number(props.paddingTop) : undefined,
                paddingRight: Number.isFinite(Number(props.paddingRight)) ? Number(props.paddingRight) : undefined,
                paddingBottom: Number.isFinite(Number(props.paddingBottom)) ? Number(props.paddingBottom) : undefined,
                paddingLeft: Number.isFinite(Number(props.paddingLeft)) ? Number(props.paddingLeft) : undefined,
                paddingLinked: typeof props.paddingLinked === 'boolean' ? props.paddingLinked : true,
                marginTop: Number.isFinite(Number(props.marginTop)) ? Number(props.marginTop) : undefined,
                marginRight: Number.isFinite(Number(props.marginRight)) ? Number(props.marginRight) : undefined,
                marginBottom: Number.isFinite(Number(props.marginBottom)) ? Number(props.marginBottom) : undefined,
                marginLeft: Number.isFinite(Number(props.marginLeft)) ? Number(props.marginLeft) : undefined,
                marginLinked: typeof props.marginLinked === 'boolean' ? props.marginLinked : false,

                dropdownMarginTop: Number.isFinite(Number(props.dropdownMarginTop)) ? Number(props.dropdownMarginTop) : undefined,
                dropdownMarginRight: Number.isFinite(Number(props.dropdownMarginRight)) ? Number(props.dropdownMarginRight) : undefined,
                dropdownMarginBottom: Number.isFinite(Number(props.dropdownMarginBottom)) ? Number(props.dropdownMarginBottom) : undefined,
                dropdownMarginLeft: Number.isFinite(Number(props.dropdownMarginLeft)) ? Number(props.dropdownMarginLeft) : undefined,
                dropdownMarginLinked: typeof props.dropdownMarginLinked === 'boolean' ? props.dropdownMarginLinked : false,
                dropdownPaddingTop: Number.isFinite(Number(props.dropdownPaddingTop)) ? Number(props.dropdownPaddingTop) : undefined,
                dropdownPaddingRight: Number.isFinite(Number(props.dropdownPaddingRight)) ? Number(props.dropdownPaddingRight) : undefined,
                dropdownPaddingBottom: Number.isFinite(Number(props.dropdownPaddingBottom)) ? Number(props.dropdownPaddingBottom) : undefined,
                dropdownPaddingLeft: Number.isFinite(Number(props.dropdownPaddingLeft)) ? Number(props.dropdownPaddingLeft) : undefined,
                dropdownPaddingLinked: typeof props.dropdownPaddingLinked === 'boolean' ? props.dropdownPaddingLinked : true,
                dropdownBorderStyle: ['solid', 'dashed', 'dotted'].includes(props.dropdownBorderStyle) ? props.dropdownBorderStyle : '',
                dropdownBorderColor: typeof props.dropdownBorderColor === 'string' ? props.dropdownBorderColor : '',
                dropdownBorderWidthTop: Number.isFinite(Number(props.dropdownBorderWidthTop)) ? Number(props.dropdownBorderWidthTop) : 0,
                dropdownBorderWidthRight: Number.isFinite(Number(props.dropdownBorderWidthRight)) ? Number(props.dropdownBorderWidthRight) : 0,
                dropdownBorderWidthBottom: Number.isFinite(Number(props.dropdownBorderWidthBottom)) ? Number(props.dropdownBorderWidthBottom) : 0,
                dropdownBorderWidthLeft: Number.isFinite(Number(props.dropdownBorderWidthLeft)) ? Number(props.dropdownBorderWidthLeft) : 0,
                dropdownBorderWidthLinked: typeof props.dropdownBorderWidthLinked === 'boolean' ? props.dropdownBorderWidthLinked : true,
                dropdownBorderRadius: Number.isFinite(Number(props.dropdownBorderRadius)) ? Number(props.dropdownBorderRadius) : undefined,
                dropdownBackgroundColor: typeof props.dropdownBackgroundColor === 'string' ? props.dropdownBackgroundColor : '',

                dropdownItemColor: typeof props.dropdownItemColor === 'string' ? props.dropdownItemColor : '',
                dropdownItemFontFamily: typeof props.dropdownItemFontFamily === 'string' ? props.dropdownItemFontFamily : '',
                dropdownItemFontSize: Number.isFinite(Number(props.dropdownItemFontSize)) ? Number(props.dropdownItemFontSize) : undefined,
                dropdownItemFontWeight: typeof props.dropdownItemFontWeight === 'string' ? props.dropdownItemFontWeight : '',
                dropdownItemLineHeight: Number.isFinite(Number(props.dropdownItemLineHeight)) ? Number(props.dropdownItemLineHeight) : undefined,
                dropdownItemLetterSpacing: Number.isFinite(Number(props.dropdownItemLetterSpacing)) ? Number(props.dropdownItemLetterSpacing) : undefined,
                dropdownItemWordSpacing: Number.isFinite(Number(props.dropdownItemWordSpacing)) ? Number(props.dropdownItemWordSpacing) : undefined,
                dropdownItemMarginTop: Number.isFinite(Number(props.dropdownItemMarginTop)) ? Number(props.dropdownItemMarginTop) : undefined,
                dropdownItemMarginRight: Number.isFinite(Number(props.dropdownItemMarginRight)) ? Number(props.dropdownItemMarginRight) : undefined,
                dropdownItemMarginBottom: Number.isFinite(Number(props.dropdownItemMarginBottom)) ? Number(props.dropdownItemMarginBottom) : undefined,
                dropdownItemMarginLeft: Number.isFinite(Number(props.dropdownItemMarginLeft)) ? Number(props.dropdownItemMarginLeft) : undefined,
                dropdownItemMarginLinked: typeof props.dropdownItemMarginLinked === 'boolean' ? props.dropdownItemMarginLinked : false,
                dropdownItemPaddingTop: Number.isFinite(Number(props.dropdownItemPaddingTop)) ? Number(props.dropdownItemPaddingTop) : undefined,
                dropdownItemPaddingRight: Number.isFinite(Number(props.dropdownItemPaddingRight)) ? Number(props.dropdownItemPaddingRight) : undefined,
                dropdownItemPaddingBottom: Number.isFinite(Number(props.dropdownItemPaddingBottom)) ? Number(props.dropdownItemPaddingBottom) : undefined,
                dropdownItemPaddingLeft: Number.isFinite(Number(props.dropdownItemPaddingLeft)) ? Number(props.dropdownItemPaddingLeft) : undefined,
                dropdownItemPaddingLinked: typeof props.dropdownItemPaddingLinked === 'boolean' ? props.dropdownItemPaddingLinked : true,
                dropdownItemBorderStyle: ['solid', 'dashed', 'dotted'].includes(props.dropdownItemBorderStyle) ? props.dropdownItemBorderStyle : '',
                dropdownItemBorderColor: typeof props.dropdownItemBorderColor === 'string' ? props.dropdownItemBorderColor : '',
                dropdownItemBorderWidthTop: Number.isFinite(Number(props.dropdownItemBorderWidthTop)) ? Number(props.dropdownItemBorderWidthTop) : 0,
                dropdownItemBorderWidthRight: Number.isFinite(Number(props.dropdownItemBorderWidthRight)) ? Number(props.dropdownItemBorderWidthRight) : 0,
                dropdownItemBorderWidthBottom: Number.isFinite(Number(props.dropdownItemBorderWidthBottom)) ? Number(props.dropdownItemBorderWidthBottom) : 0,
                dropdownItemBorderWidthLeft: Number.isFinite(Number(props.dropdownItemBorderWidthLeft)) ? Number(props.dropdownItemBorderWidthLeft) : 0,
                dropdownItemBorderWidthLinked: typeof props.dropdownItemBorderWidthLinked === 'boolean' ? props.dropdownItemBorderWidthLinked : true,
                dropdownItemBorderRadius: Number.isFinite(Number(props.dropdownItemBorderRadius)) ? Number(props.dropdownItemBorderRadius) : undefined,
                dropdownItemBorderRadiusLinked: typeof props.dropdownItemBorderRadiusLinked === 'boolean' ? props.dropdownItemBorderRadiusLinked : true,
                dropdownItemBorderRadiusTop: Number.isFinite(Number(props.dropdownItemBorderRadiusTop)) ? Number(props.dropdownItemBorderRadiusTop) : undefined,
                dropdownItemBorderRadiusRight: Number.isFinite(Number(props.dropdownItemBorderRadiusRight)) ? Number(props.dropdownItemBorderRadiusRight) : undefined,
                dropdownItemBorderRadiusBottom: Number.isFinite(Number(props.dropdownItemBorderRadiusBottom)) ? Number(props.dropdownItemBorderRadiusBottom) : undefined,
                dropdownItemBorderRadiusLeft: Number.isFinite(Number(props.dropdownItemBorderRadiusLeft)) ? Number(props.dropdownItemBorderRadiusLeft) : undefined,

                dropdownWidth: Number.isFinite(Number(props.dropdownWidth)) ? Number(props.dropdownWidth) : undefined,

                megaMenuWidthMode: ['full', 'fixed'].includes(props.megaMenuWidthMode) ? props.megaMenuWidthMode : 'full',
                megaMenuFixedWidth: Number.isFinite(Number(props.megaMenuFixedWidth)) ? Number(props.megaMenuFixedWidth) : undefined,
                megaMenuBackgroundColor: typeof props.megaMenuBackgroundColor === 'string' ? props.megaMenuBackgroundColor : '',
                megaMenuPaddingTop: Number.isFinite(Number(props.megaMenuPaddingTop)) ? Number(props.megaMenuPaddingTop) : undefined,
                megaMenuPaddingRight: Number.isFinite(Number(props.megaMenuPaddingRight)) ? Number(props.megaMenuPaddingRight) : undefined,
                megaMenuPaddingBottom: Number.isFinite(Number(props.megaMenuPaddingBottom)) ? Number(props.megaMenuPaddingBottom) : undefined,
                megaMenuPaddingLeft: Number.isFinite(Number(props.megaMenuPaddingLeft)) ? Number(props.megaMenuPaddingLeft) : undefined,
                megaMenuPaddingLinked: typeof props.megaMenuPaddingLinked === 'boolean' ? props.megaMenuPaddingLinked : true,
                megaMenuMarginTop: Number.isFinite(Number(props.megaMenuMarginTop)) ? Number(props.megaMenuMarginTop) : undefined,
                megaMenuMarginRight: Number.isFinite(Number(props.megaMenuMarginRight)) ? Number(props.megaMenuMarginRight) : undefined,
                megaMenuMarginBottom: Number.isFinite(Number(props.megaMenuMarginBottom)) ? Number(props.megaMenuMarginBottom) : undefined,
                megaMenuMarginLeft: Number.isFinite(Number(props.megaMenuMarginLeft)) ? Number(props.megaMenuMarginLeft) : undefined,
                megaMenuMarginLinked: typeof props.megaMenuMarginLinked === 'boolean' ? props.megaMenuMarginLinked : false,
                megaMenuBorderStyle: ['solid', 'dashed', 'dotted'].includes(props.megaMenuBorderStyle) ? props.megaMenuBorderStyle : '',
                megaMenuBorderColor: typeof props.megaMenuBorderColor === 'string' ? props.megaMenuBorderColor : '',
                megaMenuBorderWidthTop: Number.isFinite(Number(props.megaMenuBorderWidthTop)) ? Number(props.megaMenuBorderWidthTop) : 0,
                megaMenuBorderWidthRight: Number.isFinite(Number(props.megaMenuBorderWidthRight)) ? Number(props.megaMenuBorderWidthRight) : 0,
                megaMenuBorderWidthBottom: Number.isFinite(Number(props.megaMenuBorderWidthBottom)) ? Number(props.megaMenuBorderWidthBottom) : 0,
                megaMenuBorderWidthLeft: Number.isFinite(Number(props.megaMenuBorderWidthLeft)) ? Number(props.megaMenuBorderWidthLeft) : 0,
                megaMenuBorderWidthLinked: typeof props.megaMenuBorderWidthLinked === 'boolean' ? props.megaMenuBorderWidthLinked : true,
                megaMenuBorderRadius: Number.isFinite(Number(props.megaMenuBorderRadius)) ? Number(props.megaMenuBorderRadius) : undefined,
                megaMenuBorderRadiusLinked: typeof props.megaMenuBorderRadiusLinked === 'boolean' ? props.megaMenuBorderRadiusLinked : true,
                megaMenuBorderRadiusTop: Number.isFinite(Number(props.megaMenuBorderRadiusTop)) ? Number(props.megaMenuBorderRadiusTop) : undefined,
                megaMenuBorderRadiusRight: Number.isFinite(Number(props.megaMenuBorderRadiusRight)) ? Number(props.megaMenuBorderRadiusRight) : undefined,
                megaMenuBorderRadiusBottom: Number.isFinite(Number(props.megaMenuBorderRadiusBottom)) ? Number(props.megaMenuBorderRadiusBottom) : undefined,
                megaMenuBorderRadiusLeft: Number.isFinite(Number(props.megaMenuBorderRadiusLeft)) ? Number(props.megaMenuBorderRadiusLeft) : undefined,
                ...motion,
            },
        };
    }

    if (type === 'social_icons') {
        const rawIcons = Array.isArray(props.icons) ? props.icons : [];
        const normalizedIcons = rawIcons
            .filter((it) => it && typeof it === 'object')
            .map((it) => ({
                svg: typeof it.svg === 'string' ? it.svg : '',
                url: typeof it.url === 'string' ? it.url : '',
                target: ['_self', '_parent', '_top'].includes(it.target ?? null) ? it.target : '_blank',
            }));

        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Social Icons',
            advanced,
            props: {
                ...props,
                icons: normalizedIcons.length ? normalizedIcons : [],
                size: Number.isFinite(Number(props.size)) ? Math.max(10, Math.min(80, Math.round(Number(props.size)))) : 20,
                gap: Number.isFinite(Number(props.gap)) ? Math.max(0, Math.min(80, Math.round(Number(props.gap)))) : 16,
                align: ['left', 'center', 'right'].includes(props.align) ? props.align : 'left',
                ...motion,
            },
        };
    }

    if (type === 'social_proof') {
        const rawAvatars = Array.isArray(props.avatars) ? props.avatars : [];
        const normalizedAvatars = rawAvatars
            .map((it) => {
                if (typeof it === 'string') return { image: it };
                if (it && typeof it === 'object') return { image: typeof it.image === 'string' ? it.image : '' };
                return null;
            })
            .filter((it) => it !== null);

        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Social Proof',
            advanced,
            props: {
                avatars: normalizedAvatars.length ? normalizedAvatars : [
                    { image: '' },
                    { image: '' },
                    { image: '' },
                    { image: '' },
                ],
                rating: Number.isFinite(Number(props.rating)) ? Math.max(0, Math.min(5, Number(props.rating))) : 5,
                text: typeof props.text === 'string' ? props.text : 'Trusted by 1000+ clients',
                showAvatars: props.showAvatars !== false,
                showStars: props.showStars !== false,
                layout: props.layout === 'stacked' ? 'stacked' : 'inline',
                align: ['left', 'center', 'right'].includes(props.align) ? props.align : 'left',
                avatarSize: Number.isFinite(Number(props.avatarSize)) ? Math.max(20, Math.min(96, Math.round(Number(props.avatarSize)))) : 48,
                starSize: Number.isFinite(Number(props.starSize)) ? Math.max(10, Math.min(48, Math.round(Number(props.starSize)))) : 22,
                textSize: Number.isFinite(Number(props.textSize)) ? Math.max(10, Math.min(48, Math.round(Number(props.textSize)))) : 18,
                starColor: typeof props.starColor === 'string' && props.starColor.trim() ? props.starColor : '#F59E0B',
                textColor: typeof props.textColor === 'string' && props.textColor.trim() ? props.textColor : '#334155',
                ...motion,
            },
        };
    }

    if (type === 'newsletter_form') {
        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Newsletter Form',
            advanced,
            props: {
                buttonText: typeof props.buttonText === 'string' ? props.buttonText : 'Subscribe',
                placeholder: typeof props.placeholder === 'string' ? props.placeholder : 'Enter your email',
                successMessage: typeof props.successMessage === 'string' ? props.successMessage : 'Thanks for subscribing!',
                listId: typeof props.listId === 'string' ? props.listId : '',
                ...motion,
            },
        };
    }

    if (type === 'copyright') {
        const extra = {};
        [
            'color', 'fontFamily', 'fontWeight', 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing',
            'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft', 'paddingLinked',
            'marginTop', 'marginRight', 'marginBottom', 'marginLeft', 'marginLinked',
            'backgroundType', 'backgroundColor', 'gradientFrom', 'gradientTo', 'gradientAngle',
        ].forEach((key) => {
            if (props[key] !== undefined && props[key] !== null && props[key] !== '') {
                extra[key] = props[key];
            }
        });

        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Copyright',
            advanced,
            props: {
                text: typeof props.text === 'string' ? props.text : '&copy; {{year}} {{site_name}}',
                align: ['left', 'center', 'right'].includes(props.align) ? props.align : 'center',
                ...extra,
                ...motion,
            },
        };
    }

    if (type === 'rating') {
        const max = Number.isFinite(Number(props.max)) ? Math.max(1, Math.min(10, Math.round(Number(props.max)))) : 5;
        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Rating',
            advanced,
            props: {
                rating: Number.isFinite(Number(props.rating)) ? Math.max(0, Math.min(max, Number(props.rating))) : 4.5,
                max,
                starSize: Number.isFinite(Number(props.starSize)) ? Math.max(8, Math.min(64, Math.round(Number(props.starSize)))) : 20,
                gap: Number.isFinite(Number(props.gap)) ? Math.max(0, Math.min(24, Math.round(Number(props.gap)))) : 4,
                starColor: typeof props.starColor === 'string' && props.starColor.trim() ? props.starColor : '#F59E0B',
                emptyColor: typeof props.emptyColor === 'string' && props.emptyColor.trim() ? props.emptyColor : '#E5E7EB',
                align: ['left', 'center', 'right'].includes(props.align) ? props.align : 'left',
                showValue: props.showValue === true,
                textSize: Number.isFinite(Number(props.textSize)) ? Math.max(10, Math.min(48, Math.round(Number(props.textSize)))) : 14,
                textColor: typeof props.textColor === 'string' && props.textColor.trim() ? props.textColor : '#334155',
                ...motion,
            },
        };
    }

    if (type === 'login_form') {
        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Login Form',
            advanced,
            props: {
                ...props,
                buttonText: typeof props.buttonText === 'string' ? props.buttonText : 'Sign In',
                showRemember: props.showRemember !== false,
                showForgot: props.showForgot !== false,
                showRegisterLink: props.showRegisterLink !== false,
                ...motion,
            },
        };
    }

    if (type === 'register_form') {
        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Register Form',
            advanced,
            props: {
                ...props,
                buttonText: typeof props.buttonText === 'string' ? props.buttonText : 'Create Account',
                showLoginLink: props.showLoginLink !== false,
                ...motion,
            },
        };
    }

    const normalizeFormFieldProps = () => {
        const paddingTop = Number.isFinite(Number(props.paddingTop))
            ? Number(props.paddingTop)
            : Number.isFinite(Number(props.paddingY))
              ? Number(props.paddingY)
              : undefined;
        const paddingRight = Number.isFinite(Number(props.paddingRight))
            ? Number(props.paddingRight)
            : Number.isFinite(Number(props.paddingX))
              ? Number(props.paddingX)
              : undefined;
        const paddingBottom = Number.isFinite(Number(props.paddingBottom))
            ? Number(props.paddingBottom)
            : Number.isFinite(Number(props.paddingY))
              ? Number(props.paddingY)
              : undefined;
        const paddingLeft = Number.isFinite(Number(props.paddingLeft))
            ? Number(props.paddingLeft)
            : Number.isFinite(Number(props.paddingX))
              ? Number(props.paddingX)
              : undefined;
        const borderWidthTop = Number.isFinite(Number(props.borderWidthTop))
            ? Number(props.borderWidthTop)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;
        const borderWidthRight = Number.isFinite(Number(props.borderWidthRight))
            ? Number(props.borderWidthRight)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;
        const borderWidthBottom = Number.isFinite(Number(props.borderWidthBottom))
            ? Number(props.borderWidthBottom)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;
        const borderWidthLeft = Number.isFinite(Number(props.borderWidthLeft))
            ? Number(props.borderWidthLeft)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;
        const borderRadiusTop = Number.isFinite(Number(props.borderRadiusTop))
            ? Number(props.borderRadiusTop)
            : Number.isFinite(Number(props.borderRadius))
              ? Number(props.borderRadius)
              : undefined;
        const borderRadiusRight = Number.isFinite(Number(props.borderRadiusRight))
            ? Number(props.borderRadiusRight)
            : Number.isFinite(Number(props.borderRadius))
              ? Number(props.borderRadius)
              : undefined;
        const borderRadiusBottom = Number.isFinite(Number(props.borderRadiusBottom))
            ? Number(props.borderRadiusBottom)
            : Number.isFinite(Number(props.borderRadius))
              ? Number(props.borderRadius)
              : undefined;
        const borderRadiusLeft = Number.isFinite(Number(props.borderRadiusLeft))
            ? Number(props.borderRadiusLeft)
            : Number.isFinite(Number(props.borderRadius))
              ? Number(props.borderRadius)
              : undefined;

        return {
            label: typeof props.label === 'string' ? props.label : '',
            placeholder: typeof props.placeholder === 'string' ? props.placeholder : '',
            required: props.required === true,
            helpText: typeof props.helpText === 'string' ? props.helpText : '',
            labelColor: typeof props.labelColor === 'string' ? props.labelColor : '',
            textColor: typeof props.textColor === 'string' ? props.textColor : '',
            backgroundColor: typeof props.backgroundColor === 'string' ? props.backgroundColor : '',
            borderStyle: typeof props.borderStyle === 'string' ? props.borderStyle : '',
            borderColor: typeof props.borderColor === 'string' ? props.borderColor : '',
            fontFamily: typeof props.fontFamily === 'string' ? props.fontFamily : '',
            fontSize: Number.isFinite(Number(props.fontSize)) ? Number(props.fontSize) : undefined,
            fontWeight: typeof props.fontWeight === 'string' ? props.fontWeight : '',
            lineHeight: Number.isFinite(Number(props.lineHeight)) ? Number(props.lineHeight) : undefined,
            letterSpacing: Number.isFinite(Number(props.letterSpacing)) ? Number(props.letterSpacing) : undefined,
            wordSpacing: Number.isFinite(Number(props.wordSpacing)) ? Number(props.wordSpacing) : undefined,
            paddingLinked: typeof props.paddingLinked === 'boolean'
                ? props.paddingLinked
                : inferLinked4(
                    toFiniteNumberOrUndefined(paddingTop),
                    toFiniteNumberOrUndefined(paddingRight),
                    toFiniteNumberOrUndefined(paddingBottom),
                    toFiniteNumberOrUndefined(paddingLeft),
                    true
                ),
            paddingTop,
            paddingRight,
            paddingBottom,
            paddingLeft,
            borderWidthLinked: typeof props.borderWidthLinked === 'boolean'
                ? props.borderWidthLinked
                : inferLinked4(
                    toFiniteNumberOrUndefined(borderWidthTop),
                    toFiniteNumberOrUndefined(borderWidthRight),
                    toFiniteNumberOrUndefined(borderWidthBottom),
                    toFiniteNumberOrUndefined(borderWidthLeft),
                    true
                ),
            borderWidthTop,
            borderWidthRight,
            borderWidthBottom,
            borderWidthLeft,
            borderRadiusLinked: typeof props.borderRadiusLinked === 'boolean'
                ? props.borderRadiusLinked
                : inferLinked4(
                    toFiniteNumberOrUndefined(borderRadiusTop),
                    toFiniteNumberOrUndefined(borderRadiusRight),
                    toFiniteNumberOrUndefined(borderRadiusBottom),
                    toFiniteNumberOrUndefined(borderRadiusLeft),
                    true
                ),
            borderRadiusTop,
            borderRadiusRight,
            borderRadiusBottom,
            borderRadiusLeft,
            marginTop: Number.isFinite(Number(props.marginTop)) ? Number(props.marginTop) : undefined,
            marginRight: Number.isFinite(Number(props.marginRight)) ? Number(props.marginRight) : undefined,
            marginBottom: Number.isFinite(Number(props.marginBottom)) ? Number(props.marginBottom) : undefined,
            marginLeft: Number.isFinite(Number(props.marginLeft)) ? Number(props.marginLeft) : undefined,
            marginLinked: typeof props.marginLinked === 'boolean'
                ? props.marginLinked
                : inferLinked4(
                    toFiniteNumberOrUndefined(props.marginTop),
                    toFiniteNumberOrUndefined(props.marginRight),
                    toFiniteNumberOrUndefined(props.marginBottom),
                    toFiniteNumberOrUndefined(props.marginLeft),
                    true
                ),
            backgroundType: ['color', 'classic', 'gradient'].includes(props.backgroundType) ? props.backgroundType : 'classic',
            gradientFrom: typeof props.gradientFrom === 'string' ? props.gradientFrom : '',
            gradientTo: typeof props.gradientTo === 'string' ? props.gradientTo : '',
            gradientType: ['linear', 'radial'].includes(props.gradientType) ? props.gradientType : 'linear',
            gradientPosition: typeof props.gradientPosition === 'string' ? props.gradientPosition : '',
            gradientFromLocation: Number.isFinite(Number(props.gradientFromLocation)) ? Number(props.gradientFromLocation) : undefined,
            gradientToLocation: Number.isFinite(Number(props.gradientToLocation)) ? Number(props.gradientToLocation) : undefined,
        };
    };

    if (type === 'form_text') {
        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Text Input',
            advanced,
            props: {
                ...normalizeFormFieldProps(),
                ...motion,
            },
        };
    }

    if (type === 'form_email') {
        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Email',
            advanced,
            props: {
                ...normalizeFormFieldProps(),
                ...motion,
            },
        };
    }

    if (type === 'form_textarea') {
        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Textarea',
            advanced,
            props: {
                ...normalizeFormFieldProps(),
                rows: Number.isFinite(Number(props.rows)) ? Math.max(1, Math.round(Number(props.rows))) : 4,
                ...motion,
            },
        };
    }

    if (type === 'form_select') {
        const rawOptions = Array.isArray(props.options) ? props.options : [];
        const options = rawOptions.map((o) => {
            if (typeof o === 'string') return { value: o, label: o };
            return { value: String(o.value || ''), label: String(o.label || o.value || '') };
        }).filter((o) => o.value !== '');

        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Dropdown',
            advanced,
            props: {
                ...normalizeFormFieldProps(),
                options: options.length ? options : [{ value: 'option_1', label: 'Option 1' }, { value: 'option_2', label: 'Option 2' }],
                ...motion,
            },
        };
    }

    if (type === 'form_checkbox') {
        const rawOptions = Array.isArray(props.options) ? props.options : [];
        const options = rawOptions.map((o) => {
            if (typeof o === 'string') return { value: o, label: o };
            return { value: String(o.value || ''), label: String(o.label || o.value || '') };
        }).filter((o) => o.value !== '');

        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Checkbox',
            advanced,
            props: {
                ...normalizeFormFieldProps(),
                options: options.length ? options : [{ value: 'agree', label: 'I agree' }],
                ...motion,
            },
        };
    }

    if (type === 'form_radio') {
        const rawOptions = Array.isArray(props.options) ? props.options : [];
        const options = rawOptions.map((o) => {
            if (typeof o === 'string') return { value: o, label: o };
            return { value: String(o.value || ''), label: String(o.label || o.value || '') };
        }).filter((o) => o.value !== '');

        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Radio Group',
            advanced,
            props: {
                ...normalizeFormFieldProps(),
                options: options.length ? options : [{ value: 'option_1', label: 'Option 1' }, { value: 'option_2', label: 'Option 2' }],
                ...motion,
            },
        };
    }

    if (type === 'form_submit') {
        const paddingTop = Number.isFinite(Number(props.paddingTop))
            ? Number(props.paddingTop)
            : Number.isFinite(Number(props.paddingY))
              ? Number(props.paddingY)
              : undefined;
        const paddingRight = Number.isFinite(Number(props.paddingRight))
            ? Number(props.paddingRight)
            : Number.isFinite(Number(props.paddingX))
              ? Number(props.paddingX)
              : undefined;
        const paddingBottom = Number.isFinite(Number(props.paddingBottom))
            ? Number(props.paddingBottom)
            : Number.isFinite(Number(props.paddingY))
              ? Number(props.paddingY)
              : undefined;
        const paddingLeft = Number.isFinite(Number(props.paddingLeft))
            ? Number(props.paddingLeft)
            : Number.isFinite(Number(props.paddingX))
              ? Number(props.paddingX)
              : undefined;
        const borderWidthTop = Number.isFinite(Number(props.borderWidthTop))
            ? Number(props.borderWidthTop)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;
        const borderWidthRight = Number.isFinite(Number(props.borderWidthRight))
            ? Number(props.borderWidthRight)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;
        const borderWidthBottom = Number.isFinite(Number(props.borderWidthBottom))
            ? Number(props.borderWidthBottom)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;
        const borderWidthLeft = Number.isFinite(Number(props.borderWidthLeft))
            ? Number(props.borderWidthLeft)
            : Number.isFinite(Number(props.borderWidth))
              ? Number(props.borderWidth)
              : undefined;
        const borderRadiusTop = Number.isFinite(Number(props.borderRadiusTop))
            ? Number(props.borderRadiusTop)
            : Number.isFinite(Number(props.borderRadius))
              ? Number(props.borderRadius)
              : undefined;
        const borderRadiusRight = Number.isFinite(Number(props.borderRadiusRight))
            ? Number(props.borderRadiusRight)
            : Number.isFinite(Number(props.borderRadius))
              ? Number(props.borderRadius)
              : undefined;
        const borderRadiusBottom = Number.isFinite(Number(props.borderRadiusBottom))
            ? Number(props.borderRadiusBottom)
            : Number.isFinite(Number(props.borderRadius))
              ? Number(props.borderRadius)
              : undefined;
        const borderRadiusLeft = Number.isFinite(Number(props.borderRadiusLeft))
            ? Number(props.borderRadiusLeft)
            : Number.isFinite(Number(props.borderRadius))
              ? Number(props.borderRadius)
              : undefined;

        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Submit Button',
            advanced,
            props: {
                text: typeof props.text === 'string' ? props.text : 'Submit',
                fullWidth: props.fullWidth === true,
                textColor: typeof props.textColor === 'string' ? props.textColor : '',
                backgroundColor: typeof props.backgroundColor === 'string' ? props.backgroundColor : '',
                borderStyle: typeof props.borderStyle === 'string' ? props.borderStyle : '',
                borderColor: typeof props.borderColor === 'string' ? props.borderColor : '',
                fontFamily: typeof props.fontFamily === 'string' ? props.fontFamily : '',
                fontSize: Number.isFinite(Number(props.fontSize)) ? Number(props.fontSize) : undefined,
                fontWeight: typeof props.fontWeight === 'string' ? props.fontWeight : '',
                lineHeight: Number.isFinite(Number(props.lineHeight)) ? Number(props.lineHeight) : undefined,
                letterSpacing: Number.isFinite(Number(props.letterSpacing)) ? Number(props.letterSpacing) : undefined,
                wordSpacing: Number.isFinite(Number(props.wordSpacing)) ? Number(props.wordSpacing) : undefined,
                paddingLinked: typeof props.paddingLinked === 'boolean'
                    ? props.paddingLinked
                    : inferLinked4(
                        toFiniteNumberOrUndefined(paddingTop),
                        toFiniteNumberOrUndefined(paddingRight),
                        toFiniteNumberOrUndefined(paddingBottom),
                        toFiniteNumberOrUndefined(paddingLeft),
                        true
                    ),
                paddingTop,
                paddingRight,
                paddingBottom,
                paddingLeft,
                borderWidthLinked: typeof props.borderWidthLinked === 'boolean'
                    ? props.borderWidthLinked
                    : inferLinked4(
                        toFiniteNumberOrUndefined(borderWidthTop),
                        toFiniteNumberOrUndefined(borderWidthRight),
                        toFiniteNumberOrUndefined(borderWidthBottom),
                        toFiniteNumberOrUndefined(borderWidthLeft),
                        true
                    ),
                borderWidthTop,
                borderWidthRight,
                borderWidthBottom,
                borderWidthLeft,
                borderRadiusLinked: typeof props.borderRadiusLinked === 'boolean'
                    ? props.borderRadiusLinked
                    : inferLinked4(
                        toFiniteNumberOrUndefined(borderRadiusTop),
                        toFiniteNumberOrUndefined(borderRadiusRight),
                        toFiniteNumberOrUndefined(borderRadiusBottom),
                        toFiniteNumberOrUndefined(borderRadiusLeft),
                        true
                    ),
                borderRadiusTop,
                borderRadiusRight,
                borderRadiusBottom,
                borderRadiusLeft,
                marginTop: Number.isFinite(Number(props.marginTop)) ? Number(props.marginTop) : undefined,
                marginRight: Number.isFinite(Number(props.marginRight)) ? Number(props.marginRight) : undefined,
                marginBottom: Number.isFinite(Number(props.marginBottom)) ? Number(props.marginBottom) : undefined,
                marginLeft: Number.isFinite(Number(props.marginLeft)) ? Number(props.marginLeft) : undefined,
                marginLinked: typeof props.marginLinked === 'boolean'
                    ? props.marginLinked
                    : inferLinked4(
                        toFiniteNumberOrUndefined(props.marginTop),
                        toFiniteNumberOrUndefined(props.marginRight),
                        toFiniteNumberOrUndefined(props.marginBottom),
                        toFiniteNumberOrUndefined(props.marginLeft),
                        true
                    ),
                backgroundType: ['color', 'classic', 'gradient'].includes(props.backgroundType) ? props.backgroundType : 'classic',
                gradientFrom: typeof props.gradientFrom === 'string' ? props.gradientFrom : '',
                gradientTo: typeof props.gradientTo === 'string' ? props.gradientTo : '',
                gradientType: ['linear', 'radial'].includes(props.gradientType) ? props.gradientType : 'linear',
                gradientPosition: typeof props.gradientPosition === 'string' ? props.gradientPosition : '',
                gradientFromLocation: Number.isFinite(Number(props.gradientFromLocation)) ? Number(props.gradientFromLocation) : undefined,
                gradientToLocation: Number.isFinite(Number(props.gradientToLocation)) ? Number(props.gradientToLocation) : undefined,
                hoverTextColor: typeof props.hoverTextColor === 'string' ? props.hoverTextColor : '',
                hoverBackgroundType: ['color', 'classic', 'gradient'].includes(props.hoverBackgroundType) ? props.hoverBackgroundType : 'classic',
                hoverBackgroundColor: typeof props.hoverBackgroundColor === 'string' ? props.hoverBackgroundColor : '',
                hoverGradientFrom: typeof props.hoverGradientFrom === 'string' ? props.hoverGradientFrom : '',
                hoverGradientTo: typeof props.hoverGradientTo === 'string' ? props.hoverGradientTo : '',
                hoverGradientType: ['linear', 'radial'].includes(props.hoverGradientType) ? props.hoverGradientType : 'linear',
                hoverGradientPosition: typeof props.hoverGradientPosition === 'string' ? props.hoverGradientPosition : '',
                hoverGradientFromLocation: Number.isFinite(Number(props.hoverGradientFromLocation)) ? Number(props.hoverGradientFromLocation) : undefined,
                hoverGradientToLocation: Number.isFinite(Number(props.hoverGradientToLocation)) ? Number(props.hoverGradientToLocation) : undefined,
                focusTextColor: typeof props.focusTextColor === 'string' ? props.focusTextColor : '',
                focusBackgroundType: ['color', 'classic', 'gradient'].includes(props.focusBackgroundType) ? props.focusBackgroundType : 'classic',
                focusBackgroundColor: typeof props.focusBackgroundColor === 'string' ? props.focusBackgroundColor : '',
                focusGradientFrom: typeof props.focusGradientFrom === 'string' ? props.focusGradientFrom : '',
                focusGradientTo: typeof props.focusGradientTo === 'string' ? props.focusGradientTo : '',
                focusGradientType: ['linear', 'radial'].includes(props.focusGradientType) ? props.focusGradientType : 'linear',
                focusGradientPosition: typeof props.focusGradientPosition === 'string' ? props.focusGradientPosition : '',
                focusGradientFromLocation: Number.isFinite(Number(props.focusGradientFromLocation)) ? Number(props.focusGradientFromLocation) : undefined,
                focusGradientToLocation: Number.isFinite(Number(props.focusGradientToLocation)) ? Number(props.focusGradientToLocation) : undefined,
                ...motion,
            },
        };
    }

    if (type === 'button_group') {
        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Button Group',
            advanced,
            props: {
                align: ['left', 'center', 'right'].includes(props.align) ? props.align : 'left',
                direction: ['horizontal', 'vertical'].includes(props.direction) ? props.direction : 'horizontal',
                gap: Number.isFinite(Number(props.gap)) ? Math.max(0, Math.round(Number(props.gap))) : 16,
                ...props,
                ...motion,
            },
        };
    }

    if (type === 'badge') {
        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Badge',
            advanced,
            props: {
                text: typeof props.text === 'string' ? props.text : 'Announcing ShadcnSpace 1.0',
                leading: ['none', 'label', 'dot', 'icon'].includes(props.leading) ? props.leading : 'label',
                labelText: typeof props.labelText === 'string' ? props.labelText : 'New',
                align: ['left', 'center', 'right'].includes(props.align) ? props.align : 'left',
                gap: Number.isFinite(Number(props.gap)) ? Math.max(0, Math.round(Number(props.gap))) : 8,
                textColor: typeof props.textColor === 'string' ? props.textColor : '#111827',
                backgroundType: ['color', 'classic', 'gradient'].includes(props.backgroundType) ? props.backgroundType : 'classic',
                backgroundColor: typeof props.backgroundColor === 'string' ? props.backgroundColor : '#ffffff',
                gradientFrom: typeof props.gradientFrom === 'string' ? props.gradientFrom : '#111827',
                gradientTo: typeof props.gradientTo === 'string' ? props.gradientTo : '#3b82f6',
                gradientFromLocation: Number.isFinite(Number(props.gradientFromLocation)) ? Number(props.gradientFromLocation) : 0,
                gradientToLocation: Number.isFinite(Number(props.gradientToLocation)) ? Number(props.gradientToLocation) : 100,
                gradientType: ['linear', 'radial'].includes(props.gradientType) ? props.gradientType : 'linear',
                gradientPosition: typeof props.gradientPosition === 'string' ? props.gradientPosition : 'top center',
                borderStyle: typeof props.borderStyle === 'string' ? props.borderStyle : 'solid',
                borderColor: typeof props.borderColor === 'string' ? props.borderColor : '#e5e7eb',
                borderWidthTop: Number.isFinite(Number(props.borderWidthTop)) ? Number(props.borderWidthTop) : 1,
                borderWidthRight: Number.isFinite(Number(props.borderWidthRight)) ? Number(props.borderWidthRight) : 1,
                borderWidthBottom: Number.isFinite(Number(props.borderWidthBottom)) ? Number(props.borderWidthBottom) : 1,
                borderWidthLeft: Number.isFinite(Number(props.borderWidthLeft)) ? Number(props.borderWidthLeft) : 1,
                borderRadiusTop: Number.isFinite(Number(props.borderRadiusTop)) ? Number(props.borderRadiusTop) : 9999,
                borderRadiusRight: Number.isFinite(Number(props.borderRadiusRight)) ? Number(props.borderRadiusRight) : 9999,
                borderRadiusBottom: Number.isFinite(Number(props.borderRadiusBottom)) ? Number(props.borderRadiusBottom) : 9999,
                borderRadiusLeft: Number.isFinite(Number(props.borderRadiusLeft)) ? Number(props.borderRadiusLeft) : 9999,
                useGradientBorder: props.useGradientBorder === true,
                borderGradientFrom: typeof props.borderGradientFrom === 'string' ? props.borderGradientFrom : '#3b82f6',
                borderGradientTo: typeof props.borderGradientTo === 'string' ? props.borderGradientTo : '#a855f7',
                labelBg: typeof props.labelBg === 'string' ? props.labelBg : '#2563eb',
                labelColor: typeof props.labelColor === 'string' ? props.labelColor : '#ffffff',
                labelUseGradient: props.labelUseGradient === true,
                labelGradientFrom: typeof props.labelGradientFrom === 'string' ? props.labelGradientFrom : '#3b82f6',
                labelGradientTo: typeof props.labelGradientTo === 'string' ? props.labelGradientTo : '#1d4ed8',
                labelFontSize: Number.isFinite(Number(props.labelFontSize)) ? Number(props.labelFontSize) : undefined,
                labelFontWeight: typeof props.labelFontWeight === 'string' ? props.labelFontWeight : '600',
                dotColor: typeof props.dotColor === 'string' ? props.dotColor : '#22c55e',
                dotSize: Number.isFinite(Number(props.dotSize)) ? Number(props.dotSize) : 8,
                iconUrl: typeof props.iconUrl === 'string' ? props.iconUrl : '',
                iconSvg: typeof props.iconSvg === 'string' ? props.iconSvg : '',
                iconColor: typeof props.iconColor === 'string' ? props.iconColor : '',
                iconSize: Number.isFinite(Number(props.iconSize)) ? Number(props.iconSize) : 16,
                paddingTop: Number.isFinite(Number(props.paddingTop)) ? Number(props.paddingTop) : 6,
                paddingRight: Number.isFinite(Number(props.paddingRight)) ? Number(props.paddingRight) : 14,
                paddingBottom: Number.isFinite(Number(props.paddingBottom)) ? Number(props.paddingBottom) : 6,
                paddingLeft: Number.isFinite(Number(props.paddingLeft)) ? Number(props.paddingLeft) : 6,
                fontFamily: typeof props.fontFamily === 'string' ? props.fontFamily : '',
                fontSize: Number.isFinite(Number(props.fontSize)) ? Number(props.fontSize) : 14,
                fontWeight: typeof props.fontWeight === 'string' ? props.fontWeight : '500',
                lineHeight: Number.isFinite(Number(props.lineHeight)) ? Number(props.lineHeight) : undefined,
                letterSpacing: Number.isFinite(Number(props.letterSpacing)) ? Number(props.letterSpacing) : undefined,
                wordSpacing: Number.isFinite(Number(props.wordSpacing)) ? Number(props.wordSpacing) : undefined,
                marginTop: Number.isFinite(Number(props.marginTop)) ? Number(props.marginTop) : undefined,
                marginRight: Number.isFinite(Number(props.marginRight)) ? Number(props.marginRight) : undefined,
                marginBottom: Number.isFinite(Number(props.marginBottom)) ? Number(props.marginBottom) : undefined,
                marginLeft: Number.isFinite(Number(props.marginLeft)) ? Number(props.marginLeft) : undefined,
                borderEffect: typeof props.borderEffect === 'string' ? props.borderEffect : '',
                borderEffectColor: typeof props.borderEffectColor === 'string' ? props.borderEffectColor : '',
                borderEffectColor2: typeof props.borderEffectColor2 === 'string' ? props.borderEffectColor2 : '',
                borderEffectSpeed: Number.isFinite(Number(props.borderEffectSpeed)) ? Number(props.borderEffectSpeed) : undefined,
                borderEffectThickness: Number.isFinite(Number(props.borderEffectThickness)) ? Number(props.borderEffectThickness) : undefined,
                ...motion,
            },
        };
    }

    const rawItems = Array.isArray(props.items) ? props.items : [];
    const images = Array.isArray(props.images) ? props.images : [];

    const fromItems = rawItems
        .filter((it) => it && typeof it === 'object')
        .map((it) => ({ url: String(it.url || '') }))
        .filter((it) => it.url);

    const textItems = rawItems
        .map((it) => {
            if (it && typeof it === 'object') {
                return { text: typeof it.text === 'string' ? it.text : '' };
            }
            if (typeof it === 'string') {
                return { text: it };
            }
            return { text: '' };
        })
        .filter((it) => it.text);

    if (type === 'ai_prompt_input') {
        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'AI Prompt Input',
            advanced,
            props: {
                placeholder: typeof props.placeholder === 'string' ? props.placeholder : 'What would you like to build?',
                showFileUpload: props.showFileUpload !== false,
                showDiscuss: props.showDiscuss !== false,
                discussText: typeof props.discussText === 'string' ? props.discussText : 'Discuss',
                showVoice: props.showVoice !== false,
                maxWidth: Number.isFinite(Number(props.maxWidth)) ? Number(props.maxWidth) : 640,
                borderRadius: Number.isFinite(Number(props.borderRadius)) ? Number(props.borderRadius) : 16,
                backgroundColor: typeof props.backgroundColor === 'string' ? props.backgroundColor : '#ffffff',
                borderColor: typeof props.borderColor === 'string' ? props.borderColor : '#e5e7eb',
                textColor: typeof props.textColor === 'string' ? props.textColor : '#1f2937',
                placeholderColor: typeof props.placeholderColor === 'string' ? props.placeholderColor : '#9ca3af',
                submitUrl: typeof props.submitUrl === 'string' ? props.submitUrl : '',
                loginUrl: typeof props.loginUrl === 'string' ? props.loginUrl : '',
                ...motion,
            },
        };
    }

    if (type === 'text_timeline') {
        const rawItems = Array.isArray(props.items) ? props.items : [];
        const normalizedItems = rawItems
            .filter((it) => it && typeof it === 'object')
            .map((it) => {
                const rawEvents = Array.isArray(it.events) ? it.events : [];
                const events = rawEvents
                    .filter((ev) => ev && typeof ev === 'object')
                    .map((ev) => ({
                        tag: typeof ev.tag === 'string' ? ev.tag : 'NEW',
                        title: typeof ev.title === 'string' ? ev.title : '',
                        description: typeof ev.description === 'string' ? ev.description : '',
                    }));
                return {
                    version: typeof it.version === 'string' ? it.version : '',
                    date: typeof it.date === 'string' ? it.date : '',
                    events: events.length ? events : [],
                };
            });

        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Text Timeline',
            advanced,
            props: {
                items: normalizedItems.length ? normalizedItems : [],
                metaWidth: Number.isFinite(Number(props.metaWidth)) ? Number(props.metaWidth) : 170,
                contentGap: Number.isFinite(Number(props.contentGap)) ? Number(props.contentGap) : 34,
                gap: Number.isFinite(Number(props.gap)) ? Number(props.gap) : 34,
                markerShape: ['circle', 'square'].includes(props.markerShape) ? props.markerShape : 'circle',
                markerSize: Number.isFinite(Number(props.markerSize)) ? Number(props.markerSize) : 14,
                railColor: typeof props.railColor === 'string' ? props.railColor : '',
                markerColor: typeof props.markerColor === 'string' ? props.markerColor : '',
                cardColor: typeof props.cardColor === 'string' ? props.cardColor : '',
                cardBorderColor: typeof props.cardBorderColor === 'string' ? props.cardBorderColor : '',
                cardRadius: Number.isFinite(Number(props.cardRadius)) ? Number(props.cardRadius) : 0,
                cardPaddingTop: Number.isFinite(Number(props.cardPaddingTop)) ? Number(props.cardPaddingTop) : undefined,
                cardPaddingRight: Number.isFinite(Number(props.cardPaddingRight)) ? Number(props.cardPaddingRight) : undefined,
                cardPaddingBottom: Number.isFinite(Number(props.cardPaddingBottom)) ? Number(props.cardPaddingBottom) : undefined,
                cardPaddingLeft: Number.isFinite(Number(props.cardPaddingLeft)) ? Number(props.cardPaddingLeft) : undefined,
                titleColor: typeof props.titleColor === 'string' ? props.titleColor : '',
                titleFontSize: Number.isFinite(Number(props.titleFontSize)) ? Number(props.titleFontSize) : undefined,
                descriptionColor: typeof props.descriptionColor === 'string' ? props.descriptionColor : '',
                descriptionFontSize: Number.isFinite(Number(props.descriptionFontSize)) ? Number(props.descriptionFontSize) : undefined,
                metaColor: typeof props.metaColor === 'string' ? props.metaColor : '',
                perPage: Number.isFinite(Number(props.perPage)) ? Math.max(1, Number(props.perPage)) : 20,
                showLoadMore: typeof props.showLoadMore === 'boolean' ? props.showLoadMore : true,
                loadMoreText: typeof props.loadMoreText === 'string' ? props.loadMoreText : 'Read More',
                loadMoreBackgroundColor: typeof props.loadMoreBackgroundColor === 'string' ? props.loadMoreBackgroundColor : '',
                loadMoreColor: typeof props.loadMoreColor === 'string' ? props.loadMoreColor : '',
                loadMoreHoverBackground: typeof props.loadMoreHoverBackground === 'string' ? props.loadMoreHoverBackground : '',
                loadMoreBorderRadius: Number.isFinite(Number(props.loadMoreBorderRadius)) ? Number(props.loadMoreBorderRadius) : undefined,
                ...motion,
            },
        };
    }

    if (type === 'link') {
        const rawItems = Array.isArray(props.items) ? props.items : [];
        const items = rawItems
            .filter((it) => it && typeof it === 'object')
            .map((it) => ({
                label: typeof it.label === 'string' ? it.label : '',
                url: typeof it.url === 'string' ? it.url : '#',
                target: it.target === '_blank' ? '_blank' : '_self',
            }));

        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Link',
            advanced,
            props: {
                ...props,
                items,
                gap: Number.isFinite(Number(props.gap)) ? Math.max(0, Math.round(Number(props.gap))) : 12,
                separator: typeof props.separator === 'string' ? props.separator : '·',
                align: ['left', 'center', 'right'].includes(props.align) ? props.align : 'left',
                ...motion,
            },
        };
    }

    if (type === 'testimonials') {
        const rawItems = Array.isArray(props.items) ? props.items : [];
        const items = rawItems
            .filter((it) => it && typeof it === 'object')
            .map((it) => ({
                avatarUrl: typeof it.avatarUrl === 'string' ? it.avatarUrl : '',
                avatarSvg: typeof it.avatarSvg === 'string' ? it.avatarSvg : '',
                avatarSource: it.avatarSource === 'svg' || it.avatarSource === 'url' ? it.avatarSource : 'url',
                rating: Number.isFinite(Number(it.rating)) ? Math.max(0, Math.min(5, Number(it.rating))) : 5,
                quote: typeof it.quote === 'string' ? it.quote : '',
                name: typeof it.name === 'string' ? it.name : '',
                role: typeof it.role === 'string' ? it.role : '',
            }));

        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Testimonial Carousel',
            advanced,
            props: {
                ...CAROUSEL_PROP_DEFAULTS,
                ...props,
                items,
                ...motion,
            },
        };
    }

    if (type === 'testimonial') {
        return {
            id: String(w.id || uid('w')),
            type,
            name: name || 'Testimonial',
            advanced,
            props: {
                ...props,
                rating: Number.isFinite(Number(props.rating)) ? Math.max(0, Math.min(5, Number(props.rating))) : 5,
                avatarSource: props.avatarSource === 'svg' || props.avatarSource === 'url' ? props.avatarSource : 'url',
                ...motion,
            },
        };
    }

    if (type === 'divider' || type === 'spacer' || type === 'logo') {
        return {
            id: String(w.id || uid('w')),
            type,
            name: name || (type === 'divider' ? 'Divider' : type === 'spacer' ? 'Spacer' : 'Logo'),
            advanced,
            props: {
                ...props,
                ...motion,
            },
        };
    }

    return {
        id: String(w.id || uid('w')),
        type: 'marquee',
        name: name || 'Marquee',
        advanced,
        props: {
            mode: ['images', 'text'].includes(props.mode) ? props.mode : 'images',
            speed: (() => {
                const n = Number(props.speed);
                if (!Number.isFinite(n)) return 30;
                return Math.max(5, Math.min(200, Math.round(n)));
            })(),
            gap: (() => {
                const n = Number(props.gap);
                if (!Number.isFinite(n)) return 24;
                return Math.max(0, Math.round(n));
            })(),
            direction: ['left', 'right'].includes(props.direction) ? props.direction : 'left',
            images: (images.length ? images : fromItems)
                .filter((i) => i && typeof i === 'object')
                .map((i) => ({ url: String(i.url || '') }))
                .filter((i) => i.url),

            items: textItems,
            separatorIcon: typeof props.separatorIcon === 'string' ? props.separatorIcon : '',

            imageWidth: (() => {
                const n = Number(props.imageWidth);
                if (!Number.isFinite(n)) return undefined;
                const next = Math.max(0, Math.min(600, Math.round(n)));
                return next === 0 ? undefined : next;
            })(),
            imageHeight: (() => {
                const n = Number(props.imageHeight);
                if (!Number.isFinite(n)) return undefined;
                return Math.max(10, Math.min(200, Math.round(n)));
            })(),

            fadeEnabled: typeof props.fadeEnabled === 'boolean' ? props.fadeEnabled : true,
            fadeMask: typeof props.fadeMask === 'boolean' ? props.fadeMask : true,
            fadeColor: typeof props.fadeColor === 'string' ? props.fadeColor : '',
            fadeWidth: (() => {
                const n = Number(props.fadeWidth);
                if (!Number.isFinite(n)) return undefined;
                return Math.max(0, Math.min(200, Math.round(n)));
            })(),

            fontFamily: typeof props.fontFamily === 'string' ? props.fontFamily : '',
            fontSize: cssLenOr(props.fontSize, undefined),
            fontWeight: typeof props.fontWeight === 'string' ? props.fontWeight : '',
            lineHeight: cssLenOr(props.lineHeight, undefined),
            letterSpacing: cssLenOr(props.letterSpacing, undefined),
            wordSpacing: cssLenOr(props.wordSpacing, undefined),
            textColor: typeof props.textColor === 'string' ? props.textColor : '',

            backgroundType: ['color', 'classic', 'gradient', 'image'].includes(props.backgroundType) ? props.backgroundType : 'classic',
            backgroundColor: typeof props.backgroundColor === 'string' ? props.backgroundColor : '',
            gradientFrom: typeof props.gradientFrom === 'string' ? props.gradientFrom : '',
            gradientTo: typeof props.gradientTo === 'string' ? props.gradientTo : '',
            gradientFromLocation: Number.isFinite(Number(props.gradientFromLocation)) ? Number(props.gradientFromLocation) : undefined,
            gradientToLocation: Number.isFinite(Number(props.gradientToLocation)) ? Number(props.gradientToLocation) : undefined,
            gradientType: ['linear', 'radial'].includes(props.gradientType) ? props.gradientType : 'linear',
            gradientPosition: typeof props.gradientPosition === 'string' ? props.gradientPosition : '',
            gradientAngle: Number.isFinite(Number(props.gradientAngle)) ? Number(props.gradientAngle) : undefined,
            ...motion,
        },
    };
}

// Preserve responsive typography overrides (…Tablet / …Mobile) that the
// per-type normalizers don't explicitly whitelist, so they survive save/reload.
function normalizeWidget(w) {
    const out = normalizeWidgetCore(w);
    if (!out || typeof out !== 'object') return out;
    const src = w && typeof w.props === 'object' && w.props !== null ? w.props : {};
    const extra = {};
    for (const key of Object.keys(src)) {
        if (/Tablet$|Mobile$/.test(key)) extra[key] = src[key];
    }
    if (Object.keys(extra).length) {
        out.props = { ...(out.props || {}), ...extra };
    }
    const styleSrc = w && typeof w.style === 'object' && w.style !== null ? w.style : {};
    const styleExtra = {};
    for (const key of Object.keys(styleSrc)) {
        if (/Tablet$|Mobile$/.test(key)) styleExtra[key] = styleSrc[key];
    }
    if (Object.keys(styleExtra).length) {
        out.style = { ...(out.style || {}), ...styleExtra };
    }
    return out;
}

const WIDGET_CONTENT_KEYS = {
    heading: ['html', 'text'],
    rich_text: ['html'],
    image: ['url', 'svg', 'source', 'alt', 'link', 'target'],
    button: ['text', 'url'],
    button_group: ['align', 'direction', 'gap'],
    slider: ['images'],
    grid: ['columns'],
    icon: ['iconSvg', 'iconUrl', 'heading', 'subtext'],
    pricing_table: ['heading', 'subheading', 'plans'],
    faqs: ['items'],
    tabs: ['items', 'orientation', 'activeIndex'],
    marquee: ['mode', 'images', 'items', 'separatorIcon'],
    nav_menu: ['items', 'nav_menu_id'],
    link: ['items', 'gap', 'separator'],
    divider: ['backgroundColor', 'height', 'borderTop', 'borderColor'],
    spacer: ['height'],
    logo: ['url', 'svg', 'alt', 'width', 'align'],
    text_timeline: ['items', 'showLoadMore', 'loadMoreText', 'perPage'],
    social_icons: ['icons'],
    social_proof: ['avatars', 'rating', 'text'],
    rating: ['rating', 'max', 'showValue', 'align'],
    newsletter_form: ['buttonText', 'placeholder', 'successMessage', 'listId'],
    copyright: ['text'],
    form_text: ['label', 'placeholder', 'required', 'helpText'],
    form_email: ['label', 'placeholder', 'required', 'helpText'],
    form_textarea: ['label', 'placeholder', 'required', 'helpText', 'rows'],
    form_select: ['label', 'required', 'helpText', 'options'],
    form_checkbox: ['label', 'required', 'helpText', 'options'],
    form_radio: ['label', 'required', 'helpText', 'options'],
    form_submit: ['text', 'fullWidth'],
    ai_prompt_input: ['placeholder', 'showFileUpload', 'showDiscuss', 'discussText', 'showVoice', 'submitUrl', 'loginUrl'],
    testimonials: ['items', ...CAROUSEL_CONTENT_KEYS],
    testimonial: ['avatarUrl', 'avatarSvg', 'avatarSource', 'rating', 'quote', 'name', 'role'],
};

// The per-grid-column floating toolbar (top-center) is redundant with the blue
// column bar shown on hover. Keep the implementation but stop rendering it, and
// surface the grid-level control bar on hover instead.
const GRID_COLUMN_INLINE_TOOLBAR_ENABLED = false;

function isSafeStyleValue(value) {
    if (value === null || value === undefined) return false;
    if (typeof value === 'function') return false;
    if (Array.isArray(value)) return false;
    if (typeof value === 'object') {
        if (typeof value.global === 'string' && value.global) return true;
        if (typeof value.unit === 'string' && (value.value !== undefined || value.value === '')) return true;
        return false;
    }
    return true;
}

function extractWidgetStyleProps(widget) {
    if (!widget || !widget.props || typeof widget.props !== 'object') return {};
    const type = widget.type;
    const props = widget.props;
    const exclude = new Set(WIDGET_CONTENT_KEYS[type] || []);
    const style = {};
    for (const key of Object.keys(props)) {
        if (!exclude.has(key) && isSafeStyleValue(props[key])) {
            style[key] = props[key];
        }
    }
    return style;
}

const TYPOGRAPHY_EXACT_KEYS = new Set([
    'color',
    'textColor',
    'align',
    'textAlign',
    'fontFamily',
    'fontSize',
    'fontWeight',
    'fontStyle',
    'lineHeight',
    'letterSpacing',
    'wordSpacing',
    'textTransform',
    'textDecoration',
    'textShadowColor',
    'textShadowX',
    'textShadowY',
    'textShadowBlur',
]);

const TYPOGRAPHY_KEY_SUFFIXES = [
    'FontFamily',
    'FontSize',
    'FontWeight',
    'FontStyle',
    'LineHeight',
    'LetterSpacing',
    'WordSpacing',
    'TextTransform',
    'TextDecoration',
    'TextColor',
    'TextAlign',
];

function isTypographyPropKey(key) {
    if (typeof key !== 'string' || key === '') return false;
    if (TYPOGRAPHY_EXACT_KEYS.has(key)) return true;
    return TYPOGRAPHY_KEY_SUFFIXES.some((suffix) => key.length > suffix.length && key.endsWith(suffix));
}

function extractWidgetTypographyProps(widget) {
    if (!widget || !widget.props || typeof widget.props !== 'object') return {};
    const exclude = new Set(WIDGET_CONTENT_KEYS[widget.type] || []);
    const out = {};
    for (const key of Object.keys(widget.props)) {
        if (exclude.has(key)) continue;
        if (isTypographyPropKey(key) && isSafeStyleValue(widget.props[key])) {
            out[key] = widget.props[key];
        }
    }
    return out;
}

function typographyStyleFromProps(props) {
    const p = props || {};
    const style = {};

    if (typeof p.color === 'string' && p.color.trim()) style.color = p.color;
    if (typeof p.fontFamily === 'string' && p.fontFamily.trim()) style.fontFamily = p.fontFamily;

    if (typeof p.fontWeight === 'string' && p.fontWeight.trim()) {
        const n = Number(p.fontWeight);
        if (Number.isFinite(n)) style.fontWeight = n;
    }

    // The canvas is a fixed-width preview, so media queries can't drive the
    // breakpoints here. Resolve the active builder viewport instead.
    const fontSize = cssLengthToCss(pickResponsive(p.fontSize, p.fontSizeTablet, p.fontSizeMobile));
    if (fontSize) style.fontSize = fontSize;
    const lineHeight = cssLengthToCss(pickResponsive(p.lineHeight, p.lineHeightTablet, p.lineHeightMobile), { lineHeight: true });
    if (lineHeight) style.lineHeight = lineHeight;
    const letterSpacing = cssLengthToCss(p.letterSpacing);
    if (letterSpacing) style.letterSpacing = letterSpacing;
    const wordSpacing = cssLengthToCss(p.wordSpacing);
    if (wordSpacing) style.wordSpacing = wordSpacing;

    if (typeof p.textShadowColor === 'string' && p.textShadowColor.trim()) {
        const x = typeof p.textShadowX === 'number' && Number.isFinite(p.textShadowX) ? p.textShadowX : 0;
        const y = typeof p.textShadowY === 'number' && Number.isFinite(p.textShadowY) ? p.textShadowY : 0;
        const blur = typeof p.textShadowBlur === 'number' && Number.isFinite(p.textShadowBlur) ? p.textShadowBlur : 0;
        style.textShadow = `${x}px ${y}px ${blur}px ${p.textShadowColor}`;
    }

    const align = pickResponsive(p.align, p.alignTablet, p.alignMobile);
    if (typeof align === 'string' && align.trim()) style.textAlign = align;

    return Object.keys(style).length ? style : undefined;
}

// Emits the scoped CSS variables + media queries that let the typography
// values (font size / line height / align) switch per breakpoint.
function responsiveTypographyCss(props, scope) {
    const p = props || {};
    if (!scope) return '';
    const base = [];
    const fs = cssLengthToCss(p.fontSize);
    const lh = cssLengthToCss(p.lineHeight, { lineHeight: true });
    if (fs) base.push(`--mp-typo-fontSize:${fs}`);
    if (lh) base.push(`--mp-typo-lineHeight:${lh}`);
    if (typeof p.align === 'string' && p.align) base.push(`--mp-typo-textAlign:${p.align}`);

    const tablet = [];
    const fsT = cssLengthToCss(p.fontSizeTablet);
    const lhT = cssLengthToCss(p.lineHeightTablet, { lineHeight: true });
    if (fsT) tablet.push(`--mp-typo-fontSize:${fsT}`);
    if (lhT) tablet.push(`--mp-typo-lineHeight:${lhT}`);
    if (typeof p.alignTablet === 'string' && p.alignTablet) tablet.push(`--mp-typo-textAlign:${p.alignTablet}`);

    const mobile = [];
    const fsM = cssLengthToCss(p.fontSizeMobile);
    const lhM = cssLengthToCss(p.lineHeightMobile, { lineHeight: true });
    if (fsM) mobile.push(`--mp-typo-fontSize:${fsM}`);
    if (lhM) mobile.push(`--mp-typo-lineHeight:${lhM}`);
    if (typeof p.alignMobile === 'string' && p.alignMobile) mobile.push(`--mp-typo-textAlign:${p.alignMobile}`);

    let css = '';
    if (base.length) css += `.${scope}{${base.join(';')};}`;
    if (tablet.length) css += `@media (max-width:1023px){.${scope}{${tablet.join(';')};}}`;
    if (mobile.length) css += `@media (max-width:639px){.${scope}{${mobile.join(';')};}}`;
    return css;
}

function RichTextEditor({ value, onChange }) {
    const ref = useRef(null);
    const onChangeRef = useRef(onChange);
    const [, forceRender] = useState(0);
    const [active, setActive] = useState({ bold: false, italic: false, underline: false, ol: false, ul: false, link: false });

    onChangeRef.current = onChange;

    useEffect(() => {
        if (!ref.current) return;
        if (ref.current.innerHTML !== value) {
            ref.current.innerHTML = value || '';
        }
    }, [value]);

    const readState = () => {
        const el = ref.current;
        if (!el || !el.contains(document.activeElement)) {
            setActive({ bold: false, italic: false, underline: false, ol: false, ul: false, link: false });
            return;
        }
        const sel = window.getSelection();
        let inLink = false, inOl = false, inUl = false;
        if (sel && sel.rangeCount > 0) {
            let node = sel.getRangeAt(0).commonAncestorContainer;
            while (node && node !== el) {
                if (node.nodeName === 'A') inLink = true;
                if (node.nodeName === 'OL') inOl = true;
                if (node.nodeName === 'UL') inUl = true;
                node = node.parentNode;
            }
        }
        setActive({
            bold: document.queryCommandState('bold'),
            italic: document.queryCommandState('italic'),
            underline: document.queryCommandState('underline'),
            ol: inOl,
            ul: inUl,
            link: inLink,
        });
    };

    useEffect(() => {
        document.addEventListener('selectionchange', readState);
        return () => document.removeEventListener('selectionchange', readState);
    }, []);

    useEffect(() => {
        const el = ref.current;
        if (!el) return;

        const handlePaste = (e) => {
            if (e.cancelable) e.preventDefault();
            const html = e.clipboardData?.getData('text/html');
            const text = e.clipboardData?.getData('text/plain');
            const content = html || text || '';
            if (!content) return;
            const selection = window.getSelection();
            if (!selection || selection.rangeCount === 0) return;
            const range = selection.getRangeAt(0);
            if (!el.contains(range.commonAncestorContainer)) return;
            range.deleteContents();
            if (html) {
                const frag = document.createRange().createContextualFragment(html);
                range.insertNode(frag);
            } else {
                range.insertNode(document.createTextNode(content));
            }
            selection.removeAllRanges();
            const newRange = document.createRange();
            newRange.selectNodeContents(el);
            newRange.collapse(false);
            selection.addRange(newRange);
            onChangeRef.current(el.innerHTML);
        };

        el.addEventListener('paste', handlePaste, true);
        return () => el.removeEventListener('paste', handlePaste, true);
    }, []);

    const exec = (cmd, arg) => {
        ref.current?.focus();
        try { document.execCommand(cmd, false, arg); } catch {}
        if (ref.current) onChange(ref.current.innerHTML);
        setTimeout(readState, 0);
    };

    const blockTags = [
        { value: 'p', label: 'Paragraph' },
        { value: 'h1', label: 'Heading 1' },
        { value: 'h2', label: 'Heading 2' },
        { value: 'h3', label: 'Heading 3' },
        { value: 'h4', label: 'Heading 4' },
        { value: 'h5', label: 'Heading 5' },
        { value: 'h6', label: 'Heading 6' },
    ];

    const handleBlock = (tag) => {
        if (tag === 'p') {
            exec('removeFormat');
        } else {
            exec('formatBlock', `<${tag}>`);
        }
    };

    const tbNorm = "!border-0 !px-1 !py-0 text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/10";
    const tbActive = "!border-0 !px-1 !py-0 bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300";

    return (
        <div className="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-900">
            <div className="flex flex-wrap items-center gap-1 border-b border-gray-200 dark:border-gray-700 px-1.5 py-1">
                <select
                    onMouseDown={(e) => e.preventDefault()}
                    onChange={(e) => { handleBlock(e.target.value); e.target.value = 'p'; }}
                    defaultValue="p"
                    className="h-7 rounded border-0 bg-transparent text-xs text-gray-700 dark:text-gray-300 px-1 focus:outline-none cursor-pointer"
                >
                    {blockTags.map((t) => (
                        <option key={t.value} value={t.value}>{t.label}</option>
                    ))}
                </select>
                <div className="w-px h-4 bg-gray-200 dark:bg-gray-700 mx-0.5" />
                <Button type="button" className={active.bold ? tbActive : tbNorm} onMouseDown={(e) => e.preventDefault()} onClick={() => exec('bold')} title="Bold">
                    <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M15.6 10.79c.97-.67 1.65-1.77 1.65-2.79 0-2.26-1.75-4-4-4H7v14h7.04c2.09 0 3.71-1.7 3.71-3.79 0-1.52-.86-2.82-2.15-3.42zM10 6.5h3c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-3v-3zm3.5 9H10v-3h3.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5z" />
                    </svg>
                </Button>
                <Button type="button" className={active.italic ? tbActive : tbNorm} onMouseDown={(e) => e.preventDefault()} onClick={() => exec('italic')} title="Italic">
                    <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M10 4v3h2.21l-3.42 8H6v3h8v-3h-2.21l3.42-8H18V4z" />
                    </svg>
                </Button>
                <Button type="button" className={active.underline ? tbActive : tbNorm} onMouseDown={(e) => e.preventDefault()} onClick={() => exec('underline')} title="Underline">
                    <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 17c3.31 0 6-2.69 6-6V3h-2.5v8c0 1.93-1.57 3.5-3.5 3.5S8.5 12.93 8.5 11V3H6v8c0 3.31 2.69 6 6 6zm-7 2v2h14v-2H5z" />
                    </svg>
                </Button>
                <div className="w-px h-4 bg-gray-200 dark:bg-gray-700 mx-0.5" />
                <Button type="button" className={active.ol ? tbActive : tbNorm} onMouseDown={(e) => e.preventDefault()} onClick={() => exec('insertOrderedList')} title="Ordered List">
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><line x1="10" x2="21" y1="6" y2="6"/><line x1="10" x2="21" y1="12" y2="12"/><line x1="10" x2="21" y1="18" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/></svg>
                </Button>
                <Button type="button" className={active.ul ? tbActive : tbNorm} onMouseDown={(e) => e.preventDefault()} onClick={() => exec('insertUnorderedList')} title="Unordered List">
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><line x1="9" x2="20" y1="6" y2="6"/><line x1="9" x2="20" y1="12" y2="12"/><line x1="9" x2="20" y1="18" y2="18"/><circle cx="4" cy="6" r="1" fill="currentColor"/><circle cx="4" cy="12" r="1" fill="currentColor"/><circle cx="4" cy="18" r="1" fill="currentColor"/></svg>
                </Button>
                <div className="w-px h-4 bg-gray-200 dark:bg-gray-700 mx-0.5" />
                <Button
                    type="button"
                    className={active.link ? tbActive : tbNorm}
                    onMouseDown={(e) => e.preventDefault()}
                    onClick={() => {
                        if (active.link) {
                            exec('unlink');
                        } else {
                            const url = window.prompt('Link URL (https://, mailto:, #anchor)');
                            if (!url) return;
                            exec('createLink', url);
                        }
                    }}
                    title={active.link ? 'Remove Link' : 'Insert Link'}
                >
                    {active.link ? (
                        <svg xmlns="http://www.w3.org/2000/svg" className="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M15 7h2a5 5 0 0 1 0 10h-2m-6 0H7A5 5 0 0 1 7 7h2"/>
                        </svg>
                    ) : (
                        <svg xmlns="http://www.w3.org/2000/svg" className="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M9 17H7A5 5 0 0 1 7 7h2"/>
                            <path d="M15 7h2a5 5 0 1 1 0 10h-2"/>
                            <line x1="8" x2="16" y1="12" y2="12"/>
                        </svg>
                    )}
                </Button>
            </div>
            <div
                ref={ref}
                className="p-3 min-h-[200px] prose max-w-none dark:prose-invert empty:before:content-[attr(data-placeholder)] empty:before:text-gray-400 dark:empty:before:text-gray-500"
                contentEditable
                suppressContentEditableWarning
                data-placeholder="Start typing..."
                onInput={() => {
                    if (ref.current) {
                        onChange(ref.current.innerHTML);
                    }
                }}
            />
        </div>
    );
}

function escapeHtml(text) {
    return String(text || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function normalizeHeadingHtml(html) {
    const s = String(html || '');
    return s
        .replace(/<\/?span[^>]*>/gi, '')
        .replace(/<\/?div[^>]*>/gi, '')
        .replace(/<\/?p[^>]*>/gi, '')
        .replace(/\s*<br\s*\/?>(\s*<br\s*\/?\s*>)+/gi, '<br>')
        .trim();
}

function HeadingContentInspectorRichText({ widget, onChange }) {
    const props = widget.props || {};
    const htmlValue = props.html ? props.html : escapeHtml(props.text || '');

    return (
        <div className="space-y-3">
            <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text</label>
                <RichTextEditor
                    value={htmlValue}
                    onChange={(html) => {
                        const nextHtml = normalizeHeadingHtml(html);
                        const next = { ...props, html: nextHtml };
                        if (String(html || '').trim() === '') {
                            next.text = '';
                        }
                        onChange({ props: next });
                    }}
                />
                <p className="mt-1 text-xs text-gray-500">
                    Wrap any words in <span className="font-mono">{'{{'}like this{'}}'}</span> to apply highlight.
                </p>
            </div>

            <div className="grid grid-cols-2 gap-2">
                <div>
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Level</label>
                    <Select
                        value={String(props.level ?? 2)}
                        onChange={(v) => onChange({ props: { ...props, level: Number(v) } })}
                        options={[1, 2, 3, 4, 5, 6].map((n) => ({ value: String(n), label: `H${n}` }))}
                    />
                </div>
                <div />
            </div>
        </div>
    );
}

function RichTextContentInspector({ widget, onChange }) {
    const props = widget.props || {};

    return (
        <div className="space-y-3">
            <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Content</label>
                <RichTextEditor value={props.html || ''} onChange={(html) => onChange({ props: { ...props, html } })} />
                <p className="mt-1 text-xs text-gray-500">
                    Wrap any words in <span className="font-mono">{'{{'}like this{'}}'}</span> to apply highlight.
                </p>
            </div>
        </div>
    );
}

function RichTextStyleInspector({ widget, onChange }) {
    const props = widget.props || {};

    const paddingKeys = {
        linked: 'paddingLinked',
        t: 'paddingTop',
        r: 'paddingRight',
        b: 'paddingBottom',
        l: 'paddingLeft',
        legacyX: 'paddingX',
        legacyY: 'paddingY',
    };

    const marginKeys = {
        linked: 'marginLinked',
        t: 'marginTop',
        r: 'marginRight',
        b: 'marginBottom',
        l: 'marginLeft',
    };

    const borderWidthKeys = {
        linked: 'borderWidthLinked',
        t: 'borderWidthTop',
        r: 'borderWidthRight',
        b: 'borderWidthBottom',
        l: 'borderWidthLeft',
        legacy: 'borderWidth',
    };

    const borderRadiusKeys = {
        linked: 'borderRadiusLinked',
        t: 'borderRadiusTop',
        r: 'borderRadiusRight',
        b: 'borderRadiusBottom',
        l: 'borderRadiusLeft',
        legacy: 'borderRadius',
    };

    return (
        <div className="border-y border-gray-200 dark:border-gray-700">
            <StyleAccordionItem title="Text" defaultOpen={true}>
                <div className="space-y-3 pb-3">
                    <TypographyPopover
                        value={{
                            fontFamily: props.fontFamily || '',
                            fontSize: props.fontSize,
                            fontWeight: props.fontWeight || '',
                            align: props.align || 'left',
                            lineHeight: props.lineHeight,
                            letterSpacing: props.letterSpacing,
                            wordSpacing: props.wordSpacing,
                        }}
                        onChange={(next) =>
                            onChange({
                                props: {
                                    ...props,
                                    fontFamily: next.fontFamily,
                                    fontSize: next.fontSize === '' ? undefined : next.fontSize,
                                    fontWeight: next.fontWeight,
                                    align: next.align,
                                    lineHeight: next.lineHeight === '' ? undefined : next.lineHeight,
                                    letterSpacing: next.letterSpacing === '' ? undefined : next.letterSpacing,
                                    wordSpacing: next.wordSpacing === '' ? undefined : next.wordSpacing,
                                },
                            })
                        }
                    />

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text Color</label>
                        <div className="flex justify-end">
                            <ColorInput
                                value={props.color || ''}
                                onChange={(v) => onChange({ props: { ...props, color: v } })}
                            />
                        </div>
                    </div>

                    <ShadowPopover
                        label="Text Shadow"
                        type="text"
                        value={{
                            color: props.textShadowColor,
                            offsetX: props.textShadowX,
                            offsetY: props.textShadowY,
                            blur: props.textShadowBlur,
                        }}
                        onChange={(v) =>
                            onChange({
                                props: {
                                    ...props,
                                    textShadowColor: v.color,
                                    textShadowX: v.offsetX,
                                    textShadowY: v.offsetY,
                                    textShadowBlur: v.blur,
                                },
                            })
                        }
                    />

                    <SpacingControls
                        value={props}
                        onChange={(nextProps) => onChange({ props: nextProps })}
                        paddingKeys={paddingKeys}
                        paddingFallbackValue={props.paddingTop ?? props.paddingY ?? props.paddingX}
                        marginKeys={marginKeys}
                        marginFallbackValue={props.marginTop ?? props.marginBottom ?? props.marginLeft ?? props.marginRight}
                    />

                    <BackgroundControls value={props} onChange={(nextProps) => onChange({ props: nextProps })} />

                    <div className="space-y-3">
                        <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Border</div>
                        <BorderControls
                            value={props}
                            onChange={(nextProps) => onChange({ props: nextProps })}
                            widthKeys={borderWidthKeys}
                            widthFallbackValue={props.borderWidthTop ?? props.borderWidth}
                        />
                        <FourSideLinkedControl
                            label="Border Radius"
                            value={props}
                            keys={borderRadiusKeys}
                            fallbackValue={props.borderRadiusTop ?? props.borderRadius}
                            onChange={(nextProps) => onChange({ props: nextProps })}
                        />
                    </div>
                </div>
            </StyleAccordionItem>

            <StyleAccordionItem title="Highlight" defaultOpen={false}>
                <div className="space-y-2 pb-3">
                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Mode</label>
                        <div className="flex justify-end">
                            <Select
                                value={String(props.highlightMode || 'none')}
                                onChange={(v) =>
                                    onChange({
                                        props: {
                                            ...props,
                                            highlightMode: v,
                                        },
                                    })
                                }
                                options={[
                                    { value: 'none', label: 'Off' },
                                    { value: 'color', label: 'Color' },
                                    { value: 'gradient', label: 'Gradient' },
                                ]}
                            />
                        </div>
                    </div>

                    {props.highlightMode && props.highlightMode !== 'none' ? (
                        <TypographyPopover
                            value={{
                                fontFamily: props.highlightFontFamily || '',
                                fontSize: props.highlightFontSize,
                                fontWeight: props.highlightFontWeight || '',
                                align: 'left',
                                lineHeight: props.highlightLineHeight,
                                letterSpacing: props.highlightLetterSpacing,
                                wordSpacing: props.highlightWordSpacing,
                            }}
                            onChange={(next) =>
                                onChange({
                                    props: {
                                        ...props,
                                        highlightFontFamily: next.fontFamily,
                                        highlightFontSize: next.fontSize === '' ? undefined : next.fontSize,
                                        highlightFontWeight: next.fontWeight,
                                        highlightLineHeight: next.lineHeight === '' ? undefined : next.lineHeight,
                                        highlightLetterSpacing:
                                            next.letterSpacing === '' ? undefined : next.letterSpacing,
                                        highlightWordSpacing: next.wordSpacing === '' ? undefined : next.wordSpacing,
                                    },
                                })
                            }
                        />
                    ) : null}

                    {props.highlightMode === 'color' ? (
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Color</label>
                            <div className="flex justify-end">
                                <ColorInput
                                    value={props.highlightColor || ''}
                                    onChange={(v) => onChange({ props: { ...props, highlightColor: v } })}
                                />
                            </div>
                        </div>
                    ) : null}

                    {props.highlightMode === 'gradient' ? (
                        <div className="space-y-2">
                            <div className="grid grid-cols-2 gap-3 items-center">
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">From</label>
                                <div className="flex justify-end">
                                    <ColorInput
                                        value={props.highlightGradientFrom || ''}
                                        onChange={(v) => onChange({ props: { ...props, highlightGradientFrom: v } })}
                                    />
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-3 items-center">
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
                                <div className="flex justify-end">
                                    <ColorInput
                                        value={props.highlightGradientTo || ''}
                                        onChange={(v) => onChange({ props: { ...props, highlightGradientTo: v } })}
                                    />
                                </div>
                            </div>

                            <label className="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input
                                    type="checkbox"
                                    checked={props.highlightAnimate === true}
                                    onChange={(e) =>
                                        onChange({ props: { ...props, highlightAnimate: e.target.checked } })
                                    }
                                />
                                <span>Animate Gradient</span>
                            </label>

                            {props.highlightAnimate === true ? (
                                <div className="grid grid-cols-2 gap-3 items-center">
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Animation (s)
                                    </label>
                                    <div className="flex justify-end">
                                        <Input
                                            type="number"
                                            value={String(props.highlightAnimationDuration ?? 6)}
                                            onChange={(v) => {
                                                const n = Number(v);
                                                onChange({
                                                    props: {
                                                        ...props,
                                                        highlightAnimationDuration: Number.isFinite(n) ? n : undefined,
                                                    },
                                                });
                                            }}
                                        />
                                    </div>
                                </div>
                            ) : null}
                        </div>
                    ) : null}
                </div>
            </StyleAccordionItem>
        </div>
    );
}

function PageBuilderApp({ initialData, onChange, uploadUrl, aiUrl, aiMessageUrl, aiModelsUrl, aiMessagesUrl, pageTitle, settingsUrl, previewUrl, renderHtmlUrl, testEmailUrl, templateName, deliveryServers = [], aiEnabled = true, aiDefaultModel = '', aiEnabledModels = [], embedMode, mode = 'page', megaMenus = [], navMenus = [], lists = [], templatesApi = null, initialGlobals = null, layoutType = '', megaMenuBuilderEnabled = false }) {
    if (typeof window !== 'undefined') {
        window.__MP_MEGA_MENUS = megaMenus;
        window.__MP_NAV_MENUS = navMenus;
        window.__MP_MEGA_MENU_BUILDER_ENABLED = megaMenuBuilderEnabled === true;
    }
    const initialPageRef = useRef(null);
    if (initialPageRef.current === null) {
         initialPageRef.current = normalizePage(initialData, mode);
    }

    const [page, setPageState] = useState(() => initialPageRef.current);
    const [globalOverridesOpen, setGlobalOverridesOpen] = useState(false);
    const globals = useGlobals(initialGlobals, page.globals);
    const pageRef = useRef(page);
    useLayoutEffect(() => {
        pageRef.current = page;
    }, [page]);

    useLayoutEffect(() => {
        if (railContentRef.current && railContentRef.current.scrollTop !== railScrollTopRef.current) {
            railContentRef.current.scrollTop = railScrollTopRef.current;
        }
    }, [page]);

    const onChangeRef = useRef(onChange);
    useEffect(() => {
        onChangeRef.current = onChange;
    }, [onChange]);
    useLayoutEffect(() => {
        try {
            onChangeRef.current(page);
        } catch (e) {
            // ignore
        }
    }, [page]);

    const savedSnapshotRef = useRef('');
    if (!savedSnapshotRef.current) {
        savedSnapshotRef.current = stableStringify(initialPageRef.current);
    }

    const pageSnapshot = useMemo(() => stableStringify(page), [page]);
    const isDirty = pageSnapshot !== savedSnapshotRef.current;
    const isSubmittingRef = useRef(false);
    const [, setSavedTick] = useState(0);

    useEffect(() => {
        if (typeof window === 'undefined') return undefined;

        window.mailpursePageBuilderMarkSaved = () => {
            savedSnapshotRef.current = stableStringify(pageRef.current || page);
            setSavedTick((tick) => tick + 1);
        };

        return () => {
            delete window.mailpursePageBuilderMarkSaved;
        };
    }, []);

    useEffect(() => {
        const seen = new Set();

        const loadPropsFonts = (props) => {
            if (!props || typeof props !== 'object') return;
            Object.entries(props).forEach(([key, value]) => {
                if (!key || !key.toLowerCase().endsWith('fontfamily')) return;
                if (typeof value !== 'string' || !value.trim()) return;
                if (seen.has(value)) return;
                seen.add(value);
                loadGoogleFont(value);
            });
        };

        const walkWidgets = (widgets) => {
            (Array.isArray(widgets) ? widgets : []).forEach((w) => {
                if (!w || typeof w !== 'object') return;

                const props = w.props && typeof w.props === 'object' ? w.props : null;
                loadPropsFonts(props);

                if (!props) return;
                if (w.type === 'grid') {
                    (Array.isArray(props.columns) ? props.columns : []).forEach((gc) => walkWidgets(gc?.widgets));
                    return;
                }
                if (w.type === 'tabs') {
                    (Array.isArray(props.items) ? props.items : []).forEach((it) => walkWidgets(it?.widgets));
                }
            });
        };

        (Array.isArray(page.sections) ? page.sections : []).forEach((s) => {
            (Array.isArray(s?.columns) ? s.columns : []).forEach((c) => walkWidgets(c?.widgets));
        });
    }, [page]);

    const [history, setHistory] = useState(() => ({ past: [], future: [] }));

    const setPage = (updater, opts = {}) => {
        const recordHistory = opts && Object.prototype.hasOwnProperty.call(opts, 'history') ? Boolean(opts.history) : true;
        const curr = pageRef.current;
        const next = typeof updater === 'function' ? updater(curr) : updater;

        // Defensive: never let a section's columns become a non-array value.
        // This catches any stray patch/bind that would otherwise break .map calls.
        if (next && next !== curr && Array.isArray(next.sections)) {
            let needsFix = false;
            const fixedSections = next.sections.map((s) => {
                if (!s || typeof s !== 'object') return s;
                if (Array.isArray(s.columns)) return s;
                needsFix = true;
                return { ...s, columns: [] };
            });
            if (needsFix) {
                next.sections = fixedSections;
            }
        }

        // Keep the ref in sync immediately so rapid successive calls in the same
        // event don't read a stale state.
        pageRef.current = next;

        if (next !== curr) {
            if (recordHistory) {
                // Trim the undo stack so long editing sessions don't grow unbounded.
                const MAX_HISTORY = 100;
                setHistory((h) => {
                    const past = [...h.past, deepClone(curr)];
                    return { past: past.length > MAX_HISTORY ? past.slice(past.length - MAX_HISTORY) : past, future: [] };
                });
            }
            setPageState(next);
        }
    };

    const handlePageChange = (patch) => setPage((p) => {
        const next = { ...p };
        for (const key of Object.keys(patch)) {
            if (patch[key] && typeof patch[key] === 'object' && !Array.isArray(patch[key]) && p[key] && typeof p[key] === 'object' && !Array.isArray(p[key])) {
                next[key] = { ...p[key], ...patch[key] };
            } else {
                next[key] = patch[key];
            }
        }
        return next;
    });

    const getInitialSelection = (p) => {
        const sections = p && Array.isArray(p.sections) ? p.sections : [];
        for (const s of sections) {
            const columns = s && Array.isArray(s.columns) ? s.columns : [];
            for (const c of columns) {
                const widgets = c && Array.isArray(c.widgets) ? c.widgets : [];
                for (const w of widgets) {
                    if (!w || !w.id) continue;

                    if (w.type === 'grid') {
                        const gridCols = w.props && Array.isArray(w.props.columns) ? w.props.columns : [];
                        for (const gc of gridCols) {
                            const innerWidgets = gc && Array.isArray(gc.widgets) ? gc.widgets : [];
                            const firstInner = innerWidgets.find((x) => x && x.id);
                            if (gc && gc.id && firstInner) {
                                return {
                                    kind: 'grid_widget',
                                    sectionId: s.id,
                                    columnId: c.id,
                                    gridWidgetId: w.id,
                                    gridColumnId: gc.id,
                                    widgetId: firstInner.id,
                                };
                            }
                        }
                    }

                    return { kind: 'widget', sectionId: s.id, columnId: c.id, widgetId: w.id };
                }
            }
        }

        const firstSectionId = sections[0] && sections[0].id ? sections[0].id : null;
        return firstSectionId ? { kind: 'section', sectionId: firstSectionId } : {};
    };

    const uiStateKeyRef = useRef('');
    if (!uiStateKeyRef.current) {
        try {
            const form = document.getElementById('page-builder-form');
            const action = form && form.action ? String(form.action) : '';
            uiStateKeyRef.current = `mp_page_builder_ui:${action || String(pageTitle || '') || 'page'}`;
        } catch (e) {
            uiStateKeyRef.current = `mp_page_builder_ui:${String(pageTitle || '') || 'page'}`;
        }
    }

    const initialUiStateRef = useRef(undefined);
    if (initialUiStateRef.current === undefined) {
        try {
            const raw = typeof window !== 'undefined' && window.sessionStorage ? window.sessionStorage.getItem(uiStateKeyRef.current) : null;
            const parsed = raw ? JSON.parse(raw) : null;
            if (parsed && typeof parsed === 'object' && parsed.restoreOnce === true) {
                initialUiStateRef.current = parsed;
            } else {
                initialUiStateRef.current = null;
            }
        } catch (e) {
            initialUiStateRef.current = null;
        }
    }

    const restoringUiRef = useRef(Boolean(initialUiStateRef.current));

    const [selected, setSelected] = useState(() => {
        const s = initialUiStateRef.current;
        return s && typeof s === 'object' && s.selected && typeof s.selected === 'object' ? s.selected : {};
    });

    const [railTab, setRailTab] = useState(() => {
        const s = initialUiStateRef.current;
        return s && typeof s === 'object' && typeof s.railTab === 'string' ? s.railTab : 'elements';
    });
    const [editTab, setEditTab] = useState(() => {
        const s = initialUiStateRef.current;
        return s && typeof s === 'object' && typeof s.editTab === 'string' ? s.editTab : 'content';
    });
    const [query, setQuery] = useState('');
    const [quickAdd, setQuickAdd] = useState(null);

    const [hoveredSectionId, setHoveredSectionId] = useState(null);
    const [topInsertSectionId, setTopInsertSectionId] = useState(null);
    const [hoveredColumnKey, setHoveredColumnKey] = useState(null);
    const [hoveredGridWidgetKey, setHoveredGridWidgetKey] = useState(null);
    const [hoveredGridColumnKey, setHoveredGridColumnKey] = useState(null);
    const [dropIndicator, setDropIndicator] = useState(null);
    const [headerStickyActive, setHeaderStickyActive] = useState(false);

    const [insertModal, setInsertModal] = useState(null);

    const skipAutoEditRef = useRef(false);
    const lastSelectionKeyRef = useRef(null);
    const widgetsContainerRefs = useRef(new Map()).current;
    const gridWidgetsContainerRefs = useRef(new Map()).current;
    const previewScrollRef = useRef(null);
    const railContentRef = useRef(null);
    const railScrollTopRef = useRef(0);

    const [viewport, setViewport] = useState(() => {
        const s = initialUiStateRef.current;
        return s && typeof s === 'object' && typeof s.viewport === 'string' ? s.viewport : 'desktop';
    });
    setBuilderViewport(viewport);

    const [collapsedSections, setCollapsedSections] = useState(() => ({}));
    const [collapsedColumns, setCollapsedColumns] = useState(() => ({}));

    const [contextMenu, setContextMenu] = useState(null);
    const [clipboard, setClipboard] = useState(null);
    const [styleClipboard, setStyleClipboard] = useState(null);
    const [typographyClipboard, setTypographyClipboard] = useState(null);

    const [exportOpen, setExportOpen] = useState(false);
    const exportWrapRef = useRef(null);

    const [navigatorOpen, setNavigatorOpen] = useState(false);
    const [navigatorPos, setNavigatorPos] = useState({ x: 16, y: 16 });
    const navigatorDragRef = useRef(null);
    const navigatorFlashRef = useRef(null);

    const focusNavigatorTarget = (target) => {
        const container = previewScrollRef.current;
        if (!container || !target) return;

        let selector = '';
        if (target.kind === 'section') selector = `[data-mp-nav-section="${target.sectionId}"]`;
        else if (target.kind === 'column') selector = `[data-mp-nav-column="${target.columnId}"]`;
        else if (target.kind === 'widget') selector = `[data-widget-id="${target.widgetId}"]`;
        if (!selector) return;

        const el = container.querySelector(selector);
        if (!el) return;

        el.scrollIntoView({ behavior: 'smooth', block: 'center' });

        if (navigatorFlashRef.current && navigatorFlashRef.current.el && navigatorFlashRef.current.restore) {
            navigatorFlashRef.current.restore();
        }

        const prevOutline = el.style.outline;
        const prevOffset = el.style.outlineOffset;
        const prevTransition = el.style.transition;
        el.style.transition = 'outline-color 0.4s ease';
        el.style.outline = '2px solid #2563eb';
        el.style.outlineOffset = '2px';

        const restore = () => {
            el.style.outline = prevOutline;
            el.style.outlineOffset = prevOffset;
            el.style.transition = prevTransition;
        };
        const timer = window.setTimeout(() => {
            restore();
            if (navigatorFlashRef.current && navigatorFlashRef.current.timer === timer) {
                navigatorFlashRef.current = null;
            }
        }, 1400);
        navigatorFlashRef.current = { el, restore, timer };
    };
    const exportButtonRef = useRef(null);
    const exportMenuRef = useRef(null);
    const [exportMenuPos, setExportMenuPos] = useState(null);

    const importWrapRef = useRef(null);
    const importButtonRef = useRef(null);
    const importMenuRef = useRef(null);
    const importInputRef = useRef(null);
    const importFormatRef = useRef('json');
    const [importOpen, setImportOpen] = useState(false);
    const [importMenuPos, setImportMenuPos] = useState(null);
    const [importCodeOpen, setImportCodeOpen] = useState(false);
    const [importCodeValue, setImportCodeValue] = useState('');

    const [status, setStatusState] = useState(() => {
        const input = document.getElementById('status');
        const v = input ? String(input.value || 'draft') : 'draft';
        return v === 'publish' ? 'publish' : 'draft';
    });
    const [previewOpen, setPreviewOpen] = useState(false);
    const [previewHtml, setPreviewHtml] = useState('');
    const [previewLoading, setPreviewLoading] = useState(false);
    const [testOpen, setTestOpen] = useState(false);
    const [testServerId, setTestServerId] = useState('');
    const [testEmail, setTestEmail] = useState('');
    const [testLoading, setTestLoading] = useState(false);
    const [testMessage, setTestMessage] = useState('');
    const [testError, setTestError] = useState('');

    useEffect(() => {
        try {
            if (initialUiStateRef.current && typeof window !== 'undefined' && window.sessionStorage) {
                window.sessionStorage.removeItem(uiStateKeyRef.current);
            }
        } catch (e) {
        }
    }, []);

    useLayoutEffect(() => {
        const s = initialUiStateRef.current;
        if (!s || typeof s !== 'object') return undefined;

        const apply = () => {
            if (previewScrollRef.current && Number.isFinite(Number(s.previewScrollTop))) {
                previewScrollRef.current.scrollTop = Number(s.previewScrollTop);
            }
            if (railContentRef.current && Number.isFinite(Number(s.railScrollTop))) {
                railContentRef.current.scrollTop = Number(s.railScrollTop);
                railScrollTopRef.current = Number(s.railScrollTop);
            }
        };

        apply();
        const raf = typeof window !== 'undefined' ? window.requestAnimationFrame(apply) : null;
        const timer = typeof window !== 'undefined' ? window.setTimeout(apply, 150) : null;
        return () => {
            if (raf && typeof window !== 'undefined') window.cancelAnimationFrame(raf);
            if (timer && typeof window !== 'undefined') window.clearTimeout(timer);
        };
    }, []);

    useEffect(() => {
        if (!aiEnabled && railTab === 'ai') {
            setRailTab('elements');
        }
    }, [aiEnabled, railTab]);

    useEffect(() => {
        onChange(page);

        try {
            if (typeof window !== 'undefined' && typeof window.__mailpurseInitGsapAnimations === 'function') {
                requestAnimationFrame(() => {
                    try {
                        window.__mailpurseInitGsapAnimations();
                    } catch (e) {
                    }
                });
            }
        } catch (e) {
        }
    }, [page, onChange]);

    useEffect(() => {
        if (typeof window === 'undefined') return;

        const onBeforeUnload = (e) => {
            if (!isDirty || isSubmittingRef.current) return;
            e.preventDefault();
            e.returnValue = '';
            return '';
        };

        const onTurboBeforeVisit = (e) => {
            if (!isDirty || isSubmittingRef.current) return;
            try {
                const ok = window.confirm('You have unsaved changes. Are you sure you want to leave this page?');
                if (!ok) {
                    e.preventDefault();
                }
            } catch (err) {
                e.preventDefault();
            }
        };

        window.addEventListener('beforeunload', onBeforeUnload);
        document.addEventListener('turbo:before-visit', onTurboBeforeVisit);
        return () => {
            window.removeEventListener('beforeunload', onBeforeUnload);
            document.removeEventListener('turbo:before-visit', onTurboBeforeVisit);
        };
    }, [isDirty]);

    useEffect(() => {
        if (typeof window === 'undefined') return;
        const onDragEnd = () => setDropIndicator(null);
        window.addEventListener('dragend', onDragEnd);
        return () => window.removeEventListener('dragend', onDragEnd);
    }, []);

    useEffect(() => {
        const container = previewScrollRef.current;
        const shouldTrackSticky = layoutType === 'header' && page.style?.headerSticky === true;

        if (!container || !shouldTrackSticky) {
            setHeaderStickyActive(false);
            return undefined;
        }

        const onScroll = () => {
            setHeaderStickyActive(container.scrollTop > 10);
        };

        container.addEventListener('scroll', onScroll, { passive: true });
        onScroll();

        return () => container.removeEventListener('scroll', onScroll);
    }, [layoutType, page.style?.headerSticky]);

    useEffect(() => {
        if (!exportOpen) return;

        const onPointerDown = (e) => {
            const el = exportWrapRef.current;
            const menuEl = exportMenuRef.current;
            if (!el && !menuEl) return;
            if (el && el.contains(e.target)) return;
            if (menuEl && menuEl.contains(e.target)) return;
            setExportOpen(false);
        };

        const onKeyDown = (e) => {
            if (e.key === 'Escape') {
                setExportOpen(false);
            }
        };

        document.addEventListener('pointerdown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);
        return () => {
            document.removeEventListener('pointerdown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [exportOpen]);

    useEffect(() => {
        if (!exportOpen) {
            setExportMenuPos(null);
            return;
        }

        const updatePos = () => {
            const btn = exportButtonRef.current;
            if (!btn) return;
            const r = btn.getBoundingClientRect();
            setExportMenuPos({ top: r.bottom + 8, left: r.right });
        };

        updatePos();
        window.addEventListener('resize', updatePos);
        window.addEventListener('scroll', updatePos, true);
        return () => {
            window.removeEventListener('resize', updatePos);
            window.removeEventListener('scroll', updatePos, true);
        };
    }, [exportOpen]);

    useEffect(() => {
        if (!importOpen) return;

        const onPointerDown = (e) => {
            const el = importWrapRef.current;
            const menuEl = importMenuRef.current;
            if (!el && !menuEl) return;
            if (el && el.contains(e.target)) return;
            if (menuEl && menuEl.contains(e.target)) return;
            setImportOpen(false);
        };

        const onKeyDown = (e) => {
            if (e.key === 'Escape') {
                setImportOpen(false);
            }
        };

        document.addEventListener('pointerdown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);
        return () => {
            document.removeEventListener('pointerdown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [importOpen]);

    useEffect(() => {
        if (!importOpen) {
            setImportMenuPos(null);
            return;
        }

        const updatePos = () => {
            const btn = importButtonRef.current;
            if (!btn) return;
            const r = btn.getBoundingClientRect();
            setImportMenuPos({ top: r.bottom + 8, left: r.right });
        };

        updatePos();
        window.addEventListener('resize', updatePos);
        window.addEventListener('scroll', updatePos, true);
        return () => {
            window.removeEventListener('resize', updatePos);
            window.removeEventListener('scroll', updatePos, true);
        };
    }, [importOpen]);

    const canUndo = history.past.length > 0;
    const canRedo = history.future.length > 0;

    const undo = () => {
        setHistory((h) => {
            if (h.past.length === 0) return h;
            const prev = h.past[h.past.length - 1];
            const nextPast = h.past.slice(0, -1);
            const nextFuture = [deepClone(pageRef.current), ...h.future];
            setPageState(deepClone(prev));
            return { past: nextPast, future: nextFuture };
        });
    };

    const redo = () => {
        setHistory((h) => {
            if (h.future.length === 0) return h;
            const next = h.future[0];
            const nextFuture = h.future.slice(1);
            const nextPast = [...h.past, deepClone(pageRef.current)];
            setPageState(deepClone(next));
            return { past: nextPast, future: nextFuture };
        });
    };

    const safeKeyframeId = (id) => String(id || '').replace(/[^a-zA-Z0-9_-]/g, '_');
    const transformString = (tx, ty, rot, scale) => `translate(${tx}px, ${ty}px) rotate(${rot}deg) scale(${scale})`;
    const clampScale = (v) => {
        const n = Number(v);
        if (!Number.isFinite(n)) return 1;
        return Math.max(0.1, Math.min(3, n));
    };

    const customMotionKeyframesCss = useMemo(() => {
        const rules = [];
        const seen = new Set();

        const addRule = (id, motionLike) => {
            const m = motionLike && typeof motionLike === 'object' ? motionLike : {};
            if (String(m.animationPreset || '') !== 'custom') return;

            const safeId = safeKeyframeId(id);
            if (!safeId) return;
            const name = `mp-custom-${safeId}`;
            if (seen.has(name)) return;
            seen.add(name);

            const fromTx = Number.isFinite(Number(m.animationFromTranslateX)) ? Number(m.animationFromTranslateX) : 0;
            const fromTy = Number.isFinite(Number(m.animationFromTranslateY)) ? Number(m.animationFromTranslateY) : 0;
            const fromRot = Number.isFinite(Number(m.animationFromRotate)) ? Number(m.animationFromRotate) : 0;
            const fromScale = clampScale(m.animationFromScale);

            const fallbackToTx = Number.isFinite(Number(m.transformTranslateX)) ? Number(m.transformTranslateX) : 0;
            const fallbackToTy = Number.isFinite(Number(m.transformTranslateY)) ? Number(m.transformTranslateY) : 0;
            const fallbackToRot = Number.isFinite(Number(m.transformRotate)) ? Number(m.transformRotate) : 0;
            const fallbackToScale = clampScale(m.transformScale);

            const toTx = Number.isFinite(Number(m.animationToTranslateX)) ? Number(m.animationToTranslateX) : fallbackToTx;
            const toTy = Number.isFinite(Number(m.animationToTranslateY)) ? Number(m.animationToTranslateY) : fallbackToTy;
            const toRot = Number.isFinite(Number(m.animationToRotate)) ? Number(m.animationToRotate) : fallbackToRot;
            const toScale = Number.isFinite(Number(m.animationToScale)) ? clampScale(m.animationToScale) : fallbackToScale;

            rules.push(`@keyframes ${name}{from{transform:${transformString(fromTx, fromTy, fromRot, fromScale)};}to{transform:${transformString(toTx, toTy, toRot, toScale)};}}`);
        };

        const walkWidgets = (widgets) => {
            (Array.isArray(widgets) ? widgets : []).forEach((w) => {
                if (!w || typeof w !== 'object') return;
                addRule(w.id, w.props);

                // Add style rules for grid widgets
                if (w.type === 'grid') {
                    addRule(w.id, w.style);
                    
                    const cols = w.props && Array.isArray(w.props.columns) ? w.props.columns : [];
                    cols.forEach((gc) => {
                        if (!gc || typeof gc !== 'object') return;
                        walkWidgets(gc.widgets);
                    });
                } else {
                    // For non-grid widgets, also add style rules if they exist
                    if (w.style && typeof w.style === 'object') {
                        addRule(w.id, w.style);
                    }
                }
            });
        };

        (Array.isArray(page.sections) ? page.sections : []).forEach((s) => {
            if (!s || typeof s !== 'object') return;
            addRule(s.id, s.style);
            (Array.isArray(s.columns) ? s.columns : []).forEach((c) => {
                if (!c || typeof c !== 'object') return;
                walkWidgets(c.widgets);
            });

            const presetKey = s.style && typeof s.style === 'object' && s.style.backgroundPreset && typeof s.style.backgroundPreset === 'object'
                ? s.style.backgroundPreset.key
                : 'css-none';
            const preset = getPresetByKey(presetKey);
            if (preset && preset.key !== 'css-none') {
                const kf = getPresetKeyframes(preset, s.id);
                if (kf && !seen.has(kf)) {
                    seen.add(kf);
                    rules.push(kf);
                }
            }
        });

        return rules.length ? rules.join('\n') : '';
    }, [page]);

    const sectionBackgroundStyle = (style) => {
        const s = style && typeof style === 'object' ? style : {};
        const out = {};
        const rawBgType = typeof s.backgroundType === 'string' ? s.backgroundType : 'color';
        const bgType = rawBgType === 'classic' ? 'color' : rawBgType;
        if (bgType === 'gradient') {
            const from = typeof s.gradientFrom === 'string' ? s.gradientFrom : '#111827';
            const to = typeof s.gradientTo === 'string' ? s.gradientTo : '#3b82f6';
            const ang = Number.isFinite(Number(s.gradientAngle)) ? Number(s.gradientAngle) : 135;
            const type = (s.gradientType === 'radial' || s.gradientType === 'linear') ? s.gradientType : 'linear';
            const position = typeof s.gradientPosition === 'string' && s.gradientPosition ? s.gradientPosition : 'top center';
            if (type === 'radial') {
                out.backgroundImage = `radial-gradient(at ${position}, ${from} 0%, ${to} 100%)`;
            } else {
                out.backgroundImage = `linear-gradient(${ang}deg, ${from} 0%, ${to} 100%)`;
            }
            if (s.gradientAnimate) {
                out.backgroundSize = '400% 400%';
            }
        } else if (bgType === 'image') {
            const url = s.backgroundImage && typeof s.backgroundImage === 'object' ? String(s.backgroundImage.url || '') : '';
            if (url) {
                out.backgroundImage = `url(${url})`;
                out.backgroundSize = typeof s.backgroundSize === 'string' && s.backgroundSize ? s.backgroundSize : 'cover';
                out.backgroundPosition = typeof s.backgroundPosition === 'string' && s.backgroundPosition ? s.backgroundPosition : 'center';
                out.backgroundRepeat = typeof s.backgroundRepeat === 'string' && s.backgroundRepeat ? s.backgroundRepeat : 'no-repeat';
            }
        } else if (bgType === 'color') {
            const color = typeof s.backgroundColor === 'string' ? s.backgroundColor : '#ffffff';
            out.backgroundColor = color;
        }
        if (s.darkMode) {
            out.backgroundColor = '#000000';
            delete out.backgroundImage;
        }
        const backdropBlur = Number.isFinite(Number(s.backdropBlur)) ? Number(s.backdropBlur) : 0;
        if (backdropBlur > 0) {
            out.backdropFilter = `blur(${backdropBlur}px)`;
            out.WebkitBackdropFilter = `blur(${backdropBlur}px)`;
        }
        return out;
    };

    const sectionHasNavMenu = (sid) => {
        const section = page.sections?.find((sec) => sec.id === sid);
        if (!section || !Array.isArray(section.columns)) return false;
        return section.columns.some((col) =>
            Array.isArray(col.widgets) && col.widgets.some((w) => w.type === 'nav_menu')
        );
    };

    const sectionCanvasStyle = (style, sectionId, sectionIndex = 0, stickyConfig = null) => {
        const s = style && typeof style === 'object' ? style : {};
        const hasNavMenu = sectionHasNavMenu(sectionId);

        const sPick = (key) => pickResponsive(s[key], s[`${key}Tablet`], s[`${key}Mobile`]);
        const out = {
            marginTop: Number.isFinite(Number(sPick('marginTop'))) ? Number(sPick('marginTop')) : 0,
            marginRight: Number.isFinite(Number(sPick('marginRight'))) ? Number(sPick('marginRight')) : 0,
            marginBottom: Number.isFinite(Number(sPick('marginBottom'))) ? Number(sPick('marginBottom')) : 0,
            marginLeft: Number.isFinite(Number(sPick('marginLeft'))) ? Number(sPick('marginLeft')) : 0,
        };

        const maxWidthType = s.maxWidthType || (s.maxWidth === 'full' ? 'full' : 'boxed');
        if (maxWidthType === 'full') {
            out.width = '100%';
            Object.assign(out, sectionBackgroundStyle(s));
        } else {
            const boxedWidth = resolveUnit(pickResponsive(s.boxedWidth, s.boxedWidthTablet, s.boxedWidthMobile), 'px') || (s.maxWidth === '7xl' ? '1280px' : '1152px');
            out.width = '100%';
            out.maxWidth = boxedWidth;
            if ((s.marginLeft === 0 || s.marginLeft === undefined || s.marginLeft === null) && (s.marginRight === 0 || s.marginRight === undefined || s.marginRight === null)) {
                out.marginLeft = 'auto';
                out.marginRight = 'auto';
            }
        }

        const headerHeight = Number.isFinite(Number(s.headerHeight)) ? Number(s.headerHeight) : undefined;
        if (maxWidthType === 'full' && headerHeight !== undefined && headerHeight > 0) {
            out.height = `${headerHeight}px`;
            out.display = 'flex';
            out.flexDirection = 'column';
            out.alignItems = 'center';
            out.overflow = hasNavMenu ? 'visible' : 'hidden';
        }

        // Apply sticky header styles directly to the section canvas for full-width
        // headers so the sticky background overrides the base background.
        // Keep width:100% always and only transition max-width so the browser
        // can interpolate between two concrete length values smoothly.
        if (stickyConfig && maxWidthType === 'full') {
            out.position = 'sticky';
            out.top = 0;
            out.zIndex = 50;
            out.marginLeft = 'auto';
            out.marginRight = 'auto';
            out.maxWidth = '100%';
            out.display = 'flex';
            out.flexDirection = 'column';
            out.justifyContent = 'center';
            out.transition = 'height 0.4s ease, max-width 0.4s ease, background-color 0.4s ease, background-image 0.4s ease, border 0.4s ease, border-radius 0.4s ease, color 0.4s ease';
            if (stickyConfig.active) {
                const stickyHeight = stickyConfig.height ? resolveUnit(stickyConfig.height) : '';
                if (stickyHeight) {
                    out.height = stickyHeight;
                }
                const stickyWidth = stickyConfig.width ? resolveUnit(stickyConfig.width) : '';
                if (stickyWidth) {
                    out.maxWidth = stickyWidth;
                }
                if (stickyConfig.backgroundType === 'gradient') {
                    const from = stickyConfig.gradientFrom || '#111827';
                    const to = stickyConfig.gradientTo || '#3b82f6';
                    const angle = Number.isFinite(Number(stickyConfig.gradientAngle)) ? Number(stickyConfig.gradientAngle) : 135;
                    const type = (stickyConfig.gradientType === 'radial' || stickyConfig.gradientType === 'linear') ? stickyConfig.gradientType : 'linear';
                    const position = stickyConfig.gradientPosition || 'top center';
                    if (type === 'radial') {
                        out.backgroundImage = `radial-gradient(at ${position}, ${from} 0%, ${to} 100%)`;
                    } else {
                        out.backgroundImage = `linear-gradient(${angle}deg, ${from} 0%, ${to} 100%)`;
                    }
                    out.backgroundColor = undefined;
                } else if (stickyConfig.backgroundColor) {
                    out.backgroundColor = stickyConfig.backgroundColor;
                    out.backgroundImage = undefined;
                }
                if (Number.isFinite(Number(stickyConfig.backdropBlur)) && Number(stickyConfig.backdropBlur) >= 0) {
                    out.backdropFilter = `blur(${stickyConfig.backdropBlur}px)`;
                    out.WebkitBackdropFilter = `blur(${stickyConfig.backdropBlur}px)`;
                }
                if (typeof stickyConfig.borderStyle === 'string' && stickyConfig.borderStyle && stickyConfig.borderStyle !== 'default') {
                    out.borderStyle = stickyConfig.borderStyle;
                    if (typeof stickyConfig.borderColor === 'string' && stickyConfig.borderColor) {
                        out.borderColor = stickyConfig.borderColor;
                    }
                    if (Number.isFinite(Number(stickyConfig.borderWidthTop))) out.borderTopWidth = `${stickyConfig.borderWidthTop}px`;
                    if (Number.isFinite(Number(stickyConfig.borderWidthRight))) out.borderRightWidth = `${stickyConfig.borderWidthRight}px`;
                    if (Number.isFinite(Number(stickyConfig.borderWidthBottom))) out.borderBottomWidth = `${stickyConfig.borderWidthBottom}px`;
                    if (Number.isFinite(Number(stickyConfig.borderWidthLeft))) out.borderLeftWidth = `${stickyConfig.borderWidthLeft}px`;
                }
                if (Number.isFinite(Number(stickyConfig.borderRadiusTop))) out.borderTopLeftRadius = `${stickyConfig.borderRadiusTop}px`;
                if (Number.isFinite(Number(stickyConfig.borderRadiusRight))) out.borderTopRightRadius = `${stickyConfig.borderRadiusRight}px`;
                if (Number.isFinite(Number(stickyConfig.borderRadiusBottom))) out.borderBottomRightRadius = `${stickyConfig.borderRadiusBottom}px`;
                if (Number.isFinite(Number(stickyConfig.borderRadiusLeft))) out.borderBottomLeftRadius = `${stickyConfig.borderRadiusLeft}px`;
                if (typeof stickyConfig.textColor === 'string' && stickyConfig.textColor) {
                    out.color = stickyConfig.textColor;
                }
                if (typeof stickyConfig.linkColor === 'string' && stickyConfig.linkColor) {
                    out['--mp-sticky-link-color'] = stickyConfig.linkColor;
                }
                if (Number.isFinite(Number(stickyConfig.marginTop))) out.top = `${stickyConfig.marginTop}px`;
                if (Number.isFinite(Number(stickyConfig.marginRight))) out.marginRight = `${stickyConfig.marginRight}px`;
                if (Number.isFinite(Number(stickyConfig.marginBottom))) out.marginBottom = `${stickyConfig.marginBottom}px`;
                if (Number.isFinite(Number(stickyConfig.marginLeft))) out.marginLeft = `${stickyConfig.marginLeft}px`;
                if (Number.isFinite(Number(stickyConfig.marginLeft)) || Number.isFinite(Number(stickyConfig.marginRight))) out.width = 'auto';
            }
        }

        // Border scope: full = canvas (default), inner = apply to inner wrapper
        const borderScope = s.borderScope === 'inner' ? 'inner' : 'full';

        // Border style / color / width
        if (borderScope === 'full') {
            if (typeof s.borderStyle === 'string' && s.borderStyle && s.borderStyle !== 'default') {
                out.borderStyle = s.borderStyle;
            }
            if (typeof s.borderColor === 'string' && s.borderColor.trim()) {
                out.borderColor = s.borderColor;
            }
            const bwTop = Number.isFinite(Number(s.borderWidthTop)) ? Number(s.borderWidthTop) : undefined;
            const bwRight = Number.isFinite(Number(s.borderWidthRight)) ? Number(s.borderWidthRight) : undefined;
            const bwBottom = Number.isFinite(Number(s.borderWidthBottom)) ? Number(s.borderWidthBottom) : undefined;
            const bwLeft = Number.isFinite(Number(s.borderWidthLeft)) ? Number(s.borderWidthLeft) : undefined;
            if (bwTop !== undefined && bwTop >= 0) out.borderTopWidth = `${bwTop}px`;
            if (bwRight !== undefined && bwRight >= 0) out.borderRightWidth = `${bwRight}px`;
            if (bwBottom !== undefined && bwBottom >= 0) out.borderBottomWidth = `${bwBottom}px`;
            if (bwLeft !== undefined && bwLeft >= 0) out.borderLeftWidth = `${bwLeft}px`;

            // Border radius
            const borderRadius = Number.isFinite(Number(s.borderRadius)) ? Number(s.borderRadius) : undefined;
            const brTop = Number.isFinite(Number(s.borderRadiusTop)) ? Number(s.borderRadiusTop) : borderRadius;
            const brRight = Number.isFinite(Number(s.borderRadiusRight)) ? Number(s.borderRadiusRight) : borderRadius;
            const brBottom = Number.isFinite(Number(s.borderRadiusBottom)) ? Number(s.borderRadiusBottom) : borderRadius;
            const brLeft = Number.isFinite(Number(s.borderRadiusLeft)) ? Number(s.borderRadiusLeft) : borderRadius;
            if (brTop !== undefined && brTop >= 0) out.borderTopLeftRadius = `${brTop}px`;
            if (brRight !== undefined && brRight >= 0) out.borderTopRightRadius = `${brRight}px`;
            if (brBottom !== undefined && brBottom >= 0) out.borderBottomRightRadius = `${brBottom}px`;
            if (brLeft !== undefined && brLeft >= 0) out.borderBottomLeftRadius = `${brLeft}px`;
        }

        const heightCss = resolveUnit(pickResponsive(s.height, s.heightTablet, s.heightMobile));
        if (heightCss && heightCss !== 'auto' && !/^0(?:\.0+)?(px|em|rem|%|vw|vh)?$/i.test(heightCss)) {
            out.height = heightCss;
            out.display = 'flex';
            out.flexDirection = 'column';
        }

        if (s.stackedSticky === true) {
            const rawZ = Number(s.stackedZIndex);
            const zIndex = Number.isFinite(rawZ) && rawZ > 0 ? Math.max(0, Math.round(rawZ)) : sectionIndex + 1;
            out.position = 'sticky';
            out.top = 0;
            out.minHeight = heightCss && heightCss !== 'auto' ? heightCss : '100vh';
            out.height = 'auto';
            out.zIndex = zIndex;
            out.overflow = hasNavMenu ? 'visible' : 'hidden';
            out.marginTop = 0;
            out.marginBottom = 0;
            out.display = 'flex';
            out.flexDirection = 'column';
        }

        const tx = Number.isFinite(Number(s.transformTranslateX)) ? Number(s.transformTranslateX) : 0;
        const ty = Number.isFinite(Number(s.transformTranslateY)) ? Number(s.transformTranslateY) : 0;
        const rot = Number.isFinite(Number(s.transformRotate)) ? Number(s.transformRotate) : 0;
        const scale = Number.isFinite(Number(s.transformScale)) ? Number(s.transformScale) : 1;
        const origin = typeof s.transformOrigin === 'string' ? s.transformOrigin : 'center center';

        if (tx !== 0 || ty !== 0 || rot !== 0 || scale !== 1) {
            out.transform = transformString(tx, ty, rot, scale);
            out.transformOrigin = origin;
        }

        const preset = typeof s.animationPreset === 'string' ? s.animationPreset : '';
        if (preset === 'custom') {
            const dur = Number.isFinite(Number(s.animationDuration)) ? Number(s.animationDuration) : 0.8;
            const delay = Number.isFinite(Number(s.animationDelay)) ? Number(s.animationDelay) : 0;
            const easing = typeof s.animationEasing === 'string' ? s.animationEasing : 'ease';
            let iter = s.animationIterationCount === 'infinite' ? 'infinite' : (Number.isFinite(Number(s.animationIterationCount)) ? String(Math.max(1, Math.round(Number(s.animationIterationCount)))) : '1');
            const fill = typeof s.animationFillMode === 'string' ? s.animationFillMode : 'both';
            const pingPong = s.animationPingPong === true;
            if (pingPong && iter === '1') {
                iter = '2';
            }

            out.animationName = `mp-custom-${safeKeyframeId(sectionId)}`;
            out.animationDuration = `${dur}s`;
            out.animationDelay = `${delay}s`;
            out.animationTimingFunction = easing;
            out.animationIterationCount = iter;
            out.animationFillMode = fill;
            if (pingPong) {
                out.animationDirection = 'alternate';
            }
            out.willChange = out.transform ? 'transform, opacity' : 'opacity, transform';

            if (origin) {
                out.transformOrigin = origin;
            }
        }

        if (preset && preset !== 'custom') {
            const dur = Number.isFinite(Number(s.animationDuration)) ? Number(s.animationDuration) : 0.8;
            const delay = Number.isFinite(Number(s.animationDelay)) ? Number(s.animationDelay) : 0;
            const easing = typeof s.animationEasing === 'string' ? s.animationEasing : 'ease';
            const iter = s.animationIterationCount === 'infinite' ? 'infinite' : (Number.isFinite(Number(s.animationIterationCount)) ? String(Math.max(1, Math.round(Number(s.animationIterationCount)))) : '1');
            const fill = typeof s.animationFillMode === 'string' ? s.animationFillMode : 'both';

            out.animationName = preset;
            out.animationDuration = `${dur}s`;
            out.animationDelay = `${delay}s`;
            out.animationTimingFunction = easing;
            out.animationIterationCount = iter;
            out.animationFillMode = fill;
            out.willChange = out.transform ? 'transform, opacity' : 'opacity, transform';
        }

        return out;
    };

    const widgetCanvasStyle = (props, widgetId) => {
        const p = props && typeof props === 'object' ? props : {};
        const out = {};

        const tx = Number.isFinite(Number(p.transformTranslateX)) ? Number(p.transformTranslateX) : 0;
        const ty = Number.isFinite(Number(p.transformTranslateY)) ? Number(p.transformTranslateY) : 0;
        const rot = Number.isFinite(Number(p.transformRotate)) ? Number(p.transformRotate) : 0;
        const scale = Number.isFinite(Number(p.transformScale)) ? Number(p.transformScale) : 1;
        const origin = typeof p.transformOrigin === 'string' ? p.transformOrigin : 'center center';

        if (tx !== 0 || ty !== 0 || rot !== 0 || scale !== 1) {
            out.transform = transformString(tx, ty, rot, scale);
            out.transformOrigin = origin;
        }

        const preset = typeof p.animationPreset === 'string' ? p.animationPreset : '';
        if (preset === 'custom') {
            const dur = Number.isFinite(Number(p.animationDuration)) ? Number(p.animationDuration) : 0.8;
            const delay = Number.isFinite(Number(p.animationDelay)) ? Number(p.animationDelay) : 0;
            const easing = typeof p.animationEasing === 'string' ? p.animationEasing : 'ease';
            let iter =
                p.animationIterationCount === 'infinite'
                    ? 'infinite'
                    : (Number.isFinite(Number(p.animationIterationCount))
                        ? String(Math.max(1, Math.round(Number(p.animationIterationCount))))
                        : '1');
            const fill = typeof p.animationFillMode === 'string' ? p.animationFillMode : 'both';
            const pingPong = p.animationPingPong === true;
            if (pingPong && iter === '1') {
                iter = '2';
            }

            out.animationName = `mp-custom-${safeKeyframeId(widgetId)}`;
            out.animationDuration = `${dur}s`;
            out.animationDelay = `${delay}s`;
            out.animationTimingFunction = easing;
            out.animationIterationCount = iter;
            out.animationFillMode = fill;
            if (pingPong) {
                out.animationDirection = 'alternate';
            }
            out.willChange = out.transform ? 'transform, opacity' : 'opacity, transform';

            if (origin) {
                out.transformOrigin = origin;
            }
        }

        if (preset && preset !== 'custom') {
            const dur = Number.isFinite(Number(p.animationDuration)) ? Number(p.animationDuration) : 0.8;
            const delay = Number.isFinite(Number(p.animationDelay)) ? Number(p.animationDelay) : 0;
            const easing = typeof p.animationEasing === 'string' ? p.animationEasing : 'ease';
            const iter =
                p.animationIterationCount === 'infinite'
                    ? 'infinite'
                    : (Number.isFinite(Number(p.animationIterationCount))
                        ? String(Math.max(1, Math.round(Number(p.animationIterationCount))))
                        : '1');
            const fill = typeof p.animationFillMode === 'string' ? p.animationFillMode : 'both';

            out.animationName = preset;
            out.animationDuration = `${dur}s`;
            out.animationDelay = `${delay}s`;
            out.animationTimingFunction = easing;
            out.animationIterationCount = iter;
            out.animationFillMode = fill;
            out.willChange = out.transform ? 'transform, opacity' : 'opacity, transform';
        }

        return out;
    };

    const pageWrapperStyle = (style) => {
        const s = style && typeof style === 'object' ? style : {};
        const out = {};

        if (Number.isFinite(Number(s.marginTop))) out.marginTop = `${s.marginTop}px`;
        if (Number.isFinite(Number(s.marginRight))) out.marginRight = `${s.marginRight}px`;
        if (Number.isFinite(Number(s.marginBottom))) out.marginBottom = `${s.marginBottom}px`;
        if (Number.isFinite(Number(s.marginLeft))) out.marginLeft = `${s.marginLeft}px`;
        if (Number.isFinite(Number(s.paddingTop))) out.paddingTop = `${s.paddingTop}px`;
        if (Number.isFinite(Number(s.paddingRight))) out.paddingRight = `${s.paddingRight}px`;
        if (Number.isFinite(Number(s.paddingBottom))) out.paddingBottom = `${s.paddingBottom}px`;
        if (Number.isFinite(Number(s.paddingLeft))) out.paddingLeft = `${s.paddingLeft}px`;

        const rawBgType = typeof s.backgroundType === 'string' ? s.backgroundType : 'color';
        const bgType = rawBgType === 'classic' ? 'color' : rawBgType;

        if (bgType === 'gradient') {
            const from = typeof s.gradientFrom === 'string' && s.gradientFrom ? s.gradientFrom : '#111827';
            const to = typeof s.gradientTo === 'string' && s.gradientTo ? s.gradientTo : '#3b82f6';
            const angle = Number.isFinite(Number(s.gradientAngle)) ? Number(s.gradientAngle) : 135;
            const type = (s.gradientType === 'radial' || s.gradientType === 'linear') ? s.gradientType : 'linear';
            const position = typeof s.gradientPosition === 'string' && s.gradientPosition ? s.gradientPosition : 'top center';

            if (type === 'radial') {
                out.backgroundImage = `radial-gradient(at ${position}, ${from} 0%, ${to} 100%)`;
            } else {
                out.backgroundImage = `linear-gradient(${angle}deg, ${from} 0%, ${to} 100%)`;
            }
        } else if (bgType === 'image') {
            const url = s.backgroundImage && typeof s.backgroundImage === 'object' ? String(s.backgroundImage.url || '') : '';
            if (url) {
                out.backgroundImage = `url(${url})`;
                out.backgroundSize = typeof s.backgroundSize === 'string' && s.backgroundSize ? s.backgroundSize : 'cover';
                out.backgroundPosition = typeof s.backgroundPosition === 'string' && s.backgroundPosition ? s.backgroundPosition : 'center';
                out.backgroundRepeat = typeof s.backgroundRepeat === 'string' && s.backgroundRepeat ? s.backgroundRepeat : 'no-repeat';
            }
        } else if (bgType === 'color') {
            const color = typeof s.backgroundColor === 'string' && s.backgroundColor ? s.backgroundColor : '';
            if (color) {
                out.backgroundColor = color;
            }
        }

        return out;
    };

    const pageBackgroundStyle = (style) => {
        const s = style && typeof style === 'object' ? style : {};
        const out = {};

        const bgType = typeof s.backgroundType === 'string' ? s.backgroundType : 'color';

        if (bgType === 'gradient') {
            const from = typeof s.gradientFrom === 'string' && s.gradientFrom ? s.gradientFrom : '#111827';
            const to = typeof s.gradientTo === 'string' && s.gradientTo ? s.gradientTo : '#3b82f6';
            const angle = Number.isFinite(Number(s.gradientAngle)) ? Number(s.gradientAngle) : 135;
            const type = (s.gradientType === 'radial' || s.gradientType === 'linear') ? s.gradientType : 'linear';
            const position = typeof s.gradientPosition === 'string' && s.gradientPosition ? s.gradientPosition : 'top center';
            if (type === 'radial') {
                out.backgroundImage = `radial-gradient(at ${position}, ${from} 0%, ${to} 100%)`;
            } else {
                out.backgroundImage = `linear-gradient(${angle}deg, ${from} 0%, ${to} 100%)`;
            }
        } else if (bgType === 'image') {
            const url = s.backgroundImage && typeof s.backgroundImage === 'object' ? String(s.backgroundImage.url || '') : '';
            if (url) {
                out.backgroundImage = `url(${url})`;
                out.backgroundSize = typeof s.backgroundSize === 'string' && s.backgroundSize ? s.backgroundSize : 'cover';
                out.backgroundPosition = typeof s.backgroundPosition === 'string' && s.backgroundPosition ? s.backgroundPosition : 'center';
                out.backgroundRepeat = typeof s.backgroundRepeat === 'string' && s.backgroundRepeat ? s.backgroundRepeat : 'no-repeat';
            }
        } else if (bgType === 'color') {
            const color = typeof s.backgroundColor === 'string' && s.backgroundColor ? s.backgroundColor : '';
            if (color) {
                out.backgroundColor = color;
            }
        }

        return out;
    };

    const pageContentStyle = (style) => {
        const s = style && typeof style === 'object' ? style : {};
        const out = {};

        if (Number.isFinite(Number(s.marginTop))) out.marginTop = `${s.marginTop}px`;
        if (Number.isFinite(Number(s.marginRight))) out.marginRight = `${s.marginRight}px`;
        if (Number.isFinite(Number(s.marginBottom))) out.marginBottom = `${s.marginBottom}px`;
        if (Number.isFinite(Number(s.marginLeft))) out.marginLeft = `${s.marginLeft}px`;
        if (Number.isFinite(Number(s.paddingTop))) out.paddingTop = `${s.paddingTop}px`;
        if (Number.isFinite(Number(s.paddingRight))) out.paddingRight = `${s.paddingRight}px`;
        if (Number.isFinite(Number(s.paddingBottom))) out.paddingBottom = `${s.paddingBottom}px`;
        if (Number.isFinite(Number(s.paddingLeft))) out.paddingLeft = `${s.paddingLeft}px`;

        const maxWidthType = s.pageMaxWidthType === 'boxed' ? 'boxed' : 'full';
        if (maxWidthType === 'boxed') {
            const boxedWidth = resolveUnit(s.pageBoxedWidth, 'px') || '1152px';
            out.maxWidth = boxedWidth;
            out.marginLeft = 'auto';
            out.marginRight = 'auto';
        }

        out.position = 'relative';

        return out;
    };

    const sectionInnerStyle = (style, stickyConfig = null, hasNavMenu = false) => {
        const s = style && typeof style === 'object' ? style : {};
        const out = {};
        const maxWidthType = s.maxWidthType || (s.maxWidth === 'full' ? 'full' : 'boxed');
        if (maxWidthType === 'full') {
            out.width = '100%';
            const innerMaxWidth = resolveUnit(pickResponsive(s.innerMaxWidth, s.innerMaxWidthTablet, s.innerMaxWidthMobile), 'px');
            out.maxWidth = innerMaxWidth || '100%';
        } else {
            const boxedWidth = resolveUnit(pickResponsive(s.boxedWidth, s.boxedWidthTablet, s.boxedWidthMobile), 'px') || (s.maxWidth === '7xl' ? '1280px' : '1152px');
            out.width = '100%';
            out.maxWidth = boxedWidth;
        }

        const rPick = (key) => pickResponsive(s[key], s[`${key}Tablet`], s[`${key}Mobile`]);
        out.paddingTop = Number.isFinite(Number(rPick('paddingTop'))) ? Number(rPick('paddingTop')) : (Number.isFinite(Number(rPick('paddingY'))) ? Number(rPick('paddingY')) : 48);
        out.paddingRight = Number.isFinite(Number(rPick('paddingRight'))) ? Number(rPick('paddingRight')) : (Number.isFinite(Number(rPick('paddingX'))) ? Number(rPick('paddingX')) : 0);
        out.paddingBottom = Number.isFinite(Number(rPick('paddingBottom'))) ? Number(rPick('paddingBottom')) : (Number.isFinite(Number(rPick('paddingY'))) ? Number(rPick('paddingY')) : 48);
        out.paddingLeft = Number.isFinite(Number(rPick('paddingLeft'))) ? Number(rPick('paddingLeft')) : (Number.isFinite(Number(rPick('paddingX'))) ? Number(rPick('paddingX')) : 0);

        const headerHeight = Number.isFinite(Number(s.headerHeight)) ? Number(s.headerHeight) : undefined;
        if (maxWidthType !== 'full' && headerHeight !== undefined && headerHeight > 0) {
            out.height = `${headerHeight}px`;
            out.display = 'flex';
            out.flexDirection = 'column';
            out.alignItems = 'center';
            out.overflow = hasNavMenu ? 'visible' : 'hidden';
        }

        // Border scope: inner means borders live on the inner wrapper instead of the full-width canvas
        const borderScope = s.borderScope === 'inner' ? 'inner' : 'full';
        if (borderScope === 'inner') {
            if (typeof s.borderStyle === 'string' && s.borderStyle && s.borderStyle !== 'default') {
                out.borderStyle = s.borderStyle;
            }
            if (typeof s.borderColor === 'string' && s.borderColor.trim()) {
                out.borderColor = s.borderColor;
            }
            const bwTop = Number.isFinite(Number(s.borderWidthTop)) ? Number(s.borderWidthTop) : undefined;
            const bwRight = Number.isFinite(Number(s.borderWidthRight)) ? Number(s.borderWidthRight) : undefined;
            const bwBottom = Number.isFinite(Number(s.borderWidthBottom)) ? Number(s.borderWidthBottom) : undefined;
            const bwLeft = Number.isFinite(Number(s.borderWidthLeft)) ? Number(s.borderWidthLeft) : undefined;
            if (bwTop !== undefined && bwTop >= 0) out.borderTopWidth = `${bwTop}px`;
            if (bwRight !== undefined && bwRight >= 0) out.borderRightWidth = `${bwRight}px`;
            if (bwBottom !== undefined && bwBottom >= 0) out.borderBottomWidth = `${bwBottom}px`;
            if (bwLeft !== undefined && bwLeft >= 0) out.borderLeftWidth = `${bwLeft}px`;

            const borderRadius = Number.isFinite(Number(s.borderRadius)) ? Number(s.borderRadius) : undefined;
            const brTop = Number.isFinite(Number(s.borderRadiusTop)) ? Number(s.borderRadiusTop) : borderRadius;
            const brRight = Number.isFinite(Number(s.borderRadiusRight)) ? Number(s.borderRadiusRight) : borderRadius;
            const brBottom = Number.isFinite(Number(s.borderRadiusBottom)) ? Number(s.borderRadiusBottom) : borderRadius;
            const brLeft = Number.isFinite(Number(s.borderRadiusLeft)) ? Number(s.borderRadiusLeft) : borderRadius;
            if (brTop !== undefined && brTop >= 0) out.borderTopLeftRadius = `${brTop}px`;
            if (brRight !== undefined && brRight >= 0) out.borderTopRightRadius = `${brRight}px`;
            if (brBottom !== undefined && brBottom >= 0) out.borderBottomRightRadius = `${brBottom}px`;
            if (brLeft !== undefined && brLeft >= 0) out.borderBottomLeftRadius = `${brLeft}px`;
        }

        // For full-width sections, background is applied to outer canvas (full viewport); skip it here.
        if (maxWidthType === 'full') {
            return out;
        }

        const rawBgType = typeof s.backgroundType === 'string' ? s.backgroundType : 'color';
        const bgType = rawBgType === 'classic' ? 'color' : rawBgType;
        if (bgType === 'gradient') {
            const from = typeof s.gradientFrom === 'string' ? s.gradientFrom : '#111827';
            const to = typeof s.gradientTo === 'string' ? s.gradientTo : '#3b82f6';
            const ang = Number.isFinite(Number(s.gradientAngle)) ? Number(s.gradientAngle) : 135;
            const type = (s.gradientType === 'radial' || s.gradientType === 'linear') ? s.gradientType : 'linear';
            const position = typeof s.gradientPosition === 'string' && s.gradientPosition ? s.gradientPosition : 'top center';
            if (type === 'radial') {
                out.backgroundImage = `radial-gradient(at ${position}, ${from} 0%, ${to} 100%)`;
            } else {
                out.backgroundImage = `linear-gradient(${ang}deg, ${from} 0%, ${to} 100%)`;
            }
        }
        if (bgType === 'image') {
            const url = s.backgroundImage && typeof s.backgroundImage === 'object' ? String(s.backgroundImage.url || '') : '';
            if (url) {
                out.backgroundImage = `url(${url})`;
                out.backgroundSize = typeof s.backgroundSize === 'string' && s.backgroundSize ? s.backgroundSize : 'cover';
                out.backgroundPosition = typeof s.backgroundPosition === 'string' && s.backgroundPosition ? s.backgroundPosition : 'center';
                out.backgroundRepeat = typeof s.backgroundRepeat === 'string' && s.backgroundRepeat ? s.backgroundRepeat : 'no-repeat';
            }
        }
        if (bgType === 'color') {
            const color = typeof s.backgroundColor === 'string' ? s.backgroundColor : '#ffffff';
            out.backgroundColor = color;
        }
        if (s.darkMode) {
            out.backgroundColor = '#000000';
            delete out.backgroundImage;
        }
        const backdropBlur = Number.isFinite(Number(s.backdropBlur)) ? Number(s.backdropBlur) : 0;
        if (backdropBlur > 0) {
            out.backdropFilter = `blur(${backdropBlur}px)`;
            out.WebkitBackdropFilter = `blur(${backdropBlur}px)`;
        }

        // Apply sticky header styles directly to the boxed section inner so the
        // sticky background overrides the base background on the same element.
        // Keep width:100% always and only transition max-width so the browser
        // can interpolate between two concrete length values smoothly.
        if (stickyConfig && maxWidthType !== 'full') {
            out.position = 'sticky';
            out.top = 0;
            out.zIndex = 50;
            out.marginLeft = 'auto';
            out.marginRight = 'auto';
            out.transition = 'height 0.4s ease, max-width 0.4s ease, background-color 0.4s ease, background-image 0.4s ease, border 0.4s ease, border-radius 0.4s ease, color 0.4s ease';
            if (stickyConfig.active) {
                const stickyHeight = stickyConfig.height ? resolveUnit(stickyConfig.height) : '';
                if (stickyHeight) {
                    out.height = stickyHeight;
                }
                const stickyWidth = stickyConfig.width ? resolveUnit(stickyConfig.width) : '';
                if (stickyWidth) {
                    out.maxWidth = stickyWidth;
                }
                if (stickyConfig.backgroundType === 'gradient') {
                    const from = stickyConfig.gradientFrom || '#111827';
                    const to = stickyConfig.gradientTo || '#3b82f6';
                    const angle = Number.isFinite(Number(stickyConfig.gradientAngle)) ? Number(stickyConfig.gradientAngle) : 135;
                    const type = (stickyConfig.gradientType === 'radial' || stickyConfig.gradientType === 'linear') ? stickyConfig.gradientType : 'linear';
                    const position = stickyConfig.gradientPosition || 'top center';
                    if (type === 'radial') {
                        out.backgroundImage = `radial-gradient(at ${position}, ${from} 0%, ${to} 100%)`;
                    } else {
                        out.backgroundImage = `linear-gradient(${angle}deg, ${from} 0%, ${to} 100%)`;
                    }
                    out.backgroundColor = undefined;
                } else if (stickyConfig.backgroundColor) {
                    out.backgroundColor = stickyConfig.backgroundColor;
                    out.backgroundImage = undefined;
                }
                if (Number.isFinite(Number(stickyConfig.backdropBlur)) && Number(stickyConfig.backdropBlur) >= 0) {
                    out.backdropFilter = `blur(${stickyConfig.backdropBlur}px)`;
                    out.WebkitBackdropFilter = `blur(${stickyConfig.backdropBlur}px)`;
                }
                if (typeof stickyConfig.borderStyle === 'string' && stickyConfig.borderStyle && stickyConfig.borderStyle !== 'default') {
                    out.borderStyle = stickyConfig.borderStyle;
                    if (typeof stickyConfig.borderColor === 'string' && stickyConfig.borderColor) {
                        out.borderColor = stickyConfig.borderColor;
                    }
                    if (Number.isFinite(Number(stickyConfig.borderWidthTop))) out.borderTopWidth = `${stickyConfig.borderWidthTop}px`;
                    if (Number.isFinite(Number(stickyConfig.borderWidthRight))) out.borderRightWidth = `${stickyConfig.borderWidthRight}px`;
                    if (Number.isFinite(Number(stickyConfig.borderWidthBottom))) out.borderBottomWidth = `${stickyConfig.borderWidthBottom}px`;
                    if (Number.isFinite(Number(stickyConfig.borderWidthLeft))) out.borderLeftWidth = `${stickyConfig.borderWidthLeft}px`;
                }
                if (Number.isFinite(Number(stickyConfig.borderRadiusTop))) out.borderTopLeftRadius = `${stickyConfig.borderRadiusTop}px`;
                if (Number.isFinite(Number(stickyConfig.borderRadiusRight))) out.borderTopRightRadius = `${stickyConfig.borderRadiusRight}px`;
                if (Number.isFinite(Number(stickyConfig.borderRadiusBottom))) out.borderBottomRightRadius = `${stickyConfig.borderRadiusBottom}px`;
                if (Number.isFinite(Number(stickyConfig.borderRadiusLeft))) out.borderBottomLeftRadius = `${stickyConfig.borderRadiusLeft}px`;
                if (typeof stickyConfig.textColor === 'string' && stickyConfig.textColor) {
                    out.color = stickyConfig.textColor;
                }
                if (typeof stickyConfig.linkColor === 'string' && stickyConfig.linkColor) {
                    out['--mp-sticky-link-color'] = stickyConfig.linkColor;
                }
                if (Number.isFinite(Number(stickyConfig.marginTop))) out.top = `${stickyConfig.marginTop}px`;
                if (Number.isFinite(Number(stickyConfig.marginRight))) out.marginRight = `${stickyConfig.marginRight}px`;
                if (Number.isFinite(Number(stickyConfig.marginBottom))) out.marginBottom = `${stickyConfig.marginBottom}px`;
                if (Number.isFinite(Number(stickyConfig.marginLeft))) out.marginLeft = `${stickyConfig.marginLeft}px`;
                if (Number.isFinite(Number(stickyConfig.marginLeft)) || Number.isFinite(Number(stickyConfig.marginRight))) out.width = 'auto';
            }
        }

        return out;
    };

    const gridGapStyle = (style) => {
        const s = style && typeof style === 'object' ? style : {};
        const fallback = Number.isFinite(Number(s.gap)) ? Number(s.gap) : 24;
        const gapX = Number.isFinite(Number(s.gapX)) ? Number(s.gapX) : fallback;
        const gapY = Number.isFinite(Number(s.gapY)) ? Number(s.gapY) : fallback;
        return { columnGap: `${gapX}px`, rowGap: `${gapY}px` };
    };

    const containerInnerStyle = (style) => {
        const s = style && typeof style === 'object' ? style : {};
        const out = {};

        const maxWidthType = s.maxWidthType || (s.maxWidth === 'full' ? 'full' : 'boxed');
        const isExplicitBoxed = s.maxWidthType === 'boxed';
        const isExplicitFull = s.maxWidthType === 'full';
        const boxedWidth = resolveUnit(pickResponsive(s.boxedWidth, s.boxedWidthTablet, s.boxedWidthMobile), 'px') || (isExplicitBoxed ? '600px' : null);
        const innerMaxWidth = resolveUnit(pickResponsive(s.innerMaxWidth, s.innerMaxWidthTablet, s.innerMaxWidthMobile), 'px') || (isExplicitFull ? '1152px' : null);
        out.width = '100%';
        if (maxWidthType === 'full') {
            out.maxWidth = innerMaxWidth !== null ? innerMaxWidth : '100%';
        } else {
            out.maxWidth = boxedWidth !== null ? boxedWidth : '100%';
        }

        const rPick = (key) => pickResponsive(s[key], s[`${key}Tablet`], s[`${key}Mobile`]);
        const rNum = (key) => (Number.isFinite(Number(rPick(key))) ? Number(rPick(key)) : undefined);

        const paddingTop = rNum('paddingTop') ?? rNum('paddingY') ?? 0;
        const paddingRight = rNum('paddingRight') ?? rNum('paddingX') ?? 0;
        const paddingBottom = rNum('paddingBottom') ?? rNum('paddingY') ?? 0;
        const paddingLeft = rNum('paddingLeft') ?? rNum('paddingX') ?? 0;
        out.paddingTop = `${paddingTop}px`;
        out.paddingRight = `${paddingRight}px`;
        out.paddingBottom = `${paddingBottom}px`;
        out.paddingLeft = `${paddingLeft}px`;

        const marginTop = rNum('marginTop') ?? rNum('marginY');
        const marginRight = rNum('marginRight') ?? rNum('marginX');
        const marginBottom = rNum('marginBottom') ?? rNum('marginY');
        const marginLeft = Number.isFinite(Number(s.marginLeft)) ? Number(s.marginLeft) : (Number.isFinite(Number(s.marginX)) ? Number(s.marginX) : undefined);
        if (marginTop !== undefined) out.marginTop = `${marginTop}px`;
        if (marginRight !== undefined) out.marginRight = `${marginRight}px`;
        if (marginBottom !== undefined) out.marginBottom = `${marginBottom}px`;
        if (marginLeft !== undefined) out.marginLeft = `${marginLeft}px`;

        if (typeof s.borderStyle === 'string' && s.borderStyle && s.borderStyle !== 'default') {
            out.borderStyle = s.borderStyle;
        }
        if (typeof s.borderColor === 'string' && s.borderColor.trim()) {
            out.borderColor = s.borderColor;
        }
        const bwTop = Number.isFinite(Number(s.borderWidthTop)) ? Number(s.borderWidthTop) : undefined;
        const bwRight = Number.isFinite(Number(s.borderWidthRight)) ? Number(s.borderWidthRight) : undefined;
        const bwBottom = Number.isFinite(Number(s.borderWidthBottom)) ? Number(s.borderWidthBottom) : undefined;
        const bwLeft = Number.isFinite(Number(s.borderWidthLeft)) ? Number(s.borderWidthLeft) : undefined;
        if (bwTop !== undefined && bwTop >= 0) out.borderTopWidth = `${bwTop}px`;
        if (bwRight !== undefined && bwRight >= 0) out.borderRightWidth = `${bwRight}px`;
        if (bwBottom !== undefined && bwBottom >= 0) out.borderBottomWidth = `${bwBottom}px`;
        if (bwLeft !== undefined && bwLeft >= 0) out.borderLeftWidth = `${bwLeft}px`;

        const brTop = Number.isFinite(Number(s.borderRadiusTop)) ? Number(s.borderRadiusTop) : undefined;
        const brRight = Number.isFinite(Number(s.borderRadiusRight)) ? Number(s.borderRadiusRight) : undefined;
        const brBottom = Number.isFinite(Number(s.borderRadiusBottom)) ? Number(s.borderRadiusBottom) : undefined;
        const brLeft = Number.isFinite(Number(s.borderRadiusLeft)) ? Number(s.borderRadiusLeft) : undefined;
        if (brTop !== undefined && brTop >= 0) out.borderTopLeftRadius = `${brTop}px`;
        if (brRight !== undefined && brRight >= 0) out.borderTopRightRadius = `${brRight}px`;
        if (brBottom !== undefined && brBottom >= 0) out.borderBottomRightRadius = `${brBottom}px`;
        if (brLeft !== undefined && brLeft >= 0) out.borderBottomLeftRadius = `${brLeft}px`;

        const rawBgType = typeof s.backgroundType === 'string' ? s.backgroundType : 'color';
        const bgType = rawBgType === 'classic' ? 'color' : rawBgType;
        if (bgType === 'gradient') {
            const from = typeof s.gradientFrom === 'string' ? s.gradientFrom : '#111827';
            const to = typeof s.gradientTo === 'string' ? s.gradientTo : '#3b82f6';
            const ang = Number.isFinite(Number(s.gradientAngle)) ? Number(s.gradientAngle) : 135;
            const type = (s.gradientType === 'radial' || s.gradientType === 'linear') ? s.gradientType : 'linear';
            const position = typeof s.gradientPosition === 'string' && s.gradientPosition ? s.gradientPosition : 'top center';
            if (type === 'radial') {
                out.backgroundImage = `radial-gradient(at ${position}, ${from} 0%, ${to} 100%)`;
            } else {
                out.backgroundImage = `linear-gradient(${ang}deg, ${from} 0%, ${to} 100%)`;
            }
        } else if (bgType === 'image') {
            const url = s.backgroundImage && typeof s.backgroundImage === 'object' ? String(s.backgroundImage.url || '') : '';
            if (url) {
                out.backgroundImage = `url(${url})`;
                out.backgroundSize = typeof s.backgroundSize === 'string' && s.backgroundSize ? s.backgroundSize : 'cover';
                out.backgroundPosition = typeof s.backgroundPosition === 'string' && s.backgroundPosition ? s.backgroundPosition : 'center';
                out.backgroundRepeat = typeof s.backgroundRepeat === 'string' && s.backgroundRepeat ? s.backgroundRepeat : 'no-repeat';
            }
        } else {
            const color = typeof s.backgroundColor === 'string' && s.backgroundColor.trim() ? s.backgroundColor : undefined;
            if (color) out.backgroundColor = color;
        }

        const heightCss = resolveUnit(pickResponsive(s.height, s.heightTablet, s.heightMobile));
        if (heightCss && heightCss !== 'auto' && !/^0(?:\.0+)?(px|em|rem|%|vw|vh)?$/i.test(heightCss)) out.height = heightCss;

        if (out.maxWidth !== '100%') {
            if (marginLeft === undefined || marginLeft === 0 || marginLeft === null) out.marginLeft = 'auto';
            if (marginRight === undefined || marginRight === 0 || marginRight === null) out.marginRight = 'auto';
        }

        return out;
    };

    const findWidgetDeep = (widgets, widgetId) => {
        const list = Array.isArray(widgets) ? widgets : [];
        for (const w of list) {
            if (!w || typeof w !== 'object') continue;
            if (w.id === widgetId) return w;
            if (w.type === 'grid') {
                const props = w.props && typeof w.props === 'object' ? w.props : {};
                const cols = Array.isArray(props.columns) ? props.columns : [];
                for (const gc of cols) {
                    if (!gc || typeof gc !== 'object') continue;
                    const hit = findWidgetDeep(gc.widgets, widgetId);
                    if (hit) return hit;
                }
            }
            if (w.type === 'tabs') {
                const props = w.props && typeof w.props === 'object' ? w.props : {};
                const items = Array.isArray(props.items) ? props.items : [];
                for (const it of items) {
                    if (!it || typeof it !== 'object') continue;
                    const hit = findWidgetDeep(it.widgets, widgetId);
                    if (hit) return hit;
                }
            }
        }
        return null;
    };

    const removeWidgetDeep = (widgets, targetId) => {
        const list = Array.isArray(widgets) ? widgets : [];
        let removed = false;
        const next = [];
        for (const w of list) {
            if (!w) continue;
            if (w.id === targetId) { removed = true; continue; }
            const props = w.props && typeof w.props === 'object' ? w.props : null;
            if (!removed && w.type === 'grid' && props && Array.isArray(props.columns)) {
                let colsChanged = false;
                const nextCols = props.columns.map((gc) => {
                    if (removed || !gc || typeof gc !== 'object') return gc;
                    const res = removeWidgetDeep(Array.isArray(gc.widgets) ? gc.widgets : [], targetId);
                    if (res.removed) { colsChanged = true; return { ...gc, widgets: res.widgets }; }
                    return gc;
                });
                if (colsChanged) { removed = true; next.push({ ...w, props: { ...props, columns: nextCols } }); continue; }
            }
            if (!removed && w.type === 'tabs' && props && Array.isArray(props.items)) {
                let itemsChanged = false;
                const nextItems = props.items.map((it) => {
                    if (removed || !it || typeof it !== 'object') return it;
                    const res = removeWidgetDeep(Array.isArray(it.widgets) ? it.widgets : [], targetId);
                    if (res.removed) { itemsChanged = true; return { ...it, widgets: res.widgets }; }
                    return it;
                });
                if (itemsChanged) { removed = true; next.push({ ...w, props: { ...props, items: nextItems } }); continue; }
            }
            next.push(w);
        }
        return { widgets: next, removed };
    };

    const updateWidgetsDeep = (widgets, targetWidgetId, updater) => {
        const list = Array.isArray(widgets) ? widgets : [];
        let updated = false;
        let changed = false;

        const next = list.map((w) => {
            if (updated) return w;
            if (!w || typeof w !== 'object') return w;

            if (w.id === targetWidgetId) {
                updated = true;
                changed = true;
                return updater(w);
            }

            const props = w.props && typeof w.props === 'object' ? w.props : {};

            if (w.type === 'grid') {
                const cols = Array.isArray(props.columns) ? props.columns : [];
                let colsChanged = false;
                const nextCols = cols.map((gc) => {
                    if (updated) return gc;
                    if (!gc || typeof gc !== 'object') return gc;
                    const innerWidgets = Array.isArray(gc.widgets) ? gc.widgets : [];
                    const [nextInner, innerUpdated] = updateWidgetsDeep(innerWidgets, targetWidgetId, updater);
                    if (!innerUpdated) return gc;
                    updated = true;
                    colsChanged = true;
                    return { ...gc, widgets: nextInner };
                });
                if (!colsChanged) return w;
                changed = true;
                return { ...w, props: { ...props, columns: nextCols } };
            }

            if (w.type === 'tabs') {
                const items = Array.isArray(props.items) ? props.items : [];
                let itemsChanged = false;
                const nextItems = items.map((it) => {
                    if (updated) return it;
                    if (!it || typeof it !== 'object') return it;
                    const innerWidgets = Array.isArray(it.widgets) ? it.widgets : [];
                    const [nextInner, innerUpdated] = updateWidgetsDeep(innerWidgets, targetWidgetId, updater);
                    if (!innerUpdated) return it;
                    updated = true;
                    itemsChanged = true;
                    return { ...it, widgets: nextInner };
                });
                if (!itemsChanged) return w;
                changed = true;
                return { ...w, props: { ...props, items: nextItems } };
            }

            return w;
        });

        return [changed ? next : widgets, updated];
    };

    const selectedModel = useMemo(() => {
        if (!selected || !selected.kind || Object.keys(selected).length === 0) {
            return { kind: null };
        }

        const sec = page.sections.find((s) => s.id === selected.sectionId) || null;
        if (!sec) return { kind: 'none' };

        if (selected.kind === 'section') {
            return { kind: 'section', section: sec };
        }

        const columns = Array.isArray(sec.columns) ? sec.columns : [];
        const col = columns.find((c) => c && c.id === selected.columnId) || null;
        if (!col) return { kind: 'section', section: sec };

        if (selected.kind === 'column') {
            return { kind: 'column', section: sec, column: col };
        }

        if (selected.kind === 'grid_column' || selected.kind === 'grid_widget') {
            const gridWidget = findWidgetDeep(col.widgets, selected.gridWidgetId) || null;
            if (!gridWidget || gridWidget.type !== 'grid') {
                return { kind: 'column', section: sec, column: col };
            }

            const gridCols = gridWidget.props && Array.isArray(gridWidget.props.columns) ? gridWidget.props.columns : [];
            const gridCol = gridCols.find((gc) => gc && gc.id === selected.gridColumnId) || null;
            if (!gridCol) {
                return { kind: 'widget', section: sec, column: col, widget: gridWidget };
            }

            if (selected.kind === 'grid_column') {
                return { kind: 'grid_column', section: sec, column: col, gridWidget, gridColumn: gridCol };
            }

            const innerWidgets = Array.isArray(gridCol.widgets) ? gridCol.widgets : [];
            const innerWidget = innerWidgets.find((x) => x && x.id === selected.widgetId) || null;
            if (!innerWidget) {
                return { kind: 'grid_column', section: sec, column: col, gridWidget, gridColumn: gridCol };
            }

            return {
                kind: 'grid_widget',
                section: sec,
                column: col,
                gridWidget,
                gridColumn: gridCol,
                widget: innerWidget,
            };
        }

        if (selected.kind === 'tab_item' || selected.kind === 'tab_widget') {
            const tabWidget = findWidgetDeep(col.widgets, selected.tabWidgetId) || null;
            if (!tabWidget || tabWidget.type !== 'tabs') {
                return { kind: 'column', section: sec, column: col };
            }

            const props = tabWidget.props && typeof tabWidget.props === 'object' ? tabWidget.props : {};
            const items = Array.isArray(props.items) ? props.items : [];
            const tabItem = items.find((it) => it && it.id === selected.tabItemId) || null;
            if (!tabItem) {
                return { kind: 'widget', section: sec, column: col, widget: tabWidget };
            }

            if (selected.kind === 'tab_item') {
                return { kind: 'tab_item', section: sec, column: col, tabWidget, tabItem };
            }

            const innerWidgets = Array.isArray(tabItem.widgets) ? tabItem.widgets : [];
            const innerWidget = innerWidgets.find((x) => x && x.id === selected.widgetId) || null;
            if (!innerWidget) {
                return { kind: 'tab_item', section: sec, column: col, tabWidget, tabItem };
            }

            return {
                kind: 'tab_widget',
                section: sec,
                column: col,
                tabWidget,
                tabItem,
                widget: innerWidget,
            };
        }

        const w = col.widgets.find((x) => x.id === selected.widgetId) || null;
        if (!w) return { kind: 'column', section: sec, column: col };

        return { kind: 'widget', section: sec, column: col, widget: w };
    }, [page, selected]);

    useEffect(() => {
        if (selectedModel.kind && selectedModel.kind !== 'none') {
            if (restoringUiRef.current) {
                return;
            }
            if (skipAutoEditRef.current) {
                skipAutoEditRef.current = false;
                return;
            }
            setRailTab('edit');
        }
    }, [selectedModel.kind]);

    useEffect(() => {
        const key = selected
            ? [
                  selected.kind || '',
                  selected.sectionId || '',
                  selected.columnId || '',
                  selected.widgetId || '',
                  selected.gridWidgetId || '',
                  selected.gridColumnId || '',
                  selected.tabWidgetId || '',
                  selected.tabItemId || '',
              ].join('|')
            : '';

        if (key === lastSelectionKeyRef.current) return;
        lastSelectionKeyRef.current = key;

        if (restoringUiRef.current) {
            return;
        }

        if (selectedModel.kind === 'widget' || selectedModel.kind === 'grid_widget' || selectedModel.kind === 'tab_widget') {
            setEditTab('content');
        }

        if (selectedModel.kind === null && layoutType === 'header' && editTab === 'content') {
            setEditTab('advanced');
        }
    }, [selected, selectedModel.kind, selectedModel.widget, layoutType, editTab]);

    useEffect(() => {
        restoringUiRef.current = false;
    }, []);

    const handleContextMenu = (e, target) => {
        e.preventDefault();
        e.stopPropagation();

        setContextMenu({
            x: e.clientX,
            y: e.clientY,
            target,
            hasClipboard: clipboard !== null,
            hasStyleClipboard: styleClipboard !== null,
            hasTypoClipboard: typographyClipboard !== null,
        });
    };

    const handleContextMenuAction = (action) => {
        if (!contextMenu || !contextMenu.target) return;

        const target = contextMenu.target;

        switch (action) {
            case 'edit':
                setSelected(target);
                setRailTab('edit');
                setEditTab('content');
                break;

            case 'duplicate':
                if (target.kind === 'section') {
                    duplicateSection(target.sectionId);
                } else if (target.kind === 'widget') {
                    duplicateWidget(target.sectionId, target.columnId, target.widgetId);
                } else if (target.kind === 'grid_column') {
                    duplicateGridColumn(target.sectionId, target.columnId, target.gridWidgetId, target.gridColumnId);
                } else if (target.kind === 'grid_widget') {
                    duplicateGridWidget(
                        target.sectionId,
                        target.columnId,
                        target.gridWidgetId,
                        target.gridColumnId,
                        target.widgetId
                    );
                } else if (target.kind === 'tab_widget') {
                    duplicateTabWidget(target.sectionId, target.columnId, target.tabWidgetId, target.tabItemId, target.widgetId);
                } else if (target.kind === 'tab_item') {
                    duplicateTabItem(target.sectionId, target.columnId, target.tabWidgetId, target.tabItemId);
                }
                break;

            case 'copy':
                if (target.kind === 'section') {
                    const section = page.sections.find((s) => s.id === target.sectionId);
                    if (section) {
                        setClipboard({ kind: 'section', data: deepClone(section) });
                        setStyleClipboard({ kind: 'section', style: deepClone(section.style || {}) });
                    }
                } else if (target.kind === 'column') {
                    const section = page.sections.find((s) => s.id === target.sectionId);
                    const column = section?.columns.find((c) => c.id === target.columnId);
                    if (column) setClipboard({ kind: 'column', data: deepClone(column) });
                } else if (target.kind === 'grid_column') {
                    const section = page.sections.find((s) => s.id === target.sectionId);
                    const column = section?.columns.find((c) => c.id === target.columnId);
                    const gridWidget = column ? findWidgetDeep(column.widgets, target.gridWidgetId) : null;
                    const gridColumn = (Array.isArray(gridWidget?.props?.columns) ? gridWidget.props.columns : []).find((gc) => gc && gc.id === target.gridColumnId);
                    if (gridColumn) {
                        setClipboard({ kind: 'grid_column', data: deepClone(gridColumn) });
                        setStyleClipboard({ kind: 'grid_column', style: deepClone(gridColumn.style || {}) });
                    }
                } else if (target.kind === 'widget') {
                    const section = page.sections.find((s) => s.id === target.sectionId);
                    const column = section?.columns.find((c) => c.id === target.columnId);
                    const widget = column ? findWidgetDeep(column.widgets, target.widgetId) : null;
                    if (widget) {
                        setClipboard({ kind: 'widget', data: deepClone(widget) });
                        setStyleClipboard({ kind: 'widget', style: extractWidgetStyleProps(widget) });
                        setTypographyClipboard({ kind: 'widget', props: extractWidgetTypographyProps(widget) });
                    }
                } else if (target.kind === 'grid_widget') {
                    const section = page.sections.find((s) => s.id === target.sectionId);
                    const column = section?.columns.find((c) => c.id === target.columnId);
                    const gridWidget = column ? findWidgetDeep(column.widgets, target.gridWidgetId) : null;
                    const gridColumn = (Array.isArray(gridWidget?.props?.columns) ? gridWidget.props.columns : []).find((gc) => gc && gc.id === target.gridColumnId);
                    const widget = gridColumn?.widgets?.find((w) => w && w.id === target.widgetId);
                    if (widget) {
                        setClipboard({ kind: 'widget', data: deepClone(widget) });
                        setStyleClipboard({ kind: 'widget', style: extractWidgetStyleProps(widget) });
                        setTypographyClipboard({ kind: 'widget', props: extractWidgetTypographyProps(widget) });
                    }
                } else if (target.kind === 'tab_widget') {
                    const section = page.sections.find((s) => s.id === target.sectionId);
                    const column = section?.columns.find((c) => c.id === target.columnId);
                    const tabWidget = column ? findWidgetDeep(column.widgets, target.tabWidgetId) : null;
                    const props = tabWidget?.props || {};
                    const items = Array.isArray(props.items) ? props.items : [];
                    const tabItem = items.find((it) => it && it.id === target.tabItemId);
                    const widget = tabItem?.widgets?.find((w) => w && w.id === target.widgetId);
                    if (widget) {
                        setClipboard({ kind: 'widget', data: deepClone(widget) });
                        setStyleClipboard({ kind: 'widget', style: extractWidgetStyleProps(widget) });
                        setTypographyClipboard({ kind: 'widget', props: extractWidgetTypographyProps(widget) });
                    }
                }
                break;

            case 'paste':
                if (!clipboard) return;
                pasteAtTarget(target, clipboard);
                break;

            case 'paste-style':
                if (!styleClipboard) return;

                if ((target.kind === 'widget' || target.kind === 'grid_widget' || target.kind === 'tab_widget') && styleClipboard.kind === 'widget') {
                    updateWidget(target.sectionId, target.columnId, target.widgetId, {
                        props: { ...selectedModel.widget?.props, ...styleClipboard.style },
                    });
                } else if (target.kind === 'section' && styleClipboard.kind === 'section') {
                    updateSection(target.sectionId, { style: styleClipboard.style });
                }
                break;

            case 'paste-typography':
                if (!typographyClipboard || typographyClipboard.kind !== 'widget') return;

                if (target.kind === 'widget' || target.kind === 'grid_widget' || target.kind === 'tab_widget') {
                    const typographyProps = typographyClipboard.props && typeof typographyClipboard.props === 'object'
                        ? typographyClipboard.props
                        : {};
                    if (Object.keys(typographyProps).length === 0) return;
                    updateWidget(target.sectionId, target.columnId, target.widgetId, {
                        props: { ...typographyProps },
                    });
                }
                break;

            case 'reset-style':
                if (target.kind === 'widget' || target.kind === 'grid_widget' || target.kind === 'tab_widget') {
                    const widget = selectedModel.widget;
                    if (widget) {
                        const normalized = normalizeWidget({ ...widget, props: {} });
                        if (normalized) {
                            updateWidget(target.sectionId, target.columnId, target.widgetId, { props: normalized.props });
                        }
                    }
                } else if (target.kind === 'section') {
                    updateSection(target.sectionId, {
                        style: {
                            backgroundType: 'color',
                            backgroundColor: '#ffffff',
                            paddingY: 48,
                            gap: 24,
                            maxWidth: '6xl',
                        },
                    });
                }
                break;

            case 'export-json': {
                if (target.kind === 'section') {
                    const section = page.sections.find((s) => s.id === target.sectionId);
                    if (section) {
                        let json = '';
                        try {
                            json = JSON.stringify(section, null, 2);
                        } catch (e) {
                            json = '';
                        }
                        const base = safeFilenameBase(section.name || section.id || 'section');
                        downloadTextFile(`${base}.json`, json, 'application/json;charset=utf-8');
                    }
                }
                break;
            }

            case 'copy-json': {
                if (target.kind === 'section') {
                    const section = page.sections.find((s) => s.id === target.sectionId);
                    if (section) {
                        let json = '';
                        try {
                            json = JSON.stringify(section, null, 2);
                        } catch (e) {
                            json = '';
                        }

                        const notifyCopied = () => {
                            if (typeof window !== 'undefined' && typeof window.mailpurseToast === 'function') {
                                window.mailpurseToast({ type: 'success', title: 'Copied', message: 'Section JSON copied to clipboard.' });
                            }
                        };

                        if (navigator.clipboard && window.isSecureContext) {
                            navigator.clipboard.writeText(json).then(notifyCopied).catch(() => {});
                        } else {
                            try {
                                const ta = document.createElement('textarea');
                                ta.value = json;
                                ta.setAttribute('readonly', '');
                                ta.style.position = 'fixed';
                                ta.style.top = '-9999px';
                                document.body.appendChild(ta);
                                ta.select();
                                document.execCommand('copy');
                                ta.remove();
                                notifyCopied();
                            } catch (e) {}
                        }
                    }
                }
                break;
            }

            case 'delete':
                if (target.kind === 'section') {
                    deleteSection(target.sectionId);
                } else if (target.kind === 'column') {
                    deleteColumn(target.sectionId, target.columnId);
                } else if (target.kind === 'widget') {
                    deleteWidget(target.sectionId, target.columnId, target.widgetId);
                } else if (target.kind === 'grid_widget') {
                    removeGridInnerWidget(target.sectionId, target.columnId, target.gridWidgetId, target.gridColumnId, target.widgetId);
                } else if (target.kind === 'grid_column') {
                    deleteGridColumn(target.sectionId, target.columnId, target.gridWidgetId, target.gridColumnId);
                } else if (target.kind === 'tab_widget') {
                    removeTabInnerWidget(target.sectionId, target.columnId, target.tabWidgetId, target.tabItemId, target.widgetId);
                    setSelected({ kind: 'tab_item', sectionId: target.sectionId, columnId: target.columnId, tabWidgetId: target.tabWidgetId, tabItemId: target.tabItemId });
                } else if (target.kind === 'tab_item') {
                    removeTabItem(target.sectionId, target.columnId, target.tabWidgetId, target.tabItemId);
                }
                break;
        }
    };

    useEffect(() => {
        const handleKeyDown = (e) => {
            const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
            const cmdOrCtrl = isMac ? e.metaKey : e.ctrlKey;

            // Undo / Redo
            if (cmdOrCtrl && !e.altKey) {
                const comboKey = typeof e.key === 'string' ? e.key.toLowerCase() : '';
                const inEditable = e.target && (
                    e.target.tagName === 'INPUT'
                    || e.target.tagName === 'TEXTAREA'
                    || e.target.isContentEditable
                );

                if (comboKey === 'z') {
                    if (inEditable) return; // Let text fields use their native undo.
                    e.preventDefault();
                    if (e.shiftKey) {
                        redo();
                    } else {
                        undo();
                    }
                    return;
                }

                if (comboKey === 'y' && !e.shiftKey) {
                    if (inEditable) return;
                    e.preventDefault();
                    redo();
                    return;
                }
            }

            if (cmdOrCtrl && e.key === 'd') {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) {
                    return;
                }
                e.preventDefault();

                if (selectedModel.kind === 'section') {
                    duplicateSection(selectedModel.section.id);
                } else if (selectedModel.kind === 'widget') {
                    duplicateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id);
                } else if (selectedModel.kind === 'grid_widget') {
                    duplicateGridWidget(
                        selectedModel.section.id,
                        selectedModel.column.id,
                        selectedModel.gridWidget.id,
                        selectedModel.gridColumn.id,
                        selectedModel.widget.id
                    );
                } else if (selectedModel.kind === 'tab_widget') {
                    duplicateTabWidget(
                        selectedModel.section.id,
                        selectedModel.column.id,
                        selectedModel.tabWidget.id,
                        selectedModel.tabItem.id,
                        selectedModel.widget.id
                    );
                }
            }

            if (cmdOrCtrl && e.key === 'c' && !e.shiftKey) {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) {
                    return;
                }
                e.preventDefault();

                if (selectedModel.kind === 'section') {
                    setClipboard({ kind: 'section', data: deepClone(selectedModel.section) });
                    setStyleClipboard({ kind: 'section', style: deepClone(selectedModel.section.style || {}) });
                } else if (selectedModel.kind === 'column') {
                    setClipboard({ kind: 'column', data: deepClone(selectedModel.column) });
                } else if (selectedModel.kind === 'widget') {
                    setClipboard({ kind: 'widget', data: deepClone(selectedModel.widget) });
                    setStyleClipboard({ kind: 'widget', style: extractWidgetStyleProps(selectedModel.widget) });
                    setTypographyClipboard({ kind: 'widget', props: extractWidgetTypographyProps(selectedModel.widget) });
                } else if (selectedModel.kind === 'grid_widget' || selectedModel.kind === 'tab_widget') {
                    setClipboard({ kind: 'widget', data: deepClone(selectedModel.widget) });
                    setStyleClipboard({ kind: 'widget', style: extractWidgetStyleProps(selectedModel.widget) });
                    setTypographyClipboard({ kind: 'widget', props: extractWidgetTypographyProps(selectedModel.widget) });
                }
            }

            if (cmdOrCtrl && e.key === 'v' && !e.shiftKey && !e.altKey) {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) {
                    return;
                }
                e.preventDefault();

                if (!clipboard) return;

                pasteAtTarget(pasteTargetFromSelection(), clipboard);
            }

            if (cmdOrCtrl && e.altKey && e.key === 'v') {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) {
                    return;
                }
                e.preventDefault();

                if (!typographyClipboard || typographyClipboard.kind !== 'widget') return;

                if (selectedModel.kind === 'widget' || selectedModel.kind === 'grid_widget' || selectedModel.kind === 'tab_widget') {
                    const typographyProps = typographyClipboard.props && typeof typographyClipboard.props === 'object'
                        ? typographyClipboard.props
                        : {};
                    if (Object.keys(typographyProps).length === 0) return;
                    updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, {
                        props: { ...typographyProps },
                    });
                }
            }

            if (cmdOrCtrl && e.shiftKey && e.key === 'v') {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) {
                    return;
                }
                e.preventDefault();

                if (!styleClipboard) return;

                if ((selectedModel.kind === 'widget' || selectedModel.kind === 'grid_widget' || selectedModel.kind === 'tab_widget') && styleClipboard.kind === 'widget') {
                    updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, {
                        props: { ...selectedModel.widget.props, ...styleClipboard.style },
                    });
                } else if (selectedModel.kind === 'section' && styleClipboard.kind === 'section') {
                    updateSection(selectedModel.section.id, { style: styleClipboard.style });
                }
            }

            if ((e.key === 'Delete' || e.key === 'Backspace') && !e.metaKey && !e.ctrlKey) {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) {
                    return;
                }
                e.preventDefault();

                if (selectedModel.kind === 'widget') {
                    deleteWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id);
                } else if (selectedModel.kind === 'grid_widget') {
                    removeGridInnerWidget(
                        selectedModel.section.id,
                        selectedModel.column.id,
                        selectedModel.gridWidget.id,
                        selectedModel.gridColumn.id,
                        selectedModel.widget.id
                    );
                } else if (selectedModel.kind === 'tab_widget') {
                    removeTabInnerWidget(
                        selectedModel.section.id,
                        selectedModel.column.id,
                        selectedModel.tabWidget.id,
                        selectedModel.tabItem.id,
                        selectedModel.widget.id
                    );
                    setSelected({
                        kind: 'tab_item',
                        sectionId: selectedModel.section.id,
                        columnId: selectedModel.column.id,
                        tabWidgetId: selectedModel.tabWidget.id,
                        tabItemId: selectedModel.tabItem.id,
                    });
                }
            }
        };

        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [selectedModel, clipboard, styleClipboard, typographyClipboard]);

    const duplicateWidget = (sectionId, columnId, widgetId) => {
        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== sectionId) return s;
                return {
                    ...s,
                    columns: s.columns.map((c) => {
                        if (c.id !== columnId) return c;
                        const idx = c.widgets.findIndex((w) => w.id === widgetId);
                        if (idx === -1) return c;
                        const widget = c.widgets[idx];
                        const duplicate = reIdWidget(widget);
                        const newWidgets = [...c.widgets];
                        newWidgets.splice(idx + 1, 0, duplicate);
                        return { ...c, widgets: newWidgets };
                    }),
                };
            }),
        }));
    };

    const deleteWidget = (sectionId, columnId, widgetId) => {
        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== sectionId) return s;
                return {
                    ...s,
                    columns: s.columns.map((c) => {
                        if (c.id !== columnId) return c;
                        const res = removeWidgetDeep(Array.isArray(c.widgets) ? c.widgets : [], widgetId);
                        return res.removed ? { ...c, widgets: res.widgets } : c;
                    }),
                };
            }),
        }));

        setSelected((sel) => {
            if (!sel || sel.sectionId !== sectionId || sel.columnId !== columnId) return sel;
            if (sel.kind === 'widget' && sel.widgetId === widgetId) return { kind: 'column', sectionId, columnId };
            return sel;
        });
    };

    const reIdWidget = (widget) => {
        if (!widget || typeof widget !== 'object') return widget;
        const w = { ...deepClone(widget), id: uid('w') };

        if (w.type === 'grid') {
            const props = w.props && typeof w.props === 'object' ? w.props : {};
            const cols = Array.isArray(props.columns) ? props.columns : [];
            const nextCols = cols
                .filter(Boolean)
                .map((gc, idx) => {
                    const normalized = normalizeColumn({
                        ...gc,
                        id: uid('gcol'),
                        name: typeof gc?.name === 'string' ? gc.name : `Column ${idx + 1}`,
                        widgets: (Array.isArray(gc?.widgets) ? gc.widgets : []).map((iw) => reIdWidget(iw)),
                    });
                    return normalized || gc;
                })
                .filter(Boolean);

            const merged = { ...w, props: { ...props, columns: nextCols, count: nextCols.length || props.count } };
            return normalizeWidget(merged) || merged;
        }

        if (w.type === 'tabs') {
            const props = w.props && typeof w.props === 'object' ? w.props : {};
            const items = Array.isArray(props.items) ? props.items : [];
            const nextItems = items
                .filter(Boolean)
                .map((it) => ({
                    ...it,
                    id: uid('tab'),
                    widgets: (Array.isArray(it.widgets) ? it.widgets : []).map((iw) => reIdWidget(iw)),
                }));
            const merged = { ...w, props: { ...props, items: nextItems } };
            return normalizeWidget(merged) || merged;
        }

        return normalizeWidget(w) || w;
    };

    const pasteAtTarget = (target, payload) => {
        if (!target || !payload || !payload.data) return false;

        if (payload.kind === 'section') {
            const src = deepClone(payload.data);
            let created = null;
            const next = {
                ...src,
                id: uid('sec'),
                columns: (Array.isArray(src.columns) ? src.columns : []).filter(Boolean).map((c) => ({
                    ...c,
                    id: uid('col'),
                    widgets: (Array.isArray(c.widgets) ? c.widgets : []).map((w) => reIdWidget(w)),
                })),
            };
            setPage((p) => {
                const normalized = normalizeSection(next) || next;
                created = normalized;
                const sections = [...p.sections];
                let at = target.sectionId ? sections.findIndex((s) => s.id === target.sectionId) : sections.length - 1;
                if (at === -1) at = sections.length - 1;
                sections.splice(at + 1, 0, normalized);
                return { ...p, sections };
            });
            if (created) setSelected({ kind: 'section', sectionId: created.id });
            return true;
        }

        if (payload.kind === 'column') {
            if (!target.sectionId) return false;
            const src = deepClone(payload.data);
            const nextCol = {
                ...src,
                id: uid('col'),
                widgets: (Array.isArray(src.widgets) ? src.widgets : []).map((w) => reIdWidget(w)),
            };
            setPage((p) => ({
                ...p,
                sections: p.sections.map((s) => {
                    if (s.id !== target.sectionId) return s;
                    const columns = [...(Array.isArray(s.columns) ? s.columns : [])];
                    const idx = target.columnId ? columns.findIndex((c) => c.id === target.columnId) : columns.length - 1;
                    columns.splice(idx >= 0 ? idx + 1 : columns.length, 0, normalizeColumn(nextCol) || nextCol);
                    return { ...s, columns };
                }),
            }));
            setSelected({ kind: 'column', sectionId: target.sectionId, columnId: nextCol.id });
            return true;
        }

        if (payload.kind === 'grid_column') {
            const src = deepClone(payload.data);
            const nextGc = normalizeColumn({
                ...src,
                id: uid('gcol'),
                widgets: (Array.isArray(src.widgets) ? src.widgets : []).map((w) => reIdWidget(w)),
            }) || { ...src, id: uid('gcol') };

            let destGridWidgetId = null;
            let anchorGridColumnId = null;
            if (target.kind === 'grid_column' || target.kind === 'grid_widget') {
                destGridWidgetId = target.gridWidgetId;
                anchorGridColumnId = target.gridColumnId;
            } else if (target.kind === 'widget') {
                const sec = page.sections.find((s) => s.id === target.sectionId);
                const col = sec?.columns.find((c) => c.id === target.columnId);
                const w = col ? findWidgetDeep(col.widgets, target.widgetId) : null;
                if (w && w.type === 'grid') destGridWidgetId = w.id;
            }
            if (!destGridWidgetId || !target.sectionId || !target.columnId) return false;

            setPage((p) => ({
                ...p,
                sections: p.sections.map((s) => {
                    if (s.id !== target.sectionId) return s;
                    return {
                        ...s,
                        columns: s.columns.map((c) => {
                            if (c.id !== target.columnId) return c;
                            const [nextWidgets, didUpdate] = updateWidgetsDeep(
                                Array.isArray(c.widgets) ? c.widgets : [],
                                destGridWidgetId,
                                (w) => {
                                    if (!w || w.type !== 'grid') return w;
                                    const props = w.props && typeof w.props === 'object' ? w.props : {};
                                    const cols = Array.isArray(props.columns) ? props.columns : [];
                                    const idx = anchorGridColumnId ? cols.findIndex((gc) => gc && gc.id === anchorGridColumnId) : cols.length - 1;
                                    const nextCols = [...cols];
                                    nextCols.splice(idx >= 0 ? idx + 1 : nextCols.length, 0, nextGc);
                                    return { ...w, props: { ...props, columns: nextCols } };
                                }
                            );
                            return didUpdate ? { ...c, widgets: nextWidgets } : c;
                        }),
                    };
                }),
            }));

            setSelected({
                kind: 'grid_column',
                sectionId: target.sectionId,
                columnId: target.columnId,
                gridWidgetId: destGridWidgetId,
                gridColumnId: nextGc.id,
            });
            return true;
        }

        if (payload.kind !== 'widget') return false;

        const newWidget = reIdWidget(deepClone(payload.data));
        if (!newWidget || !target.sectionId) return false;

        const placeIntoList = (list, anchorId) => {
            const arr = Array.isArray(list) ? [...list] : [];
            const idx = anchorId ? arr.findIndex((x) => x && x.id === anchorId) : -1;
            const at = idx >= 0 ? idx + 1 : arr.length;
            arr.splice(at, 0, newWidget);
            return arr;
        };

        const targetColumnId = target.columnId
            || (target.kind === 'section'
                ? ((page.sections.find((s) => s.id === target.sectionId)?.columns || [])[0] || {}).id
                : undefined);
        if (!targetColumnId) return false;

        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== target.sectionId) return s;
                return {
                    ...s,
                    columns: s.columns.map((c) => {
                        if (c.id !== targetColumnId) return c;

                        if (target.kind === 'column' || target.kind === 'section') {
                            return { ...c, widgets: placeIntoList(c.widgets, null) };
                        }
                        if (target.kind === 'widget') {
                            return { ...c, widgets: placeIntoList(c.widgets, target.widgetId) };
                        }
                        if (target.kind === 'grid_column' || target.kind === 'grid_widget') {
                            const [nextWidgets, didUpdate] = updateWidgetsDeep(
                                Array.isArray(c.widgets) ? c.widgets : [],
                                target.gridWidgetId,
                                (w) => {
                                    if (!w || w.type !== 'grid') return w;
                                    const props = w.props && typeof w.props === 'object' ? w.props : {};
                                    const cols = Array.isArray(props.columns) ? props.columns : [];
                                    return {
                                        ...w,
                                        props: {
                                            ...props,
                                            columns: cols.map((gc) => (gc && gc.id === target.gridColumnId
                                                ? { ...gc, widgets: placeIntoList(gc.widgets, target.kind === 'grid_widget' ? target.widgetId : null) }
                                                : gc)),
                                        },
                                    };
                                }
                            );
                            return didUpdate ? { ...c, widgets: nextWidgets } : c;
                        }
                        if (target.kind === 'tab_item' || target.kind === 'tab_widget') {
                            const [nextWidgets, didUpdate] = updateWidgetsDeep(
                                Array.isArray(c.widgets) ? c.widgets : [],
                                target.tabWidgetId,
                                (w) => {
                                    if (!w || w.type !== 'tabs') return w;
                                    const props = w.props && typeof w.props === 'object' ? w.props : {};
                                    const items = Array.isArray(props.items) ? props.items : [];
                                    return {
                                        ...w,
                                        props: {
                                            ...props,
                                            items: items.map((it) => (it && it.id === target.tabItemId
                                                ? { ...it, widgets: placeIntoList(it.widgets, target.kind === 'tab_widget' ? target.widgetId : null) }
                                                : it)),
                                        },
                                    };
                                }
                            );
                            return didUpdate ? { ...c, widgets: nextWidgets } : c;
                        }
                        return c;
                    }),
                };
            }),
        }));

        if (target.kind === 'grid_column' || target.kind === 'grid_widget') {
            setSelected({
                kind: 'grid_widget',
                sectionId: target.sectionId,
                columnId: targetColumnId,
                gridWidgetId: target.gridWidgetId,
                gridColumnId: target.gridColumnId,
                widgetId: newWidget.id,
            });
        } else if (target.kind === 'tab_item' || target.kind === 'tab_widget') {
            setSelected({
                kind: 'tab_widget',
                sectionId: target.sectionId,
                columnId: targetColumnId,
                tabWidgetId: target.tabWidgetId,
                tabItemId: target.tabItemId,
                widgetId: newWidget.id,
            });
        } else {
            setSelected({ kind: 'widget', sectionId: target.sectionId, columnId: targetColumnId, widgetId: newWidget.id });
        }
        return true;
    };

    const pasteTargetFromSelection = () => {
        const sec = selectedModel.section;
        const col = selectedModel.column;
        switch (selectedModel.kind) {
            case 'section':
                return { kind: 'section', sectionId: sec.id };
            case 'column':
                return { kind: 'column', sectionId: sec.id, columnId: col.id };
            case 'widget':
                return { kind: 'widget', sectionId: sec.id, columnId: col.id, widgetId: selectedModel.widget.id };
            case 'grid_column':
                return { kind: 'grid_column', sectionId: sec.id, columnId: col.id, gridWidgetId: selectedModel.gridWidget.id, gridColumnId: selectedModel.gridColumn.id };
            case 'grid_widget':
                return { kind: 'grid_widget', sectionId: sec.id, columnId: col.id, gridWidgetId: selectedModel.gridWidget.id, gridColumnId: selectedModel.gridColumn.id, widgetId: selectedModel.widget.id };
            case 'tab_item':
                return { kind: 'tab_item', sectionId: sec.id, columnId: col.id, tabWidgetId: selectedModel.tabWidget.id, tabItemId: selectedModel.tabItem.id };
            case 'tab_widget':
                return { kind: 'tab_widget', sectionId: sec.id, columnId: col.id, tabWidgetId: selectedModel.tabWidget.id, tabItemId: selectedModel.tabItem.id, widgetId: selectedModel.widget.id };
            default:
                return null;
        }
    };

    const duplicateGridWidget = (sectionId, columnId, gridWidgetId, gridColumnId, widgetId) => {
        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== sectionId) return s;
                return {
                    ...s,
                    columns: s.columns.map((c) => {
                        if (c.id !== columnId) return c;
                        const rawWidgets = Array.isArray(c.widgets) ? c.widgets : [];
                        const [nextWidgets, didUpdate] = updateWidgetsDeep(rawWidgets, gridWidgetId, (w) => {
                            if (!w || w.type !== 'grid') return w;
                            const props = w.props && typeof w.props === 'object' ? w.props : {};
                            const cols = Array.isArray(props.columns) ? props.columns : [];
                            const nextCols = cols.map((gc) => {
                                if (!gc || gc.id !== gridColumnId) return gc;
                                const widgets = Array.isArray(gc.widgets) ? gc.widgets : [];
                                const idx = widgets.findIndex((iw) => iw && iw.id === widgetId);
                                if (idx === -1) return gc;
                                const widget = widgets[idx];
                                const duplicate = reIdWidget(widget);
                                const newWidgets = [...widgets];
                                newWidgets.splice(idx + 1, 0, duplicate);
                                return { ...gc, widgets: newWidgets };
                            });
                            return { ...w, props: { ...props, columns: nextCols } };
                        });
                        if (!didUpdate) return c;
                        return { ...c, widgets: nextWidgets };
                    }),
                };
            }),
        }));
    };

    const duplicateTabWidget = (sectionId, columnId, tabWidgetId, tabItemId, widgetId) => {
        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== sectionId) return s;
                return {
                    ...s,
                    columns: s.columns.map((c) => {
                        if (c.id !== columnId) return c;
                        const rawWidgets = Array.isArray(c.widgets) ? c.widgets : [];
                        const [nextWidgets, didUpdate] = updateWidgetsDeep(rawWidgets, tabWidgetId, (w) => {
                            if (!w || w.type !== 'tabs') return w;
                            const props = w.props && typeof w.props === 'object' ? w.props : {};
                            const items = Array.isArray(props.items) ? props.items : [];
                            const nextItems = items.map((it) => {
                                if (!it || it.id !== tabItemId) return it;
                                const widgets = Array.isArray(it.widgets) ? it.widgets : [];
                                const idx = widgets.findIndex((iw) => iw && iw.id === widgetId);
                                if (idx === -1) return it;
                                const widget = widgets[idx];
                                const duplicate = reIdWidget(widget);
                                const newWidgets = [...widgets];
                                newWidgets.splice(idx + 1, 0, duplicate);
                                return { ...it, widgets: newWidgets };
                            });
                            return { ...w, props: { ...props, items: nextItems } };
                        });
                        if (!didUpdate) return c;
                        return { ...c, widgets: nextWidgets };
                    }),
                };
            }),
        }));
    };

    const duplicateTabItem = (sectionId, columnId, tabWidgetId, tabItemId) => {
        let created = null;
        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== sectionId) return s;
                return {
                    ...s,
                    columns: s.columns.map((c) => {
                        if (c.id !== columnId) return c;
                        const rawWidgets = Array.isArray(c.widgets) ? c.widgets : [];
                        const [nextWidgets, didUpdate] = updateWidgetsDeep(rawWidgets, tabWidgetId, (w) => {
                            if (!w || w.type !== 'tabs') return w;
                            const props = w.props && typeof w.props === 'object' ? w.props : {};
                            const items = Array.isArray(props.items) ? props.items : [];
                            const idx = items.findIndex((it) => it && it.id === tabItemId);
                            if (idx === -1) return w;
                            const item = items[idx];
                            const duplicateItem = {
                                ...deepClone(item),
                                id: uid('tabitem'),
                                widgets: (Array.isArray(item.widgets) ? item.widgets : []).map((iw) => reIdWidget(iw)),
                            };
                            created = duplicateItem.id;
                            const newItems = [...items];
                            newItems.splice(idx + 1, 0, duplicateItem);
                            return { ...w, props: { ...props, items: newItems } };
                        });
                        if (!didUpdate) return c;
                        return { ...c, widgets: nextWidgets };
                    }),
                };
            }),
        }));
        if (created) {
            setSelected({ kind: 'tab_item', sectionId, columnId, tabWidgetId, tabItemId: created });
            setRailTab('edit');
            setEditTab('content');
        }
        return created;
    };

    const duplicateSection = (sectionId) => {
        let created = null;

        setPage((p) => {
            const idx = p.sections.findIndex((s) => s && s.id === sectionId);
            if (idx === -1) return p;

            const section = p.sections[idx];
            const duplicate = deepClone(section);

            const next = {
                ...duplicate,
                id: uid('sec'),
                columns: (Array.isArray(duplicate.columns) ? duplicate.columns : [])
                    .filter(Boolean)
                    .map((c) => {
                        const col = {
                            ...c,
                            id: uid('col'),
                            widgets: (Array.isArray(c.widgets) ? c.widgets : []).map((w) => reIdWidget(w)),
                        };
                        return normalizeColumn(col) || col;
                    }),
            };

            const normalized = normalizeSection(next) || next;
            created = normalized;

            const sections = [...p.sections];
            sections.splice(idx + 1, 0, normalized);
            return { ...p, sections };
        });

        if (created) {
            setSelected({ kind: 'section', sectionId: created.id });
            setRailTab('edit');
            setEditTab('content');
        }
    };

    const duplicateColumn = (sectionId, columnId) => {
        let created = null;

        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== sectionId) return s;

                const cols = Array.isArray(s.columns) ? s.columns : [];
                const idx = cols.findIndex((c) => c && c.id === columnId);
                if (idx === -1) return s;

                const source = cols[idx];
                const duplicate = deepClone(source);
                const nextColumn = normalizeColumn({
                    ...duplicate,
                    id: uid('col'),
                    widgets: (Array.isArray(duplicate.widgets) ? duplicate.widgets : []).map((w) => reIdWidget(w)),
                }) || duplicate;

                created = nextColumn;

                const nextCols = [...cols];
                nextCols.splice(idx + 1, 0, nextColumn);

                return { ...s, columns: nextCols };
            }),
        }));

        if (created) {
            setSelected({ kind: 'column', sectionId, columnId: created.id });
            setRailTab('edit');
            setEditTab('content');
        }
    };

    const ensureSectionColumnsCount = (sectionId, count) => {
        const n = Math.max(1, Math.min(4, Math.round(Number(count) || 1)));
        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== sectionId) return s;

                const cols = Array.isArray(s.columns) ? s.columns.slice() : [];
                while (cols.length < n) {
                    cols.push(
                        normalizeColumn({
                            id: uid('col'),
                            name: `Column ${cols.length + 1}`,
                            spans: { base: 12, md: 12, lg: 12 },
                            widgets: [],
                        })
                    );
                }
                while (cols.length > n) {
                    cols.pop();
                }

                const even = Math.floor(12 / n);
                const remainder = 12 - even * n;
                const mdSpans = Array.from({ length: n }, (_, idx) => even + (idx === n - 1 ? remainder : 0));

                const nextCols = cols
                    .filter(Boolean)
                    .map((c, idx) => {
                        const normalized = normalizeColumn({
                            ...c,
                            name: typeof c.name === 'string' ? c.name : `Column ${idx + 1}`,
                            spans: {
                                ...(c.spans && typeof c.spans === 'object' ? c.spans : { base: 12, md: 12, lg: 12 }),
                                base: 12,
                                md: mdSpans[idx] || 12,
                                lg: mdSpans[idx] || 12,
                            },
                        });
                        return normalized || c;
                    });

                return { ...s, columns: nextCols };
            }),
        }));
    };

    const startResizeColumns = (e, sectionId, leftColumnId, rightColumnId, edge = 'right') => {
        e.preventDefault();
        e.stopPropagation();

        const key = viewport === 'desktop' ? 'lg' : viewport === 'tablet' ? 'md' : 'base';
        const startX = e.clientX;

        const columnEl = e.currentTarget && e.currentTarget.parentElement ? e.currentTarget.parentElement : null;
        const leftEl = edge === 'left' ? (columnEl && columnEl.previousElementSibling ? columnEl.previousElementSibling : null) : columnEl;
        const rightEl =
            edge === 'left'
                ? columnEl
                : leftEl && leftEl.nextElementSibling
                  ? leftEl.nextElementSibling
                  : null;
        if (!leftEl || !rightEl) return;

        const leftRect = leftEl.getBoundingClientRect();
        const rightRect = rightEl.getBoundingClientRect();
        const totalPx = leftRect.width + rightRect.width;
        if (!Number.isFinite(totalPx) || totalPx <= 0) return;

        const snap = pageRef.current;
        const sec = snap && Array.isArray(snap.sections) ? snap.sections.find((s) => s && s.id === sectionId) : null;
        const leftCol = sec && Array.isArray(sec.columns) ? sec.columns.find((c) => c && c.id === leftColumnId) : null;
        const rightCol = sec && Array.isArray(sec.columns) ? sec.columns.find((c) => c && c.id === rightColumnId) : null;
        if (!leftCol || !rightCol) return;

        const leftSpan0 = clampSpan(leftCol.spans && leftCol.spans[key] !== undefined ? leftCol.spans[key] : 12);
        const rightSpan0 = clampSpan(rightCol.spans && rightCol.spans[key] !== undefined ? rightCol.spans[key] : 12);
        const totalSpan = Math.max(2, leftSpan0 + rightSpan0);

        try {
            if (e.currentTarget && typeof e.currentTarget.setPointerCapture === 'function') {
                e.currentTarget.setPointerCapture(e.pointerId);
            }
        } catch (err) {
        }

        try {
            document.body.style.cursor = 'col-resize';
        } catch (err) {
        }

        const onMove = (ev) => {
            const dx = ev.clientX - startX;
            const nextLeftPx = leftRect.width + dx;

            let nextLeftSpan = Math.round((nextLeftPx / totalPx) * totalSpan);
            nextLeftSpan = Math.max(1, Math.min(totalSpan - 1, nextLeftSpan));
            const nextRightSpan = Math.max(1, totalSpan - nextLeftSpan);

            setPage((p) => ({
                ...p,
                sections: p.sections.map((s) => {
                    if (s.id !== sectionId) return s;
                    return {
                        ...s,
                        columns: s.columns.map((c) => {
                            if (c.id === leftColumnId) {
                                return { ...c, spans: { ...c.spans, [key]: clampSpan(nextLeftSpan) } };
                            }
                            if (c.id === rightColumnId) {
                                return { ...c, spans: { ...c.spans, [key]: clampSpan(nextRightSpan) } };
                            }
                            return c;
                        }),
                    };
                }),
            }));
        };

        const onUp = () => {
            window.removeEventListener('pointermove', onMove);
            window.removeEventListener('pointerup', onUp);
            try {
                document.body.style.cursor = '';
            } catch (err) {
            }
        };

        window.addEventListener('pointermove', onMove);
        window.addEventListener('pointerup', onUp);
    };

    const updateSection = (sectionId, patch) => {
        // The section Columns select binds to `columns.length`. A normal patch
        // from that control is `{ columns: { length: "N" } }`, which would
        // replace the columns array with a plain object. Route it through the
        // proper resize helper instead.
        if (
            patch &&
            typeof patch.columns === 'object' &&
            patch.columns !== null &&
            !Array.isArray(patch.columns) &&
            typeof patch.columns.length !== 'undefined'
        ) {
            ensureSectionColumnsCount(sectionId, patch.columns.length);
            return;
        }

        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== sectionId) return s;

                const next = { ...s, ...patch };

                if (patch && typeof patch.style === 'object' && patch.style !== null) {
                    next.style = {
                        ...(s.style || {}),
                        ...patch.style,
                    };
                }

                if (patch && typeof patch.advanced === 'object' && patch.advanced !== null) {
                    next.advanced = {
                        ...(s.advanced || {}),
                        ...patch.advanced,
                    };
                }

                // Never let the columns array be replaced by a non-array value.
                if (!Array.isArray(next.columns)) {
                    next.columns = Array.isArray(s.columns) ? s.columns : [];
                }

                return next;
            }),
        }));
    };

    const updateColumn = (sectionId, columnId, patch) => {
        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== sectionId) return s;
                return {
                    ...s,
                    columns: s.columns.map((c) => {
                        if (c.id !== columnId) return c;
                        const next = { ...c, ...patch };
                        if (patch && typeof patch.style === 'object' && patch.style !== null) {
                            next.style = { ...(c.style || {}), ...patch.style };
                        }
                        if (patch && typeof patch.advanced === 'object' && patch.advanced !== null) {
                            next.advanced = { ...(c.advanced || {}), ...patch.advanced };
                        }
                        if (patch && typeof patch.spans === 'object' && patch.spans !== null) {
                            next.spans = { ...(c.spans || {}), ...patch.spans };
                        }
                        return next;
                    }),
                };
            }),
        }));
    };

    const deleteSection = (sectionId) => {
        const fallbackSections = defaultPage().sections;
        const currentSections = Array.isArray(pageRef.current?.sections) ? pageRef.current.sections : [];
        const nextFromCurrent = currentSections.find((s) => s && s.id !== sectionId) || null;
        const fallbackFirst = Array.isArray(fallbackSections) ? fallbackSections[0] : null;

        setPage((p) => {
            const raw = Array.isArray(p.sections) ? p.sections : [];
            const nextSections = raw.filter((s) => s && s.id !== sectionId);
            return { ...p, sections: nextSections.length ? nextSections : fallbackSections };
        });

        setSelected((sel) => {
            if (!sel || sel.sectionId !== sectionId) return sel;
            const nextId = nextFromCurrent?.id || fallbackFirst?.id;
            return nextId ? { kind: 'section', sectionId: nextId } : { kind: 'none' };
        });
    };

    const deleteColumn = (sectionId, columnId) => {
        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== sectionId) return s;

                const rawCols = Array.isArray(s.columns) ? s.columns : [];
                const cols = rawCols.filter((c) => c && c.id !== columnId);

                if (cols.length === 0) {
                    return {
                        ...s,
                        columns: [
                            normalizeColumn({
                                id: uid('col'),
                                name: 'Column 1',
                                spans: { base: 12, md: 12, lg: 12 },
                                widgets: [],
                            }),
                        ].filter(Boolean),
                    };
                }

                const even = Math.floor(12 / cols.length);
                const remainder = 12 - even * cols.length;
                const mdSpans = Array.from({ length: cols.length }, (_, idx) => even + (idx === cols.length - 1 ? remainder : 0));

                const nextCols = cols.map((c, idx) => ({
                    ...c,
                    spans: {
                        base: 12,
                        md: mdSpans[idx] || 12,
                        lg: mdSpans[idx] || 12,
                    },
                }));

                return { ...s, columns: nextCols };
            }),
        }));

        setSelected((sel) => {
            if (!sel || sel.sectionId !== sectionId) return sel;
            if (sel.kind === 'column' && sel.columnId === columnId) return { kind: 'section', sectionId };
            if (sel.kind === 'widget' && sel.columnId === columnId) return { kind: 'section', sectionId };
            if (sel.kind === 'grid_column' && sel.columnId === columnId) return { kind: 'section', sectionId };
            if (sel.kind === 'grid_widget' && sel.columnId === columnId) return { kind: 'section', sectionId };
            return sel;
        });
    };

    const duplicateGridColumn = (sectionId, columnId, gridWidgetId, gridColumnId) => {
        let created = null;

        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== sectionId) return s;
                return {
                    ...s,
                    columns: s.columns.map((c) => {
                        if (c.id !== columnId) return c;
                        const rawWidgets = Array.isArray(c.widgets) ? c.widgets : [];
                        const [nextWidgets, didUpdate] = updateWidgetsDeep(rawWidgets, gridWidgetId, (w) => {
                            if (!w || w.type !== 'grid') return w;
                            const props = w.props && typeof w.props === 'object' ? w.props : {};
                            const cols = Array.isArray(props.columns) ? props.columns : [];
                            const idx = cols.findIndex((gc) => gc && gc.id === gridColumnId);
                            if (idx === -1) return w;

                            const source = cols[idx];
                            const duplicate = deepClone(source);
                            const nextGc = normalizeColumn({
                                ...duplicate,
                                id: uid('gcol'),
                                widgets: (Array.isArray(duplicate.widgets) ? duplicate.widgets : []).map((iw) => reIdWidget(iw)),
                            }) || duplicate;
                            created = nextGc;

                            const finalCols = [...cols];
                            finalCols.splice(idx + 1, 0, nextGc);

                            const mergedGrid = { ...w, props: { ...props, columns: finalCols } };
                            return normalizeWidget(mergedGrid) || mergedGrid;
                        });
                        if (!didUpdate) return c;
                        return { ...c, widgets: nextWidgets };
                    }),
                };
            }),
        }));

        if (created) {
            setSelected({ kind: 'grid_column', sectionId, columnId, gridWidgetId, gridColumnId: created.id });
            setRailTab('edit');
            setEditTab('content');
        }
    };

    const deleteGridColumn = (sectionId, columnId, gridWidgetId, gridColumnId) => {
        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== sectionId) return s;
                return {
                    ...s,
                    columns: s.columns.map((c) => {
                        if (c.id !== columnId) return c;
                        const rawWidgets = Array.isArray(c.widgets) ? c.widgets : [];
                        const [nextWidgets, didUpdate] = updateWidgetsDeep(rawWidgets, gridWidgetId, (w) => {
                            if (!w || w.type !== 'grid') return w;
                            const props = w.props && typeof w.props === 'object' ? w.props : {};
                            const rawCols = Array.isArray(props.columns) ? props.columns : [];
                            const kept = rawCols.filter((gc) => gc && gc.id !== gridColumnId);
                            const nextCols = kept.length
                                ? kept
                                : [
                                      normalizeColumn({
                                          id: uid('gcol'),
                                          name: 'Column 1',
                                          spans: { base: 12, md: 12, lg: 12 },
                                          widgets: [],
                                      }),
                                  ].filter(Boolean);
                            const merged = {
                                ...w,
                                props: {
                                    ...props,
                                    count: nextCols.length,
                                    columns: nextCols,
                                },
                            };
                            return normalizeWidget(merged) || merged;
                        });
                        if (!didUpdate) return c;
                        return { ...c, widgets: nextWidgets };
                    }),
                };
            }),
        }));

        setSelected((sel) => {
            if (!sel || sel.sectionId !== sectionId || sel.columnId !== columnId) return sel;
            if (sel.kind === 'grid_column' && sel.gridWidgetId === gridWidgetId && sel.gridColumnId === gridColumnId) {
                return { kind: 'widget', sectionId, columnId, widgetId: gridWidgetId };
            }
            if (sel.kind === 'grid_widget' && sel.gridWidgetId === gridWidgetId && sel.gridColumnId === gridColumnId) {
                return { kind: 'widget', sectionId, columnId, widgetId: gridWidgetId };
            }
            return sel;
        });
    };

    const updateWidget = (sectionId, columnId, widgetId, patch) => {
        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== sectionId) return s;
                return {
                    ...s,
                    columns: s.columns.map((c) => {
                        if (c.id !== columnId) return c;
                        const rawWidgets = Array.isArray(c.widgets) ? c.widgets : [];
                        const [nextWidgets, didUpdate] = updateWidgetsDeep(rawWidgets, widgetId, (w) => {
                            const next = { ...w, ...patch, props: { ...(w.props || {}), ...(patch.props || {}) } };
                            return next && (next.type === 'grid' || next.type === 'tabs') ? normalizeWidget(next) || next : next;
                        });
                        if (!didUpdate) return c;
                        return { ...c, widgets: nextWidgets };
                    }),
                };
            }),
        }));
    };

    const updateGridWidget = (sectionId, columnId, gridWidgetId, patchProps) => {
        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== sectionId) return s;
                return {
                    ...s,
                    columns: s.columns.map((c) => {
                        if (c.id !== columnId) return c;
                        const rawWidgets = Array.isArray(c.widgets) ? c.widgets : [];
                        const [nextWidgets, didUpdate] = updateWidgetsDeep(rawWidgets, gridWidgetId, (w) => {
                            if (!w || w.type !== 'grid') return w;
                            const currentProps = w.props && typeof w.props === 'object' ? w.props : {};
                            const merged = { ...w, props: { ...currentProps, ...patchProps } };
                            return normalizeWidget(merged) || merged;
                        });
                        if (!didUpdate) return c;
                        return { ...c, widgets: nextWidgets };
                    }),
                };
            }),
        }));
    };

    const updateGridInnerWidget = (sectionId, columnId, gridWidgetId, gridColumnId, widgetId, patch) => {
        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== sectionId) return s;
                return {
                    ...s,
                    columns: s.columns.map((c) => {
                        if (c.id !== columnId) return c;
                        const rawWidgets = Array.isArray(c.widgets) ? c.widgets : [];
                        const [nextWidgets, didUpdate] = updateWidgetsDeep(rawWidgets, gridWidgetId, (w) => {
                            if (!w || w.type !== 'grid') return w;
                            const props = w.props && typeof w.props === 'object' ? w.props : {};
                            const cols = Array.isArray(props.columns) ? props.columns : [];
                            const nextCols = cols.map((gc) => {
                                if (!gc || gc.id !== gridColumnId) return gc;
                                const widgets = Array.isArray(gc.widgets) ? gc.widgets : [];
                                return {
                                    ...gc,
                                    widgets: widgets.map((iw) => {
                                        if (!iw || iw.id !== widgetId) return iw;
                                        const next = { ...iw, ...patch, props: { ...(iw.props || {}), ...(patch.props || {}) } };
                                        if (next && next.type === 'grid') {
                                            return normalizeWidget(next) || next;
                                        }
                                        return next;
                                    }),
                                };
                            });
                            return { ...w, props: { ...props, columns: nextCols } };
                        });
                        if (!didUpdate) return c;
                        return { ...c, widgets: nextWidgets };
                    }),
                };
            }),
        }));
    };

    const addWidgetToGridColumn = (sectionId, columnId, gridWidgetId, gridColumnId, type, initialProps = {}, insertIndex = null) => {
        const defaults = getElementDefaults(type);
        const widget = normalizeWidget({ id: uid('w'), type, name: '', props: { ...defaults, ...initialProps } });
        if (!widget) return;

        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== sectionId) return s;
                return {
                    ...s,
                    columns: s.columns.map((c) => {
                        if (c.id !== columnId) return c;
                        const rawWidgets = Array.isArray(c.widgets) ? c.widgets : [];
                        const [nextWidgets, didUpdate] = updateWidgetsDeep(rawWidgets, gridWidgetId, (w) => {
                            if (!w || w.type !== 'grid') return w;
                            const props = w.props && typeof w.props === 'object' ? w.props : {};
                            const cols = Array.isArray(props.columns) ? props.columns : [];
                            const nextCols = cols.map((gc) => {
                                if (!gc || gc.id !== gridColumnId) return gc;
                                const widgets = Array.isArray(gc.widgets) ? gc.widgets : [];
                                const idx = Number.isInteger(insertIndex) ? Math.max(0, Math.min(insertIndex, widgets.length)) : widgets.length;
                                const nextWidgets = [...widgets.slice(0, idx), widget, ...widgets.slice(idx)];
                                return { ...gc, widgets: nextWidgets };
                            });
                            return { ...w, props: { ...props, columns: nextCols } };
                        });
                        if (!didUpdate) return c;
                        return { ...c, widgets: nextWidgets };
                    }),
                };
            }),
        }));

        setSelected({ kind: 'grid_widget', sectionId, columnId, gridWidgetId, gridColumnId, widgetId: widget.id });
        setRailTab('edit');
        setEditTab('content');
    };

    const updateGridColumn = (sectionId, columnId, gridWidgetId, gridColumnId, patch) => {
        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== sectionId) return s;
                return {
                    ...s,
                    columns: s.columns.map((c) => {
                        if (c.id !== columnId) return c;
                        const rawWidgets = Array.isArray(c.widgets) ? c.widgets : [];
                        const [nextWidgets, didUpdate] = updateWidgetsDeep(rawWidgets, gridWidgetId, (w) => {
                            if (!w || w.type !== 'grid') return w;
                            const props = w.props && typeof w.props === 'object' ? w.props : {};
                            const cols = Array.isArray(props.columns) ? props.columns : [];
                            const nextCols = cols.map((gc) => {
                                if (!gc || gc.id !== gridColumnId) return gc;
                                const merged = { ...gc, ...patch };
                                if (patch && typeof patch.style === 'object' && patch.style !== null) {
                                    merged.style = { ...(gc.style || {}), ...patch.style };
                                }
                                if (patch && typeof patch.advanced === 'object' && patch.advanced !== null) {
                                    merged.advanced = { ...(gc.advanced || {}), ...patch.advanced };
                                }
                                if (patch && typeof patch.spans === 'object' && patch.spans !== null) {
                                    merged.spans = { ...(gc.spans || {}), ...patch.spans };
                                }
                                return normalizeColumn(merged) || merged;
                            });
                            const mergedGrid = { ...w, props: { ...props, columns: nextCols } };
                            return normalizeWidget(mergedGrid) || mergedGrid;
                        });
                        if (!didUpdate) return c;
                        return { ...c, widgets: nextWidgets };
                    }),
                };
            }),
        }));
    };

    const removeGridInnerWidget = (sectionId, columnId, gridWidgetId, gridColumnId, widgetId) => {
        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== sectionId) return s;
                return {
                    ...s,
                    columns: s.columns.map((c) => {
                        if (c.id !== columnId) return c;
                        const rawWidgets = Array.isArray(c.widgets) ? c.widgets : [];
                        const [nextWidgets, didUpdate] = updateWidgetsDeep(rawWidgets, gridWidgetId, (w) => {
                            if (!w || w.type !== 'grid') return w;
                            const props = w.props && typeof w.props === 'object' ? w.props : {};
                            const cols = Array.isArray(props.columns) ? props.columns : [];
                            const nextCols = cols.map((gc) => {
                                if (!gc || gc.id !== gridColumnId) return gc;
                                const widgets = Array.isArray(gc.widgets) ? gc.widgets : [];
                                return { ...gc, widgets: widgets.filter((iw) => !(iw && iw.id === widgetId)) };
                            });
                            const merged = { ...w, props: { ...props, columns: nextCols } };
                            return normalizeWidget(merged) || merged;
                        });
                        if (!didUpdate) return c;
                        return { ...c, widgets: nextWidgets };
                    }),
                };
            }),
        }));
    };

    const addWidgetToTabItem = (sectionId, columnId, tabWidgetId, tabItemId, type, initialProps = {}, insertIndex = null) => {
        const defaults = getElementDefaults(type);
        const widget = normalizeWidget({ id: uid('w'), type, name: '', props: { ...defaults, ...initialProps } });
        if (!widget) return;

        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== sectionId) return s;
                return {
                    ...s,
                    columns: s.columns.map((c) => {
                        if (c.id !== columnId) return c;
                        const rawWidgets = Array.isArray(c.widgets) ? c.widgets : [];
                        const [nextWidgets, didUpdate] = updateWidgetsDeep(rawWidgets, tabWidgetId, (w) => {
                            if (!w || w.type !== 'tabs') return w;
                            const props = w.props && typeof w.props === 'object' ? w.props : {};
                            const items = Array.isArray(props.items) ? props.items : [];
                            const nextItems = items.map((it) => {
                                if (!it || it.id !== tabItemId) return it;
                                const widgets = Array.isArray(it.widgets) ? it.widgets : [];
                                const idx = Number.isInteger(insertIndex) ? Math.max(0, Math.min(insertIndex, widgets.length)) : widgets.length;
                                return { ...it, widgets: [...widgets.slice(0, idx), widget, ...widgets.slice(idx)] };
                            });
                            return { ...w, props: { ...props, items: nextItems } };
                        });
                        if (!didUpdate) return c;
                        return { ...c, widgets: nextWidgets };
                    }),
                };
            }),
        }));

        setSelected({ kind: 'tab_widget', sectionId, columnId, tabWidgetId, tabItemId, widgetId: widget.id });
        setRailTab('edit');
        setEditTab('content');
    };

    const updateTabItem = (sectionId, columnId, tabWidgetId, tabItemId, patch) => {
        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== sectionId) return s;
                return {
                    ...s,
                    columns: s.columns.map((c) => {
                        if (c.id !== columnId) return c;
                        const rawWidgets = Array.isArray(c.widgets) ? c.widgets : [];
                        const [nextWidgets, didUpdate] = updateWidgetsDeep(rawWidgets, tabWidgetId, (w) => {
                            if (!w || w.type !== 'tabs') return w;
                            const props = w.props && typeof w.props === 'object' ? w.props : {};
                            const items = Array.isArray(props.items) ? props.items : [];
                            const nextItems = items.map((it) => {
                                if (!it || it.id !== tabItemId) return it;
                                return { ...it, ...patch };
                            });
                            return { ...w, props: { ...props, items: nextItems } };
                        });
                        if (!didUpdate) return c;
                        return { ...c, widgets: nextWidgets };
                    }),
                };
            }),
        }));
    };

    const removeTabItem = (sectionId, columnId, tabWidgetId, tabItemId) => {
        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== sectionId) return s;
                return {
                    ...s,
                    columns: s.columns.map((c) => {
                        if (c.id !== columnId) return c;
                        const rawWidgets = Array.isArray(c.widgets) ? c.widgets : [];
                        const [nextWidgets, didUpdate] = updateWidgetsDeep(rawWidgets, tabWidgetId, (w) => {
                            if (!w || w.type !== 'tabs') return w;
                            const props = w.props && typeof w.props === 'object' ? w.props : {};
                            const items = Array.isArray(props.items) ? props.items : [];
                            const nextItems = items.filter((it) => !(it && it.id === tabItemId));
                            return { ...w, props: { ...props, items: nextItems } };
                        });
                        if (!didUpdate) return c;
                        return { ...c, widgets: nextWidgets };
                    }),
                };
            }),
        }));
    };

    const updateTabInnerWidget = (sectionId, columnId, tabWidgetId, tabItemId, widgetId, patch) => {
        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== sectionId) return s;
                return {
                    ...s,
                    columns: s.columns.map((c) => {
                        if (c.id !== columnId) return c;
                        const rawWidgets = Array.isArray(c.widgets) ? c.widgets : [];
                        const [nextWidgets, didUpdate] = updateWidgetsDeep(rawWidgets, widgetId, (iw) => {
                            const next = { ...iw, ...patch };
                            return next && next.type === 'tabs' ? normalizeWidget(next) || next : next;
                        });
                        if (!didUpdate) return c;
                        return { ...c, widgets: nextWidgets };
                    }),
                };
            }),
        }));
    };

    const removeTabInnerWidget = (sectionId, columnId, tabWidgetId, tabItemId, widgetId) => {
        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== sectionId) return s;
                return {
                    ...s,
                    columns: s.columns.map((c) => {
                        if (c.id !== columnId) return c;
                        const rawWidgets = Array.isArray(c.widgets) ? c.widgets : [];
                        const [nextWidgets, didUpdate] = updateWidgetsDeep(rawWidgets, tabWidgetId, (w) => {
                            if (!w || w.type !== 'tabs') return w;
                            const props = w.props && typeof w.props === 'object' ? w.props : {};
                            const items = Array.isArray(props.items) ? props.items : [];
                            const nextItems = items.map((it) => {
                                if (!it || it.id !== tabItemId) return it;
                                return { ...it, widgets: (Array.isArray(it.widgets) ? it.widgets : []).filter((iw) => !(iw && iw.id === widgetId)) };
                            });
                            return { ...w, props: { ...props, items: nextItems } };
                        });
                        if (!didUpdate) return c;
                        return { ...c, widgets: nextWidgets };
                    }),
                };
            }),
        }));
    };

    const moveWidgetAnywhere = (from, to) => {
        if (!from || !to) return;
        if (!from.sectionId || !from.columnId || !from.widgetId) return;
        if (!to.sectionId || !to.columnId) return;

        let moved = null;
        let fromPath = null;
        let fromIndex = -1;
        const insertIndex = Number.isInteger(to.insertIndex) ? to.insertIndex : null;
        const sameContainer =
            from.sectionId === to.sectionId &&
            from.columnId === to.columnId &&
            String(from.gridWidgetId || '') === String(to.gridWidgetId || '') &&
            String(from.gridColumnId || '') === String(to.gridColumnId || '');

        const removeDeep = (widgets) => {
            if (!Array.isArray(widgets)) return { widgets, removed: null, index: -1, path: null };
            const topIdx = widgets.findIndex((w) => w && w.id === from.widgetId);
            if (topIdx >= 0) {
                const next = [...widgets];
                const [removed] = next.splice(topIdx, 1);
                return { widgets: next, removed, index: topIdx, path: [] };
            }
            for (let i = 0; i < widgets.length; i++) {
                const w = widgets[i];
                if (!w) continue;
                const props = w.props && typeof w.props === 'object' ? w.props : null;
                if (w.type === 'grid' && props && Array.isArray(props.columns)) {
                    for (let gi = 0; gi < props.columns.length; gi++) {
                        const gc = props.columns[gi];
                        const res = removeDeep(gc && Array.isArray(gc.widgets) ? gc.widgets : []);
                        if (res.removed) {
                            const nextCols = [...props.columns];
                            nextCols[gi] = { ...gc, widgets: res.widgets };
                            const next = [...widgets];
                            next[i] = { ...w, props: { ...props, columns: nextCols } };
                            return { widgets: next, removed: res.removed, index: res.index, path: [{ widgetId: w.id, gridColumnId: gc && gc.id }] };
                        }
                    }
                }
                if (w.type === 'tabs' && props && Array.isArray(props.items)) {
                    for (let ti = 0; ti < props.items.length; ti++) {
                        const tab = props.items[ti];
                        const res = removeDeep(tab && Array.isArray(tab.widgets) ? tab.widgets : []);
                        if (res.removed) {
                            const nextItems = [...props.items];
                            nextItems[ti] = { ...tab, widgets: res.widgets };
                            const next = [...widgets];
                            next[i] = { ...w, props: { ...props, items: nextItems } };
                            return { widgets: next, removed: res.removed, index: res.index, path: [{ widgetId: w.id, tabItemId: tab && tab.id }] };
                        }
                    }
                }
            }
            return { widgets, removed: null, index: -1, path: null };
        };

        const pathExists = (widgets, path) => {
            if (!path || path.length === 0) return true;
            const [step, ...rest] = path;
            const w = (Array.isArray(widgets) ? widgets : []).find((x) => x && x.id === step.widgetId);
            if (!w) return false;
            const props = w.props && typeof w.props === 'object' ? w.props : {};
            if (step.gridColumnId) {
                const gc = (Array.isArray(props.columns) ? props.columns : []).find((x) => x && x.id === step.gridColumnId);
                return !!gc && pathExists(gc.widgets || [], rest);
            }
            if (step.tabItemId) {
                const tab = (Array.isArray(props.items) ? props.items : []).find((x) => x && x.id === step.tabItemId);
                return !!tab && pathExists(tab.widgets || [], rest);
            }
            return false;
        };

        const insertDeep = (widgets, path, widget, idx) => {
            if (!Array.isArray(widgets)) widgets = [];
            if (!path || path.length === 0) {
                let at = Number.isInteger(idx) ? Math.max(0, Math.min(idx, widgets.length)) : widgets.length;
                if (sameContainer && fromIndex >= 0 && fromIndex < at) {
                    at = Math.max(0, Math.min(at - 1, widgets.length));
                }
                return [...widgets.slice(0, at), widget, ...widgets.slice(at)];
            }
            const [step, ...rest] = path;
            return widgets.map((w) => {
                if (!w || w.id !== step.widgetId) return w;
                const props = w.props && typeof w.props === 'object' ? w.props : {};
                if (step.gridColumnId) {
                    const cols = Array.isArray(props.columns) ? props.columns : [];
                    const nextCols = cols.map((gc) => {
                        if (!gc || gc.id !== step.gridColumnId) return gc;
                        return { ...gc, widgets: insertDeep(gc.widgets || [], rest, widget, idx) };
                    });
                    return { ...w, props: { ...props, columns: nextCols } };
                }
                if (step.tabItemId) {
                    const items = Array.isArray(props.items) ? props.items : [];
                    const nextItems = items.map((t) => {
                        if (!t || t.id !== step.tabItemId) return t;
                        return { ...t, widgets: insertDeep(t.widgets || [], rest, widget, idx) };
                    });
                    return { ...w, props: { ...props, items: nextItems } };
                }
                return w;
            });
        };

        setPage((p) => {
            const removedSections = p.sections.map((s) => {
                if (s.id !== from.sectionId && s.id !== to.sectionId) return s;
                return {
                    ...s,
                    columns: s.columns.map((c) => {
                        if (s.id !== from.sectionId || c.id !== from.columnId) return c;
                        const res = removeDeep(Array.isArray(c.widgets) ? c.widgets : []);
                        if (res.removed) {
                            moved = res.removed;
                            fromIndex = res.index;
                            fromPath = res.path || [];
                            return { ...c, widgets: res.widgets };
                        }
                        return c;
                    }),
                };
            });

            if (!moved) {
                return p;
            }

            const finalSections = removedSections.map((s) => {
                if (s.id !== to.sectionId) return s;
                return {
                    ...s,
                    columns: s.columns.map((c) => {
                        if (c.id !== to.columnId) return c;
                        const widgets = Array.isArray(c.widgets) ? c.widgets : [];
                        const path = to.gridWidgetId && to.gridColumnId
                            ? [{ widgetId: to.gridWidgetId, gridColumnId: to.gridColumnId }]
                            : (to.tabWidgetId && to.tabItemId
                                ? [{ widgetId: to.tabWidgetId, tabItemId: to.tabItemId }]
                                : []);
                        if (!pathExists(widgets, path)) return c;
                        const nextWidgets = insertDeep(widgets, path, moved, insertIndex);
                        return { ...c, widgets: nextWidgets };
                    }),
                };
            });

            const containsDeep = (widgets, id) =>
                (Array.isArray(widgets) ? widgets : []).some((w) => {
                    if (!w) return false;
                    if (w.id === id) return true;
                    const props = w.props && typeof w.props === 'object' ? w.props : {};
                    if (w.type === 'grid' && Array.isArray(props.columns)) {
                        return props.columns.some((gc) => containsDeep(gc && gc.widgets, id));
                    }
                    if (w.type === 'tabs' && Array.isArray(props.items)) {
                        return props.items.some((t) => containsDeep(t && t.widgets, id));
                    }
                    return false;
                });

            const inserted = finalSections.some((s) =>
                s.id === to.sectionId &&
                s.columns.some((c) => c.id === to.columnId && containsDeep(c.widgets, moved.id))
            );
            if (!inserted) {
                moved = null;
                return p;
            }

            return { ...p, sections: finalSections };
        });

        if (moved) {
            if (to.gridWidgetId && to.gridColumnId) {
                setSelected({ kind: 'grid_widget', sectionId: to.sectionId, columnId: to.columnId, gridWidgetId: to.gridWidgetId, gridColumnId: to.gridColumnId, widgetId: moved.id });
            } else {
                setSelected({ kind: 'widget', sectionId: to.sectionId, columnId: to.columnId, widgetId: moved.id });
            }
            setRailTab('edit');
            setEditTab('content');
        }
    };

    const moveWidgetToColumn = (from, to) => {
        moveWidgetAnywhere(from, to);
    };

    const addSection = () => {
        let created = null;

        setPage((p) => {
            const nextName = `Section ${p.sections.length + 1}`;
            const sec = normalizeSection({
                id: uid('sec'),
                name: nextName,
                style: defaultSectionStyle(mode),
                columns: [
                    {
                        id: uid('col'),
                        name: 'Column 1',
                        spans: { base: 12, md: 12, lg: 12 },
                        widgets: [],
                    },
                ],
            });

            if (!sec) return p;
            created = sec;
            return { ...p, sections: [...p.sections, sec] };
        });

        if (created) {
            setSelected({ kind: 'section', sectionId: created.id });
            setRailTab('edit');
            setEditTab('content');
        }
    };

    const insertSectionAfter = (afterSectionId, makeSection) => {
        let created = null;

        setPage((p) => {
            const raw = typeof makeSection === 'function' ? makeSection(p) : null;
            const normalized = normalizeSection(raw) || raw;
            if (!normalized) return p;

            const sections = Array.isArray(p.sections) ? [...p.sections] : [];
            const idx = afterSectionId ? sections.findIndex((x) => x && x.id === afterSectionId) : -1;
            const insertAt = idx >= 0 ? idx + 1 : sections.length;
            sections.splice(insertAt, 0, normalized);
            created = normalized;
            return { ...p, sections };
        });

        if (created) {
            setSelected({ kind: 'section', sectionId: created.id });
            setRailTab('edit');
            setEditTab('content');
        }
    };

    const insertSectionAtEnd = (makeSection) => {
        let created = null;

        setPage((p) => {
            const raw = typeof makeSection === 'function' ? makeSection(p) : null;
            const normalized = normalizeSection(raw) || raw;
            if (!normalized) return p;

            const sections = Array.isArray(p.sections) ? [...p.sections] : [];
            sections.push(normalized);
            created = normalized;
            return { ...p, sections };
        });

        if (created) {
            setSelected({ kind: 'section', sectionId: created.id });
            setEditTab('content');
        }

        return created;
    };

    const insertSectionBefore = (beforeSectionId, makeSection) => {
        let created = null;

        setPage((p) => {
            const raw = typeof makeSection === 'function' ? makeSection(p) : null;
            const normalized = normalizeSection(raw) || raw;
            if (!normalized) return p;

            const sections = Array.isArray(p.sections) ? [...p.sections] : [];
            const idx = beforeSectionId ? sections.findIndex((x) => x && x.id === beforeSectionId) : -1;
            const insertAt = idx >= 0 ? idx : 0;
            sections.splice(insertAt, 0, normalized);
            created = normalized;
            return { ...p, sections };
        });

        if (created) {
            setSelected({ kind: 'section', sectionId: created.id });
            setRailTab('edit');
            setEditTab('content');
        }
    };

    const replaceSectionByIndex = (sectionIndex, makeSection) => {
        const idx = Number.isInteger(sectionIndex) ? sectionIndex : -1;

        setPage((p) => {
            const raw = typeof makeSection === 'function' ? makeSection(p) : null;
            const normalized = normalizeSection(raw) || raw;
            if (!normalized) return p;

            const sections = Array.isArray(p.sections) ? [...p.sections] : [];
            if (idx >= 0 && idx < sections.length) {
                sections[idx] = normalized;
            } else {
                sections.push(normalized);
            }
            return { ...p, sections };
        });
    };

    const aiBuilder = useAiBuilder({
        aiUrl,
        aiMessageUrl,
        aiModelsUrl,
        aiMessagesUrl,
        builderData: page,
        insertSectionAfter,
        insertSectionAtEnd,
        insertSectionBefore,
        replaceSectionByIndex,
        selectedModel: null,
        setSelected,
        setRailTab,
        setEditTab,
        defaultModel: aiDefaultModel,
        enabledModels: aiEnabledModels,
        aiEnabled,
        mode,
    });

    const blankSectionFactory = (p) => {
        const nextName = `Section ${((Array.isArray(p.sections) ? p.sections.length : 0) + 1)}`;
        return {
            id: uid('sec'),
            name: nextName,
            style: defaultSectionStyle(mode),
            columns: [
                {
                    id: uid('col'),
                    name: 'Column 1',
                    spans: { base: 12, md: 12, lg: 12 },
                    widgets: [],
                },
            ],
        };
    };

    const sectionTemplateFactory = (templateId) => (p) => {
        const nextName = `Section ${((Array.isArray(p.sections) ? p.sections.length : 0) + 1)}`;

        if (templateId === 'two_columns') {
            return {
                id: uid('sec'),
                name: nextName,
                style: {
                    backgroundType: 'color',
                    backgroundColor: '#ffffff',
                    paddingY: 48,
                    gap: 24,
                    gapX: 24,
                    gapY: 24,
                    gapLinked: true,
                    itemsDirection: 'row',
                    itemsJustify: 'flex-start',
                    itemsAlign: 'stretch',
                    maxWidth: '6xl',
                },
                columns: [
                    {
                        id: uid('col'),
                        name: 'Column 1',
                        spans: { base: 12, md: 6, lg: 6 },
                        widgets: [
                            { id: uid('w'), type: 'heading', name: 'Heading', props: { text: 'Heading', level: 2, align: 'left' } },
                            { id: uid('w'), type: 'rich_text', name: 'Text', props: { html: '<p>Start writing...</p>', align: 'left' } },
                        ],
                    },
                    {
                        id: uid('col'),
                        name: 'Column 2',
                        spans: { base: 12, md: 6, lg: 6 },
                        widgets: [
                            { id: uid('w'), type: 'heading', name: 'Heading', props: { text: 'Heading', level: 3, align: 'left' } },
                            { id: uid('w'), type: 'rich_text', name: 'Text', props: { html: '<p>Start writing...</p>', align: 'left' } },
                        ],
                    },
                ],
            };
        }

        if (templateId === 'features_3') {
            return {
                id: uid('sec'),
                name: nextName,
                style: {
                    backgroundType: 'color',
                    backgroundColor: '#ffffff',
                    paddingY: 56,
                    gap: 24,
                    gapX: 24,
                    gapY: 24,
                    gapLinked: true,
                    itemsDirection: 'row',
                    itemsJustify: 'flex-start',
                    itemsAlign: 'stretch',
                    maxWidth: '6xl',
                },
                columns: [
                    {
                        id: uid('col'),
                        name: 'Column 1',
                        spans: { base: 12, md: 4, lg: 4 },
                        widgets: [
                            {
                                id: uid('w'),
                                type: 'icon',
                                name: 'Feature',
                                props: { heading: 'Feature', subtext: 'Describe the benefit.', align: 'center', iconPosition: 'top' },
                            },
                        ],
                    },
                    {
                        id: uid('col'),
                        name: 'Column 2',
                        spans: { base: 12, md: 4, lg: 4 },
                        widgets: [
                            {
                                id: uid('w'),
                                type: 'icon',
                                name: 'Feature',
                                props: { heading: 'Feature', subtext: 'Describe the benefit.', align: 'center', iconPosition: 'top' },
                            },
                        ],
                    },
                    {
                        id: uid('col'),
                        name: 'Column 3',
                        spans: { base: 12, md: 4, lg: 4 },
                        widgets: [
                            {
                                id: uid('w'),
                                type: 'icon',
                                name: 'Feature',
                                props: { heading: 'Feature', subtext: 'Describe the benefit.', align: 'center', iconPosition: 'top' },
                            },
                        ],
                    },
                ],
            };
        }

        if (templateId === 'cta') {
            return {
                id: uid('sec'),
                name: nextName,
                style: {
                    backgroundType: 'color',
                    backgroundColor: '#ffffff',
                    paddingY: 64,
                    gap: 24,
                    gapX: 24,
                    gapY: 24,
                    gapLinked: true,
                    itemsDirection: 'row',
                    itemsJustify: 'center',
                    itemsAlign: 'stretch',
                    maxWidth: '6xl',
                },
                columns: [
                    {
                        id: uid('col'),
                        name: 'Column 1',
                        spans: { base: 12, md: 12, lg: 12 },
                        widgets: [
                            { id: uid('w'), type: 'heading', name: 'Heading', props: { text: 'Call to action', level: 2, align: 'center' } },
                            { id: uid('w'), type: 'rich_text', name: 'Text', props: { html: '<p>Describe what you want the visitor to do next.</p>', align: 'center' } },
                            { id: uid('w'), type: 'button', name: 'Button', props: { text: 'Get Started', url: '#', align: 'center' } },
                        ],
                    },
                ],
            };
        }

        return {
            id: uid('sec'),
            name: nextName,
            style: {
                backgroundType: 'color',
                backgroundColor: '#ffffff',
                paddingY: 48,
                gap: 24,
                gapX: 24,
                gapY: 24,
                gapLinked: true,
                itemsDirection: 'row',
                itemsJustify: 'flex-start',
                itemsAlign: 'stretch',
                maxWidth: '6xl',
            },
            columns: [
                {
                    id: uid('col'),
                    name: 'Column 1',
                    spans: { base: 12, md: 12, lg: 12 },
                    widgets: [
                        { id: uid('w'), type: 'heading', name: 'Heading', props: { text: 'Heading', level: 2, align: 'left' } },
                        { id: uid('w'), type: 'rich_text', name: 'Text', props: { html: '<p>Start writing...</p>', align: 'left' } },
                        { id: uid('w'), type: 'button', name: 'Button', props: { text: 'Button', url: '#', align: 'left' } },
                    ],
                },
            ],
        };
    };

    const addWidgetToColumn = (sectionId, columnId, type, initialProps = {}, insertIndex = null) => {
        const defaults = getElementDefaults(type);
        const widget = normalizeWidget({ id: uid('w'), type, name: '', props: { ...defaults, ...initialProps } });
        if (!widget) return;

        setPage((p) => ({
            ...p,
            sections: p.sections.map((s) => {
                if (s.id !== sectionId) return s;
                return {
                    ...s,
                    columns: s.columns.map((c) => {
                        if (c.id !== columnId) return c;
                        const widgets = Array.isArray(c.widgets) ? c.widgets : [];
                        const idx = Number.isInteger(insertIndex) ? Math.max(0, Math.min(insertIndex, widgets.length)) : widgets.length;
                        const nextWidgets = [...widgets.slice(0, idx), widget, ...widgets.slice(idx)];
                        return { ...c, widgets: nextWidgets };
                    }),
                };
            }),
        }));

        setSelected({ kind: 'widget', sectionId, columnId, widgetId: widget.id });
        setQuickAdd(null);
        setRailTab('edit');
        setEditTab('content');
    };

    const addWidget = (type) => {
        if (!type) return;
        if (selectedModel.kind === 'none') return;

        if (selectedModel.kind === 'grid_column' || selectedModel.kind === 'grid_widget') {
            addWidgetToGridColumn(
                selectedModel.section.id,
                selectedModel.column.id,
                selectedModel.gridWidget.id,
                selectedModel.gridColumn.id,
                type
            );
            return;
        }

        if (selectedModel.kind === 'tab_item' || selectedModel.kind === 'tab_widget') {
            addWidgetToTabItem(
                selectedModel.section.id,
                selectedModel.column.id,
                selectedModel.tabWidget.id,
                selectedModel.tabItem.id,
                type
            );
            return;
        }

        if (selectedModel.kind === null) {
            const first = page.sections && page.sections[0] ? page.sections[0] : null;
            const firstCol = first && first.columns && first.columns[0] ? first.columns[0] : null;
            if (first && firstCol) {
                addWidgetToColumn(first.id, firstCol.id, type);
            }
            return;
        }

        const sectionId = selectedModel.section.id;
        const columnId = selectedModel.kind === 'section'
            ? selectedModel.section.columns[0]?.id
            : selectedModel.column.id;
        if (!columnId) return;

        addWidgetToColumn(sectionId, columnId, type);
    };

    const addGrid = (count = 2) => {
        const n = Math.max(1, Math.min(4, Math.round(Number(count) || 2)));

        if (selectedModel.kind === 'grid_column') {
            addWidgetToGridColumn(
                selectedModel.section.id,
                selectedModel.column.id,
                selectedModel.gridWidget.id,
                selectedModel.gridColumn.id,
                'grid',
                { count: n }
            );
            return;
        }

        if (selectedModel.kind === 'grid_widget') {
            addWidgetToGridColumn(
                selectedModel.section.id,
                selectedModel.column.id,
                selectedModel.gridWidget.id,
                selectedModel.gridColumn.id,
                'grid',
                { count: n }
            );
            return;
        }

        if (selectedModel.kind === 'column') {
            addWidgetToColumn(selectedModel.section.id, selectedModel.column.id, 'grid', { count: n });
            return;
        }

        if (selectedModel.kind === 'widget') {
            addWidgetToColumn(selectedModel.section.id, selectedModel.column.id, 'grid', { count: n });
            return;
        }

        if (selectedModel.kind === 'section') {
            const colId = selectedModel.section.columns[0]?.id;
            if (colId) addWidgetToColumn(selectedModel.section.id, colId, 'grid', { count: n });
            return;
        }

        addSection();
    };

    const addMobileMenu = () => {
        const init = { ...MOBILE_MENU_DEFAULTS, count: 1, mobileMenu: true };

        if (selectedModel.kind === 'grid_column') {
            addWidgetToGridColumn(selectedModel.section.id, selectedModel.column.id, selectedModel.gridWidget.id, selectedModel.gridColumn.id, 'grid', init);
            return;
        }
        if (selectedModel.kind === 'grid_widget') {
            addWidgetToGridColumn(selectedModel.section.id, selectedModel.column.id, selectedModel.gridWidget.id, selectedModel.gridColumn.id, 'grid', init);
            return;
        }
        if (selectedModel.kind === 'column') {
            addWidgetToColumn(selectedModel.section.id, selectedModel.column.id, 'grid', init);
            return;
        }
        if (selectedModel.kind === 'widget') {
            addWidgetToColumn(selectedModel.section.id, selectedModel.column.id, 'grid', init);
            return;
        }
        if (selectedModel.kind === 'section') {
            const colId = selectedModel.section.columns[0]?.id;
            if (colId) addWidgetToColumn(selectedModel.section.id, colId, 'grid', init);
            return;
        }
        addSection();
    };


    const widgetCatalog = useMemo(() => listPalette({ mode }), [mode]);

    const widgetLabelByType = useMemo(() => {
        const map = {};
        for (const it of widgetCatalog) {
            if (it && typeof it.type === 'string' && typeof it.label === 'string') {
                map[it.type] = it.label;
            }
        }
        return map;
    }, [widgetCatalog]);

    const widgetPalette = useMemo(() => {
        const q = query.trim().toLowerCase();
        if (!q) return widgetCatalog;
        return widgetCatalog.filter((x) => x.label.toLowerCase().includes(q) || x.type.toLowerCase().includes(q));
    }, [query, widgetCatalog]);

    const getWidgetDisplayName = (widget) => {
        if (!widget || typeof widget !== 'object') return '';
        const widgetName = (typeof widget.name === 'string') ? widget.name.trim() : '';
        if (widgetName !== '') return widgetName;
        const type = (typeof widget.type === 'string') ? widget.type : '';
        if (type && widgetLabelByType[type]) return widgetLabelByType[type];
        return humanizeWidgetType(type) || 'Widget';
    };

    const renderWidgetPreview = (w, ctx = {}) => {
        const isGridInner = Boolean(ctx.gridWidgetId && ctx.gridColumnId);

        if (
            w &&
            (w.type === 'heading' || w.type === 'rich_text' || w.type === 'image' || w.type === 'button' || w.type === 'button_group' || w.type === 'badge' || w.type === 'icon' || w.type === 'pricing_table' || w.type === 'faqs' || w.type === 'tabs' || w.type === 'nav_menu' || w.type === 'link' || w.type === 'divider' || w.type === 'spacer' || w.type === 'logo' || w.type === 'rating' || w.type === 'text_timeline' || w.type === 'social_icons' || w.type === 'social_proof' || w.type === 'newsletter_form' || w.type === 'login_form' || w.type === 'register_form' || w.type === 'copyright' || w.type === 'form_text' || w.type === 'form_email' || w.type === 'form_textarea' || w.type === 'form_select' || w.type === 'form_checkbox' || w.type === 'form_radio' || w.type === 'form_submit' || w.type === 'ai_prompt_input' || w.type === 'testimonials' || w.type === 'testimonial')
        ) {
            const elementPreview = renderElementPreview(w, {
                globals,
                typographyStyleFromProps,
                renderWidgetPreview,
                setSelected,
                setRailTab,
                selected,
                handleContextMenu,
                addWidgetToTabItem,
                moveWidgetAnywhere,
                getDragData,
                sectionId: ctx.sectionId,
                columnId: ctx.columnId,
                widgetId: w.id,
            });
            if (elementPreview) {
                return elementPreview;
            }
        }

        if (w.type === 'heading') {
            const text = (w.props && w.props.text) ? String(w.props.text) : 'Heading';
            const style = typographyStyleFromProps(w.props);
            const boxStyle = { ...(style || {}) };
            if (w.props && typeof w.props.color === 'string' && w.props.color.trim()) {
                boxStyle.color = w.props.color.trim();
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
                    const from = typeof w.props.gradientFrom === 'string' && w.props.gradientFrom.trim() ? w.props.gradientFrom.trim() : '#111827';
                    const to = typeof w.props.gradientTo === 'string' && w.props.gradientTo.trim() ? w.props.gradientTo.trim() : '#3b82f6';
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
                    if (typeof w.props.backgroundColor === 'string' && w.props.backgroundColor.trim()) {
                        boxStyle.backgroundColor = w.props.backgroundColor;
                    }
                }

                if (typeof w.props.borderStyle === 'string' && w.props.borderStyle && w.props.borderStyle !== 'default') {
                    boxStyle.borderStyle = w.props.borderStyle;
                }
                if (typeof w.props.borderColor === 'string' && w.props.borderColor.trim()) {
                    boxStyle.borderColor = w.props.borderColor;
                }
                if (typeof w.props.borderWidthTop === 'number' && Number.isFinite(w.props.borderWidthTop)) boxStyle.borderTopWidth = `${w.props.borderWidthTop}px`;
                if (typeof w.props.borderWidthRight === 'number' && Number.isFinite(w.props.borderWidthRight)) boxStyle.borderRightWidth = `${w.props.borderWidthRight}px`;
                if (typeof w.props.borderWidthBottom === 'number' && Number.isFinite(w.props.borderWidthBottom)) boxStyle.borderBottomWidth = `${w.props.borderWidthBottom}px`;
                if (typeof w.props.borderWidthLeft === 'number' && Number.isFinite(w.props.borderWidthLeft)) boxStyle.borderLeftWidth = `${w.props.borderWidthLeft}px`;
                {
                    const brT = cssLengthToCss(w.props.borderRadiusTop ?? w.props.borderRadius);
                    const brR = cssLengthToCss(w.props.borderRadiusRight ?? w.props.borderRadius);
                    const brB = cssLengthToCss(w.props.borderRadiusBottom ?? w.props.borderRadius);
                    const brL = cssLengthToCss(w.props.borderRadiusLeft ?? w.props.borderRadius);
                    if (brT) boxStyle.borderTopLeftRadius = brT;
                    if (brR) boxStyle.borderTopRightRadius = brR;
                    if (brB) boxStyle.borderBottomRightRadius = brB;
                    if (brL) boxStyle.borderBottomLeftRadius = brL;
                }
            }
            return (
                <div className="text-4xl font-semibold tracking-tight text-gray-900" style={boxStyle}>
                    {text || 'Heading'}
                </div>
            );
        }
        if (w.type === 'icon') {
            const iconUrl = (w.props && w.props.iconUrl) ? String(w.props.iconUrl) : '';
            const iconSvg = (w.props && w.props.iconSvg) ? String(w.props.iconSvg) : '';
            const heading = (w.props && w.props.heading) ? String(w.props.heading) : 'Heading';
            const subtext = (w.props && w.props.subtext) ? String(w.props.subtext) : 'Subtext';
            const iconPosition = (w.props && w.props.iconPosition) ? String(w.props.iconPosition) : 'top';
            const iconSize = (w.props && Number.isFinite(Number(w.props.iconSize))) ? Number(w.props.iconSize) : 64;
            const iconGap = (w.props && Number.isFinite(Number(w.props.iconGap))) ? Number(w.props.iconGap) : 16;
            const align = (w.props && w.props.align) ? String(w.props.align) : 'center';

            const containerStyle = {};
            const iconContainerStyle = {};
            const headingStyle = {};
            const subtextStyle = {};

            const alignClasses = {
                left: 'text-left',
                center: 'text-center',
                right: 'text-right',
            };

            if (w.props?.iconBackgroundColor) iconContainerStyle.backgroundColor = w.props.iconBackgroundColor;
            if (w.props?.iconColor) iconContainerStyle.color = w.props.iconColor;

            if (w.props?.iconPaddingTop !== undefined) iconContainerStyle.paddingTop = `${w.props.iconPaddingTop}px`;
            if (w.props?.iconPaddingRight !== undefined) iconContainerStyle.paddingRight = `${w.props.iconPaddingRight}px`;
            if (w.props?.iconPaddingBottom !== undefined) iconContainerStyle.paddingBottom = `${w.props.iconPaddingBottom}px`;
            if (w.props?.iconPaddingLeft !== undefined) iconContainerStyle.paddingLeft = `${w.props.iconPaddingLeft}px`;

            if (w.props?.iconBorderRadiusTop !== undefined) iconContainerStyle.borderTopLeftRadius = `${w.props.iconBorderRadiusTop}px`;
            if (w.props?.iconBorderRadiusRight !== undefined) iconContainerStyle.borderTopRightRadius = `${w.props.iconBorderRadiusRight}px`;
            if (w.props?.iconBorderRadiusBottom !== undefined) iconContainerStyle.borderBottomRightRadius = `${w.props.iconBorderRadiusBottom}px`;
            if (w.props?.iconBorderRadiusLeft !== undefined) iconContainerStyle.borderBottomLeftRadius = `${w.props.iconBorderRadiusLeft}px`;
            if (w.props?.iconBorderRadius !== undefined) iconContainerStyle.borderRadius = `${w.props.iconBorderRadius}px`;

            const hasIconBorder = w.props?.iconBorderWidthTop || w.props?.iconBorderWidthRight || w.props?.iconBorderWidthBottom || w.props?.iconBorderWidthLeft;
            if (hasIconBorder) {
                iconContainerStyle.borderStyle = 'solid';
                if (w.props?.iconBorderWidthTop !== undefined) iconContainerStyle.borderTopWidth = `${w.props.iconBorderWidthTop}px`;
                if (w.props?.iconBorderWidthRight !== undefined) iconContainerStyle.borderRightWidth = `${w.props.iconBorderWidthRight}px`;
                if (w.props?.iconBorderWidthBottom !== undefined) iconContainerStyle.borderBottomWidth = `${w.props.iconBorderWidthBottom}px`;
                if (w.props?.iconBorderWidthLeft !== undefined) iconContainerStyle.borderLeftWidth = `${w.props.iconBorderWidthLeft}px`;
                iconContainerStyle.borderColor = w.props?.iconBorderColor ? w.props.iconBorderColor : '#e5e7eb';
            }

            if (w.props?.shadowColor && (w.props?.shadowBlur !== undefined || w.props?.shadowX !== undefined || w.props?.shadowY !== undefined)) {
                const x = w.props.shadowX ?? 0;
                const y = w.props.shadowY ?? 0;
                const blur = w.props.shadowBlur ?? 0;
                iconContainerStyle.filter = `drop-shadow(${x}px ${y}px ${blur}px ${w.props.shadowColor})`;
            }

            iconContainerStyle.display = 'inline-flex';
            iconContainerStyle.alignItems = 'center';
            iconContainerStyle.justifyContent = 'center';
            iconContainerStyle.lineHeight = 0;

            if (w.props?.headingColor) headingStyle.color = w.props.headingColor;
            if (w.props?.headingFontFamily) headingStyle.fontFamily = w.props.headingFontFamily;
            if (w.props?.headingFontSize !== undefined) headingStyle.fontSize = `${w.props.headingFontSize}px`;
            if (w.props?.headingFontWeight) headingStyle.fontWeight = w.props.headingFontWeight;
            if (w.props?.headingLineHeight !== undefined) headingStyle.lineHeight = w.props.headingLineHeight;
            if (w.props?.headingLetterSpacing !== undefined) headingStyle.letterSpacing = `${w.props.headingLetterSpacing}px`;
            if (w.props?.headingMarginTop !== undefined) headingStyle.marginTop = `${w.props.headingMarginTop}px`;
            if (w.props?.headingMarginRight !== undefined) headingStyle.marginRight = `${w.props.headingMarginRight}px`;
            if (w.props?.headingMarginBottom !== undefined) headingStyle.marginBottom = `${w.props.headingMarginBottom}px`;
            if (w.props?.headingMarginLeft !== undefined) headingStyle.marginLeft = `${w.props.headingMarginLeft}px`;

            if (w.props?.subtextColor) subtextStyle.color = w.props.subtextColor;
            if (w.props?.subtextFontFamily) subtextStyle.fontFamily = w.props.subtextFontFamily;
            if (w.props?.subtextFontSize !== undefined) subtextStyle.fontSize = `${w.props.subtextFontSize}px`;
            if (w.props?.subtextFontWeight) subtextStyle.fontWeight = w.props.subtextFontWeight;
            if (w.props?.subtextLineHeight !== undefined) subtextStyle.lineHeight = w.props.subtextLineHeight;
            if (w.props?.subtextLetterSpacing !== undefined) subtextStyle.letterSpacing = `${w.props.subtextLetterSpacing}px`;
            if (w.props?.subtextMarginTop !== undefined) subtextStyle.marginTop = `${w.props.subtextMarginTop}px`;
            if (w.props?.subtextMarginRight !== undefined) subtextStyle.marginRight = `${w.props.subtextMarginRight}px`;
            if (w.props?.subtextMarginBottom !== undefined) subtextStyle.marginBottom = `${w.props.subtextMarginBottom}px`;
            if (w.props?.subtextMarginLeft !== undefined) subtextStyle.marginLeft = `${w.props.subtextMarginLeft}px`;

            if (w.props?.backgroundColor) containerStyle.backgroundColor = w.props.backgroundColor;

            const pTop = w.props?.paddingTop;
            const pRight = w.props?.paddingRight;
            const pBottom = w.props?.paddingBottom;
            const pLeft = w.props?.paddingLeft;
            if (pTop !== undefined) containerStyle.paddingTop = `${pTop}px`;
            if (pRight !== undefined) containerStyle.paddingRight = `${pRight}px`;
            if (pBottom !== undefined) containerStyle.paddingBottom = `${pBottom}px`;
            if (pLeft !== undefined) containerStyle.paddingLeft = `${pLeft}px`;

            const brTop = w.props?.borderRadiusTop;
            const brRight = w.props?.borderRadiusRight;
            const brBottom = w.props?.borderRadiusBottom;
            const brLeft = w.props?.borderRadiusLeft;
            if (brTop !== undefined) containerStyle.borderTopLeftRadius = `${brTop}px`;
            if (brRight !== undefined) containerStyle.borderTopRightRadius = `${brRight}px`;
            if (brBottom !== undefined) containerStyle.borderBottomRightRadius = `${brBottom}px`;
            if (brLeft !== undefined) containerStyle.borderBottomLeftRadius = `${brLeft}px`;
            if (w.props?.borderRadius !== undefined) containerStyle.borderRadius = `${w.props.borderRadius}px`;

            const mTop = w.props?.marginTop;
            const mRight = w.props?.marginRight;
            const mBottom = w.props?.marginBottom;
            const mLeft = w.props?.marginLeft;
            if (mTop !== undefined) containerStyle.marginTop = `${mTop}px`;
            if (mRight !== undefined) containerStyle.marginRight = `${mRight}px`;
            if (mBottom !== undefined) containerStyle.marginBottom = `${mBottom}px`;
            if (mLeft !== undefined) containerStyle.marginLeft = `${mLeft}px`;

            const iconElement = iconUrl ? (
                <div style={iconContainerStyle}>
                    <img src={iconUrl} alt="" style={{ width: `${iconSize}px`, height: `${iconSize}px`, objectFit: 'contain' }} />
                </div>
            ) : iconSvg ? (
                <div style={iconContainerStyle} dangerouslySetInnerHTML={{ __html: iconSvg }} />
            ) : (
                <div
                    style={{
                        ...iconContainerStyle,
                        width: `${iconSize}px`,
                        height: `${iconSize}px`,
                        backgroundColor: iconContainerStyle.backgroundColor ?? '#e5e7eb',
                        borderRadius: iconContainerStyle.borderRadius ?? '8px',
                    }}
                    className="flex items-center justify-center text-gray-400"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        aria-hidden="true"
                        style={{ width: '60%', height: '60%' }}
                    >
                        <circle cx="12" cy="13" r="8" />
                        <path d="M12 9v4l2 2" />
                        <path d="M5 3 2 6" />
                        <path d="m22 6-3-3" />
                        <path d="M6.38 18.7 4 21" />
                        <path d="M17.64 18.67 20 21" />
                    </svg>
                </div>
            );

            const textContent = (
                <div>
                    <div className="font-semibold text-lg" style={headingStyle}>{heading}</div>
                    <div className="text-sm text-gray-600" style={subtextStyle}>{subtext}</div>
                </div>
            );

            if (iconPosition === 'top') {
                return (
                    <div className={alignClasses[align] || 'text-center'} style={containerStyle}>
                        <div style={{ display: 'flex', flexDirection: 'column', alignItems: align === 'left' ? 'flex-start' : align === 'right' ? 'flex-end' : 'center', gap: `${iconGap}px` }}>
                            {iconElement}
                            {textContent}
                        </div>
                    </div>
                );
            }

            if (iconPosition === 'left') {
                return (
                    <div className={alignClasses[align] || 'text-center'} style={containerStyle}>
                        <div style={{ display: 'flex', flexDirection: 'row', alignItems: 'center', gap: `${iconGap}px`, justifyContent: align === 'left' ? 'flex-start' : align === 'right' ? 'flex-end' : 'center' }}>
                            {iconElement}
                            {textContent}
                        </div>
                    </div>
                );
            }

            if (iconPosition === 'right') {
                return (
                    <div className={alignClasses[align] || 'text-center'} style={containerStyle}>
                        <div style={{ display: 'flex', flexDirection: 'row', alignItems: 'center', gap: `${iconGap}px`, justifyContent: align === 'left' ? 'flex-start' : align === 'right' ? 'flex-end' : 'center' }}>
                            {textContent}
                            {iconElement}
                        </div>
                    </div>
                );
            }
        }
        if (w.type === 'rich_text') {
            const html = (w.props && typeof w.props.html === 'string') ? w.props.html : '<p></p>';
            const style = typographyStyleFromProps(w.props);
            const boxStyle = { ...(style || {}) };
            if (w.props && typeof w.props.color === 'string' && w.props.color.trim()) {
                boxStyle.color = w.props.color.trim();
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
                    const from = typeof w.props.gradientFrom === 'string' && w.props.gradientFrom.trim() ? w.props.gradientFrom.trim() : '#111827';
                    const to = typeof w.props.gradientTo === 'string' && w.props.gradientTo.trim() ? w.props.gradientTo.trim() : '#3b82f6';
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
                    if (typeof w.props.backgroundColor === 'string' && w.props.backgroundColor.trim()) {
                        boxStyle.backgroundColor = w.props.backgroundColor;
                    }
                }

                if (typeof w.props.borderStyle === 'string' && w.props.borderStyle && w.props.borderStyle !== 'default') {
                    boxStyle.borderStyle = w.props.borderStyle;
                }
                if (typeof w.props.borderColor === 'string' && w.props.borderColor.trim()) {
                    boxStyle.borderColor = w.props.borderColor;
                }
                if (typeof w.props.borderWidthTop === 'number' && Number.isFinite(w.props.borderWidthTop)) boxStyle.borderTopWidth = `${w.props.borderWidthTop}px`;
                if (typeof w.props.borderWidthRight === 'number' && Number.isFinite(w.props.borderWidthRight)) boxStyle.borderRightWidth = `${w.props.borderWidthRight}px`;
                if (typeof w.props.borderWidthBottom === 'number' && Number.isFinite(w.props.borderWidthBottom)) boxStyle.borderBottomWidth = `${w.props.borderWidthBottom}px`;
                if (typeof w.props.borderWidthLeft === 'number' && Number.isFinite(w.props.borderWidthLeft)) boxStyle.borderLeftWidth = `${w.props.borderWidthLeft}px`;
                {
                    const brT = cssLengthToCss(w.props.borderRadiusTop ?? w.props.borderRadius);
                    const brR = cssLengthToCss(w.props.borderRadiusRight ?? w.props.borderRadius);
                    const brB = cssLengthToCss(w.props.borderRadiusBottom ?? w.props.borderRadius);
                    const brL = cssLengthToCss(w.props.borderRadiusLeft ?? w.props.borderRadius);
                    if (brT) boxStyle.borderTopLeftRadius = brT;
                    if (brR) boxStyle.borderTopRightRadius = brR;
                    if (brB) boxStyle.borderBottomRightRadius = brB;
                    if (brL) boxStyle.borderBottomLeftRadius = brL;
                }
            }

            return <div className="prose max-w-none" style={boxStyle} dangerouslySetInnerHTML={{ __html: html }} />;
        }
        if (w.type === 'image') {
            const url = (w.props && w.props.url) ? String(w.props.url) : '';
            const svgMarkup = (w.props && w.props.svg) ? String(w.props.svg) : '';
            const useSvg = svgMarkup !== '' && (w.props && w.props.source === 'svg' || !url);
            const imgStyle = {};
            if (w.props) {
                const widthCss = resolveUnit(w.props.width, 'px');
                const heightCss = resolveUnit(w.props.height, 'px');
                if (widthCss) imgStyle.width = widthCss;
                if (heightCss) imgStyle.height = heightCss;
                if (['cover', 'contain', 'fill', 'none', 'scale-down'].includes(w.props.objectFit)) imgStyle.objectFit = w.props.objectFit;
            }
            if (useSvg) {
                return (
                    <div
                        className="max-w-full rounded-xl"
                        style={Object.keys(imgStyle).length ? { ...imgStyle, height: 'auto' } : undefined}
                        dangerouslySetInnerHTML={{ __html: svgWithWidth(svgMarkup, imgStyle.width, '100%') }}
                    />
                );
            }
            return url ? (
                <img
                    src={url}
                    alt=""
                    className="max-w-full h-auto rounded-xl"
                    style={Object.keys(imgStyle).length ? imgStyle : undefined}
                />
            ) : (
                <div
                    className="rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 text-gray-300 flex items-center justify-center"
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
            );
        }
        if (w.type === 'button') {
            const text = (w.props && w.props.text) ? String(w.props.text) : 'Click Me';
            const buttonStyle = (w.props && w.props.style) ? String(w.props.style) : 'primary';
            const size = (w.props && w.props.size) ? String(w.props.size) : 'md';
            const fullWidth = (w.props && w.props.fullWidth) ? true : false;
            const align = (w.props && w.props.align) ? String(w.props.align) : 'left';

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
                if (typeof w.props.fontSize === 'number' && Number.isFinite(w.props.fontSize)) inlineStyle.fontSize = `${w.props.fontSize}px`;
                if (typeof w.props.fontWeight === 'string' && w.props.fontWeight.trim()) inlineStyle.fontWeight = w.props.fontWeight;
                if (typeof w.props.lineHeight === 'number' && Number.isFinite(w.props.lineHeight)) inlineStyle.lineHeight = `${w.props.lineHeight}px`;
                if (typeof w.props.letterSpacing === 'number' && Number.isFinite(w.props.letterSpacing)) inlineStyle.letterSpacing = `${w.props.letterSpacing}px`;
                if (typeof w.props.wordSpacing === 'number' && Number.isFinite(w.props.wordSpacing)) inlineStyle.wordSpacing = `${w.props.wordSpacing}px`;

                if (typeof w.props.paddingTop === 'number' && Number.isFinite(w.props.paddingTop)) inlineStyle.paddingTop = `${w.props.paddingTop}px`;
                if (typeof w.props.paddingRight === 'number' && Number.isFinite(w.props.paddingRight)) inlineStyle.paddingRight = `${w.props.paddingRight}px`;
                if (typeof w.props.paddingBottom === 'number' && Number.isFinite(w.props.paddingBottom)) inlineStyle.paddingBottom = `${w.props.paddingBottom}px`;
                if (typeof w.props.paddingLeft === 'number' && Number.isFinite(w.props.paddingLeft)) inlineStyle.paddingLeft = `${w.props.paddingLeft}px`;

                if (!fullWidth && typeof w.props.width === 'number' && Number.isFinite(w.props.width)) inlineStyle.width = `${w.props.width}px`;
                if (typeof w.props.height === 'number' && Number.isFinite(w.props.height)) inlineStyle.height = `${w.props.height}px`;

                if (typeof w.props.textColor === 'string' && w.props.textColor.trim()) {
                    inlineStyle.color = w.props.textColor;
                }

                if (typeof w.props.marginTop === 'number' && Number.isFinite(w.props.marginTop)) wrapperStyle.marginTop = `${w.props.marginTop}px`;
                if (typeof w.props.marginRight === 'number' && Number.isFinite(w.props.marginRight)) wrapperStyle.marginRight = `${w.props.marginRight}px`;
                if (typeof w.props.marginBottom === 'number' && Number.isFinite(w.props.marginBottom)) wrapperStyle.marginBottom = `${w.props.marginBottom}px`;
                if (typeof w.props.marginLeft === 'number' && Number.isFinite(w.props.marginLeft)) wrapperStyle.marginLeft = `${w.props.marginLeft}px`;

                const bgType = typeof w.props.backgroundType === 'string' ? w.props.backgroundType : 'classic';
                if (bgType === 'gradient') {
                    const from = typeof w.props.gradientFrom === 'string' && w.props.gradientFrom.trim() ? w.props.gradientFrom.trim() : '#111827';
                    const to = typeof w.props.gradientTo === 'string' && w.props.gradientTo.trim() ? w.props.gradientTo.trim() : '#3b82f6';
                    const fromLoc = typeof w.props.gradientFromLocation === 'number' && Number.isFinite(w.props.gradientFromLocation) ? w.props.gradientFromLocation : 0;
                    const toLoc = typeof w.props.gradientToLocation === 'number' && Number.isFinite(w.props.gradientToLocation) ? w.props.gradientToLocation : 100;
                    const type = typeof w.props.gradientType === 'string' ? w.props.gradientType : 'linear';
                    const position = typeof w.props.gradientPosition === 'string' && w.props.gradientPosition.trim()
                        ? w.props.gradientPosition.trim()
                        : 'top center';

                    if (type === 'radial') {
                        inlineStyle.backgroundImage = `radial-gradient(at ${position}, ${from} ${fromLoc}%, ${to} ${toLoc}%)`;
                    } else {
                        inlineStyle.backgroundImage = `linear-gradient(135deg, ${from} ${fromLoc}%, ${to} ${toLoc}%)`;
                    }
                } else {
                    if (typeof w.props.backgroundColor === 'string' && w.props.backgroundColor.trim()) {
                        inlineStyle.backgroundColor = w.props.backgroundColor;
                    }
                }

                if (typeof w.props.borderStyle === 'string' && w.props.borderStyle && w.props.borderStyle !== 'default') {
                    inlineStyle.borderStyle = w.props.borderStyle;
                }
                if (typeof w.props.borderColor === 'string' && w.props.borderColor.trim()) {
                    inlineStyle.borderColor = w.props.borderColor;
                }
                if (typeof w.props.borderWidthTop === 'number' && Number.isFinite(w.props.borderWidthTop)) inlineStyle.borderTopWidth = `${w.props.borderWidthTop}px`;
                if (typeof w.props.borderWidthRight === 'number' && Number.isFinite(w.props.borderWidthRight)) inlineStyle.borderRightWidth = `${w.props.borderWidthRight}px`;
                if (typeof w.props.borderWidthBottom === 'number' && Number.isFinite(w.props.borderWidthBottom)) inlineStyle.borderBottomWidth = `${w.props.borderWidthBottom}px`;
                if (typeof w.props.borderWidthLeft === 'number' && Number.isFinite(w.props.borderWidthLeft)) inlineStyle.borderLeftWidth = `${w.props.borderWidthLeft}px`;

                {
                    const brT = cssLengthToCss(w.props.borderRadiusTop ?? w.props.borderRadius);
                    const brR = cssLengthToCss(w.props.borderRadiusRight ?? w.props.borderRadius);
                    const brB = cssLengthToCss(w.props.borderRadiusBottom ?? w.props.borderRadius);
                    const brL = cssLengthToCss(w.props.borderRadiusLeft ?? w.props.borderRadius);
                    if (brT) inlineStyle.borderTopLeftRadius = brT;
                    if (brR) inlineStyle.borderTopRightRadius = brR;
                    if (brB) inlineStyle.borderBottomRightRadius = brB;
                    if (brL) inlineStyle.borderBottomLeftRadius = brL;
                }
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

            return (
                <div className={alignClasses[align] || 'text-left'} style={Object.keys(wrapperStyle).length ? wrapperStyle : undefined}>
                    <button
                        type="button"
                        className={`inline-flex items-center justify-center font-semibold rounded-lg border transition-colors ${sizeClasses[size] || sizeClasses.md} ${styleClasses[buttonStyle] || styleClasses.primary} ${fullWidth ? 'w-full' : ''}`}
                        style={Object.keys(inlineStyle).length ? inlineStyle : undefined}
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

                            if (typeof w.props.hoverTextColor === 'string' && w.props.hoverTextColor.trim()) {
                                el.style.color = w.props.hoverTextColor;
                            }
                            const hoverBgType = typeof w.props.hoverBackgroundType === 'string' ? w.props.hoverBackgroundType : 'classic';
                            if (hoverBgType === 'gradient') {
                                const from = typeof w.props.hoverGradientFrom === 'string' && w.props.hoverGradientFrom.trim() ? w.props.hoverGradientFrom.trim() : '#111827';
                                const to = typeof w.props.hoverGradientTo === 'string' && w.props.hoverGradientTo.trim() ? w.props.hoverGradientTo.trim() : '#3b82f6';
                                const fromLoc = typeof w.props.hoverGradientFromLocation === 'number' && Number.isFinite(w.props.hoverGradientFromLocation) ? w.props.hoverGradientFromLocation : 0;
                                const toLoc = typeof w.props.hoverGradientToLocation === 'number' && Number.isFinite(w.props.hoverGradientToLocation) ? w.props.hoverGradientToLocation : 100;
                                const type = typeof w.props.hoverGradientType === 'string' ? w.props.hoverGradientType : 'linear';
                                const position = typeof w.props.hoverGradientPosition === 'string' && w.props.hoverGradientPosition.trim()
                                    ? w.props.hoverGradientPosition.trim()
                                    : 'top center';

                                el.style.backgroundColor = '';
                                if (type === 'radial') {
                                    el.style.backgroundImage = `radial-gradient(at ${position}, ${from} ${fromLoc}%, ${to} ${toLoc}%)`;
                                } else {
                                    el.style.backgroundImage = `linear-gradient(135deg, ${from} ${fromLoc}%, ${to} ${toLoc}%)`;
                                }
                            } else if (typeof w.props.hoverBackgroundColor === 'string' && w.props.hoverBackgroundColor.trim()) {
                                el.style.backgroundImage = '';
                                el.style.backgroundColor = w.props.hoverBackgroundColor;
                            }
                        }}
                        onMouseLeave={(e) => {
                            const el = e.currentTarget;
                            if (typeof document !== 'undefined' && document.activeElement === el) return;
                            const origColor = el.dataset.mpOrigColor;
                            const origBg = el.dataset.mpOrigBg;
                            const origBgImg = el.dataset.mpOrigBgImg;

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

                            if (typeof w.props.focusTextColor === 'string' && w.props.focusTextColor.trim()) {
                                el.style.color = w.props.focusTextColor;
                            }

                            const focusBgType = typeof w.props.focusBackgroundType === 'string' ? w.props.focusBackgroundType : 'classic';
                            if (focusBgType === 'gradient') {
                                const from = typeof w.props.focusGradientFrom === 'string' && w.props.focusGradientFrom.trim() ? w.props.focusGradientFrom.trim() : '#111827';
                                const to = typeof w.props.focusGradientTo === 'string' && w.props.focusGradientTo.trim() ? w.props.focusGradientTo.trim() : '#3b82f6';
                                const fromLoc = typeof w.props.focusGradientFromLocation === 'number' && Number.isFinite(w.props.focusGradientFromLocation) ? w.props.focusGradientFromLocation : 0;
                                const toLoc = typeof w.props.focusGradientToLocation === 'number' && Number.isFinite(w.props.focusGradientToLocation) ? w.props.focusGradientToLocation : 100;
                                const type = typeof w.props.focusGradientType === 'string' ? w.props.focusGradientType : 'linear';
                                const position = typeof w.props.focusGradientPosition === 'string' && w.props.focusGradientPosition.trim()
                                    ? w.props.focusGradientPosition.trim()
                                    : 'top center';

                                el.style.backgroundColor = '';
                                if (type === 'radial') {
                                    el.style.backgroundImage = `radial-gradient(at ${position}, ${from} ${fromLoc}%, ${to} ${toLoc}%)`;
                                } else {
                                    el.style.backgroundImage = `linear-gradient(135deg, ${from} ${fromLoc}%, ${to} ${toLoc}%)`;
                                }
                            } else if (typeof w.props.focusBackgroundColor === 'string' && w.props.focusBackgroundColor.trim()) {
                                el.style.backgroundImage = '';
                                el.style.backgroundColor = w.props.focusBackgroundColor;
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
                        {text}
                    </button>
                </div>
            );
        }
        if (w.type === 'slider') {
            const images = w.props && Array.isArray(w.props.images) ? w.props.images : [];
            const sliderStyle = {};
            if (w.props) {
                if (typeof w.props.width === 'number' && Number.isFinite(w.props.width)) sliderStyle.width = `${w.props.width}px`;
                if (typeof w.props.height === 'number' && Number.isFinite(w.props.height)) sliderStyle.height = `${w.props.height}px`;
            }

            const height = w.props && typeof w.props.height === 'number' && Number.isFinite(w.props.height) ? w.props.height : 360;
            const safeHeight = Math.max(120, Math.min(900, height));
            const perPageRaw = w.props && Number.isFinite(Number(w.props.carouselPerPage)) ? Number(w.props.carouselPerPage) : 1;
            const perPage = Math.max(1, Math.min(6, Math.round(perPageRaw)));
            const gapRaw = w.props && Number.isFinite(Number(w.props.carouselGap)) ? Number(w.props.carouselGap) : 16;
            const gap = Math.max(0, Math.min(120, Math.round(gapRaw)));
            const autoplay = !(w.props && w.props.carouselAutoplay === false);
            const arrows = !(w.props && w.props.carouselArrows === false);
            const intervalRaw = w.props && Number.isFinite(Number(w.props.carouselInterval)) ? Number(w.props.carouselInterval) : 4;
            const intervalSeconds = Math.max(1, Math.min(60, intervalRaw));
            const ease = w.props && typeof w.props.carouselEase === 'string' ? w.props.carouselEase : 'ease';
            const safeImages = images
                .filter((i) => i && typeof i === 'object')
                .map((i) => ({ url: String(i.url || '') }))
                .filter((i) => i.url);

            const arrowSizeRaw = w.props && Number.isFinite(Number(w.props.arrowSize)) ? Number(w.props.arrowSize) : 36;
            const arrowSize = Math.max(24, Math.min(72, Math.round(arrowSizeRaw)));
            const arrowRadiusRaw = w.props && Number.isFinite(Number(w.props.arrowRadius)) ? Number(w.props.arrowRadius) : 999;
            const arrowRadius = Math.max(0, Math.min(999, Math.round(arrowRadiusRaw)));
            const arrowBgColor = w.props && typeof w.props.arrowBgColor === 'string' ? w.props.arrowBgColor : '';
            const arrowColor = w.props && typeof w.props.arrowColor === 'string' ? w.props.arrowColor : '';
            const arrowBorderColor = w.props && typeof w.props.arrowBorderColor === 'string' ? w.props.arrowBorderColor : '';

            const wrapStyle = {
                ...(Object.keys(sliderStyle).length ? sliderStyle : {}),
            };
            if (wrapStyle.height === undefined) {
                wrapStyle.height = `${safeHeight}px`;
            }

            const sliderVars = {
                '--mp-slider-gap': `${gap}px`,
                '--mp-slider-per-page': String(perPage),
                '--mp-slider-arrow-size': `${arrowSize}px`,
                '--mp-slider-arrow-radius': `${arrowRadius}px`,
                ...(arrowBgColor ? { '--mp-slider-arrow-bg': arrowBgColor } : {}),
                ...(arrowColor ? { '--mp-slider-arrow-color': arrowColor } : {}),
                ...(arrowBorderColor ? { '--mp-slider-arrow-border': arrowBorderColor } : {}),
            };

            const sliderRootStyle = {
                ...sliderVars,
                position: 'relative',
            };

            const arrowBtnBase = {
                position: 'absolute',
                top: '50%',
                transform: 'translateY(-50%)',
                zIndex: '5',
                display: 'inline-flex',
                alignItems: 'center',
                justifyContent: 'center',
                border: '1px solid var(--mp-slider-arrow-border, rgba(0,0,0,0.12))',
                cursor: 'pointer',
                userSelect: 'none',
                width: 'var(--mp-slider-arrow-size, 36px)',
                height: 'var(--mp-slider-arrow-size, 36px)',
                borderRadius: 'var(--mp-slider-arrow-radius, 999px)',
                background: 'var(--mp-slider-arrow-bg, rgba(255,255,255,0.92))',
                color: 'var(--mp-slider-arrow-color, #111827)',
            };

            return (
                <div style={wrapStyle}>
                    {safeImages.length ? (
                        <div
                            className="mp-slider"
                            data-height={String(safeHeight)}
                            data-mp-slider-per-page={String(perPage)}
                            data-mp-slider-interval={String(intervalSeconds)}
                            data-mp-slider-ease={String(ease || 'ease')}
                            data-mp-slider-autoplay={autoplay ? undefined : '0'}
                            data-mp-slider-arrows={arrows ? undefined : '0'}
                            style={sliderRootStyle}
                        >
                            <div className="mp-slider__track">
                                {safeImages.map((img, idx) => (
                                    <div key={idx}>
                                        <img
                                            src={img.url}
                                            alt=""
                                            style={{ height: `${safeHeight}px`, width: '100%', objectFit: 'cover' }}
                                        />
                                    </div>
                                ))}
                            </div>

                            {arrows ? (
                                <>
                                    <button
                                        type="button"
                                        data-mp-slider-prev=""
                                        aria-label="Previous"
                                        style={{ ...arrowBtnBase, left: '12px' }}
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="18"
                                            height="18"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            strokeWidth="2"
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                        >
                                            <path d="m15 18-6-6 6-6" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        data-mp-slider-next=""
                                        aria-label="Next"
                                        style={{ ...arrowBtnBase, right: '12px' }}
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="18"
                                            height="18"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            strokeWidth="2"
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                        >
                                            <path d="m9 18 6-6-6-6" />
                                        </svg>
                                    </button>
                                </>
                            ) : null}
                        </div>
                    ) : (
                        <div className="rounded-xl border border-dashed border-gray-200 p-4 text-sm text-gray-500">
                            Slider (no images)
                        </div>
                    )}
                </div>
            );
        }
        if (w.type === 'grid') {
            const countRaw = Number(w.props && w.props.count);
            const count = Math.max(1, Math.min(4, Number.isFinite(countRaw) ? Math.round(countRaw) : 2));

            // Get style properties
            const style = w.style || {};
            const gridDirection = pickResponsive(style.itemsDirection, style.itemsDirectionTablet, style.itemsDirectionMobile);
            const direction = ['row', 'column', 'row-reverse', 'column-reverse'].includes(gridDirection) ? gridDirection : 'row';
            const justify = ['flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'].includes(style.itemsJustify)
                ? style.itemsJustify
                : 'flex-start';
            const align = ['stretch', 'flex-start', 'center', 'flex-end', 'baseline'].includes(style.itemsAlign) ? style.itemsAlign : 'stretch';

            const fallbackGap = Number.isFinite(Number(style.gap)) ? Math.max(0, Number(style.gap)) : 0;
            const gapX = Number.isFinite(Number(style.gapX)) ? Math.max(0, Number(style.gapX)) : fallbackGap;
            const gapY = Number.isFinite(Number(style.gapY)) ? Math.max(0, Number(style.gapY)) : fallbackGap;

            // "Columns per row" wraps the (fixed) grid columns into rows without
            // removing any of them.
            const columnsPerRowRaw = Number(pickResponsive(style.columnsPerRow, style.columnsPerRowTablet, style.columnsPerRowMobile));
            const wrapPerRow = Number.isFinite(columnsPerRowRaw) && columnsPerRowRaw >= 1
                ? Math.max(1, Math.min(6, Math.round(columnsPerRowRaw)))
                : 0;

            const gridKey = `${ctx.sectionId}:${ctx.columnId}:${w.id}`;

            const isGridActive =
                (selected.kind === 'widget' &&
                    selected.sectionId === ctx.sectionId &&
                    selected.columnId === ctx.columnId &&
                    selected.widgetId === w.id) ||
                ((selected.kind === 'grid_column' || selected.kind === 'grid_widget') &&
                    selected.sectionId === ctx.sectionId &&
                    selected.columnId === ctx.columnId &&
                    selected.gridWidgetId === w.id);

            const isGridHovered = hoveredGridWidgetKey === gridKey;

            const cols = w.props && Array.isArray(w.props.columns) ? w.props.columns : [];
            const visibleCols = cols.slice(0, count);

            const spanForViewportInner = (c) => {
                if (!c || !c.spans) return 12;
                if (viewport === 'desktop') return Number(c.spans.lg || c.spans.md || c.spans.base || 12);
                if (viewport === 'tablet') {
                    const v = Number(c.spans.md || c.spans.base || 12);
                    return Number.isFinite(v) && v > 0 ? Math.max(6, Math.min(12, v)) : 12;
                }
                return 12;
            };

            const gridContent = (
                <GridPricingScope widget={w}>
                <div
                    className={`border rounded-none relative transition-colors ${
                        isGridHovered
                            ? 'border-blue-300'
                            : isGridActive
                                ? 'border-gray-300'
                                : 'border-transparent'
                    }`}
                    onPointerEnter={() => setHoveredGridWidgetKey(gridKey)}
                    onPointerLeave={() => {
                        setHoveredGridWidgetKey((curr) => (curr === gridKey ? null : curr));
                        setHoveredGridColumnKey((curr) => (typeof curr === 'string' && curr.startsWith(`${gridKey}:`) ? null : curr));
                    }}
                >
                    {isGridHovered && !(selected.kind === 'grid_column' && selected.gridWidgetId === w.id) ? (
                        <div className="absolute z-30" style={{ top: -24, left: '50%', transform: 'translate(-50%, 0)' }}>
                            <div className="flex items-center bg-white border border-gray-200 shadow">
                                <button
                                    type="button"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        const firstGcId = visibleCols && visibleCols[0] ? visibleCols[0].id : null;
                                        if (!firstGcId) return;
                                        setSelected({
                                            kind: 'grid_column',
                                            sectionId: ctx.sectionId,
                                            columnId: ctx.columnId,
                                            gridWidgetId: w.id,
                                            gridColumnId: firstGcId,
                                        });
                                        setRailTab('elements');
                                    }}
                                    className="h-9 w-10 flex items-center justify-center hover:bg-gray-50"
                                    title="Add"
                                >
                                    <span aria-hidden>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <g>
                                                <path d="M24 11H13V0H11V11H0V13H11V24H13V13H24V11Z" fill="#374957" />
                                            </g>
                                        </svg>
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        const next = Math.max(1, Math.min(4, count + 1));
                                        updateGridWidget(ctx.sectionId, ctx.columnId, w.id, { count: next });
                                    }}
                                    className={`h-9 w-10 flex items-center justify-center hover:bg-gray-50 ${count >= 4 ? 'opacity-50 cursor-not-allowed' : ''}`}
                                    title="Add Column"
                                    disabled={count >= 4}
                                    style={{ cursor: count >= 4 ? 'not-allowed' : 'pointer' }}
                                >
                                    <span aria-hidden>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="16"
                                            height="16"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            strokeWidth="2"
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            className="lucide lucide-grid2x2-plus-icon lucide-grid-2x2-plus"
                                        >
                                            <path d="M12 3v17a1 1 0 0 1-1 1H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6a1 1 0 0 1-1 1H3" />
                                            <path d="M16 19h6" />
                                            <path d="M19 22v-6" />
                                        </svg>
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        duplicateWidget(ctx.sectionId, ctx.columnId, w.id);
                                    }}
                                    className="h-9 w-10 flex items-center justify-center hover:bg-gray-50"
                                    title="Duplicate"
                                >
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <g clip-path="url(#clip0_403_3199)">
                                        <path d="M13.0001 20.0001C14.3257 19.9986 15.5966 19.4713 16.5339 18.5339C17.4712 17.5966 17.9985 16.3257 18.0001 15.0001V6.24314C18.0017 5.7175 17.8989 5.19678 17.6977 4.71116C17.4965 4.22554 17.2009 3.78468 16.8281 3.41414L14.5861 1.17214C14.2156 0.799313 13.7747 0.503742 13.2891 0.30256C12.8035 0.101377 12.2828 -0.00141588 11.7571 0.00013946H7.00012C5.67453 0.00172732 4.40368 0.529021 3.46634 1.46636C2.529 2.4037 2.00171 3.67454 2.00012 5.00014V15.0001C2.00171 16.3257 2.529 17.5966 3.46634 18.5339C4.40368 19.4713 5.67453 19.9986 7.00012 20.0001H13.0001ZM4.00012 15.0001V5.00014C4.00012 4.20449 4.31619 3.44143 4.8788 2.87882C5.44141 2.31621 6.20447 2.00014 7.00012 2.00014C7.00012 2.00014 11.9191 2.01414 12.0001 2.02414V4.00014C12.0001 4.53057 12.2108 5.03928 12.5859 5.41435C12.961 5.78943 13.4697 6.00014 14.0001 6.00014H15.9761C15.9861 6.08114 16.0001 15.0001 16.0001 15.0001C16.0001 15.7958 15.6841 16.5589 15.1214 17.1215C14.5588 17.6841 13.7958 18.0001 13.0001 18.0001H7.00012C6.20447 18.0001 5.44141 17.6841 4.8788 17.1215C4.31619 16.5589 4.00012 15.7958 4.00012 15.0001ZM22.0001 8.00014V19.0001C21.9985 20.3257 21.4712 21.5966 20.5339 22.5339C19.5966 23.4713 18.3257 23.9986 17.0001 24.0001H8.00012C7.73491 24.0001 7.48055 23.8948 7.29302 23.7072C7.10548 23.5197 7.00012 23.2654 7.00012 23.0001C7.00012 22.7349 7.10548 22.4806 7.29302 22.293C7.48055 22.1055 7.73491 22.0001 8.00012 22.0001H17.0001C17.7958 22.0001 18.5588 21.6841 19.1214 21.1215C19.6841 20.5589 20.0001 19.7958 20.0001 19.0001V8.00014C20.0001 7.73492 20.1055 7.48057 20.293 7.29303C20.4806 7.1055 20.7349 7.00014 21.0001 7.00014C21.2653 7.00014 21.5197 7.1055 21.7072 7.29303C21.8948 7.48057 22.0001 7.73492 22.0001 8.00014Z" fill="#374957"/>
                                        </g>
                                        <defs>
                                        <clipPath id="clip0_403_3199">
                                        <rect width="24" height="24" fill="white"/>
                                        </clipPath>
                                        </defs>
                                    </svg>
                                </button>

                                <button
                                    type="button"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        setSelected({ kind: 'widget', sectionId: ctx.sectionId, columnId: ctx.columnId, widgetId: w.id });
                                        setRailTab('edit');
                                        setEditTab('content');
                                    }}
                                    className="h-9 w-10 flex items-center justify-center hover:bg-gray-50"
                                    title="Settings"
                                >
                                    <span aria-hidden>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <g clip-path="url(#clip0_403_3393)">
                                            <path d="M15.0004 24H9.00042V20.487C7.95768 20.1181 6.99187 19.5601 6.15142 18.841L3.10742 20.6L0.107422 15.4L3.15042 13.645C2.95053 12.5574 2.95053 11.4426 3.15042 10.355L0.107422 8.6L3.10742 3.4L6.15142 5.159C6.99187 4.43993 7.95768 3.88194 9.00042 3.513V0H15.0004V3.513C16.0432 3.88194 17.009 4.43993 17.8494 5.159L20.8934 3.4L23.8934 8.6L20.8504 10.355C21.0503 11.4426 21.0503 12.5574 20.8504 13.645L23.8934 15.4L20.8934 20.6L17.8494 18.842C17.0089 19.5607 16.0431 20.1184 15.0004 20.487V24ZM11.0004 22H13.0004V18.973L13.7514 18.779C14.9834 18.4598 16.1048 17.8101 16.9944 16.9L17.5374 16.347L20.1604 17.862L21.1604 16.13L18.5404 14.617L18.7464 13.871C19.0851 12.6439 19.0851 11.3481 18.7464 10.121L18.5404 9.375L21.1604 7.862L20.1604 6.13L17.5374 7.649L16.9944 7.1C16.1043 6.19134 14.983 5.54302 13.7514 5.225L13.0004 5.027V2H11.0004V5.027L10.2494 5.221C9.01741 5.54015 7.89603 6.18988 7.00642 7.1L6.46342 7.653L3.84042 6.134L2.84042 7.866L5.46042 9.379L5.25442 10.125C4.91578 11.3521 4.91578 12.6479 5.25442 13.875L5.46042 14.621L2.84042 16.134L3.84042 17.866L6.46342 16.351L7.00642 16.904C7.89651 17.8127 9.01785 18.461 10.2494 18.779L11.0004 18.973V22ZM12.0004 16C11.2093 16 10.4359 15.7654 9.77814 15.3259C9.12034 14.8864 8.60765 14.2616 8.3049 13.5307C8.00215 12.7998 7.92294 11.9956 8.07728 11.2196C8.23162 10.4437 8.61258 9.73098 9.17199 9.17157C9.7314 8.61216 10.4441 8.2312 11.2201 8.07686C11.996 7.92252 12.8003 8.00173 13.5312 8.30448C14.2621 8.60723 14.8868 9.11992 15.3263 9.77772C15.7658 10.4355 16.0004 11.2089 16.0004 12C16.0004 13.0609 15.579 14.0783 14.8288 14.8284C14.0787 15.5786 13.0613 16 12.0004 16ZM12.0004 10C11.6049 10 11.2182 10.1173 10.8893 10.3371C10.5604 10.5568 10.304 10.8692 10.1527 11.2346C10.0013 11.6001 9.96168 12.0022 10.0389 12.3902C10.116 12.7781 10.3065 13.1345 10.5862 13.4142C10.8659 13.6939 11.2223 13.8844 11.6102 13.9616C11.9982 14.0387 12.4003 13.9991 12.7658 13.8478C13.1312 13.6964 13.4436 13.44 13.6634 13.1111C13.8831 12.7822 14.0004 12.3956 14.0004 12C14.0004 11.4696 13.7897 10.9609 13.4146 10.5858C13.0396 10.2107 12.5309 10 12.0004 10Z" fill="#374957"/>
                                            </g>
                                            <defs>
                                            <clipPath id="clip0_403_3393">
                                            <rect width="24" height="24" fill="white"/>
                                            </clipPath>
                                            </defs>
                                        </svg>
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        e.preventDefault();
                                        deleteWidget(ctx.sectionId, ctx.columnId, w.id);
                                    }}
                                    className="h-9 w-10 flex items-center justify-center hover:bg-gray-50 text-red-600"
                                    title="Delete"
                                >
                                    <span aria-hidden>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M20.9999 4H17.8999C17.6678 2.87141 17.0537 1.85735 16.1611 1.12872C15.2686 0.40009 14.1521 0.00145452 12.9999 0L10.9999 0C9.84767 0.00145452 8.7312 0.40009 7.83863 1.12872C6.94606 1.85735 6.33197 2.87141 6.09988 4H2.99988C2.73466 4 2.48031 4.10536 2.29277 4.29289C2.10523 4.48043 1.99988 4.73478 1.99988 5C1.99988 5.26522 2.10523 5.51957 2.29277 5.70711C2.48031 5.89464 2.73466 6 2.99988 6H3.99988V19C4.00147 20.3256 4.52876 21.5964 5.4661 22.5338C6.40344 23.4711 7.67428 23.9984 8.99988 24H14.9999C16.3255 23.9984 17.5963 23.4711 18.5337 22.5338C19.471 21.5964 19.9983 20.3256 19.9999 19V6H20.9999C21.2651 6 21.5194 5.89464 21.707 5.70711C21.8945 5.51957 21.9999 5.26522 21.9999 5C21.9999 4.73478 21.8945 4.48043 21.707 4.29289C21.5194 4.10536 21.2651 4 20.9999 4ZM10.9999 2H12.9999C13.6202 2.00076 14.225 2.19338 14.7315 2.55144C15.238 2.90951 15.6213 3.41549 15.8289 4H8.17088C8.37846 3.41549 8.76178 2.90951 9.26826 2.55144C9.77475 2.19338 10.3796 2.00076 10.9999 2ZM17.9999 19C17.9999 19.7956 17.6838 20.5587 17.1212 21.1213C16.5586 21.6839 15.7955 22 14.9999 22H8.99988C8.20423 22 7.44117 21.6839 6.87856 21.1213C6.31595 20.5587 5.99988 19.7956 5.99988 19V6H17.9999V19Z" fill="#374957"/>
                                            <path d="M10 17.9994C10.2652 17.9994 10.5196 17.894 10.7071 17.7065C10.8946 17.5189 11 17.2646 11 16.9994V10.9994C11 10.7342 10.8946 10.4798 10.7071 10.2923C10.5196 10.1047 10.2652 9.99939 10 9.99939C9.73478 9.99939 9.48043 10.1047 9.29289 10.2923C9.10536 10.4798 9 10.7342 9 10.9994V16.9994C9 17.2646 9.10536 17.5189 9.29289 17.7065C9.48043 17.894 9.73478 17.9994 10 17.9994Z" fill="#374957"/>
                                            <path d="M13.9999 17.9994C14.2651 17.9994 14.5195 17.894 14.707 17.7065C14.8945 17.5189 14.9999 17.2646 14.9999 16.9994V10.9994C14.9999 10.7342 14.8945 10.4798 14.707 10.2923C14.5195 10.1047 14.2651 9.99939 13.9999 9.99939C13.7347 9.99939 13.4803 10.1047 13.2928 10.2923C13.1052 10.4798 12.9999 10.7342 12.9999 10.9994V16.9994C12.9999 17.2646 13.1052 17.5189 13.2928 17.7065C13.4803 17.894 13.7347 17.9994 13.9999 17.9994Z" fill="#374957"/>
                                        </svg>
                                    </span>
                                </button>
                            </div>
                        </div>
                    ) : null}

                    <div
                        className="mp-grid"
                        style={{
                            ...containerInnerStyle(w.style),
                            display: 'flex',
                            flexWrap: 'wrap',
                            width: '100%',
                            boxSizing: 'border-box',
                            flexDirection: wrapPerRow ? 'row' : direction,
                            justifyContent: justify,
                            alignItems: align,
                            rowGap: `${gapY}px`,
                            ...((wrapPerRow || direction === 'row' || direction === 'row-reverse') ? { columnGap: `${gapX}px` } : {}),
                        }}
                    >
                        {visibleCols.map((gc) => {
                            const gcAdv = advancedReactAttrs(gc.advanced);
                            const active =
                                (selected.kind === 'grid_column' &&
                                    selected.sectionId === ctx.sectionId &&
                                    selected.columnId === ctx.columnId &&
                                    selected.gridWidgetId === w.id &&
                                    selected.gridColumnId === gc.id) ||
                                (selected.kind === 'grid_widget' &&
                                    selected.sectionId === ctx.sectionId &&
                                    selected.columnId === ctx.columnId &&
                                    selected.gridWidgetId === w.id &&
                                    selected.gridColumnId === gc.id);

                            const gridColKey = `${ctx.sectionId}:${ctx.columnId}:${w.id}:${gc.id}`;
                            const isGridColHovered = hoveredGridColumnKey === gridColKey;

                            const span = Math.max(1, Math.min(12, spanForViewportInner(gc)));

                            const gcStyle = gc.style || {};
                            const gcMaxWidthType = gcStyle.maxWidthType || (gcStyle.maxWidth === 'full' ? 'full' : 'boxed');
                            const gcWidthValue = gcMaxWidthType === 'full' ? resolveUnit(pickResponsive(gcStyle.innerMaxWidth, gcStyle.innerMaxWidthTablet, gcStyle.innerMaxWidthMobile), 'px') : resolveUnit(pickResponsive(gcStyle.boxedWidth, gcStyle.boxedWidthTablet, gcStyle.boxedWidthMobile), 'px');
                            const gcHasWidthConstraint = !!gcWidthValue;
                            const gcWidthConstraint = gcWidthValue || null;

                            return (
                                <div
                                    key={gc.id}
                                    id={gcAdv.id}
                                    data-mp-cache={gcAdv.cacheAttr['data-mp-cache']}
                                    className={mergeClassNames('mp-col group relative', gcAdv.className, isGridColHovered ? 'mp-col-hovered' : '')}
                                    style={{
                                        ...containerInnerStyle(gc.style),
                                        ...(wrapPerRow >= 1
                                            ? {
                                                  width: 'auto',
                                                  maxWidth: `calc((100% - ${(wrapPerRow - 1) * gapX}px) / ${wrapPerRow})`,
                                                  flex: `0 0 calc((100% - ${(wrapPerRow - 1) * gapX}px) / ${wrapPerRow})`,
                                                  minWidth: 0,
                                                  boxSizing: 'border-box',
                                              }
                                            : (gcHasWidthConstraint
                                                ? {
                                                      flex: '0 0 auto',
                                                      width: '100%',
                                                      maxWidth: gcWidthConstraint,
                                                      minWidth: 0,
                                                  }
                                                : {
                                                      // Grow-based sizing avoids sub-pixel seams that
                                                      // percentage flex-basis + borders can leave in the
                                                      // canvas (which is not the real viewport width).
                                                      width: 'auto',
                                                      maxWidth: 'none',
                                                      flexBasis: 0,
                                                      flexGrow: span,
                                                      flexShrink: 1,
                                                      minWidth: 0,
                                                      boxSizing: 'border-box',
                                                  })),
                                        alignSelf: (gc.style && typeof gc.style.align === 'string' && gc.style.align) ? gc.style.align : align,
                                        ...(gcAdv.style || {}),
                                    }}
                                    onPointerEnter={() => setHoveredGridColumnKey(gridColKey)}
                                >
                                    {(() => {
                                        const isGridColumnActive =
                                            hoveredGridColumnKey === gridColKey ||
                                            (selected.kind === 'grid_column' &&
                                                selected.sectionId === ctx.sectionId &&
                                                selected.columnId === ctx.columnId &&
                                                selected.gridWidgetId === w.id &&
                                                selected.gridColumnId === gc.id) ||
                                            (selected.kind === 'grid_widget' &&
                                                selected.sectionId === ctx.sectionId &&
                                                selected.columnId === ctx.columnId &&
                                                selected.gridWidgetId === w.id &&
                                                selected.gridColumnId === gc.id);

                                        return GRID_COLUMN_INLINE_TOOLBAR_ENABLED && isGridColumnActive ? (
                                            <div
                                                className="absolute z-30"
                                                style={{ top: -24, left: '50%', transform: 'translate(-50%, 0)' }}
                                            >
                                                <div className="flex items-center bg-white border border-gray-200 shadow">
                                                    <button
                                                        type="button"
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            setSelected({
                                                                kind: 'grid_column',
                                                                sectionId: ctx.sectionId,
                                                                columnId: ctx.columnId,
                                                                gridWidgetId: w.id,
                                                                gridColumnId: gc.id,
                                                            });
                                                            setRailTab('edit');
                                                            setEditTab('content');
                                                        }}
                                                        className="h-9 w-10 flex items-center justify-center hover:bg-gray-50"
                                                        title="Settings"
                                                    >
                                                        <span aria-hidden>
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <g clip-path="url(#clip0_403_3393)">
                                                                <path d="M15.0004 24H9.00042V20.487C7.95768 20.1181 6.99187 19.5601 6.15142 18.841L3.10742 20.6L0.107422 15.4L3.15042 13.645C2.95053 12.5574 2.95053 11.4426 3.15042 10.355L0.107422 8.6L3.10742 3.4L6.15142 5.159C6.99187 4.43993 7.95768 3.88194 9.00042 3.513V0H15.0004V3.513C16.0432 3.88194 17.009 4.43993 17.8494 5.159L20.8934 3.4L23.8934 8.6L20.8504 10.355C21.0503 11.4426 21.0503 12.5574 20.8504 13.645L23.8934 15.4L20.8934 20.6L17.8494 18.842C17.0089 19.5607 16.0431 20.1184 15.0004 20.487V24ZM11.0004 22H13.0004V18.973L13.7514 18.779C14.9834 18.4598 16.1048 17.8101 16.9944 16.9L17.5374 16.347L20.1604 17.862L21.1604 16.13L18.5404 14.617L18.7464 13.871C19.0851 12.6439 19.0851 11.3481 18.7464 10.121L18.5404 9.375L21.1604 7.862L20.1604 6.13L17.5374 7.649L16.9944 7.1C16.1043 6.19134 14.983 5.54302 13.7514 5.225L13.0004 5.027V2H11.0004V5.027L10.2494 5.221C9.01741 5.54015 7.89603 6.18988 7.00642 7.1L6.46342 7.653L3.84042 6.134L2.84042 7.866L5.46042 9.379L5.25442 10.125C4.91578 11.3521 4.91578 12.6479 5.25442 13.875L5.46042 14.621L2.84042 16.134L3.84042 17.866L6.46342 16.351L7.00642 16.904C7.89651 17.8127 9.01785 18.461 10.2494 18.779L11.0004 18.973V22ZM12.0004 16C11.2093 16 10.4359 15.7654 9.77814 15.3259C9.12034 14.8864 8.60765 14.2616 8.3049 13.5307C8.00215 12.7998 7.92294 11.9956 8.07728 11.2196C8.23162 10.4437 8.61258 9.73098 9.17199 9.17157C9.7314 8.61216 10.4441 8.2312 11.2201 8.07686C11.996 7.92252 12.8003 8.00173 13.5312 8.30448C14.2621 8.60723 14.8868 9.11992 15.3263 9.77772C15.7658 10.4355 16.0004 11.2089 16.0004 12C16.0004 13.0609 15.579 14.0783 14.8288 14.8284C14.0787 15.5786 13.0613 16 12.0004 16ZM12.0004 10C11.6049 10 11.2182 10.1173 10.8893 10.3371C10.5604 10.5568 10.304 10.8692 10.1527 11.2346C10.0013 11.6001 9.96168 12.0022 10.0389 12.3902C10.116 12.7781 10.3065 13.1345 10.5862 13.4142C10.8659 13.6939 11.2223 13.8844 11.6102 13.9616C11.9982 14.0387 12.4003 13.9991 12.7658 13.8478C13.1312 13.6964 13.4436 13.44 13.6633 13.1111C13.8831 12.7822 14.0004 12.3955 14.0004 12C14.0004 11.2044 13.6843 10.4413 13.1217 9.87868C12.5591 9.31607 11.7959 9 11.0004 9Z" fill="#374957"/>
                                                                </g>
                                                                <defs>
                                                                <clipPath id="clip0_403_3393">
                                                                <rect width="24" height="24" fill="white"/>
                                                                </clipPath>
                                                                </defs>
                                                            </svg>
                                                        </span>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            duplicateGridColumn(ctx.sectionId, ctx.columnId, w.id, gc.id);
                                                        }}
                                                        className="h-9 w-10 flex items-center justify-center hover:bg-gray-50"
                                                        title="Duplicate"
                                                    >
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M16 1H5a2 2 0 0 0-2 2v11h2V3h11V1Zm3 4H9a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2Zm0 16H9V7h10v14Z" fill="#374957"/>
                                                        </svg>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            deleteGridColumn(ctx.sectionId, ctx.columnId, w.id, gc.id);
                                                        }}
                                                        className="h-9 w-10 flex items-center justify-center hover:bg-gray-50 text-red-600"
                                                        title="Delete"
                                                    >
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M20.9999 4H17.8999C17.6678 2.87141 17.0537 1.85735 16.1611 1.12872C15.2686 0.40009 14.1521 0.00145452 12.9999 0L10.9999 0C9.84767 0.00145452 8.7312 0.40009 7.83863 1.12872C6.94606 1.85735 6.33197 2.87141 6.09988 4H2.99988C2.73466 4 2.48031 4.10536 2.29277 4.29289C2.10523 4.48043 1.99988 4.73478 1.99988 5C1.99988 5.26522 2.10523 5.51957 2.29277 5.70711C2.48031 5.89464 2.73466 6 2.99988 6H3.99988V19C4.00147 20.3256 4.52876 21.5964 5.4661 22.5338C6.40344 23.4711 7.67428 23.9984 8.99988 24H14.9999C16.3255 23.9984 17.5963 23.4711 18.5337 22.5338C19.471 21.5964 19.9983 20.3256 19.9999 19V6H20.9999C21.2651 6 21.5194 5.89464 21.707 5.70711C21.8945 5.51957 21.9999 5.26522 21.9999 5C21.9999 4.73478 21.8945 4.48043 21.707 4.29289C21.5194 4.10536 21.2651 4 20.9999 4ZM10.9999 2H12.9999C13.6202 2.00076 14.225 2.19338 14.7315 2.55144C15.238 2.90951 15.6213 3.41549 15.8289 4H8.17088C8.37846 3.41549 8.76178 2.90951 9.26826 2.55144C9.77475 2.19338 10.3796 2.00076 10.9999 2ZM17.9999 19C17.9999 19.7956 17.6838 20.5587 17.1212 21.1213C16.5586 21.6839 15.7955 22 14.9999 22H8.99988C8.20423 22 7.44117 21.6839 6.87856 21.1213C6.31595 20.5587 5.99988 19.7956 5.99988 19V6H17.9999V19Z" fill="#374957"/>
                                                            <path d="M10 17.9994C10.2652 17.9994 10.5196 17.894 10.7071 17.7065C10.8946 17.5189 11 17.2646 11 16.9994V10.9994C11 10.7342 10.8946 10.4798 10.7071 10.2923C10.5196 10.1047 10.2652 9.99939 10 9.99939C9.73478 9.99939 9.48043 10.1047 9.29289 10.2923C9.10536 10.4798 9 10.7342 9 10.9994V16.9994C9 17.2646 9.10536 17.5189 9.29289 17.7065C9.48043 17.894 9.73478 17.9994 10 17.9994Z" fill="#374957"/>
                                                            <path d="M13.9999 17.9994C14.2651 17.9994 14.5195 17.894 14.707 17.7065C14.8945 17.5189 14.9999 17.2646 14.9999 16.9994V10.9994C14.9999 10.7342 14.8945 10.4798 14.707 10.2923C14.5195 10.1047 14.2651 9.99939 13.9999 9.99939C13.7347 9.99939 13.4803 10.1047 13.2928 10.2923C13.1052 10.4798 12.9999 10.7342 12.9999 10.9994V16.9994C12.9999 17.2646 13.1052 17.5189 13.2928 17.7065C13.4803 17.894 13.7347 17.9994 13.9999 17.9994Z" fill="#374957"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        ) : null;
                                    })()}
                                    <div
                                        className={`relative min-h-0 cursor-pointer overflow-visible rounded-none border border-transparent transition-colors ${
                                            active ? 'border-gray-300' : ''
                                        }`}
                                        onContextMenu={(e) =>
                                            handleContextMenu(e, {
                                                kind: 'grid_column',
                                                sectionId: ctx.sectionId,
                                                columnId: ctx.columnId,
                                                gridWidgetId: w.id,
                                                gridColumnId: gc.id,
                                                gridColumn: gc,
                                            })
                                        }
                                        onPointerDown={(e) => {
                                            e.stopPropagation();
                                            setSelected({
                                                kind: 'grid_column',
                                                sectionId: ctx.sectionId,
                                                columnId: ctx.columnId,
                                                gridWidgetId: w.id,
                                                gridColumnId: gc.id,
                                            });
                                        }}
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            setSelected({
                                                kind: 'grid_column',
                                                sectionId: ctx.sectionId,
                                                columnId: ctx.columnId,
                                                gridWidgetId: w.id,
                                                gridColumnId: gc.id,
                                            });
                                        }}
                                        onDragOver={(e) => {
                                            e.preventDefault();
                                            const data = getDragData(e);
                                            if (!data) return;
                                            setDropIndicator({
                                                key: `${ctx.sectionId}:${ctx.columnId}:${w.id}:${gc.id}`,
                                                sectionId: ctx.sectionId,
                                                columnId: ctx.columnId,
                                                gridWidgetId: w.id,
                                                gridColumnId: gc.id,
                                            });
                                        }}
                                        onDragLeave={(e) => {
                                            const next = e.relatedTarget;
                                            if (next && e.currentTarget && e.currentTarget.contains(next)) {
                                                return;
                                            }
                                            setDropIndicator((curr) => (curr && curr.key === `${ctx.sectionId}:${ctx.columnId}:${w.id}:${gc.id}` ? null : curr));
                                        }}
                                        onDrop={(e) => {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            const data = getDragData(e);
                                            setDropIndicator(null);
                                            if (!data) return;
                                            const container = gridWidgetsContainerRefs.get(`${ctx.sectionId}:${ctx.columnId}:${w.id}:${gc.id}`);
                                            const pos = container ? findDropPosition(container, e.clientY) : { index: (gc.widgets || []).length, top: 0 };
                                            const insertIndex = Number.isInteger(pos.index) ? pos.index : (gc.widgets || []).length;

                                            if (data.kind === 'grid') {
                                                addWidgetToGridColumn(ctx.sectionId, ctx.columnId, w.id, gc.id, 'grid', { count: data.count || 2 }, insertIndex);
                                            }

                                            if (data.kind === 'container') {
                                                addWidgetToGridColumn(ctx.sectionId, ctx.columnId, w.id, gc.id, 'grid', { count: 1 }, insertIndex);
                                            }

                                            if (data.kind === 'mobile_menu') {
                                                addWidgetToGridColumn(ctx.sectionId, ctx.columnId, w.id, gc.id, 'grid', { ...MOBILE_MENU_DEFAULTS, count: 1, mobileMenu: true }, insertIndex);
                                            }

                                            if (data.kind === 'new_widget' && data.widgetType) {
                                                addWidgetToGridColumn(ctx.sectionId, ctx.columnId, w.id, gc.id, data.widgetType, {}, insertIndex);
                                            }

                                            if (data.kind === 'move_widget') {
                                                moveWidgetAnywhere(data, {
                                                    sectionId: ctx.sectionId,
                                                    columnId: ctx.columnId,
                                                    gridWidgetId: w.id,
                                                    gridColumnId: gc.id,
                                                    insertIndex,
                                                });
                                            }
                                        }}
                                    >
                                        {isGridColHovered ? (
                                            <div className="absolute z-20 pointer-events-none" style={{ top: 0, right: 0 }}>
                                                <div
                                                    className="bg-blue-600 text-white px-3 py-1 text-xs font-semibold inline-flex items-center gap-2 pointer-events-auto shadow cursor-pointer"
                                                    style={{ cursor: 'pointer' }}
                                                >
                                                    <button
                                                        type="button"
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            setSelected({
                                                                kind: 'grid_column',
                                                                sectionId: ctx.sectionId,
                                                                columnId: ctx.columnId,
                                                                gridWidgetId: w.id,
                                                                gridColumnId: gc.id,
                                                            });
                                                            setRailTab('edit');
                                                            setEditTab('content');
                                                        }}
                                                        className="hover:underline"
                                                        style={{ cursor: 'pointer' }}
                                                        title="Edit Column"
                                                    >
                                                        <span>{(gc.name || '').trim() || 'Column'}</span>
                                                    </button>

                                                <button
                                                    type="button"
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        setSelected({
                                                            kind: 'grid_column',
                                                            sectionId: ctx.sectionId,
                                                            columnId: ctx.columnId,
                                                            gridWidgetId: w.id,
                                                            gridColumnId: gc.id,
                                                        });
                                                        setRailTab('edit');
                                                        setEditTab('content');
                                                    }}
                                                    className="text-white/90 hover:text-white"
                                                    style={{ cursor: 'pointer' }}
                                                    title="Column Settings"
                                                >
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_403_3393)"><path d="M15.0004 24H9.00042V20.487C7.95768 20.1181 6.99187 19.5601 6.15142 18.841L3.10742 20.6L0.107422 15.4L3.15042 13.645C2.95053 12.5574 2.95053 11.4426 3.15042 10.355L0.107422 8.6L3.10742 3.4L6.15142 5.159C6.99187 4.43993 7.95768 3.88194 9.00042 3.513V0H15.0004V3.513C16.0432 3.88194 17.009 4.43993 17.8494 5.159L20.8934 3.4L23.8934 8.6L20.8504 10.355C21.0503 11.4426 21.0503 12.5574 20.8504 13.645L23.8934 15.4L20.8934 20.6L17.8494 18.842C17.0089 19.5607 16.0431 20.1184 15.0004 20.487V24ZM11.0004 22H13.0004V18.973L13.7514 18.779C14.9834 18.4598 16.1048 17.8101 16.9944 16.9L17.5374 16.347L20.1604 17.862L21.1604 16.13L18.5404 14.617L18.7464 13.871C19.0851 12.6439 19.0851 11.3481 18.7464 10.121L18.5404 9.375L21.1604 7.862L20.1604 6.13L17.5374 7.649L16.9944 7.1C16.1043 6.19134 14.983 5.54302 13.7514 5.225L13.0004 5.027V2H11.0004V5.027L10.2494 5.221C9.01741 5.54015 7.89603 6.18988 7.00642 7.1L6.46342 7.653L3.84042 6.134L2.84042 7.866L5.46042 9.379L5.25442 10.125C4.91578 11.3521 4.91578 12.6479 5.25442 13.875L5.46042 14.621L2.84042 16.134L3.84042 17.866L6.46342 16.351L7.00642 16.904C7.89651 17.8127 9.01785 18.461 10.2494 18.779L11.0004 18.973V22ZM12.0004 16C11.2093 16 10.4359 15.7654 9.77814 15.3259C9.12034 14.8864 8.60765 14.2616 8.3049 13.5307C8.00215 12.7998 7.92294 11.9956 8.07728 11.2196C8.23162 10.4437 8.61258 9.73098 9.17199 9.17157C9.7314 8.61216 10.4441 8.2312 11.2201 8.07686C11.996 7.92252 12.8003 8.00173 13.5312 8.30448C14.2621 8.60723 14.8868 9.11992 15.3263 9.77772C15.7658 10.4355 16.0004 11.2089 16.0004 12C16.0004 13.0609 15.579 14.0783 14.8288 14.8284C14.0787 15.5786 13.0613 16 12.0004 16ZM12.0004 10C11.6049 10 11.2182 10.1173 10.8893 10.3371C10.5604 10.5568 10.304 10.8692 10.1527 11.2346C10.0013 11.6001 9.96168 12.0022 10.0389 12.3902C10.116 12.7781 10.3065 13.1345 10.5862 13.4142C10.8659 13.6939 11.2223 13.8844 11.6102 13.9616C11.9982 14.0387 12.4003 13.9991 12.7658 13.8478C13.1312 13.6964 13.4436 13.44 13.6634 13.1111C13.8831 12.7822 14.0004 12.3956 14.0004 12C14.0004 11.4696 13.7897 10.9609 13.4146 10.5858C13.0396 10.2107 12.5309 10 12.0004 10Z" fill="#fff"></path></g><defs><clipPath id="clip0_403_3393"><rect width="24" height="24" fill="white"></rect></clipPath></defs></svg>
                                                </button>

                                                <button
                                                    type="button"
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        e.preventDefault();
                                                        console.log('Delete grid column clicked:', gc.id);
                                                        console.log('Total grid columns:', visibleCols.length);
                                                        if (visibleCols.length <= 1) {
                                                            console.log('Cannot delete: only 1 grid column remaining');
                                                            return;
                                                        }
                                                        console.log('Calling deleteGridColumn');
                                                        deleteGridColumn(ctx.sectionId, ctx.columnId, w.id, gc.id);
                                                    }}
                                                    className={`text-white/90 hover:text-white ${visibleCols.length <= 1 ? 'opacity-50 cursor-not-allowed' : ''}`}
                                                    style={{ cursor: visibleCols.length <= 1 ? 'not-allowed' : 'pointer' }}
                                                    title="Delete Column"
                                                    disabled={visibleCols.length <= 1}
                                                >
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style={{ pointerEvents: 'none' }}>
                                                        <path d="M21 6H18V4.33C18 3.4 17.6 2.53 16.9 1.93C16.2 1.33 15.3 1 14.4 1H9.6C8.7 1 7.8 1.33 7.1 1.93C6.4 2.53 6 3.4 6 4.33V6H3C2.73478 6 2.48043 6.10536 2.29289 6.29289C2.10536 6.48043 2 6.73478 2 7C2 7.26522 2.10536 7.51957 2.29289 7.70711C2.48043 7.89464 2.73478 8 3 8H4V19C4 20.0609 4.42143 21.0783 5.17157 21.8284C5.92172 22.5786 6.93913 23 8 23H16C17.0609 23 18.0783 22.5786 18.8284 21.8284C19.5786 21.0783 20 20.0609 20 19V8H21C21.2652 8 21.5196 7.89464 21.7071 7.70711C21.8946 7.51957 22 7.26522 22 7C22 6.73478 21.8946 6.48043 21.7071 6.29289C21.5196 6.10536 21.2652 6 21 6ZM8 4.33C8 3.97 8.13 3.62 8.37 3.36C8.61 3.1 8.95 2.95 9.32 2.95H14.68C15.05 2.95 15.39 3.08 15.63 3.32C15.87 3.56 16 3.9 16 4.27V6H8V4.33ZM18 19C18 19.5304 17.7893 20.0391 17.4142 20.4142C17.0391 20.7893 16.5304 21 16 21H8C7.46957 21 6.96086 20.7893 6.58579 20.4142C6.21071 20.0391 6 19.5304 6 19V8H18V19Z" fill="#fff"/>
                                                        <path d="M10 18C10.2652 18 10.5196 17.8946 10.7071 17.7071C10.8946 17.5196 11 17.2652 11 17V12C11 11.7348 10.8946 11.4804 10.7071 11.2929C10.5196 11.1054 10.2652 11 10 11C9.73478 11 9.48043 11.1054 9.29289 11.2929C9.10536 11.4804 9 11.7348 9 12V17C9 17.2652 9.10536 17.5196 9.29289 17.7071C9.48043 17.8946 9.73478 18 10 18Z" fill="#fff"/>
                                                        <path d="M14 18C14.2652 18 14.5196 17.8946 14.7071 17.7071C14.8946 17.5196 15 17.2652 15 17V12C15 11.7348 14.8946 11.4804 14.7071 11.2929C14.5196 11.1054 14.2652 11 14 11C13.7348 11 13.4804 11.1054 13.2929 11.2929C13.1054 11.4804 13 11.7348 13 12V17C13 17.2652 13.1054 17.5196 13.2929 17.7071C13.4804 17.8946 13.7348 18 14 18Z" fill="#fff"/>
                                                    </svg>
                                                </button>
                                                </div>
                                            </div>
                                        ) : null}

                                        <div
                                            className={`absolute inset-0 z-30 border border-dashed pointer-events-none transition-colors ${
                                                dropIndicator && dropIndicator.key === `${ctx.sectionId}:${ctx.columnId}:${w.id}:${gc.id}`
                                                    ? 'opacity-100'
                                                    : 'opacity-0 group-hover:opacity-100'
                                            }`}
                                            style={
                                                dropIndicator && dropIndicator.key === `${ctx.sectionId}:${ctx.columnId}:${w.id}:${gc.id}`
                                                    ? { borderColor: '#2563eb', borderWidth: 2, borderStyle: 'solid', backgroundColor: 'rgba(37, 99, 235, 0.06)', borderRadius: 0 }
                                                    : { borderColor: '#ddd', borderRadius: 0 }
                                            }
                                        />

                                        {(Array.isArray(gc.widgets) ? gc.widgets.length : 0) === 0 ? (
                                            <div className="text-center py-6">
                                                <button
                                                    type="button"
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        if (selected.kind !== 'grid_column') {
                                                            skipAutoEditRef.current = true;
                                                        }
                                                        setSelected({
                                                            kind: 'grid_column',
                                                            sectionId: ctx.sectionId,
                                                            columnId: ctx.columnId,
                                                            gridWidgetId: w.id,
                                                            gridColumnId: gc.id,
                                                        });
                                                        setRailTab('elements');
                                                    }}
                                                    className="mx-auto h-10 w-10 rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 flex items-center justify-center text-2xl leading-none"
                                                >
                                                    <span aria-hidden>+</span>
                                                </button>
                                            </div>
                                         ) : (
                                             (() => {
                                                 const wrap = computeWrapPerRow(gc.style);
                                                 const basis = wrap ? `calc((100% - ${(wrap.n - 1) * wrap.gapX}px) / ${wrap.n})` : null;
                                                 return (
                                             <div
                                                 className={wrap ? 'relative w-full' : 'space-y-3 relative w-full'}
                                                 style={wrap
                                                     ? { width: '100%', minWidth: 0, display: 'flex', flexWrap: 'wrap', gap: `${wrap.gapY}px ${wrap.gapX}px` }
                                                     : { width: '100%', minWidth: 0 }}
                                                 ref={(el) => {
                                                     const key = `${ctx.sectionId}:${ctx.columnId}:${w.id}:${gc.id}`;
                                                     if (el) gridWidgetsContainerRefs.set(key, el);
                                                     else gridWidgetsContainerRefs.delete(key);
                                                 }}
                                             >
                                                  {(Array.isArray(gc.widgets) ? gc.widgets : []).map((iw) => {
                                                      const iwAdv = advancedReactAttrs(iw.advanced);
                                                      return (
                                                     <div
                                                         key={iw.id}
                                                         data-widget-id={iw.id}
                                                         draggable
                                                        onDragStart={(e) =>
                                                            setDragData(e, {
                                                                kind: 'move_widget',
                                                                sectionId: ctx.sectionId,
                                                                columnId: ctx.columnId,
                                                                gridWidgetId: w.id,
                                                                gridColumnId: gc.id,
                                                                widgetId: iw.id,
                                                            })
                                                        }
                                                        onContextMenu={(e) =>
                                                            handleContextMenu(e, {
                                                                kind: 'grid_widget',
                                                                sectionId: ctx.sectionId,
                                                                columnId: ctx.columnId,
                                                                gridWidgetId: w.id,
                                                                gridColumnId: gc.id,
                                                                widgetId: iw.id,
                                                                widget: iw,
                                                            })
                                                        }
                                                        onPointerDown={(e) => {
                                                            e.stopPropagation();
                                                            setSelected({
                                                                kind: 'grid_widget',
                                                                sectionId: ctx.sectionId,
                                                                columnId: ctx.columnId,
                                                                gridWidgetId: w.id,
                                                                gridColumnId: gc.id,
                                                                widgetId: iw.id,
                                                            });
                                                        }}
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            setSelected({
                                                                kind: 'grid_widget',
                                                                sectionId: ctx.sectionId,
                                                                columnId: ctx.columnId,
                                                                gridWidgetId: w.id,
                                                                gridColumnId: gc.id,
                                                                widgetId: iw.id,
                                                            });
                                                        }}
                                                        className={`cursor-pointer rounded-none border border-transparent transition-colors hover:border-blue-300 ${
                                                            selected.kind === 'grid_widget' &&
                                                            selected.sectionId === ctx.sectionId &&
                                                            selected.columnId === ctx.columnId &&
                                                            selected.gridWidgetId === w.id &&
                                                            selected.gridColumnId === gc.id &&
                                                            selected.widgetId === iw.id
                                                                ? 'border-gray-300'
                                                                : ''
                                                         }`}
                                                           style={basis
                                                               ? { width: basis, maxWidth: basis, flex: `0 0 ${basis}`, minWidth: 0, boxSizing: 'border-box', ...(iwAdv.wrapperStyle || {}) }
                                                               : { width: '100%', maxWidth: '100%', ...(iwAdv.wrapperStyle || {}) }}
                                                     >
                                                         {(() => {
                                                             return (
                                                                 <div
                                                                     id={iwAdv.id}
                                                                     data-mp-cache={iwAdv.cacheAttr['data-mp-cache']}
                                                                     className={mergeClassNames(iwAdv.className, `mp-typo-${iw.id}`)}
                                                                     style={iwAdv.style}
                                                                 >
                                                                     {responsiveTypographyCss(iw.props, `mp-typo-${iw.id}`) ? (
                                                                         <style dangerouslySetInnerHTML={{ __html: responsiveTypographyCss(iw.props, `mp-typo-${iw.id}`) }} />
                                                                     ) : null}
                                                                     {renderWidgetPreview(iw, {
                                                                         sectionId: ctx.sectionId,
                                                                         columnId: ctx.columnId,
                                                                         gridWidgetId: w.id,
                                                                         gridColumnId: gc.id,
                                                                     })}
                                                                 </div>
                                                             );
                                                         })()}
                                                      </div>
                                                       );
                                                   })}
                                             </div>
                                                 );
                                             })()
                                         )}
                                     </div>
                                 </div>
                             );
                         })}
                     </div>
                 </div>
                 </GridPricingScope>
            );

            if (w.props && w.props.mobileMenu) {
                return (
                    <div onClick={() => setSelected({ kind: 'widget', sectionId: ctx.sectionId, columnId: ctx.columnId, widgetId: w.id })}>
                        <MobileMenuCanvas props={w.props}>{gridContent}</MobileMenuCanvas>
                    </div>
                );
            }

            return gridContent;
        }
        if (w.type === 'marquee') {
            const mode = w.props && ['images', 'text'].includes(w.props.mode) ? w.props.mode : 'images';
            const speedRaw = w.props && Number.isFinite(Number(w.props.speed)) ? Number(w.props.speed) : 30;
            const speed = Math.max(5, Math.min(200, Math.round(speedRaw || 0) || 30));

            const gapRaw = w.props && Number.isFinite(Number(w.props.gap)) ? Number(w.props.gap) : 24;
            const gap = Math.max(0, Math.round(gapRaw || 0) || 0);
            const direction = w.props && ['left', 'right'].includes(w.props.direction) ? w.props.direction : 'left';

            const fadeEnabled = w.props && typeof w.props.fadeEnabled === 'boolean' ? w.props.fadeEnabled : true;
            const fadeMask = w.props && typeof w.props.fadeMask === 'boolean' ? w.props.fadeMask : true;
            const fadeColor = w.props && typeof w.props.fadeColor === 'string' ? w.props.fadeColor : '';
            const fadeWidthRaw = w.props && Number.isFinite(Number(w.props.fadeWidth)) ? Number(w.props.fadeWidth) : undefined;
            const fadeWidth = Number.isFinite(fadeWidthRaw) ? Math.max(0, Math.min(200, Math.round(fadeWidthRaw))) : undefined;
            const fadeWidthCss = `${fadeWidth !== undefined ? fadeWidth : 48}px`;

            const marqueeTextCss = typographyStyleFromProps({ ...(w.props || {}), color: (w.props && typeof w.props.textColor === 'string') ? w.props.textColor : '' });
            const marqueeBg = (() => {
                const p = w.props || {};
                const out = {};
                const bgType = typeof p.backgroundType === 'string' ? p.backgroundType : 'classic';
                if (bgType === 'gradient') {
                    const from = typeof p.gradientFrom === 'string' && p.gradientFrom.trim() ? p.gradientFrom.trim() : '#111827';
                    const to = typeof p.gradientTo === 'string' && p.gradientTo.trim() ? p.gradientTo.trim() : '#3b82f6';
                    if (p.gradientType === 'radial') {
                        const pos = typeof p.gradientPosition === 'string' && p.gradientPosition.trim() ? p.gradientPosition.trim() : 'top center';
                        out.backgroundImage = `radial-gradient(at ${pos}, ${from} 0%, ${to} 100%)`;
                    } else {
                        const ang = Number.isFinite(Number(p.gradientAngle)) ? Number(p.gradientAngle) : 135;
                        out.backgroundImage = `linear-gradient(${ang}deg, ${from} 0%, ${to} 100%)`;
                    }
                } else if (typeof p.backgroundColor === 'string' && p.backgroundColor.trim()) {
                    out.backgroundColor = p.backgroundColor.trim();
                }
                return out;
            })();

            let innerA = [];
            let innerB = [];

            if (mode === 'text') {
                const textItems = w.props && Array.isArray(w.props.items) ? w.props.items : [];
                const separatorIcon = w.props && typeof w.props.separatorIcon === 'string' ? w.props.separatorIcon : '';
                const cleaned = textItems.filter((it) => it && it.text);

                if (cleaned.length === 0) {
                    return (
                        <div className="rounded-xl border border-dashed border-gray-200 p-4 text-sm text-gray-500">
                            Marquee (0 text items)
                        </div>
                    );
                }

                const renderTextItem = (it, idx, prefix) => {
                    const hasText = typeof it.text === 'string' && it.text !== '';
                    const halfGap = Math.round((gap || 0) / 2);
                    return (
                        <React.Fragment key={`${prefix}-${idx}`}>
                            <div
                                className="mp-marquee__item"
                                style={{ flex: '0 0 auto', paddingLeft: `${halfGap}px`, paddingRight: `${halfGap}px`, display: 'flex', alignItems: 'center' }}
                            >
                                {hasText ? <span style={{ whiteSpace: 'nowrap', ...marqueeTextCss }}>{it.text}</span> : null}
                            </div>
                            {separatorIcon ? (
                                <span
                                    className="mp-marquee__separator-icon"
                                    style={{
                                        display: 'inline-flex',
                                        alignItems: 'center',
                                        justifyContent: 'center',
                                        paddingLeft: `${halfGap}px`,
                                        paddingRight: `${halfGap}px`,
                                        flex: '0 0 auto',
                                        color: (w.props && typeof w.props.textColor === 'string' && w.props.textColor.trim()) ? w.props.textColor.trim() : undefined,
                                    }}
                                    dangerouslySetInnerHTML={{ __html: separatorIcon }}
                                />
                            ) : null}
                        </React.Fragment>
                    );
                };

                innerA = cleaned.map((it, idx) => renderTextItem(it, idx, 'a'));
                innerB = cleaned.map((it, idx) => renderTextItem(it, idx, 'b'));
            } else {
                const images = w.props && Array.isArray(w.props.images) ? w.props.images : [];
                const imageHeightRaw = w.props && Number.isFinite(Number(w.props.imageHeight)) ? Number(w.props.imageHeight) : undefined;
                const imageWidthRaw = w.props && Number.isFinite(Number(w.props.imageWidth)) ? Number(w.props.imageWidth) : undefined;
                const imageWidth = Number.isFinite(imageWidthRaw) ? Math.max(0, Math.min(600, Math.round(imageWidthRaw))) : undefined;
                let imageHeight;
                if (Number.isFinite(imageHeightRaw)) {
                    imageHeight = Math.max(10, Math.min(200, Math.round(imageHeightRaw)));
                } else if (imageWidth !== undefined) {
                    imageHeight = 'auto';
                } else {
                    imageHeight = 40;
                }

                if (images.length === 0) {
                    return (
                        <div className="rounded-xl border border-dashed border-gray-200 p-4 text-sm text-gray-500">
                            Marquee (0 images)
                        </div>
                    );
                }

                const cleaned = images.filter((i) => i && typeof i === 'object' && i.url);
                innerA = cleaned.map((i, idx) => (
                    <div key={`a-${idx}`} className="mp-marquee__item" style={{ flex: '0 0 auto', paddingRight: `${gap}px` }}>
                        <img
                            src={String(i.url)}
                            alt=""
                            style={{ height: imageHeight, width: imageWidth !== undefined ? imageWidth : 'auto', display: 'block' }}
                        />
                    </div>
                ));
                innerB = cleaned.map((i, idx) => (
                    <div key={`b-${idx}`} className="mp-marquee__item" style={{ flex: '0 0 auto', paddingRight: `${gap}px` }}>
                        <img
                            src={String(i.url)}
                            alt=""
                            style={{ height: imageHeight, width: imageWidth !== undefined ? imageWidth : 'auto', display: 'block' }}
                        />
                    </div>
                ));
            }

            return (
                <div
                    className={mergeClassNames(
                        'mp-marquee rounded-xl border border-dashed border-gray-200 p-2',
                        fadeEnabled && fadeMask ? 'mp-marquee--mask' : '',
                        fadeEnabled && fadeColor ? 'mp-marquee--overlay' : ''
                    )}
                    style={{
                        ...marqueeBg,
                        overflow: 'hidden',
                        width: '100%',
                        whiteSpace: 'nowrap',
                        position: 'relative',
                        ...(fadeEnabled && fadeWidth !== undefined ? { '--mp-marquee-fade-width': `${fadeWidth}px` } : null),
                        ...(fadeEnabled && fadeColor ? { '--mp-marquee-fade-color': fadeColor } : null),
                        ...(fadeEnabled && fadeMask
                            ? {
                                  WebkitMaskImage: `linear-gradient(to right, rgba(0,0,0,0) 0%, rgba(0,0,0,1) ${fadeWidthCss}, rgba(0,0,0,1) calc(100% - ${fadeWidthCss}), rgba(0,0,0,0) 100%)`,
                                  maskImage: `linear-gradient(to right, rgba(0,0,0,0) 0%, rgba(0,0,0,1) ${fadeWidthCss}, rgba(0,0,0,1) calc(100% - ${fadeWidthCss}), rgba(0,0,0,0) 100%)`,
                                  WebkitMaskRepeat: 'no-repeat',
                                  maskRepeat: 'no-repeat',
                                  WebkitMaskSize: '100% 100%',
                                  maskSize: '100% 100%',
                              }
                            : null),
                    }}
                >
                    <style>{`@keyframes mp-marquee{from{transform:translateX(0)}to{transform:translateX(-50%)}}`}</style>

                    {fadeEnabled && fadeColor ? (
                        <>
                            <div
                                style={{
                                    position: 'absolute',
                                    left: 0,
                                    top: 0,
                                    bottom: 0,
                                    width: fadeWidthCss,
                                    zIndex: 1,
                                    pointerEvents: 'none',
                                    background: `linear-gradient(to right, ${fadeColor} 0%, rgba(0,0,0,0) 100%)`,
                                }}
                            />
                            <div
                                style={{
                                    position: 'absolute',
                                    right: 0,
                                    top: 0,
                                    bottom: 0,
                                    width: fadeWidthCss,
                                    zIndex: 1,
                                    pointerEvents: 'none',
                                    background: `linear-gradient(to left, ${fadeColor} 0%, rgba(0,0,0,0) 100%)`,
                                }}
                            />
                        </>
                    ) : null}
                    <div
                        className="mp-marquee__track"
                        style={{
                            display: 'flex',
                            alignItems: 'center',
                            position: 'relative',
                            zIndex: 0,
                            width: 'max-content',
                            flexWrap: 'nowrap',
                            animationName: 'mp-marquee',
                            animationDuration: `${speed}s`,
                            animationTimingFunction: 'linear',
                            animationIterationCount: 'infinite',
                            animationDirection: direction === 'right' ? 'reverse' : 'normal',
                            animationPlayState: 'running',
                            willChange: 'transform',
                            pointerEvents: 'none',
                        }}
                    >
                        {innerA}
                        {innerB}
                    </div>
                </div>
            );
        }

        return <div className="text-sm text-gray-400">{w.type}</div>;
    };

    const submitForm = () => {
        isSubmittingRef.current = true;
        try {
            onChange(pageRef.current || page);
        } catch (e) {
            // ignore
        }
        const form = document.getElementById('page-builder-form') || document.getElementById('unlayer-form');
        if (form && typeof form.requestSubmit === 'function') {
            form.requestSubmit();
            return;
        }
        if (form) {
            form.submit();
        }
    };

    const safeFilenameBase = (name) => {
        const raw = String(name || 'page')
            .trim()
            .toLowerCase()
            .replace(/\s+/g, '-')
            .replace(/[^a-z0-9\-_.]/g, '')
            .replace(/-+/g, '-');
        return raw || 'page';
    };

    const downloadTextFile = (filename, content, mime) => {
        try {
            const blob = new Blob([content], { type: mime || 'text/plain;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(() => {
                try {
                    URL.revokeObjectURL(url);
                } catch (e) {
                }
            }, 0);
        } catch (e) {
            // ignore
        }
    };

    const exportAsJson = () => {
        const base = safeFilenameBase(pageTitle);
        let json = '';
        try {
            json = JSON.stringify(pageRef.current || page, null, 2);
        } catch (e) {
            json = '';
        }
        downloadTextFile(`${base}.json`, json, 'application/json;charset=utf-8');
    };

    const exportAsHtml = async () => {
        if (!renderHtmlUrl) return;

        const base = safeFilenameBase(pageTitle);
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const payload = { builder_data: JSON.stringify(pageRef.current || page) };

        try {
            const res = await fetch(renderHtmlUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                },
                body: JSON.stringify(payload),
            });

            const data = await res.json().catch(() => null);
            const html = data && typeof data === 'object' && typeof data.html === 'string' ? data.html : '';

            if (!res.ok || !html) {
                throw new Error('Failed to render HTML');
            }

            downloadTextFile(`${base}.html`, html, 'text/html;charset=utf-8');
        } catch (e) {
            try {
                window.alert('Export failed. Please try again.');
            } catch (e2) {
            }
        }
    };

    const importAsJson = (file) => {
        const reader = new FileReader();
        reader.onload = () => {
            let normalized = null;
            try {
                const parsed = JSON.parse(String(reader.result || ''));
                let candidate = parsed;
                if (Array.isArray(parsed)) {
                    candidate = { builder: 'mailpurse', version: 1, sections: parsed };
                } else if (parsed && typeof parsed === 'object') {
                    if (parsed.page && typeof parsed.page === 'object') {
                        candidate = parsed.page;
                    } else if (parsed.sections && !parsed.builder) {
                        candidate = { builder: 'mailpurse', version: 1, ...parsed };
                    } else if (parsed.builder === 'mailpurse' && !parsed.version) {
                        candidate = { version: 1, ...parsed };
                    }
                }
                normalized = normalizePage(candidate, mode);
            } catch (e) {
                normalized = null;
            }
            if (!normalized || !Array.isArray(normalized.sections)) {
                window.alert('That file is not a valid MailPurse builder JSON export.');
                return;
            }
            setPage(() => normalized);
            setSelected({ kind: 'none' });
            setRailTab('elements');
        };
        reader.onerror = () => window.alert('Could not read that file.');
        reader.readAsText(file);
    };

    const importHtmlString = (raw, { silent = false } = {}) => {
        const rawSections = htmlToSections(raw, { mode });
        const sections = rawSections.map((s) => normalizeSection(s)).filter(Boolean).filter((s) => s.columns.length > 0);
        if (!sections.length) {
            if (!silent) window.alert('No importable HTML content was found.');
            return false;
        }
        setPage((p) => {
            const existing = Array.isArray(p.sections) ? [...p.sections] : [];
            return { ...p, sections: [...existing, ...sections] };
        });
        setSelected({ kind: 'section', sectionId: sections[0].id });
        setRailTab('edit');
        setEditTab('content');
        return true;
    };

    const importAsHtml = (file) => {
        const reader = new FileReader();
        reader.onload = () => importHtmlString(reader.result || '');
        reader.onerror = () => window.alert('Could not read that file.');
        reader.readAsText(file);
    };

    const triggerImport = (format) => {
        importFormatRef.current = format === 'html' ? 'html' : 'json';
        setImportOpen(false);
        const input = importInputRef.current;
        if (!input) return;
        input.value = '';
        input.click();
    };

    const openImportCode = () => {
        setImportOpen(false);
        setImportCodeValue('');
        setImportCodeOpen(true);
    };

    const applyImportCode = () => {
        if (importHtmlString(importCodeValue)) {
            setImportCodeOpen(false);
            setImportCodeValue('');
        }
    };

    const handleImportFileChange = (e) => {
        const file = e.target.files && e.target.files[0] ? e.target.files[0] : null;
        const format = importFormatRef.current;
        e.target.value = '';
        if (!file) return;
        if (format === 'html') {
            importAsHtml(file);
        } else {
            importAsJson(file);
        }
    };

    const renderEmail = async () => {
        if (!renderHtmlUrl) throw new Error('Email rendering is not configured.');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const res = await fetch(renderHtmlUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            },
            body: JSON.stringify({ builder_data: JSON.stringify(pageRef.current || page) }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.html_content) throw new Error(data.message || 'Unable to render the email.');
        return data;
    };

    const openEmailPreview = async () => {
        setPreviewLoading(true);
        setPreviewOpen(true);
        try {
            const data = await renderEmail();
            setPreviewHtml(data.html_content || '');
        } catch (e) {
            setPreviewOpen(false);
            window.alert(e.message || 'Unable to render the preview.');
        } finally {
            setPreviewLoading(false);
        }
    };

    const sendEmailTest = async () => {
        if (!testServerId || !testEmail) {
            setTestError('Select a delivery server and enter a destination email.');
            return;
        }
        setTestLoading(true);
        setTestError('');
        setTestMessage('');
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch(testEmailUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                },
                body: JSON.stringify({
                    builder_data: JSON.stringify(pageRef.current || page),
                    delivery_server_id: testServerId,
                    to_email: testEmail,
                    template_name: document.getElementById('name')?.value || templateName,
                }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Unable to send the test email.');
            setTestMessage(data.message || 'Test email sent successfully.');
        } catch (e) {
            setTestError(e.message || 'Unable to send the test email.');
        } finally {
            setTestLoading(false);
        }
    };

    const submitWithStatus = async (nextStatus) => {
        const v = nextStatus === 'publish' ? 'publish' : 'draft';

        if (mode === 'email') {
            const form = document.getElementById('unlayer-form');
            try {
                isSubmittingRef.current = true;

                const data = await renderEmail();
                const payload = pageRef.current || page;
                const htmlInput = document.getElementById('html_content');
                const textInput = document.getElementById('plain_text_content');
                const builderInput = document.getElementById('grapesjs_data');
                const statusInput = document.getElementById('status');
                if (htmlInput) htmlInput.value = data.html_content || '';
                if (textInput) textInput.value = data.plain_text_content || '';
                if (builderInput) builderInput.value = JSON.stringify(payload);
                if (statusInput) statusInput.value = v;

                let savedTemplateId = null;
                let savedEditUrl = '';
                if (form) {
                    const formData = new FormData(form);
                    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const res = await fetch(form.action, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                        },
                        body: formData,
                    });
                    const result = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        throw new Error(result.message || 'Unable to save the email template.');
                    }
                    savedTemplateId = result?.template?.id ?? null;
                    savedEditUrl = result?.edit_url || '';

                    if (result?.update_url) {
                        form.action = result.update_url;
                        form.method = 'POST';
                        if (!form.querySelector('input[name="_method"]')) {
                            const methodInput = document.createElement('input');
                            methodInput.type = 'hidden';
                            methodInput.name = '_method';
                            methodInput.value = 'PUT';
                            form.appendChild(methodInput);
                        }
                    }
                }

                if (savedTemplateId && savedEditUrl && typeof window !== 'undefined' && window.history && typeof window.history.replaceState === 'function') {
                    try {
                        window.history.replaceState(window.history.state, '', savedEditUrl);
                    } catch (e) {
                    }
                }

                setStatusState(v);
                if (statusInput) statusInput.value = v;
                savedSnapshotRef.current = stableStringify(payload);
                setSavedTick((tick) => tick + 1);

                try {
                    if (typeof window !== 'undefined' && typeof window.mailpurseToast === 'function') {
                        window.mailpurseToast({
                            type: 'success',
                            message: v === 'publish' ? 'Template published successfully.' : 'Draft saved successfully.',
                        });
                    }
                } catch (e) {
                }
            } catch (e) {
                window.alert(e.message || 'Unable to publish the email template.');
            } finally {
                isSubmittingRef.current = false;
            }
            return;
        }

        try {
            if (typeof window !== 'undefined' && window.sessionStorage) {
                const payload = {
                    selected,
                    railTab,
                    editTab,
                    viewport,
                    status: v,
                    previewScrollTop: previewScrollRef.current ? previewScrollRef.current.scrollTop : 0,
                    railScrollTop: railContentRef.current ? railContentRef.current.scrollTop : 0,
                    restoreOnce: true,
                };
                window.sessionStorage.setItem(uiStateKeyRef.current, JSON.stringify(payload));
            }
        } catch (e) {
        }

        setStatusState(v);

        const input = document.getElementById('status');
        if (input) {
            input.value = v;
        }
        submitForm();
    };

    useEffect(() => {
        const input = document.getElementById('status');
        if (input) {
            input.value = status;
        }
    }, [status]);

    const canvasMaxWidthClass = mode === 'email'
        ? 'max-w-[600px]'
        : viewport === 'desktop' ? '' : viewport === 'tablet' ? 'max-w-3xl' : 'max-w-md';

    return (
        <BuilderViewportContext.Provider value={{ viewport, setViewport }}>
        <BuilderModeContext.Provider value={mode}>        <div className="mp-builder h-full flex flex-col bg-gray-50 fixed w-full left-0">
            <div className="h-12 shrink-0 flex items-center pr-1 bg-black text-white backdrop-blur">
                <div className="flex items-center gap-3 min-w-0">
                    {settingsUrl ? (
                        <a
                            href={settingsUrl}
                            className="h-10 w-10 inline-flex items-center justify-center"
                            title="Back"
                        >
                            <span aria-hidden>
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M19 10.9999H9L12.29 7.70994C12.3837 7.61697 12.4581 7.50637 12.5089 7.38451C12.5597 7.26265 12.5858 7.13195 12.5858 6.99994C12.5858 6.86793 12.5597 6.73722 12.5089 6.61536C12.4581 6.4935 12.3837 6.3829 12.29 6.28994C12.1026 6.10369 11.8492 5.99915 11.585 5.99915C11.3208 5.99915 11.0674 6.10369 10.88 6.28994L6.59 10.5899C6.21441 10.9633 6.00223 11.4704 6 11.9999C6.00487 12.526 6.21684 13.029 6.59 13.3999L10.88 17.6999C10.9732 17.7925 11.0838 17.8658 11.2054 17.9157C11.3269 17.9655 11.4571 17.9909 11.5885 17.9905C11.7199 17.99 11.8499 17.9637 11.9712 17.913C12.0924 17.8623 12.2024 17.7882 12.295 17.6949C12.3876 17.6017 12.4609 17.4911 12.5107 17.3696C12.5606 17.248 12.586 17.1178 12.5856 16.9864C12.5851 16.855 12.5588 16.725 12.508 16.6038C12.4573 16.4825 12.3832 16.3725 12.29 16.2799L9 12.9999H19C19.2652 12.9999 19.5196 12.8946 19.7071 12.707C19.8946 12.5195 20 12.2651 20 11.9999C20 11.7347 19.8946 11.4804 19.7071 11.2928C19.5196 11.1053 19.2652 10.9999 19 10.9999Z" fill="#fff"/>
                                </svg>
                            </span>
                        </a>
                    ) : null}

                    <div className="min-w-0">
                        <div className="text-sm font-semibold truncate">{pageTitle || 'Page'}</div>
                        <div className="text-[11px] text-white/60 truncate">
                            {status === 'publish' ? 'Published' : 'Draft'}
                        </div>
                    </div>

                    <div className="flex items-center gap-2 pl-2">
                        <button
                            type="button"
                            onClick={undo}
                            disabled={!canUndo}
                            className="h-10 w-10 inline-flex items-center justify-center rounded-lg bg-gray-800 hover:bg-gray-700 cursor-pointer"
                            title="Undo"
                        >
                            <span aria-hidden>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="lucide lucide-undo-icon lucide-undo"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg>
                            </span>
                        </button>
                        <button
                            type="button"
                            onClick={redo}
                            disabled={!canRedo}
                            className="h-10 w-10 inline-flex items-center justify-center rounded-lg bg-gray-800 hover:bg-gray-700 cursor-pointer"
                            title="Redo"
                        >
                            <span aria-hidden>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="lucide lucide-redo-icon lucide-redo"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3l3 2.7"/></svg>
                            </span>
                        </button>
                    </div>
                </div>

                <div className="flex-1 flex items-center justify-center">
                    <div className="hidden md:flex items-center gap-1 rounded-lg p-1">
                        <button
                            type="button"
                            onClick={() => setViewport('desktop')}
                            className={`h-9 w-10 mx-auto px-2.5 rounded-lg ${viewport === 'desktop' ? 'bg-gray-800' : ' hover:text-gray-500 hover:bg-gray-700'}`}
                            title="Desktop"
                        >
                            <span aria-hidden>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M19 0.999878H5C3.67441 1.00147 2.40356 1.52876 1.46622 2.4661C0.528882 3.40344 0.00158786 4.67428 0 5.99988L0 13.9999C0.00158786 15.3255 0.528882 16.5963 1.46622 17.5337C2.40356 18.471 3.67441 18.9983 5 18.9999H11V20.9999H7C6.73478 20.9999 6.48043 21.1052 6.29289 21.2928C6.10536 21.4803 6 21.7347 6 21.9999C6 22.2651 6.10536 22.5194 6.29289 22.707C6.48043 22.8945 6.73478 22.9999 7 22.9999H17C17.2652 22.9999 17.5196 22.8945 17.7071 22.707C17.8946 22.5194 18 22.2651 18 21.9999C18 21.7347 17.8946 21.4803 17.7071 21.2928C17.5196 21.1052 17.2652 20.9999 17 20.9999H13V18.9999H19C20.3256 18.9983 21.5964 18.471 22.5338 17.5337C23.4711 16.5963 23.9984 15.3255 24 13.9999V5.99988C23.9984 4.67428 23.4711 3.40344 22.5338 2.4661C21.5964 1.52876 20.3256 1.00147 19 0.999878ZM5 2.99988H19C19.7956 2.99988 20.5587 3.31595 21.1213 3.87856C21.6839 4.44117 22 5.20423 22 5.99988V12.9999H2V5.99988C2 5.20423 2.31607 4.44117 2.87868 3.87856C3.44129 3.31595 4.20435 2.99988 5 2.99988ZM19 16.9999H5C4.38174 16.9973 3.77937 16.8038 3.2753 16.4458C2.77123 16.0878 2.39008 15.5828 2.184 14.9999H21.816C21.6099 15.5828 21.2288 16.0878 20.7247 16.4458C20.2206 16.8038 19.6183 16.9973 19 16.9999Z" fill="#fff"/>
</svg>

                            </span>
                        </button>
                        <button
                            type="button"
                            onClick={() => setViewport('tablet')}
                            className={`h-9 w-10 mx-auto px-2.5 rounded-lg ${viewport === 'tablet' ? 'bg-gray-800' : ' hover:text-gray-500 hover:bg-gray-700'}`}
                            title="Tablet"
                        >
                            <span aria-hidden>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M17.0001 0H7.00012C5.67453 0.00158786 4.40368 0.528882 3.46634 1.46622C2.529 2.40356 2.00171 3.67441 2.00012 5V19C2.00171 20.3256 2.529 21.5964 3.46634 22.5338C4.40368 23.4711 5.67453 23.9984 7.00012 24H17.0001C18.3257 23.9984 19.5966 23.4711 20.5339 22.5338C21.4712 21.5964 21.9985 20.3256 22.0001 19V5C21.9985 3.67441 21.4712 2.40356 20.5339 1.46622C19.5966 0.528882 18.3257 0.00158786 17.0001 0V0ZM7.00012 2H17.0001C17.7958 2 18.5588 2.31607 19.1214 2.87868C19.6841 3.44129 20.0001 4.20435 20.0001 5V17H4.00012V5C4.00012 4.20435 4.31619 3.44129 4.8788 2.87868C5.44141 2.31607 6.20447 2 7.00012 2ZM17.0001 22H7.00012C6.20447 22 5.44141 21.6839 4.8788 21.1213C4.31619 20.5587 4.00012 19.7956 4.00012 19H11.0001V20C11.0001 20.2652 11.1055 20.5196 11.293 20.7071C11.4806 20.8946 11.7349 21 12.0001 21C12.2653 21 12.5197 20.8946 12.7072 20.7071C12.8948 20.5196 13.0001 20.2652 13.0001 20V19H20.0001C20.0001 19.7956 19.6841 20.5587 19.1214 21.1213C18.5588 21.6839 17.7958 22 17.0001 22Z" fill="#fff"/>
</svg>

                            </span>
                        </button>
                        <button
                            type="button"
                            onClick={() => setViewport('mobile')}
                            className={`h-9 w-10 mx-auto px-2.5 rounded-lg ${viewport === 'mobile' ? 'bg-gray-800' : ' hover:text-gray-500 hover:bg-gray-700'}`}
                            title="Mobile"
                        >
                            <span aria-hidden>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M15.0001 0H9.00012C7.67453 0.00158786 6.40368 0.528882 5.46634 1.46622C4.529 2.40356 4.00171 3.67441 4.00012 5V19C4.00171 20.3256 4.529 21.5964 5.46634 22.5338C6.40368 23.4711 7.67453 23.9984 9.00012 24H15.0001C16.3257 23.9984 17.5966 23.4711 18.5339 22.5338C19.4712 21.5964 19.9985 20.3256 20.0001 19V5C19.9985 3.67441 19.4712 2.40356 18.5339 1.46622C17.5966 0.528882 16.3257 0.00158786 15.0001 0V0ZM9.00012 2H15.0001C15.7958 2 16.5588 2.31607 17.1214 2.87868C17.6841 3.44129 18.0001 4.20435 18.0001 5V16H6.00012V5C6.00012 4.20435 6.31619 3.44129 6.8788 2.87868C7.44141 2.31607 8.20447 2 9.00012 2ZM15.0001 22H9.00012C8.20447 22 7.44141 21.6839 6.8788 21.1213C6.31619 20.5587 6.00012 19.7956 6.00012 19V18H18.0001V19C18.0001 19.7956 17.6841 20.5587 17.1214 21.1213C16.5588 21.6839 15.7958 22 15.0001 22Z" fill="#fff"/>
<path d="M12.0001 20.9994C12.5524 20.9994 13.0001 20.5517 13.0001 19.9994C13.0001 19.4471 12.5524 18.9994 12.0001 18.9994C11.4478 18.9994 11.0001 19.4471 11.0001 19.9994C11.0001 20.5517 11.4478 20.9994 12.0001 20.9994Z" fill="#fff"/>
</svg>

                            </span>
                        </button>
                    </div>
                </div>

                <div className="flex items-center gap-2">
                    {mode === 'email' ? (
                        <button
                            type="button"
                            onClick={openEmailPreview}
                            className="h-10 w-10 inline-flex items-center justify-center rounded-lg bg-gray-800 hover:bg-gray-700 text-white"
                            title="Preview"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    ) : null}
                    {previewUrl ? (
                        <a
                            href={previewUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="h-10 w-10 inline-flex items-center justify-center rounded-lg bg-gray-800 hover:bg-gray-700 text-white"
                            title="Preview"
                        >
                            <span aria-hidden>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="lucide lucide-eye-icon lucide-eye"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                            </span>
                        </a>
                    ) : null}

                    {mode === 'email' ? (
                        <button
                            type="button"
                            onClick={() => { setTestError(''); setTestMessage(''); setTestOpen(true); }}
                            className="h-10 px-4 inline-flex items-center justify-center rounded-lg bg-gray-800 hover:bg-gray-700 text-sm font-semibold text-white"
                        >
                            Test
                        </button>
                    ) : null}

                    <button
                        type="button"
                        onClick={() => setNavigatorOpen((v) => !v)}
                        className={`h-10 w-10 inline-flex items-center justify-center rounded-lg ${navigatorOpen ? 'bg-gray-800 text-white' : 'bg-gray-800 hover:bg-gray-700 text-white'}`}
                        title="Navigator"
                    >
                        <span aria-hidden>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="lucide lucide-layers2-icon lucide-layers-2"><path d="M13 13.74a2 2 0 0 1-2 0L2.5 8.87a1 1 0 0 1 0-1.74L11 2.26a2 2 0 0 1 2 0l8.5 4.87a1 1 0 0 1 0 1.74z"/><path d="m20 14.285 1.5.845a1 1 0 0 1 0 1.74L13 21.74a2 2 0 0 1-2 0l-8.5-4.87a1 1 0 0 1 0-1.74l1.5-.845"/></svg>
                        </span>
                    </button>

                    <div ref={exportWrapRef} className="relative">
                        <button
                            type="button"
                            ref={exportButtonRef}
                            onClick={() => setExportOpen((v) => !v)}
                            className="h-10 p-2 gap-2 inline-flex items-center justify-center rounded-lg bg-gray-800 hover:bg-gray-700 text-sm font-semibold text-white"
                            title="Export"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-download-icon lucide-download"><path d="M12 15V3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/></svg>
                            Export
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-down-icon lucide-chevron-down"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                    </div>

                    {exportOpen && exportMenuPos
                        ? createPortal(
                            <div
                                ref={exportMenuRef}
                                className="w-44 rounded-lg bg-gray-900 shadow-lg overflow-hidden"
                                style={{
                                    position: 'fixed',
                                    top: exportMenuPos.top,
                                    left: exportMenuPos.left,
                                    transform: 'translateX(-100%)',
                                    zIndex: 9999,
                                }}
                            >
                                <button
                                    type="button"
                                    onClick={() => {
                                        setExportOpen(false);
                                        exportAsJson();
                                    }}
                                    className="w-full px-3 flex items-center gap-2 py-2 text-left text-xs font-semibold text-white hover:bg-gray-800"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-braces-corner-icon lucide-file-braces-corner"><path d="M14 22h4a2 2 0 0 0 2-2V8a2.4 2.4 0 0 0-.706-1.706l-3.588-3.588A2.4 2.4 0 0 0 14 2H6a2 2 0 0 0-2 2v6"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M5 14a1 1 0 0 0-1 1v2a1 1 0 0 1-1 1 1 1 0 0 1 1 1v2a1 1 0 0 0 1 1"/><path d="M9 22a1 1 0 0 0 1-1v-2a1 1 0 0 1 1-1 1 1 0 0 1-1-1v-2a1 1 0 0 0-1-1"/></svg>
                                    Export as JSON
                                </button>
                                <button
                                    type="button"
                                    onClick={() => {
                                        setExportOpen(false);
                                        exportAsHtml();
                                    }}
                                    className="w-full px-3 py-2 flex items-center gap-2 text-left text-xs font-semibold text-white hover:bg-gray-800"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-code-corner-icon lucide-file-code-corner"><path d="M4 12.15V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.706.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2h-3.35"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="m5 16-3 3 3 3"/><path d="m9 22 3-3-3-3"/></svg>
                                    Export as HTML
                                </button>
                            </div>,
                            document.body
                        )
                        : null}

                    <div ref={importWrapRef} className="relative">
                        <button
                            type="button"
                            ref={importButtonRef}
                            onClick={() => setImportOpen((v) => !v)}
                            className="h-10 p-2 gap-2 inline-flex items-center justify-center rounded-lg bg-gray-800 hover:bg-gray-700 text-sm font-semibold text-white"
                            title="Import"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 3v12"/><path d="m17 8-5-5-5 5"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/></svg>
                            Import
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <input
                            ref={importInputRef}
                            type="file"
                            accept=".json,application/json,.html,.htm,text/html"
                            onChange={handleImportFileChange}
                            className="hidden"
                        />
                    </div>

                    {importOpen && importMenuPos
                        ? createPortal(
                            <div
                                ref={importMenuRef}
                                className="w-44 rounded-lg bg-gray-900 shadow-lg overflow-hidden"
                                style={{
                                    position: 'fixed',
                                    top: importMenuPos.top,
                                    left: importMenuPos.left,
                                    transform: 'translateX(-100%)',
                                    zIndex: 9999,
                                }}
                            >
                                <button
                                    type="button"
                                    onClick={() => triggerImport('json')}
                                    className="w-full px-3 flex items-center gap-2 py-2 text-left text-xs font-semibold text-white hover:bg-gray-800"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M14 22h4a2 2 0 0 0 2-2V8a2.4 2.4 0 0 0-.706-1.706l-3.588-3.588A2.4 2.4 0 0 0 14 2H6a2 2 0 0 0-2 2v6"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M5 14a1 1 0 0 0-1 1v2a1 1 0 0 1-1 1 1 1 0 0 1 1 1v2a1 1 0 0 0 1 1"/><path d="M9 22a1 1 0 0 0 1-1v-2a1 1 0 0 1 1-1 1 1 0 0 1-1-1v-2a1 1 0 0 0-1-1"/></svg>
                                    Import from JSON
                                </button>
                                <button
                                    type="button"
                                    onClick={() => triggerImport('html')}
                                    className="w-full px-3 py-2 flex items-center gap-2 text-left text-xs font-semibold text-white hover:bg-gray-800"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M4 12.15V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.706.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2h-3.35"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="m5 16-3 3 3 3"/><path d="m9 22 3-3-3-3"/></svg>
                                    Import from HTML file
                                </button>
                                <button
                                    type="button"
                                    onClick={openImportCode}
                                    className="w-full px-3 py-2 flex items-center gap-2 text-left text-xs font-semibold text-white hover:bg-gray-800"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m18 16 4-4-4-4"/><path d="m6 8-4 4 4 4"/><path d="m14.5 4-5 16"/></svg>
                                    Import from HTML code
                                </button>
                            </div>,
                            document.body
                        )
                        : null}

                    <button
                        type="button"
                        onClick={() => submitWithStatus('publish')}
                        disabled={!isDirty}
                        className={`h-10 px-5 inline-flex items-center justify-center rounded-lg text-sm font-semibold text-white ${
                            isDirty ? 'bg-blue-600 hover:bg-blue-500' : 'bg-blue-600/50 cursor-not-allowed'
                        }`}
                    >
                        Publish
                    </button>
                </div>
            </div>

            <div className="flex-1 min-h-0 flex relative">
                <div className="w-9 shrink-0 bg-gray-900 text-white flex flex-col items-center py-3 gap-3">
                    <button
                        type="button"
                        onClick={() => setRailTab('elements')}
                        className={`h-10 w-10 rounded flex items-center justify-center ${
                            railTab === 'elements' ? 'bg-gray-800' : 'hover:bg-gray-700'
                        }`}
                        title="Elements"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="lucide lucide-package-plus-icon lucide-package-plus"><path d="M16 16h6"/><path d="M19 13v6"/><path d="M21 10V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l2-1.14"/><path d="m7.5 4.27 9 5.15"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" x2="12" y1="22" y2="12"/></svg>
                    </button>

                    {aiEnabled ? (
                    <button
                        type="button"
                        onClick={() => setRailTab('ai')}
                        className={`h-10 w-10 rounded flex items-center justify-center ${
                            railTab === 'ai' ? 'bg-gray-800' : 'hover:bg-gray-700'
                        }`}
                        title="AI section builder"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/><path d="M5 3v4"/><path d="M19 17v4"/><path d="M3 5h4"/><path d="M17 19h4"/></svg>
                    </button>
                    ) : null}

                    <button
                        type="button"
                        onClick={() => {
                            if (selectedModel.kind !== 'none') {
                                setRailTab('edit');
                            }
                        }}
                        className={`h-10 w-10 rounded flex items-center justify-center ${
                            railTab === 'edit' && selectedModel.kind !== null ? 'bg-gray-800' : 'hover:bg-gray-700'
                        } ${selectedModel.kind === 'none' ? 'opacity-40 cursor-not-allowed' : ''}`}
                        disabled={selectedModel.kind === 'none'}
                        title="Edit"
                    >
                        <span aria-hidden>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="lucide lucide-settings2-icon lucide-settings-2"><path d="M14 17H5"/><path d="M19 7h-9"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></svg>

                        </span>
                    </button>

                    <button
                        type="button"
                        onClick={() => {
                            setSelected({});
                            setRailTab('edit');
                            setEditTab(layoutType === 'header' ? 'advanced' : 'content');
                        }}
                        className={`h-10 w-10 rounded flex items-center justify-center ${
                            selectedModel.kind === null && railTab === 'edit' ? 'bg-gray-800' : 'hover:bg-gray-700'
                        }`}
                        title="Page Settings"
                    >
                        <span aria-hidden>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="lucide lucide-file-cog-icon lucide-file-cog"><path d="M13.85 22H18a2 2 0 0 0 2-2V8a2 2 0 0 0-.586-1.414l-4-4A2 2 0 0 0 14 2H6a2 2 0 0 0-2 2v6.6"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="m3.305 19.53.923-.382"/><path d="m4.228 16.852-.924-.383"/><path d="m5.852 15.228-.383-.923"/><path d="m5.852 20.772-.383.924"/><path d="m8.148 15.228.383-.923"/><path d="m8.53 21.696-.382-.924"/><path d="m9.773 16.852.922-.383"/><path d="m9.773 19.148.922.383"/><circle cx="7" cy="18" r="3"/></svg>
                        </span>
                    </button>

                    <button
                        type="button"
                        onClick={() => setRailTab('globals')}
                        className={`h-10 w-10 rounded flex items-center justify-center ${
                            railTab === 'globals' ? 'bg-gray-800' : 'hover:bg-gray-700'
                        }`}
                        title="Global Settings"
                    >
                        <span aria-hidden>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="lucide lucide-palette-icon lucide-palette"><path d="M12 22a1 1 0 0 1 0-20 10 9 0 0 1 10 9c0 4.97-4.03 8-9 8H12Z"/><path d="M13 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"/><path d="M17 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"/><path d="M9 13a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"/><path d="M15 16a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"/></svg>
                        </span>
                    </button>
                </div>

                <div
                    data-mp-builder-rail="1"
                    className="shrink-0 border-r border-gray-200 bg-white flex flex-col overflow-hidden"
                    style={{ width: 300, minWidth: 300, maxWidth: 300 }}
                >
                    {railTab !== 'edit' ? (
                        <div className="px-4 py-3 border-b border-gray-200">
                            <div className="text-sm font-semibold text-gray-900">
                                {railTab === 'elements' ? 'Elements' : railTab === 'globals' ? 'Global Settings' : railTab === 'ai' ? (mode === 'email' ? 'AI Email Builder' : 'AI Section Builder') : ''}
                            </div>
                            {railTab === 'elements' ? (
                                <div className="mt-3">
                                    <input
                                        value={query}
                                        onChange={(e) => setQuery(e.target.value)}
                                        placeholder="Search Widget..."
                                        className="block w-full rounded-md border border-gray-200 bg-white px-3 py-2 text-sm"
                                    />
                                </div>
                            ) : null}
                        </div>
                    ) : null}

                    <div
                        ref={railContentRef}
                        onScroll={(e) => {
                            railScrollTopRef.current = e.currentTarget.scrollTop;
                        }}
                        className={`flex-1 min-w-0 overflow-auto ${railTab === 'edit' ? 'py-4' : railTab === 'ai' ? '' : 'px-4 py-4'}`}
                    >
                        {railTab === 'elements' ? (
                            <div className="space-y-4">
                                <div>
                                    <p className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Layout</p>
                                    <div className="mt-2 grid grid-cols-2 gap-2">
                                        <div
                                            draggable
                                            onDragStart={(e) => setDragData(e, { kind: 'container' })}
                                            onClick={() => addGrid(1)}
                                            className="rounded-md border border-gray-200 p-3 text-center hover:bg-gray-50 cursor-move"
                                        >
                                            <div className="flex flex-col items-center gap-2">
                                                <ElementPaletteIcon name="container" />
                                                <div className="min-w-0 text-center">
                                                    <div className="text-sm font-semibold text-gray-900">Container</div>
                                                    <div className="text-xs text-gray-500">1 column</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            draggable
                                            onDragStart={(e) => setDragData(e, { kind: 'grid', count: 2 })}
                                            onClick={() => addGrid(2)}
                                            className="rounded-md border border-gray-200 p-3 text-center hover:bg-gray-50 cursor-move"
                                        >
                                            <div className="flex flex-col items-center gap-2">
                                                <ElementPaletteIcon name="grid" />
                                                <div className="min-w-0 text-center">
                                                    <div className="text-sm font-semibold text-gray-900">Grid</div>
                                                    <div className="text-xs text-gray-500">2 columns</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            draggable
                                            onDragStart={(e) => setDragData(e, { kind: 'mobile_menu' })}
                                            onClick={() => addMobileMenu()}
                                            className="rounded-md border border-gray-200 p-3 text-center hover:bg-gray-50 cursor-move"
                                        >
                                            <div className="flex flex-col items-center gap-2">
                                                <ElementPaletteIcon name="mobile_menu" />
                                                <div className="min-w-0 text-center">
                                                    <div className="text-sm font-semibold text-gray-900">Mobile Menu</div>
                                                    <div className="text-xs text-gray-500">Drawer</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {Array.from(
                                    new Set(widgetPalette.map((w) => w.group).filter(Boolean))
                                ).map((group) => (
                                    <div key={group}>
                                        <p className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{group}</p>
                                        <div className="mt-2 grid grid-cols-2 gap-2">
                                            {widgetPalette
                                                .filter((w) => w.group === group)
                                                .map((w) => (
                                                    <div
                                                        key={w.type}
                                                        draggable
                                                        onDragStart={(e) => setDragData(e, { kind: 'new_widget', widgetType: w.type })}
                                                        onClick={() => addWidget(w.type)}
                                                        className="rounded-md border border-gray-200 p-3 text-center hover:bg-gray-50 cursor-move"
                                                    >
                                                        <div className="flex flex-col items-center gap-2">
                                                            <ElementPaletteIcon name={w.type} />
                                                            <div className="text-sm font-semibold text-gray-900 text-center">{w.label}</div>
                                                        </div>
                                                    </div>
                                                ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : null}

                        {railTab === 'globals' ? (
                            <div className="overflow-y-auto flex-1">
                                <GlobalSettingsPanel
                                    value={page.globals || {}}
                                    onChange={(patch) =>
                                        setPage((p) => ({
                                            ...p,
                                            globals: { ...(p.globals || {}), ...patch },
                                        }))
                                    }
                                />
                            </div>
                        ) : null}

                        {railTab === 'ai' ? (
                            <AiBuilderPanel
                                {...aiBuilder}
                                stopGeneration={aiBuilder.stop}
                                selectedThemeId={aiBuilder.selectedThemeId}
                                themePresets={aiBuilder.themePresets}
                                selectTheme={aiBuilder.selectTheme}
                                mode={mode}
                            />
                        ) : null}

                        {railTab === 'edit' ? (
                            <div className="space-y-4">
                                <ColorPresetsProvider value={globals.colors}>
                                    <div className="px-4">
                                        <div className="text-sm font-semibold text-gray-900">
                                            {selectedModel.kind === 'widget' || selectedModel.kind === 'tab_widget'
                                                ? `Edit ${getWidgetDisplayName(selectedModel.widget)}`
                                                : selectedModel.kind === 'grid_widget'
                                                    ? `Edit ${getWidgetDisplayName(selectedModel.widget)}`
                                                : selectedModel.kind === 'section' || selectedModel.kind === 'column' || selectedModel.kind === 'grid_column'
                                                    ? 'Edit Container'
                                                : selectedModel.kind === 'tab_item'
                                                    ? 'Edit Tab Item'
                                                : selectedModel.kind === null
                                                    ? 'Page Settings'
                                                : 'Edit'}
                                    </div>

                                    <div className="mt-3 rounded-full bg-gray-100 p-1 flex items-center gap-1 dark:bg-gray-800 elements-tab-rail">
                                        {!(selectedModel.kind === null && layoutType === 'header') && (
                                        <button
                                            type="button"
                                            onClick={() => setEditTab('content')}
                                            className={`flex-1 px-3 py-1.5 text-sm font-medium rounded-full transition-colors focus:outline-none dark:focus:ring-offset-gray-900 ${
                                                editTab === 'content'
                                                    ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-900 dark:text-gray-100'
                                                    : 'bg-transparent text-gray-500 hover:bg-gray-200 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200'
                                            }`}
                                        >
                                            Content
                                        </button>
                                        )}
                                        <button
                                            type="button"
                                            onClick={() => setEditTab('style')}
                                            className={`flex-1 px-3 py-1.5 text-sm font-medium rounded-full transition-colors focus:outline-none dark:focus:ring-offset-gray-900 ${
                                                editTab === 'style'
                                                    ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-900 dark:text-gray-100'
                                                    : 'bg-transparent text-gray-500 hover:bg-gray-200 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200'
                                            }`}
                                        >
                                            Style
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => setEditTab('advanced')}
                                            className={`flex-1 px-3 py-1.5 text-sm font-medium rounded-full transition-colors dark:focus:ring-offset-gray-900 ${
                                                editTab === 'advanced'
                                                    ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-900 dark:text-gray-100'
                                                    : 'bg-transparent text-gray-500 hover:bg-gray-200 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200'
                                            }`}
                                        >
                                            Advanced
                                        </button>
                                    </div>
                                </div>

                                {editTab === 'content' ? (
                                    <div className="space-y-4">
                                        {selectedModel.kind === null ? (
                                            layoutType === 'header' ? (
                                                <SchemaInspector
                                                    kind="page"
                                                    page={page}
                                                    onPageChange={(patch) => setPage((p) => {
                                                        const next = { ...p };
                                                        for (const key of Object.keys(patch)) {
                                                            if (patch[key] && typeof patch[key] === 'object' && !Array.isArray(patch[key]) && p[key] && typeof p[key] === 'object' && !Array.isArray(p[key])) {
                                                                next[key] = { ...p[key], ...patch[key] };
                                                            } else {
                                                                next[key] = patch[key];
                                                            }
                                                        }
                                                        return next;
                                                    })}
                                                    uploadUrl={uploadUrl}
                                                    megaMenus={megaMenus}
                                                    globals={globals}
                                                    editTab={editTab}
                                                    layoutType={layoutType}
                                                />
                                            ) : (
                                            <div className="space-y-4 px-4">
                                                <div>
                                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Page Title</label>
                                                    <Input
                                                        value={page.meta?.title || ''}
                                                        onChange={(v) => setPage((p) => ({ ...p, meta: { ...p.meta, title: v } }))}
                                                        placeholder="Enter page title"
                                                    />
                                                </div>

                                                <div>
                                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Meta Description</label>
                                                    <textarea
                                                        value={page.meta?.description || ''}
                                                        onChange={(e) =>
                                                            setPage((p) => ({
                                                                ...p,
                                                                meta: { ...p.meta, description: e.target.value },
                                                            }))
                                                        }
                                                        placeholder="Enter meta description"
                                                        rows={3}
                                                        className="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm placeholder-gray-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-400"
                                                    />
                                                </div>

                                                <div>
                                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Meta Keywords</label>
                                                    <Input
                                                        value={page.meta?.keywords || ''}
                                                        onChange={(v) => setPage((p) => ({ ...p, meta: { ...p.meta, keywords: v } }))}
                                                        placeholder="keyword1, keyword2, keyword3"
                                                    />
                                                </div>

                                                <div>
                                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Featured Image</label>
                                                    <ImageUrlUploadControl
                                                        value={page.meta?.featuredImage || ''}
                                                        onChange={(v) =>
                                                            setPage((p) => ({
                                                                ...p,
                                                                meta: { ...p.meta, featuredImage: v },
                                                            }))
                                                        }
                                                        uploadUrl={uploadUrl}
                                                        onUploaded={(url) =>
                                                            setPage((p) => ({
                                                                ...p,
                                                                meta: { ...p.meta, featuredImage: url },
                                                            }))
                                                        }
                                                        placeholder="Enter image URL"
                                                    />
                                                </div>
                                            </div>
                                            )
                                        ) : null}

                                        {selectedModel.kind === 'section' || selectedModel.kind === 'column' || selectedModel.kind === 'grid_column' ? (
                                            <SectionInspector
                                                model={
                                                    selectedModel.kind === 'section'
                                                        ? selectedModel.section
                                                        : selectedModel.kind === 'column'
                                                        ? selectedModel.column
                                                        : selectedModel.gridColumn
                                                }
                                                uploadUrl={uploadUrl}
                                                editTab={editTab}
                                                kind={selectedModel.kind}
                                                globals={globals}
                                                onChange={
                                                    selectedModel.kind === 'section'
                                                        ? (patch) => updateSection(selectedModel.section.id, patch)
                                                        : selectedModel.kind === 'column'
                                                        ? (patch) => updateColumn(selectedModel.section.id, selectedModel.column.id, patch)
                                                        : (patch) => updateGridColumn(selectedModel.section.id, selectedModel.column.id, selectedModel.gridWidget.id, selectedModel.gridColumn.id, patch)
                                                }
                                            />
                                        ) : null}

                                        {selectedModel.kind === 'tab_item' ? (
                                            <div className="space-y-4">
                                                <div>
                                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tab Title</label>
                                                    <Input
                                                        value={selectedModel.tabItem.title || ''}
                                                        onChange={(v) =>
                                                            updateTabItem(
                                                                selectedModel.section.id,
                                                                selectedModel.column.id,
                                                                selectedModel.tabWidget.id,
                                                                selectedModel.tabItem.id,
                                                                { title: v }
                                                            )
                                                        }
                                                    />
                                                </div>
                                                <div className="text-xs text-gray-500">
                                                    Add widgets to this tab from the Elements panel. Select a nested widget inside the tab to edit it.
                                                </div>
                                            </div>
                                        ) : null}

                                        {selectedModel.kind === 'grid_widget' && selectedModel.widget.type === 'grid' ? (
                                            <GridInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                editTab={editTab}
                                                globals={globals}
                                                onChange={(patch) => updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)}
                                            />
                                        ) : null}

                                        {(selectedModel.kind === 'widget' || selectedModel.kind === 'tab_widget') ? (
                                            selectedModel.widget.type === 'grid' ? (
                                                <GridInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    editTab={editTab}
                                                    globals={globals}
                                                    onChange={(patch) =>
                                                        updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'heading' ? (
                                                <WidgetInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    megaMenus={megaMenus}
                                                    editTab={editTab}
                                                    globals={globals}
                                                    onChange={(patch) =>
                                                        updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'rich_text' ? (
                                                <WidgetInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    megaMenus={megaMenus}
                                                    editTab={editTab}
                                                    globals={globals}
                                                    onChange={(patch) =>
                                                        updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'button' ? (
                                                <ButtonContentInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    megaMenus={megaMenus}
                                                    globals={globals}
                                                    onChange={(patch) =>
                                                        updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'social_proof' ? (
                                                <SocialProofContentInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    onChange={(patch) =>
                                                        updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'button_group' ? (
                                                <ButtonGroupContentInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    megaMenus={megaMenus}
                                                    globals={globals}
                                                    onChange={(patch) =>
                                                        updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'badge' ? (
                                                <WidgetInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    megaMenus={megaMenus}
                                                    editTab={editTab}
                                                    globals={globals}
                                                    onChange={(patch) =>
                                                        updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'pricing_table' ? (
                                                <PricingTableContentInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    onChange={(patch) =>
                                                        updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'slider' ? (
                                                <SliderContentInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    onChange={(patch) =>
                                                        updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'marquee' ? (
                                                <MarqueeContentInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    onChange={(patch) =>
                                                        updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'text_timeline' ? (
                                                <TextTimelineContentInspector
                                                    widget={selectedModel.widget}
                                                    onChange={(patch) =>
                                                        updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'faqs' ? (
                                                <WidgetInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    megaMenus={megaMenus}
                                                    editTab={editTab}
                                                    globals={globals}
                                                    onChange={(patch) =>
                                                        updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'tabs' ? (
                                                <TabsContentInspector
                                                    widget={selectedModel.widget}
                                                    onChange={(patch) =>
                                                        updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'nav_menu' ? (
                                                <NavMenuContentInspector
                                                    widget={selectedModel.widget}
                                                    megaMenus={megaMenus}
                                                    navMenus={navMenus}
                                                    onChange={(patch) =>
                                                        updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'icon' ? (
                                                <WidgetInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    megaMenus={megaMenus}
                                                    editTab={editTab}
                                                    globals={globals}
                                                    onChange={(patch) =>
                                                        updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'social_icons' ? (
                                                <SocialIconsContentInspector
                                                    widget={selectedModel.widget}
                                                    onChange={(patch) =>
                                                        updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'newsletter_form' ? (
                                                <NewsletterFormContentInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    lists={lists}
                                                    onChange={(patch) =>
                                                        updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                    }
                                                />
                                            ) : selectedModel.widget.type.startsWith('form_') ? (
                                                <WidgetInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    megaMenus={megaMenus}
                                                    editTab={editTab}
                                                    globals={globals}
                                                    onChange={(patch) =>
                                                        updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                    }
                                                />
                                            ) : (
                                                <WidgetInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    megaMenus={megaMenus}
                                                    editTab={editTab}
                                                    globals={globals}
                                                    onChange={(patch) =>
                                                        updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                    }
                                                />
                                            )
                                        ) : null}

                                        {selectedModel.kind === 'grid_widget' ? (
                                            selectedModel.widget.type === 'grid' ? null : selectedModel.widget.type === 'heading' ? (
                                                <WidgetInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    megaMenus={megaMenus}
                                                    editTab={editTab}
                                                    globals={globals}
                                                    onChange={(patch) =>
                                                        updateGridInnerWidget(
                                                            selectedModel.section.id,
                                                            selectedModel.column.id,
                                                            selectedModel.gridWidget.id,
                                                            selectedModel.gridColumn.id,
                                                            selectedModel.widget.id,
                                                            patch
                                                        )
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'rich_text' ? (
                                                <WidgetInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    megaMenus={megaMenus}
                                                    editTab={editTab}
                                                    globals={globals}
                                                    onChange={(patch) =>
                                                        updateGridInnerWidget(
                                                            selectedModel.section.id,
                                                            selectedModel.column.id,
                                                            selectedModel.gridWidget.id,
                                                            selectedModel.gridColumn.id,
                                                            selectedModel.widget.id,
                                                            patch
                                                        )
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'button' ? (
                                                <WidgetInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    megaMenus={megaMenus}
                                                    editTab={editTab}
                                                    globals={globals}
                                                    onChange={(patch) =>
                                                        updateGridInnerWidget(
                                                            selectedModel.section.id,
                                                            selectedModel.column.id,
                                                            selectedModel.gridWidget.id,
                                                            selectedModel.gridColumn.id,
                                                            selectedModel.widget.id,
                                                            patch
                                                        )
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'button_group' ? (
                                                <ButtonGroupContentInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    megaMenus={megaMenus}
                                                    globals={globals}
                                                    onChange={(patch) =>
                                                        updateGridInnerWidget(
                                                            selectedModel.section.id,
                                                            selectedModel.column.id,
                                                            selectedModel.gridWidget.id,
                                                            selectedModel.gridColumn.id,
                                                            selectedModel.widget.id,
                                                            patch
                                                        )
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'badge' ? (
                                                <WidgetInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    megaMenus={megaMenus}
                                                    editTab={editTab}
                                                    globals={globals}
                                                    onChange={(patch) =>
                                                        updateGridInnerWidget(
                                                            selectedModel.section.id,
                                                            selectedModel.column.id,
                                                            selectedModel.gridWidget.id,
                                                            selectedModel.gridColumn.id,
                                                            selectedModel.widget.id,
                                                            patch
                                                        )
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'pricing_table' ? (
                                                <PricingTableContentInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    onChange={(patch) =>
                                                        updateGridInnerWidget(
                                                            selectedModel.section.id,
                                                            selectedModel.column.id,
                                                            selectedModel.gridWidget.id,
                                                            selectedModel.gridColumn.id,
                                                            selectedModel.widget.id,
                                                            patch
                                                        )
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'slider' ? (
                                                <SliderContentInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    onChange={(patch) =>
                                                        updateGridInnerWidget(
                                                            selectedModel.section.id,
                                                            selectedModel.column.id,
                                                            selectedModel.gridWidget.id,
                                                            selectedModel.gridColumn.id,
                                                            selectedModel.widget.id,
                                                            patch
                                                        )
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'marquee' ? (
                                                <MarqueeContentInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    onChange={(patch) =>
                                                        updateGridInnerWidget(
                                                            selectedModel.section.id,
                                                            selectedModel.column.id,
                                                            selectedModel.gridWidget.id,
                                                            selectedModel.gridColumn.id,
                                                            selectedModel.widget.id,
                                                            patch
                                                        )
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'text_timeline' ? (
                                                <TextTimelineContentInspector
                                                    widget={selectedModel.widget}
                                                    onChange={(patch) =>
                                                        updateGridInnerWidget(
                                                            selectedModel.section.id,
                                                            selectedModel.column.id,
                                                            selectedModel.gridWidget.id,
                                                            selectedModel.gridColumn.id,
                                                            selectedModel.widget.id,
                                                            patch
                                                        )
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'faqs' ? (
                                                <WidgetInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    megaMenus={megaMenus}
                                                    editTab={editTab}
                                                    globals={globals}
                                                    onChange={(patch) =>
                                                        updateGridInnerWidget(
                                                            selectedModel.section.id,
                                                            selectedModel.column.id,
                                                            selectedModel.gridWidget.id,
                                                            selectedModel.gridColumn.id,
                                                            selectedModel.widget.id,
                                                            patch
                                                        )
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'tabs' ? (
                                                <TabsContentInspector
                                                    widget={selectedModel.widget}
                                                    onChange={(patch) =>
                                                        updateGridInnerWidget(
                                                            selectedModel.section.id,
                                                            selectedModel.column.id,
                                                            selectedModel.gridWidget.id,
                                                            selectedModel.gridColumn.id,
                                                            selectedModel.widget.id,
                                                            patch
                                                        )
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'nav_menu' ? (
                                                <NavMenuContentInspector
                                                    widget={selectedModel.widget}
                                                    megaMenus={megaMenus}
                                                    navMenus={navMenus}
                                                    onChange={(patch) =>
                                                        updateGridInnerWidget(
                                                            selectedModel.section.id,
                                                            selectedModel.column.id,
                                                            selectedModel.gridWidget.id,
                                                            selectedModel.gridColumn.id,
                                                            selectedModel.widget.id,
                                                            patch
                                                        )
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'newsletter_form' ? (
                                                <NewsletterFormContentInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    lists={lists}
                                                    onChange={(patch) =>
                                                        updateGridInnerWidget(
                                                            selectedModel.section.id,
                                                            selectedModel.column.id,
                                                            selectedModel.gridWidget.id,
                                                            selectedModel.gridColumn.id,
                                                            selectedModel.widget.id,
                                                            patch
                                                        )
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'social_icons' ? (
                                                <SocialIconsContentInspector
                                                    widget={selectedModel.widget}
                                                    onChange={(patch) =>
                                                        updateGridInnerWidget(
                                                            selectedModel.section.id,
                                                            selectedModel.column.id,
                                                            selectedModel.gridWidget.id,
                                                            selectedModel.gridColumn.id,
                                                            selectedModel.widget.id,
                                                            patch
                                                        )
                                                    }
                                                />
                                            ) : selectedModel.widget.type === 'icon' ? (
                                                <WidgetInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    megaMenus={megaMenus}
                                                    editTab={editTab}
                                                    globals={globals}
                                                    onChange={(patch) =>
                                                        updateGridInnerWidget(
                                                            selectedModel.section.id,
                                                            selectedModel.column.id,
                                                            selectedModel.gridWidget.id,
                                                            selectedModel.gridColumn.id,
                                                            selectedModel.widget.id,
                                                            patch
                                                        )
                                                    }
                                                />
                                            ) : (
                                                <WidgetInspector
                                                    widget={selectedModel.widget}
                                                    uploadUrl={uploadUrl}
                                                    megaMenus={megaMenus}
                                                    editTab={editTab}
                                                    globals={globals}
                                                    onChange={(patch) =>
                                                        updateGridInnerWidget(
                                                            selectedModel.section.id,
                                                            selectedModel.column.id,
                                                            selectedModel.gridWidget.id,
                                                            selectedModel.gridColumn.id,
                                                            selectedModel.widget.id,
                                                            patch
                                                        )
                                                    }
                                                />
                                            )
                                        ) : null}

                                        {selectedModel.kind === 'none' ? <p className="text-sm text-gray-500">Select something.</p> : null}
                                    </div>
                                ) : null}

                                {editTab === 'style' ? (
                                    <div className="space-y-4">
                                        {selectedModel.kind === 'section' || selectedModel.kind === 'column' || selectedModel.kind === 'grid_column' ? (
                                            <SectionInspector
                                                model={
                                                    selectedModel.kind === 'section'
                                                        ? selectedModel.section
                                                        : selectedModel.kind === 'column'
                                                        ? selectedModel.column
                                                        : selectedModel.gridColumn
                                                }
                                                uploadUrl={uploadUrl}
                                                editTab={editTab}
                                                kind={selectedModel.kind}
                                                globals={globals}
                                                onChange={
                                                    selectedModel.kind === 'section'
                                                        ? (patch) => updateSection(selectedModel.section.id, patch)
                                                        : selectedModel.kind === 'column'
                                                        ? (patch) => updateColumn(selectedModel.section.id, selectedModel.column.id, patch)
                                                        : (patch) => updateGridColumn(selectedModel.section.id, selectedModel.column.id, selectedModel.gridWidget.id, selectedModel.gridColumn.id, patch)
                                                }
                                            />
                                        ) : null}

                                        {selectedModel.kind === 'grid_widget' && selectedModel.widget.type === 'grid' ? (
                                            <GridInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                mode="style"
                                                onChange={(patch) => updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)}
                                            />
                                        ) : null}
                                        {selectedModel.kind === null ? (
                                            <div className="space-y-4 px-4">
                                                <FourSideLinkedControl
                                                    label="Page Margin (px)"
                                                    value={page.style}
                                                    keys={{
                                                        linked: 'marginLinked',
                                                        t: 'marginTop',
                                                        r: 'marginRight',
                                                        b: 'marginBottom',
                                                        l: 'marginLeft',
                                                    }}
                                                    fallbackValue={page.style?.marginTop ?? 0}
                                                    onChange={(next) =>
                                                        setPage((p) => ({
                                                            ...p,
                                                            style: { ...(p.style || {}), ...next },
                                                        }))
                                                    }
                                                />

                                                <FourSideLinkedControl
                                                    label="Page Padding (px)"
                                                    value={page.style}
                                                    keys={{
                                                        linked: 'paddingLinked',
                                                        t: 'paddingTop',
                                                        r: 'paddingRight',
                                                        b: 'paddingBottom',
                                                        l: 'paddingLeft',
                                                    }}
                                                    fallbackValue={page.style?.paddingTop ?? 0}
                                                    onChange={(next) =>
                                                        setPage((p) => ({
                                                            ...p,
                                                            style: { ...(p.style || {}), ...next },
                                                        }))
                                                    }
                                                />

                                                <div>
                                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Page Width</label>
                                                    <div className="flex items-center gap-2 mb-2">
                                                        <button
                                                            type="button"
                                                            className={`flex-1 py-2 text-xs rounded border ${(page.style?.pageMaxWidthType !== 'boxed') ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600'}`}
                                                            onClick={() => setPage((p) => ({ ...p, style: { ...(p.style || {}), pageMaxWidthType: 'full' } }))}
                                                        >
                                                            Full width
                                                        </button>
                                                        <button
                                                            type="button"
                                                            className={`flex-1 py-2 text-xs rounded border ${(page.style?.pageMaxWidthType === 'boxed') ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600'}`}
                                                            onClick={() => setPage((p) => ({ ...p, style: { ...(p.style || {}), pageMaxWidthType: 'boxed', pageBoxedWidth: p.style?.pageBoxedWidth ?? 1152 } }))}
                                                        >
                                                            Boxed
                                                        </button>
                                                    </div>

                                                    {page.style?.pageMaxWidthType === 'boxed' ? (
                                                        <div>
                                                            <div className="flex items-center justify-between mb-1">
                                                                <span className="text-xs text-gray-500">Width</span>
                                                                <span className="text-xs font-medium text-gray-700 dark:text-gray-300">{Number.isFinite(Number(page.style?.pageBoxedWidth)) ? Number(page.style.pageBoxedWidth) : 1152}px</span>
                                                            </div>
                                                            <Range
                                                                min={320}
                                                                max={1920}
                                                                step={8}
                                                                value={String(Number.isFinite(Number(page.style?.pageBoxedWidth)) ? Number(page.style.pageBoxedWidth) : 1152)}
                                                                onChange={(e) => setPage((p) => ({ ...p, style: { ...(p.style || {}), pageBoxedWidth: Number(e.target.value) } }))}
                                                            />
                                                        </div>
                                                    ) : null}
                                                </div>

                                                <div>
                                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Background</label>
                                                    <Select
                                                        value={page.style?.backgroundType || 'color'}
                                                        onChange={(v) => setPage((p) => ({ ...p, style: { ...p.style, backgroundType: v } }))}
                                                        options={[
                                                            { value: 'color', label: 'Color' },
                                                            { value: 'gradient', label: 'Gradient' },
                                                            { value: 'image', label: 'Image' },
                                                        ]}
                                                    />
                                                </div>

                                                {page.style?.backgroundType === 'color' || !page.style?.backgroundType ? (
                                                    <div>
                                                        <div className="grid grid-cols-2 gap-3 items-center">
                                                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Background Color</label>
                                                            <div className="flex justify-end">
                                                                <ColorInput
                                                                    value={page.style?.backgroundColor || ''}
                                                                    onChange={(v) => setPage((p) => ({ ...p, style: { ...p.style, backgroundColor: v } }))}
                                                                    fallback="#ffffff"
                                                                />
                                                            </div>
                                                        </div>
                                                    </div>
                                                ) : null}

                                                {page.style?.backgroundType === 'gradient' ? (
                                                    <div className="space-y-3">
                                                        <div>
                                                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">From</label>
                                                            <ColorInput
                                                                value={page.style?.gradientFrom || ''}
                                                                onChange={(v) => setPage((p) => ({ ...p, style: { ...p.style, gradientFrom: v } }))}
                                                                fallback="#111827"
                                                            />
                                                        </div>
                                                        <div>
                                                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
                                                            <ColorInput
                                                                value={page.style?.gradientTo || ''}
                                                                onChange={(v) => setPage((p) => ({ ...p, style: { ...p.style, gradientTo: v } }))}
                                                                fallback="#3b82f6"
                                                            />
                                                        </div>
                                                        <div>
                                                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                                                            <Select
                                                                value={(page.style?.gradientType === 'radial' || page.style?.gradientType === 'linear') ? page.style.gradientType : 'linear'}
                                                                onChange={(v) =>
                                                                    setPage((p) => ({
                                                                        ...p,
                                                                        style: {
                                                                            ...p.style,
                                                                            gradientType: v,
                                                                            gradientPosition: v === 'radial' ? (p.style?.gradientPosition || 'top center') : p.style?.gradientPosition,
                                                                        },
                                                                    }))
                                                                }
                                                                options={[
                                                                    { value: 'linear', label: 'Linear' },
                                                                    { value: 'radial', label: 'Radial' },
                                                                ]}
                                                            />
                                                        </div>

                                                        {(page.style?.gradientType === 'radial') ? (
                                                            <div>
                                                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Position</label>
                                                                <Select
                                                                    value={typeof page.style?.gradientPosition === 'string' && page.style.gradientPosition ? page.style.gradientPosition : 'top center'}
                                                                    onChange={(v) => setPage((p) => ({ ...p, style: { ...p.style, gradientPosition: v } }))}
                                                                    options={[
                                                                        { value: 'top left', label: 'Top Left' },
                                                                        { value: 'top center', label: 'Top Center' },
                                                                        { value: 'top right', label: 'Top Right' },
                                                                        { value: 'center left', label: 'Center Left' },
                                                                        { value: 'center center', label: 'Center' },
                                                                        { value: 'center right', label: 'Center Right' },
                                                                        { value: 'bottom left', label: 'Bottom Left' },
                                                                        { value: 'bottom center', label: 'Bottom Center' },
                                                                        { value: 'bottom right', label: 'Bottom Right' },
                                                                    ]}
                                                                />
                                                            </div>
                                                        ) : (
                                                        <div>
                                                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Angle</label>
                                                            <Input
                                                                type="number"
                                                                value={String(page.style?.gradientAngle ?? 135)}
                                                                onChange={(v) =>
                                                                    setPage((p) => ({ ...p, style: { ...p.style, gradientAngle: Number(v) } }))
                                                                }
                                                            />
                                                        </div>
                                                        )}
                                                    </div>
                                                ) : null}

                                                {page.style?.backgroundType === 'image' ? (
                                                    <div className="space-y-2">
                                                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Background Image</label>
                                                        <ImageUrlUploadControl
                                                            value={(page.style?.backgroundImage && page.style.backgroundImage.url) || ''}
                                                            onChange={(v) =>
                                                                setPage((p) => ({
                                                                    ...p,
                                                                    style: { ...p.style, backgroundImage: { url: v } },
                                                                }))
                                                            }
                                                            uploadUrl={uploadUrl}
                                                            onUploaded={(url) =>
                                                                setPage((p) => ({
                                                                    ...p,
                                                                    style: { ...p.style, backgroundImage: { url } },
                                                                }))
                                                            }
                                                            placeholder="Enter image URL"
                                                        />

                                                        <div className="grid grid-cols-2 gap-3">
                                                            <div>
                                                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Size</label>
                                                                <Select
                                                                    value={
                                                                        ['cover', 'contain', 'auto'].includes(page.style?.backgroundSize)
                                                                            ? page.style.backgroundSize
                                                                            : 'custom'
                                                                    }
                                                                    onChange={(v) =>
                                                                        setPage((p) => ({
                                                                            ...p,
                                                                            style: {
                                                                                ...p.style,
                                                                                backgroundSize: v === 'custom' ? (p.style?.backgroundSize || '100% 100%') : v,
                                                                            },
                                                                        }))
                                                                    }
                                                                    options={[
                                                                        { value: 'cover', label: 'Cover' },
                                                                        { value: 'contain', label: 'Contain' },
                                                                        { value: 'auto', label: 'Auto' },
                                                                        { value: 'custom', label: 'Custom' },
                                                                    ]}
                                                                />
                                                            </div>
                                                            <div>
                                                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Repeat</label>
                                                                <Select
                                                                    value={page.style?.backgroundRepeat || 'no-repeat'}
                                                                    onChange={(v) => setPage((p) => ({ ...p, style: { ...p.style, backgroundRepeat: v } }))}
                                                                    options={[
                                                                        { value: 'no-repeat', label: 'No repeat' },
                                                                        { value: 'repeat', label: 'Repeat' },
                                                                        { value: 'repeat-x', label: 'Repeat X' },
                                                                        { value: 'repeat-y', label: 'Repeat Y' },
                                                                    ]}
                                                                />
                                                            </div>
                                                        </div>

                                                        {!(Array.isArray(page.style?.backgroundSize) || ['cover', 'contain', 'auto'].includes(page.style?.backgroundSize)) &&
                                                        (page.style?.backgroundSize || '') !== '' ? null :
                                                        (['cover', 'contain', 'auto'].includes(page.style?.backgroundSize) ? null : (
                                                            <div>
                                                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Custom size</label>
                                                                <Input
                                                                    value={page.style?.backgroundSize || ''}
                                                                    onChange={(v) => setPage((p) => ({ ...p, style: { ...p.style, backgroundSize: v } }))}
                                                                    placeholder="e.g. 100% 100%"
                                                                />
                                                            </div>
                                                        ))}

                                                        <div>
                                                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Position</label>
                                                            <Select
                                                                value={page.style?.backgroundPosition || 'center'}
                                                                onChange={(v) => setPage((p) => ({ ...p, style: { ...p.style, backgroundPosition: v } }))}
                                                                options={[
                                                                    { value: 'center', label: 'Center' },
                                                                    { value: 'top', label: 'Top' },
                                                                    { value: 'bottom', label: 'Bottom' },
                                                                    { value: 'left', label: 'Left' },
                                                                    { value: 'right', label: 'Right' },
                                                                    { value: 'top left', label: 'Top Left' },
                                                                    { value: 'top right', label: 'Top Right' },
                                                                    { value: 'bottom left', label: 'Bottom Left' },
                                                                    { value: 'bottom right', label: 'Bottom Right' },
                                                                ]}
                                                            />
                                                        </div>
                                                    </div>
                                                ) : null}
                                            </div>
                                        ) : null}

                                        {(selectedModel.kind === 'widget' || selectedModel.kind === 'tab_widget') && selectedModel.widget.type === 'button' ? (
                                            <ButtonStyleInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                megaMenus={megaMenus}
                                                globals={globals}
                                                onChange={(patch) =>
                                                    updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                }
                                            />
                                        ) : null}

                                        {(selectedModel.kind === 'widget' || selectedModel.kind === 'tab_widget') && selectedModel.widget.type === 'button_group' ? (
                                            <ButtonGroupStyleInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                megaMenus={megaMenus}
                                                globals={globals}
                                                onChange={(patch) =>
                                                    updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                }
                                            />
                                        ) : null}

                                        {(selectedModel.kind === 'widget' || selectedModel.kind === 'tab_widget') && selectedModel.widget.type === 'newsletter_form' ? (
                                            <NewsletterFormStyleInspector
                                                widget={selectedModel.widget}
                                                onChange={(patch) =>
                                                    updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                }
                                                globals={globals}
                                                uploadUrl={uploadUrl}
                                            />
                                        ) : null}

                                        {(selectedModel.kind === 'widget' || selectedModel.kind === 'tab_widget') && selectedModel.widget.type === 'social_icons' ? (
                                            <SocialIconsStyleInspector
                                                widget={selectedModel.widget}
                                                onChange={(patch) =>
                                                    updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                }
                                                globals={globals}
                                                uploadUrl={uploadUrl}
                                            />
                                        ) : null}

                                        {(selectedModel.kind === 'widget' || selectedModel.kind === 'tab_widget') && selectedModel.widget.type === 'social_proof' ? (
                                            <SocialProofStyleInspector
                                                widget={selectedModel.widget}
                                                onChange={(patch) =>
                                                    updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                }
                                            />
                                        ) : null}

                                        {(selectedModel.kind === 'widget' || selectedModel.kind === 'tab_widget') && selectedModel.widget.type === 'badge' ? (
                                            <WidgetInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                megaMenus={megaMenus}
                                                editTab={editTab}
                                                globals={globals}
                                                onChange={(patch) =>
                                                    updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                }
                                            />
                                        ) : null}

                                        {(selectedModel.kind === 'widget' || selectedModel.kind === 'tab_widget') && selectedModel.widget.type === 'heading' ? (
                                            <WidgetInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                megaMenus={megaMenus}
                                                editTab={editTab}
                                                globals={globals}
                                                onChange={(patch) =>
                                                    updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                }
                                            />
                                        ) : null}

                                        {(selectedModel.kind === 'widget' || selectedModel.kind === 'tab_widget') && selectedModel.widget.type === 'image' ? (
                                            <WidgetInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                megaMenus={megaMenus}
                                                editTab={editTab}
                                                globals={globals}
                                                onChange={(patch) =>
                                                    updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                }
                                            />
                                        ) : null}

                                        {(selectedModel.kind === 'widget' || selectedModel.kind === 'tab_widget') && selectedModel.widget.type === 'rich_text' ? (
                                            <WidgetInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                megaMenus={megaMenus}
                                                editTab={editTab}
                                                globals={globals}
                                                onChange={(patch) =>
                                                    updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                }
                                            />
                                        ) : null}

                                        {(selectedModel.kind === 'widget' || selectedModel.kind === 'tab_widget') && selectedModel.widget.type === 'pricing_table' ? (
                                            <PricingTableStyleInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                onChange={(patch) =>
                                                    updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                }
                                            />
                                        ) : null}

                                        {(selectedModel.kind === 'widget' || selectedModel.kind === 'tab_widget') && selectedModel.widget.type === 'slider' ? (
                                            <SliderStyleInspector
                                                widget={selectedModel.widget}
                                                onChange={(patch) =>
                                                    updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                }
                                            />
                                        ) : null}

                                         {(selectedModel.kind === 'widget' || selectedModel.kind === 'tab_widget') && selectedModel.widget.type === 'marquee' ? (
                                            <MarqueeStyleInspector
                                                widget={selectedModel.widget}
                                                onChange={(patch) =>
                                                    updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                }
                                            />
                                         ) : null}

                                         {(selectedModel.kind === 'widget' || selectedModel.kind === 'tab_widget') && selectedModel.widget.type === 'text_timeline' ? (
                                             <WidgetInspector
                                                 widget={selectedModel.widget}
                                                 uploadUrl={uploadUrl}
                                                 megaMenus={megaMenus}
                                                 editTab={editTab}
                                                 globals={globals}
                                                 onChange={(patch) =>
                                                     updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                 }
                                             />
                                         ) : null}

                                        {(selectedModel.kind === 'widget' || selectedModel.kind === 'tab_widget') && selectedModel.widget.type === 'faqs' ? (
                                            <WidgetInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                megaMenus={megaMenus}
                                                editTab={editTab}
                                                globals={globals}
                                                onChange={(patch) =>
                                                    updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                }
                                            />
                                        ) : null}

                                        {(selectedModel.kind === 'widget' || selectedModel.kind === 'tab_widget') && selectedModel.widget.type === 'tabs' ? (
                                            <TabsStyleInspector
                                                widget={selectedModel.widget}
                                                onChange={(patch) =>
                                                    updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                }
                                            />
                                        ) : null}

                                        {(selectedModel.kind === 'widget' || selectedModel.kind === 'tab_widget') && selectedModel.widget.type === 'icon' ? (
                                            <WidgetInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                megaMenus={megaMenus}
                                                editTab={editTab}
                                                globals={globals}
                                                onChange={(patch) =>
                                                    updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                }
                                            />
                                        ) : null}

                                        {(selectedModel.kind === 'widget' || selectedModel.kind === 'tab_widget') && selectedModel.widget.type === 'nav_menu' ? (
                                            <NavMenuStyleInspector
                                                widget={selectedModel.widget}
                                                onChange={(patch) =>
                                                    updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                }
                                            />
                                        ) : null}

                                        {(selectedModel.kind === 'widget' || selectedModel.kind === 'tab_widget') && selectedModel.widget.type.startsWith('form_') ? (
                                            <WidgetInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                megaMenus={megaMenus}
                                                editTab={editTab}
                                                globals={globals}
                                                onChange={(patch) =>
                                                    updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                }
                                            />
                                        ) : null}

                                        {(selectedModel.kind === 'widget' || selectedModel.kind === 'tab_widget') && (selectedModel.widget.type === 'login_form' || selectedModel.widget.type === 'register_form') ? (
                                            <WidgetInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                megaMenus={megaMenus}
                                                editTab={editTab}
                                                globals={globals}
                                                onChange={(patch) =>
                                                    updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                }
                                            />
                                        ) : null}

                                        {(selectedModel.kind === 'widget' || selectedModel.kind === 'tab_widget') && !(
                                            ['button', 'button_group', 'newsletter_form', 'social_icons', 'social_proof', 'badge', 'heading', 'image', 'rich_text', 'pricing_table', 'slider', 'marquee', 'text_timeline', 'faqs', 'tabs', 'icon', 'nav_menu', 'login_form', 'register_form', 'grid'].includes(selectedModel.widget.type)
                                            || selectedModel.widget.type.startsWith('form_')
                                        ) ? (
                                            <WidgetInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                megaMenus={megaMenus}
                                                editTab={editTab}
                                                globals={globals}
                                                onChange={(patch) =>
                                                    updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                }
                                            />
                                        ) : null}

                                        {selectedModel.kind === 'grid_widget' && (selectedModel.widget.type === 'login_form' || selectedModel.widget.type === 'register_form') ? (
                                            <WidgetInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                megaMenus={megaMenus}
                                                editTab={editTab}
                                                globals={globals}
                                                onChange={(patch) =>
                                                    updateGridInnerWidget(
                                                        selectedModel.section.id,
                                                        selectedModel.column.id,
                                                        selectedModel.gridWidget.id,
                                                        selectedModel.gridColumn.id,
                                                        selectedModel.widget.id,
                                                        patch
                                                    )
                                                }
                                            />
                                        ) : null}

                                        {selectedModel.kind === 'grid_widget' && selectedModel.widget.type === 'button' ? (
                                            <ButtonStyleInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                megaMenus={megaMenus}
                                                globals={globals}
                                                onChange={(patch) =>
                                                    updateGridInnerWidget(
                                                        selectedModel.section.id,
                                                        selectedModel.column.id,
                                                        selectedModel.gridWidget.id,
                                                        selectedModel.gridColumn.id,
                                                        selectedModel.widget.id,
                                                        patch
                                                    )
                                                }
                                            />
                                        ) : null}

                                        {selectedModel.kind === 'grid_widget' && selectedModel.widget.type === 'button_group' ? (
                                            <ButtonGroupStyleInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                megaMenus={megaMenus}
                                                globals={globals}
                                                onChange={(patch) =>
                                                    updateGridInnerWidget(
                                                        selectedModel.section.id,
                                                        selectedModel.column.id,
                                                        selectedModel.gridWidget.id,
                                                        selectedModel.gridColumn.id,
                                                        selectedModel.widget.id,
                                                        patch
                                                    )
                                                }
                                            />
                                        ) : null}

                                        {selectedModel.kind === 'grid_widget' && selectedModel.widget.type === 'newsletter_form' ? (
                                            <NewsletterFormStyleInspector
                                                widget={selectedModel.widget}
                                                onChange={(patch) =>
                                                    updateGridInnerWidget(
                                                        selectedModel.section.id,
                                                        selectedModel.column.id,
                                                        selectedModel.gridWidget.id,
                                                        selectedModel.gridColumn.id,
                                                        selectedModel.widget.id,
                                                        patch
                                                    )
                                                }
                                                globals={globals}
                                                uploadUrl={uploadUrl}
                                            />
                                        ) : null}

                                        {selectedModel.kind === 'grid_widget' && selectedModel.widget.type === 'social_icons' ? (
                                            <SocialIconsStyleInspector
                                                widget={selectedModel.widget}
                                                onChange={(patch) =>
                                                    updateGridInnerWidget(
                                                        selectedModel.section.id,
                                                        selectedModel.column.id,
                                                        selectedModel.gridWidget.id,
                                                        selectedModel.gridColumn.id,
                                                        selectedModel.widget.id,
                                                        patch
                                                    )
                                                }
                                                globals={globals}
                                                uploadUrl={uploadUrl}
                                            />
                                        ) : null}

                                        {selectedModel.kind === 'grid_widget' && selectedModel.widget.type === 'badge' ? (
                                            <WidgetInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                megaMenus={megaMenus}
                                                editTab={editTab}
                                                globals={globals}
                                                onChange={(patch) =>
                                                    updateGridInnerWidget(
                                                        selectedModel.section.id,
                                                        selectedModel.column.id,
                                                        selectedModel.gridWidget.id,
                                                        selectedModel.gridColumn.id,
                                                        selectedModel.widget.id,
                                                        patch
                                                    )
                                                }
                                            />
                                        ) : null}

                                        {selectedModel.kind === 'grid_widget' && selectedModel.widget.type === 'heading' ? (
                                            <WidgetInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                megaMenus={megaMenus}
                                                editTab={editTab}
                                                globals={globals}
                                                onChange={(patch) =>
                                                    updateGridInnerWidget(
                                                        selectedModel.section.id,
                                                        selectedModel.column.id,
                                                        selectedModel.gridWidget.id,
                                                        selectedModel.gridColumn.id,
                                                        selectedModel.widget.id,
                                                        patch
                                                    )
                                                }
                                            />
                                        ) : null}

                                        {selectedModel.kind === 'grid_widget' && selectedModel.widget.type === 'rich_text' ? (
                                            <WidgetInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                megaMenus={megaMenus}
                                                editTab={editTab}
                                                globals={globals}
                                                onChange={(patch) =>
                                                    updateGridInnerWidget(
                                                        selectedModel.section.id,
                                                        selectedModel.column.id,
                                                        selectedModel.gridWidget.id,
                                                        selectedModel.gridColumn.id,
                                                        selectedModel.widget.id,
                                                        patch
                                                    )
                                                }
                                            />
                                        ) : null}

                                        {selectedModel.kind === 'grid_widget' && selectedModel.widget.type === 'image' ? (
                                            <WidgetInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                megaMenus={megaMenus}
                                                editTab={editTab}
                                                globals={globals}
                                                onChange={(patch) =>
                                                    updateGridInnerWidget(
                                                        selectedModel.section.id,
                                                        selectedModel.column.id,
                                                        selectedModel.gridWidget.id,
                                                        selectedModel.gridColumn.id,
                                                        selectedModel.widget.id,
                                                        patch
                                                    )
                                                }
                                            />
                                        ) : null}

                                        {selectedModel.kind === 'grid_widget' && selectedModel.widget.type === 'pricing_table' ? (
                                            <PricingTableStyleInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                onChange={(patch) =>
                                                    updateGridInnerWidget(
                                                        selectedModel.section.id,
                                                        selectedModel.column.id,
                                                        selectedModel.gridWidget.id,
                                                        selectedModel.gridColumn.id,
                                                        selectedModel.widget.id,
                                                        patch
                                                    )
                                                }
                                            />
                                        ) : null}

                                        {selectedModel.kind === 'grid_widget' && selectedModel.widget.type === 'slider' ? (
                                            <SliderStyleInspector
                                                widget={selectedModel.widget}
                                                onChange={(patch) =>
                                                    updateGridInnerWidget(
                                                        selectedModel.section.id,
                                                        selectedModel.column.id,
                                                        selectedModel.gridWidget.id,
                                                        selectedModel.gridColumn.id,
                                                        selectedModel.widget.id,
                                                        patch
                                                    )
                                                }
                                            />
                                        ) : null}

                                        {selectedModel.kind === 'grid_widget' && selectedModel.widget.type === 'faqs' ? (
                                            <WidgetInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                megaMenus={megaMenus}
                                                editTab={editTab}
                                                globals={globals}
                                                onChange={(patch) =>
                                                    updateGridInnerWidget(
                                                        selectedModel.section.id,
                                                        selectedModel.column.id,
                                                        selectedModel.gridWidget.id,
                                                        selectedModel.gridColumn.id,
                                                        selectedModel.widget.id,
                                                        patch
                                                    )
                                                }
                                            />
                                        ) : null}

                                        {selectedModel.kind === 'grid_widget' && selectedModel.widget.type === 'tabs' ? (
                                            <TabsStyleInspector
                                                widget={selectedModel.widget}
                                                onChange={(patch) =>
                                                    updateGridInnerWidget(
                                                        selectedModel.section.id,
                                                        selectedModel.column.id,
                                                        selectedModel.gridWidget.id,
                                                        selectedModel.gridColumn.id,
                                                        selectedModel.widget.id,
                                                        patch
                                                    )
                                                }
                                            />
                                        ) : null}

                                        {selectedModel.kind === 'grid_widget' && selectedModel.widget.type === 'icon' ? (
                                            <WidgetInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                megaMenus={megaMenus}
                                                editTab={editTab}
                                                globals={globals}
                                                onChange={(patch) =>
                                                    updateGridInnerWidget(
                                                        selectedModel.section.id,
                                                        selectedModel.column.id,
                                                        selectedModel.gridWidget.id,
                                                        selectedModel.gridColumn.id,
                                                        selectedModel.widget.id,
                                                        patch
                                                    )
                                                }
                                            />
                                        ) : null}

                                        {selectedModel.kind === 'grid_widget' && selectedModel.widget.type === 'nav_menu' ? (
                                            <NavMenuStyleInspector
                                                widget={selectedModel.widget}
                                                onChange={(patch) =>
                                                    updateGridInnerWidget(
                                                        selectedModel.section.id,
                                                        selectedModel.column.id,
                                                        selectedModel.gridWidget.id,
                                                        selectedModel.gridColumn.id,
                                                        selectedModel.widget.id,
                                                        patch
                                                    )
                                                }
                                            />
                                        ) : null}

                                        {(selectedModel.kind === 'widget' || selectedModel.kind === 'tab_widget') && selectedModel.widget.type === 'grid' ? (
                                            <GridInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                mode="style"
                                                onChange={(patch) =>
                                                    updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, patch)
                                                }
                                            />
                                        ) : null}

                                        {selectedModel.kind === 'grid_widget' && !['grid', 'button', 'button_group', 'newsletter_form', 'social_icons', 'badge', 'heading', 'rich_text', 'image', 'pricing_table', 'slider', 'faqs', 'tabs', 'icon', 'nav_menu', 'login_form', 'register_form'].includes(selectedModel.widget.type) ? (
                                            <WidgetInspector
                                                widget={selectedModel.widget}
                                                uploadUrl={uploadUrl}
                                                megaMenus={megaMenus}
                                                editTab={editTab}
                                                globals={globals}
                                                onChange={(patch) =>
                                                    updateGridInnerWidget(
                                                        selectedModel.section.id,
                                                        selectedModel.column.id,
                                                        selectedModel.gridWidget.id,
                                                        selectedModel.gridColumn.id,
                                                        selectedModel.widget.id,
                                                        patch
                                                    )
                                                }
                                            />
                                        ) : null}

                                    </div>
                                ) : null}

                                {editTab === 'advanced' ? (
                                    <div className="space-y-4">
                                        {selectedModel.kind === null ? (
                                            <div className="space-y-4 px-4">
                                                <div>
                                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Custom CSS</label>
                                                    <textarea
                                                        value={page.customCss || ''}
                                                        onChange={(e) => setPage((p) => ({ ...p, customCss: e.target.value }))}
                                                        placeholder="/* Add custom CSS here */"
                                                        rows={10}
                                                        className="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-mono placeholder-gray-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-400"
                                                    />
                                                </div>

                                                {page.customCss ? (
                                                    <div>
                                                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">CSS Preview</label>
                                                        <div className="rounded-md border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
                                                            <pre className="text-xs text-gray-600 dark:text-gray-300 whitespace-pre-wrap font-mono">
                                                                {page.customCss}
                                                            </pre>
                                                        </div>
                                                    </div>
                                                ) : null}
                                            </div>
                                        ) : null}

                                        {selectedModel.kind === 'section' || selectedModel.kind === 'column' || selectedModel.kind === 'grid_column' ? (() => {
                                            const containerModel = selectedModel.kind === 'section'
                                                ? selectedModel.section
                                                : selectedModel.kind === 'column'
                                                    ? selectedModel.column
                                                    : selectedModel.gridColumn;
                                            const containerStyle = (containerModel && containerModel.style) || {};
                                            const containerAdvanced = containerModel ? containerModel.advanced : undefined;
                                            const applyContainerPatch = (patch) => {
                                                if (selectedModel.kind === 'section') {
                                                    updateSection(selectedModel.section.id, patch);
                                                    return;
                                                }
                                                if (selectedModel.kind === 'column') {
                                                    updateColumn(selectedModel.section.id, selectedModel.column.id, patch);
                                                    return;
                                                }
                                                updateGridColumn(
                                                    selectedModel.section.id,
                                                    selectedModel.column.id,
                                                    selectedModel.gridWidget.id,
                                                    selectedModel.gridColumn.id,
                                                    patch
                                                );
                                            };

                                            return (
                                            <MotionInspector
                                                targetType="container"
                                                hideMotion={mode === 'email'}
                                                value={containerStyle}
                                                onChange={(patch) => applyContainerPatch({ style: { ...containerStyle, ...patch } })}
                                                advanced={containerAdvanced}
                                                onAdvancedChange={(nextAdv) => applyContainerPatch({ advanced: nextAdv })}
                                                backgroundPreset={
                                                    selectedModel.kind === 'section' ? (
                                                        <StyleAccordionItem title="Background Preset" defaultOpen={false}>
                                                            <div className="px-4">
                                                                <SectionBackgroundPresetInspector
                                                                    value={selectedModel.section.style?.backgroundPreset}
                                                                    onChange={(preset) =>
                                                                        updateSection(selectedModel.section.id, {
                                                                            style: {
                                                                                ...(selectedModel.section.style || {}),
                                                                                backgroundPreset: preset,
                                                                            },
                                                                        })
                                                                    }
                                                                />
                                                            </div>
                                                        </StyleAccordionItem>
                                                    ) : null
                                                }
                                                pricingTabs={
                                                    sectionHasStandalonePricingTable(selectedModel.section) ? (
                                                        <StyleAccordionItem title="Pricing Section" defaultOpen={true}>
                                                            <div className="px-4">
                                                                <PricingSectionTabsInspector
                                                                    value={selectedModel.section}
                                                                    onChange={(patch) => updateSection(selectedModel.section.id, patch)}
                                                                />
                                                            </div>
                                                        </StyleAccordionItem>
                                                    ) : null
                                                }
                                                headerSettings={
                                                    layoutType === 'header' ? (
                                                        <StyleAccordionItem title="Header settings" defaultOpen={true}>
                                                            <div className="space-y-4">
                                                                <ControlsRenderer
                                                                    schema={headerSettingsControls}
                                                                    value={page}
                                                                    onChange={handlePageChange}
                                                                    globals={globals}
                                                                    uploadUrl={uploadUrl}
                                                                />
                                                            </div>
                                                        </StyleAccordionItem>
                                                    ) : null
                                                }
                                            />
                                            );
                                        })() : null}

                                        {selectedModel.kind === 'widget' ? (
                                            <>
                                                <MotionInspector
                                                    targetType={selectedModel.widget.type}
                                                    value={selectedModel.widget.props}
                                                    onChange={(patch) =>
                                                        updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, {
                                                            props: { ...(selectedModel.widget.props || {}), ...patch },
                                                        })
                                                    }
                                                    advanced={selectedModel.widget.advanced}
                                                    onAdvancedChange={(nextAdv) =>
                                                        updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, {
                                                            advanced: nextAdv,
                                                        })
                                                    }
                                                    pricingTabs={
                                                        selectedModel.widget.type === 'grid' && gridHasPricingTable(selectedModel.widget) ? (
                                                            <StyleAccordionItem title="Pricing Section" defaultOpen={true}>
                                                                <div className="px-4">
                                                                    <PricingSectionTabsInspector
                                                                        value={selectedModel.widget.props || {}}
                                                                        onChange={(patch) =>
                                                                            updateWidget(selectedModel.section.id, selectedModel.column.id, selectedModel.widget.id, {
                                                                                props: { ...(selectedModel.widget.props || {}), ...patch },
                                                                            })
                                                                        }
                                                                    />
                                                                </div>
                                                            </StyleAccordionItem>
                                                        ) : null
                                                    }
                                                />
                                            </>
                                        ) : null}

                                        {selectedModel.kind === 'grid_widget' ? (
                                            <>
                                                <MotionInspector
                                                    targetType={selectedModel.widget.type}
                                                    value={selectedModel.widget.props}
                                                    onChange={(patch) =>
                                                        updateGridInnerWidget(
                                                            selectedModel.section.id,
                                                            selectedModel.column.id,
                                                            selectedModel.gridWidget.id,
                                                            selectedModel.gridColumn.id,
                                                            selectedModel.widget.id,
                                                            { props: { ...(selectedModel.widget.props || {}), ...patch } }
                                                        )
                                                    }
                                                    advanced={selectedModel.widget.advanced}
                                                    onAdvancedChange={(nextAdv) =>
                                                        updateGridInnerWidget(
                                                            selectedModel.section.id,
                                                            selectedModel.column.id,
                                                            selectedModel.gridWidget.id,
                                                            selectedModel.gridColumn.id,
                                                            selectedModel.widget.id,
                                                            { advanced: nextAdv }
                                                        )
                                                    }
                                                />
                                            </>
                                        ) : null}

                                        {selectedModel.kind === 'none' ? <p className="text-sm text-gray-500">Select something.</p> : null}
                                    </div>
                                ) : null}
                                </ColorPresetsProvider>
                            </div>
                        ) : null}
                    </div>
                </div>

                <div ref={previewScrollRef} className="flex-1 min-w-0 overflow-auto" style={{ transform: 'translate(0,0)' }}>
                    <div className="min-h-full" style={pageBackgroundStyle(page.style)}>
                        <div className="px-10 py-10">
                            <div className={`mx-auto ${canvasMaxWidthClass} transition-all duration-300`} style={pageContentStyle(page.style)}>
                                <div
                                    className="space-y-0"
                                    onDragOver={(e) => e.preventDefault()}
                                    onDrop={(e) => {
                                        e.preventDefault();
                                        const data = getDragData(e);
                                        if (!data) return;
                                        if (data.kind === 'container') {
                                            addSection();
                                        }
                                        if (data.kind === 'mobile_menu') {
                                            addMobileMenu();
                                        }
                                    }}
                                >
                                {customMotionKeyframesCss ? <style>{customMotionKeyframesCss}</style> : null}
                                {page.style?.headerSticky ? <style>{'.mp-sticky-header a{color:var(--mp-sticky-link-color,inherit);}'}</style> : null}
                                {(Array.isArray(page.sections) ? page.sections : []).map((s, sectionIndex) => {
                                    const sections = Array.isArray(page.sections) ? page.sections : [];
                                    const isSectionActive =
                                        hoveredSectionId === s.id || (selected.kind === 'section' && selected.sectionId === s.id);
                                    const isSectionHovered = hoveredSectionId === s.id;

                                    const sAdv = advancedReactAttrs(s.advanced);

                                    const sStyle = s.style && typeof s.style === 'object' ? s.style : {};
                                    const sectionSpacingBottom = sectionIndex < sections.length - 1 ? 1 : 0;
                                    const direction = ['row', 'column', 'row-reverse', 'column-reverse'].includes(
                                        pickResponsive(sStyle.itemsDirection, sStyle.itemsDirectionTablet, sStyle.itemsDirectionMobile)
                                    )
                                        ? pickResponsive(sStyle.itemsDirection, sStyle.itemsDirectionTablet, sStyle.itemsDirectionMobile)
                                        : 'row';
                                    const justifyContent = [
                                        'flex-start',
                                        'center',
                                        'flex-end',
                                        'space-between',
                                        'space-around',
                                        'space-evenly',
                                    ].includes(sStyle.itemsJustify)
                                        ? sStyle.itemsJustify
                                        : 'flex-start';
                                    const alignItems = ['stretch', 'flex-start', 'center', 'flex-end', 'baseline'].includes(sStyle.itemsAlign)
                                        ? sStyle.itemsAlign
                                        : 'stretch';
                                    const sGapFallback = Number.isFinite(Number(sStyle.gap)) ? Number(sStyle.gap) : 24;
                                    const sGapX = Number.isFinite(Number(sStyle.gapX)) ? Number(sStyle.gapX) : sGapFallback;
                                    const sGapY = Number.isFinite(Number(sStyle.gapY)) ? Number(sStyle.gapY) : sGapFallback;
                                    const sWrap = computeWrapPerRow(sStyle);
                                    const sColumnCount = Array.isArray(s.columns) ? s.columns.length : 0;
                                    const sWrapPerRow = sWrap ? Math.min(sWrap.n, Math.max(1, sColumnCount)) : 0;
                                    const sWrapBasis = sWrapPerRow >= 1 ? `calc((100% - ${(sWrapPerRow - 1) * sGapX}px) / ${sWrapPerRow})` : null;
                                    const isHeaderSection = sectionIndex === 0 && page.style?.headerSticky === true;
                                    const stickyConfig = isHeaderSection ? {
                                        active: headerStickyActive,
                                        width: page.style?.stickyWidth,
                                        height: page.style?.stickyHeight,
                                        backgroundType: page.style?.stickyBackgroundType,
                                        backgroundColor: page.style?.stickyBackgroundColor,
                                        backdropBlur: page.style?.stickyBackdropBlur,
                                        gradientFrom: page.style?.stickyGradientFrom,
                                        gradientTo: page.style?.stickyGradientTo,
                                        gradientAngle: page.style?.stickyGradientAngle,
                                        gradientType: page.style?.stickyGradientType,
                                        gradientPosition: page.style?.stickyGradientPosition,
                                        borderStyle: page.style?.stickyBorderStyle,
                                        borderColor: page.style?.stickyBorderColor,
                                        borderWidthTop: page.style?.stickyBorderWidthTop,
                                        borderWidthRight: page.style?.stickyBorderWidthRight,
                                        borderWidthBottom: page.style?.stickyBorderWidthBottom,
                                        borderWidthLeft: page.style?.stickyBorderWidthLeft,
                                        borderRadiusTop: page.style?.stickyBorderRadiusTop,
                                        borderRadiusRight: page.style?.stickyBorderRadiusRight,
                                        borderRadiusBottom: page.style?.stickyBorderRadiusBottom,
                                        borderRadiusLeft: page.style?.stickyBorderRadiusLeft,
                                        textColor: page.style?.stickyTextColor,
                                        linkColor: page.style?.stickyLinkColor,
                                        marginTop: page.style?.stickyMarginTop,
                                        marginRight: page.style?.stickyMarginRight,
                                        marginBottom: page.style?.stickyMarginBottom,
                                        marginLeft: page.style?.stickyMarginLeft,
                                    } : null;
                                    const sectionStyle = {
                                        ...sectionCanvasStyle(s.style, s.id, sectionIndex, stickyConfig),
                                        ...(sAdv.style || {}),
                                        ...(sStyle && sStyle.gridOverlay ? {
                                            '--mp-grid-color': sStyle.gridColor || 'rgba(0,0,0,0.15)',
                                            '--mp-grid-size': `${sStyle.gridSize || 40}px`,
                                            '--mp-grid-opacity': sStyle.gridOpacity ?? 0.5,
                                        } : {}),
                                    };
                                    const currentMarginBottom = typeof sectionStyle.marginBottom === 'string'
                                        ? parseFloat(sectionStyle.marginBottom)
                                        : Number(sectionStyle.marginBottom);
                                    sectionStyle.marginBottom = Math.max(Number.isFinite(currentMarginBottom) ? currentMarginBottom : 0, sectionSpacingBottom);

                                    const spanForViewport = (c) => {
                                        if (!c || !c.spans) return 12;
                                        if (viewport === 'desktop') return Number(c.spans.lg || c.spans.md || c.spans.base || 12);
                                        if (viewport === 'tablet') {
                                            const v = Number(c.spans.md || c.spans.base || 12);
                                            return Number.isFinite(v) && v > 0 ? Math.max(6, Math.min(12, v)) : 12;
                                        }
                                        return 12;
                                    };

                                    return (
                                        <React.Fragment key={s.id}>
                                            {topInsertSectionId === s.id ? (
                                                <div className="flex items-center justify-center gap-3 py-6" onClick={(e) => e.stopPropagation()}>
                                                    <button
                                                        type="button"
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            insertSectionBefore(s.id, blankSectionFactory);
                                                            setTopInsertSectionId(null);
                                                        }}
                                                        className="h-10 px-4 rounded-md bg-blue-600 hover:bg-blue-700 text-white shadow flex items-center gap-2"
                                                        title="Add section"
                                                    >
                                                        <span aria-hidden className="text-lg leading-none">+</span>
                                                        <span className="text-sm font-semibold">Add section</span>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            setInsertModal({ beforeSectionId: s.id, mode: 'templates' });
                                                            setTopInsertSectionId(null);
                                                        }}
                                                        className="h-10 px-4 rounded-md bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 shadow-sm"
                                                        title="Templates"
                                                    >
                                                        <span className="text-sm font-semibold">Templates</span>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            setTopInsertSectionId(null);
                                                        }}
                                                        className="h-10 w-10 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 flex items-center justify-center"
                                                        title="Close"
                                                        aria-label="Close"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                                    </button>
                                                </div>
                                            ) : null}
                                            <div
                                                id={sAdv.id}
                                                data-mp-nav-section={s.id}
                                                data-mp-cache={sAdv.cacheAttr['data-mp-cache']}
                                                className={mergeClassNames(
                                                    'group relative transition-all',
                                                    isSectionActive ? 'ring-1 ring-gray-300 z-10' : '',
                                                    sAdv.className
                                                    , sStyle && sStyle.gradientAnimate && sStyle.backgroundType === 'gradient' ? 'mp-gradient-animated' : ''
                                                    , sStyle && sStyle.gridOverlay ? 'mp-grid-overlay' : ''
                                                    , sStyle && sStyle.stackedSticky === true ? 'mp-section-stacked' : ''
                                                    , isHeaderSection ? 'mp-sticky-header' : ''
                                                )}
                                                style={sectionStyle}
                                                onMouseEnter={() => setHoveredSectionId(s.id)}
                                                onMouseLeave={() => {
                                                    setHoveredSectionId((curr) => (curr === s.id ? null : curr));
                                                    setHoveredColumnKey((curr) => (typeof curr === 'string' && curr.startsWith(`${s.id}:`) ? null : curr));
                                                    setHoveredGridWidgetKey((curr) => (typeof curr === 'string' && curr.startsWith(`${s.id}:`) ? null : curr));
                                                    setHoveredGridColumnKey((curr) => (typeof curr === 'string' && curr.startsWith(`${s.id}:`) ? null : curr));
                                                }}
                                                onClick={() => setSelected({ kind: 'section', sectionId: s.id })}
                                                onContextMenu={(e) => handleContextMenu(e, { kind: 'section', sectionId: s.id, section: s })}
                                                onDragOver={(e) => e.preventDefault()}
                                                onDrop={(e) => {
                                                    e.preventDefault();
                                                    e.stopPropagation();
                                                    const data = getDragData(e);
                                                    if (!data) return;
                                                    if (data.kind === 'grid') {
                                                        ensureSectionColumnsCount(s.id, data.count || 2);
                                                    }
                                                    if (data.kind === 'container') {
                                                        insertSectionAfter(s.id, blankSectionFactory);
                                                    }
                                                    if (data.kind === 'mobile_menu') {
                                                        const firstColId = s.columns && s.columns[0] ? s.columns[0].id : null;
                                                        if (firstColId) {
                                                            addWidgetToColumn(s.id, firstColId, 'grid', { ...MOBILE_MENU_DEFAULTS, count: 1, mobileMenu: true });
                                                        }
                                                    }
                                                }}
                                            >
                                            {sStyle?.backgroundPreset?.key && sStyle.backgroundPreset.key !== 'css-none' ? (
                                                <SectionBackgroundPresetOverlay
                                                    presetOrKey={sStyle.backgroundPreset.key}
                                                    params={sStyle.backgroundPreset.params || {}}
                                                    sectionId={s.id}
                                                />
                                            ) : null}
                                            {isSectionHovered ? (
                                                <div
                                                    className="absolute z-20"
                                                    style={{ top: 0, left: 8 }}
                                                >
                                                    <div className="bg-blue-600 text-white px-3 py-1 text-xs font-semibold flex items-center gap-2">
                                                        <span>{s.name || 'Section'}</span>
                                                    </div>
                                                </div>
                                            ) : null}

                                            {isSectionHovered ? (
                                                <div
                                                    className="absolute z-20"
                                                    style={{ top: -36, left: '50%', transform: 'translate(-50%, 0)' }}
                                                >
                                                    <div className="flex items-center bg-white border border-gray-200 shadow">
                                                        <button
                                                            type="button"
                                                            onClick={(e) => {
                                                                e.stopPropagation();
                                                                setTopInsertSectionId((curr) => (curr === s.id ? null : s.id));
                                                            }}
                                                            className={`h-9 w-10 flex items-center justify-center hover:bg-gray-50 ${topInsertSectionId === s.id ? 'bg-gray-100' : ''}`}
                                                            title="Add section"
                                                        >
                                                            <span aria-hidden>
                                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_403_3396)">
<path d="M24 11H13V0H11V11H0V13H11V24H13V13H24V11Z" fill="#374957"/>
</g>
<defs>
<clipPath id="clip0_403_3396">
<rect width="24" height="24" fill="white"/>
</clipPath>
</defs>
</svg>

                                                            </span>
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={(e) => {
                                                                e.stopPropagation();
                                                                const nextCount = (Array.isArray(s.columns) ? s.columns.length : 0) + 1;
                                                                ensureSectionColumnsCount(s.id, nextCount);
                                                            }}
                                                            className="h-9 w-10 flex items-center justify-center hover:bg-gray-50"
                                                            title="Add Column"
                                                        >
                                                            <span aria-hidden>
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-grid2x2-plus-icon lucide-grid-2x2-plus"><path d="M12 3v17a1 1 0 0 1-1 1H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6a1 1 0 0 1-1 1H3"/><path d="M16 19h6"/><path d="M19 22v-6"/></svg>
                                                            </span>
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={(e) => {
                                                                e.stopPropagation();
                                                                duplicateSection(s.id);
                                                            }}
                                                            className="h-9 w-10 flex items-center justify-center hover:bg-gray-50"
                                                            title="Duplicate"
                                                        >
                                                            <span aria-hidden>
                                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <g clip-path="url(#clip0_403_3199)">
                                                                    <path d="M13.0001 20.0001C14.3257 19.9986 15.5966 19.4713 16.5339 18.5339C17.4712 17.5966 17.9985 16.3257 18.0001 15.0001V6.24314C18.0017 5.7175 17.8989 5.19678 17.6977 4.71116C17.4965 4.22554 17.2009 3.78468 16.8281 3.41414L14.5861 1.17214C14.2156 0.799313 13.7747 0.503742 13.2891 0.30256C12.8035 0.101377 12.2828 -0.00141588 11.7571 0.00013946H7.00012C5.67453 0.00172732 4.40368 0.529021 3.46634 1.46636C2.529 2.4037 2.00171 3.67454 2.00012 5.00014V15.0001C2.00171 16.3257 2.529 17.5966 3.46634 18.5339C4.40368 19.4713 5.67453 19.9986 7.00012 20.0001H13.0001ZM4.00012 15.0001V5.00014C4.00012 4.20449 4.31619 3.44143 4.8788 2.87882C5.44141 2.31621 6.20447 2.00014 7.00012 2.00014C7.00012 2.00014 11.9191 2.01414 12.0001 2.02414V4.00014C12.0001 4.53057 12.2108 5.03928 12.5859 5.41435C12.961 5.78943 13.4697 6.00014 14.0001 6.00014H15.9761C15.9861 6.08114 16.0001 15.0001 16.0001 15.0001C16.0001 15.7958 15.6841 16.5589 15.1214 17.1215C14.5588 17.6841 13.7958 18.0001 13.0001 18.0001H7.00012C6.20447 18.0001 5.44141 17.6841 4.8788 17.1215C4.31619 16.5589 4.00012 15.7958 4.00012 15.0001ZM22.0001 8.00014V19.0001C21.9985 20.3257 21.4712 21.5966 20.5339 22.5339C19.5966 23.4713 18.3257 23.9986 17.0001 24.0001H8.00012C7.73491 24.0001 7.48055 23.8948 7.29302 23.7072C7.10548 23.5197 7.00012 23.2654 7.00012 23.0001C7.00012 22.7349 7.10548 22.4806 7.29302 22.293C7.48055 22.1055 7.73491 22.0001 8.00012 22.0001H17.0001C17.7958 22.0001 18.5588 21.6841 19.1214 21.1215C19.6841 20.5589 20.0001 19.7958 20.0001 19.0001V8.00014C20.0001 7.73492 20.1055 7.48057 20.293 7.29303C20.4806 7.1055 20.7349 7.00014 21.0001 7.00014C21.2653 7.00014 21.5197 7.1055 21.7072 7.29303C21.8948 7.48057 22.0001 7.73492 22.0001 8.00014Z" fill="#374957"/>
                                                                    </g>
                                                                    <defs>
                                                                    <clipPath id="clip0_403_3199">
                                                                    <rect width="24" height="24" fill="white"/>
                                                                    </clipPath>
                                                                    </defs>
                                                                </svg>
                                                            </span>
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={(e) => {
                                                                e.stopPropagation();
                                                                setSelected({ kind: 'section', sectionId: s.id });
                                                                setRailTab('edit');
                                                                setEditTab('content');
                                                            }}
                                                            className="h-9 w-10 flex items-center justify-center hover:bg-gray-50"
                                                            title="Settings"
                                                        >
                                                            <span aria-hidden>
                                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <g clip-path="url(#clip0_403_3393)">
                                                                    <path d="M15.0004 24H9.00042V20.487C7.95768 20.1181 6.99187 19.5601 6.15142 18.841L3.10742 20.6L0.107422 15.4L3.15042 13.645C2.95053 12.5574 2.95053 11.4426 3.15042 10.355L0.107422 8.6L3.10742 3.4L6.15142 5.159C6.99187 4.43993 7.95768 3.88194 9.00042 3.513V0H15.0004V3.513C16.0432 3.88194 17.009 4.43993 17.8494 5.159L20.8934 3.4L23.8934 8.6L20.8504 10.355C21.0503 11.4426 21.0503 12.5574 20.8504 13.645L23.8934 15.4L20.8934 20.6L17.8494 18.842C17.0089 19.5607 16.0431 20.1184 15.0004 20.487V24ZM11.0004 22H13.0004V18.973L13.7514 18.779C14.9834 18.4598 16.1048 17.8101 16.9944 16.9L17.5374 16.347L20.1604 17.862L21.1604 16.13L18.5404 14.617L18.7464 13.871C19.0851 12.6439 19.0851 11.3481 18.7464 10.121L18.5404 9.375L21.1604 7.862L20.1604 6.13L17.5374 7.649L16.9944 7.1C16.1043 6.19134 14.983 5.54302 13.7514 5.225L13.0004 5.027V2H11.0004V5.027L10.2494 5.221C9.01741 5.54015 7.89603 6.18988 7.00642 7.1L6.46342 7.653L3.84042 6.134L2.84042 7.866L5.46042 9.379L5.25442 10.125C4.91578 11.3521 4.91578 12.6479 5.25442 13.875L5.46042 14.621L2.84042 16.134L3.84042 17.866L6.46342 16.351L7.00642 16.904C7.89651 17.8127 9.01785 18.461 10.2494 18.779L11.0004 18.973V22ZM12.0004 16C11.2093 16 10.4359 15.7654 9.77814 15.3259C9.12034 14.8864 8.60765 14.2616 8.3049 13.5307C8.00215 12.7998 7.92294 11.9956 8.07728 11.2196C8.23162 10.4437 8.61258 9.73098 9.17199 9.17157C9.7314 8.61216 10.4441 8.2312 11.2201 8.07686C11.996 7.92252 12.8003 8.00173 13.5312 8.30448C14.2621 8.60723 14.8868 9.11992 15.3263 9.77772C15.7658 10.4355 16.0004 11.2089 16.0004 12C16.0004 13.0609 15.579 14.0783 14.8288 14.8284C14.0787 15.5786 13.0613 16 12.0004 16ZM12.0004 10C11.6049 10 11.2182 10.1173 10.8893 10.3371C10.5604 10.5568 10.304 10.8692 10.1527 11.2346C10.0013 11.6001 9.96168 12.0022 10.0389 12.3902C10.116 12.7781 10.3065 13.1345 10.5862 13.4142C10.8659 13.6939 11.2223 13.8844 11.6102 13.9616C11.9982 14.0387 12.4003 13.9991 12.7658 13.8478C13.1312 13.6964 13.4436 13.44 13.6634 13.1111C13.8831 12.7822 14.0004 12.3956 14.0004 12C14.0004 11.4696 13.7897 10.9609 13.4146 10.5858C13.0396 10.2107 12.5309 10 12.0004 10Z" fill="#374957"/>
                                                                    </g>
                                                                    <defs>
                                                                    <clipPath id="clip0_403_3393">
                                                                    <rect width="24" height="24" fill="white"/>
                                                                    </clipPath>
                                                                    </defs>
                                                                </svg>
                                                            </span>
                                                        </button>

                                                        <button
                                                            type="button"
                                                            onClick={(e) => {
                                                                e.stopPropagation();
                                                                updateSection(s.id, { style: { ...s.style, darkMode: !s.style?.darkMode } });
                                                            }}
                                                            className={`h-9 w-10 flex items-center justify-center hover:bg-gray-50 ${s.style?.darkMode ? 'bg-blue-50 text-blue-600' : 'text-gray-700'}`}
                                                            title={s.style?.darkMode ? 'Disable Dark Mode' : 'Enable Dark Mode'}
                                                        >
                                                            <span aria-hidden>
                                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                                                            </span>
                                                        </button>

                                                        <button
                                                            type="button"
                                                            onClick={(e) => {
                                                                e.stopPropagation();
                                                                e.preventDefault();
                                                                deleteSection(s.id);
                                                            }}
                                                            className="h-9 w-10 flex items-center justify-center hover:bg-gray-50 text-red-600"
                                                            title="Delete"
                                                        >
                                                            <span aria-hidden>
                                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <path d="M20.9999 4H17.8999C17.6678 2.87141 17.0537 1.85735 16.1611 1.12872C15.2686 0.40009 14.1521 0.00145452 12.9999 0L10.9999 0C9.84767 0.00145452 8.7312 0.40009 7.83863 1.12872C6.94606 1.85735 6.33197 2.87141 6.09988 4H2.99988C2.73466 4 2.48031 4.10536 2.29277 4.29289C2.10523 4.48043 1.99988 4.73478 1.99988 5C1.99988 5.26522 2.10523 5.51957 2.29277 5.70711C2.48031 5.89464 2.73466 6 2.99988 6H3.99988V19C4.00147 20.3256 4.52876 21.5964 5.4661 22.5338C6.40344 23.4711 7.67428 23.9984 8.99988 24H14.9999C16.3255 23.9984 17.5963 23.4711 18.5337 22.5338C19.471 21.5964 19.9983 20.3256 19.9999 19V6H20.9999C21.2651 6 21.5194 5.89464 21.707 5.70711C21.8945 5.51957 21.9999 5.26522 21.9999 5C21.9999 4.73478 21.8945 4.48043 21.707 4.29289C21.5194 4.10536 21.2651 4 20.9999 4ZM10.9999 2H12.9999C13.6202 2.00076 14.225 2.19338 14.7315 2.55144C15.238 2.90951 15.6213 3.41549 15.8289 4H8.17088C8.37846 3.41549 8.76178 2.90951 9.26826 2.55144C9.77475 2.19338 10.3796 2.00076 10.9999 2ZM17.9999 19C17.9999 19.7956 17.6838 20.5587 17.1212 21.1213C16.5586 21.6839 15.7955 22 14.9999 22H8.99988C8.20423 22 7.44117 21.6839 6.87856 21.1213C6.31595 20.5587 5.99988 19.7956 5.99988 19V6H17.9999V19Z" fill="#374957"/>
                                                                    <path d="M10 17.9994C10.2652 17.9994 10.5196 17.894 10.7071 17.7065C10.8946 17.5189 11 17.2646 11 16.9994V10.9994C11 10.7342 10.8946 10.4798 10.7071 10.2923C10.5196 10.1047 10.2652 9.99939 10 9.99939C9.73478 9.99939 9.48043 10.1047 9.29289 10.2923C9.10536 10.4798 9 10.7342 9 10.9994V16.9994C9 17.2646 9.10536 17.5189 9.29289 17.7065C9.48043 17.894 9.73478 17.9994 10 17.9994Z" fill="#374957"/>
                                                                    <path d="M13.9999 17.9994C14.2651 17.9994 14.5195 17.894 14.707 17.7065C14.8945 17.5189 14.9999 17.2646 14.9999 16.9994V10.9994C14.9999 10.7342 14.8945 10.4798 14.707 10.2923C14.5195 10.1047 14.2651 9.99939 13.9999 9.99939C13.7347 9.99939 13.4803 10.1047 13.2928 10.2923C13.1052 10.4798 12.9999 10.7342 12.9999 10.9994V16.9994C12.9999 17.2646 13.1052 17.5189 13.2928 17.7065C13.4803 17.894 13.7347 17.9994 13.9999 17.9994Z" fill="#374957"/>
                                                                </svg>
                                                            </span>
                                                        </button>
                                                    </div>
                                                </div>
                                            ) : null}

                                            <div style={{ flex: stickyConfig ? '0 0 auto' : '1 1 auto', display: 'flex', flexDirection: 'column', position: 'relative', zIndex: 10, ...(typeof sStyle.overflowX === 'string' && sStyle.overflowX ? { overflowX: sStyle.overflowX } : {}), ...(typeof sStyle.overflowY === 'string' && sStyle.overflowY ? { overflowY: sStyle.overflowY } : {}) }}>
                                                <div className="mx-auto" style={{ ...sectionInnerStyle(s.style, stickyConfig, sectionHasNavMenu(s.id)), flex: '1 1 auto', display: 'flex', flexDirection: 'column' }}>
                                                    <PricingSectionProvider section={s}>
                                                    <div
                                                        className="mp-grid"
                                                        style={{
                                                            display: 'flex',
                                                            flexWrap: 'wrap',
                                                            flexDirection: sWrapBasis ? 'row' : direction,
                                                            justifyContent,
                                                            alignItems,
                                                            width: '100%',
                                                            flex: '1 1 auto',
                                                            rowGap: `${sGapY}px`,
                                                            ...((sWrapBasis || justifyContent === 'flex-start') ? { columnGap: `${sGapX}px` } : {}),
                                                        }}
                                                    >
                                                        {(Array.isArray(s.columns) ? s.columns : []).map((c) => (
                                                            (() => {
                                                                const cAdv = advancedReactAttrs(c.advanced);
                                                                const cStyle = c.style || {};
                                                                const cMaxWidthType = cStyle.maxWidthType || (cStyle.maxWidth === 'full' ? 'full' : 'boxed');
                                                                const cWidthValue = cMaxWidthType === 'full' ? resolveUnit(pickResponsive(cStyle.innerMaxWidth, cStyle.innerMaxWidthTablet, cStyle.innerMaxWidthMobile), 'px') : resolveUnit(pickResponsive(cStyle.boxedWidth, cStyle.boxedWidthTablet, cStyle.boxedWidthMobile), 'px');
                                                                const cHasWidthConstraint = !!cWidthValue;
                                                                const cWidthConstraint = cWidthValue || null;

                                                                return (
                                                            <div
                                                                key={c.id}
                                                                id={cAdv.id}
                                                                data-mp-nav-column={c.id}
                                                                data-mp-cache={cAdv.cacheAttr['data-mp-cache']}
                                                                className={mergeClassNames('mp-col rounded-xl relative group', cAdv.className)}
                                                                data-span-base={c.spans.base}
                                                                data-span-md={c.spans.md}
                                                                data-span-lg={c.spans.lg}
                                                                style={{
                                                                    ...containerInnerStyle(c.style),
                                                                    ...(sWrapBasis
                                                                        ? {
                                                                              width: 'auto',
                                                                              maxWidth: sWrapBasis,
                                                                              flex: `0 0 ${sWrapBasis}`,
                                                                              minWidth: 0,
                                                                              boxSizing: 'border-box',
                                                                          }
                                                                        : (cHasWidthConstraint
                                                                            ? {
                                                                                  flex: '0 0 auto',
                                                                                  width: '100%',
                                                                                  maxWidth: cWidthConstraint,
                                                                                  minWidth: 0,
                                                                              }
                                                                            : (direction === 'column' || direction === 'column-reverse'
                                                                                ? {
                                                                                      flex: '0 0 auto',
                                                                                      minWidth: 0,
                                                                                  }
                                                                                : (justifyContent !== 'flex-start'
                                                                                    ? (() => {
                                                                                          const span = Math.max(1, Math.min(12, spanForViewport(c)));
                                                                                          return {
                                                                                              flexBasis: `calc(${(span / 12) * 100}% - ${(1 - span / 12) * sGapX}px)`,
                                                                                              flexGrow: 0,
                                                                                              flexShrink: 0,
                                                                                              minWidth: 0,
                                                                                          };
                                                                                      })()
                                                                                    : {
                                                                                          flexBasis: 0,
                                                                                          flexGrow: Math.max(1, Math.min(12, spanForViewport(c))),
                                                                                          flexShrink: 1,
                                                                                          minWidth: 0,
                                                                                      })))),
                                                                    alignSelf: (c.style && typeof c.style.align === 'string' && c.style.align) ? c.style.align : alignItems,
                                                                    display: 'flex',
                                                                    flexDirection: (c.style && ['row', 'column', 'row-reverse', 'column-reverse'].includes(c.style.itemsDirection)) ? c.style.itemsDirection : 'column',
                                                                    justifyContent: (c.style && ['flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'].includes(c.style.itemsJustify)) ? c.style.itemsJustify : 'flex-start',
                                                                    alignItems: (c.style && ['stretch', 'flex-start', 'center', 'flex-end', 'baseline'].includes(c.style.itemsAlign)) ? c.style.itemsAlign : 'stretch',
                                                                    ...(c.style && Number.isFinite(Number(c.style.gapX)) ? { columnGap: `${Number(c.style.gapX)}px` } : {}),
                                                                    ...(c.style && Number.isFinite(Number(c.style.gapY)) ? { rowGap: `${Number(c.style.gapY)}px` } : {}),
                                                                    ...(cAdv.style || {}),
                                                                }}
                                                                onPointerEnter={() => setHoveredColumnKey(`${s.id}:${c.id}`)}
                                                                onPointerDown={(e) => {
                                                                    e.stopPropagation();
                                                                    setSelected({ kind: 'column', sectionId: s.id, columnId: c.id });
                                                                }}
                                                                onContextMenu={(e) =>
                                                                    handleContextMenu(e, { kind: 'column', sectionId: s.id, columnId: c.id, column: c })
                                                                }
                                                                onClick={(e) => {
                                                                    e.stopPropagation();
                                                                    setSelected({ kind: 'column', sectionId: s.id, columnId: c.id });
                                                                }}
                                                                onDragOver={(e) => {
                                                                    e.preventDefault();
                                                                    const data = getDragData(e);
                                                                    if (!data) return;
                                                                    const container = widgetsContainerRefs.get(`${s.id}:${c.id}`);
                                                                    if (!container) return;
                                                                    const pos = findDropPosition(container, e.clientY);
                                                                    setDropIndicator({
                                                                        ...pos,
                                                                        key: `${s.id}:${c.id}`,
                                                                        sectionId: s.id,
                                                                        columnId: c.id,
                                                                    });
                                                                }}
                                                                onDragLeave={(e) => {
                                                                    const next = e.relatedTarget;
                                                                    if (next && e.currentTarget && e.currentTarget.contains(next)) {
                                                                        return;
                                                                    }
                                                                    setDropIndicator((curr) => (curr && curr.key === `${s.id}:${c.id}` ? null : curr));
                                                                }}
                                                                onDrop={(e) => {
                                                                    e.preventDefault();
                                                                    e.stopPropagation();
                                                                    const data = getDragData(e);
                                                                    setDropIndicator(null);
                                                                    if (!data) return;
                                                                    const container = widgetsContainerRefs.get(`${s.id}:${c.id}`);
                                                                    const pos = container ? findDropPosition(container, e.clientY) : { index: c.widgets.length, top: 0 };
                                                                    const insertIndex = Number.isInteger(pos.index) ? pos.index : c.widgets.length;
                                                                    if (data.kind === 'grid') {
                                                                        addWidgetToColumn(s.id, c.id, 'grid', { count: data.count || 2 }, insertIndex);
                                                                    }
                                                                    if (data.kind === 'container') {
                                                                        addWidgetToColumn(s.id, c.id, 'grid', { count: 1 }, insertIndex);
                                                                    }
                                                                    if (data.kind === 'mobile_menu') {
                                                                        addWidgetToColumn(s.id, c.id, 'grid', { ...MOBILE_MENU_DEFAULTS, count: 1, mobileMenu: true }, insertIndex);
                                                                    }
                                                                    if (data.kind === 'new_widget' && data.widgetType) {
                                                                        addWidgetToColumn(s.id, c.id, data.widgetType, {}, insertIndex);
                                                                    }
                                                                    if (data.kind === 'move_widget') {
                                                                        if (data.gridWidgetId && data.gridColumnId) {
                                                                            moveWidgetAnywhere(data, { sectionId: s.id, columnId: c.id, insertIndex });
                                                                        } else {
                                                                            moveWidgetAnywhere(data, { sectionId: s.id, columnId: c.id, insertIndex });
                                                                        }
                                                                    }
                                                                }}
                                                            >
                                                                {direction === 'row' && s.columns && s.columns[0] && s.columns[0].id !== c.id ? (
                                                                    <div
                                                                        onPointerDown={(e) => {
                                                                            const idx = Array.isArray(s.columns) ? s.columns.findIndex((x) => x && x.id === c.id) : -1;
                                                                            const prevCol = idx > 0 && s.columns[idx - 1] ? s.columns[idx - 1] : null;
                                                                            if (!prevCol) return;
                                                                            startResizeColumns(e, s.id, prevCol.id, c.id, 'left');
                                                                        }}
                                                                        className="absolute top-0 left-0 h-full w-4 z-20 group"
                                                                        style={{ cursor: 'col-resize', touchAction: 'none' }}
                                                                    >
                                                                        <div className="absolute hidden top-0 bottom-0 left-1/2 -translate-x-1/2 w-px bg-blue-600/40 opacity-0 group-hover:opacity-100" />
                                                                    </div>
                                                                ) : null}

                                                                {direction === 'row' && s.columns && s.columns[s.columns.length - 1] && s.columns[s.columns.length - 1].id !== c.id ? (
                                                                    <div
                                                                        onPointerDown={(e) => {
                                                                            const idx = Array.isArray(s.columns) ? s.columns.findIndex((x) => x && x.id === c.id) : -1;
                                                                            const nextCol = idx >= 0 && s.columns[idx + 1] ? s.columns[idx + 1] : null;
                                                                            if (!nextCol) return;
                                                                            startResizeColumns(e, s.id, c.id, nextCol.id, 'right');
                                                                        }}
                                                                        className="absolute top-0 right-0 h-full w-4 z-20 group"
                                                                        style={{ cursor: 'col-resize', touchAction: 'none' }}
                                                                    >
                                                                        <div className="absolute hidden top-0 bottom-0 left-1/2 -translate-x-1/2 w-px bg-blue-600/40 opacity-0 group-hover:opacity-100" />
                                                                    </div>
                                                                ) : null}
                                                                {(() => {
                                                                    const key = `${s.id}:${c.id}`;
                                                                    const isColumnHovered = hoveredColumnKey === key;

                                                                    return isColumnHovered ? (
                                                                        <button
                                                                            type="button"
                                                                            onClick={(e) => {
                                                                                e.stopPropagation();
                                                                                setSelected({ kind: 'column', sectionId: s.id, columnId: c.id });
                                                                                setRailTab('edit');
                                                                                setEditTab('content');
                                                                            }}
                                                                            className="absolute z-30 bg-white border border-gray-200 shadow hover:bg-gray-50 flex items-center justify-center"
                                                                            style={{ top: -1, right: -1, width: 20, height: 20 }}
                                                                            title="Column settings"
                                                                        >
                                                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-600">
                                                                                <circle cx="12" cy="12" r="3"/>
                                                                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                                                            </svg>
                                                                        </button>
                                                                    ) : null;
                                                                })()}

                                                                <div
                                                                    className={` absolute inset-0 border border-dashed pointer-events-none ${
                                                                        c.widgets.length === 0 ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'
                                                                    }`}
                                                                    style={{ borderColor: '#ddd', borderRadius: 0 }}
                                                                />
                                                                <div className="rounded-xl bg-transparent p-0 my-3" style={{ flex: '1 1 auto', display: 'flex', flexDirection: 'column' }}>
                                                                    {c.widgets.length === 0 ? (
                                                                        <div className="text-center" style={{ flex: '1 1 auto', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                                                                            <button
                                                                                type="button"
                                                                                onClick={(e) => {
                                                                                    e.stopPropagation();
                                                                                    if (selected.kind !== 'column') {
                                                                                        skipAutoEditRef.current = true;
                                                                                    }
                                                                                    setSelected({ kind: 'column', sectionId: s.id, columnId: c.id });
                                                                                    setRailTab('elements');
                                                                                }}
                                                                                className="mx-auto h-12 w-12 rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 flex items-center justify-center text-2xl leading-none"
                                                                            >
                                                                                <span aria-hidden>
                                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-lg" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2"/>
</svg>
                                                                                </span>
                                                                            </button>
                                                                        </div>
                                                                     ) : (
                                                                         (() => {
                                                                             const wrap = computeWrapPerRow(c.style);
                                                                             const basis = wrap ? `calc((100% - ${(wrap.n - 1) * wrap.gapX}px) / ${wrap.n})` : null;
                                                                             return (
                                                                          <div
                                                                              className={wrap ? 'relative w-full' : 'space-y-4 relative w-full'}
                                                                              style={wrap
                                                                                  ? { flex: '1 1 auto', width: '100%', minWidth: 0, display: 'flex', flexWrap: 'wrap', gap: `${wrap.gapY}px ${wrap.gapX}px` }
                                                                                  : { flex: '1 1 auto', width: '100%', minWidth: 0 }}
                                                                             ref={(el) => {
                                                                                 const key = `${s.id}:${c.id}`;
                                                                                 if (el) widgetsContainerRefs.set(key, el);
                                                                                 else widgetsContainerRefs.delete(key);
                                                                             }}
                                                                         >
                                                                             {dropIndicator && dropIndicator.key === `${s.id}:${c.id}` ? (
                                                                                 <div
                                                                                     className="absolute left-2 right-2 h-0.5 bg-blue-500 z-30 pointer-events-none rounded"
                                                                                     style={{ top: `${dropIndicator.top}px` }}
                                                                                 />
                                                                             ) : null}
                                                                             {c.widgets.map((w) => (
                                                                                 (() => {
                                                                                     const wAdv = advancedReactAttrs(w.advanced);
                                                                                     return (
                                                                                 <div
                                                                                     key={w.id}
                                                                                     data-widget-id={w.id}
                                                                                     draggable
                                                                                     onDragStart={(e) =>
                                                                                         setDragData(e, {
                                                                                             kind: 'move_widget',
                                                                                             sectionId: s.id,
                                                                                             columnId: c.id,
                                                                                             widgetId: w.id,
                                                                                         })
                                                                                     }
                                                                                     onContextMenu={(e) =>
                                                                                         handleContextMenu(e, {
                                                                                             kind: 'widget',
                                                                                             sectionId: s.id,
                                                                                             columnId: c.id,
                                                                                             widgetId: w.id,
                                                                                             widget: w,
                                                                                         })
                                                                                     }
                                                                                     onPointerDown={(e) => {
                                                                                         e.stopPropagation();
                                                                                         setSelected({
                                                                                             kind: 'widget',
                                                                                             sectionId: s.id,
                                                                                             columnId: c.id,
                                                                                             widgetId: w.id,
                                                                                         });
                                                                                     }}
                                                                                     onClick={(e) => {
                                                                                         e.stopPropagation();
                                                                                         setSelected({
                                                                                             kind: 'widget',
                                                                                             sectionId: s.id,
                                                                                             columnId: c.id,
                                                                                             widgetId: w.id,
                                                                                         });
                                                                                     }}
                                                                                      className="cursor-pointer"
                                                                                      style={basis
                                                                                          ? { width: basis, maxWidth: basis, flex: `0 0 ${basis}`, minWidth: 0, boxSizing: 'border-box', ...(wAdv.wrapperStyle || {}) }
                                                                                          : { width: '100%', maxWidth: '100%', ...(wAdv.wrapperStyle || {}) }}
                                                                                  >
                                                                                     <div
                                                                                         id={wAdv.id}
                                                                                         data-mp-cache={wAdv.cacheAttr['data-mp-cache']}
                                                                                         className={mergeClassNames(
                                                                                             wAdv.className,
                                                                                             `mp-typo-${w.id}`
                                                                                         )}
                                                                                         style={{
                                                                                             ...(wAdv.style || {}),
                                                                                             ...widgetCanvasStyle(w.props, w.id),
                                                                                         }}
                                                                                     >
                                                                                         {responsiveTypographyCss(w.props, `mp-typo-${w.id}`) ? (
                                                                                             <style dangerouslySetInnerHTML={{ __html: responsiveTypographyCss(w.props, `mp-typo-${w.id}`) }} />
                                                                                         ) : null}
                                                                                         {renderWidgetPreview(w, { sectionId: s.id, columnId: c.id })}
                                                                                     </div>
                                                                                 </div>
                                                                                     );
                                                                                 })()
                                                                             ))}
                                                                         </div>
                                                                             );
                                                                         })()
                                                                     )}
                                                                </div>
                                                            </div>
                                                                );
                                                            })()
                                                        ))}
                                                    </div>
                                                    </PricingSectionProvider>
                                                </div>
                                            </div>

                                        </div>

                                        </React.Fragment>
                                    );
                                })}

                                <div className="py-40 flex items-center justify-center gap-3">
                                    <button
                                        type="button"
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            insertSectionAfter(null, blankSectionFactory);
                                        }}
                                        className="h-10 px-4 rounded-md bg-blue-600 hover:bg-blue-700 text-white shadow flex items-center gap-2"
                                        title="Add section"
                                    >
                                        <span aria-hidden className="text-lg leading-none">+</span>
                                        <span className="text-sm font-semibold">Add section</span>
                                    </button>

                                    <button
                                        type="button"
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            setInsertModal({ afterSectionId: null, mode: 'templates' });
                                        }}
                                        className="h-10 px-4 rounded-md bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 shadow-sm"
                                        title="Templates"
                                    >
                                        <span className="text-sm font-semibold">Templates</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {navigatorOpen ? createPortal(
                    <div
                        className="fixed z-50 bg-white rounded-lg shadow-2xl border border-gray-200 flex flex-col"
                        style={{
                            left: navigatorPos.x,
                            top: navigatorPos.y,
                            width: 280,
                            maxHeight: '70vh',
                        }}
                    >
                        <div
                            className="flex items-center justify-between px-3 py-2 border-b border-gray-200 cursor-move select-none rounded-t-lg bg-gray-50"
                            onMouseDown={(e) => {
                                e.preventDefault();
                                const startX = e.clientX;
                                const startY = e.clientY;
                                const startPx = navigatorPos.x;
                                const startPy = navigatorPos.y;
                                const onMove = (ev) => {
                                    setNavigatorPos({
                                        x: Math.max(0, startPx + ev.clientX - startX),
                                        y: Math.max(0, startPy + ev.clientY - startY),
                                    });
                                };
                                const onUp = () => {
                                    document.removeEventListener('mousemove', onMove);
                                    document.removeEventListener('mouseup', onUp);
                                };
                                document.addEventListener('mousemove', onMove);
                                document.addEventListener('mouseup', onUp);
                            }}
                        >
                            <span className="text-xs font-semibold text-gray-700">Navigator</span>
                            <button
                                type="button"
                                onClick={() => setNavigatorOpen(false)}
                                className="text-gray-400 hover:text-gray-700"
                                title="Close"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                            </button>
                        </div>
                        <div className="overflow-y-auto p-2 flex-1 select-none">
                            <div className="space-y-0.5">
                                {page.sections.map((s) => (
                                    <div key={s.id}>
                                        <div
                                            className={`flex items-center gap-2 px-2 py-1.5 rounded cursor-pointer text-sm font-medium text-gray-700 ${
                                                selected.sectionId === s.id && selected.kind === 'section'
                                                    ? 'bg-gray-100'
                                                    : 'hover:bg-gray-50'
                                            }`}
                                            onClick={() => {
                                                setSelected({ kind: 'section', sectionId: s.id });
                                                focusNavigatorTarget({ kind: 'section', sectionId: s.id });
                                            }}
                                        >
                                            <button
                                                type="button"
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    setCollapsedSections((m) => ({ ...m, [s.id]: !m[s.id] }));
                                                }}
                                                className="w-4 h-4 flex items-center justify-center text-gray-500 hover:text-gray-700"
                                                title={collapsedSections[s.id] ? 'Expand' : 'Collapse'}
                                            >
                                                <span aria-hidden className="text-[10px]">{collapsedSections[s.id] ? '▸' : '▾'}</span>
                                            </button>
                                            <ElementPaletteIcon name="container" className="h-4 w-4 text-gray-500" />
                                            <span className="truncate">{s.name || 'Section'}</span>
                                        </div>

                                        {!collapsedSections[s.id] ? (
                                            <div className="pl-6">
                                                {s.columns.map((c) => {
                                                    const colKey = `${s.id}:${c.id}`;
                                                    const colCollapsed = Boolean(collapsedColumns[colKey]);
                                                    return (
                                                        <div key={c.id}>
                                                            <div
                                                                className={`flex items-center gap-2 px-2 py-1.5 rounded cursor-pointer text-sm font-medium text-gray-700 ${
                                                                    selected.columnId === c.id && selected.kind === 'column'
                                                                        ? 'bg-gray-100'
                                                                        : 'hover:bg-gray-50'
                                                                }`}
                                                                onClick={() => {
                                                                    setSelected({ kind: 'column', sectionId: s.id, columnId: c.id });
                                                                    focusNavigatorTarget({ kind: 'column', sectionId: s.id, columnId: c.id });
                                                                }}
                                                            >
                                                                <button
                                                                    type="button"
                                                                    onClick={(e) => {
                                                                        e.stopPropagation();
                                                                        setCollapsedColumns((m) => ({ ...m, [colKey]: !m[colKey] }));
                                                                    }}
                                                                    className="w-4 h-4 flex items-center justify-center text-gray-500 hover:text-gray-700"
                                                                    title={colCollapsed ? 'Expand' : 'Collapse'}
                                                                >
                                                                    <span aria-hidden className="text-[10px]">{colCollapsed ? '▸' : '▾'}</span>
                                                                </button>
                                                                <ElementPaletteIcon name="grid" className="h-4 w-4 text-gray-500" />
                                                                <span className="truncate">{c.name || 'Column'}</span>
                                                            </div>

                                                            {!colCollapsed ? (
                                                                <div className="pl-6">
                                                                    {c.widgets.map((w) => (
                                                                        <div
                                                                            key={w.id}
                                                                            className={`flex items-center gap-2 px-2 py-1.5 rounded cursor-pointer text-sm font-medium text-gray-700 ${
                                                                                selected.widgetId === w.id && selected.kind === 'widget'
                                                                                    ? 'bg-gray-100'
                                                                                    : 'hover:bg-gray-50'
                                                                            }`}
                                                                            onClick={() => {
                                                                                setSelected({ kind: 'widget', sectionId: s.id, columnId: c.id, widgetId: w.id });
                                                                                focusNavigatorTarget({ kind: 'widget', sectionId: s.id, columnId: c.id, widgetId: w.id });
                                                                            }}
                                                                        >
                                                                            <span className="w-4" />
                                                                            <ElementPaletteIcon name={w.type} className="h-4 w-4 text-gray-500" />
                                                                            <span className="truncate">{w.name || humanizeWidgetType(w.type)}</span>
                                                                        </div>
                                                                    ))}
                                                                </div>
                                                            ) : null}
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        ) : null}
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                , document.body) : null}
            </div>

                <ContextMenu menu={contextMenu} onClose={() => setContextMenu(null)} onAction={handleContextMenuAction} />

                <InsertSectionModal
                    open={Boolean(insertModal)}
                    onClose={() => setInsertModal(null)}
                    mode={(insertModal && insertModal.mode) ? insertModal.mode : 'all'}
                    builderMode={mode}
                    templatesApi={templatesApi}
                    onInsertBlank={() => {
                        const beforeId = insertModal && insertModal.beforeSectionId ? insertModal.beforeSectionId : null;
                        const afterId = insertModal && insertModal.afterSectionId ? insertModal.afterSectionId : null;
                        if (beforeId) {
                            insertSectionBefore(beforeId, blankSectionFactory);
                        } else {
                            insertSectionAfter(afterId, blankSectionFactory);
                        }
                        setInsertModal(null);
                    }}
                    onInsertTemplate={(templateId) => {
                        const beforeId = insertModal && insertModal.beforeSectionId ? insertModal.beforeSectionId : null;
                        const afterId = insertModal && insertModal.afterSectionId ? insertModal.afterSectionId : null;
                        if (beforeId) {
                            insertSectionBefore(beforeId, sectionTemplateFactory(templateId));
                        } else {
                            insertSectionAfter(afterId, sectionTemplateFactory(templateId));
                        }
                        setInsertModal(null);
                    }}
                    onInsertTemplateJson={(jsonCode) => {
                        const beforeId = insertModal && insertModal.beforeSectionId ? insertModal.beforeSectionId : null;
                        const afterId = insertModal && insertModal.afterSectionId ? insertModal.afterSectionId : null;
                        const parsed = parseTemplateJsonToSections(jsonCode);
                        if (!parsed.length) return;
                        const fresh = parsed
                            .map((sec) => normalizeSection({ ...sec, id: uid('sec') }))
                            .filter(Boolean);
                        if (!fresh.length) return;
                        setPage((p) => {
                            const sections = Array.isArray(p.sections) ? [...p.sections] : [];
                            const beforeIdx = beforeId ? sections.findIndex((x) => x && x.id === beforeId) : -1;
                            const afterIdx = afterId ? sections.findIndex((x) => x && x.id === afterId) : -1;
                            const insertAt = beforeIdx >= 0 ? beforeIdx : (afterIdx >= 0 ? afterIdx + 1 : sections.length);
                            sections.splice(insertAt, 0, ...fresh);
                            return { ...p, sections };
                        });
                        setSelected({ kind: 'section', sectionId: fresh[0].id });
                        setRailTab('edit');
                        setEditTab('content');
                        setInsertModal(null);
                    }}
                />

                {globalOverridesOpen ? (
                    <GlobalOverridesPanel
                        value={page.globals || {}}
                        onChange={(patch) =>
                            setPage((p) => ({
                                ...p,
                                globals: { ...(p.globals || {}), ...patch },
                            }))
                        }
                        globals={globals}
                        uploadUrl={uploadUrl}
                        onClose={() => setGlobalOverridesOpen(false)}
                    />
                ) : null}

                {importCodeOpen ? (
                    <div className="fixed inset-0 z-[110] flex items-center justify-center p-4" role="dialog" aria-modal="true">
                        <button type="button" className="absolute inset-0 h-full w-full bg-black/50" onClick={() => setImportCodeOpen(false)} aria-label="Close import dialog" />
                        <div className="relative flex max-h-[85vh] w-full max-w-3xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
                            <div className="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                                <div>
                                    <h2 className="text-base font-semibold text-gray-900">Import HTML code</h2>
                                    <p className="mt-1 text-xs text-gray-500">Paste HTML below. It will be sanitized and added as a new section.</p>
                                </div>
                                <button type="button" onClick={() => setImportCodeOpen(false)} className="text-gray-500 hover:text-gray-900" aria-label="Close import dialog">×</button>
                            </div>
                            <div className="min-h-0 flex-1 overflow-auto p-5">
                                <textarea
                                    value={importCodeValue}
                                    onChange={(e) => setImportCodeValue(e.target.value)}
                                    placeholder="<table>…</table>"
                                    spellCheck={false}
                                    className="h-80 w-full resize-none rounded-lg border border-gray-300 bg-gray-50 p-3 font-mono text-xs text-gray-800 outline-none focus:border-blue-500 focus:bg-white dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                />
                            </div>
                            <div className="flex items-center justify-end gap-3 border-t border-gray-200 px-5 py-4">
                                <button type="button" onClick={() => setImportCodeOpen(false)} className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                                <button
                                    type="button"
                                    disabled={!String(importCodeValue).trim()}
                                    onClick={applyImportCode}
                                    className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    Import
                                </button>
                            </div>
                        </div>
                    </div>
                ) : null}

                {mode === 'email' && previewOpen ? (
                    <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true">
                        <div className="flex h-full max-h-[900px] w-full max-w-4xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
                            <div className="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                                <h2 className="text-sm font-semibold text-gray-900">Email Preview</h2>
                                <button type="button" onClick={() => setPreviewOpen(false)} className="text-gray-500 hover:text-gray-900" aria-label="Close preview">×</button>
                            </div>
                            <div className="min-h-0 flex-1 bg-gray-100 p-4">
                                {previewLoading ? <div className="flex h-full items-center justify-center text-sm text-gray-500">Rendering preview...</div> : <iframe title="Email preview" srcDoc={previewHtml} className="h-full w-full border-0 bg-white" sandbox="allow-same-origin" />}
                            </div>
                        </div>
                    </div>
                ) : null}

                {mode === 'email' && testOpen ? (
                    <div className="fixed inset-0 z-[100]" role="dialog" aria-modal="true">
                        <button type="button" className="absolute inset-0 h-full w-full bg-black/50" onClick={() => setTestOpen(false)} aria-label="Close test drawer" />
                        <aside className="absolute right-0 top-0 flex h-full w-full max-w-md flex-col bg-white shadow-2xl">
                            <div className="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                                <div><h2 className="text-base font-semibold text-gray-900">Test Email</h2><p className="mt-1 text-xs text-gray-500">Send the current draft without publishing it.</p></div>
                                <button type="button" onClick={() => setTestOpen(false)} className="text-gray-500 hover:text-gray-900" aria-label="Close test drawer">×</button>
                            </div>
                            <div className="flex-1 space-y-5 overflow-y-auto px-5 py-5">
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700">Delivery server</label>
                                    <select value={testServerId} onChange={(e) => setTestServerId(e.target.value)} className="block w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Select a delivery server...</option>
                                        {deliveryServers.map((server) => <option key={server.id} value={server.id}>{server.name}</option>)}
                                    </select>
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700">Destination email</label>
                                    <input type="email" value={testEmail} onChange={(e) => setTestEmail(e.target.value)} placeholder="test@example.com" className="block w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                                </div>
                                {testError ? <div className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{testError}</div> : null}
                                {testMessage ? <div className="rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">{testMessage}</div> : null}
                            </div>
                            <div className="border-t border-gray-200 px-5 py-4">
                                <button type="button" disabled={testLoading || !testEmailUrl} onClick={sendEmailTest} className="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-50">{testLoading ? 'Sending...' : 'Send Test Email'}</button>
                            </div>
                        </aside>
                    </div>
                ) : null}
            </div>
        </div>
        </BuilderModeContext.Provider>
        </BuilderViewportContext.Provider>
    );
}

function SectionInspector({ model, onChange, uploadUrl, kind, editTab = 'content', globals }) {
    const isSection = kind === 'section';
    const schema = isSection ? sectionSchema[editTab] : (kind === 'grid_column' ? gridColumnSchema[editTab] : columnSchema[editTab]);

    if (!schema || schema.length === 0) {
        return (
            <div className="space-y-4">
                <p className="text-sm text-gray-500">No {editTab} controls for this container.</p>
            </div>
        );
    }

    return (
        <ControlsRenderer
            schema={schema}
            value={model}
            onChange={onChange}
            globals={globals}
            uploadUrl={uploadUrl}
        />
    );
}

function GridInspector({ widget, onChange, mode = 'content', globals, uploadUrl }) {
    const style = widget.style || {};
    const gridViewportCtx = React.useContext(BuilderViewportContext);
    const gridDevice = gridViewportCtx && typeof gridViewportCtx.viewport === 'string' ? gridViewportCtx.viewport : 'desktop';
    const gridSuffix = gridDevice === 'desktop' ? '' : gridDevice === 'tablet' ? 'Tablet' : 'Mobile';

    if (widget.props && widget.props.mobileMenu) {
        const mobileMenuSchema = mode === 'style' ? mobileMenuStyleSchema : mobileMenuContentSchema;
        return (
            <div className="space-y-4">
                {mode !== 'style' ? (
                    <div className="mx-4 rounded-md border border-dashed border-gray-300 bg-gray-50 px-3 py-2 text-xs text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                        Add nav links, buttons and other elements inside the drawer from the canvas.
                    </div>
                ) : null}
                <ControlsRenderer
                    schema={mobileMenuSchema}
                    value={widget}
                    onChange={onChange}
                    globals={globals}
                    uploadUrl={uploadUrl}
                />
            </div>
        );
    }

    const paddingKeys = {
        linked: 'paddingLinked',
        t: `paddingTop${gridSuffix}`,
        r: `paddingRight${gridSuffix}`,
        b: `paddingBottom${gridSuffix}`,
        l: `paddingLeft${gridSuffix}`,
        legacyX: 'paddingX',
        legacyY: 'paddingY',
    };
    const marginKeys = {
        linked: 'marginLinked',
        t: `marginTop${gridSuffix}`,
        r: `marginRight${gridSuffix}`,
        b: `marginBottom${gridSuffix}`,
        l: `marginLeft${gridSuffix}`,
    };
    const borderRadiusKeys = {
        linked: 'borderRadiusLinked',
        t: 'borderRadiusTop',
        r: 'borderRadiusRight',
        b: 'borderRadiusBottom',
        l: 'borderRadiusLeft',
        legacy: 'borderRadius',
    };
    const borderWidthKeys = {
        linked: 'borderWidthLinked',
        t: 'borderWidthTop',
        r: 'borderWidthRight',
        b: 'borderWidthBottom',
        l: 'borderWidthLeft',
        legacy: 'borderWidth',
    };

    if (mode === 'style') {
        return (
            <div className="space-y-4">
                <div className="px-4">
                    <DeviceSwitcher />
                </div>
                <StyleAccordionItem title="Spacing" defaultOpen={true}>
                    <div className="px-4 space-y-3">
                        <FourSideLinkedControl
                            label="Padding"
                            value={style}
                            keys={paddingKeys}
                            fallbackValue={style.paddingTop ?? style.paddingY ?? style.paddingX}
                            onChange={(next) => onChange({ style: { ...style, ...next } })}
                        />
                        <FourSideLinkedControl
                            label="Margin"
                            value={style}
                            keys={marginKeys}
                            fallbackValue={style.marginTop ?? style.marginRight ?? style.marginBottom ?? style.marginLeft}
                            onChange={(next) => onChange({ style: { ...style, ...next } })}
                        />
                    </div>
                </StyleAccordionItem>

                <StyleAccordionItem title="Border" defaultOpen={false}>
                    <div className="px-4 space-y-3">
                        <BorderControls
                            value={style}
                            onChange={(next) => onChange({ style: { ...style, ...next } })}
                            widthKeys={borderWidthKeys}
                            widthFallbackValue={style.borderWidthTop ?? style.borderWidth}
                        />
                        <FourSideLinkedControl
                            label="Border Radius"
                            value={style}
                            keys={borderRadiusKeys}
                            fallbackValue={style.borderRadiusTop ?? style.borderRadius}
                            onChange={(next) => onChange({ style: { ...style, ...next } })}
                        />
                    </div>
                </StyleAccordionItem>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            <ControlsRenderer
                schema={gridContentSchema}
                value={widget}
                globals={globals}
                uploadUrl={uploadUrl}
                onChange={(patch) => {
                    if (patch && typeof patch.style === 'object' && patch.style !== null) {
                        onChange({ ...patch, style: { ...style, ...patch.style } });
                        return;
                    }
                    onChange(patch);
                }}
            />
        </div>
    );
}

function WidgetInspector({ widget, onChange, uploadUrl, megaMenus = [], editTab = 'content', globals }) {
    const element = useMemo(() => getElement(widget.type), [widget.type]);
    const schema = element?.schema?.[editTab] || [];

    if (schema.length === 0) {
        return (
            <div className="space-y-4">
                <p className="text-sm text-gray-500">No {editTab} controls for this element.</p>
            </div>
        );
    }

    return (
        <ControlsRenderer
            schema={schema}
            value={widget}
            onChange={onChange}
            globals={globals}
            megaMenus={megaMenus}
            uploadUrl={uploadUrl}
        />
    );
}

const { __initPageBuilder } = createPageBuilderRuntime({ PageBuilderApp });

export { __initPageBuilder };
