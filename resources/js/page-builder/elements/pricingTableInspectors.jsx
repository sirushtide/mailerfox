import React from 'react';
import { uid } from '../lib/uid.js';
import { Button, Input, Select } from '../ui/primitives.jsx';
import { ColorInput } from '../ui/color.jsx';
import { SpacingControls } from '../reusable/spacingControls.jsx';
import { FourSideLinkedControl } from '../reusable/boxControls.jsx';
import { ImageUrlUploadControl } from '../reusable/imageUploadControl.jsx';
import { BorderEffectControls } from '../reusable/borderEffect.jsx';
import { Popover, PopoverTriggerButton } from '../ui/popover.jsx';
import { TypographyPopover } from '../reusable/typography.jsx';
import { StyleAccordionItem } from '../controls/accordion.jsx';

const CONTENT_TYPOGRAPHY_PARTS = [
    { prefix: 'title', label: 'Title' },
    { prefix: 'price', label: 'Price' },
    { prefix: 'description', label: 'Description' },
    { prefix: 'feature', label: 'Features' },
    { prefix: 'excludedFeature', label: 'Non Features' },
    { prefix: 'button', label: 'Button' },
];

function ContentTypographyRow({ label, prefix, props, onChange }) {
    const key = (name) => `${prefix}${name}`;
    return (
        <div className="space-y-1">
            <div className="block text-xs font-medium text-gray-700 dark:text-gray-300">{label}</div>
            <TypographyPopover
                withAlign={false}
                value={{
                    fontFamily: props[key('FontFamily')],
                    fontSize: props[key('FontSize')],
                    fontWeight: props[key('FontWeight')],
                    lineHeight: props[key('LineHeight')],
                    letterSpacing: props[key('LetterSpacing')],
                    wordSpacing: props[key('WordSpacing')],
                }}
                onChange={(next) =>
                    onChange({
                        [key('FontFamily')]: next.fontFamily,
                        [key('FontSize')]: next.fontSize === '' ? undefined : next.fontSize,
                        [key('FontWeight')]: next.fontWeight,
                        [key('LineHeight')]: next.lineHeight === '' ? undefined : next.lineHeight,
                        [key('LetterSpacing')]: next.letterSpacing === '' ? undefined : next.letterSpacing,
                        [key('WordSpacing')]: next.wordSpacing === '' ? undefined : next.wordSpacing,
                    })
                }
            />
        </div>
    );
}

function normalizePlan(plan, legacyPlans) {
    if (plan && typeof plan === 'object') {
        return {
            title: typeof plan.title === 'string' ? plan.title : 'Plan',
            description: typeof plan.description === 'string' ? plan.description : '',
            monthlyPrice: typeof plan.monthlyPrice === 'string' ? plan.monthlyPrice : '$0',
            monthlyPeriod: typeof plan.monthlyPeriod === 'string' ? plan.monthlyPeriod : '/mo',
            yearlyPrice: typeof plan.yearlyPrice === 'string' ? plan.yearlyPrice : '$0',
            yearlyPeriod: typeof plan.yearlyPeriod === 'string' ? plan.yearlyPeriod : '/yr',
            features: Array.isArray(plan.features) ? plan.features : [],
            buttonText: typeof plan.buttonText === 'string' ? plan.buttonText : 'Get started',
            buttonUrl: typeof plan.buttonUrl === 'string' ? plan.buttonUrl : '#',
            buttonTarget: typeof plan.buttonTarget === 'string' ? plan.buttonTarget : '_self',
            featured: Boolean(plan.featured),
            badge: typeof plan.badge === 'string' ? plan.badge : '',
            icon: typeof plan.icon === 'string' ? plan.icon : '',
            iconType: typeof plan.iconType === 'string' ? plan.iconType : '',
            iconUrl: typeof plan.iconUrl === 'string' ? plan.iconUrl : '',
            featuresTitle: typeof plan.featuresTitle === 'string' ? plan.featuresTitle : '',
            discount: typeof plan.discount === 'string' ? plan.discount : '',
            buttonIconType: typeof plan.buttonIconType === 'string' ? plan.buttonIconType : '',
            buttonIcon: typeof plan.buttonIcon === 'string' ? plan.buttonIcon : '',
            buttonIconUrl: typeof plan.buttonIconUrl === 'string' ? plan.buttonIconUrl : '',
            buttonIconPosition: typeof plan.buttonIconPosition === 'string' ? plan.buttonIconPosition : 'left',
            buttonHoverTextEffect: typeof plan.buttonHoverTextEffect === 'string' ? plan.buttonHoverTextEffect : 'none',
        };
    }

    if (Array.isArray(legacyPlans)) {
        const legacy = legacyPlans.filter((p) => p && typeof p === 'object');
        const first = legacy[0];
        const second = legacy[1];
        if (first) {
            return {
                title: typeof first.title === 'string' ? first.title : 'Plan',
                description: typeof first.description === 'string' ? first.description : '',
                monthlyPrice: typeof first.price === 'string' ? first.price : '$0',
                monthlyPeriod: typeof first.period === 'string' ? first.period : '/mo',
                yearlyPrice: second && typeof second.price === 'string' ? second.price : first.price,
                yearlyPeriod: second && typeof second.period === 'string' ? second.period : '/yr',
                features: Array.isArray(first.features) ? first.features : [],
                buttonText: typeof first.buttonText === 'string' ? first.buttonText : 'Get started',
                buttonUrl: typeof first.buttonUrl === 'string' ? first.buttonUrl : '#',
                buttonTarget: typeof first.buttonTarget === 'string' ? first.buttonTarget : '_self',
                featured: Boolean(first.featured),
                badge: typeof first.badge === 'string' ? first.badge : '',
                icon: typeof first.icon === 'string' ? first.icon : '',
                iconType: '',
                iconUrl: '',
                featuresTitle: typeof first.featuresTitle === 'string' ? first.featuresTitle : '',
                discount: typeof first.discount === 'string' ? first.discount : '',
                buttonIconType: '',
                buttonIcon: '',
                buttonIconUrl: '',
                buttonIconPosition: 'left',
                buttonHoverTextEffect: 'none',
            };
        }
    }

    return {
        title: 'Plan',
        description: '',
        monthlyPrice: '$0',
        monthlyPeriod: '/mo',
        yearlyPrice: '$0',
        yearlyPeriod: '/yr',
        features: [],
        buttonText: 'Get started',
        buttonUrl: '#',
        buttonTarget: '_self',
        featured: false,
        badge: '',
        icon: '',
        iconType: '',
        iconUrl: '',
        featuresTitle: '',
        discount: '',
        buttonIconType: '',
        buttonIcon: '',
        buttonIconUrl: '',
        buttonIconPosition: 'left',
        buttonHoverTextEffect: 'none',
    };
}

export function PricingTableContentInspector({ widget, onChange, uploadUrl }) {
    const props = widget.props || {};
    const featureIconType = ['check', 'custom'].includes(props.featureIconType)
        ? props.featureIconType
        : (typeof props.featureIconUrl === 'string' && props.featureIconUrl.trim() ? 'custom' : 'check');

    const plan = normalizePlan(props.plan, props.plans);

    const setProps = (patch) => {
        onChange({ props: { ...props, ...patch } });
    };

    const updatePlan = (patch) => {
        setProps({ plan: { ...plan, ...patch }, plans: undefined });
    };

    const features = Array.isArray(plan.features) ? plan.features : [];

    const addFeature = () => {
        updatePlan({ features: [...features, ''] });
    };

    const updateFeature = (idx, value) => {
        const next = features.slice();
        next[idx] = value;
        updatePlan({ features: next });
    };

    const removeFeature = (idx) => {
        updatePlan({ features: features.filter((_, i) => i !== idx) });
    };

    const style = ['default', 'classic', 'dark', 'header', 'minimal', 'split', 'gradient'].includes(props.style)
        ? props.style
        : 'default';

    return (
        <>
            <StyleAccordionItem title="Table Content" defaultOpen={true}>
                <div className="space-y-4 px-4">
                    <div className="space-y-2">
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Feature Icon</label>
                            <div className="flex justify-end">
                                <Select
                                    value={featureIconType}
                                    onChange={(v) =>
                                        setProps({
                                            featureIconType: v,
                                            featureIconUrl: v === 'custom' ? (props.featureIconUrl || '') : '',
                                        })
                                    }
                                    options={[
                                        { value: 'check', label: 'Check' },
                                        { value: 'custom', label: 'Custom Upload' },
                                    ]}
                                />
                            </div>
                        </div>

                        {featureIconType === 'custom' ? (
                            <div>
                                <ImageUrlUploadControl
                                    value={props.featureIconUrl || ''}
                                    onChange={(v) => setProps({ featureIconType: 'custom', featureIconUrl: v })}
                                    uploadUrl={uploadUrl}
                                    onUploaded={(url) => setProps({ featureIconType: 'custom', featureIconUrl: url })}
                                    placeholder="Enter image URL"
                                    accept="image/svg+xml,image/png,image/jpeg,image/jpg,image/gif,image/webp"
                                />
                            </div>
                        ) : null}
                    </div>

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300">Align</label>
                        <div className="flex items-center justify-end gap-1">
                            {['left', 'center', 'right', 'justify'].map((val) => {
                                const active = (props.align || 'center') === val;
                                return (
                                    <button
                                        key={val}
                                        type="button"
                                        onClick={() => setProps({ align: val })}
                                        className={`p-1.5 rounded border ${active ? 'bg-primary-100 border-primary-500 text-primary-700 dark:bg-primary-900/30 dark:border-primary-400 dark:text-primary-300' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800'}`}
                                        title={val.charAt(0).toUpperCase() + val.slice(1)}
                                    >
                                        {val === 'left' ? (
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                <line x1="3" y1="6" x2="21" y2="6" />
                                                <line x1="3" y1="12" x2="15" y2="12" />
                                                <line x1="3" y1="18" x2="18" y2="18" />
                                            </svg>
                                        ) : val === 'center' ? (
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                <line x1="3" y1="6" x2="21" y2="6" />
                                                <line x1="6" y1="12" x2="18" y2="12" />
                                                <line x1="5" y1="18" x2="19" y2="18" />
                                            </svg>
                                        ) : val === 'right' ? (
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                <line x1="3" y1="6" x2="21" y2="6" />
                                                <line x1="9" y1="12" x2="21" y2="12" />
                                                <line x1="6" y1="18" x2="21" y2="18" />
                                            </svg>
                                        ) : (
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                <line x1="3" y1="6" x2="21" y2="6" />
                                                <line x1="3" y1="12" x2="21" y2="12" />
                                                <line x1="3" y1="18" x2="21" y2="18" />
                                            </svg>
                                        )}
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    <div className="space-y-3">
                        <div className="space-y-2">
                            <div className="grid grid-cols-2 gap-3 items-center">
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Icon</label>
                                <div className="flex justify-end">
                                    <Select
                                        value={['text', 'custom'].includes(plan.iconType) ? plan.iconType : (plan.icon ? 'text' : '')}
                                        onChange={(v) =>
                                            updatePlan({
                                                iconType: v,
                                                icon: v === 'text' ? (plan.icon || '') : '',
                                                iconUrl: v === 'custom' ? (plan.iconUrl || '') : '',
                                            })
                                        }
                                        options={[
                                            { value: '', label: 'None' },
                                            { value: 'text', label: 'Text / Emoji' },
                                            { value: 'custom', label: 'Custom Upload' },
                                        ]}
                                    />
                                </div>
                            </div>

                            {plan.iconType === 'text' ? (
                                <div>
                                    <Input value={plan.icon || ''} onChange={(v) => updatePlan({ icon: v, iconType: 'text' })} placeholder="Emoji or icon text" />
                                </div>
                            ) : null}

                            {plan.iconType === 'custom' ? (
                                <div>
                                    <ImageUrlUploadControl
                                        value={plan.iconUrl || ''}
                                        onChange={(v) => updatePlan({ iconType: 'custom', iconUrl: v })}
                                        uploadUrl={uploadUrl}
                                        onUploaded={(url) => updatePlan({ iconType: 'custom', iconUrl: url })}
                                        placeholder="Enter image URL"
                                        accept="image/svg+xml,image/png,image/jpeg,image/jpg,image/gif,image/webp"
                                    />
                                </div>
                            ) : null}
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                            <Input value={plan.title || ''} onChange={(v) => updatePlan({ title: v })} />
                        </div>

                        {(style === 'dark' || style === 'header' || style === 'gradient' || style === 'split') ? (
                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Badge</label>
                                <Input value={plan.badge || ''} onChange={(v) => updatePlan({ badge: v })} placeholder="Popular / Recommend" />
                            </div>
                        ) : null}

                        {style === 'header' ? (
                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Discount Badge</label>
                                <Input value={plan.discount || ''} onChange={(v) => updatePlan({ discount: v })} placeholder="29% OFF" />
                            </div>
                        ) : null}

                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Monthly Price</label>
                                <Input value={plan.monthlyPrice || ''} onChange={(v) => updatePlan({ monthlyPrice: v })} />
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Monthly Period</label>
                                <Input value={plan.monthlyPeriod || ''} onChange={(v) => updatePlan({ monthlyPeriod: v })} placeholder="/mo" />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Yearly Price</label>
                                <Input value={plan.yearlyPrice || ''} onChange={(v) => updatePlan({ yearlyPrice: v })} />
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Yearly Period</label>
                                <Input value={plan.yearlyPeriod || ''} onChange={(v) => updatePlan({ yearlyPeriod: v })} placeholder="/yr" />
                            </div>
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                            <textarea
                                value={plan.description || ''}
                                onChange={(e) => updatePlan({ description: e.target.value })}
                                rows={2}
                                className="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-sm"
                            />
                        </div>

                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Features</label>
                                <Button
                                    type="button"
                                    onClick={addFeature}
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
                            <div className="text-xs text-gray-500">Prefix with ! or - to mark a feature as excluded.</div>
                            {features.length === 0 ? <div className="text-sm text-gray-500">No features.</div> : null}
                            {features.map((f, fi) => (
                                <div key={fi} className="flex items-center gap-2">
                                    <Input value={f || ''} onChange={(v) => updateFeature(fi, v)} placeholder={`Feature ${fi + 1}`} />
                                    <Button
                                        type="button"
                                        className="text-red-600 dark:text-red-400 px-2"
                                        onClick={() => removeFeature(fi)}
                                        title="Remove"
                                        aria-label="Remove"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="lucide lucide-trash2-icon lucide-trash-2">
                                            <path d="M10 11v6"/>
                                            <path d="M14 11v6"/>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                                            <path d="M3 6h18"/>
                                            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        </svg>
                                    </Button>
                                </div>
                            ))}
                        </div>

                        {(style === 'split' || style === 'minimal') ? (
                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Features Title</label>
                                <Input value={plan.featuresTitle || ''} onChange={(v) => updatePlan({ featuresTitle: v })} placeholder="Features / What's included" />
                            </div>
                        ) : null}

                        <div className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={Boolean(plan.featured)}
                                onChange={(e) => updatePlan({ featured: e.target.checked })}
                                className="rounded border-gray-300"
                            />
                            <span className="text-sm text-gray-700 dark:text-gray-300">Highlight this plan</span>
                        </div>
                    </div>
                </div>
            </StyleAccordionItem>

            <StyleAccordionItem title="Buttons" defaultOpen={true}>
                <div className="space-y-3 px-4">
                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text</label>
                        <Input value={plan.buttonText || ''} onChange={(v) => updatePlan({ buttonText: v })} />
                    </div>

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Icon Type</label>
                        <Select
                            value={['none', 'preset', 'custom'].includes(plan.buttonIconType) ? plan.buttonIconType : 'none'}
                            onChange={(v) =>
                                updatePlan({
                                    buttonIconType: v,
                                    buttonIcon: v === 'preset' ? (plan.buttonIcon || '') : '',
                                    buttonIconUrl: v === 'custom' ? (plan.buttonIconUrl || '') : '',
                                    buttonIconPosition: ['left', 'right'].includes(plan.buttonIconPosition) ? plan.buttonIconPosition : 'left',
                                })
                            }
                            options={[
                                { value: 'none', label: 'None' },
                                { value: 'preset', label: 'Preset' },
                                { value: 'custom', label: 'Custom Upload' },
                            ]}
                        />
                    </div>

                    {plan.buttonIconType === 'preset' ? (
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Icon</label>
                            <Select
                                value={plan.buttonIcon || ''}
                                onChange={(v) => updatePlan({ buttonIconType: 'preset', buttonIcon: v, buttonIconUrl: '' })}
                                options={[
                                    { value: 'arrow-right', label: 'Arrow Right' },
                                    { value: 'arrow-left', label: 'Arrow Left' },
                                    { value: 'download', label: 'Download' },
                                    { value: 'external', label: 'External' },
                                    { value: 'check', label: 'Check' },
                                    { value: 'plus', label: 'Plus' },
                                    { value: 'heart', label: 'Heart' },
                                    { value: 'star', label: 'Star' },
                                ]}
                            />
                        </div>
                    ) : null}

                    {plan.buttonIconType === 'custom' ? (
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Icon URL</label>
                            <ImageUrlUploadControl
                                value={plan.buttonIconUrl || ''}
                                onChange={(v) => updatePlan({ buttonIconType: 'custom', buttonIconUrl: v, buttonIcon: '' })}
                                uploadUrl={uploadUrl}
                                onUploaded={(url) => updatePlan({ buttonIconType: 'custom', buttonIconUrl: url, buttonIcon: '' })}
                                placeholder="Enter image URL"
                                accept="image/svg+xml,image/png,image/jpeg,image/jpg,image/webp"
                            />
                        </div>
                    ) : null}

                    {plan.buttonIconType !== 'none' ? (
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Icon Position</label>
                            <Select
                                value={plan.buttonIconPosition || 'left'}
                                onChange={(v) => updatePlan({ buttonIconPosition: v })}
                                options={[
                                    { value: 'left', label: 'Left' },
                                    { value: 'right', label: 'Right' },
                                ]}
                            />
                        </div>
                    ) : null}

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Link</label>
                        <Input value={plan.buttonUrl || ''} onChange={(v) => updatePlan({ buttonUrl: v })} />
                    </div>

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Target</label>
                        <Select
                            value={plan.buttonTarget || '_self'}
                            onChange={(v) => updatePlan({ buttonTarget: v })}
                            options={[
                                { value: '_self', label: 'Same Tab' },
                                { value: '_blank', label: 'New Tab' },
                            ]}
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Hover Text Effect</label>
                        <Select
                            value={plan.buttonHoverTextEffect || 'none'}
                            onChange={(v) => updatePlan({ buttonHoverTextEffect: v })}
                            options={[
                                { value: 'none', label: 'None' },
                                { value: 'swap-up', label: 'Swap Up' },
                                { value: 'swap-icon', label: 'Swap Icon' },
                                { value: 'slide-icon-right', label: 'Slide Icon Right' },
                            ]}
                        />
                    </div>
                </div>
            </StyleAccordionItem>
        </>
    );
}

export function PricingTableStyleInspector({ widget, onChange, uploadUrl }) {
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

    const setProps = (patch) => {
        onChange({ props: { ...props, ...patch } });
    };

    const buttonPosition = ['default', 'center'].includes(props.buttonPosition)
        ? props.buttonPosition
        : 'default';

    return (
        <>
            <StyleAccordionItem title="Table Style" defaultOpen={true}>
                <div className="space-y-3 px-4">
                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Layout</label>
                        <Select
                            value={buttonPosition}
                            onChange={(v) => setProps({ buttonPosition: v })}
                            options={[
                                { value: 'default', label: 'Default (Button last)' },
                                { value: 'center', label: 'Button Center (before features)' },
                            ]}
                        />
                    </div>
                </div>
            </StyleAccordionItem>

            <StyleAccordionItem title="Cards" defaultOpen={true}>
                <div className="space-y-3 px-4">
                    <div className="space-y-3">
                        <div className="flex items-center justify-between">
                            <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Card Background</div>
                            <Popover
                                width={320}
                                trigger={
                                    <PopoverTriggerButton
                                        active={Boolean(
                                            props.cardBackgroundType === 'gradient' ||
                                            props.cardBackgroundType === 'image' ||
                                            (props.cardBackgroundColor && props.cardBackgroundColor.trim())
                                        )}
                                        title="Edit card background"
                                        icon={
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" /><path d="m15 5 4 4" /></svg>
                                        }
                                    />
                                }
                            >
                                <div className="space-y-3">
                                    <div className="grid grid-cols-2 gap-3 items-center">
                                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Background Type</label>
                                        <Select
                                            value={['color', 'gradient', 'image'].includes(props.cardBackgroundType) ? props.cardBackgroundType : 'color'}
                                            onChange={(v) => setProps({ cardBackgroundType: v })}
                                            options={[
                                                { value: 'color', label: 'Color' },
                                                { value: 'gradient', label: 'Gradient' },
                                                { value: 'image', label: 'Image' },
                                            ]}
                                        />
                                    </div>

                                    {props.cardBackgroundType === 'gradient' ? (
                                        <div className="space-y-3">
                                            <div className="grid grid-cols-2 gap-3 items-center">
                                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">From</label>
                                                <div className="flex justify-end">
                                                    <ColorInput value={props.cardBackgroundGradientFrom || ''} onChange={(v) => setProps({ cardBackgroundGradientFrom: v })} fallback="#111827" />
                                                </div>
                                            </div>
                                            <div className="grid grid-cols-2 gap-3 items-center">
                                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
                                                <div className="flex justify-end">
                                                    <ColorInput value={props.cardBackgroundGradientTo || ''} onChange={(v) => setProps({ cardBackgroundGradientTo: v })} fallback="#3b82f6" />
                                                </div>
                                            </div>
                                            <div className="grid grid-cols-2 gap-3 items-center">
                                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                                                <Select
                                                    value={['linear', 'radial'].includes(props.cardBackgroundGradientType) ? props.cardBackgroundGradientType : 'linear'}
                                                    onChange={(v) => setProps({ cardBackgroundGradientType: v })}
                                                    options={[
                                                        { value: 'linear', label: 'Linear' },
                                                        { value: 'radial', label: 'Radial' },
                                                    ]}
                                                />
                                            </div>
                                            {props.cardBackgroundGradientType === 'radial' ? (
                                                <div className="grid grid-cols-2 gap-3 items-center">
                                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Position</label>
                                                    <Select
                                                        value={typeof props.cardBackgroundGradientPosition === 'string' ? props.cardBackgroundGradientPosition : 'top center'}
                                                        onChange={(v) => setProps({ cardBackgroundGradientPosition: v })}
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
                                            ) : null}
                                        </div>
                                    ) : null}

                                    {props.cardBackgroundType === 'image' ? (
                                        <div>
                                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Image URL</label>
                                            <ImageUrlUploadControl
                                                value={props.cardBackgroundImageUrl || ''}
                                                onChange={(v) => setProps({ cardBackgroundImageUrl: v })}
                                                uploadUrl={uploadUrl}
                                                onUploaded={(url) => setProps({ cardBackgroundImageUrl: url })}
                                                placeholder="Enter image URL"
                                                accept="image/*"
                                            />
                                        </div>
                                    ) : null}

                                    {props.cardBackgroundType === 'color' || !props.cardBackgroundType ? (
                                        <div className="grid grid-cols-2 gap-3 items-center">
                                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Color</label>
                                            <div className="flex justify-end">
                                                <ColorInput value={props.cardBackgroundColor || ''} onChange={(v) => setProps({ cardBackgroundColor: v })} fallback="#ffffff" />
                                            </div>
                                        </div>
                                    ) : null}
                                </div>
                            </Popover>
                        </div>
                    </div>

                    <div className="space-y-3">
                        <div className="flex items-center justify-between">
                            <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Card Border</div>
                            <Popover
                                width={320}
                                trigger={
                                    <PopoverTriggerButton
                                        active={Boolean(
                                            (props.cardBorderColor && props.cardBorderColor.trim()) ||
                                            props.cardBorderWidth ||
                                            props.cardBorderWidthTop ||
                                            props.cardBorderWidthRight ||
                                            props.cardBorderWidthBottom ||
                                            props.cardBorderWidthLeft ||
                                            props.cardBorderRadius ||
                                            props.cardBorderRadiusTopLeft ||
                                            props.cardBorderRadiusTopRight ||
                                            props.cardBorderRadiusBottomRight ||
                                            props.cardBorderRadiusBottomLeft
                                        )}
                                        title="Edit card border"
                                        icon={
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" /><path d="m15 5 4 4" /></svg>
                                        }
                                    />
                                }
                            >
                                <div className="space-y-3">
                                    <div className="grid grid-cols-2 gap-3 items-center">
                                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Border Color</label>
                                        <div className="flex justify-end">
                                            <ColorInput value={props.cardBorderColor || ''} onChange={(v) => setProps({ cardBorderColor: v })} fallback="#e5e7eb" />
                                        </div>
                                    </div>

                                    <FourSideLinkedControl
                                        label="Border Width (px)"
                                        value={props}
                                        onChange={(nextProps) => setProps(nextProps)}
                                        keys={{
                                            linked: 'cardBorderWidthLinked',
                                            t: 'cardBorderWidthTop',
                                            r: 'cardBorderWidthRight',
                                            b: 'cardBorderWidthBottom',
                                            l: 'cardBorderWidthLeft',
                                            legacy: 'cardBorderWidth',
                                        }}
                                        fallbackValue={1}
                                    />

                                    <FourSideLinkedControl
                                        label="Border Radius (px)"
                                        value={props}
                                        onChange={(nextProps) => setProps(nextProps)}
                                        keys={{
                                            linked: 'cardBorderRadiusLinked',
                                            t: 'cardBorderRadiusTopLeft',
                                            r: 'cardBorderRadiusTopRight',
                                            b: 'cardBorderRadiusBottomRight',
                                            l: 'cardBorderRadiusBottomLeft',
                                            legacy: 'cardBorderRadius',
                                        }}
                                        fallbackValue={14}
                                    />
                                </div>
                            </Popover>
                        </div>
                    </div>

                    <FourSideLinkedControl
                        label="Card Padding (px)"
                        value={props}
                        onChange={(nextProps) => setProps(nextProps)}
                        keys={{
                            linked: 'cardPaddingLinked',
                            t: 'cardPaddingTop',
                            r: 'cardPaddingRight',
                            b: 'cardPaddingBottom',
                            l: 'cardPaddingLeft',
                            legacy: 'cardPadding',
                        }}
                        fallbackValue={20}
                    />

                    <div className="space-y-3 pt-2 border-t border-gray-200 dark:border-gray-700">
                        <div className="flex items-center justify-between">
                            <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Animated Border</div>
                            <Popover
                                width={320}
                                trigger={
                                    <PopoverTriggerButton
                                        active={Boolean(props.cardBorderEffect && props.cardBorderEffect !== 'none')}
                                        title="Edit animated border"
                                        icon={
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" /><path d="m15 5 4 4" /></svg>
                                        }
                                    />
                                }
                            >
                                <BorderEffectControls
                                    value={props}
                                    onChange={(nextProps) => onChange({ props: nextProps })}
                                    keys={{
                                        effect: 'cardBorderEffect',
                                        color: 'cardBorderEffectColor',
                                        color2: 'cardBorderEffectColor2',
                                        speed: 'cardBorderEffectSpeed',
                                        thickness: 'cardBorderEffectThickness',
                                    }}
                                />
                            </Popover>
                        </div>
                    </div>
                </div>
            </StyleAccordionItem>

            <StyleAccordionItem title="Content Colors" defaultOpen={true}>
                <div className="space-y-3 px-4">
                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                        <div className="flex justify-end">
                            <ColorInput value={props.titleColor || ''} onChange={(v) => setProps({ titleColor: v })} fallback="#111827" />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Icon</label>
                        <div className="flex justify-end">
                            <ColorInput value={props.iconColor || ''} onChange={(v) => setProps({ iconColor: v })} fallback="#111827" />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Price</label>
                        <div className="flex justify-end">
                            <ColorInput value={props.priceColor || ''} onChange={(v) => setProps({ priceColor: v })} fallback="#111827" />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <div className="flex justify-end">
                            <ColorInput value={props.descriptionColor || ''} onChange={(v) => setProps({ descriptionColor: v })} fallback="#6b7280" />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Features</label>
                        <div className="flex justify-end">
                            <ColorInput value={props.featureTextColor || ''} onChange={(v) => setProps({ featureTextColor: v })} fallback="#374151" />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Non Features</label>
                        <div className="flex justify-end">
                            <ColorInput value={props.excludedFeatureTextColor || ''} onChange={(v) => setProps({ excludedFeatureTextColor: v })} fallback="#6b7280" />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Features Icons</label>
                        <div className="flex justify-end">
                            <ColorInput value={props.featureIconColor || ''} onChange={(v) => setProps({ featureIconColor: v })} fallback="#10b981" />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Non Features Icons</label>
                        <div className="flex justify-end">
                            <ColorInput value={props.excludedFeatureIconColor || ''} onChange={(v) => setProps({ excludedFeatureIconColor: v })} fallback="#ef4444" />
                        </div>
                    </div>
                </div>
            </StyleAccordionItem>

            <StyleAccordionItem title="Content Typography" defaultOpen={false}>
                <div className="space-y-4 px-4">
                    {CONTENT_TYPOGRAPHY_PARTS.map((part) => (
                        <ContentTypographyRow
                            key={part.prefix}
                            label={part.label}
                            prefix={part.prefix}
                            props={props}
                            onChange={setProps}
                        />
                    ))}
                </div>
            </StyleAccordionItem>

            <StyleAccordionItem title="Button" defaultOpen={false}>
                <div className="space-y-3 px-4">
                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Button Background</label>
                        <div className="flex justify-end">
                            <ColorInput value={props.buttonBackgroundColor || ''} onChange={(v) => setProps({ buttonBackgroundColor: v })} fallback="#2563eb" />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Button Text</label>
                        <div className="flex justify-end">
                            <ColorInput value={props.buttonTextColor || ''} onChange={(v) => setProps({ buttonTextColor: v })} fallback="#ffffff" />
                        </div>
                    </div>

                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Button Radius (px)</label>
                        <Input
                            type="number"
                            value={props.buttonBorderRadius === undefined ? '' : String(props.buttonBorderRadius)}
                            onChange={(raw) => {
                                if (raw === '' || raw === null || raw === undefined) {
                                    setProps({ buttonBorderRadius: undefined });
                                    return;
                                }
                                const n = Number(raw);
                                if (!Number.isFinite(n)) return;
                                setProps({ buttonBorderRadius: Math.max(0, Math.round(n)) });
                            }}
                            placeholder="10"
                        />
                    </div>
                </div>
            </StyleAccordionItem>

            <StyleAccordionItem title="Spacing" defaultOpen={false}>
                <div className="px-4">
                    <SpacingControls
                        value={props}
                        onChange={(nextProps) => onChange({ props: nextProps })}
                        paddingKeys={paddingKeys}
                        paddingFallbackValue={props.paddingTop ?? props.paddingY ?? props.paddingX}
                        marginKeys={marginKeys}
                        marginFallbackValue={props.marginTop ?? props.marginRight ?? props.marginBottom ?? props.marginLeft}
                    />
                </div>
            </StyleAccordionItem>
        </>
    );
}

function ColorRow({ label, valueKey, style, setStyle, fallback }) {
    return (
        <div className="grid grid-cols-2 gap-3 items-center">
            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{label}</label>
            <div className="flex justify-end">
                <ColorInput value={style[valueKey] || ''} onChange={(v) => setStyle({ [valueKey]: v })} fallback={fallback} />
            </div>
        </div>
    );
}

export function PricingSectionTabsInspector({ value, onChange }) {
    const config = value && typeof value === 'object' ? value : {};
    const hasTabs = Array.isArray(config.pricingTabs) && config.pricingTabs.length > 0;
    const mode = ['switcher', 'tabs'].includes(config.pricingMode)
        ? config.pricingMode
        : (hasTabs ? 'tabs' : 'switcher');

    const tabs = hasTabs
        ? config.pricingTabs
        : [
            { id: uid('pt'), text: 'Monthly', badge: '' },
            { id: uid('pt'), text: 'Yearly', badge: '' },
        ];

    const style = config.pricingStyle && typeof config.pricingStyle === 'object' ? config.pricingStyle : {};

    const setStyle = (patch) => {
        onChange({ pricingStyle: { ...style, ...patch } });
    };

    const setTabs = (nextTabs) => {
        onChange({ pricingTabs: nextTabs });
    };

    const updateTab = (idx, patch) => {
        const next = tabs.slice();
        const curr = next[idx] && typeof next[idx] === 'object' ? next[idx] : { id: uid('pt'), text: '', badge: '' };
        next[idx] = { ...curr, ...patch };
        setTabs(next);
    };

    const addTab = () => {
        setTabs([...tabs, { id: uid('pt'), text: `Tab ${tabs.length + 1}`, badge: '' }]);
    };

    const removeTab = (idx) => {
        if (tabs.length <= 1) return;
        setTabs(tabs.filter((_, i) => i !== idx));
    };

    const moveTab = (idx, dir) => {
        const j = idx + dir;
        if (j < 0 || j >= tabs.length) return;
        const next = tabs.slice();
        const tmp = next[idx];
        next[idx] = next[j];
        next[j] = tmp;
        setTabs(next);
    };

    return (
        <div className="space-y-4">
            <div className="grid grid-cols-2 gap-3 items-center">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Display</label>
                <Select
                    value={mode}
                    onChange={(v) => {
                        const patch = { pricingMode: v };
                        if (v === 'tabs' && !hasTabs) patch.pricingTabs = tabs;
                        onChange(patch);
                    }}
                    options={[
                        { value: 'switcher', label: 'Switcher' },
                        { value: 'tabs', label: 'Tab Pills' },
                    ]}
                />
            </div>

            {mode === 'switcher' ? (
                <div className="space-y-3">
                    <div className="block text-xs font-medium text-gray-700 dark:text-gray-300">Labels</div>
                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Left Label</label>
                        <Input value={style.switcherLeftLabel || ''} onChange={(v) => setStyle({ switcherLeftLabel: v })} placeholder="Monthly" />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Left Label Sub Text</label>
                        <Input value={style.switcherLeftSubLabel || ''} onChange={(v) => setStyle({ switcherLeftSubLabel: v })} placeholder="e.g. Billed monthly" />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Right Label</label>
                        <Input value={style.switcherRightLabel || ''} onChange={(v) => setStyle({ switcherRightLabel: v })} placeholder="Yearly" />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Right Label Sub Text</label>
                        <Input value={style.switcherRightSubLabel || ''} onChange={(v) => setStyle({ switcherRightSubLabel: v })} placeholder="e.g. Save 20%" />
                    </div>
                </div>
            ) : null}

            <div className="space-y-3">
                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300">Colors</div>

                {mode === 'switcher' ? (
                    <div className="space-y-3">
                        <ColorRow label="Track" valueKey="switcherTrackColor" style={style} setStyle={setStyle} fallback="#2563eb" />
                        <ColorRow label="Knob" valueKey="switcherDotColor" style={style} setStyle={setStyle} fallback="#ffffff" />
                        <ColorRow label="Label" valueKey="switcherLabelColor" style={style} setStyle={setStyle} fallback="#4b5563" />
                        <ColorRow label="Active Label" valueKey="switcherActiveLabelColor" style={style} setStyle={setStyle} fallback="#2563eb" />
                    </div>
                ) : (
                    <div className="space-y-3">
                        <ColorRow label="Pill Background" valueKey="tabBackgroundColor" style={style} setStyle={setStyle} fallback="#f3f4f6" />
                        <ColorRow label="Pill Active Background" valueKey="tabActiveBackgroundColor" style={style} setStyle={setStyle} fallback="#2563eb" />
                        <ColorRow label="Pill Text" valueKey="tabTextColor" style={style} setStyle={setStyle} fallback="#374151" />
                        <ColorRow label="Pill Active Text" valueKey="tabActiveTextColor" style={style} setStyle={setStyle} fallback="#ffffff" />
                        <ColorRow label="Badge Background" valueKey="tabBadgeBackgroundColor" style={style} setStyle={setStyle} fallback="#dbeafe" />
                        <ColorRow label="Badge Text" valueKey="tabBadgeTextColor" style={style} setStyle={setStyle} fallback="#1d4ed8" />
                        <ColorRow label="Badge Active Background" valueKey="tabBadgeActiveBackgroundColor" style={style} setStyle={setStyle} fallback="rgba(255,255,255,0.2)" />
                        <ColorRow label="Badge Active Text" valueKey="tabBadgeActiveTextColor" style={style} setStyle={setStyle} fallback="#ffffff" />
                    </div>
                )}
            </div>

            <div className="space-y-3">
                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300">Label Typography</div>
                <TypographyPopover
                    withAlign={false}
                    value={{
                        fontFamily: style.labelFontFamily,
                        fontSize: style.labelFontSize,
                        fontWeight: style.labelFontWeight,
                        lineHeight: style.labelLineHeight,
                        letterSpacing: style.labelLetterSpacing,
                        wordSpacing: style.labelWordSpacing,
                    }}
                    onChange={(next) =>
                        setStyle({
                            labelFontFamily: next.fontFamily,
                            labelFontSize: next.fontSize === '' ? undefined : next.fontSize,
                            labelFontWeight: next.fontWeight,
                            labelLineHeight: next.lineHeight === '' ? undefined : next.lineHeight,
                            labelLetterSpacing: next.letterSpacing === '' ? undefined : next.letterSpacing,
                            labelWordSpacing: next.wordSpacing === '' ? undefined : next.wordSpacing,
                        })
                    }
                />
            </div>

            {mode === 'tabs' ? (
                <>
                    <div className="flex items-center justify-between">
                        <div className="block text-xs font-medium text-gray-700 dark:text-gray-300">Pricing Tabs</div>
                        <Button type="button" onClick={addTab} className="px-2" title="Add tab" aria-label="Add tab">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M12 5v14" />
                                <path d="M5 12h14" />
                            </svg>
                        </Button>
                    </div>

                    <div className="space-y-3">
                        {tabs.map((tab, idx) => (
                            <div key={tab.id || idx} className="rounded-md border border-gray-200 dark:border-gray-700 p-3 space-y-3">
                                <div className="flex items-center justify-between gap-2">
                                    <div className="block text-xs font-medium text-gray-700 dark:text-gray-300">Tab {idx + 1}</div>
                                    <div className="flex items-center gap-1">
                                        <Button type="button" onClick={() => moveTab(idx, -1)} disabled={idx === 0} className="px-2" title="Move up" aria-label="Move up">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>
                                        </Button>
                                        <Button type="button" onClick={() => moveTab(idx, 1)} disabled={idx === tabs.length - 1} className="px-2" title="Move down" aria-label="Move down">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>
                                        </Button>
                                        <Button type="button" onClick={() => removeTab(idx)} disabled={tabs.length <= 1} className="text-red-600 dark:text-red-400 px-2" title="Remove" aria-label="Remove">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M10 11v6"/><path d="M14 11v6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        </Button>
                                    </div>
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text</label>
                                    <Input value={typeof tab.text === 'string' ? tab.text : ''} onChange={(v) => updateTab(idx, { text: v })} placeholder="e.g. Monthly" />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Badge</label>
                                    <Input value={typeof tab.badge === 'string' ? tab.badge : ''} onChange={(v) => updateTab(idx, { badge: v })} placeholder="e.g. Save 20%" />
                                </div>
                            </div>
                        ))}
                    </div>

                    <p className="text-xs text-gray-500 dark:text-gray-400">
                        Tab 1 shows monthly prices. Other tabs switch to yearly prices.
                    </p>
                </>
            ) : null}
        </div>
    );
}
