<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MasterObjek;
use App\Models\Transaksi;
use App\Models\TransaksiObjek;
use App\Models\User;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;

class ExternalPaymentWebController extends Controller
{
    public function index(Request $request, $opdId = null, $lokasiId = null)
    {
        $objects = null;

        if ($opdId && $lokasiId) {
            $objects = MasterObjek::where('master_opd_id', $opdId)
                ->where('master_lokasi_id', $lokasiId)
                ->where('master_status_objek_id', 1)
                ->with(['lokasi', 'opd'])
                ->orderBy('id', 'asc')
                ->paginate(12);
        }

        return view('external.payments', [
            'opdId' => $opdId,
            'lokasiId' => $lokasiId,
            'objects' => $objects,
        ]);
    }

    // GET /external/objects/{id} → show object detail page with quantity form
    public function showObject($id)
    {
        $object = MasterObjek::with(['lokasi', 'opd', 'retribusi', 'rincianRetribusi', 'satuan'])
            ->where('master_status_objek_id', 1)
            ->findOrFail($id);

        return view('external.object_show', compact('object'));
    }

    // POST /external/transactions { object_id, quantity, masterTipePembayaranId }
    public function createTransaction(Request $request)
    {
        $validated = $request->validate([
            'object_id' => 'required|integer|exists:master_objek,id',
            'quantity' => 'required|integer|min:1',
            'masterTipePembayaranId' => 'required|integer|in:1,2,3',
        ]);

        $object = MasterObjek::with(['opd'])->findOrFail($validated['object_id']);
        $lineTotal = ($object->harga_objek ?? 0) * (int) $validated['quantity'];

        // Lookup user with same opd & lokasi and master_role_id=7
        $user = User::where('master_opd_id', $object->master_opd_id)
            ->where('master_lokasi_id', $object->master_lokasi_id)
            ->where('master_role_id', 7)
            ->first();

        // Determine transaction_type based on nama_instansi
        $transactionType = 'perorangan';
        if ($user && !empty($user->nama_instansi)) {
            $transactionType = 'INSTANSI';
        }

        $uuid1 = Uuid::uuid1();
        $referenceNo = substr($uuid1, 0, 22);

        DB::beginTransaction();
        try {
            $transaksi = Transaksi::create([
                'reference_no' => $referenceNo,
                'subject_id' => $user?->id,
                'npwrd' => $user?->npwrd,
                'subject_name' => $user?->name,
                'subject_address' => $user?->address,
                'nama_instansi' => $user?->nama_instansi,
                'no_telp' => $user?->no_telp,
                'penalty_amount' => 0,
                'total_amount' => $lineTotal,
                'registration_date' => Carbon::now('Asia/Jakarta'),
                'payment_date' => null,
                'due_date' => null,
                'document_support_link' => null,
                'transaction_type' => $transactionType,
                'master_tipe_pembayaran_id' => (int) $validated['masterTipePembayaranId'],
                'transaction_response' => null,
                'master_status_pembayaran_id' => 2,
                'va' => null,
                'qr_url' => null,
                'kode_bayar' => null,
                'is_installment' => 0,
                'installment_completed' => 0,
            ]);

            TransaksiObjek::create([
                'transaction_id' => $transaksi->reference_no,
                'object_id' => $object->id,
                'quantity' => (int) $validated['quantity'],
                'tpj_quantity' => (int) $validated['quantity'],
                'satuan_quantity' => (int) $validated['quantity'],
                'total_amount' => $lineTotal,
                'document_support_link' => null,
                'document_payment_link' => null,
                'document_result' => null,
                'start_date' => null,
                'end_date' => null,
            ]);

            DB::commit();

            return redirect()->to('/external/pay/' . $transaksi->reference_no);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['message' => 'Failed to create transaction: ' . $e->getMessage()])->withInput();
        }
    }

    // GET /external/pay/{reference_no}
    public function paymentPage($referenceNo)
    {
        $transaksi = Transaksi::with(['transaksiObject.object.opd', 'transaksiObject.object.lokasi'])
            ->where('reference_no', $referenceNo)
            ->firstOrFail();

        return view('external.payment', compact('transaksi'));
    }

    // GET /external/objects?master_opd_id=&master_lokasi_id=&per_page=
    public function getObjects(Request $request)
    {
        $request->validate([
            'master_opd_id' => 'required|integer|exists:master_opd,id',
            'master_lokasi_id' => 'required|integer|exists:master_lokasi_objek,id',
            'per_page' => 'sometimes|integer|min:1|max:50',
        ]);

        $query = MasterObjek::where('master_opd_id', $request->master_opd_id)
            ->where('master_lokasi_id', $request->master_lokasi_id)
            ->where('master_status_objek_id', 1)
            ->with(['lokasi', 'opd', 'retribusi', 'rincianRetribusi', 'satuan', 'tpj', 'statusObjek', 'typeObject', 'currency'])
            ->withAvg('ratings', 'rating_star')
            ->orderBy('id', 'asc');

        $objects = $query->paginate($request->input('per_page', 10), ['*'], 'objects');

        return response()->json([
            'status' => 200,
            'message' => 'Objects fetched successfully',
            'pagination' => [
                'current_page' => $objects->currentPage(),
                'per_page' => $objects->perPage(),
                'total' => $objects->total(),
                'last_page' => $objects->lastPage(),
                'next_page_url' => $objects->nextPageUrl(),
                'prev_page_url' => $objects->previousPageUrl(),
            ],
            'data' => $objects->items(),
        ]);
    }

    // POST /external/payment/transaction { reference_no, masterTipePembayaranId }
    public function setPaymentTransaction(Request $request)
    {
        $request->validate([
            'reference_no' => 'required|string|exists:transactions,reference_no',
            'masterTipePembayaranId' => 'required|integer|in:1,2,3',
        ]);

        $transaksi = Transaksi::with(['transaksiObject.object.opd', 'transaksiObject.object.retribusi', 'transaksiObject.object'])
            ->where('reference_no', $request->reference_no)
            ->firstOrFail();

        $datePart = Carbon::now('Asia/Jakarta')->format('dmy');
        $kodeDinas = str_pad($transaksi->transaksiObject->first()->object->master_opd_id, 2, '0', STR_PAD_LEFT);

        if ($request->masterTipePembayaranId == 1) { // QRIS
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api-retribusi.bapendalampung.cloud/paymentprod/create_qris/CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9P',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode([
                    'no_reference' => $transaksi->reference_no,
                    'kode_dinas' => $transaksi->transaksiObject->first()->object->master_opd_id,
                    'nama_dinas' => $transaksi->transaksiObject->first()->object->opd->nama_opd,
                    'tgl_bayar' => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'total_bayar' => $transaksi->total_amount,
                    'items' => $transaksi->transaksiObject->map(function ($item, $index) use ($transaksi, $datePart, $kodeDinas) {
                        $nomorUrut = str_pad($index + 1, 3, '0', STR_PAD_LEFT);
                        return [
                            'id' => "TRX{$datePart}{$kodeDinas}{$nomorUrut}",
                            'price' => $transaksi->total_amount,
                            'quantity' => (int) $item->quantity,
                            'name' => $item->object->sub_rincian_objek,
                        ];
                    })->toArray(),
                ]),
                CURLOPT_HTTPHEADER => ['x-api-key: CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9PYUUYUY', 'Content-Type: application/json'],
            ]);
            $response = curl_exec($curl);

            if (curl_errno($curl)) {
                return response()->json(['status' => 500, 'message' => 'Curl Error: ' . curl_error($curl)]);
            }

            $data = json_decode($response);

            if (isset($data->codemessage) && $data->codemessage == '200') {
                $transaksi->update([
                    'due_date' => Carbon::now('Asia/Jakarta')->addMinutes(10),
                    'qr_url' => $data->data->qrimage ?? null,
                    'master_tipe_pembayaran_id' => $request->masterTipePembayaranId,
                ]);
                return response()->json(['status' => 200, 'message' => 'QRIS created', 'data' => $transaksi->fresh()]);
            }

            $errorMessage = $data->message ?? $data->codemessage ?? 'Gagal membuat QRIS. Silakan coba lagi.';
            return response()->json(['status' => 500, 'message' => $errorMessage]);
        }

        if ($request->masterTipePembayaranId == 2) { // VA
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api-retribusi.bapendalampung.cloud/paymentprod-va/create/CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9P',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode([
                    'no_reference' => $transaksi->reference_no,
                    'kode_dinas' => $transaksi->transaksiObject->first()->object->master_opd_id,
                    'nama_dinas' => $transaksi->transaksiObject->first()->object->opd->nama_opd,
                    'tgl_bayar' => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'total_bayar' => $transaksi->total_amount,
                    'items' => $transaksi->transaksiObject->map(function ($item, $index) use ($transaksi, $datePart, $kodeDinas) {
                        $nomorUrut = str_pad($index + 1, 3, '0', STR_PAD_LEFT);
                        return [
                            'id' => "TRX{$datePart}{$kodeDinas}{$nomorUrut}",
                            'price' => $item->total_amount,
                            'quantity' => $item->quantity,
                            'name' => $item->object->sub_rincian_objek,
                        ];
                    })->toArray(),
                ]),
                CURLOPT_HTTPHEADER => ['x-api-key: CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9PYUUYUY', 'Content-Type: application/json'],
            ]);
            $response = curl_exec($curl);

            if (curl_errno($curl)) {
                return response()->json(['status' => 500, 'message' => 'Curl Error: ' . curl_error($curl)]);
            }

            $data = json_decode($response);
            if (isset($data->codemessage) && $data->codemessage == '200') {
                $transaksi->update([
                    'due_date' => Carbon::now('Asia/Jakarta')->addHours(1),
                    'va' => $data->data->kodeva ?? null,
                    'master_tipe_pembayaran_id' => $request->masterTipePembayaranId,
                ]);
                return response()->json(['status' => 200, 'message' => 'VA created', 'data' => $transaksi->fresh()]);
            }
            $errorMessage = $data->message ?? $data->codemessage ?? 'Gagal membuat Virtual Account. Silakan coba lagi.';
            return response()->json(['status' => 500, 'message' => $errorMessage]);
        }

        if ($request->masterTipePembayaranId == 3) { // Kode Bayar
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api-retribusi.bapendalampung.cloud/paymentprod-va/create/CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9P',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode([
                    'no_reference' => $transaksi->reference_no,
                    'kode_dinas' => $transaksi->transaksiObject->first()->object->master_opd_id,
                    'nama_dinas' => $transaksi->transaksiObject->first()->object->opd->nama_opd,
                    'tgl_bayar' => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'total_bayar' => $transaksi->total_amount,
                    'items' => $transaksi->transaksiObject->map(function ($item, $index) use ($transaksi, $datePart, $kodeDinas) {
                        $nomorUrut = str_pad($index + 1, 3, '0', STR_PAD_LEFT);
                        $price = ($item->total_amount == 0 || $item->total_amount == '0') ? 1 : $item->total_amount;
                        return [
                            'id' => "TRX{$datePart}{$kodeDinas}{$nomorUrut}",
                            'price' => $price,
                            'quantity' => $item->quantity,
                            'name' => $item->object->sub_rincian_objek,
                        ];
                    })->toArray(),
                ]),
                CURLOPT_HTTPHEADER => ['x-api-key: CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9PYUUYUY', 'Content-Type: application/json'],
            ]);
            $response = curl_exec($curl);

            if (curl_errno($curl)) {
                return response()->json(['status' => 500, 'message' => 'Curl Error: ' . curl_error($curl)]);
            }

            $data = json_decode($response);
            if (isset($data->codemessage) && $data->codemessage == '200') {
                $transaksi->update([
                    'due_date' => Carbon::now('Asia/Jakarta')->addDays(1),
                    'kode_bayar' => isset($data->data->kodeva) ? substr($data->data->kodeva, 5) : null,
                    'master_tipe_pembayaran_id' => $request->masterTipePembayaranId,
                ]);
                return response()->json(['status' => 200, 'message' => 'Kode Bayar created', 'data' => $transaksi->fresh()]);
            }
            $errorMessage = $data->message ?? $data->codemessage ?? 'Gagal membuat Kode Bayar. Silakan coba lagi.';
            return response()->json(['status' => 500, 'message' => $errorMessage]);
        }

        return response()->json(['status' => 400, 'message' => 'Invalid channel']);
    }
}
