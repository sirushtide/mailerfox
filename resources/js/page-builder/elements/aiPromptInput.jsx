import React from 'react';
import * as CONTROL from '../controls/types.js';
import { registerElement } from '../elementRegistry.js';
import { resolveColorToken } from '../controls/bind.js';

function strColor(value, globals, fallback) {
    const resolved = resolveColorToken(value, globals);
    if (typeof resolved === 'string' && resolved.trim()) return resolved.trim();
    if (typeof value === 'string' && value.trim()) return value.trim();
    return fallback;
}

/**
 * Static, non-functional preview of the Foundry-style AI prompt input for the
 * page builder editor canvas. The real interactive behaviour (login popover,
 * voice input, submit, etc.) is rendered server-side for the published page —
 * see PageBuilderRenderer::renderAiPromptInput().
 */
export function renderAiPromptInputPreview(widget, helpers = {}) {
    const p = widget.props || {};
    const globals = helpers.globals || {};

    const placeholder = typeof p.placeholder === 'string' && p.placeholder.trim() ? p.placeholder : 'What would you like to build?';
    const showFileUpload = p.showFileUpload !== false;
    const showDiscuss = p.showDiscuss !== false;
    const showVoice = p.showVoice !== false;
    const discussText = typeof p.discussText === 'string' && p.discussText.trim() ? p.discussText : 'Discuss';
    const maxWidth = typeof p.maxWidth === 'number' && Number.isFinite(p.maxWidth) ? p.maxWidth : 640;

    const backgroundColor = strColor(p.backgroundColor, globals, '#ffffff');
    const borderColor = strColor(p.borderColor, globals, '#e5e7eb');
    const textColor = strColor(p.textColor, globals, '#1f2937');
    const placeholderColor = strColor(p.placeholderColor, globals, '#9ca3af');
    const borderRadius = typeof p.borderRadius === 'number' && Number.isFinite(p.borderRadius) ? p.borderRadius : 16;

    const wrapperStyle = { maxWidth: `${maxWidth}px`, width: '100%', margin: '0 auto' };
    const boxStyle = {
        backgroundColor,
        borderColor,
        borderWidth: '1px',
        borderStyle: 'solid',
        borderRadius: `${borderRadius}px`,
    };

    return (
        <div className="mp-ai-prompt-input" style={wrapperStyle}>
            <div className="p-3" style={boxStyle}>
                <textarea
                    readOnly
                    rows={2}
                    value=""
                    placeholder={placeholder}
                    className="block w-full resize-none border-0 bg-transparent px-0 py-0 text-sm outline-none"
                    style={{ color: textColor, ['--mp-ph-color']: placeholderColor }}
                />
                <div className="mt-2 flex items-center justify-between">
                    <div className="flex items-center gap-1 -mb-2 -ml-1">
                        <span className="h-7 w-7 inline-flex items-center justify-center rounded-lg text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path fillRule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2" />
                            </svg>
                        </span>
                        {showFileUpload ? (
                            <span className="h-7 px-2 inline-flex items-center gap-1.5 rounded-lg text-xs font-medium text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/><circle cx="12" cy="12" r="1"/></svg>
                                Auto
                            </span>
                        ) : null}
                        {showDiscuss ? (
                            <span className="h-7 px-2 inline-flex items-center gap-1 rounded-lg text-xs font-medium text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" /></svg>
                                {discussText}
                            </span>
                        ) : null}
                    </div>
                    <div className="flex items-center gap-2">
                        {showVoice ? (
                            <span className="h-8 w-8 inline-flex items-center justify-center rounded-md text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z" /><path d="M19 10v2a7 7 0 0 1-14 0v-2" /><line x1="12" x2="12" y1="19" y2="22" /></svg>
                            </span>
                        ) : null}
                        <span className="h-8 w-8 inline-flex items-center justify-center rounded-md bg-gray-900 text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M5 12h14M12 5l7 7-7 7" /></svg>
                        </span>
                    </div>
                </div>
            </div>
            <style>{`.mp-ai-prompt-input textarea::placeholder{color:var(--mp-ph-color, #9ca3af);}`}</style>
        </div>
    );
}

const aiPromptInputSchema = {
    content: [
        { type: CONTROL.CONTROL_TEXT, name: 'placeholder', label: 'Placeholder', bind: 'props.placeholder' },
        { type: CONTROL.CONTROL_SWITCHER, name: 'showFileUpload', label: 'Show File Upload', bind: 'props.showFileUpload' },
        { type: CONTROL.CONTROL_SWITCHER, name: 'showDiscuss', label: 'Show Discuss Button', bind: 'props.showDiscuss' },
        { type: CONTROL.CONTROL_TEXT, name: 'discussText', label: 'Discuss Label', bind: 'props.discussText', condition: { showDiscuss: true } },
        { type: CONTROL.CONTROL_SWITCHER, name: 'showVoice', label: 'Show Voice Input', bind: 'props.showVoice' },
        { type: CONTROL.CONTROL_URL, name: 'submitUrl', label: 'Submit Destination (logged in)', bind: 'props.submitUrl', placeholder: '/customer/ai-projects/builder' },
        { type: CONTROL.CONTROL_URL, name: 'loginUrl', label: 'Login URL Override', bind: 'props.loginUrl', placeholder: 'Defaults to site login page' },
    ],
    style: [
        { type: CONTROL.CONTROL_SLIDER, name: 'maxWidth', label: 'Max Width', bind: 'props.maxWidth', kind: 'width', units: ['px'], defaultUnit: 'px', min: 280, max: 960 },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'backgroundColor', label: 'Background Color', bind: 'props.backgroundColor' },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'borderColor', label: 'Border Color', bind: 'props.borderColor' },
        { type: CONTROL.CONTROL_SLIDER, name: 'borderRadius', label: 'Border Radius', bind: 'props.borderRadius', kind: 'border-radius', units: ['px'], defaultUnit: 'px' },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'textColor', label: 'Text Color', bind: 'props.textColor' },
        { type: CONTROL.CONTROL_GLOBAL_COLOR, name: 'placeholderColor', label: 'Placeholder Color', bind: 'props.placeholderColor' },
    ],
    advanced: [],
};

registerElement({
    type: 'ai_prompt_input',
    label: 'AI Prompt Input',
    group: 'Marketing',
    defaultProps: {
        placeholder: 'What would you like to build?',
        showFileUpload: true,
        showDiscuss: true,
        discussText: 'Discuss',
        showVoice: true,
        maxWidth: 640,
        borderRadius: 16,
        backgroundColor: '#ffffff',
        borderColor: '#e5e7eb',
        textColor: '#1f2937',
        placeholderColor: '#9ca3af',
        submitUrl: '',
        loginUrl: '',
    },
    render: renderAiPromptInputPreview,
    schema: aiPromptInputSchema,
});
