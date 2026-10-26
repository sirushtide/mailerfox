@extends('layouts.customer')

@section('title', 'Edit')
@section('page-title', '')

@php
    $rules = is_array($segment->rules) ? $segment->rules : [];
    $combineOperator = $rules['combine_operator'] ?? 'all';
    $conditions = $rules['conditions'] ?? [];
@endphp

@section('breadcrumbs')
    <nav aria-label="Breadcrumb" class="mb-0">
        <ol class="flex flex-wrap items-center gap-1.5 text-[12px] text-gray-500 dark:text-gray-400">
            <li>
                <a href="{{ route('customer.lists.index') }}"
                   class="font-medium text-gray-600 transition hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400">
                    Audience
                </a>
            </li>
            <li aria-hidden="true" class="text-gray-300 dark:text-gray-600">/</li>
            <li>
                <a href="{{ route('customer.lists.show', $list) }}"
                   class="font-medium text-gray-600 transition hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400">
                    {{ $list->display_name ?? $list->name }}
                </a>
            </li>
            <li aria-hidden="true" class="text-gray-300 dark:text-gray-600">/</li>
            <li>
                <a href="{{ route('customer.lists.segments.index', $list) }}"
                   class="font-medium text-gray-600 transition hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400">
                    Segments
                </a>
            </li>
            <li aria-hidden="true" class="text-gray-300 dark:text-gray-600">/</li>
            <li class="font-medium text-gray-900 dark:text-gray-100">{{ $segment->name }}</li>
            <li aria-hidden="true" class="text-gray-300 dark:text-gray-600">/</li>
            <li class="font-medium text-gray-900 dark:text-gray-100">Edit</li>
        </ol>
    </nav>
@endsection

@section('content')
<div class="mx-auto max-w-3xl space-y-6">

    <form method="POST" action="{{ route('customer.lists.segments.update', ['list' => $list, 'segment' => $segment]) }}" id="segment-edit-form">
        @csrf
        @method('PUT')

        {{-- Name --}}
        <div class="mb-6">
            <div class="space-y-5">
                <div>
                    <label for="seg-name" class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Segment Name</label>
                    <input id="seg-name" type="text" name="name" value="{{ old('name', $segment->name) }}" required
                           placeholder="e.g. VIP Customers"
                           class="block w-full rounded-lg border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-500">
                    @error('name')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- Conditions --}}
        <div>
            <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-gray-50">Conditions</h2>

            <div class="mb-4 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                Contacts must match
                <select name="combine_operator"
                        class="min-w-[80px] rounded-md border border-gray-200 bg-white px-2.5 py-1 text-sm font-bold text-gray-800 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    <option value="all" {{ old('combine_operator', $combineOperator) === 'all' ? 'selected' : '' }}>ALL</option>
                    <option value="any" {{ old('combine_operator', $combineOperator) === 'any' ? 'selected' : '' }}>ANY</option>
                </select>
                of the following rules:
            </div>

            @error('conditions')<p class="mb-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror

            <div class="space-y-3" id="conditions-list">
                @php
                    $oldConditions = old('conditions', $conditions);
                    $conditionCount = max(1, count($oldConditions));
                @endphp
                @for($i = 0; $i < $conditionCount; $i++)
                    @php
                        $cond = $oldConditions[$i] ?? ['field' => '', 'operator' => 'is', 'value' => ''];
                    @endphp
                    <div class="condition-row rounded-xl border border-blue-100 bg-blue-50/40 p-4 dark:border-gray-600 dark:bg-gray-700/30">
                        <div class="flex flex-wrap items-center gap-2">
                            <select name="conditions[{{ $i }}][field]" required
                                    class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-700 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                <optgroup label="PROFILE">
                                    <option value="email" {{ ($cond['field'] ?? '') === 'email' ? 'selected' : '' }}>Email</option>
                                    <option value="first_name" {{ ($cond['field'] ?? '') === 'first_name' ? 'selected' : '' }}>First Name</option>
                                    <option value="last_name" {{ ($cond['field'] ?? '') === 'last_name' ? 'selected' : '' }}>Last Name</option>
                                    <option value="tags" {{ ($cond['field'] ?? '') === 'tags' ? 'selected' : '' }}>Tags</option>
                                    <option value="custom_fields" {{ ($cond['field'] ?? '') === 'custom_fields' ? 'selected' : '' }}>Custom Field</option>
                                </optgroup>
                                <optgroup label="SUBSCRIPTION">
                                    <option value="status" {{ ($cond['field'] ?? '') === 'status' ? 'selected' : '' }}>Status</option>
                                    <option value="subscribed_at" {{ ($cond['field'] ?? '') === 'subscribed_at' ? 'selected' : '' }}>Subscribed Date</option>
                                    <option value="unsubscribed_at" {{ ($cond['field'] ?? '') === 'unsubscribed_at' ? 'selected' : '' }}>Unsubscribed Date</option>
                                    <option value="source" {{ ($cond['field'] ?? '') === 'source' ? 'selected' : '' }}>Source</option>
                                    <option value="confirmed_at" {{ ($cond['field'] ?? '') === 'confirmed_at' ? 'selected' : '' }}>Confirmed Date</option>
                                </optgroup>
                                <optgroup label="ENGAGEMENT">
                                    <option value="last_opened_at" {{ ($cond['field'] ?? '') === 'last_opened_at' ? 'selected' : '' }}>Last Opened</option>
                                    <option value="open_count" {{ ($cond['field'] ?? '') === 'open_count' ? 'selected' : '' }}>Open Count</option>
                                    <option value="last_clicked_at" {{ ($cond['field'] ?? '') === 'last_clicked_at' ? 'selected' : '' }}>Last Clicked</option>
                                    <option value="click_count" {{ ($cond['field'] ?? '') === 'click_count' ? 'selected' : '' }}>Click Count</option>
                                    <option value="inactive_days" {{ ($cond['field'] ?? '') === 'inactive_days' ? 'selected' : '' }}>Inactive Days</option>
                                </optgroup>
                                <optgroup label="CAMPAIGN">
                                    <option value="campaign_received" {{ ($cond['field'] ?? '') === 'campaign_received' ? 'selected' : '' }}>Received Campaign</option>
                                    <option value="campaign_opened" {{ ($cond['field'] ?? '') === 'campaign_opened' ? 'selected' : '' }}>Opened Campaign</option>
                                    <option value="campaign_clicked" {{ ($cond['field'] ?? '') === 'campaign_clicked' ? 'selected' : '' }}>Clicked Campaign</option>
                                    <option value="campaign_not_opened" {{ ($cond['field'] ?? '') === 'campaign_not_opened' ? 'selected' : '' }}>Not Opened Campaign</option>
                                    <option value="campaign_bounced" {{ ($cond['field'] ?? '') === 'campaign_bounced' ? 'selected' : '' }}>Bounced Campaign</option>
                                </optgroup>
                            </select>

                            <select name="conditions[{{ $i }}][operator]" required
                                    class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-700 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                <option value="is" {{ ($cond['operator'] ?? 'is') === 'is' ? 'selected' : '' }}>is</option>
                                <option value="is_not" {{ ($cond['operator'] ?? '') === 'is_not' ? 'selected' : '' }}>is not</option>
                                <option value="contains" {{ ($cond['operator'] ?? '') === 'contains' ? 'selected' : '' }}>contains</option>
                                <option value="not_contains" {{ ($cond['operator'] ?? '') === 'not_contains' ? 'selected' : '' }}>does not contain</option>
                                <option value="greater_than" {{ ($cond['operator'] ?? '') === 'greater_than' ? 'selected' : '' }}>is greater than</option>
                                <option value="less_than" {{ ($cond['operator'] ?? '') === 'less_than' ? 'selected' : '' }}>is less than</option>
                                <option value="between" {{ ($cond['operator'] ?? '') === 'between' ? 'selected' : '' }}>is between</option>
                                <option value="in_last_days" {{ ($cond['operator'] ?? '') === 'in_last_days' ? 'selected' : '' }}>is in last (days)</option>
                                <option value="before" {{ ($cond['operator'] ?? '') === 'before' ? 'selected' : '' }}>is before</option>
                                <option value="after" {{ ($cond['operator'] ?? '') === 'after' ? 'selected' : '' }}>is after</option>
                            </select>

                            <input type="text" name="conditions[{{ $i }}][value]" value="{{ $cond['value'] ?? '' }}" required
                                   placeholder="Value..."
                                   class="min-w-[160px] flex-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-700 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">

                            @if($i > 0)
                                <button type="button" onclick="this.closest('.condition-row').remove()"
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                </button>
                            @endif
                        </div>
                    </div>
                @endfor
            </div>

            <button type="button" onclick="addCondition()"
                    class="mt-3 inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add Condition
            </button>
        </div>

        {{-- Bottom actions --}}
        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('customer.lists.segments.index', $list) }}"
               class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.75V16.5L12 14.25 7.5 16.5V3.75m9 0H18A2.25 2.25 0 0 1 20.25 6v12A2.25 2.25 0 0 1 18 20.25H6A2.25 2.25 0 0 1 3.75 18V6A2.25 2.25 0 0 1 6 3.75h1.5m9 0h-9"/></svg>
                Save Segment
            </button>
        </div>
    </form>
</div>

<script>
function addCondition() {
    const list = document.getElementById('conditions-list');
    const count = list.children.length;
    const div = document.createElement('div');
    div.className = 'condition-row rounded-xl border border-blue-100 bg-blue-50/40 p-4 dark:border-gray-600 dark:bg-gray-700/30';
    div.innerHTML = `
        <div class="flex flex-wrap items-center gap-2">
            <select name="conditions[${count}][field]" required
                    class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-700 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                <optgroup label="PROFILE">
                    <option value="email">Email</option>
                    <option value="first_name">First Name</option>
                    <option value="last_name">Last Name</option>
                    <option value="tags">Tags</option>
                    <option value="custom_fields">Custom Field</option>
                </optgroup>
                <optgroup label="SUBSCRIPTION">
                    <option value="status">Status</option>
                    <option value="subscribed_at">Subscribed Date</option>
                    <option value="unsubscribed_at">Unsubscribed Date</option>
                    <option value="source">Source</option>
                    <option value="confirmed_at">Confirmed Date</option>
                </optgroup>
                <optgroup label="ENGAGEMENT">
                    <option value="last_opened_at">Last Opened</option>
                    <option value="open_count">Open Count</option>
                    <option value="last_clicked_at">Last Clicked</option>
                    <option value="click_count">Click Count</option>
                    <option value="inactive_days">Inactive Days</option>
                </optgroup>
                <optgroup label="CAMPAIGN">
                    <option value="campaign_received">Received Campaign</option>
                    <option value="campaign_opened">Opened Campaign</option>
                    <option value="campaign_clicked">Clicked Campaign</option>
                    <option value="campaign_not_opened">Not Opened Campaign</option>
                    <option value="campaign_bounced">Bounced Campaign</option>
                </optgroup>
            </select>
            <select name="conditions[${count}][operator]" required
                    class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-700 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                <option value="is">is</option>
                <option value="is_not">is not</option>
                <option value="contains">contains</option>
                <option value="not_contains">does not contain</option>
                <option value="greater_than">is greater than</option>
                <option value="less_than">is less than</option>
                <option value="between">is between</option>
                <option value="in_last_days">is in last (days)</option>
                <option value="before">is before</option>
                <option value="after">is after</option>
            </select>
            <input type="text" name="conditions[${count}][value]" required
                   placeholder="Value..."
                   class="min-w-[160px] flex-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-700 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
            <button type="button" onclick="this.closest('.condition-row').remove()"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
            </button>
        </div>
    `;
    list.appendChild(div);
}
</script>
@endsection
