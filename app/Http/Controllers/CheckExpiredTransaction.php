<?php

namespace App\Console\Commands;

use App\Models\InstallmentTransaction;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckExpiredTransaction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-expired-transaction';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and update expired transactions (both regular and installment)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking expired Transactions...');

        // Your logic here
        $expiredInstallments = InstallmentTransaction::where('due_date', '<=', Carbon::now('Asia/Jakarta'))
            ->where('master_status_pembayaran_id', 8) // Unpaid
            ->get();
        $installmentCount = 0;

        foreach ($expiredInstallments as $installment) {
            // Update status
            $installment->update([
                'due_date' => null,
                'master_tipe_pembayaran_id' => null,
                'qr_url' => null,
                'va' => null,
                'kode_bayar' => null,
            ]);
            $installmentCount++;
        }
        $expiredTransactions = Transaksi::where('due_date', '<=', Carbon::now('Asia/Jakarta'))
            ->where('master_status_pembayaran_id', 2) // Unpaid
            ->get();
        $transactionCount = 0;

        foreach ($expiredTransactions as $transaction) {
            // Update status
            $transaction->update([
                'master_status_pembayaran_id' => 6, // Expired
            ]);
            $transactionCount++;
        }

        $this->info("✓ Processed {$transactionCount} expired regular transactions");
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('Total expired: '.($installmentCount + $transactionCount).' transactions');

        return 0;
    }
}
