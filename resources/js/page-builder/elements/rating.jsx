import React from 'react';
import { registerElement } from '../elementRegistry.js';
import * as CONTROL from '../controls/types.js';

const STAR_PATH = 'M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z';

function clamp(n, min, max) {
    return Math.max(min, Math.min(max, n));
}

function StarIcon({ size, color }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" width={size} height={size} viewBox="0 0 24 24" fill={color} aria-hidden="true" style={{ display: 'block' }}>
            <path d={STAR_PATH} />
        </svg>
    );
}

export function renderRatingPreview(widget) {
    const p = widget?.props || {};

    const max = clamp(Number.isFinite(Number(p.max)) ? Math.round(Number(p.max)) : 5, 1, 10);
    const rating = clamp(Number.isFinite(Number(p.rating)) ? Number(p.rating) : 4.5, 0, max);
    const size = clamp(Number.isFinite(Number(p.starSize)) ? Number(p.starSize) : 20, 8, 64);
    const gap = clamp(Number.isFinite(Number(p.gap)) ? Number(p.gap) : 4, 0, 24);
    const starColor = typeof p.starColor === 'string' && p.starColor.trim() ? p.starColor : '#F59E0B';
    const emptyColor = typeof p.emptyColor === 'string' && p.emptyColor.trim() ? p.emptyColor : '#E5E7EB';
    const align = ['left', 'center', 'right'].includes(p.align) ? p.align : 'left';
    const showValue = p.showValue === true;
    const textSize = clamp(Number.isFinite(Number(p.textSize)) ? Number(p.textSize) : 14, 10, 48);
    const textColor = typeof p.textColor === 'string' && p.textColor.trim() ? p.textColor : '#334155';

    const displayRating = Number.isInteger(rating) ? String(rating) : rating.toFixed(1);

    return (
        <div style={{ display: 'flex', alignItems: 'center', gap: `${gap}px`, width: '100%', justifyContent: align === 'right' ? 'flex-end' : align === 'center' ? 'center' : 'flex-start', flexWrap: 'wrap' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: `${gap}px` }}>
                {Array.from({ length: max }, (_, i) => {
                    const fill = clamp(rating - i, 0, 1);
                    return (
                        <span key={i} style={{ position: 'relative', display: 'inline-block', width: size, height: size, lineHeight: 0 }}>
                            <StarIcon size={size} color={emptyColor} />
                            {fill > 0 ? (
                                <span style={{ position: 'absolute', left: 0, top: 0, height: size, width: `${fill * 100}%`, overflow: 'hidden', display: 'block', lineHeight: 0 }}>
                                    <StarIcon size={size} color={starColor} />
                                </span>
                            ) : null}
                        </span>
                    );
                })}
            </div>
            {showValue ? (
                <span style={{ fontSize: textSize, lineHeight: 1.2, color: textColor, fontWeight: 600 }}>
                    {displayRating}/{max}
                </span>
            ) : null}
        </div>
    );
}

const ratingSchema = {
    content: [
        { type: CONTROL.CONTROL_SLIDER, name: 'rating', label: 'Rating', bind: 'props.rating', min: 0, max: 10, step: 0.1, units: [''] },
        { type: CONTROL.CONTROL_NUMBER, name: 'max', label: 'Max Stars', bind: 'props.max', min: 1, max: 10, step: 1 },
        { type: CONTROL.CONTROL_SWITCHER, name: 'showValue', label: 'Show Value', bind: 'props.showValue' },
        { type: CONTROL.CONTROL_SELECT, name: 'align', label: 'Align', bind: 'props.align', options: [{ value: 'left', label: 'Left' }, { value: 'center', label: 'Center' }, { value: 'right', label: 'Right' }] },
    ],
    style: [
        { type: CONTROL.CONTROL_SLIDER, name: 'starSize', label: 'Star Size', bind: 'props.starSize', kind: 'default', max: 64 },
        { type: CONTROL.CONTROL_SLIDER, name: 'gap', label: 'Spacing', bind: 'props.gap', kind: 'default', max: 24 },
        { type: CONTROL.CONTROL_COLOR, name: 'starColor', label: 'Star Color', bind: 'props.starColor' },
        { type: CONTROL.CONTROL_COLOR, name: 'emptyColor', label: 'Empty Star Color', bind: 'props.emptyColor' },
        { type: CONTROL.CONTROL_COLOR, name: 'textColor', label: 'Text Color', bind: 'props.textColor' },
        { type: CONTROL.CONTROL_SLIDER, name: 'textSize', label: 'Text Size', bind: 'props.textSize', kind: 'font-size' },
    ],
    advanced: [],
};

registerElement({
    type: 'rating',
    label: 'Rating',
    group: 'Basic',
    defaultProps: {
        rating: 4.5,
        max: 5,
        starSize: 20,
        gap: 4,
        starColor: '#F59E0B',
        emptyColor: '#E5E7EB',
        align: 'left',
        showValue: false,
        textSize: 14,
        textColor: '#334155',
    },
    render: renderRatingPreview,
    schema: ratingSchema,
});
