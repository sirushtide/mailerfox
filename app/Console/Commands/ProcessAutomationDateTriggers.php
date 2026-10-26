<?php

namespace App\Console\Commands;

use App\Services\AutomationTriggerService;
use Illuminate\Console\Command;

class ProcessAutomationDateTriggers extends Command
{
    protected $signature = 'automation:process-date-triggers';

    protected $description = 'Process scheduled automation triggers (birthday, anniversary, scheduled datetime)';

    public function handle(AutomationTriggerService $service): int
    {
        $this->info('Processing date-based automation triggers...');
        $service->processDateTriggers();
        $this->info('Done.');
        return self::SUCCESS;
    }
}
