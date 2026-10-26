@extends('layouts.customer')

@section('title', 'Campaigns')
@section('page-title', 'Campaigns')

@section('page-title-details')
    Create, schedule and track your email campaigns.
@endsection

@section('page-actions')
    @php
        $statusFilters = [
            '' => 'All',
            'draft' => 'Draft',
            'scheduled' => 'Scheduled',
            'running' => 'Running',
            'completed' => 'Completed',
        ];
    @endphp
    <div class="mp-toolbar justify-end">
        <x-ui.segmented label="Status" class="flex-wrap">
            @foreach($statusFilters as $value => $label)
                <x-ui.segment
                    href="{{ route('customer.campaigns.index', array_filter(['status' => $value ?: null, 'search' => request('search')])) }}"
                    :active="(string) request('status', '') === (string) $value"
                >{{ $label }}</x-ui.segment>
            @endforeach
        </x-ui.segmented>

        <form method="GET" action="{{ route('customer.campaigns.index') }}" class="w-40 sm:w-48">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <x-ui.input icon="search" name="search" :value="request('search')" placeholder="Search" aria-label="Search campaigns" />
        </form>

        @customercan('campaigns.permissions.can_create_campaigns')
            <a href="{{ route('customer.campaigns.create', ['fresh' => 1]) }}" class="mp-btn mp-btn--md mp-btn--primary" data-track="campaign.create">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create campaign
            </a>
        @endcustomercan
    </div>
@endsection

@section('content')
<div class="space-y-6">
    <div class="mp-card">
        <div class="mp-table-wrap">
            <table class="mp-table">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>List</th>
                        <th>Status</th>
                        <th class="is-num">Recipients</th>
                        <th>Performance</th>
                        <th>Created</th>
                        <th class="is-num">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $campaign)
                        @php
                            $statusTones = [
                                'draft' => 'neutral',
                                'queued' => 'info',
                                'scheduled' => 'warning',
                                'running' => 'info',
                                'completed' => 'success',
                                'paused' => 'warning',
                                'stopped' => 'neutral',
                                'failed' => 'danger',
                            ];
                            $statusTone = $statusTones[$campaign->status] ?? 'neutral';
                            $scheduledFor = $campaign->scheduled_at ?? $campaign->send_at;
                            $timezone = data_get($campaign->settings, 'sending_schedule.timezone')
                                ?: (auth('customer')->user()->timezone ?? config('app.timezone', 'UTC'));
                        @endphp
                        <tr class="cursor-pointer" onclick="window.location='{{ route('customer.campaigns.show', $campaign) }}'">
                            <td>
                                <div class="font-medium text-admin-text-primary">{{ $campaign->name }}</div>
                                <div class="text-xs text-admin-text-secondary" title="{{ $campaign->subject }}">
                                    {{ \Illuminate\Support\Str::words($campaign->subject ?? '', 6, ' …') }}
                                </div>
                            </td>
                            <td class="text-admin-text-secondary">{{ $campaign->emailList->name ?? 'No List' }}</td>
                            <td>
                                <x-status-badge :tone="$statusTone" :dot="$campaign->status === 'running'">
                                    {{ ucfirst($campaign->status) }}
                                </x-status-badge>
                                @if($scheduledFor)
                                    <div class="mt-1 text-xs text-admin-text-secondary">
                                        {{ $scheduledFor->timezone($timezone)->format('M d, Y h:i A') }}
                                    </div>
                                @endif
                            </td>
                            <td class="is-num">
                                {{ number_format($campaign->sent_count ?? 0) }} / {{ number_format($campaign->expected_recipients ?? $campaign->total_recipients ?? 0) }}
                            </td>
                            <td>
                                @if($campaign->sent_count > 0)
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                                        <span class="text-admin-text-secondary">Opens <span class="font-medium text-admin-text-primary">{{ number_format($campaign->open_rate ?? 0, 1) }}%</span></span>
                                        <span class="text-admin-text-secondary">Clicks <span class="font-medium text-admin-text-primary">{{ number_format($campaign->click_rate ?? 0, 1) }}%</span></span>
                                        <span class="text-admin-text-secondary">Replies <span class="font-medium text-admin-text-primary">{{ number_format($campaign->replied_count ?? 0) }}</span></span>
                                    </div>
                                @else
                                    <span class="text-admin-text-secondary">—</span>
                                @endif
                            </td>
                            <td class="text-admin-text-secondary">{{ $campaign->created_at->format('M d, Y') }}</td>
                            <td class="is-num" onclick="event.stopPropagation()">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('customer.campaigns.show', $campaign) }}" class="mp-icon-btn" style="width:2rem;height:2rem" title="View" aria-label="View">
                                        <x-lucide name="eye" class="h-4 w-4" />
                                    </a>
                                    @customercan('campaigns.permissions.can_edit_campaigns')
                                        <a href="{{ route('customer.campaigns.edit', $campaign) }}" class="mp-icon-btn" style="width:2rem;height:2rem" title="Edit" aria-label="Edit">
                                            <x-lucide name="pencil" class="h-4 w-4" />
                                        </a>
                                    @endcustomercan
                                    @customercan('campaigns.permissions.can_create_campaigns')
                                        <form method="POST" action="{{ route('customer.campaigns.duplicate', $campaign) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="mp-icon-btn" style="width:2rem;height:2rem" title="Duplicate" aria-label="Duplicate">
                                                <x-lucide name="copy" class="h-4 w-4" />
                                            </button>
                                        </form>
                                    @endcustomercan
                                    @customercan('campaigns.permissions.can_delete_campaigns')
                                        <form method="POST" action="{{ route('customer.campaigns.destroy', $campaign) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this campaign?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="mp-icon-btn hover:text-red-600 dark:hover:text-red-400" style="width:2rem;height:2rem" title="Delete" aria-label="Delete">
                                                <x-lucide name="trash-2" class="h-4 w-4" />
                                            </button>
                                        </form>
                                    @endcustomercan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="mp-empty">
                                    <div class="mp-empty__icon">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 6a13 13 0 0 0 8.4-2.8A1 1 0 0 1 21 4v12a1 1 0 0 1-1.6.8A13 13 0 0 0 11 14H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 14a12 12 0 0 0 2.4 7.2 2 2 0 0 0 3.2-2.4A8 8 0 0 1 10 14M8 6v8"/></svg>
                                    </div>
                                    <p class="mp-empty__title">No campaigns found</p>
                                    <p class="mp-empty__desc">
                                        @customercan('campaigns.permissions.can_create_campaigns')
                                            <a href="{{ route('customer.campaigns.create', ['fresh' => 1]) }}" class="font-medium" style="color: var(--tw-brand)">Create your first campaign</a>
                                        @else
                                            Campaigns will appear here once they are created.
                                        @endcustomercan
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($campaigns->hasPages())
            <div class="mp-card__footer">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
