import React, { useState } from 'react';
import { Input, Select } from '../ui/primitives.jsx';
import { ColorInput } from '../ui/color.jsx';
import { TypographyPopover } from '../reusable/typography.jsx';
import { BorderControls, FourSideLinkedControl } from '../reusable/boxControls.jsx';
import { BackgroundPopover } from '../reusable/backgroundControls.jsx';
import { BorderEffectControls } from '../reusable/borderEffect.jsx';
import { ImageUrlUploadControl } from '../reusable/imageUploadControl.jsx';

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

export function BadgeContentInspector({ widget, onChange, uploadUrl }) {
    const props = widget.props || {};
    const leading = ['none', 'label', 'dot', 'icon'].includes(props.leading) ? props.leading : 'label';

    return (
        <div className="space-y-3">
            <div className="grid grid-cols-2 gap-3 items-center">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text</label>
                <Input value={props.text || ''} onChange={(v) => onChange({ props: { ...props, text: v } })} />
            </div>

            <div className="grid grid-cols-2 gap-3 items-center">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Leading</label>
                <Select
                    value={leading}
                    onChange={(v) => onChange({ props: { ...props, leading: v } })}
                    options={[
                        { value: 'label', label: 'Label Chip' },
                        { value: 'dot', label: 'Status Dot' },
                        { value: 'icon', label: 'Icon' },
                        { value: 'none', label: 'None' },
                    ]}
                />
            </div>

            {leading === 'label' ? (
                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Label Text</label>
                    <Input value={props.labelText || ''} onChange={(v) => onChange({ props: { ...props, labelText: v } })} />
                </div>
            ) : null}

            {leading === 'icon' ? (
                <div className="space-y-3">
                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Icon URL</label>
                        <ImageUrlUploadControl
                            value={props.iconUrl || ''}
                            onChange={(v) => onChange({ props: { ...props, iconUrl: v } })}
                            uploadUrl={uploadUrl}
                            onUploaded={(url) => onChange({ props: { ...props, iconUrl: url } })}
                            placeholder="Enter image URL"
                            accept="image/svg+xml,image/png,image/jpeg,image/jpg,image/webp"
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Icon SVG</label>
                        <textarea
                            value={props.iconSvg || ''}
                            onChange={(e) => onChange({ props: { ...props, iconSvg: e.target.value } })}
                            rows={4}
                            className="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-sm"
                            placeholder="<svg>...</svg>"
                        />
                    </div>
                </div>
            ) : null}

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
        </div>
    );
}

export function BadgeStyleInspector({ widget, onChange }) {
    const props = widget.props || {};
    const leading = ['none', 'label', 'dot', 'icon'].includes(props.leading) ? props.leading : 'label';

    const paddingKeys = {
        linked: 'paddingLinked',
        t: 'paddingTop',
        r: 'paddingRight',
        b: 'paddingBottom',
        l: 'paddingLeft',
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
        <div className="border-y border-gray-200 dark:border-gray-700">
            <StyleAccordionItem title="Badge" defaultOpen={true}>
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
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text Color</label>
                            <div className="flex justify-end">
                                <ColorInput value={props.textColor || ''} onChange={(v) => onChange({ props: { ...props, textColor: v } })} />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Gap (px)</label>
                            <Input
                                type="number"
                                value={props.gap ?? 8}
                                onChange={(v) => onChange({ props: { ...props, gap: parseInt(v, 10) || 0 } })}
                            />
                        </div>
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
                        <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Border</div>
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Gradient Border</label>
                            <div className="flex justify-end">
                                <input
                                    type="checkbox"
                                    checked={Boolean(props.useGradientBorder)}
                                    onChange={(e) => onChange({ props: { ...props, useGradientBorder: e.target.checked } })}
                                    className="rounded border-gray-300"
                                />
                            </div>
                        </div>

                        {props.useGradientBorder ? (
                            <>
                                <div className="grid grid-cols-2 gap-3 items-center">
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">From</label>
                                    <div className="flex justify-end">
                                        <ColorInput value={props.borderGradientFrom || ''} onChange={(v) => onChange({ props: { ...props, borderGradientFrom: v } })} />
                                    </div>
                                </div>
                                <div className="grid grid-cols-2 gap-3 items-center">
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
                                    <div className="flex justify-end">
                                        <ColorInput value={props.borderGradientTo || ''} onChange={(v) => onChange({ props: { ...props, borderGradientTo: v } })} />
                                    </div>
                                </div>
                                <div className="grid grid-cols-2 gap-3 items-center">
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Width (px)</label>
                                    <Input
                                        type="number"
                                        value={props.borderWidthTop ?? 1.5}
                                        onChange={(v) => {
                                            const n = parseFloat(v);
                                            const val = Number.isFinite(n) ? n : 0;
                                            onChange({ props: { ...props, borderWidthTop: val, borderWidthRight: val, borderWidthBottom: val, borderWidthLeft: val } });
                                        }}
                                    />
                                </div>
                            </>
                        ) : (
                            <BorderControls
                                value={props}
                                onChange={(nextProps) => onChange({ props: nextProps })}
                                widthKeys={borderWidthKeys}
                                widthFallbackValue={props.borderWidthTop ?? props.borderWidth}
                            />
                        )}

                        <FourSideLinkedControl
                            label="Border Radius"
                            value={props}
                            keys={borderRadiusKeys}
                            fallbackValue={props.borderRadiusTop ?? props.borderRadius}
                            onChange={(nextProps) => onChange({ props: nextProps })}
                        />

                        <div className="space-y-3 pt-2 border-t border-gray-200 dark:border-gray-700">
                            <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Animated Border</div>
                            <BorderEffectControls value={props} onChange={(nextProps) => onChange({ props: nextProps })} />
                        </div>
                    </div>

                    <div className="space-y-3">
                        <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Spacing</div>
                        <FourSideLinkedControl
                            label="Padding"
                            value={props}
                            keys={paddingKeys}
                            fallbackValue={props.paddingTop}
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

            {leading === 'label' ? (
                <StyleAccordionItem title="Label Chip" defaultOpen={false}>
                    <div className="space-y-3 pb-3">
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Use Gradient</label>
                            <div className="flex justify-end">
                                <input
                                    type="checkbox"
                                    checked={Boolean(props.labelUseGradient)}
                                    onChange={(e) => onChange({ props: { ...props, labelUseGradient: e.target.checked } })}
                                    className="rounded border-gray-300"
                                />
                            </div>
                        </div>
                        {props.labelUseGradient ? (
                            <>
                                <div className="grid grid-cols-2 gap-3 items-center">
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Chip From</label>
                                    <div className="flex justify-end">
                                        <ColorInput value={props.labelGradientFrom || ''} onChange={(v) => onChange({ props: { ...props, labelGradientFrom: v } })} />
                                    </div>
                                </div>
                                <div className="grid grid-cols-2 gap-3 items-center">
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Chip To</label>
                                    <div className="flex justify-end">
                                        <ColorInput value={props.labelGradientTo || ''} onChange={(v) => onChange({ props: { ...props, labelGradientTo: v } })} />
                                    </div>
                                </div>
                            </>
                        ) : (
                            <div className="grid grid-cols-2 gap-3 items-center">
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Chip Background</label>
                                <div className="flex justify-end">
                                    <ColorInput value={props.labelBg || ''} onChange={(v) => onChange({ props: { ...props, labelBg: v } })} />
                                </div>
                            </div>
                        )}
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Chip Text Color</label>
                            <div className="flex justify-end">
                                <ColorInput value={props.labelColor || ''} onChange={(v) => onChange({ props: { ...props, labelColor: v } })} />
                            </div>
                        </div>
                    </div>
                </StyleAccordionItem>
            ) : null}

            {leading === 'dot' ? (
                <StyleAccordionItem title="Status Dot" defaultOpen={false}>
                    <div className="space-y-3 pb-3">
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Dot Color</label>
                            <div className="flex justify-end">
                                <ColorInput value={props.dotColor || ''} onChange={(v) => onChange({ props: { ...props, dotColor: v } })} />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Dot Size (px)</label>
                            <Input
                                type="number"
                                value={props.dotSize ?? 8}
                                onChange={(v) => onChange({ props: { ...props, dotSize: parseInt(v, 10) || 0 } })}
                            />
                        </div>
                    </div>
                </StyleAccordionItem>
            ) : null}

            {leading === 'icon' ? (
                <StyleAccordionItem title="Icon" defaultOpen={false}>
                    <div className="space-y-3 pb-3">
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Icon Color</label>
                            <div className="flex justify-end">
                                <ColorInput value={props.iconColor || ''} onChange={(v) => onChange({ props: { ...props, iconColor: v } })} />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Icon Size (px)</label>
                            <Input
                                type="number"
                                value={props.iconSize ?? 16}
                                onChange={(v) => onChange({ props: { ...props, iconSize: parseInt(v, 10) || 16 } })}
                            />
                        </div>
                    </div>
                </StyleAccordionItem>
            ) : null}
        </div>
    );
}
