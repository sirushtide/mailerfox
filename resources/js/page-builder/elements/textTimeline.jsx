import React, { useState } from 'react';
import * as CONTROL from '../controls/types.js';
import { registerElement } from '../elementRegistry.js';

const defaultItems = [
    {
        version: '1.4.0',
        date: 'May 7, 2026',
        events: [
            { tag: 'NEW', title: 'Relationship Canvas: Multi Select and Group Actions', description: 'Select multiple nodes on the Relationship Canvas and act on them as a unit. Multi-select stays responsive on canvases with hundreds of nodes.' },
            { tag: 'UPDATE', title: 'Improved Canvas Performance', description: 'Optimized rendering pipeline for large node graphs.' },
        ],
    },
    {
        version: '1.3.2',
        date: 'Apr 28, 2026',
        events: [
            { tag: 'FIX', title: 'Crash on Importing Large CSVs', description: 'Fixed a memory crash when importing CSVs over 50MB.' },
        ],
    },
];

function number(value, fallback) {
    return typeof value === 'number' && Number.isFinite(value) ? value : fallback;
}

function string(value, fallback) {
    return typeof value === 'string' && value.trim() ? value : fallback;
}

function typography(props, prefix, fallback) {
    const style = {};
    const fontFamily = string(props[`${prefix}FontFamily`], '');
    if (fontFamily) style.fontFamily = fontFamily;
    style.fontSize = `${number(props[`${prefix}FontSize`], fallback)}px`;
    const weight = string(props[`${prefix}FontWeight`], '');
    if (weight) style.fontWeight = weight;
    if (typeof props[`${prefix}LineHeight`] === 'number') style.lineHeight = `${props[`${prefix}LineHeight`]}px`;
    if (typeof props[`${prefix}LetterSpacing`] === 'number') style.letterSpacing = `${props[`${prefix}LetterSpacing`]}px`;
    return style;
}

function TextTimelineInner({ widget }) {
    const props = (widget && widget.props && typeof widget.props === 'object') ? widget.props : {};
    const rawItems = Array.isArray(props.items) ? props.items : [];
    const items = rawItems.length ? rawItems : defaultItems;

    const perPage = Math.max(1, number(props.perPage, 20));
    const showLoadMore = props.showLoadMore !== false;
    const loadMoreText = string(props.loadMoreText, 'Read More');
    const loadMoreBg = string(props.loadMoreBackgroundColor, '#111827');
    const loadMoreColor = string(props.loadMoreColor, '#ffffff');
    const loadMoreHoverBg = string(props.loadMoreHoverBackground, '#374151');
    const loadMoreBorderRadius = number(props.loadMoreBorderRadius, 6);

    const [visibleCount, setVisibleCount] = useState(perPage);

    const visibleItems = items.slice(0, visibleCount);
    const hasMore = visibleCount < items.length;

    const markerShape = props.markerShape === 'square' ? 'square' : 'circle';
    const railColor = string(props.railColor, '#d1d5db');
    const markerColor = string(props.markerColor, '#111827');
    const cardColor = string(props.cardColor, '#ffffff');
    const cardBorderColor = string(props.cardBorderColor, '#f1f1f1');
    const cardRadius = number(props.cardRadius, 0);
    const gap = number(props.gap, 34);
    const markerSize = Math.max(8, number(props.markerSize, 14));
    const metaWidth = Math.max(120, number(props.metaWidth, 170));
    const contentGap = number(props.contentGap, 34);

    const wrapperStyle = {
        position: 'relative',
        paddingTop: `${number(props.paddingTop, 12)}px`,
        paddingRight: `${number(props.paddingRight, 0)}px`,
        paddingBottom: `${number(props.paddingBottom, 12)}px`,
        paddingLeft: `${number(props.paddingLeft, 0)}px`,
        marginTop: `${number(props.marginTop, 0)}px`,
        marginRight: `${number(props.marginRight, 0)}px`,
        marginBottom: `${number(props.marginBottom, 0)}px`,
        marginLeft: `${number(props.marginLeft, 0)}px`,
    };
    if (props.backgroundColor) wrapperStyle.backgroundColor = props.backgroundColor;

    const titleStyle = typography(props, 'title', 22);
    titleStyle.color = string(props.titleColor, '#111827');
    const descriptionStyle = typography(props, 'description', 14);
    descriptionStyle.color = string(props.descriptionColor, '#6b7280');
    const metaStyle = typography(props, 'meta', 15);
    metaStyle.color = string(props.metaColor, '#777777');
    const tagStyle = {
        fontSize: '11px',
        fontWeight: 700,
        letterSpacing: '0.05em',
        textTransform: 'uppercase',
        padding: '3px 8px',
        borderRadius: '4px',
        lineHeight: '1.4',
    };

    const tagColors = {
        NEW: { bg: '#dcfce7', color: '#166534' },
        FIX: { bg: '#fee2e2', color: '#991b1b' },
        UPDATE: { bg: '#dbeafe', color: '#1e40af' },
        IMPROVED: { bg: '#fef9c3', color: '#854d0e' },
        DEPRECATED: { bg: '#f3e8ff', color: '#6b21a8' },
        REMOVED: { bg: '#ffe4e6', color: '#9f1239' },
    };

    const getTagColors = (tag) => {
        const upper = (tag || '').toUpperCase();
        if (tagColors[upper]) return tagColors[upper];
        return { bg: '#f3f4f6', color: '#374151' };
    };

    const railBottom = hasMore ? 'auto' : `${markerSize}px`;

    return (
        <div className="mp-text-timeline" style={wrapperStyle}>
            <div style={{ position: 'relative' }}>
                <div aria-hidden="true" className="mp-text-timeline__rail" style={{
                    position: 'absolute',
                    left: `${metaWidth + contentGap / 2}px`,
                    top: `${markerSize}px`,
                    bottom: railBottom,
                    width: '2px',
                    backgroundColor: railColor,
                }} />
                <div style={{ display: 'flex', flexDirection: 'column', gap: `${gap}px` }}>
                    {visibleItems.map((item, index) => {
                        const entry = item && typeof item === 'object' ? item : {};
                        const version = string(entry.version, `1.0.${index}`);
                        const date = string(entry.date, 'Date');
                        const events = Array.isArray(entry.events) ? entry.events : [];

                        const markerStyle = {
                            position: 'absolute',
                            left: `${-contentGap / 2 - markerSize / 2}px`,
                            top: `${markerSize / 2}px`,
                            width: `${markerSize}px`,
                            height: `${markerSize}px`,
                            boxSizing: 'border-box',
                            backgroundColor: markerColor,
                            border: `3px solid ${string(props.markerBorderColor, '#ffffff')}`,
                            borderRadius: markerShape === 'circle' ? '50%' : '1px',
                            zIndex: 1,
                        };

                        return (
                            <div key={index} className="mp-text-timeline__row" style={{ position: 'relative', display: 'grid', gridTemplateColumns: `${metaWidth}px minmax(0, 1fr)`, gap: `${contentGap}px`, alignItems: 'start' }}>
                                <div className="mp-text-timeline__meta" style={{ ...metaStyle, paddingTop: '5px', paddingRight: '12px', textAlign: 'right', whiteSpace: 'normal' }}>
                                    <div style={{ display: 'inline-block', padding: '7px 12px', backgroundColor: string(props.versionBackgroundColor, '#f1f1f1'), color: string(props.versionColor, '#555555') }}>{version}</div>
                                    <div style={{ marginTop: '8px' }}>{date}</div>
                                </div>
                                <div className="mp-text-timeline__card" style={{ position: 'relative', backgroundColor: cardColor, border: `1px solid ${cardBorderColor}`, borderRadius: `${cardRadius}px`, padding: `${number(props.cardPaddingTop, 32)}px ${number(props.cardPaddingRight, 36)}px ${number(props.cardPaddingBottom, 32)}px ${number(props.cardPaddingLeft, 36)}px`, minWidth: 0 }}>
                                    <span className="mp-text-timeline__marker" style={markerStyle} />
                                    {events.length ? events.map((ev, evIdx) => {
                                        const evEntry = ev && typeof ev === 'object' ? ev : {};
                                        const tag = string(evEntry.tag, 'NEW');
                                        const tc = getTagColors(tag);
                                        return (
                                            <div key={evIdx} style={evIdx > 0 ? { marginTop: '20px', paddingTop: '20px', borderTop: `1px solid ${string(props.cardBorderColor, '#f1f1f1')}` } : undefined}>
                                                <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '8px' }}>
                                                    <span style={{ ...tagStyle, backgroundColor: tc.bg, color: tc.color }}>{tag}</span>
                                                </div>
                                                <div style={{ ...titleStyle }}>{string(evEntry.title, 'Event title')}</div>
                                                {string(evEntry.description, '') && (
                                                    <div style={{ ...descriptionStyle, marginTop: '8px', whiteSpace: 'pre-wrap' }}>{evEntry.description}</div>
                                                )}
                                            </div>
                                        );
                                    }) : (
                                        <div style={titleStyle}>{string(entry.title, 'Timeline title')}</div>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
                {showLoadMore && hasMore && (
                    <div style={{ display: 'flex', justifyContent: 'center', marginTop: `${gap}px`, paddingLeft: `${metaWidth + contentGap}px` }}>
                        <button
                            type="button"
                            onClick={() => setVisibleCount((prev) => Math.min(prev + perPage, items.length))}
                            style={{
                                padding: '10px 28px',
                                backgroundColor: loadMoreBg,
                                color: loadMoreColor,
                                border: 'none',
                                borderRadius: `${loadMoreBorderRadius}px`,
                                fontSize: '15px',
                                fontWeight: 600,
                                cursor: 'pointer',
                                transition: 'background-color 0.2s',
                            }}
                            onMouseEnter={(e) => { e.currentTarget.style.backgroundColor = loadMoreHoverBg; }}
                            onMouseLeave={(e) => { e.currentTarget.style.backgroundColor = loadMoreBg; }}
                        >
                            {loadMoreText}
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
}

export function renderTextTimelinePreview(widget) {
    return <TextTimelineInner widget={widget} />;
}

const textTimelineSchema = {
    content: [
        { type: CONTROL.CONTROL_GROUP, kind: 'repeater', label: 'Timeline Versions', bind: 'props.items', itemSchema: {
            itemLabel: 'Version',
            defaultItem: () => ({ version: '1.0.0', date: 'Date', events: [{ tag: 'NEW', title: 'Event title', description: '' }] }),
            fields: [
                { name: 'version', label: 'Version' },
                { name: 'date', label: 'Date' },
            ],
            children: {
                key: 'events',
                label: 'Events',
                defaultItem: () => ({ tag: 'NEW', title: 'Event title', description: '' }),
                fields: [
                    { name: 'tag', label: 'Tag (NEW, FIX, UPDATE...)' },
                    { name: 'title', label: 'Title' },
                    { name: 'description', label: 'Description' },
                ],
            },
        }},
        { type: CONTROL.CONTROL_SWITCHER, name: 'showLoadMore', label: 'Show Load More', bind: 'props.showLoadMore' },
        { type: CONTROL.CONTROL_TEXT, name: 'loadMoreText', label: 'Button Text', bind: 'props.loadMoreText', condition: [{ showLoadMore: true }] },
        { type: CONTROL.CONTROL_TEXT, name: 'perPage', label: 'Versions Per Page', bind: 'props.perPage', condition: [{ showLoadMore: true }] },
    ],
    style: [
        { type: CONTROL.CONTROL_TEXT, name: 'metaWidth', label: 'Meta Width (px)', bind: 'props.metaWidth' },
        { type: CONTROL.CONTROL_TEXT, name: 'contentGap', label: 'Content Gap (px)', bind: 'props.contentGap' },
        { type: CONTROL.CONTROL_TEXT, name: 'gap', label: 'Entry Gap (px)', bind: 'props.gap' },
        { type: CONTROL.CONTROL_SELECT, name: 'markerShape', label: 'Marker Shape', bind: 'props.markerShape', options: [{ value: 'circle', label: 'Circle' }, { value: 'square', label: 'Square' }] },
        { type: CONTROL.CONTROL_TEXT, name: 'markerSize', label: 'Marker Size (px)', bind: 'props.markerSize' },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'railColor', label: 'Rail Color', bind: 'props.railColor' },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'markerColor', label: 'Marker Color', bind: 'props.markerColor' },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'cardColor', label: 'Card Background', bind: 'props.cardColor' },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'cardBorderColor', label: 'Card Border', bind: 'props.cardBorderColor' },
        { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Title Typography', bind: { fontFamily: 'props.titleFontFamily', fontSize: 'props.titleFontSize', fontWeight: 'props.titleFontWeight', lineHeight: 'props.titleLineHeight', letterSpacing: 'props.titleLetterSpacing' } },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'titleColor', label: 'Title Color', bind: 'props.titleColor' },
        { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Description Typography', bind: { fontFamily: 'props.descriptionFontFamily', fontSize: 'props.descriptionFontSize', fontWeight: 'props.descriptionFontWeight', lineHeight: 'props.descriptionLineHeight', letterSpacing: 'props.descriptionLetterSpacing' } },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'descriptionColor', label: 'Description Color', bind: 'props.descriptionColor' },
        { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Meta Typography', bind: { fontFamily: 'props.metaFontFamily', fontSize: 'props.metaFontSize', fontWeight: 'props.metaFontWeight', lineHeight: 'props.metaLineHeight', letterSpacing: 'props.metaLetterSpacing' } },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'metaColor', label: 'Meta Color', bind: 'props.metaColor' },
        { type: CONTROL.CONTROL_GROUP, kind: 'spacing', label: 'Wrapper Spacing', bind: { padding: { linked: 'props.paddingLinked', t: 'props.paddingTop', r: 'props.paddingRight', b: 'props.paddingBottom', l: 'props.paddingLeft' }, margin: { linked: 'props.marginLinked', t: 'props.marginTop', r: 'props.marginRight', b: 'props.marginBottom', l: 'props.marginLeft' } } },
        { type: CONTROL.CONTROL_HEADING, label: 'Load More Button' },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'loadMoreBackgroundColor', label: 'Button Background', bind: 'props.loadMoreBackgroundColor' },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'loadMoreColor', label: 'Button Text Color', bind: 'props.loadMoreColor' },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'loadMoreHoverBackground', label: 'Button Hover Background', bind: 'props.loadMoreHoverBackground' },
        { type: CONTROL.CONTROL_TEXT, name: 'loadMoreBorderRadius', label: 'Button Border Radius (px)', bind: 'props.loadMoreBorderRadius' },
    ],
    advanced: [],
};

registerElement({
    type: 'text_timeline',
    label: 'Text Timeline',
    group: 'Basic',
    defaultProps: { items: defaultItems, metaWidth: 170, contentGap: 34, gap: 34, markerShape: 'circle', markerSize: 14, railColor: '#d1d5db', markerColor: '#111827', cardColor: '#ffffff', cardBorderColor: '#f1f1f1', cardRadius: 0, cardPaddingTop: 32, cardPaddingRight: 36, cardPaddingBottom: 32, cardPaddingLeft: 36, titleColor: '#111827', titleFontSize: 22, descriptionColor: '#6b7280', descriptionFontSize: 14, metaColor: '#777777', perPage: 20, showLoadMore: true, loadMoreText: 'Read More', loadMoreBackgroundColor: '#111827', loadMoreColor: '#ffffff', loadMoreHoverBackground: '#374151', loadMoreBorderRadius: 6 },
    render: renderTextTimelinePreview,
    schema: textTimelineSchema,
});
