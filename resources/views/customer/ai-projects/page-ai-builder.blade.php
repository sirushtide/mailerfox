@extends('layouts.customer')

@section('title', __('AI Builder'))

@section('content-padding-classes', 'p-0')
@section('force-sidebar-collapsed', '1')
@section('hide-top-header', '1')
@section('hide-page-header', '1')
@section('collapsed-sidebar-padding-class', 'lg:pl-0')

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
        data-analyze-url="{{ wsRoute('customer.ai-projects.builder.analyze') }}"
        data-suggestions-url="{{ wsRoute('customer.ai-projects.builder.suggestions') }}"
        data-generate-url="{{ wsRoute('customer.ai-projects.builder.generate') }}"
        data-starter-files-url="{{ wsRoute('customer.ai-projects.builder.starter-files') }}"
        data-iterate-url-template="{{ wsRoute('customer.ai-projects.builder.iterate', ['session' => '__SESSION__']) }}"
        data-discuss-url-template="{{ wsRoute('customer.ai-projects.builder.discuss', ['session' => '__SESSION__']) }}"
        data-save-url="{{ wsRoute('customer.ai-projects.builder.save') }}"
        data-image-upload-url="{{ wsRoute('customer.uploads.inline-image') }}"
        data-custom-domain-url="{{ wsRoute('customer.ai-projects.builder.custom-domain', ['session' => '__SESSION__']) }}"
        data-server-ip="{{ \App\Models\Setting::get('custom_domain_server_ip', '') }}"
        data-server-host="{{ request()->getHost() }}"
        data-pages-index-url="{{ wsRoute('customer.ai-projects.index') }}"
        data-dashboard-url="{{ wsRoute('customer.dashboard') }}"
        data-new-project-url="{{ wsRoute('customer.ai-projects.builder') }}"
        data-app-supabase-url-template=""
        data-app-supabase-users-url-template="{{ wsRoute('customer.supabase.users') }}"
        data-app-supabase-buckets-url-template="{{ wsRoute('customer.supabase.buckets') }}"
        data-app-supabase-tables-url-template="{{ wsRoute('customer.supabase.tables') }}"
        data-app-supabase-table-data-url-template="{{ wsRoute('customer.supabase.table-data') }}"
        data-supabase-configs-list-url=""
        data-supabase-oauth-redirect-url=""
        data-customer-supabase-status-url="{{ wsRoute('customer.supabase.status') }}"
        data-session-store-url="{{ wsRoute('customer.ai-projects.builder.session.store') }}"
        data-session-update-url="{{ isset($sessionData['session_id']) ? wsRoute('customer.ai-projects.builder.session.update', $sessionData['session_id']) : '' }}"
        data-session-compile-url-template="{{ wsRoute('customer.ai-projects.builder.sandbox.compile', ['session' => '__SESSION__']) }}"
        data-session-preview-url-template="{{ wsRoute('customer.ai-projects.builder.sandbox.preview', ['session' => '__SESSION__', 'path' => 'index.html']) }}"
         data-session-show-url-template="{{ wsRoute('customer.ai-projects.builder.show', ['session' => '__SESSION__']) }}"
         data-progress-url="{{ wsRoute('customer.ai-projects.builder.progress') }}"
         data-stream-url="{{ wsRoute('customer.ai-projects.builder.stream') }}"
         data-cancel-url="{{ wsRoute('customer.ai-projects.builder.cancel') }}"
        data-credit-status-url="{{ wsRoute('customer.ai-projects.builder.credit-status') }}"
        data-shares-url="{{ wsRoute('customer.ai-projects.builder.shares', ['session' => '__SESSION__']) }}"
        data-shares-invite-url="{{ wsRoute('customer.ai-projects.builder.shares.invite', ['session' => '__SESSION__']) }}"
        data-shares-update-url="{{ wsRoute('customer.ai-projects.builder.shares.update', ['session' => '__SESSION__', 'share' => '__SHARE__']) }}"
        data-shares-remove-url="{{ wsRoute('customer.ai-projects.builder.shares.remove', ['session' => '__SESSION__', 'share' => '__SHARE__']) }}"
        data-export-url-template="{{ wsRoute('customer.ai-projects.builder.export', ['session' => '__SESSION__']) }}"
        data-initial-session='@json($sessionData)'
        data-provider-models='@json($providerModels ?? [])'
        data-ai-builder-capabilities='@json($aiBuilderCapabilities ?? [])'
        data-ai-builder-credit-status='@json($aiBuilderCreditStatus ?? [])'
    ></div>
</div>
@endsection
