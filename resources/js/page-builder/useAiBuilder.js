import { useState, useCallback, useEffect, useMemo, useRef } from 'react';
import { THEME_PRESETS, composePromptWithTheme, getThemePreset } from './themePresets.js';

let nextId = 0;
function uid(prefix = 'ai') {
    return `${prefix}-${++nextId}-${Date.now().toString(36)}`;
}

const TOOL_STEP_LABELS = {
    find_element: 'Finding best matching elements',
    create_section: 'Building section…',
    create_page: 'Assembling the page…',
    update_section: 'Updating section…',
    insert_column: 'Adding a column…',
    update_column: 'Updating column…',
    remove_column: 'Removing column…',
    insert_element: 'Adding an element…',
    update_element: 'Updating element…',
    remove_element: 'Removing an element…',
    get_style_presets: 'Loading style presets…',
};

function toolStepLabel(tool) {
    if (TOOL_STEP_LABELS[tool]) return TOOL_STEP_LABELS[tool];
    const pretty = String(tool || '').replace(/_/g, ' ');
    return pretty ? pretty.charAt(0).toUpperCase() + pretty.slice(1) : 'Step';
}

const MODEL_OPTIONS = [
    { value: 'auto', label: 'Auto (recommended)' },
    { value: 'gpt-5.5', label: 'GPT-5.5' },
    { value: 'gpt-5.5-pro', label: 'GPT-5.5 Pro' },
    { value: 'gpt-5.4', label: 'GPT-5.4' },
    { value: 'gpt-5.4-pro', label: 'GPT-5.4 Pro' },
    { value: 'gpt-5.4-mini', label: 'GPT-5.4 Mini' },
    { value: 'gpt-5.4-nano', label: 'GPT-5.4 Nano' },
    { value: 'gpt-5.2', label: 'GPT-5.2' },
    { value: 'gpt-5.2-pro', label: 'GPT-5.2 Pro' },
    { value: 'gpt-5.1', label: 'GPT-5.1' },
    { value: 'gpt-5', label: 'GPT-5' },
    { value: 'gpt-5-pro', label: 'GPT-5 Pro' },
    { value: 'gpt-5-mini', label: 'GPT-5 Mini' },
    { value: 'gpt-5-nano', label: 'GPT-5 Nano' },
    { value: 'gpt-4.1', label: 'GPT-4.1' },
    { value: 'gpt-4.1-mini', label: 'GPT-4.1 Mini' },
    { value: 'gpt-4.1-nano', label: 'GPT-4.1 Nano' },
    { value: 'gpt-4o', label: 'GPT-4o' },
    { value: 'gpt-4o-mini', label: 'GPT-4o Mini' },
    { value: 'o4-mini', label: 'o4-mini' },
    { value: 'o3', label: 'o3' },
    { value: 'o3-mini', label: 'o3-mini' },
    { value: 'o3-pro', label: 'o3-pro' },
    { value: 'gpt-4-32k', label: 'GPT-4 32k' },
    { value: 'gpt-3.5-turbo', label: 'GPT-3.5 Turbo' },
];

export function useAiBuilder({ aiUrl, aiMessageUrl, aiModelsUrl, aiMessagesUrl, builderData, insertSectionAfter, insertSectionAtEnd, replaceSectionByIndex, selectedModel, setSelected, setRailTab, setEditTab, defaultModel = '', enabledModels = [], aiEnabled = true, mode = 'page' }) {
    const [prompt, setPrompt] = useState('');
    const [screenshot, setScreenshot] = useState(null);
    const [screenshotPreviewUrl, setScreenshotPreviewUrl] = useState(null);
    const [model, setModel] = useState(defaultModel || '');
    const enabledModelSet = useMemo(() => new Set(Array.isArray(enabledModels) ? enabledModels : []), [enabledModels]);
    const [strategy, setStrategy] = useState('auto');
    const [availableModels, setAvailableModels] = useState([]);
    const [scope, setScope] = useState('section');
    const [selectedThemeId, setSelectedThemeId] = useState('');
    const [status, setStatus] = useState('idle');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const [messages, setMessages] = useState([]);
    const [messagesLoading, setMessagesLoading] = useState(false);

    const abortRef = useRef(null);
    const appliedRef = useRef({});
    const activeGenerationRef = useRef(null);
    const startSectionCountRef = useRef(0);
    const selectedTheme = useMemo(() => getThemePreset(selectedThemeId), [selectedThemeId]);

    useEffect(() => {
        if (!aiModelsUrl || !aiEnabled || mode === 'email') return;
        fetch(aiModelsUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then((response) => response.json())
            .then((data) => {
                if (Array.isArray(data?.data?.models)) setAvailableModels(data.data.models);
            })
            .catch(() => {});
    }, [aiModelsUrl, aiEnabled, mode]);

    useEffect(() => {
        if (!screenshot) {
            setScreenshotPreviewUrl(null);
            return;
        }
        try {
            const url = URL.createObjectURL(screenshot);
            setScreenshotPreviewUrl(url);
            return () => {
                try { URL.revokeObjectURL(url); } catch (e) {}
            };
        } catch (e) {
            setScreenshotPreviewUrl(null);
        }
    }, [screenshot]);

    useEffect(() => {
        return () => {
            if (abortRef.current) {
                abortRef.current.abort();
            }
        };
    }, []);

    useEffect(() => {
        if (!aiMessagesUrl) return;
        let cancelled = false;
        setMessagesLoading(true);
        (async () => {
            try {
                const resp = await fetch(aiMessagesUrl, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                });
                const data = await resp.json().catch(() => null);
                if (cancelled) return;
                if (resp.ok && data && Array.isArray(data.messages)) {
                    setMessages(data.messages);
                }
            } catch (e) {
                // optional
            } finally {
                if (!cancelled) setMessagesLoading(false);
            }
        })();
        return () => { cancelled = true; };
    }, [aiMessagesUrl]);

    const generate = useCallback(async () => {
        setError('');
        if (!aiUrl && !aiMessageUrl) {
            setError('AI Builder is not available in this environment.');
            return;
        }
        const trimmedPrompt = String(prompt || '').trim();
        const finalPrompt = composePromptWithTheme(trimmedPrompt, selectedThemeId);
        if (finalPrompt.trim() === '') {
            setError('Please enter a prompt describing what you want to generate.');
            return;
        }

        const optimisticUser = {
            id: uid('user'),
            role: 'user',
            content: trimmedPrompt,
            scope,
            model,
            status: 'ok',
            created_at: new Date().toISOString(),
        };
        setMessages((prev) => [...prev, optimisticUser]);

        setPrompt('');
        const sentScreenshot = screenshot;
        setScreenshot(null);
        setScreenshotPreviewUrl(null);

        startSectionCountRef.current = Array.isArray(builderData?.sections) ? builderData.sections.length : 0;
        // Reset per-generation bookkeeping so section ids reused across generations
        // are not treated as "already applied".
        appliedRef.current = {};
        // Guard so a stopped generation's cleanup cannot clear the abort handle of
        // a newly started generation.
        const requestId = uid('req');
        activeGenerationRef.current = requestId;
        setLoading(true);
        setStatus('generating');
        abortRef.current = new AbortController();
        const thoughtId = uid('thought');
        const narrationId = uid('narr');
        const planId = uid('plan');
        const shownRef = {};

        try {
            const form = document.getElementById('page-builder-form');
            const tokenEl = form ? form.querySelector('input[name="_token"]') : null;
            const metaTokenEl = document.querySelector('meta[name="csrf-token"]');
            const token = tokenEl && tokenEl.value
                ? String(tokenEl.value)
                : (metaTokenEl && metaTokenEl.content ? String(metaTokenEl.content) : '');

            const endpoint = aiMessageUrl || aiUrl;
            const payload = {
                operation: 'section',
                prompt: finalPrompt,
                model: model === 'auto' ? null : model,
                strategy,
                section_index: Array.isArray(builderData?.sections) ? builderData.sections.length : 0,
                page: builderData || {},
                stream: true,
                mode,
            };
            const resp = await fetch(endpoint, {
                method: 'POST',
                body: aiMessageUrl ? JSON.stringify(payload) : (() => {
                    const fd = new FormData();
                    if (token) fd.append('_token', token);
                    fd.append('prompt', finalPrompt);
                    fd.append('raw_prompt', trimmedPrompt);
                    fd.append('model', String(model));
                    fd.append('scope', String(scope));
                    fd.append('section_index', String(payload.section_index));
                    fd.append('builder_data', JSON.stringify(builderData || {}));
                    if (sentScreenshot) fd.append('screenshot', sentScreenshot);
                    return fd;
                })(),
                credentials: 'same-origin',
                signal: abortRef.current.signal,
                headers: {
                    ...(aiMessageUrl ? { 'Content-Type': 'application/json' } : {}),
                    ...(token ? { 'X-CSRF-TOKEN': token } : {}),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: aiMessageUrl ? 'text/event-stream, application/json' : 'application/json',
                },
            });

            if (!resp.ok) {
                const json = await resp.json().catch(() => null);
                const msg = json?.message || 'AI generation failed.';
                setError(msg);
                setStatus('error');
                setMessages((prev) => [
                    ...prev,
                    { id: uid('err'), role: 'assistant', type: 'error', content: msg, status: 'error', created_at: new Date().toISOString() },
                ]);
                return;
            }

            const contentType = resp.headers.get('content-type') || '';
            const isSse = aiMessageUrl && contentType.includes('text/event-stream');

            const toolMessagesRef = [];
            let effectiveCount = startSectionCountRef.current;
            const upsert = (id, makeMsg) => {
                setMessages((prev) => {
                    const rest = makeMsg();
                    const idx = prev.findIndex((m) => m.id === id);
                    if (idx < 0) return [...prev, { id, ...rest }];
                    const next = [...prev];
                    next[idx] = { ...next[idx], ...rest };
                    return next;
                });
            };
            const patch = (id, updater) => {
                setMessages((prev) => {
                    const idx = prev.findIndex((m) => m.id === id);
                    if (idx < 0) return prev;
                    const next = [...prev];
                    next[idx] = { ...next[idx], ...updater() };
                    return next;
                });
            };
            const appendMessage = (msg) => setMessages((prev) => [...prev, msg]);
            const handleEvent = (evt) => {
                const name = evt.event;
                const d = evt.data || {};

                if (name === 'connected') {
                    return;
                }

                if (name === 'think') {
                    upsert(thoughtId, () => ({
                        role: 'assistant',
                        type: 'thought',
                        content: 'Thought',
                        thought: d.thought || d.narration || '',
                        status: 'progress',
                        created_at: new Date().toISOString(),
                    }));
                    if (d.narration) {
                        upsert(narrationId, () => ({
                            role: 'assistant',
                            type: 'narration',
                            content: d.narration,
                            status: 'progress',
                            created_at: new Date().toISOString(),
                        }));
                    }
                    return;
                }

                if (name === 'narrate') {
                    upsert(narrationId, () => ({
                        role: 'assistant',
                        type: 'narration',
                        content: d.narration || d.message || '',
                        status: 'progress',
                        created_at: new Date().toISOString(),
                    }));
                    return;
                }

                if (name === 'plan') {
                    upsert(planId, () => ({
                        role: 'assistant',
                        type: 'plan',
                        content: d.items && d.items.length ? d.items.length : '0',
                        plan: Array.isArray(d.items) ? d.items : [],
                        status: 'progress',
                        created_at: new Date().toISOString(),
                    }));
                    return;
                }

                if (name === 'tool_call') {
                    const toolId = uid('tool');
                    const toolName = String(d.tool || '');
                    appendMessage({
                        id: toolId,
                        role: 'assistant',
                        type: 'tool',
                        action: toolStepLabel(toolName),
                        content: toolName === 'find_element' ? '' : (d.narration || ''),
                        status: 'progress',
                        created_at: new Date().toISOString(),
                    });
                    toolMessagesRef.push({ tool: toolName, id: toolId });
                    return;
                }

                if (name === 'tool_result') {
                    let match = null;
                    for (let i = toolMessagesRef.length - 1; i >= 0; i--) {
                        if (toolMessagesRef[i].tool === d.tool) {
                            match = toolMessagesRef[i];
                            break;
                        }
                    }
                    if (match) {
                        patch(match.id, () => ({
                            status: d.ok ? 'ok' : 'error',
                        }));
                    }
                    return;
                }

                if (name === 'section_ready' && d.section) {
                    const section = d.section;
                    const idx = typeof d.index === 'number' ? d.index : null;
                    if (idx !== null && idx < effectiveCount && typeof replaceSectionByIndex === 'function') {
                        replaceSectionByIndex(idx, () => section);
                    } else if (typeof insertSectionAtEnd === 'function') {
                        // Only append once per new section; later updates to it
                        // arrive with an index that is < effectiveCount.
                        if (!appliedRef.current[section.id]) {
                            insertSectionAtEnd(() => section);
                            appliedRef.current[section.id] = true;
                            effectiveCount++;
                        } else if (idx !== null && typeof replaceSectionByIndex === 'function') {
                            replaceSectionByIndex(idx, () => section);
                        }
                    } else if (idx !== null && typeof replaceSectionByIndex === 'function') {
                        replaceSectionByIndex(idx, () => section);
                    }
                    return;
                }

                if (name === 'page_ready' && d.page && typeof insertSectionAtEnd === 'function') {
                    // full-page final: only use it if no section_ready was received
                    // (handled by section_ready already being the source of truth)
                    return;
                }

                if (name === 'clarification') {
                    const question = d.question || 'I need more detail to keep going.';
                    patch(narrationId, () => ({ content: question, status: 'ok' }));
                    appendMessage({
                        id: uid('summary'),
                        role: 'assistant',
                        type: 'summary',
                        content: question,
                        status: 'ok',
                        created_at: new Date().toISOString(),
                    });
                    setStatus('idle');
                    return;
                }

                if (name === 'done') {
                    const resultSection = d.result && d.result.type === 'section' ? d.result.section : null;
                    patch(narrationId, () => ({
                        status: 'ok',
                        ...(resultSection ? { section: resultSection } : {}),
                    }));
                    patch(thoughtId, () => ({ status: 'ok' }));
                    patch(planId, () => ({ status: 'ok' }));
                    setStatus('idle');
                    return;
                }

                if (name === 'error') {
                    const msg = d.message || 'AI generation failed.';
                    setError(msg);
                    setStatus('error');
                    appendMessage({
                        id: uid('err'),
                        role: 'assistant',
                        type: 'error',
                        content: msg,
                        status: 'error',
                        created_at: new Date().toISOString(),
                    });
                    return;
                }
            };

            if (isSse) {
                const reader = resp.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';
                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');
                    buffer = lines.pop() || '';
                    for (const line of lines) {
                        const trimmed = line.trim();
                        if (!trimmed.startsWith('data: ')) continue;
                        try {
                            handleEvent(JSON.parse(trimmed.slice(6)));
                        } catch (e) {
                            // malformed
                        }
                    }
                }
                // remaining
                if (buffer.trim()) {
                    const trimmed = buffer.trim();
                    if (trimmed.startsWith('data: ')) {
                        try { handleEvent(JSON.parse(trimmed.slice(6))); } catch (e) {}
                    }
                }
            } else {
                const json = await resp.json().catch(() => null);
                const events = json?.data?.events;
                if (Array.isArray(events)) {
                    events.forEach(handleEvent);
                } else {
                    const msg = json?.data?.message || json?.message || 'AI generation failed.';
                    setError(msg);
                    setStatus('error');
                    appendMessage({
                        id: uid('err'),
                        role: 'assistant',
                        type: 'error',
                        content: msg,
                        status: 'error',
                        created_at: new Date().toISOString(),
                    });
                }
            }
        } catch (e) {
            if (e && e.name === 'AbortError') {
                setStatus('idle');
                return;
            }
            setError(e && e.message ? String(e.message) : 'AI generation failed.');
            setStatus('error');
        } finally {
            if (activeGenerationRef.current === requestId) {
                setLoading(false);
                abortRef.current = null;
            }
        }
    }, [aiUrl, aiMessageUrl, prompt, screenshot, model, strategy, scope, builderData, insertSectionAtEnd, replaceSectionByIndex, selectedThemeId, selectedTheme]);

    const selectTheme = useCallback((themeId) => {
        const resolvedTheme = getThemePreset(themeId);
        setSelectedThemeId(resolvedTheme ? resolvedTheme.id : '');
    }, []);

    const reapplySection = useCallback((section) => {
        if (!section || typeof insertSectionAtEnd !== 'function') return;
        insertSectionAtEnd(() => section);
    }, [insertSectionAtEnd]);

    const stop = useCallback(() => {
        if (abortRef.current) {
            abortRef.current.abort();
            abortRef.current = null;
            setLoading(false);
            setStatus('idle');
        }
    }, []);

    const statusMessage = '';

    return {
        prompt,
        setPrompt,
        screenshot,
        setScreenshot,
        screenshotPreviewUrl,
        model,
        setModel,
        strategy,
        setStrategy,
        availableModels,
        scope,
        setScope,
        status,
        statusMessage,
        error,
        loading,
        messages,
        messagesLoading,
        generate,
        reapplySection,
        stop,
        modelOptions: (availableModels.length > 0
            ? availableModels.map((item) => ({
                value: item.value,
                label: item.label || item.value,
                providerKey: item.provider || item.providerKey || null,
            }))
            : MODEL_OPTIONS.filter((option) => option.value !== 'auto'))
            .filter((option) => enabledModelSet.size === 0 || enabledModelSet.has(option.value)),
        selectedThemeId,
        selectedTheme,
        themePresets: THEME_PRESETS,
        selectTheme,
        canGenerate: !loading && (String(prompt).trim() !== '' || selectedThemeId !== ''),
    };
}
