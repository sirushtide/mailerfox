@extends('layouts.admin')

@section('title', __('Edit Plan'))
@section('page-title', __('Edit Plan'))
@section('content-padding-classes', 'p-0')

@section('breadcrumbs')
    <ol class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
        <li>
            <a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700 dark:hover:text-gray-300">
                {{ __('Dashboard') }}
            </a>
        </li>
        <li class="text-gray-400">/</li>
        <li>
            <a href="{{ route('admin.plans.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">
                {{ __('Plans') }}
            </a>
        </li>
        <li class="text-gray-400">/</li>
        <li>
            <a href="{{ route('admin.plans.show', $plan) }}" class="hover:text-gray-700 dark:hover:text-gray-300">
                {{ $plan->name }}
            </a>
        </li>
        <li class="text-gray-400">/</li>
        <li class="font-medium text-admin-text-primary">
            {{ __('Edit') }}
        </li>
    </ol>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.plans.update', $plan) }}" class="flex flex-1 h-full overflow-hidden">
    @csrf
    @method('PUT')
    @include('admin.plans.form', ['plan' => $plan])
</form>
@endsection

