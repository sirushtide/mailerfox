import React, { useState } from 'react';
import { Input, Select } from '../ui/primitives.jsx';
import { ColorInput } from '../ui/color.jsx';
import { TypographyPopover } from '../reusable/typography.jsx';
import { BorderControls, FourSideLinkedControl } from '../reusable/boxControls.jsx';
import { BackgroundPopover } from '../reusable/backgroundControls.jsx';

function StyleAccordionItem({ title, children, defaultOpen = false }) {
    const [open, setOpen] = useState(Boolean(defaultOpen));
    return (
        <div>
            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                className="w-full flex items-center justify-between py-3 text-left"
            >
                <span className="text-sm font-semibold text-gray-900 dark:text-gray-200">{title}</span>
                <span className="text-xs text-gray-500">{open ? '▾' : '▸'}</span>
            </button>
            {open ? <div>{children}</div> : null}
        </div>
    );
}

function CommonContentFields({ widget, onChange, showPlaceholder = true, showRows = false, showOptions = false, showCheckLabel = false, showName = true }) {
    const props = widget.props || {};
    return (
        <div className="space-y-3">
            {showName && (
                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Field Name</label>
                    <Input value={String(props.name || '')} onChange={(v) => onChange({ props: { ...props, name: v } })} />
                </div>
            )}

            <div className="grid grid-cols-2 gap-3 items-center">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Label</label>
                <Input value={String(props.label || '')} onChange={(v) => onChange({ props: { ...props, label: v } })} />
            </div>

            {showPlaceholder && (
                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Placeholder</label>
                    <Input value={String(props.placeholder || '')} onChange={(v) => onChange({ props: { ...props, placeholder: v } })} />
                </div>
            )}

            {showRows && (
                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Rows</label>
                    <Input
                        type="number"
                        value={String(props.rows ?? 4)}
                        onChange={(v) => onChange({ props: { ...props, rows: Number(v) || 4 } })}
                    />
                </div>
            )}

            {showCheckLabel && (
                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Checkbox Label</label>
                    <Input value={String(props.checkLabel || '')} onChange={(v) => onChange({ props: { ...props, checkLabel: v } })} />
                </div>
            )}

            {showOptions && (
                <div>
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Options (one per line)</label>
                    <textarea
                        className="block w-full rounded-md border border-gray-200 bg-white px-3 py-2 text-sm"
                        rows={4}
                        value={Array.isArray(props.options) ? props.options.map((o) => (typeof o === 'string' ? o : `${o.value}:${o.label}`)).join('\n') : ''}
                        onChange={(e) => {
                            const raw = e.target.value.split('\n').filter((l) => l.trim() !== '');
                            const parsed = raw.map((line) => {
                                const parts = line.split(':');
                                if (parts.length >= 2) {
                                    return { value: parts[0].trim(), label: parts.slice(1).join(':').trim() };
                                }
                                return { value: line.trim(), label: line.trim() };
                            });
                            onChange({ props: { ...props, options: parsed } });
                        }}
                    />
                </div>
            )}

            <div className="grid grid-cols-2 gap-3 items-center">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Required</label>
                <div className="flex justify-end">
                    <input
                        type="checkbox"
                        checked={Boolean(props.required)}
                        onChange={(e) => onChange({ props: { ...props, required: e.target.checked } })}
                        className="rounded border-gray-300"
                    />
                </div>
            </div>

            <div className="grid grid-cols-2 gap-3 items-center">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Help Text</label>
                <Input value={String(props.helpText || '')} onChange={(v) => onChange({ props: { ...props, helpText: v } })} />
            </div>
        </div>
    );
}

export function FormTextContentInspector({ widget, onChange }) {
    return <CommonContentFields widget={widget} onChange={onChange} showPlaceholder={true} />;
}

export function FormEmailContentInspector({ widget, onChange }) {
    return <CommonContentFields widget={widget} onChange={onChange} showPlaceholder={true} />;
}

export function FormTextareaContentInspector({ widget, onChange }) {
    return <CommonContentFields widget={widget} onChange={onChange} showPlaceholder={true} showRows={true} />;
}

export function FormSelectContentInspector({ widget, onChange }) {
    return <CommonContentFields widget={widget} onChange={onChange} showPlaceholder={false} showOptions={true} />;
}

export function FormCheckboxContentInspector({ widget, onChange }) {
    return <CommonContentFields widget={widget} onChange={onChange} showPlaceholder={false} showOptions={true} />;
}

export function FormRadioContentInspector({ widget, onChange }) {
    return <CommonContentFields widget={widget} onChange={onChange} showPlaceholder={false} showOptions={true} />;
}

export function FormSubmitContentInspector({ widget, onChange }) {
    const props = widget.props || {};
    return (
        <div className="space-y-3">
            <div className="grid grid-cols-2 gap-3 items-center">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Button Text</label>
                <Input value={String(props.text || '')} onChange={(v) => onChange({ props: { ...props, text: v } })} />
            </div>
            <div className="grid grid-cols-2 gap-3 items-center">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Full Width</label>
                <div className="flex justify-end">
                    <input
                        type="checkbox"
                        checked={Boolean(props.fullWidth)}
                        onChange={(e) => onChange({ props: { ...props, fullWidth: e.target.checked } })}
                        className="rounded border-gray-300"
                    />
                </div>
            </div>
        </div>
    );
}

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

export function FormFieldStyleInspector({ widget, onChange }) {
    const props = widget.props || {};

    return (
        <div className="border-y border-gray-200 dark:border-gray-700">
            <StyleAccordionItem title="Field" defaultOpen={true}>
                <div className="space-y-4 pb-3">
                    <div className="space-y-3">
                        <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Typography</div>
                        <TypographyPopover
                            withAlign={false}
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
                        <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Colors</div>
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text Color</label>
                            <div className="flex justify-end">
                                <ColorInput value={props.textColor || ''} onChange={(v) => onChange({ props: { ...props, textColor: v } })} />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Label Color</label>
                            <div className="flex justify-end">
                                <ColorInput value={props.labelColor || ''} onChange={(v) => onChange({ props: { ...props, labelColor: v } })} />
                            </div>
                        </div>
                    </div>

                    <div className="space-y-3">
                        <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Border</div>
                        <BorderControls
                            value={props}
                            onChange={(nextProps) => onChange({ props: nextProps })}
                            widthKeys={borderWidthKeys}
                            widthFallbackValue={props.borderWidthTop ?? props.borderWidth}
                        />
                        <FourSideLinkedControl
                            label="Border Radius"
                            value={props}
                            keys={borderRadiusKeys}
                            fallbackValue={props.borderRadiusTop ?? props.borderRadius}
                            onChange={(nextProps) => onChange({ props: nextProps })}
                        />
                    </div>

                    <BackgroundPopover
                        label="Background"
                        value={props}
                        onChange={(nextProps) => onChange({ props: nextProps })}
                        keys={{
                            type: 'backgroundType',
                            color: 'backgroundColor',
                            from: 'gradientFrom',
                            to: 'gradientTo',
                            fromLocation: 'gradientFromLocation',
                            toLocation: 'gradientToLocation',
                            gradientType: 'gradientType',
                            position: 'gradientPosition',
                        }}
                    />

                    <div className="space-y-3">
                        <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Spacing</div>
                        <FourSideLinkedControl
                            label="Padding"
                            value={props}
                            keys={paddingKeys}
                            fallbackValue={props.paddingTop ?? props.paddingY ?? props.paddingX}
                            onChange={(nextProps) => onChange({ props: nextProps })}
                        />
                        <FourSideLinkedControl
                            label="Margin"
                            value={props}
                            keys={marginKeys}
                            fallbackValue={props.marginTop ?? props.marginRight ?? props.marginBottom ?? props.marginLeft}
                            onChange={(nextProps) => onChange({ props: nextProps })}
                        />
                    </div>
                </div>
            </StyleAccordionItem>
        </div>
    );
}

export function FormSubmitStyleInspector({ widget, onChange }) {
    const props = widget.props || {};
    const [stateTab, setStateTab] = useState('normal');

    const bgKeysForState = (tab) => {
        if (tab === 'hover') {
            return {
                type: 'hoverBackgroundType',
                color: 'hoverBackgroundColor',
                from: 'hoverGradientFrom',
                to: 'hoverGradientTo',
                fromLocation: 'hoverGradientFromLocation',
                toLocation: 'hoverGradientToLocation',
                gradientType: 'hoverGradientType',
                position: 'hoverGradientPosition',
            };
        }
        if (tab === 'focus') {
            return {
                type: 'focusBackgroundType',
                color: 'focusBackgroundColor',
                from: 'focusGradientFrom',
                to: 'focusGradientTo',
                fromLocation: 'focusGradientFromLocation',
                toLocation: 'focusGradientToLocation',
                gradientType: 'focusGradientType',
                position: 'focusGradientPosition',
            };
        }
        return {
            type: 'backgroundType',
            color: 'backgroundColor',
            from: 'gradientFrom',
            to: 'gradientTo',
            fromLocation: 'gradientFromLocation',
            toLocation: 'gradientToLocation',
            gradientType: 'gradientType',
            position: 'gradientPosition',
        };
    };

    const textColorKey = stateTab === 'hover' ? 'hoverTextColor' : stateTab === 'focus' ? 'focusTextColor' : 'textColor';
    const bgKeys = bgKeysForState(stateTab);

    const colorsSegment = (
        <div className="flex items-center justify-between">
            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Colors</label>
            <div className="inline-flex overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                {['normal', 'hover', 'focus'].map((tab) => (
                    <button
                        key={tab}
                        type="button"
                        onClick={() => setStateTab(tab)}
                        className={`px-3 py-1.5 text-xs font-semibold capitalize ${
                            stateTab === tab
                                ? 'bg-gray-200 text-gray-900'
                                : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800'
                        } ${tab !== 'normal' ? 'border-l border-gray-200 dark:border-gray-700' : ''}`}
                    >
                        {tab}
                    </button>
                ))}
            </div>
        </div>
    );

    return (
        <div className="border-y border-gray-200 dark:border-gray-700">
            <StyleAccordionItem title="Button" defaultOpen={true}>
                <div className="space-y-4 pb-3">
                    <div className="space-y-3">
                        <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Typography</div>
                        <TypographyPopover
                            withAlign={false}
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
                        <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Border</div>
                        <BorderControls
                            value={props}
                            onChange={(nextProps) => onChange({ props: nextProps })}
                            widthKeys={borderWidthKeys}
                            widthFallbackValue={props.borderWidthTop ?? props.borderWidth}
                        />
                        <FourSideLinkedControl
                            label="Border Radius"
                            value={props}
                            keys={borderRadiusKeys}
                            fallbackValue={props.borderRadiusTop ?? props.borderRadius}
                            onChange={(nextProps) => onChange({ props: nextProps })}
                        />
                    </div>

                    <div className="space-y-3">
                        <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Spacing</div>
                        <FourSideLinkedControl
                            label="Padding"
                            value={props}
                            keys={paddingKeys}
                            fallbackValue={props.paddingTop ?? props.paddingY ?? props.paddingX}
                            onChange={(nextProps) => onChange({ props: nextProps })}
                        />
                        <FourSideLinkedControl
                            label="Margin"
                            value={props}
                            keys={marginKeys}
                            fallbackValue={props.marginTop ?? props.marginRight ?? props.marginBottom ?? props.marginLeft}
                            onChange={(nextProps) => onChange({ props: nextProps })}
                        />
                    </div>
                </div>
            </StyleAccordionItem>

            <StyleAccordionItem title="States" defaultOpen={false}>
                <div className="space-y-3 pb-3">
                    {colorsSegment}
                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text Color</label>
                        <div className="flex justify-end">
                            <ColorInput value={props[textColorKey] || ''} onChange={(v) => onChange({ props: { ...props, [textColorKey]: v } })} />
                        </div>
                    </div>
                    <BackgroundPopover label="Background" value={props} onChange={(nextProps) => onChange({ props: nextProps })} keys={bgKeys} />
                </div>
            </StyleAccordionItem>
        </div>
    );
}
