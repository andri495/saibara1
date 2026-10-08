<?php

namespace App\Console\Commands;

use App\Services\ExchangeRateService;
use Illuminate\Console\Command;

class CheckExchangeRates extends Command
{
    protected $signature = 'exchange:status';

    protected $description = 'Check exchange rates status';

    protected $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        parent::__construct();
        $this->exchangeRateService = $exchangeRateService;
    }

    public function handle()
    {
        $lastUpdate = $this->exchangeRateService->getLastUpdateTime();
        $currencies = $this->exchangeRateService->getAllCurrencies();

        $this->info('╔════════════════════════════════════╗');
        $this->info('║   Exchange Rates Status (IDR)      ║');
        $this->info('╚════════════════════════════════════╝');
        $this->newLine();

        if ($lastUpdate) {
            $this->info('Last Updated: '.$lastUpdate->format('Y-m-d H:i:s'));
            $this->info('Time Ago: '.$lastUpdate->diffForHumans());
        } else {
            $this->warn('⚠ No rates found. Run: php artisan exchange:update');

            return 0;
        }

        $this->newLine();
        $this->info('Base Currency: IDR');
        $this->info('Total Currencies: '.$currencies->count());
        $this->newLine();

        // Show USD to IDR
        $usdToIdr = $this->exchangeRateService->getRate('USD', 'IDR');
        $this->info('═══ Main Rate ═══');
        $this->info('1 USD = '.number_format($usdToIdr, 2).' IDR');
        $this->newLine();

        // Show popular currencies
        $popular = ['USD', 'EUR', 'GBP', 'JPY', 'SGD', 'MYR', 'AUD'];
        $data = [];

        foreach ($popular as $currency) {
            if ($currency === 'IDR') {
                continue;
            }

            $toIdr = $this->exchangeRateService->getRate($currency, 'IDR');
            if ($toIdr) {
                $data[] = [
                    $currency,
                    number_format($toIdr, 2),
                    $this->exchangeRateService->convertAndFormat(100, $currency, 'IDR'),
                ];
            }
        }

        $this->info('═══ Popular Currencies to IDR ═══');
        $this->table(
            ['Currency', '1 Unit = IDR', '100 Units = '],
            $data
        );

        return 0;
    }
}
