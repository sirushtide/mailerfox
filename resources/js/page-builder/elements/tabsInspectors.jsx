import React from 'react';
import { uid } from '../lib/uid.js';
import { Button, Input, Select } from '../ui/primitives.jsx';
import { ColorInput } from '../ui/color.jsx';
import { TypographyPopover } from '../reusable/typography.jsx';
import { BackgroundPopover } from '../reusable/backgroundControls.jsx';
import { FourSideLinkedControl } from '../reusable/boxControls.jsx';
import { SpacingControls } from '../reusable/spacingControls.jsx';

export function TabsContentInspector({ widget, onChange }) {
    const props = widget.props || {};
    const items = Array.isArray(props.items) ? props.items : [];

    const setItems = (nextItems) => {
        onChange({ props: { ...props, items: nextItems } });
    };

    const updateItem = (idx, patch) => {
        const next = items.slice();
        const curr = next[idx] && typeof next[idx] === 'object' ? next[idx] : { title: '', content: '' };
        next[idx] = { ...curr, ...patch };
        setItems(next);
    };

    const removeItem = (idx) => {
        setItems(items.filter((_, i) => i !== idx));
    };

    const moveItem = (idx, dir) => {
        const next = items.slice();
        const j = idx + dir;
        if (j < 0 || j >= next.length) return;
        const tmp = next[idx];
        next[idx] = next[j];
        next[j] = tmp;
        setItems(next);
    };

    const orientation = ['horizontal', 'vertical', 'timeline'].includes(props.orientation) ? props.orientation : 'horizontal';

    return (
        <div className="space-y-4">
            <div className="space-y-3">
                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Layout</div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Orientation</label>
                    <Select
                        value={orientation}
                        onChange={(v) => onChange({ props: { ...props, orientation: v } })}
                        options={[
                            { value: 'horizontal', label: 'Horizontal' },
                            { value: 'vertical', label: 'Vertical' },
                            { value: 'timeline', label: 'Timeline' },
                        ]}
                    />
                </div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Default Active Tab</label>
                    <Input
                        type="number"
                        value={props.activeIndex !== undefined ? String(props.activeIndex) : '0'}
                        onChange={(raw) => {
                            const n = raw === '' ? 0 : Number(raw);
                            onChange({ props: { ...props, activeIndex: Number.isFinite(n) ? Math.max(0, Math.round(n)) : 0 } });
                        }}
                        min="0"
                    />
                </div>
            </div>

            <div className="space-y-3">
                <div className="flex items-center justify-between">
                    <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tab Items</div>
                    <Button
                        type="button"
                        onClick={() => setItems([...items, { id: uid('tab'), title: `Tab ${items.length + 1}`, content: '', widgets: [] }])}
                        className="px-2"
                        title="Add"
                        aria-label="Add"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M12 5v14" />
                            <path d="M5 12h14" />
                        </svg>
                    </Button>
                </div>

                {items.length === 0 ? <div className="text-sm text-gray-500">No items yet.</div> : null}

                <div className="space-y-3">
                    {items.map((it, idx) => (
                        <div key={idx} className="rounded-md border border-gray-200 dark:border-gray-700 p-3 space-y-3">
                            <div className="flex items-center justify-between gap-2">
                                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Item {idx + 1}</div>
                                <div className="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        onClick={() => moveItem(idx, -1)}
                                        disabled={idx === 0}
                                        className="px-2"
                                        title="Move up"
                                        aria-label="Move up"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>
                                    </Button>
                                    <Button
                                        type="button"
                                        onClick={() => moveItem(idx, 1)}
                                        disabled={idx === items.length - 1}
                                        className="px-2"
                                        title="Move down"
                                        aria-label="Move down"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>
                                    </Button>
                                    <Button
                                        type="button"
                                        className="text-red-600 dark:text-red-400 px-2"
                                        onClick={() => removeItem(idx)}
                                        title="Remove"
                                        aria-label="Remove"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M10 11v6"/><path d="M14 11v6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </Button>
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                                <Input value={(it && typeof it.title === 'string') ? it.title : ''} onChange={(v) => updateItem(idx, { title: v })} />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Content</label>
                                <textarea
                                    value={(it && typeof it.content === 'string') ? it.content : ''}
                                    onChange={(e) => updateItem(idx, { content: e.target.value })}
                                    rows={3}
                                    className="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm placeholder-gray-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-400"
                                />
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}

export function TabsStyleInspector({ widget, onChange }) {
    const props = widget.props || {};

    const paddingKeys = {
        linked: 'paddingLinked',
        t: 'paddingTop',
        r: 'paddingRight',
        b: 'paddingBottom',
        l: 'paddingLeft',
        legacyX: 'paddingX',
        legacyY: 'paddingY',
    };

    const marginKeys = {
        linked: 'marginLinked',
        t: 'marginTop',
        r: 'marginRight',
        b: 'marginBottom',
        l: 'marginLeft',
    };

    const borderWidthKeys = {
        linked: 'borderWidthLinked',
        t: 'borderWidthTop',
        r: 'borderWidthRight',
        b: 'borderWidthBottom',
        l: 'borderWidthLeft',
        legacy: 'borderWidth',
    };

    const borderRadiusKeys = {
        linked: 'borderRadiusLinked',
        t: 'borderRadiusTop',
        r: 'borderRadiusRight',
        b: 'borderRadiusBottom',
        l: 'borderRadiusLeft',
        legacy: 'borderRadius',
    };

    return (
        <div className="space-y-4">
            <div className="space-y-3">
                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Layout</div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tab Gap (px)</label>
                    <Input
                        type="number"
                        value={props.tabGap !== undefined ? String(props.tabGap) : ''}
                        onChange={(raw) => {
                            const n = raw === '' ? undefined : Number(raw);
                            onChange({ props: { ...props, tabGap: Number.isFinite(n) ? Math.max(0, Math.round(n)) : undefined } });
                        }}
                        placeholder="0"
                    />
                </div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tab Padding X (px)</label>
                    <Input
                        type="number"
                        value={props.tabPaddingX !== undefined ? String(props.tabPaddingX) : ''}
                        onChange={(raw) => {
                            const n = raw === '' ? undefined : Number(raw);
                            onChange({ props: { ...props, tabPaddingX: Number.isFinite(n) ? Math.max(0, Math.round(n)) : undefined } });
                        }}
                        placeholder="16"
                    />
                </div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tab Padding Y (px)</label>
                    <Input
                        type="number"
                        value={props.tabPaddingY !== undefined ? String(props.tabPaddingY) : ''}
                        onChange={(raw) => {
                            const n = raw === '' ? undefined : Number(raw);
                            onChange({ props: { ...props, tabPaddingY: Number.isFinite(n) ? Math.max(0, Math.round(n)) : undefined } });
                        }}
                        placeholder="10"
                    />
                </div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Content Padding (px)</label>
                    <Input
                        type="number"
                        value={props.contentPadding !== undefined ? String(props.contentPadding) : ''}
                        onChange={(raw) => {
                            const n = raw === '' ? undefined : Number(raw);
                            onChange({ props: { ...props, contentPadding: Number.isFinite(n) ? Math.max(0, Math.round(n)) : undefined } });
                        }}
                        placeholder="16"
                    />
                </div>

                <SpacingControls
                    value={props}
                    onChange={(nextProps) => onChange({ props: nextProps })}
                    paddingKeys={paddingKeys}
                    paddingFallbackValue={props.paddingTop ?? props.paddingY ?? props.paddingX}
                    marginKeys={marginKeys}
                    marginFallbackValue={props.marginTop ?? props.marginRight ?? props.marginBottom ?? props.marginLeft}
                />
            </div>

            <BackgroundPopover label="Background" value={props} onChange={(nextProps) => onChange({ props: nextProps })} />

            <div className="space-y-3">
                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Border</div>

                <FourSideLinkedControl
                    label="Border Radius"
                    value={props}
                    keys={borderRadiusKeys}
                    fallbackValue={props.borderRadiusTop ?? props.borderRadius}
                    onChange={(nextProps) => onChange({ props: nextProps })}
                />

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Border Style</label>
                    <Select
                        value={props.borderStyle || 'default'}
                        onChange={(v) => onChange({ props: { ...props, borderStyle: v } })}
                        options={[
                            { value: 'default', label: 'Default' },
                            { value: 'solid', label: 'Solid' },
                            { value: 'dashed', label: 'Dashed' },
                            { value: 'dotted', label: 'Dotted' },
                        ]}
                    />
                </div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Border Color</label>
                    <div className="flex justify-end">
                        <ColorInput value={props.borderColor || ''} onChange={(v) => onChange({ props: { ...props, borderColor: v } })} />
                    </div>
                </div>
            </div>

            <div className="space-y-3">
                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tab Typography</div>
                <TypographyPopover
                    value={{
                        fontFamily: props.fontFamily,
                        fontSize: props.fontSize,
                        fontWeight: props.fontWeight,
                        lineHeight: props.lineHeight,
                        letterSpacing: props.letterSpacing,
                        wordSpacing: props.wordSpacing,
                    }}
                    onChange={(next) =>
                        onChange({
                            props: {
                                ...props,
                                fontFamily: next.fontFamily,
                                fontSize: next.fontSize === '' ? undefined : next.fontSize,
                                fontWeight: next.fontWeight,
                                lineHeight: next.lineHeight === '' ? undefined : next.lineHeight,
                                letterSpacing: next.letterSpacing === '' ? undefined : next.letterSpacing,
                                wordSpacing: next.wordSpacing === '' ? undefined : next.wordSpacing,
                            },
                        })
                    }
                />
            </div>

            <div className="space-y-3">
                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tab Colors</div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text Color</label>
                    <div className="flex justify-end">
                        <ColorInput value={props.tabTextColor || ''} onChange={(v) => onChange({ props: { ...props, tabTextColor: v } })} fallback="#6b7280" />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Background</label>
                    <div className="flex justify-end">
                        <ColorInput value={props.tabBackgroundColor || ''} onChange={(v) => onChange({ props: { ...props, tabBackgroundColor: v } })} fallback="transparent" />
                    </div>
                </div>
            </div>

            <div className="space-y-3">
                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Active Tab Colors</div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text Color</label>
                    <div className="flex justify-end">
                        <ColorInput value={props.activeTabTextColor || ''} onChange={(v) => onChange({ props: { ...props, activeTabTextColor: v } })} fallback="#111827" />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Background</label>
                    <div className="flex justify-end">
                        <ColorInput value={props.activeTabBackgroundColor || ''} onChange={(v) => onChange({ props: { ...props, activeTabBackgroundColor: v } })} fallback="transparent" />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Indicator Color</label>
                    <div className="flex justify-end">
                        <ColorInput value={props.activeTabBorderColor || ''} onChange={(v) => onChange({ props: { ...props, activeTabBorderColor: v } })} fallback="#2563eb" />
                    </div>
                </div>
            </div>

            <div className="space-y-3">
                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Content Colors</div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text Color</label>
                    <div className="flex justify-end">
                        <ColorInput value={props.contentTextColor || ''} onChange={(v) => onChange({ props: { ...props, contentTextColor: v } })} fallback="#374151" />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Background</label>
                    <div className="flex justify-end">
                        <ColorInput value={props.contentBackgroundColor || ''} onChange={(v) => onChange({ props: { ...props, contentBackgroundColor: v } })} fallback="transparent" />
                    </div>
                </div>
            </div>

            <div className="space-y-3">
                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tab List Divider</div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Show Divider</label>
                    <div className="flex justify-end">
                        <input
                            type="checkbox"
                            checked={props.tabListBorder !== false}
                            onChange={(e) => onChange({ props: { ...props, tabListBorder: e.target.checked } })}
                            className="rounded border-gray-300"
                        />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Divider Color</label>
                    <div className="flex justify-end">
                        <ColorInput value={props.tabListBorderColor || ''} onChange={(v) => onChange({ props: { ...props, tabListBorderColor: v } })} fallback="#e5e7eb" />
                    </div>
                </div>
            </div>

            <div className="space-y-3">
                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Timeline</div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Connector Color</label>
                    <div className="flex justify-end">
                        <ColorInput value={props.timelineConnectorColor || ''} onChange={(v) => onChange({ props: { ...props, timelineConnectorColor: v } })} fallback="#e5e7eb" />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Dot Color</label>
                    <div className="flex justify-end">
                        <ColorInput value={props.timelineDotColor || ''} onChange={(v) => onChange({ props: { ...props, timelineDotColor: v } })} fallback="#2563eb" />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Dot Size (px)</label>
                    <Input
                        type="number"
                        value={props.timelineDotSize !== undefined ? String(props.timelineDotSize) : ''}
                        onChange={(raw) => {
                            const n = raw === '' ? undefined : Number(raw);
                            onChange({ props: { ...props, timelineDotSize: Number.isFinite(n) ? Math.max(4, Math.round(n)) : undefined } });
                        }}
                        placeholder="12"
                    />
                </div>
            </div>
        </div>
    );
}
