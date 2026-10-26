<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * Wildcard '*' lets any client spoof X-Forwarded-For and defeat IP-based
     * throttling, so configure a CIDR list via TRUSTED_PROXIES in production
     * (e.g. your load balancer). '*' remains the default only for local/dev.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    public function __construct()
    {
        $configured = env('TRUSTED_PROXIES');

        if (is_string($configured) && trim($configured) !== '') {
            $this->proxies = array_map('trim', explode(',', $configured));
        } elseif (app()->environment(['local', 'development', 'testing'])) {
            $this->proxies = '*';
        }
    }
}

