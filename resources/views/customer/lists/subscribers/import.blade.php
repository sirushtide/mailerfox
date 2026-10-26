@extends('layouts.customer')

@section('title', 'Import Subscribers')

@section('breadcrumbs')
    <nav aria-label="Breadcrumb" class="mb-0">
        <ol class="flex flex-wrap items-center gap-1.5 text-sm text-admin-text-secondary">
            <li>
                <a href="{{ route('customer.dashboard') }}" class="font-medium transition hover:text-admin-text-primary">Dashboard</a>
            </li>
            <li aria-hidden="true" class="text-admin-text-secondary/60">/</li>
            <li>
                <a href="{{ route('customer.lists.subscribers.index', $list) }}" class="font-medium transition hover:text-admin-text-primary">{{ $list->name }}</a>
            </li>
            <li aria-hidden="true" class="text-admin-text-secondary/60">/</li>
            <li class="font-medium text-admin-text-primary">Import Subscribers</li>
        </ol>
    </nav>
@endsection

@section('content')
@php
    $listCustomFields = is_array($list->custom_fields ?? null) ? $list->custom_fields : [];
    $listCustomFields = array_values(array_filter($listCustomFields, function ($f) {
        return is_array($f) && !empty($f['key']);
    }));
    $manualCustomFieldDefaults = [];
    foreach ($listCustomFields as $field) {
        $fieldKey = trim((string) ($field['key'] ?? ''));
        if ($fieldKey !== '') {
            $manualCustomFieldDefaults[$fieldKey] = '';
        }
    }
@endphp

<div class="max-w-4xl mx-auto" x-data="importSubscribersWizard()">
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-md text-sm">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-md text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    <x-card padding="false">
        <div class="px-6 pt-6 pb-2">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-lg bg-primary-50 dark:bg-primary-900/20 flex items-center justify-center text-primary-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2Z"/></svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Import Subscribers</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Import subscribers from a CSV file, paste email addresses manually, or add full subscriber details one by one.</p>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="px-6 mt-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex gap-8">
                <button type="button" @click="switchTab('file')"
                    :class="tab === 'file' ? 'border-primary-600 text-primary-600' : '!border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="inline-flex items-center gap-2 py-3 px-1 border-b-2 text-sm font-medium transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 3v4a1 1 0 0 0 1 1h4M5 3h9l5 5v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/></svg>
                    File Upload
                </button>
                <button type="button" @click="switchTab('paste')"
                    :class="tab === 'paste' ? 'border-primary-600 text-primary-600' : '!border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="inline-flex items-center gap-2 py-3 px-1 border-b-2 text-sm font-medium transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2"/></svg>
                    Manual Paste
                </button>
                <button type="button" @click="switchTab('manual')"
                    :class="tab === 'manual' ? 'border-primary-600 text-primary-600' : '!border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="inline-flex items-center gap-2 py-3 px-1 border-b-2 text-sm font-medium transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2Z"/></svg>
                    Manual Import
                </button>
            </div>
        </div>

        <!-- Stepper -->
        <div class="px-6 py-6">
            <div class="flex items-center justify-center">
                <template x-for="(s, i) in totalSteps" :key="i">
                    <div class="flex items-center" :class="i < totalSteps - 1 ? 'flex-1 max-w-[120px]' : ''">
                        <div :class="step >= (i + 1) ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500'"
                            class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold transition">
                            <span x-text="i + 1"></span>
                        </div>
                        <div x-show="i < totalSteps - 1" class="flex-1 h-px mx-2"
                             :class="step > (i + 1) ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700'"></div>
                    </div>
                </template>
            </div>
        </div>

        <!-- File Upload tab -->
        <div x-show="tab === 'file'" class="px-6 pb-6">
            {{-- Step 1: dropzone --}}
            <div x-show="step === 1">
                <div @dragover.prevent="dragActive = true"
                     @dragleave.prevent="dragActive = false"
                     @drop.prevent="handleDrop($event)"
                     :class="dragActive ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-900/10' : 'border-gray-300 dark:border-gray-600'"
                     class="border-2 border-dashed rounded-lg p-10 text-center transition-colors">
                    <template x-if="!selectedFile">
                        <div>
                            <div class="mx-auto w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M12 4v12m0-12-4 4m4-4 4 4"/></svg>
                            </div>
                            <p class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">Drop your CSV file here</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">or click to browse from your computer</p>
                            <button type="button" @click="$refs.fileInput.click()" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                                Select File
                            </button>
                            <input type="file" x-ref="fileInput" class="hidden" accept=".csv,text/csv" @change="handleFileSelect($event)">
                        </div>
                    </template>
                    <template x-if="selectedFile">
                        <div class="text-left">
                            <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-700 p-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-md bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 3v4a1 1 0 0 0 1 1h4M5 3h9l5 5v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate" x-text="selectedFile.name"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="formatBytes(selectedFile.size) + ' • ' + csvColumns.length + ' columns detected'"></p>
                                    </div>
                                </div>
                                <button type="button" @click="clearFile()" class="text-gray-400 hover:text-red-500 ml-3 shrink-0" title="Remove file">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="text-center mt-4">
                    <button type="button" @click="downloadSampleCsv()" class="inline-flex items-center gap-2 text-sm text-primary-600 hover:text-primary-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                        Download sample CSV template
                    </button>
                </div>
                <p class="text-xs text-center text-gray-400 mt-2">CSV file, max 50MB</p>
            </div>

            {{-- Step 2: column mapping --}}
            <div x-show="step === 2" x-cloak>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">Map columns</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Match your CSV columns to subscriber fields.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Email Column <span class="text-red-500">*</span>
                        </label>
                        <select x-model="mapping.email" id="column_mapping_email"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <option value="">Select column...</option>
                            <template x-for="column in csvColumns" :key="column">
                                <option :value="column" x-text="column"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">First Name Column</label>
                        <select x-model="mapping.firstName" id="column_mapping_first_name"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <option value="">Select column...</option>
                            <template x-for="column in csvColumns" :key="column">
                                <option :value="column" x-text="column"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Last Name Column</label>
                        <select x-model="mapping.lastName" id="column_mapping_last_name"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <option value="">Select column...</option>
                            <template x-for="column in csvColumns" :key="column">
                                <option :value="column" x-text="column"></option>
                            </template>
                        </select>
                    </div>
                    @foreach($listCustomFields as $field)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $field['label'] ?? $field['key'] }} Column</label>
                            <select data-custom-field-key="{{ $field['key'] }}" name="column_mapping[custom_fields][{{ $field['key'] }}]"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                <option value="">Select column...</option>
                                <template x-for="column in csvColumns" :key="column">
                                    <option :value="column" x-text="column"></option>
                                </template>
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Step 3: import options --}}
            <div x-show="step === 3" x-cloak>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">Import options</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Choose how to handle the imported data.</p>
                <div class="space-y-3">
                    <label class="flex items-center gap-3 p-3 rounded-md border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <input type="checkbox" id="capture_unmapped" x-model="opts.captureUnmapped" class="h-4 w-4 text-primary-600 rounded border-gray-300 focus:ring-primary-500">
                        <span class="text-sm text-gray-900 dark:text-gray-200">Import extra CSV columns as Custom Fields</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 rounded-md border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <input type="checkbox" id="add_list_custom_fields" x-model="opts.addListCustomFields" class="h-4 w-4 text-primary-600 rounded border-gray-300 focus:ring-primary-500">
                        <span class="text-sm text-gray-900 dark:text-gray-200">Add imported Custom Fields to this Email List</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 rounded-md border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <input type="checkbox" id="skip_duplicates" x-model="opts.skipDuplicates" class="h-4 w-4 text-primary-600 rounded border-gray-300 focus:ring-primary-500">
                        <span class="text-sm text-gray-900 dark:text-gray-200">Skip duplicate emails</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 rounded-md border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <input type="checkbox" id="update_existing" x-model="opts.updateExisting" class="h-4 w-4 text-primary-600 rounded border-gray-300 focus:ring-primary-500">
                        <span class="text-sm text-gray-900 dark:text-gray-200">Update existing subscribers</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Manual Paste tab -->
        <div x-show="tab === 'paste'" x-cloak class="px-6 pb-6">
            <div x-show="step === 1">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">Manual Paste</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Paste email addresses separated by new lines, commas, or spaces (up to 1,000).</p>
                <textarea x-model="pasteText" @input="updatePasteStats()" rows="12"
                    placeholder="alice@example.com&#10;bob@example.com&#10;carol@example.com&#10;...&#10;&#10;Or: alice@example.com, bob@example.com, carol@example.com"
                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm font-mono"></textarea>
                <div class="flex items-center justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
                    <span><span x-text="pasteEmails.length"></span> emails detected</span>
                    <span><span x-text="pasteText.length"></span>/50000 characters</span>
                </div>
                <div class="mt-4 p-3 rounded-md bg-gray-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-200 mb-1">Format Examples:</p>
                    <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-0.5 list-disc list-inside">
                        <li>One per line: <span class="font-mono">alice@example.com</span></li>
                        <li>Comma separated: <span class="font-mono">alice@example.com, bob@example.com</span></li>
                        <li>Mixed format: <span class="font-mono">alice@example.com bob@example.com</span></li>
                    </ul>
                </div>
            </div>

            <div x-show="step === 2" x-cloak>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">Import options</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Choose how to handle the imported data.</p>
                <div class="space-y-3">
                    <label class="flex items-center gap-3 p-3 rounded-md border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <input type="checkbox" x-model="opts.skipDuplicates" class="h-4 w-4 text-primary-600 rounded border-gray-300 focus:ring-primary-500">
                        <span class="text-sm text-gray-900 dark:text-gray-200">Skip duplicate emails</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 rounded-md border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <input type="checkbox" x-model="opts.updateExisting" class="h-4 w-4 text-primary-600 rounded border-gray-300 focus:ring-primary-500">
                        <span class="text-sm text-gray-900 dark:text-gray-200">Update existing subscribers</span>
                    </label>
                </div>
            </div>

        </div>

        <!-- Manual Import tab -->
        <div x-show="tab === 'manual'" x-cloak class="px-6 pb-6">
            {{-- Step 1: dynamic manual rows --}}
            <div x-show="step === 1">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">Manual Import</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Add subscribers manually with email, name, and custom field values.</p>
                    </div>
                    <button type="button" @click="addManualRow()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold hover:bg-[#1E5FEA] border border-dashed border-[#1E5FEA]/40 hover:border-[#1E5FEA] rounded-lg transition-colors brand-text-hover">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        Add more
                    </button>
                </div>

                <div class="space-y-3">
                    <template x-for="(row, idx) in manualRows" :key="row.id">
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/40 p-4 shadow-sm space-y-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Subscriber <span x-text="idx + 1"></span></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Fill the fields below for this contact.</p>
                                </div>
                                <button type="button" @click="removeManualRow(idx)" x-show="manualRows.length > 1"
                                    class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                    Remove
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email <span class="text-red-500">*</span></label>
                                    <input type="email" x-model="row.email" placeholder="alice@example.com"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">First Name</label>
                                    <input type="text" x-model="row.first_name" placeholder="Alice"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Last Name</label>
                                    <input type="text" x-model="row.last_name" placeholder="Johnson"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                                </div>
                            </div>

                            <div x-show="manualCustomFields.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <template x-for="field in manualCustomFields" :key="field.key">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" x-text="field.label || field.key"></label>
                                        <input type="text" x-model="row.custom_fields[field.key]" :placeholder="field.label || field.key"
                                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button type="button" @click="addManualRow()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold hover:bg-[#1E5FEA] border border-dashed border-[#1E5FEA]/40 hover:border-[#1E5FEA] rounded-lg transition-colors brand-text-hover">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        Add more
                    </button>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Rows with empty emails are ignored.</p>
                </div>
            </div>

            {{-- Step 2: import options (manual) --}}
            <div x-show="step === 2" x-cloak>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">Import options</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Choose how to handle the imported data.</p>
                <div class="space-y-3">
                    <label class="flex items-center gap-3 p-3 rounded-md border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <input type="checkbox" x-model="opts.skipDuplicates" class="h-4 w-4 text-primary-600 rounded border-gray-300 focus:ring-primary-500">
                        <span class="text-sm text-gray-900 dark:text-gray-200">Skip duplicate emails</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 rounded-md border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <input type="checkbox" x-model="opts.updateExisting" class="h-4 w-4 text-primary-600 rounded border-gray-300 focus:ring-primary-500">
                        <span class="text-sm text-gray-900 dark:text-gray-200">Update existing subscribers</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Final step: review/start (shared) --}}
        <div x-show="step === totalSteps" x-cloak class="px-6 pb-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">Review &amp; start import</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Review your settings and start importing.</p>

            <div class="rounded-md border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700 mb-4">
                <div class="flex justify-between px-4 py-3 text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Source</span>
                    <span class="text-gray-900 dark:text-gray-100" x-text="tab === 'file' ? 'CSV file upload' : (tab === 'paste' ? 'Manual paste' : 'Manual import')"></span>
                </div>
                <div class="flex justify-between px-4 py-3 text-sm" x-show="tab === 'file'">
                    <span class="text-gray-500 dark:text-gray-400">File</span>
                    <span class="text-gray-900 dark:text-gray-100" x-text="selectedFile ? selectedFile.name : '—'"></span>
                </div>
                <div class="flex justify-between px-4 py-3 text-sm" x-show="tab === 'paste'">
                    <span class="text-gray-500 dark:text-gray-400">Emails detected</span>
                    <span class="text-gray-900 dark:text-gray-100" x-text="pasteEmails.length"></span>
                </div>
                <div class="flex justify-between px-4 py-3 text-sm" x-show="tab === 'manual'">
                    <span class="text-gray-500 dark:text-gray-400">Subscriber rows</span>
                    <span class="text-gray-900 dark:text-gray-100" x-text="manualPreparedRows().length"></span>
                </div>
                <div class="flex justify-between px-4 py-3 text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Skip duplicates</span>
                    <span class="text-gray-900 dark:text-gray-100" x-text="opts.skipDuplicates ? 'Yes' : 'No'"></span>
                </div>
                <div class="flex justify-between px-4 py-3 text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Update existing</span>
                    <span class="text-gray-900 dark:text-gray-100" x-text="opts.updateExisting ? 'Yes' : 'No'"></span>
                </div>
            </div>

            <template x-if="uploading || importStarted || importCompleted || importFailed">
                <div class="space-y-3">
                    <template x-if="uploading">
                        <div>
                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                <span>Uploading...</span>
                                <span x-text="uploadProgress + '%'"></span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                                <div class="bg-primary-600 h-full transition-all" :style="`width: ${uploadProgress}%`"></div>
                            </div>
                        </div>
                    </template>
                    <template x-if="importStarted && !importCompleted && !importFailed">
                        <div>
                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                <span>Processing...</span>
                                <span x-text="importText"></span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                                <div class="bg-primary-600 h-full transition-all" :style="`width: ${importPercent}%`"></div>
                            </div>
                        </div>
                    </template>
                    <template x-if="importCompleted">
                        <div class="p-3 rounded-md bg-green-50 border border-green-200 text-green-800 text-sm" x-text="importSuccess"></div>
                    </template>
                    <template x-if="importFailed">
                        <div class="p-3 rounded-md bg-red-50 border border-red-200 text-red-700 text-sm" x-text="importError"></div>
                    </template>
                </div>
            </template>
        </div>

        {{-- Footer actions --}}
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div>
                <button type="button" x-show="step > 1 && !importStarted" @click="back()"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                    Back
                </button>
            </div>
            <div class="flex items-center gap-3">
                <a x-show="!importStarted" href="{{ route('customer.lists.subscribers.index', $list) }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</a>
                <a x-show="importCompleted" href="{{ route('customer.lists.subscribers.index', $list) }}"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                    Done
                </a>

                <button type="button" x-show="step < totalSteps" @click="next()" :disabled="!canContinue()"
                    :class="canContinue() ? 'bg-primary-600 hover:bg-primary-700' : 'bg-primary-300 cursor-not-allowed'"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white">
                    Continue
                </button>
                <button type="button" x-show="step === totalSteps && !importStarted && !importCompleted" @click="startImport()" :disabled="uploading"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-60 disabled:cursor-not-allowed">
                    <span x-show="!uploading">Start Import</span>
                    <span x-show="uploading">Uploading...</span>
                </button>
            </div>
        </div>
    </x-card>
</div>

<script>
function importSubscribersWizard() {
    return {
        tab: 'file',
        step: 1,
        dragActive: false,
        pasteText: '',
        pasteEmails: [],
        manualCustomFields: @json($listCustomFields),
        manualCustomFieldDefaults: @json($manualCustomFieldDefaults),

        // file upload state
        selectedFile: null,
        csvColumns: [],
        mapping: { email: '', firstName: '', lastName: '' },

        // manual import state
        manualRows: [],

        // shared options
        opts: {
            captureUnmapped: true,
            addListCustomFields: true,
            skipDuplicates: true,
            updateExisting: false,
        },

        // progress
        uploading: false,
        uploadProgress: 0,
        importStarted: false,
        importId: null,
        importPercent: 0,
        importText: '0 / 0 processed',
        importCompleted: false,
        importFailed: false,
        importError: '',
        importSuccess: '',
        stepTimer: null,

        init() {
            if (!this.manualRows.length) {
                this.manualRows.push(this.createManualRow());
            }
            this.updatePasteStats();
        },

        createManualRow() {
            return {
                id: `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`,
                email: '',
                first_name: '',
                last_name: '',
                custom_fields: { ...this.manualCustomFieldDefaults },
            };
        },

        addManualRow() {
            this.manualRows.push(this.createManualRow());
        },

        removeManualRow(index) {
            if (this.manualRows.length <= 1) return;
            this.manualRows.splice(index, 1);
        },

        updatePasteStats() {
            const text = String(this.pasteText || '');
            const tokens = text.split(/[\s,;]+/).map((s) => s.trim()).filter(Boolean);
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const seen = new Set();
            const out = [];

            for (const token of tokens) {
                if (!re.test(token)) continue;
                const lower = token.toLowerCase();
                if (seen.has(lower)) continue;
                seen.add(lower);
                out.push(lower);
                if (out.length >= 1000) break;
            }

            this.pasteEmails = out;
        },

        manualPreparedRows() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            return this.manualRows
                .map((row) => {
                    const email = String(row.email || '').trim().toLowerCase();
                    const firstName = String(row.first_name || '').trim();
                    const lastName = String(row.last_name || '').trim();
                    const customFields = {};

                    for (const field of this.manualCustomFields) {
                        const key = String(field.key || '').trim();
                        if (!key) continue;
                        const value = String((row.custom_fields && row.custom_fields[key]) || '').trim();
                        if (value !== '') {
                            customFields[key] = value;
                        }
                    }

                    return {
                        id: row.id,
                        email,
                        first_name: firstName,
                        last_name: lastName,
                        custom_fields: customFields,
                        has_data: Boolean(email || firstName || lastName || Object.keys(customFields).length),
                        is_valid: emailRegex.test(email),
                    };
                })
                .filter((row) => row.has_data && row.is_valid);
        },

        escapeCsvValue(value) {
            const stringValue = String(value ?? '');
            if (/[",\n\r]/.test(stringValue)) {
                return '"' + stringValue.replace(/"/g, '""') + '"';
            }
            return stringValue;
        },

        buildPasteCsvBlob() {
            const lines = ['email'];
            for (const email of this.pasteEmails) {
                lines.push(this.escapeCsvValue(email));
            }
            return new Blob([lines.join('\n')], { type: 'text/csv' });
        },

        get totalSteps() {
            return this.tab === 'file' ? 4 : 3;
        },

        switchTab(t) {
            if (this.importStarted) return;
            this.tab = t;
            this.step = 1;
            if (t === 'paste') {
                this.updatePasteStats();
            }
            if (t === 'manual' && !this.manualRows.length) {
                this.manualRows.push(this.createManualRow());
            }
        },

        canContinue() {
            if (this.tab === 'file') {
                if (this.step === 1) return !!this.selectedFile && this.csvColumns.length > 0;
                if (this.step === 2) return !!this.mapping.email;
                if (this.step === 3) return true;
            } else {
                if (this.step === 1) {
                    if (this.tab === 'paste') return this.pasteEmails.length > 0 && this.pasteEmails.length <= 1000;
                    if (this.tab === 'manual') return this.manualPreparedRows().length > 0 && this.manualPreparedRows().length <= 1000;
                }
                if (this.step === 2) return true;
            }
            return true;
        },

        next() {
            if (!this.canContinue()) return;
            if (this.step < this.totalSteps) this.step++;
        },

        back() {
            if (this.step > 1) this.step--;
        },

        formatBytes(bytes) {
            if (!bytes) return '0 B';
            const k = 1024, units = ['B','KB','MB','GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return (bytes / Math.pow(k, i)).toFixed(i ? 1 : 0) + ' ' + units[i];
        },

        clearFile() {
            this.selectedFile = null;
            this.csvColumns = [];
            this.mapping = { email: '', firstName: '', lastName: '' };
            if (this.$refs.fileInput) this.$refs.fileInput.value = '';
        },

        handleDrop(e) {
            this.dragActive = false;
            const file = e.dataTransfer.files && e.dataTransfer.files[0];
            if (file) this.acceptFile(file);
        },

        handleFileSelect(e) {
            const file = e.target.files && e.target.files[0];
            if (file) this.acceptFile(file);
        },

        acceptFile(file) {
            if (!/\.(csv|txt)$/i.test(file.name)) {
                alert('Please choose a CSV file.');
                return;
            }
            this.selectedFile = file;
            this.resetImportState();
            const reader = new FileReader();
            reader.onload = (ev) => {
                const text = String(ev.target.result || '');
                const firstLine = text.split(/\r?\n/)[0] || '';
                this.csvColumns = firstLine.split(',').map(c => c.trim().replace(/^"|"$/g, '')).filter(Boolean);
                // auto-detect email column
                const emailGuess = this.csvColumns.find(c => /email/i.test(c));
                if (emailGuess) this.mapping.email = emailGuess;
                const fnGuess = this.csvColumns.find(c => /^first[_\s-]?name$/i.test(c));
                if (fnGuess) this.mapping.firstName = fnGuess;
                const lnGuess = this.csvColumns.find(c => /^last[_\s-]?name$/i.test(c));
                if (lnGuess) this.mapping.lastName = lnGuess;
            };
            reader.readAsText(file);
        },

        downloadSampleCsv() {
            const sample = 'email,first_name,last_name\njohn@example.com,John,Doe\njane@example.com,Jane,Smith\n';
            const blob = new Blob([sample], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'sample_subscribers.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        },

        resetImportState() {
            this.importStarted = false;
            this.importCompleted = false;
            this.importFailed = false;
            this.importError = '';
            this.importSuccess = '';
            this.importPercent = 0;
            this.importText = '0 / 0 processed';
            this.importId = null;
            this.uploading = false;
            this.uploadProgress = 0;
        },

        buildManualCsvBlob() {
            const rows = this.manualPreparedRows();
            const customFieldKeys = this.manualCustomFields
                .map((field) => String(field.key || '').trim())
                .filter(Boolean);

            const headers = ['email', 'first_name', 'last_name', ...customFieldKeys];
            const lines = [headers.join(',')];

            for (const row of rows) {
                const values = [
                    row.email,
                    row.first_name,
                    row.last_name,
                    ...customFieldKeys.map((key) => row.custom_fields[key] ?? ''),
                ].map((value) => this.escapeCsvValue(value));

                lines.push(values.join(','));
            }

            return new Blob([lines.join('\n')], { type: 'text/csv' });
        },

        startImport() {
            if (this.importStarted || this.uploading) return;

            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');

            if (this.tab === 'file') {
                if (!this.selectedFile || !this.mapping.email) return;
                formData.append('csv_file', this.selectedFile);
                formData.append('column_mapping[email]', this.mapping.email);
                if (this.mapping.firstName) formData.append('column_mapping[first_name]', this.mapping.firstName);
                if (this.mapping.lastName)  formData.append('column_mapping[last_name]',  this.mapping.lastName);
                document.querySelectorAll('select[data-custom-field-key]').forEach((el) => {
                    const name = el.getAttribute('name');
                    const value = el.value || '';
                    if (name && value) formData.append(name, value);
                });
                formData.append('column_mapping[capture_unmapped]', this.opts.captureUnmapped ? 1 : 0);
                formData.append('column_mapping[add_list_custom_fields]', this.opts.addListCustomFields ? 1 : 0);
            } else if (this.tab === 'paste') {
                if (this.pasteEmails.length === 0) return;
                const blob = this.buildPasteCsvBlob();
                formData.append('csv_file', blob, 'pasted_emails.csv');
                formData.append('column_mapping[email]', 'email');
                formData.append('column_mapping[capture_unmapped]', 0);
                formData.append('column_mapping[add_list_custom_fields]', 0);
            } else {
                const rows = this.manualPreparedRows();
                if (rows.length === 0) return;

                const customFieldKeys = this.manualCustomFields
                    .map((field) => String(field.key || '').trim())
                    .filter(Boolean);

                const blob = this.buildManualCsvBlob();
                formData.append('csv_file', blob, 'manual_subscribers.csv');
                formData.append('column_mapping[email]', 'email');
                formData.append('column_mapping[first_name]', 'first_name');
                formData.append('column_mapping[last_name]', 'last_name');
                customFieldKeys.forEach((key) => {
                    formData.append(`column_mapping[custom_fields][${key}]`, key);
                });
                formData.append('column_mapping[capture_unmapped]', 0);
                formData.append('column_mapping[add_list_custom_fields]', 0);
            }

            formData.append('skip_duplicates', this.opts.skipDuplicates ? 1 : 0);
            formData.append('update_existing', this.opts.updateExisting ? 1 : 0);

            this.uploading = true;
            this.uploadProgress = 0;
            this.importFailed = false;
            this.importError = '';

            const xhr = new XMLHttpRequest();
            xhr.open('POST', '{{ route('customer.lists.subscribers.import.ajax.start', $list) }}', true);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.upload.onprogress = (event) => {
                if (!event.lengthComputable) return;
                this.uploadProgress = Math.max(0, Math.min(100, Math.round((event.loaded / event.total) * 100)));
            };
            xhr.onload = () => {
                this.uploading = false;
                this.uploadProgress = 100;
                let payload = null;
                try { payload = JSON.parse(xhr.responseText); } catch (e) { payload = null; }
                if (!payload || !payload.success) {
                    this.importFailed = true;
                    this.importError = (payload && payload.message) ? payload.message : 'Failed to start import.';
                    return;
                }
                this.importId = payload.import_id;
                this.importStarted = true;
                this.queueStep();
            };
            xhr.onerror = () => {
                this.uploading = false;
                this.importFailed = true;
                this.importError = 'Upload failed. Please try again.';
            };
            xhr.send(formData);
        },

        queueStep() {
            if (!this.importId) return;
            if (this.stepTimer) clearTimeout(this.stepTimer);
            this.stepTimer = setTimeout(() => this.stepOnce(), 600);
        },

        stepOnce() {
            if (!this.importId || this.importCompleted || this.importFailed) return;
            fetch('{{ route('customer.lists.subscribers.import.ajax.step', $list) }}', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ import_id: this.importId })
            })
            .then(r => r.json())
            .then(data => {
                if (!data || !data.success) {
                    this.importFailed = true;
                    this.importError = 'Import failed.';
                    return;
                }
                const stats = data.import;
                if (!stats) { this.queueStep(); return; }
                this.importPercent = stats.percent || 0;
                const fmt = new Intl.NumberFormat();
                this.importText = fmt.format(stats.processed_count || 0) + ' / ' + fmt.format(stats.total_rows || 0) + ' processed';
                if (stats.status === 'failed') {
                    this.importFailed = true;
                    this.importError = stats.failure_reason || 'Import failed.';
                    return;
                }
                if (stats.status === 'completed') {
                    this.importCompleted = true;
                    this.importSuccess = 'Import completed: ' + fmt.format(stats.imported_count || 0) + ' imported, ' + fmt.format(stats.updated_count || 0) + ' updated, ' + fmt.format(stats.skipped_count || 0) + ' skipped, ' + fmt.format(stats.error_count || 0) + ' errors.';
                    return;
                }
                this.queueStep();
            })
            .catch(() => this.queueStep());
        },
    };
}
</script>
@endsection
