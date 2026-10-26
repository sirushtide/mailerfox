<?php

namespace App\Services\Supabase;

use App\Models\AdminSupabaseConfig;
use App\Models\SupabaseIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseService
{
    public function __construct(protected SupabaseIntegration|AdminSupabaseConfig $integration)
    {
    }

    private function credentials(): SupabaseIntegration|AdminSupabaseConfig
    {
        if (
            $this->integration instanceof SupabaseIntegration
            && $this->integration->isAdminManaged()
            && $this->integration->adminSupabaseConfig
        ) {
            return $this->integration->adminSupabaseConfig;
        }

        return $this->integration;
    }

    private function schemaName(): string
    {
        if ($this->integration instanceof SupabaseIntegration) {
            return 'project_' . $this->integration->getKey();
        }

        return 'admin_' . $this->integration->getKey();
    }

    public function testConnection(): array
    {
        try {
            $source = $this->credentials();
            $secretKey = $this->secretKey($source);
            $hasSecretKey = ! empty($secretKey);

            // Try the admin endpoint when a secret key is provided.
            if ($hasSecretKey) {
                $response = Http::withHeaders($this->headers(true))
                    ->timeout(15)
                    ->get($this->url('/auth/v1/admin/users'));

                if ($response->successful()) {
                    return ['success' => true, 'error' => null];
                }
            }

            // Fall back to a public auth endpoint using the publishable key.
            $response = Http::withHeaders($this->headers(false))
                ->timeout(15)
                ->get($this->url('/auth/v1/settings'));

            if ($response->successful()) {
                return ['success' => true, 'error' => null];
            }

            return ['success' => false, 'error' => $response->json('message') ?? $response->body() ?? 'Unknown error'];
        } catch (\Throwable $e) {
            Log::error('Supabase connection test failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getUsers(?string $appId = null): array
    {
        $source = $this->credentials();

        if (empty($this->secretKey($source))) {
            return [
                'success' => false,
                'error' => 'A Supabase secret key is required to list users. Add it in Settings > Database.',
            ];
        }

        if ($appId !== null) {
            return $this->getSchemaUsers($appId);
        }

        return $this->getJson('/auth/v1/admin/users', true);
    }

    private function getSchemaUsers(string $appId): array
    {
        $schema = $this->schemaName();

        try {
            $response = Http::withHeaders($this->headers(true))
                ->timeout(15)
                ->get($this->url('/rest/v1/users'), [
                    'select' => 'id,email,created_at',
                    'schema' => $schema,
                    'order' => 'created_at.desc',
                ]);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json() ?? []];
            }

            if ($response->status() === 404) {
                return [
                    'success' => false,
                    'error' => 'Users table not found in schema ' . $schema . '. Run the Supabase setup to create it.',
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message') ?? $response->body() ?? 'Request failed',
            ];
        } catch (\Throwable $e) {
            Log::error('Supabase schema users query failed', ['schema' => $schema, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function loginUser(string $appId, string $email, string $password): array
    {
        $source = $this->credentials();

        if (empty($this->secretKey($source))) {
            return [
                'success' => false,
                'error' => 'A Supabase secret key is required.',
            ];
        }

        $schema = $this->schemaName();

        try {
            $response = Http::withHeaders($this->headers(true))
                ->timeout(15)
                ->post($this->url('/rest/v1/rpc/validate_user_credentials'), [
                    'p_schema' => $schema,
                    'p_email' => $email,
                    'p_password' => $password,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $user = $data[0] ?? null;

                if ($user && isset($user['email'])) {
                    return ['success' => true, 'data' => ['email' => $user['email']]];
                }

                return ['success' => false, 'error' => 'Invalid email or password.'];
            }

            return [
                'success' => false,
                'error' => $response->json('message') ?? $response->body() ?? 'Request failed',
            ];
        } catch (\Throwable $e) {
            Log::error('Supabase custom auth login failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function signupUser(string $appId, string $email, string $password): array
    {
        $source = $this->credentials();

        if (empty($this->secretKey($source))) {
            return [
                'success' => false,
                'error' => 'A Supabase secret key is required.',
            ];
        }

        $schema = $this->schemaName();

        try {
            $response = Http::withHeaders($this->headers(true))
                ->timeout(15)
                ->post($this->url('/rest/v1/rpc/register_user'), [
                    'p_schema' => $schema,
                    'p_email' => $email,
                    'p_password' => $password,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $user = $data[0] ?? null;

                if ($user && isset($user['email'])) {
                    return ['success' => true, 'data' => ['email' => $user['email']]];
                }

                return ['success' => false, 'error' => 'Registration failed.'];
            }

            return [
                'success' => false,
                'error' => $response->json('message') ?? $response->body() ?? 'Request failed',
            ];
        } catch (\Throwable $e) {
            Log::error('Supabase custom auth signup failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function createAppSchema(): array
    {
        $accessToken = $this->accessToken();
        $projectRef = $this->projectRef();

        if (empty($accessToken) || empty($projectRef)) {
            return [
                'success' => false,
                'error' => 'A Supabase access token and project reference are required to create the app schema.',
            ];
        }

        $sql = <<<'SQL'
create or replace function public.validate_user_credentials(p_email text, p_password text)
returns table(id uuid, email text, created_at timestamptz)
language plpgsql
security definer
set search_path = public
as $$
begin
    return query
    select u.id, u.email, u.created_at
    from public.users u
    where u.email = p_email
    and u.password_hash = crypt(p_password, u.password_hash);
end;
$$;

create or replace function public.register_user(p_email text, p_password text)
returns table(id uuid, email text, created_at timestamptz)
language plpgsql
security definer
set search_path = public
as $$
declare
    v_hash text;
    v_id uuid;
begin
    v_hash := crypt(p_password, gen_salt('bf'));
    insert into public.users (email, password_hash)
    values (p_email, v_hash)
    on conflict (email) do nothing
    returning id, email, created_at into v_id, v_id, v_id;

    if v_id is null then
        return query select null::uuid, null::text, null::timestamptz;
        return;
    end if;

    return query select u.id, u.email, u.created_at
    from public.users u
    where u.email = p_email;
end;
$$;

grant execute on function public.validate_user_credentials(text, text) to anon, authenticated, service_role;
grant execute on function public.register_user(text, text) to anon, authenticated, service_role;
SQL;

        try {
            $response = Http::withToken($accessToken)
                ->asJson()
                ->timeout(30)
                ->post("https://api.supabase.com/v1/projects/{$projectRef}/database/migrations", [
                    'name' => 'Create custom auth functions for app builder',
                    'query' => $sql,
                ]);

            if ($response->successful()) {
                return ['success' => true, 'error' => null];
            }

            return [
                'success' => false,
                'error' => $response->json('message') ?? $response->body() ?? 'Failed to create custom auth functions',
            ];
        } catch (\Throwable $e) {
            Log::error('Supabase custom auth schema creation failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function createAppUsersTable(): array
    {
        return $this->createAppSchema();
    }

    public function getBuckets(): array
    {
        $source = $this->credentials();

        if (empty($this->secretKey($source))) {
            return [
                'success' => false,
                'error' => 'A Supabase secret key is required to list storage buckets. Add it in Settings > Database.',
            ];
        }

        return $this->getJson('/storage/v1/bucket', true);
    }

    public function getTables(): array
    {
        $source = $this->credentials();

        // PostgREST OpenAPI spec is public and can be read with the publishable key.
        // Prefer the secret key when available, but fall back to the publishable key.
        $useServiceRole = ! empty($this->secretKey($source));

        try {
            $response = Http::withHeaders(array_merge($this->headers($useServiceRole), ['Accept' => 'application/openapi+json']))
                ->timeout(15)
                ->get($this->url('/rest/v1/'));

            if ($response->successful()) {
                $tables = $this->extractTablesFromOpenApi($response->json() ?? []);

                return ['success' => true, 'data' => $tables];
            }

            return [
                'success' => false,
                'error' => $response->json('message') ?? $response->body() ?? 'Request failed',
            ];
        } catch (\Throwable $e) {
            Log::error('Supabase table listing failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getTableData(string $table, ?int $limit = 100): array
    {
        $source = $this->credentials();

        if (empty($this->secretKey($source))) {
            return [
                'success' => false,
                'error' => 'A Supabase secret key is required to view table data. Add it in Settings > Database.',
            ];
        }

        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table)) {
            return ['success' => false, 'error' => 'Invalid table name.'];
        }

        try {
            $response = Http::withHeaders($this->headers(true))
                ->timeout(15)
                ->get($this->url('/rest/v1/' . rawurlencode($table)), [
                    'select' => '*',
                    'limit' => max(1, min((int) ($limit ?? 100), 1000)),
                ]);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json() ?? []];
            }

            return [
                'success' => false,
                'error' => $response->json('message') ?? $response->body() ?? 'Request failed',
            ];
        } catch (\Throwable $e) {
            Log::error('Supabase table data query failed', ['table' => $table, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function extractTablesFromOpenApi(array $payload): array
    {
        $paths = is_array($payload['paths'] ?? null) ? $payload['paths'] : [];
        $tables = [];

        foreach (array_keys($paths) as $path) {
            if (! is_string($path)) {
                continue;
            }

            $normalized = trim($path);
            if (! preg_match('#^/([A-Za-z0-9_]+)$#', $normalized, $matches)) {
                continue;
            }

            $tableName = $matches[1] ?? '';
            if ($tableName === '' || in_array($tableName, ['rpc', 'auth', 'storage', 'realtime', 'graphql', 'rest'], true)) {
                continue;
            }

            $tables[$tableName] = [
                'table_schema' => 'public',
                'table_name' => $tableName,
            ];
        }

        $tables = array_values($tables);
        usort($tables, function (array $a, array $b) {
            return strcmp($a['table_name'], $b['table_name']);
        });

        return $tables;
    }

    private function getJson(string $path, bool $serviceRole): array
    {
        try {
            $response = Http::withHeaders($this->headers($serviceRole))
                ->timeout(15)
                ->get($this->url($path));

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json() ?? []];
            }

            $body = $response->json() ?? [];
            $error = $body['msg'] ?? $body['message'] ?? $response->body() ?? 'Request failed';

            // Detect bad_jwt errors from sb_secret_... keys on admin endpoints.
            if ($serviceRole && ($body['error_code'] ?? null) === 'bad_jwt') {
                $error = 'The secret key was rejected by Supabase (bad_jwt). Go to Supabase Dashboard > Settings > API > JWT Settings and copy the full service_role JWT (starts with eyJ...).';
            }

            return ['success' => false, 'error' => $error];
        } catch (\Throwable $e) {
            Log::error('Supabase API request failed', ['path' => $path, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function url(string $path): string
    {
        $base = rtrim($this->credentials()->project_url, '/');
        return $base . $path;
    }

    private function headers(bool $serviceRole): array
    {
        $source = $this->credentials();
        $secretKey = $this->secretKey($source);
        $publishableKey = $this->publishableKey($source);

        // For admin endpoints, use the secret key for both apikey and Authorization.
        // For public endpoints, use the publishable key for both.
        $key = $serviceRole && ! empty($secretKey)
            ? $secretKey
            : $publishableKey;

        $headers = [
            'apikey' => $key,
            'Content-Type' => 'application/json',
        ];

        if (! empty($key)) {
            $headers['Authorization'] = 'Bearer ' . $key;
        }

        return $headers;
    }

    private function publishableKey(SupabaseIntegration|AdminSupabaseConfig $source): ?string
    {
        return $source->publishable_key ?? null;
    }

    private function secretKey(SupabaseIntegration|AdminSupabaseConfig $source): ?string
    {
        return $source->secret_key ?? null;
    }

    private function accessToken(): ?string
    {
        $source = $this->credentials();
        return $source->access_token ?? null;
    }

    private function projectRef(): ?string
    {
        $source = $this->credentials();
        return $source->project_id ?? null;
    }

    public static function isValidSecretKeyFormat(?string $token): bool
    {
        return ! empty($token);
    }
}
