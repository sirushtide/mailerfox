import React, { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { Range } from '../ui/primitives.jsx';
import { ColorInput } from '../ui/color.jsx';

export const BORDER_EFFECT_OPTIONS = [
    { value: 'none', label: 'None' },
    { value: 'glow', label: 'Glow (animated)' },
    { value: 'gradient', label: 'Gradient border' },
    { value: 'sweep', label: 'Border sweep' },
    { value: 'running', label: 'Running border' },
    { value: 'trace', label: 'Border tracing' },
    { value: 'neon', label: 'Neon border' },
    { value: 'shimmer', label: 'Shimmer border' },
];

const VALID_EFFECTS = BORDER_EFFECT_OPTIONS.map((o) => o.value).filter((v) => v !== 'none');

export const DEFAULT_BORDER_EFFECT_KEYS = {
    effect: 'borderEffect',
    color: 'borderEffectColor',
    color2: 'borderEffectColor2',
    speed: 'borderEffectSpeed',
    thickness: 'borderEffectThickness',
};

function normEffect(value) {
    return VALID_EFFECTS.includes(value) ? value : 'none';
}

function clampNumber(value, min, max, fallback) {
    const n = Number(value);
    if (!Number.isFinite(n)) return fallback;
    return Math.max(min, Math.min(max, n));
}

// Returns the class string to add to the target element (or '' when disabled).
export function borderEffectClassName(props, keys = DEFAULT_BORDER_EFFECT_KEYS) {
    const p = props || {};
    const effect = normEffect(p[keys.effect]);
    if (effect === 'none') return '';
    return `mp-bfx mp-bfx--${effect}`;
}

// Returns an object of CSS custom properties to merge into the element's inline style.
export function borderEffectStyleVars(props, keys = DEFAULT_BORDER_EFFECT_KEYS) {
    const p = props || {};
    const effect = normEffect(p[keys.effect]);
    if (effect === 'none') return {};

    const vars = {};
    const color = typeof p[keys.color] === 'string' && p[keys.color].trim() ? p[keys.color].trim() : '#3b82f6';
    const color2 = typeof p[keys.color2] === 'string' && p[keys.color2].trim() ? p[keys.color2].trim() : '#8b5cf6';
    const speed = clampNumber(p[keys.speed], 0.5, 30, 3);
    const thickness = clampNumber(p[keys.thickness], 1, 12, 2);

    vars['--mp-bfx-color'] = color;
    vars['--mp-bfx-color2'] = color2;
    vars['--mp-bfx-speed'] = `${speed}s`;
    vars['--mp-bfx-size'] = `${Math.round(thickness)}px`;
    return vars;
}

function EffectSelect({ value, onChange, options }) {
    const [open, setOpen] = useState(false);
    const buttonRef = useRef(null);
    const listRef = useRef(null);
    const [style, setStyle] = useState({ top: 0, left: 0, width: 0 });
    const listId = useRef(`mp-bfx-effect-${Math.random().toString(36).slice(2)}`).current;
    const selected = options.find((o) => o.value === value) || options[0];

    useEffect(() => {
        if (!open) return;
        const update = () => {
            const rect = buttonRef.current?.getBoundingClientRect();
            if (!rect) return;
            const below = rect.bottom + 8;
            const listHeight = Math.min(options.length * 36, 240);
            const spaceBelow = window.innerHeight - below - 8;
            const placeBelow = spaceBelow >= listHeight || spaceBelow >= rect.top - 8;
            const top = placeBelow ? below : Math.max(8, rect.top - 8 - listHeight);
            setStyle({ top, left: rect.left, width: rect.width });
        };
        update();
        const onDocClick = (e) => {
            if (buttonRef.current && buttonRef.current.contains(e.target)) return;
            if (listRef.current && listRef.current.contains(e.target)) return;
            setOpen(false);
        };
        document.addEventListener('mousedown', onDocClick);
        window.addEventListener('resize', update);
        window.addEventListener('scroll', update, true);
        return () => {
            document.removeEventListener('mousedown', onDocClick);
            window.removeEventListener('resize', update);
            window.removeEventListener('scroll', update, true);
        };
    }, [open, options.length]);

    return (
        <div className="relative">
            <button
                ref={buttonRef}
                type="button"
                onClick={() => setOpen((v) => !v)}
                className="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-left text-sm text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
            >
                <span className="block truncate">{selected.label}</span>
                <span className="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </span>
            </button>
            {open
                ? createPortal(
                      <div
                          ref={listRef}
                          id={listId}
                          className="rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-lg max-h-60 overflow-auto"
                          style={{ position: 'fixed', top: style.top, left: style.left, width: style.width, zIndex: 99999 }}
                      >
                          {options.map((o) => (
                              <button
                                  key={o.value}
                                  type="button"
                                  onClick={() => {
                                      onChange(o.value);
                                      setOpen(false);
                                  }}
                                  className={`w-full text-left px-3 py-2 text-sm ${o.value === value ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800'}`}
                              >
                                  {o.label}
                              </button>
                          ))}
                      </div>,
                      document.body
                  )
                : null}
        </div>
    );
}

export function BorderEffectControls({ value, onChange, keys = DEFAULT_BORDER_EFFECT_KEYS }) {
    const v = value || {};
    const effect = normEffect(v[keys.effect]);

    const set = (patch) => onChange({ ...v, ...patch });

    const speed = clampNumber(v[keys.speed], 0.5, 30, 3);
    const thickness = clampNumber(v[keys.thickness], 1, 12, 2);

    return (
        <div className="space-y-3">
            <div className="grid grid-cols-2 gap-3 items-center">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Effect</label>
                <EffectSelect
                    value={effect}
                    onChange={(next) => set({ [keys.effect]: next === 'none' ? '' : next })}
                    options={BORDER_EFFECT_OPTIONS}
                />
            </div>

            {effect !== 'none' ? (
                <>
                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Color 1</label>
                        <div className="flex justify-end">
                            <ColorInput
                                value={typeof v[keys.color] === 'string' ? v[keys.color] : ''}
                                onChange={(x) => set({ [keys.color]: x })}
                                fallback="#3b82f6"
                            />
                        </div>
                    </div>

                    {effect !== 'neon' ? (
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Color 2</label>
                            <div className="flex justify-end">
                                <ColorInput
                                    value={typeof v[keys.color2] === 'string' ? v[keys.color2] : ''}
                                    onChange={(x) => set({ [keys.color2]: x })}
                                    fallback="#8b5cf6"
                                />
                            </div>
                        </div>
                    ) : null}

                    <div>
                        <div className="grid grid-cols-2 gap-3 items-center mb-2">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Thickness</label>
                            <div className="flex items-center justify-end gap-2">
                                <span className="text-xs text-gray-500 dark:text-gray-400">{Math.round(thickness)}px</span>
                            </div>
                        </div>
                        <Range
                            min="1"
                            max="12"
                            step="1"
                            value={String(Math.round(thickness))}
                            onChange={(e) => set({ [keys.thickness]: clampNumber(e.target.value, 1, 12, 2) })}
                        />
                    </div>

                    <div>
                        <div className="grid grid-cols-2 gap-3 items-center mb-2">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Speed</label>
                            <div className="flex items-center justify-end gap-2">
                                <span className="text-xs text-gray-500 dark:text-gray-400">{speed}s</span>
                            </div>
                        </div>
                        <Range
                            min="0.5"
                            max="10"
                            step="0.5"
                            value={String(speed)}
                            onChange={(e) => set({ [keys.speed]: clampNumber(e.target.value, 0.5, 30, 3) })}
                        />
                    </div>
                </>
            ) : null}
        </div>
    );
}
