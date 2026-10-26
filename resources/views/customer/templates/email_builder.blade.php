@php
    $emailBuilderData = null;
    if (isset($template) && is_array($template->grapesjs_data ?? null) && ($template->grapesjs_data['builder'] ?? null) === 'mailpurse') {
        $emailBuilderData = $template->grapesjs_data;
    }
    $emailAiUser = auth('customer')->user();
    $emailAiEnabled = (bool) $emailAiUser?->groupAllows('templates.permissions.can_use_ai_builder');
    $emailAiDefaultModel = (string) $emailAiUser?->groupSetting('templates.ai_builder.default_model', '');
    $emailAiEnabledModels = (array) $emailAiUser?->groupSetting('templates.ai_builder.models.enabled', []);
@endphp

@vite(['resources/js/page-builder/main/index.js'])

<div class="flex min-h-0 flex-1 flex-col overflow-hidden bg-slate-100 dark:bg-slate-950">
    <div
        id="page-builder-react"
        class="min-h-0 flex-1"
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
        data-initial-builder="{{ json_encode($emailBuilderData) }}"
    ></div>
</div>

@push('scripts')
<script>
(function () {
    const bindEmailBuilderSave = () => {
        const button = document.getElementById('btn-save');
        const form = document.getElementById('unlayer-form');
        if (!button || !form || button.dataset.emailBuilderBound === '1') return;
        button.dataset.emailBuilderBound = '1';

        button.addEventListener('click', async () => {
            const builderData = window.mailpursePageBuilder?.serialize?.();
            if (!builderData) return;

            const originalText = button.textContent;
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

                const formData = new FormData(form);
                const saveResponse = await fetch(form.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: formData,
                });
                const saveResult = await saveResponse.json().catch(() => ({}));
                if (!saveResponse.ok) throw new Error(saveResult.message || 'Unable to save the email template.');

                if (saveResult.update_url) {
                    form.action = saveResult.update_url;
                    form.method = 'POST';
                    if (!form.querySelector('input[name="_method"]')) {
                        const methodInput = document.createElement('input');
                        methodInput.type = 'hidden';
                        methodInput.name = '_method';
                        methodInput.value = 'PUT';
                        form.appendChild(methodInput);
                    }
                }

                if (saveResult.edit_url && window.history && typeof window.history.replaceState === 'function') {
                    try {
                        window.history.replaceState(window.history.state, '', saveResult.edit_url);
                    } catch (e) {
                    }
                }

                window.mailpursePageBuilderMarkSaved?.();

                if (typeof window.mailpurseToast === 'function') {
                    window.mailpurseToast({ type: 'success', message: saveResult.message || 'Template saved successfully.' });
                }

                button.disabled = false;
                button.textContent = originalText;
            } catch (error) {
                window.alert(error.message || 'Unable to save the email template.');
                button.disabled = false;
                button.textContent = originalText;
            }
        });
    };

    document.addEventListener('DOMContentLoaded', bindEmailBuilderSave, { once: true });
    document.addEventListener('turbo:load', bindEmailBuilderSave);
    window.setTimeout(bindEmailBuilderSave, 250);
})();
</script>
@endpush
