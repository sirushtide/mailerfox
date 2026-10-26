import React, { useContext, useState } from 'react';
import { BuilderModeContext } from '../controls/builderMode.js';
import { Input, Select } from '../ui/primitives.jsx';
import { ColorInput } from '../ui/color.jsx';
import { TypographyPopover } from '../reusable/typography.jsx';
import { ShadowPopover } from '../reusable/shadow.jsx';
import { BorderControls, FourSideLinkedControl } from '../reusable/boxControls.jsx';
import { BackgroundPopover } from '../reusable/backgroundControls.jsx';
import { BorderEffectControls } from '../reusable/borderEffect.jsx';
import { DimensionsControls } from '../reusable/dimensionsControls.jsx';
import { ImageUrlUploadControl } from '../reusable/imageUploadControl.jsx';
import { Popover, PopoverTriggerButton } from '../ui/popover.jsx';
import { StyleAccordionItem } from '../controls/accordion.jsx';
import { Switcher } from '../controls/Switcher.jsx';

export function ButtonContentInspector({ widget, onChange, uploadUrl }) {
    const props = widget.props || {};

    const iconOptions = [
        { value: '', label: 'None' },
        { value: 'arrow-right', label: 'Arrow Right' },
        { value: 'arrow-left', label: 'Arrow Left' },
        { value: 'download', label: 'Download' },
        { value: 'external', label: 'External' },
        { value: 'check', label: 'Check' },
        { value: 'plus', label: 'Plus' },
        { value: 'heart', label: 'Heart' },
        { value: 'star', label: 'Star' },
    ];

    const iconType = ['none', 'preset', 'custom'].includes(props.iconType) ? props.iconType : (props.iconUrl ? 'custom' : props.icon ? 'preset' : 'none');

    return (
        <div className="space-y-3 px-4">
            <div className="grid grid-cols-2 gap-3 items-center">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text</label>
                <Input value={props.text || ''} onChange={(v) => onChange({ props: { ...props, text: v } })} />
            </div>

            <div className="grid grid-cols-2 gap-3 items-center">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Icon Type</label>
                <Select
                    value={iconType}
                    onChange={(v) =>
                        onChange({
                            props: {
                                ...props,
                                iconType: v,
                                icon: v === 'preset' ? (props.icon || '') : '',
                                iconUrl: v === 'custom' ? (props.iconUrl || '') : '',
                                iconPosition: props.iconPosition && ['left', 'right'].includes(props.iconPosition)
                                    ? props.iconPosition
                                    : 'left',
                            },
                        })
                    }
                    options={[
                        { value: 'none', label: 'None' },
                        { value: 'preset', label: 'Preset' },
                        { value: 'custom', label: 'Custom Upload' },
                    ]}
                />
            </div>

            {iconType === 'preset' ? (
                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Icon</label>
                    <Select
                        value={String(props.icon || '')}
                        onChange={(v) =>
                            onChange({
                                props: {
                                    ...props,
                                    iconType: 'preset',
                                    icon: v,
                                    iconUrl: '',
                                    iconPosition: props.iconPosition && ['left', 'right'].includes(props.iconPosition)
                                        ? props.iconPosition
                                        : 'left',
                                },
                            })
                        }
                        options={iconOptions}
                    />
                </div>
            ) : null}

            {iconType === 'custom' ? (
                <div>
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Icon URL</label>
                    <ImageUrlUploadControl
                        value={props.iconUrl || ''}
                        onChange={(v) => onChange({ props: { ...props, iconType: 'custom', iconUrl: v, icon: '' } })}
                        uploadUrl={uploadUrl}
                        onUploaded={(url) => onChange({ props: { ...props, iconType: 'custom', iconUrl: url, icon: '' } })}
                        placeholder="Enter image URL"
                        accept="image/svg+xml,image/png,image/jpeg,image/jpg,image/webp"
                    />
                </div>
            ) : null}

            {iconType !== 'none' ? (
                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Icon Position</label>
                    <Select
                        value={props.iconPosition || 'left'}
                        onChange={(v) => onChange({ props: { ...props, iconPosition: v } })}
                        options={[
                            { value: 'left', label: 'Left' },
                            { value: 'right', label: 'Right' },
                        ]}
                    />
                </div>
            ) : null}

            <div className="grid grid-cols-2 gap-3 items-center">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Link</label>
                <Input value={props.url || ''} onChange={(v) => onChange({ props: { ...props, url: v } })} />
            </div>

            <div className="grid grid-cols-2 gap-3 items-center">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Target</label>
                <Select
                    value={props.target || '_self'}
                    onChange={(v) => onChange({ props: { ...props, target: v } })}
                    options={[
                        { value: '_self', label: 'Same Tab' },
                        { value: '_blank', label: 'New Tab' },
                    ]}
                />
            </div>

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

            <div className="grid grid-cols-2 gap-3 items-center">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Hover Text Effect</label>
                <Select
                    value={props.hoverTextEffect || 'none'}
                    onChange={(v) => onChange({ props: { ...props, hoverTextEffect: v } })}
                    options={[
                        { value: 'none', label: 'None' },
                        { value: 'swap-up', label: 'Style 1: Swap Up' },
                        { value: 'swap-icon', label: 'Style 2: Swap Icon' },
                        { value: 'slide-icon-right', label: 'Style 3: Slide Icon Right' },
                    ]}
                />
            </div>
        </div>
    );
}

export function ButtonStyleInspector({ widget, onChange }) {
    const props = widget.props || {};
    const builderMode = useContext(BuilderModeContext);
    const isEmailMode = builderMode === 'email';
    const [stateTab, setStateTab] = useState('normal');
    const activeStateTab = isEmailMode ? 'normal' : stateTab;

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

    const backgroundKeysForState = (tab) => {
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

    const textColorKeyForState = (tab) => {
        if (tab === 'hover') return 'hoverTextColor';
        if (tab === 'focus') return 'focusTextColor';
        return 'textColor';
    };

    const colorsSegment = (
        <div className="flex items-center justify-between">
            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Colors</label>
            <div className="inline-flex overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                <button
                    type="button"
                    onClick={() => setStateTab('normal')}
                    className={`px-3 py-1.5 text-xs font-semibold ${
                        activeStateTab === 'normal'
                            ? 'bg-gray-200 text-gray-900'
                            : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800'
                    }`}
                >
                    Normal
                </button>
                {!isEmailMode ? (
                    <>
                        <button
                            type="button"
                            onClick={() => setStateTab('hover')}
                            className={`px-3 py-1.5 text-xs font-semibold border-l border-gray-200 dark:border-gray-700 ${
                                stateTab === 'hover'
                                    ? 'bg-gray-200 text-gray-900'
                                    : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800'
                            }`}
                        >
                            Hover
                        </button>
                        <button
                            type="button"
                            onClick={() => setStateTab('focus')}
                            className={`px-3 py-1.5 text-xs font-semibold border-l border-gray-200 dark:border-gray-700 ${
                                stateTab === 'focus'
                                    ? 'bg-gray-200 text-gray-900'
                                    : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800'
                            }`}
                        >
                            Focus
                        </button>
                    </>
                ) : null}
            </div>
        </div>
    );

    const textColorKey = textColorKeyForState(activeStateTab);
    const bgKeys = backgroundKeysForState(activeStateTab);

    return (
        <>
            <StyleAccordionItem title="Button" defaultOpen={true}>
                <div className="space-y-4 px-4">
                    <div className="space-y-3">
                        <Switcher
                            label="Full Width"
                            value={Boolean(props.fullWidth)}
                            onChange={(v) => onChange({ props: { ...props, fullWidth: v } })}
                        />

                        <DimensionsControls
                            value={props}
                            onChange={(nextProps) => onChange({ props: nextProps })}
                            showWidth={!props.fullWidth}
                            showHeight={true}
                            widthLabel="Width (px)"
                            heightLabel="Height (px)"
                            widthPlaceholder="Auto"
                            heightPlaceholder="Auto"
                            useSliders={true}
                            widthMin={0}
                            widthMax={1200}
                            widthStep={1}
                            heightMin={0}
                            heightMax={800}
                            heightStep={1}
                        />
                    </div>

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

                    <BackgroundPopover
                        label="Background"
                        value={props}
                        onChange={(nextProps) => onChange({ props: nextProps })}
                        keys={backgroundKeysForState('normal')}
                    />

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text Color</label>
                        <div className="flex justify-end">
                            <ColorInput
                                value={props.textColor || ''}
                                onChange={(v) => onChange({ props: { ...props, textColor: v } })}
                            />
                        </div>
                    </div>

                    <div className="space-y-3">
                        <div className="flex items-center justify-between">
                            <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Border</div>
                            <Popover
                                width={320}
                                trigger={
                                    <PopoverTriggerButton
                                        active={Boolean(
                                            props.borderStyle ||
                                            (props.borderColor && props.borderColor.trim()) ||
                                            props.borderWidthTop ||
                                            props.borderWidthRight ||
                                            props.borderWidthBottom ||
                                            props.borderWidthLeft ||
                                            props.borderWidth
                                        )}
                                        title="Edit border"
                                        icon={
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil-icon lucide-pencil"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg>
                                        }
                                    />
                                }
                            >
                                <div className="space-y-3">
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
                            </Popover>
                        </div>
                    </div>

                    {!isEmailMode ? (
                        <div className="space-y-3">
                            <div className="flex items-center justify-between">
                                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Animated Border</div>
                                <Popover
                                    width={320}
                                    trigger={
                                        <PopoverTriggerButton
                                            active={Boolean(props.borderEffect && props.borderEffect !== 'none')}
                                            title="Edit animated border"
                                            icon={
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="lucide lucide-pencil-icon lucide-pencil"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg>
                                            }
                                        />
                                    }
                                >
                                    <BorderEffectControls
                                        value={props}
                                        onChange={(nextProps) => onChange({ props: nextProps })}
                                    />
                                </Popover>
                            </div>
                        </div>
                    ) : null}

                    <ShadowPopover
                        label="Box Shadow"
                        type="box"
                        value={{
                            color: props.boxShadowColor,
                            offsetX: props.boxShadowX,
                            offsetY: props.boxShadowY,
                            blur: props.boxShadowBlur,
                            spread: props.boxShadowSpread,
                        }}
                        onChange={(v) =>
                            onChange({
                                props: {
                                    ...props,
                                    boxShadowColor: v.color,
                                    boxShadowX: v.offsetX,
                                    boxShadowY: v.offsetY,
                                    boxShadowBlur: v.blur,
                                    boxShadowSpread: v.spread,
                                },
                            })
                        }
                    />

                    <div className="space-y-3">
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

            {!isEmailMode ? (
                <StyleAccordionItem title="States" defaultOpen={false}>
                    <div className="space-y-3 px-4">
                        {colorsSegment}

                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text Color</label>
                            <div className="flex justify-end">
                                <ColorInput
                                    value={props[textColorKey] || ''}
                                    onChange={(v) => onChange({ props: { ...props, [textColorKey]: v } })}
                                />
                            </div>
                        </div>

                        <BackgroundPopover label="Background" value={props} onChange={(nextProps) => onChange({ props: nextProps })} keys={bgKeys} />

                    </div>
                </StyleAccordionItem>
            ) : null}
        </>
    );
}
