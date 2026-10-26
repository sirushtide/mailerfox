@extends('layouts.public')

@section('title', 'UniBox — Shared Inbox')
@section('metaDescription', 'Unify sales, support, and relationship conversations inside a shared inbox with collaboration tools, faster response workflows, organized accounts, and clearer conversation context.')

@section('content')
<div class="bg-white dark:bg-gray-900">

    {{-- ── Hero ───────────────────────────────────────────────────────────── --}}
    <section class="relative isolate overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_right,rgba(6,182,212,.14),transparent_50%),radial-gradient(circle_at_bottom_left,rgba(16,185,129,.10),transparent_50%)]"></div>
        <div class="mx-auto max-w-7xl px-6 pb-20 pt-24 lg:px-8 lg:pt-32">
            <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
                <div class="max-w-2xl">
                    <span class="inline-flex items-center rounded-full bg-cyan-50 dark:bg-cyan-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-cyan-700 dark:text-cyan-300 ring-1 ring-cyan-200 dark:ring-cyan-500/20">Collaborative Inbox System</span>
                    <h1 class="mt-6 text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl lg:text-4xl">Bring every important conversation into one calm, organized workspace.</h1>
                    <p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300">UniBox gives your team a shared inbox experience: connected accounts, account-level organization, team collaboration, and faster, more consistent replies.</p>
                    <div class="mt-8 flex flex-wrap gap-4">
                        <a href="{{ route('register') }}" class="rounded-xl bg-gray-900 dark:bg-white px-6 py-3 text-sm font-semibold text-white dark:text-gray-900 shadow-lg transition hover:bg-gray-800 dark:hover:bg-gray-100">Get Started Free</a>
                        <a href="#unibox-features" class="rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-6 py-3 text-sm font-semibold text-gray-700 dark:text-gray-200 transition hover:bg-gray-50 dark:hover:bg-gray-700">Explore Features</a>
                    </div>
                    <div class="mt-10 grid grid-cols-3 gap-6">
                        <div>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">Unified</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Inbox view</p>
                        </div>
                        <div>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">Collaborative</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Team workflow</p>
                        </div>
                        <div>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">Visible</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Conversation context</p>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <div class="">
                        <img src="https://i.ibb.co.com/PzwtXfxf/Group-5-2.png" alt="Group 5 (2)" border="0">
                    </div>
                    <div class="absolute -bottom-6 -right-6 hidden rounded-2xl bg-cyan-600 p-5 text-white shadow-xl lg:block">
                        <p class="text-xs uppercase tracking-widest text-cyan-100">Ideal for</p>
                        <p class="mt-1 text-sm font-semibold">Support &amp; sales teams</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Icon Boxes ─────────────────────────────────────────────────────── --}}
    <section id="unibox-features" class="py-20 bg-gray-50 dark:bg-gray-800/40">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <span class="inline-flex rounded-full bg-cyan-50 dark:bg-cyan-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-cyan-700 dark:text-cyan-300">Core Capabilities</span>
                <h2 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">A modern inbox built for fast-moving teams</h2>
                <p class="mt-4 text-base leading-7 text-gray-600 dark:text-gray-400">From capturing new inboxes to replying with context, UniBox keeps collaboration structured.</p>
            </div>
            <div class="mt-14 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 shadow-sm transition hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-cyan-50 dark:bg-cyan-500/10 text-cyan-600 dark:text-cyan-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">Connected Inboxes</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">Group connected inbox accounts into a cleaner workspace without hopping between tools and tabs.</p>
                </div>
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 shadow-sm transition hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">Shared Ownership</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">Assign conversations, add notes, and keep internal context visible so your team responds with confidence.</p>
                </div>
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 shadow-sm transition hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-50 dark:bg-violet-500/10 text-violet-600 dark:text-violet-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/></svg>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">Reply Workflow</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">Keep conversations moving with a cleaner thread view and a reply experience built for speed and clarity.</p>
                </div>
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 shadow-sm transition hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"/></svg>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">Organized Accounts</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">Switch inbox contexts, manage conversations, and reduce missed messages across multiple connected mailboxes.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Feature Showcase ───────────────────────────────────────────────── --}}
    <section class="py-20">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="grid gap-16 lg:grid-cols-2 lg:items-center">
                <div>
                    <span class="inline-flex rounded-full bg-emerald-50 dark:bg-emerald-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-emerald-700 dark:text-emerald-300">Why UniBox</span>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">One surface for scattered conversations, built for real collaboration.</h2>
                    <div class="mt-8 space-y-5">
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 p-5">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Single workspace for every inbox</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Instead of jumping account to account, bring communication into a shared workflow where the important messages stay visible.</p>
                        </div>
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 p-5">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Shared context improves reply quality</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Notes, contact details, and account context help your team reply with continuity rather than guesswork.</p>
                        </div>
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 p-5">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Designed for collaboration, not just storage</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">UniBox focuses on workflow visibility so messages can be assigned, reviewed, and handled more intentionally.</p>
                        </div>
                    </div>
                </div>
                <div>                        
                    <img src="https://i.ibb.co.com/dJkZQMVq/Group-6.png" alt="Group 6" border="0">
                </div>
            </div>
        </div>
    </section>

    {{-- ── How It Works ───────────────────────────────────────────────────── --}}
    <section class="py-20 bg-gray-50 dark:bg-gray-800/40">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <span class="inline-flex rounded-full bg-emerald-50 dark:bg-emerald-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-emerald-700 dark:text-emerald-300">Team Workflow</span>
                <h2 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">How UniBox helps teams stay aligned every day.</h2>
            </div>
            <div class="mt-14 grid gap-8 lg:grid-cols-3">
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-7">
                    <span class="text-xs font-bold uppercase tracking-widest text-cyan-600 dark:text-cyan-400">01</span>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Connect inbox sources</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">Bring supported accounts into the workspace so messages can be organized around one shared operational flow.</p>
                </div>
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-7">
                    <span class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">02</span>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Collaborate inside threads</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">Assign owners, review context, and keep important notes close to the conversation before replying.</p>
                </div>
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-7">
                    <span class="text-xs font-bold uppercase tracking-widest text-violet-600 dark:text-violet-400">03</span>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Respond with consistency</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">Keep service and relationship quality high by giving your team a clearer view of every conversation lifecycle.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Detailed Benefits ──────────────────────────────────────────────── --}}
    <section class="py-20">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-start">
                <div class="lg:sticky lg:top-24">
                    <span class="inline-flex rounded-full bg-cyan-50 dark:bg-cyan-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-cyan-700 dark:text-cyan-300">Built for shared communication</span>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">Replace scattered inboxes with a shared operating system for conversations.</h2>
                    <p class="mt-5 text-base leading-8 text-gray-600 dark:text-gray-400">UniBox gives teams a clearer way to manage email conversations across accounts, owners, and customer contexts. It reduces inbox switching, improves response consistency, and helps teams understand what needs attention before messages slip through the cracks.</p>
                </div>
                <div class="grid gap-5">
                    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">A single source of truth for team inboxes</h3>
                        <p class="mt-3 text-sm leading-7 text-gray-600 dark:text-gray-400">Instead of leaving important customer or sales messages inside individual mailboxes, UniBox brings them into a shared workspace where teammates can see context, ownership, and conversation status in one place.</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Faster triage with clearer ownership</h3>
                        <p class="mt-3 text-sm leading-7 text-gray-600 dark:text-gray-400">Teams can quickly identify new messages, open conversations, and priority threads. Clear assignment and visibility reduce duplicate replies, missed follow-ups, and the confusion that happens when everyone assumes someone else is handling it.</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Better continuity across every reply</h3>
                        <p class="mt-3 text-sm leading-7 text-gray-600 dark:text-gray-400">Because conversation context stays close to the thread, teammates can understand previous interactions before responding. That helps support, sales, and success teams sound aligned even when multiple people participate.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Use Cases ──────────────────────────────────────────────────────── --}}
    <section class="py-20 bg-gray-50 dark:bg-gray-800/40">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-3xl text-center">
                <span class="inline-flex rounded-full bg-teal-50 dark:bg-teal-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-teal-700 dark:text-teal-300">Use cases</span>
                <h2 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">A smarter inbox layer for every team that lives in email.</h2>
                <p class="mt-4 text-base leading-7 text-gray-600 dark:text-gray-400">UniBox is useful anywhere conversation quality, response speed, and team accountability matter.</p>
            </div>
            <div class="mt-14 grid gap-6 lg:grid-cols-3">
                <div class="rounded-2xl bg-white dark:bg-gray-900 p-7 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Customer support teams</h3>
                    <p class="mt-3 text-sm leading-7 text-gray-600 dark:text-gray-400">Centralize incoming support conversations, reduce missed customer messages, and make it easier for teammates to understand open issues before replying. UniBox helps support teams deliver more consistent experiences.</p>
                </div>
                <div class="rounded-2xl bg-white dark:bg-gray-900 p-7 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Sales and success teams</h3>
                    <p class="mt-3 text-sm leading-7 text-gray-600 dark:text-gray-400">Keep prospect, customer, renewal, and onboarding conversations organized so every relationship has visible context. Teams can follow up faster and maintain continuity across handoffs.</p>
                </div>
                <div class="rounded-2xl bg-white dark:bg-gray-900 p-7 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Founder-led operations</h3>
                    <p class="mt-3 text-sm leading-7 text-gray-600 dark:text-gray-400">For lean teams, UniBox creates a simple shared inbox foundation without adding heavyweight helpdesk complexity. Founders can stay close to important conversations while giving teammates room to collaborate.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Workflow Narrative ─────────────────────────────────────────────── --}}
    <section class="py-20">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
                <img src="https://i.ibb.co.com/39NjQZVg/Group-8.png" alt="Group 8" border="0">
                <div>
                    <span class="inline-flex rounded-full bg-violet-50 dark:bg-violet-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-violet-700 dark:text-violet-300">Calmer operations</span>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Give every conversation a clear path from arrival to resolution.</h2>
                    <p class="mt-5 text-base leading-8 text-gray-600 dark:text-gray-400">UniBox is not just a place to store messages. It is a workflow layer that helps teams understand what arrived, who should respond, what context matters, and whether a conversation has been handled properly.</p>
                    <div class="mt-8 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Message triage</p>
                            <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">Spot new and unresolved conversations quickly so response queues stay manageable.</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Team ownership</p>
                            <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">Make responsibility visible so teammates know who is handling each thread.</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Account context</p>
                            <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">Keep connected inboxes and customer relationships organized in one interface.</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Reply consistency</p>
                            <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">Help every teammate respond with the same level of detail and awareness.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── FAQs ───────────────────────────────────────────────────────────── --}}
    <section id="unibox-faq" class="py-20">
        <div class="mx-auto max-w-3xl px-6 lg:px-8">
            <div class="text-center">
                <span class="inline-flex rounded-full bg-violet-50 dark:bg-violet-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-violet-700 dark:text-violet-300">FAQs</span>
                <h2 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Common questions before adopting a shared inbox.</h2>
            </div>
            <div class="mt-10 space-y-4" x-data="{ active: null }">
                <div class="rounded-xl border border-gray-200 dark:border-gray-700">
                    <button @click="active = active === 1 ? null : 1" class="flex w-full items-center justify-between px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                        Is UniBox meant for a single inbox or multiple inboxes?
                        <svg class="h-5 w-5 shrink-0 transition" :class="active === 1 ? 'rotate-45' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    </button>
                    <div x-show="active === 1" x-collapse class="px-6 pb-4 text-sm leading-7 text-gray-600 dark:text-gray-400">
                        It is designed to make one or many connected inboxes easier to manage inside a single organized workspace.
                    </div>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700">
                    <button @click="active = active === 2 ? null : 2" class="flex w-full items-center justify-between px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                        Can teams collaborate on the same conversations?
                        <svg class="h-5 w-5 shrink-0 transition" :class="active === 2 ? 'rotate-45' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    </button>
                    <div x-show="active === 2" x-collapse class="px-6 pb-4 text-sm leading-7 text-gray-600 dark:text-gray-400">
                        Yes. UniBox is positioned as a collaborative inbox experience where teammates can review context, assign responsibility, and keep communication consistent.
                    </div>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700">
                    <button @click="active = active === 3 ? null : 3" class="flex w-full items-center justify-between px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                        What kind of teams benefit most from UniBox?
                        <svg class="h-5 w-5 shrink-0 transition" :class="active === 3 ? 'rotate-45' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    </button>
                    <div x-show="active === 3" x-collapse class="px-6 pb-4 text-sm leading-7 text-gray-600 dark:text-gray-400">
                        Sales, customer success, support, and founder-led teams benefit most when they need better visibility across inbox-based communication.
                    </div>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700">
                    <button @click="active = active === 4 ? null : 4" class="flex w-full items-center justify-between px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                        Does UniBox replace the actual inbox for day-to-day responses?
                        <svg class="h-5 w-5 shrink-0 transition" :class="active === 4 ? 'rotate-45' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    </button>
                    <div x-show="active === 4" x-collapse class="px-6 pb-4 text-sm leading-7 text-gray-600 dark:text-gray-400">
                        That is the goal: to centralize conversations and make day-to-day response operations cleaner, faster, and more collaborative.
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── CTA ────────────────────────────────────────────────────────────── --}}
    <section class="py-20 bg-gradient-to-br from-cyan-600 to-teal-700">
        <div class="mx-auto max-w-4xl px-6 text-center lg:px-8">
            <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">Ready to create a cleaner shared inbox experience?</h2>
            <p class="mt-4 text-lg leading-8 text-cyan-100">Open UniBox to manage accounts, centralize conversations, and keep your team aligned on every reply.</p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                <a href="{{ route('register') }}" class="rounded-xl bg-white px-6 py-3 text-sm font-semibold text-cyan-700 shadow-lg transition hover:bg-cyan-50">Get Started Free</a>
                <a href="{{ route('login') }}" class="rounded-xl border border-white/30 bg-white/10 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/20">Sign In</a>
            </div>
        </div>
    </section>

</div>
@endsection
