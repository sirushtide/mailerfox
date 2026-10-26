<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TemplateGalleryController extends Controller
{
    private const BROWSER_UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

    private const DEFAULT_PRODUCT_ID = 7;

    private const DEFAULT_PRODUCT_SECRET = 'oKxmiXDrSzVvlV7xj46t8IAPaomXIA1x';

    private function browserHeaders(): array
    {
        return [
            'User-Agent' => self::BROWSER_UA,
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Referer' => rtrim((string) config('app.url'), '/') . '/',
            'Origin' => rtrim((string) config('app.url'), '/'),
        ];
    }

    private function baseUrl(): string
    {
        $configured = Setting::get('external_templates_api_base_url', '');
        if (is_string($configured) && trim($configured) !== '') {
            return rtrim(trim($configured), '/');
        }

        return rtrim((string) config('services.update_server.base_url', 'https://api.getmailpurse.com'), '/');
    }

    private function apiRoot(): string
    {
        $base = $this->baseUrl();
        if (str_contains($base, '/wp-json/')) {
            return $base;
        }
        return $base . '/wp-json/v1';
    }

    private function legacyApiRoot(): string
    {
        $base = $this->baseUrl();
        if (str_contains($base, '/wp-json/')) {
            return $base;
        }
        return $base . '/wp-json/lss-bridge/v1';
    }

    private function shouldFallbackToLegacy(?int $status, mixed $json): bool
    {
        if ($status !== 404) {
            return false;
        }
        if (!is_array($json)) {
            return false;
        }
        return (string) ($json['code'] ?? '') === 'rest_no_route';
    }

    private function productId(): int
    {
        $configured = Setting::get('external_templates_api_product_id', '');
        if (is_numeric($configured) && (int) $configured > 0) {
            return (int) $configured;
        }
        return self::DEFAULT_PRODUCT_ID;
    }

    private function productSecret(): string
    {
        $configured = Setting::get('external_templates_api_product_secret', '');
        if (is_string($configured) && trim($configured) !== '') {
            return trim($configured);
        }
        return self::DEFAULT_PRODUCT_SECRET;
    }

    private function licenseKey(): string
    {
        $key = Setting::get('external_templates_license_key', '');
        if (!is_string($key) || trim($key) === '') {
            $key = Setting::get('update_license_key', '');
        }
        return is_string($key) ? trim($key) : '';
    }

    private function domain(): string
    {
        $appUrl = (string) config('app.url');
        $host = parse_url($appUrl, PHP_URL_HOST);
        if (is_string($host) && trim($host) !== '') {
            return $host;
        }
        return request()->getHost();
    }

    private function requestHeaders(): array
    {
        $headers = $this->browserHeaders();
        $licenseKey = $this->licenseKey();
        if ($licenseKey !== '') {
            $headers['Authorization'] = $licenseKey;
            $headers['X-License-Key'] = $licenseKey;
        }
        $headers['X-Product-Id'] = (string) $this->productId();
        $headers['X-Product-Secret'] = $this->productSecret();

        return $headers;
    }

    public function list(Request $request, string $resourceType): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 100);
        if ($perPage <= 0 || $perPage > 200) {
            $perPage = 100;
        }
        $page = max(1, (int) $request->query('page', 1));

        $query = [
            'product_id' => $this->productId(),
            'product_secret' => $this->productSecret(),
            'per_page' => $perPage,
            'page' => $page,
            'license_key' => $this->licenseKey(),
            'domain' => $this->domain(),
        ];

        foreach (['search', 'category', 'plan', 'builder', 'id'] as $opt) {
            $val = $request->query($opt);
            if ($val !== null && $val !== '') {
                $query[$opt] = $val;
            }
        }

        $url = $this->apiRoot() . '/templates/' . rawurlencode($resourceType);

        try {
            $response = Http::timeout(20)
                ->withHeaders($this->requestHeaders())
                ->withOptions(['decode_content' => true])
                ->get($url, $query);

            if ($this->shouldFallbackToLegacy($response->status(), $response->json())) {
                $legacyUrl = $this->legacyApiRoot() . '/templates/' . rawurlencode($resourceType);
                if ($legacyUrl !== $url) {
                    $response = Http::timeout(20)
                        ->withHeaders($this->requestHeaders())
                        ->withOptions(['decode_content' => true])
                        ->get($legacyUrl, $query);
                }
            }
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'upstream_unreachable',
                'message' => $e->getMessage(),
            ], 502);
        }

        if (!$response->successful()) {
            return response()->json([
                'error' => 'upstream_error',
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ], $response->status() >= 400 && $response->status() < 600 ? $response->status() : 502);
        }

        return response()->json($response->json() ?? []);
    }

    public function show(Request $request, int $templateId): JsonResponse
    {
        $payload = [
            'product_id' => $this->productId(),
            'product_secret' => $this->productSecret(),
            'license_key' => $this->licenseKey(),
            'domain' => $this->domain(),
        ];

        $url = $this->apiRoot() . '/templates/' . $templateId;

        try {
            $response = Http::timeout(30)
                ->withHeaders($this->requestHeaders())
                ->withOptions(['decode_content' => true])
                ->asForm()
                ->post($url, $payload);

            if ($this->shouldFallbackToLegacy($response->status(), $response->json())) {
                $legacyUrl = $this->legacyApiRoot() . '/templates/' . $templateId;
                if ($legacyUrl !== $url) {
                    $response = Http::timeout(30)
                        ->withHeaders($this->requestHeaders())
                        ->withOptions(['decode_content' => true])
                        ->asForm()
                        ->post($legacyUrl, $payload);
                }
            }
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'upstream_unreachable',
                'message' => $e->getMessage(),
            ], 502);
        }

        if (!$response->successful()) {
            return response()->json([
                'error' => 'upstream_error',
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ], $response->status() >= 400 && $response->status() < 600 ? $response->status() : 502);
        }

        return response()->json($response->json() ?? []);
    }
}
