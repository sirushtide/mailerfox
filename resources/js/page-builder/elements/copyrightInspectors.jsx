import React from 'react';
import { Input, Select } from '../ui/primitives.jsx';

export function CopyrightContentInspector({ widget, onChange }) {
    const props = widget.props || {};

    return (
        <div className="space-y-3">
            <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text</label>
                <Input value={String(props.text || '')} onChange={(v) => onChange({ props: { ...props, text: v } })} placeholder="&copy; {{year}} {{site_name}}" />
                <p className="text-xs text-gray-500 mt-1">{'Use {{year}} and {{site_name}} as placeholders.'}</p>
            </div>
            <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Alignment</label>
                <Select
                    value={props.align || 'center'}
                    onChange={(v) => onChange({ props: { ...props, align: v } })}
                    options={[
                        { value: 'left', label: 'Left' },
                        { value: 'center', label: 'Center' },
                        { value: 'right', label: 'Right' },
                    ]}
                />
            </div>
        </div>
    );
}
