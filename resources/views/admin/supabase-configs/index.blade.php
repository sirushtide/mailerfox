@extends('layouts.admin')

@section('title', __('Supabase Presets'))
@section('page-title', __('Supabase Presets'))

@section('content')
<div class="space-y-6">
    <x-card>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Admin-managed Supabase') }}</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Create presets that eligible customers can use up to their group limits. BYOS users can also authorize Supabase via OAuth when the app credentials are configured below.') }}</p>

        <div class="mt-4 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('OAuth credentials') }}</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Configure the Supabase Management API OAuth application to enable one-click connectors for admin and BYOS users.') }}</p>
            <form method="POST" action="{{ route('admin.supabase-configs.oauth-settings') }}" class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Client ID') }}</label>
                    <input type="text" name="supabase_oauth_client_id" value="{{ \App\Models\Setting::get('supabase_oauth_client_id') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Client Secret') }}</label>
                    <input type="password" name="supabase_oauth_client_secret" value="{{ \App\Models\Setting::get('supabase_oauth_client_secret') ? '********' : '' }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" />
                </div>
                <div class="md:col-span-2">
                    <x-button type="submit" variant="primary" size="sm">{{ __('Save credentials') }}</x-button>
                </div>
            </form>
            @if(\App\Models\Setting::get('supabase_oauth_client_id') && \App\Models\Setting::get('supabase_oauth_client_secret'))
                <a href="{{ route('admin.supabase.oauth.authorize', ['target_type' => 'admin']) }}" class="mt-3 inline-block text-sm text-primary-600 hover:text-primary-700">{{ __('Connect admin preset via Supabase OAuth') }}</a>
            @endif
        </div>
    </x-card>

    <x-card>
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Presets') }}</h3>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-2">{{ __('Name') }}</th>
                        <th class="px-4 py-2">{{ __('Project URL') }}</th>
                        <th class="px-4 py-2">{{ __('Status') }}</th>
                        <th class="px-4 py-2">{{ __('Default') }}</th>
                        <th class="px-4 py-2">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($configs as $config)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="px-4 py-2 font-medium text-gray-900 dark:text-gray-100">{{ $config->name }}</td>
                            <td class="px-4 py-2">{{ $config->project_url }}</td>
                            <td class="px-4 py-2">{{ $config->status }}</td>
                            <td class="px-4 py-2">{{ $config->is_default ? __('Yes') : __('No') }}</td>
                            <td class="px-4 py-2 space-x-2">
                                @if(! $config->is_default)
                                    <form method="POST" action="{{ route('admin.supabase-configs.default', $config) }}" class="inline">
                                        @csrf
                                        <x-button type="submit" variant="secondary" size="xs">{{ __('Set default') }}</x-button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.supabase-configs.destroy', $config) }}" class="inline" onsubmit="return confirm('{{ __('Remove this preset?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" variant="danger" size="xs">{{ __('Remove') }}</x-button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-center">{{ __('No presets yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-card>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Add preset manually') }}</h3>
        <form method="POST" action="{{ route('admin.supabase-configs.store') }}" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Name') }}</label>
                <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Connection type') }}</label>
                <select name="connection_type" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                    <option value="hosted">Supabase Hosted</option>
                    <option value="self_hosted">Self-hosted</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Project URL') }}</label>
                <input type="url" name="project_url" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Project ID') }}</label>
                <input type="text" name="project_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Publishable key') }}</label>
                <input type="text" name="publishable_key" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('API secret') }}</label>
                <input type="text" name="secret_key" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Database password') }}</label>
                <input type="password" name="db_password" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Region') }}</label>
                <input type="text" name="region" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" />
            </div>
            <div class="md:col-span-2 flex items-center gap-2">
                <input type="checkbox" name="is_default" value="1" id="is_default" />
                <label for="is_default" class="text-sm text-gray-700 dark:text-gray-300">{{ __('Set as default') }}</label>
            </div>
            <div class="md:col-span-2">
                <x-button type="submit" variant="primary" size="sm">{{ __('Connect and save') }}</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
