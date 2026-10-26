import React from 'react';
import { Input, Select } from '../ui/primitives.jsx';
import { ButtonContentInspector, ButtonStyleInspector } from './buttonInspectors.jsx';
import { InputStyleControls } from '../reusable/inputStyleControls.jsx';
import { makePrefixedWidget, makePrefixedOnChange, makePrefixedProps } from '../reusable/prefixedWidget.js';

function listOptionLabel(list) {
    if (!list) return '';
    if (typeof list.name === 'string' && list.name.trim()) return list.name;
    if (typeof list.display_name === 'string' && list.display_name.trim()) return list.display_name;
    return String(list.id || list.value || '');
}

export function NewsletterFormContentInspector({ widget, onChange, uploadUrl, lists = [] }) {
    const props = widget.props || {};

    const buttonProps = makePrefixedProps(props, 'button_');
    buttonProps.text = typeof buttonProps.text === 'string' && buttonProps.text.trim()
        ? buttonProps.text
        : (typeof props.buttonText === 'string' ? props.buttonText : 'Subscribe');
    const buttonWidget = { ...widget, props: buttonProps };
    const buttonOnChange = makePrefixedOnChange(onChange, 'button_', props);

    const listOptions = [
        { value: '', label: 'None / Default' },
        ...(Array.isArray(lists) ? lists.map((l) => ({ value: String(l.id || l.value || ''), label: listOptionLabel(l) })) : []),
    ];

    return (
        <div className="space-y-5">
            <div className='px-4'>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">List</label>
                <Select
                    value={String(props.listId || '')}
                    onChange={(v) => onChange({ props: { ...props, listId: v, list_id: v } })}
                    options={listOptions}
                />
            </div>
            <div className="px-4">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Placeholder</label>
                <Input value={String(props.placeholder || '')} onChange={(v) => onChange({ props: { ...props, placeholder: v } })} />
            </div>
            <div className="px-4">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Success Message</label>
                <Input value={String(props.successMessage || '')} onChange={(v) => onChange({ props: { ...props, successMessage: v } })} />
            </div>

            <div className="pt-4 mt-2 border-t border-gray-200 dark:border-gray-700">
                <label className="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-3 px-4">Button</label>
                <ButtonContentInspector widget={buttonWidget} onChange={buttonOnChange} uploadUrl={uploadUrl} />
            </div>
        </div>
    );
}

export function NewsletterFormStyleInspector({ widget, onChange, globals, uploadUrl }) {
    const props = widget.props || {};
    const buttonWidget = makePrefixedWidget(widget, 'button_');
    const buttonOnChange = makePrefixedOnChange(onChange, 'button_', props);

    return (
        <>
            <InputStyleControls
                widget={widget}
                onChange={onChange}
                prefix="input_"
                globals={globals}
                uploadUrl={uploadUrl}
            />
            <ButtonStyleInspector
                widget={buttonWidget}
                onChange={buttonOnChange}
            />
        </>
    );
}
