@extends('layouts.customer')

@section('title', __('Lead Lookup & Enrichment'))
@section('page-title', __('Lead Lookup & Enrichment'))

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="mb-6">
        <nav aria-label="Breadcrumb" class="mb-2">
            <ol class="flex flex-wrap items-center gap-1.5 text-sm text-admin-text-secondary">
                <li><a href="{{ route('customer.dashboard') }}" class="font-medium transition hover:text-admin-text-primary">{{ __('Dashboard') }}</a></li>
                <li aria-hidden="true" class="text-admin-text-secondary/60">/</li>
                <li><a href="{{ route('customer.scraper.index') }}" class="font-medium transition hover:text-admin-text-primary">{{ __('Lead Scraper') }}</a></li>
                <li aria-hidden="true" class="text-admin-text-secondary/60">/</li>
                <li class="font-medium text-admin-text-primary">{{ __('Lookup') }}</li>
            </ol>
        </nav>
        <h1 class="text-[22px] font-semibold tracking-tight text-admin-text-primary">{{ __('Lead Lookup & Enrichment') }}</h1>
        <p class="text-sm text-admin-text-secondary mt-1">{{ __('Search any person by name and company to enrich their B2B profile.') }}</p>
    </div>

    <div class="bg-white dark:bg-admin-card border border-gray-100 dark:border-admin-border rounded-xl p-6">
        <form method="POST" action="{{ route('customer.scraper.lookup') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-admin-text-primary mb-1.5">{{ __('Full Name') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                        class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-admin-border rounded-lg bg-white dark:bg-admin-main text-admin-text-primary placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]/30 focus:border-[#1E5FEA]"
                        placeholder="e.g. John Smith">
                    @error('name')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="domain" class="block text-sm font-medium text-admin-text-primary mb-1.5">{{ __('Company Domain') }}</label>
                    <input type="text" name="domain" id="domain" value="{{ old('domain') }}"
                        class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-admin-border rounded-lg bg-white dark:bg-admin-main text-admin-text-primary placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]/30 focus:border-[#1E5FEA]"
                        placeholder="e.g. acme.com">
                    @error('domain')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-admin-text-primary mb-1.5">{{ __('Known Email (optional)') }}</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}"
                    class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-admin-border rounded-lg bg-white dark:bg-admin-main text-admin-text-primary placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]/30 focus:border-[#1E5FEA]"
                    placeholder="e.g. john@acme.com">
                @error('email')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-[#1E5FEA] hover:bg-blue-700 rounded-lg transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    {{ __('Enrich Lead') }}
                </button>
            </div>
        </form>
    </div>

    @if($result && $result->enrichment_status === 'enriched')
    <div class="bg-white dark:bg-admin-card border border-gray-100 dark:border-admin-border rounded-xl p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-green-50 dark:bg-green-900/30 flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-admin-text-primary">{{ __('Enriched Profile') }}</h2>
                <p class="text-sm text-admin-text-secondary">{{ __('Confidence score:') }} {{ $result->confidence_score ?? 0 }}/100</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-admin-text-secondary w-28 shrink-0">{{ __('Name') }}</span>
                    <span class="text-sm font-medium text-admin-text-primary">{{ $result->name ?? '—' }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-admin-text-secondary w-28 shrink-0">{{ __('Email') }}</span>
                    <span class="text-sm font-medium text-admin-text-primary">{{ $result->email ?? '—' }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-admin-text-secondary w-28 shrink-0">{{ __('Phone') }}</span>
                    <span class="text-sm font-medium text-admin-text-primary">{{ $result->phone ?? '—' }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-admin-text-secondary w-28 shrink-0">{{ __('Website') }}</span>
                    <span class="text-sm font-medium text-admin-text-primary">{{ $result->website ?? '—' }}</span>
                </div>
            </div>
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-admin-text-secondary w-28 shrink-0">{{ __('Title') }}</span>
                    <span class="text-sm font-medium text-admin-text-primary">{{ $result->job_title ?? '—' }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-admin-text-secondary w-28 shrink-0">{{ __('LinkedIn') }}</span>
                    @if($result->linkedin_url)
                        <a href="{{ $result->linkedin_url }}" target="_blank" class="text-sm font-medium text-[#1E5FEA] hover:underline">{{ Str::limit($result->linkedin_url, 40) }}</a>
                    @else
                        <span class="text-sm font-medium text-admin-text-primary">—</span>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-admin-text-secondary w-28 shrink-0">{{ __('Company Size') }}</span>
                    <span class="text-sm font-medium text-admin-text-primary">{{ $result->company_size ?? '—' }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-admin-text-secondary w-28 shrink-0">{{ __('Industry') }}</span>
                    <span class="text-sm font-medium text-admin-text-primary">{{ $result->industry ?? '—' }}</span>
                </div>
            </div>
        </div>

        @if(is_array($result->enrichment_sources) && count($result->enrichment_sources) > 0)
        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-admin-border">
            <span class="text-xs text-admin-text-secondary">{{ __('Sources:') }}</span>
            <div class="flex gap-2 mt-1">
                @foreach($result->enrichment_sources as $source)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">{{ ucfirst($source) }}</span>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @elseif($result && $result->enrichment_status === 'failed')
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-6 text-center">
        <p class="text-sm text-red-700 dark:text-red-300">{{ __('No enrichment data found for this lead. Try a different name or company domain.') }}</p>
    </div>
    @endif
</div>
@endsection
