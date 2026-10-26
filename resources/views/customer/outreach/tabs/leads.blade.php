@php
    $statusOptions = ['pending', 'sent', 'opened', 'clicked', 'replied', 'bounced', 'unsubscribed'];
@endphp

{{-- Toolbar --}}
<div class="flex flex-col sm:flex-row sm:items-center gap-3">
    <form method="GET" action="{{ route('customer.outreach.campaigns.show', $campaign) }}" class="flex-1 flex items-center gap-3">
        <input type="hidden" name="tab" value="leads">
        <div class="relative flex-1 max-w-sm" x-data="{ searchTimeout: null }">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input type="text" name="search" value="{{ $searchQuery }}" placeholder="{{ __('Search leads by name, email, or company') }}"
                class="pl-10 pr-4 py-2 w-full text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-900 dark:text-admin-text-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] placeholder-gray-400"
                @input.debounce.300ms="$el.form.submit()">
        </div>
        <div class="relative">
            <select name="status" onchange="this.form.submit()"
                class="appearance-none pl-3 pr-8 py-2 text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-700 dark:text-admin-text-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
                <option value="">{{ __('All Statuses') }}</option>
                @foreach($statusOptions as $s)
                    <option value="{{ $s }}" {{ $statusFilter === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
    </form>
    <div class="flex items-center gap-2 flex-shrink-0">
        <a href="#" class="inline-flex items-center gap-2 px-3.5 py-2 text-sm font-medium text-gray-700 dark:text-admin-text-primary border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 hover:bg-gray-50 dark:hover:bg-white/10 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            {{ __('Export') }}
        </a>
        <button type="button" x-data @click="$dispatch('open-manage-fields')"
            class="inline-flex items-center gap-2 px-3.5 py-2 text-sm font-medium text-gray-700 dark:text-admin-text-primary border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 hover:bg-gray-50 dark:hover:bg-white/10 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
            {{ __('Custom Fields') }}
        </button>
        <button type="button" x-data @click="$dispatch('open-add-leads')"
            class="inline-flex items-center gap-2 px-3.5 py-2 text-sm font-semibold text-white hover:opacity-90 rounded-lg transition-colors shadow-sm bg-[#1E5FEA]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            {{ __('Add Leads') }}
        </button>
    </div>
</div>

{{-- Leads Table --}}
<div
    class="bg-white dark:bg-admin-card border border-gray-100 dark:border-admin-border rounded-xl overflow-hidden"
    x-data="leadsData()"
    x-init="initLeadsData()"
    @open-add-leads.window="showAddLeads = true"
    @open-manage-fields.window="openManageFields()"
>
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-100 dark:border-admin-border bg-gray-50/50 dark:bg-white/2">
                <th class="w-10 px-4 py-3"><input type="checkbox" x-ref="selectAll" @click="toggleAll()" class="rounded border-gray-300"></th>
                <th class="text-left text-xs font-medium text-gray-500 dark:text-admin-text-secondary px-4 py-3">{{ __('Lead Info') }}</th>
                <th class="text-left text-xs font-medium text-gray-500 dark:text-admin-text-secondary px-4 py-3">{{ __('Company') }}</th>
                <th class="text-left text-xs font-medium text-gray-500 dark:text-admin-text-secondary px-4 py-3">{{ __('At Step') }}</th>
                <th class="text-left text-xs font-medium text-gray-500 dark:text-admin-text-secondary px-4 py-3">{{ __('Next Step In') }}</th>
                <th class="text-left text-xs font-medium text-gray-500 dark:text-admin-text-secondary px-4 py-3">{{ __('Status') }}</th>
                <th class="text-left text-xs font-medium text-gray-500 dark:text-admin-text-secondary px-4 py-3">{{ __('Last Activity') }}</th>
                <th class="text-right text-xs font-medium text-gray-500 dark:text-admin-text-secondary px-4 py-3">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50 dark:divide-admin-border">
            @forelse($leads as $lead)
                @php
                    $stepTotal = max(1, (int) ($totalSequenceSteps ?? 0));
                    $stepNumber = min($stepTotal, max(1, ((int) ($lead->current_step ?? 0)) + 1));

                    if ($lead->sequence_completed_at) {
                        $stepNumber = $stepTotal;
                    }

                    $nextStepIn = $lead->next_send_at
                        ? $lead->next_send_at->diffForHumans()
                        : __('—');
                @endphp
                <tr class="hover:bg-gray-50/40 dark:hover:bg-white/2 transition-colors">
                    <td class="px-4 py-3.5"><input type="checkbox" name="lead_ids[]" value="{{ $lead->id }}" @change="syncSelectAll()" class="js-lead-checkbox rounded border-gray-300"></td>
                    <td class="px-4 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-semibold flex-shrink-0 bg-[#1E5FEA]">
                                {{ $lead->initials }}
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-gray-900 dark:text-admin-text-primary truncate">{{ $lead->full_name ?: '—' }}</p>
                                <p class="text-xs text-gray-400 dark:text-admin-text-secondary truncate">{{ $lead->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3.5 text-gray-600 dark:text-admin-text-secondary">
                        <div class="mp-line-clamp-3">{{ $lead->company ?: '—' }}</div>
                    </td>
                    <td class="px-4 py-3.5 text-xs font-medium text-gray-600 dark:text-admin-text-secondary">{{ $stepNumber }}/{{ $stepTotal }}</td>
                    <td class="px-4 py-3.5 text-xs text-gray-500 dark:text-admin-text-secondary">{{ $nextStepIn }}</td>
                    <td class="px-4 py-3.5">
                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full border {{ $lead->status_color }}">
                            {{ ucfirst($lead->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3.5 text-xs text-gray-500 dark:text-admin-text-secondary">
                        {{ $lead->last_activity_at ? $lead->last_activity_at->diffForHumans() : __('—') }}
                    </td>
                    <td class="px-4 py-3.5 text-right">
                        <div class="flex items-center justify-end gap-1">
                            @php
                                $leadPayload = [
                                    'id' => $lead->id,
                                    'email' => $lead->email,
                                    'first_name' => $lead->first_name,
                                    'last_name' => $lead->last_name,
                                    'company' => $lead->company,
                                    'status' => $lead->status,
                                    'meta' => is_array($lead->meta) ? $lead->meta : [],
                                ];
                                $leadEditUrl = route('customer.outreach.campaigns.leads.edit', [$campaign, $lead]);
                            @endphp
                            <button type="button"
                                @click="openEdit({{ \Illuminate\Support\Js::from($leadPayload) }}, {{ \Illuminate\Support\Js::from($leadEditUrl) }})"
                                class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-admin-text-primary rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 transition-colors"
                                title="{{ __('Edit') }}"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </button>
                            <form method="POST" action="{{ route('customer.outreach.campaigns.leads.destroy', [$campaign, $lead]) }}" onsubmit="return confirm('{{ __('Remove this lead?') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <div class="mp-empty">
                            <div class="mp-empty__icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#bbb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-star-icon lucide-user-star w-5 h-5"><path d="M16.051 12.616a1 1 0 0 1 1.909.024l.737 1.452a1 1 0 0 0 .737.535l1.634.256a1 1 0 0 1 .588 1.806l-1.172 1.168a1 1 0 0 0-.282.866l.259 1.613a1 1 0 0 1-1.541 1.134l-1.465-.75a1 1 0 0 0-.912 0l-1.465.75a1 1 0 0 1-1.539-1.133l.258-1.613a1 1 0 0 0-.282-.866l-1.156-1.153a1 1 0 0 1 .572-1.822l1.633-.256a1 1 0 0 0 .737-.535z"/><path d="M8 15H7a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/></svg>
                            </div>
                            <p class="mp-empty__title">{{ __('No leads yet. Click "Add Leads" to import.') }}</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Pagination --}}
    @if($leads->hasPages())
        <div class="px-5 py-3.5 border-t border-gray-100 dark:border-admin-border flex items-center justify-between">
            <p class="text-xs text-gray-500 dark:text-admin-text-secondary">
                {{ __('Showing') }} <strong>{{ $leads->firstItem() }}</strong> {{ __('to') }} <strong>{{ $leads->lastItem() }}</strong> {{ __('of') }} <strong>{{ number_format($leads->total()) }}</strong> {{ __('leads') }}
            </p>
            <div class="flex items-center gap-1">
                @if($leads->onFirstPage())
                    <span class="w-8 h-8 flex items-center justify-center text-gray-300 dark:text-admin-text-secondary rounded-lg border border-gray-200 dark:border-admin-border cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </span>
                @else
                    <a href="{{ $leads->previousPageUrl() }}" class="w-8 h-8 flex items-center justify-center text-gray-500 dark:text-admin-text-secondary hover:text-[#1E5FEA] rounded-lg border border-gray-200 dark:border-admin-border hover:border-[#1E5FEA] transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                @endif
                @foreach($leads->getUrlRange(max(1, $leads->currentPage() - 2), min($leads->lastPage(), $leads->currentPage() + 2)) as $page => $url)
                    <a href="{{ $url }}" class="w-8 h-8 flex items-center justify-center text-xs rounded-lg border transition-colors
                        {{ $page == $leads->currentPage() ? 'border-[#1E5FEA] bg-[#1E5FEA] text-white font-semibold' : 'border-gray-200 dark:border-admin-border text-gray-600 dark:text-admin-text-secondary hover:border-[#1E5FEA] hover:text-[#1E5FEA]' }}">
                        {{ $page }}
                    </a>
                @endforeach
                @if($leads->hasMorePages())
                    <a href="{{ $leads->nextPageUrl() }}" class="w-8 h-8 flex items-center justify-center text-gray-500 dark:text-admin-text-secondary hover:text-[#1E5FEA] rounded-lg border border-gray-200 dark:border-admin-border hover:border-[#1E5FEA] transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @endif
            </div>
        </div>
    @endif

    {{-- Add Leads Modal --}}
    <div x-cloak x-show="showAddLeads"
        x-data="{
            source: 'form',
            rows: [{ email: '', first_name: '', last_name: '', company: '', meta: {} }],
            addRow() { this.rows.push({ email: '', first_name: '', last_name: '', company: '', meta: {} }); },
            removeRow(i) { if (this.rows.length > 1) this.rows.splice(i, 1); else this.rows[0] = { email: '', first_name: '', last_name: '', company: '', meta: {} }; }
        }"
        x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="showAddLeads = false"
    >
        <div x-show="showAddLeads"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="bg-white dark:bg-admin-card rounded-2xl shadow-xl w-full max-w-2xl p-6 space-y-5 max-h-[90vh] overflow-y-auto"
        >
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('Import Leads') }}</h2>
                    <p class="text-xs text-gray-500 dark:text-admin-text-secondary mt-0.5">{{ __('Choose how you want to add leads to this campaign.') }}</p>
                </div>
                <button type="button" @click="showAddLeads = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('customer.outreach.campaigns.leads.update', $campaign) }}" id="save-form-leads" class="space-y-5" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="leads_source" :value="source">

                {{-- Source toggle (styled radio cards) --}}
                <div class="grid grid-cols-2 gap-3">
                    <label
                            :class="source === 'list'
                            ? 'border-[#1E5FEA] ring-[#1E5FEA]/20 bg-[rgba(30,95,234,0.05)]'
                            : 'border-gray-200 dark:border-admin-border hover:border-gray-300 dark:hover:border-white/20 bg-white dark:bg-white/5'"
                        class="relative flex items-start gap-2 p-3 rounded-xl border cursor-pointer transition-all"
                    >
                        <input type="radio" value="list" x-model="source" class="sr-only">
                        <div class="flex-shrink-0 mt-0.5">
                            <div :class="source === 'list' ? 'text-white bg-[#1E5FEA]' : 'bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-admin-text-secondary'" class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-contact-icon lucide-contact"><path d="M16 2v2"/><path d="M7 22v-2a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2"/><path d="M8 2v2"/><circle cx="12" cy="11" r="3"/><rect x="3" y="4" width="18" height="18" rx="2"/></svg>
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('Email List') }}</p>
                                <span :class="source === 'list' ? 'border-[#1E5FEA] bg-[#1E5FEA]' : 'border-gray-300 dark:border-admin-border'" class="w-3.5 h-3.5 rounded-full border-2 flex items-center justify-center transition-colors">
                                    <span x-show="source === 'list'" class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                </span>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-admin-text-secondary mt-0.5">{{ __('Pick existing list.') }}</p>
                        </div>
                    </label>

                    <label
                        :class="source === 'form'
                            ? 'border-[#1E5FEA] ring-[#1E5FEA]/20 bg-[rgba(30,95,234,0.05)]'
                            : 'border-gray-200 dark:border-admin-border hover:border-gray-300 dark:hover:border-white/20 bg-white dark:bg-white/5'"
                        class="relative flex items-start gap-2 p-3 rounded-xl border cursor-pointer transition-all"
                    >
                        <input type="radio" value="form" x-model="source" class="sr-only">
                        <div class="flex-shrink-0 mt-0.5">
                            <div :class="source === 'form' ? 'text-white bg-[#1E5FEA]' : 'bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-admin-text-secondary'" class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('Import Form') }}</p>
                                <span :class="source === 'form' ? 'border-[#1E5FEA] bg-[#1E5FEA]' : 'border-gray-300 dark:border-admin-border'" class="w-3.5 h-3.5 rounded-full border-2 flex items-center justify-center transition-colors">
                                    <span x-show="source === 'form'" class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                </span>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-admin-text-secondary mt-0.5">{{ __('Bulk add manually.') }}</p>
                        </div>
                    </label>

                    <label
                        :class="source === 'paste'
                            ? 'border-[#1E5FEA] ring-[#1E5FEA]/20 bg-[rgba(30,95,234,0.05)]'
                            : 'border-gray-200 dark:border-admin-border hover:border-gray-300 dark:hover:border-white/20 bg-white dark:bg-white/5'"
                        class="relative flex items-start gap-2 p-3 rounded-xl border cursor-pointer transition-all"
                    >
                        <input type="radio" value="paste" x-model="source" class="sr-only">
                        <div class="flex-shrink-0 mt-0.5">
                            <div :class="source === 'paste' ? 'text-white bg-[#1E5FEA]' : 'bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-admin-text-secondary'" class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('Manual Paste') }}</p>
                                <span :class="source === 'paste' ? 'border-[#1E5FEA] bg-[#1E5FEA]' : 'border-gray-300 dark:border-admin-border'" class="w-3.5 h-3.5 rounded-full border-2 flex items-center justify-center transition-colors">
                                    <span x-show="source === 'paste'" class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                </span>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-admin-text-secondary mt-0.5">{{ __('Paste CSV data.') }}</p>
                        </div>
                    </label>

                    <label
                        :class="source === 'file'
                            ? 'border-[#1E5FEA] ring-[#1E5FEA]/20 bg-[rgba(30,95,234,0.05)]'
                            : 'border-gray-200 dark:border-admin-border hover:border-gray-300 dark:hover:border-white/20 bg-white dark:bg-white/5'"
                        class="relative flex items-start gap-2 p-3 rounded-xl border cursor-pointer transition-all"
                    >
                        <input type="radio" value="file" x-model="source" class="sr-only">
                        <div class="flex-shrink-0 mt-0.5">
                            <div :class="source === 'file' ? 'text-white bg-[#1E5FEA]' : 'bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-admin-text-secondary'" class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('CSV/XLS Import') }}</p>
                                <span :class="source === 'file' ? 'border-[#1E5FEA] bg-[#1E5FEA]' : 'border-gray-300 dark:border-admin-border'" class="w-3.5 h-3.5 rounded-full border-2 flex items-center justify-center transition-colors">
                                    <span x-show="source === 'file'" class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                </span>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-admin-text-secondary mt-0.5">{{ __('Upload file.') }}</p>
                        </div>
                    </label>
                </div>

                {{-- Content container with fixed height to prevent jumping --}}
                <div class="min-h-[200px]">
                    {{-- From Email List --}}
                    <div x-show="source === 'list'" class="space-y-3">
                        <div class="space-y-2">
                            <label class="block text-xs font-medium text-gray-700 dark:text-admin-text-secondary">{{ __('Select List or Segment') }}</label>
                            @if(($emailLists ?? collect())->isEmpty())
                                <div class="px-3 py-3 text-xs text-gray-500 dark:text-admin-text-secondary border border-dashed border-gray-200 dark:border-admin-border rounded-lg bg-gray-50/50 dark:bg-white/2">
                                    {{ __('You have no email lists yet. Create one first or use the Import Form option.') }}
                                </div>
                            @else
                                <select name="import_source" x-model="selectedImportSource"
                                    class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-900 dark:text-admin-text-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
                                    <option value="">{{ __('Choose a list or segment…') }}</option>
                                    <optgroup label="{{ __('Lists') }}">
                                        @foreach($emailLists as $list)
                                            <option value="list:{{ $list->id }}">{{ $list->display_name ?: $list->name }} ({{ number_format($list->subscribers_count ?? 0) }} {{ __('subscribers') }})</option>
                                        @endforeach
                                    </optgroup>
                                    @if(!empty($listSegments))
                                        <optgroup label="{{ __('Segments') }}">
                                            @foreach($listSegments as $segment)
                                                <option value="segment:{{ $segment->id }}">{{ $segment->name }} ({{ number_format($segment->subscribers_count ?? 0) }} {{ __('subscribers') }})</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </select>
                            @endif
                            <p class="text-xs text-gray-500 dark:text-admin-text-secondary">{{ __('Confirmed and unconfirmed subscribers will be imported.') }}</p>
                        </div>
                    </div>

                    {{-- Import Form (dynamic rows) --}}
                    <div x-show="source === 'form'" class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-medium text-gray-700 dark:text-admin-text-secondary">{{ __('Leads') }}</label>
                        <span class="text-xs text-gray-400 dark:text-admin-text-secondary" x-text="rows.length + ' ' + (rows.length === 1 ? '{{ __('row') }}' : '{{ __('rows') }}')"></span>
                    </div>
                    <div class="space-y-2 max-h-72">
                        <template x-for="(row, idx) in rows" :key="idx">
                            <div class="grid grid-cols-12 gap-2 items-center">
                                <input type="email" :name="`rows[${idx}][email]`" x-model="row.email" placeholder="{{ __('email@example.com') }}"
                                    class="col-span-12 sm:col-span-4 px-3 py-2 text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-900 dark:text-admin-text-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] placeholder-gray-400">
                                <input type="text" :name="`rows[${idx}][first_name]`" x-model="row.first_name" placeholder="{{ __('First name') }}"
                                    class="col-span-6 sm:col-span-2 px-3 py-2 text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-900 dark:text-admin-text-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] placeholder-gray-400">
                                <input type="text" :name="`rows[${idx}][last_name]`" x-model="row.last_name" placeholder="{{ __('Last name') }}"
                                    class="col-span-6 sm:col-span-2 px-3 py-2 text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-900 dark:text-admin-text-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] placeholder-gray-400">
                                 <input type="text" :name="`rows[${idx}][company]`" x-model="row.company" placeholder="{{ __('Company') }}"
                                     class="col-span-10 sm:col-span-3 px-3 py-2 text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-900 dark:text-admin-text-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] placeholder-gray-400">
                                <button type="button" @click="removeRow(idx)" class="col-span-2 sm:col-span-1 w-9 h-9 mx-auto flex items-center justify-center text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" :title="'{{ __('Remove') }}'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                             </div>
                             @if(!empty($customFields))
                                 <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                                     @foreach($customFields as $field)
                                         <input type="text" :name="`rows[${idx}][meta][{{ $field['key'] }}]`" x-model="row.meta['{{ $field['key'] }}']" placeholder="{{ $field['label'] ?? $field['key'] }}"
                                             class="px-3 py-2 text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-900 dark:text-admin-text-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] placeholder-gray-400">
                                     @endforeach
                                 </div>
                             @endif
                        </template>
                    </div>
                    <button type="button" @click="addRow()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold hover:bg-[#1E5FEA] border border-dashed border-[#1E5FEA]/40 hover:border-[#1E5FEA] rounded-lg transition-colors brand-text-hover">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        {{ __('Add more') }}
                    </button>
                </div>

                {{-- Manual Paste (textarea for CSV data) --}}
                <div x-show="source === 'paste'" class="space-y-3">
                    <div class="space-y-2">
                        <label class="block text-xs font-medium text-gray-700 dark:text-admin-text-secondary">{{ __('Paste CSV Data') }}</label>
                        <p class="text-xs text-gray-500 dark:text-admin-text-secondary">{{ __('Paste your CSV data below. Format: email, first_name, last_name, company (one per line).') }}</p>
                        <textarea name="paste_data" rows="10" placeholder="email@example.com, John, Doe, Acme Corp&#10;jane@example.com, Jane, Smith, Tech Co"
                            class="w-full px-3 py-2.5 text-xs border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-900 dark:text-admin-text-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] placeholder-gray-400 font-mono"></textarea>
                    </div>
                </div>

                {{-- CSV/XLS File Upload --}}
                <div x-show="source === 'file'" class="space-y-3">
                        <div class="space-y-2">
                            <label class="block text-xs font-medium text-gray-700 dark:text-admin-text-secondary">{{ __('Upload CSV or Excel File') }}</label>
                            <p class="text-xs text-gray-500 dark:text-admin-text-secondary">{{ __('Use the standard columns below, then map custom fields by entering the exact CSV header.') }}</p>
                        <div class="relative">
                            <input type="file" name="import_file" accept=".csv,.xlsx,.xls"
                                class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-900 dark:text-admin-text-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] file:mr-4 file:py-1 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-[#1E5FEA] file:text-white hover:file:bg-[#1a4fd8]">
                        </div>
                        @if(!empty($customFields))
                            <div class="rounded-lg border border-gray-200 dark:border-admin-border p-3 space-y-2">
                                <p class="text-xs font-semibold text-gray-700 dark:text-admin-text-primary">{{ __('Custom field mapping') }}</p>
                                @foreach($customFields as $field)
                                    <label class="grid grid-cols-2 items-center gap-2 text-xs text-gray-600 dark:text-admin-text-secondary">
                                        <span>{{ $field['label'] ?? $field['key'] }} <span class="font-mono text-gray-400">({{ $field['key'] }})</span></span>
                                        <input type="text" name="field_mapping[{{ $field['key'] }}]" placeholder="{{ __('CSV header') }}"
                                            class="px-2.5 py-2 text-xs border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-900 dark:text-admin-text-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="my-6">
                    <label class="block text-xs font-medium text-gray-700 dark:text-admin-text-secondary mb-2">{{ __('Import Mode') }}</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="radio" name="leads_import_mode" value="append" checked class="accent-[#1E5FEA]">
                            <span class="text-gray-700 dark:text-admin-text-primary">{{ __('Append to existing') }}</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="radio" name="leads_import_mode" value="replace" class="accent-[#1E5FEA]">
                            <span class="text-gray-700 dark:text-admin-text-primary">{{ __('Replace all leads') }}</span>
                        </label>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 py-2.5 text-sm font-semibold text-white hover:opacity-90 rounded-lg transition-colors bg-[#1E5FEA]">{{ __('Import Leads') }}</button>
                    <button type="button" @click="showAddLeads = false" class="flex-1 py-2.5 text-sm font-medium text-gray-700 dark:text-admin-text-primary border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 hover:bg-gray-50 dark:hover:bg-white/10 rounded-lg transition-colors">{{ __('Cancel') }}</button>
                </div>
            </form>
        </div>
    </div>
    </div>

    {{-- Edit Lead Modal --}}
    <div x-cloak x-show="showEditLead"
        x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="showEditLead = false"
    >
        <div x-show="showEditLead"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="bg-white dark:bg-admin-card rounded-2xl shadow-xl w-full max-w-lg p-6 space-y-4"
        >
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('Edit Lead') }}</h2>
                    <p class="text-xs text-gray-500 dark:text-admin-text-secondary mt-0.5">{{ __('Update the lead details below.') }}</p>
                </div>
                <button type="button" @click="showEditLead = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" :action="editAction" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-admin-text-secondary mb-1">{{ __('Email') }}</label>
                    <input type="email" name="email" x-model="editLead.email" required
                        class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-900 dark:text-admin-text-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] placeholder-gray-400">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-admin-text-secondary mb-1">{{ __('First name') }}</label>
                        <input type="text" name="first_name" x-model="editLead.first_name"
                            class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-900 dark:text-admin-text-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] placeholder-gray-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-admin-text-secondary mb-1">{{ __('Last name') }}</label>
                        <input type="text" name="last_name" x-model="editLead.last_name"
                            class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-900 dark:text-admin-text-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] placeholder-gray-400">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-admin-text-secondary mb-1">{{ __('Company') }}</label>
                    <input type="text" name="company" x-model="editLead.company"
                        class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-900 dark:text-admin-text-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] placeholder-gray-400">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-admin-text-secondary mb-1">{{ __('Status') }}</label>
                    <select name="status" x-model="editLead.status"
                        class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-900 dark:text-admin-text-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
                        @foreach(['pending', 'sent', 'opened', 'clicked', 'replied', 'bounced', 'unsubscribed'] as $s)
                            <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Custom field inputs (driven by campaign settings) --}}
                <template x-if="(customFields && customFields.length > 0) || (customVariables && customVariables.filter(v => !v.field).length > 0)">
                    <div class="space-y-3 pt-2 border-t border-gray-100 dark:border-admin-border">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold text-gray-700 dark:text-admin-text-secondary uppercase tracking-wide">{{ __('Custom Fields') }}</p>
                            <button type="button" @click="showEditLead = false; openManageFields()" class="text-xs font-medium text-[#1E5FEA] hover:opacity-80">{{ __('Manage') }}</button>
                        </div>
                        <template x-for="f in customFields" :key="'cf-' + f.key">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-admin-text-secondary mb-1">
                                    <span x-text="f.label"></span>
                                    <span class="text-gray-400 font-mono ml-1" x-text="f.key"></span>
                                </label>
                                <input type="text" :name="`meta[${f.key}]`" x-model="editLead.meta[f.key]"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-900 dark:text-admin-text-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] placeholder-gray-400">
                            </div>
                        </template>
                        {{-- Legacy: unmapped custom variables fall back to using their tokenName as meta key --}}
                        <template x-for="v in (customVariables || []).filter(v => !v.field)" :key="'cv-' + v.tokenName">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-admin-text-secondary mb-1">
                                    <span x-text="v.name"></span>
                                    <span class="text-gray-400 font-mono ml-1" x-text="'{' + v.tokenName + '}'"></span>
                                </label>
                                <input type="text" :name="`meta[${v.tokenName}]`" x-model="editLead.meta[v.tokenName]"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-900 dark:text-admin-text-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] placeholder-gray-400">
                            </div>
                        </template>
                    </div>
                </template>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 py-2.5 text-sm font-semibold text-white hover:opacity-90 rounded-lg transition-colors bg-[#1E5FEA]">{{ __('Save Changes') }}</button>
                    <button type="button" @click="showEditLead = false" class="flex-1 py-2.5 text-sm font-medium text-gray-700 dark:text-admin-text-primary border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 hover:bg-gray-50 dark:hover:bg-white/10 rounded-lg transition-colors">{{ __('Cancel') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Manage Custom Fields Modal --}}
    <div x-cloak x-show="showManageFields"
        x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="showManageFields = false"
    >
        <div x-show="showManageFields"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="bg-white dark:bg-admin-card rounded-2xl shadow-xl w-full max-w-lg overflow-hidden"
        >
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-admin-border">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('Manage Custom Fields') }}</h3>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-admin-text-secondary">{{ __('Define custom fields that appear on each lead. Map them to variables in Sequences.') }}</p>
                </div>
                <button type="button" @click="showManageFields = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-admin-text-primary transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-5 py-4 max-h-[60vh] overflow-y-auto space-y-3">
                <template x-if="manageFields.length === 0">
                    <div class="rounded-xl border border-dashed border-gray-200 dark:border-admin-border px-4 py-8 text-center text-sm text-gray-500 dark:text-admin-text-secondary">
                        {{ __('No custom fields yet. Click "Add field" below.') }}
                    </div>
                </template>
                <template x-for="(f, idx) in manageFields" :key="idx">
                    <div class="grid grid-cols-12 gap-2 items-center">
                        <input type="text" x-model="f.label" @input="autoFieldKey(idx)" placeholder="{{ __('Field label (e.g. Job Title)') }}"
                            class="col-span-5 px-3 py-2 text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-900 dark:text-admin-text-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] placeholder-gray-400">
                        <div class="col-span-6 flex items-center gap-1 px-2 py-2 text-sm font-mono border border-gray-200 dark:border-admin-border bg-gray-50 dark:bg-white/5 text-gray-700 dark:text-admin-text-primary rounded-lg">
                            <input type="text" x-model="f.key" @input="f.keyEdited = true" placeholder="key"
                                class="flex-1 bg-transparent border-0 p-0 text-sm font-mono text-gray-700 dark:text-admin-text-primary focus:outline-none focus:ring-0 placeholder-gray-400">
                        </div>
                        <button type="button" @click="removeManageField(idx)" class="col-span-1 w-9 h-9 mx-auto flex items-center justify-center text-gray-400 hover:text-red-500 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" title="{{ __('Remove') }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </template>
                <button type="button" @click="addManageField()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold hover:bg-[#1E5FEA] border border-dashed border-[#1E5FEA]/40 hover:border-[#1E5FEA] rounded-lg transition-colors brand-text-hover">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    {{ __('Add field') }}
                </button>
            </div>
            <div class="flex items-center justify-end gap-3 px-5 py-4 bg-gray-50/70 dark:bg-white/2 border-t border-gray-100 dark:border-admin-border">
                <button type="button" @click="showManageFields = false" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-admin-text-primary border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 hover:bg-gray-50 dark:hover:bg-white/10 transition-colors">{{ __('Cancel') }}</button>
                <button type="button" @click="saveCustomFields()" class="inline-flex items-center justify-center px-5 py-2 rounded-lg text-white text-sm font-semibold hover:opacity-90 transition-colors bg-[#1E5FEA]">{{ __('Save') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
function leadsData() {
    return {
        showAddLeads: false,
        showEditLead: false,
        showManageFields: false,
        editLead: { id: null, email: '', first_name: '', last_name: '', company: '', status: 'pending', meta: {} },
        editAction: '',
        selectedIds: [],
        customVariables: {{ \Illuminate\Support\Js::from($customVariables ?? []) }},
        customFields: {{ \Illuminate\Support\Js::from($customFields ?? []) }},
        manageFields: [],
        _saveFieldsUrl: {{ \Illuminate\Support\Js::from(route('customer.outreach.campaigns.fields.update', $campaign->id)) }},
        _csrfToken: document.querySelector('meta[name=csrf-token]')?.content ?? '',
        selectedImportSource: '',
        slugifyKey(value) {
            const cleaned = String(value || '').replace(/[^a-zA-Z0-9 _]+/g, '').trim().split(/\s+/).filter(Boolean);
            if (cleaned.length === 0) return '';
            return cleaned.map((p, i) => i === 0 ? p.toLowerCase() : p.charAt(0).toUpperCase() + p.slice(1).toLowerCase()).join('');
        },
        openManageFields() {
            this.manageFields = (this.customFields || []).map(f => ({ key: f.key, label: f.label, keyEdited: true }));
            if (this.manageFields.length === 0) this.addManageField();
            this.showManageFields = true;
        },
        addManageField() { this.manageFields.push({ key: '', label: '', keyEdited: false }); },
        removeManageField(i) { this.manageFields.splice(i, 1); },
        autoFieldKey(i) {
            const row = this.manageFields[i];
            if (!row || row.keyEdited) return;
            row.key = this.slugifyKey(row.label);
        },
        async saveCustomFields() {
            const reserved = ['email', 'first_name', 'last_name', 'full_name', 'company'];
            const seen = new Set();
            const cleaned = this.manageFields
                .map(f => ({ key: String(f.key || '').trim().replace(/[^a-zA-Z0-9_]/g, ''), label: String(f.label || '').trim() }))
                .filter(f => f.key && /^[a-zA-Z_][a-zA-Z0-9_]*$/.test(f.key) && !reserved.includes(f.key))
                .map(f => ({ key: f.key, label: f.label || f.key }))
                .filter(f => { if (seen.has(f.key)) return false; seen.add(f.key); return true; });
            try {
                const res = await fetch(this._saveFieldsUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this._csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ fields: cleaned }),
                });
                if (!res.ok) { alert('Failed to save fields. Please try again.'); return; }
                const data = await res.json().catch(() => ({}));
                this.customFields = Array.isArray(data.fields) ? data.fields : cleaned;
                this.showManageFields = false;
            } catch (e) { console.error(e); alert('Failed to save fields. Please try again.'); }
        },
        openEdit(lead, action) {
            const meta = (lead.meta && typeof lead.meta === 'object') ? lead.meta : {};
            const filledMeta = {};
            (this.customFields || []).forEach(f => {
                if (f && f.key) filledMeta[f.key] = meta[f.key] ?? '';
            });
            (this.customVariables || []).forEach(v => {
                if (v && v.tokenName && !v.field && !(v.tokenName in filledMeta)) {
                    filledMeta[v.tokenName] = meta[v.tokenName] ?? '';
                }
            });
            Object.keys(meta).forEach(k => {
                if (!(k in filledMeta)) filledMeta[k] = meta[k];
            });
            this.editLead = {
                id: lead.id,
                email: lead.email ?? '',
                first_name: lead.first_name ?? '',
                last_name: lead.last_name ?? '',
                company: lead.company ?? '',
                status: lead.status ?? 'pending',
                meta: filledMeta,
            };
            this.editAction = action;
            this.showEditLead = true;
        },
        toggleAll() {
            const checkboxes = document.querySelectorAll('.js-lead-checkbox');
            const checked = this.$refs.selectAll.checked;
            checkboxes.forEach(cb => cb.checked = checked);
            this.selectedIds = checked
                ? Array.from(checkboxes).map(cb => parseInt(cb.value))
                : [];
        },
        syncSelectAll() {
            const checkboxes = document.querySelectorAll('.js-lead-checkbox');
            const checkedBoxes = Array.from(checkboxes).filter(cb => cb.checked);
            this.$refs.selectAll.checked = checkedBoxes.length === checkboxes.length && checkboxes.length > 0;
            this.selectedIds = checkedBoxes.map(cb => parseInt(cb.value));
        }
    };
}

function initLeadsData() {
    // Initialization logic if needed
}
</script>
