@extends('layouts.customer')

@section('title', __('Pipelines'))

@section('hide-page-header', '1')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Pipelines') }}</h1>
        <a href="{{ route('customer.crm.pipelines.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-[#1E5FEA] px-4 py-2 text-sm font-medium text-white hover:bg-[#174bc0] transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            {{ __('Create Pipeline') }}
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($pipelines as $pipeline)
        <a href="{{ route('customer.crm.pipelines.show', $pipeline) }}" class="rounded-xl border border-gray-200 bg-white p-5 hover:shadow-lg transition-shadow dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $pipeline->name }}</h2>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $pipeline->deals_count }} {{ __('deals') }}</span>
            </div>
            @if($pipeline->description)
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3 line-clamp-2">{{ $pipeline->description }}</p>
            @endif
            <div class="flex gap-2">
                @foreach($pipeline->stages->take(4) as $stage)
                <span class="inline-block h-2 w-2 rounded-full" style="background: {{ $stage->color }}"></span>
                @endforeach
                @if($pipeline->stages->count() > 4)
                <span class="text-xs text-gray-400">+{{ $pipeline->stages->count() - 4 }}</span>
                @endif
            </div>
        </a>
        @empty
        <div class="col-span-full rounded-xl border border-gray-200 bg-white p-8 text-center dark:border-gray-700 dark:bg-gray-800">
            <p class="text-gray-500 dark:text-gray-400">{{ __('No pipelines yet.') }}</p>
            <a href="{{ route('customer.crm.pipelines.create') }}" class="mt-3 inline-block text-sm text-[#1E5FEA] hover:underline">{{ __('Create your first pipeline') }}</a>
        </div>
        @endforelse
    </div>
</div>
@endsection
