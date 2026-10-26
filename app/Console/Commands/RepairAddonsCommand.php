<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Repairs installed addon bootstrap files that were written for the legacy
 * addon system.
 *
 * The MailPurse 2.0 addon loader actually executes storage/app/addons/{slug}/
 * bootstrap.php. Older addon packages (installed before 2.0) can contain
 * bootstrap files that register duplicate routes and reference a middleware
 * alias that no longer exists ("addon.active"), which can break the app.
 *
 * This command replaces any such bootstrap with a safe, thin bootstrap that
 * only registers addon-local views and migrations. It never touches the core
 * application code.
 */
class RepairAddonsCommand extends Command
{
    protected $signature = 'addons:repair {--force : Repair even bootstraps that do not look unsafe}';

    protected $description = 'Repair legacy addon bootstrap files that break the 2.0 addon loader';

    public function handle(): int
    {
        $base = storage_path('app/addons');

        if (!is_dir($base)) {
            $this->info('No installed addons found.');

            return self::SUCCESS;
        }

        $repaired = 0;
        $skipped = 0;

        foreach (File::directories($base) as $dir) {
            $bootstrap = $dir . '/bootstrap.php';

            if (!is_file($bootstrap)) {
                continue;
            }

            $slug = basename($dir);
            $contents = (string) file_get_contents($bootstrap);

            $unsafe = str_contains($contents, 'addon.active') || str_contains($contents, 'Route::group');

            if (!$unsafe && !$this->option('force')) {
                $this->line("  ok    {$slug} (looks safe)");
                $skipped++;

                continue;
            }

            $backup = $bootstrap . '.legacy-' . date('YmdHis') . '.bak';
            File::copy($bootstrap, $backup);
            File::put($bootstrap, $this->safeBootstrap());

            $this->info("  fixed {$slug} (backup: " . basename($backup) . ')');
            $repaired++;
        }

        $this->newLine();
        $this->info("Repaired {$repaired} addon bootstrap(s); {$skipped} left unchanged.");
        $this->line('Now run: php artisan optimize:clear');

        return self::SUCCESS;
    }

    protected function safeBootstrap(): string
    {
        return <<<'PHP'
<?php

/**
 * MailPurse addon bootstrap (repaired).
 *
 * Feature code, routes, views and migrations for first-party addons ship
 * with the MailPurse core app. This file only registers addon-local
 * resources that the package may actually provide. It intentionally does
 * NOT register routes or reference middleware aliases.
 */

use Illuminate\Support\Facades\View;

if (!defined('MAILPURSE_ADDON_LOADED')) {
    return;
}

$addonPath = __DIR__;

if (is_dir($addonPath . '/resources/views')) {
    View::addLocation($addonPath . '/resources/views');
}

if (function_exists('add_filter')) {
    add_filter('addon_migrations_paths', function ($paths) use ($addonPath) {
        if (is_dir($addonPath . '/database/migrations')) {
            $paths[] = $addonPath . '/database/migrations';
        }

        return $paths;
    });
}
PHP;
    }
}
