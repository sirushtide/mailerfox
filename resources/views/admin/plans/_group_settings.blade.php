@php
    $outreachAddonActive = \App\Models\Addon::isInstalled('cold-email-outreach') && \App\Models\Addon::isActive('cold-email-outreach');
    $superScrapeAddonActive = \App\Models\Addon::isActive('super-scrape');
    $workspaceAddonActive = \App\Models\Addon::isActive('workspace');
    $effectiveSettings = $settings ?? $defaultSettings ?? [];
    $sectionInputClass = 'block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm';
@endphp

<section x-show="activePlanSection === 'messages'" x-cloak>
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Messages') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Customize access denied and limit-reached messages shown to customers.') }}</p>
        </div>
        <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600">{{ __('Save') }}</button>
    </div>
    <x-settings-section>
        <x-settings-row :title="__('Default access denied message')" :description="__('Shown when a customer hits a restricted area without a specific override.')">
            <input type="text" name="messages[access][default]" value="{{ old('messages.access.default', data_get($effectiveSettings, 'messages.access.default', '')) }}" class="{{ $sectionInputClass }}" placeholder="{{ __('You have no access here.') }}">
        </x-settings-row>
        <x-settings-row :title="__('Access denied: Tracking Domains')" :description="__('Message shown when customers cannot manage tracking domains.')">
            <input type="text" name="messages[access][domains][tracking_domains][can_manage]" value="{{ old('messages.access.domains.tracking_domains.can_manage', data_get($effectiveSettings, 'messages.access.domains.tracking_domains.can_manage', '')) }}" class="{{ $sectionInputClass }}" placeholder="{{ __('You have no access to tracking domains.') }}">
        </x-settings-row>
        <x-settings-row :title="__('Access denied: Sending Domains')" :description="__('Message shown when customers cannot manage sending domains.')">
            <input type="text" name="messages[access][domains][sending_domains][can_manage]" value="{{ old('messages.access.domains.sending_domains.can_manage', data_get($effectiveSettings, 'messages.access.domains.sending_domains.can_manage', '')) }}" class="{{ $sectionInputClass }}" placeholder="{{ __('You have no access to sending domains.') }}">
        </x-settings-row>
        <x-settings-row :title="__('Access denied: Delivery Servers')" :description="__('Message shown when customers cannot add delivery servers.')">
            <input type="text" name="messages[access][servers][permissions][can_add_delivery_servers]" value="{{ old('messages.access.servers.permissions.can_add_delivery_servers', data_get($effectiveSettings, 'messages.access.servers.permissions.can_add_delivery_servers', '')) }}" class="{{ $sectionInputClass }}" placeholder="{{ __('You have no access to delivery servers.') }}">
        </x-settings-row>
        <x-settings-row :title="__('Limit reached: Tracking Domains')" :description="__('Message shown when the tracking domain limit has been reached.')">
            <input type="text" name="messages[limits][domains][tracking_domains][max_tracking_domains]" value="{{ old('messages.limits.domains.tracking_domains.max_tracking_domains', data_get($effectiveSettings, 'messages.limits.domains.tracking_domains.max_tracking_domains', '')) }}" class="{{ $sectionInputClass }}" placeholder="{{ __('Your tracking domain limits expired.') }}">
        </x-settings-row>
        <x-settings-row :title="__('Limit reached: Sending Domains')" :description="__('Message shown when the sending domain limit has been reached.')">
            <input type="text" name="messages[limits][domains][sending_domains][max_sending_domains]" value="{{ old('messages.limits.domains.sending_domains.max_sending_domains', data_get($effectiveSettings, 'messages.limits.domains.sending_domains.max_sending_domains', '')) }}" class="{{ $sectionInputClass }}" placeholder="{{ __('Your sending domain limits expired.') }}">
        </x-settings-row>
    </x-settings-section>
</section>

<section x-show="activePlanSection === 'email_lists'" x-cloak>
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Email Lists') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Control email list access and limits for this plan.') }}</p>
        </div>
        <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600">{{ __('Save') }}</button>
    </div>
    <x-settings-section>
        <x-settings-row :title="__('Max number of lists')" :description="__('Maximum email lists a customer can create. Set -1 for unlimited.')">
            <input type="number" min="-1" name="lists[limits][max_lists]" value="{{ old('lists.limits.max_lists', data_get($effectiveSettings, 'lists.limits.max_lists', -1)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        <x-settings-row :title="__('Max subscribers per list')" :description="__('Subscriber cap for each individual list. Set -1 for unlimited.')">
            <input type="number" min="-1" name="lists[limits][max_subscribers_per_list]" value="{{ old('lists.limits.max_subscribers_per_list', data_get($effectiveSettings, 'lists.limits.max_subscribers_per_list', -1)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        <x-settings-row :title="__('Max forms per list')" :description="__('Maximum subscription forms allowed per list.')">
            <input type="number" min="-1" name="lists[limits][max_forms_per_list]" value="{{ old('lists.limits.max_forms_per_list', data_get($effectiveSettings, 'lists.limits.max_forms_per_list', -1)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        <x-settings-row :title="__('Max subscribers (all lists)')" :description="__('Subscriber cap across all of the customer&rsquo;s lists. Set -1 for unlimited.')">
            <input type="number" min="-1" name="lists[limits][max_subscribers]" value="{{ old('lists.limits.max_subscribers', data_get($effectiveSettings, 'lists.limits.max_subscribers', -1)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
    </x-settings-section>
</section>

<section x-show="activePlanSection === 'email_footer'" x-cloak>
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Email Footer') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Set the default email footer appended to campaign emails.') }}</p>
        </div>
        <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600">{{ __('Save') }}</button>
    </div>
    @php $emailFooterValue = old('email_footer.content', data_get($effectiveSettings, 'email_footer.content', '')); @endphp
    <x-settings-section>
        <div class="px-5 py-5">
            <div class="mb-3">
                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Footer content') }}</div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{!! __('This footer is automatically appended to every email sent by customers in this plan (campaigns, test emails, and outreach). Personalization tokens (e.g. <code>@{{first_name}}</code>) are supported.') !!}</p>
            </div>
            <textarea name="email_footer[content]" id="email_footer_content" class="hidden">{{ $emailFooterValue }}</textarea>
            <div id="email_footer_editor" data-outreach-rich-editor data-upload-url="{{ route('admin.uploads.inline-image') }}" data-step-id="plan-group-footer" data-variant="a" data-field="content" data-value="{{ $emailFooterValue }}" data-placeholder="{{ __('e.g. -- Sent via MyCompany. 123 Main St, City, Country') }}" data-target-textarea="email_footer_content"></div>
            @error('email_footer.content')<p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('Leave empty to disable. Max 20,000 characters.') }}</p>
        </div>
    </x-settings-section>
</section>

<section x-show="activePlanSection === 'email_templates'" x-cloak>
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Email Templates') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Control which template libraries and template actions customers can access.') }}</p>
        </div>
        <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600">{{ __('Save') }}</button>
    </div>
    <x-settings-section>
        <x-settings-row :title="__('Access to templates')" :description="__('Allow customers to view and use email templates.')">
            <x-settings-toggle name="templates[permissions][can_access_templates]" :checked="(bool) old('templates.permissions.can_access_templates', data_get($effectiveSettings, 'templates.permissions.can_access_templates', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Access to footer templates')" :description="__('Allow customers to view and use footer templates.')">
            <x-settings-toggle name="templates[permissions][can_access_footer_templates]" :checked="(bool) old('templates.permissions.can_access_footer_templates', data_get($effectiveSettings, 'templates.permissions.can_access_footer_templates', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Access to signature templates')" :description="__('Allow customers to view and use signature templates.')">
            <x-settings-toggle name="templates[permissions][can_access_signature_templates]" :checked="(bool) old('templates.permissions.can_access_signature_templates', data_get($effectiveSettings, 'templates.permissions.can_access_signature_templates', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Create templates')" :description="__('Allow customers to create and edit templates.')">
            <x-settings-toggle name="templates[permissions][can_create_templates]" :checked="(bool) old('templates.permissions.can_create_templates', data_get($effectiveSettings, 'templates.permissions.can_create_templates', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Import templates')" :description="__('Allow customers to import templates from available sources.')">
            <x-settings-toggle name="templates[permissions][can_import_templates]" :checked="(bool) old('templates.permissions.can_import_templates', data_get($effectiveSettings, 'templates.permissions.can_import_templates', false))" />
        </x-settings-row>
    </x-settings-section>

    <div class="mt-8 mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('AI Builder') }}</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Control AI generation for the email template builder.') }}</p>
    </div>
    <x-settings-section>
        <x-settings-row :title="__('Access AI builder')" :description="__('Allow customers to use the AI builder when creating email templates.')">
            <x-settings-toggle name="templates[permissions][can_use_ai_builder]" :checked="(bool) old('templates.permissions.can_use_ai_builder', data_get($effectiveSettings, 'templates.permissions.can_use_ai_builder', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Default AI model')" :description="__('Model preselected for email template generation. Leave empty to use the system default.')">
            <x-searchable-model-select
                :provider-models="$aiProviderModels ?? []"
                :selected="(string) old('templates.ai_builder.default_model', data_get($effectiveSettings, 'templates.ai_builder.default_model', ''))"
                name="templates[ai_builder][default_model]"
                :allow-empty="true"
                empty-label="{{ __('System default') }}"
                placeholder="{{ __('Search models...') }}"
                dropup
            />
        </x-settings-row>
    </x-settings-section>
</section>

<section x-show="activePlanSection === 'campaigns'" x-cloak>
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Campaigns') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Manage campaign permissions, limits, and behavior.') }}</p>
        </div>
        <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600">{{ __('Save') }}</button>
    </div>
    <x-settings-section>
        <x-settings-row :title="__('Max campaigns to create')" :description="__('Total number of campaigns a customer can create. Set -1 for unlimited.')">
            <input type="number" min="-1" name="campaigns[limits][max_campaigns]" value="{{ old('campaigns.limits.max_campaigns', data_get($effectiveSettings, 'campaigns.limits.max_campaigns', -1)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        <x-settings-row :title="__('Max campaigns to run')" :description="__('Maximum number of campaigns that can be active concurrently. Set -1 for unlimited.')">
            <input type="number" min="-1" name="campaigns[limits][max_active_campaigns]" value="{{ old('campaigns.limits.max_active_campaigns', data_get($effectiveSettings, 'campaigns.limits.max_active_campaigns', -1)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        <x-settings-row :title="__('Access to A/B testing')" :description="__('Allow customers to use A/B testing in their campaigns.')">
            <x-settings-toggle name="campaigns[features][ab_testing]" :checked="(bool) old('campaigns.features.ab_testing', data_get($effectiveSettings, 'campaigns.features.ab_testing', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Can create campaigns')" :description="__('Allow customers to create new campaigns.')">
            <x-settings-toggle name="campaigns[permissions][can_create_campaigns]" :checked="(bool) old('campaigns.permissions.can_create_campaigns', data_get($effectiveSettings, 'campaigns.permissions.can_create_campaigns', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Can edit campaigns')" :description="__('Allow customers to edit existing campaigns.')">
            <x-settings-toggle name="campaigns[permissions][can_edit_campaigns]" :checked="(bool) old('campaigns.permissions.can_edit_campaigns', data_get($effectiveSettings, 'campaigns.permissions.can_edit_campaigns', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Can start/pause/resume campaigns')" :description="__('Allow customers to control campaign run state.')">
            <x-settings-toggle name="campaigns[permissions][can_start_campaigns]" :checked="(bool) old('campaigns.permissions.can_start_campaigns', data_get($effectiveSettings, 'campaigns.permissions.can_start_campaigns', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Can delete campaigns')" :description="__('Allow customers to delete campaigns.')">
            <x-settings-toggle name="campaigns[permissions][can_delete_campaigns]" :checked="(bool) old('campaigns.permissions.can_delete_campaigns', data_get($effectiveSettings, 'campaigns.permissions.can_delete_campaigns', false))" />
        </x-settings-row>
    </x-settings-section>
</section>

<section x-show="activePlanSection === 'autoresponders'" x-cloak>
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Auto Responders') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Configure auto responder access and limits.') }}</p>
        </div>
        <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600">{{ __('Save') }}</button>
    </div>
    <x-settings-section>
        <x-settings-row :title="__('Access to auto responders')" :description="__('Allow customers in this plan to use auto responders.')">
            <x-settings-toggle name="autoresponders[enabled]" :checked="(bool) old('autoresponders.enabled', data_get($effectiveSettings, 'autoresponders.enabled', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Max auto responders to create')" :description="__('Maximum number of auto responders a customer can create. Set -1 for unlimited.')">
            <input type="number" min="-1" name="autoresponders[max_autoresponders]" value="{{ old('autoresponders.max_autoresponders', data_get($effectiveSettings, 'autoresponders.max_autoresponders', -1)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
    </x-settings-section>
</section>

<section x-show="activePlanSection === 'automations'" x-cloak>
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Automations') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Configure automation access and limits.') }}</p>
        </div>
        <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600">{{ __('Save') }}</button>
    </div>
    <x-settings-section>
        <x-settings-row :title="__('Access to automations')" :description="__('Allow customers in this plan to use the automations feature.')">
            <x-settings-toggle name="automations[enabled]" :checked="(bool) old('automations.enabled', data_get($effectiveSettings, 'automations.enabled', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Max automations to create')" :description="__('Maximum number of automations a customer can create. Set -1 for unlimited.')">
            <input type="number" min="-1" name="automations[max_automations]" value="{{ old('automations.max_automations', data_get($effectiveSettings, 'automations.max_automations', -1)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
    </x-settings-section>
</section>

@if($outreachAddonActive)
<section x-show="activePlanSection === 'outreach'" x-cloak>
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Outreach') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Configure cold email outreach access and limits.') }}</p>
        </div>
        <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600">{{ __('Save') }}</button>
    </div>
    <x-settings-section>
        <x-settings-row :title="__('Access to outreach')" :description="__('Show the Outreach section for customers in this plan and allow them to use the cold outreach addon.')">
            <x-settings-toggle name="outreach[access]" :checked="(bool) old('outreach.access', data_get($effectiveSettings, 'outreach.access', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Max outreach campaigns')" :description="__('Maximum number of outreach campaigns. Set -1 for unlimited.')">
            <input type="number" min="0" name="outreach[max_campaigns]" value="{{ old('outreach.max_campaigns', data_get($effectiveSettings, 'outreach.max_campaigns', 0)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        <x-settings-row :title="__('Max sequences per outreach')" :description="__('Maximum number of sequences per outreach campaign. Set -1 for unlimited.')">
            <input type="number" min="0" name="outreach[max_sequences_per_campaign]" value="{{ old('outreach.max_sequences_per_campaign', data_get($effectiveSettings, 'outreach.max_sequences_per_campaign', 0)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        <x-settings-row :title="__('Max leads per outreach')" :description="__('Maximum number of leads per outreach campaign. Set -1 for unlimited.')">
            <input type="number" min="0" name="outreach[max_leads_per_campaign]" value="{{ old('outreach.max_leads_per_campaign', data_get($effectiveSettings, 'outreach.max_leads_per_campaign', 0)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
    </x-settings-section>
</section>
@endif

@if($superScrapeAddonActive)
<section x-show="activePlanSection === 'super_scrape'" x-cloak>
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Lead Scraper') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Configure Lead Scraper access and scraping limits.') }}</p>
        </div>
        <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600">{{ __('Save') }}</button>
    </div>
    <x-settings-section>
        <x-settings-row :title="__('Access to Lead Scraper')" :description="__('Allow customers in this plan to access the Lead Scraper and run scraping jobs.')">
            <x-settings-toggle name="super_scrape[access]" :checked="(bool) old('super_scrape.access', data_get($effectiveSettings, 'super_scrape.access', true))" />
        </x-settings-row>
        <x-settings-row :title="__('Monthly scraper credits override')" :description="__('Set 0 to use the global default from SuperScrape settings.')">
            <input type="number" min="0" name="super_scrape[monthly_credits]" value="{{ old('super_scrape.monthly_credits', data_get($effectiveSettings, 'super_scrape.monthly_credits', 0)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        <x-settings-row :title="__('Max scraping jobs')" :description="__('Maximum total scraping jobs allowed. Set -1 for unlimited.')">
            <input type="number" min="0" name="super_scrape[max_jobs]" value="{{ old('super_scrape.max_jobs', data_get($effectiveSettings, 'super_scrape.max_jobs', 0)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        <div class="px-5 py-5">
            <div class="mb-3">
                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Scraping sources') }}</div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Toggle which scraping sources are available to this plan.') }}</p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Google') }}</div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Allow access to Google scraping.') }}</p>
                        </div>
                        <div class="flex items-center">
                            <input type="hidden" name="super_scrape[google_access]" value="0">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="super_scrape[google_access]" value="1" {{ old('super_scrape.google_access', data_get($effectiveSettings, 'super_scrape.google_access', true)) ? 'checked' : '' }} class="sr-only peer">
                                <div class="relative w-11 h-6 bg-gray-200 rounded-full peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-500 dark:bg-gray-700 peer-checked:bg-primary-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:border-gray-300 after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                            </label>
                        </div>
                    </div>
                </div>
                @foreach(['Instagram','LinkedIn','TikTok','Facebook','X'] as $source)
                <div class="rounded-lg border border-dashed border-gray-200 dark:border-gray-700 p-4 opacity-70">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __($source) }} <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('(Coming soon)') }}</span></div>
                        </div>
                        <div class="flex items-center">
                            <label class="inline-flex items-center cursor-not-allowed">
                                <input type="checkbox" disabled class="sr-only peer">
                                <div class="relative w-11 h-6 bg-gray-200 rounded-full dark:bg-gray-700 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:border-gray-300 after:rounded-full after:h-5 after:w-5"></div>
                            </label>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </x-settings-section>
</section>

<section x-show="activePlanSection === 'enrichment'" x-cloak>
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Enrichment APIs') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Set plan-level enrichment API keys and BYOK policy.') }}</p>
        </div>
        <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600">{{ __('Save') }}</button>
    </div>
    <x-settings-section>
        <x-settings-row :title="__('Apollo API Key')" :description="__('Apollo.io API key for B2B enrichment. Leave blank to use global settings.')">
            <input type="password" name="enrichment[apollo_key]" value="{{ old('enrichment.apollo_key', data_get($effectiveSettings, 'enrichment.apollo_key', '')) }}" class="{{ $sectionInputClass }}" placeholder="apollo_key_...">
        </x-settings-row>
        <x-settings-row :title="__('Hunter.io API Key')" :description="__('Hunter.io API key for email discovery and verification. Leave blank to use global settings.')">
            <input type="password" name="enrichment[hunter_key]" value="{{ old('enrichment.hunter_key', data_get($effectiveSettings, 'enrichment.hunter_key', '')) }}" class="{{ $sectionInputClass }}" placeholder="hunter_key_...">
        </x-settings-row>
        <x-settings-row :title="__('RocketReach API Key')" :description="__('RocketReach API key for professional profile lookup. Leave blank to use global settings.')">
            <input type="password" name="enrichment[rocketreach_key]" value="{{ old('enrichment.rocketreach_key', data_get($effectiveSettings, 'enrichment.rocketreach_key', '')) }}" class="{{ $sectionInputClass }}" placeholder="rocketreach_key_...">
        </x-settings-row>
        <x-settings-row :title="__('Allow BYOK')" :description="__('Let customers in this plan use their own enrichment API keys in their settings.')">
            <x-settings-toggle name="enrichment[allow_byok]" :checked="(bool) old('enrichment.allow_byok', data_get($effectiveSettings, 'enrichment.allow_byok', true))" />
        </x-settings-row>
    </x-settings-section>
</section>
@endif

<section x-show="activePlanSection === 'integrations'" x-cloak>
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Integrations') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Toggle available third-party integrations.') }}</p>
        </div>
        <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600">{{ __('Save') }}</button>
    </div>
    <x-settings-section>
        <x-settings-row :title="__('Google Integrations')" :description="__('Enables the Integrations → Google page for customers in this plan (Sheets/Drive connect & sync).')">
            <x-settings-toggle name="integrations[permissions][can_access_google]" :checked="(bool) old('integrations.permissions.can_access_google', data_get($effectiveSettings, 'integrations.permissions.can_access_google', false))" />
        </x-settings-row>
    </x-settings-section>
</section>

<section x-show="activePlanSection === 'servers'" x-cloak>
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Servers') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Manage delivery and bounce server access and limits.') }}</p>
        </div>
        <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600">{{ __('Save') }}</button>
    </div>
    @php
        $allocatedDeliveryServerOptions = collect($deliveryServers ?? [])->map(function ($server) {
            $ownerLabel = $server->customer ? ('Customer: ' . $server->customer->email) : 'System';
            return ['id' => (string) $server->id, 'label' => $server->name . ' (' . $ownerLabel . ')'];
        })->values()->all();
        $showExtendedFeaturePopup = !($hasExtendedLicense ?? false);
    @endphp

    <x-settings-section class="mb-6">
        <div class="px-5 py-5">
            <div class="mb-3">
                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Allocated Delivery Servers') }}</div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('If set, this plan can only use these delivery servers (unless a customer has their own allocation).') }}</p>
            </div>
            <x-tag-multiselect name="allocated_delivery_server_ids[]" :options="$allocatedDeliveryServerOptions" :selected="old('allocated_delivery_server_ids', $allocatedDeliveryServerIds ?? [])" placeholder="{{ __('Select delivery servers') }}" search-placeholder="{{ __('Search servers...') }}" />
            @error('allocated_delivery_server_ids')<p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            @error('allocated_delivery_server_ids.*')<p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
    </x-settings-section>

    <x-modal name="pro-feature-extended-mailbox-providers-admin" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Pro Feature') }}</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ __('Gmail and Outlook mailbox providers are available as an Extended/Pro feature.') }}</p>
            <div class="mt-6 flex justify-end">
                <x-button type="button" variant="secondary" @click="$dispatch('close-modal', 'pro-feature-extended-mailbox-providers-admin')">{{ __('Close') }}</x-button>
            </div>
        </div>
    </x-modal>

    <div class="space-y-6">
        <x-settings-section>
            <div class="px-5 pt-4 pb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Delivery servers') }}</div>
            <x-settings-row :title="__('Must add delivery server')" :description="__('Require customers to add their own delivery server before sending.')">
                <x-settings-toggle name="servers[permissions][must_add_delivery_server]" :checked="(bool) old('servers.permissions.must_add_delivery_server', data_get($effectiveSettings, 'servers.permissions.must_add_delivery_server', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Can add delivery servers')" :description="__('Allow customers to add their own delivery servers.')">
                <x-settings-toggle name="servers[permissions][can_add_delivery_servers]" :checked="(bool) old('servers.permissions.can_add_delivery_servers', data_get($effectiveSettings, 'servers.permissions.can_add_delivery_servers', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Can use Gmail/Outlook mailbox providers (Extended)')" :description="__('Premium feature: lets customers connect Gmail and Outlook mailbox providers.')">
                <div class="flex items-center justify-end gap-2">
                    <button type="button" @click="if (@json($showExtendedFeaturePopup)) { $dispatch('open-modal', 'pro-feature-extended-mailbox-providers-admin') }" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-amber-300 bg-amber-50 text-amber-600 transition hover:bg-amber-100 dark:border-amber-700/70 dark:bg-amber-900/25 dark:text-amber-300 dark:hover:bg-amber-900/40" title="{{ __('Pro feature') }}" aria-label="{{ __('Pro feature') }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M5 18h14l1.5-9-4.5 2.5L12 4 7.5 11.5 3 9 5 18zm0 2a1 1 0 100 2h14a1 1 0 100-2H5z" /></svg>
                    </button>
                    <input type="hidden" name="servers[permissions][can_use_extended_mailbox_providers]" value="0">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="servers[permissions][can_use_extended_mailbox_providers]" value="1" {{ old('servers.permissions.can_use_extended_mailbox_providers', data_get($effectiveSettings, 'servers.permissions.can_use_extended_mailbox_providers', false)) ? 'checked' : '' }} class="sr-only peer" @change="if (@json($showExtendedFeaturePopup) && $event.target.checked) { $event.target.checked = false; $dispatch('open-modal', 'pro-feature-extended-mailbox-providers-admin') }">
                        <div class="relative w-11 h-6 bg-gray-200 rounded-full peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-500 dark:bg-gray-700 peer-checked:bg-primary-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:border-gray-300 after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                    </label>
                </div>
            </x-settings-row>
        <x-settings-row :title="__('Max delivery servers')" :description="__('Maximum delivery servers a customer can add. Set -1 for unlimited.')">
            <input type="number" min="-1" name="servers[limits][max_delivery_servers]" value="{{ old('servers.limits.max_delivery_servers', data_get($effectiveSettings, 'servers.limits.max_delivery_servers', -1)) }}" class="{{ $sectionInputClass }}">
            </x-settings-row>
        </x-settings-section>

        <x-settings-section>
            <div class="px-5 pt-4 pb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Bounce servers') }}</div>
            <x-settings-row :title="__('Must add bounce server')" :description="__('Require customers to add a bounce server.')">
                <x-settings-toggle name="servers[permissions][must_add_bounce_server]" :checked="(bool) old('servers.permissions.must_add_bounce_server', data_get($effectiveSettings, 'servers.permissions.must_add_bounce_server', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Access bounce servers')" :description="__('Allow customers to view bounce server pages.')">
                <x-settings-toggle name="servers[permissions][can_access_bounce_servers]" :checked="(bool) old('servers.permissions.can_access_bounce_servers', data_get($effectiveSettings, 'servers.permissions.can_access_bounce_servers', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Can add bounce servers')" :description="__('Allow customers to add their own bounce servers.')">
                <x-settings-toggle name="servers[permissions][can_add_bounce_servers]" :checked="(bool) old('servers.permissions.can_add_bounce_servers', data_get($effectiveSettings, 'servers.permissions.can_add_bounce_servers', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Can edit bounce servers')" :description="__('Allow customers to edit existing bounce servers.')">
                <x-settings-toggle name="servers[permissions][can_edit_bounce_servers]" :checked="(bool) old('servers.permissions.can_edit_bounce_servers', data_get($effectiveSettings, 'servers.permissions.can_edit_bounce_servers', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Can delete bounce servers')" :description="__('Allow customers to delete bounce servers.')">
                <x-settings-toggle name="servers[permissions][can_delete_bounce_servers]" :checked="(bool) old('servers.permissions.can_delete_bounce_servers', data_get($effectiveSettings, 'servers.permissions.can_delete_bounce_servers', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Max bounce servers')" :description="__('Maximum bounce servers a customer can add. Set -1 for unlimited.')">
                <input type="number" min="-1" name="servers[limits][max_bounce_servers]" value="{{ old('servers.limits.max_bounce_servers', data_get($effectiveSettings, 'servers.limits.max_bounce_servers', -1)) }}" class="{{ $sectionInputClass }}">
            </x-settings-row>
        </x-settings-section>

        <x-settings-section>
            <div class="px-5 pt-4 pb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Reply servers') }}</div>
            <x-settings-row :title="__('Must add reply server')" :description="__('Require customers to add a reply server.')">
                <x-settings-toggle name="servers[permissions][must_add_reply_server]" :checked="(bool) old('servers.permissions.must_add_reply_server', data_get($effectiveSettings, 'servers.permissions.must_add_reply_server', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Access reply servers')" :description="__('Allow customers to view reply server pages.')">
                <x-settings-toggle name="servers[permissions][can_access_reply_servers]" :checked="(bool) old('servers.permissions.can_access_reply_servers', data_get($effectiveSettings, 'servers.permissions.can_access_reply_servers', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Can add reply servers')" :description="__('Allow customers to add their own reply servers.')">
                <x-settings-toggle name="servers[permissions][can_add_reply_servers]" :checked="(bool) old('servers.permissions.can_add_reply_servers', data_get($effectiveSettings, 'servers.permissions.can_add_reply_servers', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Can edit reply servers')" :description="__('Allow customers to edit existing reply servers.')">
                <x-settings-toggle name="servers[permissions][can_edit_reply_servers]" :checked="(bool) old('servers.permissions.can_edit_reply_servers', data_get($effectiveSettings, 'servers.permissions.can_edit_reply_servers', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Can delete reply servers')" :description="__('Allow customers to delete reply servers.')">
                <x-settings-toggle name="servers[permissions][can_delete_reply_servers]" :checked="(bool) old('servers.permissions.can_delete_reply_servers', data_get($effectiveSettings, 'servers.permissions.can_delete_reply_servers', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Max reply servers')" :description="__('Maximum reply servers a customer can add. Set -1 for unlimited.')">
                <input type="number" min="-1" name="servers[limits][max_reply_servers]" value="{{ old('servers.limits.max_reply_servers', data_get($effectiveSettings, 'servers.limits.max_reply_servers', -1)) }}" class="{{ $sectionInputClass }}">
            </x-settings-row>
        </x-settings-section>
    </div>
</section>

<section x-show="activePlanSection === 'tracking_domains'" x-cloak>
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Tracking Domain') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Control tracking domain access and limits.') }}</p>
        </div>
        <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600">{{ __('Save') }}</button>
    </div>
    <x-settings-section>
        <x-settings-row :title="__('Must add tracking domain')" :description="__('Require customers to add at least one tracking domain.')">
            <x-settings-toggle name="domains[tracking_domains][must_add]" :checked="(bool) old('domains.tracking_domains.must_add', data_get($effectiveSettings, 'domains.tracking_domains.must_add', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Access')" :description="__('Allow customers to view and manage tracking domains.')">
            <x-settings-toggle name="domains[tracking_domains][can_manage]" :checked="(bool) old('domains.tracking_domains.can_manage', data_get($effectiveSettings, 'domains.tracking_domains.can_manage', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Max tracking domains')" :description="__('Maximum tracking domains a customer can add. Set -1 for unlimited.')">
            <input type="number" min="-1" name="domains[tracking_domains][max_tracking_domains]" value="{{ old('domains.tracking_domains.max_tracking_domains', data_get($effectiveSettings, 'domains.tracking_domains.max_tracking_domains', -1)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
    </x-settings-section>
</section>

<section x-show="activePlanSection === 'sending_domains'" x-cloak>
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Sending Domain') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Control sending domain access and limits.') }}</p>
        </div>
        <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600">{{ __('Save') }}</button>
    </div>
    <x-settings-section>
        <x-settings-row :title="__('Must add sending domain')" :description="__('Require customers to add at least one sending domain.')">
            <x-settings-toggle name="domains[sending_domains][must_add]" :checked="(bool) old('domains.sending_domains.must_add', data_get($effectiveSettings, 'domains.sending_domains.must_add', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Access')" :description="__('Allow customers to view and manage sending domains.')">
            <x-settings-toggle name="domains[sending_domains][can_manage]" :checked="(bool) old('domains.sending_domains.can_manage', data_get($effectiveSettings, 'domains.sending_domains.can_manage', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Max sending domains')" :description="__('Maximum sending domains a customer can add. Set -1 for unlimited.')">
            <input type="number" min="-1" name="domains[sending_domains][max_sending_domains]" value="{{ old('domains.sending_domains.max_sending_domains', data_get($effectiveSettings, 'domains.sending_domains.max_sending_domains', -1)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
    </x-settings-section>
</section>

<section x-show="activePlanSection === 'sending_quota'" x-cloak>
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Email Sending Quota') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Set daily, weekly, and monthly sending quotas.') }}</p>
        </div>
        <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600">{{ __('Save') }}</button>
    </div>
    <x-settings-section>
        <x-settings-row :title="__('Daily sending quota')" :description="__('Maximum emails this plan can send per day. Set -1 for unlimited.')">
            <input type="number" min="-1" name="sending_quota[daily_quota]" value="{{ old('sending_quota.daily_quota', data_get($effectiveSettings, 'sending_quota.daily_quota', -1)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        <x-settings-row :title="__('Weekly sending quota')" :description="__('Maximum emails this plan can send per week. Set -1 for unlimited.')">
            <input type="number" min="-1" name="sending_quota[weekly_quota]" value="{{ old('sending_quota.weekly_quota', data_get($effectiveSettings, 'sending_quota.weekly_quota', -1)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        <x-settings-row :title="__('Monthly sending quota')" :description="__('Maximum emails this plan can send per month. Set -1 for unlimited.')">
            <input type="number" min="-1" name="sending_quota[monthly_quota]" value="{{ old('sending_quota.monthly_quota', data_get($effectiveSettings, 'sending_quota.monthly_quota', -1)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
    </x-settings-section>
</section>

<section x-show="activePlanSection === 'email_validation'" x-cloak>
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Email Validation') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Manage email validation features and providers.') }}</p>
        </div>
        <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600">{{ __('Save') }}</button>
    </div>
    <x-settings-section>
        <x-settings-row :title="__('Access')" :description="__('Allow customers in this plan to use email validation.')">
            <x-settings-toggle name="email_validation[access]" :checked="(bool) old('email_validation.access', data_get($effectiveSettings, 'email_validation.access', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Must add own tool')" :description="__('Require customers to add their own validation provider keys.')">
            <x-settings-toggle name="email_validation[must_add]" :checked="(bool) old('email_validation.must_add', data_get($effectiveSettings, 'email_validation.must_add', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Max number of tools to add')" :description="__('Maximum number of validation providers a customer can add.')">
            <input type="number" min="-1" name="email_validation[max_tools]" value="{{ old('email_validation.max_tools', data_get($effectiveSettings, 'email_validation.max_tools', -1)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        <x-settings-row :title="__('Monthly validation limit')" :description="__('Maximum email validations allowed per month. Set -1 for unlimited.')">
            <input type="number" min="-1" name="email_validation[monthly_limit]" value="{{ old('email_validation.monthly_limit', data_get($effectiveSettings, 'email_validation.monthly_limit', -1)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
    </x-settings-section>
</section>

<section x-show="activePlanSection === 'supabase'" x-cloak>
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Supabase') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Configure Supabase access modes, BYOS options, and usage limits.') }}</p>
        </div>
        <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600">{{ __('Save') }}</button>
    </div>
    <x-settings-section>
        <x-settings-row :title="__('Access Supabase')" :description="__('Allow customers in this plan to connect Supabase for their apps.')">
            <x-settings-toggle name="supabase[access]" :checked="(bool) old('supabase.access', data_get($effectiveSettings, 'supabase.access', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Allow admin-managed Supabase')" :description="__('Customers can use the admin preset instead of bringing their own project.')">
            <x-settings-toggle name="supabase[allow_admin_managed]" :checked="(bool) old('supabase.allow_admin_managed', data_get($effectiveSettings, 'supabase.allow_admin_managed', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Allow BYOS')" :description="__('Customers can connect their own Supabase project.')">
            <x-settings-toggle name="supabase[allow_byos]" :checked="(bool) old('supabase.allow_byos', data_get($effectiveSettings, 'supabase.allow_byos', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Allow one-click BYOS')" :description="__('Show the Supabase OAuth one-click connector for BYOS users.')">
            <x-settings-toggle name="supabase[allow_byos_oauth]" :checked="(bool) old('supabase.allow_byos_oauth', data_get($effectiveSettings, 'supabase.allow_byos_oauth', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Allow manual BYOS')" :description="__('Show the manual credentials form for BYOS users.')">
            <x-settings-toggle name="supabase[allow_byos_manual]" :checked="(bool) old('supabase.allow_byos_manual', data_get($effectiveSettings, 'supabase.allow_byos_manual', true))" />
        </x-settings-row>
        <x-settings-row :title="__('Max admin-managed projects')" :description="__('Maximum admin-managed Supabase projects per customer. Set -1 for unlimited.')">
            <input type="number" min="0" name="supabase[limits][max_admin_managed_projects]" value="{{ old('supabase.limits.max_admin_managed_projects', data_get($effectiveSettings, 'supabase.limits.max_admin_managed_projects', 0)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        <x-settings-row :title="__('Max BYOS projects')" :description="__('Maximum BYOS Supabase projects per customer. Set -1 for unlimited.')">
            <input type="number" min="0" name="supabase[limits][max_byos_projects]" value="{{ old('supabase.limits.max_byos_projects', data_get($effectiveSettings, 'supabase.limits.max_byos_projects', 0)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        <x-settings-row :title="__('Max storage (GB)')" :description="__('Storage limit for admin-managed Supabase usage. Set -1 for unlimited.')">
            <input type="number" min="0" name="supabase[limits][max_storage_gb]" value="{{ old('supabase.limits.max_storage_gb', data_get($effectiveSettings, 'supabase.limits.max_storage_gb', 0)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        <x-settings-row :title="__('Max monthly active users')" :description="__('MAU limit for admin-managed Supabase usage. Set -1 for unlimited.')">
            <input type="number" min="0" name="supabase[limits][max_monthly_active_users]" value="{{ old('supabase.limits.max_monthly_active_users', data_get($effectiveSettings, 'supabase.limits.max_monthly_active_users', 0)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
    </x-settings-section>
</section>

<section x-show="activePlanSection === 'ai'" x-cloak>
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('AI') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Configure AI feature access and credit limits.') }}</p>
        </div>
        <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600">{{ __('Save') }}</button>
    </div>
    <x-settings-section>
        <x-settings-row :title="__('Customer must use their API keys')" :description="__('Require customers to supply their own AI provider API keys.')">
            <x-settings-toggle name="ai[must_use_own_keys]" :checked="(bool) old('ai.must_use_own_keys', data_get($effectiveSettings, 'ai.must_use_own_keys', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Token limit')" :description="__('Token quota applied when customers use the admin-provided keys.')">
            <input type="number" min="0" name="ai[token_limit]" value="{{ old('ai.token_limit', data_get($effectiveSettings, 'ai.token_limit', 0)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        <x-settings-row :title="__('Image generation credits')" :description="__('Image credits applied when customers use the admin-provided keys.')">
            <input type="number" min="0" name="ai[image_credits]" value="{{ old('ai.image_credits', data_get($effectiveSettings, 'ai.image_credits', 0)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
    </x-settings-section>
</section>

<section x-show="activePlanSection === 'ai_builder'" x-cloak>
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('AI Builder') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Control AI Builder models, features, and credit system.') }}</p>
        </div>
        <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600">{{ __('Save') }}</button>
    </div>
    <x-settings-section>
        <div class="px-5 py-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">{{ __('Model access') }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ __('All models are visible, but only the checked ones can be selected. Locked models show an upgrade prompt.') }}</p>
            <div class="space-y-4">
                @foreach(($aiProviderModels ?? []) as $provider => $models)
                    <div>
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">{{ ucfirst($provider) }}</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($models as $model)
                                @php
                                    $enabled = in_array($model, (array) data_get($effectiveSettings, 'ai_builder.models.enabled', []), true);
                                @endphp
                                <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                                    <input type="checkbox" name="ai_builder[models][enabled][]" value="{{ $model }}" {{ $enabled ? 'checked' : '' }} class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    <span class="text-sm text-gray-700 dark:text-gray-200">{{ $model }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <x-settings-row :title="__('Discuss button')" :description="__('Allow customers to use the Discuss feature in the AI Builder.')">
            <x-settings-toggle name="ai_builder[permissions][can_discuss]" :checked="(bool) old('ai_builder.permissions.can_discuss', data_get($effectiveSettings, 'ai_builder.permissions.can_discuss', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Project sharing')" :description="__('Allow customers to share AI Builder projects.')">
            <x-settings-toggle name="ai_builder[permissions][can_share_project]" :checked="(bool) old('ai_builder.permissions.can_share_project', data_get($effectiveSettings, 'ai_builder.permissions.can_share_project', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Code preview')" :description="__('Allow customers to view generated code.')">
            <x-settings-toggle name="ai_builder[permissions][can_code_preview]" :checked="(bool) old('ai_builder.permissions.can_code_preview', data_get($effectiveSettings, 'ai_builder.permissions.can_code_preview', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Code edit')" :description="__('Allow customers to edit generated code.')">
            <x-settings-toggle name="ai_builder[permissions][can_code_edit]" :checked="(bool) old('ai_builder.permissions.can_code_edit', data_get($effectiveSettings, 'ai_builder.permissions.can_code_edit', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Live preview edit / selection')" :description="__('Allow customers to edit or select elements directly in the live preview.')">
            <x-settings-toggle name="ai_builder[permissions][can_live_preview_edit]" :checked="(bool) old('ai_builder.permissions.can_live_preview_edit', data_get($effectiveSettings, 'ai_builder.permissions.can_live_preview_edit', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Custom domains')" :description="__('Allow customers to add custom domains to published pages.')">
            <x-settings-toggle name="ai_builder[permissions][can_custom_domain]" :checked="(bool) old('ai_builder.permissions.can_custom_domain', data_get($effectiveSettings, 'ai_builder.permissions.can_custom_domain', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Bring your own Supabase (BYOS)')" :description="__('Allow customers to connect their own Supabase project.')">
            <x-settings-toggle name="ai_builder[permissions][can_byos]" :checked="(bool) old('ai_builder.permissions.can_byos', data_get($effectiveSettings, 'ai_builder.permissions.can_byos', false))" />
        </x-settings-row>

        <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">{{ __('Credit system') }}</h3>
            <x-settings-row :title="__('Credit mode')" :description="__('Choose how AI Builder usage is charged.')">
                <select name="ai_builder[credit_system]" class="{{ $sectionInputClass }}">
                    <option value="token_based" {{ old('ai_builder.credit_system', data_get($effectiveSettings, 'ai_builder.credit_system', 'token_based')) === 'token_based' ? 'selected' : '' }}>{{ __('Token based') }}</option>
                    <option value="generation_based" {{ old('ai_builder.credit_system', data_get($effectiveSettings, 'ai_builder.credit_system', 'token_based')) === 'generation_based' ? 'selected' : '' }}>{{ __('Generation based') }}</option>
                </select>
            </x-settings-row>
            <x-settings-row :title="__('Daily credit limit')" :description="__('Max credits a customer can use per day. Set -1 for unlimited daily usage.')">
                <input type="number" min="0" step="any" name="ai_builder[daily_limit]" value="{{ old('ai_builder.daily_limit', data_get($effectiveSettings, 'ai_builder.daily_limit', 0)) }}" class="{{ $sectionInputClass }}">
            </x-settings-row>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-4">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Token based') }}</h4>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 dark:text-white">{{ __('Total credits') }}</label>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('Total credits available per billing period. Set -1 for unlimited.') }}</p>
                        <input type="number" min="0" step="any" name="ai_builder[token_based][total_credits]" value="{{ old('ai_builder.token_based.total_credits', data_get($effectiveSettings, 'ai_builder.token_based.total_credits', 0)) }}" class="mt-1.5 {{ $sectionInputClass }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 dark:text-white">{{ __('Tokens per credit') }}</label>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('How many AI tokens each credit represents.') }}</p>
                        <input type="number" min="1" step="any" name="ai_builder[token_based][tokens_per_credit]" value="{{ old('ai_builder.token_based.tokens_per_credit', data_get($effectiveSettings, 'ai_builder.token_based.tokens_per_credit', 1000)) }}" class="mt-1.5 {{ $sectionInputClass }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 dark:text-white">{{ __('Credits per iteration') }}</label>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('Credits deducted for each normal iteration.') }}</p>
                        <input type="number" min="0" step="any" name="ai_builder[token_based][credits_per_iteration]" value="{{ old('ai_builder.token_based.credits_per_iteration', data_get($effectiveSettings, 'ai_builder.token_based.credits_per_iteration', 1)) }}" class="mt-1.5 {{ $sectionInputClass }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 dark:text-white">{{ __('Credits per selection iteration') }}</label>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('Credits deducted for each selection-based iteration.') }}</p>
                        <input type="number" min="0" step="any" name="ai_builder[token_based][credits_per_selection_iteration]" value="{{ old('ai_builder.token_based.credits_per_selection_iteration', data_get($effectiveSettings, 'ai_builder.token_based.credits_per_selection_iteration', 1)) }}" class="mt-1.5 {{ $sectionInputClass }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 dark:text-white">{{ __('Credits per discussion') }}</label>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('Credits deducted for each discussion message.') }}</p>
                        <input type="number" min="0" step="any" name="ai_builder[token_based][credits_per_discussion]" value="{{ old('ai_builder.token_based.credits_per_discussion', data_get($effectiveSettings, 'ai_builder.token_based.credits_per_discussion', 1)) }}" class="mt-1.5 {{ $sectionInputClass }}">
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-4">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Generation based') }}</h4>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 dark:text-white">{{ __('Total credits') }}</label>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('Total credits available per billing period. Set -1 for unlimited.') }}</p>
                        <input type="number" min="0" step="any" name="ai_builder[generation_based][total_credits]" value="{{ old('ai_builder.generation_based.total_credits', data_get($effectiveSettings, 'ai_builder.generation_based.total_credits', 0)) }}" class="mt-1.5 {{ $sectionInputClass }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 dark:text-white">{{ __('Credits per generation') }}</label>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('Credits deducted for each full page generation.') }}</p>
                        <input type="number" min="0" step="any" name="ai_builder[generation_based][credits_per_generation]" value="{{ old('ai_builder.generation_based.credits_per_generation', data_get($effectiveSettings, 'ai_builder.generation_based.credits_per_generation', 1)) }}" class="mt-1.5 {{ $sectionInputClass }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 dark:text-white">{{ __('Credits per iteration') }}</label>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('Credits deducted for each normal iteration.') }}</p>
                        <input type="number" min="0" step="any" name="ai_builder[generation_based][credits_per_iteration]" value="{{ old('ai_builder.generation_based.credits_per_iteration', data_get($effectiveSettings, 'ai_builder.generation_based.credits_per_iteration', 1)) }}" class="mt-1.5 {{ $sectionInputClass }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 dark:text-white">{{ __('Credits per discussion') }}</label>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('Credits deducted for each discussion message.') }}</p>
                        <input type="number" min="0" step="any" name="ai_builder[generation_based][credits_per_discussion]" value="{{ old('ai_builder.generation_based.credits_per_discussion', data_get($effectiveSettings, 'ai_builder.generation_based.credits_per_discussion', 1)) }}" class="mt-1.5 {{ $sectionInputClass }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">{{ __('Platform badge') }}</h3>
            <x-settings-row :title="__('Show platform badge')" :description="__('Display a badge on published AI Builder apps for customers in this plan.')">
                <x-settings-toggle name="ai_builder[platform_badge][enabled]" :checked="(bool) old('ai_builder.platform_badge.enabled', data_get($effectiveSettings, 'ai_builder.platform_badge.enabled', false))" />
            </x-settings-row>
            <x-settings-row :title="__('Badge text')" :description="__('Custom text shown inside the platform badge (e.g. Built with MailerFox).')">
                <input type="text" name="ai_builder[platform_badge][text]" value="{{ old('ai_builder.platform_badge.text', data_get($effectiveSettings, 'ai_builder.platform_badge.text', 'Built with MailerFox')) }}" class="{{ $sectionInputClass }}" placeholder="Built with MailerFox">
            </x-settings-row>
        </div>
    </x-settings-section>
</section>

@if($workspaceAddonActive)
<section x-show="activePlanSection === 'workspaces'" x-cloak>
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Workspaces') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Control workspace creation and per-workspace limits.') }}</p>
        </div>
        <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600">{{ __('Save') }}</button>
    </div>
    <x-settings-section>
        <x-settings-row :title="__('Can create workspaces')" :description="__('Allow customers in this plan to create and manage workspaces.')">
            <x-settings-toggle name="workspaces[permissions][can_create_workspace]" :checked="(bool) old('workspaces.permissions.can_create_workspace', data_get($effectiveSettings, 'workspaces.permissions.can_create_workspace', false))" />
        </x-settings-row>
        <x-settings-row :title="__('Max workspaces')" :description="__('Maximum workspaces a customer can create. Set -1 for unlimited.')">
            <input type="number" min="0" name="workspaces[limits][max_workspaces]" value="{{ old('workspaces.limits.max_workspaces', data_get($effectiveSettings, 'workspaces.limits.max_workspaces', 0)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        <x-settings-row :title="__('Max team members per workspace')" :description="__('Cap on team members in each workspace. Set -1 for unlimited.')">
            <input type="number" min="0" name="workspaces[limits][max_team_members]" value="{{ old('workspaces.limits.max_team_members', data_get($effectiveSettings, 'workspaces.limits.max_team_members', 0)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        <x-settings-row :title="__('Max email lists per workspace')" :description="__('Cap on email lists per workspace. Set -1 for unlimited.')">
            <input type="number" min="0" name="workspaces[limits][max_email_lists]" value="{{ old('workspaces.limits.max_email_lists', data_get($effectiveSettings, 'workspaces.limits.max_email_lists', 0)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        <x-settings-row :title="__('Max templates per workspace')" :description="__('Cap on templates per workspace. Set -1 for unlimited.')">
            <input type="number" min="0" name="workspaces[limits][max_templates]" value="{{ old('workspaces.limits.max_templates', data_get($effectiveSettings, 'workspaces.limits.max_templates', 0)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        <x-settings-row :title="__('Max campaigns per workspace')" :description="__('Cap on campaigns per workspace. Set -1 for unlimited.')">
            <input type="number" min="0" name="workspaces[limits][max_campaigns]" value="{{ old('workspaces.limits.max_campaigns', data_get($effectiveSettings, 'workspaces.limits.max_campaigns', 0)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        @if($outreachAddonActive)
        <x-settings-row :title="__('Max outreach campaigns per workspace')" :description="__('Cap on outreach campaigns per workspace. Set -1 for unlimited.')">
            <input type="number" min="0" name="workspaces[limits][max_outreach_campaigns]" value="{{ old('workspaces.limits.max_outreach_campaigns', data_get($effectiveSettings, 'workspaces.limits.max_outreach_campaigns', 0)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        @endif
        <x-settings-row :title="__('Max inbox accounts per workspace')" :description="__('Cap on inbox accounts per workspace. Set -1 for unlimited.')">
            <input type="number" min="0" name="workspaces[limits][max_inbox_accounts]" value="{{ old('workspaces.limits.max_inbox_accounts', data_get($effectiveSettings, 'workspaces.limits.max_inbox_accounts', 0)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        <x-settings-row :title="__('Max delivery servers per workspace')" :description="__('Cap on delivery servers per workspace. Set -1 for unlimited.')">
            <input type="number" min="0" name="workspaces[limits][max_delivery_servers]" value="{{ old('workspaces.limits.max_delivery_servers', data_get($effectiveSettings, 'workspaces.limits.max_delivery_servers', 0)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        <x-settings-row :title="__('Max bounce servers per workspace')" :description="__('Cap on bounce servers per workspace. Set -1 for unlimited.')">
            <input type="number" min="0" name="workspaces[limits][max_bounce_servers]" value="{{ old('workspaces.limits.max_bounce_servers', data_get($effectiveSettings, 'workspaces.limits.max_bounce_servers', 0)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        <x-settings-row :title="__('Max sending domains per workspace')" :description="__('Cap on sending domains per workspace. Set -1 for unlimited.')">
            <input type="number" min="0" name="workspaces[limits][max_sending_domains]" value="{{ old('workspaces.limits.max_sending_domains', data_get($effectiveSettings, 'workspaces.limits.max_sending_domains', 0)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
        <x-settings-row :title="__('Max tracking domains per workspace')" :description="__('Cap on tracking domains per workspace. Set -1 for unlimited.')">
            <input type="number" min="0" name="workspaces[limits][max_tracking_domains]" value="{{ old('workspaces.limits.max_tracking_domains', data_get($effectiveSettings, 'workspaces.limits.max_tracking_domains', 0)) }}" class="{{ $sectionInputClass }}">
        </x-settings-row>
    </x-settings-section>
</section>
@endif
