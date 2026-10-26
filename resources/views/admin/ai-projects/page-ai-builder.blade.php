@extends('layouts.admin')

@section('title', __('Page AI Builder'))
@section('page-title', __('Page AI Builder'))


@section('content-padding-classes', 'p-0')

@push('styles')
<style>
    .page-ai-builder-shell {
        height: 100vh;
        min-height: 600px;
    }
</style>
@endpush

@push('scripts')
@vite('resources/js/page-ai-builder.jsx')
@endpush

@section('content')
<div class="page-ai-builder-shell" x-data="{ saveModalOpen: false, generatedHtml: '', generatedTitle: '', pageSlug: '', pageStatus: 'draft' }">
    <div
        id="page-ai-builder-react"
        class="h-full"
        data-analyze-url="{{ route('admin.ai-projects.page-ai-builder.analyze') }}"
        data-suggestions-url="{{ route('admin.ai-projects.page-ai-builder.suggestions') }}"
        data-generate-url="{{ route('admin.ai-projects.page-ai-builder.generate') }}"
        data-starter-files-url="{{ route('admin.ai-projects.page-ai-builder.starter-files') }}"
        data-iterate-url-template="{{ route('admin.ai-projects.page-ai-builder.iterate', ['session' => '__SESSION__']) }}"
        data-discuss-url-template="{{ route('admin.ai-projects.page-ai-builder.discuss', ['session' => '__SESSION__']) }}"
        data-save-url="{{ route('admin.ai-projects.page-ai-builder.save') }}"
        data-image-upload-url="{{ route('admin.uploads.inline-image') }}"
        data-custom-domain-url="{{ route('admin.ai-projects.page-ai-builder.custom-domain') }}"
        data-server-ip="{{ \App\Models\Setting::get('custom_domain_server_ip', '') }}"
        data-server-host="{{ request()->getHost() }}"
        data-pages-index-url="{{ route('admin.pages.index') }}"
        data-dashboard-url="{{ route('admin.dashboard') }}"
        data-app-supabase-url-template="{{ route('admin.apps.supabase.show', ['page' => '__PAGE__']) }}"
        data-app-supabase-users-url-template="{{ route('admin.apps.supabase.users', ['page' => '__PAGE__']) }}"
        data-app-supabase-buckets-url-template="{{ route('admin.apps.supabase.buckets', ['page' => '__PAGE__']) }}"
        data-app-supabase-tables-url-template="{{ route('admin.apps.supabase.tables', ['page' => '__PAGE__']) }}"
        data-app-supabase-table-data-url-template="{{ route('admin.apps.supabase.table-data', ['page' => '__PAGE__']) }}"
        data-supabase-configs-list-url="{{ route('admin.supabase-configs.list.json') }}"
        data-supabase-oauth-redirect-url="{{ route('admin.supabase.oauth.redirect') }}"
        data-session-store-url="{{ route('admin.ai-projects.page-ai-builder.session.store') }}"
        data-session-update-url="{{ isset($sessionData['session_id']) ? route('admin.ai-projects.page-ai-builder.session.update', $sessionData['session_id']) : '' }}"
        data-session-compile-url-template="{{ route('admin.ai-projects.page-ai-builder.sandbox.compile', ['session' => '__SESSION__']) }}"
        data-session-preview-url-template="{{ route('admin.ai-projects.page-ai-builder.sandbox.preview', ['session' => '__SESSION__']) }}"
        data-session-show-url-template="{{ route('admin.ai-projects.page-ai-builder.show', ['session' => '__SESSION__']) }}"
        data-progress-url="{{ route('admin.ai-projects.page-ai-builder.progress') }}"
        data-stream-url="{{ route('admin.ai-projects.page-ai-builder.stream') }}"
        data-cancel-url="{{ route('admin.ai-projects.page-ai-builder.cancel') }}"
        data-shares-url="{{ route('admin.ai-projects.page-ai-builder.shares.index', ['session' => '__SESSION__']) }}"
        data-shares-invite-url="{{ route('admin.ai-projects.page-ai-builder.shares.invite', ['session' => '__SESSION__']) }}"
        data-shares-update-url="{{ route('admin.ai-projects.page-ai-builder.shares.update', ['session' => '__SESSION__', 'shareId' => '__SHARE_ID__']) }}"
        data-shares-remove-url="{{ route('admin.ai-projects.page-ai-builder.shares.remove', ['session' => '__SESSION__', 'shareId' => '__SHARE_ID__']) }}"
        data-export-url-template="{{ route('admin.ai-projects.page-ai-builder.export', ['session' => '__SESSION__']) }}"
        data-initial-session='@json($sessionData)'
        data-provider-models='@json($providerModels ?? [])'
        data-ai-builder-capabilities='@json($aiBuilderCapabilities ?? [])'
        data-ai-builder-credit-status='@json($aiBuilderCreditStatus ?? [])'
    ></div>
</div>
@endsection
