@extends('layouts.admin')

@section('title', __('Layout Builder'))
@section('page-title', __('Layout Builder'))

@section('content')
@php
$activeTab = request()->query('tab', 'header');
$megaMenuBuilderEnabled = (bool) config('mailpurse.features.mega_menu_builder');
$allowedTabs = ['header', 'footer'];
if ($megaMenuBuilderEnabled) {
    $allowedTabs[] = 'megamenu';
}
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'header';
}
$layouts = match ($activeTab) {
    'footer' => $footers,
    'megamenu' => $megaMenus,
    default => $headers,
};
$newLabel = match ($activeTab) {
    'footer' => __('New Footer'),
    'megamenu' => __('New Megamenu'),
    default => __('New Header'),
};
@endphp
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-1 rounded-lg border border-admin-border bg-white dark:bg-gray-800 p-1">
            <a href="{{ route('admin.layouts.index', ['tab' => 'header']) }}" class="px-3 py-1.5 text-sm font-medium rounded-md {{ $activeTab === 'header' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                {{ __('Header Builder') }}
            </a>
            <a href="{{ route('admin.layouts.index', ['tab' => 'footer']) }}" class="px-3 py-1.5 text-sm font-medium rounded-md {{ $activeTab === 'footer' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                {{ __('Footer Builder') }}
            </a>
            @if($megaMenuBuilderEnabled)
            <a href="{{ route('admin.layouts.index', ['tab' => 'megamenu']) }}" class="px-3 py-1.5 text-sm font-medium rounded-md {{ $activeTab === 'megamenu' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                {{ __('Megamenu Builder') }}
            </a>
            @endif
        </div>
        <x-button href="{{ route('admin.layouts.create', ['type' => $activeTab]) }}" variant="primary">{{ $newLabel }}</x-button>
    </div>

    @if($layouts->count() > 0)
        <x-card :padding="false">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Conditions') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($layouts as $layout)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $layout->name }}</td>
                            <td class="px-6 py-2 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $layout->status === 'publish' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $layout->status === 'publish' ? __('Published') : __('Draft') }}
                                </span>
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                @php
                                    $conditions = is_array($layout->display_conditions) ? $layout->display_conditions : [];
                                    $allPages = ($conditions['all_pages'] ?? false) === true;
                                @endphp
                                {{ $allPages ? __('All pages') : __('Custom') }}
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <x-button href="{{ route('admin.layouts.builder', $layout) }}" variant="table" size="action" :pill="true" class="p-2" title="{{ __('Builder') }}" aria-label="{{ __('Builder') }}"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.106-3.105c.32-.322.863-.22.983.218a6 6 0 0 1-8.259 7.057l-7.91 7.91a1 1 0 0 1-2.999-3l7.91-7.91a6 6 0 0 1 7.057-8.259c.438.12.54.662.219.984z"/></svg><span class="sr-only">{{ __('Builder') }}</span></x-button>
                                    <x-button href="{{ route('admin.layouts.edit', $layout) }}" variant="table" size="action" :pill="true" class="p-2" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}"><x-lucide name="pencil" class="h-4 w-4" /><span class="sr-only">{{ __('Edit') }}</span></x-button>
                                    <form method="POST" action="{{ route('admin.layouts.destroy', $layout) }}" class="inline" onsubmit="return confirm(@json(__('Delete this layout?')));">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" variant="table-danger" size="action" :pill="true" class="p-2" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}"><x-lucide name="trash-2" class="h-4 w-4" /><span class="sr-only">{{ __('Delete') }}</span></x-button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </x-card>
    @else
        <x-card class="text-center text-sm text-gray-500 dark:text-gray-400 py-6">
            {{ __('No layouts created yet.') }}
        </x-card>
    @endif
</div>
@endsection
