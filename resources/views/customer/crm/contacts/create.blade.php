@extends('layouts.customer')

@section('title', __('Add Contact'))

@section('hide-page-header', '1')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('customer.crm.contacts.index') }}" class="text-sm text-gray-500 hover:text-[#1E5FEA] transition-colors">&larr; {{ __('Back to contacts') }}</a>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ __('Add Contact') }}</h1>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
        <form method="POST" action="{{ route('customer.crm.contacts.store') }}" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('First Name') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    @error('first_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Last Name') }}</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Email') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Phone') }}</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Title') }}</label>
                    <input type="text" name="title" value="{{ old('title') }}" placeholder="{{ __('e.g. CEO, Marketing Manager') }}" class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Company') }}</label>
                    <select name="company_id" class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">{{ __('Select company') }}</option>
                        @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Status') }}</label>
                    <select name="status" class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="lead" {{ old('status', 'lead') === 'lead' ? 'selected' : '' }}>{{ __('Lead') }}</option>
                        <option value="prospect" {{ old('status') === 'prospect' ? 'selected' : '' }}>{{ __('Prospect') }}</option>
                        <option value="customer" {{ old('status') === 'customer' ? 'selected' : '' }}>{{ __('Customer') }}</option>
                        <option value="churned" {{ old('status') === 'churned' ? 'selected' : '' }}>{{ __('Churned') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Source') }}</label>
                    <input type="text" name="source" value="{{ old('source') }}" placeholder="{{ __('e.g. Website, Referral') }}" class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Notes') }}</label>
                <textarea name="notes" rows="3" class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('notes') }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('customer.crm.contacts.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">{{ __('Cancel') }}</a>
                <button type="submit" class="rounded-lg bg-[#1E5FEA] px-4 py-2 text-sm font-medium text-white hover:bg-[#174bc0] transition-colors">{{ __('Save Contact') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
