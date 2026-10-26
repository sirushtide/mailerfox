import * as CONTROL from '../../controls/types.js';

const borderWidthKeys = (prefix) => ({
    linked: `props.${prefix}BorderWidthLinked`,
    t: `props.${prefix}BorderWidthTop`,
    r: `props.${prefix}BorderWidthRight`,
    b: `props.${prefix}BorderWidthBottom`,
    l: `props.${prefix}BorderWidthLeft`,
});

const paddingBind = (prefix) => ({
    linked: `props.${prefix}PaddingLinked`,
    t: `props.${prefix}PaddingTop`,
    r: `props.${prefix}PaddingRight`,
    b: `props.${prefix}PaddingBottom`,
    l: `props.${prefix}PaddingLeft`,
});

const marginBind = (prefix) => ({
    linked: `props.${prefix}MarginLinked`,
    t: `props.${prefix}MarginTop`,
    r: `props.${prefix}MarginRight`,
    b: `props.${prefix}MarginBottom`,
    l: `props.${prefix}MarginLeft`,
});

// Arrow + Dots styling accordions for the STYLE tab of any carousel element.
export function carouselStyleControls() {
    return [
        {
            title: 'Arrow',
            defaultOpen: false,
            children: [
                { type: CONTROL.CONTROL_MEDIA_SOURCES, name: 'arrowIcon', label: 'Icon', bind: { url: 'props.arrowIconUrl', svg: 'props.arrowIconSvg', source: 'props.arrowIconSource' } },
                {
                    type: CONTROL.CONTROL_GROUP,
                    kind: 'states',
                    label: 'Arrow Style',
                    tabs: ['normal', 'hover'],
                    children: [
                        {
                            type: CONTROL.CONTROL_GROUP,
                            kind: 'border',
                            label: 'Border',
                            includeRadius: true,
                            radiusBind: {
                                normal: { linked: 'props.arrowBorderRadiusLinked', t: 'props.arrowBorderRadiusTop', r: 'props.arrowBorderRadiusRight', b: 'props.arrowBorderRadiusBottom', l: 'props.arrowBorderRadiusLeft' },
                                hover: { linked: 'props.arrowHoverBorderRadiusLinked', t: 'props.arrowHoverBorderRadiusTop', r: 'props.arrowHoverBorderRadiusRight', b: 'props.arrowHoverBorderRadiusBottom', l: 'props.arrowHoverBorderRadiusLeft' },
                            },
                            bind: {
                                normal: { styleKey: 'props.arrowBorderStyle', colorKey: 'props.arrowBorderColor', widthKeys: borderWidthKeys('arrow') },
                                hover: { styleKey: 'props.arrowHoverBorderStyle', colorKey: 'props.arrowHoverBorderColor', widthKeys: borderWidthKeys('arrowHover') },
                            },
                        },
                        {
                            type: CONTROL.CONTROL_GROUP,
                            kind: 'background',
                            label: 'Background',
                            bind: {
                                normal: { type: 'props.arrowBackgroundType', color: 'props.arrowBackgroundColor', from: 'props.arrowGradientFrom', to: 'props.arrowGradientTo', gradientType: 'props.arrowGradientType', position: 'props.arrowGradientPosition' },
                                hover: { type: 'props.arrowHoverBackgroundType', color: 'props.arrowHoverBackgroundColor', from: 'props.arrowHoverGradientFrom', to: 'props.arrowHoverGradientTo', gradientType: 'props.arrowHoverGradientType', position: 'props.arrowHoverGradientPosition' },
                            },
                        },
                        {
                            type: CONTROL.CONTROL_GROUP,
                            kind: 'box_shadow',
                            label: 'Shadow',
                            bind: {
                                normal: { color: 'props.arrowShadowColor', offsetX: 'props.arrowShadowX', offsetY: 'props.arrowShadowY', blur: 'props.arrowShadowBlur', spread: 'props.arrowShadowSpread' },
                                hover: { color: 'props.arrowHoverShadowColor', offsetX: 'props.arrowHoverShadowX', offsetY: 'props.arrowHoverShadowY', blur: 'props.arrowHoverShadowBlur', spread: 'props.arrowHoverShadowSpread' },
                            },
                        },
                        {
                            type: CONTROL.CONTROL_GROUP,
                            kind: 'dimensions',
                            label: 'Size',
                            bind: {
                                normal: { width: 'props.arrowWidth', height: 'props.arrowHeight' },
                                hover: { width: 'props.arrowHoverWidth', height: 'props.arrowHoverHeight' },
                            },
                        },
                        {
                            type: CONTROL.CONTROL_GROUP,
                            kind: 'spacing',
                            label: 'Spacing',
                            bind: {
                                normal: { padding: paddingBind('arrow'), margin: marginBind('arrow') },
                                hover: { padding: paddingBind('arrowHover'), margin: marginBind('arrowHover') },
                            },
                        },
                        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'arrowIconColor', label: 'Icon Color', bind: { normal: 'props.arrowIconColor', hover: 'props.arrowHoverIconColor' } },
                    ],
                },
                { type: CONTROL.CONTROL_SLIDER, name: 'arrowGap', label: 'Gap', kind: 'gap', bind: 'props.arrowGap' },
            ],
        },
        {
            title: 'Dots',
            defaultOpen: false,
            children: [
                {
                    type: CONTROL.CONTROL_GROUP,
                    kind: 'states',
                    label: 'Dots Style',
                    tabs: ['normal', 'active'],
                    children: [
                        {
                            type: CONTROL.CONTROL_GROUP,
                            kind: 'background',
                            label: 'Background',
                            bind: {
                                normal: { type: 'props.dotBackgroundType', color: 'props.dotBackgroundColor', from: 'props.dotGradientFrom', to: 'props.dotGradientTo' },
                                active: { type: 'props.dotActiveBackgroundType', color: 'props.dotActiveBackgroundColor', from: 'props.dotActiveGradientFrom', to: 'props.dotActiveGradientTo' },
                            },
                        },
                        {
                            type: CONTROL.CONTROL_GROUP,
                            kind: 'box_shadow',
                            label: 'Shadow',
                            bind: {
                                normal: { color: 'props.dotShadowColor', offsetX: 'props.dotShadowX', offsetY: 'props.dotShadowY', blur: 'props.dotShadowBlur', spread: 'props.dotShadowSpread' },
                                active: { color: 'props.dotActiveShadowColor', offsetX: 'props.dotActiveShadowX', offsetY: 'props.dotActiveShadowY', blur: 'props.dotActiveShadowBlur', spread: 'props.dotActiveShadowSpread' },
                            },
                        },
                        {
                            type: CONTROL.CONTROL_GROUP,
                            kind: 'dimensions',
                            label: 'Size',
                            bind: {
                                normal: { width: 'props.dotWidth', height: 'props.dotHeight' },
                                active: { width: 'props.dotActiveWidth', height: 'props.dotActiveHeight' },
                            },
                        },
                        { type: CONTROL.CONTROL_SLIDER, name: 'dotGap', label: 'Inner Spacing', kind: 'gap', bind: { normal: 'props.dotGap', active: 'props.dotActiveGap' } },
                        {
                            type: CONTROL.CONTROL_GROUP,
                            kind: 'margin',
                            label: 'Margin',
                            bind: {
                                normal: marginBind('dot'),
                                active: marginBind('dotActive'),
                            },
                        },
                    ],
                },
            ],
        },
    ];
}

// Reusable "Carousel" control group. Spread into any element schema so it can be
// turned into a carousel without duplicating the control definitions.
// Example: content: [ ..., carouselControls() ]
// Binds use flat, namespaced prop keys (props.carouselX) so the shallow widget
// prop merge cannot clobber sibling settings.
export function carouselControls({ prefix = 'props.carousel', title = 'Carousel', defaultOpen = false } = {}) {
    const bind = (suffix) => `${prefix}${suffix}`;

    return {
        title,
        defaultOpen,
        children: [
            { type: CONTROL.CONTROL_DEVICE, name: 'carouselResponsiveDevice' },
            { type: CONTROL.CONTROL_SLIDER, name: 'carouselPerPage', label: 'Slides per view', bind: bind('PerPage'), kind: 'count', min: 1, max: 6, responsive: true },
            { type: CONTROL.CONTROL_SLIDER, name: 'carouselGap', label: 'Gap', bind: bind('Gap'), kind: 'gap' },
            { type: CONTROL.CONTROL_SWITCHER, name: 'carouselLoop', label: 'Loop', bind: bind('Loop') },
            { type: CONTROL.CONTROL_SWITCHER, name: 'carouselAutoplay', label: 'Autoplay', bind: bind('Autoplay') },
            { type: CONTROL.CONTROL_NUMBER, name: 'carouselInterval', label: 'Autoplay interval (s)', bind: bind('Interval'), min: 1, max: 30, condition: { carouselAutoplay: true } },
            { type: CONTROL.CONTROL_SWITCHER, name: 'carouselPauseOnHover', label: 'Pause on hover', bind: bind('PauseOnHover'), condition: { carouselAutoplay: true } },
            { type: CONTROL.CONTROL_SELECT, name: 'carouselEffect', label: 'Effect', bind: bind('Effect'), options: [
                { value: 'slide', label: 'Slide' },
                { value: 'fade', label: 'Fade' },
            ] },
            { type: CONTROL.CONTROL_NUMBER, name: 'carouselSpeed', label: 'Transition speed (ms)', bind: bind('Speed'), min: 100, max: 4000 },
            { type: CONTROL.CONTROL_SELECT, name: 'carouselDirection', label: 'Direction', bind: bind('Direction'), options: [
                { value: 'horizontal', label: 'Horizontal' },
                { value: 'vertical', label: 'Vertical' },
            ], condition: { carouselEffect: 'slide' } },
            { type: CONTROL.CONTROL_SWITCHER, name: 'carouselArrowsShow', label: 'Arrows', bind: bind('ArrowsShow') },
            { type: CONTROL.CONTROL_SELECT, name: 'carouselArrowsPosition', label: 'Arrows position', bind: bind('ArrowsPosition'), options: [
                { value: 'sides', label: 'Left / Right' },
                { value: 'top-left', label: 'Top Left' },
                { value: 'top-right', label: 'Top Right' },
                { value: 'bottom-center', label: 'Bottom Center' },
            ], condition: { carouselArrowsShow: true } },
            { type: CONTROL.CONTROL_SWITCHER, name: 'carouselDotsShow', label: 'Dots', bind: bind('DotsShow') },
            { type: CONTROL.CONTROL_SELECT, name: 'carouselDotsPosition', label: 'Dots position', bind: bind('DotsPosition'), options: [
                { value: 'bottom-center', label: 'Bottom Center' },
                { value: 'bottom-left', label: 'Bottom Left' },
                { value: 'bottom-right', label: 'Bottom Right' },
                { value: 'top-right', label: 'Top Right' },
            ], condition: { carouselDotsShow: true } },
        ],
    };
}
