@extends('layouts.public')

@section('title', 'Cold Email Outreach')
@section('metaDescription', 'Launch high-converting cold email outreach campaigns with multi-step sequences, personalization, sender rotation, reply tracking, and pipeline visibility — all from one streamlined workspace.')

@section('content')
<div class="bg-white dark:bg-gray-900">

    {{-- ── Hero ───────────────────────────────────────────────────────────── --}}
    <section class="relative isolate overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,rgba(99,102,241,.14),transparent_50%),radial-gradient(circle_at_bottom_right,rgba(59,130,246,.10),transparent_50%)]"></div>
        <div class="mx-auto max-w-7xl px-6 pb-20 pt-24 lg:px-8 lg:pt-32">
            <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
                <div class="max-w-2xl">
                    <span class="inline-flex items-center rounded-full bg-indigo-50 dark:bg-indigo-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-indigo-600 dark:text-indigo-300 ring-1 ring-indigo-200 dark:ring-indigo-500/20">Outbound Revenue Engine</span>
                    <h1 class="mt-6 text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl lg:text-6xl">Scale personalized outbound without losing deliverability.</h1>
                    <p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300">Build multistep cold-email sequences, personalize every message, rotate senders intelligently, and monitor engagement — all from one focused command center.</p>
                    <div class="mt-8 flex flex-wrap gap-4">
                        <a href="https://codecanyon.net/item/coldmail-cold-email-outreach-addons-for-mailpurse/62976476" target="_blank" class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-indigo-700">Get ColdMail Addons</a>
                        <a href="#outreach-features" class="rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-6 py-3 text-sm font-semibold text-gray-700 dark:text-gray-200 transition hover:bg-gray-50 dark:hover:bg-gray-700">Explore Features</a>
                    </div>
                    <div class="mt-10 grid grid-cols-3 gap-6">
                        <div>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">Multi-step</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Automated sequences</p>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">Dynamic</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Personalization</p>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">Smart</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sender rotation</p>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <img src="https://i.ibb.co.com/v6wx3jqC/Group-9.png" alt="Group 9" border="0">
                    <div class="absolute -bottom-6 -right-6 hidden rounded-2xl bg-indigo-600 p-5 text-white shadow-xl lg:block">
                        <p class="text-xs uppercase tracking-widest text-indigo-200">Best for</p>
                        <p class="mt-1 text-sm font-semibold">Agencies &amp; SDR teams</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Icon Boxes ─────────────────────────────────────────────────────── --}}
    <section id="outreach-features" class="py-20 bg-gray-50 dark:bg-gray-800/40">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <span class="inline-flex rounded-full bg-blue-50 dark:bg-blue-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-blue-700 dark:text-blue-300">Core Capabilities</span>
                <h2 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">Everything your outbound team needs</h2>
                <p class="mt-4 text-base leading-7 text-gray-600 dark:text-gray-400">From campaign creation to reply management, Outreach keeps your process structured.</p>
            </div>
            <div class="mt-14 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 shadow-sm transition hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m22 2-7 20-4-9-9-4Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M22 2 11 13"/></svg>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">Sequence Builder</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">Create outreach journeys with intros, follow-ups, and nudges in one structured flow.</p>
                </div>
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 shadow-sm transition hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-50 dark:bg-violet-500/10 text-violet-600 dark:text-violet-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20v-6M6 20V10M18 20V4"/></svg>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">Performance Analytics</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">Track what gets delivered, opened, and replied to — see exactly where each sender contributes.</p>
                </div>
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 shadow-sm transition hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">Pipeline-Ready Replies</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">Surface active conversations and intent signals to turn warm engagement into real meetings.</p>
                </div>
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 shadow-sm transition hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3a6 6 0 0 0-6 6c0 7-3 9-3 9h18s-3-2-3-9a6 6 0 0 0-6-6"/><path stroke-linecap="round" stroke-linejoin="round" d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">Deliverability Awareness</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">Keep sender activity balanced with rotation-aware sending and clearer feedback on send quality.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Feature Showcase ───────────────────────────────────────────────── --}}
    <section class="py-20">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="grid gap-16 lg:grid-cols-2 lg:items-center">
                <div>
                    <span class="inline-flex rounded-full bg-indigo-50 dark:bg-indigo-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-indigo-700 dark:text-indigo-300">Designed for Focus</span>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Everything your outbound team needs to move from cold lead to warm reply.</h2>
                    <div class="mt-8 space-y-5">
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 p-5">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Smart sequence execution</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Build campaigns with staged touches, timing control, and flexible options so your outbound never feels robotic.</p>
                        </div>
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 p-5">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Team-ready visibility</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">See campaign health, lead movement, and sender-level performance without stitching reports together manually.</p>
                        </div>
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 p-5">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Personalized at scale</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Use lead data and campaign variables to keep every email relevant while scaling your outbound pipeline.</p>
                        </div>
                    </div>
                </div>
                <div>
                    {{-- <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-6">
                        <div class="aspect-[4/3] rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center p-8 text-center text-sm leading-relaxed text-gray-400 dark:text-gray-500">
                            Placeholder image: A premium editorial-style illustration of a revenue team reviewing outreach funnels, deliverability trends, and reply sentiment across multiple sender identities.
                        </div>
                    </div> --}}
                    <img src="https://i.ibb.co.com/zTsg7Ppy/Group-11-1.png" alt="Group 11 (1)" border="0">
                </div>
            </div>
        </div>
    </section>

    {{-- ── How It Works ───────────────────────────────────────────────────── --}}
    <section class="py-20 bg-gray-50 dark:bg-gray-800/40">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <span class="inline-flex rounded-full bg-violet-50 dark:bg-violet-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-violet-700 dark:text-violet-300">How It Works</span>
                <h2 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">A simple workflow for complex outbound operations.</h2>
            </div>
            <div class="mt-14 grid gap-8 lg:grid-cols-3">
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-7">
                    <span class="text-xs font-bold uppercase tracking-widest text-indigo-600 dark:text-indigo-400">01</span>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Plan the campaign</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">Set objectives, add leads, configure sender pools, and build the sequence your audience should receive.</p>
                </div>
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-7">
                    <span class="text-xs font-bold uppercase tracking-widest text-violet-600 dark:text-violet-400">02</span>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Launch with confidence</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">Start sending with clear visibility into sender usage, campaign progress, and engagement signals that matter.</p>
                </div>
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-7">
                    <span class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">03</span>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Optimize &amp; scale</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">Use analytics, delivery insights, and engagement patterns to refine campaigns and improve conversion quality over time.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Detailed Benefits ──────────────────────────────────────────────── --}}
    <section class="py-20">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-start">
                <div class="lg:sticky lg:top-24">
                    <span class="inline-flex rounded-full bg-blue-50 dark:bg-blue-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-blue-700 dark:text-blue-300">Built for modern outbound</span>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">Turn cold outreach into a repeatable, measurable acquisition channel.</h2>
                    <p class="mt-5 text-base leading-8 text-gray-600 dark:text-gray-400">Outreach is designed for teams that need more than a basic email blast. It helps you plan targeted sequences, control sending behavior, understand engagement, and keep replies visible so every campaign can improve over time.</p>
                </div>
                <div class="grid gap-5">
                    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Audience-first campaign planning</h3>
                        <p class="mt-3 text-sm leading-7 text-gray-600 dark:text-gray-400">Segment prospects by intent, source, role, industry, or offer so each outreach path feels relevant. Instead of one generic message for everyone, teams can create focused sequences that match the recipient's problem and stage of awareness.</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Sequence logic that supports real conversations</h3>
                        <p class="mt-3 text-sm leading-7 text-gray-600 dark:text-gray-400">Use structured follow-ups to stay persistent without becoming noisy. Each step can reinforce the value proposition, introduce proof, handle objections, or move a lead toward a reply when timing is right.</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Operational visibility for growing teams</h3>
                        <p class="mt-3 text-sm leading-7 text-gray-600 dark:text-gray-400">Managers can understand which campaigns are active, where leads are in the journey, how senders are performing, and which messaging angles are producing replies. This makes outbound easier to coach, audit, and scale.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Use Cases ──────────────────────────────────────────────────────── --}}
    <section class="py-20 bg-gray-50 dark:bg-gray-800/40">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-3xl text-center">
                <span class="inline-flex rounded-full bg-amber-50 dark:bg-amber-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-amber-700 dark:text-amber-300">Use cases</span>
                <h2 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Designed for teams that rely on outbound conversations.</h2>
                <p class="mt-4 text-base leading-7 text-gray-600 dark:text-gray-400">Whether you are finding your first customers or managing outreach for many clients, the addon gives you a clear operating system for outbound growth.</p>
            </div>
            <div class="mt-14 grid gap-6 lg:grid-cols-3">
                <div class="rounded-2xl bg-white dark:bg-gray-900 p-7 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Lead generation agencies</h3>
                    <p class="mt-3 text-sm leading-7 text-gray-600 dark:text-gray-400">Run multiple campaigns for different client offers while keeping sequencing, sender usage, and reply outcomes easier to track. Outreach gives agencies a more professional workflow for campaign delivery and optimization.</p>
                </div>
                <div class="rounded-2xl bg-white dark:bg-gray-900 p-7 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">SaaS sales teams</h3>
                    <p class="mt-3 text-sm leading-7 text-gray-600 dark:text-gray-400">Reach target accounts with messaging that can evolve across product education, pain-point framing, social proof, and meeting requests. Teams can measure which sequences generate the right kind of conversations.</p>
                </div>
                <div class="rounded-2xl bg-white dark:bg-gray-900 p-7 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Founders and operators</h3>
                    <p class="mt-3 text-sm leading-7 text-gray-600 dark:text-gray-400">Validate offers, open partner conversations, and create predictable follow-up without building a complex outbound stack. Outreach keeps the workflow focused so small teams can move quickly.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Reporting Narrative ────────────────────────────────────────────── --}}
    <section class="py-20">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
                {{-- <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-6">
                    <div class="aspect-[16/10] rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center p-8 text-center text-sm leading-relaxed text-gray-400 dark:text-gray-500">
                        Placeholder image: A detailed analytics dashboard showing campaign comparison, reply rate trends, sender health indicators, sequence step performance, and recommended next actions.
                    </div>
                </div> --}}
                <img src="https://i.ibb.co.com/svPRV7Jv/Group-13.png" alt="Group 13" border="0">
                <div>
                    <span class="inline-flex rounded-full bg-emerald-50 dark:bg-emerald-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-emerald-700 dark:text-emerald-300">Clearer decisions</span>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Know what is working before you scale it.</h2>
                    <p class="mt-5 text-base leading-8 text-gray-600 dark:text-gray-400">Cold email succeeds when teams learn quickly. Outreach helps you understand whether a campaign is earning replies, whether follow-ups are improving conversion, and whether sender activity is balanced enough to support sustainable growth.</p>
                    <div class="mt-8 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Campaign health</p>
                            <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">Monitor active campaign performance without digging through disconnected reports.</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Reply quality</p>
                            <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">Focus on conversations that can become demos, partnerships, or qualified opportunities.</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Sender balance</p>
                            <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">Understand how sender identities are contributing to campaign volume and outcomes.</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Sequence insight</p>
                            <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">Identify which steps create engagement and where messaging should be refined.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── FAQs ───────────────────────────────────────────────────────────── --}}
    <section id="outreach-faq" class="py-20">
        <div class="mx-auto max-w-3xl px-6 lg:px-8">
            <div class="text-center">
                <span class="inline-flex rounded-full bg-emerald-50 dark:bg-emerald-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-emerald-700 dark:text-emerald-300">FAQs</span>
                <h2 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Questions teams ask before they start sending.</h2>
            </div>
            <div class="mt-10 space-y-4" x-data="{ active: null }">
                <div class="rounded-xl border border-gray-200 dark:border-gray-700">
                    <button @click="active = active === 1 ? null : 1" class="flex w-full items-center justify-between px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                        Can Outreach support multistep cold email campaigns?
                        <svg class="h-5 w-5 shrink-0 transition" :class="active === 1 ? 'rotate-45' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    </button>
                    <div x-show="active === 1" x-collapse class="px-6 pb-4 text-sm leading-7 text-gray-600 dark:text-gray-400">
                        Yes. The addon is designed around staged outbound messaging so you can build structured follow-up journeys instead of sending one-off emails.
                    </div>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700">
                    <button @click="active = active === 2 ? null : 2" class="flex w-full items-center justify-between px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                        Will I be able to personalize messages per lead?
                        <svg class="h-5 w-5 shrink-0 transition" :class="active === 2 ? 'rotate-45' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    </button>
                    <div x-show="active === 2" x-collapse class="px-6 pb-4 text-sm leading-7 text-gray-600 dark:text-gray-400">
                        Yes. Outreach supports variable-driven messaging so you can keep your campaigns relevant while maintaining sending efficiency.
                    </div>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700">
                    <button @click="active = active === 3 ? null : 3" class="flex w-full items-center justify-between px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                        What makes this different from basic campaign sending?
                        <svg class="h-5 w-5 shrink-0 transition" :class="active === 3 ? 'rotate-45' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    </button>
                    <div x-show="active === 3" x-collapse class="px-6 pb-4 text-sm leading-7 text-gray-600 dark:text-gray-400">
                        Outreach focuses on outbound workflows: sequence progression, lead-by-lead activity, sender coordination, and reply-centric optimization.
                    </div>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700">
                    <button @click="active = active === 4 ? null : 4" class="flex w-full items-center justify-between px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                        Is this useful for agencies managing several campaigns?
                        <svg class="h-5 w-5 shrink-0 transition" :class="active === 4 ? 'rotate-45' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    </button>
                    <div x-show="active === 4" x-collapse class="px-6 pb-4 text-sm leading-7 text-gray-600 dark:text-gray-400">
                        Absolutely. The workspace is structured for teams that need clarity across multiple campaigns, senders, and prospect lists.
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── CTA ────────────────────────────────────────────────────────────── --}}
    <section class="py-20 bg-gradient-to-br from-indigo-600 to-blue-700">
        <div class="mx-auto max-w-4xl px-6 text-center lg:px-8">
            <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">Ready to turn outbound into a predictable growth channel?</h2>
            <p class="mt-4 text-lg leading-8 text-indigo-100">Open your Outreach workspace and start launching sequences that convert cold leads into warm conversations.</p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                <a href="https://codecanyon.net/item/coldmail-cold-email-outreach-addons-for-mailpurse/62976476" target="_blank" class="rounded-xl bg-white px-6 py-3 text-sm font-semibold text-indigo-700 shadow-lg transition hover:bg-indigo-50">Purchase Now ($29)</a>
                <a href="{{ route('login') }}" class="rounded-xl border border-white/30 bg-white/10 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/20">Sign In</a>
            </div>
        </div>
    </section>

</div>
@endsection
