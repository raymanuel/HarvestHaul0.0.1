<?php

namespace App\Console\Commands;

use App\Services\Routing\OsrmRoutingService;
use Illuminate\Console\Command;

/**
 * Live connectivity probe for the configured routing provider — useful right
 * after deploying to Hostinger (or anywhere shared-hosting egress rules
 * might block the public OSRM instance).
 */
class RoutingHealthCheck extends Command
{
    protected $signature = 'routing:health';

    protected $description = 'Check connectivity to the configured routing provider (OSRM table/route services)';

    public function handle(OsrmRoutingService $osrm): int
    {
        $this->info('Routing Provider: '.strtoupper((string) config('routing.provider')));
        $this->info('Endpoint: '.config('routing.osrm.base_url'));
        $this->newLine();

        $result = $osrm->checkHealth();

        $this->line('Table Service: '.($result['table_ok'] ? '<fg=green>OK</>' : '<fg=red>FAILED</>'));
        $this->line('Route Service: '.($result['route_ok'] ? '<fg=green>OK</>' : '<fg=red>FAILED</>'));

        if ($result['error']) {
            $this->newLine();
            $this->error('Error: '.$result['error']);
        }

        return ($result['table_ok'] && $result['route_ok']) ? self::SUCCESS : self::FAILURE;
    }
}
