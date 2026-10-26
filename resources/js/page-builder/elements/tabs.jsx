import React, { useState, useEffect } from 'react';
import { registerElement } from '../elementRegistry.js';

const defaultItems = [
    { title: 'Tab 1', content: 'Content for tab 1' },
    { title: 'Tab 2', content: 'Content for tab 2' },
];

const orientations = ['horizontal', 'vertical', 'timeline'];

function buildGradient(props) {
    const bgType = typeof props.backgroundType === 'string' ? props.backgroundType : 'classic';
    if (bgType !== 'gradient') return null;

    const from = typeof props.gradientFrom === 'string' && props.gradientFrom.trim() ? props.gradientFrom.trim() : '#111827';
    const to = typeof props.gradientTo === 'string' && props.gradientTo.trim() ? props.gradientTo.trim() : '#3b82f6';
    const fromLoc = typeof props.gradientFromLocation === 'number' && Number.isFinite(props.gradientFromLocation) ? props.gradientFromLocation : 0;
    const toLoc = typeof props.gradientToLocation === 'number' && Number.isFinite(props.gradientToLocation) ? props.gradientToLocation : 100;
    const type = typeof props.gradientType === 'string' ? props.gradientType : 'linear';
    const position = typeof props.gradientPosition === 'string' && props.gradientPosition.trim() ? props.gradientPosition.trim() : 'top center';

    if (type === 'radial') {
        return `radial-gradient(at ${position}, ${from} ${fromLoc}%, ${to} ${toLoc}%)`;
    }
    return `linear-gradient(135deg, ${from} ${fromLoc}%, ${to} ${toLoc}%)`;
}

function buildWrapperStyle(props) {
    const style = {};

    if (typeof props.marginTop === 'number' && Number.isFinite(props.marginTop)) style.marginTop = `${props.marginTop}px`;
    if (typeof props.marginRight === 'number' && Number.isFinite(props.marginRight)) style.marginRight = `${props.marginRight}px`;
    if (typeof props.marginBottom === 'number' && Number.isFinite(props.marginBottom)) style.marginBottom = `${props.marginBottom}px`;
    if (typeof props.marginLeft === 'number' && Number.isFinite(props.marginLeft)) style.marginLeft = `${props.marginLeft}px`;

    if (typeof props.paddingTop === 'number' && Number.isFinite(props.paddingTop)) style.paddingTop = `${props.paddingTop}px`;
    if (typeof props.paddingRight === 'number' && Number.isFinite(props.paddingRight)) style.paddingRight = `${props.paddingRight}px`;
    if (typeof props.paddingBottom === 'number' && Number.isFinite(props.paddingBottom)) style.paddingBottom = `${props.paddingBottom}px`;
    if (typeof props.paddingLeft === 'number' && Number.isFinite(props.paddingLeft)) style.paddingLeft = `${props.paddingLeft}px`;

    const gradient = buildGradient(props);
    if (gradient) {
        style.backgroundImage = gradient;
    } else if (typeof props.backgroundColor === 'string' && props.backgroundColor.trim()) {
        style.backgroundColor = props.backgroundColor;
    }

    if (typeof props.borderColor === 'string' && props.borderColor.trim()) style.borderColor = props.borderColor;
    if (typeof props.borderStyle === 'string' && props.borderStyle && props.borderStyle !== 'default') style.borderStyle = props.borderStyle;
    if (typeof props.borderWidthTop === 'number' && Number.isFinite(props.borderWidthTop)) style.borderTopWidth = `${props.borderWidthTop}px`;
    if (typeof props.borderWidthRight === 'number' && Number.isFinite(props.borderWidthRight)) style.borderRightWidth = `${props.borderWidthRight}px`;
    if (typeof props.borderWidthBottom === 'number' && Number.isFinite(props.borderWidthBottom)) style.borderBottomWidth = `${props.borderWidthBottom}px`;
    if (typeof props.borderWidthLeft === 'number' && Number.isFinite(props.borderWidthLeft)) style.borderLeftWidth = `${props.borderWidthLeft}px`;

    if (typeof props.borderRadiusTop === 'number' && Number.isFinite(props.borderRadiusTop)) style.borderTopLeftRadius = `${props.borderRadiusTop}px`;
    if (typeof props.borderRadiusRight === 'number' && Number.isFinite(props.borderRadiusRight)) style.borderTopRightRadius = `${props.borderRadiusRight}px`;
    if (typeof props.borderRadiusBottom === 'number' && Number.isFinite(props.borderRadiusBottom)) style.borderBottomRightRadius = `${props.borderRadiusBottom}px`;
    if (typeof props.borderRadiusLeft === 'number' && Number.isFinite(props.borderRadiusLeft)) style.borderBottomLeftRadius = `${props.borderRadiusLeft}px`;

    return style;
}

function buildTabTypography(props) {
    const style = {};
    if (typeof props.fontFamily === 'string' && props.fontFamily.trim()) style.fontFamily = props.fontFamily;
    if (typeof props.fontSize === 'number' && Number.isFinite(props.fontSize)) style.fontSize = `${props.fontSize}px`;
    if (typeof props.fontWeight === 'string' && props.fontWeight.trim()) style.fontWeight = props.fontWeight;
    if (typeof props.lineHeight === 'number' && Number.isFinite(props.lineHeight)) style.lineHeight = `${props.lineHeight}px`;
    if (typeof props.letterSpacing === 'number' && Number.isFinite(props.letterSpacing)) style.letterSpacing = `${props.letterSpacing}px`;
    if (typeof props.wordSpacing === 'number' && Number.isFinite(props.wordSpacing)) style.wordSpacing = `${props.wordSpacing}px`;
    return style;
}

function TabsPreview({ widget, helpers = {} }) {
    const w = widget;
    const props = (w && w.props && typeof w.props === 'object') ? w.props : {};

    const rawItems = Array.isArray(props.items) ? props.items : [];
    const items = rawItems.length ? rawItems : defaultItems;
    const orientation = orientations.includes(props.orientation) ? props.orientation : 'horizontal';
    const rawActive = typeof props.activeIndex === 'number' && Number.isFinite(props.activeIndex) ? props.activeIndex : 0;
    const defaultActive = Math.min(Math.max(rawActive, 0), items.length - 1);

    const [current, setCurrent] = useState(defaultActive);

    useEffect(() => {
        setCurrent(Math.min(Math.max(defaultActive, 0), items.length - 1));
    }, [defaultActive, items.length]);

    const wrapperStyle = buildWrapperStyle(props);
    const tabTypography = buildTabTypography(props);
    const contentTypography = buildTabTypography(props);

    const tabGap = typeof props.tabGap === 'number' && Number.isFinite(props.tabGap) ? props.tabGap : 0;
    const tabPaddingX = typeof props.tabPaddingX === 'number' && Number.isFinite(props.tabPaddingX) ? props.tabPaddingX : 16;
    const tabPaddingY = typeof props.tabPaddingY === 'number' && Number.isFinite(props.tabPaddingY) ? props.tabPaddingY : 10;
    const contentPadding = typeof props.contentPadding === 'number' && Number.isFinite(props.contentPadding) ? props.contentPadding : 16;

    const tabTextColor = typeof props.tabTextColor === 'string' && props.tabTextColor.trim() ? props.tabTextColor : '#6b7280';
    const tabBackgroundColor = typeof props.tabBackgroundColor === 'string' && props.tabBackgroundColor.trim() ? props.tabBackgroundColor : 'transparent';
    const activeTabTextColor = typeof props.activeTabTextColor === 'string' && props.activeTabTextColor.trim() ? props.activeTabTextColor : '#111827';
    const activeTabBackgroundColor = typeof props.activeTabBackgroundColor === 'string' && props.activeTabBackgroundColor.trim() ? props.activeTabBackgroundColor : 'transparent';
    const activeTabBorderColor = typeof props.activeTabBorderColor === 'string' && props.activeTabBorderColor.trim() ? props.activeTabBorderColor : '#2563eb';
    const contentBackgroundColor = typeof props.contentBackgroundColor === 'string' && props.contentBackgroundColor.trim() ? props.contentBackgroundColor : 'transparent';
    const contentTextColor = typeof props.contentTextColor === 'string' && props.contentTextColor.trim() ? props.contentTextColor : '#374151';
    const timelineConnectorColor = typeof props.timelineConnectorColor === 'string' && props.timelineConnectorColor.trim() ? props.timelineConnectorColor : '#e5e7eb';
    const timelineDotColor = typeof props.timelineDotColor === 'string' && props.timelineDotColor.trim() ? props.timelineDotColor : '#2563eb';
    const timelineDotSize = typeof props.timelineDotSize === 'number' && Number.isFinite(props.timelineDotSize) ? props.timelineDotSize : 12;

    const isHorizontal = orientation === 'horizontal';
    const isVertical = orientation === 'vertical';
    const isTimeline = orientation === 'timeline';

    const wrapperClass = 'mp-tabs';

    const renderTabButton = (it, idx) => {
        const isActive = current === idx;
        const baseStyle = {
            ...tabTypography,
            padding: `${tabPaddingY}px ${tabPaddingX}px`,
            color: isActive ? activeTabTextColor : tabTextColor,
            backgroundColor: isActive ? activeTabBackgroundColor : tabBackgroundColor,
            border: 'none',
            cursor: 'pointer',
            textAlign: 'left',
            transition: 'color 0.2s, background-color 0.2s',
            display: 'flex',
            alignItems: 'center',
            gap: '8px',
            whiteSpace: 'nowrap',
        };

        if (isHorizontal) {
            baseStyle.borderBottom = `2px solid ${isActive ? activeTabBorderColor : 'transparent'}`;
        } else if (isVertical) {
            baseStyle.borderLeft = `3px solid ${isActive ? activeTabBorderColor : 'transparent'}`;
            baseStyle.width = '100%';
        } else {
            baseStyle.width = '100%';
        }

        return (
            <button
                key={idx}
                type="button"
                role="tab"
                aria-selected={isActive}
                style={baseStyle}
                onClick={() => setCurrent(idx)}
            >
                {isTimeline ? (
                    <span
                        style={{
                            display: 'inline-flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            width: `${timelineDotSize}px`,
                            height: `${timelineDotSize}px`,
                            borderRadius: '9999px',
                            backgroundColor: isActive ? timelineDotColor : timelineConnectorColor,
                            color: '#ffffff',
                            fontSize: `${Math.max(10, timelineDotSize * 0.55)}px`,
                            fontWeight: 700,
                            flexShrink: 0,
                        }}
                    >
                        {idx + 1}
                    </span>
                ) : null}
                <span>{it && typeof it.title === 'string' ? it.title : `Tab ${idx + 1}`}</span>
            </button>
        );
    };

    const activeItem = items[Math.min(current, items.length - 1)] || null;
    const activeWidgets = activeItem && Array.isArray(activeItem.widgets) ? activeItem.widgets : [];
    const activeContent = activeItem && typeof activeItem.content === 'string' ? activeItem.content : '';

    const contentPanel = (
        <div
            className="mp-tabs__content"
            role="tabpanel"
            style={{
                flex: 1,
                padding: `${contentPadding}px`,
                backgroundColor: contentBackgroundColor,
                color: contentTextColor,
                ...contentTypography,
                whiteSpace: activeWidgets.length ? undefined : 'pre-wrap',
            }}
            onDragOver={(e) => {
                e.preventDefault();
                e.stopPropagation();
            }}
            onClick={(e) => {
                if (e.defaultPrevented || !activeItem) return;
                if (typeof helpers.setSelected === 'function') {
                    helpers.setSelected({
                        kind: 'tab_item',
                        sectionId: helpers.sectionId,
                        columnId: helpers.columnId,
                        tabWidgetId: helpers.widgetId,
                        tabItemId: activeItem.id,
                    });
                }
            }}
            onDrop={(e) => {
                e.preventDefault();
                e.stopPropagation();
                if (!activeItem || !activeItem.id) return;
                const data = typeof helpers.getDragData === 'function' ? helpers.getDragData(e) : null;
                if (!data) return;
                if (data.kind === 'new_widget' && data.widgetType) {
                    if (typeof helpers.addWidgetToTabItem === 'function') {
                        helpers.addWidgetToTabItem(
                            helpers.sectionId,
                            helpers.columnId,
                            helpers.widgetId,
                            activeItem.id,
                            data.widgetType,
                            {}
                        );
                    }
                }
            }}
        >
            {activeWidgets.length ? (
                <div className="space-y-2">
                    {activeWidgets.map((iw) => {
                        const id = iw && iw.id ? iw.id : Math.random();
                        return (
                            <div
                                key={id}
                                className="cursor-pointer"
                                onClick={(e) => {
                                    e.stopPropagation();
                                    if (typeof helpers.setSelected === 'function') {
                                        helpers.setSelected({
                                            kind: 'tab_widget',
                                            sectionId: helpers.sectionId,
                                            columnId: helpers.columnId,
                                            tabWidgetId: helpers.widgetId,
                                            tabItemId: activeItem.id,
                                            widgetId: id,
                                        });
                                    }
                                }}
                                onPointerDown={(e) => {
                                    e.stopPropagation();
                                    if (typeof helpers.setSelected === 'function') {
                                        helpers.setSelected({
                                            kind: 'tab_widget',
                                            sectionId: helpers.sectionId,
                                            columnId: helpers.columnId,
                                            tabWidgetId: helpers.widgetId,
                                            tabItemId: activeItem.id,
                                            widgetId: id,
                                        });
                                    }
                                }}
                                onContextMenu={(e) => {
                                    if (typeof helpers.handleContextMenu === 'function') {
                                        helpers.handleContextMenu(e, {
                                            kind: 'tab_widget',
                                            sectionId: helpers.sectionId,
                                            columnId: helpers.columnId,
                                            tabWidgetId: helpers.widgetId,
                                            tabItemId: activeItem.id,
                                            widgetId: id,
                                            widget: iw,
                                        });
                                    }
                                }}
                            >
                                {typeof helpers.renderWidgetPreview === 'function' ? helpers.renderWidgetPreview(iw, { sectionId: helpers.sectionId, columnId: helpers.columnId }) : null}
                            </div>
                        );
                    })}
                </div>
            ) : activeContent ? (
                activeContent
            ) : activeItem && activeItem.id ? (
                <div className="text-center py-6">
                    <button
                        type="button"
                        onClick={(e) => {
                            e.stopPropagation();
                            if (typeof helpers.setSelected === 'function') {
                                helpers.setSelected({
                                    kind: 'tab_item',
                                    sectionId: helpers.sectionId,
                                    columnId: helpers.columnId,
                                    tabWidgetId: helpers.widgetId,
                                    tabItemId: activeItem.id,
                                });
                            }
                            if (typeof helpers.setRailTab === 'function') {
                                helpers.setRailTab('elements');
                            }
                        }}
                        className="mx-auto h-10 w-10 rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 flex items-center justify-center text-2xl leading-none"
                    >
                        <span aria-hidden>+</span>
                    </button>
                </div>
            ) : null}
        </div>
    );

    if (isTimeline) {
        return (
            <div className={wrapperClass} style={wrapperStyle}>
                <div
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: `${tabGap}px`,
                        position: 'relative',
                    }}
                >
                    {items.map((it, idx) => {
                        const isActive = current === idx;
                        return (
                            <div
                                key={idx}
                                style={{
                                    display: 'flex',
                                    gap: '16px',
                                    alignItems: 'flex-start',
                                }}
                            >
                                <div
                                    style={{
                                        display: 'flex',
                                        flexDirection: 'column',
                                        alignItems: 'center',
                                        width: `${timelineDotSize}px`,
                                        flexShrink: 0,
                                    }}
                                >
                                    <button
                                        type="button"
                                        onClick={() => setCurrent(idx)}
                                        style={{
                                            width: `${timelineDotSize}px`,
                                            height: `${timelineDotSize}px`,
                                            borderRadius: '9999px',
                                            backgroundColor: isActive ? timelineDotColor : timelineConnectorColor,
                                            border: 'none',
                                            cursor: 'pointer',
                                            padding: 0,
                                            flexShrink: 0,
                                        }}
                                        aria-label={`Step ${idx + 1}`}
                                    />
                                    {idx < items.length - 1 ? (
                                        <div
                                            style={{
                                                width: '2px',
                                                flex: 1,
                                                minHeight: '24px',
                                                backgroundColor: timelineConnectorColor,
                                                marginTop: '4px',
                                            }}
                                        />
                                    ) : null}
                                </div>
                                <div style={{ flex: 1 }}>
                                    <button
                                        type="button"
                                        onClick={() => setCurrent(idx)}
                                        style={{
                                            ...tabTypography,
                                            color: isActive ? activeTabTextColor : tabTextColor,
                                            backgroundColor: 'transparent',
                                            border: 'none',
                                            cursor: 'pointer',
                                            padding: 0,
                                            fontWeight: isActive ? 700 : 500,
                                            textAlign: 'left',
                                        }}
                                    >
                                        {it && typeof it.title === 'string' ? it.title : `Step ${idx + 1}`}
                                    </button>
                                    {isActive ? (
                                        <div
                                            style={{
                                                marginTop: '8px',
                                                padding: `${contentPadding}px`,
                                                backgroundColor: contentBackgroundColor,
                                                color: contentTextColor,
                                                ...contentTypography,
                                                whiteSpace: Array.isArray(it.widgets) && it.widgets.length ? undefined : 'pre-wrap',
                                                borderRadius: '8px',
                                            }}
                                        >
                                            {Array.isArray(it.widgets) && it.widgets.length ? (
                                                <div className="space-y-2">
                                                    {it.widgets.map((iw) => (
                                                        <div key={iw && iw.id ? iw.id : Math.random()}>
                                                            {typeof helpers.renderWidgetPreview === 'function' ? helpers.renderWidgetPreview(iw) : null}
                                                        </div>
                                                    ))}
                                                </div>
                                            ) : it && typeof it.content === 'string' ? (
                                                it.content
                                            ) : null}
                                        </div>
                                    ) : null}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        );
    }

    return (
        <div
            className={wrapperClass}
            style={{
                ...wrapperStyle,
                display: 'flex',
                flexDirection: isVertical ? 'row' : 'column',
                gap: isVertical ? '16px' : '0',
            }}
        >
            <div
                style={{
                    display: 'flex',
                    flexDirection: isHorizontal ? 'row' : 'column',
                    gap: `${tabGap}px`,
                    borderBottom: isHorizontal && props.tabListBorder !== false ? `1px solid ${typeof props.tabListBorderColor === 'string' && props.tabListBorderColor.trim() ? props.tabListBorderColor : '#e5e7eb'}` : undefined,
                    borderRight: isVertical && props.tabListBorder !== false ? `1px solid ${typeof props.tabListBorderColor === 'string' && props.tabListBorderColor.trim() ? props.tabListBorderColor : '#e5e7eb'}` : undefined,
                    padding: isHorizontal ? '0' : '0 12px 0 0',
                    flexShrink: isVertical ? 0 : undefined,
                    minWidth: isVertical ? '120px' : undefined,
                }}
            >
                {items.map((it, idx) => renderTabButton(it, idx))}
            </div>
            {contentPanel}
        </div>
    );
}

export function renderTabsPreview(widget, helpers = {}) {
    return <TabsPreview widget={widget} helpers={helpers} />;
}

registerElement({
    type: 'tabs',
    label: 'Tabs',
    group: 'Basic',
    defaultProps: {
        items: [
            { title: 'Tab 1', content: 'Content for tab 1' },
            { title: 'Tab 2', content: 'Content for tab 2' },
        ],
        orientation: 'horizontal',
        activeIndex: 0,
    },
    render: renderTabsPreview,
    schema: { content: [], style: [], advanced: [] },
});
