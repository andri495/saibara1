<?php

namespace App\Services;

use App\Models\ExchangeRate;
use App\Models\MasterObjek;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    protected $apiUrl = 'https://api.exchangerate-api.com/v4/latest/IDR'; // Base IDR

    protected $baseCurrency = 'IDR';

    /**
     * Fetch dan update exchange rates dari API (Base IDR)
     */
    public function updateRates()
    {
        try {
            // Fetch data dengan base IDR
            $response = Http::timeout(10)->get($this->apiUrl);

            if (! $response->successful()) {
                Log::error('Failed to fetch exchange rates', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            $data = $response->json();
            $rates = $data['rates'] ?? [];

            if (empty($rates)) {
                Log::error('No rates data received from API');

                return false;
            }

            // Currency names mapping
            $currencyNames = $this->getCurrencyNames();

            // Update setiap currency dengan rate terhadap IDR
            foreach ($rates as $currency => $rate) {
                ExchangeRate::updateOrCreate(
                    ['currency' => $currency],
                    [
                        'currency_name' => $currencyNames[$currency] ?? $currency,
                        'rate' => $rate,
                        'base_currency' => $this->baseCurrency,
                        'last_updated_at' => now(),
                    ]
                );
            }

            // Tambahkan IDR sendiri dengan rate 1
            ExchangeRate::updateOrCreate(
                ['currency' => 'IDR'],
                [
                    'currency_name' => 'Indonesian Rupiah',
                    'rate' => 1.0000000000,
                    'base_currency' => $this->baseCurrency,
                    'last_updated_at' => now(),
                ]
            );
            // Clear cache setelah update
            Cache::forget('exchange_rates_all');

            // Update harga objek berdasarkan exchange rate terbaru
            $this->updateObjectPrices();

            Log::info('Exchange rates updated successfully', [
                'base_currency' => 'IDR',
                'total_currencies' => count($rates),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Exception while updating exchange rates', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Update harga_objek di master_objek berdasarkan exchange rate
     * Formula: harga_objek = harga_objek_asing * exchange_rate
     */
    public function updateObjectPrices()
    {
        try {
            // Get all objects yang punya foreign currency
            $objects = MasterObjek::whereNotNull('master_currency_id')
                ->with('currency')
                ->get();

            $updatedCount = 0;
            $failedCount = 0;

            foreach ($objects as $object) {
                try {
                    // Skip jika tidak ada currency relation
                    if (! $object->currency) {
                        Log::warning('Object has master_currency_id but no currency relation', [
                            'object_id' => $object->id,
                            'master_currency_id' => $object->master_currency_id,
                        ]);
                        $failedCount++;

                        continue;
                    }

                    // Get currency code dari master_currencies
                    // Asumsi master_currencies punya field 'currency' yang berisi kode seperti 'USD', 'EUR', dll
                    $currencyCode = $object->currency->currency ?? null;

                    if (! $currencyCode) {
                        Log::warning('Currency has no currency code', [
                            'object_id' => $object->id,
                            'currency_id' => $object->master_currency_id,
                        ]);
                        $failedCount++;

                        continue;
                    }

                    // Get exchange rate dari exchange_rates table
                    $exchangeRate = ExchangeRate::where('currency', $currencyCode)->first();

                    if (! $exchangeRate) {
                        Log::warning('Exchange rate not found for currency', [
                            'object_id' => $object->id,
                            'currency_code' => $currencyCode,
                        ]);
                        $failedCount++;

                        continue;
                    }

                    // Asumsi ada field harga_objek_asing di master_objek
                    // Jika tidak ada, maka kita perlu menyimpan harga asli sebelum konversi
                    $hargaAsing = $object->harga_objek_asing ?? $object->harga_objek;

                    if (! $hargaAsing) {
                        Log::warning('Object has no harga_objek_asing', [
                            'object_id' => $object->id,
                        ]);
                        $failedCount++;

                        continue;
                    }

                    // Calculate: harga_objek = harga_objek_asing * exchange_rate
                    // Exchange rate adalah rate terhadap IDR
                    // Jika currency = USD dan rate = 0.000063, maka 1 USD = 1/0.000063 = 15873 IDR
                    // Maka: harga_objek (IDR) = harga_objek_asing (USD) / rate
                    $hargaObjek = $hargaAsing / $exchangeRate->rate;

                    // Round ke integer untuk IDR
                    $hargaObjek = (int) round($hargaObjek);

                    // Update harga_objek
                    $object->harga_objek = $hargaObjek;
                    $object->updated_at = now();
                    $object->save();

                    $updatedCount++;
                } catch (\Exception $e) {
                    Log::error('Error updating object price', [
                        'object_id' => $object->id,
                        'error' => $e->getMessage(),
                    ]);
                    $failedCount++;
                }
            }

            Log::info('Object prices updated', [
                'total_objects' => $objects->count(),
                'updated' => $updatedCount,
                'failed' => $failedCount,
            ]);

            return [
                'success' => true,
                'updated' => $updatedCount,
                'failed' => $failedCount,
            ];
        } catch (\Exception $e) {
            Log::error('Exception while updating object prices', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get exchange rate antara dua currency (dari IDR)
     * Contoh: getRate('USD', 'IDR') = berapa IDR per 1 USD
     * Contoh: getRate('IDR', 'USD') = berapa USD per 1 IDR
     */
    public function getRate($from, $to)
    {
        if ($from === $to) {
            return 1.0;
        }

        $cacheKey = "exchange_rate_{$from}_{$to}";

        return Cache::remember($cacheKey, 3600, function () use ($from, $to) {
            try {
                // Rate dari database (rate terhadap IDR)
                $fromRate = ExchangeRate::where('currency', $from)->value('rate');
                $toRate = ExchangeRate::where('currency', $to)->value('rate');

                if (! $fromRate || ! $toRate) {
                    Log::warning('Currency rate not found', [
                        'from' => $from,
                        'to' => $to,
                    ]);

                    return null;
                }

                // Convert: (TO rate / FROM rate)
                // Contoh: USD ke IDR
                // FROM = USD (rate 0.000063 terhadap IDR)
                // TO = IDR (rate 1 terhadap IDR)
                // Result = 1 / 0.000063 = 15873 IDR per 1 USD
                return $toRate / $fromRate;
            } catch (\Exception $e) {
                Log::error('Error getting exchange rate', [
                    'from' => $from,
                    'to' => $to,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Convert amount dari satu currency ke currency lain
     */
    public function convert($amount, $from, $to)
    {
        $rate = $this->getRate($from, $to);

        if ($rate === null) {
            return null;
        }

        $result = $amount * $rate;

        // Cast ke integer untuk IDR, JPY, KRW (tidak ada desimal)
        if (in_array($to, ['IDR', 'JPY', 'KRW'])) {
            return (int) round($result);
        }

        // Round ke 2 desimal untuk currency lain
        return round($result, 2);
    }

    /**
     * Convert dan format dengan symbol currency
     */
    public function convertAndFormat($amount, $from, $to)
    {
        $converted = $this->convert($amount, $from, $to);

        if ($converted === null) {
            return null;
        }

        return $this->formatCurrency($converted, $to);
    }

    /**
     * Format currency dengan symbol
     */
    public function formatCurrency($amount, $currency)
    {
        switch ($currency) {
            case 'IDR':
                return 'Rp ' . number_format($amount, 0, ',', '.');
            case 'USD':
                return '$' . number_format($amount, 2, '.', ',');
            case 'EUR':
                return '€' . number_format($amount, 2, '.', ',');
            case 'GBP':
                return '£' . number_format($amount, 2, '.', ',');
            case 'JPY':
                return '¥' . number_format($amount, 0, '.', ',');
            case 'SGD':
                return 'S$' . number_format($amount, 2, '.', ',');
            case 'MYR':
                return 'RM ' . number_format($amount, 2, '.', ',');
            default:
                $symbols = $this->getCurrencySymbols();
                $symbol = $symbols[$currency] ?? $currency;

                return $symbol . ' ' . number_format($amount, 2, '.', ',');
        }
    }

    /**
     * Get all available currencies
     */
    public function getAllCurrencies()
    {
        return Cache::remember('exchange_rates_all', 3600, function () {
            return ExchangeRate::orderBy('currency')->get();
        });
    }

    /**
     * Get specific currency data
     */
    public function getCurrency($currency)
    {
        return ExchangeRate::where('currency', $currency)->first();
    }

    /**
     * Check if currency exists
     */
    public function currencyExists($currency)
    {
        return ExchangeRate::where('currency', $currency)->exists();
    }

    /**
     * Get last update time
     */
    public function getLastUpdateTime()
    {
        $latest = ExchangeRate::orderBy('last_updated_at', 'desc')->first();

        return $latest ? $latest->last_updated_at : null;
    }

    /**
     * Clear all caches
     */
    public function clearCache()
    {
        Cache::forget('exchange_rates_all');

        $currencies = ExchangeRate::pluck('currency');
        foreach ($currencies as $from) {
            foreach ($currencies as $to) {
                Cache::forget("exchange_rate_{$from}_{$to}");
            }
        }
    }

    /**
     * Currency symbols mapping
     */
    protected function getCurrencySymbols()
    {
        return [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'IDR' => 'Rp',
            'SGD' => 'S$',
            'MYR' => 'RM',
            'THB' => '฿',
            'PHP' => '₱',
            'CNY' => '¥',
            'KRW' => '₩',
            'INR' => '₹',
            'AUD' => 'A$',
            'CAD' => 'C$',
            'CHF' => 'Fr',
        ];
    }

    /**
     * Currency names mapping
     */
    protected function getCurrencyNames()
    {
        return [
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
            'JPY' => 'Japanese Yen',
            'IDR' => 'Indonesian Rupiah',
            'SGD' => 'Singapore Dollar',
            'MYR' => 'Malaysian Ringgit',
            'THB' => 'Thai Baht',
            'PHP' => 'Philippine Peso',
            'CNY' => 'Chinese Yuan',
            'KRW' => 'South Korean Won',
            'INR' => 'Indian Rupee',
            'AUD' => 'Australian Dollar',
            'CAD' => 'Canadian Dollar',
            'CHF' => 'Swiss Franc',
            'NZD' => 'New Zealand Dollar',
            'SEK' => 'Swedish Krona',
            'NOK' => 'Norwegian Krone',
            'DKK' => 'Danish Krone',
            'PLN' => 'Polish Zloty',
            'RUB' => 'Russian Ruble',
            'TRY' => 'Turkish Lira',
            'BRL' => 'Brazilian Real',
            'MXN' => 'Mexican Peso',
            'ZAR' => 'South African Rand',
            'AED' => 'UAE Dirham',
            'SAR' => 'Saudi Riyal',
        ];
    }
}
