@props([
    'name' => 'page-editor',
])

<x-modal :name="$name" maxWidth="7xl">
    <div class="relative flex h-[92vh] w-full overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-900">
        <div class="flex w-full flex-col">
            <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100" data-page-editor-title>{{ __('Edit Page') }}</p>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Changes are saved inside the editor.') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <a data-page-editor-open-public target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-md border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800" style="display: none;">{{ __('Open') }}</a>
                    <button type="button" class="inline-flex items-center rounded-md border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800" data-page-editor-close>{{ __('Close') }}</button>
                </div>
            </div>
            <iframe data-page-editor-iframe class="h-[calc(92vh-52px)] w-full bg-white dark:bg-gray-900" src="about:blank"></iframe>
        </div>
    </div>
</x-modal>

@once
    @push('scripts')
        <script>
            (function () {
                var currentUrl = null;

                var closeModal = function () {
                    var iframe = document.querySelector('[data-page-editor-iframe]');
                    var titleEl = document.querySelector('[data-page-editor-title]');
                    var openPublicEl = document.querySelector('[data-page-editor-open-public]');

                    window.dispatchEvent(new CustomEvent('close-modal', { detail: @json($name) }));

                    if (iframe) {
                        iframe.setAttribute('src', 'about:blank');
                    }
                    if (titleEl) {
                        titleEl.textContent = @json(__('Edit Page'));
                    }
                    if (openPublicEl) {
                        openPublicEl.style.display = 'none';
                        openPublicEl.removeAttribute('href');
                    }
                    currentUrl = null;
                };

                var openModal = function (url, opts) {
                    opts = opts && typeof opts === 'object' ? opts : {};

                    currentUrl = url;
                    var iframe = document.querySelector('[data-page-editor-iframe]');
                    var titleEl = document.querySelector('[data-page-editor-title]');
                    var openPublicEl = document.querySelector('[data-page-editor-open-public]');

                    if (titleEl && typeof opts.title === 'string' && opts.title.trim()) {
                        titleEl.textContent = opts.title;
                    } else if (titleEl) {
                        titleEl.textContent = @json(__('Edit Page'));
                    }

                    if (openPublicEl && typeof opts.publicUrl === 'string' && opts.publicUrl.trim()) {
                        openPublicEl.style.display = 'inline-flex';
                        openPublicEl.setAttribute('href', opts.publicUrl);
                    } else if (openPublicEl) {
                        openPublicEl.style.display = 'none';
                        openPublicEl.removeAttribute('href');
                    }

                    if (iframe) {
                        iframe.setAttribute('src', url);
                    }

                    window.dispatchEvent(new CustomEvent('open-modal', { detail: @json($name) }));
                };

                window.mailpursePageEditor = window.mailpursePageEditor || {};
                window.mailpursePageEditor.open = openModal;
                window.mailpursePageEditor.close = closeModal;

                document.addEventListener('click', function (e) {
                    var btn = e && e.target && e.target.closest ? e.target.closest('[data-page-editor-open]') : null;
                    if (btn) {
                        var url = btn.getAttribute('data-page-editor-url');
                        if (!url) return;

                        var title = btn.getAttribute('data-page-editor-title') || '';
                        var publicUrl = btn.getAttribute('data-page-editor-public-url') || '';

                        openModal(url, { title: title, publicUrl: publicUrl });
                        return;
                    }

                    var closeBtn = e && e.target && e.target.closest ? e.target.closest('[data-page-editor-close]') : null;
                    if (closeBtn) {
                        closeModal();
                    }
                });

                window.addEventListener('message', function (e) {
                    if (!e || !e.data || typeof e.data !== 'object') {
                        return;
                    }
                    if (e.data.type === 'mailpurse:page-editor-close') {
                        closeModal();
                    }
                });
            })();
        </script>
    @endpush
@endonce
