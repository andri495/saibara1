<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\InstallmentTransaction;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckInstallmentPaymentKodeBayarCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:check-installment-kodebayar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check payment status for all active installment Kode Bayar transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting installment Kode Bayar payment check...');

        // Ambil semua installment dengan status active dan menggunakan Kode Bayar (master_tipe_pembayaran_id = 3)
        $installmentList = InstallmentTransaction::where('master_status_pembayaran_id', 8)
            ->where('master_tipe_pembayaran_id', 3)
            ->whereNotNull('kode_bayar')
            ->get();

        $this->info("Found {$installmentList->count()} active installment Kode Bayar transactions to check.");

        $successCount = 0;
        $expiredCount = 0;

        foreach ($installmentList as $installment) {
            try {
                $this->info("Checking installment Kode Bayar: {$installment->kode_bayar}");

                // Cek apakah installment sudah kadaluwarsa
                if ($installment->due_date && Carbon::now('Asia/Jakarta')->gte($installment->due_date)) {
                    // Reset field untuk installment yang kadaluwarsa
                    $installment->update([
                        'due_date' => null,
                        'master_tipe_pembayaran_id' => null,
                        'kode_bayar' => null,
                    ]);
                    $this->warn("⚠ Installment expired and reset: {$installment->installment_ref_id}");
                    $expiredCount++;
                    continue;
                }

                $curl = curl_init();

                curl_setopt_array($curl, [
                    CURLOPT_URL => 'https://api-retribusi.bapendalampung.cloud/paymentprod-va/statustrx/CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9P',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => json_encode([
                        'kodeBilling' => '18003' . $installment->kode_bayar,
                    ]),
                    CURLOPT_HTTPHEADER => [
                        'x-api-key: CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9PYUUYUY',
                        'Content-Type: application/json',
                    ],
                ]);

                $response = curl_exec($curl);
                curl_close($curl);

                $data = json_decode($response);

                $transaksi = Transaksi::where('reference_no', $installment->transaction_id)->first();

                if (isset($data->data->statusbayar) && $data->data->statusbayar == 'Payment') {
                    // Cek apakah installment sudah paid sebelumnya
                    if ($installment->master_status_pembayaran_id == 9) {
                        $this->info("⚠ Installment payment already processed: {$installment->kode_bayar}");
                        continue;
                    }

                    // Gunakan DB transaction untuk mencegah race condition
                    DB::transaction(function () use ($installment, $transaksi) {
                        // Update status installment menjadi paid
                        $installment->update([
                            'master_status_pembayaran_id' => 9,
                            'paid_date' => Carbon::now('Asia/Jakarta'),
                        ]);

                        // Check if all installments are paid
                        $allInstallments = InstallmentTransaction::where('transaction_id', $transaksi->reference_no)->get();
                        $paidInstallmentsCount = $allInstallments->where('master_status_pembayaran_id', 9)->count();
                        $totalInstallments = $allInstallments->first()->installment_total ?? 0;

                        if ($paidInstallmentsCount == $totalInstallments && $totalInstallments > 0) {
                            $transaksi->update([
                                'master_status_pembayaran_id' => 9,
                                'payment_date' => Carbon::now('Asia/Jakarta'),
                            ]);
                        }

                        // Cek apakah dokumen SPdORD sudah ada
                        $documentspdord = Document::where('transaksi_id', $installment->transaction_id)
                            ->where('master_tipe_dokumen_id', 1)
                            ->first();

                        if ($documentspdord == null) {
                            $documentspdord = Document::create([
                                'master_tipe_dokumen_id' => 1,
                                'transaksi_id' => $transaksi->reference_no,
                                'document_number' => 'SPDORD',
                                'linked_document_number' => 'SKRD',
                                'issue_date' => Carbon::now('Asia/Jakarta'),
                            ]);

                            $opd = $transaksi->transaksiObject->first()->object->master_opd_id;
                            $lokasi = $transaksi->transaksiObject->first()->object->master_lokasi_id;
                            $time = Carbon::now('Asia/Jakarta')->format('dmy');
                            $formattedId = str_pad($documentspdord->id, 4, '0', STR_PAD_LEFT);
                            $documentspdord->update([
                                'document_number' => 'SPdORD' . '-' . $opd . '-' . $lokasi . '-' . $time . '-' . $formattedId,
                            ]);
                        }

                        // Cek apakah dokumen SKRD untuk installment ini sudah ada
                        $documentskrd = Document::where('transaksi_id', $installment->transaction_id)
                            ->where('master_tipe_dokumen_id', 2)
                            ->where('installment_id', $installment->id)
                            ->first();

                        if ($documentskrd == null) {
                            $documentskrd = Document::create([
                                'master_tipe_dokumen_id' => 2,
                                'transaksi_id' => $installment->transaction_id,
                                'installment_id' => $installment->id,
                                'document_number' => 'SKRD',
                                'linked_document_number' => $documentspdord->document_number,
                                'issue_date' => Carbon::now('Asia/Jakarta'),
                            ]);

                            $opd = $transaksi->transaksiObject->first()->object->master_opd_id;
                            $lokasi = $transaksi->transaksiObject->first()->object->master_lokasi_id;
                            $rincianRetribusi = $transaksi->transaksiObject->first()->object->rincianRetribusi->rincian_retribusi;
                            $subRincianRetribusi = $transaksi->transaksiObject->first()->object->sub_rincian_retribusi;
                            $totalAmount = $installment->payment_amount;
                            $time = Carbon::now('Asia/Jakarta')->format('dmy');
                            $formattedId = str_pad($documentskrd->id, 4, '0', STR_PAD_LEFT);
                            $documentskrd->update([
                                'document_number' => 'SKRD-' . $opd . '-' . $lokasi . '-' . $rincianRetribusi . '-' . $subRincianRetribusi . '-' . 'Rp. ' . $totalAmount . '-' . $time . '-' . $formattedId,
                            ]);

                            $documentspdord->update([
                                'linked_document_number' => $documentskrd->document_number,
                            ]);
                        }
                    });

                    $this->info("✓ Installment Kode Bayar payment confirmed: {$installment->va}");
                    $successCount++;
                }
            } catch (\Exception $e) {
                $this->error("✗ Error checking installment Kode Bayar {$installment->va}: {$e->getMessage()}");
            }
        }

        $this->info('Installment Kode Bayar payment check completed!');
        $this->info("Success: {$successCount}, Expired: {$expiredCount}");

        return Command::SUCCESS;
    }
}
