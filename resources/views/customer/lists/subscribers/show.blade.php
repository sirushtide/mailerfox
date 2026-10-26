@extends('layouts.customer')

@section('title', '')

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
                <a href="{{ route('customer.lists.subscribers.index', $list) }}"
                   class="font-medium text-gray-600 transition hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400">
                    Subscribers
                </a>
            </li>
            <li aria-hidden="true" class="text-gray-300 dark:text-gray-600">/</li>
            <li class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ $subscriber->email }}</li>
        </ol>
    </nav>
@endsection

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    {{-- Header row: name + actions --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ trim($subscriber->first_name . ' ' . $subscriber->last_name) ?: 'Unnamed' }}</h2>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                    {{ $subscriber->status === 'confirmed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                    {{ $subscriber->status === 'unconfirmed' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                    {{ $subscriber->status === 'unsubscribed' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
                    {{ $subscriber->status === 'bounced' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                ">{{ ucfirst($subscriber->status) }}</span>
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $subscriber->email }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('customer.lists.subscribers.edit', [$list, $subscriber]) }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                Edit
            </a>
        @if($subscriber->status === 'unconfirmed')
            <form method="POST" action="{{ route('customer.lists.subscribers.resend-confirmation', [$list, $subscriber]) }}" class="inline">
                @csrf
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">Resend</button>
            </form>
            <form method="POST" action="{{ route('customer.lists.subscribers.confirm', [$list, $subscriber]) }}" class="inline">
                @csrf
                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-emerald-700">Confirm</button>
            </form>
        @endif
        @if($subscriber->status !== 'unsubscribed')
            <form method="POST" action="{{ route('customer.lists.subscribers.unsubscribe', [$list, $subscriber]) }}" class="inline">
                @csrf
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-red-700">Unsubscribe</button>
            </form>
        @endif
        </div>
    </div>

    {{-- Stats row --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <div class="rounded-xl border border-admin-border bg-admin-sidebar p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Campaigns</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format((int) ($contactPerformance['total_campaigns'] ?? 0)) }}</p>
        </div>
        <div class="rounded-xl border border-admin-border bg-admin-sidebar p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Sent</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format((int) ($contactPerformance['sent_count'] ?? 0)) }}</p>
        </div>
        <div class="rounded-xl border border-admin-border bg-admin-sidebar p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Open Rate</p>
            <p class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format((float) ($contactPerformance['open_rate'] ?? 0), 1) }}%</p>
        </div>
        <div class="rounded-xl border border-admin-border bg-admin-sidebar p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Click Rate</p>
            <p class="mt-1 text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format((float) ($contactPerformance['click_rate'] ?? 0), 1) }}%</p>
        </div>
        <div class="rounded-xl border border-admin-border bg-admin-sidebar p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Bounced</p>
            <p class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format((int) ($contactPerformance['bounced_count'] ?? 0)) }}</p>
        </div>
    </div>

    {{-- Two column layout: info left, activity right --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left: Subscriber info --}}
        <div class="space-y-6 lg:col-span-1">
            <x-card title="Subscriber Information">
                <dl class="space-y-4">
                    <div class="flex justify-between gap-4">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">First Name</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $subscriber->first_name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Last Name</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $subscriber->last_name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Source</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $subscriber->source ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">IP Address</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $subscriber->ip_address ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Subscribed</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $subscriber->subscribed_at ? $subscriber->subscribed_at->format('M d, Y') : '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Confirmed</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $subscriber->confirmed_at ? $subscriber->confirmed_at->format('M d, Y') : '—' }}</dd>
                    </div>
                    @if($subscriber->unsubscribed_at)
                    <div class="flex justify-between gap-4">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Unsubscribed</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $subscriber->unsubscribed_at->format('M d, Y') }}</dd>
                    </div>
                    @endif
                </dl>
            </x-card>

            @if($subscriber->notes)
                <x-card title="Notes">
                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $subscriber->notes }}</p>
                </x-card>
            @endif
        </div>

        {{-- Right: Activity history --}}
        <div class="lg:col-span-2">
            <x-card title="Activity History" :padding="false" class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Time</th>
                                <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Event</th>
                                <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Campaign</th>
                                <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Details</th>
                                <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            @forelse($activityHistory as $event)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ optional($event['occurred_at'] ?? null)->format('M d, Y H:i') ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ ucfirst((string) ($event['event'] ?? 'unknown')) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $event['campaign_name'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        @if(!empty($event['url']))
                                            <div class="max-w-xs truncate">{{ $event['url'] }}</div>
                                        @endif
                                        @if(!empty($event['details']))
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $event['details'] }}</div>
                                        @endif
                                        @if(empty($event['url']) && empty($event['details']))
                                            —
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $event['ip_address'] ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="mp-empty">
                                            <div class="mp-empty__icon">
                                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11h3.75a2 2 0 0 1 1.6.8l.45.6a4 4 0 0 0 6.4 0l.45-.6a2 2 0 0 1 1.6-.8H21"/><path d="M3 7h18"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                                            </div>
                                            <p class="mp-empty__title">No tracked activity yet for this contact.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    </div>
@endsection

