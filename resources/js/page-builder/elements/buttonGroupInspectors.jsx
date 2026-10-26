import React, { useState } from 'react';
import { Input, Select } from '../ui/primitives.jsx';
import { ButtonContentInspector, ButtonStyleInspector } from './buttonInspectors.jsx';
import { expandButtonGroupProps } from './buttonGroup.jsx';

function ButtonTabs({ active, onChange }) {
    return (
        <div className="flex items-center border-t border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
            {[
                { value: 'btn1', label: 'Button 1' },
                { value: 'btn2', label: 'Button 2' },
            ].map((tab, idx, arr) => (
                <button
                    key={tab.value}
                    type="button"
                    onClick={() => onChange(tab.value)}
                    className={`flex-1 h-8 text-xs font-medium transition-colors ${
                        idx !== arr.length - 1 ? 'border-r border-gray-200 dark:border-gray-700' : ''
                    } ${
                        active === tab.value
                            ? 'bg-gray-100 text-gray-900 dark:bg-white/10 dark:text-white'
                            : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5'
                    }`}
                >
                    {tab.label}
                </button>
            ))}
        </div>
    );
}

function makePrefixedWidget(widget, prefix) {
    const props = widget.props || {};
    const prefixedProps = {};
    for (const [k, v] of Object.entries(props)) {
        if (k.startsWith(prefix)) {
            prefixedProps[k.slice(prefix.length)] = v;
        }
    }
    return { ...widget, props: prefixedProps };
}

function makePrefixedOnChange(onChange, prefix, allProps) {
    return (patch) => {
        const newProps = { ...allProps };
        for (const [k, v] of Object.entries(patch.props || {})) {
            newProps[prefix + k] = v;
        }
        onChange({ props: newProps });
    };
}

export function ButtonGroupContentInspector({ widget, onChange, uploadUrl, megaMenus, globals }) {
    const props = expandButtonGroupProps(widget.props || {});
    const [activeButton, setActiveButton] = useState('btn1');

    return (
        <div className="space-y-4">
            <div className="px-4 space-y-3">
                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Group Layout</div>
                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Align</label>
                    <Select
                        value={props.align || 'left'}
                        onChange={(v) => onChange({ props: { ...props, align: v } })}
                        options={[
                            { value: 'left', label: 'Left' },
                            { value: 'center', label: 'Center' },
                            { value: 'right', label: 'Right' },
                        ]}
                    />
                </div>
                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Direction</label>
                    <Select
                        value={props.direction || 'horizontal'}
                        onChange={(v) => onChange({ props: { ...props, direction: v } })}
                        options={[
                            { value: 'horizontal', label: 'Horizontal' },
                            { value: 'vertical', label: 'Vertical' },
                        ]}
                    />
                </div>
                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Gap (px)</label>
                    <Input
                        type="number"
                        value={props.gap ?? 16}
                        onChange={(v) => onChange({ props: { ...props, gap: parseInt(v, 10) || 0 } })}
                    />
                </div>
            </div>

            <ButtonTabs active={activeButton} onChange={setActiveButton} />

            {activeButton === 'btn1' ? (
                <ButtonContentInspector
                    widget={makePrefixedWidget({ ...widget, props }, 'btn1_')}
                    onChange={makePrefixedOnChange(onChange, 'btn1_', props)}
                    uploadUrl={uploadUrl}
                />
            ) : (
                <ButtonContentInspector
                    widget={makePrefixedWidget({ ...widget, props }, 'btn2_')}
                    onChange={makePrefixedOnChange(onChange, 'btn2_', props)}
                    uploadUrl={uploadUrl}
                />
            )}
        </div>
    );
}

export function ButtonGroupStyleInspector({ widget, onChange, uploadUrl, megaMenus, globals }) {
    const props = expandButtonGroupProps(widget.props || {});
    const [activeButton, setActiveButton] = useState('btn1');

    return (
        <div className="space-y-4">
            <div className="flex items-center border-t border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
                {[
                    { value: 'btn1', label: 'Button 1' },
                    { value: 'btn2', label: 'Button 2' },
                ].map((tab, idx, arr) => (
                    <button
                        key={tab.value}
                        type="button"
                        onClick={() => setActiveButton(tab.value)}
                        className={`flex-1 h-8 text-xs font-medium transition-colors ${
                            idx !== arr.length - 1 ? 'border-r border-gray-200 dark:border-gray-700' : ''
                        } ${
                            activeButton === tab.value
                                ? 'bg-gray-100 text-gray-900 dark:bg-white/10 dark:text-white'
                                : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5'
                        }`}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>

            {activeButton === 'btn1' ? (
                <ButtonStyleInspector
                    widget={makePrefixedWidget({ ...widget, props }, 'btn1_')}
                    onChange={makePrefixedOnChange(onChange, 'btn1_', props)}
                />
            ) : (
                <ButtonStyleInspector
                    widget={makePrefixedWidget({ ...widget, props }, 'btn2_')}
                    onChange={makePrefixedOnChange(onChange, 'btn2_', props)}
                />
            )}
        </div>
    );
}
