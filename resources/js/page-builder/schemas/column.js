import * as CONTROL from '../controls/types.js';

const containerStyleSchema = {
    style: [
        { type: CONTROL.CONTROL_DEVICE, name: 'responsiveDevice' },
        { type: CONTROL.CONTROL_SELECT, name: 'maxWidthType', label: 'Width', bind: 'style.maxWidthType', options: [{ value: 'boxed', label: 'Boxed' }, { value: 'full', label: 'Full Width' }] },
        { type: CONTROL.CONTROL_SLIDER, name: 'boxedWidth', label: 'Boxed Width', bind: 'style.boxedWidth', kind: 'width', responsive: true, condition: { maxWidthType: 'boxed' } },
        { type: CONTROL.CONTROL_SLIDER, name: 'innerMaxWidth', label: 'Inner Max Width', bind: 'style.innerMaxWidth', kind: 'width', responsive: true, condition: { maxWidthType: 'full' } },
        { type: CONTROL.CONTROL_GROUP, kind: 'background', label: 'Background', bind: { type: 'style.backgroundType', color: 'style.backgroundColor', from: 'style.gradientFrom', to: 'style.gradientTo', fromLocation: 'style.gradientFromLocation', toLocation: 'style.gradientToLocation', gradientType: 'style.gradientType', position: 'style.gradientPosition', backdropBlur: 'style.backdropBlur', image: 'style.backgroundImage', imageSize: 'style.backgroundSize', imagePosition: 'style.backgroundPosition', imageRepeat: 'style.backgroundRepeat' } },
        { type: CONTROL.CONTROL_GROUP, kind: 'border', label: 'Border', bind: { styleKey: 'style.borderStyle', colorKey: 'style.borderColor', widthKeys: { linked: 'style.borderWidthLinked', t: 'style.borderWidthTop', r: 'style.borderWidthRight', b: 'style.borderWidthBottom', l: 'style.borderWidthLeft' } }, includeRadius: false, scopeControl: { type: CONTROL.CONTROL_CHOOSE, name: 'borderScope', label: 'Border Scope', bind: 'style.borderScope', options: [{ value: 'full-width', label: 'Full-width' }, { value: 'inner', label: 'Inner' }] } },
        { type: CONTROL.CONTROL_DIMENSIONS, name: 'borderRadius', label: 'Border Radius', bind: { linked: 'style.borderRadiusLinked', t: 'style.borderRadiusTop', r: 'style.borderRadiusRight', b: 'style.borderRadiusBottom', l: 'style.borderRadiusLeft' }, kind: 'border-radius' },
        { type: CONTROL.CONTROL_GROUP, kind: 'spacing', label: 'Spacing', responsive: true, bind: { padding: { linked: 'style.paddingLinked', t: 'style.paddingTop', r: 'style.paddingRight', b: 'style.paddingBottom', l: 'style.paddingLeft' }, margin: { linked: 'style.marginLinked', t: 'style.marginTop', r: 'style.marginRight', b: 'style.marginBottom', l: 'style.marginLeft' } } },
        { type: CONTROL.CONTROL_SLIDER, name: 'height', label: 'Height', bind: 'style.height', kind: 'height', responsive: true },
    ],
    advanced: [
        { type: CONTROL.CONTROL_SWITCHER, name: 'stackedSticky', label: 'Stacked Sticky', bind: 'style.stackedSticky' },
        { type: CONTROL.CONTROL_NUMBER, name: 'stackedZIndex', label: 'Stacked Z-Index', bind: 'style.stackedZIndex', condition: { stackedSticky: true } },
        { type: CONTROL.CONTROL_SLIDER, name: 'transformTranslateX', label: 'Translate X', bind: 'style.transformTranslateX', kind: 'offset' },
        { type: CONTROL.CONTROL_SLIDER, name: 'transformTranslateY', label: 'Translate Y', bind: 'style.transformTranslateY', kind: 'offset' },
        { type: CONTROL.CONTROL_SLIDER, name: 'transformRotate', label: 'Rotate', bind: 'style.transformRotate', kind: 'angle' },
        { type: CONTROL.CONTROL_SLIDER, name: 'transformScale', label: 'Scale', bind: 'style.transformScale', kind: 'opacity' },
        { type: CONTROL.CONTROL_SELECT, name: 'transformOrigin', label: 'Transform Origin', bind: 'style.transformOrigin', options: [{ value: 'center center', label: 'Center' }, { value: 'top left', label: 'Top Left' }, { value: 'top right', label: 'Top Right' }, { value: 'bottom left', label: 'Bottom Left' }, { value: 'bottom right', label: 'Bottom Right' }] },
    ],
};

const containerContentSchema = [
    { type: CONTROL.CONTROL_SELECT, name: 'position', label: 'Position', bind: 'advanced.position', options: [{ value: '', label: 'Default' }, { value: 'static', label: 'Static' }, { value: 'relative', label: 'Relative' }, { value: 'absolute', label: 'Absolute' }, { value: 'fixed', label: 'Fixed' }, { value: 'sticky', label: 'Sticky' }] },
    { type: CONTROL.CONTROL_SLIDER, name: 'absXOffset', label: 'Horizontal position', bind: 'advanced.absXOffset', kind: 'offset', condition: { position: 'absolute' } },
    { type: CONTROL.CONTROL_SLIDER, name: 'absYOffset', label: 'Vertical position', bind: 'advanced.absYOffset', kind: 'offset', condition: { position: 'absolute' } },
    { type: CONTROL.CONTROL_HEADING, label: 'Items' },
    { type: CONTROL.CONTROL_CHOOSE, name: 'itemsDirection', label: 'Direction', responsive: true, bind: 'style.itemsDirection', options: [
        { value: 'row', label: '→', tooltip: 'Row' },
        { value: 'column', label: '↓', tooltip: 'Column' },
        { value: 'row-reverse', label: '←', tooltip: 'Row Reverse' },
        { value: 'column-reverse', label: '↑', tooltip: 'Column Reverse' },
    ]},
    { type: CONTROL.CONTROL_CHOOSE, name: 'itemsJustify', label: 'Justify Content', bind: 'style.itemsJustify', options: [
        { value: 'flex-start', label: 'Start', icon: 'justify-start', tooltip: 'Start' },
        { value: 'center', label: 'Center', icon: 'justify-center', tooltip: 'Center' },
        { value: 'flex-end', label: 'End', icon: 'justify-end', tooltip: 'End' },
        { value: 'space-between', label: 'Between', icon: 'justify-space-between', tooltip: 'Space Between' },
        { value: 'space-around', label: 'Around', icon: 'justify-space-around', tooltip: 'Space Around' },
        { value: 'space-evenly', label: 'Evenly', icon: 'justify-space-evenly', tooltip: 'Space Evenly' },
    ]},
    { type: CONTROL.CONTROL_CHOOSE, name: 'itemsAlign', label: 'Align Items', bind: 'style.itemsAlign', options: [
        { value: 'stretch', label: 'Stretch', icon: 'align-stretch', tooltip: 'Stretch' },
        { value: 'flex-start', label: 'Start', icon: 'align-start', tooltip: 'Start' },
        { value: 'center', label: 'Center', icon: 'align-center', tooltip: 'Center' },
        { value: 'flex-end', label: 'End', icon: 'align-end', tooltip: 'End' },
        { value: 'baseline', label: 'Baseline', icon: 'align-baseline', tooltip: 'Baseline' },
    ]},
    { type: CONTROL.CONTROL_SLIDER, name: 'columnsPerRow', label: 'Columns per row', bind: 'style.columnsPerRow', kind: 'count', min: 1, max: 6, step: 1, responsive: true },
    { type: CONTROL.CONTROL_GAP_BOX, name: 'gapBox', label: 'Gap', bind: { linked: 'style.gapLinked', x: 'style.gapX', y: 'style.gapY', gap: 'style.gap' } },
];

export const columnSchema = {
    content: [
        { type: CONTROL.CONTROL_DEVICE, name: 'responsiveDevice' },
        ...containerContentSchema,
    ],
    style: containerStyleSchema.style,
    advanced: containerStyleSchema.advanced,
};

export const gridColumnSchema = {
    content: [
        { type: CONTROL.CONTROL_DEVICE, name: 'responsiveDevice' },
        ...containerContentSchema,
    ],
    style: containerStyleSchema.style,
    advanced: containerStyleSchema.advanced,
};

// The Grid widget reuses the same Items controls as a Container (Direction,
// Justify Content, Align Items, Columns per row, Gap) plus its own column count.
export const gridContentSchema = [
    { type: CONTROL.CONTROL_DEVICE, name: 'responsiveDevice' },
    { type: CONTROL.CONTROL_NUMBER, name: 'count', label: 'Columns', bind: 'props.count', min: 1, max: 4, step: 1 },
    ...containerContentSchema.slice(3),
];
