@extends('layouts.customer')

@section('title', $pipeline->name)

@section('hide-page-header', '1')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('customer.crm.pipelines.index') }}" class="text-sm text-gray-500 hover:text-[#1E5FEA] transition-colors">&larr; {{ __('Back to pipelines') }}</a>
            <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $pipeline->name }}</h1>
        </div>
        <a href="{{ route('customer.crm.deals.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-[#1E5FEA] px-4 py-2 text-sm font-medium text-white hover:bg-[#174bc0] transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            {{ __('Add Deal') }}
        </a>
    </div>

    <div class="flex gap-4 overflow-x-auto pb-4" style="min-height: 400px;">
        @foreach($pipeline->stages as $stage)
        <div class="flex-shrink-0 w-80 rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50">
            <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <span class="h-3 w-3 rounded-full" style="background: {{ $stage->color }}"></span>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $stage->name }}</h3>
                <span class="ml-auto rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $stage->deals->count() }}</span>
            </div>
            <div class="p-3 space-y-3">
                @foreach($stage->deals as $deal)
                <a href="{{ route('customer.crm.deals.show', $deal) }}" class="block rounded-lg border border-gray-200 bg-white p-4 hover:shadow-md transition-shadow dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{ $deal->title }}</h4>
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $deal->status === 'won' ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400' : ($deal->status === 'lost' ? 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400') }}">{{ ucfirst($deal->status) }}</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ $deal->contact?->full_name }}</p>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">${{ number_format($deal->value, 2) }}</span>
                        <span class="text-xs text-gray-400">{{ $deal->expected_close_date?->format('M d') ?? '-' }}</span>
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        @foreach($stage->pipeline->stages as $s)
                        @if($s->id !== $stage->id && $deal->status === 'open')
                        <form method="POST" action="{{ route('customer.crm.deals.move-stage', $deal) }}" class="inline">
                            @csrf
                            <input type="hidden" name="stage_id" value="{{ $s->id }}">
                            <button type="submit" class="text-xs rounded-full border border-gray-200 px-2 py-0.5 text-gray-500 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700 transition-colors">{{ $s->name }}</button>
                        </form>
                        @endif
                        @endforeach
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
