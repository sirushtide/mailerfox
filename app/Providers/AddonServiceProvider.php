<?php

namespace App\Providers;

use App\Models\Addon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * Real addon loader.
 *
 * Every active addon stored under storage/app/addons/{slug} is bootstrapped:
 *   - its bootstrap.php is required (after MAILPURSE_ADDON_LOADED is defined)
 *   - its resources/views directory is registered with the view finder
 *   - its database/migrations directory is registered via the
 *     "addon_migrations_paths" filter
 *   - classes under its src/ directory are autoloaded from the
 *     MailPurse\Addons\* namespace
 */
class AddonServiceProvider extends ServiceProvider
{
    /** @var array<string, string> slug => absolute path */
    protected array $loaded = [];

    public function register(): void
    {
        require_once __DIR__ . '/../Helpers/addon_hooks.php';

        $this->registerAddonAutoloader();
    }

    public function boot(): void
    {
        // The addon system must never be able to take the whole site down.
        // Every step is isolated so a single bad/mismatched addon only logs.
        try {
            if (!defined('MAILPURSE_ADDON_LOADED')) {
                define('MAILPURSE_ADDON_LOADED', true);
            }

            foreach ($this->activeAddons() as $addon) {
                try {
                    $path = storage_path('app/addons/' . $addon->slug);
                    if (!is_dir($path)) {
                        continue;
                    }

                    $this->loadAddon($addon->slug, $path);
                } catch (\Throwable $e) {
                    Log::error("Failed to load addon [{$addon->slug}]: " . $e->getMessage());
                }
            }

            $this->loadAddonMigrations();

            do_action('mailpurse_addons_loaded', $this->loaded);
        } catch (\Throwable $e) {
            Log::error('Addon system boot failed: ' . $e->getMessage());
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, Addon>
     */
    protected function activeAddons()
    {
        try {
            if (!Schema::hasTable('addons')) {
                return collect();
            }

            return Addon::query()->where('status', 'active')->get();
        } catch (\Throwable $e) {
            // Database not available yet (install wizard, migrations, CLI boot).
            return collect();
        }
    }

    protected function loadAddon(string $slug, string $path): void
    {
        $bootstrap = $path . '/bootstrap.php';
        if (is_file($bootstrap)) {
            try {
                require $bootstrap;
            } catch (\Throwable $e) {
                Log::error("Failed to bootstrap addon [{$slug}]: " . $e->getMessage());
            }
        }

        $views = $path . '/resources/views';
        if (is_dir($views)) {
            View::addLocation($views);
        }

        $this->loaded[$slug] = $path;
    }

    protected function loadAddonMigrations(): void
    {
        $paths = apply_filters('addon_migrations_paths', []);
        if (!is_array($paths)) {
            return;
        }

        $paths = array_unique(array_filter($paths, static fn ($p) => is_string($p) && is_dir($p)));
        if ($paths === []) {
            return;
        }

        try {
            $this->loadMigrationsFrom(array_values($paths));
        } catch (\Throwable $e) {
            Log::error('Failed to register addon migrations: ' . $e->getMessage());
        }
    }

    /**
     * Autoload addon classes from MailPurse\Addons\{Name}\... maps to
     * storage/app/addons/{kebab-name}/src/...
     */
    protected function registerAddonAutoloader(): void
    {
        spl_autoload_register(function (string $class): void {
            $prefix = 'MailPurse\\Addons\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }

            $relative = substr($class, strlen($prefix));
            $parts = explode('\\', $relative);
            $namespace = array_shift($parts);
            if ($parts === []) {
                return;
            }

            $slug = Str::kebab($namespace);
            $file = storage_path('app/addons/' . $slug . '/src/' . implode('/', $parts) . '.php');

            if (is_file($file)) {
                require $file;
            }
        });
    }
}
