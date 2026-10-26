@extends('layouts.customer')

@section('title', 'Tracking Domains')
@section('page-title', 'Tracking Domains')

@section('page-actions')
    @customercan('domains.tracking_domains.permissions.can_create_tracking_domains')
        <div class="mp-toolbar w-full justify-end">
            <x-button href="{{ route('customer.tracking-domains.create') }}" variant="primary" class="w-full sm:w-auto">Add Domain</x-button>
        </div>
    @endcustomercan
@endsection

@section('content')
<div class="space-y-6">
    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="mp-table">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Domain</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Verified</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($trackingDomains as $domain)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $domain->domain }}</td>
                            <td class="px-6 py-2 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $domain->status === 'verified' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst($domain->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $domain->verified_at ? $domain->verified_at->format('M d, Y') : 'Not verified' }}
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <x-button href="{{ route('customer.tracking-domains.show', $domain) }}" variant="table" size="action" :pill="true" class="p-2" title="View" aria-label="View"><x-lucide name="eye" class="h-4 w-4" /><span class="sr-only">View</span></x-button>
                                    @if($domain->customer_id)
                                        @customercan('domains.tracking_domains.permissions.can_edit_tracking_domains')
                                            <x-button href="{{ route('customer.tracking-domains.edit', $domain) }}" variant="table" size="action" :pill="true" class="p-2" title="Edit" aria-label="Edit"><x-lucide name="pencil" class="h-4 w-4" /><span class="sr-only">Edit</span></x-button>
                                        @endcustomercan

                                        @customercan('domains.tracking_domains.permissions.can_delete_tracking_domains')
                                            <form method="POST" action="{{ route('customer.tracking-domains.destroy', $domain) }}" class="inline" onsubmit="return confirm('Are you sure?');">
                                                @csrf
                                                @method('DELETE')
                                                <x-button type="submit" variant="table-danger" size="action" :pill="true" class="p-2" title="Delete" aria-label="Delete"><x-lucide name="trash-2" class="h-4 w-4" /><span class="sr-only">Delete</span></x-button>
                                            </form>
                                        @endcustomercan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="mp-empty">
                                    <div class="mp-empty__icon">
                                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11h3.75a2 2 0 0 1 1.6.8l.45.6a4 4 0 0 0 6.4 0l.45-.6a2 2 0 0 1 1.6-.8H21"/><path d="M3 7h18"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                                    </div>
                                    <p class="mp-empty__title">No tracking domains found</p>
                                    <p class="mp-empty__desc">
                                        @customercan('domains.tracking_domains.permissions.can_create_tracking_domains')
                                            <a href="{{ route('customer.tracking-domains.create') }}" class="font-medium" style="color: var(--tw-brand)">Add your first domain</a>
                                        @else
                                            Tracking domains will appear here once they are added.
                                        @endcustomercan
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($trackingDomains->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">{{ $trackingDomains->links() }}</div>
        @endif
    </x-card>
</div>
@endsection
