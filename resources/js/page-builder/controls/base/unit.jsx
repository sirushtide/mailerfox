import React, { useMemo, useState } from 'react';
import { SliderInput } from '../SliderInput.jsx';
import { Switcher } from '../Switcher.jsx';
import { getValueByPath, parseUnit, setValueByPath } from '../bind.js';
import { toFiniteNumberOrUndefined } from '../../lib/boxModel.js';

function ControlLabel({ label }) {
    return <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{label}</label>;
}

const LinkIcon = ({ className = '' }) => (
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className}>
        <path d="M9 17H7A5 5 0 0 1 7 7h2" />
        <path d="M15 7h2a5 5 0 1 1 0 10h-2" />
        <line x1="8" x2="16" y1="12" y2="12" />
    </svg>
);

const UnlinkIcon = ({ className = '' }) => (
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className}>
        <path d="M15 7h2a5 5 0 0 1 0 10h-2m-6 0H7A5 5 0 0 1 7 7h2" />
    </svg>
);

function parseNumeric(value) {
    if (value === undefined || value === null || value === '') return '';
    const { value: num } = parseUnit(value);
    return Number.isFinite(num) ? num : '';
}

function emitValue(raw, unit) {
    if (raw === '' || raw === null || raw === undefined) return undefined;
    const n = Number(raw);
    if (!Number.isFinite(n)) return undefined;
    if (unit === 'px') return n;
    return { value: n, unit };
}

function BorderRadiusDimensionsControl({ label, value, onChange, bind }) {
    const data = value || {};
    const linked = Boolean(getValueByPath(data, bind.linked));

    const top = getValueByPath(data, bind.t);
    const right = getValueByPath(data, bind.r);
    const bottom = getValueByPath(data, bind.b);
    const left = getValueByPath(data, bind.l);

    const firstValue = top !== undefined ? top : right !== undefined ? right : bottom !== undefined ? bottom : left;
    const firstParsed = parseUnit(firstValue);
    const [unit, setUnit] = useState(['px', '%'].includes(firstParsed.unit) ? firstParsed.unit : 'px');

    const sides = [
        { key: 't', path: bind.t, label: 'TOP' },
        { key: 'r', path: bind.r, label: 'RIGHT' },
        { key: 'b', path: bind.b, label: 'BOTTOM' },
        { key: 'l', path: bind.l, label: 'LEFT' },
    ];

    const changeUnit = (nextUnit) => {
        if (nextUnit === unit) return;
        setUnit(nextUnit);
        const convert = (val) => {
            const num = parseNumeric(val);
            if (num === '') return val;
            return nextUnit === 'px' ? num : { value: num, unit: nextUnit };
        };
        onChange({
            linked: true,
            t: convert(top),
            r: convert(right),
            b: convert(bottom),
            l: convert(left),
        });
    };

    const setLinkedValue = (raw) => {
        const emitted = emitValue(raw, unit);
        onChange({
            linked: true,
            t: emitted,
            r: emitted,
            b: emitted,
            l: emitted,
        });
    };

    const setSideValue = (side, raw) => {
        if (linked) {
            setLinkedValue(raw);
            return;
        }
        onChange({ [side]: emitValue(raw, unit) });
    };

    const toggleLinked = () => {
        if (!linked) {
            setLinkedValue(parseNumeric(top));
        } else {
            onChange({ linked: false });
        }
    };

    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between">
                <ControlLabel label={label} />
                <div className="flex items-center gap-2">
                    <div className="inline-flex rounded-md bg-gray-100 dark:bg-gray-800 p-0.5 text-xs font-medium">
                        {['px', '%'].map((u) => (
                            <button
                                key={u}
                                type="button"
                                onClick={() => changeUnit(u)}
                                className={`px-2 py-0.5 rounded ${unit === u ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 shadow-sm' : 'text-gray-500 dark:text-gray-400'}`}
                            >
                                {u}
                            </button>
                        ))}
                    </div>
                    <button
                        type="button"
                        onClick={toggleLinked}
                        className={`p-1 rounded ${linked ? 'text-blue-600 bg-blue-50 dark:bg-blue-900/20' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'}`}
                        title={linked ? 'Unlink sides' : 'Link sides'}
                    >
                        <LinkIcon />
                    </button>
                </div>
            </div>
            <div className="flex rounded-lg border px-1 border-gray-50 text-center bg-gray-50 dark:bg-gray-900 overflow-hidden">
                {sides.map((side, idx) => {
                    const sideValue = getValueByPath(data, side.path);
                    return (
                        <div
                            key={side.path}
                            className={`flex-1 ${idx !== sides.length - 1 ? 'border-gray-200 dark:border-gray-700' : ''}`}
                        >
                            <input
                                type="number"
                                value={parseNumeric(sideValue)}
                                onChange={(e) => setSideValue(side.key, e.target.value)}
                                className="w-[55px] h-[30px] mt-2 mb-1 p-0 rounded-md border-gray-50 text-center text-sm bg-transparent text-gray-900 dark:text-gray-100 focus:outline-none focus:bg-blue-50 dark:focus:bg-blue-900/20"
                            />
                            <div className="text-[10px] text-center text-gray-400 dark:text-gray-500 pb-1 uppercase tracking-wide">
                                {side.label}
                            </div>
                        </div>
                    );
                })}
                
            </div>
        </div>
    );
}

export function SliderControl({ label, value, onChange, kind, units, defaultUnit, min, max, step }) {
    return (
        <SliderInput
            label={label}
            value={value}
            onChange={onChange}
            kind={kind}
            units={units}
            defaultUnit={defaultUnit}
            min={min}
            max={max}
            step={step}
        />
    );
}

export function DimensionsControl({ label, value, onChange, bind, kind = 'spacing' }) {
    if (kind === 'border-radius') {
        return <BorderRadiusDimensionsControl label={label} value={value} onChange={onChange} bind={bind} />;
    }

    const data = value || {};
    const linked = Boolean(getValueByPath(data, bind.linked));

    const top = getValueByPath(data, bind.t);
    const right = getValueByPath(data, bind.r);
    const bottom = getValueByPath(data, bind.b);
    const left = getValueByPath(data, bind.l);

    const sides = [
        { key: 't', path: bind.t, label: 'TOP' },
        { key: 'r', path: bind.r, label: 'RIGHT' },
        { key: 'b', path: bind.b, label: 'BOTTOM' },
        { key: 'l', path: bind.l, label: 'LEFT' },
    ];

    const setLinkedValue = (raw) => {
        const n = raw === '' || raw === null || raw === undefined ? undefined : Number(raw);
        const emitted = Number.isFinite(n) ? n : undefined;
        onChange({
            linked: true,
            t: emitted,
            r: emitted,
            b: emitted,
            l: emitted,
        });
    };

    const setSideValue = (side, raw) => {
        if (linked) {
            setLinkedValue(raw);
            return;
        }
        const n = raw === '' || raw === null || raw === undefined ? undefined : Number(raw);
        onChange({ [side]: Number.isFinite(n) ? n : undefined });
    };

    const toggleLinked = () => {
        if (!linked) {
            const firstRaw = parseNumeric(top);
            setLinkedValue(firstRaw);
        } else {
            onChange({ linked: false });
        }
    };

    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between">
                <ControlLabel label={label} />
                <button
                    type="button"
                    onClick={toggleLinked}
                    className={`p-1 rounded ${linked ? 'text-blue-600 bg-blue-50 dark:bg-blue-900/20' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'}`}
                    title={linked ? 'Unlink sides' : 'Link sides'}
                >
                    {linked ? <LinkIcon /> : <UnlinkIcon />}
                </button>
            </div>
            <div className="flex rounded-lg border px-1 border-gray-50 text-center bg-gray-50 dark:bg-gray-900 overflow-hidden">
                {sides.map((side, idx) => {
                    const sideValue = getValueByPath(data, side.path);
                    return (
                        <div
                            key={side.path}
                            className={`flex-1 ${idx !== sides.length - 1 ? 'border-gray-200 dark:border-gray-700' : ''}`}
                        >
                            <input
                                type="number"
                                value={parseNumeric(sideValue)}
                                onChange={(e) => setSideValue(side.key, e.target.value)}
                                className="w-[55px] h-[30px] mt-2 mb-1 p-0 rounded-md border-gray-50 text-center text-sm bg-transparent text-gray-900 dark:text-gray-100 focus:outline-none focus:bg-blue-50 dark:focus:bg-blue-900/20"
                            />
                            <div className="text-[10px] text-center text-gray-400 dark:text-gray-500 pb-1 uppercase tracking-wide">
                                {side.label}
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

export function GapBoxControl({ label, value, onChange, bind }) {
    const data = value || {};
    const linked = getValueByPath(data, bind.linked) !== false;

    const gapX = getValueByPath(data, bind.x);
    const gapY = getValueByPath(data, bind.y);
    const gap = getValueByPath(data, bind.gap);

    const parseNumeric = (v) => {
        if (v === undefined || v === null || v === '') return '';
        const n = Number(v);
        return Number.isFinite(n) ? String(n) : '';
    };

    const handleChange = (axis, raw) => {
        const n = raw === '' || raw === null || raw === undefined ? undefined : Number(raw);
        const emitted = Number.isFinite(n) ? n : undefined;
        if (linked) {
            onChange({ x: emitted, y: emitted, gap: emitted });
        } else {
            onChange({ [axis]: emitted });
        }
    };

    const toggleLinked = () => {
        if (!linked) {
            const v =
                toFiniteNumberOrUndefined(gapX) ??
                toFiniteNumberOrUndefined(gapY) ??
                toFiniteNumberOrUndefined(gap) ??
                0;
            onChange({ gap: v, x: v, y: v, linked: true });
        } else {
            onChange({ linked: false });
        }
    };

    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between">
                <ControlLabel label={label || 'Dimensions'} />
                <button
                    type="button"
                    onClick={toggleLinked}
                    className={`p-1 rounded ${linked ? 'text-blue-600 bg-blue-50 dark:bg-blue-900/20' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'}`}
                    title={linked ? 'Unlink column and row gap' : 'Link column and row gap'}
                >
                    {linked ? <LinkIcon /> : <UnlinkIcon />}
                </button>
            </div>
            <div className="flex rounded-lg border px-1 border-gray-50 text-center bg-gray-50 dark:bg-gray-900 overflow-hidden">
                <div className="flex-1 border-r border-gray-200 dark:border-gray-700">
                    <input
                        type="number"
                        value={parseNumeric(linked ? gap : gapX)}
                        onChange={(e) => handleChange('x', e.target.value)}
                        className="w-full h-[30px] mt-2 mb-1 p-0 rounded-md border-gray-50 text-center text-sm bg-transparent text-gray-900 dark:text-gray-100 focus:outline-none focus:bg-blue-50 dark:focus:bg-blue-900/20"
                    />
                    <div className="text-[10px] text-center text-gray-400 dark:text-gray-500 pb-1 uppercase tracking-wide">Column</div>
                </div>
                <div className="flex-1">
                    <input
                        type="number"
                        value={parseNumeric(linked ? gap : gapY)}
                        onChange={(e) => handleChange('y', e.target.value)}
                        className="w-full h-[30px] mt-2 mb-1 p-0 rounded-md border-gray-50 text-center text-sm bg-transparent text-gray-900 dark:text-gray-100 focus:outline-none focus:bg-blue-50 dark:focus:bg-blue-900/20"
                    />
                    <div className="text-[10px] text-center text-gray-400 dark:text-gray-500 pb-1 uppercase tracking-wide">Row</div>
                </div>
            </div>
        </div>
    );
}
