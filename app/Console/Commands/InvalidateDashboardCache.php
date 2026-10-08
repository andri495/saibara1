<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class InvalidateDashboardCache extends Command
{
    protected $signature = 'dashboard:invalidate-cache';

    protected $description = 'Invalidate dashboard cache by bumping version key';

    public function handle(): int
    {
        $currentVersion = (int) Cache::get('dashboard_cache_version', 1);
        $nextVersion = $currentVersion + 1;

        Cache::forever('dashboard_cache_version', $nextVersion);

        $this->info("Dashboard cache version bumped: {$currentVersion} -> {$nextVersion}");

        return self::SUCCESS;
    }
}
