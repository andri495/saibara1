<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckPaymentQRCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:check-qr';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check payment status for all active QR Code transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting QR payment check...');

        // Ambil semua transaksi dengan status active (2) dan menggunakan QR (master_tipe_pembayaran_id = 1)
        // Hanya untuk transaksi reguler (bukan installment)
        $transaksiList = Transaksi::where('master_status_pembayaran_id', 2)
            ->where('master_tipe_pembayaran_id', 1)
            ->where('is_installment', 0)
            ->whereNotNull('qr_url')
            ->get();

        $this->info("Found {$transaksiList->count()} active QR transactions to check.");

        $successCount = 0;
        $overdueCount = 0;

        foreach ($transaksiList as $transaksi) {
            try {
                $this->info("Checking transaction: {$transaksi->reference_no}");

                $curl = curl_init();

                curl_setopt_array($curl, [
                    CURLOPT_URL => 'https://api-retribusi.bapendalampung.cloud/paymentprod/statustrx/CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9P',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => json_encode([
                        'no_reference' => $transaksi->reference_no,
                    ]),
                    CURLOPT_HTTPHEADER => [
                        'x-api-key: CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9PYUUYUY',
                        'Content-Type: application/json',
                    ],
                ]);

                $response = curl_exec($curl);
                curl_close($curl);

                $data = json_decode($response);

                if (isset($data->codemessage) && $data->codemessage == '200') {
                    if (isset($data->data->lasttrxstatus) && $data->data->lasttrxstatus == '00') {
                        // Cek apakah transaksi sudah paid sebelumnya
                        if ($transaksi->master_status_pembayaran_id == 3) {
                            $this->info("⚠ Payment already processed for transaction: {$transaksi->reference_no}");
                            continue;
                        }

                        // Gunakan DB transaction untuk mencegah race condition
                        DB::transaction(function () use ($transaksi) {
                            // Update status menjadi paid
                            $transaksi->update([
                                'master_status_pembayaran_id' => 3,
                                'payment_date' => Carbon::now('Asia/Jakarta'),
                            ]);

                            // Cek apakah dokumen SPdORD sudah ada
                            $documentspdord = Document::where('transaksi_id', $transaksi->reference_no)
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

                            // Cek apakah dokumen SKRD sudah ada
                            $documentskrd = Document::where('transaksi_id', $transaksi->reference_no)
                                ->where('master_tipe_dokumen_id', 2)
                                ->whereNull('installment_id')
                                ->first();

                            if ($documentskrd == null) {
                                $documentskrd = Document::create([
                                    'master_tipe_dokumen_id' => 2,
                                    'transaksi_id' => $transaksi->reference_no,
                                    'document_number' => 'SKRD',
                                    'linked_document_number' => $documentspdord->document_number,
                                    'issue_date' => Carbon::now('Asia/Jakarta'),
                                ]);

                                $opd = $transaksi->transaksiObject->first()->object->master_opd_id;
                                $lokasi = $transaksi->transaksiObject->first()->object->master_lokasi_id;
                                $rincianRetribusi = $transaksi->transaksiObject->first()->object->rincianRetribusi->rincian_retribusi;
                                $subRincianRetribusi = $transaksi->transaksiObject->first()->object->sub_rincian_retribusi;
                                $totalAmount = $transaksi->total_amount;
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

                        $this->info("✓ Payment confirmed for transaction: {$transaksi->reference_no}");
                        $successCount++;
                    } elseif (isset($data->data->lasttrxstatus) && $data->data->lasttrxstatus == '06') {
                        // Status overdue dari gateway
                        $transaksi->update([
                            'master_status_pembayaran_id' => 6,
                        ]);
                        $this->warn("⚠ Transaction overdue (gateway): {$transaksi->reference_no}");
                        $overdueCount++;
                    } else {
                        // Cek apakah sudah overdue berdasarkan due_date
                        if (Carbon::now('Asia/Jakarta')->gte($transaksi->due_date)) {
                            $transaksi->update([
                                'master_status_pembayaran_id' => 6,
                            ]);
                            $this->warn("⚠ Transaction overdue (time): {$transaksi->reference_no}");
                            $overdueCount++;
                        }
                    }
                } else {
                    // Error dari API, cek overdue
                    if (Carbon::now('Asia/Jakarta')->gte($transaksi->due_date)) {
                        $transaksi->update([
                            'master_status_pembayaran_id' => 6,
                        ]);
                        $this->warn("⚠ Transaction overdue: {$transaksi->reference_no}");
                        $overdueCount++;
                    }
                }
            } catch (\Exception $e) {
                $this->error("✗ Error checking transaction {$transaksi->reference_no}: {$e->getMessage()}");
            }
        }

        $this->info("QR payment check completed!");
        $this->info("Success: {$successCount}, Overdue: {$overdueCount}");

        return Command::SUCCESS;
    }
}
