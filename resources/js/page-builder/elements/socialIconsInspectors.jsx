import React from 'react';
import { Button, Input, Select, Textarea, Range } from '../ui/primitives.jsx';
import { BackgroundControls } from '../reusable/backgroundControls.jsx';
import { ColorInput } from '../ui/color.jsx';
import { Popover, PopoverTriggerButton } from '../ui/popover.jsx';
import { StyleAccordionItem } from '../controls/accordion.jsx';

const PenIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="lucide lucide-pencil-icon lucide-pencil">
        <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
        <path d="m15 5 4 4" />
    </svg>
);

function IconBorderPopover({ label, value, onChange, keys }) {
    const v = value || {};
    const set = (patch) => onChange({ ...v, ...patch });

    const style = v[keys.style] || '';
    const color = v[keys.color] || '';
    const width = typeof v[keys.width] === 'number' ? v[keys.width] : 0;
    const radius = typeof v[keys.radius] === 'number' ? v[keys.radius] : 0;

    return (
        <Popover
            width={260}
            trigger={
                <PopoverTriggerButton
                    active={!!style || !!color || width > 0 || radius > 0}
                    title={`Edit ${label}`}
                    icon={<PenIcon />}
                />
            }
        >
            <div className="space-y-3 p-2">
                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Style</label>
                    <Select
                        value={style}
                        onChange={(x) => set({ [keys.style]: x })}
                        options={[
                            { value: '', label: 'None' },
                            { value: 'solid', label: 'Solid' },
                            { value: 'dashed', label: 'Dashed' },
                            { value: 'dotted', label: 'Dotted' },
                        ]}
                    />
                </div>
                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Color</label>
                    <ColorInput
                        value={color}
                        onChange={(x) => set({ [keys.color]: x })}
                        fallback="#000000"
                        variant="minimal"
                    />
                </div>
                <div>
                    <div className="flex items-center justify-between mb-1">
                        <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Width</label>
                        <span className="text-xs font-medium text-gray-700 dark:text-gray-300">{width}px</span>
                    </div>
                    <Range
                        min={0}
                        max={20}
                        step={1}
                        value={String(width)}
                        onChange={(e) => set({ [keys.width]: Number(e.target.value) })}
                    />
                </div>
                <div>
                    <div className="flex items-center justify-between mb-1">
                        <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Radius</label>
                        <span className="text-xs font-medium text-gray-700 dark:text-gray-300">{radius}px</span>
                    </div>
                    <Range
                        min={0}
                        max={50}
                        step={1}
                        value={String(radius)}
                        onChange={(e) => set({ [keys.radius]: Number(e.target.value) })}
                    />
                </div>
            </div>
        </Popover>
    );
}

export function SocialIconsContentInspector({ widget, onChange }) {
    const props = widget.props || {};
    const icons = Array.isArray(props.icons) ? props.icons : [];

    function updateIcons(next) {
        onChange({ props: { ...props, icons: next } });
    }

    function addIcon() {
        updateIcons([...icons, { svg: '', url: '', target: '_blank' }]);
    }

    function updateIcon(index, patch) {
        const next = icons.map((ic, i) => (i === index ? { ...ic, ...patch } : ic));
        updateIcons(next);
    }

    function removeIcon(index) {
        const next = icons.filter((_, i) => i !== index);
        updateIcons(next);
    }

    const sizeValue = props.size ?? 20;
    const gapValue = props.gap ?? 16;

    return (
        <div className="space-y-4 px-4">
            <div className="flex items-center justify-between gap-3">
                <label className="text-xs font-medium text-gray-700 dark:text-gray-300 shrink-0">Size (px)</label>
                <div className="flex items-center gap-2 flex-1 max-w-[200px]">
                    <Range
                        min={10}
                        max={80}
                        step={1}
                        value={String(sizeValue)}
                        onChange={(e) => onChange({ props: { ...props, size: Number(e.target.value) } })}
                    />
                    <span className="text-xs font-medium text-gray-700 dark:text-gray-300 w-10 text-right">{sizeValue}px</span>
                </div>
            </div>
            <div className="flex items-center justify-between gap-3">
                <label className="text-xs font-medium text-gray-700 dark:text-gray-300 shrink-0">Gap (px)</label>
                <div className="flex items-center gap-2 flex-1 max-w-[200px]">
                    <Range
                        min={0}
                        max={80}
                        step={1}
                        value={String(gapValue)}
                        onChange={(e) => onChange({ props: { ...props, gap: Number(e.target.value) } })}
                    />
                    <span className="text-xs font-medium text-gray-700 dark:text-gray-300 w-10 text-right">{gapValue}px</span>
                </div>
            </div>
            <div className="flex items-center justify-between gap-3">
                <label className="text-xs font-medium text-gray-700 dark:text-gray-300 shrink-0">Align</label>
                <div className="w-32">
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

            <div className="space-y-2">
                <div className="flex items-center justify-between">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Icons</label>
                    <Button type="button" variant="secondary" onClick={addIcon}>Add</Button>
                </div>
                {icons.map((icon, idx) => (
                    <div key={idx} className="space-y-2 border border-gray-200 dark:border-gray-700 rounded-md p-2">
                        <Textarea
                            value={String(icon.svg || '')}
                            onChange={(v) => updateIcon(idx, { svg: v })}
                            rows={3}
                            placeholder="Paste SVG markup..."
                        />
                        <div className="grid grid-cols-[1fr_120px_auto] gap-2 items-center">
                            <Input value={String(icon.url || '')} onChange={(v) => updateIcon(idx, { url: v })} placeholder="URL" />
                            <Select
                                value={String(icon.target || '_blank')}
                                onChange={(v) => updateIcon(idx, { target: v })}
                                options={[
                                    { value: '_blank', label: 'New tab' },
                                    { value: '_self', label: 'Same tab' },
                                    { value: '_parent', label: 'Parent' },
                                    { value: '_top', label: 'Top' },
                                ]}
                            />
                            <button type="button" className="text-xs text-red-600 px-1" onClick={() => removeIcon(idx)}>Remove</button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

export function SocialIconsStyleInspector({ widget, onChange, globals, uploadUrl }) {
    const props = widget.props || {};
    const set = (patch) => onChange({ props: { ...props, ...patch } });

    function stateKeys(state) {
        return {
            bg: {
                type: `${state}BgType`,
                color: `${state}BgColor`,
                from: `${state}BgGradientFrom`,
                to: `${state}BgGradientTo`,
                fromLocation: `${state}BgGradientFromLocation`,
                toLocation: `${state}BgGradientToLocation`,
                gradientType: `${state}BgGradientType`,
                position: `${state}BgGradientPosition`,
                angle: `${state}BgGradientAngle`,
            },
            border: {
                style: `${state}BorderStyle`,
                color: `${state}BorderColor`,
                width: `${state}BorderWidth`,
                radius: `${state}BorderRadius`,
            },
        };
    }

    function renderStateSection(title, state) {
        const keys = stateKeys(state);
        return (
            <StyleAccordionItem title={title} defaultOpen={true}>
                <div className="space-y-4 px-4 pb-3">
                    <div className="flex items-center justify-between gap-3">
                        <label className="text-xs font-medium text-gray-700 dark:text-gray-300 shrink-0">Background</label>
                        <Popover
                            width={340}
                            trigger={
                                <PopoverTriggerButton
                                    active={props[keys.bg.type] === 'gradient' || (typeof props[keys.bg.color] === 'string' && props[keys.bg.color].trim())}
                                    title="Edit background"
                                    icon={<PenIcon />}
                                />
                            }
                        >
                            <BackgroundControls value={props} onChange={set} keys={keys.bg} />
                        </Popover>
                    </div>
                    <div className="flex items-center justify-between gap-3">
                        <label className="text-xs font-medium text-gray-700 dark:text-gray-300 shrink-0">Border</label>
                        <IconBorderPopover label="Border" value={props} onChange={set} keys={keys.border} />
                    </div>
                    <div className="flex items-center justify-between gap-3">
                        <label className="text-xs font-medium text-gray-700 dark:text-gray-300 shrink-0">Icon Color</label>
                        <ColorInput
                            value={props[`${state}Color`] || ''}
                            onChange={(x) => set({ [`${state}Color`]: x })}
                            fallback="#000000"
                            variant="minimal"
                        />
                    </div>
                </div>
            </StyleAccordionItem>
        );
    }

    return (
        <>
            {renderStateSection('Normal', 'icon')}
            {renderStateSection('Hover', 'iconHover')}
        </>
    );
}
