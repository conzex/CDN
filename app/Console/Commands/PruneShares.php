<?php

namespace App\Console\Commands;

use App\Models\Share;
use Illuminate\Console\Command;

class PruneShares extends Command
{
    protected $signature = 'shares:prune';
    protected $description = 'Prune expired or exhausted share links older than 7 days';

    public function handle(): int
    {
        $cutoff = now()->subDays(7);

        $deleted = Share::where(function ($q) use ($cutoff) {
            $q->where('expires_at', '<', $cutoff)
              ->orWhere(function ($q2) use ($cutoff) {
                  $q2->whereNotNull('max_downloads')
                     ->whereColumn('download_count', '>=', 'max_downloads')
                     ->where('updated_at', '<', $cutoff);
              });
        })->delete();

        $this->info("Pruned {$deleted} inactive share link(s).");

        return 0;
    }
}
