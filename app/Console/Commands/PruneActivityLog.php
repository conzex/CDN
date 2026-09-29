<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

class PruneActivityLog extends Command
{
    protected $signature = 'activity:prune {--days=90 : Number of days to retain logs}';
    protected $description = 'Prune old activity logs older than specified days';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $deleted = ActivityLog::where('created_at', '<', $cutoff)->delete();
        $this->info("Pruned {$deleted} activity log entry/entries older than {$days} days.");

        return 0;
    }
}
