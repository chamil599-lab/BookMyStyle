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
    protected $signature = 'featured:purge-expired {--apply : Apply changes after host operations are enabled}';

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
        $query = FeaturedSalon::where('active', true)->whereNotNull('ends_at')->where('ends_at', '<=', now());
        if (! $this->option('apply')) {
            $this->info('Dry run: '.$query->count().' expired featured salon(s); no changes made.');

            return self::SUCCESS;
        }
        if (! config('operations.featured_cleanup_enabled')) {
            $this->error('Featured cleanup is disabled in host operations configuration.');

            return self::FAILURE;
        }
        $count = $query->update(['active' => false]);

        if ($count > 0) {
            $this->info("Deactivated {$count} expired featured salon(s).");
        } else {
            $this->info('No expired featured salons to deactivate.');
        }

        return Command::SUCCESS;
    }
}
