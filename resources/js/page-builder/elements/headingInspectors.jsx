import React from 'react';
import { Input, Select } from '../ui/primitives.jsx';
import { ColorInput } from '../ui/color.jsx';
import { TypographyPopover } from '../reusable/typography.jsx';
import { ShadowPopover } from '../reusable/shadow.jsx';
import { BackgroundPopover } from '../reusable/backgroundControls.jsx';
import { SpacingControls } from '../reusable/spacingControls.jsx';
import { BorderControls, FourSideLinkedControl } from '../reusable/boxControls.jsx';
import { stripHtml } from '../lib/typographyUtils.js';

function StyleAccordionItem({ title, children, defaultOpen = false }) {
    const [open, setOpen] = React.useState(Boolean(defaultOpen));

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

export function HeadingContentInspector({ widget, onChange }) {
    const props = widget.props || {};
    const text = props.text || stripHtml(props.html);

    return (
        <div className="space-y-3">
            <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text</label>
                <Input
                    value={text}
                    onChange={(v) => {
                        const next = { ...props, text: v };
                        if (v.trim() !== '') {
                            delete next.html;
                        }
                        onChange({ props: next });
                    }}
                />
            </div>

            <div className="grid grid-cols-2 gap-2">
                <div>
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Level</label>
                    <Select
                        value={String(props.level ?? 2)}
                        onChange={(v) => onChange({ props: { ...props, level: Number(v) } })}
                        options={[1, 2, 3, 4, 5, 6].map((n) => ({ value: String(n), label: `H${n}` }))}
                    />
                </div>
                <div />
            </div>
        </div>
    );
}

export function HeadingStyleInspector({ widget, onChange }) {
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
        <div className="border-y border-gray-200 dark:border-gray-700">
            <StyleAccordionItem title="Heading" defaultOpen={true}>
                <div className="space-y-3 pb-3">
                    <TypographyPopover
                        value={{
                            fontFamily: props.fontFamily || '',
                            fontSize: props.fontSize,
                            fontWeight: props.fontWeight || '',
                            align: props.align || 'left',
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
                                    align: next.align,
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
                            <ColorInput
                                value={props.color || ''}
                                onChange={(v) => onChange({ props: { ...props, color: v } })}
                            />
                        </div>
                    </div>

                    <ShadowPopover
                        label="Text Shadow"
                        type="text"
                        value={{
                            color: props.textShadowColor,
                            offsetX: props.textShadowX,
                            offsetY: props.textShadowY,
                            blur: props.textShadowBlur,
                        }}
                        onChange={(v) =>
                            onChange({
                                props: {
                                    ...props,
                                    textShadowColor: v.color,
                                    textShadowX: v.offsetX,
                                    textShadowY: v.offsetY,
                                    textShadowBlur: v.blur,
                                },
                            })
                        }
                    />

                    <SpacingControls
                        value={props}
                        onChange={(nextProps) => onChange({ props: nextProps })}
                        paddingKeys={paddingKeys}
                        paddingFallbackValue={props.paddingTop ?? props.paddingY ?? props.paddingX}
                        marginKeys={marginKeys}
                        marginFallbackValue={props.marginTop ?? props.marginRight ?? props.marginBottom ?? props.marginLeft}
                    />

                    <BackgroundPopover label="Background" value={props} onChange={(nextProps) => onChange({ props: nextProps })} />

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
                </div>
            </StyleAccordionItem>

            <StyleAccordionItem title="Highlight" defaultOpen={false}>
                <div className="space-y-2 pb-3">
                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Mode</label>
                        <div className="flex justify-end">
                            <Select
                                value={String(props.highlightMode || 'none')}
                                onChange={(v) =>
                                    onChange({
                                        props: {
                                            ...props,
                                            highlightMode: v,
                                        },
                                    })
                                }
                                options={[
                                    { value: 'none', label: 'Off' },
                                    { value: 'color', label: 'Color' },
                                    { value: 'gradient', label: 'Gradient' },
                                ]}
                            />
                        </div>
                    </div>

                    {props.highlightMode && props.highlightMode !== 'none' ? (
                        <TypographyPopover
                            value={{
                                fontFamily: props.highlightFontFamily || '',
                                fontSize: props.highlightFontSize,
                                fontWeight: props.highlightFontWeight || '',
                                align: 'left',
                                lineHeight: props.highlightLineHeight,
                                letterSpacing: props.highlightLetterSpacing,
                                wordSpacing: props.highlightWordSpacing,
                            }}
                            onChange={(next) =>
                                onChange({
                                    props: {
                                        ...props,
                                        highlightFontFamily: next.fontFamily,
                                        highlightFontSize: next.fontSize === '' ? undefined : next.fontSize,
                                        highlightFontWeight: next.fontWeight,
                                        highlightLineHeight: next.lineHeight === '' ? undefined : next.lineHeight,
                                        highlightLetterSpacing:
                                            next.letterSpacing === '' ? undefined : next.letterSpacing,
                                        highlightWordSpacing: next.wordSpacing === '' ? undefined : next.wordSpacing,
                                    },
                                })
                            }
                        />
                    ) : null}

                    {props.highlightMode === 'color' ? (
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Color</label>
                            <div className="flex justify-end">
                                <ColorInput
                                    value={props.highlightColor || ''}
                                    onChange={(v) => onChange({ props: { ...props, highlightColor: v } })}
                                />
                            </div>
                        </div>
                    ) : null}

                    {props.highlightMode === 'gradient' ? (
                        <div className="space-y-2">
                            <div className="grid grid-cols-2 gap-3 items-center">
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">From</label>
                                <div className="flex justify-end">
                                    <ColorInput
                                        value={props.highlightGradientFrom || ''}
                                        onChange={(v) => onChange({ props: { ...props, highlightGradientFrom: v } })}
                                    />
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-3 items-center">
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
                                <div className="flex justify-end">
                                    <ColorInput
                                        value={props.highlightGradientTo || ''}
                                        onChange={(v) => onChange({ props: { ...props, highlightGradientTo: v } })}
                                    />
                                </div>
                            </div>

                            <label className="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input
                                    type="checkbox"
                                    checked={props.highlightAnimate === true}
                                    onChange={(e) =>
                                        onChange({ props: { ...props, highlightAnimate: e.target.checked } })
                                    }
                                />
                                <span>Animate Gradient</span>
                            </label>

                            {props.highlightAnimate === true ? (
                                <div className="grid grid-cols-2 gap-3 items-center">
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Animation (s)
                                    </label>
                                    <div className="flex justify-end">
                                        <Input
                                            type="number"
                                            value={String(props.highlightAnimationDuration ?? 6)}
                                            onChange={(v) => {
                                                const n = Number(v);
                                                onChange({
                                                    props: {
                                                        ...props,
                                                        highlightAnimationDuration: Number.isFinite(n) ? n : undefined,
                                                    },
                                                });
                                            }}
                                        />
                                    </div>
                                </div>
                            ) : null}
                        </div>
                    ) : null}
                </div>
            </StyleAccordionItem>
        </div>
    );
}
