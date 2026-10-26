import React, { useState, useRef, useEffect, useLayoutEffect } from 'react';
import { createPortal } from 'react-dom';
import { registerElement } from '../elementRegistry.js';
import { HOVER_EFFECT_OPTIONS, hoverEffectCss } from '../lib/hoverEffects.js';

let navMenuPortalRoot = null;
function getNavMenuPortalRoot() {
    if (typeof document === 'undefined') return null;
    if (navMenuPortalRoot && navMenuPortalRoot.isConnected) return navMenuPortalRoot;
    navMenuPortalRoot = document.getElementById('mp-nav-menu-portal-root');
    if (!navMenuPortalRoot) {
        navMenuPortalRoot = document.createElement('div');
        navMenuPortalRoot.id = 'mp-nav-menu-portal-root';
        navMenuPortalRoot.style.position = 'fixed';
        navMenuPortalRoot.style.top = '0';
        navMenuPortalRoot.style.left = '0';
        navMenuPortalRoot.style.width = '0';
        navMenuPortalRoot.style.height = '0';
        navMenuPortalRoot.style.zIndex = '9999';
        navMenuPortalRoot.style.pointerEvents = 'none';
        document.body.appendChild(navMenuPortalRoot);
    }
    return navMenuPortalRoot;
}

function buildStyle(pairs) {
    const out = {};
    pairs.forEach(([k, v]) => {
        if (v !== undefined && v !== null && v !== '') out[k] = v;
    });
    return out;
}

function intOrNull(v) {
    const n = Number(v);
    return Number.isFinite(n) ? n : null;
}

function NavMenuItemPreview({ item, props, megaMenus = [] }) {
    const [open, setOpen] = useState(false);
    const [visible, setVisible] = useState(false);
    const [panelPos, setPanelPos] = useState({ top: 0, left: 0, width: 0, right: 0 });
    const timerRef = useRef(null);
    const animTimerRef = useRef(null);
    const liRef = useRef(null);
    const portalRoot = getNavMenuPortalRoot();
    const label = String(item?.label || 'Link');
    const isDropdown = item?.type === 'dropdown';
    const isMegaMenu = item?.type === 'megamenu';
    const hasChildren = isDropdown && Array.isArray(item?.children) && item.children.length > 0;
    const url = String(item?.url || '#');
    const mmWidthMode = props?.megaMenuWidthMode || 'full';

    function updatePanelPos() {
        if (!liRef.current) return;
        const r = liRef.current.getBoundingClientRect();
        setPanelPos({ top: r.bottom, left: r.left, width: r.width, right: r.right });
    }

    function handleEnter() {
        if (timerRef.current) {
            clearTimeout(timerRef.current);
            timerRef.current = null;
        }
        if (animTimerRef.current) {
            clearTimeout(animTimerRef.current);
            animTimerRef.current = null;
        }
        updatePanelPos();
        setOpen(true);
        requestAnimationFrame(() => {
            requestAnimationFrame(() => setVisible(true));
        });
    }

    function handleLeave() {
        if (timerRef.current) clearTimeout(timerRef.current);
        timerRef.current = setTimeout(() => {
            setVisible(false);
            animTimerRef.current = setTimeout(() => {
                setOpen(false);
                animTimerRef.current = null;
                timerRef.current = null;
            }, 400);
        }, 80);
    }

    useEffect(() => {
        return () => {
            if (timerRef.current) clearTimeout(timerRef.current);
            if (animTimerRef.current) clearTimeout(animTimerRef.current);
        };
    }, []);

    useLayoutEffect(() => {
        if (!open) return;
        updatePanelPos();
        window.addEventListener('resize', updatePanelPos);
        window.addEventListener('scroll', updatePanelPos, true);
        return () => {
            window.removeEventListener('resize', updatePanelPos);
            window.removeEventListener('scroll', updatePanelPos, true);
        };
    }, [open]);

    const color = props?.color || '';
    const fontFamily = props?.fontFamily || '';
    const fontSize = intOrNull(props?.fontSize);
    const fontWeight = props?.fontWeight || '';
    const lineHeight = intOrNull(props?.lineHeight);
    const letterSpacing = intOrNull(props?.letterSpacing);
    const wordSpacing = intOrNull(props?.wordSpacing);

    const linkStyle = buildStyle([
        ['textDecoration', 'none'],
        ['color', color || undefined],
        ['fontFamily', fontFamily || undefined],
        ['fontSize', fontSize !== null && fontSize > 0 ? `${fontSize}px` : undefined],
        ['fontWeight', fontWeight || undefined],
        ['lineHeight', lineHeight !== null && lineHeight > 0 ? lineHeight : undefined],
        ['letterSpacing', letterSpacing !== null ? `${letterSpacing}px` : undefined],
        ['wordSpacing', wordSpacing !== null ? `${wordSpacing}px` : undefined],
    ]);

    const dmTop = intOrNull(props?.dropdownMarginTop);
    const dmRight = intOrNull(props?.dropdownMarginRight);
    const dmBottom = intOrNull(props?.dropdownMarginBottom);
    const dmLeft = intOrNull(props?.dropdownMarginLeft);
    const dpTop = intOrNull(props?.dropdownPaddingTop);
    const dpRight = intOrNull(props?.dropdownPaddingRight);
    const dpBottom = intOrNull(props?.dropdownPaddingBottom);
    const dpLeft = intOrNull(props?.dropdownPaddingLeft);
    const dropdownBg = props?.dropdownBackgroundColor || '#fff';
    const dropdownBorderStyle = props?.dropdownBorderStyle || '';
    const dropdownBorderColor = props?.dropdownBorderColor || '';
    const dbwTop = intOrNull(props?.dropdownBorderWidthTop);
    const dbwRight = intOrNull(props?.dropdownBorderWidthRight);
    const dbwBottom = intOrNull(props?.dropdownBorderWidthBottom);
    const dbwLeft = intOrNull(props?.dropdownBorderWidthLeft);
    const dropdownBorderRadius = intOrNull(props?.dropdownBorderRadius);
    const dropdownBorderRadiusTop = intOrNull(props?.dropdownBorderRadiusTop) ?? dropdownBorderRadius;
    const dropdownBorderRadiusRight = intOrNull(props?.dropdownBorderRadiusRight) ?? dropdownBorderRadius;
    const dropdownBorderRadiusBottom = intOrNull(props?.dropdownBorderRadiusBottom) ?? dropdownBorderRadius;
    const dropdownBorderRadiusLeft = intOrNull(props?.dropdownBorderRadiusLeft) ?? dropdownBorderRadius;

    const dropdownUlStyle = buildStyle([
        ['position', 'fixed'],
        ['top', panelPos.top + 12],
        ['left', panelPos.left],
        ['minWidth', intOrNull(props?.dropdownWidth) ?? 200],
        ['listStyle', 'none'],
        ['display', open ? 'block' : 'none'],
        ['opacity', visible ? 1 : 0],
        ['transform', visible ? 'translateY(0) scale(1)' : 'translateY(20px) scale(0.98)'],
        ['transformOrigin', 'top center'],
        ['transition', 'opacity 400ms cubic-bezier(0.16,1,0.3,1), transform 400ms cubic-bezier(0.16,1,0.3,1)'],
        ['pointerEvents', 'auto'],
        ['backgroundColor', dropdownBg],
        ['borderTopLeftRadius', dropdownBorderRadiusTop !== null && dropdownBorderRadiusTop >= 0 ? `${dropdownBorderRadiusTop}px` : '6px'],
        ['borderTopRightRadius', dropdownBorderRadiusRight !== null && dropdownBorderRadiusRight >= 0 ? `${dropdownBorderRadiusRight}px` : '6px'],
        ['borderBottomRightRadius', dropdownBorderRadiusBottom !== null && dropdownBorderRadiusBottom >= 0 ? `${dropdownBorderRadiusBottom}px` : '6px'],
        ['borderBottomLeftRadius', dropdownBorderRadiusLeft !== null && dropdownBorderRadiusLeft >= 0 ? `${dropdownBorderRadiusLeft}px` : '6px'],
        ['boxShadow', '0 4px 12px rgba(0,0,0,0.08)'],
        ['marginTop', dmTop !== null ? `${dmTop}px` : undefined],
        ['marginRight', dmRight !== null ? `${dmRight}px` : undefined],
        ['marginBottom', dmBottom !== null ? `${dmBottom}px` : undefined],
        ['marginLeft', dmLeft !== null ? `${dmLeft}px` : undefined],
        ['paddingTop', dpTop !== null ? `${dpTop}px` : '8px'],
        ['paddingRight', dpRight !== null ? `${dpRight}px` : undefined],
        ['paddingBottom', dpBottom !== null ? `${dpBottom}px` : undefined],
        ['paddingLeft', dpLeft !== null ? `${dpLeft}px` : undefined],
    ]);

    if (dropdownBorderStyle !== '') {
        dropdownUlStyle.borderStyle = dropdownBorderStyle;
        if (dropdownBorderColor !== '') dropdownUlStyle.borderColor = dropdownBorderColor;
        if (dbwTop !== null && dbwTop >= 0) dropdownUlStyle.borderTopWidth = `${dbwTop}px`;
        if (dbwRight !== null && dbwRight >= 0) dropdownUlStyle.borderRightWidth = `${dbwRight}px`;
        if (dbwBottom !== null && dbwBottom >= 0) dropdownUlStyle.borderBottomWidth = `${dbwBottom}px`;
        if (dbwLeft !== null && dbwLeft >= 0) dropdownUlStyle.borderLeftWidth = `${dbwLeft}px`;
    } else {
        dropdownUlStyle.border = '1px solid #e5e7eb';
    }

    if (dmTop === null && dmRight === null && dmBottom === null && dmLeft === null) {
        dropdownUlStyle.margin = 0;
    }
    if (dpTop === null && dpRight === null && dpBottom === null && dpLeft === null) {
        dropdownUlStyle.paddingRight = '0px';
        dropdownUlStyle.paddingBottom = '8px';
        dropdownUlStyle.paddingLeft = '0px';
    }

    const dic = props?.dropdownItemColor || '';
    const dif = props?.dropdownItemFontFamily || '';
    const dis = intOrNull(props?.dropdownItemFontSize);
    const diw = props?.dropdownItemFontWeight || '';
    const dilh = intOrNull(props?.dropdownItemLineHeight);
    const dils = intOrNull(props?.dropdownItemLetterSpacing);
    const diws = intOrNull(props?.dropdownItemWordSpacing);
    const dimt = intOrNull(props?.dropdownItemMarginTop);
    const dimr = intOrNull(props?.dropdownItemMarginRight);
    const dimb = intOrNull(props?.dropdownItemMarginBottom);
    const diml = intOrNull(props?.dropdownItemMarginLeft);
    const dipt = intOrNull(props?.dropdownItemPaddingTop);
    const dipr = intOrNull(props?.dropdownItemPaddingRight);
    const dipb = intOrNull(props?.dropdownItemPaddingBottom);
    const dipl = intOrNull(props?.dropdownItemPaddingLeft);
    const dibStyle = props?.dropdownItemBorderStyle || '';
    const dibColor = props?.dropdownItemBorderColor || '';
    const dibwTop = intOrNull(props?.dropdownItemBorderWidthTop);
    const dibwRight = intOrNull(props?.dropdownItemBorderWidthRight);
    const dibwBottom = intOrNull(props?.dropdownItemBorderWidthBottom);
    const dibwLeft = intOrNull(props?.dropdownItemBorderWidthLeft);
    const dibRadius = intOrNull(props?.dropdownItemBorderRadius);

    const dropdownLinkStyle = buildStyle([
        ['display', 'block'],
        ['textDecoration', 'none'],
        ['color', dic || '#374151'],
        ['fontFamily', dif || undefined],
        ['fontSize', dis !== null && dis > 0 ? `${dis}px` : '14px'],
        ['fontWeight', diw || undefined],
        ['lineHeight', dilh !== null && dilh > 0 ? dilh : undefined],
        ['letterSpacing', dils !== null ? `${dils}px` : undefined],
        ['wordSpacing', diws !== null ? `${diws}px` : undefined],
        ['marginTop', dimt !== null ? `${dimt}px` : undefined],
        ['marginRight', dimr !== null ? `${dimr}px` : undefined],
        ['marginBottom', dimb !== null ? `${dimb}px` : undefined],
        ['marginLeft', diml !== null ? `${diml}px` : undefined],
        ['paddingTop', dipt !== null ? `${dipt}px` : undefined],
        ['paddingRight', dipr !== null ? `${dipr}px` : undefined],
        ['paddingBottom', dipb !== null ? `${dipb}px` : undefined],
        ['paddingLeft', dipl !== null ? `${dipl}px` : undefined],
        ['borderRadius', dibRadius !== null && dibRadius >= 0 ? `${dibRadius}px` : undefined],
    ]);

    if (dibStyle !== '') {
        dropdownLinkStyle.borderStyle = dibStyle;
        if (dibColor !== '') dropdownLinkStyle.borderColor = dibColor;
        if (dibwTop !== null && dibwTop >= 0) dropdownLinkStyle.borderTopWidth = `${dibwTop}px`;
        if (dibwRight !== null && dibwRight >= 0) dropdownLinkStyle.borderRightWidth = `${dibwRight}px`;
        if (dibwBottom !== null && dibwBottom >= 0) dropdownLinkStyle.borderBottomWidth = `${dibwBottom}px`;
        if (dibwLeft !== null && dibwLeft >= 0) dropdownLinkStyle.borderLeftWidth = `${dibwLeft}px`;
    }

    if (dipt === null && dipr === null && dipb === null && dipl === null) {
        dropdownLinkStyle.padding = '6px 16px';
    }

    const mmFixedWidth = intOrNull(props?.megaMenuFixedWidth) ?? 800;
    const mmBg = props?.megaMenuBackgroundColor || '#fff';
    const mmPadTop = intOrNull(props?.megaMenuPaddingTop);
    const mmPadRight = intOrNull(props?.megaMenuPaddingRight);
    const mmPadBottom = intOrNull(props?.megaMenuPaddingBottom);
    const mmPadLeft = intOrNull(props?.megaMenuPaddingLeft);
    const mmMarTop = intOrNull(props?.megaMenuMarginTop);
    const mmMarRight = intOrNull(props?.megaMenuMarginRight);
    const mmMarBottom = intOrNull(props?.megaMenuMarginBottom);
    const mmMarLeft = intOrNull(props?.megaMenuMarginLeft);
    const mmBStyle = props?.megaMenuBorderStyle || '';
    const mmBColor = props?.megaMenuBorderColor || '';
    const mmBwTop = intOrNull(props?.megaMenuBorderWidthTop);
    const mmBwRight = intOrNull(props?.megaMenuBorderWidthRight);
    const mmBwBottom = intOrNull(props?.megaMenuBorderWidthBottom);
    const mmBwLeft = intOrNull(props?.megaMenuBorderWidthLeft);
    const mmBR = intOrNull(props?.megaMenuBorderRadius);
    const mmBRTop = intOrNull(props?.megaMenuBorderRadiusTop) ?? mmBR;
    const mmBRRight = intOrNull(props?.megaMenuBorderRadiusRight) ?? mmBR;
    const mmBRBottom = intOrNull(props?.megaMenuBorderRadiusBottom) ?? mmBR;
    const mmBRLeft = intOrNull(props?.megaMenuBorderRadiusLeft) ?? mmBR;

    const mmPanelStyle = buildStyle([
        ['position', 'fixed'],
        ['top', panelPos.top + 12],
        ['left', mmWidthMode === 'full' ? 0 : panelPos.left],
        ['width', mmWidthMode === 'fixed' ? `${mmFixedWidth}px` : '100vw'],
        ['maxWidth', mmWidthMode === 'fixed' ? '90vw' : undefined],
        ['display', open ? 'block' : 'none'],
        ['opacity', visible ? 1 : 0],
        ['transform', visible ? 'translateY(0) scale(1)' : 'translateY(20px) scale(0.98)'],
        ['transformOrigin', 'top center'],
        ['transition', 'opacity 400ms cubic-bezier(0.16,1,0.3,1), transform 400ms cubic-bezier(0.16,1,0.3,1)'],
        ['pointerEvents', 'auto'],
        ['backgroundColor', mmBg],
        ['paddingTop', mmPadTop !== null ? `${mmPadTop}px` : '24px'],
        ['paddingRight', mmPadRight !== null ? `${mmPadRight}px` : undefined],
        ['paddingBottom', mmPadBottom !== null ? `${mmPadBottom}px` : '24px'],
        ['paddingLeft', mmPadLeft !== null ? `${mmPadLeft}px` : undefined],
        ['marginTop', mmMarTop !== null ? `${mmMarTop}px` : undefined],
        ['marginRight', mmMarRight !== null ? `${mmMarRight}px` : undefined],
        ['marginBottom', mmMarBottom !== null ? `${mmMarBottom}px` : undefined],
        ['marginLeft', mmMarLeft !== null ? `${mmMarLeft}px` : undefined],
        ['borderTopLeftRadius', mmBRTop !== null && mmBRTop >= 0 ? `${mmBRTop}px` : '12px'],
        ['borderTopRightRadius', mmBRRight !== null && mmBRRight >= 0 ? `${mmBRRight}px` : '12px'],
        ['borderBottomRightRadius', mmBRBottom !== null && mmBRBottom >= 0 ? `${mmBRBottom}px` : '12px'],
        ['borderBottomLeftRadius', mmBRLeft !== null && mmBRLeft >= 0 ? `${mmBRLeft}px` : '12px'],
        ['boxShadow', '0 8px 24px rgba(0,0,0,0.1)'],
    ]);

    if (mmBStyle !== '') {
        mmPanelStyle.borderStyle = mmBStyle;
        if (mmBColor !== '') mmPanelStyle.borderColor = mmBColor;
        if (mmBwTop !== null && mmBwTop >= 0) mmPanelStyle.borderTopWidth = `${mmBwTop}px`;
        if (mmBwRight !== null && mmBwRight >= 0) mmPanelStyle.borderRightWidth = `${mmBwRight}px`;
        if (mmBwBottom !== null && mmBwBottom >= 0) mmPanelStyle.borderBottomWidth = `${mmBwBottom}px`;
        if (mmBwLeft !== null && mmBwLeft >= 0) mmPanelStyle.borderLeftWidth = `${mmBwLeft}px`;
    } else {
        mmPanelStyle.border = '1px solid #e5e7eb';
    }

    const portalContent = (
        <React.Fragment>
            {isDropdown && hasChildren && open && (
                <ul
                    className="mp-nav-dropdown"
                    style={dropdownUlStyle}
                    onMouseEnter={handleEnter}
                    onMouseLeave={handleLeave}
                >
                    {item.children.map((child, cidx) => (
                        <li key={cidx}>
                            <a href={String(child?.url || '#')} className="mp-nav-dropdown-link" style={dropdownLinkStyle}>
                                {String(child?.label || 'Link')}
                            </a>
                        </li>
                    ))}
                </ul>
            )}
            {isMegaMenu && open && (
                <div
                    className="mp-nav-megamenu"
                    style={mmPanelStyle}
                    onMouseEnter={handleEnter}
                    onMouseLeave={handleLeave}
                >
                    <div className="text-xs text-gray-400">
                        {(() => {
                            const mm = megaMenus.find((m) => String(m.id) === String(item?.mega_menu_id));
                            return mm ? `Mega Menu: ${mm.name}` : 'Mega Menu Panel';
                        })()}
                    </div>
                </div>
            )}
        </React.Fragment>
    );

    return (
        <div
            ref={liRef}
            className="mp-nav-item"
            style={{ position: 'relative' }}
            onMouseEnter={handleEnter}
            onMouseLeave={handleLeave}
        >
            <a
                href={url}
                className="mp-nav-link flex items-center gap-1"
                style={linkStyle}
                data-text={label}
                onMouseMove={props.hoverEffect === 'magnetic' ? (e) => {
                    const r = e.currentTarget.getBoundingClientRect();
                    if (!r.width) return;
                    const x = ((e.clientX - r.left) / r.width) * 100;
                    e.currentTarget.style.setProperty('--mp-mag-origin', `${Math.max(0, Math.min(100, x))}%`);
                } : undefined}
            >
                <span className="mp-link-text">{label}</span>
                {(hasChildren || isMegaMenu) && (
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                )}
            </a>
            {open && portalRoot ? createPortal(portalContent, portalRoot) : null}
        </div>
    );
}

export function renderNavMenuPreview(widget) {
    const w = widget;
    const navMenus = (typeof window !== 'undefined' && Array.isArray(window.__MP_NAV_MENUS)) ? window.__MP_NAV_MENUS : [];
    const selectedMenu = w.props?.nav_menu_id != null
        ? navMenus.find((m) => String(m?.id) === String(w.props.nav_menu_id))
        : null;
    const rawItems = Array.isArray(selectedMenu?.items)
        ? selectedMenu.items
        : (Array.isArray(w.props?.items) ? w.props.items : []);

    // Resolve page_id / route items to URLs for preview
    const items = rawItems.map((item) => {
        if (!item || typeof item !== 'object') return item;
        if (item.type === 'route' && item.route) {
            return { ...item, type: 'link', url: '/' + item.route.replace(/\./g, '/'), label: item.label || item.route };
        }
        if (item.type === 'page' && item.page_id) {
            return { ...item, type: 'link', url: '/page-' + item.page_id, label: item.label || 'Page' };
        }
        if (item.type === 'custom') {
            return { ...item, type: 'link', url: item.url || '#', label: item.label || item.url || 'Link' };
        }
        if (item.type === 'dropdown' && Array.isArray(item.children)) {
            return { ...item, children: item.children.map((c) => {
                if (c.type === 'route' && c.route) {
                    return { ...c, type: 'link', url: '/' + c.route.replace(/\./g, '/'), label: c.label || c.route };
                }
                if (c.type === 'page' && c.page_id) {
                    return { ...c, type: 'link', url: '/page-' + c.page_id, label: c.label || 'Page' };
                }
                if (c.type === 'custom') {
                    return { ...c, type: 'link', url: c.url || '#', label: c.label || c.url || 'Link' };
                }
                return c;
            })};
        }
        return item;
    });

    const align = w.props?.align || 'left';
    const spacing = w.props?.spacing || '24';
    const props = w.props || {};

    const scopeClass = `mp-nav-menu-${String(w.id || '0').replace(/[^a-zA-Z0-9]/g, '-')}`;
    const hoverEffect = HOVER_EFFECT_OPTIONS.some((o) => o.value && o.value === props.hoverEffect) ? props.hoverEffect : '';
    const effectCss = hoverEffectCss(hoverEffect, `.${scopeClass}`, '.mp-nav-link');

    const justify = align === 'center' ? 'center' : align === 'right' ? 'flex-end' : 'flex-start';

    const gap = intOrNull(props.gap);
    const gapPx = gap !== null && gap >= 0 ? gap : (Number.isFinite(Number(spacing)) ? Number(spacing) : 24);

    const marginTop = intOrNull(props.marginTop);
    const marginRight = intOrNull(props.marginRight);
    const marginBottom = intOrNull(props.marginBottom);
    const marginLeft = intOrNull(props.marginLeft);

    const paddingTop = intOrNull(props.paddingTop);
    const paddingRight = intOrNull(props.paddingRight);
    const paddingBottom = intOrNull(props.paddingBottom);
    const paddingLeft = intOrNull(props.paddingLeft);

    const backgroundColor = props.backgroundColor || '';
    const borderRadius = intOrNull(props.borderRadius);
    const borderRadiusTop = intOrNull(props.borderRadiusTop) ?? borderRadius;
    const borderRadiusRight = intOrNull(props.borderRadiusRight) ?? borderRadius;
    const borderRadiusBottom = intOrNull(props.borderRadiusBottom) ?? borderRadius;
    const borderRadiusLeft = intOrNull(props.borderRadiusLeft) ?? borderRadius;
    const backdropBlur = Number.isFinite(Number(props.backdropBlur)) ? Number(props.backdropBlur) : 0;

    const ulStyle = buildStyle([
        ['display', 'flex'],
        ['flexWrap', 'wrap'],
        ['alignItems', 'center'],
        ['justifyContent', justify],
        ['gap', `${gapPx}px`],
        ['listStyle', 'none'],
        ['marginTop', marginTop !== null ? `${marginTop}px` : undefined],
        ['marginRight', marginRight !== null ? `${marginRight}px` : undefined],
        ['marginBottom', marginBottom !== null ? `${marginBottom}px` : undefined],
        ['marginLeft', marginLeft !== null ? `${marginLeft}px` : undefined],
        ['paddingTop', paddingTop !== null ? `${paddingTop}px` : undefined],
        ['paddingRight', paddingRight !== null ? `${paddingRight}px` : undefined],
        ['paddingBottom', paddingBottom !== null ? `${paddingBottom}px` : undefined],
        ['paddingLeft', paddingLeft !== null ? `${paddingLeft}px` : undefined],
        ['backgroundColor', backgroundColor || undefined],
        ['borderTopLeftRadius', borderRadiusTop !== null && borderRadiusTop >= 0 ? `${borderRadiusTop}px` : undefined],
        ['borderTopRightRadius', borderRadiusRight !== null && borderRadiusRight >= 0 ? `${borderRadiusRight}px` : undefined],
        ['borderBottomRightRadius', borderRadiusBottom !== null && borderRadiusBottom >= 0 ? `${borderRadiusBottom}px` : undefined],
        ['borderBottomLeftRadius', borderRadiusLeft !== null && borderRadiusLeft >= 0 ? `${borderRadiusLeft}px` : undefined],
        ['backdropFilter', backdropBlur > 0 ? `blur(${backdropBlur}px)` : undefined],
        ['WebkitBackdropFilter', backdropBlur > 0 ? `blur(${backdropBlur}px)` : undefined],
    ]);

    if (marginTop === null && marginRight === null && marginBottom === null && marginLeft === null) {
        ulStyle.margin = 0;
    }
    if (paddingTop === null && paddingRight === null && paddingBottom === null && paddingLeft === null) {
        ulStyle.padding = 0;
    }

    return (
        <div className={`mp-nav-menu ${scopeClass}`} style={ulStyle}>
            {effectCss ? <style>{effectCss}</style> : null}
            {items.length === 0 ? (
                <span className="text-sm text-gray-400">Nav Menu (empty)</span>
            ) : (
                items.map((item, idx) => <NavMenuItemPreview key={idx} item={item} props={props} megaMenus={(typeof window !== 'undefined' && window.__MP_MEGA_MENUS) || []} />)
            )}
        </div>
    );
}

registerElement({
    type: 'nav_menu',
    label: 'Nav Menu',
    group: 'Basic',
    defaultProps: {
        items: [],
        nav_menu_id: null,
        align: 'left',
        spacing: 24,
    },
    render: renderNavMenuPreview,
    schema: { content: [], style: [], advanced: [] },
});
