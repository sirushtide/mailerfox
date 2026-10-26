import React from 'react';
import { Input } from '../ui/primitives.jsx';
import { FourSideLinkedControl } from './boxControls.jsx';

export function SpacingControls({
    value,
    onChange,
    paddingKeys,
    paddingFallbackValue,
    marginKeys,
    marginFallbackValue,
    marginTopKey = 'marginTop',
    marginBottomKey = 'marginBottom',
}) {
    const v = value || {};

    return (
        <div className="space-y-3">
            <FourSideLinkedControl
                label="Padding"
                value={v}
                keys={paddingKeys}
                fallbackValue={paddingFallbackValue}
                onChange={(next) => onChange(next)}
            />

            {marginKeys ? (
                <FourSideLinkedControl
                    label="Margin"
                    value={v}
                    keys={marginKeys}
                    fallbackValue={
                        marginFallbackValue ??
                        v[marginKeys.t] ??
                        v[marginKeys.r] ??
                        v[marginKeys.b] ??
                        v[marginKeys.l]
                    }
                    onChange={(next) => onChange(next)}
                />
            ) : (
                <div className="grid grid-cols-2 gap-3">
                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Margin Top (px)</label>
                        <Input
                            type="number"
                            value={v[marginTopKey] !== undefined ? String(v[marginTopKey]) : ''}
                            onChange={(raw) => {
                                const next = raw === '' || raw === null || raw === undefined ? undefined : Number(raw);
                                onChange({ ...v, [marginTopKey]: Number.isFinite(next) ? next : undefined });
                            }}
                            placeholder="0"
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Margin Bottom (px)</label>
                        <Input
                            type="number"
                            value={v[marginBottomKey] !== undefined ? String(v[marginBottomKey]) : ''}
                            onChange={(raw) => {
                                const next = raw === '' || raw === null || raw === undefined ? undefined : Number(raw);
                                onChange({ ...v, [marginBottomKey]: Number.isFinite(next) ? next : undefined });
                            }}
                            placeholder="0"
                        />
                    </div>
                </div>
            )}
        </div>
    );
}
