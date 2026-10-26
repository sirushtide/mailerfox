@extends('layouts.customer')

@section('title', __('Edit Company'))

@section('hide-page-header', '1')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('customer.crm.companies.show', $company) }}" class="text-sm text-gray-500 hover:text-[#1E5FEA] transition-colors">&larr; {{ __('Back to company') }}</a>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ __('Edit Company') }}</h1>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
        <form method="POST" action="{{ route('customer.crm.companies.update', $company) }}" class="space-y-5">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Name') }} <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $company->name) }}" required class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Email') }}</label>
                    <input type="email" name="email" value="{{ old('email', $company->email) }}" class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Phone') }}</label>
                    <input type="text" name="phone" value="{{ old('phone', $company->phone) }}" class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Website') }}</label>
                <input type="url" name="website" value="{{ old('website', $company->website) }}" class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Industry') }}</label>
                    <input type="text" name="industry" value="{{ old('industry', $company->industry) }}" class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Size') }}</label>
                    <input type="text" name="size" value="{{ old('size', $company->size) }}" class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Address') }}</label>
                <textarea name="address" rows="2" class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('address', $company->address) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Description') }}</label>
                <textarea name="description" rows="3" class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('description', $company->description) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Status') }}</label>
                <select name="status" class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="active" {{ old('status', $company->status) === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                    <option value="inactive" {{ old('status', $company->status) === 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                </select>
            </div>
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('customer.crm.companies.show', $company) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">{{ __('Cancel') }}</a>
                <button type="submit" class="rounded-lg bg-[#1E5FEA] px-4 py-2 text-sm font-medium text-white hover:bg-[#174bc0] transition-colors">{{ __('Update Company') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
