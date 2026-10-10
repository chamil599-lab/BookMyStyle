<?php

namespace App\Console\Commands;

use App\Models\FeaturedSalon;
use Illuminate\Console\Command;

class PurgeExpiredFeaturedSalons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'featured:purge-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate featured salons that have passed their end date';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = FeaturedSalon::where('active', true)
            ->where('ends_at', '!=', null)
            ->where('ends_at', '<', now())
            ->update(['active' => false]);

        if ($count > 0) {
            $this->info("Deactivated {$count} expired featured salon(s).");
        } else {
            $this->info('No expired featured salons to deactivate.');
        }

        return Command::SUCCESS;
    }
}
