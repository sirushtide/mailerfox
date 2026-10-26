<?php

if (!function_exists('wsRoute')) {
    /**
     * Generate a workspace-aware URL for a named route.
     *
     * When a workspace is active, customer.* routes are rewritten to
     * /customer/w/{slug}/... so the URL reflects the workspace context.
     *
     * @param  string  $name       Named route (e.g. 'customer.dashboard')
     * @param  mixed   $parameters Route parameters
     * @param  bool    $absolute   Whether to generate an absolute URL
     * @return string
     */
    function wsRoute(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        $url = route($name, $parameters, $absolute);

        // Only rewrite customer.* routes
        if (!str_starts_with($name, 'customer.')) {
            return $url;
        }

        $workspace = view()->shared('activeWorkspace');

        if (!$workspace || !$workspace->slug) {
            return $url;
        }

        // Insert /w/{slug} after /customer in the URL
        $slug = $workspace->slug;
        $prefix = $absolute ? url('customer') : '/customer';

        // Idempotent: if the URL already points at this workspace, leave it alone
        if (str_contains($url, $prefix . '/w/' . $slug)) {
            return $url;
        }

        if (str_contains($url, $prefix)) {
            // Strip any existing (stale) workspace segment first, then insert the
            // current one — prevents /customer/w/{a}/w/{b} when called twice.
            $url = preg_replace(
                '#' . preg_quote($prefix, '#') . '/w/[^/]+#',
                $prefix,
                $url,
                1
            );

            $url = preg_replace(
                '#(' . preg_quote($prefix, '#') . ')(/|$)#',
                '$1/w/' . $slug . '$2',
                $url,
                1
            );
        }

        return $url;
    }
}
