import React, { useMemo } from 'react';
import * as CONTROL from '../controls/types.js';
import { ControlsRenderer } from '../controls/renderer.jsx';

function typographyBind(prefix, name) {
    return {
        fontFamily: `props.${prefix}${name}FontFamily`,
        fontSize: `props.${prefix}${name}FontSize`,
        fontWeight: `props.${prefix}${name}FontWeight`,
        lineHeight: `props.${prefix}${name}LineHeight`,
        letterSpacing: `props.${prefix}${name}LetterSpacing`,
        wordSpacing: `props.${prefix}${name}WordSpacing`,
    };
}

function makeInputStyleSchema(prefix = 'input_') {
    return [
        {
            title: 'Input',
            defaultOpen: true,
            children: [
                { type: CONTROL.CONTROL_HEADING, label: 'Label' },
                { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Label Typography', bind: typographyBind(prefix, 'label') },
                { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'labelColor', label: 'Label Color', bind: `props.${prefix}labelColor` },

                { type: CONTROL.CONTROL_HEADING, label: 'Placeholder' },
                { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Placeholder Typography', bind: typographyBind(prefix, 'placeholder') },
                { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'placeholderColor', label: 'Placeholder Color', bind: `props.${prefix}placeholderColor` },

                { type: CONTROL.CONTROL_HEADING, label: 'Input Text' },
                { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Input Text Typography', bind: typographyBind(prefix, '') },
                { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'textColor', label: 'Input Text Color', bind: `props.${prefix}textColor` },

                {
                    type: CONTROL.CONTROL_GROUP,
                    kind: 'background',
                    label: 'Background',
                    bind: {
                        type: `props.${prefix}backgroundType`,
                        color: `props.${prefix}backgroundColor`,
                        from: `props.${prefix}gradientFrom`,
                        to: `props.${prefix}gradientTo`,
                        fromLocation: `props.${prefix}gradientFromLocation`,
                        toLocation: `props.${prefix}gradientToLocation`,
                        gradientType: `props.${prefix}gradientType`,
                        position: `props.${prefix}gradientPosition`,
                        angle: `props.${prefix}gradientAngle`,
                    },
                },
                {
                    type: CONTROL.CONTROL_GROUP,
                    kind: 'border',
                    label: 'Border',
                    bind: {
                        styleKey: `props.${prefix}borderStyle`,
                        colorKey: `props.${prefix}borderColor`,
                        widthKeys: {
                            linked: `props.${prefix}borderWidthLinked`,
                            t: `props.${prefix}borderWidthTop`,
                            r: `props.${prefix}borderWidthRight`,
                            b: `props.${prefix}borderWidthBottom`,
                            l: `props.${prefix}borderWidthLeft`,
                        },
                    },
                    includeRadius: true,
                    radiusBind: `props.${prefix}borderRadius`,
                },
                { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'borderHoverColor', label: 'Border Hover Color', bind: `props.${prefix}borderHoverColor` },
                { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'borderFocusColor', label: 'Border Focus Color', bind: `props.${prefix}borderFocusColor` },
                {
                    type: CONTROL.CONTROL_GROUP,
                    kind: 'spacing',
                    label: 'Margin / Padding',
                    bind: {
                        padding: {
                            linked: `props.${prefix}paddingLinked`,
                            t: `props.${prefix}paddingTop`,
                            r: `props.${prefix}paddingRight`,
                            b: `props.${prefix}paddingBottom`,
                            l: `props.${prefix}paddingLeft`,
                        },
                        margin: {
                            linked: `props.${prefix}marginLinked`,
                            t: `props.${prefix}marginTop`,
                            r: `props.${prefix}marginRight`,
                            b: `props.${prefix}marginBottom`,
                            l: `props.${prefix}marginLeft`,
                        },
                    },
                },
            ],
        },
    ];
}

export function InputStyleControls({ widget, onChange, prefix = 'input_', globals, uploadUrl }) {
    const schema = useMemo(() => makeInputStyleSchema(prefix), [prefix]);
    return (
        <ControlsRenderer
            schema={schema}
            value={widget}
            onChange={onChange}
            globals={globals}
            uploadUrl={uploadUrl}
        />
    );
}
