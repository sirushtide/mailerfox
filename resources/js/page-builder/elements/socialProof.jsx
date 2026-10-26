import React from 'react';
import * as CONTROL from '../controls/types.js';
import { registerElement } from '../elementRegistry.js';
import { resolveColorToken } from '../controls/bind.js';

function Star({ size, color, filled }) {
    return (
        <svg
            width={size}
            height={size}
            viewBox="0 0 24 24"
            fill={filled ? color : 'none'}
            stroke={color}
            strokeWidth="1.5"
            style={{ flexShrink: 0 }}
        >
            <path d="M12 2.5l2.9 5.88 6.49.94-4.7 4.58 1.11 6.46L12 17.3l-5.8 3.06 1.11-6.46-4.7-4.58 6.49-.94z" />
        </svg>
    );
}

export function renderSocialProofPreview(widget, helpers = {}) {
    const p = widget.props || {};
    const globals = helpers.globals || {};
    const resolveColor = (val, fallback) => {
        const resolved = resolveColorToken(val, globals);
        return typeof resolved === 'string' && resolved.trim() ? resolved : fallback;
    };
    const avatars = Array.isArray(p.avatars) ? p.avatars : [];
    const rating = Number.isFinite(Number(p.rating)) ? Math.max(0, Math.min(5, Number(p.rating))) : 5;
    const showStars = p.showStars !== false;
    const showAvatars = p.showAvatars !== false;
    const text = typeof p.text === 'string' ? p.text : 'Trusted by 1000+ clients';
    const align = ['left', 'center', 'right'].includes(p.align) ? p.align : 'left';
    const layout = p.layout === 'stacked' ? 'stacked' : 'inline';
    const avatarSize = Number.isFinite(Number(p.avatarSize)) ? Math.max(20, Math.min(96, Number(p.avatarSize))) : 48;
    const starSize = Number.isFinite(Number(p.starSize)) ? Math.max(10, Math.min(48, Number(p.starSize))) : 22;
    const starColor = resolveColor(p.starColor, '#F59E0B');
    const textColor = resolveColor(p.textColor, '#334155');
    const textSize = Number.isFinite(Number(p.textSize)) ? Math.max(10, Math.min(48, Number(p.textSize))) : 18;
    const overlap = Math.round(avatarSize * 0.32);

    const justify = align === 'center' ? 'center' : align === 'right' ? 'flex-end' : 'flex-start';

    const avatarStack = showAvatars && avatars.length > 0 ? (
        <div style={{ display: 'flex', alignItems: 'center' }}>
            {avatars.map((av, i) => {
                const src = typeof av === 'string' ? av : av && av.image ? av.image : '';
                return (
                    <div
                        key={i}
                        style={{
                            width: `${avatarSize}px`,
                            height: `${avatarSize}px`,
                            borderRadius: '9999px',
                            border: '2px solid #ffffff',
                            boxShadow: '0 1px 2px rgba(0,0,0,0.08)',
                            marginLeft: i === 0 ? 0 : `-${overlap}px`,
                            backgroundColor: '#e2e8f0',
                            backgroundImage: src ? `url("${src}")` : 'none',
                            backgroundSize: 'cover',
                            backgroundPosition: 'center',
                            flexShrink: 0,
                            zIndex: avatars.length - i,
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            color: '#64748b',
                            fontSize: `${Math.round(avatarSize * 0.4)}px`,
                            fontWeight: 600,
                        }}
                    >
                        {src ? '' : String(i + 1)}
                    </div>
                );
            })}
        </div>
    ) : null;

    const stars = showStars ? (
        <div style={{ display: 'flex', alignItems: 'center', gap: '2px' }}>
            {[0, 1, 2, 3, 4].map((i) => (
                <Star key={i} size={starSize} color={starColor} filled={i < Math.round(rating)} />
            ))}
        </div>
    ) : null;

    const ratingBlock = (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '4px', alignItems: align === 'center' ? 'center' : align === 'right' ? 'flex-end' : 'flex-start' }}>
            {stars}
            {text ? (
                <span style={{ color: textColor, fontSize: `${textSize}px`, fontWeight: 600 }}>{text}</span>
            ) : null}
        </div>
    );

    return (
        <div
            className="mp-social-proof"
            style={{
                display: 'flex',
                flexDirection: layout === 'stacked' ? 'column' : 'row',
                alignItems: layout === 'stacked' ? (align === 'center' ? 'center' : align === 'right' ? 'flex-end' : 'flex-start') : 'center',
                justifyContent: justify,
                gap: layout === 'stacked' ? '8px' : '16px',
                flexWrap: layout === 'stacked' ? 'nowrap' : 'wrap',
            }}
        >
            {avatarStack}
            {ratingBlock}
        </div>
    );
}

const socialProofSchema = {
    content: [
        { type: CONTROL.CONTROL_TEXT, name: 'text', label: 'Text', bind: 'props.text' },
        { type: CONTROL.CONTROL_SELECT, name: 'align', label: 'Align', bind: 'props.align', options: [
            { value: 'left', label: 'Left' },
            { value: 'center', label: 'Center' },
            { value: 'right', label: 'Right' },
        ]},
        { type: CONTROL.CONTROL_SELECT, name: 'layout', label: 'Layout', bind: 'props.layout', options: [
            { value: 'inline', label: 'Inline' },
            { value: 'stacked', label: 'Stacked' },
        ]},
        { type: CONTROL.CONTROL_TEXT, name: 'rating', label: 'Rating (0-5)', bind: 'props.rating' },
        { type: CONTROL.CONTROL_SWITCHER, name: 'showStars', label: 'Show Stars', bind: 'props.showStars' },
        { type: CONTROL.CONTROL_SWITCHER, name: 'showAvatars', label: 'Show Avatars', bind: 'props.showAvatars' },
        { type: CONTROL.CONTROL_GROUP, kind: 'repeater', label: 'Avatars', bind: 'props.avatars', itemSchema: {
            itemLabel: 'Avatar',
            defaultItem: () => ({ image: '' }),
            fields: [
                { name: 'image', label: 'Image URL' },
            ],
        }},
    ],
    style: [
        { type: CONTROL.CONTROL_TEXT, name: 'avatarSize', label: 'Avatar Size (px)', bind: 'props.avatarSize' },
        { type: CONTROL.CONTROL_TEXT, name: 'starSize', label: 'Star Size (px)', bind: 'props.starSize' },
        { type: CONTROL.CONTROL_TEXT, name: 'textSize', label: 'Text Size (px)', bind: 'props.textSize' },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'starColor', label: 'Star Color', bind: 'props.starColor' },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'textColor', label: 'Text Color', bind: 'props.textColor' },
    ],
    advanced: [],
};

registerElement({
    type: 'social_proof',
    label: 'Social Proof',
    group: 'Basic',
    defaultProps: { text: 'Trusted by 1000+ clients', rating: 5, showStars: true, showAvatars: true, align: 'left', layout: 'inline', avatarSize: 48, starSize: 22, starColor: '#F59E0B', textColor: '#334155', textSize: 18, avatars: [] },
    render: renderSocialProofPreview,
    schema: socialProofSchema,
});
