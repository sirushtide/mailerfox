import React from 'react';
import { uid } from '../lib/uid.js';
import { Button, Input, Select } from '../ui/primitives.jsx';
import { StyleAccordionItem } from '../controls/accordion.jsx';

const TAG_OPTIONS = ['NEW', 'FIX', 'UPDATE', 'IMPROVED', 'DEPRECATED', 'REMOVED'];

function normalizeEvent(ev) {
    if (ev && typeof ev === 'object') {
        return {
            tag: typeof ev.tag === 'string' ? ev.tag : 'NEW',
            title: typeof ev.title === 'string' ? ev.title : '',
            description: typeof ev.description === 'string' ? ev.description : '',
        };
    }
    return { tag: 'NEW', title: '', description: '' };
}

function normalizeVersion(v) {
    if (v && typeof v === 'object') {
        return {
            version: typeof v.version === 'string' ? v.version : '',
            date: typeof v.date === 'string' ? v.date : '',
            events: Array.isArray(v.events) ? v.events.map(normalizeEvent) : [],
        };
    }
    return { version: '', date: '', events: [] };
}

function TrashIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M3 6h18" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
        </svg>
    );
}

function AddIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M12 5v14" /><path d="M5 12h14" />
        </svg>
    );
}

export function TextTimelineContentInspector({ widget, onChange }) {
    const props = widget.props || {};
    const items = Array.isArray(props.items) ? props.items.map(normalizeVersion) : [];

    const setItems = (next) => onChange({ props: { ...props, items: next } });

    const addVersion = () => {
        setItems([...items, { version: '', date: '', events: [{ tag: 'NEW', title: '', description: '' }] }]);
    };

    const removeVersion = (vIdx) => {
        setItems(items.filter((_, i) => i !== vIdx));
    };

    const updateVersion = (vIdx, patch) => {
        setItems(items.map((it, i) => i === vIdx ? { ...it, ...patch } : it));
    };

    const addEvent = (vIdx) => {
        const next = items.map((it, i) => {
            if (i !== vIdx) return it;
            return { ...it, events: [...it.events, { tag: 'NEW', title: '', description: '' }] };
        });
        setItems(next);
    };

    const removeEvent = (vIdx, eIdx) => {
        const next = items.map((it, i) => {
            if (i !== vIdx) return it;
            return { ...it, events: it.events.filter((_, j) => j !== eIdx) };
        });
        setItems(next);
    };

    const updateEvent = (vIdx, eIdx, patch) => {
        const next = items.map((it, i) => {
            if (i !== vIdx) return it;
            return { ...it, events: it.events.map((ev, j) => j === eIdx ? { ...ev, ...patch } : ev) };
        });
        setItems(next);
    };

    return (
        <>
            <StyleAccordionItem title="Timeline Versions" defaultOpen={true}>
                <div className="space-y-3 px-4">
                    {items.map((version, vIdx) => (
                        <div key={vIdx} className="border border-gray-200 dark:border-gray-700 rounded-lg p-3 space-y-3">
                            <div className="flex items-center justify-between">
                                <span className="text-xs font-medium text-gray-700 dark:text-gray-300">Version {vIdx + 1}</span>
                                <button type="button" onClick={() => removeVersion(vIdx)} className="text-red-600 hover:text-red-700 text-xs flex items-center gap-1">
                                    <TrashIcon /> Remove
                                </button>
                            </div>

                            <div className="grid grid-cols-2 gap-2">
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Version</label>
                                    <Input
                                        value={version.version}
                                        onChange={(v) => updateVersion(vIdx, { version: v })}
                                        placeholder="1.4.0"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Date</label>
                                    <Input
                                        value={version.date}
                                        onChange={(v) => updateVersion(vIdx, { date: v })}
                                        placeholder="May 7, 2026"
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <div className="text-xs font-medium text-gray-500">Events</div>
                                {version.events.map((event, eIdx) => (
                                    <div key={eIdx} className="bg-gray-50 dark:bg-gray-800/50 rounded-md p-2 space-y-2">
                                        <div className="flex items-center justify-between">
                                            <span className="text-xs text-gray-500">Event {eIdx + 1}</span>
                                            <button type="button" onClick={() => removeEvent(vIdx, eIdx)} className="text-red-500 hover:text-red-600">
                                                <TrashIcon />
                                            </button>
                                        </div>
                                        <div className="grid grid-cols-3 gap-2">
                                            <div>
                                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tag</label>
                                                <Select
                                                    value={event.tag}
                                                    onChange={(v) => updateEvent(vIdx, eIdx, { tag: v })}
                                                    options={TAG_OPTIONS.map((t) => ({ value: t, label: t }))}
                                                />
                                            </div>
                                            <div className="col-span-2">
                                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                                                <Input
                                                    value={event.title}
                                                    onChange={(v) => updateEvent(vIdx, eIdx, { title: v })}
                                                    placeholder="Event title"
                                                />
                                            </div>
                                        </div>
                                        <div>
                                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                            <textarea
                                                value={event.description}
                                                onChange={(e) => updateEvent(vIdx, eIdx, { description: e.target.value })}
                                                placeholder="Event description"
                                                rows={2}
                                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-2 py-1 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                                            />
                                        </div>
                                    </div>
                                ))}
                                <button
                                    type="button"
                                    onClick={() => addEvent(vIdx)}
                                    className="w-full py-1.5 rounded-md border border-dashed border-gray-300 dark:border-gray-600 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 flex items-center justify-center gap-1"
                                >
                                    <AddIcon /> Add Event
                                </button>
                            </div>
                        </div>
                    ))}

                    <button
                        type="button"
                        onClick={addVersion}
                        className="w-full py-2 rounded-md border border-dashed border-gray-300 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 flex items-center justify-center gap-1"
                    >
                        <AddIcon /> Add Version
                    </button>
                </div>
            </StyleAccordionItem>

            <StyleAccordionItem title="Load More" defaultOpen={false}>
                <div className="space-y-3 px-4">
                    <div className="flex items-center gap-2">
                        <input
                            type="checkbox"
                            checked={props.showLoadMore !== false}
                            onChange={(e) => onChange({ props: { ...props, showLoadMore: e.target.checked } })}
                            className="rounded border-gray-300"
                        />
                        <label className="text-xs text-gray-700 dark:text-gray-300">Show Load More button</label>
                    </div>
                    {props.showLoadMore !== false && (
                        <>
                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Button Text</label>
                                <Input
                                    value={props.loadMoreText || 'Read More'}
                                    onChange={(v) => onChange({ props: { ...props, loadMoreText: v } })}
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Versions Per Page</label>
                                <Input
                                    type="number"
                                    value={String(props.perPage || 20)}
                                    onChange={(v) => onChange({ props: { ...props, perPage: Number(v) || 20 } })}
                                />
                            </div>
                        </>
                    )}
                </div>
            </StyleAccordionItem>
        </>
    );
}
