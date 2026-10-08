<?php

namespace App\Console\Commands;

use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateExpiredInstallmentRentalObjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'installment:update-expired-rental-objects';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update object status to available (master_status_objek_id = 1) when installment rental end_date has passed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting expired installment rental objects check...');

        // Get all installment transactions
        $installmentTransactions = Transaksi::with(['transaksiObject.object'])
            ->where('is_installment', 1)
            ->get();

        $updatedCount = 0;
        $currentTime = Carbon::now('Asia/Jakarta');

        foreach ($installmentTransactions as $transaksi) {
            foreach ($transaksi->transaksiObject as $transaksiObject) {
                // Check if object exists and has master_type_object_id = 4 (rental type)
                if ($transaksiObject->object && $transaksiObject->object->master_type_object_id == 4) {
                    // Check if end_date has passed
                    if ($transaksiObject->end_date && Carbon::parse($transaksiObject->end_date)->lt($currentTime)) {
                        // Only update if currently rented (master_status_objek_id = 0)
                        if ($transaksiObject->object->master_status_objek_id == 0) {
                            $transaksiObject->object->update([
                                'master_status_objek_id' => 1,
                            ]);
                            $updatedCount++;
                            $this->info("✓ Updated object ID: {$transaksiObject->object->id} - Transaction: {$transaksi->reference_no} - End Date: {$transaksiObject->end_date}");
                        }
                    }
                }
            }
        }

        $this->info("Completed! Updated {$updatedCount} expired installment rental object(s) to available status.");

        return 0;
    }
}
