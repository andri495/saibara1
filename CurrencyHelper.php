<?php

use App\Services\ExchangeRateService;

if (! function_exists('exchange_rate')) {
    /**
     * Get exchange rate dari satu currency ke currency lain
     * Contoh: exchange_rate('USD', 'IDR') = 15750
     */
    function exchange_rate($from, $to)
    {
        return app(ExchangeRateService::class)->getRate($from, $to);
    }
}

if (! function_exists('convert_currency')) {
    /**
     * Convert amount dari satu currency ke currency lain
     * Contoh: convert_currency(100, 'USD', 'IDR') = 1575000
     */
    function convert_currency($amount, $from, $to)
    {
        return app(ExchangeRateService::class)->convert($amount, $from, $to);
    }
}

if (! function_exists('format_currency')) {
    /**
     * Format currency dengan symbol
     * Contoh: format_currency(1575000, 'IDR') = "Rp 1.575.000"
     */
    function format_currency($amount, $currency)
    {
        return app(ExchangeRateService::class)->formatCurrency($amount, $currency);
    }
}

if (! function_exists('convert_and_format')) {
    /**
     * Convert dan format sekaligus
     * Contoh: convert_and_format(100, 'USD', 'IDR') = "Rp 1.575.000"
     */
    function convert_and_format($amount, $from, $to)
    {
        return app(ExchangeRateService::class)->convertAndFormat($amount, $from, $to);
    }
}

if (! function_exists('usd_to_idr')) {
    /**
     * Shortcut: Convert USD ke IDR
     * Contoh: usd_to_idr(100) = 1575000
     */
    function usd_to_idr($amount)
    {
        return app(ExchangeRateService::class)->convert($amount, 'USD', 'IDR');
    }
}

if (! function_exists('idr_to_usd')) {
    /**
     * Shortcut: Convert IDR ke USD
     * Contoh: idr_to_usd(1575000) = 100
     */
    function idr_to_usd($amount)
    {
        return app(ExchangeRateService::class)->convert($amount, 'IDR', 'USD');
    }
}
