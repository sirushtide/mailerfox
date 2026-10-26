@php
    $stopOnReply      = $campaign->getSetting('stop_on_reply', true);
    $stopOnAutoReply  = $campaign->getSetting('stop_on_auto_reply', false);
    $trackingDomain   = $campaign->getSetting('tracking_domain');
    $bccEmail         = $campaign->getSetting('bcc_email', '');
    $emailFooter      = $campaign->getSetting('email_footer', '');
    $senderIds        = $campaign->getSetting('sender_account_ids', []);
    $accountRotation  = $campaign->getSetting('enable_account_rotation', true);
    $sendStyledBody  = $campaign->getSetting('send_styled_body', true);
@endphp

<form method="POST" action="{{ route('customer.outreach.campaigns.options.update', $campaign) }}" id="save-form-options"
    x-data="optionsFooterEditor()"
    x-init="init()"
    class="space-y-5">
    @csrf

    <input type="hidden" name="email_footer" :value="email_footer">

    {{-- General Settings --}}
    <div class="bg-white dark:bg-admin-card border border-gray-100 dark:border-admin-border rounded-xl divide-y divide-gray-100 dark:divide-admin-border">
        <div class="px-6 py-4">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('General Settings') }}</h2>
            <p class="text-xs text-gray-500 dark:text-admin-text-secondary mt-0.5">{{ __('Basic configuration for this campaign.') }}</p>
        </div>

        {{-- Campaign Name --}}
        <div class="px-6 py-5 flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="sm:w-64 flex-shrink-0">
                <label class="text-sm font-medium text-gray-700 dark:text-admin-text-primary">{{ __('Campaign Name') }}</label>
            </div>
            <input type="text" name="name" value="{{ $campaign->name }}"
                class="flex-1 px-3 py-2.5 text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-900 dark:text-admin-text-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
        </div>

        {{-- Stop on reply --}}
        <div class="px-6 py-5">
            <div class="flex items-center justify-between">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-admin-text-primary">{{ __('Stop sending on reply') }}</label>
                    <p class="text-xs text-gray-400 dark:text-admin-text-secondary mt-0.5">{{ __('Automatically stop the sequence for a lead if they reply to any email.') }}</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                    <input type="checkbox" name="stop_on_reply" value="1" {{ $stopOnReply ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 dark:bg-white/10 rounded-full peer peer-checked:bg-[#1E5FEA] transition-colors after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></div>
                </label>
            </div>
        </div>

        {{-- Stop on auto-reply --}}
        <div class="px-6 py-5">
            <div class="flex items-center justify-between">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-admin-text-primary">{{ __('Stop sending on auto-reply') }}</label>
                    <p class="text-xs text-gray-400 dark:text-admin-text-secondary mt-0.5">{{ __('Pause the sequence if an out-of-office or auto-reply is detected.') }}</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                    <input type="checkbox" name="stop_on_auto_reply" value="1" {{ $stopOnAutoReply ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 dark:bg-white/10 rounded-full peer peer-checked:bg-[#1E5FEA] transition-colors after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></div>
                </label>
            </div>
        </div>

        {{-- Custom Tracking Domain --}}
        <div class="px-6 py-5 flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex-1">
                <label class="text-sm font-medium text-gray-700 dark:text-admin-text-primary">{{ __('Custom Tracking Domain') }}</label>
                <p class="text-xs text-gray-400 dark:text-admin-text-secondary mt-0.5">{{ __('Use a custom domain for tracking opens and clicks to improve deliverability.') }}</p>
            </div>
            <div class="sm:w-64">
                <select name="tracking_domain" class="w-full appearance-none px-3 py-2.5 text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-700 dark:text-admin-text-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
                    <option value="">{{ __('None') }}</option>
                    @foreach($trackingDomains as $td)
                        <option value="{{ $td->domain }}" {{ $trackingDomain === $td->domain ? 'selected' : '' }}>{{ $td->domain }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- BCC Email --}}
        <div class="px-6 py-5 flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex-1">
                <label class="text-sm font-medium text-gray-700 dark:text-admin-text-primary">{{ __('BCC Email (CRM Integration)') }}</label>
                <p class="text-xs text-gray-400 dark:text-admin-text-secondary mt-0.5">{{ __('Automatically BCC all outgoing campaign emails to this address (e.g., Salesforce, HubSpot).') }}</p>
            </div>
            <input type="email" name="bcc_email" value="{{ $bccEmail }}" placeholder="{{ __('e.g. bcc@hubspot.com') }}"
                class="sm:w-64 px-3 py-2.5 text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-900 dark:text-admin-text-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] placeholder-gray-400">
        </div>

        <div class="px-6 py-5">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-admin-text-primary">{{ __('Send email with styled body and content') }}</label>
                    <p class="text-xs text-gray-400 dark:text-admin-text-secondary mt-0.5">{{ __('When enabled, outreach emails preserve the rich editor formatting. When disabled, emails are sent with bare styling for a more minimal look.') }}</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                    <input type="checkbox" name="send_styled_body" value="1" {{ $sendStyledBody ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 dark:bg-white/10 rounded-full peer peer-checked:bg-[#1E5FEA] transition-colors after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></div>
                </label>
            </div>
        </div>

        <div class="px-6 py-5 space-y-3">
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-admin-text-primary">{{ __('Email Footer') }}</label>
                <p class="text-xs text-gray-400 dark:text-admin-text-secondary mt-0.5">{{ __('This footer is appended to every outreach email in this campaign. Variables like {firstName}, {fullName}, {company}, and custom tokens are supported.') }}</p>
            </div>
            <div
                data-outreach-rich-editor
                data-upload-url="{{ route('customer.uploads.inline-image') }}"
                data-step-id="campaign-options-footer"
                data-variant="a"
                data-field="footer"
                data-placeholder="{{ __('Best regards,\n{fullName}\n{company}') }}"
                :data-value="email_footer"
            ></div>
            <div class="flex items-center gap-3">
                <button type="button" @click="openVariablePicker()"
                    class="text-xs text-[#1E5FEA] hover:text-blue-700 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                    {{ __('Insert Variable') }}
                </button>
                <button type="button" @click="openTemplatePicker()"
                    class="text-xs text-[#1E5FEA] hover:text-blue-700 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    {{ __('Pick Template') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Sending Accounts --}}
    <div class="bg-white dark:bg-admin-card border border-gray-100 dark:border-admin-border rounded-xl divide-y divide-gray-100 dark:divide-admin-border">
        <div class="px-6 py-4">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('Sending Accounts') }}</h2>
            <p class="text-xs text-gray-500 dark:text-admin-text-secondary mt-0.5">{{ __('Select which email accounts will be used to send this campaign.') }}</p>
        </div>

        <div class="px-6 py-5 space-y-4">
            <div>
                <label class="text-xs font-semibold text-gray-700 dark:text-admin-text-secondary uppercase tracking-wide mb-3 block">{{ __('Sender Accounts') }}</label>
                <p class="text-xs text-gray-400 dark:text-admin-text-secondary mb-3">{{ __('Select one or more accounts. Emails will be distributed among selected accounts.') }}</p>
                @if($deliveryServers->isEmpty())
                    <div class="px-4 py-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg text-xs text-amber-700 dark:text-amber-400">
                        {{ __('No active delivery servers found.') }}
                        <a href="{{ route('customer.delivery-servers.create') }}" class="underline font-medium ml-1">{{ __('Add one now') }}</a>
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach($deliveryServers as $ds)
                            <label class="flex items-center justify-between p-3.5 border border-gray-200 dark:border-admin-border rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" name="sender_account_ids[]" value="{{ $ds->id }}"
                                        {{ in_array($ds->id, $senderIds) ? 'checked' : '' }}
                                        class="w-4 h-4 rounded border-gray-300 text-[#1E5FEA]">
                                    <div class="w-7 h-7 rounded-full bg-gray-100 dark:bg-white/10 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-sm text-gray-700 dark:text-admin-text-primary font-medium">{{ $ds->name }}</span>
                                        @if($ds->from_email)
                                            <span class="text-xs text-gray-500 dark:text-admin-text-secondary">{{ $ds->from_email }}</span>
                                        @endif
                                    </div>
                                </div>
                                <span class="text-xs px-2 py-1 rounded-full border font-medium
                                    {{ $ds->status === 'active' ? 'text-green-700 bg-green-50 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800' : 'text-amber-600 bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:text-amber-400' }}">
                                    {{ ucfirst($ds->status) }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-admin-border">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-admin-text-primary">{{ __('Enable Account Rotation') }}</label>
                    <p class="text-xs text-gray-400 dark:text-admin-text-secondary mt-0.5">{{ __('Automatically rotate between selected sender accounts to maximize deliverability.') }}</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                    <input type="checkbox" name="enable_account_rotation" value="1" {{ $accountRotation ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 dark:bg-white/10 rounded-full peer peer-checked:bg-[#1E5FEA] transition-colors after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></div>
                </label>
            </div>
        </div>
    </div>

    {{-- Danger Zone --}}
    <div class="bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800 rounded-xl divide-y divide-red-100 dark:divide-red-800">
        <div class="px-6 py-4">
            <h2 class="text-sm font-semibold text-red-700 dark:text-red-400">{{ __('Danger Zone') }}</h2>
            <p class="text-xs text-red-600 dark:text-red-500 mt-0.5">{{ __('Irreversible and destructive actions for this campaign.') }}</p>
        </div>
        <div class="px-6 py-5 flex items-center justify-between">
            <div>
                <label class="text-sm font-medium text-gray-800 dark:text-admin-text-primary">{{ __('Delete Campaign') }}</label>
                <p class="text-xs text-gray-500 dark:text-admin-text-secondary mt-0.5">{{ __('Permanently delete this campaign, all its sequences, and related statistics. This action cannot be undone.') }}</p>
            </div>
            <button type="button" @click="confirm_delete = true"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                {{ __('Delete Campaign') }}
            </button>
        </div>
    </div>

    {{-- Delete Confirm Modal --}}
    <div x-cloak x-show="confirm_delete"
        x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="confirm_delete = false"
    >
        <div x-show="confirm_delete"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="bg-white dark:bg-admin-card rounded-2xl shadow-xl w-full max-w-sm p-6 space-y-4"
        >
            <div class="w-12 h-12 mx-auto bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div class="text-center">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('Delete Campaign?') }}</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-admin-text-secondary">{{ __('This will permanently delete "') }}{{ $campaign->name }}{{ __('" including all leads and sequences. This cannot be undone.') }}</p>
            </div>
            <div class="flex gap-3">
                <button type="submit" form="delete-outreach-campaign-form" class="flex-1 w-full py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">{{ __('Yes, Delete') }}</button>
                <button type="button" @click="confirm_delete = false" class="flex-1 py-2.5 text-sm font-medium text-gray-700 dark:text-admin-text-primary border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 hover:bg-gray-50 dark:hover:bg-white/10 rounded-lg transition-colors">{{ __('Cancel') }}</button>
            </div>
        </div>
    </div>

<div x-cloak x-show="showVarPicker"
    x-transition:enter="ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-black/50"
    @click.self="closeVariablePicker()"
>
    <div class="bg-white dark:bg-admin-card rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-admin-border">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('Insert Variable') }}</h3>
            <button type="button" @click="closeVariablePicker()" class="text-gray-400 hover:text-gray-600 dark:hover:text-admin-text-primary transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-5 py-4 max-h-[65vh] overflow-y-auto">
            <div class="relative mb-6">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35"/><circle cx="11" cy="11" r="6"/></svg>
                </span>
                <input type="text" x-model="variableSearch" placeholder="{{ __('Search variables...') }}"
                    class="w-full h-11 pl-11 pr-4 rounded-xl border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-admin-text-primary placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
            </div>

            <div class="space-y-6">
                <template x-for="group in filteredVariableGroups" :key="group.label">
                    <div>
                        <h4 class="mb-3 text-[11px] font-semibold tracking-[0.16em] text-slate-400 uppercase" x-text="group.label"></h4>
                        <div class="space-y-2">
                            <template x-for="variable in group.items" :key="variable.token">
                                <button type="button" @click="selectVariable(variable.token)"
                                    class="w-full flex items-center justify-between gap-3 px-4 py-3 text-left rounded-xl border transition-colors"
                                    :class="selectedVariableToken === variable.token ? 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20' : 'border-transparent hover:border-gray-200 hover:bg-slate-50 dark:hover:border-admin-border dark:hover:bg-white/5'">
                                    <div class="flex items-center gap-4 min-w-0">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 border border-slate-200 dark:border-admin-border dark:text-admin-text-secondary" x-html="variable.icon"></span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-admin-text-primary truncate" x-text="variable.name"></span>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-white/10 text-xs font-mono text-gray-700 dark:text-admin-text-primary" x-text="variable.token"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
        <div class="flex items-center justify-between gap-3 px-5 py-4 bg-blue-50/70 dark:bg-blue-900/10 border-t border-blue-100 dark:border-blue-900/30">
            <button type="button" @click="openManageVariables()" class="inline-flex items-center gap-2 text-sm font-medium text-[#1E5FEA] hover:text-blue-700 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5"/></svg>
                {{ __('Manage Variables') }}
            </button>
            <button type="button" @click="applySelectedVariable()" :disabled="!selectedVariableToken"
                class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-[#2563eb] text-white text-sm font-semibold hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                {{ __('Insert Variable') }}
            </button>
        </div>
    </div>
</div>

<div x-cloak x-show="showManageVariables"
    x-transition:enter="ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-4 bg-black/50"
    @click.self="closeManageVariables()"
>
    <div class="bg-white dark:bg-admin-card rounded-2xl shadow-xl w-full max-w-lg overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-admin-border">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('Manage Variables') }}</h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-admin-text-secondary">{{ __('Map each variable token to a built-in lead column or a custom field.') }}</p>
            </div>
            <button type="button" @click="closeManageVariables()" class="text-gray-400 hover:text-gray-600 dark:hover:text-admin-text-primary transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-5 py-4 max-h-[60vh] overflow-y-auto space-y-3">
            <template x-if="manageVariables.length === 0">
                <div class="rounded-xl border border-dashed border-gray-200 dark:border-admin-border px-4 py-8 text-center text-sm text-gray-500 dark:text-admin-text-secondary">
                    {{ __('No custom variables yet. Click "Add variable" below.') }}
                </div>
            </template>
            <div class="grid grid-cols-12 gap-2 items-center text-[11px] font-semibold tracking-wide uppercase text-slate-400">
                <span class="col-span-4">{{ __('Variable') }}</span>
                <span class="col-span-4">{{ __('Token') }}</span>
                <span class="col-span-3">{{ __('Maps to') }}</span>
                <span class="col-span-1"></span>
            </div>
            <template x-for="(v, idx) in manageVariables" :key="idx">
                <div class="grid grid-cols-12 gap-2 items-center">
                    <input type="text" x-model="v.name" @input="autoToken(idx)" placeholder="{{ __('Variable name') }}"
                        class="col-span-4 px-3 py-2 text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-900 dark:text-admin-text-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E5FEA] placeholder-gray-400">
                    <div class="col-span-4 flex items-center gap-1 px-2 py-2 text-sm font-mono border border-gray-200 dark:border-admin-border bg-gray-50 dark:bg-white/5 text-gray-700 dark:text-admin-text-primary rounded-lg">
                        <span class="text-gray-400">{</span>
                        <input type="text" x-model="v.tokenName" @input="v.tokenEdited = true" placeholder="tokenName"
                            class="flex-1 min-w-0 bg-transparent border-0 p-0 text-sm font-mono text-gray-700 dark:text-admin-text-primary focus:outline-none focus:ring-0 placeholder-gray-400">
                        <span class="text-gray-400">}</span>
                    </div>
                    <select x-model="v.field"
                        class="col-span-3 px-2 py-2 text-sm border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-gray-900 dark:text-admin-text-primary rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">
                        <option value="">{{ __('Same as token') }}</option>
                        <optgroup label="{{ __('Built-in') }}">
                            <option value="email">{{ __('Email') }}</option>
                            <option value="first_name">{{ __('First Name') }}</option>
                            <option value="last_name">{{ __('Last Name') }}</option>
                            <option value="full_name">{{ __('Full Name') }}</option>
                            <option value="company">{{ __('Company') }}</option>
                        </optgroup>
                        <template x-if="customFields && customFields.length > 0">
                            <optgroup label="{{ __('Custom Fields') }}">
                                <template x-for="f in customFields" :key="f.key">
                                    <option :value="f.key" x-text="f.label"></option>
                                </template>
                            </optgroup>
                        </template>
                    </select>
                    <button type="button" @click="removeCustomVariable(idx)" class="col-span-1 w-9 h-9 mx-auto flex items-center justify-center text-gray-400 hover:text-red-500 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" title="{{ __('Remove') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </template>
            <button type="button" @click="addCustomVariable()"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-[#1E5FEA] hover:text-white hover:bg-[#1E5FEA] border border-dashed border-[#1E5FEA]/40 hover:border-[#1E5FEA] rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                {{ __('Add variable') }}
            </button>
        </div>
        <div class="flex items-center justify-end gap-3 px-5 py-4 bg-gray-50/70 dark:bg-white/2 border-t border-gray-100 dark:border-admin-border">
            <button type="button" @click="closeManageVariables()" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-admin-text-primary border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 hover:bg-gray-50 dark:hover:bg-white/10 transition-colors">{{ __('Cancel') }}</button>
            <button type="button" @click="saveCustomVariables()" class="inline-flex items-center justify-center px-5 py-2 rounded-lg bg-[#2563eb] text-white text-sm font-semibold hover:bg-blue-700 transition-colors">{{ __('Save') }}</button>
        </div>
    </div>
</div>

<div x-cloak x-show="showTemplatePicker"
    x-transition:enter="ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-black/50"
    @click.self="closeTemplatePicker()"
>
    <div class="bg-white dark:bg-admin-card rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-admin-border">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-admin-text-primary">{{ __('Pick Template') }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-admin-text-secondary">{{ __('Load an existing template into this footer.') }}</p>
            </div>
            <button type="button" @click="closeTemplatePicker()" class="text-gray-400 hover:text-gray-600 dark:hover:text-admin-text-primary transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
            <input type="text" x-model="templateSearch" placeholder="{{ __('Search templates...') }}"
                class="w-full h-11 px-4 rounded-xl border border-gray-200 dark:border-admin-border bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-admin-text-primary placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1E5FEA]">

            <div class="space-y-3" x-show="filteredTemplates.length > 0">
                <template x-for="template in filteredTemplates" :key="template.id">
                    <button type="button" @click="selectTemplate(template.id)"
                        class="w-full text-left px-4 py-4 rounded-xl border transition-colors"
                        :class="selectedTemplateId === template.id ? 'border-blue-200 bg-blue-50/60 dark:border-blue-800 dark:bg-blue-900/10' : 'border-gray-200 dark:border-admin-border hover:border-blue-200 hover:bg-blue-50/60 dark:hover:border-blue-800 dark:hover:bg-blue-900/10'">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary" x-text="template.name"></div>
                                <div class="mt-1 text-sm text-gray-500 dark:text-admin-text-secondary" x-text="template.description || '{{ __('No description') }}'"></div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-white/10 text-xs font-medium text-gray-600 dark:text-admin-text-secondary">#<span x-text="template.id"></span></span>
                        </div>
                    </button>
                </template>
            </div>

            <div x-show="filteredTemplates.length === 0" class="rounded-xl border border-dashed border-gray-200 dark:border-admin-border px-4 py-10 text-center text-sm text-gray-500 dark:text-admin-text-secondary">
                {{ __('No templates matched your search.') }}
            </div>
        </div>
    </div>
</div>

</form>

<form id="delete-outreach-campaign-form" method="POST" action="{{ route('customer.outreach.campaigns.destroy', $campaign) }}" class="hidden">
    @csrf
    @method('DELETE')
</form>

@push('scripts')
@vite('resources/js/outreach-sequence-editor.jsx')
<script>
function optionsFooterEditor() {
    return {
        confirm_delete: false,
        email_footer: @js($emailFooter),
        templates: @json(($templates ?? collect())->map(fn ($template) => ['id' => $template->id, 'name' => $template->name, 'description' => $template->description])->values()),
        showVarPicker: false,
        showTemplatePicker: false,
        variableSearch: '',
        templateSearch: '',
        selectedVariableToken: '',
        selectedTemplateId: null,
        showManageVariables: false,
        manageVariables: [],
        customVariables: @json($customVariables ?? $campaign->getSetting('custom_variables', [])),
        customFields: @json($customFields ?? $campaign->getSetting('custom_fields', [])),
        _saveCustomVariablesUrl: @json(route('customer.outreach.campaigns.variables.update', $campaign->id)),
        _csrfToken: document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        _builtinVariableGroups: [
            {
                label: 'LEAD',
                items: [
                    { name: 'First Name', token: '{firstName}', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="5"></circle><path d="M20 21a8 8 0 0 0-16 0"></path></svg>' },
                    { name: 'Last Name', token: '{lastName}', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>' },
                    { name: 'Full Name', token: '{fullName}', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>' },
                    { name: 'Email Address', token: '{email}', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>' },
                    { name: 'Company', token: '{company}', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path><path d="M10 6h4"></path><path d="M10 10h4"></path><path d="M10 14h4"></path><path d="M10 18h4"></path></svg>' },
                    { name: 'Unsubscribe Link', token: '{unsubscribe_url}', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 17H7A5 5 0 0 1 7 7h2"></path><path d="M15 7h2a5 5 0 1 1 0 10h-2"></path><line x1="8" x2="16" y1="12" y2="12"></line></svg>' },
                ],
            },
        ],

        get variableGroups() {
            const groups = this._builtinVariableGroups.map((g) => ({ ...g, items: [...g.items] }));
            if (this.customVariables && this.customVariables.length > 0) {
                groups.push({
                    label: 'CUSTOM',
                    items: this.customVariables.map((v) => ({
                        name: v.name,
                        token: '{' + v.tokenName + '}',
                        icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2"></path><circle cx="12" cy="12" r="10" stroke-width="1.8"></circle></svg>',
                    })),
                });
            }
            return groups;
        },

        get filteredVariableGroups() {
            const search = this.variableSearch.trim().toLowerCase();

            return this.variableGroups
                .map((group) => ({
                    ...group,
                    items: group.items.filter((item) => {
                        if (!search) {
                            return true;
                        }

                        return [item.name, item.token].some((value) => String(value).toLowerCase().includes(search));
                    }),
                }))
                .filter((group) => group.items.length > 0);
        },

        get filteredTemplates() {
            const search = this.templateSearch.trim().toLowerCase();

            return this.templates.filter((template) => {
                if (!search) {
                    return true;
                }

                return [template.name, template.description]
                    .filter(Boolean)
                    .some((value) => String(value).toLowerCase().includes(search));
            });
        },

        init() {
            this.loadCustomVariables();
            window.addEventListener('outreach-plate-change', (event) => {
                if ((event.detail || {}).stepId === 'campaign-options-footer') {
                    this.email_footer = event.detail.value || '';
                }
            });
        },

        openVariablePicker() {
            this.variableSearch = '';
            this.selectedVariableToken = '';
            this.showVarPicker = true;
        },

        closeVariablePicker() {
            this.showVarPicker = false;
            this.variableSearch = '';
            this.selectedVariableToken = '';
        },

        selectVariable(token) {
            this.selectedVariableToken = token;
        },

        applySelectedVariable() {
            if (!this.selectedVariableToken) {
                return;
            }

            window.dispatchEvent(new CustomEvent('outreach-plate-insert-token', {
                detail: {
                    stepId: 'campaign-options-footer',
                    variant: 'a',
                    field: 'footer',
                    token: this.selectedVariableToken,
                },
            }));

            this.closeVariablePicker();
        },

        openTemplatePicker() {
            this.templateSearch = '';
            this.selectedTemplateId = null;
            this.showTemplatePicker = true;
        },

        closeTemplatePicker() {
            this.showTemplatePicker = false;
            this.templateSearch = '';
            this.selectedTemplateId = null;
        },

        async selectTemplate(templateId) {
            if (!templateId) {
                return;
            }

            this.selectedTemplateId = templateId;
            const response = await fetch(this.templateContentUrl(templateId), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            const plain = String(data.plain_text_content || '').trim();
            const html = String(data.html_content || '').trim();
            this.email_footer = html || plain;
            this.closeTemplatePicker();
        },

        templateContentUrl(templateId) {
            return `{{ url('/customer/templates') }}/${templateId}/content`;
        },

        loadCustomVariables() {
            this.customVariables = Array.isArray(this.customVariables)
                ? this.customVariables.filter((v) => v && v.name && v.tokenName)
                : [];
        },

        slugifyToken(value) {
            const cleaned = String(value || '')
                .replace(/[^a-zA-Z0-9 ]+/g, '')
                .trim()
                .split(/\s+/)
                .filter(Boolean);
            if (cleaned.length === 0) {
                return '';
            }
            return cleaned
                .map((part, i) => {
                    const lower = part.toLowerCase();
                    return i === 0 ? lower : lower.charAt(0).toUpperCase() + lower.slice(1);
                })
                .join('');
        },

        openManageVariables() {
            this.manageVariables = this.customVariables.map((v) => ({
                name: v.name,
                tokenName: v.tokenName,
                field: v.field || '',
                tokenEdited: true,
            }));
            if (this.manageVariables.length === 0) {
                this.addCustomVariable();
            }
            this.showManageVariables = true;
        },

        closeManageVariables() {
            this.showManageVariables = false;
            this.manageVariables = [];
        },

        addCustomVariable() {
            this.manageVariables.push({ name: '', tokenName: '', field: '', tokenEdited: false });
        },

        removeCustomVariable(idx) {
            this.manageVariables.splice(idx, 1);
        },

        autoToken(idx) {
            const row = this.manageVariables[idx];
            if (!row || row.tokenEdited) {
                return;
            }
            row.tokenName = this.slugifyToken(row.name);
        },

        async saveCustomVariables() {
            const seen = new Set();
            const allowedFields = new Set([
                'email', 'first_name', 'last_name', 'full_name', 'company',
                ...((this.customFields || []).map((f) => f.key)),
            ]);
            const cleaned = this.manageVariables
                .map((v) => ({
                    name: String(v.name || '').trim(),
                    tokenName: String(v.tokenName || '').trim().replace(/[^a-zA-Z0-9_]/g, ''),
                    field: String(v.field || '').trim(),
                }))
                .filter((v) => v.tokenName && /^[a-zA-Z_][a-zA-Z0-9_]*$/.test(v.tokenName))
                .map((v) => ({
                    name: v.name || v.tokenName,
                    tokenName: v.tokenName,
                    field: allowedFields.has(v.field) ? v.field : '',
                }))
                .filter((v) => {
                    if (seen.has(v.tokenName)) return false;
                    seen.add(v.tokenName);
                    return true;
                });

            try {
                const response = await fetch(this._saveCustomVariablesUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this._csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ variables: cleaned }),
                });

                if (!response.ok) {
                    const text = await response.text();
                    console.error('Failed saving custom variables', text);
                    alert('Failed to save variables. Please try again.');
                    return;
                }

                const data = await response.json().catch(() => ({}));
                this.customVariables = Array.isArray(data.variables) ? data.variables : cleaned;
                this.closeManageVariables();
            } catch (e) {
                console.error(e);
                alert('Failed to save variables. Please try again.');
            }
        },
    };
}
</script>
@endpush
