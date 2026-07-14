<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class BackfillBuyerProfiles extends Command
{
    protected $signature = 'app:backfill-buyer-profiles';

    protected $description = 'Create buyer_profiles rows for existing buyers missing one';

    public function handle()
    {
        $buyers = User::where('role', 'buyer')
            ->whereDoesntHave('buyerProfile')
            ->get();

        if ($buyers->isEmpty()) {
            $this->info('All buyers already have a profile.');
            return;
        }

        $bar = $this->output->createProgressBar($buyers->count());
        $bar->start();

        foreach ($buyers as $buyer) {
            $buyer->buyerProfile()->create([
                'phone'       => $buyer->phone,
                'is_verified' => false,
            ]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Created profiles for {$buyers->count()} buyer(s).");
    }
}
