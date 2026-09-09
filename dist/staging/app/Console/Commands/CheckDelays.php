<?php

namespace App\Console\Commands;

use App\Services\DelayDetectionService;
use Illuminate\Console\Command;

class CheckDelays extends Command
{
    protected $signature = 'delays:check';
    protected $description = 'Check all active jobs for stalls and stop delays, send alerts.';

    public function handle(DelayDetectionService $delayService): int
    {
        $this->info('Checking for delays on active jobs...');

        $alerts = $delayService->checkAllActiveJobs();

        if (empty($alerts)) {
            $this->info('No delays detected.');
        } else {
            foreach ($alerts as $alert) {
                $this->warn("[{$alert['severity']}] {$alert['message']}");
            }
            $this->info(count($alerts) . ' delay alert(s) sent.');
        }

        return self::SUCCESS;
    }
}
