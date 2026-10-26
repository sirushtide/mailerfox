import React from 'react';
import { createPortal } from 'react-dom';
import * as CONTROL from '../controls/types.js';
import { svgWithWidth } from '../lib/svg.js';

// Mobile Menu is a container widget. It reuses the grid container data shape
// (props.columns) so nested elements (nav menu, buttons, etc.) work with the
// existing container editing infrastructure. `props.mobileMenu` marks it as a
// mobile menu so renderers/inspectors branch accordingly.
export const MOBILE_MENU_DEFAULTS = {
    mobileMenu: true,
    count: 1,
    mobileMenuToggleIconUrl: '',
    mobileMenuToggleIconSvg: '',
    mobileMenuToggleIconSource: 'url',
    mobileMenuToggleIconSize: 28,
    mobileMenuDirection: 'right',
    mobileMenuAnimation: 'slide',
    mobileMenuOverlayColor: 'rgba(17, 24, 39, 0.5)',
    mobileMenuCloseColor: '',
    mobileMenuBackgroundType: 'color',
    mobileMenuBackgroundColor: '#ffffff',
    mobileMenuGradientFrom: '#111827',
    mobileMenuGradientTo: '#3b82f6',
    mobileMenuGradientType: 'linear',
    mobileMenuGradientPosition: 'top center',
    mobileMenuGradientAngle: 135,
    mobileMenuBorderStyle: 'solid',
    mobileMenuBorderColor: '#e5e7eb',
    mobileMenuBorderWidthTop: 0,
    mobileMenuBorderWidthRight: 0,
    mobileMenuBorderWidthBottom: 0,
    mobileMenuBorderWidthLeft: 0,
    mobileMenuBorderWidthLinked: true,
    mobileMenuBorderRadiusTop: 0,
    mobileMenuBorderRadiusRight: 0,
    mobileMenuBorderRadiusBottom: 0,
    mobileMenuBorderRadiusLeft: 0,
    mobileMenuBorderRadiusLinked: true,
    mobileMenuPaddingTop: 24,
    mobileMenuPaddingRight: 24,
    mobileMenuPaddingBottom: 24,
    mobileMenuPaddingLeft: 24,
    mobileMenuPaddingLinked: true,
};

export function isMobileMenuProps(props) {
    return Boolean(props && props.mobileMenu === true);
}

function pick(v, fallback) {
    return typeof v === 'string' && v.trim() ? v.trim() : fallback;
}

export function mobileMenuDrawerStyle(props) {
    const p = props || {};
    const style = {};

    if (p.mobileMenuBackgroundType === 'gradient') {
        const from = pick(p.mobileMenuGradientFrom, '#111827');
        const to = pick(p.mobileMenuGradientTo, '#3b82f6');
        const type = p.mobileMenuGradientType === 'radial' ? 'radial' : 'linear';
        if (type === 'radial') {
            const position = pick(p.mobileMenuGradientPosition, 'top center');
            style.backgroundImage = `radial-gradient(at ${position}, ${from}, ${to})`;
        } else {
            const angle = Number.isFinite(Number(p.mobileMenuGradientAngle)) ? Number(p.mobileMenuGradientAngle) : 135;
            style.backgroundImage = `linear-gradient(${angle}deg, ${from}, ${to})`;
        }
    } else if (typeof p.mobileMenuBackgroundColor === 'string' && p.mobileMenuBackgroundColor.trim()) {
        style.backgroundColor = p.mobileMenuBackgroundColor.trim();
    }

    const bwTop = p.mobileMenuBorderWidthTop;
    const bwRight = p.mobileMenuBorderWidthRight;
    const bwBottom = p.mobileMenuBorderWidthBottom;
    const bwLeft = p.mobileMenuBorderWidthLeft;
    const hasBorder = [bwTop, bwRight, bwBottom, bwLeft].some((v) => Number(v) > 0);
    if (hasBorder) {
        style.borderStyle = pick(p.mobileMenuBorderStyle, 'solid');
        style.borderColor = pick(p.mobileMenuBorderColor, '#e5e7eb');
        if (Number.isFinite(Number(bwTop))) style.borderTopWidth = `${Number(bwTop)}px`;
        if (Number.isFinite(Number(bwRight))) style.borderRightWidth = `${Number(bwRight)}px`;
        if (Number.isFinite(Number(bwBottom))) style.borderBottomWidth = `${Number(bwBottom)}px`;
        if (Number.isFinite(Number(bwLeft))) style.borderLeftWidth = `${Number(bwLeft)}px`;
    }

    const radii = [
        ['mobileMenuBorderRadiusTop', 'borderTopLeftRadius'],
        ['mobileMenuBorderRadiusRight', 'borderTopRightRadius'],
        ['mobileMenuBorderRadiusBottom', 'borderBottomRightRadius'],
        ['mobileMenuBorderRadiusLeft', 'borderBottomLeftRadius'],
    ];
    for (const [key, cssKey] of radii) {
        if (Number.isFinite(Number(p[key]))) style[cssKey] = `${Number(p[key])}px`;
    }

    const pads = [
        ['mobileMenuPaddingTop', 'paddingTop'],
        ['mobileMenuPaddingRight', 'paddingRight'],
        ['mobileMenuPaddingBottom', 'paddingBottom'],
        ['mobileMenuPaddingLeft', 'paddingLeft'],
    ];
    for (const [key, cssKey] of pads) {
        if (Number.isFinite(Number(p[key]))) style[cssKey] = `${Number(p[key])}px`;
    }

    return style;
}

export function MobileMenuToggleIcon({ props, size, color }) {
    const p = props || {};
    const url = typeof p.mobileMenuToggleIconUrl === 'string' ? p.mobileMenuToggleIconUrl.trim() : '';
    const svg = typeof p.mobileMenuToggleIconSvg === 'string' ? p.mobileMenuToggleIconSvg.trim() : '';
    const source = p.mobileMenuToggleIconSource === 'svg' || p.mobileMenuToggleIconSource === 'url' ? p.mobileMenuToggleIconSource : '';
    const px = Number.isFinite(Number(size)) ? Number(size) : (Number.isFinite(Number(p.mobileMenuToggleIconSize)) ? Number(p.mobileMenuToggleIconSize) : 28);

    if (svg !== '' && (source === 'svg' || url === '')) {
        return <span style={{ display: 'inline-flex', color: color || undefined }} dangerouslySetInnerHTML={{ __html: svgWithWidth(svg, `${px}px`, `${px}px`) }} />;
    }
    if (url !== '') {
        return <img src={url} alt="" style={{ width: px, height: px, objectFit: 'contain', display: 'block' }} />;
    }
    return (
        <svg xmlns="http://www.w3.org/2000/svg" width={px} height={px} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{ color: color || undefined }}>
            <line x1="3" y1="6" x2="21" y2="6" />
            <line x1="3" y1="12" x2="21" y2="12" />
            <line x1="3" y1="18" x2="21" y2="18" />
        </svg>
    );
}

export function MobileMenuCanvas({ props, children }) {
    const p = props || {};
    const [open, setOpen] = React.useState(false);
    const direction = p.mobileMenuDirection === 'left' ? 'left' : 'right';
    const drawerStyle = mobileMenuDrawerStyle(p);

    return (
        <div className="relative inline-block max-w-full">
            <button
                type="button"
                onClick={() => {
                    setOpen((v) => !v);
                }}
                className="inline-flex items-center justify-center rounded-md border border-dashed border-blue-300 bg-white/80 p-2 text-gray-800 hover:bg-blue-50"
                title="Toggle mobile menu drawer"
                aria-expanded={open}
            >
                <MobileMenuToggleIcon props={p} />
            </button>

            {open && typeof document !== 'undefined'
                ? createPortal(
                      <div className="fixed inset-0 z-[9999]" role="dialog" aria-modal="true">
                          <div className="absolute inset-0 bg-black/50" onClick={() => setOpen(false)} />
                          <div
                              className="absolute top-0 bottom-0 w-[360px] max-w-[85vw] overflow-y-auto shadow-2xl"
                              style={{ ...drawerStyle, [direction]: 0 }}
                              onClick={(e) => e.stopPropagation()}
                          >
                              <div className="flex items-center justify-between px-3 py-2">
                                  <span className="text-xs font-medium text-gray-400">Drawer preview</span>
                                  <button
                                      type="button"
                                      onClick={() => setOpen(false)}
                                      className="text-xl leading-none text-gray-400 hover:text-gray-700"
                                      aria-label="Close menu"
                                  >
                                      ×
                                  </button>
                              </div>
                              <div className="p-3">{children}</div>
                          </div>
                      </div>,
                      document.body
                  )
                : null}
        </div>
    );
}

export const mobileMenuContentSchema = [
    { type: CONTROL.CONTROL_MEDIA_SOURCES, name: 'mobileMenuToggleIcon', label: 'Toggle Icon', bind: { url: 'props.mobileMenuToggleIconUrl', svg: 'props.mobileMenuToggleIconSvg', source: 'props.mobileMenuToggleIconSource' } },
    { type: CONTROL.CONTROL_SLIDER, name: 'mobileMenuToggleIconSize', label: 'Icon Size (px)', bind: 'props.mobileMenuToggleIconSize', kind: 'font-size', min: 12, max: 96 },
    { type: CONTROL.CONTROL_SELECT, name: 'mobileMenuDirection', label: 'Drawer Direction', bind: 'props.mobileMenuDirection', options: [
        { value: 'right', label: 'Right' },
        { value: 'left', label: 'Left' },
    ]},
    { type: CONTROL.CONTROL_SELECT, name: 'mobileMenuAnimation', label: 'Open Animation', bind: 'props.mobileMenuAnimation', options: [
        { value: 'slide', label: 'Slide' },
        { value: 'fade', label: 'Fade' },
        { value: 'scale', label: 'Scale' },
        { value: 'none', label: 'None' },
    ]},
];

export const mobileMenuStyleSchema = [
    {
        title: 'Drawer',
        defaultOpen: true,
        children: [
            { type: CONTROL.CONTROL_GROUP, kind: 'background', label: 'Background', bind: { type: 'props.mobileMenuBackgroundType', color: 'props.mobileMenuBackgroundColor', from: 'props.mobileMenuGradientFrom', to: 'props.mobileMenuGradientTo', gradientType: 'props.mobileMenuGradientType', position: 'props.mobileMenuGradientPosition', angle: 'props.mobileMenuGradientAngle' } },
            { type: CONTROL.CONTROL_GROUP, kind: 'border', label: 'Border', bind: { styleKey: 'props.mobileMenuBorderStyle', colorKey: 'props.mobileMenuBorderColor', widthKeys: { linked: 'props.mobileMenuBorderWidthLinked', t: 'props.mobileMenuBorderWidthTop', r: 'props.mobileMenuBorderWidthRight', b: 'props.mobileMenuBorderWidthBottom', l: 'props.mobileMenuBorderWidthLeft' } }, includeRadius: true, radiusBind: { linked: 'props.mobileMenuBorderRadiusLinked', t: 'props.mobileMenuBorderRadiusTop', r: 'props.mobileMenuBorderRadiusRight', b: 'props.mobileMenuBorderRadiusBottom', l: 'props.mobileMenuBorderRadiusLeft' } },
            { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'mobileMenuCloseColor', label: 'Close Icon Color', bind: 'props.mobileMenuCloseColor' },
            { type: CONTROL.CONTROL_DIMENSIONS, name: 'mobileMenuPadding', label: 'Padding', bind: { linked: 'props.mobileMenuPaddingLinked', t: 'props.mobileMenuPaddingTop', r: 'props.mobileMenuPaddingRight', b: 'props.mobileMenuPaddingBottom', l: 'props.mobileMenuPaddingLeft' } },
        ],
    },
];
