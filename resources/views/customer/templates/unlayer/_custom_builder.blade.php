@php
    $isEdit = isset($template);
    $requireInitialTemplateDetails = $requireInitialTemplateDetails ?? false;
    $unlayerProjectId = config('services.unlayer.project_id');
    $isEmbeddedTemplateEditor = request()->boolean('embedded');

    $unlayerDesign = null;
    $initialBuilder = isset($initialBuilder) && in_array($initialBuilder, ['unlayer', 'text', 'email'], true)
        ? $initialBuilder
        : 'unlayer';
    if ($isEdit) {
        $data = is_array($template->grapesjs_data) ? $template->grapesjs_data : null;
        if ($data !== null && ($data['builder'] ?? null) === 'unlayer' && is_array($data['unlayer'] ?? null)) {
            $unlayerDesign = $data['unlayer'];
            $initialBuilder = 'unlayer';
        } elseif ($data !== null && ($data['builder'] ?? null) === 'text') {
            $initialBuilder = 'text';
        }
    }

    $initialName = $isEdit ? old('name', $template->name) : old('name', $name ?? '');
    $initialType = $isEdit ? old('type', $template->type) : old('type', $type ?? 'email');
    $initialHtml = $isEdit ? (string) ($template->html_content ?? '') : '';
    $showModal    = $requireInitialTemplateDetails && !trim((string) $initialName);

    $emailAiUser = auth('customer')->user();
    $emailAiEnabled = (bool) $emailAiUser?->groupAllows('templates.permissions.can_use_ai_builder');
    $emailAiDefaultModel = (string) $emailAiUser?->groupSetting('templates.ai_builder.default_model', '');
    $emailAiEnabledModels = (array) $emailAiUser?->groupSetting('templates.ai_builder.models.enabled', []);
@endphp

@if($unlayerDesign !== null)
<script type="application/json" id="unlayer-design-json">@json($unlayerDesign)</script>
@endif

<div
    x-data="{
        showModal: @js($showModal),
        name: @js($initialName),
        type: @js($initialType),
        builder: @js($initialBuilder),
        error: '',
        confirm() {
            if (!this.name.trim()) { this.error = 'Template name is required.'; return; }
            this.error = '';
            const n = document.getElementById('name');
            const t = document.getElementById('type');
            const h = document.getElementById('template-header-title');
            if (n) n.value = this.name;
            if (t) t.value = this.type;
            if (h) h.textContent = this.name;
            this.showModal = false;
            if (this.builder === 'unlayer' && typeof window.__mailpurseSetupUnlayerEditors === 'function') {
                window.__mailpurseSetupUnlayerEditors();
            }
        }
    }"
    class="flex-1 min-h-0 overflow-hidden"
>
    <input type="hidden" id="builder-type" :value="builder" value="{{ $initialBuilder }}">
    {{-- Initial template details modal (create flow only) --}}
    @if($requireInitialTemplateDetails)
    <div x-cloak x-show="showModal" class="fixed inset-0 z-[70] flex items-center justify-center p-4" aria-modal="true" role="dialog">
        <div class="absolute inset-0 bg-slate-950/45"></div>
        <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Create template</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Enter the template name and choose a type to start editing.</p>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Template Name</label>
                    <input type="text" x-model="name" @keydown.enter.prevent="confirm()" class="block w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm" placeholder="My new template">
                    <p x-show="error" x-text="error" class="mt-2 text-xs text-red-600"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Template Type</label>
                    <select x-model="type" class="block w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm">
                        <option value="email">Email</option>
                        <option value="campaign">Campaign</option>
                        <option value="transactional">Transactional</option>
                        <option value="autoresponder">Autoresponder</option>
                        <option value="footer">Footer Template</option>
                        <option value="signature">Signature</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Builder Type</label>
                    <select x-model="builder" class="block w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm">
                        <option value="unlayer">Unlayer Builder</option>
                        <option value="email">Email Page Builder</option>
                        <option value="text">Text Composer</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors" @click="confirm()">
                    Start Editing
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Unlayer editor --}}
    <template x-if="builder === 'unlayer'">
        <div
            id="editor-container"
            data-unlayer-editor
            data-unlayer-display-mode="email"
            @if($unlayerProjectId) data-unlayer-project-id="{{ $unlayerProjectId }}" @endif
            @if($unlayerDesign !== null) data-unlayer-design-script-id="unlayer-design-json" @endif
            class="{{ $isEmbeddedTemplateEditor ? 'relative w-full flex-1 min-h-0' : 'fixed w-[calc(100%-40px)]' }}"
            style="{{ $isEmbeddedTemplateEditor ? 'height:100%;min-height:640px;' : 'height:calc(100vh - 50px); margin-top: 50px' }}"
        >
            @if($isEmbeddedTemplateEditor)
            <div id="embedded-unlayer-status" style="position:absolute;top:12px;left:12px;right:12px;z-index:30;display:none;padding:10px 12px;border-radius:10px;border:1px solid #fca5a5;background:#fef2f2;color:#991b1b;font:500 13px/1.4 ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;"></div>
            @endif
            <div data-unlayer-loading style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;background:#f8fafc;z-index:20;gap:16px;">
                <svg style="width:40px;height:40px;color:#6366f1;animation:spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle style="opacity:.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path style="opacity:.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p style="font-size:14px;font-weight:500;color:#64748b;margin:0;">Loading builder&hellip;</p>
                <style>@keyframes spin{to{transform:rotate(360deg)}}</style>
            </div>
        </div>
    </template>

    {{-- Email Page Builder --}}
    <template x-if="builder === 'email'">
        <div class="flex-1 min-h-0 overflow-hidden">
            <div
                id="page-builder-react"
                class="h-full"
                data-mode="email"
                data-page-title="{{ e($template->name ?? $name ?? 'Email template') }}"
                data-ai-message-url="{{ route('customer.templates.ai.message') }}"
                data-ai-models-url="{{ route('customer.templates.ai.models') }}"
                data-ai-enabled="{{ $emailAiEnabled ? '1' : '0' }}"
                data-ai-default-model="{{ e($emailAiDefaultModel) }}"
                data-ai-enabled-models="{{ json_encode(array_values($emailAiEnabledModels)) }}"
                data-templates-list-url="{{ url('customer/templates/gallery') }}"
                data-templates-fetch-url="{{ url('customer/templates/gallery-fetch') }}"
                data-templates-product-id="7"
                data-settings-url="{{ route('customer.templates.index') }}"
                data-test-email-url="{{ route('customer.templates.test-builder-email') }}"
                data-render-html-url="{{ route('customer.templates.render-builder') }}"
                data-delivery-servers="{{ json_encode(($deliveryServers ?? collect())->map(fn ($server) => ['id' => $server->id, 'name' => $server->name])->values()) }}"
                data-template-name="{{ e($template->name ?? $name ?? 'Email template') }}"
                data-upload-url="{{ route('customer.uploads.inline-image') }}"
                data-builder-input-id="grapesjs_data"
                data-initial-builder="{{ json_encode($isEdit ? ($template->grapesjs_data ?? null) : null) }}"
            ></div>
            @vite(['resources/js/page-builder/main/index.js'])
            @push('scripts')
            <script>
            (function () {
                const bindEmailBuilderSave = () => {
                    const button = document.getElementById('btn-save');
                    const form = document.getElementById('unlayer-form');
                    if (!button || !form || button.dataset.emailBuilderBound2 === '1') return;
                    button.dataset.emailBuilderBound2 = '1';
                    button.addEventListener('click', async () => {
                        const builderData = window.mailpursePageBuilder?.serialize?.();
                        if (!builderData) return;
                        button.disabled = true;
                        button.textContent = 'Rendering…';
                        try {
                            const response = await fetch('{{ route('customer.templates.render-builder') }}', {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                                body: JSON.stringify({ builder_data: builderData }),
                            });
                            const result = await response.json();
                            if (!response.ok) throw new Error(result.message || 'Unable to render the email.');
                            document.getElementById('html_content').value = result.html_content || '';
                            document.getElementById('plain_text_content').value = result.plain_text_content || '';
                            document.getElementById('grapesjs_data').value = JSON.stringify(builderData);
                            form.submit();
                        } catch (error) {
                            window.alert(error.message || 'Unable to save the email template.');
                            button.disabled = false;
                            button.textContent = 'Save Template';
                        }
                    });
                };
                document.addEventListener('DOMContentLoaded', bindEmailBuilderSave, { once: true });
                document.addEventListener('turbo:load', bindEmailBuilderSave);
                window.setTimeout(bindEmailBuilderSave, 250);
            })();
            </script>
            @endpush
        </div>
    </template>

    {{-- Text composer (PlateJS) --}}
    <template x-if="builder === 'text'">
        <div class="{{ $isEmbeddedTemplateEditor ? 'relative w-full flex-1 min-h-0 overflow-auto' : 'fixed w-[calc(100%-40px)] overflow-auto' }} bg-slate-50 dark:bg-slate-900"
             style="{{ $isEmbeddedTemplateEditor ? 'height:100%;min-height:640px;' : 'height:calc(100vh - 50px); margin-top: 50px;' }}">
            <div class="mx-auto max-w-3xl">
                <div class="border-slate-200 dark:border-slate-700 dark:bg-slate-900 shadow-sm">
                    
                    <div class="p-5">
                        <div
                            data-outreach-rich-editor
                            data-upload-url="{{ route('customer.uploads.inline-image') }}"
                            data-template-editor="1"
                            data-step-id="template"
                            data-variant="a"
                            data-field="body"
                            data-value="{{ $initialBuilder === 'text' ? $initialHtml : '' }}"
                            data-placeholder="Write your email body..."
                        ></div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

@once
    @push('scripts')
    @vite('resources/js/outreach-sequence-editor.jsx')
    <script>
    (function () {
        const bindSave = () => {
            const btn  = document.getElementById('btn-save');
            const form = document.getElementById('unlayer-form');
            const statusEl = document.getElementById('embedded-unlayer-status');
            const isEmbeddedEditor = @js($isEmbeddedTemplateEditor);
            if (!btn || !form) return;
            if (btn.dataset.unlayerEmbeddedBound === '1') return;
            btn.dataset.unlayerEmbeddedBound = '1';
            const showEmbeddedStatus = (message) => {
                if (!isEmbeddedEditor || !statusEl) {
                    return;
                }
                statusEl.textContent = String(message || 'Builder failed to load.');
                statusEl.style.display = 'block';
            };
            const hideEmbeddedStatus = () => {
                if (!isEmbeddedEditor || !statusEl) {
                    return;
                }
                statusEl.textContent = '';
                statusEl.style.display = 'none';
            };
            const notifyParent = (type, payload = {}) => {
                if (!isEmbeddedEditor || window.parent === window) {
                    return;
                }
                try {
                    const message = {
                        source: 'mailpurse-template-editor',
                        type,
                        ...payload,
                    };
                    window.parent.postMessage(message, window.location.origin);
                    if (window.top && window.top !== window.parent) {
                        window.top.postMessage(message, window.location.origin);
                    }
                } catch (e) {
                }
            };
            const escapeHtml = (value) => String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
            const tokenIcon = (label) => {
                const value = String(label || '').toLowerCase();
                if (value.includes('email')) {
                    return '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>';
                }
                if (value.includes('company')) {
                    return '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path></svg>';
                }
                if (value.includes('unsubscribe')) {
                    return '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 17H7A5 5 0 0 1 7 7h2"></path><path d="M15 7h2a5 5 0 1 1 0 10h-2"></path><line x1="8" x2="16" y1="12" y2="12"></line></svg>';
                }
                return '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="5"></circle><path d="M20 21a8 8 0 0 0-16 0"></path></svg>';
            };
            const embeddedVariableState = {
                selectedToken: '',
            };
            const getEmbeddedVariableGroups = () => {
                const jsonEl = document.getElementById('embedded-campaign-tags-json');
                let config = null;
                try {
                    config = jsonEl ? JSON.parse((jsonEl.textContent || '').trim() || 'null') : null;
                } catch (e) {
                    config = null;
                }
                if (!config || typeof config !== 'object') {
                    return [];
                }
                const standard = Array.isArray(config.standard) ? config.standard : [];
                const custom = Array.isArray(config.custom) ? config.custom : [];
                return [
                    {
                        label: 'LEAD',
                        items: [...standard, ...custom].map((tag) => ({
                            name: String(tag.label || tag.tag || 'Variable'),
                            token: String(tag.tag || ''),
                            icon: tokenIcon(tag.label || tag.tag || ''),
                        })).filter((item) => item.token),
                    },
                ].filter((group) => group.items.length > 0);
            };
            const openVariablePickerBtn = document.getElementById('btn-open-embedded-variable-picker');
            const variableModal = document.getElementById('embedded-variable-modal');
            const variableSearchInput = document.getElementById('embedded-variable-search');
            const variableGroupsContainer = document.getElementById('embedded-variable-groups');
            const applyVariableBtn = document.getElementById('apply-embedded-variable-btn');
            const closeVariableModal = () => {
                if (!variableModal) {
                    return;
                }
                variableModal.classList.add('hidden');
                variableModal.classList.remove('flex');
                embeddedVariableState.selectedToken = '';
                if (variableSearchInput) {
                    variableSearchInput.value = '';
                }
                if (applyVariableBtn) {
                    applyVariableBtn.disabled = true;
                }
            };
            const renderVariableGroups = () => {
                if (!variableGroupsContainer) {
                    return;
                }
                const search = String(variableSearchInput?.value || '').trim().toLowerCase();
                const groups = getEmbeddedVariableGroups().map((group) => ({
                    ...group,
                    items: group.items.filter((item) => {
                        if (!search) {
                            return true;
                        }
                        return item.name.toLowerCase().includes(search) || item.token.toLowerCase().includes(search);
                    }),
                })).filter((group) => group.items.length > 0);
                if (!groups.length) {
                    variableGroupsContainer.innerHTML = '<div class="rounded-xl border border-dashed border-gray-200 dark:border-gray-700 px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">No variables matched your search.</div>';
                    return;
                }
                variableGroupsContainer.innerHTML = groups.map((group) => `
                    <div>
                        <h4 class="mb-3 text-[11px] font-semibold tracking-[0.16em] text-slate-400 uppercase">${escapeHtml(group.label)}</h4>
                        <div class="space-y-2">
                            ${group.items.map((item) => `
                                <button type="button" data-embedded-variable-token="${escapeHtml(item.token)}" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-left rounded-xl border transition-colors ${embeddedVariableState.selectedToken === item.token ? 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20' : 'border-transparent hover:border-gray-200 hover:bg-slate-50 dark:hover:border-gray-700 dark:hover:bg-white/5'}">
                                    <div class="flex items-center gap-4 min-w-0">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 border border-slate-200 dark:border-gray-700 dark:text-gray-400">${item.icon}</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">${escapeHtml(item.name)}</span>
                                    </div>
                                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">${escapeHtml(item.token)}</span>
                                </button>
                            `).join('')}
                        </div>
                    </div>
                `).join('');
            };
            const openVariableModal = () => {
                if (!variableModal) {
                    return;
                }
                hideEmbeddedStatus();
                embeddedVariableState.selectedToken = '';
                if (applyVariableBtn) {
                    applyVariableBtn.disabled = true;
                }
                renderVariableGroups();
                variableModal.classList.remove('hidden');
                variableModal.classList.add('flex');
                window.setTimeout(() => {
                    if (variableSearchInput) {
                        variableSearchInput.focus();
                    }
                }, 0);
            };
            const insertTokenIntoActiveEditable = async (token) => {
                const text = String(token || '');
                if (!text) {
                    return false;
                }
                const activeEl = document.activeElement;
                try {
                    if (activeEl && (activeEl.tagName === 'TEXTAREA' || (activeEl.tagName === 'INPUT' && /^(text|search|email|url|tel)$/i.test(activeEl.type || 'text')))) {
                        const start = typeof activeEl.selectionStart === 'number' ? activeEl.selectionStart : activeEl.value.length;
                        const end = typeof activeEl.selectionEnd === 'number' ? activeEl.selectionEnd : activeEl.value.length;
                        activeEl.setRangeText(text, start, end, 'end');
                        activeEl.dispatchEvent(new Event('input', { bubbles: true }));
                        return true;
                    }
                    if (activeEl && activeEl.isContentEditable) {
                        activeEl.focus();
                        const selection = window.getSelection();
                        if (selection && selection.rangeCount > 0) {
                            selection.deleteFromDocument();
                            selection.getRangeAt(0).insertNode(document.createTextNode(text));
                            selection.collapseToEnd();
                            activeEl.dispatchEvent(new Event('input', { bubbles: true }));
                            return true;
                        }
                    }
                    if (document.queryCommandSupported && document.queryCommandSupported('insertText')) {
                        const inserted = document.execCommand('insertText', false, text);
                        if (inserted) {
                            return true;
                        }
                    }
                } catch (e) {
                }
                try {
                    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                        await navigator.clipboard.writeText(text);
                        showEmbeddedStatus('Variable copied to clipboard. Place the cursor in a text block and paste it.');
                        return true;
                    }
                } catch (e) {
                }
                showEmbeddedStatus('Select a text block in the builder before inserting a variable.');
                return false;
            };

            btn.dataset.unlayerEmbeddedHandled = isEmbeddedEditor ? '1' : '0';
            btn.addEventListener('click', () => {
                const builderTypeEl = document.getElementById('builder-type');
                const currentBuilder = builderTypeEl ? (builderTypeEl.value || 'unlayer') : 'unlayer';

                if (currentBuilder === 'text') {
                    const plateEl = document.querySelector('[data-outreach-rich-editor][data-template-editor="1"]');
                    const html = plateEl ? String(plateEl.dataset.value || '') : '';
                    const htmlEl  = document.getElementById('html_content');
                    const plainEl = document.getElementById('plain_text_content');
                    const dataEl  = document.getElementById('grapesjs_data');
                    if (htmlEl)  htmlEl.value  = html;
                    if (plainEl) plainEl.value = html.replace(/<style[\s\S]*?<\/style>/gi, ' ').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
                    if (dataEl)  dataEl.value  = JSON.stringify({ builder: 'text' });

                    if (isEmbeddedEditor) {
                        btn.disabled = true;
                        const formData = new FormData(form);
                        const url = new URL(form.action, window.location.origin);
                        url.searchParams.set('embedded', '1');
                        fetch(url.toString(), {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: formData,
                            credentials: 'same-origin',
                        })
                            .then(async (response) => {
                                const payload = await response.json().catch(() => ({}));
                                if (!response.ok) {
                                    const message = payload && payload.message ? String(payload.message) : 'Failed to save template.';
                                    throw new Error(message);
                                }
                                notifyParent('saved', {
                                    templateId: formData.get('template_id') || null,
                                    templateName: formData.get('name') || '',
                                    message: payload && payload.message ? String(payload.message) : 'Template updated successfully.',
                                });
                                btn.disabled = false;
                            })
                            .catch((error) => {
                                notifyParent('save-error', {
                                    message: error && error.message ? String(error.message) : 'Failed to save template.',
                                });
                                btn.disabled = false;
                            });
                        return;
                    }

                    form.submit();
                    return;
                }

                if (!window.unlayer || typeof window.unlayer.exportHtml !== 'function') {
                    showEmbeddedStatus('Builder is still loading. Please wait a moment and try again.');
                    if (isEmbeddedEditor) {
                        notifyParent('save-error', {
                            message: 'Builder is still loading. Please wait a moment and try again.',
                        });
                        return;
                    }
                    form.submit();
                    return;
                }
                btn.disabled = true;
                window.unlayer.exportHtml((data) => {
                    const html   = (data && data.html)   ? String(data.html)   : '';
                    const design = (data && data.design) ? data.design         : null;
                    const htmlEl  = document.getElementById('html_content');
                    const plainEl = document.getElementById('plain_text_content');
                    const dataEl  = document.getElementById('grapesjs_data');
                    if (htmlEl)  htmlEl.value  = html;
                    if (plainEl) plainEl.value = html.replace(/<style[\s\S]*?<\/style>/gi, ' ').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
                    if (dataEl && design) dataEl.value = JSON.stringify({ builder: 'unlayer', unlayer: design });
                    if (isEmbeddedEditor) {
                        const formData = new FormData(form);
                        const url = new URL(form.action, window.location.origin);
                        url.searchParams.set('embedded', '1');

                        fetch(url.toString(), {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: formData,
                            credentials: 'same-origin',
                        })
                            .then(async (response) => {
                                const payload = await response.json().catch(() => ({}));
                                if (!response.ok) {
                                    const message = payload && payload.message ? String(payload.message) : 'Failed to save template.';
                                    throw new Error(message);
                                }
                                notifyParent('saved', {
                                    templateId: formData.get('template_id') || null,
                                    templateName: formData.get('name') || '',
                                    message: payload && payload.message ? String(payload.message) : 'Template updated successfully.',
                                });
                                btn.disabled = false;
                            })
                            .catch((error) => {
                                notifyParent('save-error', {
                                    message: error && error.message ? String(error.message) : 'Failed to save template.',
                                });
                                btn.disabled = false;
                            });
                        return;
                    }
                    form.submit();
                });
            });

            if (isEmbeddedEditor) {
                if (openVariablePickerBtn && openVariablePickerBtn.dataset.unlayerEmbeddedVariableBound !== '1') {
                    openVariablePickerBtn.dataset.unlayerEmbeddedVariableBound = '1';
                    openVariablePickerBtn.addEventListener('click', () => {
                        if (!getEmbeddedVariableGroups().length) {
                            showEmbeddedStatus('No variables are available for the currently selected campaign list.');
                            return;
                        }
                        openVariableModal();
                    });
                }

                const closeBtn = document.getElementById('btn-close-embedded-template-editor');
                if (closeBtn && closeBtn.dataset.unlayerEmbeddedCloseBound !== '1') {
                    closeBtn.dataset.unlayerEmbeddedCloseBound = '1';
                    closeBtn.dataset.unlayerEmbeddedHandled = '1';
                    closeBtn.addEventListener('click', (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        notifyParent('close');
                    });
                }

                if (variableModal && variableModal.dataset.unlayerEmbeddedVariableModalBound !== '1') {
                    variableModal.dataset.unlayerEmbeddedVariableModalBound = '1';
                    variableModal.addEventListener('click', (event) => {
                        const closeTrigger = event.target && event.target.closest ? event.target.closest('[data-close-embedded-variable-modal]') : null;
                        if (closeTrigger) {
                            closeVariableModal();
                            return;
                        }
                        const tokenButton = event.target && event.target.closest ? event.target.closest('[data-embedded-variable-token]') : null;
                        if (!tokenButton) {
                            return;
                        }
                        embeddedVariableState.selectedToken = tokenButton.getAttribute('data-embedded-variable-token') || '';
                        if (applyVariableBtn) {
                            applyVariableBtn.disabled = !embeddedVariableState.selectedToken;
                        }
                        renderVariableGroups();
                    });
                }

                if (variableSearchInput && variableSearchInput.dataset.unlayerEmbeddedVariableSearchBound !== '1') {
                    variableSearchInput.dataset.unlayerEmbeddedVariableSearchBound = '1';
                    variableSearchInput.addEventListener('input', renderVariableGroups);
                }

                if (applyVariableBtn && applyVariableBtn.dataset.unlayerEmbeddedVariableApplyBound !== '1') {
                    applyVariableBtn.dataset.unlayerEmbeddedVariableApplyBound = '1';
                    applyVariableBtn.addEventListener('click', async () => {
                        if (!embeddedVariableState.selectedToken) {
                            return;
                        }
                        const didInsert = await insertTokenIntoActiveEditable(embeddedVariableState.selectedToken);
                        if (didInsert) {
                            closeVariableModal();
                        }
                    });
                }

                window.setTimeout(() => {
                    const loadingEl = document.querySelector('[data-unlayer-loading]');
                    if (loadingEl && window.__mailpurseUnlayerReady !== true) {
                        showEmbeddedStatus('The builder did not finish loading inside the popup. Please reload the popup and try again.');
                    }
                }, 8000);
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bindSave);
        } else {
            bindSave();
        }

        if (typeof window.__mailpurseSetupUnlayerEditors === 'function') {
            window.__mailpurseSetupUnlayerEditors();
        }
    })();
    </script>
    @endpush
@endonce
