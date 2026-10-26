import React from 'react';
import { Range, Select, Input } from '../ui/primitives.jsx';
import { ColorInput } from '../ui/color.jsx';
import { Popover, PopoverTriggerButton } from '../ui/popover.jsx';
import { ImageUrlUploadControl } from './imageUploadControl.jsx';

const DEFAULT_BACKGROUND_KEYS = {
    type: 'type',
    color: 'color',
    from: 'from',
    to: 'to',
    fromLocation: 'fromLocation',
    toLocation: 'toLocation',
    gradientType: 'gradientType',
    position: 'position',
    angle: 'angle',
    backdropBlur: 'backdropBlur',
    image: 'image',
    imageSize: 'imageSize',
    imagePosition: 'imagePosition',
    imageRepeat: 'imageRepeat',
};

export function BackgroundControls({
    value,
    onChange,
    uploadUrl = '',
    allowImage = false,
    keys: keysProp,
}) {
    const keys = { ...DEFAULT_BACKGROUND_KEYS, ...(keysProp || {}) };
    const v = value || {};
    const backgroundType = allowImage
        ? ((v[keys.type] === 'gradient' || v[keys.type] === 'image') ? v[keys.type] : 'color')
        : (v[keys.type] === 'gradient' ? 'gradient' : 'color');
    const gradientType = (v[keys.gradientType] === 'radial' || v[keys.gradientType] === 'linear') ? v[keys.gradientType] : 'linear';

    const imageUrl = v[keys.image] && typeof v[keys.image] === 'object' ? String(v[keys.image].url || '') : '';

    const fromLoc = Number.isFinite(Number(v[keys.fromLocation])) ? Number(v[keys.fromLocation]) : 0;
    const toLoc = Number.isFinite(Number(v[keys.toLocation])) ? Number(v[keys.toLocation]) : 100;
    const angleVal = Number.isFinite(Number(v[keys.angle])) ? Number(v[keys.angle]) : 135;

    const set = (patch) => {
        onChange({ ...v, ...patch });
    };

    const backdropBlurKey = keys.backdropBlur || 'backdropBlur';
    const backdropBlur = Number.isFinite(Number(v[backdropBlurKey])) ? Number(v[backdropBlurKey]) : 0;

    const clamp01 = (n) => {
        if (!Number.isFinite(n)) return 0;
        return Math.max(0, Math.min(100, n));
    };

    const locationRow = (label, key, curr) => (
        <div>
            <div className="grid grid-cols-2 gap-3 items-center mb-2">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{label}</label>
                <div className="flex items-center justify-end gap-2">
                    <span className="text-xs text-gray-500 dark:text-gray-400">%</span>
                </div>
            </div>
            <div className="flex items-center gap-2">
                <Range
                    min="0"
                    max="100"
                    value={String(curr)}
                    onChange={(e) => {
                        const n = clamp01(Number(e.target.value));
                        set({ [key]: n });
                    }}
                    className="flex-1"
                />
                <input
                    type="number"
                    min="0"
                    max="100"
                    value={String(curr)}
                    onChange={(e) => {
                        const n = clamp01(Number(e.target.value));
                        set({ [key]: n });
                    }}
                    className="w-20 px-2 py-1 text-sm border rounded-lg"
                />
            </div>
        </div>
    );

    return (
        <div className="space-y-3">
            <div>
                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                    <Select
                        value={backgroundType}
                        onChange={(x) => set({ [keys.type]: x })}
                        options={[
                            { value: 'color', label: 'Classic' },
                            { value: 'gradient', label: 'Gradient' },
                            ...(allowImage ? [{ value: 'image', label: 'Image' }] : []),
                        ]}
                    />
                </div>
            </div>

            {backgroundType === 'color' ? (
                <div>
                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Color</label>
                        <div className="flex justify-end">
                            <ColorInput
                                value={typeof v[keys.color] === 'string' ? v[keys.color] : ''}
                                onChange={(x) => set({ [keys.color]: x })}
                                fallback="#ffffff"
                                variant="minimal"
                            />
                        </div>
                    </div>
                </div>
            ) : null}

            {backgroundType === 'gradient' ? (
                <div className="space-y-3">
                    <div>
                        <div className="grid grid-cols-2 gap-3 items-center mb-2">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">From</label>
                            <div className="flex justify-end">
                                <ColorInput
                                    value={typeof v[keys.from] === 'string' ? v[keys.from] : ''}
                                    onChange={(x) => set({ [keys.from]: x })}
                                    fallback="#111827"
                                    variant="minimal"
                                />
                            </div>
                        </div>
                    </div>

                    {locationRow('From Location', keys.fromLocation, fromLoc)}

                    <div>
                        <div className="grid grid-cols-2 gap-3 items-center mb-2">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
                            <div className="flex justify-end">
                                <ColorInput
                                    value={typeof v[keys.to] === 'string' ? v[keys.to] : ''}
                                    onChange={(x) => set({ [keys.to]: x })}
                                    fallback="#3b82f6"
                                    variant="minimal"
                                />
                            </div>
                        </div>
                    </div>

                    {locationRow('To Location', keys.toLocation, toLoc)}

                    <div>
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                            <Select
                                value={gradientType}
                                onChange={(x) => set({ [keys.gradientType]: x })}
                                options={[
                                    { value: 'linear', label: 'Linear' },
                                    { value: 'radial', label: 'Radial' },
                                ]}
                            />
                        </div>
                    </div>

                    {gradientType === 'radial' ? (
                        <div>
                            <div className="grid grid-cols-2 gap-3 items-center">
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Position</label>
                                <Select
                                    value={typeof v[keys.position] === 'string' && v[keys.position] ? v[keys.position] : 'top center'}
                                    onChange={(x) => set({ [keys.position]: x })}
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
                        </div>
                    ) : null}

                    {gradientType === 'linear' ? (
                        <div className="grid grid-cols-2 gap-3 items-center">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Angle</label>
                            <Input
                                type="number"
                                value={String(angleVal)}
                                onChange={(v) => set({ [keys.angle]: Number(v) })}
                            />
                        </div>
                    ) : null}
                </div>
            ) : null}

            {allowImage && backgroundType === 'image' ? (
                <div className="space-y-3">
                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Image</label>
                        <ImageUrlUploadControl
                            value={imageUrl}
                            onChange={(url) => set({ [keys.image]: { url: typeof url === 'string' ? url : '' } })}
                            uploadUrl={uploadUrl}
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Size</label>
                        <Select
                            value={typeof v[keys.imageSize] === 'string' && v[keys.imageSize] ? v[keys.imageSize] : 'cover'}
                            onChange={(x) => set({ [keys.imageSize]: x })}
                            options={[
                                { value: 'cover', label: 'Cover' },
                                { value: 'contain', label: 'Contain' },
                                { value: 'auto', label: 'Auto' },
                                { value: '100% 100%', label: 'Stretch' },
                            ]}
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Position</label>
                        <Select
                            value={typeof v[keys.imagePosition] === 'string' && v[keys.imagePosition] ? v[keys.imagePosition] : 'center'}
                            onChange={(x) => set({ [keys.imagePosition]: x })}
                            options={[
                                { value: 'center', label: 'Center' },
                                { value: 'top', label: 'Top' },
                                { value: 'bottom', label: 'Bottom' },
                                { value: 'left', label: 'Left' },
                                { value: 'right', label: 'Right' },
                                { value: 'top left', label: 'Top Left' },
                                { value: 'top right', label: 'Top Right' },
                                { value: 'bottom left', label: 'Bottom Left' },
                                { value: 'bottom right', label: 'Bottom Right' },
                            ]}
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Repeat</label>
                        <Select
                            value={typeof v[keys.imageRepeat] === 'string' && v[keys.imageRepeat] ? v[keys.imageRepeat] : 'no-repeat'}
                            onChange={(x) => set({ [keys.imageRepeat]: x })}
                            options={[
                                { value: 'no-repeat', label: 'No Repeat' },
                                { value: 'repeat', label: 'Repeat' },
                                { value: 'repeat-x', label: 'Repeat X' },
                                { value: 'repeat-y', label: 'Repeat Y' },
                            ]}
                        />
                    </div>
                </div>
            ) : null}

            <div>
                <div className="flex items-center justify-between mb-1">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Backdrop Blur</label>
                    <span className="text-xs font-medium text-gray-700 dark:text-gray-300">{backdropBlur}px</span>
                </div>
                <Range
                    min={0}
                    max={40}
                    step={1}
                    value={String(backdropBlur)}
                    onChange={(e) => set({ [backdropBlurKey]: Number(e.target.value) })}
                />
            </div>
        </div>
    );
}

const PenIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="lucide lucide-pencil-icon lucide-pencil">
        <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
        <path d="m15 5 4 4" />
    </svg>
);

export function BackgroundPopover({ label, value, onChange, keys, active, children, uploadUrl = '', allowImage = false }) {
    const v = value || {};
    const k = keys || {};
    const hasBg =
        typeof active === 'boolean'
            ? active
            : v[k.type] === 'gradient'
                || (allowImage && v[k.type] === 'image')
                || (typeof v[k.color || 'color'] === 'string' && v[k.color || 'color'].trim())
                || Number(v[k.backdropBlur || 'backdropBlur']) > 0;

    return (
        <div className="flex items-center justify-between">
            <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{label}</div>
            <Popover
                width={340}
                trigger={
                    <PopoverTriggerButton
                        active={hasBg}
                        title={`Edit ${typeof label === 'string' ? label.toLowerCase() : 'background'}`}
                        icon={<PenIcon />}
                    />
                }
            >
                {children || <BackgroundControls value={value} onChange={onChange} keys={keys} uploadUrl={uploadUrl} allowImage={allowImage} />}
            </Popover>
        </div>
    );
}
