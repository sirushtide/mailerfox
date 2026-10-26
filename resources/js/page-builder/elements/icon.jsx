import React from 'react';
import * as CONTROL from '../controls/types.js';
import { registerElement } from '../elementRegistry.js';
import { pickResponsive } from '../reusable/builderViewport.js';
import { buildHoverCss, hoverStyleAccordion } from '../reusable/elementHover.js';

export function injectSvgColor(svg, color) {
    if (!svg || !color) return svg;
    return svg.replace(
        /<svg\b([^>]*)>/i,
        (match, attrs) => {
            const hasFillNone = /fill=["']none["']/i.test(attrs);
            const hasStrokeNone = /stroke=["']none["']/i.test(attrs);
            let styleInject = `color:${color};`;
            if (!hasFillNone) styleInject += 'fill:currentColor;';
            if (!hasStrokeNone) styleInject += 'stroke:currentColor;';
            const existingStyle = /style=["']([^"]*)["']/i.exec(attrs);
            if (existingStyle) {
                const newStyle = existingStyle[1] + styleInject;
                return `<svg${attrs.replace(existingStyle[0], `style="${newStyle}"`)}>`;
            }
            return `<svg${attrs} style="${styleInject}">`;
        }
    );
}

function svgWithSize(svg, size) {
    const clean = String(svg || '').trim();
    if (!clean) return '';
    const sizePx = Number.isFinite(Number(size)) ? Number(size) : 64;
    const sizeStyle = `display:inline-block;width:${sizePx}px;height:${sizePx}px;`;
    if (/<svg\b[^>]*\bstyle\s*=/i.test(clean)) {
        return clean.replace(
            /(<svg\b[^>]*\bstyle\s*=\s*)(["'])([\s\S]*?)\2/i,
            (m, pre, q, existing) => `${pre}${q}${existing};${sizeStyle}${q}`
        );
    }
    return clean.replace(/<svg\b/i, `<svg style="${sizeStyle}"`);
}

export function renderIconPreview(widget) {
    const w = widget;

    const iconUrl = (w.props && w.props.iconUrl) ? String(w.props.iconUrl) : '';
    const iconSvg = (w.props && w.props.iconSvg) ? String(w.props.iconSvg) : '';
    const heading = (w.props && w.props.heading) ? String(w.props.heading) : 'Heading';
    const subtext = (w.props && typeof w.props.subtext === 'string') ? w.props.subtext : 'Subtext';
    const iconPosition = (w.props && w.props.iconPosition) ? String(w.props.iconPosition) : 'top';
    const iconSize = (w.props && Number.isFinite(Number(w.props.iconSize))) ? Number(w.props.iconSize) : 64;
    const iconGap = (w.props && Number.isFinite(Number(w.props.iconGap))) ? Number(w.props.iconGap) : 16;
    const align = (w.props && w.props.align) ? String(w.props.align) : 'center';

    const subtextPlain = String(subtext).replace(/<[^>]*>/g, '').replace(/&nbsp;/gi, ' ').trim();
    const hasSubtext = subtextPlain !== '' || /<(img|br|hr|video|iframe|svg)\b/i.test(String(subtext));

    const containerStyle = {};
    const iconContainerStyle = {};
    const headingStyle = {};
    const subtextStyle = {};

    const alignClasses = {
        left: 'text-left',
        center: 'text-center',
        right: 'text-right',
    };

    const iconBgType = w.props?.iconBackgroundType || 'color';
    if (iconBgType === 'gradient') {
        const from = w.props?.iconGradientFrom || '#111827';
        const to = w.props?.iconGradientTo || '#3b82f6';
        const gradientType = (w.props?.iconGradientType === 'radial' || w.props?.iconGradientType === 'linear') ? w.props.iconGradientType : 'linear';
        if (gradientType === 'radial') {
            const position = typeof w.props?.iconGradientPosition === 'string' && w.props.iconGradientPosition ? w.props.iconGradientPosition : 'top center';
            iconContainerStyle.background = `radial-gradient(at ${position}, ${from}, ${to})`;
        } else {
            const angle = Number.isFinite(Number(w.props?.iconGradientAngle)) ? Number(w.props.iconGradientAngle) : 135;
            iconContainerStyle.background = `linear-gradient(${angle}deg, ${from}, ${to})`;
        }
    } else if (w.props?.iconBackgroundColor) {
        iconContainerStyle.backgroundColor = w.props.iconBackgroundColor;
    }
    if (w.props?.iconColor) iconContainerStyle.color = w.props.iconColor;

    if (w.props?.iconPaddingTop !== undefined) iconContainerStyle.paddingTop = `${w.props.iconPaddingTop}px`;
    if (w.props?.iconPaddingRight !== undefined) iconContainerStyle.paddingRight = `${w.props.iconPaddingRight}px`;
    if (w.props?.iconPaddingBottom !== undefined) iconContainerStyle.paddingBottom = `${w.props.iconPaddingBottom}px`;
    if (w.props?.iconPaddingLeft !== undefined) iconContainerStyle.paddingLeft = `${w.props.iconPaddingLeft}px`;

    if (w.props?.iconBorderRadiusTop !== undefined) iconContainerStyle.borderTopLeftRadius = `${w.props.iconBorderRadiusTop}px`;
    if (w.props?.iconBorderRadiusRight !== undefined) iconContainerStyle.borderTopRightRadius = `${w.props.iconBorderRadiusRight}px`;
    if (w.props?.iconBorderRadiusBottom !== undefined) iconContainerStyle.borderBottomRightRadius = `${w.props.iconBorderRadiusBottom}px`;
    if (w.props?.iconBorderRadiusLeft !== undefined) iconContainerStyle.borderBottomLeftRadius = `${w.props.iconBorderRadiusLeft}px`;
    if (w.props?.iconBorderRadius !== undefined) iconContainerStyle.borderRadius = `${w.props.iconBorderRadius}px`;

    const hasIconBorder = w.props?.iconBorderWidthTop || w.props?.iconBorderWidthRight || w.props?.iconBorderWidthBottom || w.props?.iconBorderWidthLeft;
    if (hasIconBorder) {
        iconContainerStyle.borderStyle = 'solid';
        if (w.props?.iconBorderWidthTop !== undefined) iconContainerStyle.borderTopWidth = `${w.props.iconBorderWidthTop}px`;
        if (w.props?.iconBorderWidthRight !== undefined) iconContainerStyle.borderRightWidth = `${w.props.iconBorderWidthRight}px`;
        if (w.props?.iconBorderWidthBottom !== undefined) iconContainerStyle.borderBottomWidth = `${w.props.iconBorderWidthBottom}px`;
        if (w.props?.iconBorderWidthLeft !== undefined) iconContainerStyle.borderLeftWidth = `${w.props.iconBorderWidthLeft}px`;
        iconContainerStyle.borderColor = w.props?.iconBorderColor ? w.props.iconBorderColor : '#e5e7eb';
    }

    if (w.props?.shadowColor && (w.props?.shadowBlur !== undefined || w.props?.shadowX !== undefined || w.props?.shadowY !== undefined)) {
        const x = w.props.shadowX ?? 0;
        const y = w.props.shadowY ?? 0;
        const blur = w.props.shadowBlur ?? 0;
        iconContainerStyle.filter = `drop-shadow(${x}px ${y}px ${blur}px ${w.props.shadowColor})`;
    }

    iconContainerStyle.display = 'inline-flex';
    iconContainerStyle.alignItems = 'center';
    iconContainerStyle.justifyContent = 'center';
    iconContainerStyle.lineHeight = 0;

    if (w.props?.headingColor) headingStyle.color = w.props.headingColor;
    if (w.props?.headingFontFamily) headingStyle.fontFamily = w.props.headingFontFamily;
    const headingFs = pickResponsive(w.props?.headingFontSize, w.props?.headingFontSizeTablet, w.props?.headingFontSizeMobile);
    if (headingFs !== undefined && headingFs !== null && headingFs !== '') headingStyle.fontSize = `${headingFs}px`;
    if (w.props?.headingFontWeight) headingStyle.fontWeight = w.props.headingFontWeight;
    const headingLh = pickResponsive(w.props?.headingLineHeight, w.props?.headingLineHeightTablet, w.props?.headingLineHeightMobile);
    if (headingLh !== undefined && headingLh !== null && headingLh !== '') headingStyle.lineHeight = headingLh;
    if (w.props?.headingLetterSpacing !== undefined) headingStyle.letterSpacing = `${w.props.headingLetterSpacing}px`;
    if (w.props?.headingWordSpacing !== undefined) headingStyle.wordSpacing = `${w.props.headingWordSpacing}px`;
    if (w.props?.headingMarginTop !== undefined) headingStyle.marginTop = `${w.props.headingMarginTop}px`;
    if (w.props?.headingMarginRight !== undefined) headingStyle.marginRight = `${w.props.headingMarginRight}px`;
    if (w.props?.headingMarginBottom !== undefined) headingStyle.marginBottom = `${w.props.headingMarginBottom}px`;
    if (w.props?.headingMarginLeft !== undefined) headingStyle.marginLeft = `${w.props.headingMarginLeft}px`;

    if (w.props?.subtextColor) subtextStyle.color = w.props.subtextColor;
    if (w.props?.subtextFontFamily) subtextStyle.fontFamily = w.props.subtextFontFamily;
    const subtextFs = pickResponsive(w.props?.subtextFontSize, w.props?.subtextFontSizeTablet, w.props?.subtextFontSizeMobile);
    if (subtextFs !== undefined && subtextFs !== null && subtextFs !== '') subtextStyle.fontSize = `${subtextFs}px`;
    if (w.props?.subtextFontWeight) subtextStyle.fontWeight = w.props.subtextFontWeight;
    const subtextLh = pickResponsive(w.props?.subtextLineHeight, w.props?.subtextLineHeightTablet, w.props?.subtextLineHeightMobile);
    if (subtextLh !== undefined && subtextLh !== null && subtextLh !== '') subtextStyle.lineHeight = subtextLh;
    if (w.props?.subtextLetterSpacing !== undefined) subtextStyle.letterSpacing = `${w.props.subtextLetterSpacing}px`;
    if (w.props?.subtextWordSpacing !== undefined) subtextStyle.wordSpacing = `${w.props.subtextWordSpacing}px`;
    if (w.props?.subtextMarginTop !== undefined) subtextStyle.marginTop = `${w.props.subtextMarginTop}px`;
    if (w.props?.subtextMarginRight !== undefined) subtextStyle.marginRight = `${w.props.subtextMarginRight}px`;
    if (w.props?.subtextMarginBottom !== undefined) subtextStyle.marginBottom = `${w.props.subtextMarginBottom}px`;
    if (w.props?.subtextMarginLeft !== undefined) subtextStyle.marginLeft = `${w.props.subtextMarginLeft}px`;

    if (w.props?.backgroundColor) containerStyle.backgroundColor = w.props.backgroundColor;

    const hasContainerBorderWidth = w.props?.borderWidthTop !== undefined || w.props?.borderWidthRight !== undefined || w.props?.borderWidthBottom !== undefined || w.props?.borderWidthLeft !== undefined;
    if (typeof w.props?.borderStyle === 'string' && w.props.borderStyle && w.props.borderStyle !== 'default') {
        containerStyle.borderStyle = w.props.borderStyle;
    } else if (hasContainerBorderWidth) {
        containerStyle.borderStyle = 'solid';
    }
    if (typeof w.props?.borderColor === 'string' && w.props.borderColor.trim()) {
        containerStyle.borderColor = w.props.borderColor;
    } else if (hasContainerBorderWidth) {
        containerStyle.borderColor = '#e5e7eb';
    }
    if (w.props?.borderWidthTop !== undefined) containerStyle.borderTopWidth = `${w.props.borderWidthTop}px`;
    if (w.props?.borderWidthRight !== undefined) containerStyle.borderRightWidth = `${w.props.borderWidthRight}px`;
    if (w.props?.borderWidthBottom !== undefined) containerStyle.borderBottomWidth = `${w.props.borderWidthBottom}px`;
    if (w.props?.borderWidthLeft !== undefined) containerStyle.borderLeftWidth = `${w.props.borderWidthLeft}px`;

    const pTop = w.props?.paddingTop;
    const pRight = w.props?.paddingRight;
    const pBottom = w.props?.paddingBottom;
    const pLeft = w.props?.paddingLeft;
    if (pTop !== undefined) containerStyle.paddingTop = `${pTop}px`;
    if (pRight !== undefined) containerStyle.paddingRight = `${pRight}px`;
    if (pBottom !== undefined) containerStyle.paddingBottom = `${pBottom}px`;
    if (pLeft !== undefined) containerStyle.paddingLeft = `${pLeft}px`;

    const brTop = w.props?.borderRadiusTop;
    const brRight = w.props?.borderRadiusRight;
    const brBottom = w.props?.borderRadiusBottom;
    const brLeft = w.props?.borderRadiusLeft;
    if (brTop !== undefined) containerStyle.borderTopLeftRadius = `${brTop}px`;
    if (brRight !== undefined) containerStyle.borderTopRightRadius = `${brRight}px`;
    if (brBottom !== undefined) containerStyle.borderBottomRightRadius = `${brBottom}px`;
    if (brLeft !== undefined) containerStyle.borderBottomLeftRadius = `${brLeft}px`;
    if (w.props?.borderRadius !== undefined) containerStyle.borderRadius = `${w.props.borderRadius}px`;

    const mTop = w.props?.marginTop;
    const mRight = w.props?.marginRight;
    const mBottom = w.props?.marginBottom;
    const mLeft = w.props?.marginLeft;
    if (mTop !== undefined) containerStyle.marginTop = `${mTop}px`;
    if (mRight !== undefined) containerStyle.marginRight = `${mRight}px`;
    if (mBottom !== undefined) containerStyle.marginBottom = `${mBottom}px`;
    if (mLeft !== undefined) containerStyle.marginLeft = `${mLeft}px`;

    const processedSvg = w.props?.iconColor ? injectSvgColor(iconSvg, w.props.iconColor) : iconSvg;
    const iconSource = w.props?.iconSource === 'svg' || w.props?.iconSource === 'url' ? w.props.iconSource : '';
    const useSvg = iconSvg !== '' && (iconSource === 'svg' || iconUrl === '');

    const iconElement = useSvg ? (
        <div style={iconContainerStyle} dangerouslySetInnerHTML={{ __html: svgWithSize(processedSvg, iconSize) }} />
    ) : iconUrl ? (
        <div style={iconContainerStyle}>
            <img src={iconUrl} alt="" style={{ width: `${iconSize}px`, height: `${iconSize}px`, objectFit: 'contain' }} />
        </div>
    ) : (
        <div
            style={{
                ...iconContainerStyle,
                width: `${iconSize}px`,
                height: `${iconSize}px`,
                backgroundColor: iconContainerStyle.backgroundColor ?? '#e5e7eb',
                borderRadius: iconContainerStyle.borderRadius ?? '8px',
            }}
            className="flex items-center justify-center text-gray-400"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
                aria-hidden="true"
                style={{ width: '60%', height: '60%' }}
            >
                <circle cx="12" cy="13" r="8" />
                <path d="M12 9v4l2 2" />
                <path d="M5 3 2 6" />
                <path d="m22 6-3-3" />
                <path d="M6.38 18.7 4 21" />
                <path d="M17.64 18.67 20 21" />
            </svg>
        </div>
    );

    const textContent = (
        <div>
            <div className="font-semibold text-lg" style={headingStyle} dangerouslySetInnerHTML={{ __html: heading }} />
            {hasSubtext ? (
                <div className="text-sm text-gray-600" style={subtextStyle} dangerouslySetInnerHTML={{ __html: subtext }} />
            ) : null}
        </div>
    );

    const hoverScope = `mp-icon-hover-${String(w.id || 'x').replace(/[^a-zA-Z0-9_-]/g, '')}`;
    const hoverCss = buildHoverCss(w.props || {}, hoverScope);
    const hoverStyleEl = hoverCss ? <style dangerouslySetInnerHTML={{ __html: hoverCss }} /> : null;
    const rootClass = `${alignClasses[align] || 'text-center'} ${hoverScope}`;

    if (iconPosition === 'top') {
        return (
            <div className={rootClass} style={containerStyle}>
                <div style={{ display: 'flex', flexDirection: 'column', alignItems: align === 'left' ? 'flex-start' : align === 'right' ? 'flex-end' : 'center', gap: `${iconGap}px` }}>
                    {iconElement}
                    {textContent}
                </div>
                {hoverStyleEl}
            </div>
        );
    }

    if (iconPosition === 'left') {
        return (
            <div className={rootClass} style={containerStyle}>
                <div style={{ display: 'flex', flexDirection: 'row', alignItems: 'center', gap: `${iconGap}px`, justifyContent: align === 'left' ? 'flex-start' : align === 'right' ? 'flex-end' : 'center' }}>
                    {iconElement}
                    {textContent}
                </div>
                {hoverStyleEl}
            </div>
        );
    }

    if (iconPosition === 'right') {
        return (
            <div className={rootClass} style={containerStyle}>
                <div style={{ display: 'flex', flexDirection: 'row', alignItems: 'center', gap: `${iconGap}px`, justifyContent: align === 'left' ? 'flex-start' : align === 'right' ? 'flex-end' : 'center' }}>
                    {textContent}
                    {iconElement}
                </div>
                {hoverStyleEl}
            </div>
        );
    }

    return null;
}

const iconSchema = {
    content: [
        {
            type: CONTROL.CONTROL_GROUP,
            kind: 'stack',
            name: 'iconContentStack',
            children: [
                { type: CONTROL.CONTROL_WYSIWYG, name: 'heading', label: 'Heading', bind: 'props.heading' },
                { type: CONTROL.CONTROL_WYSIWYG, name: 'subtext', label: 'Subtext', bind: 'props.subtext' },
                { type: CONTROL.CONTROL_MEDIA_SOURCES, name: 'iconSources', label: 'Icon', bind: { url: 'props.iconUrl', svg: 'props.iconSvg', source: 'props.iconSource' } },
                { type: CONTROL.CONTROL_SELECT, name: 'iconPosition', label: 'Icon Position', bind: 'props.iconPosition', options: [
                    { value: 'top', label: 'Top' },
                    { value: 'left', label: 'Left' },
                    { value: 'right', label: 'Right' },
                ]},
                { type: CONTROL.CONTROL_SELECT, name: 'align', label: 'Alignment', bind: 'props.align', options: [
                    { value: 'left', label: 'Left' },
                    { value: 'center', label: 'Center' },
                    { value: 'right', label: 'Right' },
                ]},
                { type: CONTROL.CONTROL_SLIDER, name: 'iconSize', label: 'Icon Size (px)', bind: 'props.iconSize', kind: 'font-size', min: 16, max: 256 },
                { type: CONTROL.CONTROL_SLIDER, name: 'iconGap', label: 'Icon Gap (px)', bind: 'props.iconGap', kind: 'gap' },
            ],
        },
    ],
    style: [
        {
            title: 'Icon',
            defaultOpen: true,
            children: [
                { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'iconColor', label: 'Icon Color', bind: 'props.iconColor' },
                { type: CONTROL.CONTROL_GROUP, kind: 'background', label: 'Icon Background', bind: { type: 'props.iconBackgroundType', color: 'props.iconBackgroundColor', from: 'props.iconGradientFrom', to: 'props.iconGradientTo', gradientType: 'props.iconGradientType', position: 'props.iconGradientPosition', angle: 'props.iconGradientAngle' } },
                { type: CONTROL.CONTROL_DIMENSIONS, name: 'iconPadding', label: 'Icon Padding', bind: { linked: 'props.iconPaddingLinked', t: 'props.iconPaddingTop', r: 'props.iconPaddingRight', b: 'props.iconPaddingBottom', l: 'props.iconPaddingLeft' } },
                { type: CONTROL.CONTROL_GROUP, kind: 'border', label: 'Icon Border', bind: { styleKey: 'props.iconBorderStyle', colorKey: 'props.iconBorderColor', widthKeys: { linked: 'props.iconBorderWidthLinked', t: 'props.iconBorderWidthTop', r: 'props.iconBorderWidthRight', b: 'props.iconBorderWidthBottom', l: 'props.iconBorderWidthLeft' } }, includeRadius: true, radiusBind: { linked: 'props.iconBorderRadiusLinked', t: 'props.iconBorderRadiusTop', r: 'props.iconBorderRadiusRight', b: 'props.iconBorderRadiusBottom', l: 'props.iconBorderRadiusLeft' } },
                { type: CONTROL.CONTROL_GROUP, kind: 'box_shadow', label: 'Icon Shadow', bind: { color: 'props.shadowColor', offsetX: 'props.shadowX', offsetY: 'props.shadowY', blur: 'props.shadowBlur' } },
            ],
        },
        {
            title: 'Heading',
            defaultOpen: false,
            children: [
                { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Heading Typography', bind: { fontFamily: 'props.headingFontFamily', fontSize: 'props.headingFontSize', fontWeight: 'props.headingFontWeight', lineHeight: 'props.headingLineHeight', letterSpacing: 'props.headingLetterSpacing', wordSpacing: 'props.headingWordSpacing' } },
                { type: CONTROL.CONTROL_DIMENSIONS, name: 'headingMargin', label: 'Heading Margin', bind: { linked: 'props.headingMarginLinked', t: 'props.headingMarginTop', r: 'props.headingMarginRight', b: 'props.headingMarginBottom', l: 'props.headingMarginLeft' } },
                { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'headingColor', label: 'Heading Color', bind: 'props.headingColor' },
            ],
        },
        {
            title: 'Subtext',
            defaultOpen: false,
            children: [
                { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Subtext Typography', bind: { fontFamily: 'props.subtextFontFamily', fontSize: 'props.subtextFontSize', fontWeight: 'props.subtextFontWeight', lineHeight: 'props.subtextLineHeight', letterSpacing: 'props.subtextLetterSpacing', wordSpacing: 'props.subtextWordSpacing' } },
                { type: CONTROL.CONTROL_DIMENSIONS, name: 'subtextMargin', label: 'Subtext Margin', bind: { linked: 'props.subtextMarginLinked', t: 'props.subtextMarginTop', r: 'props.subtextMarginRight', b: 'props.subtextMarginBottom', l: 'props.subtextMarginLeft' } },
                { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'subtextColor', label: 'Subtext Color', bind: 'props.subtextColor' },
            ],
        },
        {
            title: 'Container',
            defaultOpen: false,
            children: [
                { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'backgroundColor', label: 'Container Background', bind: 'props.backgroundColor' },
                { type: CONTROL.CONTROL_DIMENSIONS, name: 'padding', label: 'Container Padding', bind: { linked: 'props.paddingLinked', t: 'props.paddingTop', r: 'props.paddingRight', b: 'props.paddingBottom', l: 'props.paddingLeft' } },
                { type: CONTROL.CONTROL_DIMENSIONS, name: 'margin', label: 'Container Margin', bind: { linked: 'props.marginLinked', t: 'props.marginTop', r: 'props.marginRight', b: 'props.marginBottom', l: 'props.marginLeft' } },
                { type: CONTROL.CONTROL_GROUP, kind: 'border', label: 'Container Border', bind: { styleKey: 'props.borderStyle', colorKey: 'props.borderColor', widthKeys: { linked: 'props.borderWidthLinked', t: 'props.borderWidthTop', r: 'props.borderWidthRight', b: 'props.borderWidthBottom', l: 'props.borderWidthLeft' } }, includeRadius: true, radiusBind: { linked: 'props.borderRadiusLinked', t: 'props.borderRadiusTop', r: 'props.borderRadiusRight', b: 'props.borderRadiusBottom', l: 'props.borderRadiusLeft' } },
            ],
        },
        hoverStyleAccordion(),
    ],
    advanced: [],
};

registerElement({
    type: 'icon',
    label: 'Icon',
    group: 'Basic',
    defaultProps: {
        iconUrl: '', iconSvg: '', iconSource: 'url', heading: 'Heading', subtext: 'Subtext', iconPosition: 'top', iconSize: 64, iconGap: 16, align: 'center',
        iconColor: '', iconBackgroundType: 'color', iconBackgroundColor: '', iconGradientFrom: '#111827', iconGradientTo: '#3b82f6', iconGradientType: 'linear', iconGradientPosition: 'top center', iconGradientAngle: 135,
        shadowColor: '', shadowX: 0, shadowY: 0, shadowBlur: 0,
        headingColor: '', subtextColor: '', backgroundColor: '',
    },
    render: renderIconPreview,
    schema: iconSchema,
});
