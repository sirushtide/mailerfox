@extends('layouts.customer')

@section('title', __('Edit Pipeline'))

@section('hide-page-header', '1')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('customer.crm.pipelines.show', $pipeline) }}" class="text-sm text-gray-500 hover:text-[#1E5FEA] transition-colors">&larr; {{ __('Back to pipeline') }}</a>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ __('Edit Pipeline') }}</h1>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
        <form method="POST" action="{{ route('customer.crm.pipelines.update', $pipeline) }}" class="space-y-5">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Name') }} <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $pipeline->name) }}" required class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Description') }}</label>
                <textarea name="description" rows="2" class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('description', $pipeline->description) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Status') }}</label>
                <select name="status" class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="active" {{ old('status', $pipeline->status) === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                    <option value="archived" {{ old('status', $pipeline->status) === 'archived' ? 'selected' : '' }}>{{ __('Archived') }}</option>
                </select>
            </div>
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('customer.crm.pipelines.show', $pipeline) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">{{ __('Cancel') }}</a>
                <button type="submit" class="rounded-lg bg-[#1E5FEA] px-4 py-2 text-sm font-medium text-white hover:bg-[#174bc0] transition-colors">{{ __('Update Pipeline') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
