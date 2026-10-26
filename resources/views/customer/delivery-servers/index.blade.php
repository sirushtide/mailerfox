@extends('layouts.customer')

@section('title', 'Delivery Servers')
@section('page-title', 'Delivery Servers')

@section('page-actions')
    <div class="mp-toolbar w-full justify-end">
        <form method="GET" action="{{ route('customer.delivery-servers.index') }}" class="mp-toolbar w-full sm:w-auto">
            <select name="type" class="mp-input w-full sm:w-auto">
                <option value="">All Types</option>
                <option value="smtp" {{ ($filters['type'] ?? '') === 'smtp' ? 'selected' : '' }}>SMTP</option>
                <option value="gmail" {{ ($filters['type'] ?? '') === 'gmail' ? 'selected' : '' }}>Gmail</option>
                <option value="outlook" {{ ($filters['type'] ?? '') === 'outlook' ? 'selected' : '' }}>Outlook</option>
                <option value="sendmail" {{ ($filters['type'] ?? '') === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                <option value="zeptomail" {{ ($filters['type'] ?? '') === 'zeptomail' ? 'selected' : '' }}>ZeptoMail</option>
                <option value="amazon-ses" {{ ($filters['type'] ?? '') === 'amazon-ses' ? 'selected' : '' }}>Amazon SES</option>
                <option value="mailgun" {{ ($filters['type'] ?? '') === 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                <option value="sendgrid" {{ ($filters['type'] ?? '') === 'sendgrid' ? 'selected' : '' }}>SendGrid</option>
                <option value="postmark" {{ ($filters['type'] ?? '') === 'postmark' ? 'selected' : '' }}>Postmark</option>
                <option value="sparkpost" {{ ($filters['type'] ?? '') === 'sparkpost' ? 'selected' : '' }}>SparkPost</option>
                <option value="resend" {{ ($filters['type'] ?? '') === 'resend' ? 'selected' : '' }}>Resend</option>
                <option value="emailit" {{ ($filters['type'] ?? '') === 'emailit' ? 'selected' : '' }}>Emailit</option>
            </select>
            <select name="status" class="mp-input w-full sm:w-auto">
                <option value="">All Statuses</option>
                <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
            </select>
            <x-button type="submit" variant="primary" class="w-full sm:w-auto">Search</x-button>
        </form>

        <x-button href="{{ route('customer.delivery-servers.webhook-events') }}" variant="secondary" class="w-full sm:w-auto">Webhook Logs</x-button>
        @customercan('servers.permissions.can_create_delivery_servers')
            <x-button type="button" variant="primary" class="w-full sm:w-auto" data-track="delivery-server.create" @click="$dispatch('open-delivery-server-drawer')">Add Server</x-button>
        @endcustomercan
    </div>
@endsection

@section('content')
<script>
window.deliveryServerDrawerConfig = {
    canUseExtendedMailboxProviders: @json((bool) ($canUseExtendedMailboxProviders ?? false)),
    storeUrl: @json(route('customer.delivery-servers.store')),
    fetchSendGridDomainsUrl: @json(route('customer.delivery-servers.fetch-sendgrid-domains')),
    fetchEmailitDomainsUrl: @json(route('customer.delivery-servers.fetch-emailit-domains')),
    gmailOAuthUrl: @json(route('customer.oauth.gmail.redirect')),
    csrfToken: @json(csrf_token())
};
</script>
@verbatim
<script>
function deliveryServerDrawer() {
    const cfg = window.deliveryServerDrawerConfig || {};
    return {
        drawerOpen: false,
        drawerMode: 'create',
        editingServerId: null,
        selectedProvider: '',
        viewDrawerOpen: false,
        viewingServer: null,
        canUseExtendedMailboxProviders: !!cfg.canUseExtendedMailboxProviders,
        providerDefaults: {
            'gmail': { name: 'Gmail', type: 'gmail', flow: 'smtp', hostname: 'smtp.gmail.com', port: 587, encryption: 'tls' },
            'outlook': { name: 'Outlook', type: 'outlook', flow: 'smtp', hostname: 'smtp.office365.com', port: 587, encryption: 'tls' },
            'smtp': { name: 'Other SMTP', type: 'smtp', flow: 'smtp', hostname: '', port: 587, encryption: 'tls' },
            'mailgun': { name: 'Mailgun', type: 'mailgun', flow: 'api' },
            'sendgrid': { name: 'SendGrid', type: 'sendgrid', flow: 'api' },
            'sparkpost': { name: 'SparkPost', type: 'sparkpost', flow: 'api' },
            'resend': { name: 'Resend', type: 'resend', flow: 'api' },
            'amazon-ses': { name: 'Amazon SES', type: 'amazon-ses', flow: 'api' },
            'postmark': { name: 'Postmark', type: 'postmark', flow: 'api' },
            'zeptomail-api': { name: 'ZeptoMail API', type: 'zeptomail-api', flow: 'api' },
            'emailit': { name: 'Emailit', type: 'emailit', flow: 'api' },
            'gmail-api': { name: 'Gmail API (OAuth2)', type: 'gmail-api', flow: 'api' }
        },

        openDrawer() {
            this.drawerOpen = true;
            this.drawerMode = 'create';
            this.editingServerId = null;
            this.selectedProvider = '';
            this.resetForm();
        },

        openEditDrawer(server) {
            this.drawerOpen = true;
            this.drawerMode = 'edit';
            this.editingServerId = server.id;
            this.resetForm();

            const form = document.getElementById('delivery-server-form');
            const methodInput = document.getElementById('delivery-server-form-method');
            const providerSelect = document.getElementById('provider-select');
            const selectedType = document.getElementById('selected-type');
            const selectedFlow = document.getElementById('selected-flow');
            const selectedProviderName = document.getElementById('selected-provider-name');
            const serverName = document.getElementById('server-name');
            const serverStatus = document.getElementById('server-status');
            const smtpHostname = document.getElementById('smtp-hostname');
            const smtpPort = document.getElementById('smtp-port');
            const smtpUsername = document.getElementById('smtp-username');
            const smtpEncryption = document.getElementById('smtp-encryption');
            const fromName = document.getElementById('from-name');
            const dailyQuota = document.getElementById('daily-quota');
            const notes = document.getElementById('notes');
            const useFor = document.querySelector('input[name=use_for]');
            const useForTransactional = document.querySelector('input[name=use_for_transactional]');
            const useAsReplyServer = document.querySelector('input[name=use_as_reply_server]');
            const useAsBounceServer = document.querySelector('input[name=use_as_bounce_server]');

            if (form) {
                form.action = server.update_url;
            }

            if (methodInput) {
                methodInput.disabled = false;
                methodInput.value = 'PUT';
            }

            this.selectedProvider = server.type;
            if (providerSelect) providerSelect.value = server.type;

            this.onProviderChange();

            if (selectedType) selectedType.value = server.type;
            if (selectedFlow) selectedFlow.value = this.providerDefaults[server.type]?.flow || 'smtp';
            if (selectedProviderName) selectedProviderName.textContent = this.providerDefaults[server.type]?.name || server.type;
            if (serverName) serverName.value = server.name || '';
            if (serverStatus) serverStatus.value = server.status || 'pending';
            if (smtpHostname) smtpHostname.value = server.hostname || '';
            if (smtpPort) smtpPort.value = server.port || '';
            if (smtpUsername) smtpUsername.value = server.username || '';
            if (smtpEncryption) smtpEncryption.value = server.encryption || 'tls';
            if (fromName) fromName.value = server.from_name || '';
            if (dailyQuota) dailyQuota.value = server.daily_quota ?? 0;
            if (notes) notes.value = server.notes || '';
            if (useFor) useFor.checked = !!server.use_for;
            if (useForTransactional) useForTransactional.checked = !!server.use_for_transactional;
            if (useAsReplyServer) useAsReplyServer.checked = false;
            if (useAsBounceServer) useAsBounceServer.checked = false;

            const mailgunDomain = document.getElementById('mailgun-domain');
            const sendgridApiKey = document.getElementById('sendgrid-api-key');
            const sparkpostApiKey = document.getElementById('sparkpost-api-key');
            const sparkpostDomain = document.getElementById('settings_sending_domain_index');
            const sparkpostPrefix = document.getElementById('from_email_prefix_index');
            const sparkpostFromEmail = document.getElementById('from_email_index');
            const sparkpostFromEmailText = document.getElementById('full_from_email_index');
            const sesAccessKey = document.getElementById('ses-access-key');
            const sesSecretKey = document.getElementById('ses-secret-key');
            const sesRegion = document.getElementById('ses-region');
            const sesFromEmail = document.getElementById('ses-from-email');
            const postmarkToken = document.getElementById('postmark-token');
            const zeptomailToken = document.getElementById('zeptomail-token');
            const zeptomailMode = document.getElementById('zeptomail-mode');
            const emailitApiKey = document.getElementById('emailit-api-key');

            const mailgunPrefix = document.getElementById('mailgun_from_email_prefix');
            const mailgunDomainDisplay = document.getElementById('mailgun_from_domain_display');
            const mailgunFromEmail = document.getElementById('mailgun_from_email');
            const mailgunFromEmailText = document.getElementById('mailgun_full_from_email');

            if (mailgunDomain) mailgunDomain.value = server.settings?.domain || '';
            if (mailgunDomainDisplay) mailgunDomainDisplay.value = server.settings?.domain || '';
            if (mailgunPrefix) {
                const fromEmail = server.from_email || '';
                mailgunPrefix.value = fromEmail.includes('@') ? fromEmail.split('@')[0] : 'hello';
            }
            if (mailgunFromEmail) mailgunFromEmail.value = server.from_email || '';
            if (mailgunFromEmailText) mailgunFromEmailText.textContent = server.from_email || 'hello@domain.com';

            const sendgridDomain = document.getElementById('sendgrid_sending_domain');
            const sendgridPrefix = document.getElementById('sendgrid_from_email_prefix');
            const sendgridDomainDisplay = document.getElementById('sendgrid_from_domain_display');
            const sendgridFromEmail = document.getElementById('sendgrid_from_email');
            const sendgridFromEmailText = document.getElementById('sendgrid_full_from_email');

            if (sendgridApiKey) sendgridApiKey.value = '';
            if (sendgridDomain) sendgridDomain.value = server.settings?.sending_domain || '';
            if (sendgridDomainDisplay) sendgridDomainDisplay.value = server.settings?.sending_domain || '';
            if (sendgridPrefix) {
                const fromEmail = server.from_email || '';
                sendgridPrefix.value = fromEmail.includes('@') ? fromEmail.split('@')[0] : 'hello';
            }
            if (sendgridFromEmail) sendgridFromEmail.value = server.from_email || '';
            if (sendgridFromEmailText) sendgridFromEmailText.textContent = server.from_email || 'hello@domain.com';

            if (sparkpostApiKey) sparkpostApiKey.value = '';
            if (sesAccessKey) sesAccessKey.value = '';
            if (sesSecretKey) sesSecretKey.value = '';
            if (sesRegion) sesRegion.value = server.settings?.region || 'us-east-1';
            if (sesFromEmail) sesFromEmail.value = server.from_email || '';
            if (postmarkToken) postmarkToken.value = '';
            if (zeptomailToken) zeptomailToken.value = '';
            if (zeptomailMode) zeptomailMode.value = server.settings?.mode || 'raw';
            if (emailitApiKey) emailitApiKey.value = '';

            const emailitDomain = document.getElementById('emailit_sending_domain');
            const emailitPrefix = document.getElementById('emailit_from_email_prefix');
            const emailitFromEmail = document.getElementById('emailit_from_email');
            const emailitFromEmailText = document.getElementById('emailit_full_from_email');

            if (emailitDomain) {
                const sendingDomain = server.settings?.sending_domain || '';
                emailitDomain.innerHTML = '<option value="">Select domain</option>';
                if (sendingDomain !== '') {
                    const option = document.createElement('option');
                    option.value = sendingDomain;
                    option.textContent = sendingDomain;
                    option.selected = true;
                    emailitDomain.appendChild(option);
                }
            }
            if (emailitPrefix) {
                const fromEmail = server.from_email || '';
                emailitPrefix.value = fromEmail.includes('@') ? fromEmail.split('@')[0] : 'hello';
            }
            if (emailitFromEmail) {
                emailitFromEmail.value = server.from_email || '';
            }
            if (emailitFromEmailText) {
                emailitFromEmailText.textContent = server.from_email || 'hello@domain.com';
            }

            if (sparkpostDomain) {
                const sendingDomain = server.settings?.sending_domain || '';
                sparkpostDomain.innerHTML = '<option value="">Select domain</option>';
                if (sendingDomain !== '') {
                    const option = document.createElement('option');
                    option.value = sendingDomain;
                    option.textContent = sendingDomain;
                    option.selected = true;
                    sparkpostDomain.appendChild(option);
                }
            }

            if (sparkpostPrefix) {
                const fromEmail = server.from_email || '';
                sparkpostPrefix.value = fromEmail.includes('@') ? fromEmail.split('@')[0] : 'hello';
            }

            if (sparkpostFromEmail) {
                sparkpostFromEmail.value = server.from_email || '';
            }

            if (sparkpostFromEmailText) {
                sparkpostFromEmailText.textContent = server.from_email || 'hello@domain.com';
            }

            const resendApiKey = document.getElementById('resend-api-key');
            const resendEndpoint = document.getElementById('resend-endpoint');
            const resendDomain = document.getElementById('resend_sending_domain');
            const resendPrefix = document.getElementById('resend_from_email_prefix');
            const resendFromEmail = document.getElementById('resend_from_email');
            const resendFromEmailText = document.getElementById('resend_full_from_email');

            if (resendApiKey) resendApiKey.value = '';
            if (resendEndpoint) resendEndpoint.value = server.settings?.endpoint || 'https://api.resend.com';

            const resendDomainValue = server.settings?.sending_domain || (server.from_email && server.from_email.includes('@') ? server.from_email.split('@')[1] : '');
            if (resendDomain) resendDomain.value = resendDomainValue || '';

            if (resendPrefix) {
                const fromEmail = server.from_email || '';
                resendPrefix.value = fromEmail.includes('@') ? fromEmail.split('@')[0] : 'hello';
            }

            if (resendFromEmail) resendFromEmail.value = server.from_email || '';
            if (resendFromEmailText) resendFromEmailText.textContent = server.from_email || 'hello@domain.com';
        },

        closeDrawer() {
            this.drawerOpen = false;
            this.drawerMode = 'create';
            this.editingServerId = null;
            this.selectedProvider = '';
            this.resetForm();
        },

        resetForm() {
            const form = document.getElementById('delivery-server-form');
            if (form) form.reset();

            const methodInput = document.getElementById('delivery-server-form-method');
            if (form) {
                form.action = cfg.storeUrl || '';
            }
            if (methodInput) {
                methodInput.value = 'POST';
                methodInput.disabled = true;
            }

            const saveServerBtn = document.getElementById('save-server-btn');
            const providerName = document.getElementById('selected-provider-name');
            const fromEmailInput = document.getElementById('from_email_index');
            const fullFromEmail = document.getElementById('full_from_email_index');
            const sendingDomainSelect = document.getElementById('settings_sending_domain_index');

            if (saveServerBtn) saveServerBtn.disabled = true;
            if (providerName) providerName.textContent = '';
            if (fromEmailInput) fromEmailInput.value = '';
            if (fullFromEmail) fullFromEmail.textContent = 'hello@domain.com';
            if (sendingDomainSelect) sendingDomainSelect.innerHTML = '<option value="">Select domain</option>';

            this.hideAllFields();
        },

        hideAllFields() {
            const configSection = document.getElementById('config-section');
            const smtpFields = document.getElementById('smtp-fields');
            const apiFields = document.getElementById('api-fields');
            const mailgunFields = document.getElementById('mailgun-fields');
            const sendgridFields = document.getElementById('sendgrid-fields');
            const resendFields = document.getElementById('resend-fields');
            const sparkpostFields = document.getElementById('sparkpost-fields');
            const amazonSesFields = document.getElementById('amazon-ses-fields');
            const postmarkFields = document.getElementById('postmark-fields');
            const zeptomailApiFields = document.getElementById('zeptomail-api-fields');
            const emailitFields = document.getElementById('emailit-fields');
            const gmailApiFields = document.getElementById('gmail-api-fields');
            const gmailHelp = document.getElementById('gmail-help');
            const additionalServerTypes = document.getElementById('additional-server-types');

            if (configSection) configSection.classList.add('hidden');
            if (smtpFields) smtpFields.classList.add('is-hidden');
            if (apiFields) apiFields.classList.add('is-hidden');
            if (mailgunFields) mailgunFields.classList.add('is-hidden');
            if (sendgridFields) sendgridFields.classList.add('is-hidden');
            if (resendFields) resendFields.classList.add('is-hidden');
            if (sparkpostFields) sparkpostFields.classList.add('is-hidden');
            if (amazonSesFields) amazonSesFields.classList.add('is-hidden');
            if (postmarkFields) postmarkFields.classList.add('is-hidden');
            if (zeptomailApiFields) zeptomailApiFields.classList.add('is-hidden');
            if (emailitFields) emailitFields.classList.add('is-hidden');
            if (gmailApiFields) gmailApiFields.classList.add('is-hidden');
            if (gmailHelp) gmailHelp.classList.add('hidden');
            if (additionalServerTypes) additionalServerTypes.classList.add('hidden');

            this.syncFieldStates();
        },

        syncFieldStates() {
            const form = document.getElementById('delivery-server-form');
            if (!form) return;
            form.querySelectorAll('input, select, textarea').forEach((field) => {
                if (field.id === 'delivery-server-form-method') return;
                const hidden = field.closest('.provider-grid-group.is-hidden') || field.closest('#config-section.hidden');
                field.disabled = !!hidden;
            });
        },

        onProviderChange(event) {
            const providerType = event?.target?.value || this.selectedProvider || document.getElementById('provider-select')?.value;
            this.selectedProvider = providerType || '';
            const config = this.providerDefaults[providerType];
            if (!config) return;

            if ((providerType === 'gmail' || providerType === 'outlook') && !this.canUseExtendedMailboxProviders) {
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'pro-feature-extended-mailbox-providers' }));
                return;
            }

            const selectedType = document.getElementById('selected-type');
            const selectedFlow = document.getElementById('selected-flow');
            const selectedProviderName = document.getElementById('selected-provider-name');
            const saveServerBtn = document.getElementById('save-server-btn');
            const gmailHelp = document.getElementById('gmail-help');

            if (selectedType) selectedType.value = config.type;
            if (selectedFlow) selectedFlow.value = config.flow;
            if (selectedProviderName) selectedProviderName.textContent = config.name;
            if (gmailHelp) gmailHelp.classList.toggle('hidden', providerType !== 'gmail');

            const configSection = document.getElementById('config-section');
            const smtpFields = document.getElementById('smtp-fields');
            const apiFields = document.getElementById('api-fields');
            const mailgunFields = document.getElementById('mailgun-fields');
            const sendgridFields = document.getElementById('sendgrid-fields');
            const resendFields = document.getElementById('resend-fields');
            const sparkpostFields = document.getElementById('sparkpost-fields');
            const amazonSesFields = document.getElementById('amazon-ses-fields');
            const postmarkFields = document.getElementById('postmark-fields');
            const zeptomailApiFields = document.getElementById('zeptomail-api-fields');
            const emailitFields = document.getElementById('emailit-fields');
            const gmailApiFields = document.getElementById('gmail-api-fields');
            const additionalServerTypes = document.getElementById('additional-server-types');

            if (configSection) configSection.classList.remove('hidden');
            if (saveServerBtn) saveServerBtn.disabled = false;

            if (smtpFields) smtpFields.classList.add('is-hidden');
            if (apiFields) apiFields.classList.add('is-hidden');
            if (mailgunFields) mailgunFields.classList.add('is-hidden');
            if (sendgridFields) sendgridFields.classList.add('is-hidden');
            if (resendFields) resendFields.classList.add('is-hidden');
            if (sparkpostFields) sparkpostFields.classList.add('is-hidden');
            if (amazonSesFields) amazonSesFields.classList.add('is-hidden');
            if (postmarkFields) postmarkFields.classList.add('is-hidden');
            if (zeptomailApiFields) zeptomailApiFields.classList.add('is-hidden');
            if (emailitFields) emailitFields.classList.add('is-hidden');
            if (gmailApiFields) gmailApiFields.classList.add('is-hidden');
            if (additionalServerTypes) additionalServerTypes.classList.add('hidden');

            if (config.flow === 'smtp') {
                if (smtpFields) smtpFields.classList.remove('is-hidden');
                if (additionalServerTypes) additionalServerTypes.classList.remove('hidden');
                if (config.hostname) {
                    const hostnameInput = document.getElementById('smtp-hostname');
                    if (hostnameInput) hostnameInput.value = config.hostname;
                }
                if (config.port) {
                    const portInput = document.getElementById('smtp-port');
                    if (portInput) portInput.value = config.port;
                }
                if (config.encryption) {
                    const encryptionInput = document.getElementById('smtp-encryption');
                    if (encryptionInput) encryptionInput.value = config.encryption;
                }
            } else {
                if (apiFields) apiFields.classList.remove('is-hidden');

                switch (providerType) {
                    case 'mailgun':
                        if (mailgunFields) mailgunFields.classList.remove('is-hidden');
                        break;
                    case 'sendgrid':
                        if (sendgridFields) sendgridFields.classList.remove('is-hidden');
                        break;
                    case 'resend':
                        if (resendFields) resendFields.classList.remove('is-hidden');
                        break;
                    case 'sparkpost':
                        if (sparkpostFields) sparkpostFields.classList.remove('is-hidden');
                        break;
                    case 'amazon-ses':
                        if (amazonSesFields) amazonSesFields.classList.remove('is-hidden');
                        break;
                    case 'postmark':
                        if (postmarkFields) postmarkFields.classList.remove('is-hidden');
                        break;
                    case 'zeptomail-api':
                        if (zeptomailApiFields) zeptomailApiFields.classList.remove('is-hidden');
                        break;
                    case 'emailit':
                        if (emailitFields) emailitFields.classList.remove('is-hidden');
                        break;
                    case 'gmail-api':
                        if (gmailApiFields) gmailApiFields.classList.remove('is-hidden');
                        break;
                }
            }

            this.syncFieldStates();

            requestAnimationFrame(() => {
                if (configSection) {
                    configSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        },

        connectGmail() {
            const nameInput = document.getElementById('server-name');
            const name = nameInput ? nameInput.value.trim() : '';
            if (!name) {
                nameInput && nameInput.focus();
                alert('Please enter a server name first.');
                return;
            }
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = cfg.gmailOAuthUrl;
            const csrf = document.createElement('input');
            csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = cfg.csrfToken;
            form.appendChild(csrf);
            const nameField = document.createElement('input');
            nameField.type = 'hidden'; nameField.name = 'name'; nameField.value = name;
            form.appendChild(nameField);
            document.body.appendChild(form);
            form.submit();
        },

        fetchSendGridDomains() {
            const apiKeyInput = document.getElementById('sendgrid-api-key');
            const domainInput = document.getElementById('sendgrid_sending_domain');
            const domainsList = document.getElementById('sendgrid_domains_list');
            const fetchStatus = document.getElementById('sendgrid_fetch_status');
            const apiKey = apiKeyInput ? apiKeyInput.value : '';

            if (!apiKey || apiKey.length < 10 || !domainsList) return;

            if (fetchStatus) {
                fetchStatus.textContent = 'Fetching domains...';
                fetchStatus.classList.remove('hidden', 'text-red-600', 'dark:text-red-400');
                fetchStatus.classList.add('text-gray-500', 'dark:text-gray-400');
            }

            fetch(cfg.fetchSendGridDomainsUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': cfg.csrfToken
                },
                body: JSON.stringify({ api_key: apiKey })
            })
            .then(response => response.json())
            .then(data => {
                if (fetchStatus) fetchStatus.classList.add('hidden');
                domainsList.innerHTML = '';
                if (data.domains && data.domains.length > 0) {
                    data.domains.forEach(domain => {
                        const option = document.createElement('option');
                        option.value = domain.domain;
                        option.label = domain.status ? domain.status : '';
                        domainsList.appendChild(option);
                    });
                    if (domainInput && !domainInput.value.trim()) {
                        domainInput.value = data.domains[0].domain;
                        const prefixInput = document.getElementById('sendgrid_from_email_prefix');
                        const displayInput = document.getElementById('sendgrid_from_domain_display');
                        const fullFromEmail = document.getElementById('sendgrid_full_from_email');
                        const fromEmail = document.getElementById('sendgrid_from_email');
                        const prefix = (prefixInput && prefixInput.value) ? prefixInput.value : 'hello';
                        if (displayInput) displayInput.value = data.domains[0].domain;
                        if (fullFromEmail) fullFromEmail.textContent = prefix + '@' + data.domains[0].domain;
                        if (fromEmail) fromEmail.value = prefix + '@' + data.domains[0].domain;
                    }
                    if (fetchStatus) {
                        fetchStatus.textContent = data.domains.length + ' domain(s) found.';
                        fetchStatus.classList.remove('hidden', 'text-red-600', 'dark:text-red-400');
                        fetchStatus.classList.add('text-gray-500', 'dark:text-gray-400');
                    }
                } else {
                    if (fetchStatus) {
                        fetchStatus.textContent = 'No domains found in this SendGrid account.';
                        fetchStatus.classList.remove('hidden', 'text-gray-500', 'dark:text-gray-400');
                        fetchStatus.classList.add('text-red-600', 'dark:text-red-400');
                    }
                }
            })
            .catch(error => {
                if (fetchStatus) {
                    fetchStatus.textContent = 'Failed to fetch domains: ' + (error.message || 'Unknown error');
                    fetchStatus.classList.remove('hidden', 'text-gray-500', 'dark:text-gray-400');
                    fetchStatus.classList.add('text-red-600', 'dark:text-red-400');
                }
            });
        },

        fetchEmailitDomains() {
            const apiKeyInput = document.getElementById('emailit-api-key');
            const domainSelect = document.getElementById('emailit_sending_domain');
            const apiKey = apiKeyInput ? apiKeyInput.value : '';

            if (!apiKey || apiKey.length < 10 || !domainSelect) return;

            const currentValue = domainSelect.value;
            domainSelect.innerHTML = '<option value="">Loading...</option>';

            fetch(cfg.fetchEmailitDomainsUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': cfg.csrfToken
                },
                body: JSON.stringify({ api_key: apiKey })
            })
            .then(response => response.json())
            .then(data => {
                if (data.domains && data.domains.length > 0) {
                    domainSelect.innerHTML = '<option value="">Select domain</option>';
                    data.domains.forEach(domain => {
                        const option = document.createElement('option');
                        option.value = domain.domain;
                        option.textContent = domain.domain + (domain.status ? ' (' + domain.status + ')' : '');
                        domainSelect.appendChild(option);
                    });
                    if (!currentValue) {
                        domainSelect.value = data.domains[0].domain;
                        const prefixInput = document.getElementById('emailit_from_email_prefix');
                        const fullFromEmail = document.getElementById('emailit_full_from_email');
                        const fromEmail = document.getElementById('emailit_from_email');
                        const prefix = (prefixInput && prefixInput.value) ? prefixInput.value : 'hello';
                        if (fullFromEmail) fullFromEmail.textContent = prefix + '@' + data.domains[0].domain;
                        if (fromEmail) fromEmail.value = prefix + '@' + data.domains[0].domain;
                    }
                } else {
                    domainSelect.innerHTML = '<option value="">No domains found</option>';
                }
            })
            .catch(error => {
                domainSelect.innerHTML = '<option value="">Failed to load</option>';
            });
        },

        onFormSubmit(e) {
            if (!this.selectedProvider) {
                e.preventDefault();
                alert('Please select a delivery server provider.');
                return;
            }
        },

        openViewDrawer(server) {
            this.viewingServer = server || null;
            this.viewDrawerOpen = true;
        },

        closeViewDrawer() {
            this.viewDrawerOpen = false;
            this.viewingServer = null;
        }
    };
}
</script>
@endverbatim
<div
    x-data="deliveryServerDrawer()"
    @open-delivery-server-drawer.window="openDrawer()"
>
    <style>
        .provider-grid-group {
            display: contents;
        }

        .provider-grid-group.is-hidden {
            display: none;
        }
    </style>

    <!-- Sidebar Drawer for Adding Delivery Server -->
    <template x-teleport="body">
        <div
            x-cloak
            x-show="drawerOpen"
            class="fixed inset-0 z-[100]"
            @keydown.window.escape="closeDrawer()"
        >
            <div
                x-show="drawerOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0 bg-slate-900/30 backdrop-blur-sm"
                @click="closeDrawer()"
            ></div>

            <div
                x-show="drawerOpen"
                x-transition:enter="transform transition ease-out duration-300"
                x-transition:enter-start="translate-x-full opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100"
                x-transition:leave="transform transition ease-in duration-250"
                x-transition:leave-start="translate-x-0 opacity-100"
                x-transition:leave-end="translate-x-full opacity-0"
                class="absolute inset-y-0 right-0 w-full max-w-lg bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-800 shadow-2xl will-change-transform"
            >
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                    <div class="text-base font-semibold text-gray-900 dark:text-gray-100" x-text="drawerMode === 'edit' ? 'Edit Delivery Server' : 'Add Delivery Server'"></div>
                    <button type="button" class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800" @click="closeDrawer()" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="h-[calc(100%-64px)] overflow-y-auto p-5">
                    <form id="delivery-server-form" method="POST" action="{{ route('customer.delivery-servers.store') }}" class="space-y-6" @submit="onFormSubmit($event)">
                        @csrf
                        <input type="hidden" name="_method" id="delivery-server-form-method" value="POST" disabled>

                    <div>
                        <label for="provider-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Provider</label>
                        <select
                            id="provider-select"
                            x-model="selectedProvider"
                            @change="onProviderChange($event)"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                        >
                            <option value="">Choose a provider...</option>
                            <optgroup label="Standard SMTP">
                                @if($canUseExtendedMailboxProviders ?? false)
                                    <option value="gmail">Gmail (SMTP with App Password)</option>
                                    <option value="outlook">Outlook (SMTP with App Password)</option>
                                @endif
                                <option value="smtp">Other SMTP (Custom SMTP server)</option>
                            </optgroup>
                            <optgroup label="API Integrations">
                                <option value="mailgun">Mailgun (API Key & Domain)</option>
                                <option value="sendgrid">SendGrid (API Key)</option>
                                <option value="sparkpost">SparkPost (API Key)</option>
                                <option value="resend">Resend (API Key)</option>
                                <option value="amazon-ses">Amazon SES (Access Key & Secret)</option>
                                <option value="postmark">Postmark (API Token)</option>
                                <option value="zeptomail-api">ZeptoMail API (Send Mail Token)</option>
                                <option value="emailit">Emailit (API Key)</option>
                                <option value="gmail-api">Gmail API (OAuth2 — no SMTP ports needed)</option>
                            </optgroup>
                        </select>
                    </div>

                <div id="config-section" class="hidden">
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4"><span x-text="drawerMode === 'edit' ? 'Edit' : 'Configure'"></span> <span id="selected-provider-name"></span> Server</h3>
                        
                        <input type="hidden" name="type" id="selected-type">
                        <input type="hidden" name="flow" id="selected-flow">

                        <div id="gmail-help" class="hidden mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900 dark:border-blue-900/40 dark:bg-blue-900/10 dark:text-blue-100">
                            <div class="font-semibold mb-2">Gmail setup instructions</div>
                            <ol class="list-decimal space-y-1 pl-5">
                                <li>Sign in to your Google account and enable 2-Step Verification.</li>
                                <li>Open Google Account settings and go to Security.</li>
                                <li>Under App passwords, create a new app password for Mail.</li>
                                <li>Use your full Gmail address as Username.</li>
                                <li>Paste the generated app password into the Password field here.</li>
                            </ol>
                            <a href="https://support.google.com/accounts/answer/185833" target="_blank" rel="noopener noreferrer" class="mt-2 inline-block font-medium underline text-blue-700 dark:text-blue-300">Open Google App Password instructions</a>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="server-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Server Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" id="server-name" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>
                            
                            <div>
                                <label for="server-status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                <select name="status" id="server-status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                    <option value="pending">Pending</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <div id="smtp-fields" class="provider-grid-group is-hidden">
                                <div>
                                    <label for="smtp-hostname" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hostname <span class="text-red-500">*</span></label>
                                    <input type="text" name="hostname" id="smtp-hostname" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                </div>
                                <div>
                                    <label for="smtp-port" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Port <span class="text-red-500">*</span></label>
                                    <input type="number" name="port" id="smtp-port" value="587" min="1" max="65535" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                </div>
                                <div>
                                    <label for="smtp-username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username <span class="text-red-500">*</span></label>
                                    <input type="text" name="username" id="smtp-username" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                </div>
                                <div>
                                    <label for="smtp-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password <span class="text-red-500">*</span></label>
                                    <input type="password" name="password" id="smtp-password" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                </div>
                                <div>
                                    <label for="smtp-encryption" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Encryption</label>
                                    <select name="encryption" id="smtp-encryption" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                        <option value="tls">TLS</option>
                                        <option value="ssl">SSL</option>
                                        <option value="none">None</option>
                                    </select>
                                </div>
                            </div>

                            <div id="api-fields" class="provider-grid-group is-hidden">
                                <div id="mailgun-fields" class="provider-grid-group is-hidden">
                                    <div>
                                        <label for="mailgun-domain" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sending Domain <span class="text-red-500">*</span></label>
                                        <input type="text" name="settings[domain]" id="mailgun-domain" placeholder="mg.yourdomain.com" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                    </div>
                                    <div>
                                        <label for="mailgun-api-key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">API Key <span class="text-red-500">*</span></label>
                                        <input type="password" name="settings[secret]" id="mailgun-api-key" placeholder="key-xxxxxxxxxxxxxxxxxxxx" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            <a href="https://app.mailgun.com/settings/api_security" target="_blank" class="text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 underline">Get API key here</a>
                                        </p>
                                    </div>
                                    <div class="mt-2 sm:col-span-2">
                                        <label for="mailgun_from_email_prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Email <span class="text-red-500">*</span></label>
                                        <div class="mt-1 flex rounded-md shadow-sm">
                                            <input type="text" name="from_email_prefix" id="mailgun_from_email_prefix" placeholder="hello" class="flex-1 min-w-0 block w-full px-2 py-1.5 rounded-l-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm">
                                            <span class="inline-flex items-center px-2 border-l-0 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 text-xs">
                                                @
                                            </span>
                                            <input type="text" id="mailgun_from_domain_display" readonly placeholder="yourdomain.com" class="flex-1 min-w-0 block w-full px-2 py-1.5 rounded-r-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 text-sm cursor-not-allowed">
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Full from email: <span id="mailgun_full_from_email" class="font-medium">hello@domain.com</span></p>
                                        <input type="hidden" name="from_email" id="mailgun_from_email">
                                    </div>
                                </div>

                                <div id="sendgrid-fields" class="provider-grid-group is-hidden">
                                    <div>
                                        <label for="sendgrid-api-key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">API Key <span class="text-red-500">*</span></label>
                                        <input type="password" name="settings[api_key]" id="sendgrid-api-key" placeholder="SG.xxxxx" @blur="fetchSendGridDomains()" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                    </div>
                                    <div>
                                        <label for="sendgrid_sending_domain" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sending Domain <span class="text-red-500">*</span></label>
                                        <input type="text" name="settings[sending_domain]" id="sendgrid_sending_domain" list="sendgrid_domains_list" placeholder="yourdomain.com" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                        <datalist id="sendgrid_domains_list"></datalist>
                                        <p id="sendgrid_fetch_status" class="mt-1 text-xs text-gray-500 dark:text-gray-400 hidden"></p>
                                    </div>
                                    <div class="mt-2 sm:col-span-2">
                                        <label for="sendgrid_from_email_prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Email <span class="text-red-500">*</span></label>
                                        <div class="mt-1 flex rounded-md shadow-sm">
                                            <input type="text" name="from_email_prefix" id="sendgrid_from_email_prefix" placeholder="hello" class="flex-1 min-w-0 block w-full px-2 py-1.5 rounded-l-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm">
                                            <span class="inline-flex items-center px-2 border-l-0 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 text-xs">
                                                @
                                            </span>
                                            <input type="text" id="sendgrid_from_domain_display" readonly placeholder="yourdomain.com" class="flex-1 min-w-0 block w-full px-2 py-1.5 rounded-r-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 text-sm cursor-not-allowed">
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Full from email: <span id="sendgrid_full_from_email" class="font-medium">hello@domain.com</span></p>
                                        <input type="hidden" name="from_email" id="sendgrid_from_email">
                                    </div>
                                </div>

                                <div id="resend-fields" class="provider-grid-group is-hidden">
                                    <div>
                                        <label for="resend-api-key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">API Key <span class="text-red-500">*</span></label>
                                        <input type="password" name="settings[api_key]" id="resend-api-key" placeholder="re_..." class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                    </div>
                                    <div>
                                        <label for="resend-endpoint" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Endpoint</label>
                                        <input type="text" name="settings[endpoint]" id="resend-endpoint" value="https://api.resend.com" placeholder="https://api.resend.com" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                    </div>
                                    <div class="sm:col-span-2 rounded-md bg-green-50 dark:bg-green-900/20 p-3">
                                        <p class="text-xs font-medium text-green-800 dark:text-green-200 mb-1">Webhook Auto-Configuration</p>
                                        <p class="text-xs text-green-700 dark:text-green-300">Webhook will be created automatically at Resend using your API key on save.</p>
                                        <code class="text-xs text-green-700 dark:text-green-300 break-all mt-1 block">{{ url('/webhooks/resend') }}</code>
                                    </div>
                                    <div class="mt-2 sm:col-span-2">
                                        <label for="resend_from_email_prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Email <span class="text-red-500">*</span></label>
                                        <div class="mt-1 flex rounded-md shadow-sm">
                                            <input type="text" name="from_email_prefix" id="resend_from_email_prefix" placeholder="hello" class="flex-1 min-w-0 block w-full px-2 py-1.5 rounded-l-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm">
                                            <span class="inline-flex items-center px-2 border-l-0 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 text-xs">
                                                @
                                            </span>
                                            <input type="text" name="settings[sending_domain]" id="resend_sending_domain" list="resend_domains_list" placeholder="yourdomain.com" autocomplete="off" class="flex-1 min-w-0 block w-full px-2 py-1.5 rounded-r-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm">
                                            <datalist id="resend_domains_list"></datalist>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Use a domain verified in your Resend account. Full from email: <span id="resend_full_from_email" class="font-medium">hello@domain.com</span></p>
                                        <input type="hidden" name="from_email" id="resend_from_email">
                                    </div>
                                </div>

                                <div id="sparkpost-fields" class="provider-grid-group is-hidden">
                                    <div>
                                        <label for="sparkpost-api-key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">API Key <span class="text-red-500">*</span></label>
                                        <input type="password" name="settings[api_key]" id="sparkpost-api-key" placeholder="xxxxxxxxxxxxxxxxxxxx" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                    </div>
                                    <div class="sm:col-span-2 mt-2">
                                        <label for="from_email_prefix_index" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Email</label>
                                        <div class="mt-1 flex rounded-md shadow-sm">
                                            <input type="text" name="from_email_prefix" id="from_email_prefix_index" placeholder="hello" class="flex-1 min-w-0 block w-full px-2 py-1.5 rounded-l-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm">
                                            <span class="inline-flex items-center px-2 border-l-0 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 text-xs">
                                                @
                                            </span>
                                            <select name="settings[sending_domain]" id="settings_sending_domain_index" class="flex-1 min-w-0 block w-full px-2 py-1.5 rounded-r-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm">
                                                <option value="">Select domain</option>
                                            </select>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Full from email will be: <span id="full_from_email_index" class="font-medium">hello@domain.com</span></p>
                                        <input type="hidden" name="from_email" id="from_email_index">
                                    </div>
                                </div>

                                <div id="amazon-ses-fields" class="provider-grid-group is-hidden">
                                    <div>
                                        <label for="ses-access-key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Access Key ID <span class="text-red-500">*</span></label>
                                        <input type="text" name="settings[key]" id="ses-access-key" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                    </div>
                                    <div>
                                        <label for="ses-secret-key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Secret Access Key <span class="text-red-500">*</span></label>
                                        <input type="password" name="settings[secret]" id="ses-secret-key" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                    </div>
                                    <div>
                                        <label for="ses-region" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Region</label>
                                        <select name="settings[region]" id="ses-region" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                            <option value="us-east-2">US East (Ohio) -> us-east-2</option>
                                            <option value="us-east-1">US East (N. Virginia) -> us-east-1</option>
                                            <option value="us-west-2">US West (Oregon) -> us-west-2</option>
                                            <option value="ap-south-1">Asia Pacific (Mumbai) -> ap-south-1</option>
                                            <option value="ap-northeast-2">Asia Pacific (Seoul) -> ap-northeast-2</option>
                                            <option value="ap-southeast-1">Asia Pacific (Singapore) -> ap-southeast-1</option>
                                            <option value="ap-southeast-2">Asia Pacific (Sydney) -> ap-southeast-2</option>
                                            <option value="ap-northeast-1">Asia Pacific (Tokyo) -> ap-northeast-1</option>
                                            <option value="ca-central-1">Canada (Central) -> ca-central-1</option>
                                            <option value="eu-central-1">Europe (Frankfurt) -> eu-central-1</option>
                                            <option value="eu-west-1">Europe (Ireland) -> eu-west-1</option>
                                            <option value="eu-west-2">Europe (London) -> eu-west-2</option>
                                            <option value="eu-south-1">Europe (Milan) -> eu-south-1</option>
                                            <option value="eu-north-1">Europe (Stockholm) -> eu-north-1</option>
                                            <option value="sa-east-1">South America (Sao Paulo) -> sa-east-1</option>
                                            <option value="us-gov-west-1">AWS GovCloud (US West) -> us-gov-west-1</option>
                                            <option value="us-gov-east-1">AWS GovCloud (US East) -> us-gov-east-1</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="ses-from-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Email <span class="text-red-500">*</span></label>
                                        <input type="email" name="from_email" id="ses-from-email" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                    </div>
                                </div>

                                <div id="postmark-fields" class="provider-grid-group is-hidden">
                                    <div>
                                        <label for="postmark-token" class="block text-sm font-medium text-gray-700 dark:text-gray-300">API Token <span class="text-red-500">*</span></label>
                                        <input type="password" name="settings[token]" id="postmark-token" placeholder="pm-xxxxxxxxxxxxxxxxxxxx" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                    </div>
                                </div>

                                <div id="zeptomail-api-fields" class="provider-grid-group is-hidden">
                                    <div>
                                        <label for="zeptomail-token" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Send Mail Token <span class="text-red-500">*</span></label>
                                        <input type="password" name="settings[send_mail_token]" id="zeptomail-token" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                    </div>
                                    <div>
                                        <label for="zeptomail-mode" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mode</label>
                                        <select name="settings[mode]" id="zeptomail-mode" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                            <option value="raw">Raw HTML/Text</option>
                                            <option value="template">Template</option>
                                        </select>
                                    </div>
                                </div>

                                <div id="emailit-fields" class="provider-grid-group is-hidden">
                                    <div>
                                        <label for="emailit-api-key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Emailit API Key <span class="text-red-500">*</span></label>
                                        <input type="password" name="settings[api_key]" id="emailit-api-key" placeholder="Emailit API Key" @blur="fetchEmailitDomains()" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                    </div>
                                    <div class="col-span-2">
                                        <label for="emailit_from_email_prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Email</label>
                                        <div class="mt-1 flex rounded-md shadow-sm">
                                            <input type="text" name="from_email_prefix" id="emailit_from_email_prefix" placeholder="hello" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                            <span class="inline-flex items-center px-3 border-l-0 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 sm:text-sm">
                                                @
                                            </span>
                                            <select name="settings[sending_domain]" id="emailit_sending_domain" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                                                <option value="">Select domain</option>
                                            </select>
                                        </div>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Full from email will be: <span id="emailit_full_from_email" class="font-medium">hello@domain.com</span></p>
                                        <input type="hidden" name="from_email" id="emailit_from_email" value="">
                                    </div>
                                </div>

                                <div id="gmail-api-fields" class="provider-grid-group is-hidden">
                                    <div class="col-span-2 space-y-4">
                                        <div class="rounded-lg border border-blue-200 dark:border-blue-700 p-4 bg-blue-50 dark:bg-blue-900/20 flex items-start gap-3">
                                            <svg class="w-5 h-5 mt-0.5 text-blue-600 dark:text-blue-400 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                                            <div>
                                                <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Connect Gmail via OAuth2</p>
                                                <p class="text-xs text-blue-700 dark:text-blue-300 mt-0.5">Sends email over HTTPS — no SMTP ports required. You'll be redirected to Google to authorize access.</p>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            @click="connectGmail()"
                                            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 shadow-sm"
                                        >
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                                            Connect to Gmail
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="from-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Name</label>
                                <input type="text" name="from_name" id="from-name" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>

                            <div>
                                <label for="daily-quota" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Daily Quota (0 = unlimited)</label>
                                <input type="number" name="daily_quota" id="daily-quota" value="0" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                            </div>

                            <div class="sm:col-span-2">
                                <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                                <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"></textarea>
                            </div>
 
                            <div class="sm:col-span-2">
                                <div class="flex items-center space-x-6">
                                    <label class="flex items-center">
                                        <input type="checkbox" name="use_for" value="1" checked class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Use for campaigns</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="use_for_transactional" value="1" class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Use for transactional</span>
                                    </label>
                                </div>
                            </div>

                            <div id="additional-server-types" class="hidden sm:col-span-2">
                                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Also use this server as</div>
                                    <div class="mt-3 flex items-center space-x-6">
                                        <label class="flex items-center">
                                            <input type="checkbox" name="use_as_reply_server" value="1" class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Reply server</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" name="use_as_bounce_server" value="1" class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Bounce server</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" @click="closeDrawer()" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="submit" id="save-server-btn" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        <span x-text="drawerMode === 'edit' ? 'Update Server' : 'Save Server'"></span>
                    </button>
                </div>
            </form>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div
            x-cloak
            x-show="viewDrawerOpen"
            class="fixed inset-0 z-[100]"
            @keydown.window.escape="closeViewDrawer()"
        >
            <div
                x-show="viewDrawerOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0 bg-slate-900/30 backdrop-blur-sm"
                @click="closeViewDrawer()"
            ></div>

            <div
                x-show="viewDrawerOpen"
                x-transition:enter="transform transition ease-out duration-300"
                x-transition:enter-start="translate-x-full opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100"
                x-transition:leave="transform transition ease-in duration-200"
                x-transition:leave-start="translate-x-0 opacity-100"
                x-transition:leave-end="translate-x-full opacity-0"
                class="absolute inset-y-0 right-0 w-full max-w-lg bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-800 shadow-2xl will-change-transform"
            >
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                    <div class="text-base font-semibold text-gray-900 dark:text-gray-100">Delivery Server Details</div>
                    <button type="button" class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800" @click="closeViewDrawer()" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="h-[calc(100%-64px)] overflow-y-auto p-5" x-show="viewingServer">
                    <template x-if="viewingServer">
                        <div class="space-y-5 text-sm">
                            <div>
                                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</div>
                                <div class="mt-1 font-medium text-gray-900 dark:text-gray-100" x-text="viewingServer.name || '—'"></div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</div>
                                    <div class="mt-1 text-gray-900 dark:text-gray-100" x-text="viewingServer.type || '—'"></div>
                                </div>
                                <div>
                                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</div>
                                    <div class="mt-1">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                            :class="viewingServer.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'"
                                            x-text="viewingServer.status || '—'"
                                        ></span>
                                    </div>
                                </div>
                            </div>

                            <template x-if="!['mailgun','sendgrid','sparkpost','resend','postmark','zeptomail-api','gmail-api','emailit'].includes(viewingServer.type)">
                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Hostname</div>
                                            <div class="mt-1 text-gray-900 dark:text-gray-100 break-all" x-text="viewingServer.hostname || '—'"></div>
                                        </div>
                                        <div>
                                            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Port</div>
                                            <div class="mt-1 text-gray-900 dark:text-gray-100" x-text="viewingServer.port || '—'"></div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Username</div>
                                            <div class="mt-1 text-gray-900 dark:text-gray-100 break-all" x-text="viewingServer.username || '—'"></div>
                                        </div>
                                        <div>
                                            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Encryption</div>
                                            <div class="mt-1 text-gray-900 dark:text-gray-100" x-text="viewingServer.encryption || '—'"></div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">From Email</div>
                                    <div class="mt-1 text-gray-900 dark:text-gray-100 break-all" x-text="viewingServer.from_email || '—'"></div>
                                </div>
                                <div>
                                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">From Name</div>
                                    <div class="mt-1 text-gray-900 dark:text-gray-100" x-text="viewingServer.from_name || '—'"></div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Daily Quota</div>
                                    <div class="mt-1 text-gray-900 dark:text-gray-100" x-text="viewingServer.daily_quota ?? '—'"></div>
                                </div>
                                <div>
                                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Created</div>
                                    <div class="mt-1 text-gray-900 dark:text-gray-100" x-text="viewingServer.created_at || '—'"></div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Use for Campaigns</div>
                                    <div class="mt-1 text-gray-900 dark:text-gray-100" x-text="viewingServer.use_for ? 'Yes' : 'No'"></div>
                                </div>
                                <div>
                                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Use for Transactional</div>
                                    <div class="mt-1 text-gray-900 dark:text-gray-100" x-text="viewingServer.use_for_transactional ? 'Yes' : 'No'"></div>
                                </div>
                            </div>

                            <template x-if="['mailgun','sendgrid','sparkpost','resend','postmark','zeptomail-api'].includes(viewingServer.type)">
                                <div class="border-t border-gray-100 dark:border-gray-800 pt-4">
                                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Webhook Status</div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Configured</div>
                                            <div class="mt-1">
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                    :class="viewingServer.settings && (viewingServer.settings.resend_webhook_id || viewingServer.settings.webhook_secret) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                                    x-text="viewingServer.settings && (viewingServer.settings.resend_webhook_id || viewingServer.settings.webhook_secret) ? 'Yes' : 'No'"
                                                ></span>
                                            </div>
                                        </div>
                                        <template x-if="viewingServer.settings && viewingServer.settings.resend_webhook_configured_at">
                                            <div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">Configured At</div>
                                                <div class="mt-1 text-gray-900 dark:text-gray-100" x-text="viewingServer.settings.resend_webhook_configured_at"></div>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="mt-3">
                                        <a href="{{ route('customer.delivery-servers.webhook-events') }}" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">
                                            View Webhook Logs →
                                        </a>
                                    </div>
                                </div>
                            </template>

                            <template x-if="viewingServer.settings && (viewingServer.settings.domain || viewingServer.settings.sending_domain || viewingServer.settings.region || viewingServer.settings.mode)">
                                <div class="border-t border-gray-100 dark:border-gray-800 pt-4">
                                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Provider Settings</div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <template x-if="viewingServer.settings.domain">
                                            <div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">Domain</div>
                                                <div class="mt-1 text-gray-900 dark:text-gray-100 break-all" x-text="viewingServer.settings.domain"></div>
                                            </div>
                                        </template>
                                        <template x-if="viewingServer.settings.sending_domain">
                                            <div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">Sending Domain</div>
                                                <div class="mt-1 text-gray-900 dark:text-gray-100 break-all" x-text="viewingServer.settings.sending_domain"></div>
                                            </div>
                                        </template>
                                        <template x-if="viewingServer.settings.region">
                                            <div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">Region</div>
                                                <div class="mt-1 text-gray-900 dark:text-gray-100" x-text="viewingServer.settings.region"></div>
                                            </div>
                                        </template>
                                        <template x-if="viewingServer.settings.mode">
                                            <div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">Mode</div>
                                                <div class="mt-1 text-gray-900 dark:text-gray-100" x-text="viewingServer.settings.mode"></div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <template x-if="viewingServer.notes">
                                <div class="border-t border-gray-100 dark:border-gray-800 pt-4">
                                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Notes</div>
                                    <div class="text-gray-900 dark:text-gray-100 whitespace-pre-wrap" x-text="viewingServer.notes"></div>
                                </div>
                            </template>

                            <template x-if="viewingServer.can_edit || viewingServer.can_delete">
                                <div class="border-t border-gray-100 dark:border-gray-800 pt-4">
                                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Actions</div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <template x-if="viewingServer.can_edit">
                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-md bg-primary-600 text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                                                @click="const s = viewingServer; closeViewDrawer(); $nextTick(() => openEditDrawer(s));"
                                            >
                                                <x-lucide name="pencil" class="h-4 w-4" />
                                                Edit
                                            </button>
                                        </template>

                                        <template x-if="viewingServer.can_edit && viewingServer.test_connection_url">
                                            <form :action="viewingServer.test_connection_url" method="POST" class="inline">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-md border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-100 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 19a1 1 0 0 1-1-1v-2a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a1 1 0 0 1-1 1z"/><path d="M17 21v-2"/><path d="M19 14V6.5a1 1 0 0 0-7 0v11a1 1 0 0 1-7 0V10"/><path d="M21 21v-2"/><path d="M3 5V3"/><path d="M4 10a2 2 0 0 1-2-2V6a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2a2 2 0 0 1-2 2z"/><path d="M7 5V3"/></svg>
                                                    Test Connection
                                                </button>
                                            </form>
                                        </template>

                                        <template x-if="viewingServer.can_delete && viewingServer.destroy_url">
                                            <form :action="viewingServer.destroy_url" method="POST" class="inline" onsubmit="return confirm('Delete this delivery server? This cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-md border border-red-200 dark:border-red-900/50 text-red-700 dark:text-red-300 bg-white dark:bg-gray-800 hover:bg-red-50 dark:hover:bg-red-900/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                                >
                                                    <x-lucide name="trash-2" class="h-4 w-4" />
                                                    Delete
                                                </button>
                                            </form>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <template x-if="viewingServer.can_edit && viewingServer.test_email_url">
                                <div class="border-t border-gray-100 dark:border-gray-800 pt-4">
                                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Send Test Email</div>
                                    <form :action="viewingServer.test_email_url" method="POST" class="space-y-3">
                                        @csrf
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Recipient</label>
                                            <input
                                                type="email"
                                                name="to_email"
                                                required
                                                placeholder="you@example.com"
                                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                            >
                                        </div>
                                        <div class="flex justify-end">
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-md bg-primary-600 text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                                            >
                                                <x-lucide name="send" class="h-4 w-4" />
                                                Send Test Email
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>

    <x-modal name="pro-feature-extended-mailbox-providers" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Extended License Feature</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                Gmail and Outlook mailbox providers are available on the Extended License.
            </p>
            <div class="mt-6 flex justify-end">
                <x-button type="button" variant="secondary" @click="$dispatch('close-modal', 'pro-feature-extended-mailbox-providers')">Close</x-button>
            </div>
        </div>
    </x-modal>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="mp-table">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Owner</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Hostname</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($deliveryServers as $server)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $server->name }}{{ $server->customer_id ? '' : ' (System)' }}
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $server->customer_id ? 'Mine' : 'System' }}
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $server->type }}
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $server->hostname ?? '—' }}
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $server->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst($server->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-right text-sm font-medium">
                                @php
                                    $authCustomer = auth('customer')->user();
                                    $canEditServer = (bool) ($server->customer_id && $authCustomer && method_exists($authCustomer, 'groupAllows') && $authCustomer->groupAllows('servers.permissions.can_edit_delivery_servers'));
                                    $canDeleteServer = (bool) ($server->customer_id && $authCustomer && method_exists($authCustomer, 'groupAllows') && $authCustomer->groupAllows('servers.permissions.can_delete_delivery_servers'));
                                    $viewServerPayload = [
                                        'id' => $server->id,
                                        'name' => $server->name,
                                        'type' => $server->type,
                                        'status' => $server->status,
                                        'hostname' => $server->hostname,
                                        'port' => $server->port,
                                        'username' => $server->username,
                                        'encryption' => $server->encryption,
                                        'from_email' => $server->from_email,
                                        'from_name' => $server->from_name,
                                        'daily_quota' => $server->daily_quota,
                                        'notes' => $server->notes,
                                        'use_for' => (bool) $server->use_for,
                                        'use_for_transactional' => (bool) $server->use_for_transactional,
                                        'created_at' => optional($server->created_at)->format('Y-m-d H:i'),
                                        'settings' => [
                                            'domain' => data_get($server->settings, 'domain'),
                                            'sending_domain' => data_get($server->settings, 'sending_domain'),
                                            'region' => data_get($server->settings, 'region'),
                                            'mode' => data_get($server->settings, 'mode'),
                                            'resend_webhook_id' => data_get($server->settings, 'resend_webhook_id'),
                                            'webhook_secret' => data_get($server->settings, 'webhook_secret'),
                                            'resend_webhook_configured_at' => data_get($server->settings, 'resend_webhook_configured_at'),
                                        ],
                                        'can_edit' => (bool) $canEditServer,
                                        'can_delete' => (bool) $canDeleteServer,
                                        'update_url' => $canEditServer ? route('customer.delivery-servers.update', $server) : null,
                                        'destroy_url' => $canDeleteServer ? route('customer.delivery-servers.destroy', $server) : null,
                                        'test_connection_url' => $canEditServer ? route('customer.delivery-servers.test-connection', $server) : null,
                                        'test_email_url' => $canEditServer ? route('customer.delivery-servers.test-email', $server) : null,
                                    ];
                                @endphp
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        x-on:click="openViewDrawer(JSON.parse($el.dataset.server))"
                                        data-server="{{ json_encode($viewServerPayload) }}"
                                        title="View"
                                        aria-label="View"
                                        class="inline-flex items-center justify-center p-2 rounded-lg bg-white border border-gray-200 text-gray-900 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 dark:hover:bg-gray-700"
                                    >
                                        <x-lucide name="eye" class="h-4 w-4" />
                                        <span class="sr-only">View</span>
                                    </button>
                                    @if($server->customer_id)
                                        @customercan('servers.permissions.can_edit_delivery_servers')
                                            @php
                                                $editServerPayload = [
                                                    'id' => $server->id,
                                                    'update_url' => route('customer.delivery-servers.update', $server),
                                                    'type' => $server->type,
                                                    'name' => $server->name,
                                                    'status' => $server->status,
                                                    'hostname' => $server->hostname,
                                                    'port' => $server->port,
                                                    'username' => $server->username,
                                                    'encryption' => $server->encryption,
                                                    'from_email' => $server->from_email,
                                                    'from_name' => $server->from_name,
                                                    'daily_quota' => $server->daily_quota,
                                                    'notes' => $server->notes,
                                                    'use_for' => (bool) $server->use_for,
                                                    'use_for_transactional' => (bool) $server->use_for_transactional,
                                                    'settings' => [
                                                        'domain' => data_get($server->settings, 'domain'),
                                                        'sending_domain' => data_get($server->settings, 'sending_domain'),
                                                        'region' => data_get($server->settings, 'region'),
                                                        'mode' => data_get($server->settings, 'mode'),
                                                    ],
                                                ];
                                            @endphp
                                            <button
                                                type="button"
                                                x-on:click="openEditDrawer(JSON.parse($el.dataset.server))"
                                                data-server="{{ json_encode($editServerPayload) }}"
                                                title="Edit"
                                                aria-label="Edit"
                                                class="inline-flex items-center justify-center p-2 rounded-lg bg-white border border-gray-200 text-gray-900 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 dark:hover:bg-gray-700"
                                            >
                                                <x-lucide name="pencil" class="h-4 w-4" />
                                                <span class="sr-only">Edit</span>
                                            </button>
                                        @endcustomercan

                                        @customercan('servers.permissions.can_delete_delivery_servers')
                                            <form method="POST" action="{{ route('customer.delivery-servers.destroy', $server) }}" class="inline" onsubmit="return confirm('Are you sure?');">
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
                            <td colspan="6">
                                <div class="mp-empty">
                                    <div class="mp-empty__icon">
                                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11h3.75a2 2 0 0 1 1.6.8l.45.6a4 4 0 0 0 6.4 0l.45-.6a2 2 0 0 1 1.6-.8H21"/><path d="M3 7h18"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                                    </div>
                                    <p class="mp-empty__title">No delivery servers found</p>
                                    <p class="mp-empty__desc">
                                        @customercan('servers.permissions.can_create_delivery_servers')
                                            <button type="button" x-on:click="$dispatch('open-delivery-server-drawer')" class="font-medium" style="color: var(--tw-brand)">Add your first server</button>
                                        @else
                                            Delivery servers will appear here once they are added.
                                        @endcustomercan
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($deliveryServers->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">{{ $deliveryServers->links() }}</div>
        @endif
    </x-card>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // SparkPost sending domain auto-fetch for index page
    const apiKeyInputIndex = document.getElementById('sparkpost-api-key');
    const sendingDomainSelectIndex = document.getElementById('settings_sending_domain_index');
    const fromEmailPrefixIndex = document.getElementById('from_email_prefix_index');
    const fromEmailInputIndex = document.getElementById('from_email_index');
    const fullFromEmailIndex = document.getElementById('full_from_email_index');

    function updateFromEmailIndex() {
        const prefix = fromEmailPrefixIndex.value || 'hello';
        const domain = sendingDomainSelectIndex.value || 'domain.com';
        fullFromEmailIndex.textContent = prefix + '@' + domain;
        fromEmailInputIndex.value = prefix + '@' + domain;
    }

    if (fromEmailPrefixIndex) {
        fromEmailPrefixIndex.addEventListener('input', updateFromEmailIndex);
    }
    if (sendingDomainSelectIndex) {
        sendingDomainSelectIndex.addEventListener('change', updateFromEmailIndex);
    }

    // Initialize with default values
    updateFromEmailIndex();

    function fetchDomainsIndex() {
        const apiKey = apiKeyInputIndex.value;
        if (!apiKey || apiKey.length < 10) return;

        sendingDomainSelectIndex.innerHTML = '<option value="">Loading...</option>';

        fetch('{{ route('customer.delivery-servers.fetch-sparkpost-domains') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ api_key: apiKey })
        })
        .then(response => response.json())
        .then(data => {
            if (data.domains && data.domains.length > 0) {
                sendingDomainSelectIndex.innerHTML = '<option value="">Select domain</option>';
                data.domains.forEach(domain => {
                    const option = document.createElement('option');
                    option.value = domain.domain;
                    option.textContent = domain.domain + (domain.status ? ' (' + domain.status + ')' : '');
                    sendingDomainSelectIndex.appendChild(option);
                });
                if (!sendingDomainSelectIndex.value) {
                    sendingDomainSelectIndex.value = data.domains[0].domain;
                }
                updateFromEmailIndex();
            } else {
                sendingDomainSelectIndex.innerHTML = '<option value="">No domains found</option>';
            }
        })
        .catch(error => {
            sendingDomainSelectIndex.innerHTML = '<option value="">Failed to load</option>';
        });
    }

    if (apiKeyInputIndex) {
        apiKeyInputIndex.addEventListener('blur', fetchDomainsIndex);
        apiKeyInputIndex.addEventListener('change', fetchDomainsIndex);
    }

    // Resend sending domain auto-fetch (suggestions) + from email builder
    const resendApiKey = document.getElementById('resend-api-key');
    const resendDomainInput = document.getElementById('resend_sending_domain');
    const resendDomainsList = document.getElementById('resend_domains_list');
    const resendPrefix = document.getElementById('resend_from_email_prefix');
    const resendFromEmail = document.getElementById('resend_from_email');
    const resendFullFromEmail = document.getElementById('resend_full_from_email');

    function updateResendFromEmail() {
        const prefix = ((resendPrefix && resendPrefix.value) || 'hello').trim();
        const domain = ((resendDomainInput && resendDomainInput.value) || '').trim();
        if (resendFullFromEmail) resendFullFromEmail.textContent = (prefix || 'hello') + '@' + (domain || 'domain.com');
        if (resendFromEmail) resendFromEmail.value = domain ? ((prefix || 'hello') + '@' + domain) : '';
    }

    if (resendPrefix) resendPrefix.addEventListener('input', updateResendFromEmail);
    if (resendDomainInput) resendDomainInput.addEventListener('input', updateResendFromEmail);

    updateResendFromEmail();

    function fetchResendDomains() {
        const apiKey = resendApiKey ? resendApiKey.value : '';
        if (!apiKey || apiKey.length < 10 || !resendDomainsList) return;

        fetch('{{ route('customer.delivery-servers.fetch-resend-domains') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ api_key: apiKey })
        })
        .then(response => response.json())
        .then(data => {
            resendDomainsList.innerHTML = '';
            if (data.domains && data.domains.length > 0) {
                data.domains.forEach(domain => {
                    const option = document.createElement('option');
                    option.value = domain.domain;
                    if (domain.status) option.label = domain.status;
                    resendDomainsList.appendChild(option);
                });
                if (resendDomainInput && !resendDomainInput.value.trim()) {
                    resendDomainInput.value = data.domains[0].domain;
                    updateResendFromEmail();
                }
            }
        })
        .catch(error => {});
    }

    if (resendApiKey) {
        resendApiKey.addEventListener('blur', fetchResendDomains);
        resendApiKey.addEventListener('change', fetchResendDomains);
    }

    // Mailgun from email builder
    const mailgunPrefixInput = document.getElementById('mailgun_from_email_prefix');
    const mailgunDomainInput = document.getElementById('mailgun-domain');
    const mailgunDomainDisplay = document.getElementById('mailgun_from_domain_display');
    const mailgunFromEmail = document.getElementById('mailgun_from_email');
    const mailgunFullFromEmail = document.getElementById('mailgun_full_from_email');

    function updateMailgunFromEmail() {
        const prefix = ((mailgunPrefixInput && mailgunPrefixInput.value) || 'hello').trim();
        const domain = ((mailgunDomainInput && mailgunDomainInput.value) || '').trim();
        if (mailgunDomainDisplay) mailgunDomainDisplay.value = domain;
        if (mailgunFullFromEmail) mailgunFullFromEmail.textContent = (prefix || 'hello') + '@' + (domain || 'domain.com');
        if (mailgunFromEmail) mailgunFromEmail.value = domain ? ((prefix || 'hello') + '@' + domain) : '';
    }

    if (mailgunPrefixInput) mailgunPrefixInput.addEventListener('input', updateMailgunFromEmail);
    if (mailgunDomainInput) mailgunDomainInput.addEventListener('input', updateMailgunFromEmail);

    updateMailgunFromEmail();

    // SendGrid from email builder
    const sendgridPrefixInput = document.getElementById('sendgrid_from_email_prefix');
    const sendgridDomainInput = document.getElementById('sendgrid_sending_domain');
    const sendgridDomainDisplay = document.getElementById('sendgrid_from_domain_display');
    const sendgridFromEmail = document.getElementById('sendgrid_from_email');
    const sendgridFullFromEmail = document.getElementById('sendgrid_full_from_email');

    function updateSendgridFromEmail() {
        const prefix = ((sendgridPrefixInput && sendgridPrefixInput.value) || 'hello').trim();
        const domain = ((sendgridDomainInput && sendgridDomainInput.value) || '').trim();
        if (sendgridDomainDisplay) sendgridDomainDisplay.value = domain;
        if (sendgridFullFromEmail) sendgridFullFromEmail.textContent = (prefix || 'hello') + '@' + (domain || 'domain.com');
        if (sendgridFromEmail) sendgridFromEmail.value = domain ? ((prefix || 'hello') + '@' + domain) : '';
    }

    if (sendgridPrefixInput) sendgridPrefixInput.addEventListener('input', updateSendgridFromEmail);
    if (sendgridDomainInput) sendgridDomainInput.addEventListener('input', updateSendgridFromEmail);

    updateSendgridFromEmail();

    // Emailit from email builder
    const emailitPrefixInput = document.getElementById('emailit_from_email_prefix');
    const emailitDomainSelect = document.getElementById('emailit_sending_domain');
    const emailitFromEmail = document.getElementById('emailit_from_email');
    const emailitFullFromEmail = document.getElementById('emailit_full_from_email');

    function updateEmailitFromEmail() {
        const prefix = ((emailitPrefixInput && emailitPrefixInput.value) || 'hello').trim();
        const domain = ((emailitDomainSelect && emailitDomainSelect.value) || '').trim();
        if (emailitFullFromEmail) emailitFullFromEmail.textContent = (prefix || 'hello') + '@' + (domain || 'domain.com');
        if (emailitFromEmail) emailitFromEmail.value = domain ? ((prefix || 'hello') + '@' + domain) : '';
    }

    if (emailitPrefixInput) emailitPrefixInput.addEventListener('input', updateEmailitFromEmail);
    if (emailitDomainSelect) emailitDomainSelect.addEventListener('change', updateEmailitFromEmail);

    updateEmailitFromEmail();
});
</script>
@endsection
