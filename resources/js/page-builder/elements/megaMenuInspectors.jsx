import React from 'react';
import { Button, Input, Select } from '../ui/primitives.jsx';

export function MegaMenuContentInspector({ widget, onChange }) {
    const props = widget.props || {};
    const columns = Array.isArray(props.columns) ? props.columns : [];

    function updateColumns(next) {
        onChange({ props: { ...props, columns: next } });
    }

    function addColumn() {
        updateColumns([...columns, { category: '', items: [] }]);
    }

    function updateColumn(index, patch) {
        const next = columns.map((c, i) => (i === index ? { ...c, ...patch } : c));
        updateColumns(next);
    }

    function removeColumn(index) {
        const next = columns.filter((_, i) => i !== index);
        updateColumns(next);
    }

    function addItem(colIndex) {
        const col = columns[colIndex];
        const items = Array.isArray(col?.items) ? col.items : [];
        const next = columns.map((c, i) => (i === colIndex ? { ...c, items: [...items, { label: 'New Item', url: '#' }] } : c));
        updateColumns(next);
    }

    function updateItem(colIndex, itemIndex, patch) {
        const col = columns[colIndex];
        const items = Array.isArray(col?.items) ? col.items : [];
        const nextItems = items.map((it, i) => (i === itemIndex ? { ...it, ...patch } : it));
        const next = columns.map((c, i) => (i === colIndex ? { ...c, items: nextItems } : c));
        updateColumns(next);
    }

    function removeItem(colIndex, itemIndex) {
        const col = columns[colIndex];
        const items = Array.isArray(col?.items) ? col.items : [];
        const nextItems = items.filter((_, i) => i !== itemIndex);
        const next = columns.map((c, i) => (i === colIndex ? { ...c, items: nextItems } : c));
        updateColumns(next);
    }

    return (
        <div className="space-y-3">
            <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Trigger Label</label>
                <Input value={String(props.trigger_label || '')} onChange={(v) => onChange({ props: { ...props, trigger_label: v } })} />
            </div>
            <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Trigger URL</label>
                <Input value={String(props.trigger_url || '')} onChange={(v) => onChange({ props: { ...props, trigger_url: v } })} />
            </div>
            <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Panel Width</label>
                <Select
                    value={props.panel_width || 'boxed'}
                    onChange={(v) => onChange({ props: { ...props, panel_width: v } })}
                    options={[
                        { value: 'full', label: 'Full width' },
                        { value: 'boxed', label: 'From menu' },
                    ]}
                />
            </div>

            <div className="space-y-2">
                <div className="flex items-center justify-between">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Columns</label>
                    <Button type="button" variant="secondary" onClick={addColumn}>Add Column</Button>
                </div>
                {columns.map((col, cidx) => (
                    <div key={cidx} className="rounded-md border border-gray-200 p-2 space-y-2">
                        <div className="flex items-center gap-2">
                            <Input
                                value={String(col.category || '')}
                                onChange={(v) => updateColumn(cidx, { category: v })}
                                placeholder="Category header"
                                className="flex-1"
                            />
                            <button type="button" className="text-xs text-red-600" onClick={() => removeColumn(cidx)}>Remove</button>
                        </div>
                        <div className="ml-2 pl-2 border-l border-gray-200 space-y-2">
                            {Array.isArray(col.items) && col.items.map((item, iidx) => (
                                <div key={iidx} className="space-y-1">
                                    <div className="grid grid-cols-2 gap-2">
                                        <Input value={String(item.label || '')} onChange={(v) => updateItem(cidx, iidx, { label: v })} placeholder="Label" />
                                        <Input value={String(item.url || '')} onChange={(v) => updateItem(cidx, iidx, { url: v })} placeholder="URL" />
                                    </div>
                                    <Input value={String(item.description || '')} onChange={(v) => updateItem(cidx, iidx, { description: v })} placeholder="Description (optional)" />
                                    <div className="grid grid-cols-2 gap-2">
                                        <Input value={String(item.icon || '')} onChange={(v) => updateItem(cidx, iidx, { icon: v })} placeholder="Icon/SVG (optional)" />
                                        <Input value={String(item.image || '')} onChange={(v) => updateItem(cidx, iidx, { image: v })} placeholder="Image URL (optional)" />
                                    </div>
                                    <button type="button" className="text-xs text-red-600" onClick={() => removeItem(cidx, iidx)}>Remove Item</button>
                                </div>
                            ))}
                            <Button type="button" variant="secondary" onClick={() => addItem(cidx)}>Add Item</Button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
