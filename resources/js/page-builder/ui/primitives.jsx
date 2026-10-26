import React from 'react';
import { Range as ReactRange } from 'react-range';

export function Button({ children, ...props }) {
    return (
        <button
            {...props}
            className={`inline-flex items-center rounded-md border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800 ${
                props.className || ''
            }`}
        >
            {children}
        </button>
    );
}

export function Select({ value, onChange, options }) {
    return (
        <select
            value={value}
            onChange={(e) => onChange(e.target.value)}
            className="mt-1 block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
        >
            {options.map((o) => (
                <option key={o.value} value={o.value}>
                    {o.label}
                </option>
            ))}
        </select>
    );
}

export function Input({ value, onChange, type = 'text', ...props }) {
    const inputValue = value === undefined || value === null ? '' : String(value);
    return (
        <input
            {...props}
            type={type}
            value={inputValue}
            onChange={(e) => onChange(e.target.value)}
            className="mt-1 block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary placeholder:text-admin-text-secondary/70 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
        />
    );
}

export function Textarea({ value, onChange, rows = 4, ...props }) {
    const inputValue = value === undefined || value === null ? '' : String(value);
    return (
        <textarea
            {...props}
            rows={rows}
            value={inputValue}
            onChange={(e) => onChange(e.target.value)}
            className="mt-1 block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary placeholder:text-admin-text-secondary/70 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
        />
    );
}

export function Range({
    value,
    min = 0,
    max = 100,
    step = 1,
    className = '',
    style,
    onChange,
    disabled,
    ...props
}) {
    const minN = Number(min);
    const maxN = Number(max);
    const stepN = Number(step);
    const vN = Number(value);

    const safeMin = Number.isFinite(minN) ? minN : 0;
    const safeMax = Number.isFinite(maxN) ? maxN : safeMin + 100;
    const safeStep = Number.isFinite(stepN) && stepN > 0 ? stepN : 1;
    const safeValue = Number.isFinite(vN) ? Math.max(safeMin, Math.min(safeMax, vN)) : safeMin;

    const denom = safeMax - safeMin;
    const pct = denom === 0 ? 0 : ((safeValue - safeMin) / denom) * 100;
    const clampedPct = Math.max(0, Math.min(100, pct));
    const isHue = String(className || '').split(/\s+/).includes('mp-range-hue');
    const hueBg = style && typeof style === 'object' ? style['--mp-range-hue-bg'] : undefined;

    return (
        <ReactRange
            values={[safeValue]}
            step={safeStep}
            min={safeMin}
            max={safeMax}
            disabled={Boolean(disabled)}
            onChange={(values) => {
                const next = Array.isArray(values) ? values[0] : safeMin;
                if (typeof onChange === 'function') {
                    onChange({ target: { value: String(next) } });
                }
            }}
            renderTrack={({ props: trackProps, children }) => (
                <div className={`mp-range ${className || ''}`.trim()} style={style}>
                    <div
                        ref={trackProps.ref}
                        onMouseDown={trackProps.onMouseDown}
                        onTouchStart={trackProps.onTouchStart}
                        className="mp-range-track"
                        style={{
                            ...(trackProps.style || {}),
                            background: isHue
                                ? hueBg ||
                                  'linear-gradient(to right, #ff0000, #ffff00, #00ff00, #00ffff, #0000ff, #ff00ff, #ff0000)'
                                : undefined,
                        }}
                    >
                        {isHue ? null : <div className="mp-range-fill" style={{ width: `${clampedPct}%` }} />}
                        {children}
                    </div>
                </div>
            )}
            renderThumb={({ props: thumbProps, isDragged }) => (
                <div
                    {...thumbProps}
                    ref={thumbProps.ref}
                    className="mp-range-thumb"
                    style={{
                        ...(thumbProps.style || {}),
                        cursor: 'pointer',
                        ...(isDragged ? { filter: 'brightness(0.98)' } : null),
                    }}
                />
            )}
            {...props}
        />
    );
}
