import React, { useCallback, useEffect, useMemo, useRef } from 'react';
import { BlockStream } from './activity-stream/index.js';

function ModelOption({ option, selected, logo, onSelect }) {
    return (
        <button
            type="button"
            role="option"
            aria-selected={selected}
            onClick={() => onSelect(option.value)}
            className={`flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left text-xs transition-colors ${
                selected ? 'bg-gray-900 font-medium text-white' : 'text-gray-700 hover:bg-gray-100'
            }`}
        >
            {logo ? (
                <img src={logo} alt="" className="h-4 w-4 flex-shrink-0 rounded object-contain" onError={(event) => { event.currentTarget.style.display = 'none'; }} />
            ) : null}
            <span className="truncate">{option.label}</span>
            {selected ? (
                <svg className="ml-auto flex-shrink-0" xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="m20 6-11 11-5-5" />
                </svg>
            ) : null}
        </button>
    );
}

export function AiBuilderPanel({
    prompt,
    setPrompt,
    screenshot,
    setScreenshot,
    screenshotPreviewUrl,
    model,
    setModel,
    strategy,
    setStrategy,
    scope,
    setScope,
    statusMessage,
    error,
    loading,
    messages,
    messagesLoading,
    generate,
    reapplySection,
    stopGeneration,
    modelOptions,
    canGenerate,
    selectedThemeId = '',
    themePresets = [],
    selectTheme,
    mode = 'page',
}) {
    const scrollRef = useRef(null);
    const modelMenuRef = useRef(null);
    const [modelMenuOpen, setModelMenuOpen] = React.useState(false);
    const [modelSearch, setModelSearch] = React.useState('');
    const hasMessages = Array.isArray(messages) && messages.length > 0;
    const filteredModelOptions = useMemo(() => {
        const search = String(modelSearch || '').trim().toLowerCase();
        const selectableModels = modelOptions.filter((option) => option?.value !== 'auto');
        if (!search) return selectableModels;
        return selectableModels.filter((option) => {
            const value = String(option?.value || '').toLowerCase();
            const label = String(option?.label || '').toLowerCase();
            return value.includes(search) || label.includes(search);
        });
    }, [modelOptions, modelSearch]);
    const [openRouterView, setOpenRouterView] = React.useState(false);
    const groupedModelOptions = useMemo(() => ({
        openRouterOptions: filteredModelOptions.filter((option) => option.providerKey === 'openrouter'),
        otherOptions: filteredModelOptions.filter((option) => option.providerKey !== 'openrouter'),
    }), [filteredModelOptions]);
    const getModelLogo = useCallback((modelValue) => {
        if (!modelValue || modelValue === 'auto') return null;
        const value = String(modelValue).toLowerCase();
        const provider = value.split('/')[0];
        const name = value.includes('/') ? value.split('/').slice(1).join('/') : value;
        const family = [
            [/gpt-|^o\d|chatgpt/, 'openai'],
            [/claude/, 'anthropic'],
            [/gemini|gemma/, 'google'],
            [/grok/, 'xai'],
            [/llama/, 'meta'],
            [/deepseek/, 'deepseek'],
            [/kimi|moonshot/, 'kimi'],
            [/glm/, 'zai'],
            [/mimo/, 'xiaomi'],
            [/mistral|codestral/, 'mistral'],
            [/minimax/, 'minimax'],
            [/qwen|qwq/, 'qwen'],
            [/nemotron/, 'nvidia'],
            [/phi-/, 'microsoft'],
            [/^auto|^free/, 'openrouter'],
        ].find(([matcher]) => matcher.test(name));
        const providerMap = {
            openrouter: 'openrouter',
            openai: 'openai',
            chatgpt: 'openai',
            anthropic: 'anthropic',
            google: 'google',
            'meta-llama': 'meta',
            'x-ai': 'xai',
            mistralai: 'mistral',
            moonshotai: 'kimi',
            'z-ai': 'zai',
        };
        return `/images/model-logos/${family?.[1] || providerMap[provider] || provider}.png`;
    }, []);

    useEffect(() => {
        if (!modelMenuOpen) return undefined;

        const handleDocumentMouseDown = (event) => {
            if (modelMenuRef.current && !modelMenuRef.current.contains(event.target)) {
                setModelMenuOpen(false);
                setModelSearch('');
                setOpenRouterView(false);
            }
        };
        const handleKeyDown = (event) => {
            if (event.key === 'Escape') {
                setModelMenuOpen(false);
                setModelSearch('');
                setOpenRouterView(false);
            }
        };

        document.addEventListener('mousedown', handleDocumentMouseDown);
        document.addEventListener('keydown', handleKeyDown);
        return () => {
            document.removeEventListener('mousedown', handleDocumentMouseDown);
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, [modelMenuOpen]);

    const timelineSegments = useMemo(() => {
        const segments = [];
        let current = null;
        for (const m of messages) {
            if (m.role === 'user' && m.status !== 'selection') {
                if (current) segments.push(current);
                current = { key: m.id, user: m, activities: [], results: [] };
            } else if (m.status === 'progress' || m.status === 'thought') {
                if (!current) current = { key: `pre-${segments.length}`, user: null, activities: [], results: [] };
                current.activities.push(m);
            } else if (m.status === 'ok' || m.status === 'cancelled' || m.status === 'error') {
                if (!current) current = { key: `pre-${segments.length}`, user: null, activities: [], results: [] };
                current.results.push(m);
            }
        }
        if (current) segments.push(current);
        return segments;
    }, [messages]);

    const lastErrorText = useMemo(() => {
        const last = [...messages].reverse().find((m) => m.status === 'error');
        return last && typeof last.content === 'string' ? last.content : '';
    }, [messages]);

    useEffect(() => {
        const el = scrollRef.current;
        if (el) {
            el.scrollTop = el.scrollHeight;
        }
    }, [messages, loading, statusMessage]);
    const handlePaste = useCallback((e) => {
        const items = e && e.clipboardData ? e.clipboardData.items : null;
        if (!items || !items.length) return;

        for (let i = 0; i < items.length; i++) {
            const it = items[i];
            if (!it) continue;
            if (typeof it.type === 'string' && it.type.startsWith('image/')) {
                const f = it.getAsFile ? it.getAsFile() : null;
                if (f) {
                    setScreenshot(f);
                    try {
                        e.preventDefault();
                    } catch (err) {}
                    return;
                }
            }
        }
    }, [setScreenshot]);

    const handleFileChange = useCallback((e) => {
        const f = e && e.target && e.target.files && e.target.files[0] ? e.target.files[0] : null;
        if (f) setScreenshot(f);
    }, [setScreenshot]);

    const scopeOptions = [{ value: 'section', label: 'Section' }];
    const isEmailMode = mode === 'email';

    return (
        <div className="h-full flex flex-col bg-white">
            {/* Chat timeline */}
            <div ref={scrollRef} className="flex-1 min-h-0 overflow-auto">
                {!hasMessages && !messagesLoading ? (
                    <div className="flex flex-col items-center justify-center pt-10 pb-6 px-4">
                        <div className="h-10 w-10 rounded-xl bg-gray-900 flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg>
                        </div>
                        <h2 className="text-base font-semibold text-gray-900">AI Builder</h2>
                        <p className="text-xs text-gray-400 mt-1 text-center">Describe what you want to build.<br/>Generated sections are added to the end of your page.</p>
                    </div>
                ) : null}

                {messagesLoading && !hasMessages ? (
                    <div className="flex items-center justify-center gap-2 text-xs text-gray-400 py-10">
                        <span className="inline-block h-3.5 w-3.5 rounded-full border-2 border-gray-300 border-t-gray-900 animate-spin" />
                        Loading conversation…
                    </div>
                ) : null}

                <div className="px-4 py-4">
                    {loading && !hasMessages ? (
                        <BlockStream
                            items={[]}
                            loading
                            statusMessage={statusMessage}
                            error={lastErrorText}
                        />
                    ) : hasMessages ? timelineSegments.map((segment, segIdx) => {
                        const isLastSegment = segIdx === timelineSegments.length - 1;
                        const isRunning = isLastSegment && loading;
                        const items = [...segment.activities, ...segment.results];
                        const hasTimeline = segment.activities.length > 0 || segment.results.length > 0 || isRunning;
                        if (!hasTimeline) return null;
                        const sectionMsg = [...segment.results].reverse().find((m) => m.section) || null;

                        return (
                            <div key={segment.key} className={segIdx > 0 ? 'mt-4' : ''}>
                                {segment.user ? (
                                    <div className="mb-3 flex justify-end">
                                        <div className="max-w-[85%] whitespace-pre-wrap break-words rounded-2xl rounded-br-sm bg-gray-900 px-3.5 py-2 text-sm leading-relaxed text-white">
                                            {segment.user.content}
                                        </div>
                                    </div>
                                ) : null}
                                <BlockStream
                                    items={items}
                                    loading={isRunning}
                                    statusMessage={statusMessage}
                                    error={isLastSegment ? lastErrorText : ''}
                                    compact={!isLastSegment}
                                />
                                {sectionMsg ? (
                                    <div className="mt-1 flex justify-end">
                                        <button
                                            type="button"
                                            onClick={() => reapplySection(sectionMsg.section)}
                                            className="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-2 py-1 text-[11px] font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                                            Add again
                                        </button>
                                    </div>
                                ) : null}
                            </div>
                        );
                    }) : null}
                </div>
            </div>

            {/* Error (transient, outside timeline) */}
            {error && !loading ? (
                <div className="px-4 pb-2 shrink-0">
                    <div className="rounded-lg bg-red-50 border border-red-100 px-3 py-2 text-xs text-red-700">
                        {error}
                    </div>
                </div>
            ) : null}

            {/* Screenshot preview chip */}
            {!isEmailMode && screenshot ? (
                <div className="px-4 pb-2 shrink-0">
                    <div className="inline-flex items-center gap-1.5 rounded-full bg-gray-100 pl-1 pr-2 py-1">
                        {screenshotPreviewUrl ? (
                            <img src={screenshotPreviewUrl} alt="" className="h-5 w-5 rounded-full object-cover" />
                        ) : (
                            <div className="h-5 w-5 rounded-full bg-gray-300" />
                        )}
                        <span className="text-[11px] text-gray-600 truncate max-w-[120px]">{screenshot.name || 'screenshot'}</span>
                        <button
                            type="button"
                            onClick={() => setScreenshot(null)}
                            className="ml-0.5 text-gray-400 hover:text-red-500"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            ) : null}

            {/* Bottom input area */}
            <div className="px-2 py-2 border-t border-gray-100 shrink-0 space-y-2">
                {/* Prompt textarea with integrated send */}
                <div className="relative">
                    <textarea
                        value={prompt}
                        onChange={(e) => setPrompt(e.target.value)}
                        onPaste={handlePaste}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter' && !e.shiftKey) {
                                e.preventDefault();
                                if (canGenerate && !loading) generate();
                            }
                        }}
                        placeholder={
                            scope === 'full_page'
                                ? "Describe a full page…"
                                : "Describe a section…"
                        }
                        rows={3}
                        className="block w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 pr-16 text-sm text-gray-800 placeholder:text-gray-400 outline-none focus:ring-2 focus:ring-gray-900 focus:bg-white resize-none transition-colors"
                    />
                    {/* Attachment icon */}
                    {!isEmailMode ? <button
                        type="button"
                        onClick={() => document.getElementById('ai-screenshot-input')?.click()}
                        className="absolute bottom-2.5 right-10 h-7 w-7 inline-flex items-center justify-center rounded-lg hover:bg-gray-200 text-gray-400 hover:text-gray-600 transition-colors"
                        title="Attach screenshot"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84L9.41 17.41a2 2 0 0 1-2.83-2.83L15.07 6.1"/></svg>
                    </button> : null}
                    {/* Send / Generate arrow */}
                    <button
                        type="button"
                        onClick={generate}
                        disabled={!canGenerate || loading}
                        className={`absolute bottom-2.5 right-2 h-7 w-7 inline-flex items-center justify-center rounded-lg transition-colors ${
                            canGenerate && !loading
                                ? 'bg-gray-900 text-white hover:bg-gray-800'
                                : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                        }`}
                        title="Generate"
                    >
                        {loading ? (
                            <span className="inline-block h-3 w-3 rounded-full border-2 border-white/30 border-t-white animate-spin" />
                        ) : (
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        )}
                    </button>
                    <input
                        id="ai-screenshot-input"
                        type="file"
                        accept="image/png,image/jpeg,image/jpg,image/gif,image/webp,image/svg+xml"
                        onChange={handleFileChange}
                        className="hidden"
                    />
                </div>

                {/* Bottom row: Scope + Model */}
                {!isEmailMode ? (
                <div className="flex items-center justify-between gap-2">
                    {scopeOptions.length > 1 ? (
                        <select
                            value={scope}
                            onChange={(e) => setScope(e.target.value)}
                            className="rounded-lg bg-gray-100 border-0 px-2.5 py-1 text-[11px] text-gray-700 focus:ring-0 cursor-pointer"
                        >
                            {scopeOptions.map((opt) => (
                                <option key={opt.value} value={opt.value}>{opt.label}</option>
                            ))}
                        </select>
                    ) : (
                        <div />
                    )}
                    <div className="flex items-center gap-1.5">
                        <div ref={modelMenuRef} className="relative">
                            <button
                                type="button"
                                onClick={() => {
                                    setModelMenuOpen((open) => !open);
                                    setModelSearch('');
                                    setOpenRouterView(false);
                                }}
                                className="inline-flex max-w-40 items-center gap-1.5 rounded-lg bg-gray-100 px-2.5 py-1 text-[11px] text-gray-700 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300"
                                title="Search and select model"
                                aria-haspopup="listbox"
                                aria-expanded={modelMenuOpen}
                            >
                                {getModelLogo(model) ? (
                                    <img src={getModelLogo(model)} alt="" className="h-4 w-4 rounded object-contain" onError={(event) => { event.currentTarget.style.display = 'none'; }} />
                                ) : null}
                                <span className="max-w-28 truncate">
                                    {modelOptions.find((option) => option.value === model)?.label || 'Select model'}
                                </span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="m6 9 6 6 6-6" />
                                </svg>
                            </button>

                            {modelMenuOpen ? (
                                <div className="absolute bottom-full right-0 z-50 mb-2 w-64 rounded-xl border border-gray-200 bg-white p-2 shadow-xl">
                                    <div className="relative mb-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400">
                                            <circle cx="11" cy="11" r="8" />
                                            <path d="m21 21-4.3-4.3" />
                                        </svg>
                                        <input
                                            type="search"
                                            value={modelSearch}
                                            onChange={(event) => setModelSearch(event.target.value)}
                                            placeholder="Search models..."
                                            autoFocus
                                            className="w-full rounded-lg border border-gray-200 bg-gray-50 py-1.5 pl-7 pr-2 text-xs text-gray-700 outline-none focus:border-gray-400 focus:bg-white"
                                        />
                                    </div>
                                    <div className="max-h-52 overflow-y-auto space-y-0.5" role="listbox" aria-label="AI models">
                                        {openRouterView ? (
                                            <>
                                                <button
                                                    type="button"
                                                    onClick={() => { setOpenRouterView(false); setModelSearch(''); }}
                                                    className="mb-1 flex w-full items-center gap-1.5 rounded-lg px-2 py-1.5 text-left text-xs font-medium text-gray-500 hover:bg-gray-50"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                                                    Back
                                                </button>
                                                <div className="mb-1 flex items-center gap-1.5 px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                                    <img src="/images/model-logos/openrouter.png" alt="" className="h-4 w-4 rounded object-contain" />
                                                    OpenRouter
                                                </div>
                                                {groupedModelOptions.openRouterOptions.map((option) => (
                                                    <ModelOption
                                                        key={option.value}
                                                        option={option}
                                                        selected={model === option.value}
                                                        logo={getModelLogo(option.value)}
                                                        onSelect={(value) => { setModel(value); setModelMenuOpen(false); setModelSearch(''); setOpenRouterView(false); }}
                                                    />
                                                ))}
                                            </>
                                        ) : filteredModelOptions.length > 0 ? (
                                            <>
                                                {groupedModelOptions.otherOptions.map((option) => (
                                                    <ModelOption
                                                        key={option.value}
                                                        option={option}
                                                        selected={model === option.value}
                                                        logo={getModelLogo(option.value)}
                                                        onSelect={(value) => { setModel(value); setModelMenuOpen(false); setModelSearch(''); }}
                                                    />
                                                ))}
                                                {groupedModelOptions.openRouterOptions.length > 0 ? (
                                                    <button
                                                        type="button"
                                                        onClick={() => setOpenRouterView(true)}
                                                        className="flex w-full items-center gap-1.5 rounded-lg px-2 py-1.5 text-left text-xs font-semibold text-gray-500 hover:bg-gray-50"
                                                    >
                                                        <img src="/images/model-logos/openrouter.png" alt="" className="h-4 w-4 rounded object-contain" />
                                                        OpenRouter
                                                        <svg className="ml-auto h-3.5 w-3.5 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                                    </button>
                                                ) : null}
                                            </>
                                        ) : (
                                            <div className="px-2.5 py-3 text-center text-xs text-gray-400">No models found</div>
                                        )}
                                    </div>
                                </div>
                            ) : null}
                        </div>
                    </div>
                </div>
                ) : null}
            </div>
        </div>
    );
}
