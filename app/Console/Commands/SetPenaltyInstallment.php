<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\InstallmentTransaction;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SetPenaltyInstallment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:set-penalty-installment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set penalty amount for overdue installment transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now('Asia/Jakarta');

        $this->info("Processing overdue transactions as of {$now->format('Y-m-d H:i:s')}");

        $baseQuery = InstallmentTransaction::join('transactions', 'installment_transactions.transaction_id', '=', 'transactions.reference_no')
            ->where('transactions.master_status_pembayaran_id', 8)
            ->where('installment_transactions.master_status_pembayaran_id', 8)
            ->where('installment_transactions.next_penalty_date', '<=', $now);

        DB::transaction(function () use ($baseQuery) {
            (clone $baseQuery)->update([
                'installment_transactions.penalty_amount' => DB::raw('(installment_transactions.payment_amount * 0.01) + installment_transactions.penalty_amount'),
                'installment_transactions.updated_at' => now(),
            ]);

            (clone $baseQuery)->update([
                'installment_transactions.grand_total' => DB::raw('installment_transactions.payment_amount + installment_transactions.penalty_amount'),
                'installment_transactions.next_penalty_date' => DB::raw('DATE_ADD(installment_transactions.next_penalty_date, INTERVAL 1 MONTH)'),
                'installment_transactions.updated_at' => now(),
            ]);
        });

        $this->info('Successfully updated penalty_amount and recalculated grand_total for eligible installments.');
        // Log::info('Penalty installment job executed successfully.');

        return Command::SUCCESS;
    }
}
