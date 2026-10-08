<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\TransaksiSetOverdue::class, // ← Must match class name
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule
            ->command('transaksi:set-overdue')->everyMinute();
        $schedule->command('app:set-penalty-installment')->everyMinute();
        $schedule->command('app:check-expired-transaction')->everyMinute();
        $schedule->command('revenue:generate')->dailyAt('00:05')->timezone('Asia/Jakarta')->appendOutputTo(storage_path('logs/daily-revenue.log'));
        $schedule->command('dashboard:invalidate-cache')
            ->dailyAt('00:10')
            ->timezone('Asia/Jakarta')
            ->appendOutputTo(storage_path('logs/dashboard-invalidate-cache.log'));
        $schedule->command('exchange:update --force')
            ->dailyAt('00:00')
            ->timezone('Asia/Jakarta')
            ->appendOutputTo(storage_path('logs/exchange-update.log'));
        $schedule->command('installment:update-expired-rental-objects')
            ->dailyAt('00:00')
            ->timezone('Asia/Jakarta')
            ->appendOutputTo(storage_path('logs/installment-expired-rental-objects.log'));
        // $schedule->command('app:backup-database')
        //     ->dailyAt('00:10')
        //     ->timezone('Asia/Jakarta');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
