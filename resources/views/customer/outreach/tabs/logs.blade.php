<div class="space-y-5">
    {{-- Timeline + Server Events: 50 / 50 --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        {{-- Campaign Timeline --}}
        <div class="bg-white dark:bg-admin-card border border-gray-100 dark:border-admin-border rounded-xl overflow-hidden flex flex-col">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-admin-border flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('Campaign Timeline') }}</h2>
                    <p class="text-xs text-gray-500 dark:text-admin-text-secondary mt-0.5">{{ __('Sequence progress and next scheduled step.') }}</p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full border {{ $campaign->status_color }}">
                    {{ ucfirst($campaign->status) }}
                </span>
            </div>
            <div class="p-5 flex-1">
                @if(!empty($timeline) && $timeline->isNotEmpty())
                    <div class="relative">
                        @foreach($timeline as $index => $item)
                            @php
                                $isLast = $loop->last;
                                $isDone = $item['status'] === 'done';
                                $isActive = $item['status'] === 'active';
                                $isFailed = $item['status'] === 'failed';
                            @endphp
                            <div class="flex gap-4">
                                {{-- Connector line + node --}}
                                <div class="flex flex-col items-center">
                                    {{-- Node --}}
                                    @if($isDone)
                                        <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                    @elseif($isFailed)
                                        <div class="w-8 h-8 rounded-full bg-red-500 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </div>
                                    @elseif($isActive)
                                        <div class="w-8 h-8 rounded-full border-2 border-green-500 flex items-center justify-center flex-shrink-0 bg-white dark:bg-admin-card">
                                            <div class="w-2.5 h-2.5 rounded-full bg-green-500"></div>
                                        </div>
                                    @else
                                        <div class="w-8 h-8 rounded-full border-2 border-gray-200 dark:border-gray-600 flex items-center justify-center flex-shrink-0 bg-white dark:bg-admin-card">
                                            <div class="w-2.5 h-2.5 rounded-full bg-gray-200 dark:bg-gray-600"></div>
                                        </div>
                                    @endif

                                    {{-- Line (except last) --}}
                                    @if(!$isLast)
                                        <div class="w-px flex-1 my-1 {{ $isDone ? 'bg-green-400 dark:bg-green-600' : ($isFailed ? 'bg-red-300 dark:bg-red-700' : 'bg-gray-200 dark:bg-gray-700 border-l border-dashed border-gray-200 dark:border-gray-700') }}"></div>
                                    @endif
                                </div>

                                {{-- Content --}}
                                <div class="pb-6 {{ $isLast ? '' : '' }}">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary">{{ $item['subject'] }}</span>
                                        @if($isDone)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide rounded-full bg-green-50 text-green-700 border border-green-200 dark:bg-green-900/20 dark:text-green-300 dark:border-green-800">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                {{ __('Done') }}
                                            </span>
                                        @elseif($isFailed)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide rounded-full bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                                {{ __('Failed') }}
                                            </span>
                                        @elseif($isActive)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide rounded-full bg-green-50 text-green-700 border border-green-200 dark:bg-green-900/20 dark:text-green-300 dark:border-green-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                                {{ __('Active') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide rounded-full bg-gray-100 text-gray-600 border border-gray-200 dark:bg-white/5 dark:text-gray-400 dark:border-gray-700">
                                                {{ __('Pending') }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-admin-text-secondary">
                                        {{ __('Step :step', ['step' => $item['step']]) }}
                                        @if($item['delay'])
                                            <span class="text-gray-300 dark:text-gray-600 mx-1">|</span>
                                            {{ $item['delay'] }}
                                        @endif
                                    </div>
                                    @if($isDone && $item['last_sent_at'])
                                        <div class="mt-1 text-xs text-gray-400 dark:text-admin-text-secondary">
                                            {{ $item['last_sent_at']->format('d M, H:i') }}
                                            @if($item['total_sent'] > 0)
                                                <span class="text-gray-300 dark:text-gray-600 mx-1">|</span>
                                                {{ __('Sent: :count', ['count' => $item['total_sent']]) }}
                                            @endif
                                        </div>
                                        @if(!empty($item['recipients']))
                                            <div x-data="{ open: false }" class="mt-2">
                                                <button type="button"
                                                    @click="open = !open"
                                                    class="inline-flex items-center gap-1 text-xs text-primary-600 dark:text-primary-400 hover:underline focus:outline-none">
                                                    <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                    <span x-text="open ? '{{ __('Hide recipients') }}' : '{{ __('Show :count recipient(s)', ['count' => count($item['recipients'])]) }}'"></span>
                                                </button>
                                                <div x-show="open" x-transition class="mt-2 space-y-1">
                                                    @foreach($item['recipients'] as $recipient)
                                                        <div class="flex items-center gap-2 rounded-md bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-gray-700 px-2.5 py-1.5">
                                                            <svg class="w-3.5 h-3.5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                            <span class="text-xs font-medium text-gray-800 dark:text-gray-100 truncate">{{ $recipient['email'] ?: '—' }}</span>
                                                            @if($recipient['sent_at'])
                                                                <span class="ml-auto text-[11px] text-gray-400 dark:text-gray-500 flex-shrink-0">{{ \Carbon\Carbon::parse($recipient['sent_at'])->format('d M, H:i') }}</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                    @if(!empty($item['failed_recipients']))
                                                        @foreach($item['failed_recipients'] as $recipient)
                                                            <div class="flex items-center gap-2 rounded-md bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-800 px-2.5 py-1.5">
                                                                <svg class="w-3.5 h-3.5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                                <span class="text-xs font-medium text-gray-800 dark:text-gray-100 truncate">{{ $recipient['email'] ?: '—' }}</span>
                                                                @if($recipient['error'])
                                                                    <span class="ml-auto text-[11px] text-red-500 flex-shrink-0 truncate max-w-[120px]" title="{{ $recipient['error'] }}">{{ \Illuminate\Support\Str::limit($recipient['error'], 30) }}</span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    @elseif($isFailed)
                                        <div class="mt-1 text-xs text-red-600 dark:text-red-400">
                                            {{ $item['error_message'] ?: __('Send failed for this step') }}
                                            @if($item['failed_count'] > 0)
                                                <span class="text-red-400 dark:text-red-500 mx-1">|</span>
                                                {{ __(':count failed', ['count' => $item['failed_count']]) }}
                                            @endif
                                        </div>
                                        @if(!empty($item['failed_recipients']))
                                            <div x-data="{ open: false }" class="mt-2">
                                                <button type="button"
                                                    @click="open = !open"
                                                    class="inline-flex items-center gap-1 text-xs text-red-600 dark:text-red-400 hover:underline focus:outline-none">
                                                    <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                    <span x-text="open ? '{{ __('Hide') }}' : '{{ __('Show :count failed', ['count' => count($item['failed_recipients'])]) }}'"></span>
                                                </button>
                                                <div x-show="open" x-transition class="mt-2 space-y-1">
                                                    @foreach($item['failed_recipients'] as $recipient)
                                                        <div class="flex items-center gap-2 rounded-md bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-800 px-2.5 py-1.5">
                                                            <svg class="w-3.5 h-3.5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                            <span class="text-xs font-medium text-gray-800 dark:text-gray-100 truncate">{{ $recipient['email'] ?: '—' }}</span>
                                                            @if($recipient['error'])
                                                                <span class="ml-auto text-[11px] text-red-500 flex-shrink-0 truncate max-w-[120px]" title="{{ $recipient['error'] }}">{{ \Illuminate\Support\Str::limit($recipient['error'], 30) }}</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    @elseif($isActive)
                                        <div class="mt-1 text-xs text-green-600 dark:text-green-400">
                                            {{ __('Next email to send') }}
                                        </div>
                                    @else
                                        <div class="mt-1 text-xs text-gray-400 dark:text-admin-text-secondary">
                                            {{ __('Waiting for previous step') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-xl p-4 text-center bg-white dark:bg-white/5">
                        <div class="mx-auto w-11 h-11 rounded-full bg-gray-100 dark:bg-white/10 flex items-center justify-center mb-3">
                            <svg class="w-5 h-5 text-gray-400 dark:text-admin-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('No sequence steps yet') }}</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-admin-text-secondary">{{ __('Add sequence steps to see the campaign timeline.') }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Recent Server Events --}}
        <div class="bg-white dark:bg-admin-card border border-gray-100 dark:border-admin-border rounded-xl overflow-hidden flex flex-col">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-admin-border">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('Recent Server Events') }}</h2>
                <p class="text-xs text-gray-500 dark:text-admin-text-secondary mt-0.5">{{ __('Latest send attempts for this campaign grouped by delivery server usage.') }}</p>
            </div>
            <div class="p-5 flex-1">
                @if(($recentServerLogs ?? collect())->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($recentServerLogs as $log)
                            <div class="rounded-xl border border-gray-200 dark:border-admin-border p-4 bg-gray-50/60 dark:bg-white/5">
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full border {{ $log->status === 'success' ? 'text-green-700 bg-green-50 border-green-200 dark:bg-green-900/20 dark:text-green-300 dark:border-green-800' : 'text-red-700 bg-red-50 border-red-200 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800' }}">{{ ucfirst($log->status) }}</span>
                                            <span class="text-xs font-medium text-gray-700 dark:text-admin-text-primary">{{ $log->deliveryServer?->name ?? __('Unknown server') }}</span>
                                            <span class="text-xs text-gray-400 dark:text-admin-text-secondary">{{ $log->created_at?->diffForHumans() }}</span>
                                        </div>
                                        <p class="mt-2 text-sm text-gray-700 dark:text-admin-text-primary">
                                            {{ $log->to_email ?: __('Unknown recipient') }}
                                            @if(!empty($log->meta['outreach_step']))
                                                <span class="text-gray-400 dark:text-admin-text-secondary">&bull; {{ __('Step :step', ['step' => $log->meta['outreach_step']]) }}</span>
                                            @endif
                                        </p>
                                        @if($log->status === 'success' && !empty($log->meta['rendered_body']))
                                            <details class="mt-2">
                                                <summary class="cursor-pointer text-xs font-medium text-[#1E5FEA]">{{ __('View personalized email') }}</summary>
                                                <div class="mt-2 rounded-lg border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 p-3">
                                                    @if(!empty($log->meta['subject']))
                                                        <p class="mb-2 text-xs font-semibold text-gray-700 dark:text-admin-text-primary">{{ $log->meta['subject'] }}</p>
                                                    @endif
                                                    <div class="prose prose-sm max-w-none dark:prose-invert">{!! $log->meta['rendered_body'] !!}</div>
                                                </div>
                                            </details>
                                        @endif
                                        @if(!empty($log->error_message))
                                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $log->error_message }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-xl p-4 text-center bg-white dark:bg-white/5">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('No server events yet') }}</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-admin-text-secondary">{{ __('Outreach delivery activity will appear here after emails are sent.') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Bottom charts row --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <div class="xl:col-span-2 bg-white dark:bg-admin-card border border-gray-100 dark:border-admin-border rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-admin-border">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('Server Delivery Trend') }}</h2>
                <p class="text-xs text-gray-500 dark:text-admin-text-secondary mt-0.5">{{ __('Emails sent by selected delivery servers over the last 7 days for this campaign.') }}</p>
            </div>
            <div class="p-5">
                @if(!empty($serverDailyUsage) && collect($serverDailyUsage)->sum('sent_count') > 0)
                    <div class="flex items-end gap-3 h-56">
                        @foreach($serverDailyUsage as $day)
                            @php
                                $height = max(10, (int) round((($day['sent_count'] ?? 0) / max(1, $serverDailyUsageMaxSent ?? 1)) * 100));
                            @endphp
                            <div class="flex-1 min-w-0 flex flex-col items-center gap-2">
                                <div class="text-[11px] text-gray-400 dark:text-admin-text-secondary">{{ $day['sent_count'] }}</div>
                                <div class="w-full flex items-end justify-center h-40">
                                    <div class="w-full max-w-12 rounded-t-lg bg-gradient-to-t from-[#1E5FEA] to-blue-400" style="height: {{ $height }}%;"></div>
                                </div>
                                <div class="text-[11px] text-gray-500 dark:text-admin-text-secondary">{{ $day['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-xl p-4 text-center bg-white dark:bg-white/5">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('No server activity yet') }}</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-admin-text-secondary">{{ __('Once this campaign sends emails, daily volume will appear here.') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white dark:bg-admin-card border border-gray-100 dark:border-admin-border rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-admin-border">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('Server Usage') }}</h2>
                <p class="text-xs text-gray-500 dark:text-admin-text-secondary mt-0.5">{{ __('Which delivery server is sending how many emails.') }}</p>
            </div>
            <div class="p-5 space-y-4">
                @forelse($serverUsageStats ?? [] as $server)
                    @php
                        $barWidth = max(6, (int) round((($server['sent_count'] ?? 0) / max(1, $serverUsageMaxSent ?? 1)) * 100));
                    @endphp
                    <div class="space-y-2">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-900 dark:text-admin-text-primary truncate">{{ $server['server_name'] }}</div>
                                <div class="text-xs text-gray-500 dark:text-admin-text-secondary truncate">{{ $server['from_email'] ?: strtoupper((string) $server['type']) }}</div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary">{{ $server['sent_count'] }}</div>
                                <div class="text-[11px] text-gray-500 dark:text-admin-text-secondary">{{ __('sent') }}</div>
                            </div>
                        </div>
                        <div class="w-full h-2 rounded-full bg-gray-100 dark:bg-white/10 overflow-hidden">
                            <div class="h-2 rounded-full bg-[#1E5FEA]" style="width: {{ $barWidth }}%;"></div>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-admin-text-secondary">
                            <span>{{ __('Failures: :count', ['count' => $server['failed_count']]) }}</span>
                            <span>
                                {{ !empty($server['last_sent_at']) ? \Carbon\Carbon::parse($server['last_sent_at'])->diffForHumans() : __('No successful send yet') }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500 dark:text-admin-text-secondary">{{ __('No delivery server usage data yet.') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
