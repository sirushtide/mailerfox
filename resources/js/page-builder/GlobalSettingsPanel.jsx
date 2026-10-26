import React, { useState } from 'react';
import { ColorInput } from './ui/color.jsx';
import { Popover, PopoverTriggerButton } from './ui/popover.jsx';
import { TypographyControl } from './reusable/typography.jsx';
import { normalizeGlobals } from './globals/defaults.js';

function ColorRow({ color, onChange, onRemove }) {
    return (
        <div className="flex items-center gap-2 py-2 border-b border-gray-100 dark:border-gray-800 last:border-b-0">
            <ColorInput value={color.value} onChange={(v) => onChange({ ...color, value: v })} variant="minimal" />
            <input
                type="text"
                value={color.label}
                onChange={(e) => onChange({ ...color, label: e.target.value })}
                className="flex-1 min-w-0 text-sm rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
            />
            <span className="text-xs text-gray-400 font-mono uppercase">{color.value}</span>
            <button
                type="button"
                onClick={onRemove}
                className="text-gray-400 hover:text-red-600 p-1"
                title="Remove color"
                aria-label="Remove color"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
    );
}

function FontRow({ font, onChange, onRemove }) {
    const previewStyle = {
        fontFamily: font.fontFamily || 'inherit',
        fontWeight: font.fontWeight || 400,
    };

    return (
        <div className="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-800 last:border-b-0">
            <div className="min-w-0 flex-1">
                <div className="text-sm font-medium text-gray-800 dark:text-gray-200">{font.label}</div>
                <div className="text-xs text-gray-500 dark:text-gray-400 truncate" style={previewStyle}>
                    {font.fontFamily || 'Default'} · {font.fontWeight || 400} · {font.fontSize || 16}px
                </div>
            </div>
            <div className="flex items-center gap-1 flex-shrink-0">
                <Popover
                    width={340}
                    trigger={
                        <PopoverTriggerButton
                            title="Edit typography"
                            icon={
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                            }
                        />
                    }
                >
                    <TypographyControl value={font} onChange={onChange} withAlign={false} />
                </Popover>
                <button
                    type="button"
                    onClick={onRemove}
                    className="text-gray-400 hover:text-red-600 p-1"
                    title="Remove font"
                    aria-label="Remove font"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
        </div>
    );
}

export function GlobalSettingsPanel({ value, onChange }) {
    const globals = value || {};
    const resolved = normalizeGlobals(globals);
    const colors = Array.isArray(globals.colors) ? globals.colors : resolved.colors;
    const typography = Array.isArray(globals.typography) ? globals.typography : resolved.typography;

    const updateColors = (next) => onChange({ ...globals, colors: next });
    const updateTypography = (next) => onChange({ ...globals, typography: next });

    const addColor = () => {
        updateColors([...colors, { id: `color_${Date.now()}`, label: 'New Color', value: '#000000' }]);
    };

    const updateColor = (idx, next) => {
        const nextColors = colors.slice();
        nextColors[idx] = next;
        updateColors(nextColors);
    };

    const removeColor = (idx) => {
        updateColors(colors.filter((_, i) => i !== idx));
    };

    const addFont = () => {
        updateTypography([...typography, {
            id: `font_${Date.now()}`,
            label: 'New Font',
            fontFamily: 'Inter',
            fontWeight: '400',
            fontSize: 16,
            lineHeight: 1.6,
            letterSpacing: 0,
            textTransform: 'none',
        }]);
    };

    const updateFont = (idx, next) => {
        const nextFonts = typography.slice();
        nextFonts[idx] = { ...nextFonts[idx], ...next };
        updateTypography(nextFonts);
    };

    const removeFont = (idx) => {
        updateTypography(typography.filter((_, i) => i !== idx));
    };

    return (
        <div className="space-y-6">
            <div>
                <div className="flex items-center justify-between mb-3">
                    <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100">Global Colors</h3>
                    <button
                        type="button"
                        onClick={addColor}
                        className="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        Add Color
                    </button>
                </div>
                <div className="border border-gray-200 dark:border-gray-700 rounded-lg px-3">
                    {colors.length === 0 ? (
                        <div className="py-4 text-sm text-gray-500 dark:text-gray-400 text-center">No colors defined</div>
                    ) : (
                        colors.map((color, idx) => (
                            <ColorRow key={color.id || idx} color={color} onChange={(next) => updateColor(idx, next)} onRemove={() => removeColor(idx)} />
                        ))
                    )}
                </div>
            </div>

            <div>
                <div className="flex items-center justify-between mb-3">
                    <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100">Global Fonts</h3>
                    <button
                        type="button"
                        onClick={addFont}
                        className="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        Add Font
                    </button>
                </div>
                <div className="border border-gray-200 dark:border-gray-700 rounded-lg px-3">
                    {typography.length === 0 ? (
                        <div className="py-4 text-sm text-gray-500 dark:text-gray-400 text-center">No fonts defined</div>
                    ) : (
                        typography.map((font, idx) => (
                            <FontRow key={font.id || idx} font={font} onChange={(next) => updateFont(idx, next)} onRemove={() => removeFont(idx)} />
                        ))
                    )}
                </div>
            </div>
        </div>
    );
}
