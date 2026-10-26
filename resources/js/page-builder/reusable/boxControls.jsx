import React from 'react';
import { Input, Select } from '../ui/primitives.jsx';
import { ColorInput } from '../ui/color.jsx';
import { patch4, set4Linked } from '../lib/boxModel.js';

export function FourSideLinkedControl({ label, value, onChange, keys, fallbackValue }) {
    const v = value || {};

    const isLinked = v[keys.linked] !== false;

    const sides = [
        { key: keys.t, label: 'TOP' },
        { key: keys.r, label: 'RIGHT' },
        { key: keys.b, label: 'BOTTOM' },
        { key: keys.l, label: 'LEFT' },
    ];

    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{label}</label>
                <button
                    type="button"
                    onClick={() => {
                        onChange(set4Linked(!isLinked, v, keys, fallbackValue));
                    }}
                    className={`p-1 rounded ${isLinked ? 'text-blue-600 bg-blue-50 dark:bg-blue-900/20' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'}`}
                    title={isLinked ? 'Unlink sides' : 'Link sides'}
                >
                    {isLinked ? (
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M9 17H7A5 5 0 0 1 7 7h2"/><path d="M15 7h2a5 5 0 1 1 0 10h-2"/><line x1="8" x2="16" y1="12" y2="12"/></svg>
                    ) : (
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M15 7h2a5 5 0 0 1 0 10h-2m-6 0H7A5 5 0 0 1 7 7h2"/></svg>
                    )}
                </button>
            </div>
            <div className="flex rounded-lg border px-1 border-gray-50 text-center bg-gray-50 dark:bg-gray-900 overflow-hidden">
                {sides.map((side, idx) => (
                    <div
                        key={side.key}
                        className={`flex-1 ${idx !== sides.length - 1 ? 'border-gray-200 dark:border-gray-700' : ''}`}
                    >
                        <input
                            type="number"
                            value={v[side.key] === undefined ? '' : String(v[side.key])}
                            onChange={(e) =>
                                onChange(
                                    patch4(v, keys, side.key, e.target.value === '' ? undefined : Number(e.target.value))
                                )
                            }
                            placeholder="0"
                            className="w-[55px] h-[30px] mt-2 mb-1 p-0 rounded-md border-gray-50 text-center text-sm bg-transparent text-gray-900 dark:text-gray-100 focus:outline-none focus:bg-blue-50 dark:focus:bg-blue-900/20"
                        />
                        <div className="text-[10px] text-center text-gray-400 dark:text-gray-500 pb-1 uppercase tracking-wide">
                            {side.label}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

export function BorderControls({ value, onChange, styleKey = 'borderStyle', colorKey = 'borderColor', widthKeys, widthFallbackValue }) {
    const v = value || {};

    const borderStyle = typeof v[styleKey] === 'string' ? v[styleKey] : '';

    return (
        <div className="space-y-3">
            <div>
                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Border Type</label>
                    <Select
                        value={borderStyle || 'default'}
                        onChange={(next) => {
                            onChange({ ...v, [styleKey]: next === 'default' ? '' : next });
                        }}
                        options={[
                            { value: 'default', label: 'Default' },
                            { value: 'solid', label: 'Solid' },
                            { value: 'dashed', label: 'Dashed' },
                            { value: 'dotted', label: 'Dotted' },
                        ]}
                    />
                </div>
            </div>

            {borderStyle ? (
                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Border Color</label>
                    <div className="flex justify-end">
                        <ColorInput
                            value={typeof v[colorKey] === 'string' ? v[colorKey] : ''}
                            onChange={(x) => onChange({ ...v, [colorKey]: x })}
                        />
                    </div>
                </div>
            ) : null}

            {widthKeys ? (
                <FourSideLinkedControl
                    label="Border Width"
                    value={v}
                    onChange={(next) => onChange(next)}
                    keys={widthKeys}
                    fallbackValue={widthFallbackValue}
                />
            ) : null}
        </div>
    );
}

export function LabeledNumberInput({ label, value, onChange, placeholder = '' }) {
    return (
        <div className="grid grid-cols-2 gap-3 items-center">
            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{label}</label>
            <div className="flex justify-end">
                <Input
                    type="number"
                    value={value === undefined ? '' : String(value)}
                    onChange={(raw) => {
                        if (raw === '' || raw === null || raw === undefined) {
                            onChange(undefined);
                            return;
                        }
                        const n = Number(raw);
                        onChange(Number.isFinite(n) ? n : undefined);
                    }}
                    placeholder={placeholder}
                    className="mt-0 w-24"
                />
            </div>
        </div>
    );
}
