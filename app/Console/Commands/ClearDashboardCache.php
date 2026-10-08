<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearDashboardCache extends Command
{
    protected $signature = 'dashboard:clear-cache';

    protected $description = 'Clear dashboard cache by incrementing cache version';

    public function handle()
    {
        $oldVersion = Cache::get('dashboard_cache_version', 1);
        $newVersion = Cache::increment('dashboard_cache_version');
        
        $this->info("Dashboard cache cleared!");
        $this->info("Cache version: {$oldVersion} → {$newVersion}");
        $this->info("All dashboard data will be refreshed on next page load.");
        
        return 0;
    }
}
