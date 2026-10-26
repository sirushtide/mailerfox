import React from 'react';
import * as CONTROL from '../controls/types.js';
import { registerElement } from '../elementRegistry.js';

function formFieldBaseStyle(widget) {
    const p = widget.props || {};
    return {
        width: '100%',
        padding: `${p.paddingTop ?? 8}px ${p.paddingRight ?? 12}px ${p.paddingBottom ?? 8}px ${p.paddingLeft ?? 12}px`,
        borderWidth: `${p.borderWidthTop ?? 1}px`,
        borderStyle: p.borderStyle || 'solid',
        borderColor: p.borderColor || '#d1d5db',
        borderRadius: `${p.borderRadiusTop ?? 6}px`,
        backgroundColor: p.backgroundColor || '#ffffff',
        color: p.textColor || '#111827',
        fontFamily: p.fontFamily || 'inherit',
        fontSize: p.fontSize != null ? `${p.fontSize}px` : '14px',
        fontWeight: p.fontWeight || '400',
        lineHeight: p.lineHeight != null ? `${p.lineHeight}px` : '1.5',
        letterSpacing: p.letterSpacing != null ? `${p.letterSpacing}px` : undefined,
        wordSpacing: p.wordSpacing != null ? `${p.wordSpacing}px` : undefined,
        outline: 'none',
    };
}

function labelStyle(widget) {
    const p = widget.props || {};
    return {
        display: 'block',
        marginBottom: '4px',
        fontSize: '13px',
        fontWeight: '500',
        color: p.labelColor || '#374151',
    };
}

function requiredMark() {
    return <span style={{ color: '#ef4444', marginLeft: '2px' }}>*</span>;
}

function wrapperStyle(widget) {
    const p = widget.props || {};
    const style = {};
    if (p.marginTop != null) style.marginTop = `${p.marginTop}px`;
    if (p.marginRight != null) style.marginRight = `${p.marginRight}px`;
    if (p.marginBottom != null) style.marginBottom = `${p.marginBottom}px`;
    if (p.marginLeft != null) style.marginLeft = `${p.marginLeft}px`;
    return style;
}

export function renderFormTextPreview(widget) {
    const p = widget.props || {};
    const label = p.label || 'Text Input';
    const placeholder = p.placeholder || '';
    const isRequired = p.required === true;

    return (
        <div className="mp-form-field" style={{ width: '100%', ...wrapperStyle(widget) }}>
            <label style={labelStyle(widget)}>
                {label}
                {isRequired ? requiredMark() : null}
            </label>
            <div style={{ ...formFieldBaseStyle(widget), boxSizing: 'border-box', overflow: 'hidden', whiteSpace: 'nowrap', textOverflow: 'ellipsis' }}>
                {placeholder ? <span style={{ opacity: 0.5 }}>{placeholder}</span> : null}
            </div>
        </div>
    );
}

export function renderFormEmailPreview(widget) {
    const p = widget.props || {};
    const label = p.label || 'Email';
    const placeholder = p.placeholder || 'you@example.com';
    const isRequired = p.required !== false;

    return (
        <div className="mp-form-field" style={{ width: '100%', ...wrapperStyle(widget) }}>
            <label style={labelStyle(widget)}>
                {label}
                {isRequired ? requiredMark() : null}
            </label>
            <div style={{ ...formFieldBaseStyle(widget), boxSizing: 'border-box', overflow: 'hidden', whiteSpace: 'nowrap', textOverflow: 'ellipsis' }}>
                {placeholder ? <span style={{ opacity: 0.5 }}>{placeholder}</span> : null}
            </div>
        </div>
    );
}

export function renderFormTextareaPreview(widget) {
    const p = widget.props || {};
    const label = p.label || 'Textarea';
    const placeholder = p.placeholder || '';
    const isRequired = p.required === true;
    const rows = p.rows != null ? p.rows : 4;

    return (
        <div className="mp-form-field" style={{ width: '100%', ...wrapperStyle(widget) }}>
            <label style={labelStyle(widget)}>
                {label}
                {isRequired ? requiredMark() : null}
            </label>
            <div style={{ ...formFieldBaseStyle(widget), boxSizing: 'border-box', minHeight: `${rows * 24}px` }}>
                {placeholder ? <span style={{ opacity: 0.5 }}>{placeholder}</span> : null}
            </div>
        </div>
    );
}

export function renderFormSelectPreview(widget) {
    const p = widget.props || {};
    const label = p.label || 'Dropdown';
    const isRequired = p.required === true;
    const options = Array.isArray(p.options) && p.options.length > 0 ? p.options : ['Option 1', 'Option 2'];
    const firstOpt = options[0];
    const firstLabel = typeof firstOpt === 'string' ? firstOpt : firstOpt.label || firstOpt.value || '';

    return (
        <div className="mp-form-field" style={{ width: '100%', ...wrapperStyle(widget) }}>
            <label style={labelStyle(widget)}>
                {label}
                {isRequired ? requiredMark() : null}
            </label>
            <div style={{ ...formFieldBaseStyle(widget), boxSizing: 'border-box', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <span>{firstLabel}</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{ opacity: 0.5, flexShrink: 0 }}>
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </div>
        </div>
    );
}

export function renderFormCheckboxPreview(widget) {
    const p = widget.props || {};
    const label = p.label || 'Checkbox';
    const isRequired = p.required === true;
    const options = Array.isArray(p.options) && p.options.length > 0 ? p.options : [{ value: 'agree', label: 'I agree' }];

    return (
        <div className="mp-form-field" style={{ width: '100%', ...wrapperStyle(widget) }}>
            <label style={labelStyle(widget)}>
                {label}
                {isRequired ? requiredMark() : null}
            </label>
            <div style={{ display: 'flex', flexDirection: 'column', gap: '6px', marginTop: '4px' }}>
                {options.map((opt, i) => {
                    const text = typeof opt === 'string' ? opt : opt.label || opt.value || '';
                    return (
                        <div key={i} style={{ display: 'flex', alignItems: 'center', gap: '6px', fontSize: '13px', color: p.labelColor || '#374151' }}>
                            <div style={{ width: '16px', height: '16px', border: '1.5px solid #9ca3af', borderRadius: '3px', flexShrink: 0, backgroundColor: '#fff' }} />
                            {text}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

export function renderFormRadioPreview(widget) {
    const p = widget.props || {};
    const label = p.label || 'Radio Group';
    const isRequired = p.required === true;
    const options = Array.isArray(p.options) && p.options.length > 0 ? p.options : ['Option 1', 'Option 2'];

    return (
        <div className="mp-form-field" style={{ width: '100%', ...wrapperStyle(widget) }}>
            <label style={labelStyle(widget)}>
                {label}
                {isRequired ? requiredMark() : null}
            </label>
            <div style={{ display: 'flex', flexDirection: 'column', gap: '6px', marginTop: '4px' }}>
                {options.map((opt, i) => {
                    const text = typeof opt === 'string' ? opt : opt.label || opt.value || '';
                    return (
                        <div key={i} style={{ display: 'flex', alignItems: 'center', gap: '6px', fontSize: '13px', color: p.labelColor || '#374151' }}>
                            <div style={{ width: '16px', height: '16px', border: '1.5px solid #9ca3af', borderRadius: '50%', flexShrink: 0, backgroundColor: '#fff' }} />
                            {text}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

export function renderFormSubmitPreview(widget) {
    const p = widget.props || {};
    const text = p.text || 'Submit';

    const baseStyle = {
        padding: `${p.paddingTop ?? 10}px ${p.paddingRight ?? 20}px ${p.paddingBottom ?? 10}px ${p.paddingLeft ?? 20}px`,
        borderWidth: `${p.borderWidthTop ?? 0}px`,
        borderStyle: p.borderStyle || 'solid',
        borderColor: p.borderColor || 'transparent',
        borderRadius: `${p.borderRadiusTop ?? 6}px`,
        backgroundColor: p.backgroundColor || '#2563eb',
        color: p.textColor || '#ffffff',
        fontFamily: p.fontFamily || 'inherit',
        fontSize: p.fontSize != null ? `${p.fontSize}px` : '14px',
        fontWeight: p.fontWeight || '600',
        lineHeight: p.lineHeight != null ? `${p.lineHeight}px` : '1.5',
        letterSpacing: p.letterSpacing != null ? `${p.letterSpacing}px` : undefined,
        wordSpacing: p.wordSpacing != null ? `${p.wordSpacing}px` : undefined,
        cursor: 'default',
        width: p.fullWidth ? '100%' : 'auto',
        display: 'inline-block',
        textAlign: 'center',
    };

    return (
        <div className="mp-form-field" style={{ width: '100%', ...wrapperStyle(widget) }}>
            <button type="button" disabled style={baseStyle}>
                {text}
            </button>
        </div>
    );
}

const formOptionsRepeater = {
    type: CONTROL.CONTROL_GROUP,
    kind: 'repeater',
    label: 'Options',
    bind: 'props.options',
    itemSchema: {
        itemLabel: 'Option',
        defaultItem: () => ({ value: 'option', label: 'Option' }),
        fields: [
            { name: 'value', label: 'Value' },
            { name: 'label', label: 'Label' },
        ],
    },
};

const formCommonContent = [
    { type: CONTROL.CONTROL_TEXT, name: 'name', label: 'Field Name', bind: 'props.name' },
    { type: CONTROL.CONTROL_TEXT, name: 'label', label: 'Label', bind: 'props.label' },
    { type: CONTROL.CONTROL_SWITCHER, name: 'required', label: 'Required', bind: 'props.required' },
    { type: CONTROL.CONTROL_TEXT, name: 'helpText', label: 'Help Text', bind: 'props.helpText' },
];

const formFieldStyleSchema = [
    { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'textColor', label: 'Text Color', bind: 'props.textColor' },
    { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'labelColor', label: 'Label Color', bind: 'props.labelColor' },
    { type: CONTROL.CONTROL_GROUP, kind: 'typography', label: 'Typography', bind: {
        fontFamily: 'props.fontFamily', fontSize: 'props.fontSize', fontWeight: 'props.fontWeight',
        lineHeight: 'props.lineHeight', letterSpacing: 'props.letterSpacing', wordSpacing: 'props.wordSpacing',
    }},
    { type: CONTROL.CONTROL_GROUP, kind: 'border', label: 'Border', bind: {
        styleKey: 'props.borderStyle', colorKey: 'props.borderColor', widthKeys: {
            linked: 'props.borderWidthLinked', t: 'props.borderWidthTop', r: 'props.borderWidthRight', b: 'props.borderWidthBottom', l: 'props.borderWidthLeft',
        },
    }, includeRadius: true, radiusBind: {
        linked: 'props.borderRadiusLinked', t: 'props.borderRadiusTop', r: 'props.borderRadiusRight', b: 'props.borderRadiusBottom', l: 'props.borderRadiusLeft',
    }},
    { type: CONTROL.CONTROL_GROUP, kind: 'background', label: 'Background', bind: {
        type: 'props.backgroundType', color: 'props.backgroundColor', from: 'props.gradientFrom', to: 'props.gradientTo',
        fromLocation: 'props.gradientFromLocation', toLocation: 'props.gradientToLocation', gradientType: 'props.gradientType', position: 'props.gradientPosition',
    }},
    { type: CONTROL.CONTROL_GROUP, kind: 'spacing', label: 'Spacing', bind: {
        padding: { linked: 'props.paddingLinked', t: 'props.paddingTop', r: 'props.paddingRight', b: 'props.paddingBottom', l: 'props.paddingLeft' },
        margin: { linked: 'props.marginLinked', t: 'props.marginTop', r: 'props.marginRight', b: 'props.marginBottom', l: 'props.marginLeft' },
    }},
];

function makeFormSchema(content) {
    return { content, style: formFieldStyleSchema, advanced: [] };
}

registerElement({ type: 'form_text', label: 'Text Field', group: 'Form', defaultProps: { name: 'text', label: 'Text Input', placeholder: '', required: false }, render: renderFormTextPreview, schema: makeFormSchema([...formCommonContent, { type: CONTROL.CONTROL_TEXT, name: 'placeholder', label: 'Placeholder', bind: 'props.placeholder' }]) });
registerElement({ type: 'form_email', label: 'Email Field', group: 'Form', defaultProps: { name: 'email', label: 'Email', placeholder: 'you@example.com', required: true }, render: renderFormEmailPreview, schema: makeFormSchema([...formCommonContent, { type: CONTROL.CONTROL_TEXT, name: 'placeholder', label: 'Placeholder', bind: 'props.placeholder' }]) });
registerElement({ type: 'form_textarea', label: 'Textarea', group: 'Form', defaultProps: { name: 'message', label: 'Textarea', placeholder: '', required: false, rows: 4 }, render: renderFormTextareaPreview, schema: makeFormSchema([...formCommonContent, { type: CONTROL.CONTROL_TEXT, name: 'placeholder', label: 'Placeholder', bind: 'props.placeholder' }, { type: CONTROL.CONTROL_TEXT, name: 'rows', label: 'Rows', bind: 'props.rows' }]) });
registerElement({ type: 'form_select', label: 'Select', group: 'Form', defaultProps: { name: 'select', label: 'Dropdown', required: false, options: [{ value: '1', label: 'Option 1' }, { value: '2', label: 'Option 2' }] }, render: renderFormSelectPreview, schema: makeFormSchema([...formCommonContent, formOptionsRepeater]) });
registerElement({ type: 'form_checkbox', label: 'Checkbox', group: 'Form', defaultProps: { name: 'checkbox', label: 'Checkbox', required: false, options: [{ value: 'agree', label: 'I agree' }] }, render: renderFormCheckboxPreview, schema: makeFormSchema([...formCommonContent, formOptionsRepeater]) });
registerElement({ type: 'form_radio', label: 'Radio', group: 'Form', defaultProps: { name: 'radio', label: 'Radio Group', required: false, options: [{ value: '1', label: 'Option 1' }, { value: '2', label: 'Option 2' }] }, render: renderFormRadioPreview, schema: makeFormSchema([...formCommonContent, formOptionsRepeater]) });
registerElement({ type: 'form_submit', label: 'Submit Button', group: 'Form', defaultProps: { text: 'Submit', fullWidth: false }, render: renderFormSubmitPreview, schema: makeFormSchema([
    { type: CONTROL.CONTROL_TEXT, name: 'text', label: 'Button Text', bind: 'props.text' },
    { type: CONTROL.CONTROL_SWITCHER, name: 'fullWidth', label: 'Full Width', bind: 'props.fullWidth' },
]) });
