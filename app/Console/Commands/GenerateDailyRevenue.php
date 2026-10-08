<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateDailyRevenue extends Command
{
    protected $signature = 'revenue:generate';

    protected $description = 'Generate daily revenue aggregation';

    public function handle()
    {
        $date = Carbon::yesterday()->toDateString();
        $now = Carbon::now('Asia/Jakarta');
        $timestamp = $now->toDateTimeString();

        $this->info("Starting revenue generation for {$date} at {$timestamp}");
        $this->writeLog("Starting revenue generation for {$date} at {$timestamp}");

        try {
            /*
            |--------------------------------------------------------------------------
            | NON INSTALLMENT
            | Grouped at lokasi level (no master_objek_id).
            | year & month are derived from payment_date for fast index filtering.
            |--------------------------------------------------------------------------
            */

            $regularCount = DB::affectingStatement("
                INSERT INTO revenues
                (date, master_opd_id, master_lokasi_id, master_retribusi_id,
                 master_rincian_retribusi_id, master_sub_rincian_retribusi_id,
                 is_installment, total_transactions, total_revenue, created_at, updated_at)

                SELECT
                    DATE(t.payment_date),
                    mo.master_opd_id,
                    mo.master_lokasi_id,
                    mo.master_retribusi_id,
                    mo.master_rincian_retribusi_id,
                    mo.sub_rincian_retribusi,
                    0,
                    COUNT(DISTINCT t.reference_no),
                    SUM(to2.total_amount),
                    '{$timestamp}',
                    '{$timestamp}'

                FROM transactions t
                JOIN transaction_objects to2
                    ON to2.transaction_id = t.reference_no
                JOIN master_objek mo
                    ON mo.id = to2.object_id

                WHERE
                    t.master_status_pembayaran_id = 3
                    AND (t.is_installment = 0 OR t.is_installment IS NULL)
                    AND DATE(t.payment_date) = '{$date}'
                    AND mo.master_retribusi_id IN (1,2,3)

                GROUP BY
                    DATE(t.payment_date),
                    mo.master_opd_id,
                    mo.master_lokasi_id,
                    mo.master_retribusi_id,
                    mo.master_rincian_retribusi_id,
                    mo.sub_rincian_retribusi

                ON DUPLICATE KEY UPDATE
                    total_transactions = VALUES(total_transactions),
                    total_revenue = VALUES(total_revenue),
                    updated_at = '{$timestamp}'
            ");

            $this->info("Regular transactions processed: {$regularCount} row(s) affected");
            $this->writeLog("Regular transactions processed: {$regularCount} row(s) affected");

            /*
            |--------------------------------------------------------------------------
            | INSTALLMENT
            | Revenue from installment_transactions.grand_total (paid installments only).
            | Each installment transaction has only ONE object, so no split needed.
            | Grouped at lokasi level (no master_objek_id).
            |--------------------------------------------------------------------------
            */

            $installmentCount = DB::affectingStatement("
                INSERT INTO revenues
                (date, master_opd_id, master_lokasi_id, master_retribusi_id,
                 master_rincian_retribusi_id, master_sub_rincian_retribusi_id,
                 is_installment, total_transactions, total_revenue, created_at, updated_at)

                SELECT
                    DATE(it.paid_date),
                    mo.master_opd_id,
                    mo.master_lokasi_id,
                    mo.master_retribusi_id,
                    mo.master_rincian_retribusi_id,
                    mo.sub_rincian_retribusi,
                    1,
                    COUNT(it.id),
                    SUM(it.grand_total),
                    '{$timestamp}',
                    '{$timestamp}'

                FROM installment_transactions it
                JOIN transactions t
                    ON t.reference_no = it.transaction_id
                JOIN transaction_objects to2
                    ON to2.transaction_id = t.reference_no
                JOIN master_objek mo
                    ON mo.id = to2.object_id

                WHERE
                    it.master_status_pembayaran_id = 9
                    AND DATE(it.paid_date) = '{$date}'
                    AND mo.master_retribusi_id IN (1,2,3)

                GROUP BY
                    DATE(it.paid_date),
                    mo.master_opd_id,
                    mo.master_lokasi_id,
                    mo.master_retribusi_id,
                    mo.master_rincian_retribusi_id,
                    mo.sub_rincian_retribusi

                ON DUPLICATE KEY UPDATE
                    total_transactions = VALUES(total_transactions),
                    total_revenue = VALUES(total_revenue),
                    updated_at = '{$timestamp}'
            ");

            $this->info("Installment transactions processed: {$installmentCount} row(s) affected");
            $this->writeLog("Installment transactions processed: {$installmentCount} row(s) affected");

            $this->info("Revenue generated successfully for {$date}");
            $this->writeLog("Revenue generated successfully for {$date}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $message = 'Revenue generation failed: ' . $e->getMessage();
            $this->error($message);
            $this->writeLog($message);

            return self::FAILURE;
        }
    }

    private function writeLog(string $message): void
    {
        $logDirectory = storage_path('logs');
        if (! is_dir($logDirectory)) {
            mkdir($logDirectory, 0777, true);
        }

        $timestamp = Carbon::now('Asia/Jakarta')->toDateTimeString();
        $entry = "[{$timestamp}] {$message}" . PHP_EOL;

        file_put_contents($logDirectory . '/daily-revenue.log', $entry, FILE_APPEND);
    }
}
