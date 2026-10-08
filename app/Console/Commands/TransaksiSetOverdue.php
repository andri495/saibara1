<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaksi;
use Carbon\Carbon;

class TransaksiSetOverdue extends Command
{
    protected $signature = 'transaksi:set-overdue';
    protected $description = 'Set transaksi_object status to overdue if due_date is now';

    public function handle()
    {
        $now = Carbon::now('Asia/Jakarta');
        Transaksi::where('due_date', '<', $now)
            ->where('master_status_pembayaran_id', 2)
            ->update(['master_status_pembayaran_id' => 6]);
    }
}