import React, { useContext, useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { ChromePicker } from 'react-color';

export const ColorPresetsContext = React.createContext([]);
export const ColorPresetsProvider = ColorPresetsContext.Provider;

const DEFAULT_PRESETS = [
    '#000000', '#ffffff', '#ef4444', '#f97316', '#f59e0b', '#eab308',
    '#84cc16', '#22c55e', '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9',
    '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef', '#ec4899',
    '#f43f5e', '#78716c', '#94a3b8', '#cbd5e1', '#e2e8f0', '#f1f5f9',
];

export function isHexColor(value) {
    if (typeof value !== 'string') return false;
    return /^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/.test(value.trim());
}

export function normalizeHexColor(value, fallback = '#000000') {
    if (!isHexColor(value)) return fallback;
    const v = value.trim();
    if (v.length === 4) {
        return `#${v[1]}${v[1]}${v[2]}${v[2]}${v[3]}${v[3]}`;
    }
    if (v.length === 5) {
        return `#${v[1]}${v[1]}${v[2]}${v[2]}${v[3]}${v[3]}${v[4]}${v[4]}`;
    }
    return v;
}

function clamp(n, min, max) {
    if (!Number.isFinite(n)) return min;
    return Math.max(min, Math.min(max, n));
}

function round(n) {
    return Math.round(Number(n) || 0);
}

function rgbToHex(r, g, b) {
    const to = (x) => clamp(round(x), 0, 255).toString(16).padStart(2, '0');
    return `#${to(r)}${to(g)}${to(b)}`;
}

function parseRgbFunc(str) {
    const m = String(str)
        .trim()
        .match(/^rgba?\(\s*([0-9.]+)\s*,\s*([0-9.]+)\s*,\s*([0-9.]+)(?:\s*,\s*([0-9.]+)\s*)?\)$/i);
    if (!m) return null;
    const r = clamp(Number(m[1]), 0, 255);
    const g = clamp(Number(m[2]), 0, 255);
    const b = clamp(Number(m[3]), 0, 255);
    const a = m[4] === undefined ? 1 : clamp(Number(m[4]), 0, 1);
    if (![r, g, b, a].every((x) => Number.isFinite(x))) return null;
    return { r, g, b, a };
}

function parseHex(str) {
    if (!isHexColor(str)) return null;
    const hex = normalizeHexColor(str);
    const raw = hex.slice(1);
    const r = parseInt(raw.slice(0, 2), 16);
    const g = parseInt(raw.slice(2, 4), 16);
    const b = parseInt(raw.slice(4, 6), 16);
    const a = raw.length === 8 ? parseInt(raw.slice(6, 8), 16) / 255 : 1;
    return { r, g, b, a };
}

function parseColorString(value, fallback) {
    if (typeof value !== 'string' || !value.trim()) {
        return parseHex(fallback) || { r: 0, g: 0, b: 0, a: 1 };
    }
    const v = value.trim();
    const asHex = parseHex(v);
    if (asHex) return asHex;
    const asRgb = parseRgbFunc(v);
    if (asRgb) return asRgb;
    const fb = parseHex(fallback);
    return fb || { r: 0, g: 0, b: 0, a: 1 };
}

function formatColor({ r, g, b, a }) {
    const rr = clamp(round(r), 0, 255);
    const gg = clamp(round(g), 0, 255);
    const bb = clamp(round(b), 0, 255);
    const aa = clamp(Number(a), 0, 1);
    if (aa < 1) {
        const alpha = Math.round(aa * 100) / 100;
        return `rgba(${rr}, ${gg}, ${bb}, ${alpha})`;
    }
    return rgbToHex(rr, gg, bb);
}

function checkerBg() {
    return {
        backgroundImage:
            'linear-gradient(45deg, rgba(0,0,0,0.08) 25%, transparent 25%),\n' +
            'linear-gradient(-45deg, rgba(0,0,0,0.08) 25%, transparent 25%),\n' +
            'linear-gradient(45deg, transparent 75%, rgba(0,0,0,0.08) 75%),\n' +
            'linear-gradient(-45deg, transparent 75%, rgba(0,0,0,0.08) 75%)',
        backgroundSize: '12px 12px',
        backgroundPosition: '0 0, 0 6px, 6px -6px, -6px 0px',
    };
}

function usePresetColors(presetsProp) {
    const ctx = useContext(ColorPresetsContext);
    return useMemo(() => {
        const source = Array.isArray(presetsProp) && presetsProp.length > 0
            ? presetsProp
            : Array.isArray(ctx) && ctx.length > 0
              ? ctx
              : [];
        if (source.length > 0) {
            return source
                .map((c) => (c && typeof c === 'object' ? c.value : c))
                .filter((c) => typeof c === 'string' && c.startsWith('#'));
        }
        return DEFAULT_PRESETS;
    }, [presetsProp, ctx]);
}

export function ColorInput({ value, onChange, fallback = '#000000', presets: presetsProp, variant = 'default' }) {
    const hasValue = typeof value === 'string' && value.trim() !== '';
    const parsed = useMemo(() => parseColorString(value, fallback), [value, fallback]);
    const alpha = useMemo(() => clamp(parsed.a, 0, 1), [parsed.a]);
    const presets = usePresetColors(presetsProp);

    const triggerRef = useRef(null);
    const panelRef = useRef(null);
    const [open, setOpen] = useState(false);
    const [panelStyle, setPanelStyle] = useState(() => ({ top: 0, left: 0, width: 256, maxHeight: 420, placement: 'bottom' }));

    const emit = (next) => {
        onChange(formatColor(next));
    };

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
            const desiredWidth = 256;
            const maxWidth = Math.min(window.innerWidth - 16, boundsWidth - 16);
            const width = Math.max(220, Math.min(desiredWidth, Math.max(220, maxWidth)));

            let left = rect.right - width;
            left = Math.max(boundsLeft + 8, Math.min(left, boundsRight - width - 8));

            const belowTop = rect.bottom + gap;
            const spaceBelow = window.innerHeight - belowTop - 8;
            const spaceAbove = rect.top - 8;
            const placeBelow = spaceBelow >= 260 || spaceBelow >= spaceAbove;
            const top = placeBelow ? belowTop : Math.max(8, rect.top - gap - Math.min(420, Math.max(180, spaceAbove - 8)));
            const maxHeight = Math.max(180, Math.min(520, placeBelow ? spaceBelow : rect.top - 8));

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

    const displayColor =
        alpha >= 1
            ? `rgb(${clamp(round(parsed.r), 0, 255)}, ${clamp(round(parsed.g), 0, 255)}, ${clamp(round(parsed.b), 0, 255)})`
            : `rgba(${clamp(round(parsed.r), 0, 255)}, ${clamp(round(parsed.g), 0, 255)}, ${clamp(round(parsed.b), 0, 255)}, ${Math.round(alpha * 100) / 100})`;

    const isMinimal = variant === 'minimal';

    return (
        <div className={`relative inline-flex items-center ${isMinimal ? '' : 'gap-2'}`}>
            {!isMinimal && hasValue ? (
                <button
                    type="button"
                    onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        onChange('');
                    }}
                    className="w-4 h-4 inline-flex items-center justify-center rounded-md dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                    title="Reset color"
                    aria-label="Reset color"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-rotate-ccw-icon lucide-rotate-rotate-ccw"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                </button>
            ) : null}
            <button
                ref={triggerRef}
                type="button"
                onClick={() => setOpen((v) => !v)}
                className={isMinimal
                    ? 'w-7 h-7 rounded border border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 transition-colors'
                    : 'w-10 h-10 rounded-full border-2 border-gray-300 dark:border-gray-600 shadow-sm hover:border-gray-400 transition-colors'}
                style={hasValue ? { backgroundColor: displayColor } : { backgroundColor: 'transparent', ...checkerBg() }}
                title="Pick color"
            />


            {open
                ? createPortal(
                      <div
                          ref={panelRef}
                          className="bg-white/85 dark:bg-gray-900/85 shadow-lg border dark:border-gray-700 backdrop-blur-md rounded-md"
                          onMouseDown={(e) => e.stopPropagation()}
                          onClick={(e) => e.stopPropagation()}
                          style={{
                              position: 'fixed',
                              top: panelStyle.top,
                              left: panelStyle.left,
                              overflowY: 'auto',
                              overscrollBehavior: 'contain',
                              zIndex: 9999,
                          }}
                      >
                          <div className="p-2 border-b border-gray-200 dark:border-gray-700">
                              <div className="grid grid-cols-8 gap-1.5">
                                  {presets.map((c) => (
                                      <button
                                          key={c}
                                          type="button"
                                          onClick={() => onChange(c)}
                                          className="w-6 h-6 rounded-full border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                          style={{ backgroundColor: c }}
                                          title={c}
                                          aria-label={`Select color ${c}`}
                                      />
                                  ))}
                              </div>
                          </div>
                          <div>
                              <ChromePicker
                                  color={{
                                      r: clamp(round(parsed.r), 0, 255),
                                      g: clamp(round(parsed.g), 0, 255),
                                      b: clamp(round(parsed.b), 0, 255),
                                      a: alpha,
                                  }}
                                  disableAlpha={false}
                                  onChange={(c) => {
                                      const rgb = c && c.rgb ? c.rgb : null;
                                      if (!rgb) return;
                                      emit({ r: rgb.r, g: rgb.g, b: rgb.b, a: rgb.a });
                                  }}
                              />
                          </div>
                      </div>,
                      document.body
                  )
                : null}
        </div>
    );
}
