import React from 'react';
import { Range } from '../ui/primitives.jsx';

const numberInputClass =
    'block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-3 py-2 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500';

export function DimensionsControls({
    value,
    onChange,
    widthKey = 'width',
    heightKey = 'height',
    widthLabel = 'Width (px)',
    heightLabel = 'Height (px)',
    widthPlaceholder = 'Auto',
    heightPlaceholder = 'Auto',
    showWidth = true,
    showHeight = true,
    useSliders = false,
    widthMin = 0,
    widthMax = 1200,
    widthStep = 1,
    heightMin = 0,
    heightMax = 800,
    heightStep = 1,
}) {
    const v = value || {};

    const clamp = (n, min, max) => {
        if (!Number.isFinite(n)) return min;
        return Math.max(min, Math.min(max, n));
    };

    const numberOrEmpty = (raw) => {
        if (raw === '' || raw === null || raw === undefined) return '';
        const n = Number(raw);
        return Number.isFinite(n) ? n : '';
    };

    const setNumber = (key, raw) => {
        if (raw === '' || raw === null || raw === undefined) {
            onChange({ ...v, [key]: undefined });
            return;
        }
        const n = Number(raw);
        onChange({ ...v, [key]: Number.isFinite(n) ? n : undefined });
    };

    const sliderRow = ({ label, key, min, max, step, placeholder }) => {
        const curr = numberOrEmpty(v[key]);
        const sliderValue = curr === '' ? min : clamp(Number(curr), min, max);

        return (
            <div className="space-y-2">
                <div className="flex items-center justify-between gap-3">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{label}</label>
                    <div className="w-24">
                        <input
                            type="number"
                            min={min}
                            max={max}
                            step={step}
                            value={curr !== '' ? String(curr) : ''}
                            onChange={(e) => setNumber(key, e.target.value)}
                            placeholder={placeholder}
                            className={numberInputClass}
                        />
                    </div>
                </div>
                <Range
                    min={min}
                    max={max}
                    step={step}
                    value={String(sliderValue)}
                    onChange={(e) => {
                        const n = Number(e.target.value);
                        setNumber(key, Number.isFinite(n) ? n : undefined);
                    }}
                    className="w-full"
                />
            </div>
        );
    };

    return (
        <div className="space-y-3">
            {showWidth ? (
                <div>
                    {useSliders ? (
                        sliderRow({ label: widthLabel, key: widthKey, min: widthMin, max: widthMax, step: widthStep, placeholder: widthPlaceholder })
                    ) : (
                        <>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{widthLabel}</label>
                            <input
                                type="number"
                                value={v[widthKey] !== undefined ? String(v[widthKey]) : ''}
                                onChange={(e) => setNumber(widthKey, e.target.value)}
                                placeholder={widthPlaceholder}
                                className={numberInputClass}
                            />
                        </>
                    )}
                </div>
            ) : null}

            {showHeight ? (
                <div>
                    {useSliders ? (
                        sliderRow({ label: heightLabel, key: heightKey, min: heightMin, max: heightMax, step: heightStep, placeholder: heightPlaceholder })
                    ) : (
                        <>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{heightLabel}</label>
                            <input
                                type="number"
                                value={v[heightKey] !== undefined ? String(v[heightKey]) : ''}
                                onChange={(e) => setNumber(heightKey, e.target.value)}
                                placeholder={heightPlaceholder}
                                className={numberInputClass}
                            />
                        </>
                    )}
                </div>
            ) : null}
        </div>
    );
}
