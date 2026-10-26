<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSupabaseConfig;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SupabaseOAuthController extends Controller
{
    private const AUTHORIZE_URL = 'https://api.supabase.com/v1/oauth/authorize';
    private const TOKEN_URL = 'https://api.supabase.com/v1/oauth/token';
    private const PROJECTS_URL = 'https://api.supabase.com/v1/projects';
    private const PROJECT_API_KEYS_URL = 'https://api.supabase.com/v1/projects/%s/api-keys?reveal=true';

    private function getClientId(): ?string
    {
        return (string) Setting::get('supabase_oauth_client_id') ?: null;
    }

    private function getClientSecret(): ?string
    {
        return (string) Setting::get('supabase_oauth_client_secret') ?: null;
    }

    private function getRedirectUri(): string
    {
        return route('admin.supabase.oauth.callback');
    }

    private function isConfigured(): bool
    {
        return $this->getClientId() && $this->getClientSecret();
    }

    private function buildAuthorizeUrl(string $targetType, ?int $targetId, ?string $sessionId = null): string
    {
        $state = Str::random(40);
        $payload = [
            'state' => $state,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'session_id' => $sessionId,
        ];

        session(['supabase_oauth_state' => Crypt::encryptString(json_encode($payload))]);

        $params = http_build_query([
            'client_id' => $this->getClientId(),
            'redirect_uri' => $this->getRedirectUri(),
            'response_type' => 'code',
            'state' => $state,
            'scope' => 'all',
        ]);

        return self::AUTHORIZE_URL . '?' . $params;
    }

    public function startAuthorization(Request $request)
    {
        if (! $this->isConfigured()) {
            return redirect()->route('admin.dashboard')->with('error', 'Supabase OAuth is not configured.');
        }

        $validated = $request->validate([
            'target_type' => ['required', 'string', 'in:admin,page'],
            'target_id' => ['nullable', 'integer', 'min:1'],
            'session_id' => ['nullable', 'string', 'max:255'],
        ]);

        return redirect()->away($this->buildAuthorizeUrl(
            $validated['target_type'],
            $validated['target_id'] ?? null,
            $validated['session_id'] ?? null
        ));
    }

    public function redirect(Request $request)
    {
        if (! $this->isConfigured()) {
            return response()->json(['success' => false, 'error' => 'Supabase OAuth is not configured.'], 422);
        }

        $validated = $request->validate([
            'target_type' => ['required', 'string', 'in:admin,page'],
            'target_id' => ['nullable', 'integer', 'min:1'],
            'session_id' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json([
            'success' => true,
            'url' => $this->buildAuthorizeUrl(
                $validated['target_type'],
                $validated['target_id'] ?? null,
                $validated['session_id'] ?? null
            ),
        ]);
    }

    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('admin.dashboard')->with('error', 'Supabase authorization denied.');
        }

        if (! $this->isConfigured()) {
            return redirect()->route('admin.dashboard')->with('error', 'Supabase OAuth is not configured.');
        }

        $session = session('supabase_oauth_state');
        if (! $session) {
            return redirect()->route('admin.dashboard')->with('error', 'Invalid OAuth session.');
        }

        try {
            $payload = json_decode(Crypt::decryptString($session), true);
        } catch (\Throwable) {
            return redirect()->route('admin.dashboard')->with('error', 'Invalid OAuth session.');
        }

        if (($request->input('state') ?? '') !== ($payload['state'] ?? '')) {
            return redirect()->route('admin.dashboard')->with('error', 'Invalid OAuth state.');
        }

        session()->forget('supabase_oauth_state');

        $tokenResponse = Http::asForm()->post(self::TOKEN_URL, [
            'grant_type' => 'authorization_code',
            'code' => $request->input('code'),
            'client_id' => $this->getClientId(),
            'client_secret' => $this->getClientSecret(),
            'redirect_uri' => $this->getRedirectUri(),
        ]);

        $tokens = $tokenResponse->json();

        if (! empty($tokens['error']) || empty($tokens['access_token'])) {
            $error = $tokens['error_description'] ?? $tokens['error'] ?? 'Failed to exchange Supabase authorization code.';
            return redirect()->route('admin.dashboard')->with('error', $error);
        }

        $projectsResponse = Http::withToken($tokens['access_token'])
            ->get(self::PROJECTS_URL);

        $projects = $projectsResponse->json();
        $project = $projects[0] ?? null;

        if (! $project || empty($project['ref']) || empty($project['name'])) {
            return redirect()->route('admin.dashboard')->with('error', 'No Supabase projects found for this account.');
        }

        $projectUrl = rtrim($project['url'] ?? 'https://' . $project['ref'] . '.supabase.co', '/');
        $projectId = $project['ref'];
        $projectKeys = $this->fetchProjectApiKeys((string) $tokens['access_token'], $projectId);

        $tokenExpiresAt = ! empty($tokens['expires_in'])
            ? now()->addSeconds((int) $tokens['expires_in'])
            : null;

        $targetType = $payload['target_type'] ?? 'admin';
        $targetId = $payload['target_id'] ?? null;

        if ($targetType === 'admin') {
            $config = AdminSupabaseConfig::create([
                'name' => $project['name'],
                'project_url' => $projectUrl,
                'project_id' => $projectId,
                'publishable_key' => $projectKeys['publishable_key'],
                'secret_key' => $projectKeys['secret_key'],
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'token_expires_at' => $tokenExpiresAt,
                'is_default' => ! AdminSupabaseConfig::where('is_default', true)->exists(),
                'status' => 'connected',
            ]);

            // Keep only one default
            if ($config->is_default) {
                AdminSupabaseConfig::where('id', '!=', $config->id)->update(['is_default' => false]);
            }

            $this->bootstrapDefaultAppSchema((string) $tokens['access_token'], $projectId, 'admin_' . $config->id);

            return redirect()->route('admin.supabase-configs.index')
                ->with('success', 'Supabase project connected as admin-managed preset.');
        }

        if ($targetType === 'page' && $targetId) {
            $page = Page::find($targetId);
            if (! $page) {
                return redirect()->route('admin.dashboard')->with('error', 'Target page not found.');
            }

            $sessionId = is_string($payload['session_id'] ?? null) ? trim((string) $payload['session_id']) : '';

            $page->supabaseIntegration()->delete();
            $integration = $page->supabaseIntegration()->create([
                'customer_id' => $page->customer_id,
                'mode' => 'byos_oauth',
                'connection_type' => 'hosted',
                'project_url' => $projectUrl,
                'project_id' => $projectId,
                'publishable_key' => $projectKeys['publishable_key'],
                'secret_key' => $projectKeys['secret_key'],
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'token_expires_at' => $tokenExpiresAt,
                'status' => 'connected',
                'last_error' => null,
            ]);

            $this->bootstrapDefaultAppSchema((string) $tokens['access_token'], $projectId, 'project_' . $integration->getKey());

            if ($sessionId !== '') {
                return redirect()->route('admin.ai-projects.page-ai-builder.show', $sessionId)
                    ->with('success', 'Your Supabase project connected via one-click.');
            }

            return redirect()->route('admin.pages.builder', $page)
                ->with('success', 'Your Supabase project connected via one-click.');
        }

        return redirect()->route('admin.dashboard')->with('success', 'Supabase connected successfully.');
    }

    private function fetchProjectApiKeys(string $accessToken, string $projectRef): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->get(sprintf(self::PROJECT_API_KEYS_URL, $projectRef));

            if (! $response->successful()) {
                return [
                    'publishable_key' => null,
                    'secret_key' => null,
                ];
            }

            $keys = collect($response->json() ?? []);

            $publishable = $keys->first(function ($item) {
                return in_array($item['type'] ?? null, ['publishable', 'anon'], true);
            });

            $secret = $keys->first(function ($item) {
                return in_array($item['type'] ?? null, ['secret', 'service_role'], true);
            });

            return [
                'publishable_key' => $publishable['api_key'] ?? $publishable['key'] ?? null,
                'secret_key' => $secret['api_key'] ?? $secret['key'] ?? null,
            ];
        } catch (\Throwable) {
            return [
                'publishable_key' => null,
                'secret_key' => null,
            ];
        }
    }

    private function bootstrapDefaultAppSchema(string $accessToken, string $projectRef, string $schemaName): void
    {
        try {
            $sql = <<<'SQL'
create schema if not exists {SCHEMA_NAME};

create table if not exists {SCHEMA_NAME}.users (
  id uuid primary key default gen_random_uuid(),
  email text not null,
  password_hash text not null,
  created_at timestamptz default now(),
  unique(email)
);

alter table {SCHEMA_NAME}.users enable row level security;

create policy "Service role can manage users"
  on {SCHEMA_NAME}.users
  for all
  using (auth.role() = 'service_role');

create policy "Users can read own record"
  on {SCHEMA_NAME}.users
  for select
  using (email = current_setting('request.headers', true)::json->>'x-user-email');

create policy "Users can insert own record"
  on {SCHEMA_NAME}.users
  for insert
  with check (email = current_setting('request.headers', true)::json->>'x-user-email');

create policy "Users can update own record"
  on {SCHEMA_NAME}.users
  for update
  using (email = current_setting('request.headers', true)::json->>'x-user-email');

create policy "Users can delete own record"
  on {SCHEMA_NAME}.users
  for delete
  using (email = current_setting('request.headers', true)::json->>'x-user-email');

grant select, insert, update, delete on table {SCHEMA_NAME}.users to anon, authenticated, service_role;

create or replace function public.validate_user_credentials(p_schema text, p_email text, p_password text)
returns table(id uuid, email text, created_at timestamptz)
language plpgsql
security definer
set search_path = public
as $$
begin
    return query execute format(
        'select u.id, u.email, u.created_at from %I.users u where u.email = $1 and u.password_hash = crypt($2, u.password_hash)',
        p_schema
    ) using p_email, p_password;
end;
$$;

create or replace function public.register_user(p_schema text, p_email text, p_password text)
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
    execute format(
        'insert into %I.users (email, password_hash) values ($1, $2) on conflict (email) do nothing returning id, email, created_at',
        p_schema
    ) using p_email, v_hash into v_id, v_id, v_id;

    if v_id is null then
        return query select null::uuid, null::text, null::timestamptz;
        return;
    end if;

    return query execute format(
        'select u.id, u.email, u.created_at from %I.users u where u.email = $1',
        p_schema
    ) using p_email;
end;
$$;

grant execute on function public.validate_user_credentials(text, text, text) to anon, authenticated, service_role;
grant execute on function public.register_user(text, text, text) to anon, authenticated, service_role;
SQL;

            $sql = str_replace('{SCHEMA_NAME}', $schemaName, $sql);

            Http::withToken($accessToken)
                ->asJson()
                ->timeout(30)
                ->post(sprintf('https://api.supabase.com/v1/projects/%s/database/migrations', $projectRef), [
                    'name' => 'Create custom auth schema and functions for app builder',
                    'query' => $sql,
                ]);
        } catch (\Throwable) {
            // Bootstrapping is best-effort only.
        }
    }
}
