@extends('layouts.customer')

@section('title', __('Enriched Leads'))
@section('page-title', __('Enriched Leads'))

@section('page-header')
<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div class="min-w-0">
        <nav aria-label="Breadcrumb" class="mb-0">
            <ol class="flex flex-wrap items-center gap-1.5 text-sm text-admin-text-secondary">
                <li><a href="{{ route('customer.dashboard') }}" class="font-medium transition hover:text-admin-text-primary">{{ __('Dashboard') }}</a></li>
                <li aria-hidden="true" class="text-admin-text-secondary/60">/</li>
                <li><a href="{{ route('customer.scraper.index') }}" class="font-medium transition hover:text-admin-text-primary">{{ __('Lead Scraper') }}</a></li>
                <li aria-hidden="true" class="text-admin-text-secondary/60">/</li>
                <li><a href="{{ route('customer.scraper.jobs') }}" class="font-medium transition hover:text-admin-text-primary">{{ __('Jobs') }}</a></li>
                <li aria-hidden="true" class="text-admin-text-secondary/60">/</li>
                <li class="font-medium text-admin-text-primary">{{ __('Enriched') }}</li>
            </ol>
        </nav>
        <div class="flex flex-wrap items-center gap-3 mt-1">
            <h1 class="text-[22px] font-semibold tracking-tight text-admin-text-primary">{{ Str::limit($job->query, 40) }}</h1>
            <span class="px-2 py-0.5 text-xs font-medium bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-md">{{ __('Enriched') }}</span>
        </div>
    </div>

    <div class="flex items-center gap-3 shrink-0">
        <a href="{{ route('customer.scraper.export-enriched', $job) }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-admin-text-secondary bg-white dark:bg-admin-card border border-gray-200 dark:border-admin-border rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            {{ __('Export CSV') }}
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-admin-card border border-gray-100 dark:border-admin-border rounded-xl overflow-hidden">
        <div class="p-4 border-b border-gray-100 dark:border-admin-border">
            <form method="GET" action="{{ route('customer.scraper.enriched-results', $job) }}" class="flex gap-3">
                <div class="relative flex-1 max-w-sm">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    </span>
                    <input type="search" name="search" value="{{ request('search') }}"
                        placeholder="{{ __('Search by name, email, or title...') }}"
                        class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 dark:border-admin-border rounded-lg bg-white dark:bg-admin-main text-admin-text-primary placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]/30 focus:border-[#1E5FEA]">
                </div>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-[#1E5FEA] hover:bg-blue-700 rounded-lg transition-colors">
                    {{ __('Search') }}
                </button>
                @if(request('search'))
                    <a href="{{ route('customer.scraper.enriched-results', $job) }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-admin-text-secondary bg-white dark:bg-admin-card border border-gray-200 dark:border-admin-border rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                        {{ __('Clear') }}
                    </a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-white/5 text-left">
                        <th class="px-4 py-3 font-medium text-admin-text-secondary">{{ __('Name') }}</th>
                        <th class="px-4 py-3 font-medium text-admin-text-secondary">{{ __('Email') }}</th>
                        <th class="px-4 py-3 font-medium text-admin-text-secondary">{{ __('Title') }}</th>
                        <th class="px-4 py-3 font-medium text-admin-text-secondary">{{ __('Company') }}</th>
                        <th class="px-4 py-3 font-medium text-admin-text-secondary">{{ __('LinkedIn') }}</th>
                        <th class="px-4 py-3 font-medium text-admin-text-secondary">{{ __('Confidence') }}</th>
                        <th class="px-4 py-3 font-medium text-admin-text-secondary">{{ __('Sources') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-admin-border">
                    @forelse($leads as $lead)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                        <td class="px-4 py-3">
                            <div class="font-medium text-admin-text-primary">{{ $lead->name ?? '—' }}</div>
                        </td>
                        <td class="px-4 py-3 text-admin-text-primary">{{ $lead->email ?? '—' }}</td>
                        <td class="px-4 py-3 text-admin-text-primary">{{ $lead->job_title ?? '—' }}</td>
                        <td class="px-4 py-3 text-admin-text-primary">{{ $lead->company_size ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if($lead->linkedin_url)
                                <a href="{{ $lead->linkedin_url }}" target="_blank" class="text-[#1E5FEA] hover:underline">{{ __('View') }}</a>
                            @else
                                <span class="text-admin-text-secondary">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($lead->confidence_score)
                                <div class="flex items-center gap-2">
                                    <div class="w-16 h-1.5 rounded-full bg-gray-200 dark:bg-white/10 overflow-hidden">
                                        <div class="h-full rounded-full bg-green-500" style="width: {{ $lead->confidence_score }}%"></div>
                                    </div>
                                    <span class="text-xs text-admin-text-secondary">{{ $lead->confidence_score }}%</span>
                                </div>
                            @else
                                <span class="text-admin-text-secondary">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if(is_array($lead->enrichment_sources))
                                <div class="flex gap-1 flex-wrap">
                                    @foreach($lead->enrichment_sources as $source)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">{{ ucfirst($source) }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-admin-text-secondary">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="mp-empty">
                                <div class="mp-empty__icon">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11h3.75a2 2 0 0 1 1.6.8l.45.6a4 4 0 0 0 6.4 0l.45-.6a2 2 0 0 1 1.6-.8H21"/><path d="M3 7h18"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                                </div>
                                <p class="mp-empty__title">{{ __('No enriched leads found for this batch.') }}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($leads->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-admin-border">
            {{ $leads->links() }}
        </div>
        @endif
    </div>

    @if($emailLists->isNotEmpty())
    <div class="bg-white dark:bg-admin-card border border-gray-100 dark:border-admin-border rounded-xl p-6">
        <h3 class="text-sm font-semibold text-admin-text-primary mb-3">{{ __('Push Enriched Leads to List') }}</h3>
        <form method="POST" action="{{ route('customer.scraper.push', $job) }}" class="flex items-center gap-3">
            @csrf
            <select name="email_list_id" required
                class="px-3 py-2 text-sm border border-gray-200 dark:border-admin-border rounded-lg bg-white dark:bg-admin-main text-admin-text-primary focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]/30 focus:border-[#1E5FEA]">
                <option value="">{{ __('Select a list...') }}</option>
                @foreach($emailLists as $list)
                    <option value="{{ $list->id }}">{{ $list->name }}</option>
                @endforeach
            </select>
            <button type="submit"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-[#1E5FEA] hover:bg-blue-700 rounded-lg transition-colors shadow-sm">
                {{ __('Push to List') }}
            </button>
        </form>
    </div>
    @endif
</div>
@endsection
