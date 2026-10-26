@extends('layouts.customer')

@section('title', 'Subscription Form: ' . $form->name)
@section('page-title', '')

@section('breadcrumbs')
<nav aria-label="Breadcrumb">
    <ol class="flex flex-wrap items-center gap-1.5 text-sm text-admin-text-secondary">
        <li><a href="{{ route('customer.dashboard') }}" class="font-medium transition hover:text-admin-text-primary">{{ __('Home') }}</a></li>
        <li aria-hidden="true" class="text-admin-text-secondary/50">/</li>
        <li><a href="{{ route('customer.lists.index') }}" class="font-medium transition hover:text-admin-text-primary">{{ __('Email Lists') }}</a></li>
        <li aria-hidden="true" class="text-admin-text-secondary/50">/</li>
        <li><a href="{{ route('customer.lists.show', $list) }}" class="font-medium transition hover:text-admin-text-primary">{{ $list->display_name ?? $list->name }}</a></li>
        <li aria-hidden="true" class="text-admin-text-secondary/50">/</li>
        <li><a href="{{ route('customer.lists.forms.index', $list) }}" class="font-medium transition hover:text-admin-text-primary">{{ __('Forms') }}</a></li>
        <li aria-hidden="true" class="text-admin-text-secondary/50">/</li>
        <li class="font-medium text-admin-text-primary truncate">{{ $form->name }}</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div x-data="{ editingName: false, nameValue: '{{ addslashes($form->name) }}' }">
            <h2 x-show="!editingName" class="text-2xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                {{ $form->name }}
                <button type="button" @click="editingName = true" class="inline-flex items-center justify-center h-6 w-6 rounded text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition-colors" title="{{ __('Edit name') }}" aria-label="{{ __('Edit name') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                </button>
            </h2>
            <form x-show="editingName" x-cloak method="POST" action="{{ route('customer.lists.forms.update', [$list, $form]) }}" class="flex items-center gap-2">
                @csrf
                @method('PUT')
                <input type="text" name="name" x-model="nameValue" class="block w-full max-w-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-3 py-1.5" required>
                <input type="hidden" name="type" value="{{ $form->type }}">
                <button type="submit" class="inline-flex items-center justify-center h-8 w-8 rounded-md bg-primary-600 text-white hover:bg-primary-700" title="{{ __('Save') }}" aria-label="{{ __('Save') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M20 6 9 17l-5-5"/></svg>
                </button>
                <button type="button" @click="editingName = false; nameValue = '{{ addslashes($form->name) }}'" class="inline-flex items-center justify-center h-8 w-8 rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600" title="{{ __('Cancel') }}" aria-label="{{ __('Cancel') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </form>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('public.subscribe', $form->slug) }}" target="_blank" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                Preview
            </a>
            <a href="{{ route('customer.lists.forms.builder.edit', [$list, $form]) }}" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md shadow-sm hover:bg-primary-700">
                Design Form
            </a>
        </div>
    </div>

    <!-- Form Details -->
    <x-card title="Form Information">
        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Type</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ ucfirst($form->type) }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Public Title</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $form->title ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                <dd class="mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $form->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}
                    ">
                        {{ $form->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Submissions</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ number_format($form->submissions_count) }}</dd>
            </div>
            <div x-data="{ editingSlug: false, slugValue: '{{ addslashes($form->slug) }}' }">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Form Slug</dt>
                <dd x-show="!editingSlug" class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-mono flex items-center gap-2">
                    {{ $form->slug }}
                    <button type="button" @click="editingSlug = true" class="inline-flex items-center justify-center h-5 w-5 rounded text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition-colors" title="{{ __('Edit slug') }}" aria-label="{{ __('Edit slug') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                    </button>
                </dd>
                <form x-show="editingSlug" x-cloak method="POST" action="{{ route('customer.lists.forms.update', [$list, $form]) }}" class="mt-1 flex items-center gap-2">
                    @csrf
                    @method('PUT')
                    <input type="text" name="slug" x-model="slugValue" class="block w-full max-w-xs rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-3 py-1.5 font-mono" required>
                    <input type="hidden" name="name" value="{{ $form->name }}">
                    <input type="hidden" name="type" value="{{ $form->type }}">
                    <button type="submit" class="inline-flex items-center justify-center h-7 w-7 rounded-md bg-primary-600 text-white hover:bg-primary-700" title="{{ __('Save') }}" aria-label="{{ __('Save') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5"><path d="M20 6 9 17l-5-5"/></svg>
                    </button>
                    <button type="button" @click="editingSlug = false; slugValue = '{{ addslashes($form->slug) }}'" class="inline-flex items-center justify-center h-7 w-7 rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600" title="{{ __('Cancel') }}" aria-label="{{ __('Cancel') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </form>
            </div>
        </dl>
    </x-card>

    <!-- Embed Code -->
    <x-card title="Embed Code">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Copy and paste this code into your website to embed the subscription form:
            </p>
            <div class="bg-gray-900 rounded-lg p-4 relative overflow-x-auto">
                <button
                    type="button"
                    class="absolute top-3 right-3 inline-flex items-center justify-center h-8 w-8 rounded-md bg-gray-800/60 hover:bg-gray-800 text-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 focus:ring-blue-500"
                    data-copy-target="form-embed-code"
                    aria-label="Copy"
                    title="Copy"
                >
                    <svg class="copy-icon h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                    </svg>
                    <svg class="check-icon h-4 w-4 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 6 9 17l-5-5"/>
                    </svg>
                </button>
                <code id="form-embed-code" class="text-sm text-green-400">
                    &lt;iframe src="{{ url('/subscribe/' . $form->slug) }}" width="100%" height="400" frameborder="0"&gt;&lt;/iframe&gt;
                </code>
            </div>
        </x-card>

    <!-- Popup Code -->
    <x-card title="Popup Code">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Copy and paste this code into your website. The form will automatically open after the configured delay.
            </p>
            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto relative">
                <button
                    type="button"
                    class="absolute top-3 right-3 inline-flex items-center justify-center h-8 w-8 rounded-md bg-gray-800/60 hover:bg-gray-800 text-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 focus:ring-blue-500"
                    data-copy-target="form-popup-code"
                    aria-label="Copy"
                    title="Copy"
                >
                    <svg class="copy-icon h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                    </svg>
                    <svg class="check-icon h-4 w-4 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 6 9 17l-5-5"/>
                    </svg>
                </button>
                <code id="form-popup-code" class="text-sm text-green-400 whitespace-pre">
&lt;script src=&quot;{{ route('public.subscribe.popup', $form->slug) }}&quot;&gt;&lt;/script&gt;
                </code>
            </div>
        </x-card>

    <!-- API Endpoint -->
    <x-card title="API Endpoint">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Use this endpoint to subscribe users via API:
            </p>
            <div class="bg-gray-900 rounded-lg p-4 relative overflow-x-auto">
                <button
                    type="button"
                    class="absolute top-3 right-3 inline-flex items-center justify-center h-8 w-8 rounded-md bg-gray-800/60 hover:bg-gray-800 text-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 focus:ring-blue-500"
                    data-copy-target="form-api-code"
                    aria-label="Copy"
                    title="Copy"
                >
                    <svg class="copy-icon h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                    </svg>
                    <svg class="check-icon h-4 w-4 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 6 9 17l-5-5"/>
                    </svg>
                </button>
                <code id="form-api-code" class="text-sm text-green-400">
                    POST {{ url('/subscribe/' . $form->slug . '/api') }}<br>
                    Content-Type: application/json<br><br>
                    {<br>
                    &nbsp;&nbsp;"email": "user@example.com",<br>
                    &nbsp;&nbsp;"first_name": "John",<br>
                    &nbsp;&nbsp;"last_name": "Doe"<br>
                    }
                </code>
            </div>
        </x-card>

    @if($form->description)
        <x-card title="Description">
            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $form->description }}</p>
        </x-card>
    @endif
</div>

@push('scripts')
    <script>
        (function () {
            function fallbackCopyText(text) {
                var textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', '');
                textarea.style.position = 'fixed';
                textarea.style.top = '-9999px';
                textarea.style.left = '-9999px';
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');
                } finally {
                    textarea.remove();
                }
            }

            async function copyText(text) {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(text);
                    return;
                }
                fallbackCopyText(text);
            }

            document.addEventListener('click', async function (e) {
                var btn = e.target && e.target.closest ? e.target.closest('[data-copy-target]') : null;
                if (!btn) return;

                var targetId = btn.getAttribute('data-copy-target');
                var el = document.getElementById(targetId);
                if (!el) return;

                var text = (el.innerText || el.textContent || '').trim();
                if (!text) return;

                var originalTitle = btn.getAttribute('title') || '';
                var copyIcon = btn.querySelector('.copy-icon');
                var checkIcon = btn.querySelector('.check-icon');
                try {
                    btn.disabled = true;
                    await copyText(text);
                    btn.setAttribute('title', 'Copied!');
                    btn.setAttribute('aria-label', 'Copied');
                    if (copyIcon) copyIcon.classList.add('hidden');
                    if (checkIcon) checkIcon.classList.remove('hidden');
                    setTimeout(function () {
                        btn.disabled = false;
                        btn.setAttribute('title', originalTitle || 'Copy');
                        btn.setAttribute('aria-label', 'Copy');
                        if (copyIcon) copyIcon.classList.remove('hidden');
                        if (checkIcon) checkIcon.classList.add('hidden');
                    }, 1200);
                } catch (err) {
                    btn.disabled = false;
                }
            });
        })();
    </script>
@endpush
@endsection

