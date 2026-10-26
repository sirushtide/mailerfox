@extends('layouts.customer')

@section('title', __('Outreach'))
@section('page-title', __('Outreach'))

@section('page-header')
    <x-page-header
        :title="__('Outreach')"
        :description="__('Cold email campaigns, sequences and lead activity.')"
    >
        <x-slot:actions>
            <button
                type="button"
                x-on:click="$dispatch('open-outreach-create')"
                data-track="outreach.create"
                class="mp-btn mp-btn--md mp-btn--primary w-full sm:w-auto"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('New Campaign') }}
            </button>
        </x-slot:actions>
    </x-page-header>
@endsection

@section('content')
<div class="space-y-6" x-data="{ showCreate: false }" x-on:open-outreach-create.window="showCreate = true">

    @if(session('success'))
        <div class="flex items-center gap-3 px-4 py-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-sm text-green-800 dark:text-green-300">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Campaign List --}}
    @if($campaigns->isEmpty())
        <div class="mp-card">
            <div class="mp-empty">
                <div class="mp-empty__icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <p class="mp-empty__title">{{ __('No campaigns yet') }}</p>
                <p class="mp-empty__desc max-w-xs">{{ __('Create your first outreach campaign to start connecting with prospects.') }}</p>
                <button type="button" @click="showCreate = true" class="mp-btn mp-btn--md mp-btn--primary mt-4">
                    {{ __('Create Campaign') }}
                </button>
            </div>
        </div>
    @else
        <div class="mp-card">
            <div class="mp-table-wrap">
                <table class="mp-table">
                    <thead>
                        <tr>
                            <th>{{ __('Campaign') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="is-num">{{ __('Leads') }}</th>
                            <th>{{ __('Created') }}</th>
                            <th class="is-num">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($campaigns as $c)
                            @php
                                $statusTones = [
                                    'active' => 'success',
                                    'paused' => 'warning',
                                    'failed' => 'danger',
                                    'completed' => 'info',
                                ];
                                $statusTone = $statusTones[$c->status] ?? 'neutral';
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('customer.outreach.campaigns.show', $c) }}" class="font-medium text-admin-text-primary hover:underline">{{ $c->name }}</a>
                                </td>
                                <td>
                                    <x-status-badge :tone="$statusTone" :dot="$c->status === 'active'">
                                        {{ ucfirst($c->status) }}
                                    </x-status-badge>
                                </td>
                                <td class="is-num">{{ number_format($c->leads_count) }}</td>
                                <td class="text-admin-text-secondary">{{ $c->created_at->format('M j, Y') }}</td>
                                <td class="is-num">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('customer.outreach.campaigns.show', $c) }}"
                                            class="mp-icon-btn" style="width:2rem;height:2rem"
                                            title="{{ __('View') }}"
                                            aria-label="{{ __('View') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                                        </a>
                                        <a href="{{ route('customer.outreach.campaigns.show', [$c, 'tab' => 'options']) }}"
                                            class="mp-icon-btn" style="width:2rem;height:2rem"
                                            title="{{ __('Edit') }}"
                                            aria-label="{{ __('Edit') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M13 21h8"/><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/></svg>
                                        </a>
                                        <form method="POST" action="{{ route('customer.outreach.campaigns.duplicate', $c) }}" class="inline-flex">
                                            @csrf
                                            <button type="submit"
                                                class="mp-icon-btn" style="width:2rem;height:2rem"
                                                title="{{ __('Duplicate') }}"
                                                aria-label="{{ __('Duplicate') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('customer.outreach.campaigns.destroy', $c) }}" class="inline-flex" onsubmit="return confirm('{{ __('Delete this campaign?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="mp-icon-btn hover:text-red-600 dark:hover:text-red-400" style="width:2rem;height:2rem"
                                                title="{{ __('Delete') }}"
                                                aria-label="{{ __('Delete') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"/><path d="M14 11v6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Create Campaign Modal --}}
    <div
        x-cloak x-show="showCreate"
        x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="showCreate = false"
    >
        <div x-show="showCreate"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="mp-card w-full max-w-md p-6 space-y-5"
        >
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-admin-text-primary">{{ __('New Outreach Campaign') }}</h2>
                <button type="button" @click="showCreate = false" class="mp-icon-btn" style="width:2rem;height:2rem" aria-label="{{ __('Close') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form method="POST" action="{{ route('customer.outreach.campaigns.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-admin-text-primary mb-1.5">{{ __('Campaign Name') }}</label>
                    <input type="text" name="name" required placeholder="{{ __('e.g. Q1 B2B Outreach') }}" class="mp-input w-full">
                </div>
                <div class="flex gap-3 pt-1">
                    <button type="submit" class="mp-btn mp-btn--md mp-btn--primary flex-1">{{ __('Create Campaign') }}</button>
                    <button type="button" @click="showCreate = false" class="mp-btn mp-btn--md flex-1">{{ __('Cancel') }}</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
