@php
    $campaign = $campaign ?? null;
    $campaignSettings = $campaignSettings ?? (is_object($campaign) ? (array) ($campaign->settings ?? []) : []);
    $sendingSchedule = (array) data_get($campaignSettings, 'sending_schedule', []);

    $isScheduledDefault = (bool) ($campaign && ($campaign->scheduled_at || $campaign->send_at));
    $isScheduled = (bool) old('is_scheduled', $isScheduledDefault);

    $sendAtValue = old('send_at');
    if (($sendAtValue === null || $sendAtValue === '') && $campaign) {
        $sendAtRaw = $campaign->scheduled_at ?? $campaign->send_at;
        if ($sendAtRaw) {
            try {
                $tz = (auth('customer')->user()->timezone ?? null) ?: config('app.timezone', 'UTC');
                $sendAtValue = \Carbon\Carbon::parse($sendAtRaw)->setTimezone($tz)->format('Y-m-d\TH:i');
            } catch (\Throwable $e) {
                $sendAtValue = '';
            }
        }
    }

    $defaultTz = old('schedule_timezone', data_get($sendingSchedule, 'timezone', (auth('customer')->user()->timezone ?? null) ?: config('app.timezone', 'UTC')));

    $defaultDays = data_get($sendingSchedule, 'days', [1, 2, 3, 4, 5]);
    $oldDays = old('schedule_days', $defaultDays);
    $oldDays = is_array($oldDays) ? array_map('intval', $oldDays) : [];

    $dayLabels = [1 => 'M', 2 => 'T', 3 => 'W', 4 => 'T', 5 => 'F', 6 => 'S', 7 => 'S'];

    $defaultBlocks = data_get($sendingSchedule, 'time_blocks', [['from' => '09:00', 'to' => '17:30']]);
    $oldBlocks = old('schedule_time_blocks', $defaultBlocks);
    if (!is_array($oldBlocks) || empty($oldBlocks)) {
        $oldBlocks = [['from' => '09:00', 'to' => '17:30']];
    }

    $sendSpeed = (array) data_get($campaignSettings, 'send_speed', []);
    $oldGapMs = old('send_gap_ms', $sendSpeed['gap_ms'] ?? '');
    $oldRatePerMinute = old('send_rate_per_minute', $sendSpeed['rate_per_minute'] ?? '');
@endphp

<div class="accordion-step" data-accordion-step="5">
    <button type="button" class="accordion-header" data-accordion-trigger="5">
        <div class="flex items-center gap-3 min-w-0">
            <span class="accordion-badge">
                <span class="badge-num">5</span>
                <svg class="badge-check w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
            </span>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900 dark:text-white">Schedule</p>
                <p class="text-xs accordion-summary" data-step-summary="5">Choose when this campaign should be sent.</p>
            </div>
        </div>
        <span class="accordion-edit-btn">Edit</span>
    </button>
    <div class="accordion-body" data-campaign-step="5">
        <div class="space-y-6">
            {{-- Toggle: Make this campaign scheduled --}}
            <div class="flex items-start justify-between gap-4 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">Make this campaign scheduled</div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Campaign will start at the scheduled time. If disabled, it will start immediately when launched.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                    <input type="checkbox" id="is_scheduled" name="is_scheduled" value="1" class="sr-only peer" {{ $isScheduled ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-700 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                </label>
            </div>

            <div id="schedule_send_at_wrap" class="{{ $isScheduled ? '' : 'hidden' }}">
                <label for="send_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Scheduled send time</label>
                <input
                    type="datetime-local"
                    name="send_at"
                    id="send_at"
                    value="{{ $sendAtValue }}"
                    class="mt-1 block w-full sm:max-w-md rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                >
                @error('send_at')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Sending Schedule card --}}
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-5">
                <div class="mb-2">
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">Sending Schedule</div>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Define when your emails should be sent to prospects.</p>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    {{-- Timezone --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 py-4 items-start">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300 pt-2">Timezone</div>
                        <div class="sm:col-span-2">
                            <select name="schedule_timezone" id="schedule_timezone" class="block w-full sm:max-w-xs rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                @foreach (\DateTimeZone::listIdentifiers() as $tz)
                                    <option value="{{ $tz }}" {{ $defaultTz === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">All times below are based on this timezone.</p>
                        </div>
                    </div>

                    {{-- Days to send --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 py-4 items-center">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Days to send</div>
                        <div class="sm:col-span-2">
                            <div class="flex items-center gap-2 flex-wrap" data-schedule-days>
                                @foreach ($dayLabels as $d => $label)
                                    @php $checked = in_array($d, $oldDays, true); @endphp
                                    <label class="cursor-pointer">
                                        <input type="checkbox" name="schedule_days[]" value="{{ $d }}" class="sr-only peer" {{ $checked ? 'checked' : '' }}>
                                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-full text-sm font-semibold border border-gray-300 dark:border-gray-600 text-gray-400 dark:text-gray-500 bg-white dark:bg-gray-800 peer-checked:bg-purple-600 peer-checked:border-purple-600 peer-checked:text-white transition-colors">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Sending hours --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 py-4 items-start">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300 pt-2">Sending hours</div>
                        <div class="sm:col-span-2">
                            <div id="schedule-time-blocks" class="space-y-2">
                                @foreach ($oldBlocks as $idx => $block)
                                    <div class="flex items-center gap-2" data-time-block>
                                        <input type="time" name="schedule_time_blocks[{{ $idx }}][from]" value="{{ $block['from'] ?? '09:00' }}" class="rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">to</span>
                                        <input type="time" name="schedule_time_blocks[{{ $idx }}][to]" value="{{ $block['to'] ?? '17:30' }}" class="rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                        <button type="button" data-remove-time-block class="text-gray-400 hover:text-red-500 p-1.5 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a2 2 0 012-2h2a2 2 0 012 2v3"/></svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" id="add-time-block" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-purple-600 hover:text-purple-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Time Block
                            </button>
                        </div>
                    </div>

                    {{-- Sending speed --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 py-4 items-start">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300 pt-2">Gap between emails</div>
                        <div class="sm:col-span-2">
                            <div class="flex items-center gap-2">
                                <input type="number" name="send_gap_ms" id="send_gap_ms" value="{{ $oldGapMs }}" min="0" step="100"
                                    placeholder="inherit"
                                    class="rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm w-32">
                                <span class="text-sm text-gray-500 dark:text-gray-400">ms</span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Delay after each email. Blank or 0 = inherit server/global default (default 500 ms).</p>
                            @error('send_gap_ms')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 py-4 items-start">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300 pt-2">Max emails per minute</div>
                        <div class="sm:col-span-2">
                            <input type="number" name="send_rate_per_minute" id="send_rate_per_minute" value="{{ $oldRatePerMinute }}" min="0"
                                placeholder="inherit"
                                class="rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm w-32">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Blank or 0 = inherit server/global default (type default, e.g. 20/min for SMTP).</p>
                            @error('send_rate_per_minute')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="accordion-footer">
            <button type="button" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700" data-step-cancel>Back</button>
            @if ($campaign)
                <button type="button" data-step-save class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">Save Campaign</button>
            @else
                <button type="button" id="btn-save" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">Create Campaign</button>
            @endif
        </div>
    </div>
</div>
