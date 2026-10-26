<?php

namespace Database\Seeders;

use App\Models\AiBuilderTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiBuilderTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            $this->heroCenteredTemplate(),
            $this->heroSplitTemplate(),
            $this->aboutSplitTemplate(),
            $this->featuresBentoTemplate(),
            $this->testimonialsGridTemplate(),
            $this->pricingThreeTierTemplate(),
            $this->ctaBandTemplate(),
            $this->faqAccordionTemplate(),
            $this->footerSimpleTemplate(),
        ];

        foreach ($templates as $data) {
            AiBuilderTemplate::updateOrCreate(
                ['template_id' => $data['template_id']],
                $data
            );
        }
    }

    private function heroCenteredTemplate(): array
    {
        return [
            'template_id' => 'hero_centered_01',
            'category' => 'hero',
            'name' => 'Centered Gradient Hero',
            'description' => 'Bold centered hero with gradient blob, pill badge, headline, subheadline and two CTAs.',
            'tags' => ['hero', 'centered', 'gradient', 'two-cta', 'saas'],
            'component_source' => <<<'JSX'
export default function {{ComponentName}}(props) {
    return (
        <section className="relative overflow-hidden bg-ds-background py-20 md:py-28">
            <div className="absolute inset-0 -z-10" style={{ background: 'radial-gradient(circle at 50% 0%, rgba(37,99,235,0.12), transparent 50%)' }} />
            <div className="max-w-7xl mx-auto px-6 text-center">
                {subheading && (
                    <Badge variant={badgeVariant} className="mb-6">{subheading}</Badge>
                )}
                <h1 className="text-5xl md:text-6xl lg:text-7xl font-bold tracking-tight text-ds-foreground mb-6">
                    {heading}
                </h1>
                <p className="text-lg md:text-xl text-ds-muted-foreground max-w-2xl mx-auto mb-10">
                    {body}
                </p>
                <div className="flex flex-wrap items-center justify-center gap-4">
                    {primaryCtaText && (
                        <a href={primaryCtaHref} className="inline-flex items-center justify-center rounded-xl bg-ds-primary px-6 py-3 text-base font-semibold text-white shadow-lg shadow-ds-primary/20 hover:shadow-xl transition">
                            {primaryCtaText}
                        </a>
                    )}
                    {secondaryCtaText && (
                        <a href={secondaryCtaHref} className="inline-flex items-center justify-center rounded-xl border border-ds-border bg-ds-card px-6 py-3 text-base font-semibold text-ds-foreground hover:bg-ds-muted transition">
                            {secondaryCtaText}
                        </a>
                    )}
                </div>
            </div>
        </section>
    );
}
JSX,
            'fields' => [
                ['key' => 'heading', 'label' => 'Heading', 'type' => 'text', 'required' => true],
                ['key' => 'subheading', 'label' => 'Pill Badge', 'type' => 'text'],
                ['key' => 'body', 'label' => 'Body Text', 'type' => 'textarea'],
                ['key' => 'primaryCtaText', 'label' => 'Primary CTA Text', 'type' => 'text'],
                ['key' => 'primaryCtaHref', 'label' => 'Primary CTA URL', 'type' => 'text'],
                ['key' => 'secondaryCtaText', 'label' => 'Secondary CTA Text', 'type' => 'text'],
                ['key' => 'secondaryCtaHref', 'label' => 'Secondary CTA URL', 'type' => 'text'],
            ],
            'defaults' => [
                'heading' => 'Build Faster with AI',
                'subheading' => 'New • AI Powered',
                'body' => 'Create stunning landing pages in minutes with our intelligent page builder.',
                'primaryCtaText' => 'Start Free Trial',
                'primaryCtaHref' => '#',
                'secondaryCtaText' => 'Book a Demo',
                'secondaryCtaHref' => '#',
            ],
            'example_props' => [
                'heading' => 'Turn Emails Into Revenue',
                'subheading' => 'AI Email Platform',
                'body' => 'Automate campaigns, warm up inboxes, and scale your outreach effortlessly.',
                'primaryCtaText' => 'Start Free Trial',
                'primaryCtaHref' => '#',
                'secondaryCtaText' => 'See How It Works',
                'secondaryCtaHref' => '#',
            ],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    private function heroSplitTemplate(): array
    {
        return [
            'template_id' => 'hero_split_01',
            'category' => 'hero',
            'name' => 'Split Hero with Mockup',
            'description' => 'Two-column hero with copy on left and a dashboard-style product mockup on right.',
            'tags' => ['hero', 'split', 'mockup', 'dashboard'],
            'component_source' => <<<'JSX'
export default function {{ComponentName}}(props) {
    return (
        <section className="relative overflow-hidden bg-ds-background py-20 md:py-28">
            <div className="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                <div>
                    {subheading && <Badge variant={badgeVariant} className="mb-4">{subheading}</Badge>}
                    <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold tracking-tight text-ds-foreground mb-6">
                        {heading}
                    </h1>
                    <p className="text-lg text-ds-muted-foreground mb-8 max-w-lg">
                        {body}
                    </p>
                    <div className="flex flex-wrap items-center gap-4">
                        {primaryCtaText && (
                            <a href={primaryCtaHref} className="inline-flex items-center justify-center rounded-xl bg-ds-primary px-6 py-3 text-base font-semibold text-white shadow-lg shadow-ds-primary/20 hover:shadow-xl transition">
                                {primaryCtaText}
                            </a>
                        )}
                        {secondaryCtaText && (
                            <a href={secondaryCtaHref} className="inline-flex items-center justify-center rounded-xl border border-ds-border px-6 py-3 text-base font-semibold text-ds-foreground hover:bg-ds-muted transition">
                                {secondaryCtaText}
                            </a>
                        )}
                    </div>
                </div>
                <div className="rounded-2xl border border-ds-border bg-ds-card p-4 shadow-2xl">
                    <div className="flex items-center gap-2 mb-4">
                        <div className="w-3 h-3 rounded-full bg-red-400" />
                        <div className="w-3 h-3 rounded-full bg-yellow-400" />
                        <div className="w-3 h-3 rounded-full bg-green-400" />
                    </div>
                    <div className="grid grid-cols-3 gap-3">
                        <div className="col-span-2 h-24 rounded-xl bg-ds-muted" />
                        <div className="h-24 rounded-xl bg-ds-primary/10" />
                        <div className="h-16 rounded-xl bg-ds-primary/10" />
                        <div className="col-span-2 h-16 rounded-xl bg-ds-muted" />
                    </div>
                </div>
            </div>
        </section>
    );
}
JSX,
            'fields' => [
                ['key' => 'heading', 'label' => 'Heading', 'type' => 'text', 'required' => true],
                ['key' => 'subheading', 'label' => 'Subheading', 'type' => 'text'],
                ['key' => 'body', 'label' => 'Body Text', 'type' => 'textarea'],
                ['key' => 'primaryCtaText', 'label' => 'Primary CTA Text', 'type' => 'text'],
                ['key' => 'primaryCtaHref', 'label' => 'Primary CTA URL', 'type' => 'text'],
                ['key' => 'secondaryCtaText', 'label' => 'Secondary CTA Text', 'type' => 'text'],
                ['key' => 'secondaryCtaHref', 'label' => 'Secondary CTA URL', 'type' => 'text'],
            ],
            'defaults' => [
                'heading' => 'Manage Everything in One Place',
                'subheading' => 'All-in-one Dashboard',
                'body' => 'Track performance, automate tasks, and grow your business with a single powerful dashboard.',
                'primaryCtaText' => 'Get Started',
                'primaryCtaHref' => '#',
                'secondaryCtaText' => 'Learn More',
                'secondaryCtaHref' => '#',
            ],
            'example_props' => [
                'heading' => 'Scale Your Cold Outreach',
                'subheading' => 'Deliverability First',
                'body' => 'Automated warmup, rotation servers, and inbox management built for growth teams.',
                'primaryCtaText' => 'Start Free',
                'primaryCtaHref' => '#',
                'secondaryCtaText' => 'Watch Demo',
                'secondaryCtaHref' => '#',
            ],
            'is_active' => true,
            'sort_order' => 10,
        ];
    }

    private function aboutSplitTemplate(): array
    {
        return [
            'template_id' => 'about_split_01',
            'category' => 'about',
            'name' => 'Split Image About',
            'description' => 'About section with text left and image right plus optional stats.',
            'tags' => ['about', 'split', 'image', 'stats'],
            'component_source' => <<<'JSX'
export default function {{ComponentName}}(props) {
    return (
        <section className="bg-ds-background py-20 md:py-28">
            <div className="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                <div>
                    {subheading && <Badge variant={badgeVariant} className="mb-4">{subheading}</Badge>}
                    <h2 className="text-3xl md:text-4xl font-bold tracking-tight text-ds-foreground mb-6">
                        {heading}
                    </h2>
                    <p className="text-lg text-ds-muted-foreground mb-8">
                        {body}
                    </p>
                    {stats?.length > 0 && (
                        <div className="flex flex-wrap gap-8">
                            {stats.map((s, i) => (
                                <div key={i}>
                                    <p className="text-3xl font-bold text-ds-primary">{s.value}</p>
                                    <p className="text-sm text-ds-muted-foreground">{s.label}</p>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
                {image && <img src={image} alt={imageAlt} className="rounded-2xl shadow-xl w-full" />}
            </div>
        </section>
    );
}
JSX,
            'fields' => [
                ['key' => 'heading', 'label' => 'Heading', 'type' => 'text', 'required' => true],
                ['key' => 'subheading', 'label' => 'Subheading', 'type' => 'text'],
                ['key' => 'body', 'label' => 'Body Text', 'type' => 'textarea'],
                ['key' => 'image', 'label' => 'Image URL', 'type' => 'text'],
                ['key' => 'imageAlt', 'label' => 'Image Alt', 'type' => 'text'],
                ['key' => 'stats', 'label' => 'Stats', 'type' => 'array', 'itemFields' => [
                    ['key' => 'label', 'label' => 'Label', 'type' => 'text'],
                    ['key' => 'value', 'label' => 'Value', 'type' => 'text'],
                ]],
            ],
            'defaults' => [
                'heading' => 'About Us',
                'subheading' => 'Our Story',
                'body' => 'We are on a mission to build products that make a difference. Our team combines passion, expertise, and innovation.',
                'image' => 'https://picsum.photos/600/400?text=About',
                'imageAlt' => 'About our company',
                'stats' => [
                    ['label' => 'Years', 'value' => '10+'],
                    ['label' => 'Clients', 'value' => '500+'],
                ],
            ],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    private function featuresBentoTemplate(): array
    {
        return [
            'template_id' => 'features_bento_01',
            'category' => 'features',
            'name' => 'Bento Features Grid',
            'description' => 'Asymmetric bento grid with highlighted featured card.',
            'tags' => ['features', 'bento', 'grid'],
            'component_source' => <<<'JSX'
export default function {{ComponentName}}(props) {
    return (
        <section className="bg-ds-background py-20 md:py-28">
            <div className="max-w-7xl mx-auto px-6">
                <div className="text-center mb-16">
                    {subheading && <Badge variant={badgeVariant} className="mb-4">{subheading}</Badge>}
                    <h2 className="text-3xl md:text-4xl font-bold tracking-tight text-ds-foreground mb-4">{heading}</h2>
                    <p className="text-lg text-ds-muted-foreground max-w-2xl mx-auto">{body}</p>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 auto-rows-fr">
                    {items.map((item, i) => (
                        <div key={i} className={`rounded-2xl border border-ds-border bg-ds-card p-6 hover:-translate-y-1 hover:shadow-xl transition-all duration-300 ${i === 0 ? 'md:col-span-2' : ''}`}>
                            <div className="flex items-start justify-between mb-4">
                                <h3 className="text-xl font-semibold text-ds-foreground">{item.title}</h3>
                                <span className="text-ds-primary opacity-40 text-xl">{item.icon}</span>
                            </div>
                            <p className="text-ds-muted-foreground">{item.description}</p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
JSX,
            'fields' => [
                ['key' => 'heading', 'label' => 'Heading', 'type' => 'text', 'required' => true],
                ['key' => 'subheading', 'label' => 'Subheading', 'type' => 'text'],
                ['key' => 'body', 'label' => 'Body Text', 'type' => 'textarea'],
                ['key' => 'items', 'label' => 'Feature Items', 'type' => 'array', 'itemFields' => [
                    ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                    ['key' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                    ['key' => 'icon', 'label' => 'Icon Emoji or Character', 'type' => 'text'],
                ]],
            ],
            'defaults' => [
                'heading' => 'Powerful Features',
                'subheading' => 'Why Choose Us',
                'body' => 'Everything you need to launch, grow, and scale your business.',
                'items' => [
                    ['title' => 'AI Automation', 'description' => 'Let AI handle repetitive tasks so your team can focus on strategy.', 'icon' => '⚡'],
                    ['title' => 'Real-time Analytics', 'description' => 'Track every metric that matters from a single dashboard.', 'icon' => '📊'],
                    ['title' => 'Enterprise Security', 'description' => 'Bank-grade encryption and compliance built in.', 'icon' => '🛡️'],
                    ['title' => 'Global Scale', 'description' => 'Deploy anywhere in the world with one click.', 'icon' => '🌍'],
                ],
            ],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    private function testimonialsGridTemplate(): array
    {
        return [
            'template_id' => 'testimonials_grid_01',
            'category' => 'testimonials',
            'name' => 'Grid Testimonials',
            'description' => 'Grid of testimonial cards with star ratings.',
            'tags' => ['testimonials', 'grid', 'stars'],
            'component_source' => <<<'JSX'
export default function {{ComponentName}}(props) {
    return (
        <section className="bg-ds-background py-20 md:py-28">
            <div className="max-w-7xl mx-auto px-6">
                <div className="text-center mb-16">
                    {subheading && <Badge variant={badgeVariant} className="mb-4">{subheading}</Badge>}
                    <h2 className="text-3xl md:text-4xl font-bold tracking-tight text-ds-foreground mb-4">{heading}</h2>
                    <p className="text-lg text-ds-muted-foreground max-w-2xl mx-auto">{body}</p>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {items.map((t, i) => (
                        <div key={i} className="rounded-2xl border border-ds-border bg-ds-card p-6">
                            <div className="flex gap-1 mb-4 text-yellow-400">★★★★★</div>
                            <p className="text-ds-foreground mb-6">&ldquo;{t.quote}&rdquo;</p>
                            <div className="flex items-center gap-3">
                                {t.avatar && <img src={t.avatar} alt={t.name} className="h-10 w-10 rounded-full object-cover" />}
                                <div>
                                    <p className="font-semibold text-ds-foreground text-sm">{t.name}</p>
                                    <p className="text-xs text-ds-muted-foreground">{t.role}{t.company ? `, ${t.company}` : ''}</p>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
JSX,
            'fields' => [
                ['key' => 'heading', 'label' => 'Heading', 'type' => 'text', 'required' => true],
                ['key' => 'subheading', 'label' => 'Subheading', 'type' => 'text'],
                ['key' => 'body', 'label' => 'Body Text', 'type' => 'textarea'],
                ['key' => 'items', 'label' => 'Testimonials', 'type' => 'array', 'itemFields' => [
                    ['key' => 'quote', 'label' => 'Quote', 'type' => 'textarea'],
                    ['key' => 'name', 'label' => 'Name', 'type' => 'text'],
                    ['key' => 'role', 'label' => 'Role', 'type' => 'text'],
                    ['key' => 'company', 'label' => 'Company', 'type' => 'text'],
                    ['key' => 'avatar', 'label' => 'Avatar URL', 'type' => 'text'],
                ]],
            ],
            'defaults' => [
                'heading' => 'Loved by Teams',
                'subheading' => 'Testimonials',
                'body' => 'See what our customers say about working with us.',
                'items' => [
                    ['quote' => 'This platform transformed how we manage outreach.', 'name' => 'Sarah Chen', 'role' => 'Marketing Lead', 'company' => 'Acme Inc', 'avatar' => 'https://picsum.photos/100/100?random=1'],
                    ['quote' => 'Setup was instant and the AI suggestions are spot on.', 'name' => 'James Wilson', 'role' => 'Founder', 'company' => 'StartUp', 'avatar' => 'https://picsum.photos/100/100?random=2'],
                    ['quote' => 'We cut manual work by 80% in the first week.', 'name' => 'Aisha Rahman', 'role' => 'Sales Ops', 'company' => 'Scale Co', 'avatar' => 'https://picsum.photos/100/100?random=3'],
                ],
            ],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    private function pricingThreeTierTemplate(): array
    {
        return [
            'template_id' => 'pricing_three_tier_01',
            'category' => 'pricing',
            'name' => 'Three Tier Pricing',
            'description' => 'Three pricing cards with popular middle tier highlighted.',
            'tags' => ['pricing', 'three-tier', 'toggle'],
            'component_source' => <<<'JSX'
import { useState } from 'react';

export default function {{ComponentName}}(props) {
    const [annual, setAnnual] = useState(false);

    return (
        <section className="bg-ds-background py-20 md:py-28">
            <div className="max-w-7xl mx-auto px-6">
                <div className="text-center mb-12">
                    {subheading && <Badge variant={badgeVariant} className="mb-4">{subheading}</Badge>}
                    <h2 className="text-3xl md:text-4xl font-bold tracking-tight text-ds-foreground mb-4">{heading}</h2>
                    <p className="text-lg text-ds-muted-foreground max-w-2xl mx-auto mb-8">{body}</p>
                    <div className="flex justify-center gap-2">
                        <button onClick={() => setAnnual(false)} className={`px-4 py-2 rounded-lg text-sm font-semibold ${!annual ? 'bg-ds-primary text-white' : 'border border-ds-border text-ds-foreground'}`}>Monthly</button>
                        <button onClick={() => setAnnual(true)} className={`px-4 py-2 rounded-lg text-sm font-semibold ${annual ? 'bg-ds-primary text-white' : 'border border-ds-border text-ds-foreground'}`}>Yearly</button>
                    </div>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto">
                    {plans.map((p, i) => (
                        <div key={i} className={`rounded-2xl border p-8 ${p.highlighted ? 'border-ds-primary ring-2 ring-ds-primary scale-105 bg-ds-card' : 'border-ds-border bg-ds-card'}`}>
                            {p.highlighted && <Badge variant={badgeVariant} className="mb-4">Popular</Badge>}
                            <h3 className="text-xl font-semibold text-ds-foreground mb-2">{p.name}</h3>
                            <p className="text-sm text-ds-muted-foreground mb-4">{p.description}</p>
                            <p className="text-4xl font-bold text-ds-foreground mb-6">${annual ? p.priceYearly : p.priceMonthly}<span className="text-base font-normal text-ds-muted-foreground">/{annual ? 'yr' : 'mo'}</span></p>
                            <ul className="space-y-3 mb-8">
                                {p.features.map((f, j) => (
                                    <li key={j} className="flex items-center gap-2 text-sm text-ds-foreground">
                                        <span className="text-ds-primary">✓</span> {f}
                                    </li>
                                ))}
                            </ul>
                            {p.ctaText && (
                                <a href={p.ctaHref} className={`block text-center rounded-xl px-6 py-3 font-semibold ${p.highlighted ? 'bg-ds-primary text-white' : 'border border-ds-border text-ds-foreground hover:bg-ds-muted'}`}>
                                    {p.ctaText}
                                </a>
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
JSX,
            'fields' => [
                ['key' => 'heading', 'label' => 'Heading', 'type' => 'text', 'required' => true],
                ['key' => 'subheading', 'label' => 'Subheading', 'type' => 'text'],
                ['key' => 'body', 'label' => 'Body Text', 'type' => 'textarea'],
                ['key' => 'plans', 'label' => 'Pricing Plans', 'type' => 'array', 'itemFields' => [
                    ['key' => 'name', 'label' => 'Name', 'type' => 'text'],
                    ['key' => 'description', 'label' => 'Description', 'type' => 'text'],
                    ['key' => 'priceMonthly', 'label' => 'Monthly Price', 'type' => 'text'],
                    ['key' => 'priceYearly', 'label' => 'Yearly Price', 'type' => 'text'],
                    ['key' => 'features', 'label' => 'Features (one per line)', 'type' => 'textarea'],
                    ['key' => 'ctaText', 'label' => 'CTA Text', 'type' => 'text'],
                    ['key' => 'ctaHref', 'label' => 'CTA URL', 'type' => 'text'],
                    ['key' => 'highlighted', 'label' => 'Highlighted', 'type' => 'boolean'],
                ]],
            ],
            'defaults' => [
                'heading' => 'Simple, Transparent Pricing',
                'subheading' => 'Pricing',
                'body' => 'Choose the plan that fits your team.',
                'plans' => [
                    ['name' => 'Starter', 'description' => 'For individuals.', 'priceMonthly' => '19', 'priceYearly' => '190', 'features' => ['1 project', 'Basic analytics', 'Email support'], 'ctaText' => 'Get Started', 'ctaHref' => '#', 'highlighted' => false],
                    ['name' => 'Pro', 'description' => 'For growing teams.', 'priceMonthly' => '49', 'priceYearly' => '490', 'features' => ['10 projects', 'Advanced analytics', 'Priority support', 'AI suggestions'], 'ctaText' => 'Start Free Trial', 'ctaHref' => '#', 'highlighted' => true],
                    ['name' => 'Enterprise', 'description' => 'For large orgs.', 'priceMonthly' => '99', 'priceYearly' => '990', 'features' => ['Unlimited projects', 'Custom integrations', 'Dedicated support'], 'ctaText' => 'Contact Sales', 'ctaHref' => '#', 'highlighted' => false],
                ],
            ],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    private function ctaBandTemplate(): array
    {
        return [
            'template_id' => 'cta_band_01',
            'category' => 'cta',
            'name' => 'Full-width CTA Band',
            'description' => 'Bold full-width colored band with headline and single CTA.',
            'tags' => ['cta', 'band', 'full-width'],
            'component_source' => <<<'JSX'
export default function {{ComponentName}}(props) {
    return (
        <section className="bg-ds-primary py-16 md:py-20">
            <div className="max-w-7xl mx-auto px-6 text-center text-white">
                <h2 className="text-3xl md:text-4xl font-bold tracking-tight mb-4">{heading}</h2>
                <p className="text-lg text-white/80 mb-8 max-w-2xl mx-auto">{subheading}</p>
                {ctaText && (
                    <a href={ctaHref} className="inline-flex items-center justify-center rounded-xl bg-white px-8 py-3 text-base font-semibold text-ds-primary shadow-lg hover:bg-gray-100 transition">
                        {ctaText}
                    </a>
                )}
            </div>
        </section>
    );
}
JSX,
            'fields' => [
                ['key' => 'heading', 'label' => 'Heading', 'type' => 'text', 'required' => true],
                ['key' => 'subheading', 'label' => 'Subheading', 'type' => 'textarea'],
                ['key' => 'ctaText', 'label' => 'CTA Text', 'type' => 'text'],
                ['key' => 'ctaHref', 'label' => 'CTA URL', 'type' => 'text'],
            ],
            'defaults' => [
                'heading' => 'Ready to Get Started?',
                'subheading' => 'Join thousands of happy customers today.',
                'ctaText' => 'Start Free Trial',
                'ctaHref' => '#',
            ],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    private function faqAccordionTemplate(): array
    {
        return [
            'template_id' => 'faq_accordion_01',
            'category' => 'faq',
            'name' => 'Simple FAQ Accordion',
            'description' => 'Centered FAQ section with collapsible items.',
            'tags' => ['faq', 'accordion'],
            'component_source' => <<<'JSX'
import { useState } from 'react';
import { Badge } from '../ui/badge.jsx';

export default function {{ComponentName}}(props) {
    const [open, setOpen] = useState(0);

    return (
        <section className="bg-ds-background py-20 md:py-28">
            <div className="max-w-3xl mx-auto px-6">
                <div className="text-center mb-12">
                    {subheading && <Badge variant={badgeVariant} className="mb-4">{subheading}</Badge>}
                    <h2 className="text-3xl md:text-4xl font-bold tracking-tight text-ds-foreground mb-4">{heading}</h2>
                </div>
                <div className="space-y-4">
                    {items.map((item, i) => (
                        <div key={i} className="rounded-xl border border-ds-border bg-ds-card overflow-hidden">
                            <button onClick={() => setOpen(open === i ? -1 : i)} className="w-full flex items-center justify-between px-6 py-4 text-left">
                                <span className="font-semibold text-ds-foreground">{item.question}</span>
                                <span className="text-ds-muted-foreground">{open === i ? '−' : '+'}</span>
                            </button>
                            {open === i && (
                                <div className="px-6 pb-4 text-ds-muted-foreground">
                                    {item.answer}
                                </div>
                            )}
                        </div>
                    ))}
                </div>
                {contactText && (
                    <p className="text-center mt-8 text-ds-muted-foreground">
                        {contactText}{' '}
                        {contactCtaText && <a href={contactCtaHref} className="text-ds-primary font-semibold hover:underline">{contactCtaText}</a>}
                    </p>
                )}
            </div>
        </section>
    );
}
JSX,
            'fields' => [
                ['key' => 'heading', 'label' => 'Heading', 'type' => 'text', 'required' => true],
                ['key' => 'subheading', 'label' => 'Subheading', 'type' => 'text'],
                ['key' => 'items', 'label' => 'FAQ Items', 'type' => 'array', 'itemFields' => [
                    ['key' => 'question', 'label' => 'Question', 'type' => 'text'],
                    ['key' => 'answer', 'label' => 'Answer', 'type' => 'textarea'],
                ]],
                ['key' => 'contactText', 'label' => 'Contact Text', 'type' => 'text'],
                ['key' => 'contactCtaText', 'label' => 'Contact CTA Text', 'type' => 'text'],
                ['key' => 'contactCtaHref', 'label' => 'Contact CTA URL', 'type' => 'text'],
            ],
            'defaults' => [
                'heading' => 'Frequently Asked Questions',
                'subheading' => 'FAQ',
                'items' => [
                    ['question' => 'How does the free trial work?', 'answer' => 'Start with full access for 14 days, no credit card required.'],
                    ['question' => 'Can I cancel anytime?', 'answer' => 'Yes, you can cancel or change your plan at any time.'],
                    ['question' => 'Do you offer support?', 'answer' => 'We offer email support on all plans and priority support on Pro and Enterprise.'],
                ],
                'contactText' => 'Still have questions?',
                'contactCtaText' => 'Contact us',
                'contactCtaHref' => '#',
            ],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    private function footerSimpleTemplate(): array
    {
        return [
            'template_id' => 'footer_simple_01',
            'category' => 'footer',
            'name' => 'Simple Dark Footer',
            'description' => 'Minimal dark footer with brand, links and copyright.',
            'tags' => ['footer', 'dark', 'simple'],
            'component_source' => <<<'JSX'
export default function {{ComponentName}}(props) {
    return (
        <footer className="bg-slate-900 text-slate-300 py-12">
            <div className="max-w-7xl mx-auto px-6">
                <div className="flex flex-col md:flex-row items-center justify-between gap-6">
                    <div>
                        <span data-app-logo className="h-8 w-8 rounded-lg object-contain hidden" />
                        <span data-app-title className="text-xl font-bold text-white">{brandName}</span>
                        <p className="text-sm text-slate-400 mt-1">{tagline}</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-6 text-sm">
                        {links.map((l, i) => (
                            <a key={i} href={l.href} className="hover:text-white transition">{l.label}</a>
                        ))}
                    </div>
                </div>
                <div className="border-t border-slate-800 mt-8 pt-8 text-center text-sm text-slate-500">
                    {copyright}
                </div>
            </div>
        </footer>
    );
}
JSX,
            'fields' => [
                ['key' => 'brandName', 'label' => 'Brand Name', 'type' => 'text', 'required' => true],
                ['key' => 'tagline', 'label' => 'Tagline', 'type' => 'text'],
                ['key' => 'links', 'label' => 'Footer Links', 'type' => 'array', 'itemFields' => [
                    ['key' => 'label', 'label' => 'Label', 'type' => 'text'],
                    ['key' => 'href', 'label' => 'URL', 'type' => 'text'],
                ]],
                ['key' => 'copyright', 'label' => 'Copyright', 'type' => 'text'],
            ],
            'defaults' => [
                'brandName' => 'Your Brand',
                'tagline' => 'Built for growth.',
                'links' => [
                    ['label' => 'Home', 'href' => '#'],
                    ['label' => 'Features', 'href' => '#'],
                    ['label' => 'Pricing', 'href' => '#'],
                    ['label' => 'Contact', 'href' => '#'],
                ],
                'copyright' => '© 2026 Your Brand. All rights reserved.',
            ],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
