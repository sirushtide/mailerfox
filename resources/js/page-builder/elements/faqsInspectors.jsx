import React from 'react';
import { Button, Input, Select } from '../ui/primitives.jsx';
import { ColorInput } from '../ui/color.jsx';
import { TypographyPopover } from '../reusable/typography.jsx';
import { BackgroundPopover } from '../reusable/backgroundControls.jsx';
import { FourSideLinkedControl } from '../reusable/boxControls.jsx';
import { SpacingControls } from '../reusable/spacingControls.jsx';

export function FaqsContentInspector({ widget, onChange }) {
    const props = widget.props || {};
    const items = Array.isArray(props.items) ? props.items : [];

    const setItems = (nextItems) => {
        onChange({ props: { ...props, items: nextItems } });
    };

    const updateItem = (idx, patch) => {
        const next = items.slice();
        const curr = next[idx] && typeof next[idx] === 'object' ? next[idx] : { question: '', answer: '' };
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

    return (
        <div className="space-y-4">
            <div className="space-y-3">
                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">FAQ Items</div>

                <div className="flex items-center justify-between">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Items</label>
                    <Button
                        type="button"
                        onClick={() =>
                            setItems([
                                ...items,
                                {
                                    question: 'Question',
                                    answer: 'Answer',
                                },
                            ])
                        }
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

                <div className="space-y-3">
                    {items.length === 0 ? <div className="text-sm text-gray-500">No items yet.</div> : null}

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
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-up-icon lucide-arrow-up"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>
                                    </Button>
                                    <Button
                                        type="button"
                                        onClick={() => moveItem(idx, 1)}
                                        disabled={idx === items.length - 1}
                                        className="px-2"
                                        title="Move down"
                                        aria-label="Move down"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-down-icon lucide-arrow-down"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>
                                    </Button>
                                    <Button
                                        type="button"
                                        className="text-red-600 dark:text-red-400 px-2"
                                        onClick={() => removeItem(idx)}
                                        title="Remove"
                                        aria-label="Remove"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash2-icon lucide-trash-2"><path d="M10 11v6"/><path d="M14 11v6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </Button>
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Question</label>
                                <Input
                                    value={(it && typeof it === 'object' && typeof it.question === 'string') ? it.question : ''}
                                    onChange={(v) => updateItem(idx, { question: v })}
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Answer</label>
                                <textarea
                                    value={(it && typeof it === 'object' && typeof it.answer === 'string') ? it.answer : ''}
                                    onChange={(e) => updateItem(idx, { answer: e.target.value })}
                                    rows={3}
                                    className="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm placeholder-gray-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-400"
                                />
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            <div className="space-y-3">
                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Behavior</div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Open First</label>
                    <div className="flex justify-end">
                        <input
                            type="checkbox"
                            checked={Boolean(props.openFirst)}
                            onChange={(e) => onChange({ props: { ...props, openFirst: e.target.checked } })}
                            className="rounded border-gray-300"
                        />
                    </div>
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
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Carousel</label>
                    <div className="flex justify-end">
                        <input
                            type="checkbox"
                            checked={Boolean(props.carouselEnabled)}
                            onChange={(e) => onChange({ props: { ...props, carouselEnabled: e.target.checked } })}
                            className="rounded border-gray-300"
                        />
                    </div>
                </div>

                {props.carouselEnabled ? (
                    <div className="space-y-3 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Speed (s)</label>
                            <Input
                                type="number"
                                value={props.carouselSpeed !== undefined ? String(props.carouselSpeed) : ''}
                                onChange={(raw) => {
                                    const n = raw === '' ? undefined : Number(raw);
                                    onChange({
                                        props: {
                                            ...props,
                                            carouselSpeed: Number.isFinite(n) ? Math.max(1, Math.min(60, n)) : undefined,
                                        },
                                    });
                                }}
                                placeholder="4"
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Arrows</label>
                            <div className="flex justify-end">
                                <input
                                    type="checkbox"
                                    checked={props.carouselArrows !== false}
                                    onChange={(e) => onChange({ props: { ...props, carouselArrows: e.target.checked } })}
                                    className="rounded border-gray-300"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Arrow Position</label>
                            <Select
                                value={props.carouselArrowPosition || 'inside'}
                                onChange={(v) => onChange({ props: { ...props, carouselArrowPosition: v } })}
                                options={[
                                    { value: 'inside', label: 'Inside' },
                                    { value: 'outside', label: 'Outside' },
                                ]}
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Ease</label>
                            <Select
                                value={props.carouselEase || 'ease'}
                                onChange={(v) => onChange({ props: { ...props, carouselEase: v } })}
                                options={[
                                    { value: 'linear', label: 'Linear' },
                                    { value: 'ease', label: 'Ease' },
                                    { value: 'ease-in', label: 'Ease In' },
                                    { value: 'ease-out', label: 'Ease Out' },
                                    { value: 'ease-in-out', label: 'Ease In Out' },
                                ]}
                            />
                        </div>
                    </div>
                ) : null}
            </div>
        </div>
    );
}

export function FaqsStyleInspector({ widget, onChange }) {
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

    const itemPaddingKeys = {
        linked: 'itemPaddingLinked',
        t: 'itemPaddingTop',
        r: 'itemPaddingRight',
        b: 'itemPaddingBottom',
        l: 'itemPaddingLeft',
        legacyX: 'itemPaddingX',
        legacyY: 'itemPaddingY',
    };

    return (
        <div className="space-y-4">
            <div className="space-y-3">
                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Layout</div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Gap (px)</label>
                    <Input
                        type="number"
                        value={props.gap !== undefined ? String(props.gap) : ''}
                        onChange={(raw) => {
                            const n = raw === '' ? undefined : Number(raw);
                            onChange({ props: { ...props, gap: Number.isFinite(n) ? Math.max(0, Math.round(n)) : undefined } });
                        }}
                        placeholder="10"
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

                <BackgroundPopover label="Background" value={props} onChange={(nextProps) => onChange({ props: nextProps })} />
            </div>

            <div className="space-y-3">
                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Item</div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Background</label>
                    <div className="flex justify-end">
                        <ColorInput
                            value={props.itemBackgroundColor || ''}
                            onChange={(v) => onChange({ props: { ...props, itemBackgroundColor: v } })}
                            fallback="#ffffff"
                        />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Border Color</label>
                    <div className="flex justify-end">
                        <ColorInput
                            value={props.itemBorderColor || ''}
                            onChange={(v) => onChange({ props: { ...props, itemBorderColor: v } })}
                            fallback="#e5e7eb"
                        />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Border Width (px)</label>
                    <Input
                        type="number"
                        value={props.itemBorderWidth !== undefined ? String(props.itemBorderWidth) : ''}
                        onChange={(raw) => {
                            const n = raw === '' ? undefined : Number(raw);
                            onChange({ props: { ...props, itemBorderWidth: Number.isFinite(n) ? Math.max(0, Math.round(n)) : undefined } });
                        }}
                        placeholder="1"
                    />
                </div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Border Radius (px)</label>
                    <Input
                        type="number"
                        value={props.itemBorderRadius !== undefined ? String(props.itemBorderRadius) : ''}
                        onChange={(raw) => {
                            const n = raw === '' ? undefined : Number(raw);
                            onChange({ props: { ...props, itemBorderRadius: Number.isFinite(n) ? Math.max(0, Math.round(n)) : undefined } });
                        }}
                        placeholder="10"
                    />
                </div>

                <FourSideLinkedControl
                    label="Item Padding"
                    value={props}
                    keys={itemPaddingKeys}
                    fallbackValue={props.itemPaddingTop ?? props.itemPaddingY ?? props.itemPaddingX}
                    onChange={(nextProps) => onChange({ props: nextProps })}
                />
            </div>

            <div className="space-y-3">
                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Question Typography</div>

                <TypographyPopover
                    value={{
                        fontFamily: props.questionFontFamily,
                        fontSize: props.questionFontSize,
                        fontWeight: props.questionFontWeight,
                        lineHeight: props.questionLineHeight,
                        letterSpacing: props.questionLetterSpacing,
                        wordSpacing: props.questionWordSpacing,
                    }}
                    onChange={(next) =>
                        onChange({
                            props: {
                                ...props,
                                questionFontFamily: next.fontFamily,
                                questionFontSize: next.fontSize === '' ? undefined : next.fontSize,
                                questionFontWeight: next.fontWeight,
                                questionLineHeight: next.lineHeight === '' ? undefined : next.lineHeight,
                                questionLetterSpacing: next.letterSpacing === '' ? undefined : next.letterSpacing,
                                questionWordSpacing: next.wordSpacing === '' ? undefined : next.wordSpacing,
                            },
                        })
                    }
                />

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text Color</label>
                    <div className="flex justify-end">
                        <ColorInput value={props.questionColor || ''} onChange={(v) => onChange({ props: { ...props, questionColor: v } })} />
                    </div>
                </div>
            </div>

            <div className="space-y-3">
                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Answer Typography</div>

                <TypographyPopover
                    value={{
                        fontFamily: props.answerFontFamily,
                        fontSize: props.answerFontSize,
                        fontWeight: props.answerFontWeight,
                        lineHeight: props.answerLineHeight,
                        letterSpacing: props.answerLetterSpacing,
                        wordSpacing: props.answerWordSpacing,
                    }}
                    onChange={(next) =>
                        onChange({
                            props: {
                                ...props,
                                answerFontFamily: next.fontFamily,
                                answerFontSize: next.fontSize === '' ? undefined : next.fontSize,
                                answerFontWeight: next.fontWeight,
                                answerLineHeight: next.lineHeight === '' ? undefined : next.lineHeight,
                                answerLetterSpacing: next.letterSpacing === '' ? undefined : next.letterSpacing,
                                answerWordSpacing: next.wordSpacing === '' ? undefined : next.wordSpacing,
                            },
                        })
                    }
                />

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text Color</label>
                    <div className="flex justify-end">
                        <ColorInput value={props.answerColor || ''} onChange={(v) => onChange({ props: { ...props, answerColor: v } })} />
                    </div>
                </div>
            </div>
        </div>
    );
}
