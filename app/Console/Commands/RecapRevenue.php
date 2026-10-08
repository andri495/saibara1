<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecapRevenue extends Command
{
    protected $signature = 'revenue:recap {start_date} {end_date}';

    protected $description = 'Recap revenue data for a specific date range';

    public function handle()
    {
        $startDate = $this->argument('start_date');
        $endDate = $this->argument('end_date');

        // Validate dates
        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
        } catch (\Exception $e) {
            $this->error('Invalid date format. Use YYYY-MM-DD format.');
            return 1;
        }

        if ($start->gt($end)) {
            $this->error('Start date must be before or equal to end date.');
            return 1;
        }

        $this->info("Recapping revenue from {$startDate} to {$endDate}...");

        // Ask for confirmation
        if (!$this->confirm('This will delete and regenerate revenue data for the specified period. Continue?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        // Delete existing data for the period
        $this->info('Deleting existing revenue data...');
        $deleted = DB::table('revenues')
            ->whereBetween('date', [$startDate, $endDate])
            ->delete();
        $this->info("Deleted {$deleted} existing records.");

        $now = Carbon::now('Asia/Jakarta')->toDateTimeString();

        /*
        |--------------------------------------------------------------------------
        | NON INSTALLMENT
        |--------------------------------------------------------------------------
        */

        $this->info('Processing non-installment transactions...');

        DB::statement("
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
                '{$now}',
                '{$now}'

            FROM transactions t
            JOIN transaction_objects to2
                ON to2.transaction_id = t.reference_no
            JOIN master_objek mo
                ON mo.id = to2.object_id

            WHERE
                t.master_status_pembayaran_id = 3
                AND (t.is_installment = 0 OR t.is_installment IS NULL)
                AND DATE(t.payment_date) BETWEEN '{$startDate}' AND '{$endDate}'
                AND mo.master_retribusi_id IN (1,2,3)

            GROUP BY
                DATE(t.payment_date),
                mo.master_opd_id,
                mo.master_lokasi_id,
                mo.master_retribusi_id,
                mo.master_rincian_retribusi_id,
                mo.sub_rincian_retribusi
        ");

        $nonInstallmentCount = DB::table('revenues')
            ->whereBetween('date', [$startDate, $endDate])
            ->where('is_installment', 0)
            ->count();
        $this->info("Inserted {$nonInstallmentCount} non-installment records.");

        /*
        |--------------------------------------------------------------------------
        | INSTALLMENT
        | Revenue from paid installments only (installment_transactions.grand_total)
        | Each installment transaction has only ONE object
        |--------------------------------------------------------------------------
        */

        $this->info('Processing installment transactions...');

        DB::statement("
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
                '{$now}',
                '{$now}'

            FROM installment_transactions it
            JOIN transactions t
                ON t.reference_no = it.transaction_id
            JOIN transaction_objects to2
                ON to2.transaction_id = t.reference_no
            JOIN master_objek mo
                ON mo.id = to2.object_id

            WHERE
                it.master_status_pembayaran_id = 9
                AND DATE(it.paid_date) BETWEEN '{$startDate}' AND '{$endDate}'
                AND mo.master_retribusi_id IN (1,2,3)

            GROUP BY
                DATE(it.paid_date),
                mo.master_opd_id,
                mo.master_lokasi_id,
                mo.master_retribusi_id,
                mo.master_rincian_retribusi_id,
                mo.sub_rincian_retribusi
        ");

        $installmentCount = DB::table('revenues')
            ->whereBetween('date', [$startDate, $endDate])
            ->where('is_installment', 1)
            ->count();
        $this->info("Inserted {$installmentCount} installment records.");

        // Summary
        $totalRecords = $nonInstallmentCount + $installmentCount;
        $totalRevenue = DB::table('revenues')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('total_revenue');

        $this->info('');
        $this->info('=== Recap Summary ===');
        $this->info("Period: {$startDate} to {$endDate}");
        $this->info("Total records: {$totalRecords}");
        $this->info("Non-installment: {$nonInstallmentCount}");
        $this->info("Installment: {$installmentCount}");
        $this->info("Total revenue: Rp " . number_format($totalRevenue, 2, ',', '.'));
        $this->info('');
        $this->info('Revenue recap completed successfully!');

        return 0;
    }
}
