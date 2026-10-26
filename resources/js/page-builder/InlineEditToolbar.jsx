import React, { useEffect, useMemo, useRef, useState } from 'react';
import { ChromePicker } from 'react-color';
import { createPortal } from 'react-dom';
import { normalizeHexColor } from './ui/color';

const GOOGLE_FONTS = [
    'Inter', 'Roboto', 'Poppins', 'Open Sans', 'Lato', 'Montserrat', 'Raleway',
    'Playfair Display', 'Merriweather', 'Oswald', 'Nunito', 'Source Sans 3',
    'Work Sans', 'Fira Sans', 'Ubuntu', 'Cabin', 'Bitter', 'Arimo', 'PT Sans',
    'DM Sans', 'Rubik', 'Mulish', 'Manrope', 'Space Grotesk', 'Noto Sans',
    'IBM Plex Sans', 'Josefin Sans', 'Heebo', 'Teko', 'Karla', 'Quicksand',
    'Bebas Neue', 'Pacifico', 'Lobster', 'Comfortaa', 'Righteous', 'Bangers',
    'Anton', 'Permanent Marker', 'Architects Daughter', 'Indie Flower', 'Satisfy',
    'Courgette', 'Great Vibes', 'Dancing Script', 'Sacramento', 'Parisienne',
    'Amatic SC', 'Crimson Text', 'Literata', 'Cormorant Garamond', 'EB Garamond',
    'Spectral', 'Libre Baskerville', 'Cardo', 'Lora', 'Alegreya', 'Martel',
    'Sora', 'Syne', 'Unbounded', 'Onest', 'Bricolage Grotesque', 'Figtree',
    'Instrument Sans', 'Plus Jakarta Sans', 'Outfit', 'Hanken Grotesk', 'Lexend',
    'Shrikhand', 'Almarai', 'Tajawal', 'Noto Sans SC', 'Noto Serif', 'JetBrains Mono',
    'Source Code Pro', 'Space Mono', 'Inconsolata', 'Vollkorn', 'Urbanist', 'Gantari',
    'Epilogue', 'Red Hat Display', 'Kanit', 'Prompt', 'Exo 2', 'Barlow', 'Maitree',
    'Varela Round', 'Asap', 'Commissioner', 'Zilla Slab', 'Merriweather Sans',
    'Fraunces', 'Titan One', 'Young Serif', 'Teachers', 'Tiny5', 'Jersey 15',
    'Jacques Francois',
];

const FONT_WEIGHTS = [
    { label: 'Light', value: 300 },
    { label: 'Regular', value: 400 },
    { label: 'Medium', value: 500 },
    { label: 'SemiBold', value: 600 },
    { label: 'Bold', value: 700 },
];

const ICON = {
    bg: <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>,
    text: <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M4 7V5h16v2"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>,
    font: <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M4 7V5h16v2"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>,
    style: <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-type-icon lucide-type"><path d="M12 4v16"/><path d="M4 7V5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v2"/><path d="M9 20h6"/></svg>,
    opacity: <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="9" cy="9" r="7"/><circle cx="15" cy="15" r="7"/></svg>,
    chevronDown: <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m6 9 6 6 6-6"/></svg>,
    chevronRight: <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m9 6 6 6-6 6"/></svg>,
    spacing: <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-align-horizontal-space-around-icon lucide-align-horizontal-space-around"><rect width="6" height="10" x="9" y="7" rx="2"/><path d="M4 22V2"/><path d="M20 22V2"/></svg>,
    radius: <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="3" width="18" height="18" rx="6"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>,
    border: <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-square-round-corner-icon lucide-square-round-corner"><path d="M21 11a8 8 0 0 0-8-8"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/></svg>,
    size: <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M4 6V4h2"/><path d="M4 18v2h2"/><path d="M20 6V4h-2"/><path d="M20 18v2h-2"/><rect x="7" y="9" width="10" height="6" rx="2"/></svg>,
    code: <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>,
    link: <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M10 13a5 5 0 0 0 7.07.07l2-2a5 5 0 0 0-7.07-7.07l-1.15 1.15"/><path d="M14 11a5 5 0 0 0-7.07-.07l-2 2A5 5 0 0 0 12 20l1.15-1.15"/></svg>,
    image: <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>,
    trash: <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>,
    close: <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M18 6 6 18"/><path d="M6 6l12 12"/></svg>,
    bold: <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/><path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/></svg>,
    italic: <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg>,
    underline: <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M6 3v7a6 6 0 0 0 6 6 6 6 0 0 0 6-6V3"/><line x1="4" y1="21" x2="20" y2="21"/></svg>,
    upper: <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 6h18"/><path d="M7 20h4"/><path d="M13 20h4"/><path d="M9 6v14"/><path d="M15 6v14"/></svg>,
    alignLeft: <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M17 10H3"/><path d="M21 6H3"/><path d="M21 14H3"/><path d="M17 18H3"/></svg>,
    alignCenter: <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M18 10H6"/><path d="M21 6H3"/><path d="M21 14H3"/><path d="M18 18H6"/></svg>,
    alignRight: <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 10H7"/><path d="M21 6H3"/><path d="M21 14H3"/><path d="M21 18H7"/></svg>,
    alignJustify: <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 6h18"/><path d="M3 10h18"/><path d="M3 14h18"/><path d="M3 18h18"/></svg>,
};

function postMessage(iframeRef, message) {
    const iframe = iframeRef?.current;
    if (iframe && iframe.contentWindow) {
        try {
            iframe.contentWindow.postMessage(message, '*');
        } catch (e) {
            // ignore
        }
    }
}

function parseColor(value) {
    if (!value) return { hex: '#000000', alpha: 1 };
    const rgba = value.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/);
    if (rgba) {
        const r = parseInt(rgba[1], 10);
        const g = parseInt(rgba[2], 10);
        const b = parseInt(rgba[3], 10);
        const a = rgba[4] ? parseFloat(rgba[4]) : 1;
        const hex = '#' + [r, g, b].map((x) => x.toString(16).padStart(2, '0')).join('');
        return { hex, alpha: a };
    }
    if (/^#[0-9a-f]{3,8}$/i.test(value)) {
        return { hex: value.slice(0, 7), alpha: 1 };
    }
    return { hex: '#000000', alpha: 1 };
}

function getFontLabel(fontFamily) {
    if (!fontFamily || typeof fontFamily !== 'string') return 'Font';
    return fontFamily
        .split(',')[0]
        .replace(/^['"]|['"]$/g, '')
        .trim() || 'Font';
}

function hexToRgba(hex, alpha) {
    const clean = hex.replace('#', '');
    const r = parseInt(clean.slice(0, 2), 16) || 0;
    const g = parseInt(clean.slice(2, 4), 16) || 0;
    const b = parseInt(clean.slice(4, 6), 16) || 0;
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function ToolbarButton({ title, active, onClick, children, className = '' }) {
    const [showTooltip, setShowTooltip] = useState(false);
    return (
        <button
            type="button"
            aria-label={title}
            onMouseEnter={() => setShowTooltip(true)}
            onMouseLeave={() => setShowTooltip(false)}
            onClick={onClick}
            className={[
                'relative h-9 px-3 rounded-xl text-sm font-medium inline-flex items-center gap-2 transition-colors',
                active
                    ? 'bg-blue-50 text-blue-700'
                    : 'bg-transparent text-gray-700 hover:bg-gray-100',
                className,
            ].join(' ')}
        >
            {children}
            {showTooltip && title && (
                <span className="absolute bottom-full left-1/2 mb-2 -translate-x-1/2 pointer-events-none z-50 whitespace-nowrap rounded-lg bg-white border border-gray-200 px-2 py-1 text-xs font-medium text-gray-700 shadow-lg">
                    {title}
                </span>
            )}
        </button>
    );
}

function ButtonGroup({ children, className = '' }) {
    return <div className={`flex rounded-xl border border-gray-200 overflow-hidden ${className}`}>{children}</div>;
}

function GroupButton({ active, onClick, children, title }) {
    return (
        <button
            type="button"
            onClick={onClick}
            title={title}
            className={[
                'flex-1 h-8 px-2 text-sm font-medium border-r border-gray-200 last:border-r-0 flex items-center justify-center transition-colors',
                active ? 'bg-blue-50 text-blue-700' : 'bg-white text-gray-700 hover:bg-gray-50',
            ].join(' ')}
        >
            {children}
        </button>
    );
}

function Popover({ title, open, onClose, children, style, className = 'w-64' }) {
    if (!open) return null;
    return (
        <div
            className={`absolute z-50 mt-2 rounded-xl border border-gray-200 bg-white shadow-xl p-3 ${className}`}
            style={style}
        >
            <div className="flex items-center justify-between mb-2">
                <span className="text-base font-semibold text-gray-900">{title}</span>
                <button type="button" onClick={onClose} className="text-gray-400 hover:text-gray-600">
                    {ICON.close}
                </button>
            </div>
            {children}
        </div>
    );
}

function InlineColorPicker({ value, onChange, title, fallback = '#000000' }) {
    const [open, setOpen] = useState(false);
    const triggerRef = useRef(null);
    const panelRef = useRef(null);
    const [panelStyle, setPanelStyle] = useState({ top: 0, left: 0 });

    const parsed = useMemo(() => parseColor(value), [value]);
    const color = useMemo(() => {
        const hex = normalizeHexColor(parsed.hex, fallback);
        return {
            r: parseInt(hex.slice(1, 3), 16) || 0,
            g: parseInt(hex.slice(3, 5), 16) || 0,
            b: parseInt(hex.slice(5, 7), 16) || 0,
            a: parsed.alpha,
        };
    }, [parsed, fallback]);

    useEffect(() => {
        if (!open) return;
        const updatePosition = () => {
            const el = triggerRef.current;
            if (!el) return;
            const rect = el.getBoundingClientRect();
            const panelWidth = 225;
            let left = rect.left;
            if (left + panelWidth > window.innerWidth - 8) {
                left = Math.max(8, rect.right - panelWidth);
            }
            setPanelStyle({ top: rect.bottom + 8, left });
        };
        updatePosition();
        window.addEventListener('resize', updatePosition);
        window.addEventListener('scroll', updatePosition, true);
        return () => {
            window.removeEventListener('resize', updatePosition);
            window.removeEventListener('scroll', updatePosition, true);
        };
    }, [open]);

    useEffect(() => {
        if (!open) return;
        const onPointerDown = (e) => {
            if (panelRef.current?.contains(e.target)) return;
            if (triggerRef.current?.contains(e.target)) return;
            setOpen(false);
        };
        const onKeyDown = (e) => {
            if (e.key === 'Escape') setOpen(false);
        };
        document.addEventListener('mousedown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);
        return () => {
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [open]);

    return (
        <div className="relative inline-flex">
            <button
                ref={triggerRef}
                type="button"
                onClick={() => setOpen((v) => !v)}
                className="h-9 w-9 rounded-xl border border-gray-200 shadow-sm transition-transform hover:scale-105"
                style={{ backgroundColor: value || fallback }}
                title={title}
                aria-label={title}
            />
            {open &&
                createPortal(
                    <div
                        ref={panelRef}
                        className="fixed z-50 rounded-xl border border-gray-200 bg-white shadow-xl overflow-hidden"
                        style={{ top: panelStyle.top, left: panelStyle.left }}
                    >
                        <ChromePicker
                            color={color}
                            disableAlpha={false}
                            onChange={(c) => {
                                const rgb = c.rgb;
                                onChange(`rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${rgb.a})`);
                            }}
                        />
                    </div>,
                    document.body
                )}
        </div>
    );
}

export function InlineEditToolbar({ target, iframeRef, onClose, onCode, onDelete, onStyleChange, onLinkChange, onImageChange, onReplaceWithImage, onReplaceWithSvg, imageUploadUrl }) {
    const [popover, setPopover] = useState(null);
    const [fontSearch, setFontSearch] = useState('');
    const [linkUrl, setLinkUrl] = useState('');
    const [linkTarget, setLinkTarget] = useState('_self');
    const [imageUrl, setImageUrl] = useState('');
    const [svgMarkup, setSvgMarkup] = useState('');
    const [uploadingImage, setUploadingImage] = useState(false);
    const imageUploadRef = useRef(null);
    const [hoveredFont, setHoveredFont] = useState(null);
    const [computed, setComputed] = useState({});
    const [hoveredRowTop, setHoveredRowTop] = useState(null);
    const toolbarRef = useRef(null);
    const iframeRefProp = iframeRef;
    const listRef = useRef(null);
    const hoverTimeoutRef = useRef(null);

    useEffect(() => {
        setPopover(null);
        setFontSearch('');
        setHoveredFont(null);
        setHoveredRowTop(null);
        setLinkUrl(target?.href || '');
        setLinkTarget(target?.linkTarget || '_self');
        setImageUrl(target?.src || '');
    }, [target?.path, target?.href, target?.linkTarget, target?.src]);

    useEffect(() => {
        const iframe = iframeRefProp?.current;
        if (!iframe || !iframe.contentWindow) return;
        try {
            const el = iframe.contentDocument.querySelector(target?.path);
            if (el) {
                const cs = iframe.contentWindow.getComputedStyle(el);
                setComputed({
                    fontSize: parseFloat(cs.fontSize) || 16,
                    fontFamily: cs.fontFamily,
                    opacity: parseFloat(cs.opacity) || 1,
                    backgroundColor: cs.backgroundColor,
                    color: cs.color,
                    marginTop: parseFloat(cs.marginTop) || 0,
                    marginBottom: parseFloat(cs.marginBottom) || 0,
                    marginLeft: parseFloat(cs.marginLeft) || 0,
                    marginRight: parseFloat(cs.marginRight) || 0,
                    paddingTop: parseFloat(cs.paddingTop) || 0,
                    paddingBottom: parseFloat(cs.paddingBottom) || 0,
                    paddingLeft: parseFloat(cs.paddingLeft) || 0,
                    paddingRight: parseFloat(cs.paddingRight) || 0,
                    width: parseFloat(cs.width) || 0,
                    height: parseFloat(cs.height) || 0,
                    borderRadius: parseFloat(cs.borderRadius) || 0,
                    borderWidth: parseFloat(cs.borderWidth) || 0,
                    borderColor: cs.borderColor,
                    borderStyle: cs.borderStyle,
                    fontWeight: cs.fontWeight,
                    fontStyle: cs.fontStyle,
                    textDecoration: cs.textDecoration,
                    textTransform: cs.textTransform,
                    textAlign: cs.textAlign,
                    objectFit: cs.objectFit,
                    objectPosition: cs.objectPosition,
                });
            }
        } catch (e) {
            // ignore
        }
    }, [target?.path, iframeRefProp]);

    useEffect(() => {
        if (typeof document === 'undefined') return;
        const id = 'mailpurse-opacity-slider-style';
        if (document.getElementById(id)) return;
        const style = document.createElement('style');
        style.id = id;
        style.textContent = `
            .mailpurse-opacity-slider::-webkit-slider-runnable-track {
                width: 100%; height: 6px; background: #e5e7eb; border-radius: 999px;
            }
            .mailpurse-opacity-slider::-moz-range-track {
                width: 100%; height: 6px; background: #e5e7eb; border-radius: 999px;
            }
            .mailpurse-opacity-slider::-webkit-slider-thumb {
                -webkit-appearance: none; appearance: none;
                height: 16px; width: 16px; margin-top: -5px;
                border-radius: 999px; background: #2563eb;
                border: 2px solid #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.2);
                cursor: pointer;
            }
            .mailpurse-opacity-slider::-moz-range-thumb {
                height: 16px; width: 16px;
                border-radius: 999px; background: #2563eb;
                border: 2px solid #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.2);
                cursor: pointer;
            }
        `;
        document.head.appendChild(style);
    }, []);

    useEffect(() => {
        if (popover !== 'font') {
            setHoveredFont(null);
            return;
        }
        if (typeof document === 'undefined') return;
        const id = 'mailpurse-edit-font-preview';
        if (document.getElementById(id)) return;
        const families = GOOGLE_FONTS.map((f) => encodeURIComponent(f).replace(/%20/g, '+')).join('|');
        const link = document.createElement('link');
        link.id = id;
        link.rel = 'stylesheet';
        link.href = `https://fonts.googleapis.com/css?family=${families}`;
        document.head.appendChild(link);
    }, [popover]);

    const position = useMemo(() => {
        const iframe = iframeRefProp?.current;
        if (!iframe || !target?.rect) return null;
        const iframeRect = iframe.getBoundingClientRect();
        const el = target.rect;
        return {
            left: iframeRect.left + el.left + el.width / 2,
            top: iframeRect.top + el.top - 8,
        };
    }, [target, iframeRefProp]);

    const applyStyle = (prop, value) => {
        postMessage(iframeRefProp, { type: 'MAILPURSE_APPLY_STYLE', prop, value });
        if (onStyleChange) onStyleChange(target?.path, { [prop]: value });
        setComputed((prev) => ({ ...prev, [prop]: value }));
    };

    const applyStyles = (styles) => {
        postMessage(iframeRefProp, { type: 'MAILPURSE_APPLY_STYLES', styles });
        if (onStyleChange) onStyleChange(target?.path, styles);
        setComputed((prev) => ({ ...prev, ...styles }));
    };

    const toggleStyle = (prop, value) => {
        const current = computed[prop];
        const isActive = current === value;
        applyStyle(prop, isActive ? '' : value);
        setComputed((prev) => ({ ...prev, [prop]: isActive ? '' : value }));
    };

    const isMedia = target?.isMedia;
    const isText = target?.isText;
    const isImage = target?.tag === 'img';
    const canReplaceWithImage = !isImage;
    const isLinkable = target?.tag === 'a' || target?.tag === 'button';
    const currentFontLabel = useMemo(() => getFontLabel(computed.fontFamily), [computed.fontFamily]);

    const saveLink = () => {
        if (!isLinkable || !onLinkChange) return;
        onLinkChange(target?.path, linkUrl.trim(), linkTarget);
        setPopover(null);
    };

    const saveImageUrl = () => {
        if (!imageUrl.trim()) return;
        if (isImage && onImageChange) {
            onImageChange(target?.path, imageUrl.trim());
        } else if (canReplaceWithImage && onReplaceWithImage) {
            onReplaceWithImage(target?.path, imageUrl.trim());
        }
        setPopover(null);
    };

    const saveSvgMarkup = () => {
        const markup = svgMarkup.trim();
        if (!markup || !/^<svg(?:\s|>)/i.test(markup) || !/<\/svg>\s*$/i.test(markup)) {
            window.alert('Please paste valid SVG markup starting with <svg> and ending with </svg>.');
            return;
        }
        if (canReplaceWithImage && onReplaceWithSvg) {
            onReplaceWithSvg(target?.path, markup);
            setPopover(null);
        }
    };

    const uploadImage = async (file) => {
        if (!file || !imageUploadUrl || !onImageChange) return;
        setUploadingImage(true);
        try {
            const body = new FormData();
            body.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
            body.append('image', file);
            const response = await fetch(imageUploadUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body,
            });
            const data = await response.json();
            if (!response.ok || !data?.url) throw new Error('Image upload failed.');
            setImageUrl(data.url);
            if (isImage && onImageChange) {
                onImageChange(target?.path, data.url);
            } else if (canReplaceWithImage && onReplaceWithImage) {
                onReplaceWithImage(target?.path, data.url);
            }
            setPopover(null);
        } catch (error) {
            window.alert(error?.message || 'Image upload failed.');
        } finally {
            setUploadingImage(false);
            if (imageUploadRef.current) imageUploadRef.current.value = '';
        }
    };

    if (!position || !target) return null;

    const filteredFonts = GOOGLE_FONTS.filter((f) => f.toLowerCase().includes(fontSearch.toLowerCase()));

    return (
        <div
            key={target?.path || 'none'}
            ref={toolbarRef}
            className="fixed z-50 flex flex-col items-center"
            style={{ left: position.left, top: position.top + target.rect.height + 8, transform: 'translate(-50%, 0)' }}
        >
            <div className="flex items-center gap-1 rounded-2xl bg-white/95 backdrop-blur-xl border border-gray-200/80 shadow-xl px-2 py-1.5">
                {!isMedia && (
                    <>
                        <InlineColorPicker
                            value={computed.backgroundColor}
                            onChange={(color) => applyStyle('backgroundColor', color)}
                            title="Background color"
                            fallback="#ffffff"
                        />
                        {isText && (
                            <>
                                <InlineColorPicker
                                    value={computed.color}
                                    onChange={(color) => applyStyle('color', color)}
                                    title="Text color"
                                    fallback="#111827"
                                />
                                <div className="relative">
                                    <ToolbarButton
                                        title="Font family"
                                        active={popover === 'font'}
                                        onClick={() => setPopover(popover === 'font' ? null : 'font')}
                                        className="max-w-[130px]"
                                    >
                                        <span className="max-w-[132px] truncate font-semibold" style={{ fontFamily: computed.fontFamily || currentFontLabel }}>
                                            {currentFontLabel}
                                        </span>
                                        <span className="ml-0.5 text-gray-500">{ICON.chevronDown}</span>
                                    </ToolbarButton>
                                    <Popover
                                        title="Google Fonts"
                                        open={popover === 'font'}
                                        onClose={() => setPopover(null)}
                                        style={{ left: 0, top: '100%' }}
                                        className="w-[250px]"
                                    >
                                        <div
                                            className="relative flex h-72 gap-2"
                                            onMouseEnter={() => {
                                                if (hoverTimeoutRef.current) {
                                                    clearTimeout(hoverTimeoutRef.current);
                                                    hoverTimeoutRef.current = null;
                                                }
                                            }}
                                            onMouseLeave={() => {
                                                hoverTimeoutRef.current = setTimeout(() => {
                                                    setHoveredFont(null);
                                                    setHoveredRowTop(null);
                                                }, 150);
                                            }}
                                        >
                                            <div className="w-[225px] flex-shrink-0">
                                                <input
                                                    type="text"
                                                    value={fontSearch}
                                                    onChange={(e) => setFontSearch(e.target.value)}
                                                    placeholder="Search fonts..."
                                                    className="w-full mb-2 px-2 py-1.5 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                                                />
                                                <div ref={listRef} className="overflow-y-auto pr-1 space-y-0.5 h-[244px]">
                                                {filteredFonts.map((font) => (
                                                    <button
                                                        key={font}
                                                        type="button"
                                                        data-font-row
                                                        data-font={font}
                                                        onMouseEnter={(e) => {
                                                            setHoveredFont(font);
                                                            const list = listRef.current;
                                                            if (list) {
                                                                const rowHeight = e.currentTarget.offsetHeight;
                                                                setHoveredRowTop(e.currentTarget.offsetTop - list.scrollTop + list.offsetTop - rowHeight / 2 - 25);
                                                            }
                                                        }}
                                                        className={`group w-full flex items-center gap-2 text-left px-2 py-1.5 rounded-md text-gray-700 font-medium ${hoveredFont === font ? 'bg-blue-50 text-blue-700' : 'hover:bg-gray-100'}`}
                                                        style={{ fontFamily: font, fontSize: '15px' }}
                                                    >
                                                        <span className="truncate flex-1">{font}</span>
                                                        <span className={`text-gray-400 transition-opacity ${hoveredFont === font ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'}`}>
                                                            {ICON.chevronRight}
                                                        </span>
                                                    </button>
                                                ))}
                                                </div>
                                            </div>
                                            {hoveredFont && hoveredRowTop !== null ? (
                                                <div
                                                    className="absolute z-50 w-36 rounded-lg border border-gray-200 bg-white shadow-xl py-1"
                                                    style={{ top: hoveredRowTop, left: '238px' }}
                                                    onMouseEnter={() => {
                                                        if (hoverTimeoutRef.current) {
                                                            clearTimeout(hoverTimeoutRef.current);
                                                            hoverTimeoutRef.current = null;
                                                        }
                                                    }}
                                                >
                                                    {FONT_WEIGHTS.map((w) => {
                                                        const activeFont = hoveredFont || filteredFonts[0] || GOOGLE_FONTS[0];
                                                        return (
                                                            <button
                                                                key={w.value}
                                                                type="button"
                                                                onClick={() => {
                                                                    applyStyles({ fontFamily: activeFont, fontWeight: w.value });
                                                                }}
                                                                className="w-full text-left px-3 py-1.5 text-[15px] hover:bg-gray-100 text-gray-700"
                                                                style={{ fontFamily: activeFont, fontWeight: w.value, fontSize: '15px' }}
                                                            >
                                                                {w.label}
                                                            </button>
                                                        );
                                                    })}
                                                </div>
                                            ) : null}
                                        </div>
                                    </Popover>
                                </div>
                                <div className="flex items-center gap-1 h-8 px-2 rounded-lg border border-gray-200 bg-white" title="Font size">
                                    {ICON.size}
                                    <input
                                        type="number"
                                        defaultValue={computed.fontSize}
                                        onChange={(e) => applyStyle('fontSize', e.target.value + 'px')}
                                        className="w-10 text-xs text-gray-700 outline-none border-0 p-0"
                                        min="8"
                                        max="120"
                                        title="Font size"
                                    />
                                    <span className="text-[10px] text-gray-400">px</span>
                                </div>
                                <div className="relative">
                                    <ToolbarButton
                                        title="Text style"
                                        active={popover === 'style'}
                                        onClick={() => setPopover(popover === 'style' ? null : 'style')}
                                    >
                                        {ICON.style}
                                    </ToolbarButton>
                                    <Popover
                                        title="Text style"
                                        open={popover === 'style'}
                                        onClose={() => setPopover(null)}
                                        style={{ left: '50%', top: '100%', transform: 'translateX(-50%)' }}
                                    >
                                        <div className="text-sm text-gray-500 mb-1">Alignment</div>
                                        <ButtonGroup className="mb-3">
                                            <GroupButton active={computed.textAlign === 'left'} onClick={() => applyStyle('textAlign', 'left')} title="Left">{ICON.alignLeft}</GroupButton>
                                            <GroupButton active={computed.textAlign === 'center'} onClick={() => applyStyle('textAlign', 'center')} title="Center">{ICON.alignCenter}</GroupButton>
                                            <GroupButton active={computed.textAlign === 'right'} onClick={() => applyStyle('textAlign', 'right')} title="Right">{ICON.alignRight}</GroupButton>
                                            <GroupButton active={computed.textAlign === 'justify'} onClick={() => applyStyle('textAlign', 'justify')} title="Justify">{ICON.alignJustify}</GroupButton>
                                        </ButtonGroup>
                                        <div className="text-sm text-gray-500 mb-1">Case</div>
                                        <ButtonGroup className="mb-3">
                                            <GroupButton active={!computed.textTransform || computed.textTransform === 'none'} onClick={() => applyStyle('textTransform', 'none')} title="None"><span className="text-xs">—</span></GroupButton>
                                            <GroupButton active={computed.textTransform === 'uppercase'} onClick={() => applyStyle('textTransform', 'uppercase')} title="Uppercase"><span className="text-xs">AA</span></GroupButton>
                                            <GroupButton active={computed.textTransform === 'lowercase'} onClick={() => applyStyle('textTransform', 'lowercase')} title="Lowercase"><span className="text-xs">aa</span></GroupButton>
                                            <GroupButton active={computed.textTransform === 'capitalize'} onClick={() => applyStyle('textTransform', 'capitalize')} title="Capitalize"><span className="text-xs">Aa</span></GroupButton>
                                        </ButtonGroup>
                                        <div className="text-sm text-gray-500 mb-1">Decoration</div>
                                        <ButtonGroup>
                                            <GroupButton active={!computed.textDecoration || computed.textDecoration === 'none'} onClick={() => applyStyle('textDecoration', 'none')} title="None"><span className="text-xs">—</span></GroupButton>
                                            <GroupButton active={computed.textDecoration === 'underline'} onClick={() => applyStyle('textDecoration', 'underline')} title="Underline"><span className="text-xs underline">U</span></GroupButton>
                                            <GroupButton active={computed.textDecoration === 'line-through'} onClick={() => applyStyle('textDecoration', 'line-through')} title="Line through"><span className="text-xs line-through">S</span></GroupButton>
                                            <GroupButton active={computed.fontStyle === 'italic'} onClick={() => toggleStyle('fontStyle', 'italic')} title="Italic"><span className="text-xs italic">I</span></GroupButton>
                                        </ButtonGroup>
                                    </Popover>
                                </div>
                            </>
                        )}
                        <div className="relative">
                            <ToolbarButton
                                title="Border"
                                active={popover === 'border'}
                                onClick={() => setPopover(popover === 'border' ? null : 'border')}
                            >
                                {ICON.border}
                            </ToolbarButton>
                            <Popover
                                title="Border"
                                open={popover === 'border'}
                                onClose={() => setPopover(null)}
                                style={{ left: 0, top: '100%' }}
                                className="w-64"
                            >
                                <div className="space-y-3">
                                    <div className="grid grid-cols-2 gap-2">
                                        <NumberInput label="Width" value={computed.borderWidth} onChange={(v) => applyStyle('borderWidth', v + 'px')} />
                                        <NumberInput label="Radius" value={computed.borderRadius} onChange={(v) => applyStyle('borderRadius', v + 'px')} />
                                    </div>
                                    <div>
                                        <div className="text-[10px] text-gray-500 mb-1">Style</div>
                                        <div className="grid grid-cols-4 gap-1">
                                            {['solid', 'dashed', 'dotted', 'none'].map((s) => (
                                                <ToolbarButton
                                                    key={s}
                                                    title={s}
                                                    active={computed.borderStyle === s}
                                                    onClick={() => applyStyle('borderStyle', s)}
                                                    className="text-[10px]"
                                                >
                                                    {s === 'none' ? (
                                                        <svg width="20" height="14" viewBox="0 0 20 14" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                                            <rect x="2" y="4" width="16" height="6" rx="1" />
                                                            <line x1="4" y1="11" x2="16" y2="3" />
                                                        </svg>
                                                    ) : (
                                                        <div className="w-8 h-0 border-b-2" style={{ borderBottomStyle: s, borderBottomColor: computed.borderColor || '#111827' }} />
                                                    )}
                                                </ToolbarButton>
                                            ))}
                                        </div>
                                    </div>
                                    <InlineColorPicker
                                        value={computed.borderColor}
                                        onChange={(color) => applyStyle('borderColor', color)}
                                        title="Border color"
                                        fallback="#000000"
                                    />
                                </div>
                            </Popover>
                        </div>
                    </>
                )}

                {isLinkable && (
                    <div className="relative">
                        <ToolbarButton title="Insert link" active={popover === 'link'} onClick={() => setPopover(popover === 'link' ? null : 'link')}>
                            {ICON.link}
                        </ToolbarButton>
                        <Popover title="Link" open={popover === 'link'} onClose={() => setPopover(null)} style={{ left: 0, top: '100%' }} className="w-72">
                            <div className="space-y-3">
                                <input value={linkUrl} onChange={(e) => setLinkUrl(e.target.value)} placeholder="https://example.com or /pricing" className="w-full rounded-lg border border-gray-200 px-2.5 py-2 text-xs text-gray-700 outline-none focus:border-blue-500" />
                                <select value={linkTarget} onChange={(e) => setLinkTarget(e.target.value)} className="w-full rounded-lg border border-gray-200 px-2.5 py-2 text-xs text-gray-700 outline-none focus:border-blue-500">
                                    <option value="_self">Open in same tab</option>
                                    <option value="_blank">Open in new tab</option>
                                </select>
                                <button type="button" onClick={saveLink} className="w-full rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">Save link</button>
                            </div>
                        </Popover>
                    </div>
                )}

                {(isImage || canReplaceWithImage) && (
                    <>
                        {canReplaceWithImage && (
                            <div className="relative">
                                <ToolbarButton title="Replace with image" active={popover === 'replace-image'} onClick={() => setPopover(popover === 'replace-image' ? null : 'replace-image')}>
                                    {ICON.image}
                                </ToolbarButton>
                                <Popover title="Replace with image" open={popover === 'replace-image'} onClose={() => setPopover(null)} style={{ left: 0, top: '100%' }} className="w-72">
                                    <div className="space-y-3">
                                        <p className="text-xs text-gray-500">Replace this logo text or icon with an image.</p>
                                        <input value={imageUrl} onChange={(e) => setImageUrl(e.target.value)} placeholder="Paste image URL" className="w-full rounded-lg border border-gray-200 px-2.5 py-2 text-xs text-gray-700 outline-none focus:border-blue-500" />
                                        <div className="flex gap-2">
                                            <button type="button" onClick={saveImageUrl} className="flex-1 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">Use URL</button>
                                            <button type="button" onClick={() => imageUploadRef.current?.click()} disabled={!imageUploadUrl || uploadingImage} className="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50">{uploadingImage ? 'Uploading…' : 'Upload'}</button>
                                        </div>
                                        <div className="border-t border-gray-100 pt-3">
                                            <label className="mb-1 block text-[11px] font-semibold text-gray-500">Or paste SVG</label>
                                            <textarea value={svgMarkup} onChange={(e) => setSvgMarkup(e.target.value)} placeholder="<svg ...>...</svg>" rows={5} spellCheck="false" className="w-full resize-y rounded-lg border border-gray-200 px-2.5 py-2 font-mono text-[11px] text-gray-700 outline-none focus:border-blue-500" />
                                            <button type="button" onClick={saveSvgMarkup} disabled={!svgMarkup.trim()} className="mt-2 w-full rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-50">Use SVG</button>
                                        </div>
                                        <input ref={imageUploadRef} type="file" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp,image/svg+xml" className="hidden" onChange={(e) => uploadImage(e.target.files?.[0])} />
                                    </div>
                                </Popover>
                            </div>
                        )}
                        {isImage && (
                        <div className="relative">
                            <ToolbarButton title="Change image" active={popover === 'image'} onClick={() => setPopover(popover === 'image' ? null : 'image')}>
                                {ICON.image}
                            </ToolbarButton>
                            <Popover title="Change image" open={popover === 'image'} onClose={() => setPopover(null)} style={{ left: 0, top: '100%' }} className="w-72">
                                <div className="space-y-3">
                                    <input value={imageUrl} onChange={(e) => setImageUrl(e.target.value)} placeholder="Paste image URL" className="w-full rounded-lg border border-gray-200 px-2.5 py-2 text-xs text-gray-700 outline-none focus:border-blue-500" />
                                    <div className="flex gap-2">
                                        <button type="button" onClick={saveImageUrl} className="flex-1 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">Use URL</button>
                                        <button type="button" onClick={() => imageUploadRef.current?.click()} disabled={!imageUploadUrl || uploadingImage} className="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50">{uploadingImage ? 'Uploading…' : 'Upload'}</button>
                                    </div>
                                    <input ref={imageUploadRef} type="file" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp,image/svg+xml" className="hidden" onChange={(e) => uploadImage(e.target.files?.[0])} />
                                </div>
                            </Popover>
                        </div>
                        )}
                        {isImage && (<div className="relative">
                            <ToolbarButton title="Image position" active={popover === 'image-position'} onClick={() => setPopover(popover === 'image-position' ? null : 'image-position')}>
                                {ICON.alignCenter}
                            </ToolbarButton>
                            <Popover title="Image position" open={popover === 'image-position'} onClose={() => setPopover(null)} style={{ left: 0, top: '100%' }} className="w-56">
                                <div className="space-y-3">
                                    <label className="block text-xs font-medium text-gray-600">Fit</label>
                                    <select value={computed.objectFit || 'contain'} onChange={(e) => applyStyle('objectFit', e.target.value)} className="w-full rounded-lg border border-gray-200 px-2.5 py-2 text-xs text-gray-700 outline-none focus:border-blue-500"><option value="contain">Contain</option><option value="cover">Cover</option><option value="fill">Fill</option><option value="none">None</option></select>
                                    <label className="block text-xs font-medium text-gray-600">Position</label>
                                    <select value={computed.objectPosition || 'center'} onChange={(e) => applyStyle('objectPosition', e.target.value)} className="w-full rounded-lg border border-gray-200 px-2.5 py-2 text-xs text-gray-700 outline-none focus:border-blue-500"><option value="left">Left</option><option value="center">Center</option><option value="right">Right</option><option value="top">Top</option><option value="bottom">Bottom</option></select>
                                </div>
                            </Popover>
                        </div>)}
                    </>
                )}

                {isMedia && (
                    <>
                        <div className="flex items-center gap-1 h-8 px-2 rounded-lg border border-gray-200 bg-white" title="Width">
                            {ICON.size}
                            <span className="text-[10px] text-gray-400">W</span>
                            <input
                                type="number"
                                defaultValue={computed.width}
                                onChange={(e) => applyStyle('width', e.target.value + 'px')}
                                className="w-12 text-xs text-gray-700 outline-none border-0 p-0"
                                min="0"
                                title="Width"
                            />
                            <span className="text-[10px] text-gray-400">px</span>
                        </div>
                        <div className="flex items-center gap-1 h-8 px-2 rounded-lg border border-gray-200 bg-white" title="Height">
                            {ICON.size}
                            <span className="text-[10px] text-gray-400">H</span>
                            <input
                                type="number"
                                defaultValue={computed.height}
                                onChange={(e) => applyStyle('height', e.target.value + 'px')}
                                className="w-12 text-xs text-gray-700 outline-none border-0 p-0"
                                min="0"
                                title="Height"
                            />
                            <span className="text-[10px] text-gray-400">px</span>
                        </div>
                        <div className="flex items-center gap-1 h-8 px-2 rounded-lg border border-gray-200 bg-white" title="Border radius">
                            {ICON.radius}
                            <input
                                type="number"
                                defaultValue={computed.borderRadius}
                                onChange={(e) => applyStyle('borderRadius', e.target.value + 'px')}
                                className="w-12 text-xs text-gray-700 outline-none border-0 p-0"
                                min="0"
                                title="Border radius"
                            />
                            <span className="text-[10px] text-gray-400">px</span>
                        </div>
                    </>
                )}

                <div className="relative">
                    <ToolbarButton
                        title="Opacity"
                        active={popover === 'opacity'}
                        onClick={() => setPopover(popover === 'opacity' ? null : 'opacity')}
                    >
                        {ICON.opacity}
                    </ToolbarButton>
                    <Popover
                        title="Opacity"
                        open={popover === 'opacity'}
                        onClose={() => setPopover(null)}
                        style={{ right: 0, top: '100%' }}
                    >
                        <div className="flex items-center gap-3">
                            <input
                                type="range"
                                min="0"
                                max="1"
                                step="0.05"
                                defaultValue={computed.opacity}
                                onChange={(e) => applyStyle('opacity', e.target.value)}
                                className="flex-1 h-1 bg-gray-200 rounded-lg appearance-none cursor-pointer mailpurse-opacity-slider"
                            />
                            <span className="text-xs text-gray-700 w-10 text-right">{Math.round((computed.opacity || 1) * 100)}%</span>
                        </div>
                    </Popover>
                </div>

                <div className="relative">
                    <ToolbarButton
                        title="Spacing"
                        active={popover === 'spacing'}
                        onClick={() => setPopover(popover === 'spacing' ? null : 'spacing')}
                    >
                        {ICON.spacing}
                    </ToolbarButton>
                    <Popover
                        title="Spacing"
                        open={popover === 'spacing'}
                        onClose={() => setPopover(null)}
                        style={{ right: 0, top: '100%' }}
                        className="w-72"
                    >
                        <div className="space-y-3">
                            <div className="space-y-1.5">
                                <div className="text-[10px] text-gray-500">Margin</div>
                                <div className="grid grid-cols-2 gap-2">
                                    <NumberInput label="Top" value={computed.marginTop} onChange={(v) => applyStyle('marginTop', v + 'px')} />
                                    <NumberInput label="Bottom" value={computed.marginBottom} onChange={(v) => applyStyle('marginBottom', v + 'px')} />
                                    <NumberInput label="Left" value={computed.marginLeft} onChange={(v) => applyStyle('marginLeft', v + 'px')} />
                                    <NumberInput label="Right" value={computed.marginRight} onChange={(v) => applyStyle('marginRight', v + 'px')} />
                                </div>
                            </div>
                            <div className="space-y-1.5">
                                <div className="text-[10px] text-gray-500">Padding</div>
                                <div className="grid grid-cols-2 gap-2">
                                    <NumberInput label="Top" value={computed.paddingTop} onChange={(v) => applyStyle('paddingTop', v + 'px')} />
                                    <NumberInput label="Bottom" value={computed.paddingBottom} onChange={(v) => applyStyle('paddingBottom', v + 'px')} />
                                    <NumberInput label="Left" value={computed.paddingLeft} onChange={(v) => applyStyle('paddingLeft', v + 'px')} />
                                    <NumberInput label="Right" value={computed.paddingRight} onChange={(v) => applyStyle('paddingRight', v + 'px')} />
                                </div>
                            </div>
                        </div>
                    </Popover>
                </div>

                <div className="w-px h-6 bg-gray-200 mx-1" />

                <ToolbarButton title="Open in code" onClick={onCode}>
                    {ICON.code}
                </ToolbarButton>
                <ToolbarButton title="Delete element" onClick={onDelete} className="text-red-600 hover:bg-red-50 hover:border-red-200">
                    {ICON.trash}
                </ToolbarButton>
                <ToolbarButton title="Close" onClick={onClose}>
                    {ICON.close}
                </ToolbarButton>
            </div>
        </div>
    );
}

function NumberInput({ label, value, onChange }) {
    return (
        <div className="flex items-center gap-1 h-7 px-1.5 rounded-md border border-gray-200 bg-white">
            <span className="text-[10px] text-gray-400">{label}</span>
            <input
                type="number"
                defaultValue={value}
                onChange={(e) => onChange(parseFloat(e.target.value) || 0)}
                className="w-10 text-xs text-gray-700 outline-none border-0 p-0"
                min="0"
            />
            <span className="text-[10px] text-gray-400">px</span>
        </div>
    );
}
