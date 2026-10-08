<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckOverduePaymentsCommand extends Command
{
    protected $signature = 'payment:check-overdue';

    protected $description = 'Check payment status for all overdue transactions (status 6) and route by payment type';

    public function handle()
    {
        $this->info('Starting overdue payment check...');

        $transaksiList = Transaksi::where('master_status_pembayaran_id', 6)
            ->where('is_installment', 0)
            ->whereIn('master_tipe_pembayaran_id', [1, 2, 3])
            ->get();

        $this->info("Found {$transaksiList->count()} overdue transactions to check.");

        $qrCount = 0;
        $vaCount = 0;
        $kodeBayarCount = 0;
        $successCount = 0;

        foreach ($transaksiList as $transaksi) {
            try {
                $paymentType = $transaksi->master_tipe_pembayaran_id;

                if ($paymentType == 1) {
                    $qrCount++;
                    $this->checkQRPayment($transaksi, $successCount);
                } elseif ($paymentType == 2) {
                    $vaCount++;
                    $this->checkVAPayment($transaksi, $successCount);
                } elseif ($paymentType == 3) {
                    $kodeBayarCount++;
                    $this->checkKodeBayarPayment($transaksi, $successCount);
                }
            } catch (\Exception $e) {
                $this->error("✗ Error checking transaction {$transaksi->reference_no}: {$e->getMessage()}");
            }
        }

        $this->info("Overdue payment check completed!");
        $this->info("QR: {$qrCount}, VA: {$vaCount}, Kode Bayar: {$kodeBayarCount}");
        $this->info("Successfully confirmed: {$successCount}");

        return Command::SUCCESS;
    }

    private function checkQRPayment($transaksi, &$successCount)
    {
        if (empty($transaksi->qr_url)) {
            $this->warn("⚠ No QR URL for transaction: {$transaksi->reference_no}");
            return;
        }

        $this->info("Checking QR transaction: {$transaksi->reference_no}");

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
                if ($transaksi->master_status_pembayaran_id == 3) {
                    $this->info("⚠ Payment already processed for transaction: {$transaksi->reference_no}");
                    return;
                }

                DB::transaction(function () use ($transaksi) {
                    $transaksi->update([
                        'master_status_pembayaran_id' => 3,
                        'payment_date' => Carbon::now('Asia/Jakarta'),
                    ]);

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

                    $transaksi->loadMissing('transaksiObject.object');

                    foreach ($transaksi->transaksiObject as $transactionObject) {
                        if ($transactionObject->object && $transactionObject->object->master_type_object_id == 4) {
                            $transactionObject->object->update([
                                'master_status_objek_id' => 0,
                            ]);
                        }
                    }
                });

                $this->info("✓ Payment confirmed for QR transaction: {$transaksi->reference_no}");
                $successCount++;
            }
        }
    }

    private function checkVAPayment($transaksi, &$successCount)
    {
        if (empty($transaksi->va)) {
            $this->warn("⚠ No VA for transaction: {$transaksi->reference_no}");
            return;
        }

        $this->info("Checking VA: {$transaksi->va} for transaction: {$transaksi->reference_no}");

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
                'kodeBilling' => $transaksi->va,
            ]),
            CURLOPT_HTTPHEADER => [
                'x-api-key: CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9PYUUYUY',
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $data = json_decode($response);

        if (isset($data->data->statusbayar) && $data->data->statusbayar == 'Payment') {
            if ($transaksi->master_status_pembayaran_id == 3) {
                $this->info("⚠ Payment already processed for VA: {$transaksi->va}");
                return;
            }

            DB::transaction(function () use ($transaksi) {
                $transaksi->update([
                    'master_status_pembayaran_id' => 3,
                    'payment_date' => Carbon::now('Asia/Jakarta'),
                ]);

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

                $transaksi->loadMissing('transaksiObject.object');

                foreach ($transaksi->transaksiObject as $transactionObject) {
                    if ($transactionObject->object && $transactionObject->object->master_type_object_id == 4) {
                        $transactionObject->object->update([
                            'master_status_objek_id' => 0,
                        ]);
                    }
                }
            });

            $this->info("✓ Payment confirmed for VA: {$transaksi->va}");
            $successCount++;
        }
    }

    private function checkKodeBayarPayment($transaksi, &$successCount)
    {
        if (empty($transaksi->kode_bayar)) {
            $this->warn("⚠ No Kode Bayar for transaction: {$transaksi->reference_no}");
            return;
        }

        $this->info("Checking Kode Bayar: {$transaksi->kode_bayar} for transaction: {$transaksi->reference_no}");

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
                'kodeBilling' => '18003' . $transaksi->kode_bayar,
            ]),
            CURLOPT_HTTPHEADER => [
                'x-api-key: CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9PYUUYUY',
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $data = json_decode($response);

        if (isset($data->data->statusbayar) && $data->data->statusbayar == 'Payment') {
            if ($transaksi->master_status_pembayaran_id == 3) {
                $this->info("⚠ Payment already processed for Kode Bayar: {$transaksi->kode_bayar}");
                return;
            }

            DB::transaction(function () use ($transaksi) {
                $transaksi->update([
                    'master_status_pembayaran_id' => 3,
                    'payment_date' => Carbon::now('Asia/Jakarta'),
                ]);

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

                $transaksi->loadMissing('transaksiObject.object');

                foreach ($transaksi->transaksiObject as $transactionObject) {
                    if ($transactionObject->object && $transactionObject->object->master_type_object_id == 4) {
                        $transactionObject->object->update([
                            'master_status_objek_id' => 0,
                        ]);
                    }
                }
            });

            $this->info("✓ Payment confirmed for Kode Bayar: {$transaksi->kode_bayar}");
            $successCount++;
        }
    }
}
