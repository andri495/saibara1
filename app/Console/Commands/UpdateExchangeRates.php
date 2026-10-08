<?php

namespace App\Console\Commands;

use App\Services\ExchangeRateService;
use Illuminate\Console\Command;

class UpdateExchangeRates extends Command
{
    protected $signature = 'exchange:update {--force : Force update}';

    protected $description = 'Update exchange rates from API (Base IDR)';

    protected $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        parent::__construct();
        $this->exchangeRateService = $exchangeRateService;
    }

    public function handle()
    {
        $this->info('Updating exchange rates (Base: IDR)...');

        if (! $this->option('force')) {
            $lastUpdate = $this->exchangeRateService->getLastUpdateTime();

            if ($lastUpdate && $lastUpdate->diffInMinutes(now()) < 60) {
                $this->warn('Rates were updated recently ('.$lastUpdate->diffForHumans().')');

                if (! $this->confirm('Do you want to continue?')) {
                    $this->info('Update cancelled.');

                    return 0;
                }
            }
        }

        $result = $this->exchangeRateService->updateRates();

        if ($result) {
            $this->info('✓ Exchange rates updated successfully!');
            $this->newLine();

            // Show USD to IDR rate
            $usdToIdr = $this->exchangeRateService->getRate('USD', 'IDR');
            $this->info('Current Rate:');
            $this->info('1 USD = '.number_format($usdToIdr, 2).' IDR');

            $this->newLine();
            $this->info('Sample conversions:');
            $this->table(
                ['Amount', 'From', 'To', 'Result'],
                [
                    ['100', 'USD', 'IDR', $this->exchangeRateService->convertAndFormat(100, 'USD', 'IDR')],
                    ['1,000,000', 'IDR', 'USD', $this->exchangeRateService->convertAndFormat(1000000, 'IDR', 'USD')],
                    ['50', 'USD', 'IDR', $this->exchangeRateService->convertAndFormat(50, 'USD', 'IDR')],
                ]
            );

            return 0;
        }

        $this->error('✗ Failed to update exchange rates. Check logs for details.');

        return 1;
    }
}
