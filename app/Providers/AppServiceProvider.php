<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.custom');
        
        // Handle Redis connection failures gracefully
        try {
            if (method_exists(\Cache::getStore(), 'getRedis')) {
                \Cache::getStore()->getRedis()->ping();
            }
        } catch (\Throwable $e) {
            \Log::warning('Redis connection failed, falling back to file cache: ' . $e->getMessage());
            config(['cache.default' => 'file']);
        }
    }
}
