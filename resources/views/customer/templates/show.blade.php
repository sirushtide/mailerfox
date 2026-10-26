@extends('layouts.customer')

@section('title', $template->name)
@section('page-title', $template->name)
@section('page-title-meta')
    @if(is_array($template->settings) && (($template->settings['origin'] ?? null) === 'ai'))
        <span class="inline-flex items-center rounded-full bg-primary-100 px-2 py-0.5 text-[10px] font-semibold text-primary-800 dark:bg-primary-900 dark:text-primary-200">AI</span>
    @endif
@endsection

@section('breadcrumbs')
    <nav aria-label="Breadcrumb" class="mb-0">
        <ol class="flex flex-wrap items-center gap-1.5 text-sm text-admin-text-secondary">
            <li>
                <a href="{{ route('customer.dashboard') }}" class="font-medium transition hover:text-admin-text-primary">
                    Dashboard
                </a>
            </li>
            <li aria-hidden="true" class="text-admin-text-secondary/60">/</li>
            <li>
                <a href="{{ route('customer.templates.index') }}" class="font-medium transition hover:text-admin-text-primary">
                    Templates
                </a>
            </li>
            <li aria-hidden="true" class="text-admin-text-secondary/60">/</li>
            <li class="font-medium text-admin-text-primary">{{ $template->name }}</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <div class="flex items-center gap-3">
        <a href="{{ route('customer.templates.preview', $template) }}" target="_blank" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
            Preview
        </a>
        <button type="button" @click="testOpen = true" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
            Test
        </button>
        @if($template->customer_id === auth('customer')->id())
            @customercan('templates.permissions.can_edit_templates')
                <a href="{{ route('customer.templates.edit', $template) }}" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md shadow-sm hover:bg-primary-700">
                    Edit
                </a>
            @endcustomercan
            @customercan('templates.permissions.can_create_templates')
                <form method="POST" action="{{ route('customer.templates.duplicate', $template) }}" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                        Duplicate
                    </button>
                </form>
            @endcustomercan
        @endif
    </div>
@endsection

@section('content')
<div x-data="{ testOpen: {{ ($errors->has('delivery_server_id') || $errors->has('to_email')) ? 'true' : 'false' }} }" class="max-w-4xl mx-auto">
    @if (session('success'))
        <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900/50 dark:bg-green-900/20 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    <div x-cloak x-show="testOpen" class="fixed inset-0 z-50 flex items-center justify-center" aria-modal="true" role="dialog">
        <div class="fixed inset-0 bg-black/50" @click="testOpen = false"></div>
        <div class="relative w-full max-w-3xl mx-4 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Send Test Email</h3>
                <button type="button" @click="testOpen = false" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <span class="sr-only">Close</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('customer.templates.test-email', $template) }}" class="px-6 py-4 space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Delivery Server</label>
                        <select name="delivery_server_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            <option value="">Select a delivery server...</option>
                            @foreach(($deliveryServers ?? []) as $server)
                                <option value="{{ $server->id }}" {{ old('delivery_server_id') == $server->id ? 'selected' : '' }}>
                                    {{ $server->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('delivery_server_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">To Email</label>
                        <input type="email" name="to_email" value="{{ old('to_email') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm" placeholder="test@example.com" />
                        @error('to_email')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Preview</label>
                    <div class="mt-1 border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-white">
                        <iframe
                            srcdoc="{!! htmlspecialchars($template->html_content ?? '<p>No content</p>', ENT_QUOTES, 'UTF-8') !!}"
                            class="w-full h-[450px] border-0"
                            style="min-height: 450px;"
                            loading="lazy"
                            sandbox
                        ></iframe>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="testOpen = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md shadow-sm hover:bg-primary-700">
                        Send
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── TAB NAV ── --}}
    @php
        $activeTab = request()->query('tab', 'details');
        if (!in_array($activeTab, ['details', 'preview', 'settings'], true)) {
            $activeTab = 'details';
        }
        $tabs = [
            'details' => [
                'label' => 'Details',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 9V4.5a.5.5 0 00-.5-.5h-13a.5.5 0 00-.5.5V19a.5.5 0 00.5.5H12"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 9h8M8 13h5M8 17h3"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 19l1.293-1.293a2.121 2.121 0 113 3L19 22l-3 1 1-3z"/>',
            ],
            'preview' => [
                'label' => 'Preview',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.062 12.348a1 1 0 010-.696 10.75 10.75 0 0119.876 0 1 1 0 010 .696 10.75 10.75 0 01-19.876 0"/><circle stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" cx="12" cy="12" r="3"/>',
            ],
            'settings' => [
                'label' => 'Settings',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" cx="12" cy="12" r="3"/>',
            ],
        ];
    @endphp
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
        <nav class="flex overflow-x-auto" aria-label="Template tabs">
            @foreach($tabs as $slug => $tab)
                @php $isActive = $activeTab === $slug; @endphp
                <a href="{{ route('customer.templates.show', array_merge([$template], $slug !== 'details' ? ['tab' => $slug] : [])) }}"
                   class="group inline-flex items-center gap-2 whitespace-nowrap px-0 py-3 mr-6 -mb-px text-sm font-medium rounded-none bg-transparent no-underline border-b-2 transition-all duration-150
                       {{ $isActive
                           ? '!border-primary-600 text-primary-600 visited:text-primary-600 dark:text-primary-400 dark:!border-primary-400'
                           : '!border-transparent text-gray-500 visited:text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:!border-gray-300 dark:hover:!border-gray-500' }}">
                    <svg class="w-4 h-4 shrink-0 transition-colors
                             {{ $isActive ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300' }}"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        {!! $tab['icon'] !!}
                    </svg>
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </nav>
    </div>

    {{-- ═══════════════════════════════════ DETAILS TAB ═══════════════════════════════════ --}}
    @if($activeTab === 'details')
    <!-- Template Details -->
    <x-card title="Template Information">
        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $template->name }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Type</dt>
                <dd class="mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                        {{ ucfirst($template->type) }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Usage Count</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $template->usage_count }} times</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $template->created_at->format('M d, Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $template->updated_at->format('M d, Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Visibility</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    @if($template->is_public)
                        <span class="text-green-600">Public</span>
                    @else
                        <span class="text-gray-600">Private</span>
                    @endif
                </dd>
            </div>
        </dl>
    </x-card>
    @endif

    {{-- ═══════════════════════════════════ PREVIEW TAB ═══════════════════════════════════ --}}
    @if($activeTab === 'preview')
    <!-- Template Preview -->
    <x-card title="Template Preview">
        @php
            $rawBuilderData = is_array($template->grapesjs_data) ? $template->grapesjs_data : null;
            $jsonPayload = $rawBuilderData;
            if (is_array($rawBuilderData) && ($rawBuilderData['builder'] ?? null) === 'unlayer' && is_array($rawBuilderData['unlayer'] ?? null)) {
                $jsonPayload = $rawBuilderData['unlayer'];
            }
            $jsonPretty = $jsonPayload ? json_encode($jsonPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '';
        @endphp

        <div x-data="{ tab: 'design' }" class="space-y-3">
            <div class="flex items-center gap-6 border-b border-gray-200 dark:border-gray-700">
                <button type="button" @click="tab = 'design'" class="py-3 px-0 text-sm font-medium rounded-none -mb-px whitespace-nowrap transition-all duration-150" :class="tab === 'design' ? 'text-primary-600 border-b-2 !border-primary-600' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'">
                    Design
                </button>
                <button type="button" @click="tab = 'json'" class="py-3 px-0 text-sm font-medium rounded-none -mb-px whitespace-nowrap transition-all duration-150" :class="tab === 'json' ? 'text-primary-600 border-b-2 !border-primary-600' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'">
                    JSON
                </button>
                <button type="button" @click="tab = 'html'" class="py-3 px-0 text-sm font-medium rounded-none -mb-px whitespace-nowrap transition-all duration-150" :class="tab === 'html' ? 'text-primary-600 border-b-2 !border-primary-600' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'">
                    HTML
                </button>
                <button type="button" @click="tab = 'plain'" class="py-3 px-0 text-sm font-medium rounded-none -mb-px whitespace-nowrap transition-all duration-150" :class="tab === 'plain' ? 'text-primary-600 border-b-2 !border-primary-600' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'">
                    Plain Text
                </button>
            </div>

            <div x-show="tab === 'design'" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-white">
                <iframe
                    srcdoc="{!! htmlspecialchars($template->html_content ?? '<p>No content</p>', ENT_QUOTES, 'UTF-8') !!}"
                    class="w-[800px] h-[600px] border-0"
                    style="min-height: 600px;margin:0 auto"
                    loading="lazy"
                    sandbox
                ></iframe>
            </div>

            <div x-show="tab === 'json'" class="border border-gray-200 dark:border-gray-700 rounded-lg bg-white p-4">
                <textarea readonly class="w-full h-[600px] border-0 bg-transparent text-xs font-mono text-gray-800 dark:text-gray-100 resize-none focus:outline-none">{{ $jsonPretty }}</textarea>
            </div>

            <div x-show="tab === 'html'" class="border border-gray-200 dark:border-gray-700 rounded-lg bg-white p-4">
                <textarea readonly class="w-full h-[600px] border-0 bg-transparent text-xs font-mono text-gray-800 dark:text-gray-100 resize-none focus:outline-none">{{ $template->html_content ?? '' }}</textarea>
            </div>

            <div x-show="tab === 'plain'" class="border border-gray-200 dark:border-gray-700 rounded-lg bg-white p-4">
                <textarea readonly class="w-full h-[600px] border-0 bg-transparent text-sm font-mono text-gray-800 dark:text-gray-100 resize-none focus:outline-none">{{ $template->plain_text_content ?? '' }}</textarea>
            </div>
        </div>
    </x-card>
    @endif

    {{-- ═══════════════════════════════════ SETTINGS TAB ═══════════════════════════════════ --}}
    @if($activeTab === 'settings')
    <x-card title="Template Settings">
        @if($template->customer_id === auth('customer')->id())
            @customercan('templates.permissions.can_edit_templates')
                <form method="POST" action="{{ route('customer.templates.settings.update', ['template' => $template, 'tab' => 'settings']) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $template->name) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                        <select name="type" id="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="email" {{ old('type', $template->type) === 'email' ? 'selected' : '' }}>Email</option>
                            <option value="campaign" {{ old('type', $template->type) === 'campaign' ? 'selected' : '' }}>Campaign</option>
                            <option value="transactional" {{ old('type', $template->type) === 'transactional' ? 'selected' : '' }}>Transactional</option>
                            <option value="autoresponder" {{ old('type', $template->type) === 'autoresponder' ? 'selected' : '' }}>Autoresponder</option>
                            <option value="footer" {{ old('type', $template->type) === 'footer' ? 'selected' : '' }}>Footer Template</option>
                            <option value="signature" {{ old('type', $template->type) === 'signature' ? 'selected' : '' }}>Signature</option>
                        </select>
                        @error('type')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-1">
                        <input type="hidden" name="is_public" value="0">
                        <div class="flex items-center">
                            <input type="checkbox" name="is_public" id="is_public" value="1" {{ old('is_public', $template->is_public) ? 'checked' : '' }} class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="is_public" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">Make this template public</label>
                        </div>
                        @error('is_public')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end pt-2">
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md shadow-sm hover:bg-primary-700">
                            Save Changes
                        </button>
                    </div>
                </form>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">You do not have permission to edit this template.</p>
            @endcustomercan
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">This template belongs to another account and cannot be modified.</p>
        @endif
    </x-card>

    <!-- Danger Zone -->
    @if($template->customer_id === auth('customer')->id())
        <x-card class="mt-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Danger Zone</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Once you delete a template, there is no going back.</p>
                </div>
                @customercan('templates.permissions.can_delete_templates')
                    <form method="POST" action="{{ route('customer.templates.destroy', $template) }}" onsubmit="return confirm('Are you sure you want to delete this template?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md shadow-sm hover:bg-red-700">
                            Delete Template
                        </button>
                    </form>
                @endcustomercan
            </div>
        </x-card>
    @endif
    @endif
</div>
@endsection

