@extends('layouts.admin')

@section('title', __('Create Customer Group'))
@section('page-title', __('Create Customer Group'))
@section('content-padding-classes', 'p-0 overflow-hidden')

@section('breadcrumbs')
<ol class="flex flex-wrap items-center gap-1.5 text-sm text-admin-text-secondary">
    <li>
        <a href="{{ route('admin.dashboard') }}" class="font-medium transition hover:text-admin-text-primary">{{ __('Dashboard') }}</a>
    </li>
    <li aria-hidden="true" class="text-admin-text-secondary/50">/</li>
    <li>
        <a href="{{ route('admin.customer-groups.index') }}" class="font-medium transition hover:text-admin-text-primary">{{ __('Customer Groups') }}</a>
    </li>
    <li aria-hidden="true" class="text-admin-text-secondary/50">/</li>
    <li class="font-medium text-admin-text-primary">{{ __('Create') }}</li>
</ol>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.customer-groups.store') }}" class="flex flex-1 h-full overflow-hidden" x-data="{ activeTab: 'basic' }">
    @csrf
    @include('admin.customer-groups._form', ['defaultSettings' => $defaultSettings, 'customerGroup' => null])
</form>
@endsection
