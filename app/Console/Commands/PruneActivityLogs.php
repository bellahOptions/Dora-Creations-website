<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

class PruneActivityLogs extends Command
{
    protected $signature = 'activity:prune {--days=90 : Delete entries older than this many days}';

    protected $description = 'Delete old activity log entries';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $deleted = ActivityLog::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("Deleted {$deleted} activity log entries older than {$days} days.");

        return self::SUCCESS;
    }
}
