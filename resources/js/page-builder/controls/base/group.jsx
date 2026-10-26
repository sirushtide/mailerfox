import React, { useState } from 'react';
import { TypographyPopover } from '../../reusable/typography.jsx';
import { BackgroundControls } from '../../reusable/backgroundControls.jsx';
import { BorderControls, FourSideLinkedControl } from '../../reusable/boxControls.jsx';
import { BorderEffectControls } from '../../reusable/borderEffect.jsx';
import { ShadowPopover } from '../../reusable/shadow.jsx';
import { DimensionsControls } from '../../reusable/dimensionsControls.jsx';
import { SliderInput } from '../SliderInput.jsx';
import { ColorInput } from '../../ui/color.jsx';
import { Select } from '../../ui/primitives.jsx';
import { Popover, PopoverTriggerButton } from '../../ui/popover.jsx';
import { TabsControl } from './ui.jsx';
import { Switcher } from '../Switcher.jsx';
import { getValueByPath, setValueByPath, resolveColorToken } from '../bind.js';
import { GlobalColorControl, ChooseControl, WysiwygControl, MediaSourcesControl } from './data.jsx';
import { ControlsRenderer } from '../renderer.jsx';

function flattenBoundValue(value, bind) {
    if (!bind || typeof bind !== 'object') return value || {};
    const out = {};
    for (const key of Object.keys(bind)) {
        const path = bind[key];
        if (typeof path === 'string') {
            out[key] = getValueByPath(value, path);
        }
    }
    return out;
}

function patchBoundValue(value, bind, patch) {
    if (!bind || typeof bind !== 'object') return patch;
    const out = {};
    for (const key of Object.keys(patch)) {
        const path = bind[key];
        if (typeof path === 'string') {
            setValueByPath(out, path, patch[key]);
        } else {
            out[key] = patch[key];
        }
    }
    return out;
}

function ControlLabel({ label }) {
    return <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{label}</label>;
}

export function TypographyGroup({ label, value, onChange, bind, globals }) {
    const augmentedBind = React.useMemo(() => {
        const b = { ...(bind || {}) };
        for (const base of ['fontSize', 'align', 'lineHeight']) {
            const path = b[base];
            if (typeof path === 'string') {
                b[`${base}Tablet`] = `${path}Tablet`;
                b[`${base}Mobile`] = `${path}Mobile`;
            }
        }
        return b;
    }, [bind]);

    const flat = flattenBoundValue(value, augmentedBind);
    const globalId = bind?.global ? getValueByPath(value, bind.global) : undefined;
    const resolved = globalId ? resolveColorToken({ global: globalId }, globals) : null;

    return (
        <div className="space-y-2">
            {bind?.global ? (
                <div className="flex items-center justify-between">
                    <span className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Global</span>
                    <select
                        value={globalId || '__custom__'}
                        onChange={(e) => {
                            const id = e.target.value;
                            onChange({ [bind.global]: id === '__custom__' ? undefined : id });
                        }}
                        className="w-40 rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-2 py-1 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="__custom__">Custom</option>
                        {(globals?.typography || []).map((t) => (
                            <option key={t.id} value={t.id}>{t.label}</option>
                        ))}
                    </select>
                </div>
            ) : null}
            <TypographyPopover
                value={flat}
                onChange={(patch) => onChange(patchBoundValue(value, augmentedBind, patch))}
            />
        </div>
    );
}

export function BackgroundGroup({ label, value, onChange, bind, uploadUrl = '', allowImage = false }) {
    const flat = flattenBoundValue(value, bind);
    const imageUrl = flat.image && typeof flat.image === 'object' ? String(flat.image.url || '') : '';
    const hasBg = flat.type === 'gradient' || (flat.color && flat.color.trim()) || imageUrl !== '' || Number(flat.backdropBlur) > 0;

    return (
        <div className="flex items-center justify-between">
            <ControlLabel label={label} />
            <Popover
                width={340}
                trigger={
                    <PopoverTriggerButton
                        active={hasBg}
                        title="Edit background"
                        icon={
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil-icon lucide-pencil"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg>
                        }
                    />
                }
            >
                <BackgroundControls
                    value={flat}
                    onChange={(patch) => onChange(patchBoundValue(value, bind, patch))}
                    uploadUrl={uploadUrl}
                    allowImage={allowImage}
                />
            </Popover>
        </div>
    );
}

export function BorderGroup({ label, value, onChange, bind, includeRadius = true, radiusBind, scopeControl, globals }) {
    const flat = flattenBoundValue(value, bind);
    if (bind?.widthKeys && typeof bind.widthKeys === 'object') {
        Object.assign(flat, flattenBoundValue(value, bind.widthKeys));
    }
    if (bind?.styleKey) {
        flat[bind.styleKey] = getValueByPath(value, bind.styleKey);
    }
    if (bind?.colorKey) {
        flat[bind.colorKey] = getValueByPath(value, bind.colorKey);
    }
    const widthKeys = bind?.widthKeys
        ? {
            linked: 'linked',
            t: 't',
            r: 'r',
            b: 'b',
            l: 'l',
        }
        : {
        linked: 'borderWidthLinked',
        t: 'borderWidthTop',
        r: 'borderWidthRight',
        b: 'borderWidthBottom',
        l: 'borderWidthLeft',
        };
    const widthFallbackValue = bind?.widthFallbackValue ?? flat.borderWidthTop ?? flat.borderWidth ?? 0;
    const patchBind = bind?.widthKeys && typeof bind.widthKeys === 'object'
        ? {
            [bind.styleKey || 'borderStyle']: bind.styleKey || 'borderStyle',
            [bind.colorKey || 'borderColor']: bind.colorKey || 'borderColor',
            linked: bind.widthKeys.linked,
            t: bind.widthKeys.t,
            r: bind.widthKeys.r,
            b: bind.widthKeys.b,
            l: bind.widthKeys.l,
        }
        : {
            borderStyle: 'borderStyle',
            borderColor: 'borderColor',
            borderWidthLinked: 'borderWidthLinked',
            borderWidthTop: 'borderWidthTop',
            borderWidthRight: 'borderWidthRight',
            borderWidthBottom: 'borderWidthBottom',
            borderWidthLeft: 'borderWidthLeft',
        };

    const radiusValue = includeRadius && radiusBind
        ? (typeof radiusBind === 'object' ? flattenBoundValue(value, radiusBind).t : getValueByPath(value, radiusBind))
        : undefined;

    const hasBorder = Boolean(
        flat.borderStyle ||
        (flat.borderColor && flat.borderColor.trim()) ||
        (flat.borderWidthTop || flat.borderWidthRight || flat.borderWidthBottom || flat.borderWidthLeft ||
        flat.borderWidth || flat.t || flat.r || flat.b || flat.l)
    );
    const hasRadius = includeRadius && radiusValue !== undefined && radiusValue > 0;

    return (
        <div className="space-y-3">
            {scopeControl ? (
                <ChooseControl
                    label={scopeControl.label}
                    value={getValueByPath(value, scopeControl.bind) || ''}
                    options={scopeControl.options || []}
                    onChange={(next) => onChange(setValueByPath({}, scopeControl.bind, next))}
                />
            ) : null}
            <div className="flex items-center justify-between">
                <ControlLabel label={label} />
                <Popover
                    width={320}
                    trigger={
                        <PopoverTriggerButton
                            active={hasBorder || hasRadius}
                            title="Edit border"
                            icon={
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil-icon lucide-pencil"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg>
                            }
                        />
                    }
                >
                    <div className="space-y-3">
                        <BorderControls
                            value={flat}
                            onChange={(patch) => onChange(patchBoundValue(value, patchBind, patch))}
                            styleKey={bind?.styleKey || 'borderStyle'}
                            colorKey={bind?.colorKey || 'borderColor'}
                            widthKeys={widthKeys}
                            widthFallbackValue={widthFallbackValue}
                        />
                        {includeRadius ? (
                            <div>
                                <SliderInput
                                    label="Border Radius"
                                    value={radiusValue}
                                    onChange={(next) => {
                                        if (radiusBind && typeof radiusBind === 'object') {
                                            onChange(patchBoundValue(value, radiusBind, { linked: next, t: next, r: next, b: next, l: next }));
                                            return;
                                        }
                                        onChange(setValueByPath({}, radiusBind || 'borderRadius', next));
                                    }}
                                    kind="border-radius"
                                />
                            </div>
                        ) : null}
                    </div>
                </Popover>
            </div>
        </div>
    );
}

export function ShadowGroup({ label, value, onChange, bind, type = 'box' }) {
    const flat = flattenBoundValue(value, bind);
    return (
        <ShadowPopover
            value={flat}
            onChange={(patch) => onChange(patchBoundValue(value, bind, patch))}
            type={type}
            label={label}
        />
    );
}

export function SpacingGroup({ label, value, onChange, bind }) {
    const paddingFlat = bind?.padding ? flattenBoundValue(value, bind.padding) : (value || {});
    const marginFlat = bind?.margin ? flattenBoundValue(value, bind.margin) : (value || {});
    const paddingKeys = bind?.padding
        ? {
            linked: 'linked',
            t: 't',
            r: 'r',
            b: 'b',
            l: 'l',
        }
        : {
        linked: 'paddingLinked',
        t: 'paddingTop',
        r: 'paddingRight',
        b: 'paddingBottom',
        l: 'paddingLeft',
        };
    const marginKeys = bind?.margin
        ? {
            linked: 'linked',
            t: 't',
            r: 'r',
            b: 'b',
            l: 'l',
        }
        : {
        linked: 'marginLinked',
        t: 'marginTop',
        r: 'marginRight',
        b: 'marginBottom',
        l: 'marginLeft',
        };
    return (
        <div className="space-y-3">
            <FourSideLinkedControl
                label="Padding"
                value={paddingFlat}
                onChange={(patch) => onChange(patchBoundValue(value, bind?.padding || bind, patch))}
                keys={paddingKeys}
                fallbackValue={paddingFlat.t ?? 0}
            />
            <FourSideLinkedControl
                label="Margin"
                value={marginFlat}
                onChange={(patch) => onChange(patchBoundValue(value, bind?.margin || bind, patch))}
                keys={marginKeys}
                fallbackValue={marginFlat.t ?? 0}
            />
        </div>
    );
}

export function MarginsGroup({ label, value, onChange, bind }) {
    const flat = flattenBoundValue(value, bind);
    return (
        <FourSideLinkedControl
            label={label}
            value={flat}
            onChange={(patch) => onChange(patchBoundValue(value, bind, patch))}
            keys={{ linked: 'linked', t: 't', r: 'r', b: 'b', l: 'l' }}
            fallbackValue={flat.t ?? 0}
        />
    );
}

export function ImageDimensionsGroup({ label, value, onChange, bind }) {
    const flat = flattenBoundValue(value, bind);
    return (
        <DimensionsControls
            value={flat}
            onChange={(patch) => onChange(patchBoundValue(value, bind, patch))}
            widthKey="width"
            heightKey="height"
            useSliders={true}
        />
    );
}

export function DimensionsGroup({ label, value, onChange, bind }) {
    const flat = flattenBoundValue(value, bind);
    return (
        <DimensionsControls
            value={flat}
            onChange={(patch) => onChange(patchBoundValue(value, bind, patch))}
            widthKey="width"
            heightKey="height"
            useSliders={true}
        />
    );
}

export function StatesGroup({ label, value, onChange, bind, tabs = ['normal', 'hover', 'focus'], children, globals, renderItem }) {
    const [active, setActive] = useState('normal');
    const activeTab = tabs.includes(active) ? active : tabs[0];

    const mapChildForState = (child, state) => {
        if (!child) return null;
        const pick = (b) => (b && typeof b === 'object' && !Array.isArray(b) && state in b ? b[state] : b);
        return { ...child, bind: pick(child.bind), radiusBind: pick(child.radiusBind), condition: undefined };
    };

    const childArray = Array.isArray(children) ? children : React.Children.toArray(children);
    const childItems = childArray.map((child) =>
        child && typeof child === 'object' ? mapChildForState(child, activeTab) : null
    );

    return (
        <TabsControl tabs={tabs.map((t) => ({ value: t, label: t.charAt(0).toUpperCase() + t.slice(1) }))} activeTab={activeTab} onChange={setActive}>
            <div className="space-y-3">
                {typeof renderItem === 'function'
                    ? childItems.map((item, idx) => (item ? <div key={item.name || idx}>{renderItem(item)}</div> : null))
                    : childItems.map((item, idx) => (item ? <div key={item.name || idx}>{JSON.stringify(item)}</div> : null))}
            </div>
        </TabsControl>
    );
}

export function BorderEffectGroup({ label, value, onChange, bind }) {
    const flat = flattenBoundValue(value, bind);
    const hasEffect = flat.effect && flat.effect !== 'none';

    return (
        <div className="flex items-center justify-between">
            <ControlLabel label={label} />
            <Popover
                width={320}
                trigger={
                    <PopoverTriggerButton
                        active={hasEffect}
                        title="Edit animated border"
                        icon={
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil-icon lucide-pencil"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg>
                        }
                    />
                }
            >
                <BorderEffectControls
                    value={flat}
                    onChange={(patch) => onChange(patchBoundValue(value, bind, patch))}
                />
            </Popover>
        </div>
    );
}

export function RepeaterGroup({ label, value, onChange, bind, itemSchema, uploadUrl }) {
    const rawValue = typeof bind === 'string' ? getValueByPath(value, bind) : value;
    const items = Array.isArray(rawValue) ? rawValue : [];
    const accordion = Boolean(itemSchema.accordion);
    const [openIndexes, setOpenIndexes] = useState([]);

    const emit = (next) => {
        if (typeof bind === 'string') {
            onChange(setValueByPath({}, bind, next));
        } else {
            onChange(next);
        }
    };

    const addItem = () => {
        const newItem = itemSchema.defaultItem ? itemSchema.defaultItem() : {};
        emit([...items, newItem]);
        setOpenIndexes((prev) => [...prev, items.length]);
    };

    const updateItem = (idx, patch) => {
        const next = items.map((it, i) => {
            if (i !== idx) return it;
            if (it && typeof it === 'object') return { ...it, ...patch };
            const key = itemSchema.fields?.length === 1 ? itemSchema.fields[0].name : undefined;
            return key != null ? { [key]: patch[key] } : it;
        });
        emit(next);
    };

    const updatePrimitive = (idx, rawValue) => {
        const next = items.slice();
        next[idx] = rawValue;
        emit(next);
    };

    const removeItem = (idx) => {
        const next = items.filter((_, i) => i !== idx);
        emit(next);
        setOpenIndexes((prev) => prev.filter((i) => i !== idx).map((i) => (i > idx ? i - 1 : i)));
    };

    const duplicateItem = (idx) => {
        const source = items[idx];
        let copy;
        try {
            copy = JSON.parse(JSON.stringify(source));
        } catch (e) {
            copy = source && typeof source === 'object' ? { ...source } : source;
        }
        if (copy && typeof copy === 'object' && !Array.isArray(copy) && Object.prototype.hasOwnProperty.call(copy, 'id')) {
            copy.id = `item_${Date.now()}_${Math.random().toString(36).slice(2, 6)}`;
        }
        const next = [...items.slice(0, idx + 1), copy, ...items.slice(idx + 1)];
        emit(next);
        setOpenIndexes((prev) => [...prev.map((i) => (i > idx ? i + 1 : i)), idx + 1]);
    };

    const toggleItem = (idx) => {
        setOpenIndexes((prev) => (prev.includes(idx) ? prev.filter((i) => i !== idx) : [...prev, idx]));
    };

    const fieldValue = (field, item, isPrimitive) => {
        if (isPrimitive) return itemSchema.fields.length === 1 ? String(item || '') : '';
        return item && typeof item === 'object' ? (item[field.name] ?? '') : '';
    };

    const itemTitle = (item, idx) => {
        const first = itemSchema.fields?.[0];
        const raw = first ? String(fieldValue(first, item, typeof item !== 'object' || item === null) || '') : '';
        const plain = raw.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
        if (plain) return plain.length > 46 ? `${plain.slice(0, 46)}…` : plain;
        return `${itemSchema.itemLabel || 'Item'} ${idx + 1}`;
    };

    const renderField = (field, item, idx, isPrimitive) => {
        const current = fieldValue(field, item, isPrimitive);
        const setField = (v) => {
            if (isPrimitive) {
                updatePrimitive(idx, v);
                return;
            }
            updateItem(idx, { [field.name]: v });
        };

        if (field.type === 'wysiwyg') {
            return (
                <div key={field.name}>
                    <WysiwygControl label={field.label} value={current || ''} onChange={setField} />
                </div>
            );
        }

        if (field.type === 'media') {
            const urlKey = field.urlKey || 'url';
            const svgKey = field.svgKey || 'svg';
            const sourceKey = field.sourceKey || 'source';
            const obj = item && typeof item === 'object' ? item : {};
            return (
                <div key={field.name}>
                    <ControlLabel label={field.label} />
                    <MediaSourcesControl
                        value={{ url: obj[urlKey] || '', svg: obj[svgKey] || '', source: obj[sourceKey] || 'url' }}
                        bind={{ url: 'url', svg: 'svg', source: 'source' }}
                        onChange={(patch) => {
                            const next = {};
                            if ('url' in patch) next[urlKey] = patch.url;
                            if ('svg' in patch) next[svgKey] = patch.svg;
                            if ('source' in patch) next[sourceKey] = patch.source;
                            if (Object.keys(next).length) updateItem(idx, next);
                        }}
                        uploadUrl={uploadUrl}
                    />
                </div>
            );
        }

        if (field.type === 'textarea') {
            return (
                <div key={field.name}>
                    <ControlLabel label={field.label} />
                    <textarea
                        rows={field.rows || 3}
                        value={current || ''}
                        placeholder={field.placeholder}
                        onChange={(e) => setField(e.target.value)}
                        className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-2 py-1 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                    />
                </div>
            );
        }

        return (
            <div key={field.name}>
                <ControlLabel label={field.label} />
                <input
                    type="text"
                    value={current}
                    placeholder={field.placeholder}
                    onChange={(e) => setField(e.target.value)}
                    className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-2 py-1 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                />
            </div>
        );
    };

    return (
        <div className="space-y-2">
            {items.map((item, idx) => {
                const isPrimitive = typeof item !== 'object' || item === null;
                const isOpen = !accordion || openIndexes.includes(idx);
                return (
                    <div key={idx} className="border border-gray-200 dark:border-gray-700 rounded-lg">
                        <div className="flex items-center justify-between gap-2 px-3 py-2">
                            {accordion ? (
                                <button
                                    type="button"
                                    onClick={() => toggleItem(idx)}
                                    className="flex min-w-0 flex-1 items-center gap-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="14"
                                        height="14"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="2"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        className={`shrink-0 transition-transform ${isOpen ? 'rotate-90' : ''}`}
                                    >
                                        <path d="m9 18 6-6-6-6" />
                                    </svg>
                                    <span className="truncate">{itemTitle(item, idx)}</span>
                                </button>
                            ) : (
                                <span className="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                    {itemSchema.itemLabel || `Item ${idx + 1}`}
                                </span>
                            )}
                            <div className="flex shrink-0 items-center gap-1">
                                <button
                                    type="button"
                                    onClick={() => duplicateItem(idx)}
                                    className="inline-flex h-7 w-7 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                                    title="Duplicate"
                                    aria-label="Duplicate"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                        <rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
                                        <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => removeItem(idx)}
                                    className="inline-flex h-7 w-7 items-center justify-center rounded-md text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-900/20"
                                    title="Delete"
                                    aria-label="Delete"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                        <path d="M3 6h18" />
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                                        <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                        <line x1="10" x2="10" y1="11" y2="17" />
                                        <line x1="14" x2="14" y1="11" y2="17" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        {isOpen ? (
                            <div className="space-y-3 border-t border-gray-200 dark:border-gray-700 p-3">
                                {itemSchema.fields.map((field) => renderField(field, item, idx, isPrimitive))}
                            </div>
                        ) : null}
                    </div>
                );
            })}
            <button
                type="button"
                onClick={addItem}
                className="w-full py-2 rounded-md border border-dashed border-gray-300 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800"
            >
                + Add {itemSchema.itemLabel || 'Item'}
            </button>
        </div>
    );
}

export function ObjectGroup({ label, value, onChange, bind, schema, globals, uploadUrl }) {
    const obj = (typeof bind === 'string' ? getValueByPath(value, bind) : value) || {};

    const handleChildChange = (patch) => {
        const next = { ...obj, ...patch };
        if (typeof bind === 'string') {
            onChange(setValueByPath({}, bind, next));
        } else {
            onChange(next);
        }
    };

    return (
        <div className="space-y-3">
            {label ? <ControlLabel label={label} /> : null}
            <ControlsRenderer schema={schema} value={obj} onChange={handleChildChange} globals={globals} uploadUrl={uploadUrl} />
        </div>
    );
}

export function StackGroup({ label, children, renderItem, gap = '5' }) {
    const items = Array.isArray(children) ? children.filter(Boolean) : [];
    const gapClass = `space-y-${gap}`;

    return (
        <div className={gapClass}>
            {label ? <ControlLabel label={label} /> : null}
            {items.map((child, idx) => (
                <div key={child?.name || idx}>
                    {typeof renderItem === 'function' ? renderItem(child, idx) : null}
                </div>
            ))}
        </div>
    );
}

export function HighlightGroup({ label, value, onChange, bind, globals }) {
    const mode = getValueByPath(value, bind?.mode) || 'none';
    const animate = Boolean(getValueByPath(value, bind?.animate));
    const hasHighlight = mode !== 'none' && mode !== undefined;

    const setField = (path, val) => {
        onChange(setValueByPath({}, path, val));
    };

    return (
        <div className="flex items-center justify-between">
            <ControlLabel label={label} />
            <Popover
                width={300}
                trigger={
                    <PopoverTriggerButton
                        active={hasHighlight}
                        title="Edit highlight"
                        icon={
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil-icon lucide-pencil"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg>
                        }
                    />
                }
            >
                <div className="space-y-3">
                    <div>
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Mode</label>
                            <Select
                                value={mode}
                                onChange={(x) => setField(bind.mode, x)}
                                options={[
                                    { value: 'none', label: 'Off' },
                                    { value: 'color', label: 'Color' },
                                    { value: 'gradient', label: 'Gradient' },
                                ]}
                            />
                        </div>
                    </div>

                    {mode === 'color' ? (
                        <GlobalColorControl
                            label="Highlight Color"
                            value={getValueByPath(value, bind.color)}
                            onChange={(x) => setField(bind.color, x)}
                            globals={globals}
                        />
                    ) : null}

                    {mode === 'gradient' ? (
                        <>
                            <GlobalColorControl
                                label="From"
                                value={getValueByPath(value, bind.from)}
                                onChange={(x) => setField(bind.from, x)}
                                globals={globals}
                            />
                            <GlobalColorControl
                                label="To"
                                value={getValueByPath(value, bind.to)}
                                onChange={(x) => setField(bind.to, x)}
                                globals={globals}
                            />
                            <Switcher
                                label="Animate Gradient"
                                value={animate}
                                onChange={(x) => setField(bind.animate, x)}
                            />
                            {animate ? (
                                <div>
                                    <SliderInput
                                        label="Animation Duration"
                                        value={getValueByPath(value, bind.duration)}
                                        onChange={(x) => setField(bind.duration, x)}
                                        kind="duration"
                                    />
                                </div>
                            ) : null}
                        </>
                    ) : null}
                </div>
            </Popover>
        </div>
    );
}
