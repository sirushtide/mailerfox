import React from 'react';
import { Button, Input, Select } from '../ui/primitives.jsx';
import { ColorInput } from '../ui/color.jsx';
import { DimensionsControls } from '../reusable/dimensionsControls.jsx';
import { MultiImageUploadControl } from '../reusable/imageUploadControl.jsx';
import { TypographyPopover } from '../reusable/typography.jsx';
import { BackgroundPopover } from '../reusable/backgroundControls.jsx';
import { Switcher } from '../controls/Switcher.jsx';
import { SliderInput } from '../controls/SliderInput.jsx';
import { StyleAccordionItem } from '../controls/accordion.jsx';

function AddIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M12 5v14" />
            <path d="M5 12h14" />
        </svg>
    );
}

function TrashIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M10 11v6" />
            <path d="M14 11v6" />
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
            <path d="M3 6h18" />
            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
        </svg>
    );
}

export function MarqueeContentInspector({ widget, onChange, uploadUrl }) {
    const props = widget.props || {};
    const images = Array.isArray(props.images) ? props.images : [];
    const items = Array.isArray(props.items) ? props.items : [];
    const mode = ['images', 'text'].includes(props.mode) ? props.mode : 'images';
    const direction = ['left', 'right'].includes(props.direction) ? props.direction : 'left';
    const separatorIcon = typeof props.separatorIcon === 'string' ? props.separatorIcon : '';

    const setProps = (patch) => onChange({ props: { ...props, ...patch } });

    const setItems = (next) => setProps({ items: next });

    const updateItem = (idx, text) => {
        const next = items.slice();
        const curr = next[idx];
        next[idx] = { ...(typeof curr === 'object' && curr ? curr : {}), text };
        setItems(next);
    };

    const removeItem = (idx) => setItems(items.filter((_, i) => i !== idx));

    const addItem = () => setItems([...items, { text: `Item ${items.length + 1}` }]);

    return (
        <div className="space-y-4 px-4">
            <div className="grid grid-cols-2 gap-3 items-center">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Content Type</label>
                <Select
                    value={mode}
                    onChange={(v) => setProps({ mode: v })}
                    options={[
                        { value: 'images', label: 'Images' },
                        { value: 'text', label: 'Text' },
                    ]}
                />
            </div>

            <div className="grid grid-cols-2 gap-3 items-center">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Direction</label>
                <Select
                    value={direction}
                    onChange={(v) => setProps({ direction: v })}
                    options={[
                        { value: 'left', label: 'LTR' },
                        { value: 'right', label: 'RTL' },
                    ]}
                />
            </div>

            {mode === 'images' ? (
                <div className="space-y-2">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Images</label>
                    <MultiImageUploadControl
                        value={images}
                        onChange={(next) => setProps({ images: next })}
                        uploadUrl={uploadUrl}
                    />
                </div>
            ) : (
                <div className="space-y-4">
                    <div className="space-y-2">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Separator Icon SVG</label>
                        <textarea
                            value={separatorIcon}
                            onChange={(e) => setProps({ separatorIcon: e.target.value })}
                            placeholder='<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" ...></svg>'
                            rows={3}
                            className="block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary placeholder:text-admin-text-secondary/70 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                        />
                        <div className="text-xs text-gray-500">This SVG appears between each text item in the marquee.</div>
                    </div>

                    <div className="space-y-3">
                        <div className="flex items-center justify-between">
                            <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text Items</div>
                            <Button type="button" onClick={addItem} className="px-2" title="Add" aria-label="Add">
                                <AddIcon />
                            </Button>
                        </div>

                        {items.length === 0 ? <div className="text-sm text-gray-500">No text items yet.</div> : null}

                        <div className="space-y-2">
                            {items.map((it, idx) => {
                                const text = typeof it === 'string' ? it : (it && typeof it.text === 'string' ? it.text : '');
                                return (
                                    <div key={idx} className="flex items-center gap-2">
                                        <Input
                                            value={text}
                                            onChange={(v) => updateItem(idx, v)}
                                            placeholder="Marquee text"
                                            className="flex-1"
                                        />
                                        <Button
                                            type="button"
                                            onClick={() => removeItem(idx)}
                                            className="text-red-600 dark:text-red-400 px-2"
                                            title="Remove"
                                            aria-label="Remove"
                                        >
                                            <TrashIcon />
                                        </Button>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

export function MarqueeStyleInspector({ widget, onChange }) {
    const props = widget.props || {};
    const mode = ['images', 'text'].includes(props.mode) ? props.mode : 'images';

    const setProps = (patch) => {
        onChange({ props: { ...props, ...patch } });
    };

    const fadeEnabled = props.fadeEnabled !== false;
    const fadeMask = props.fadeMask !== false;

    const fadeWidthRaw = Number(props.fadeWidth);
    const fadeWidth = Number.isFinite(fadeWidthRaw) ? Math.max(0, Math.min(200, Math.round(fadeWidthRaw))) : 48;

    return (
        <>
            <StyleAccordionItem title="Marquee" defaultOpen={true}>
                <div className="space-y-4 px-4">
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
                            angle: 'gradientAngle',
                        }}
                    />

                    {mode === 'text' ? (
                        <div className="space-y-3">
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
                                    setProps({
                                        fontFamily: next.fontFamily,
                                        fontSize: next.fontSize === '' ? undefined : next.fontSize,
                                        fontWeight: next.fontWeight,
                                        lineHeight: next.lineHeight === '' ? undefined : next.lineHeight,
                                        letterSpacing: next.letterSpacing === '' ? undefined : next.letterSpacing,
                                        wordSpacing: next.wordSpacing === '' ? undefined : next.wordSpacing,
                                    })
                                }
                            />

                            <div className="grid grid-cols-2 gap-3 items-center">
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text Color</label>
                                <div className="flex justify-end">
                                    <ColorInput
                                        value={typeof props.textColor === 'string' ? props.textColor : ''}
                                        onChange={(v) => setProps({ textColor: v })}
                                    />
                                </div>
                            </div>
                        </div>
                    ) : null}

                    {mode === 'images' ? (
                        <div className="space-y-3">
                            <DimensionsControls
                                value={props}
                                onChange={(nextProps) => onChange({ props: nextProps })}
                                widthKey="imageWidth"
                                heightKey="imageHeight"
                                widthLabel="Image Width (px)"
                                heightLabel="Image Height (px)"
                                widthPlaceholder="Auto"
                                heightPlaceholder="40"
                                useSliders={true}
                                widthMin={0}
                                widthMax={600}
                                widthStep={1}
                                heightMin={10}
                                heightMax={200}
                                heightStep={1}
                            />
                        </div>
                    ) : null}
                </div>
            </StyleAccordionItem>

            <StyleAccordionItem title="Fade" defaultOpen={false}>
                <div className="space-y-3 px-4">
                    <Switcher
                        label="Fade edges"
                        value={fadeEnabled}
                        onChange={(v) => setProps({ fadeEnabled: v })}
                    />

                    {fadeEnabled ? (
                        <>
                            <Switcher
                                label="Apply mask"
                                value={fadeMask}
                                onChange={(v) => setProps({ fadeMask: v })}
                            />

                            <div className="grid grid-cols-2 gap-3 items-center">
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Fade color</label>
                                <div className="flex justify-end">
                                    <ColorInput
                                        value={typeof props.fadeColor === 'string' ? props.fadeColor : ''}
                                        onChange={(v) => setProps({ fadeColor: v })}
                                        fallback="#ffffff"
                                    />
                                </div>
                            </div>

                            <SliderInput
                                label="Fade width"
                                value={props.fadeWidth !== undefined ? fadeWidth : ''}
                                onChange={(v) => {
                                    if (v === '' || v === null || v === undefined || typeof v === 'object') {
                                        setProps({ fadeWidth: undefined });
                                        return;
                                    }
                                    const n = Number(v);
                                    setProps({ fadeWidth: Number.isFinite(n) ? Math.max(0, Math.min(200, Math.round(n))) : undefined });
                                }}
                                kind="width"
                                units={['px']}
                                defaultUnit="px"
                                min={0}
                                max={200}
                                step={1}
                            />
                        </>
                    ) : null}
                </div>
            </StyleAccordionItem>
        </>
    );
}
