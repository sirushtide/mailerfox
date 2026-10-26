@extends('layouts.public')

@section('title', __('Workspace & Teams'))

@section('content')
<div class="relative overflow-hidden">

    {{-- Hero --}}
    <section class="relative bg-gradient-to-br from-indigo-600 via-purple-600 to-blue-700 py-24 sm:py-32">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iLjA1Ij48cGF0aCBkPSJNMzYgMzRoLTJ2LTRoMnY0em0wLTZoLTJ2LTRoMnY0eiIvPjwvZz48L2c+PC9zdmc+')] opacity-30"></div>
        <div class="relative mx-auto max-w-5xl px-6 text-center">
            <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-white/10 backdrop-blur-sm">
                <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z"/></svg>
            </div>
            <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl">
                {{ __('Workspace & Teams') }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-indigo-100">
                {{ __('Collaborate on email marketing with your team. Share lists, campaigns, and analytics in one unified workspace with custom roles and granular permissions.') }}
            </p>
            <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                <a href="{{ route('register') }}"
                   class="rounded-xl bg-white px-8 py-3.5 text-sm font-bold text-indigo-700 shadow-lg transition hover:bg-indigo-50 hover:shadow-xl">
                    {{ __('Get Started Free') }}
                </a>
                <a href="#features"
                   class="rounded-xl border-2 border-white/30 px-8 py-3.5 text-sm font-bold text-white backdrop-blur-sm transition hover:border-white/50 hover:bg-white/10">
                    {{ __('See Features') }}
                </a>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="bg-white py-20 dark:bg-gray-900">
        <div class="mx-auto max-w-6xl px-6">
            <div class="text-center">
                <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">{{ __('Everything your team needs') }}</h2>
                <p class="mx-auto mt-4 max-w-2xl text-gray-600 dark:text-gray-400">{{ __('From shared inboxes to granular permissions, Workspace & Teams brings true collaboration to your email marketing workflow.') }}</p>
            </div>

            <div class="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @php
                    $features = [
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z"/>', 'title' => 'Shared Workspaces', 'desc' => 'Create dedicated workspaces for each team, client, or brand. All resources stay organized and separated.', 'color' => 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>', 'title' => 'Team Members', 'desc' => 'Invite unlimited team members via email. New users get a lightweight account created automatically.', 'color' => 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>', 'title' => 'Custom Roles & Permissions', 'desc' => 'Define Admin, Editor, Viewer — or create your own roles with fine-grained permission controls.', 'color' => 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>', 'title' => 'Invitation System', 'desc' => 'Send branded invitations with expiry control. Recipients sign up or link their existing account instantly.', 'color' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>', 'title' => 'Instant Workspace Switching', 'desc' => 'Switch between your personal account and team workspaces in a single click from the header.', 'color' => 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6"/>', 'title' => 'Activity Logs', 'desc' => 'Track every team action — who created campaigns, invited members, or changed settings.', 'color' => 'bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400'],
                    ];
                @endphp

                @foreach($features as $f)
                <div class="rounded-2xl border border-gray-100 bg-white p-7 shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl {{ $f['color'] }}">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">{!! $f['icon'] !!}</svg>
                    </div>
                    <h3 class="mt-5 text-lg font-semibold text-gray-900 dark:text-white">{{ __($f['title']) }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ __($f['desc']) }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="bg-gray-50 py-20 dark:bg-gray-800/50">
        <div class="mx-auto max-w-4xl px-6">
            <div class="text-center">
                <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('Get started in 3 steps') }}</h2>
            </div>

            <div class="mt-14 space-y-10">
                @php
                    $steps = [
                        ['num' => '1', 'title' => 'Create a Workspace', 'desc' => 'Give it a name and optional description. Default roles (Admin, Editor, Viewer) are created automatically.'],
                        ['num' => '2', 'title' => 'Invite Your Team', 'desc' => 'Enter an email, pick a role, and send. They receive a branded invitation email and join in seconds.'],
                        ['num' => '3', 'title' => 'Collaborate & Ship', 'desc' => 'Switch to the workspace and start sharing lists, campaigns, templates, and analytics — together.'],
                    ];
                @endphp

                @foreach($steps as $step)
                <div class="flex items-start gap-6">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-lg font-bold text-white">
                        {{ $step['num'] }}
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __($step['title']) }}</h3>
                        <p class="mt-1.5 text-gray-600 dark:text-gray-400">{{ __($step['desc']) }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="bg-white py-20 dark:bg-gray-900">
        <div class="mx-auto max-w-3xl px-6 text-center">
            <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">{{ __('Ready to collaborate?') }}</h2>
            <p class="mx-auto mt-4 max-w-xl text-gray-600 dark:text-gray-400">
                {{ __('Open your first workspace and bring your email marketing team together in one place.') }}
            </p>
            <div class="mt-8">
                <a href="{{ route('register') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-8 py-3.5 text-sm font-bold text-white shadow-lg transition hover:bg-indigo-700 hover:shadow-xl">
                    {{ __('Start Free') }}
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </a>
            </div>
        </div>
    </section>

</div>
@endsection
