import * as CONTROL from '../controls/types.js';

export const siteSettingsSchema = {
    content: [
        { type: CONTROL.CONTROL_HEADING, label: 'Colors' },
        { type: CONTROL.CONTROL_REPEATER, name: 'colors', label: 'Color Palette', bind: 'colors', itemSchema: { itemLabel: 'Color', defaultItem: () => ({ id: `color_${Date.now()}`, label: 'New Color', value: '#000000' }), fields: [{ name: 'id', label: 'ID' }, { name: 'label', label: 'Label' }, { name: 'value', label: 'Value' }] } },
        { type: CONTROL.CONTROL_HEADING, label: 'Typography' },
        { type: CONTROL.CONTROL_REPEATER, name: 'typography', label: 'Typography Presets', bind: 'typography', itemSchema: { itemLabel: 'Preset', defaultItem: () => ({ id: `typo_${Date.now()}`, label: 'New Preset', fontFamily: 'Inter', fontWeight: '400', fontSize: 16, lineHeight: 1.5 }), fields: [{ name: 'id', label: 'ID' }, { name: 'label', label: 'Label' }, { name: 'fontFamily', label: 'Font Family' }, { name: 'fontWeight', label: 'Weight' }, { name: 'fontSize', label: 'Size' }, { name: 'lineHeight', label: 'Line Height' }] } },
        { type: CONTROL.CONTROL_HEADING, label: 'Buttons' },
        { type: CONTROL.CONTROL_REPEATER, name: 'buttons', label: 'Button Presets', bind: 'buttons', itemSchema: { itemLabel: 'Button', defaultItem: () => ({ id: `btn_${Date.now()}`, label: 'New Button', props: {} }), fields: [{ name: 'id', label: 'ID' }, { name: 'label', label: 'Label' }] } },
    ],
    style: [],
    advanced: [],
};
