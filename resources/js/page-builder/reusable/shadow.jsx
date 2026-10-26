import React, { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { Input, Range } from '../ui/primitives.jsx';
import { ColorInput } from '../ui/color.jsx';

export function ShadowControl({ value, onChange, type = 'box' }) {
    const v = value || {};

    const set = (patch) => {
        onChange({ ...v, ...patch });
    };

    const numberOrEmpty = (input) => {
        if (input === '' || input === null || input === undefined) return '';
        const n = Number(input);
        return Number.isFinite(n) ? n : '';
    };

    const clamp = (n, min, max) => {
        if (!Number.isFinite(n)) return min;
        return Math.max(min, Math.min(max, n));
    };

    const rangeRow = ({ label, key, min, max, step = 1, unit = 'px' }) => {
        const currentValue = numberOrEmpty(v[key]);
        const normalized = currentValue === '' ? min : clamp(Number(currentValue), min, max);

        return (
            <div className="space-y-2">
                <div className="flex items-center justify-between">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{label}</label>
                    <div className="flex items-center gap-2">
                        <Input
                            type="number"
                            value={currentValue === '' ? '' : String(currentValue)}
                            onChange={(raw) => {
                                if (raw === '' || raw === null || raw === undefined) {
                                    set({ [key]: '' });
                                    return;
                                }
                                const n = Number(raw);
                                set({ [key]: Number.isFinite(n) ? clamp(n, min, max) : '' });
                            }}
                            className="mt-0 w-20"
                            placeholder={String(min)}
                        />
                        <span className="text-xs text-gray-500 dark:text-gray-400 min-w-[24px]">{unit}</span>
                    </div>
                </div>
                <Range
                    min={min}
                    max={max}
                    step={step}
                    value={String(normalized)}
                    onChange={(e) => {
                        const n = Number(e.target.value);
                        set({ [key]: Number.isFinite(n) ? n : '' });
                    }}
                    className="w-full"
                />
            </div>
        );
    };

    return (
        <div className="space-y-4">
            <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Color</label>
                <ColorInput value={v.color || ''} onChange={(x) => set({ color: x })} fallback="#000000" />
            </div>

            {rangeRow({ label: 'Offset X', key: 'offsetX', min: -50, max: 50 })}
            {rangeRow({ label: 'Offset Y', key: 'offsetY', min: -50, max: 50 })}
            {rangeRow({ label: 'Blur', key: 'blur', min: 0, max: 100 })}
            {type === 'box' ? rangeRow({ label: 'Spread', key: 'spread', min: -50, max: 50 }) : null}
        </div>
    );
}

export function ShadowPopover({ value, onChange, type = 'box', label = 'Shadow' }) {
    const [open, setOpen] = useState(false);
    const triggerRef = useRef(null);
    const panelRef = useRef(null);
    const [panelStyle, setPanelStyle] = useState(() => ({
        top: 0,
        left: 0,
        width: 320,
        maxHeight: 520,
        placement: 'bottom',
    }));

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
            const maxHeight = Math.max(180, Math.min(560, placeBelow ? spaceBelow : rect.top - 8));

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
        <div>
            <div className="flex items-center justify-between">
                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{label}</div>
                <div className="relative">
                    <button
                        ref={triggerRef}
                        type="button"
                        onClick={() => setOpen((v) => !v)}
                        className="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800"
                        title={`Edit ${label.toLowerCase()}`}
                    >
                        <span aria-hidden>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil-icon lucide-pencil"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg>
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
                                  <ShadowControl value={value} onChange={onChange} type={type} />
                              </div>,
                              document.body
                          )
                        : null}
                </div>
            </div>
        </div>
    );
}
