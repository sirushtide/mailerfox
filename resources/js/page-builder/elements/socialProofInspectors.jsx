import React from 'react';
import { Input, Select } from '../ui/primitives.jsx';
import { ColorInput } from '../ui/color.jsx';
import { MultiImageUploadControl } from '../reusable/imageUploadControl.jsx';
import { StyleAccordionItem } from '../controls/accordion.jsx';
import { SliderInput } from '../controls/SliderInput.jsx';
import { Switcher } from '../controls/Switcher.jsx';

export function SocialProofContentInspector({ widget, onChange, uploadUrl }) {
    const props = widget.props || {};
    const avatars = Array.isArray(props.avatars) ? props.avatars : [];

    function set(patch) {
        onChange({ props: { ...props, ...patch } });
    }

    function updateAvatars(next) {
        set({ avatars: next });
    }

    return (
        <div className="space-y-3 px-4">
            <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text</label>
                <Input value={String(props.text ?? '')} onChange={(v) => set({ text: v })} placeholder="Trusted by 1000+ clients" />
            </div>

            <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Rating (0-5)</label>
                <SliderInput
                    value={props.rating ?? 5}
                    onChange={(v) => set({ rating: Math.max(0, Math.min(5, Number(v) || 0)) })}
                    min={0}
                    max={5}
                    step={1}
                    units={['']}
                />
            </div>

            <div className="flex items-center justify-between">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Layout</label>
                <div className="w-40">
                    <Select
                        value={props.layout || 'inline'}
                        onChange={(v) => set({ layout: v })}
                        options={[
                            { value: 'inline', label: 'Inline (avatars + rating)' },
                            { value: 'stacked', label: 'Stacked' },
                        ]}
                    />
                </div>
            </div>

            <div className="space-y-3">
                <Switcher
                    label="Show avatars"
                    value={props.showAvatars !== false}
                    onChange={(v) => set({ showAvatars: v })}
                />
                <Switcher
                    label="Show stars"
                    value={props.showStars !== false}
                    onChange={(v) => set({ showStars: v })}
                />
            </div>

            <div className="flex items-center justify-between">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Align</label>
                <div className="w-40">
                    <Select
                        value={props.align || 'left'}
                        onChange={(v) => set({ align: v })}
                        options={[
                            { value: 'left', label: 'Left' },
                            { value: 'center', label: 'Center' },
                            { value: 'right', label: 'Right' },
                        ]}
                    />
                </div>
            </div>

            <div className="space-y-2">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Avatars</label>
                <MultiImageUploadControl
                    value={avatars.map((av) => (typeof av === 'string' ? { url: av } : { url: av && av.image ? av.image : '' }))}
                    onChange={(next) => updateAvatars(next.map((it) => ({ image: it.url || '' })))}
                    uploadUrl={uploadUrl}
                />
            </div>
        </div>
    );
}

export function SocialProofStyleInspector({ widget, onChange }) {
    const props = widget.props || {};

    function set(patch) {
        onChange({ props: { ...props, ...patch } });
    }

    return (
        <>
            <StyleAccordionItem title="Sizes" defaultOpen={true}>
                <div className="space-y-4 px-4">
                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Avatar Size (px)</label>
                        <SliderInput
                            value={props.avatarSize ?? 48}
                            onChange={(v) => set({ avatarSize: Math.max(20, Math.min(96, Number(v) || 48)) })}
                            min={20}
                            max={96}
                            step={1}
                            units={['px']}
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Star Size (px)</label>
                        <SliderInput
                            value={props.starSize ?? 22}
                            onChange={(v) => set({ starSize: Math.max(10, Math.min(48, Number(v) || 22)) })}
                            min={10}
                            max={48}
                            step={1}
                            units={['px']}
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text Size (px)</label>
                        <SliderInput
                            value={props.textSize ?? 18}
                            onChange={(v) => set({ textSize: Math.max(10, Math.min(48, Number(v) || 18)) })}
                            min={10}
                            max={48}
                            step={1}
                            units={['px']}
                        />
                    </div>
                </div>
            </StyleAccordionItem>

            <StyleAccordionItem title="Colors" defaultOpen={true}>
                <div className="space-y-3 px-4">
                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Star Color</label>
                        <div className="flex justify-end">
                            <ColorInput
                                value={String(props.starColor ?? '#F59E0B')}
                                onChange={(v) => set({ starColor: v })}
                            />
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text Color</label>
                        <div className="flex justify-end">
                            <ColorInput
                                value={String(props.textColor ?? '#334155')}
                                onChange={(v) => set({ textColor: v })}
                            />
                        </div>
                    </div>
                </div>
            </StyleAccordionItem>
        </>
    );
}
