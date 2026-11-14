<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app('locale.direction')->dir() }}">
<head>
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unsubscribe</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
                <div class="text-center mb-6">
                    <div class="rounded-full bg-gray-100 w-14 h-14 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Unsubscribe</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        You're unsubscribing <span class="font-medium text-gray-900">{{ $email }}</span>
                        @if(isset($list))
                            from <span class="font-medium text-gray-900">{{ $list->display_name ?? $list->name }}</span>
                        @endif
                    </p>
                </div>

                <form method="POST" action="{{ isset($uuid) ? route('unsubscribe.confirm', $uuid) : route('public.unsubscribe.confirm', ['list' => $list->id, 'email' => $email, 'token' => request()->route('token')]) }}">
                    @csrf

                    <p class="text-sm font-medium text-gray-700 mb-3">Before you go — why are you leaving? <span class="text-gray-400 font-normal">(optional)</span></p>

                    <div class="space-y-2.5 mb-5">
                        @foreach($reasons as $value => $label)
                            <label class="flex items-center gap-3 rounded-lg border border-gray-200 px-4 py-3 cursor-pointer transition hover:border-primary-400 hover:bg-gray-50 has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50">
                                <input type="radio" name="reason" value="{{ $value }}" class="h-4 w-4 border-gray-300 text-primary-600 focus:ring-primary-500">
                                <span class="text-sm text-gray-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <textarea name="feedback" rows="2" maxlength="1000" placeholder="Anything else you'd like us to know? (optional)"
                        class="w-full rounded-lg border border-gray-200 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:ring-primary-500 mb-6"></textarea>

                    <button type="submit" class="w-full rounded-lg bg-gray-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">
                        Unsubscribe me
                    </button>
                </form>

                <p class="mt-4 text-center">
                    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : '#' }}" onclick="window.close(); return false;" class="text-sm text-gray-500 hover:text-gray-700">Never mind, keep me subscribed</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
