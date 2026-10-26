import React from 'react';
import { Input, Select } from '../ui/primitives.jsx';
import { ColorInput } from '../ui/color.jsx';
import { DimensionsControls } from '../reusable/dimensionsControls.jsx';
import { MultiImageUploadControl } from '../reusable/imageUploadControl.jsx';

export function SliderContentInspector({ widget, onChange, uploadUrl }) {
    const props = widget.props || {};
    const images = Array.isArray(props.images) ? props.images : [];

    return (
        <div className="space-y-3">
            <div className="space-y-2">
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Images</label>
                <MultiImageUploadControl
                    value={images}
                    onChange={(next) => onChange({ props: { ...props, images: next } })}
                    uploadUrl={uploadUrl}
                    accept="image/png,image/jpeg,image/jpg,image/webp,image/gif,image/svg+xml"
                />
            </div>
        </div>
    );
}

export function SliderStyleInspector({ widget, onChange }) {
    const props = widget.props || {};

    const setProps = (patch) => {
        onChange({ props: { ...props, ...patch } });
    };

    const perPageRaw = Number(props.carouselPerPage);
    const perPage = Number.isFinite(perPageRaw) ? Math.max(1, Math.min(6, Math.round(perPageRaw))) : 1;

    const gapRaw = Number(props.carouselGap);
    const gap = Number.isFinite(gapRaw) ? Math.max(0, Math.min(120, Math.round(gapRaw))) : 16;

    const intervalRaw = Number(props.carouselInterval);
    const interval = Number.isFinite(intervalRaw) ? Math.max(1, Math.min(60, intervalRaw)) : 4;

    const arrowSizeRaw = Number(props.arrowSize);
    const arrowSize = Number.isFinite(arrowSizeRaw) ? Math.max(24, Math.min(72, Math.round(arrowSizeRaw))) : 36;

    const arrowRadiusRaw = Number(props.arrowRadius);
    const arrowRadius = Number.isFinite(arrowRadiusRaw) ? Math.max(0, Math.min(999, Math.round(arrowRadiusRaw))) : 999;

    return (
        <div className="space-y-4">
            <div className="space-y-3">
                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Layout</div>

                <DimensionsControls
                    value={props}
                    onChange={(nextProps) => onChange({ props: nextProps })}
                    widthLabel="Width (px)"
                    heightLabel="Height (px)"
                    widthPlaceholder="Auto"
                    heightPlaceholder="360"
                    useSliders={true}
                    widthMin={0}
                    widthMax={1200}
                    widthStep={1}
                    heightMin={120}
                    heightMax={900}
                    heightStep={1}
                />

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Items per page</label>
                    <Input
                        type="number"
                        value={props.carouselPerPage !== undefined ? String(perPage) : ''}
                        onChange={(v) => {
                            if (v === '' || v === null || v === undefined) {
                                setProps({ carouselPerPage: undefined });
                                return;
                            }
                            const n = Number(v);
                            setProps({ carouselPerPage: Number.isFinite(n) ? Math.max(1, Math.min(6, Math.round(n))) : 1 });
                        }}
                        placeholder="1"
                    />
                </div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Gap (px)</label>
                    <Input
                        type="number"
                        value={props.carouselGap !== undefined ? String(gap) : ''}
                        onChange={(v) => {
                            if (v === '' || v === null || v === undefined) {
                                setProps({ carouselGap: undefined });
                                return;
                            }
                            const n = Number(v);
                            setProps({ carouselGap: Number.isFinite(n) ? Math.max(0, Math.min(120, Math.round(n))) : 16 });
                        }}
                        placeholder="16"
                    />
                </div>
            </div>

            <div className="space-y-3">
                <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Carousel</div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Autoplay</label>
                    <div className="flex justify-end">
                        <input
                            type="checkbox"
                            checked={props.carouselAutoplay !== false}
                            onChange={(e) => setProps({ carouselAutoplay: e.target.checked })}
                            className="rounded border-gray-300"
                        />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Interval (s)</label>
                    <Input
                        type="number"
                        value={props.carouselInterval !== undefined ? String(interval) : ''}
                        onChange={(raw) => {
                            const n = raw === '' ? undefined : Number(raw);
                            setProps({ carouselInterval: Number.isFinite(n) ? Math.max(1, Math.min(60, n)) : undefined });
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
                            onChange={(e) => setProps({ carouselArrows: e.target.checked })}
                            className="rounded border-gray-300"
                        />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-3 items-center">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Ease</label>
                    <Select
                        value={props.carouselEase || 'ease'}
                        onChange={(v) => setProps({ carouselEase: v })}
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

            {props.carouselArrows !== false ? (
                <div className="space-y-3">
                    <div className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Arrow Styling</div>

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Size (px)</label>
                        <Input
                            type="number"
                            value={props.arrowSize !== undefined ? String(arrowSize) : ''}
                            onChange={(v) => {
                                if (v === '' || v === null || v === undefined) {
                                    setProps({ arrowSize: undefined });
                                    return;
                                }
                                const n = Number(v);
                                setProps({ arrowSize: Number.isFinite(n) ? Math.max(24, Math.min(72, Math.round(n))) : 36 });
                            }}
                            placeholder="36"
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Radius (px)</label>
                        <Input
                            type="number"
                            value={props.arrowRadius !== undefined ? String(arrowRadius) : ''}
                            onChange={(v) => {
                                if (v === '' || v === null || v === undefined) {
                                    setProps({ arrowRadius: undefined });
                                    return;
                                }
                                const n = Number(v);
                                setProps({ arrowRadius: Number.isFinite(n) ? Math.max(0, Math.min(999, Math.round(n))) : 999 });
                            }}
                            placeholder="999"
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Background</label>
                        <div className="flex justify-end">
                            <ColorInput
                                value={typeof props.arrowBgColor === 'string' ? props.arrowBgColor : ''}
                                onChange={(v) => setProps({ arrowBgColor: v })}
                                fallback="#ffffff"
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Icon Color</label>
                        <div className="flex justify-end">
                            <ColorInput
                                value={typeof props.arrowColor === 'string' ? props.arrowColor : ''}
                                onChange={(v) => setProps({ arrowColor: v })}
                                fallback="#111827"
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Border</label>
                        <div className="flex justify-end">
                            <ColorInput
                                value={typeof props.arrowBorderColor === 'string' ? props.arrowBorderColor : ''}
                                onChange={(v) => setProps({ arrowBorderColor: v })}
                                fallback="rgba(0,0,0,0.12)"
                            />
                        </div>
                    </div>
                </div>
            ) : null}
        </div>
    );
}
