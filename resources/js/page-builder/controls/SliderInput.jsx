import React, { useMemo } from 'react';
import { Range } from '../ui/primitives.jsx';
import { getSliderDefaults } from './controlDefaults.js';
import { parseUnit } from './bind.js';

export function SliderInput({
    label,
    value,
    onChange,
    kind = 'default',
    units: unitsProp,
    defaultUnit: defaultUnitProp,
    min: minProp,
    max: maxProp,
    step: stepProp,
    disabled,
    className = '',
}) {
    const defaults = getSliderDefaults(kind);
    const units = unitsProp || defaults.units;
    const defaultUnit = defaultUnitProp || defaults.defaultUnit;

    const { value: numericValue, unit } = useMemo(() => parseUnit(value), [value]);
    const currentUnit = units.includes(unit) ? unit : defaultUnit || units[0] || 'px';

    const min = minProp !== undefined ? minProp : defaults.min;
    const max = maxProp !== undefined ? maxProp : defaults.max;
    const step = stepProp !== undefined ? stepProp : defaults.step;

    const getEffectiveMax = (unit) => {
        if (unit === '%') return Math.min(Number(max), 100);
        return Number(max);
    };

    const safeValue = Number.isFinite(numericValue) ? numericValue : 0;
    const clampedValue = Math.max(Number(min), Math.min(getEffectiveMax(currentUnit), safeValue));
    const currentMax = getEffectiveMax(currentUnit);

    const isPlainNumber = units.length === 1 && units[0] === '';
    const showUnitSelect = !isPlainNumber && units.length > 1;

    const emitValue = (nextValue, nextUnit) => {
        const u = nextUnit !== undefined ? nextUnit : currentUnit;
        const effectiveMax = getEffectiveMax(u);
        const n = Number(nextValue);
        if (!Number.isFinite(n)) {
            onChange(isPlainNumber ? '' : { value: '', unit: u });
            return;
        }
        const clampedNextValue = Math.max(Number(min), Math.min(effectiveMax, n));
        if (isPlainNumber) {
            onChange(clampedNextValue);
            return;
        }
        if (u === 'px' || u === '') {
            onChange(clampedNextValue);
            return;
        }
        onChange({ value: clampedNextValue, unit: u });
    };

    return (
        <div className={className}>
            {label || showUnitSelect ? (
                <div className={`flex items-center ${label ? 'justify-between' : 'justify-end'} gap-2 mb-1 h-6`}>
                    {label ? (
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 truncate">{label}</label>
                    ) : null}
                    {showUnitSelect ? (
                        <select
                            value={currentUnit}
                            disabled={disabled}
                            onChange={(e) => emitValue(numericValue, e.target.value)}
                            style={{
                                backgroundImage:
                                    "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E\")",
                                backgroundRepeat: 'no-repeat',
                                backgroundPosition: 'right 0 center',
                            }}
                            className="h-5 max-w-[60px] shrink-0 cursor-pointer appearance-none border-0 bg-transparent bg-white/0 pr-2.5 text-[10px] font-medium leading-none text-gray-500 dark:text-gray-300 focus:outline-none"
                        >
                            {units.map((u) => (
                                <option key={u} value={u}>
                                    {u || '—'}
                                </option>
                            ))}
                        </select>
                    ) : null}
                </div>
            ) : null}
            <div className="flex items-center relative gap-2">
                <div className="flex-1 min-w-0">
                    <Range
                        value={String(clampedValue)}
                        min={min}
                        max={currentMax}
                        step={step}
                        disabled={disabled}
                        onChange={(e) => emitValue(e.target.value, currentUnit)}
                    />
                </div>
                <input
                    type="number"
                    step={step}
                    min={min}
                    max={currentMax}
                    value={Number.isFinite(numericValue) ? clampedValue : ''}
                    disabled={disabled}
                    onChange={(e) => emitValue(e.target.value, currentUnit)}
                    className="w-14 rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-0.5 py-1 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                />
            </div>
        </div>
    );
}
