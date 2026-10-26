<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SuperScrapeSettingsController extends Controller
{
    public function edit(): View
    {
        $serpApiKey     = Setting::get('superscrape_serpapi_key', '');
        $serperKey      = Setting::get('superscrape_serper_key', '');
        $monthlyCredits = (int) Setting::get('superscrape_monthly_credits', 500);
        $apolloKey      = Setting::get('superscrape_apollo_key', '');
        $hunterKey      = Setting::get('superscrape_hunter_key', '');
        $rocketreachKey = Setting::get('superscrape_rocketreach_key', '');

        return view('admin.addons.super-scrape-settings', compact(
            'serpApiKey', 'serperKey', 'monthlyCredits', 'apolloKey', 'hunterKey', 'rocketreachKey'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'superscrape_serpapi_key'       => ['nullable', 'string', 'max:500'],
            'superscrape_serper_key'        => ['nullable', 'string', 'max:500'],
            'superscrape_monthly_credits'   => ['required', 'integer', 'min:1', 'max:100000'],
            'superscrape_apollo_key'        => ['nullable', 'string', 'max:500'],
            'superscrape_hunter_key'        => ['nullable', 'string', 'max:500'],
            'superscrape_rocketreach_key'   => ['nullable', 'string', 'max:500'],
        ]);

        Setting::set('superscrape_serpapi_key', trim((string) ($validated['superscrape_serpapi_key'] ?? '')), 'addon', 'string');
        Setting::set('superscrape_serper_key', trim((string) ($validated['superscrape_serper_key'] ?? '')), 'addon', 'string');
        Setting::set('superscrape_monthly_credits', (string) (int) $validated['superscrape_monthly_credits'], 'addon', 'string');
        Setting::set('superscrape_apollo_key', trim((string) ($validated['superscrape_apollo_key'] ?? '')), 'addon', 'string');
        Setting::set('superscrape_hunter_key', trim((string) ($validated['superscrape_hunter_key'] ?? '')), 'addon', 'string');
        Setting::set('superscrape_rocketreach_key', trim((string) ($validated['superscrape_rocketreach_key'] ?? '')), 'addon', 'string');

        return redirect()->route('admin.super-scrape.settings')
            ->with('success', __('SuperScrape settings saved.'));
    }
}
