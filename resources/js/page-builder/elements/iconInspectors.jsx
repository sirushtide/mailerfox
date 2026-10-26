import React from 'react';
import { Input, Select } from '../ui/primitives.jsx';
import { ColorInput } from '../ui/color.jsx';
import { ShadowPopover } from '../reusable/shadow.jsx';
import { TypographyPopover } from '../reusable/typography.jsx';
import { BackgroundPopover } from '../reusable/backgroundControls.jsx';
import { BorderControls, FourSideLinkedControl } from '../reusable/boxControls.jsx';
import { SpacingControls } from '../reusable/spacingControls.jsx';
import { ImageUrlUploadControl } from '../reusable/imageUploadControl.jsx';

function StyleAccordionItem({ title, children, defaultOpen = false }) {
    const [open, setOpen] = React.useState(Boolean(defaultOpen));

    return (
        <div className="border-b border-gray-200 dark:border-gray-700 last:border-b-0">
            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                className="w-full flex items-center justify-between px-4 py-3 text-left"
            >
                <span className="text-sm font-semibold text-gray-900 dark:text-gray-200">{title}</span>
                <span className="text-xs text-gray-500">{open ? '▾' : '▸'}</span>
            </button>
            {open ? <div className="px-4">{children}</div> : null}
        </div>
    );
}

export function IconContentInspector({ widget, onChange, uploadUrl }) {
    const props = widget.props || {};

    return (
        <div className="border-y border-gray-200 dark:border-gray-700">
            <StyleAccordionItem title="Icon" defaultOpen={true}>
                <div className="space-y-3 pb-3">
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

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Icon Position</label>
                            <Select
                                value={props.iconPosition || 'top'}
                                onChange={(v) => onChange({ props: { ...props, iconPosition: v } })}
                                options={[
                                    { value: 'top', label: 'Top' },
                                    { value: 'left', label: 'Left' },
                                    { value: 'right', label: 'Right' },
                                ]}
                            />
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

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Icon Size (px)</label>
                            <Input
                                type="number"
                                value={props.iconSize || 64}
                                onChange={(v) => onChange({ props: { ...props, iconSize: parseInt(v) || 64 } })}
                                min="16"
                                max="256"
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Icon Gap (px)</label>
                            <Input
                                type="number"
                                value={props.iconGap || 16}
                                onChange={(v) => onChange({ props: { ...props, iconGap: parseInt(v) || 16 } })}
                                min="0"
                                max="100"
                            />
                        </div>
                    </div>
                </div>
            </StyleAccordionItem>

            <StyleAccordionItem title="Text" defaultOpen={false}>
                <div className="space-y-3 pb-3">
                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Heading</label>
                        <Input value={props.heading || ''} onChange={(v) => onChange({ props: { ...props, heading: v } })} />
                    </div>

                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Subtext</label>
                        <textarea
                            value={props.subtext || ''}
                            onChange={(e) => onChange({ props: { ...props, subtext: e.target.value } })}
                            rows={2}
                            className="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-sm"
                            placeholder="Subtext"
                        />
                    </div>
                </div>
            </StyleAccordionItem>
        </div>
    );
}

export function IconStyleInspector({ widget, onChange }) {
    const props = widget.props || {};

    const iconPaddingKeys = {
        linked: 'iconPaddingLinked',
        t: 'iconPaddingTop',
        r: 'iconPaddingRight',
        b: 'iconPaddingBottom',
        l: 'iconPaddingLeft',
    };

    const iconBorderRadiusKeys = {
        linked: 'iconBorderRadiusLinked',
        t: 'iconBorderRadiusTop',
        r: 'iconBorderRadiusRight',
        b: 'iconBorderRadiusBottom',
        l: 'iconBorderRadiusLeft',
        legacy: 'iconBorderRadius',
    };

    const iconBorderWidthKeys = {
        linked: 'iconBorderWidthLinked',
        t: 'iconBorderWidthTop',
        r: 'iconBorderWidthRight',
        b: 'iconBorderWidthBottom',
        l: 'iconBorderWidthLeft',
    };

    const paddingKeys = {
        linked: 'paddingLinked',
        t: 'paddingTop',
        r: 'paddingRight',
        b: 'paddingBottom',
        l: 'paddingLeft',
        legacyX: 'paddingX',
        legacyY: 'paddingY',
    };

    const borderRadiusKeys = {
        linked: 'borderRadiusLinked',
        t: 'borderRadiusTop',
        r: 'borderRadiusRight',
        b: 'borderRadiusBottom',
        l: 'borderRadiusLeft',
        legacy: 'borderRadius',
    };

    const borderWidthKeys = {
        linked: 'borderWidthLinked',
        t: 'borderWidthTop',
        r: 'borderWidthRight',
        b: 'borderWidthBottom',
        l: 'borderWidthLeft',
        legacy: 'borderWidth',
    };

    const headingMarginKeys = {
        linked: 'headingMarginLinked',
        t: 'headingMarginTop',
        r: 'headingMarginRight',
        b: 'headingMarginBottom',
        l: 'headingMarginLeft',
    };

    const subtextMarginKeys = {
        linked: 'subtextMarginLinked',
        t: 'subtextMarginTop',
        r: 'subtextMarginRight',
        b: 'subtextMarginBottom',
        l: 'subtextMarginLeft',
    };

    return (
        <div className="border-y border-gray-200 dark:border-gray-700">
            <StyleAccordionItem title="Icon" defaultOpen={true}>
                <div className="space-y-3 pb-3">
                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Icon Color</label>
                        <div className="flex justify-end">
                            <ColorInput value={props.iconColor || ''} onChange={(v) => onChange({ props: { ...props, iconColor: v } })} />
                        </div>
                    </div>

                    <BackgroundPopover
                        label="Icon Background"
                        active={props.iconBackgroundType === 'gradient' || (props.iconBackgroundColor && props.iconBackgroundColor.trim())}
                    >
                        <div className="space-y-3">
                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Background Type</label>
                                <Select
                                    value={props.iconBackgroundType || 'color'}
                                    onChange={(v) => onChange({ props: { ...props, iconBackgroundType: v } })}
                                    options={[
                                        { value: 'color', label: 'Color' },
                                        { value: 'gradient', label: 'Gradient' },
                                    ]}
                                />
                            </div>

                            {props.iconBackgroundType === 'gradient' ? (
                                <div className="space-y-3">
                                    <div className="grid grid-cols-2 gap-3 items-center">
                                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">From</label>
                                        <div className="flex justify-end">
                                            <ColorInput
                                                value={props.iconGradientFrom || ''}
                                                onChange={(v) => onChange({ props: { ...props, iconGradientFrom: v } })}
                                            />
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-2 gap-3 items-center">
                                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
                                        <div className="flex justify-end">
                                            <ColorInput
                                                value={props.iconGradientTo || ''}
                                                onChange={(v) => onChange({ props: { ...props, iconGradientTo: v } })}
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                                        <Select
                                            value={(props.iconGradientType === 'radial' || props.iconGradientType === 'linear') ? props.iconGradientType : 'linear'}
                                            onChange={(v) =>
                                                onChange({
                                                    props: {
                                                        ...props,
                                                        iconGradientType: v,
                                                        iconGradientPosition: v === 'radial' ? (props.iconGradientPosition || 'top center') : props.iconGradientPosition,
                                                    },
                                                })
                                            }
                                            options={[
                                                { value: 'linear', label: 'Linear' },
                                                { value: 'radial', label: 'Radial' },
                                            ]}
                                        />
                                    </div>
                                    {props.iconGradientType === 'radial' ? (
                                        <div>
                                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Position</label>
                                            <Select
                                                value={typeof props.iconGradientPosition === 'string' && props.iconGradientPosition ? props.iconGradientPosition : 'top center'}
                                                onChange={(v) => onChange({ props: { ...props, iconGradientPosition: v } })}
                                                options={[
                                                    { value: 'top left', label: 'Top Left' },
                                                    { value: 'top center', label: 'Top Center' },
                                                    { value: 'top right', label: 'Top Right' },
                                                    { value: 'center left', label: 'Center Left' },
                                                    { value: 'center center', label: 'Center' },
                                                    { value: 'center right', label: 'Center Right' },
                                                    { value: 'bottom left', label: 'Bottom Left' },
                                                    { value: 'bottom center', label: 'Bottom Center' },
                                                    { value: 'bottom right', label: 'Bottom Right' },
                                                ]}
                                            />
                                        </div>
                                    ) : (
                                        <div className="grid grid-cols-2 gap-3 items-center">
                                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Angle</label>
                                            <Input
                                                type="number"
                                                value={String(props.iconGradientAngle ?? 135)}
                                                onChange={(v) => onChange({ props: { ...props, iconGradientAngle: Number(v) } })}
                                            />
                                        </div>
                                    )}
                                </div>
                            ) : (
                                <div className="grid grid-cols-2 gap-3 items-center">
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Background Color</label>
                                    <div className="flex justify-end">
                                        <ColorInput
                                            value={props.iconBackgroundColor || ''}
                                            onChange={(v) => onChange({ props: { ...props, iconBackgroundColor: v } })}
                                        />
                                    </div>
                                </div>
                            )}
                        </div>
                    </BackgroundPopover>

                    <FourSideLinkedControl
                        label="Icon Padding"
                        value={props}
                        onChange={(nextProps) => onChange({ props: nextProps })}
                        keys={iconPaddingKeys}
                        fallbackValue={props.iconPaddingTop}
                    />

                    <FourSideLinkedControl
                        label="Icon Border Radius"
                        value={props}
                        onChange={(nextProps) => onChange({ props: nextProps })}
                        keys={iconBorderRadiusKeys}
                        fallbackValue={props.iconBorderRadiusTop ?? props.iconBorderRadius}
                    />

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Icon Border Color</label>
                        <div className="flex justify-end">
                            <ColorInput
                                value={props.iconBorderColor || ''}
                                onChange={(v) => onChange({ props: { ...props, iconBorderColor: v } })}
                            />
                        </div>
                    </div>

                    <FourSideLinkedControl
                        label="Icon Border Width"
                        value={props}
                        onChange={(nextProps) => onChange({ props: nextProps })}
                        keys={iconBorderWidthKeys}
                        fallbackValue={props.iconBorderWidthTop}
                    />

                    <ShadowPopover
                        label="Icon Shadow"
                        type="text"
                        value={{
                            color: props.shadowColor,
                            offsetX: props.shadowX,
                            offsetY: props.shadowY,
                            blur: props.shadowBlur,
                        }}
                        onChange={(v) =>
                            onChange({
                                props: {
                                    ...props,
                                    shadowColor: v.color,
                                    shadowX: v.offsetX,
                                    shadowY: v.offsetY,
                                    shadowBlur: v.blur,
                                },
                            })
                        }
                    />
                </div>
            </StyleAccordionItem>

            <StyleAccordionItem title="Text" defaultOpen={false}>
                <div className="space-y-3 pb-3">
                    <div>
                        <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Heading Typography</div>
                        <TypographyPopover
                            withAlign={false}
                            value={{
                                fontFamily: props.headingFontFamily || '',
                                fontSize: props.headingFontSize,
                                fontWeight: props.headingFontWeight || '',
                                lineHeight: props.headingLineHeight,
                                letterSpacing: props.headingLetterSpacing,
                                wordSpacing: props.headingWordSpacing,
                            }}
                            onChange={(next) =>
                                onChange({
                                    props: {
                                        ...props,
                                        headingFontFamily: next.fontFamily,
                                        headingFontSize: next.fontSize === '' ? undefined : next.fontSize,
                                        headingFontWeight: next.fontWeight,
                                        headingLineHeight: next.lineHeight === '' ? undefined : next.lineHeight,
                                        headingLetterSpacing: next.letterSpacing === '' ? undefined : next.letterSpacing,
                                        headingWordSpacing: next.wordSpacing === '' ? undefined : next.wordSpacing,
                                    },
                                })
                            }
                        />
                    </div>

                    <FourSideLinkedControl
                        label="Heading Margin"
                        value={props}
                        onChange={(nextProps) => onChange({ props: nextProps })}
                        keys={headingMarginKeys}
                        fallbackValue={props.headingMarginTop}
                    />

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Heading Color</label>
                        <div className="flex justify-end">
                            <ColorInput value={props.headingColor || ''} onChange={(v) => onChange({ props: { ...props, headingColor: v } })} />
                        </div>
                    </div>

                    <div>
                        <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Subtext Typography</div>
                        <TypographyPopover
                            withAlign={false}
                            value={{
                                fontFamily: props.subtextFontFamily || '',
                                fontSize: props.subtextFontSize,
                                fontWeight: props.subtextFontWeight || '',
                                lineHeight: props.subtextLineHeight,
                                letterSpacing: props.subtextLetterSpacing,
                                wordSpacing: props.subtextWordSpacing,
                            }}
                            onChange={(next) =>
                                onChange({
                                    props: {
                                        ...props,
                                        subtextFontFamily: next.fontFamily,
                                        subtextFontSize: next.fontSize === '' ? undefined : next.fontSize,
                                        subtextFontWeight: next.fontWeight,
                                        subtextLineHeight: next.lineHeight === '' ? undefined : next.lineHeight,
                                        subtextLetterSpacing: next.letterSpacing === '' ? undefined : next.letterSpacing,
                                        subtextWordSpacing: next.wordSpacing === '' ? undefined : next.wordSpacing,
                                    },
                                })
                            }
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Subtext Color</label>
                        <div className="flex justify-end">
                            <ColorInput value={props.subtextColor || ''} onChange={(v) => onChange({ props: { ...props, subtextColor: v } })} />
                        </div>
                    </div>

                    <FourSideLinkedControl
                        label="Subtext Margin"
                        value={props}
                        onChange={(nextProps) => onChange({ props: nextProps })}
                        keys={subtextMarginKeys}
                        fallbackValue={props.subtextMarginTop}
                    />
                </div>
            </StyleAccordionItem>

            <StyleAccordionItem title="Container" defaultOpen={false}>
                <div className="space-y-3 pb-3">
                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Container Background</label>
                        <div className="flex justify-end">
                            <ColorInput
                                value={props.backgroundColor || ''}
                                onChange={(v) => onChange({ props: { ...props, backgroundColor: v } })}
                            />
                        </div>
                    </div>

                    <SpacingControls
                        value={props}
                        onChange={(nextProps) => onChange({ props: nextProps })}
                        paddingKeys={paddingKeys}
                        paddingFallbackValue={props.paddingTop ?? props.paddingY ?? props.paddingX}
                    />

                    <BorderControls
                        value={props}
                        onChange={(nextProps) => onChange({ props: nextProps })}
                        widthKeys={borderWidthKeys}
                        widthFallbackValue={props.borderWidthTop ?? props.borderWidth}
                    />

                    <FourSideLinkedControl
                        label="Container Border Radius"
                        value={props}
                        onChange={(nextProps) => onChange({ props: nextProps })}
                        keys={borderRadiusKeys}
                        fallbackValue={props.borderRadiusTop ?? props.borderRadius}
                    />
                </div>
            </StyleAccordionItem>
        </div>
    );
}
