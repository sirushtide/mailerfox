<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Plan;
use App\Services\CustomerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateCustomersFromGroupsToPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customers:migrate-groups-to-plans
                            {--dry-run : Show what would be changed without modifying anything}
                            {--limit=0 : Limit the number of customers to process (0 = unlimited)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing customers from Customer Groups to Plans via admin-assigned subscriptions.';

    public function handle(CustomerService $customerService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');

        // Map customer_group_id -> plan_id so each group links to one plan.
        $groupToPlan = Plan::query()
            ->whereNotNull('customer_group_id')
            ->pluck('id', 'customer_group_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (empty($groupToPlan)) {
            $this->warn('No plans are linked to a customer_group_id. Nothing to migrate.');
            return self::SUCCESS;
        }

        $query = Customer::query()
            ->whereHas('customerGroups', function ($q) use ($groupToPlan) {
                $q->whereIn('customer_groups.id', array_keys($groupToPlan));
            });

        if ($limit > 0) {
            $query->limit($limit);
        }

        $customers = $query->with('customerGroups')->get();

        $this->info(sprintf(
            '%s %d customer(s) linked to a group that maps to a plan.',
            $dryRun ? 'Would migrate' : 'Migrating',
            $customers->count()
        ));

        $processed = 0;
        $skipped = 0;

        foreach ($customers as $customer) {
            $group = $customer->customerGroups->first();

            if (!$group) {
                $skipped++;
                continue;
            }

            $planId = $groupToPlan[$group->id] ?? null;

            if (!$planId) {
                $this->line("  - Skipped customer #{$customer->id}: group #{$group->id} has no linked plan");
                $skipped++;
                continue;
            }

            $this->line("  - Customer #{$customer->id} ({$customer->email}): group '{$group->name}' -> plan #{$planId}");

            if ($dryRun) {
                $processed++;
                continue;
            }

            DB::transaction(function () use ($customerService, $customer, $planId) {
                $customerService->syncSubscriptionForPlan($customer, $planId);
            });

            $processed++;
        }

        $this->info(sprintf(
            '%s complete. Processed: %d, Skipped: %d.',
            $dryRun ? 'Dry run' : 'Migration',
            $processed,
            $skipped
        ));

        return self::SUCCESS;
    }
}
