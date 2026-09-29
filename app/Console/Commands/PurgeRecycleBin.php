<?php

namespace App\Console\Commands;

use App\Models\RecycleBin;
use App\Services\RecycleBinService;
use Illuminate\Console\Command;

class PurgeRecycleBin extends Command
{
    protected $signature = 'recycle:purge {--days=30 : Number of days to retain trashed items}';
    protected $description = 'Purge items from recycle bin older than specified days';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $items = RecycleBin::where('deleted_at', '<', $cutoff)->get();
        $service = app(RecycleBinService::class);
        $count = 0;

        foreach ($items as $item) {
            $service->purge($item->id);
            $count++;
        }

        $this->info("Purged {$count} recycle bin item(s) older than {$days} days.");

        return 0;
    }
}
