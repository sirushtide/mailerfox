import React, { useContext, useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { Input, Range, Select } from '../ui/primitives.jsx';
import { pxNumber } from '../lib/typographyUtils.js';
import { BuilderViewportContext } from '../controls/builderMode.js';

const GENERIC_FONT_FAMILIES = new Set([
    'serif',
    'sans-serif',
    'monospace',
    'cursive',
    'fantasy',
    'system-ui',
    '-apple-system',
    'ui-sans-serif',
    'ui-serif',
    'ui-monospace',
    'emoji',
    'math',
    'fangsong',
]);

export function extractPrimaryFontFamily(fontFamily) {
    if (!fontFamily || typeof fontFamily !== 'string') return '';

    const raw = String(fontFamily).trim();
    if (!raw) return '';

    const quoted = raw.match(/["']([^"']+)["']/);
    const candidate = quoted ? quoted[1] : raw.split(',')[0].trim();
    const normalized = candidate.replace(/^['"]+|['"]+$/g, '').trim();
    if (!normalized) return '';
    if (GENERIC_FONT_FAMILIES.has(normalized.toLowerCase())) return '';

    return normalized;
}

export function loadGoogleFont(fontFamily) {
    if (!fontFamily || typeof fontFamily !== 'string') return;
    if (typeof document === 'undefined') return;

    const fontName = extractPrimaryFontFamily(fontFamily);
    if (!fontName) return;

    const linkId = `google-font-${fontName.toLowerCase().replace(/\s+/g, '-')}`;
    if (document.getElementById(linkId)) return;

    const link = document.createElement('link');
    link.id = linkId;
    link.rel = 'stylesheet';
    link.href = `https://fonts.googleapis.com/css2?family=${encodeURIComponent(fontName).replace(/%20/g, '+')}:wght@100;200;300;400;500;600;700;800;900&display=swap`;
    document.head.appendChild(link);
}

async function fetchAllGoogleFonts() {
    if (typeof window !== 'undefined' && window.__cachedGoogleFonts && Array.isArray(window.__cachedGoogleFonts)) {
        return window.__cachedGoogleFonts;
    }

    const popularFonts = [
        'Roboto','Open Sans','Lato', 'Google Sans','Bricolage Grotesque','Montserrat','Oswald','Source Sans Pro','Raleway','PT Sans','Roboto Condensed','Ubuntu','Poppins','Nunito','Mukta','Rubik','Work Sans','Noto Sans','Fira Sans','Oxygen','Cabin','Dosis','Quicksand','Karla','Arimo','Josefin Sans','Anton','Bebas Neue','Righteous','Archivo','Barlow','Heebo','Titillium Web','Hind','Exo 2','Varela Round','Assistant','Muli','Libre Franklin','Asap','Catamaran','Signika','Fjalla One','Teko','Yanone Kaffeesatz','Kanit','Space Grotesk','DM Sans','Manrope','Inter','Plus Jakarta Sans','Outfit','Lexend','Urbanist','Commissioner','Jost',
        'Merriweather','Playfair Display','Crimson Text','Bitter','Libre Baskerville','Slabo 27px','Noto Serif','Zilla Slab','Bree Serif','Crete Round','Cinzel','Cormorant Garamond','EB Garamond','Vollkorn','Alegreya','Spectral','Cardo','Lora','PT Serif','Source Serif Pro','Old Standard TT','Neuton','Domine','Arvo','Bodoni Moda','IBM Plex Serif','Roboto Slab','Mate','Tinos','Gentium Basic',
        'Pacifico','Dancing Script','Lobster','Great Vibes','Sacramento','Satisfy','Yellowtail','Kaushan Script','Courgette','Allura','Alex Brush','Pinyon Script','Tangerine','Cookie','Playball','Damion','Rancho','Arizonia','Parisienne','Italianno','Rochester','Mr De Haviland','Bad Script','Handlee','Patrick Hand','Indie Flower',
        'Inconsolata','Source Code Pro','Fira Code','Space Mono','JetBrains Mono','Courier Prime','IBM Plex Mono','Roboto Mono','Ubuntu Mono','Anonymous Pro','Overpass Mono','Nanum Gothic Coding','VT323','Share Tech Mono','Cutive Mono','Cousine',
        'Amatic SC','Shadows Into Light','Permanent Marker','Creepster','Bangers','Fredoka One','Luckiest Guy','Press Start 2P','Righteous','Bowlby One SC','Chewy','Comfortaa','Changa','Sora','Prompt','Kumbh Sans','DM Serif Display','DM Serif Text','Abril Fatface','Alfa Slab One','Bebas Neue','Archivo Black','Black Ops One','Cinzel Decorative','Cormorant','Coustard','Fugaz One','Gloock','Inria Serif','M PLUS Rounded 1c','Nanum Gothic','Noto Sans JP','Noto Sans KR','Noto Sans TC','Noto Serif JP','Noto Serif KR','Noto Serif TC','PT Sans Caption','PT Sans Narrow','PT Serif Caption','Palanquin','Rajdhani','Roboto Flex','Source Sans 3','Source Serif 4','Tajawal','Taviraj','Pridi','Maitree','Athiti','Bai Jamjuree','Pliant','DM Sans'
    ];

    if (typeof window !== 'undefined') {
        window.__cachedGoogleFonts = popularFonts;
    }
    return popularFonts;
}

function SearchableSelect({ value, onChange, options, placeholder = 'Search...' }) {
    const [isOpen, setIsOpen] = useState(false);
    const [search, setSearch] = useState('');
    const containerRef = useRef(null);
    const inputRef = useRef(null);

    const filtered = useMemo(() => {
        if (!search.trim()) return options;
        const q = search.toLowerCase();
        return options.filter((o) => o.label.toLowerCase().includes(q));
    }, [options, search]);

    const selectedLabel = useMemo(() => {
        const found = options.find((o) => o.value === value);
        return found ? found.label : '';
    }, [options, value]);

    useEffect(() => {
        const handleClickOutside = (e) => {
            if (containerRef.current && !containerRef.current.contains(e.target)) {
                setIsOpen(false);
                setSearch('');
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    useEffect(() => {
        if (isOpen && inputRef.current) {
            inputRef.current.focus();
        }
    }, [isOpen]);

    return (
        <div ref={containerRef} className="relative">
            <button
                type="button"
                onClick={() => setIsOpen(!isOpen)}
                className="mt-1 w-full flex items-center justify-between rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 shadow-sm px-3 py-2 text-left"
            >
                <span className={selectedLabel ? '' : 'text-gray-400'}>{selectedLabel || 'Select...'}</span>
                <svg className="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {isOpen ? (
                <div className="absolute z-50 mt-1 w-full rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-lg max-h-80 overflow-hidden">
                    <div className="p-2 border-b border-gray-200 dark:border-gray-700">
                        <input
                            ref={inputRef}
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder={placeholder}
                            className="w-full px-3 py-2 text-sm rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 outline-none"
                        />
                    </div>
                    <div className="overflow-y-auto max-h-64">
                        {filtered.length === 0 ? (
                            <div className="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">No results found</div>
                        ) : (
                            filtered.map((o) => (
                                <button
                                    key={o.value}
                                    type="button"
                                    onClick={() => {
                                        onChange(o.value);
                                        setIsOpen(false);
                                        setSearch('');
                                    }}
                                    className={`w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-800 ${
                                        o.value === value ? 'bg-gray-100 dark:bg-gray-800' : 'text-gray-700 dark:text-gray-200'
                                    }`}
                                    style={o.value ? { fontFamily: o.value } : {}}
                                >
                                    {o.label}
                                </button>
                            ))
                        )}
                    </div>
                </div>
            ) : null}
        </div>
    );
}

export function TypographyPopover({ value, onChange, withAlign = true }) {
    const [open, setOpen] = useState(false);
    const triggerRef = useRef(null);
    const panelRef = useRef(null);
    const [panelStyle, setPanelStyle] = useState(() => ({ top: 0, left: 0, width: 320, maxHeight: 520, placement: 'bottom' }));

    const arrowClassName =
        panelStyle.placement === 'bottom'
            ? 'absolute right-3 h-4 w-4 rotate-45 border-l border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900'
            : 'absolute right-3 h-4 w-4 rotate-45 border-r border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900';

    useEffect(() => {
        if (!open) return;

        const updatePosition = () => {
            const el = triggerRef.current;
            if (!el) return;

            const rail = el.closest('[data-mp-builder-rail="1"]');
            const railRect = rail ? rail.getBoundingClientRect() : null;
            const boundsLeft = railRect ? railRect.left : 8;
            const boundsRight = railRect ? railRect.right : window.innerWidth - 8;
            const boundsWidth = Math.max(0, boundsRight - boundsLeft);

            const rect = el.getBoundingClientRect();
            const gap = 8;
            const desiredWidth = 420;
            const maxWidth = Math.min(window.innerWidth - 16, boundsWidth - 16);
            const width = Math.max(220, Math.min(desiredWidth, Math.max(220, maxWidth)));

            let left = rect.right - width;
            left = Math.max(boundsLeft + 8, Math.min(left, boundsRight - width - 8));

            const belowTop = rect.bottom + gap;
            const spaceBelow = window.innerHeight - belowTop - 8;
            const spaceAbove = rect.top - 8;

            const placeBelow = spaceBelow >= 260 || spaceBelow >= spaceAbove;
            const top = placeBelow ? belowTop : Math.max(8, rect.top - gap - Math.min(520, Math.max(180, spaceAbove - 8)));
            const maxHeight = Math.max(180, Math.min(560, placeBelow ? spaceBelow : (rect.top - 8)));

            setPanelStyle({ top, left, width, maxHeight, placement: placeBelow ? 'bottom' : 'top' });
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
            const t = e.target;
            if (panelRef.current && panelRef.current.contains(t)) return;
            if (triggerRef.current && triggerRef.current.contains(t)) return;
            setOpen(false);
        };

        const onKeyDown = (e) => {
            if (e.key === 'Escape') setOpen(false);
        };

        document.addEventListener('mousedown', onPointerDown);
        document.addEventListener('touchstart', onPointerDown);
        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('touchstart', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [open]);

    return (
        <div className="">
            <div className="flex items-center justify-between">
                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Typography</div>
                <div className="relative">
                    <button
                        ref={triggerRef}
                        type="button"
                        onClick={() => setOpen((v) => !v)}
                        className="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800"
                        title="Edit typography"
                    >
                        <span aria-hidden>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil-icon lucide-pencil"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                        </span>
                    </button>

                    {open
                        ? createPortal(
                              <div
                                  ref={panelRef}
                                  className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-xl p-3"
                                  style={{
                                      position: 'fixed',
                                      top: panelStyle.top,
                                      left: panelStyle.left,
                                      width: panelStyle.width,
                                      maxHeight: panelStyle.maxHeight,
                                      overflowY: 'auto',
                                      overscrollBehavior: 'contain',
                                      zIndex: 9999,
                                  }}
                              >
                                  <div
                                      className={arrowClassName}
                                      style={{
                                          zIndex: 10000,
                                          top: panelStyle.placement === 'bottom' ? -8 : 'auto',
                                          bottom: panelStyle.placement === 'top' ? -8 : 'auto',
                                      }}
                                  />
                                  <TypographyControl value={value} onChange={onChange} withAlign={withAlign} />
                              </div>,
                              document.body
                          )
                        : null}
                </div>
            </div>
        </div>
    );
}

export function TypographyControl({ value, onChange, withAlign = true }) {
    const v = value || {};

    const [allGoogleFonts, setAllGoogleFonts] = useState([]);
    const viewportCtx = useContext(BuilderViewportContext);
    const ctxViewport = viewportCtx && typeof viewportCtx.viewport === 'string' ? viewportCtx.viewport : null;
    const [device, setDevice] = useState(ctxViewport || 'desktop');

    useEffect(() => {
        if (ctxViewport && ctxViewport !== device) {
            setDevice(ctxViewport);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [ctxViewport]);

    const chooseDevice = (id) => {
        setDevice(id);
        if (viewportCtx && typeof viewportCtx.setViewport === 'function') {
            viewportCtx.setViewport(id);
        }
    };

    const keyFor = (base) => (device === 'desktop' ? base : `${base}${device === 'tablet' ? 'Tablet' : 'Mobile'}`);

    const set = (patch) => {
        onChange({ ...v, ...patch });
    };

    useEffect(() => {
        const loadFonts = async () => {
            const fonts = await fetchAllGoogleFonts();
            setAllGoogleFonts(fonts);
        };
        loadFonts();
    }, []);

    const families = useMemo(() => {
        const systemFonts = [
            { value: '', label: 'Default' },
            { value: 'Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif', label: 'Inter' },
            { value: 'Roboto, system-ui, -apple-system, Segoe UI, Helvetica, Arial, sans-serif', label: 'Roboto' },
            { value: 'Arial, Helvetica, sans-serif', label: 'Arial' },
            { value: 'Georgia, serif', label: 'Georgia' },
            { value: 'Times New Roman, Times, serif', label: 'Times New Roman' },
            { value: 'Courier New, Courier, monospace', label: 'Courier New' },
        ];

        const googleFontOptions = (Array.isArray(allGoogleFonts) ? allGoogleFonts : []).map((font) => ({
            value: `'${font}', sans-serif`,
            label: font,
        }));

        return [...systemFonts, ...googleFontOptions];
    }, [allGoogleFonts]);

    useEffect(() => {
        if (v.fontFamily) {
            loadGoogleFont(v.fontFamily);
        }
    }, [v.fontFamily]);

    const rangeRow = ({ label, key, min, max, step = 1, unit = 'px' }) => {
        const normalized = pxNumber(v[key]);
        const displayValue = normalized === null ? '' : String(normalized);
        const sliderValue = normalized === null ? min : Math.min(Math.max(normalized, min), max);

        return (
            <div>
                <div className="flex items-center justify-between mb-2">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{label}</label>
                    <div className="flex items-center gap-2">
                        <input
                            type="number"
                            min={min}
                            max={max}
                            step={step}
                            value={displayValue}
                            onChange={(e) => {
                                const raw = e.target.value;
                                if (raw === '') {
                                    set({ [key]: undefined });
                                    return;
                                }
                                const n = Number(raw);
                                if (!Number.isFinite(n)) return;
                                set({ [key]: n });
                            }}
                            className="w-20 px-2 py-1 text-sm rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 outline-none"
                            placeholder={String(min)}
                        />
                        <span className="text-xs text-gray-500 dark:text-gray-400 min-w-[20px]">{unit}</span>
                    </div>
                </div>
                <Range
                    min={min}
                    max={max}
                    step={step}
                    value={String(sliderValue)}
                    onChange={(e) => {
                        const n = Number(e.target.value);
                        set({ [key]: Number.isFinite(n) ? n : undefined });
                    }}
                    className="w-full"
                />
            </div>
        );
    };

    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between">
                <span className="text-xs font-medium text-gray-700 dark:text-gray-300">Typography</span>
                <div className="inline-flex items-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-0.5">
                    {[
                        { id: 'desktop', label: 'Desktop', icon: (
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>
                        ) },
                        { id: 'tablet', label: 'Tablet', icon: (
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="16" height="20" x="4" y="2" rx="2"/><line x1="12" x2="12.01" y1="18" y2="18"/></svg>
                        ) },
                        { id: 'mobile', label: 'Mobile', icon: (
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="12" height="20" x="6" y="2" rx="2"/><line x1="12" x2="12.01" y1="18" y2="18"/></svg>
                        ) },
                    ].map((d) => (
                        <button
                            key={d.id}
                            type="button"
                            title={d.label}
                            onClick={() => chooseDevice(d.id)}
                            className={`inline-flex h-6 w-7 items-center justify-center rounded-md transition-colors ${
                                device === d.id
                                    ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-300'
                                    : 'text-gray-500 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-800'
                            }`}
                        >
                            {d.icon}
                        </button>
                    ))}
                </div>
            </div>

            <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Font</label>
                <SearchableSelect value={v.fontFamily || ''} onChange={(next) => set({ fontFamily: next })} options={families} placeholder="Search fonts..." />
            </div>

            {rangeRow({ label: `Font Size${device === 'desktop' ? '' : ` (${device})`}`, key: keyFor('fontSize'), min: 8, max: 96, step: 1, unit: 'px' })}

            <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Weight</label>
                <Select
                    value={v.fontWeight || ''}
                    onChange={(next) => set({ fontWeight: next })}
                    options={[
                        { value: '', label: 'Default' },
                        { value: '100', label: '100' },
                        { value: '200', label: '200' },
                        { value: '300', label: '300' },
                        { value: '400', label: '400' },
                        { value: '500', label: '500' },
                        { value: '600', label: '600' },
                        { value: '700', label: '700' },
                        { value: '800', label: '800' },
                        { value: '900', label: '900' },
                    ]}
                />
            </div>

            {withAlign ? (
                <div>
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Align{device === 'desktop' ? '' : ` (${device})`}</label>
                    <Select
                        value={v[keyFor('align')] || 'left'}
                        onChange={(next) => set({ [keyFor('align')]: next })}
                        options={[
                            { value: 'left', label: 'Left' },
                            { value: 'center', label: 'Center' },
                            { value: 'right', label: 'Right' },
                            { value: 'justify', label: 'Justify' },
                        ]}
                    />
                </div>
            ) : null}

            {rangeRow({ label: `Line Height${device === 'desktop' ? '' : ` (${device})`}`, key: keyFor('lineHeight'), min: 8, max: 120, step: 1, unit: 'px' })}
            {rangeRow({ label: 'Letter Spacing', key: 'letterSpacing', min: -5, max: 20, step: 0.5, unit: 'px' })}

            <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Word Spacing</label>
                <Input
                    type="number"
                    value={v.wordSpacing === undefined ? '' : String(v.wordSpacing)}
                    onChange={(raw) => {
                        if (raw === '') return set({ wordSpacing: undefined });
                        const n = Number(raw);
                        if (!Number.isFinite(n)) return;
                        set({ wordSpacing: Math.max(-5, Math.min(40, n)) });
                    }}
                />
            </div>
        </div>
    );
}
