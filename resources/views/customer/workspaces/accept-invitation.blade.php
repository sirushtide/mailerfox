<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app('locale.direction')->dir() }}" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-bind:class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Accept Invitation') }} - {{ \App\Models\Setting::get('app_name', config('app.name', 'MailerFox')) }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 font-sans antialiased dark:bg-gray-900">
<div class="flex min-h-screen items-center justify-center px-4">
    <div class="w-full max-w-md">
        <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-lg dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-6 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-primary-600 text-white font-bold text-lg">
                    {{ strtoupper(substr($invitation->workspace->name, 0, 2)) }}
                </div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('You\'re invited!') }}</h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('You\'ve been invited to join the workspace') }}
                    <strong class="text-gray-900 dark:text-white">{{ $invitation->workspace->name }}</strong>
                    {{ __('as') }} <strong>{{ $invitation->role?->name ?? __('Member') }}</strong>.
                </p>
            </div>

            @if($errors->any())
                <div class="mb-4 rounded-lg bg-rose-50 p-3 text-sm text-rose-600 dark:bg-rose-900/30 dark:text-rose-400">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('workspace.invite.register', $invitation->token) }}">
                @csrf

                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Email') }}</label>
                        <input type="email" disabled value="{{ $invitation->email }}"
                               class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                    </div>

                    <div>
                        <label for="name" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Your Name') }} <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                               class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                               placeholder="{{ __('John Doe') }}">
                    </div>

                    <div>
                        <label for="password" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Create Password') }} <span class="text-rose-500">*</span></label>
                        <input type="password" name="password" id="password" required
                               class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                               placeholder="********">
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Confirm Password') }} <span class="text-rose-500">*</span></label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required
                               class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                               placeholder="********">
                    </div>
                </div>

                <button type="submit"
                        class="mt-6 w-full rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
                    {{ __('Accept Invitation & Create Account') }}
                </button>
            </form>

            <p class="mt-4 text-center text-xs text-gray-500 dark:text-gray-400">
                {{ __('Already have an account?') }}
                <a href="{{ route('login') }}" class="font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">{{ __('Log in') }}</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>
