@extends('layouts.admin')

@section('title', __('Create Plan'))
@section('page-title', __('Create Plan'))
@section('content-padding-classes', 'p-0')

@section('content')
<form method="POST" action="{{ route('admin.plans.store') }}" class="flex flex-1 h-full overflow-hidden">
    @include('admin.plans.form', ['plan' => $plan ?? new \App\Models\Plan()])
</form>
@endsection

