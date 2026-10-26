@extends('layouts.admin')

@section('title', __('AI Projects'))
@section('page-title', __('AI Projects'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-sm text-gray-500 dark:text-admin-text-secondary">{{ __('Manage AI-generated pages and projects.') }}</p>
        </div>
        @admincan('admin.pages.edit')
            <x-button href="{{ route('admin.ai-projects.page-ai-builder') }}" variant="primary">
                {{ __('New AI Page') }}
            </x-button>
        @endadmincan
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-card>
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-blue-50 dark:bg-blue-500/10 text-[#1E5FEA]">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-admin-text-secondary">{{ __('Total Projects') }}</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</p>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-amber-50 dark:bg-amber-500/10 text-amber-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-admin-text-secondary">{{ __('Drafts') }}</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['draft']) }}</p>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-green-50 dark:bg-green-500/10 text-green-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-admin-text-secondary">{{ __('Published Pages') }}</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['published']) }}</p>
                </div>
            </div>
        </x-card>
    </div>

    <x-card :padding="false">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Recent AI Projects') }}</h3>
            @admincan('admin.pages.edit')
                <a href="{{ route('admin.ai-projects.page-ai-builder') }}" class="text-sm text-[#1E5FEA] hover:underline">{{ __('Open AI Builder') }}</a>
            @endadmincan
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Project') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Mode') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Last Updated') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($sessions as $session)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $session->preview_title ?? $session->page_title ?? $session->session_id }}
                                </div>
                                @if($session->page_slug)
                                    <div class="text-xs text-gray-500 dark:text-admin-text-secondary">{{ $session->page_slug }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $session->page_status === 'publish' ? 'bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-400' : 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-400' }}">
                                    {{ ucfirst($session->page_status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-admin-text-secondary">
                                {{ strtoupper($session->preview_mode) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-admin-text-secondary">
                                {{ $session->updated_at?->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                @admincan('admin.pages.edit')
                                    <a href="{{ route('admin.ai-projects.page-ai-builder.show', $session->session_id) }}" class="text-[#1E5FEA] hover:underline">{{ __('Edit') }}</a>
                                @endadmincan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-admin-text-secondary">
                                {{ __('No AI projects yet.') }}
                                @admincan('admin.pages.edit')
                                    <a href="{{ route('admin.ai-projects.page-ai-builder') }}" class="text-[#1E5FEA] hover:underline ml-1">{{ __('Create one') }}</a>
                                @endadmincan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sessions->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $sessions->links() }}
            </div>
        @endif
    </x-card>
</div>
@endsection
