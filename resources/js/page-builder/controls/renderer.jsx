import React, { useContext, useMemo } from 'react';
import * as CONTROL from './types.js';
import { BuilderModeContext, BuilderViewportContext } from './builderMode.js';
import {
    TextControl,
    TextareaControl,
    NumberControl,
    UrlControl,
    HiddenControl,
    SelectControl,
    ChooseControl,
    SwitcherControl,
    ColorControl,
    GlobalColorControl,
    GlobalFontControl,
    MediaControl,
    MediaSourcesControl,
    CodeControl,
    WysiwygControl,
} from './base/data.jsx';
import { SliderControl, DimensionsControl, GapBoxControl } from './base/unit.jsx';
import {
    HeadingControl,
    DividerControl,
    NoticeControl,
    RawHtmlControl,
    PopoverToggleControl,
    TabsControl,
    DeviceSwitcher,
    DeviceIcons,
    ColumnsControl,
} from './base/ui.jsx';
import {
    TypographyGroup,
    BackgroundGroup,
    BorderGroup,
    ShadowGroup,
    SpacingGroup,
    MarginsGroup,
    ImageDimensionsGroup,
    DimensionsGroup,
    StatesGroup,
    BorderEffectGroup,
    RepeaterGroup,
    ObjectGroup,
    HighlightGroup,
    StackGroup,
} from './base/group.jsx';
import { evaluateCondition, getConditionValues } from './condition.js';
import { getValueByPath, setValueByPath, createBoundPatch } from './bind.js';
import { StyleAccordionItem } from './accordion.jsx';

function normalizeDottedPatch(patch) {
    if (!patch || typeof patch !== 'object' || Array.isArray(patch)) return patch;
    const normalized = {};
    let hasDotted = false;
    for (const key of Object.keys(patch)) {
        if (typeof key === 'string' && key.includes('.')) {
            hasDotted = true;
            setValueByPath(normalized, key, patch[key]);
        } else {
            normalized[key] = patch[key];
        }
    }
    return hasDotted ? normalized : patch;
}

const controlMap = {
    [CONTROL.CONTROL_TEXT]: TextControl,
    [CONTROL.CONTROL_TEXTAREA]: TextareaControl,
    [CONTROL.CONTROL_NUMBER]: NumberControl,
    [CONTROL.CONTROL_URL]: UrlControl,
    [CONTROL.CONTROL_HIDDEN]: HiddenControl,
    [CONTROL.CONTROL_SELECT]: SelectControl,
    [CONTROL.CONTROL_CHOOSE]: ChooseControl,
    [CONTROL.CONTROL_SWITCHER]: SwitcherControl,
    [CONTROL.CONTROL_COLOR]: ColorControl,
    [CONTROL.CONTROL_GLOBAL_COLOR]: GlobalColorControl,
    [CONTROL.CONTROL_GLOBAL_FONT]: GlobalFontControl,
    [CONTROL.CONTROL_MEDIA]: MediaControl,
    [CONTROL.CONTROL_MEDIA_SOURCES]: MediaSourcesControl,
    [CONTROL.CONTROL_CODE]: CodeControl,
    [CONTROL.CONTROL_WYSIWYG]: WysiwygControl,
    [CONTROL.CONTROL_SLIDER]: SliderControl,
    [CONTROL.CONTROL_DIMENSIONS]: DimensionsControl,
    [CONTROL.CONTROL_GAP_BOX]: GapBoxControl,
    [CONTROL.CONTROL_HEADING]: HeadingControl,
    [CONTROL.CONTROL_DIVIDER]: DividerControl,
    [CONTROL.CONTROL_NOTICE]: NoticeControl,
    [CONTROL.CONTROL_RAW_HTML]: RawHtmlControl,
    [CONTROL.CONTROL_POPOVER_TOGGLE]: PopoverToggleControl,
    [CONTROL.CONTROL_TABS]: TabsControl,
    [CONTROL.CONTROL_DEVICE]: DeviceSwitcher,
    [CONTROL.CONTROL_COLUMNS]: ColumnsControl,
};

const groupMap = {
    typography: TypographyGroup,
    background: BackgroundGroup,
    border: BorderGroup,
    box_shadow: ShadowGroup,
    text_shadow: (props) => <ShadowGroup {...props} type="text" />,
    spacing: SpacingGroup,
    margin: MarginsGroup,
    image_dimensions: ImageDimensionsGroup,
    dimensions: DimensionsGroup,
    states: StatesGroup,
    border_effect: BorderEffectGroup,
    repeater: RepeaterGroup,
    object: ObjectGroup,
    highlight: HighlightGroup,
    stack: StackGroup,
};

function useConditionValues(schema, value) {
    return useMemo(() => {
        const base = getConditionValues(schema, value);
        if (value && typeof value === 'object') {
            const flatten = (obj, prefix = '') => {
                for (const key of Object.keys(obj)) {
                    const path = prefix ? `${prefix}.${key}` : key;
                    const v = obj[key];
                    if (base[key] === undefined) base[key] = v;
                    if (v && typeof v === 'object' && !Array.isArray(v)) {
                        flatten(v, path);
                    }
                }
            };
            flatten(value);
        }
        return base;
    }, [schema, value]);
}

function suffixBind(bind, suffix) {
    if (typeof bind === 'string') return `${bind}${suffix}`;
    if (bind && typeof bind === 'object' && !Array.isArray(bind)) {
        const out = {};
        for (const key of Object.keys(bind)) out[key] = suffixBind(bind[key], suffix);
        return out;
    }
    return bind;
}

function renderControlItem(item, { value, onChange, globals, uploadUrl, device }, index) {
    if (!item || typeof item !== 'object') return null;

    if (item.responsive && device && device !== 'desktop') {
        const suffix = device === 'tablet' ? 'Tablet' : 'Mobile';
        item = {
            ...item,
            bind: suffixBind(item.bind, suffix),
            radiusBind: item.radiusBind ? suffixBind(item.radiusBind, suffix) : item.radiusBind,
        };
    }

    const Control = controlMap[item.type];
    if (!Control) {
        if (item.type === CONTROL.CONTROL_GROUP) {
            const Group = groupMap[item.kind];
            if (!Group) return null;
            return (
                <Group
                    key={item.name || `${item.kind}-${index ?? 0}`}
                    label={item.label}
                    value={value}
                    onChange={(patch) => onChange(patch)}
                    bind={item.bind}
                    globals={globals}
                    uploadUrl={uploadUrl}
                    allowImage={Boolean(item.bind && typeof item.bind === 'object' && item.bind.image)}
                    includeRadius={item.includeRadius}
                    radiusBind={item.radiusBind}
                    scopeControl={item.scopeControl}
                    type={item.shadowType}
                    tabs={item.tabs}
                    children={item.children}
                    itemSchema={item.itemSchema}
                    renderItem={(child, childIdx) => renderControlItem(child, { value, onChange, globals, uploadUrl }, childIdx)}
                />
            );
        }
        return null;
    }

    if (Control === DimensionsControl || Control === GapBoxControl) {
        const handleDimensionsChange = (patch) => {
            if (item.bind && typeof item.bind === 'object') {
                const mapped = {};
                for (const key of Object.keys(patch)) {
                    const path = item.bind[key];
                    if (typeof path === 'string') {
                        setValueByPath(mapped, path, patch[key]);
                    }
                }
                onChange(mapped);
                return;
            }
            onChange(patch);
        };
        return (
            <Control
                key={item.name || `${item.type}-${index ?? 0}`}
                label={item.label}
                value={value}
                onChange={handleDimensionsChange}
                bind={item.bind}
                kind={item.kind}
            />
        );
    }

    if (Control === MediaSourcesControl) {
        const handleSourcesChange = (patch) => {
            const mapped = {};
            for (const key of Object.keys(patch)) {
                const path = item.bind && typeof item.bind === 'object' ? item.bind[key] : null;
                if (typeof path === 'string') {
                    setValueByPath(mapped, path, patch[key]);
                }
            }
            onChange(mapped);
        };
        return (
            <MediaSourcesControl
                key={item.name || `${item.type}-${index ?? 0}`}
                label={item.label}
                value={value}
                bind={item.bind}
                onChange={handleSourcesChange}
                uploadUrl={uploadUrl}
            />
        );
    }

    if (Control === ColumnsControl) {
        return (
            <ColumnsControl
                key={item.name || `${item.type}-${index ?? 0}`}
                label={item.label}
                value={value}
                onChange={onChange}
            />
        );
    }

    const currentValue =
        typeof item.bind === 'string'
            ? getValueByPath(value, item.bind)
            : item.bind && typeof item.bind === 'object'
                ? getValueByPath(value, item.bind[item.state || 'normal'] || Object.values(item.bind)[0])
                : undefined;

    const handleChange = (next) => {
        if (typeof item.bind === 'string') {
            onChange(setValueByPath({}, item.bind, next));
            return;
        }
        if (item.bind && typeof item.bind === 'object') {
            onChange(createBoundPatch(item.bind, next));
            return;
        }
        onChange(next);
    };

    const props = {
        label: item.label,
        value: currentValue,
        onChange: handleChange,
        options: item.options,
        placeholder: item.placeholder,
        kind: item.kind,
        units: item.units,
        defaultUnit: item.defaultUnit,
        min: item.min,
        max: item.max,
        step: item.step,
        rows: item.rows,
        language: item.language,
        uploadUrl,
        globals,
    };

    const rendered = <Control key={item.name || `${item.type}-${index ?? 0}`} {...props} />;

    if (item.responsive) {
        return (
            <div key={item.name || `${item.type}-${index ?? 0}`}>
                <div className="flex items-center justify-end mb-1">
                    <DeviceIcons />
                </div>
                {rendered}
            </div>
        );
    }

    return rendered;
}

function SchemaItems({ schema, value, onChange, globals, uploadUrl }) {
    const conditionValues = useConditionValues(schema, value);
    const builderMode = useContext(BuilderModeContext);
    const viewportCtx = useContext(BuilderViewportContext);
    const device = viewportCtx && typeof viewportCtx.viewport === 'string' ? viewportCtx.viewport : 'desktop';

    return (
        <div className="space-y-5">
            {schema.map((item, idx) => {
                if (!item || typeof item !== 'object') return null;
                if (Array.isArray(item.modes) && !item.modes.includes(builderMode)) return null;

                if (!item.type && item.title && Array.isArray(item.children)) {
                    return (
                        <StyleAccordionItem key={item.title || idx} title={item.title} defaultOpen={item.defaultOpen !== false}>
                            <SchemaItems schema={item.children} value={value} onChange={onChange} globals={globals} uploadUrl={uploadUrl} />
                        </StyleAccordionItem>
                    );
                }

                if (item.condition && !evaluateCondition(item.condition, conditionValues)) return null;
                if (item.conditions && !evaluateCondition(item.conditions, conditionValues)) return null;

                if (item.type === CONTROL.CONTROL_POPOVER_TOGGLE) {
                    return (
                        <div key={item.name || idx} className="px-4">
                            <PopoverToggleControl label={item.label} defaultOpen={item.defaultOpen}>
                                <SchemaItems schema={item.children} value={value} onChange={onChange} globals={globals} uploadUrl={uploadUrl} />
                            </PopoverToggleControl>
                        </div>
                    );
                }

                if (item.type === CONTROL.CONTROL_TABS) {
                    return (
                        <div key={item.name || idx} className="px-4">
                            <TabsControl tabs={item.tabs}>
                                <SchemaItems schema={item.children} value={value} onChange={onChange} globals={globals} uploadUrl={uploadUrl} />
                            </TabsControl>
                        </div>
                    );
                }

                return <div key={item.name || idx} className="px-4">{renderControlItem(item, { value, onChange, globals, uploadUrl, device }, idx)}</div>;
            })}
        </div>
    );
}

export function ControlsRenderer({ schema, value, onChange, globals, uploadUrl }) {
    if (!schema || !Array.isArray(schema) || schema.length === 0) return null;

    const handleChange = (patch) => onChange(normalizeDottedPatch(patch));

    return (
        <SchemaItems schema={schema} value={value} onChange={handleChange} globals={globals} uploadUrl={uploadUrl} />
    );
}

export function AccordionControlsRenderer({ schema, value, onChange, globals, uploadUrl }) {
    return (
        <div className="space-y-3">
            {schema.map((section, idx) => {
                if (!section || typeof section !== 'object') return null;
                return (
                    <StyleAccordionItem key={section.title || idx} title={section.title} defaultOpen={section.defaultOpen !== false}>
                        <ControlsRenderer schema={section.children} value={value} onChange={onChange} globals={globals} uploadUrl={uploadUrl} />
                    </StyleAccordionItem>
                );
            })}
        </div>
    );
}
