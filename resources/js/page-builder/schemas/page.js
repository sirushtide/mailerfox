import * as CONTROL from '../controls/types.js';

export const pageSchema = {
    content: [
        { type: CONTROL.CONTROL_TEXT, name: 'title', label: 'Page Title', bind: 'title' },
    ],
    style: [
        { type: CONTROL.CONTROL_SELECT, name: 'pageMaxWidthType', label: 'Width', bind: 'style.pageMaxWidthType', options: [{ value: 'full', label: 'Full Width' }, { value: 'boxed', label: 'Boxed' }] },
        { type: CONTROL.CONTROL_SLIDER, name: 'pageBoxedWidth', label: 'Boxed Width', bind: 'style.pageBoxedWidth', kind: 'width', condition: { pageMaxWidthType: 'boxed' } },
        { type: CONTROL.CONTROL_GROUP, kind: 'background', label: 'Background', bind: { type: 'style.backgroundType', color: 'style.backgroundColor', from: 'style.gradientFrom', to: 'style.gradientTo', fromLocation: 'style.gradientFromLocation', toLocation: 'style.gradientToLocation', gradientType: 'style.gradientType', position: 'style.gradientPosition', backdropBlur: 'style.backdropBlur', image: 'style.backgroundImage', imageSize: 'style.backgroundSize', imagePosition: 'style.backgroundPosition', imageRepeat: 'style.backgroundRepeat' } },
        { type: CONTROL.CONTROL_GROUP, kind: 'spacing', label: 'Spacing', bind: { padding: { linked: 'style.paddingLinked', t: 'style.paddingTop', r: 'style.paddingRight', b: 'style.paddingBottom', l: 'style.paddingLeft' }, margin: { linked: 'style.marginLinked', t: 'style.marginTop', r: 'style.marginRight', b: 'style.marginBottom', l: 'style.marginLeft' } } },
    ],
    advanced: [],
};

export const headerSettingsControls = [
    { type: CONTROL.CONTROL_SLIDER, name: 'headerHeight', label: 'Height', bind: 'style.headerHeight', kind: 'height' },
    { type: CONTROL.CONTROL_SWITCHER, name: 'headerSticky', label: 'Sticky', bind: 'style.headerSticky' },
    { type: CONTROL.CONTROL_SLIDER, name: 'stickyHeight', label: 'Sticky Height', bind: 'style.stickyHeight', kind: 'height', condition: { headerSticky: true } },
    { type: CONTROL.CONTROL_SLIDER, name: 'stickyWidth', label: 'Sticky Width', bind: 'style.stickyWidth', kind: 'width', condition: { headerSticky: true } },
    { type: CONTROL.CONTROL_DIMENSIONS, name: 'stickyMargin', label: 'Margin', bind: { linked: 'style.stickyMarginLinked', t: 'style.stickyMarginTop', r: 'style.stickyMarginRight', b: 'style.stickyMarginBottom', l: 'style.stickyMarginLeft' }, condition: { headerSticky: true } },
    { type: CONTROL.CONTROL_GROUP, kind: 'background', label: 'Sticky Background', bind: { type: 'style.stickyBackgroundType', color: 'style.stickyBackgroundColor', from: 'style.stickyGradientFrom', to: 'style.stickyGradientTo', fromLocation: 'style.stickyGradientFromLocation', toLocation: 'style.stickyGradientToLocation', gradientType: 'style.stickyGradientType', position: 'style.stickyGradientPosition', backdropBlur: 'style.stickyBackdropBlur' }, condition: { headerSticky: true } },
    { type: CONTROL.CONTROL_GROUP, kind: 'border', label: 'Sticky Border', bind: { styleKey: 'style.stickyBorderStyle', colorKey: 'style.stickyBorderColor', widthKeys: { linked: 'style.stickyBorderWidthLinked', t: 'style.stickyBorderWidthTop', r: 'style.stickyBorderWidthRight', b: 'style.stickyBorderWidthBottom', l: 'style.stickyBorderWidthLeft' } }, includeRadius: true, radiusBind: { linked: 'style.stickyBorderRadiusLinked', t: 'style.stickyBorderRadiusTop', r: 'style.stickyBorderRadiusRight', b: 'style.stickyBorderRadiusBottom', l: 'style.stickyBorderRadiusLeft' }, condition: { headerSticky: true } },
    { type: CONTROL.CONTROL_COLOR, name: 'stickyTextColor', label: 'Sticky Text Color', bind: 'style.stickyTextColor', condition: { headerSticky: true } },
    { type: CONTROL.CONTROL_COLOR, name: 'stickyLinkColor', label: 'Sticky Link Color', bind: 'style.stickyLinkColor', condition: { headerSticky: true } },
];

export const headerSchema = {
    content: [],
    style: [
        { type: CONTROL.CONTROL_SELECT, name: 'pageMaxWidthType', label: 'Width', bind: 'style.pageMaxWidthType', options: [{ value: 'full', label: 'Full Width' }, { value: 'boxed', label: 'Boxed' }] },
        { type: CONTROL.CONTROL_SLIDER, name: 'pageBoxedWidth', label: 'Boxed Width', bind: 'style.pageBoxedWidth', kind: 'width', condition: { pageMaxWidthType: 'boxed' } },
        { type: CONTROL.CONTROL_GROUP, kind: 'background', label: 'Background', bind: { type: 'style.backgroundType', color: 'style.backgroundColor', from: 'style.gradientFrom', to: 'style.gradientTo', fromLocation: 'style.gradientFromLocation', toLocation: 'style.gradientToLocation', gradientType: 'style.gradientType', position: 'style.gradientPosition', backdropBlur: 'style.backdropBlur', image: 'style.backgroundImage', imageSize: 'style.backgroundSize', imagePosition: 'style.backgroundPosition', imageRepeat: 'style.backgroundRepeat' } },
        { type: CONTROL.CONTROL_GROUP, kind: 'spacing', label: 'Spacing', bind: { padding: { linked: 'style.paddingLinked', t: 'style.paddingTop', r: 'style.paddingRight', b: 'style.paddingBottom', l: 'style.paddingLeft' }, margin: { linked: 'style.marginLinked', t: 'style.marginTop', r: 'style.marginRight', b: 'style.marginBottom', l: 'style.marginLeft' } } },
    ],
    advanced: [],
};
