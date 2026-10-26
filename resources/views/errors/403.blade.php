@php($isImpersonating = (int) session('impersonator_admin_id', 0) > 0)
@php($layout = ($isImpersonating || auth('customer')->check()) ? 'layouts.customer' : (auth('admin')->check() ? 'layouts.admin' : 'layouts.public'))
@extends($layout)

@section('title', 'Access Denied')

@section('content')
<div class="min-h-[calc(100vh-8rem)] flex flex-col items-center justify-center px-4">
    <div class="text-center max-w-md">
        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-600 dark:text-gray-300">
                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
        </div>

        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('No access to this page') }}</h1>
        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
            {{ trim($exception->getMessage()) !== '' ? $exception->getMessage() : __('You can access this page if someone approves your access.') }}
        </p>

        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
            @if(auth('admin')->check())
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center justify-center w-full sm:w-auto px-5 py-2.5 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
                    {{ __('Go to dashboard') }}
                </a>
            @elseif(auth('customer')->check())
                <a href="{{ route('customer.dashboard') }}" class="inline-flex items-center justify-center w-full sm:w-auto px-5 py-2.5 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
                    {{ __('Go to dashboard') }}
                </a>
            @else
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center w-full sm:w-auto px-5 py-2.5 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
                    {{ __('Go to login') }}
                </a>
            @endif
        </div>

        <p class="mt-6 text-xs text-gray-400 dark:text-gray-500">403</p>
    </div>
</div>
@endsection
