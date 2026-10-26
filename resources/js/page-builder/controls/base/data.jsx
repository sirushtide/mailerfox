import React, { useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import {
    AlignHorizontalJustifyStart,
    AlignHorizontalJustifyCenter,
    AlignHorizontalJustifyEnd,
    AlignHorizontalSpaceBetween,
    AlignHorizontalSpaceAround,
    AlignHorizontalDistributeCenter,
    AlignVerticalJustifyStart,
    AlignVerticalJustifyCenter,
    AlignVerticalJustifyEnd,
    AlignLeft,
    AlignCenter,
    AlignRight,
    AlignJustify,
    Baseline,
    ArrowUpDown,
} from 'lucide-react';
import { ColorInput } from '../../ui/color.jsx';
import { Button, Input, Select } from '../../ui/primitives.jsx';
import { Switcher } from '../Switcher.jsx';
import { resolveColorToken, isGlobalToken, getValueByPath } from '../bind.js';
import { ImageUrlUploadControl } from '../../reusable/imageUploadControl.jsx';

function ControlLabel({ label, htmlFor }) {
    return (
        <label htmlFor={htmlFor} className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
            {label}
        </label>
    );
}

export function TextControl({ label, value, onChange, placeholder }) {
    return (
        <div>
            <ControlLabel label={label} />
            <Input value={value || ''} onChange={onChange} placeholder={placeholder} />
        </div>
    );
}

export function TextareaControl({ label, value, onChange, placeholder, rows = 3 }) {
    return (
        <div>
            <ControlLabel label={label} />
            <textarea
                value={value || ''}
                onChange={(e) => onChange(e.target.value)}
                rows={rows}
                placeholder={placeholder}
                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
            />
        </div>
    );
}

export function NumberControl({ label, value, onChange, min, max, step }) {
    return (
        <div>
            <ControlLabel label={label} />
            <input
                type="number"
                value={value === undefined || value === null ? '' : value}
                min={min}
                max={max}
                step={step}
                onChange={(e) => onChange(e.target.value === '' ? '' : Number(e.target.value))}
                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-3 py-2 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
            />
        </div>
    );
}

export function UrlControl({ label, value, onChange, placeholder }) {
    return (
        <div>
            <ControlLabel label={label} />
            <Input type="url" value={value || ''} onChange={onChange} placeholder={placeholder} />
        </div>
    );
}

export function HiddenControl({ value, onChange }) {
    return <input type="hidden" value={value || ''} onChange={(e) => onChange(e.target.value)} />;
}

export function SelectControl({ label, value, onChange, options = [] }) {
    return (
        <div>
            <ControlLabel label={label} />
            <Select value={value || ''} onChange={onChange} options={options} />
        </div>
    );
}

const chooseIconMap = {
    'justify-start': <AlignHorizontalJustifyStart size={16} />,
    'justify-center': <AlignHorizontalJustifyCenter size={16} />,
    'justify-end': <AlignHorizontalJustifyEnd size={16} />,
    'justify-space-between': <AlignHorizontalSpaceBetween size={16} />,
    'justify-space-around': <AlignHorizontalSpaceAround size={16} />,
    'justify-space-evenly': <AlignHorizontalDistributeCenter size={16} />,
    'align-stretch': <ArrowUpDown size={16} />,
    'align-start': <AlignVerticalJustifyStart size={16} />,
    'align-center': <AlignVerticalJustifyCenter size={16} />,
    'align-end': <AlignVerticalJustifyEnd size={16} />,
    'align-baseline': <Baseline size={16} />,
    'text-left': <AlignLeft size={16} />,
    'text-center': <AlignCenter size={16} />,
    'text-right': <AlignRight size={16} />,
    'text-justify': <AlignJustify size={16} />,
};

function ChooseOption({ option, active, isLast, onClick }) {
    const [showTooltip, setShowTooltip] = useState(false);
    const [tooltipStyle, setTooltipStyle] = useState({});
    const buttonRef = useRef(null);
    const icon = typeof option.icon === 'string' ? chooseIconMap[option.icon] : option.icon;
    const tooltip = option.tooltip || option.label;

    const updateTooltipPosition = () => {
        if (!buttonRef.current) return;
        const rect = buttonRef.current.getBoundingClientRect();
        setTooltipStyle({
            position: 'fixed',
            left: rect.left + rect.width / 2,
            top: rect.top - 8,
            transform: 'translate(-50%, -100%)',
        });
    };

    const handleEnter = () => {
        updateTooltipPosition();
        setShowTooltip(true);
    };

    const handleLeave = () => setShowTooltip(false);

    return (
        <button
            ref={buttonRef}
            type="button"
            onClick={() => onClick(option.value)}
            onMouseEnter={handleEnter}
            onMouseLeave={handleLeave}
            onFocus={handleEnter}
            onBlur={handleLeave}
            className={`relative flex-1 h-9 flex items-center justify-center text-sm transition-colors ${
                isLast ? '' : 'border-r border-gray-200 dark:border-gray-700'
            } ${
                active
                    ? 'bg-gray-100 text-gray-900 dark:bg-white/10 dark:text-white'
                    : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5'
            }`}
            aria-label={tooltip}
        >
            {icon || option.label}
            {showTooltip && tooltip && createPortal(
                <span className="pointer-events-none z-[9999] whitespace-nowrap rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-2 py-1 text-xs font-medium text-gray-700 dark:text-gray-200 shadow-lg" style={tooltipStyle}>
                    {tooltip}
                </span>,
                document.body
            )}
        </button>
    );
}

export function ChooseControl({ label, value, onChange, options = [] }) {
    return (
        <div>
            {label ? <ControlLabel label={label} /> : null}
            <div className="flex items-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
                {options.map((option, idx, arr) => (
                    <ChooseOption
                        key={option.value}
                        option={option}
                        active={value === option.value}
                        isLast={idx === arr.length - 1}
                        onClick={onChange}
                    />
                ))}
            </div>
        </div>
    );
}

export function SwitcherControl({ label, value, onChange }) {
    return <Switcher label={label} value={value} onChange={onChange} />;
}

export function ColorControl({ label, value, onChange }) {
    return (
        <div className="flex items-center justify-between">
            <ControlLabel label={label} />
            <ColorInput value={value || ''} onChange={onChange} />
        </div>
    );
}

export function GlobalColorControl({ label, value, onChange, globals }) {
    const colorMap = useMemo(() => {
        const list = globals?.colors || [];
        return Array.isArray(list) ? list.reduce((acc, c) => { acc[c.id] = c; return acc; }, {}) : list;
    }, [globals]);

    const isGlobal = isGlobalToken(value);
    const selectedGlobalId = isGlobal ? value.global : null;
    const resolved = isGlobal ? resolveColorToken(value, globals) : value;
    const displayColor = typeof resolved === 'string' ? resolved : '';

    const globalTriggerRef = useRef(null);
    const globalPanelRef = useRef(null);
    const [globalOpen, setGlobalOpen] = useState(false);
    const [globalPanelStyle, setGlobalPanelStyle] = useState({ top: 0, left: 0, width: 280 });

    useEffect(() => {
        if (!globalOpen) return;

        const updatePosition = () => {
            const el = globalTriggerRef.current;
            if (!el) return;

            const rail = el.closest('[data-mp-builder-rail="1"]');
            const railRect = rail ? rail.getBoundingClientRect() : null;
            const boundsLeft = railRect ? railRect.left : 8;
            const boundsRight = railRect ? railRect.right : window.innerWidth - 8;
            const boundsWidth = Math.max(0, boundsRight - boundsLeft);

            const rect = el.getBoundingClientRect();
            const gap = 8;
            const desiredWidth = 280;
            const maxWidth = Math.min(window.innerWidth - 16, boundsWidth - 16);
            const width = Math.max(220, Math.min(desiredWidth, Math.max(220, maxWidth)));

            let left = rect.right - width;
            left = Math.max(boundsLeft + 8, Math.min(left, boundsRight - width - 8));

            const belowTop = rect.bottom + gap;
            const spaceBelow = window.innerHeight - belowTop - 8;
            const spaceAbove = rect.top - 8;
            const placeBelow = spaceBelow >= 180 || spaceBelow >= spaceAbove;
            const top = placeBelow ? belowTop : Math.max(8, rect.top - gap - 180);
            const maxHeight = Math.max(180, Math.min(320, placeBelow ? spaceBelow : rect.top - 8));

            setGlobalPanelStyle({ top, left, width, maxHeight, placement: placeBelow ? 'bottom' : 'top' });
        };

        updatePosition();
        window.addEventListener('resize', updatePosition);
        window.addEventListener('scroll', updatePosition, true);

        const onPointerDown = (e) => {
            const t = e.target;
            if (globalPanelRef.current && globalPanelRef.current.contains(t)) return;
            if (globalTriggerRef.current && globalTriggerRef.current.contains(t)) return;
            setGlobalOpen(false);
        };

        const onKeyDown = (e) => {
            if (e.key === 'Escape') setGlobalOpen(false);
        };

        document.addEventListener('mousedown', onPointerDown);
        document.addEventListener('touchstart', onPointerDown);
        document.addEventListener('keydown', onKeyDown);

        return () => {
            window.removeEventListener('resize', updatePosition);
            window.removeEventListener('scroll', updatePosition, true);
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('touchstart', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [globalOpen]);

    return (
        <div className="flex items-center justify-between">
            <ControlLabel label={label} />
            <div className="inline-flex items-center rounded-md border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 p-0.5">
                <button
                    ref={globalTriggerRef}
                    type="button"
                    onClick={() => setGlobalOpen((v) => !v)}
                    className={`p-1.5 rounded ${isGlobal ? 'bg-white dark:bg-gray-600 text-blue-600 dark:text-blue-300 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'}`}
                    title="Global colors"
                    aria-label="Global colors"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" x2="22" y1="12" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                </button>
                <div className="w-px h-5 bg-gray-300 dark:bg-gray-600 mx-0.5" />
                <ColorInput
                    value={displayColor}
                    onChange={(next) => onChange(next || '')}
                    variant="minimal"
                />
            </div>

            {globalOpen
                ? createPortal(
                      <div
                          ref={globalPanelRef}
                          className="bg-white dark:bg-gray-900 border dark:border-gray-700 shadow-xl rounded-lg overflow-hidden"
                          style={{
                              position: 'fixed',
                              top: globalPanelStyle.top,
                              left: globalPanelStyle.left,
                              width: globalPanelStyle.width,
                              maxHeight: globalPanelStyle.maxHeight,
                              overflowY: 'auto',
                              overscrollBehavior: 'contain',
                              zIndex: 9999,
                          }}
                      >
                          <div className="flex items-center justify-between p-3 border-b border-gray-200 dark:border-gray-700">
                              <div className="flex items-center gap-2">
                                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-400 dark:text-gray-500"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                  <span className="font-medium text-sm text-gray-800 dark:text-gray-100">Global Colors</span>
                              </div>
                              <button
                                  type="button"
                                  className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                  title="Manage global colors"
                                  aria-label="Manage global colors"
                              >
                                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" stroke-linejoin="round"><path d="M12 20a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z"/><path d="M12 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/><path d="M12 2v2"/><path d="M12 22v-2"/><path d="M20 12h2"/><path d="M2 12h2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="m4.93 19.07 1.41-1.41"/><path d="m17.66 6.34 1.41-1.41"/></svg>
                              </button>
                          </div>
                          <div className="p-2">
                              {Object.values(colorMap).length === 0 ? (
                                  <div className="px-3 py-4 text-sm text-gray-500 dark:text-gray-400 text-center">No global colors defined</div>
                              ) : (
                                  Object.values(colorMap).map((c) => (
                                      <button
                                          key={c.id}
                                          type="button"
                                          onClick={() => {
                                              onChange({ global: c.id });
                                              setGlobalOpen(false);
                                          }}
                                          className={`w-full flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 dark:hover:bg-gray-800 ${selectedGlobalId === c.id ? 'bg-blue-50 dark:bg-blue-900/20' : ''}`}
                                      >
                                          <div
                                              className="w-6 h-6 rounded border border-gray-200 dark:border-gray-600 flex-shrink-0"
                                              style={{ backgroundColor: c.value }}
                                          />
                                          <span className="flex-1 text-left text-sm text-gray-700 dark:text-gray-200">{c.label}</span>
                                          <span className="text-xs text-gray-400 dark:text-gray-500 font-mono">{c.value}</span>
                                      </button>
                                  ))
                              )}
                          </div>
                      </div>,
                      document.body
                  )
                : null}
        </div>
    );
}

export function GlobalFontControl({ label, value, onChange, globals }) {
    const fontMap = useMemo(() => {
        const list = globals?.typography || [];
        return Array.isArray(list) ? list.reduce((acc, t) => { acc[t.id] = t; return acc; }, {}) : list;
    }, [globals]);

    const options = [
        { value: '__custom__', label: 'Custom' },
        ...(Object.values(fontMap).map((t) => ({ value: t.id, label: t.label })) || []),
    ];

    const selected = value && typeof value === 'object' && value.global ? value.global : '__custom__';

    return (
        <div className="flex items-center justify-between">
            <ControlLabel label={label} />
            <select
                value={selected}
                onChange={(e) => {
                    const id = e.target.value;
                    if (id === '__custom__') {
                        onChange(undefined);
                        return;
                    }
                    onChange({ global: id });
                }}
                className="w-40 rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-2 py-1 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
            >
                {options.map((o) => (
                    <option key={o.value} value={o.value}>
                        {o.label}
                    </option>
                ))}
            </select>
        </div>
    );
}

export function MediaControl({ label, value, onChange, uploadUrl }) {
    return (
        <div>
            <ControlLabel label={label} />
            <ImageUrlUploadControl
                value={typeof value === 'string' ? value : (value?.url || '')}
                onChange={onChange}
                uploadUrl={uploadUrl}
            />
        </div>
    );
}

const MEDIA_SOURCE_TABS = [
    { value: 'upload', label: 'Upload' },
    { value: 'svg', label: 'SVG' },
    { value: 'url', label: 'URL' },
];

export function MediaSourcesControl({ label, value, onChange, bind = {}, uploadUrl }) {
    const read = (key) => {
        const path = bind[key];
        if (typeof path !== 'string' || !path) return undefined;
        return getValueByPath(value, path);
    };

    const url = typeof read('url') === 'string' ? read('url') : '';
    const svg = typeof read('svg') === 'string' ? read('svg') : '';
    const source = read('source') === 'svg' ? 'svg' : 'url';

    const initialTab = source === 'svg' && svg !== ''
        ? 'svg'
        : (url !== '' ? 'url' : 'upload');

    const [tab, setTab] = useState(initialTab);
    const lastWidgetKey = useRef(null);
    const widgetKey = `${url}::${svg.slice(0, 64)}`;
    useEffect(() => {
        if (lastWidgetKey.current !== null && lastWidgetKey.current !== widgetKey) {
            setTab(initialTab);
        }
        lastWidgetKey.current = widgetKey;
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [widgetKey]);

    const setUrl = (next) => onChange({ url: next, source: 'url' });
    const setSvg = (next) => onChange({ svg: next, source: next.trim() === '' ? 'url' : 'svg' });

    return (
        <div>
            <ControlLabel label={label} />
            <div className="flex items-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden mb-2">
                {MEDIA_SOURCE_TABS.map((t) => (
                    <button
                        key={t.value}
                        type="button"
                        onClick={() => setTab(t.value)}
                        className={`flex-1 h-8 text-xs font-medium transition-colors ${
                            tab === t.value
                                ? 'bg-gray-100 text-gray-900 dark:bg-white/10 dark:text-white'
                                : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5'
                        }`}
                    >
                        {t.label}
                    </button>
                ))}
            </div>

            {tab === 'upload' ? (
                <ImageUrlUploadControl
                    value={url}
                    onChange={setUrl}
                    uploadUrl={uploadUrl}
                    withUrlInput={false}
                />
            ) : null}

            {tab === 'url' ? (
                <div className="space-y-2">
                    <input
                        type="url"
                        value={url}
                        placeholder="https://example.com/image.png"
                        onChange={(e) => setUrl(e.target.value)}
                        className="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-3 py-2 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                    />
                    {url !== '' ? (
                        <div className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 flex items-center justify-center max-h-48 overflow-hidden">
                            <img src={url} alt="" className="max-w-full max-h-40 object-contain" />
                        </div>
                    ) : null}
                </div>
            ) : null}

            {tab === 'svg' ? (
                <div className="space-y-1">
                    <textarea
                        value={svg}
                        onChange={(e) => setSvg(e.target.value)}
                        rows={6}
                        placeholder="<svg …> Paste inline SVG markup here"
                        className="font-mono text-xs block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                    />
                    <p className="text-[11px] text-gray-400 dark:text-gray-500">
                        Inline SVG is used instead of the image URL while present.
                    </p>
                </div>
            ) : null}
        </div>
    );
}

export function CodeControl({ label, value, onChange, language = 'html', rows = 4 }) {
    return (
        <div>
            <ControlLabel label={label} />
            <textarea
                value={value || ''}
                onChange={(e) => onChange(e.target.value)}
                rows={rows}
                className="font-mono text-xs mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
            />
        </div>
    );
}

export function WysiwygControl({ label, value, onChange }) {
    const ref = useRef(null);
    const savedRangeRef = useRef(null);
    const onChangeRef = useRef(onChange);
    const [, forceRender] = useState(0);
    const [active, setActive] = useState({ bold: false, italic: false, underline: false, ol: false, ul: false, link: false });

    onChangeRef.current = onChange;

    useEffect(() => {
        if (!ref.current) return;
        if (ref.current.innerHTML !== (value || '')) {
            ref.current.innerHTML = value || '';
        }
    }, [value]);

    const readState = () => {
        const el = ref.current;
        if (!el) return;
        const sel = window.getSelection();
        if (sel && sel.rangeCount > 0 && el.contains(sel.anchorNode)) {
            savedRangeRef.current = sel.getRangeAt(0).cloneRange();
        }
        if (!el.contains(document.activeElement)) {
            setActive({ bold: false, italic: false, underline: false, ol: false, ul: false, link: false });
            return;
        }
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
        const el = ref.current;
        if (!el) return;
        el.focus();
        const saved = savedRangeRef.current;
        if (saved && el.contains(saved.commonAncestorContainer)) {
            const sel = window.getSelection();
            sel?.removeAllRanges();
            sel?.addRange(saved);
        }
        try { document.execCommand(cmd, false, arg); } catch {}
        onChange(el.innerHTML);
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
        exec('formatBlock', `<${tag}>`);
    };

    const tb = "inline-flex items-center justify-center w-7 h-7 text-xs font-medium rounded transition-colors";
    const tbNorm = `${tb} text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/10`;
    const tbActive = `${tb} bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300`;

    return (
        <div>
            <ControlLabel label={label} />
            <div className="mt-1 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-900">
                <div className="flex flex-wrap items-center gap-1 border-b border-gray-200 dark:border-gray-700 px-1.5 py-1">
                    <select
                        onChange={(e) => { handleBlock(e.target.value); e.target.value = 'p'; }}
                        defaultValue="p"
                        title="Block style"
                        className="h-7 rounded border-0 bg-transparent text-xs text-gray-700 dark:text-gray-300 px-1 focus:outline-none cursor-pointer"
                    >
                        {blockTags.map((t) => (
                            <option key={t.value} value={t.value}>{t.label}</option>
                        ))}
                    </select>
                    <div className="w-px h-4 bg-gray-200 dark:bg-gray-700 mx-0.5" />
                    <Button type="button" className={`!border-0 !px-0 !py-0 ${active.bold ? tbActive : tbNorm}`} onMouseDown={(e) => e.preventDefault()} onClick={() => exec('bold')} title="Bold">
                        <svg className="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M15.6 10.79c.97-.67 1.65-1.77 1.65-2.79 0-2.26-1.75-4-4-4H7v14h7.04c2.09 0 3.71-1.7 3.71-3.79 0-1.52-.86-2.82-2.15-3.42zM10 6.5h3c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-3v-3zm3.5 9H10v-3h3.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5z"/></svg>
                    </Button>
                    <Button type="button" className={`!border-0 !px-0 !py-0 ${active.italic ? tbActive : tbNorm}`} onMouseDown={(e) => e.preventDefault()} onClick={() => exec('italic')} title="Italic">
                        <svg className="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M10 4v3h2.21l-3.42 8H6v3h8v-3h-2.21l3.42-8H18V4z"/></svg>
                    </Button>
                    <Button type="button" className={`!border-0 !px-0 !py-0 ${active.underline ? tbActive : tbNorm}`} onMouseDown={(e) => e.preventDefault()} onClick={() => exec('underline')} title="Underline">
                        <svg className="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17c3.31 0 6-2.69 6-6V3h-2.5v8c0 1.93-1.57 3.5-3.5 3.5S8.5 12.93 8.5 11V3H6v8c0 3.31 2.69 6 6 6zm-7 2v2h14v-2H5z"/></svg>
                    </Button>
                    <div className="w-px h-4 bg-gray-200 dark:bg-gray-700 mx-0.5" />
                    <Button type="button" className={`!border-0 !px-0 !py-0 ${active.ol ? tbActive : tbNorm}`} onMouseDown={(e) => e.preventDefault()} onClick={() => exec('insertOrderedList')} title="Ordered List">
                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><line x1="10" x2="21" y1="6" y2="6"/><line x1="10" x2="21" y1="12" y2="12"/><line x1="10" x2="21" y1="18" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/></svg>
                    </Button>
                    <Button type="button" className={`!border-0 !px-0 !py-0 ${active.ul ? tbActive : tbNorm}`} onMouseDown={(e) => e.preventDefault()} onClick={() => exec('insertUnorderedList')} title="Unordered List">
                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><line x1="9" x2="20" y1="6" y2="6"/><line x1="9" x2="20" y1="12" y2="12"/><line x1="9" x2="20" y1="18" y2="18"/><circle cx="4" cy="6" r="1" fill="currentColor"/><circle cx="4" cy="12" r="1" fill="currentColor"/><circle cx="4" cy="18" r="1" fill="currentColor"/></svg>
                    </Button>
                    <div className="w-px h-4 bg-gray-200 dark:bg-gray-700 mx-0.5" />
                    <Button
                        type="button"
                        className={`!border-0 !px-0 !py-0 ${active.link ? tbActive : tbNorm}`}
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
                            <svg xmlns="http://www.w3.org/2000/svg" className="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M15 7h2a5 5 0 0 1 0 10h-2m-6 0H7A5 5 0 0 1 7 7h2"/></svg>
                        ) : (
                            <svg xmlns="http://www.w3.org/2000/svg" className="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M9 17H7A5 5 0 0 1 7 7h2"/><path d="M15 7h2a5 5 0 1 1 0 10h-2"/><line x1="8" x2="16" y1="12" y2="12"/></svg>
                        )}
                    </Button>
                </div>
                <div
                    ref={ref}
                    className="p-3 min-h-[160px] max-h-[400px] overflow-y-auto prose prose-sm max-w-none dark:prose-invert focus:outline-none empty:before:content-[attr(data-placeholder)] empty:before:text-gray-400 dark:empty:before:text-gray-500"
                    contentEditable
                    suppressContentEditableWarning
                    data-placeholder="Start typing..."
                    onInput={() => {
                        if (ref.current) onChange(ref.current.innerHTML);
                    }}
                />
            </div>
        </div>
    );
}
