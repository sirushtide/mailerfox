import React, { useEffect, useState } from 'react';

const formatCellValue = (value) => {
    if (value === null || value === undefined) return '—';
    if (typeof value === 'object') return JSON.stringify(value);
    if (typeof value === 'boolean') return value ? 'true' : 'false';
    return String(value);
};

const OverviewIcon = () => (
     <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
         <path d="m12 14 4-4" />
         <path d="M3.34 19a10 10 0 1 1 17.32 0" />
     </svg>
 );

 const UsersIcon = () => (
     <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
         <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
         <circle cx="12" cy="7" r="4" />
     </svg>
 );

 const DataIcon = () => (
     <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
         <ellipse cx="12" cy="5" rx="9" ry="3" />
         <path d="M3 5V19A9 3 0 0 0 21 19V5" />
         <path d="M3 12A9 3 0 0 0 21 12" />
     </svg>
 );

 const AnalyticsIcon = () => (
     <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
         <path d="M3 3v16a2 2 0 0 0 2 2h16" />
         <path d="M18 17V9" />
         <path d="M13 17V5" />
         <path d="M8 17v-3" />
     </svg>
 );

 const DomainIcon = () => (
     <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
         <path d="M15.686 15A14.5 14.5 0 0 1 12 22a14.5 14.5 0 0 1 0-20 10 10 0 1 0 9.542 13" />
         <path d="M2 12h8.5" />
         <path d="M20 6V4a2 2 0 1 0-4 0v2" />
         <rect width="8" height="5" x="14" y="6" rx="1" />
     </svg>
 );

 const LogsIcon = () => (
     <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
         <path d="M12 20v-9" />
         <path d="M14 7a4 4 0 0 1 4 4v3a6 6 0 0 1-12 0v-3a4 4 0 0 1 4-4z" />
         <path d="M14.12 3.88 16 2" />
         <path d="M21 21a4 4 0 0 0-3.81-4" />
         <path d="M21 5a4 4 0 0 1-3.55 3.97" />
         <path d="M22 13h-4" />
         <path d="M3 21a4 4 0 0 1 3.81-4" />
         <path d="M3 5a4 4 0 0 0 3.55 3.97" />
         <path d="M6 13H2" />
         <path d="m8 2 1.88 1.88" />
         <path d="M9 7.13V6a3 3 0 1 1 6 0v1.13" />
     </svg>
 );

 const ApiIcon = () => (
     <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
         <path d="M18 16.98h-5.99c-1.1 0-1.95.94-2.48 1.9A4 4 0 0 1 2 17c.01-.7.2-1.4.57-2" />
         <path d="m6 17 3.13-5.78c.53-.97.1-2.18-.5-3.1a4 4 0 1 1 6.89-4.06" />
         <path d="m12 6 3.13 5.73C15.66 12.7 16.9 13 18 13a4 4 0 0 1 0 8" />
     </svg>
 );

 const SettingsIcon = () => (
     <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
         <path d="M11 10.27 7 3.34" />
         <path d="m11 13.73-4 6.93" />
         <path d="M12 22v-2" />
         <path d="M12 2v2" />
         <path d="M14 12h8" />
         <path d="m17 20.66-1-1.73" />
         <path d="m17 3.34-1 1.73" />
         <path d="M2 12h2" />
         <path d="m20.66 17-1.73-1" />
         <path d="m20.66 7-1.73 1" />
         <path d="m3.34 17 1.73-1" />
         <path d="m3.34 7 1.73 1" />
         <circle cx="12" cy="12" r="2" />
         <circle cx="12" cy="12" r="8" />
     </svg>
 );

const ExportIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
        <polyline points="7 10 12 15 17 10" />
        <line x1="12" x2="12" y1="15" y2="3" />
    </svg>
);

const AuthIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
    </svg>
);

const sections = [
    { key: 'overview', label: 'Overview', Icon: OverviewIcon },
    { key: 'users', label: 'Users', Icon: UsersIcon },
    { key: 'auth', label: 'Auth', Icon: AuthIcon },
    { key: 'data', label: 'Data', Icon: DataIcon },
    { key: 'analytics', label: 'Analytics', Icon: AnalyticsIcon },
    { key: 'domain', label: 'Domain', Icon: DomainIcon },
    { key: 'logs', label: 'Logs', Icon: LogsIcon },
    { key: 'api', label: 'API', Icon: ApiIcon },
    { key: 'export', label: 'Export', Icon: ExportIcon },
    { key: 'settings', label: 'Settings', Icon: SettingsIcon },
];

function AppDashboardPanel({
    pageTitle,
    previewTitle,
    pageSlug,
    pageStatus,
    previewMode,
    sessionId,
    model,
    hasLiveCodeChanges,
    virtualFiles,
    previewTheme,
    savedPageId,
    appMetaTitle,
    setAppMetaTitle,
    appMetaLogo,
    setAppMetaLogo,
    appMetaIcon,
    setAppMetaIcon,
    appMetaDescription,
    setAppMetaDescription,
    appMetaSocialImage,
    setAppMetaSocialImage,
    appMetaSaveStatus,
    saveAppMeta,
    customDomain,
    customDomainLoading,
    customDomainError,
    customDomainSuccess,
    setCustomDomain,
    updateCustomDomain,
    serverIp,
    serverHost,
    supabaseIntegration,
    setSupabaseIntegration,
    supabaseForm,
    setSupabaseForm,
    supabaseConfigs = [],
    supabaseCapabilities,
    supabaseLoading,
    supabaseError,
    supabaseUsers = [],
    supabaseBuckets = [],
    supabaseTables = [],
    supabaseTableData = [],
    supabaseTableDataLoading = false,
    supabaseTableDataError = null,
    connectSupabase,
    connectSupabaseOAuth,
    disconnectSupabase,
    loadSupabaseUsers,
    loadSupabaseBuckets,
    loadSupabaseTables,
    loadSupabaseTableData,
    exportUrlTemplate,
    initialSection,
    onOpenPreview,
    onOpenCode,
    platformBadge,
}) {
    const [section, setSection] = useState(initialSection || 'overview');
    const [selectedSupabaseTable, setSelectedSupabaseTable] = useState(null);
    const [tableSearch, setTableSearch] = useState('');
    const [tableSort, setTableSort] = useState('asc');
    const [rowSearch, setRowSearch] = useState('');
    const [permissionsNoticeVisible, setPermissionsNoticeVisible] = useState(true);
    const [copiedApiField, setCopiedApiField] = useState(null);
    const [authProviders, setAuthProviders] = useState(
        supabaseIntegration?.auth_providers || { google: { enabled: true }, apple: { enabled: true } }
    );
    const [authProvidersSaving, setAuthProvidersSaving] = useState(false);
    const [authProvidersSaveError, setAuthProvidersSaveError] = useState(null);

    useEffect(() => {
        if (initialSection) setSection(initialSection);
    }, [initialSection]);

    useEffect(() => {
        if (supabaseIntegration?.auth_providers) {
            setAuthProviders(supabaseIntegration.auth_providers);
        }
    }, [supabaseIntegration?.auth_providers]);

    const saveAuthProviders = async () => {
        if (!supabaseIntegration?.id) return;
        if (!savedPageId) {
            // Cannot attach auth providers to an unsaved page — surface the problem
            // instead of posting to /admin/pages/0/... and silently failing.
            console.warn('Auth providers save skipped: page must be saved first.');
            setAuthProvidersSaveError('Save the page first, then save auth providers.');
            return;
        }
        setAuthProvidersSaving(true);
        setAuthProvidersSaveError(null);
        try {
            const resp = await fetch(`/admin/pages/${savedPageId}/supabase/auth-providers`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ auth_providers: authProviders }),
            });
            const data = await resp.json();
            if (data?.success && data.integration) {
                setSupabaseIntegration((prev) => ({ ...prev, auth_providers: data.integration.auth_providers }));
            } else {
                setAuthProvidersSaveError('Failed to save auth providers.');
            }
        } catch {
            setAuthProvidersSaveError('Failed to save auth providers. Check your connection and try again.');
        } finally {
            setAuthProvidersSaving(false);
        }
    };

    const fileToBase64 = (file) => new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });

    const handleImageUpload = async (e, setter) => {
        const file = e.target.files?.[0];
        if (!file) return;
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file.');
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            alert('Image must be under 2MB.');
            return;
        }
        try {
            const dataUrl = await fileToBase64(file);
            setter(dataUrl);
        } catch {
            alert('Failed to read image.');
        }
    };

    useEffect(() => {
        if (section === 'users') loadSupabaseUsers();
        if (section === 'data') {
            loadSupabaseTables();
        }
        if (section === 'settings' && supabaseIntegration?.status === 'connected') {
            loadSupabaseTables();
            loadSupabaseBuckets();
        }
    }, [section, loadSupabaseUsers, loadSupabaseTables, loadSupabaseBuckets, supabaseIntegration?.status]);

    const supabaseConnected = supabaseIntegration?.status === 'connected';

    const statusBadge = (status) => {
        const color = status === 'connected' ? 'bg-emerald-100 text-emerald-700' : status === 'error' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600';
        return <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${color}`}>{status || 'disconnected'}</span>;
    };

    const renderOverview = () => (
        <div className="space-y-4">
            <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div className="flex items-start gap-3">
                    <div className="h-14 w-14 rounded-xl bg-gray-900 flex items-center justify-center text-white shrink-0 overflow-hidden">
                        {appMetaLogo ? (
                            <img src={appMetaLogo} alt="App logo" className="h-full w-full object-contain" />
                        ) : (
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275Z"/></svg>
                        )}
                    </div>
                    <div className="flex-1">
                        <h2 className="text-lg font-semibold text-gray-900">{appMetaTitle || pageTitle || previewTitle || 'Untitled App'}</h2>
                        <p className="text-xs text-gray-500 mt-1">AI-generated React app</p>
                        <p className="text-[11px] text-gray-400 mt-1">Page ID: {savedPageId || 'Not saved yet'}</p>
                    </div>
                </div>
                <div className="mt-5 flex flex-wrap gap-2">
                    <button type="button" onClick={onOpenPreview} className="h-8 px-3 rounded-lg bg-gray-900 text-white text-xs font-medium hover:bg-gray-800">Open Preview</button>
                    <button type="button" onClick={onOpenCode} className="h-8 px-3 rounded-lg border border-gray-200 bg-white text-gray-700 text-xs font-medium hover:bg-gray-50">Open Code</button>
                </div>
            </div>

            <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 className="text-sm font-semibold text-gray-900">Project setup</h3>
                <div className="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                    {[
                        ['Title', pageTitle || previewTitle || 'Untitled'],
                        ['Slug', pageSlug || 'Auto-generated on save'],
                        ['Preview mode', previewMode === 'react' ? 'React runtime' : 'HTML'],
                        ['Publish status', pageStatus],
                    ].map(([label, value]) => (
                        <div key={label} className="rounded-lg border border-gray-100 bg-gray-50 p-3">
                            <div className="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{label}</div>
                            <div className="mt-1 text-sm font-medium text-gray-900 break-words">{value}</div>
                        </div>
                    ))}
                </div>
            </div>

            <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div className="flex items-center justify-between">
                    <div>
                        <h3 className="text-sm font-semibold text-gray-900">App branding</h3>
                        <p className="text-xs text-gray-500 mt-1">Logo, icon, and title are injected into the published app.</p>
                    </div>
                    <div className="flex items-center gap-2">
                        {appMetaSaveStatus === 'saving' && (
                            <span className="text-xs text-gray-500">Saving…</span>
                        )}
                        {appMetaSaveStatus === 'saved' && (
                            <span className="text-xs text-green-600">Saved</span>
                        )}
                        {appMetaSaveStatus === 'error' && (
                            <span className="text-xs text-red-600">Save failed</span>
                        )}
                        <button
                            type="button"
                            onClick={() => saveAppMeta?.()}
                            disabled={appMetaSaveStatus === 'saving'}
                            className="h-7 px-3 rounded-lg bg-gray-900 text-white text-xs font-medium hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Save
                        </button>
                    </div>
                </div>
                <div className="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div className="md:col-span-2">
                        <label className="block text-xs font-medium text-gray-700 mb-1">App title</label>
                        <input
                            type="text"
                            value={appMetaTitle || ''}
                            onChange={(e) => setAppMetaTitle(e.target.value)}
                            placeholder={pageTitle || previewTitle || 'Untitled'}
                            className="w-full h-9 px-3 rounded-lg border border-gray-200 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900"
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-gray-700 mb-1">App logo</label>
                        <div className="flex items-center gap-3">
                            {appMetaLogo ? (
                                <img src={appMetaLogo} alt="App logo" className="h-10 w-10 rounded-lg object-contain border border-gray-100 bg-white" />
                            ) : (
                                <div className="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                </div>
                            )}
                            <label className="flex-1 cursor-pointer">
                                <input type="file" accept="image/*" onChange={(e) => handleImageUpload(e, setAppMetaLogo)} className="hidden" />
                                <span className="inline-flex h-9 px-3 rounded-lg border border-gray-200 bg-white text-xs font-medium text-gray-700 items-center hover:bg-gray-50">{appMetaLogo ? 'Change logo' : 'Upload logo'}</span>
                            </label>
                            {appMetaLogo && (
                                <button type="button" onClick={() => setAppMetaLogo('')} className="text-xs text-red-600 hover:text-red-700">Remove</button>
                            )}
                        </div>
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-gray-700 mb-1">App icon</label>
                        <div className="flex items-center gap-3">
                            {appMetaIcon ? (
                                <img src={appMetaIcon} alt="App icon" className="h-10 w-10 rounded-lg object-contain border border-gray-100 bg-white" />
                            ) : (
                                <div className="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 2a10 10 0 0 0 0 20 10 10 0 0 0 0-20Z"/><path d="M12 6v6l4 2"/></svg>
                                </div>
                            )}
                            <label className="flex-1 cursor-pointer">
                                <input type="file" accept="image/*" onChange={(e) => handleImageUpload(e, setAppMetaIcon)} className="hidden" />
                                <span className="inline-flex h-9 px-3 rounded-lg border border-gray-200 bg-white text-xs font-medium text-gray-700 items-center hover:bg-gray-50">{appMetaIcon ? 'Change icon' : 'Upload icon'}</span>
                            </label>
                            {appMetaIcon && (
                                <button type="button" onClick={() => setAppMetaIcon('')} className="text-xs text-red-600 hover:text-red-700">Remove</button>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 className="text-sm font-semibold text-gray-900">SEO / social</h3>
                <p className="text-xs text-gray-500 mt-1">Meta description and image used for social sharing.</p>
                <div className="mt-4 space-y-3">
                    <div>
                        <label className="block text-xs font-medium text-gray-700 mb-1">Meta description</label>
                        <textarea
                            value={appMetaDescription || ''}
                            onChange={(e) => setAppMetaDescription(e.target.value)}
                            placeholder="Short description for search results and social cards"
                            rows={3}
                            className="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900 resize-none"
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-gray-700 mb-1">Social image</label>
                        <div className="flex items-center gap-3">
                            {appMetaSocialImage ? (
                                <img src={appMetaSocialImage} alt="Social" className="h-10 w-10 rounded-lg object-cover border border-gray-100 bg-white" />
                            ) : (
                                <div className="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                </div>
                            )}
                            <label className="flex-1 cursor-pointer">
                                <input type="file" accept="image/*" onChange={(e) => handleImageUpload(e, setAppMetaSocialImage)} className="hidden" />
                                <span className="inline-flex h-9 px-3 rounded-lg border border-gray-200 bg-white text-xs font-medium text-gray-700 items-center hover:bg-gray-50">{appMetaSocialImage ? 'Change image' : 'Upload image'}</span>
                            </label>
                            {appMetaSocialImage && (
                                <button type="button" onClick={() => setAppMetaSocialImage('')} className="text-xs text-red-600 hover:text-red-700">Remove</button>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {platformBadge?.enabled ? (
                <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div className="flex items-center justify-between gap-4">
                        <div>
                            <h3 className="text-sm font-semibold text-gray-900">Platform badge</h3>
                            <p className="text-xs text-gray-500 mt-1">
                                A <span className="font-medium text-gray-700">"{platformBadge.text || 'Built with MailPurse'}"</span> badge is shown on your published app. Upgrade to remove it.
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={() => { window.location.href = '/customer/billing'; }}
                            className="shrink-0 inline-flex items-center gap-1.5 h-8 px-4 rounded-lg bg-orange-500 text-white text-xs font-semibold hover:bg-orange-600 transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
                            Upgrade
                        </button>
                    </div>
                </div>
            ) : null}
        </div>
    );

    const renderUsers = () => (
        <div className="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 className="text-sm font-semibold text-gray-900">Supabase Auth Users</h3>
                <button onClick={loadSupabaseUsers} className="text-xs text-gray-500 hover:text-gray-900">Refresh</button>
            </div>
            {!supabaseConnected ? (
                <div className="p-8 text-center text-sm text-gray-500">Connect Supabase in Settings to view users.</div>
            ) : supabaseError ? (
                <div className="p-8 text-center text-sm text-red-600">{supabaseError}</div>
            ) : supabaseUsers.length === 0 ? (
                <div className="p-8 text-center text-sm text-gray-500">No users found.</div>
            ) : (
                <table className="w-full text-sm text-left">
                    <thead className="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th className="px-5 py-3">Email</th>
                            <th className="px-5 py-3">Created</th>
                            <th className="px-5 py-3">Last sign in</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {supabaseUsers.map((user) => (
                            <tr key={user.id}>
                                <td className="px-5 py-3">{user.email || '-'}</td>
                                <td className="px-5 py-3">{user.created_at ? new Date(user.created_at).toLocaleDateString() : '-'}</td>
                                <td className="px-5 py-3">{user.last_sign_in_at ? new Date(user.last_sign_in_at).toLocaleDateString() : '-'}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </div>
    );

    const renderAuth = () => {
        const totalUsers = supabaseUsers.length;
        const recentUsers = supabaseUsers.filter((u) => {
            if (!u.created_at) return false;
            const d = new Date(u.created_at);
            const weekAgo = new Date();
            weekAgo.setDate(weekAgo.getDate() - 7);
            return d >= weekAgo;
        }).length;

        const providers = new Set(supabaseUsers.map((u) => {
            const identities = u.identities || [];
            return identities.map((i) => i.provider).filter(Boolean);
        }).flat());
        const providerList = Array.from(providers).filter(Boolean);

        return (
            <div className="space-y-5">
                {/* Status banner */}
                <div className={`rounded-xl border p-4 ${supabaseConnected ? 'border-green-200 bg-green-50' : 'border-amber-200 bg-amber-50'}`}>
                    <div className="flex items-center gap-3">
                        <div className={`flex h-8 w-8 items-center justify-center rounded-full ${supabaseConnected ? 'bg-green-100' : 'bg-amber-100'}`}>
                            {supabaseConnected ? (
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-green-600"><path d="M20 6 9 17l-5-5"/></svg>
                            ) : (
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-amber-600"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                            )}
                        </div>
                        <div>
                            <p className={`text-sm font-semibold ${supabaseConnected ? 'text-green-900' : 'text-amber-900'}`}>
                                {supabaseConnected ? 'Authentication is active' : 'Supabase not connected'}
                            </p>
                            <p className={`text-xs ${supabaseConnected ? 'text-green-700' : 'text-amber-700'}`}>
                                {supabaseConnected
                                    ? 'Users can sign in with email/password, Apple, and Google.'
                                    : 'Connect Supabase in Settings to enable authentication.'}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Stats cards */}
                <div className="grid grid-cols-3 gap-4">
                    <div className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <p className="text-xs text-gray-500">Total Users</p>
                        <p className="mt-1 text-2xl font-bold text-gray-900">{totalUsers}</p>
                    </div>
                    <div className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <p className="text-xs text-gray-500">New (7 days)</p>
                        <p className="mt-1 text-2xl font-bold text-gray-900">{recentUsers}</p>
                    </div>
                    <div className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <p className="text-xs text-gray-500">Providers</p>
                        <p className="mt-1 text-2xl font-bold text-gray-900">{providerList.length || (supabaseConnected ? 1 : 0)}</p>
                    </div>
                </div>

                {/* Enabled providers */}
                <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h4 className="text-sm font-semibold text-gray-900">Enabled Providers</h4>
                    <p className="mt-1 text-xs text-gray-500">Configure these in your Supabase dashboard under Authentication → Providers.</p>
                    <div className="mt-4 space-y-3">
                        <div className="flex items-center justify-between rounded-lg border border-gray-100 p-3">
                            <div className="flex items-center gap-3">
                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-900">Apple</p>
                                    <p className="text-xs text-gray-500">Sign in with Apple</p>
                                </div>
                            </div>
                            <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${supabaseConnected ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}`}>
                                {supabaseConnected ? 'Active' : 'Not configured'}
                            </span>
                        </div>
                        <div className="flex items-center justify-between rounded-lg border border-gray-100 p-3">
                            <div className="flex items-center gap-3">
                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-900">Google</p>
                                    <p className="text-xs text-gray-500">Sign in with Google</p>
                                </div>
                            </div>
                            <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${supabaseConnected ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}`}>
                                {supabaseConnected ? 'Active' : 'Not configured'}
                            </span>
                        </div>
                        <div className="flex items-center justify-between rounded-lg border border-gray-100 p-3">
                            <div className="flex items-center gap-3">
                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-900">Email / Password</p>
                                    <p className="text-xs text-gray-500">Traditional email and password login</p>
                                </div>
                            </div>
                            <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${supabaseConnected ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}`}>
                                {supabaseConnected ? 'Active' : 'Not configured'}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Provider Configuration */}
                {supabaseConnected && (
                    <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <h4 className="text-sm font-semibold text-gray-900">Provider Configuration</h4>
                        <p className="mt-1 text-xs text-gray-500">Enable or disable login providers and configure OAuth credentials.</p>

                        {/* Google */}
                        <div className="mt-4 rounded-lg border border-gray-100 p-4">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                                    <span className="text-sm font-medium text-gray-900">Google</span>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => setAuthProviders((prev) => ({ ...prev, google: { ...prev.google, enabled: !prev.google?.enabled } }))}
                                    className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none ${authProviders?.google?.enabled ? 'bg-green-600' : 'bg-gray-200'}`}
                                >
                                    <span className={`pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${authProviders?.google?.enabled ? 'translate-x-5' : 'translate-x-0'}`} />
                                </button>
                            </div>
                            {authProviders?.google?.enabled && (
                                <div className="mt-3 space-y-3">
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700">Client ID</label>
                                        <input
                                            type="text"
                                            value={authProviders?.google?.client_id || ''}
                                            onChange={(e) => setAuthProviders((prev) => ({ ...prev, google: { ...prev.google, client_id: e.target.value } }))}
                                            className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500"
                                            placeholder="your-google-client-id.apps.googleusercontent.com"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700">Client Secret</label>
                                        <input
                                            type="password"
                                            value={authProviders?.google?.client_secret || ''}
                                            onChange={(e) => setAuthProviders((prev) => ({ ...prev, google: { ...prev.google, client_secret: e.target.value } }))}
                                            className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500"
                                            placeholder="GOCSPX-..."
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700">Redirect URI</label>
                                        <input
                                            type="text"
                                            value={authProviders?.google?.redirect_uri || ''}
                                            onChange={(e) => setAuthProviders((prev) => ({ ...prev, google: { ...prev.google, redirect_uri: e.target.value } }))}
                                            className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500"
                                            placeholder="https://your-app.com"
                                        />
                                    </div>
                                    <p className="text-[11px] text-gray-400">Configure these values in the <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener noreferrer" className="underline">Google Cloud Console</a>, then enable Google in Supabase → Authentication → Providers.</p>
                                </div>
                            )}
                        </div>

                        {/* Apple */}
                        <div className="mt-3 rounded-lg border border-gray-100 p-4">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                                    <span className="text-sm font-medium text-gray-900">Apple</span>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => setAuthProviders((prev) => ({ ...prev, apple: { ...prev.apple, enabled: !prev.apple?.enabled } }))}
                                    className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none ${authProviders?.apple?.enabled ? 'bg-green-600' : 'bg-gray-200'}`}
                                >
                                    <span className={`pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${authProviders?.apple?.enabled ? 'translate-x-5' : 'translate-x-0'}`} />
                                </button>
                            </div>
                            {authProviders?.apple?.enabled && (
                                <div className="mt-3 space-y-3">
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700">Client ID (Service ID)</label>
                                        <input
                                            type="text"
                                            value={authProviders?.apple?.client_id || ''}
                                            onChange={(e) => setAuthProviders((prev) => ({ ...prev, apple: { ...prev.apple, client_id: e.target.value } }))}
                                            className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900"
                                            placeholder="com.example.app"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700">Team ID</label>
                                        <input
                                            type="text"
                                            value={authProviders?.apple?.team_id || ''}
                                            onChange={(e) => setAuthProviders((prev) => ({ ...prev, apple: { ...prev.apple, team_id: e.target.value } }))}
                                            className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900"
                                            placeholder="ABCDE12345"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700">Key ID</label>
                                        <input
                                            type="text"
                                            value={authProviders?.apple?.key_id || ''}
                                            onChange={(e) => setAuthProviders((prev) => ({ ...prev, apple: { ...prev.apple, key_id: e.target.value } }))}
                                            className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900"
                                            placeholder="ABCDEF1234"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700">Private Key</label>
                                        <textarea
                                            value={authProviders?.apple?.private_key || ''}
                                            onChange={(e) => setAuthProviders((prev) => ({ ...prev, apple: { ...prev.apple, private_key: e.target.value } }))}
                                            rows={3}
                                            className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900 font-mono text-[11px]"
                                            placeholder="-----BEGIN PRIVATE KEY-----&#10;...&#10;-----END PRIVATE KEY-----"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700">Redirect URI</label>
                                        <input
                                            type="text"
                                            value={authProviders?.apple?.redirect_uri || ''}
                                            onChange={(e) => setAuthProviders((prev) => ({ ...prev, apple: { ...prev.apple, redirect_uri: e.target.value } }))}
                                            className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900"
                                            placeholder="https://your-app.com"
                                        />
                                    </div>
                                    <p className="text-[11px] text-gray-400">Configure Sign In with Apple in the <a href="https://developer.apple.com/account/resources/identifiers/list" target="_blank" rel="noopener noreferrer" className="underline">Apple Developer Console</a>, then enable Apple in Supabase → Authentication → Providers.</p>
                                </div>
                            )}
                        </div>

                        {authProvidersSaveError && (
                            <p className="mt-2 text-right text-xs text-red-600">{authProvidersSaveError}</p>
                        )}
                        <div className="mt-4 flex justify-end">
                            <button
                                type="button"
                                onClick={saveAuthProviders}
                                disabled={authProvidersSaving}
                                className="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 disabled:opacity-50"
                            >
                                {authProvidersSaving ? 'Saving…' : 'Save Provider Config'}
                            </button>
                        </div>
                    </div>
                )}

                {/* Auth users table */}
                <div className="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                    <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h4 className="text-sm font-semibold text-gray-900">Registered Users</h4>
                        <button onClick={loadSupabaseUsers} className="text-xs text-gray-500 hover:text-gray-900">Refresh</button>
                    </div>
                    {!supabaseConnected ? (
                        <div className="p-8 text-center text-sm text-gray-500">Connect Supabase to view registered users.</div>
                    ) : supabaseUsers.length === 0 ? (
                        <div className="p-8 text-center text-sm text-gray-500">No users registered yet.</div>
                    ) : (
                        <table className="w-full text-sm text-left">
                            <thead className="bg-gray-50 text-xs text-gray-500 uppercase">
                                <tr>
                                    <th className="px-5 py-3">Email</th>
                                    <th className="px-5 py-3">Provider</th>
                                    <th className="px-5 py-3">Created</th>
                                    <th className="px-5 py-3">Last sign in</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {supabaseUsers.map((u) => {
                                    const identities = u.identities || [];
                                    const provider = identities.map((i) => i.provider).filter(Boolean)[0] || 'email';
                                    return (
                                        <tr key={u.id}>
                                            <td className="px-5 py-3">{u.email || '-'}</td>
                                            <td className="px-5 py-3">
                                                <span className="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 capitalize">{provider}</span>
                                            </td>
                                            <td className="px-5 py-3">{u.created_at ? new Date(u.created_at).toLocaleDateString() : '-'}</td>
                                            <td className="px-5 py-3">{u.last_sign_in_at ? new Date(u.last_sign_in_at).toLocaleDateString() : '-'}</td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        );
    };

    const openTable = (tableName) => {
        setSelectedSupabaseTable(tableName);
        setSupabaseTableData([]);
        loadSupabaseTableData(tableName);
    };

    const renderRowGrid = () => {
        if (supabaseTableDataLoading) {
            return <div className="p-8 text-center text-sm text-gray-500">Loading data…</div>;
        }
        if (supabaseTableDataError) {
            return <div className="p-8 text-center text-sm text-red-600">{supabaseTableDataError}</div>;
        }
        if (supabaseTableData.length === 0) {
            return <div className="p-8 text-center text-sm text-gray-500">No rows found in <span className="font-mono">{selectedSupabaseTable}</span>.</div>;
        }

        const columns = Object.keys(supabaseTableData[0] || {});
        const normalizedSearch = rowSearch.trim().toLowerCase();
        const rows = normalizedSearch
            ? supabaseTableData.filter((row) => columns.some((column) => formatCellValue(row[column]).toLowerCase().includes(normalizedSearch)))
            : supabaseTableData;

        return (
            <div className="overflow-auto max-h-[520px]">
                <table className="w-full min-w-max text-sm text-left">
                    <thead className="bg-[#f8f7f5] text-xs text-gray-500 sticky top-0">
                        <tr>
                            {columns.map((column) => (
                                <th key={column} className="px-5 py-3.5 font-medium whitespace-nowrap border-b border-gray-200">{column}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {rows.map((row, index) => (
                            <tr key={index} className="hover:bg-gray-50/70">
                                {columns.map((column) => (
                                    <td key={column} className="px-5 py-4 align-top text-gray-800 max-w-[300px] truncate" title={formatCellValue(row[column])}>
                                        {formatCellValue(row[column])}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
                {rows.length === 0 && <div className="p-8 text-center text-sm text-gray-500">No matching rows.</div>}
            </div>
        );
    };

    const renderData = () => {
        const filteredTables = supabaseTables
            .filter((table) => `${table.table_schema || ''}.${table.table_name || ''}`.toLowerCase().includes(tableSearch.trim().toLowerCase()))
            .sort((a, b) => {
                const left = String(a.table_name || '').toLowerCase();
                const right = String(b.table_name || '').toLowerCase();
                return tableSort === 'asc' ? left.localeCompare(right) : right.localeCompare(left);
            });

        if (selectedSupabaseTable) {
            return (
                <div className="space-y-5">
                    <div className="flex items-center gap-3 text-sm">
                        <button type="button" onClick={() => { setSelectedSupabaseTable(null); setRowSearch(''); }} className="text-gray-500 hover:text-gray-900">Data</button>
                        <span className="text-gray-400">›</span>
                        <span className="text-gray-900">{selectedSupabaseTable}</span>
                    </div>
                    <div className="flex items-start justify-between gap-4">
                        <h1 className="text-3xl font-semibold tracking-tight text-gray-950">{selectedSupabaseTable} <span className="font-normal">({supabaseTableData.length})</span></h1>
                        <div className="flex items-center gap-2">
                            <button type="button" onClick={() => openTable(selectedSupabaseTable)} className="h-10 px-3 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-800 hover:bg-gray-50">Refresh</button>
                        </div>
                    </div>
                    {!supabaseConnected ? (
                        <div className="rounded-xl border border-gray-200 bg-white p-10 text-center text-sm text-gray-500">Connect Supabase in Settings to view data.</div>
                    ) : supabaseTableDataError ? (
                        <div className="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-700">{supabaseTableDataError}</div>
                    ) : (
                        <div className="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm">
                            <div className="flex items-center justify-between gap-3 p-5 border-b border-gray-200">
                                <div className="relative w-full max-w-md">
                                    <svg className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                                    <input value={rowSearch} onChange={(event) => setRowSearch(event.target.value)} placeholder="Search table rows..." className="h-11 w-full rounded-lg border border-gray-300 pl-10 pr-3 text-sm outline-none focus:border-gray-900" />
                                </div>
                                <button type="button" className="h-11 shrink-0 rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-800 hover:bg-gray-50">
                                    <span className="mr-2">▽</span> Filters
                                </button>
                            </div>
                            {renderRowGrid()}
                        </div>
                    )}
                </div>
            );
        }

        return (
            <div className="space-y-6">
                <div className="border-b border-gray-300 pb-7">
                    <h1 className="text-4xl font-semibold tracking-tight text-gray-950">Data</h1>
                    <p className="mt-3 text-lg text-gray-800">View and manage the data stored in your app.</p>
                </div>
                {permissionsNoticeVisible && (
                    <div className="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
                        <span className="flex h-5 w-5 items-center justify-center rounded-full border-2 border-gray-900 text-xs font-bold">!</span>
                        <span className="text-sm font-medium text-gray-900">Permissions scan is out of date</span>
                        <div className="ml-auto flex items-center gap-5">
                            <button type="button" onClick={() => setPermissionsNoticeVisible(false)} className="text-sm font-medium text-gray-900">Dismiss</button>
                            <button type="button" onClick={loadSupabaseTables} className="h-10 rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-900 hover:bg-gray-50">Re-scan</button>
                        </div>
                    </div>
                )}
                <div className="flex items-center justify-between gap-4">
                    <div className="relative w-full max-w-md">
                        <svg className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                        <input value={tableSearch} onChange={(event) => setTableSearch(event.target.value)} placeholder="Search tables..." className="h-14 w-full rounded-xl border border-gray-300 bg-white pl-11 pr-4 text-base outline-none focus:border-gray-900" />
                    </div>
                    <button type="button" onClick={() => setTableSort((current) => current === 'asc' ? 'desc' : 'asc')} className="inline-flex h-14 shrink-0 items-center gap-3 rounded-xl border border-gray-200 bg-white px-5 text-base font-medium text-gray-900 shadow-sm hover:bg-gray-50">
                        <span className="text-xl leading-none">↕</span> {tableSort === 'asc' ? 'A-Z' : 'Z-A'} <span className="text-gray-500">⌄</span>
                    </button>
                </div>
                {!supabaseConnected ? (
                    <div className="rounded-xl border border-gray-200 bg-white p-10 text-center text-sm text-gray-500">Connect Supabase in Settings to view data tables.</div>
                ) : supabaseError ? (
                    <div className="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-700">{supabaseError}</div>
                ) : filteredTables.length === 0 ? (
                    <div className="rounded-xl border border-gray-200 bg-white p-10 text-center text-sm text-gray-500">No tables found.</div>
                ) : (
                    <div className="grid grid-cols-1 gap-5 xl:grid-cols-2">
                        {filteredTables.map((table) => (
                            <button type="button" key={`${table.table_schema || 'public'}.${table.table_name}`} onClick={() => openTable(table.table_name)} className="flex min-h-[126px] items-center gap-6 rounded-2xl border border-gray-200 bg-white px-6 text-left shadow-sm transition hover:border-gray-400 hover:shadow-md">
                                <span className="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-700">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><rect x="4" y="4" width="16" height="16" rx="1"/><path d="M4 9h16M4 14h16M9 4v16M14 4v16"/></svg>
                                </span>
                                <span className="min-w-0">
                                    <span className="block truncate text-xl font-semibold text-gray-950">{table.table_name}</span>
                                    {table.table_schema && table.table_schema !== 'public' && <span className="mt-1 block text-xs text-gray-500">{table.table_schema}</span>}
                                </span>
                            </button>
                        ))}
                    </div>
                )}
            </div>
        );
    };

    const renderSettings = () => {
        const navigateToDatabase = () => {
            window.location.href = '/customer/supabase';
        };

        return (
            <div className="space-y-4 max-w-2xl">
                <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 className="text-sm font-semibold text-gray-900">Database</h3>

                    {supabaseConnected ? (
                        <div className="mt-4 space-y-4">
                            <div className="flex items-center gap-2">
                                <span className="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                <span className="text-sm text-gray-700">Connected</span>
                            </div>
                            <div className="text-xs text-gray-500 font-mono">{supabaseIntegration.project_url}</div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="rounded-lg border border-gray-100 bg-gray-50 p-3">
                                    <div className="text-xs font-medium text-gray-500 uppercase tracking-wide">Storage Buckets</div>
                                    {supabaseBuckets.length === 0 ? (
                                        <p className="mt-2 text-sm text-gray-500">No buckets found.</p>
                                    ) : (
                                        <ul className="mt-2 space-y-1 text-sm text-gray-700">
                                            {supabaseBuckets.map((bucket) => (
                                                <li key={bucket.id || bucket.name} className="flex items-center justify-between">
                                                    <span>{bucket.name}</span>
                                                    <span className="text-xs text-gray-400">{bucket.public ? 'Public' : 'Private'}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                    <button
                                        onClick={loadSupabaseBuckets}
                                        className="mt-3 text-xs text-gray-600 hover:text-gray-900 font-medium"
                                    >
                                        Refresh buckets
                                    </button>
                                </div>
                                <div className="rounded-lg border border-gray-100 bg-gray-50 p-3">
                                    <div className="text-xs font-medium text-gray-500 uppercase tracking-wide">Database Tables</div>
                                    {supabaseTables.length === 0 ? (
                                        <p className="mt-2 text-sm text-gray-500">No tables found.</p>
                                    ) : (
                                        <ul className="mt-2 space-y-1 text-sm text-gray-700 max-h-40 overflow-y-auto">
                                            {supabaseTables.map((row, i) => (
                                                <li key={i}>
                                                    {row.table_schema}.{row.table_name}
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                    <button
                                        onClick={loadSupabaseTables}
                                        className="mt-3 text-xs text-gray-600 hover:text-gray-900 font-medium"
                                    >
                                        Refresh tables
                                    </button>
                                </div>
                            </div>

                            {supabaseIntegration && (
                                <button
                                    onClick={disconnectSupabase}
                                    className="mt-4 h-8 px-3 rounded-lg border border-gray-200 bg-white text-gray-700 text-xs font-medium hover:bg-gray-50"
                                >
                                    Disconnect
                                </button>
                            )}
                        </div>
                    ) : (
                        <div className="mt-6 flex flex-col items-center justify-center py-8 text-center">
                            <div className="h-14 w-14 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/></svg>
                            </div>
                            <p className="text-sm font-medium text-gray-900">No database connected</p>
                            <p className="text-xs text-gray-500 mt-1 max-w-xs">Connect a database to enable auth, storage, and data tables for your app.</p>
                            <button
                                onClick={navigateToDatabase}
                                className="mt-4 inline-flex items-center gap-2 h-9 px-4 rounded-lg bg-gray-900 text-white text-xs font-medium hover:bg-gray-800 transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                Connect Database
                            </button>
                        </div>
                    )}
                </div>
            </div>
        );
    };

    const renderDomain = () => (
        <div className="space-y-5 max-w-3xl">
            <div className="rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 p-6 text-white shadow-lg">
                <div className="flex items-start gap-4">
                    <div className="shrink-0 h-12 w-12 rounded-xl bg-white/10 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z"/></svg>
                    </div>
                    <div>
                        <h3 className="text-lg font-semibold">Custom domain</h3>
                        <p className="mt-1 text-sm text-slate-300">Connect your own domain and serve your app on your own URL.</p>
                    </div>
                </div>
            </div>

            {!savedPageId ? (
                <div className="rounded-xl bg-amber-50 border border-amber-100 p-4 flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-amber-600 shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <div>
                        <h4 className="text-sm font-semibold text-amber-900">Publish the app first</h4>
                        <p className="text-xs text-amber-700 mt-0.5">Custom domain mapping is enabled after the app is published.</p>
                    </div>
                </div>
            ) : (
                <>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                        {/* DNS Instructions */}
                        <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                            <div className="flex items-center gap-3 mb-4">
                                <span className="h-7 w-7 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold">1</span>
                                <h4 className="text-sm font-semibold text-gray-900">Configure DNS records</h4>
                            </div>
                            <p className="text-xs text-gray-500 mb-4">Add this record at your domain registrar (GoDaddy, Namecheap, Cloudflare, etc.).</p>

                            <div className="rounded-lg border border-gray-200 overflow-hidden">
                                <div className="grid grid-cols-3 text-[11px] font-medium text-gray-500 bg-gray-50 px-3 py-2">
                                    <span>Type</span>
                                    <span>Name / Host</span>
                                    <span>Value</span>
                                </div>
                                <div className="grid grid-cols-3 text-[11px] text-gray-900 px-3 py-2 font-mono bg-white">
                                    <span>{serverIp ? 'A' : 'CNAME'}</span>
                                    <span>{serverIp ? '@ or www' : 'www or @'}</span>
                                    <span>{serverIp || serverHost}</span>
                                </div>
                            </div>

                            <div className="mt-4 flex items-start gap-2 text-[11px] text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                DNS changes can take 5–60 minutes to propagate worldwide.
                            </div>
                        </div>

                        {/* Domain Input */}
                        <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                            <div className="flex items-center gap-3 mb-4">
                                <span className="h-7 w-7 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold">2</span>
                                <h4 className="text-sm font-semibold text-gray-900">Enter your domain</h4>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 mb-1.5">Domain name</label>
                                <input
                                    type="text"
                                    value={customDomain || ''}
                                    onChange={(e) => setCustomDomain(e.target.value.trim().toLowerCase())}
                                    placeholder="app.example.com"
                                    className="w-full h-10 px-3 rounded-lg border border-gray-200 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 transition-shadow"
                                    spellCheck="false"
                                    autoComplete="off"
                                />
                            </div>

                            {customDomainError && <div className="mt-3 text-xs text-red-600 bg-red-50 rounded-lg px-3 py-2">{customDomainError}</div>}
                            {customDomainSuccess && <div className="mt-3 text-xs text-emerald-600 bg-emerald-50 rounded-lg px-3 py-2">{customDomainSuccess}</div>}

                            <div className="flex items-center gap-2 mt-4">
                                <button
                                    onClick={updateCustomDomain}
                                    disabled={customDomainLoading || !savedPageId || !customDomain}
                                    className="h-10 px-4 rounded-lg bg-slate-900 text-white text-xs font-medium hover:bg-slate-800 disabled:opacity-50 transition-colors"
                                >
                                    {customDomainLoading ? 'Saving...' : 'Save domain'}
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Current Domain Status */}
                    {customDomain && (
                        <div className="rounded-xl border border-emerald-100 bg-emerald-50/60 p-5">
                            <div className="flex items-center justify-between gap-4">
                                <div className="flex items-center gap-3">
                                    <div className="h-10 w-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                    </div>
                                    <div>
                                        <p className="text-[10px] font-semibold text-emerald-700 uppercase tracking-wider">Active domain</p>
                                        <p className="mt-0.5 font-mono text-sm text-gray-900 break-all">{customDomain}</p>
                                    </div>
                                </div>
                                <a
                                    href={`http://${customDomain}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="shrink-0 h-9 px-4 rounded-lg border border-gray-200 bg-white text-xs font-medium text-gray-700 hover:bg-gray-50 inline-flex items-center gap-1.5 shadow-sm transition-colors"
                                >
                                    Visit
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M7 7h10v10"/><path d="M7 17 17 7"/></svg>
                                </a>
                            </div>
                            <div className="mt-4 flex items-start gap-2 text-[11px] text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                Make sure your DNS records from Step 1 point to this server. Once DNS propagates, visiting {customDomain} will serve your app.
                            </div>
                        </div>
                    )}
                </>
            )}
        </div>
    );

    const renderApi = () => {
        const projectUrl = supabaseIntegration?.project_url || '';
        const publishableKey = supabaseIntegration?.publishable_key || '';
        const restUrl = projectUrl ? `${projectUrl.replace(/\/+$/, '')}/rest/v1/` : '';
        const authUrl = projectUrl ? `${projectUrl.replace(/\/+$/, '')}/auth/v1/` : '';
        const storageUrl = projectUrl ? `${projectUrl.replace(/\/+$/, '')}/storage/v1/` : '';

        const copy = async (text, key) => {
            if (!text || !navigator.clipboard) return;
            try {
                await navigator.clipboard.writeText(text);
                setCopiedApiField(key);
                setTimeout(() => setCopiedApiField(null), 1500);
            } catch {}
        };

        const renderCopyable = ({ label, value, fieldKey, isKey = false }) => {
            const isCopied = copiedApiField === fieldKey;
            const displayValue = isKey && value ? value.replace(/.(?=.{4})/g, '•') : value;

            return (
                <div className="rounded-lg border border-gray-100 bg-gray-50 p-3">
                    <div className="flex items-center justify-between mb-1.5">
                        <span className="text-[11px] font-medium text-gray-500 uppercase tracking-wide">{label}</span>
                        <button
                            onClick={() => copy(value, fieldKey)}
                            disabled={!value}
                            className="text-[11px] font-medium text-gray-600 hover:text-gray-900 disabled:opacity-40 inline-flex items-center gap-1 transition-colors"
                        >
                            {isCopied ? (
                                <>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    Copied
                                </>
                            ) : (
                                <>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>
                                    Copy
                                </>
                            )}
                        </button>
                    </div>
                    <div className={`text-sm text-gray-900 break-all ${isKey ? 'font-mono' : ''}`}>{displayValue || '—'}</div>
                </div>
            );
        };

        const initSnippet = `import { createClient } from '@supabase/supabase-js'

const supabaseUrl = '${projectUrl}'
const supabaseKey = '${publishableKey}'

const supabase = createClient(supabaseUrl, supabaseKey)`;

        const curlSnippet = `curl -X POST '${authUrl}signup' \\
  -H 'apikey: ${publishableKey}' \\
  -H 'Content-Type: application/json' \\
  -d '{"email":"user@example.com","password":"password"}'`;

        return (
            <div className="space-y-5 max-w-3xl">
                <div className="rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 p-6 text-white shadow-lg">
                    <div className="flex items-start gap-4">
                        <div className="shrink-0 h-12 w-12 rounded-xl bg-white/10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="4 17 10 11 4 5"/><line x1="12" x2="20" y1="19" y2="19"/></svg>
                        </div>
                        <div>
                            <h3 className="text-lg font-semibold">API Access</h3>
                            <p className="mt-1 text-sm text-slate-300">Connect to your app&apos;s backend using the Supabase REST, Auth, and Storage APIs.</p>
                        </div>
                    </div>
                </div>

                {!supabaseConnected ? (
                    <div className="rounded-xl bg-amber-50 border border-amber-100 p-4 flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-amber-600 shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <div>
                            <h4 className="text-sm font-semibold text-amber-900">Connect a database first</h4>
                            <p className="text-xs text-amber-700 mt-0.5">API access requires a connected Supabase project.</p>
                        </div>
                    </div>
                ) : (
                    <>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                                <h4 className="text-sm font-semibold text-gray-900 mb-4">Project credentials</h4>
                                <div className="space-y-3">
                                    {renderCopyable({ label: 'Project URL', value: projectUrl, fieldKey: 'url' })}
                                    {renderCopyable({ label: 'Publishable key', value: publishableKey, fieldKey: 'publishable', isKey: true })}
                                </div>
                            </div>

                            <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                                <h4 className="text-sm font-semibold text-gray-900 mb-4">Endpoints</h4>
                                <div className="space-y-3">
                                    {renderCopyable({ label: 'REST API', value: restUrl, fieldKey: 'rest' })}
                                    {renderCopyable({ label: 'Auth API', value: authUrl, fieldKey: 'auth' })}
                                    {renderCopyable({ label: 'Storage API', value: storageUrl, fieldKey: 'storage' })}
                                </div>
                            </div>
                        </div>

                        <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                            <div className="flex items-center justify-between mb-4">
                                <h4 className="text-sm font-semibold text-gray-900">Supabase client setup</h4>
                                <button
                                    onClick={() => copy(initSnippet, 'init')}
                                    className="text-xs font-medium text-gray-600 hover:text-gray-900 inline-flex items-center gap-1 transition-colors"
                                >
                                    {copiedApiField === 'init' ? 'Copied' : 'Copy'}
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>
                                </button>
                            </div>
                            <pre className="rounded-lg bg-slate-900 text-slate-100 p-4 text-xs font-mono overflow-x-auto whitespace-pre">{initSnippet}</pre>
                        </div>

                        <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                            <div className="flex items-center justify-between mb-4">
                                <h4 className="text-sm font-semibold text-gray-900">Example: sign up a user</h4>
                                <button
                                    onClick={() => copy(curlSnippet, 'curl')}
                                    className="text-xs font-medium text-gray-600 hover:text-gray-900 inline-flex items-center gap-1 transition-colors"
                                >
                                    {copiedApiField === 'curl' ? 'Copied' : 'Copy'}
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>
                                </button>
                            </div>
                            <pre className="rounded-lg bg-slate-900 text-slate-100 p-4 text-xs font-mono overflow-x-auto whitespace-pre">{curlSnippet}</pre>
                        </div>

                        <div className="rounded-xl border border-blue-100 bg-blue-50 p-4 flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-blue-600 shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            <p className="text-xs text-blue-700">Use the <strong>publishable key</strong> in browser or client-side code. The API secret stays server-side and is not shown here.</p>
                        </div>
                    </>
                )}
            </div>
        );
    };

    const renderExport = () => {
        const baseUrl = exportUrlTemplate?.replace('__SESSION__', sessionId) ?? '';

        const handleExport = (format) => {
            if (!baseUrl) {
                alert('Export is not available for this workspace.');
                return;
            }
            const separator = baseUrl.includes('?') ? '&' : '?';
            window.open(`${baseUrl}${separator}format=${format}`, '_blank');
        };

        return (
            <div className="space-y-4 max-w-3xl">
                <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm flex items-center justify-between gap-4">
                    <div>
                        <h3 className="text-sm font-semibold text-gray-900">Export as ZIP</h3>
                        <p className="text-xs text-gray-500 mt-1">Download the project source files as a ZIP archive.</p>
                    </div>
                    <button
                        type="button"
                        onClick={() => handleExport('zip')}
                        disabled={!baseUrl}
                        className="shrink-0 h-9 px-4 rounded-lg bg-gray-900 text-white text-xs font-medium hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Export ZIP
                    </button>
                </div>

                <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm flex items-center justify-between gap-4">
                    <div>
                        <h3 className="text-sm font-semibold text-gray-900">Export as HTML</h3>
                        <p className="text-xs text-gray-500 mt-1">Download the compiled page as a single HTML file.</p>
                    </div>
                    <button
                        type="button"
                        onClick={() => handleExport('html')}
                        disabled={!baseUrl}
                        className="shrink-0 h-9 px-4 rounded-lg bg-gray-900 text-white text-xs font-medium hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Export HTML
                    </button>
                </div>
            </div>
        );
    };

    const renderPlaceholder = (title) => (
        <div className="rounded-xl border border-gray-200 bg-white p-8 shadow-sm text-center">
            <h3 className="text-sm font-semibold text-gray-900">{title}</h3>
            <p className="text-xs text-gray-500 mt-2">This section is coming soon.</p>
        </div>
    );

    const content = {
        overview: renderOverview(),
        users: renderUsers(),
        auth: renderAuth(),
        data: renderData(),
        analytics: renderPlaceholder('Analytics'),
        domain: renderDomain(),
        logs: renderPlaceholder('Logs'),
        api: renderApi(),
        export: renderExport(),
        settings: renderSettings(),
    }[section];

    return (
        <div className="h-full flex bg-gray-50 overflow-hidden">
            <div className="w-56 border-r border-gray-200 bg-white p-3 flex flex-col gap-1 shrink-0">
                <div className="px-3 py-2">
                    <div className="text-xs font-semibold text-gray-900 uppercase tracking-wider">App Dashboard</div>
                </div>
                {sections.map(({ key, label, Icon }) => (
                    <button
                        key={key}
                        onClick={() => setSection(key)}
                        className={`flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${section === key ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100'}`}
                    >
                        <Icon />
                        {label}
                    </button>
                ))}
            </div>
            <div className="flex-1 min-h-0 overflow-auto p-5">
                <div className="max-w-4xl mx-auto">
                    {content}
                </div>
            </div>
        </div>
    );
}

export { AppDashboardPanel };
