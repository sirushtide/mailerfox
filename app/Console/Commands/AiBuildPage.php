<?php

namespace App\Console\Commands;

use App\Jobs\PageAiBuildSessionJob;
use App\Services\AI\AiProviderRouter;
use Illuminate\Console\Command;

class AiBuildPage extends Command
{
    protected $signature = 'ai:build-page
        {session_id : The sandbox session ID}
        {generation_id : The AI generation record ID}
        {prompt : The user prompt}
        {--screenshot-url= : Optional screenshot URL}
        {--model-param=auto : Model parameter}
        {--resolved-model=gpt-4o-mini : Resolved model name}
        {--theme-json= : Optional frontend theme JSON}
        {--raw-prompt= : Raw user prompt without theme composition}';

    protected $description = 'Run page AI generation in a background CLI process';

    public function handle(): int
    {
        $router = app(AiProviderRouter::class);

        $job = new PageAiBuildSessionJob(
            $this->argument('session_id'),
            (int) $this->argument('generation_id'),
            $this->argument('prompt'),
            $this->option('screenshot-url') ?: null,
            $this->option('model-param') ?: 'auto',
            $this->option('resolved-model') ?: 'gpt-4o-mini',
            $this->option('theme-json') ?: null,
            $this->option('raw-prompt') ?: null
        );

        try {
            $job->handle($router);
        } catch (\Throwable $e) {
            $job->failed($e);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
