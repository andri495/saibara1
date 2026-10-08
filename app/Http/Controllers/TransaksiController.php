<?php

namespace App\Http\Controllers;

use App\Models\CartObject;
use App\Models\Document;
use App\Models\InstallmentTransaction;
use App\Models\MasterOpd;
use App\Models\RetributionTargets;
use App\Models\Revenue;
use App\Models\Transaksi;
use App\Models\TransaksiObjek;
use App\Models\User;
use App\Models\MasterChief;
use App\Services\ExchangeRateService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Ramsey\Uuid\Uuid;
use Tymon\JWTAuth\Facades\JWTAuth;

class TransaksiController extends Controller
{
    protected $exchangeRate;

    public function __construct(ExchangeRateService $exchangeRate)
    {
        $this->exchangeRate = $exchangeRate;
    }

    private function logPayment($level, $message, array $context = [])
    {
        try {
            Log::channel('payment')->$level($message, $context);
        } catch (\Throwable $e) {
            try {
                Log::build([
                    'driver' => 'single',
                    'path' => storage_path('logs/payment.log'),
                ])->$level($message, $context);
            } catch (\Throwable $ex) {
                Log::$level($message, $context);
            }
        }
    }

    public function getDashboard(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            // 1. Jumlah transaksi dengan semua status (tidak termasuk installment)
            $allStatuses = DB::table('master_payment_status')
                ->pluck('status_pembayaran', 'id');

            $totalTransaksiData = Transaksi::join('master_payment_status', 'transactions.master_status_pembayaran_id', '=', 'master_payment_status.id')
                ->where(function ($query) {
                    $query->where('transactions.is_installment', '!=', 1)
                        ->orWhereNull('transactions.is_installment');
                })
                ->when($request->has('start_date') && $request->start_date != null, function ($query) use ($request) {
                    $query->whereRaw('DATE(COALESCE(transactions.payment_date, transactions.registration_date)) >= ?', [$request->start_date]);
                })
                ->when($request->has('end_date') && $request->end_date != null, function ($query) use ($request) {
                    $query->whereRaw('DATE(COALESCE(transactions.payment_date, transactions.registration_date)) <= ?', [$request->end_date]);
                })
                ->selectRaw('master_payment_status.status_pembayaran, COUNT(*) as count')
                ->groupBy('master_payment_status.status_pembayaran')
                ->get()
                ->pluck('count', 'status_pembayaran');

            // Isi semua status dengan 0 jika tidak ada data
            $totalTransaksi = collect();
            foreach ($allStatuses as $status) {
                $totalTransaksi[$status] = $totalTransaksiData->get($status, 0);
            }

            // 2. Jumlah transaksi installment dengan semua status
            $totalInstallmentData = Transaksi::join('master_payment_status', 'transactions.master_status_pembayaran_id', '=', 'master_payment_status.id')
                ->where('transactions.is_installment', 1)
                ->when($request->has('start_date') && $request->start_date != null, function ($query) use ($request) {
                    $query->whereRaw('DATE(COALESCE(transactions.payment_date, transactions.registration_date)) >= ?', [$request->start_date]);
                })
                ->when($request->has('end_date') && $request->end_date != null, function ($query) use ($request) {
                    $query->whereRaw('DATE(COALESCE(transactions.payment_date, transactions.registration_date)) <= ?', [$request->end_date]);
                })
                ->selectRaw('master_payment_status.status_pembayaran, COUNT(*) as count')
                ->groupBy('master_payment_status.status_pembayaran')
                ->get()
                ->pluck('count', 'status_pembayaran');

            // Isi semua status dengan 0 jika tidak ada data
            $totalInstallment = collect();
            foreach ($allStatuses as $status) {
                $totalInstallment[$status] = $totalInstallmentData->get($status, 0);
            }

            // 3. Jumlah total pendapatan setiap OPD (transaksi lunas + installment lunas)
            // Status pembayaran lunas biasanya id = 3
            $pendapatanOpd = TransaksiObjek::join('master_objek', 'transaction_objects.object_id', '=', 'master_objek.id')
                ->join('master_opd', 'master_objek.master_opd_id', '=', 'master_opd.id')
                ->join('transactions', 'transaction_objects.transaction_id', '=', 'transactions.reference_no')
                ->where('transactions.master_status_pembayaran_id', 3)
                ->whereNotNull('transactions.payment_date')
                ->whereIn('master_objek.master_retribusi_id', [1, 2, 3])
                ->when($request->has('start_date') && $request->has('end_date') && $request->start_date != null && $request->end_date != null, function ($query) use ($request) {
                    $query->whereBetween('transactions.payment_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
                })
                ->selectRaw('master_opd.id as opd_id, master_opd.nama_opd, SUM(transaction_objects.total_amount) as total_pendapatan')
                ->groupBy('master_opd.id', 'master_opd.nama_opd')
                ->get();

            // Tambahkan pendapatan dari installment yang lunas (status 9)
            $pendapatanInstallment = InstallmentTransaction::join('transactions', 'installment_transactions.transaction_id', '=', 'transactions.reference_no')
                ->join('transaction_objects', function ($join) {
                    $join->on('transactions.reference_no', '=', 'transaction_objects.transaction_id')
                        ->whereRaw('transaction_objects.id = (SELECT MIN(id) FROM transaction_objects WHERE transaction_id = transactions.reference_no)');
                })
                ->join('master_objek', 'transaction_objects.object_id', '=', 'master_objek.id')
                ->join('master_opd', 'master_objek.master_opd_id', '=', 'master_opd.id')
                ->where('installment_transactions.master_status_pembayaran_id', 9)
                ->whereNotNull('installment_transactions.paid_date')
                ->whereIn('master_objek.master_retribusi_id', [1, 2, 3])
                ->when($request->has('start_date') && $request->has('end_date') && $request->start_date != null && $request->end_date != null, function ($query) use ($request) {
                    $query->whereBetween('installment_transactions.paid_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
                })
                ->selectRaw('master_opd.id as opd_id, master_opd.nama_opd, SUM(installment_transactions.grand_total) as total_pendapatan')
                ->groupBy('master_opd.id', 'master_opd.nama_opd')
                ->get();

            // Gabungkan pendapatan transaksi dan installment
            $pendapatanPerOpd = $pendapatanOpd->map(function ($item) use ($pendapatanInstallment) {
                $installment = $pendapatanInstallment->firstWhere('opd_id', $item->opd_id);

                return [
                    'opd_id' => $item->opd_id,
                    'nama_opd' => $item->nama_opd,
                    'total_pendapatan' => $item->total_pendapatan + ($installment ? $installment->total_pendapatan : 0),
                ];
            });

            // Tambahkan OPD yang hanya ada di installment
            $pendapatanInstallment->each(function ($item) use (&$pendapatanPerOpd) {
                if (! $pendapatanPerOpd->contains('opd_id', $item->opd_id)) {
                    $pendapatanPerOpd->push([
                        'opd_id' => $item->opd_id,
                        'nama_opd' => $item->nama_opd,
                        'total_pendapatan' => $item->total_pendapatan,
                    ]);
                }
            });

            // 4. List objek 10 teratas dengan jumlah transaksi terbanyak
            $topObjek = TransaksiObjek::join('master_objek', 'transaction_objects.object_id', '=', 'master_objek.id')
                ->join('transactions', 'transaction_objects.transaction_id', '=', 'transactions.reference_no')
                ->when($request->has('start_date') && $request->start_date != null, function ($query) use ($request) {
                    $query->whereRaw('DATE(COALESCE(transactions.payment_date, transactions.registration_date)) >= ?', [$request->start_date]);
                })
                ->when($request->has('end_date') && $request->end_date != null, function ($query) use ($request) {
                    $query->whereRaw('DATE(COALESCE(transactions.payment_date, transactions.registration_date)) <= ?', [$request->end_date]);
                })
                ->selectRaw('master_objek.id, master_objek.sub_rincian_objek, master_objek.rincian_objek, COUNT(*) as jumlah_transaksi')
                ->groupBy('master_objek.id', 'master_objek.sub_rincian_objek', 'master_objek.rincian_objek')
                ->orderByDesc('jumlah_transaksi')
                ->limit(10)
                ->get();

            // Hitung total keseluruhan
            $grandTotalTransaksi = $totalTransaksi->sum();
            $grandTotalInstallment = $totalInstallment->sum();
            $grandTotalPendapatan = $pendapatanPerOpd->sum('total_pendapatan');
            $grandTotalTransaksiObjek = TransaksiObjek::join('transactions', 'transaction_objects.transaction_id', '=', 'transactions.reference_no')
                ->when($request->has('start_date') && $request->start_date != null, function ($query) use ($request) {
                    $query->whereRaw('DATE(COALESCE(transactions.payment_date, transactions.registration_date)) >= ?', [$request->start_date]);
                })
                ->when($request->has('end_date') && $request->end_date != null, function ($query) use ($request) {
                    $query->whereRaw('DATE(COALESCE(transactions.payment_date, transactions.registration_date)) <= ?', [$request->end_date]);
                })
                ->count();

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved dashboard data',
                'data' => [
                    'total_transaksi' => $totalTransaksi,
                    'grand_total_transaksi' => $grandTotalTransaksi,
                    'total_installment' => $totalInstallment,
                    'grand_total_installment' => $grandTotalInstallment,
                    'pendapatan_per_opd' => $pendapatanPerOpd,
                    'grand_total_pendapatan' => $grandTotalPendapatan,
                    'top_10_objek' => $topObjek,
                    'grand_total_transaksi_objek' => $grandTotalTransaksiObjek,
                ],
            ]);
        }

        return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
    }

    public function getDashboardByOpd(Request $request, $opdId)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            // Validasi OPD exists
            $opdExists = DB::table('master_opd')->where('id', $opdId)->exists();
            if (! $opdExists) {
                return response()->json([
                    'status' => 404,
                    'message' => 'OPD not found',
                ]);
            }

            // Get OPD info
            $opdInfo = DB::table('master_opd')->where('id', $opdId)->first();

            // 1. Jumlah transaksi dengan semua status untuk OPD tertentu (tidak termasuk installment)
            $allStatuses = DB::table('master_payment_status')
                ->pluck('status_pembayaran', 'id');

            $totalTransaksiData = Transaksi::join('master_payment_status', 'transactions.master_status_pembayaran_id', '=', 'master_payment_status.id')
                ->join('transaction_objects', 'transactions.reference_no', '=', 'transaction_objects.transaction_id')
                ->join('master_objek', 'transaction_objects.object_id', '=', 'master_objek.id')
                ->where('master_objek.master_opd_id', $opdId)
                ->where(function ($query) {
                    $query->where('transactions.is_installment', '!=', 1)
                        ->orWhereNull('transactions.is_installment');
                })
                ->when($request->has('start_date') && $request->start_date != null, function ($query) use ($request) {
                    $query->whereRaw('DATE(COALESCE(transactions.payment_date, transactions.registration_date)) >= ?', [$request->start_date]);
                })
                ->when($request->has('end_date') && $request->end_date != null, function ($query) use ($request) {
                    $query->whereRaw('DATE(COALESCE(transactions.payment_date, transactions.registration_date)) <= ?', [$request->end_date]);
                })
                ->selectRaw('master_payment_status.status_pembayaran, COUNT(DISTINCT transactions.reference_no) as count')
                ->groupBy('master_payment_status.status_pembayaran')
                ->get()
                ->pluck('count', 'status_pembayaran');

            // Isi semua status dengan 0 jika tidak ada data
            $totalTransaksi = collect();
            foreach ($allStatuses as $status) {
                $totalTransaksi[$status] = $totalTransaksiData->get($status, 0);
            }

            // 2. Jumlah transaksi installment dengan semua status untuk OPD tertentu
            //    Diambil dari JUMLAH TRANSAKSI yang is_installment = 1 dan punya installment_transactions
            $totalInstallmentData = Transaksi::join('installment_transactions', 'transactions.reference_no', '=', 'installment_transactions.transaction_id')
                ->join('master_payment_status', 'installment_transactions.master_status_pembayaran_id', '=', 'master_payment_status.id')
                ->join('transaction_objects', 'transactions.reference_no', '=', 'transaction_objects.transaction_id')
                ->join('master_objek', 'transaction_objects.object_id', '=', 'master_objek.id')
                ->where('master_objek.master_opd_id', $opdId)
                ->where('transactions.is_installment', 1)
                ->when($request->has('start_date') && $request->start_date != null, function ($query) use ($request) {
                    $query->whereRaw('DATE(COALESCE(installment_transactions.paid_date, installment_transactions.created_at)) >= ?', [$request->start_date]);
                })
                ->when($request->has('end_date') && $request->end_date != null, function ($query) use ($request) {
                    $query->whereRaw('DATE(COALESCE(installment_transactions.paid_date, installment_transactions.created_at)) <= ?', [$request->end_date]);
                })
                ->selectRaw('master_payment_status.status_pembayaran, COUNT(DISTINCT transactions.reference_no) as count')
                ->groupBy('master_payment_status.status_pembayaran')
                ->get()
                ->pluck('count', 'status_pembayaran');

            // Isi semua status dengan 0 jika tidak ada data
            $totalInstallment = collect();
            foreach ($allStatuses as $status) {
                $totalInstallment[$status] = $totalInstallmentData->get($status, 0);
            }

            // 3. Total pendapatan OPD (transaksi lunas + installment lunas)
            // Status pembayaran lunas biasanya id = 3
            $pendapatanTransaksi = TransaksiObjek::join('master_objek', 'transaction_objects.object_id', '=', 'master_objek.id')
                ->join('transactions', 'transaction_objects.transaction_id', '=', 'transactions.reference_no')
                ->where('master_objek.master_opd_id', $opdId)
                ->where('transactions.master_status_pembayaran_id', 3)
                ->whereNotNull('transactions.payment_date')
                ->whereIn('master_objek.master_retribusi_id', [1, 2, 3])
                ->when($request->has('start_date') && $request->has('end_date') && $request->start_date != null && $request->end_date != null, function ($query) use ($request) {
                    $query->whereBetween('transactions.payment_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
                })
                ->sum('transaction_objects.total_amount');

            // Tambahkan pendapatan dari installment yang lunas (status 9)
            $pendapatanInstallment = InstallmentTransaction::join('transactions', 'installment_transactions.transaction_id', '=', 'transactions.reference_no')
                ->join('transaction_objects', function ($join) {
                    $join->on('transactions.reference_no', '=', 'transaction_objects.transaction_id')
                        ->whereRaw('transaction_objects.id = (SELECT MIN(id) FROM transaction_objects WHERE transaction_id = transactions.reference_no)');
                })
                ->join('master_objek', 'transaction_objects.object_id', '=', 'master_objek.id')
                ->where('master_objek.master_opd_id', $opdId)
                ->where('installment_transactions.master_status_pembayaran_id', 9)
                ->whereNotNull('installment_transactions.paid_date')
                ->whereIn('master_objek.master_retribusi_id', [1, 2, 3])
                ->when($request->has('start_date') && $request->has('end_date') && $request->start_date != null && $request->end_date != null, function ($query) use ($request) {
                    $query->whereBetween('installment_transactions.paid_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
                })
                ->sum('installment_transactions.grand_total');

            $totalPendapatan = $pendapatanTransaksi + $pendapatanInstallment;

            // 4. List objek 10 teratas dengan jumlah transaksi terbanyak untuk OPD tertentu
            $topObjek = TransaksiObjek::join('master_objek', 'transaction_objects.object_id', '=', 'master_objek.id')
                ->join('transactions', 'transaction_objects.transaction_id', '=', 'transactions.reference_no')
                ->where('master_objek.master_opd_id', $opdId)
                ->when($request->has('start_date') && $request->start_date != null, function ($query) use ($request) {
                    $query->whereRaw('DATE(COALESCE(transactions.payment_date, transactions.registration_date)) >= ?', [$request->start_date]);
                })
                ->when($request->has('end_date') && $request->end_date != null, function ($query) use ($request) {
                    $query->whereRaw('DATE(COALESCE(transactions.payment_date, transactions.registration_date)) <= ?', [$request->end_date]);
                })
                ->selectRaw('master_objek.id, master_objek.sub_rincian_objek, master_objek.rincian_objek, COUNT(*) as jumlah_transaksi')
                ->groupBy('master_objek.id', 'master_objek.sub_rincian_objek', 'master_objek.rincian_objek')
                ->orderByDesc('jumlah_transaksi')
                ->limit(10)
                ->get();

            // Hitung total keseluruhan untuk OPD ini
            $grandTotalTransaksi = $totalTransaksi->sum();
            $grandTotalInstallment = $totalInstallment->sum();
            $grandTotalTransaksiObjek = TransaksiObjek::join('master_objek', 'transaction_objects.object_id', '=', 'master_objek.id')
                ->join('transactions', 'transaction_objects.transaction_id', '=', 'transactions.reference_no')
                ->where('master_objek.master_opd_id', $opdId)
                ->when($request->has('start_date') && $request->start_date != null, function ($query) use ($request) {
                    $query->whereRaw('DATE(COALESCE(transactions.payment_date, transactions.registration_date)) >= ?', [$request->start_date]);
                })
                ->when($request->has('end_date') && $request->end_date != null, function ($query) use ($request) {
                    $query->whereRaw('DATE(COALESCE(transactions.payment_date, transactions.registration_date)) <= ?', [$request->end_date]);
                })
                ->count();

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved dashboard data for OPD',
                'data' => [
                    'opd_info' => [
                        'id' => $opdInfo->id,
                        'nama_opd' => $opdInfo->nama_opd,
                        'kode_opd' => $opdInfo->kode_opd ?? null,
                    ],
                    'total_transaksi' => $totalTransaksi,
                    'grand_total_transaksi' => $grandTotalTransaksi,
                    'total_installment' => $totalInstallment,
                    'grand_total_installment' => $grandTotalInstallment,
                    'total_pendapatan' => $totalPendapatan,
                    'top_10_objek' => $topObjek,
                    'grand_total_transaksi_objek' => $grandTotalTransaksiObjek,
                ],
            ]);
        }

        return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
    }

    public function getTransaksiByOpdAndStatus(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            // Build query
            $query = Transaksi::with([
                'transaksiObject.object.opd',
                'transaksiObject.object.retribusi',
                'transaksiObject.object.rincianRetribusi',
                'transaksiObject.object.lokasi',
                'transaksiObject.object.satuan',
                'transaksiObject.object.tpj',
                'transaksiObject.object.statusObjek',
                'transaksiObject.object.typeObject',
                'statusPembayaran',
                'tipePembayaran',
                'subject',
            ])
                // Filter for non-installment transactions only
                ->where(function ($q) {
                    $q->where('is_installment', '!=', 1)
                        ->orWhereNull('is_installment');
                });

            // Filter by opd_id if provided
            if ($request->has('opd_id') && $request->opd_id != null) {
                $query->whereHas('transaksiObject.object', function ($q) use ($request) {
                    $q->where('master_opd_id', $request->opd_id);
                });
            }

            // Filter by master_status_pembayaran_id if provided
            if ($request->has('master_status_pembayaran_id') && $request->master_status_pembayaran_id != null) {
                $query->where('master_status_pembayaran_id', $request->master_status_pembayaran_id);
            }

            // Filter date range if provided
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereRaw('DATE(COALESCE(payment_date, registration_date)) BETWEEN ? AND ?', [$request->start_date, $request->end_date]);
            }

            // Get results ordered by latest with pagination
            $transaksi = $query->orderByRaw('COALESCE(payment_date, registration_date) DESC')->paginate(8);

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved transactions',
                'data' => $transaksi,
            ]);
        }

        return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
    }

    public function getInstallmentTransaksiByOpdAndStatus(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            // Build query
            $query = Transaksi::with([
                'transaksiObject.object.opd',
                'transaksiObject.object.retribusi',
                'transaksiObject.object.rincianRetribusi',
                'transaksiObject.object.lokasi',
                'transaksiObject.object.satuan',
                'transaksiObject.object.tpj',
                'transaksiObject.object.statusObjek',
                'transaksiObject.object.typeObject',
                'statusPembayaran',
                'tipePembayaran',
                'installmentTransaction',
                'subject',
            ])
                // Filter for installment transactions only, and ensure installment list is not empty
                ->where('is_installment', 1)
                ->whereHas('installmentTransaction');

            // Filter by opd_id if provided
            if ($request->has('opd_id') && $request->opd_id != null) {
                $query->whereHas('transaksiObject.object', function ($q) use ($request) {
                    $q->where('master_opd_id', $request->opd_id);
                });
            }

            // Filter by master_status_pembayaran_id if provided (filter by transaction status)
            if ($request->has('master_status_pembayaran_id') && $request->master_status_pembayaran_id != null) {
                $query->where('master_status_pembayaran_id', $request->master_status_pembayaran_id);
            }

            // Filter by date range if provided
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereRaw('DATE(COALESCE(payment_date, registration_date)) BETWEEN ? AND ?', [$request->start_date, $request->end_date]);
            }

            // Get results ordered by latest with pagination
            $transaksi = $query->orderByRaw('COALESCE(payment_date, registration_date) DESC')->paginate(8);

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved installment transactions',
                'data' => $transaksi,
            ]);
        }

        return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
    }

    public function getTransaksiWithoutObject(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            $query = Transaksi::with([
                'statusPembayaran',
                'tipePembayaran',
                'subject',
                'installmentTransaction',
                'transaksiObject',
            ])
                ->whereDoesntHave('transaksiObject');

            if ($request->has('master_status_pembayaran_id') && $request->master_status_pembayaran_id != null) {
                $query->where('master_status_pembayaran_id', $request->master_status_pembayaran_id);
            }

            $transaksi = $query->orderBy('created_at', 'desc')->paginate(8);

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved transactions without objects',
                'data' => $transaksi,
            ]);
        }

        return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
    }

    public function getTransaksiAndStatus(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            // Build query
            $query = Transaksi::with([
                'transaksiObject.object.opd',
                'transaksiObject.object.retribusi',
                'transaksiObject.object.rincianRetribusi',
                'transaksiObject.object.lokasi',
                'transaksiObject.object.satuan',
                'transaksiObject.object.tpj',
                'transaksiObject.object.statusObjek',
                'transaksiObject.object.typeObject',
                'statusPembayaran',
                'tipePembayaran',
                'subject',
            ])
                // Filter for non-installment transactions only
                ->where(function ($q) {
                    $q->where('is_installment', '!=', 1)
                        ->orWhereNull('is_installment');
                });

            // Filter by opd_id if provided
            if ($request->has('opd_id') && $request->opd_id != null) {
                $query->whereHas('transaksiObject.object', function ($q) use ($request) {
                    $q->where('master_opd_id', $request->opd_id);
                });
            }

            // Filter by master_status_pembayaran_id if provided
            if ($request->has('master_status_pembayaran_id') && $request->master_status_pembayaran_id != null) {
                $query->where('master_status_pembayaran_id', $request->master_status_pembayaran_id);
            }

            // Get results ordered by latest with pagination
            $transaksi = $query->orderBy('created_at', 'desc')->paginate(8);

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved transactions',
                'data' => $transaksi,
            ]);
        }

        return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
    }

    public function getTransaksiAllPerOPD(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            // Build query
            $query = Transaksi::with([
                'transaksiObject.object.opd',
                'transaksiObject.object.retribusi',
                'transaksiObject.object.rincianRetribusi',
                'transaksiObject.object.lokasi',
                'transaksiObject.object.satuan',
                'transaksiObject.object.tpj',
                'transaksiObject.object.statusObjek',
                'transaksiObject.object.typeObject',
                'statusPembayaran',
                'tipePembayaran',
                'subject',
                'installmentTransaction',

            ]);

            // Filter by opd_id if provided
            if ($request->has('opd_id') && $request->opd_id != null) {
                $query->whereHas('transaksiObject.object', function ($q) use ($request) {
                    $q->where('master_opd_id', $request->opd_id);
                });
            }

            // Filter by master_status_pembayaran_id if provided
            if ($request->has('master_status_pembayaran_id') && $request->master_status_pembayaran_id != null) {
                $query->where('master_status_pembayaran_id', $request->master_status_pembayaran_id);
            }

            // Filter date range if provided
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('registration_date', [$request->start_date, $request->end_date]);
            }
            // Get results ordered by latest with pagination
            $transaksi = $query->orderBy('created_at', 'desc')->paginate(8);

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved installment transactions',
                'data' => $transaksi,
            ]);
        }

        return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
    }

    public function createTransaksiObjek(Request $request)
    {

        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            // Validate transaksi_objek before proceeding
            $transaksiObjek = $request->transaksi_objek;
            if (empty($transaksiObjek)) {
                return response()->json([
                    'status' => 400,
                    'message' => 'transaksi_objek is required and cannot be empty',
                ], 400);
            }

            // Handle file upload
            $documentPath = null;
            if ($request->hasFile('document_support_link')) {
                $file = $request->file('document_support_link');
                $extension = $file->getClientOriginalExtension();
                $filename = Str::random(40) . '.' . $extension;
                $documentPath = 'private/documents/' . $filename;
                Storage::disk('local')->put($documentPath, file_get_contents($file));
            } else {
                $documentPath = $request->document_support_link;
            }
            $va = $request->va;
            if ($request->va == '1') {
                $va = substr(str_shuffle(str_repeat('123456789', 10)), 0, 10);
            }
            $uuid1 = Uuid::uuid1();
            $uuid = substr($uuid1, 0, 22);

            try {
                DB::beginTransaction();

                $transaksi = Transaksi::create([
                    'reference_no' => $uuid,
                    'subject_id' => $user->id,
                    'npwrd' => $request->npwrd,
                    'subject_name' => $request->subject_name,
                    'subject_address' => $request->subject_address,
                    'nama_instansi' => $request->nama_instansi,
                    'no_telp' => $request->no_telp,
                    'penalty_amount' => $request->penalty_amount,
                    'total_amount' => $request->total_amount,
                    'registration_date' => $request->registration_date,
                    'payment_date' => null,
                    'due_date' => $request->due_date,
                    'document_support_link' => $documentPath,
                    'transaction_type' => $request->transaction_type,
                    'master_tipe_pembayaran_id' => $request->master_tipe_pembayaran_id,
                    'transaction_response' => $request->transaction_response,
                    'master_status_pembayaran_id' => $request->master_status_pembayaran_id,
                    'va' => $va,
                    'installment_completed' => 0,
                ]);
                if (is_array($transaksiObjek)) {
                    foreach ($transaksiObjek as $index => $productData) {
                        TransaksiObjek::create([
                            'transaction_id' => $transaksi->reference_no,
                            'object_id' => $productData['object_id'],
                            'quantity' => $productData['quantity'],
                            'tpj_quantity' => $productData['tpj_quantity'],
                            'satuan_quantity' => $productData['satuan_quantity'],
                            'total_amount' => $productData['total_amount'],
                            'document_support_link' => $productData['document_support_link'],
                            'document_payment_link' => null,
                            'document_result' => null,
                            'start_date' => $productData['start_date'],
                            'end_date' => $productData['end_date'],
                        ]);
                    }
                } else {
                    $data = json_decode($request->transaksi_objek, true);
                    TransaksiObjek::create([
                        'transaction_id' => $transaksi->reference_no,
                        'object_id' => $data['object_id'],
                        'quantity' => $data['quantity'],
                        'tpj_quantity' => $data['tpj_quantity'],
                        'satuan_quantity' => $data['satuan_quantity'],
                        'total_amount' => $data['total_amount'],
                        'document_support_link' => $documentPath,
                        'document_payment_link' => null,
                        'document_result' => null,
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                    ]);

                    $transaksi = Transaksi::with(['transaksiObject.object'])->where('reference_no', $uuid)->first();

                    $transactionObject = $transaksi->transaksiObject->first();
                    $satuanId = $transactionObject->object->master_satuan_id ?? null;
                    $isRumdin = $transactionObject->object->master_type_object_id;

                    // bulanan
                    if (($satuanId == 5 && $isRumdin == 4) || ($satuanId == 5 && $isRumdin == 12)) {
                        $transaksi->update(['is_installment' => 1]);
                        $date1 = Carbon::parse($transactionObject->start_date);
                        $date2 = Carbon::parse($transactionObject->end_date);

                        // Calculate full months
                        $fullMonths = $date1->diffInMonths($date2);

                        // Check if there are remaining days after full months
                        $tempDate = $date1->copy()->addMonths($fullMonths);
                        $hasPartialMonth = $tempDate->lt($date2);

                        // Add 1 installment for partial month if exists
                        $installmentTotal = max(1, $fullMonths + ($hasPartialMonth ? 1 : 0));
                        $currentStartDate = $date1->copy();
                        for ($i = 0; $i < $installmentTotal; $i++) {
                            $currentEndDate = $currentStartDate->copy()->addMonths(1);
                            $penaltyDate = $currentEndDate->copy()->addDays(16);
                            $installmentUUid = Uuid::uuid1();
                            $installmentRefId = substr($installmentUUid, 0, 22);
                            $paymentAmount = $transactionObject->total_amount / $installmentTotal;
                            $now = Carbon::now('Asia/Jakarta');
                            $monthsPassed = $now->gt($penaltyDate)
                                ? (int) $penaltyDate->diffInMonths($now) + 1
                                : 0;
                            $penaltyAmount = $paymentAmount * 0.01 * $monthsPassed;
                            $nextPenaltyDate = $monthsPassed > 0
                                ? $penaltyDate->copy()->addMonths($monthsPassed)
                                : $penaltyDate;
                            InstallmentTransaction::create([
                                'installment_ref_id' => $installmentRefId,
                                'installment_total' => $installmentTotal,
                                'installment_index' => $i + 1,
                                'transaction_id' => $transaksi->reference_no,
                                'payment_amount' => $paymentAmount,
                                'penalty_amount' => $penaltyAmount,
                                'grand_total' => $paymentAmount + $penaltyAmount,
                                'master_status_pembayaran_id' => 8,
                                'start_date' => $currentStartDate,
                                'end_date' => $currentEndDate,
                                'next_penalty_date' => $nextPenaltyDate,

                            ]);
                            $currentStartDate = $currentEndDate->copy();
                        }
                    }
                    // triwulan
                    if (($satuanId == 4 && $isRumdin == 4) || ($satuanId == 4 && $isRumdin == 12)) {
                        $transaksi->update(['is_installment' => 1]);
                        $date1 = Carbon::parse($transactionObject->start_date);
                        $date2 = Carbon::parse($transactionObject->end_date);

                        // Calculate full quarters (3 months each)
                        $totalMonths = $date1->diffInMonths($date2);
                        $fullQuarters = intval($totalMonths / 3);

                        // Check if there are remaining months/days after full quarters
                        $tempDate = $date1->copy()->addMonths($fullQuarters * 3);
                        $hasPartialQuarter = $tempDate->lt($date2);

                        // Add 1 installment for partial quarter if exists
                        $installmentTotal = max(1, $fullQuarters + ($hasPartialQuarter ? 1 : 0));
                        $currentStartDate = $date1->copy();
                        for ($i = 0; $i < $installmentTotal; $i++) {
                            $currentEndDate = $currentStartDate->copy()->addMonths(3);
                            $penaltyDate = $currentEndDate->copy()->addDays(16);
                            $installmentUUid = Uuid::uuid1();
                            $installmentRefId = substr($installmentUUid, 0, 22);
                            $paymentAmount = $transactionObject->total_amount / $installmentTotal;
                            $now = Carbon::now('Asia/Jakarta');
                            $monthsPassed = $now->gt($penaltyDate)
                                ? (int) $penaltyDate->diffInMonths($now) + 1
                                : 0;
                            $penaltyAmount = $paymentAmount * 0.01 * $monthsPassed;
                            $nextPenaltyDate = $monthsPassed > 0
                                ? $penaltyDate->copy()->addMonths($monthsPassed)
                                : $penaltyDate;
                            InstallmentTransaction::create([
                                'installment_ref_id' => $installmentRefId,
                                'installment_total' => $installmentTotal,
                                'installment_index' => $i + 1,
                                'transaction_id' => $transaksi->reference_no,
                                'payment_amount' => $paymentAmount,
                                'penalty_amount' => $penaltyAmount,
                                'grand_total' => $paymentAmount + $penaltyAmount,
                                'master_status_pembayaran_id' => 8,
                                'start_date' => $currentStartDate,
                                'end_date' => $currentEndDate,
                                'next_penalty_date' => $nextPenaltyDate,

                            ]);
                            $currentStartDate = $currentEndDate->copy();
                        }
                    }
                    // tahunan (paid monthly)
                    if (($satuanId == 6 && $isRumdin == 4) || ($satuanId == 6 && $isRumdin == 12)) {
                        $transaksi->update(['is_installment' => 1]);
                        $date1 = Carbon::parse($transactionObject->start_date);
                        $date2 = Carbon::parse($transactionObject->end_date);

                        // Calculate full months (yearly contract paid monthly)
                        $fullMonths = $date1->diffInMonths($date2);

                        // Check if there are remaining days after full months
                        $tempDate = $date1->copy()->addMonths($fullMonths);
                        $hasPartialMonth = $tempDate->lt($date2);

                        // Add 1 installment for partial month if exists
                        $installmentTotal = max(1, $fullMonths + ($hasPartialMonth ? 1 : 0));
                        $currentStartDate = $date1->copy();
                        for ($i = 0; $i < $installmentTotal; $i++) {
                            $currentEndDate = $currentStartDate->copy()->addMonths(1);
                            $penaltyDate = $currentEndDate->copy()->addDays(16);
                            $installmentUUid = Uuid::uuid1();
                            $installmentRefId = substr($installmentUUid, 0, 22);
                            $paymentAmount = $transactionObject->total_amount / $installmentTotal;
                            $now = Carbon::now('Asia/Jakarta');
                            $monthsPassed = $now->gt($penaltyDate)
                                ? (int) $penaltyDate->diffInMonths($now) + 1
                                : 0;
                            $penaltyAmount = $paymentAmount * 0.01 * $monthsPassed;
                            $nextPenaltyDate = $monthsPassed > 0
                                ? $penaltyDate->copy()->addMonths($monthsPassed)
                                : $penaltyDate;
                            InstallmentTransaction::create([
                                'installment_ref_id' => $installmentRefId,
                                'installment_total' => $installmentTotal,
                                'installment_index' => $i + 1,
                                'transaction_id' => $transaksi->reference_no,
                                'payment_amount' => $paymentAmount,
                                'penalty_amount' => $penaltyAmount,
                                'grand_total' => $paymentAmount + $penaltyAmount,
                                'master_status_pembayaran_id' => 8,
                                'start_date' => $currentStartDate,
                                'end_date' => $currentEndDate,
                                'next_penalty_date' => $nextPenaltyDate,

                            ]);
                            $currentStartDate = $currentEndDate->copy();
                        }
                    }
                }

                if ($request->cart_id != null) {
                    CartObject::where('cart_id', $request->cart_id)->delete();
                }

                $transaksi = Transaksi::with(['transaksiObject.object.opd', 'transaksiObject.object.lokasi', 'transaksiObject.object.retribusi', 'transaksiObject.object'])->where('reference_no', $uuid)->first();

                // Send OneSignal notification

                $master_location_id = $transaksi->transaksiObject->first()->object->master_lokasi_id ?? null;
                $type = ($transaksi->is_installment == 1) ? 'new_installment' : 'new_transaction';
                $this->sendNotificationtoAdmin(
                    $transaksi->reference_no,
                    $master_location_id,
                    5,
                    $type,
                    'Berikan respon untuk transaksi!',
                    'Give response for the transaction!',
                    'Transaksi baru dengan nomor ' . $transaksi->reference_no . ' telah dibuat.',
                    'New Transaction with reference number ' . $transaksi->reference_no . ' has been created.',
                );

                DB::commit();

                return response()->json(['status' => 200, 'message' => 'Successfully send the request', 'data' => $transaksi]);
            } catch (\Throwable $th) {
                DB::rollBack();

                return response()->json([
                    'status' => 500,
                    'message' => 'Failed to create transaction',
                    'error' => $th->getMessage(),
                ], 500);
            }
        }

        return response()->json(['status' => 201, 'message' => "You're not authenticated", 'data' => []]);
    }

    public function createParkingTransaction(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            $transaksiObjek = $request->transaksi_objek;
            if (empty($transaksiObjek)) {
                return response()->json([
                    'status' => 400,
                    'message' => 'transaksi_objek is required and cannot be empty',
                ], 400);
            }

            $uuid1 = Uuid::uuid1();
            $uuid = substr($uuid1, 0, 22);

            try {
                DB::beginTransaction();

                $transaksi = Transaksi::create([
                    'reference_no' => $uuid,
                    'subject_id' => $user->id,
                    'npwrd' => $request->npwrd,
                    'subject_name' => $request->subject_name,
                    'subject_address' => $request->subject_address,
                    'nama_instansi' => $request->nama_instansi,
                    'no_telp' => $request->no_telp,
                    'penalty_amount' => 0,
                    'total_amount' => $request->total_amount,
                    'registration_date' => $request->registration_date,
                    'payment_date' => null,
                    'due_date' => null,
                    'document_support_link' => null,
                    'transaction_type' => $request->transaction_type,
                    'master_tipe_pembayaran_id' => 0,
                    'transaction_response' => $request->transaction_response,
                    'master_status_pembayaran_id' => $request->master_status_pembayaran_id,
                    'document_number' => $request->document_number,
                    'va' => null,
                    'is_installment' => 0,
                    'installment_completed' => 0,
                ]);

                foreach ($transaksiObjek as $productData) {
                    TransaksiObjek::create([
                        'transaction_id' => $transaksi->reference_no,
                        'object_id' => $productData['object_id'],
                        'quantity' => $productData['quantity'],
                        'tpj_quantity' => $productData['tpj_quantity'],
                        'satuan_quantity' => $productData['satuan_quantity'],
                        'total_amount' => $productData['total_amount'],
                        'document_support_link' => $productData['document_support_link'],
                        'document_payment_link' => null,
                        'document_result' => null,
                        'start_date' => $productData['start_date'],
                        'end_date' => $productData['end_date'],
                    ]);
                }

                $transaksi = Transaksi::with([
                    'transaksiObject.object.opd',
                    'transaksiObject.object.lokasi',
                    'transaksiObject.object.retribusi',
                    'transaksiObject.object',
                ])->where('reference_no', $uuid)->first();

                DB::commit();

                return response()->json([
                    'status' => 200,
                    'message' => 'Successfully created parking transaction',
                    'data' => $transaksi,
                ]);
            } catch (\Throwable $th) {
                DB::rollBack();

                return response()->json([
                    'status' => 500,
                    'message' => 'Failed to create parking transaction',
                    'error' => $th->getMessage(),
                ], 500);
            }
        }

        return response()->json(['status' => 401, 'message' => "You're not authenticated", 'data' => []]);
    }

    public function changeStatusTransaksi(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if ($user) {
            if ($request->status == 'cancelled_by_user') {
                Transaksi::where('reference_no', $request->id)->update([
                    'master_status_pembayaran_id' => 4,
                    'transaction_response' => $request->transaction_response,
                ]);
                $transaksi = Transaksi::with(['transaksiObject.object.opd', 'transaksiObject.object.lokasi', 'transaksiObject.object.retribusi', 'transaksiObject.object'])->where('reference_no', $request->id)->first();
                // If installment transaction is cancelled, return object status to 1 (available)
                if ($transaksi->is_installment == 1) {
                    foreach ($transaksi->transaksiObject as $transactionObject) {
                        if ($transactionObject->object->master_type_object_id == 4) {
                            $transactionObject->object->update([
                                'master_status_objek_id' => 1,
                            ]);
                        }
                    }
                }

                // Send OneSignal notification

                $master_location_id = $transaksi->transaksiObject->first()->object->master_lokasi_id ?? null;
                $type = ($transaksi->is_installment == 1) ? 'new_installment' : 'new_transaction';
                $this->sendNotificationtoAdmin(
                    $transaksi->reference_no,
                    $master_location_id,
                    5,
                    $type,
                    'Status Transaksi Berubah',
                    'Transaction Status Changed',
                    'Transaksi dengan nomor ' . $request->id . ' telah dibatalkan oleh wajib retribusi.',
                    'New Transaction with reference number ' . $request->id . ' has been cancelled by the Wajib Retribusi.',
                );
            }
            if ($request->status == 'cancelled_by_opd') {
                $transaksi = Transaksi::where('reference_no', $request->id)->update([
                    'master_status_pembayaran_id' => 5,
                    'transaction_response' => $request->transaction_response,
                ]);
                $transaksi = Transaksi::with(['subject', 'transaksiObject.object'])->where('reference_no', $request->id)->first();

                // If installment transaction is cancelled, return object status to 1 (available)
                if ($transaksi->is_installment == 1) {
                    foreach ($transaksi->transaksiObject as $transactionObject) {
                        if ($transactionObject->object->master_type_object_id == 4) {
                            $transactionObject->object->update([
                                'master_status_objek_id' => 1,
                            ]);
                        }
                    }
                }
                $type = ($transaksi->is_installment == 1) ? 'new_installment' : 'new_transaction';

                $this->sendNotificationtoUser(
                    $transaksi->reference_no,
                    $transaksi->subject_id,
                    $type,
                    'Status Transaksi Berubah',
                    'Transaction Status Changed',
                    'Transaksi dengan nomor ' . $request->id . ' telah dibatalkan oleh OPD.',
                    'New Transaction with reference number ' . $request->id . ' has been cancelled by the OPD.',
                );
            }
            if ($request->status == 'active_by_opd') {
                Transaksi::where('reference_no', $request->id)->update([
                    'master_status_pembayaran_id' => 2,
                ]);
                $transaksi = Transaksi::with(['subject', 'transaksiObject.object'])->where('reference_no', $request->id)->first();

                if ($transaksi->is_installment) {
                    Transaksi::where('reference_no', $request->id)->update([
                        'master_status_pembayaran_id' => 8,
                    ]);
                }

                if ($transaksi->is_installment == 1) {
                    foreach ($transaksi->transaksiObject as $transactionObject) {
                        if ($transactionObject->object->master_type_object_id == 4) {
                            $transactionObject->object->update([
                                'master_status_objek_id' => 0,
                            ]);
                        }
                    }
                }
                $type = ($transaksi->is_installment == 1) ? 'new_installment' : 'new_transaction';

                $this->sendNotificationtoUser(
                    $transaksi->reference_no,
                    $transaksi->subject_id,
                    $type,
                    'Status Transaksi Berubah',
                    'Transaction Status Changed',
                    'Transaksi dengan nomor ' . $request->id . ' telah diterima oleh OPD.',
                    'New Transaction with reference number ' . $request->id . ' has been accepted by the OPD.',
                );
            }
            if ($request->status == 'active_by_user') {
                if ($request->masterTipePembayaranId == 1) {
                    $transaksi = Transaksi::with(['transaksiObject.object.opd', 'transaksiObject.object.retribusi', 'transaksiObject.object'])->where('reference_no', $request->id)
                        ->first();
                    // return response()->json(['status' => 200, 'message' => 'Successfully Change Status', 'data' => $transaksi->transaksiObject->first()->object]);
                    $url = 'https://api-retribusi.bapendalampung.cloud/paymentprod/create_qris/CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9P';
                    $payload = [
                        'no_reference' => $transaksi->reference_no,
                        'kode_dinas' => $transaksi->transaksiObject->first()->object->master_opd_id,
                        'nama_dinas' => $transaksi->transaksiObject->first()->object->opd->nama_opd,
                        'tgl_bayar' => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                        'total_bayar' => $transaksi->total_amount,
                        'items' => $transaksi->transaksiObject->map(function ($item, $index) use ($transaksi) {
                            $datePart = Carbon::now('Asia/Jakarta')->format('dmy');
                            $kodeDinas = str_pad($transaksi->transaksiObject->first()->object->master_opd_id, 2, '0', STR_PAD_LEFT);
                            $nomorUrut = str_pad($index + 1, 3, '0', STR_PAD_LEFT);

                            return [
                                'id' => "TRX{$datePart}{$kodeDinas}{$nomorUrut}",
                                'price' => $transaksi->total_amount, // or amount field
                                'quantity' => (int) $item->quantity, // or qty field
                                'name' => $item->object->sub_rincian_objek, // or description field
                            ];
                        })->toArray(),
                    ];

                    $curl = curl_init();
                    curl_setopt_array($curl, [
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => json_encode($payload),
                        CURLOPT_HTTPHEADER => ['x-api-key: CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9PYUUYUY', 'Content-Type: application/json'],
                    ]);

                    $response = curl_exec($curl);
                    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    $curlErrno = curl_errno($curl);
                    $curlError = curl_error($curl);
                    curl_close($curl);

                    $this->logPayment('info', 'QRIS Response: ' . $response);

                    $data = json_decode($response);

                    if ($data && isset($data->codemessage) && $data->codemessage == '200') {
                        Transaksi::where('reference_no', $request->id)->update([
                            'due_date' => Carbon::now('Asia/Jakarta')->addMinutes(10),
                            'qr_url' => $data->data->qrimage,
                            'master_tipe_pembayaran_id' => $request->masterTipePembayaranId,
                        ]);
                        $transaksiObjek = Transaksi::where('reference_no', $request->id)
                            ->first();

                        return response()->json(['status' => 200, 'message' => 'Successfully Change Status', 'data' => $transaksiObjek]);
                    } else {
                        // Generate UUID baru seperti di createTransaksiObjek
                        $uuid1 = Uuid::uuid1();
                        $newUuid = substr($uuid1, 0, 22);

                        // Update reference_no dengan UUID baru
                        Transaksi::where('reference_no', $request->id)->update([
                            'reference_no' => $newUuid,
                        ]);

                        // Update transaction_id di TransaksiObjek
                        TransaksiObjek::where('transaction_id', $request->id)->update([
                            'transaction_id' => $newUuid,
                        ]);

                        $errorMessage = $data->message ?? ($curlError ?: 'Gagal memproses pembayaran QRIS');

                        $this->logPayment('error', 'setPayment [QRIS] Failed: ' . $errorMessage, [
                            'user_id' => $user->id,
                            'reference_no' => $request->id,
                            'new_reference_no' => $newUuid,
                            'master_tipe_pembayaran_id' => $request->masterTipePembayaranId,
                            'url' => $url,
                            'payload' => $payload,
                            'http_code' => $httpCode,
                            'curl_errno' => $curlErrno,
                            'curl_error' => $curlError,
                            'raw_response' => $response,
                            'response_data' => $data,
                            'error_message' => $errorMessage,
                        ]);

                        return response()->json(['status' => 500, 'message' => $errorMessage, 'new_reference_no' => $newUuid]);
                    }
                } elseif ($request->masterTipePembayaranId == 2) {
                    $transaksi = Transaksi::with(['transaksiObject.object.opd', 'transaksiObject.object.retribusi', 'transaksiObject.object'])->where('reference_no', $request->id)
                        ->first();
                    $url = 'https://api-retribusi.bapendalampung.cloud/paymentprod-va/create/CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9P';
                    $payload = [
                        'no_reference' => $transaksi->reference_no,
                        'kode_dinas' => $transaksi->transaksiObject->first()->object->master_opd_id,
                        'nama_dinas' => $transaksi->transaksiObject->first()->object->opd->nama_opd,
                        'tgl_bayar' => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                        'total_bayar' => $transaksi->total_amount,
                        'items' => $transaksi->transaksiObject->map(function ($item, $index) use ($transaksi) {
                            $datePart = Carbon::now('Asia/Jakarta')->format('dmy');
                            $kodeDinas = str_pad($transaksi->transaksiObject->first()->object->master_opd_id, 2, '0', STR_PAD_LEFT);
                            $nomorUrut = str_pad($index + 1, 3, '0', STR_PAD_LEFT);

                            return [
                                'id' => "TRX{$datePart}{$kodeDinas}{$nomorUrut}",
                                'price' => $item->total_amount, // or amount field
                                'quantity' => $item->quantity, // or qty field
                                'name' => $item->object->sub_rincian_objek, // or description field
                            ];
                        })->toArray(),
                    ];

                    $curl = curl_init();
                    curl_setopt_array($curl, [
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => json_encode($payload),
                        CURLOPT_HTTPHEADER => [
                            'x-api-key: CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9PYUUYUY',
                            'Content-Type: application/json',
                        ],
                    ]);

                    $response = curl_exec($curl);
                    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    $curlErrno = curl_errno($curl);
                    $curlError = curl_error($curl);
                    curl_close($curl);

                    $this->logPayment('info', 'VA Response: ' . $response);

                    $data = json_decode($response);

                    if ($data && isset($data->codemessage) && $data->codemessage == '200') {
                        Transaksi::where('reference_no', $request->id)->update([
                            'due_date' => Carbon::now('Asia/Jakarta')->addHours(1),
                            'va' => $data->data->kodeva,
                            'master_tipe_pembayaran_id' => $request->masterTipePembayaranId,
                        ]);
                        $transaksiObjek = Transaksi::where('reference_no', $request->id)
                            ->first();

                        return response()->json(['status' => 200, 'message' => 'Successfully Change Status', 'data' => $transaksiObjek]);
                    } else {
                        // Generate UUID baru seperti di createTransaksiObjek
                        $uuid1 = Uuid::uuid1();
                        $newUuid = substr($uuid1, 0, 22);

                        // Update reference_no dengan UUID baru
                        Transaksi::where('reference_no', $request->id)->update([
                            'reference_no' => $newUuid,
                        ]);

                        // Update transaction_id di TransaksiObjek
                        TransaksiObjek::where('transaction_id', $request->id)->update([
                            'transaction_id' => $newUuid,
                        ]);

                        $errorMessage = $data->message ?? ($curlError ?: 'Gagal memproses pembayaran Virtual Account');

                        $this->logPayment('error', 'setPayment [VA] Failed: ' . $errorMessage, [
                            'user_id' => $user->id,
                            'reference_no' => $request->id,
                            'new_reference_no' => $newUuid,
                            'master_tipe_pembayaran_id' => $request->masterTipePembayaranId,
                            'url' => $url,
                            'payload' => $payload,
                            'http_code' => $httpCode,
                            'curl_errno' => $curlErrno,
                            'curl_error' => $curlError,
                            'raw_response' => $response,
                            'response_data' => $data,
                            'error_message' => $errorMessage,
                        ]);

                        return response()->json(['status' => 500, 'message' => $errorMessage, 'new_reference_no' => $newUuid]);
                    }
                } elseif ($request->masterTipePembayaranId == 3) {
                    $transaksi = Transaksi::with(['transaksiObject.object.opd', 'transaksiObject.object.retribusi', 'transaksiObject.object'])->where('reference_no', $request->id)
                        ->first();
                    $url = 'https://api-retribusi.bapendalampung.cloud/paymentprod-va/create/CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9P';
                    $payload = [
                        'no_reference' => $transaksi->reference_no,
                        'kode_dinas' => $transaksi->transaksiObject->first()->object->master_opd_id,
                        'nama_dinas' => $transaksi->transaksiObject->first()->object->opd->nama_opd,
                        'tgl_bayar' => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                        'total_bayar' => $transaksi->total_amount,
                        'items' => $transaksi->transaksiObject->map(function ($item, $index) use ($transaksi) {
                            $datePart = Carbon::now('Asia/Jakarta')->format('dmy');
                            $kodeDinas = str_pad($transaksi->transaksiObject->first()->object->master_opd_id, 2, '0', STR_PAD_LEFT);
                            $nomorUrut = str_pad($index + 1, 3, '0', STR_PAD_LEFT);
                            if ($item->total_amount == 0 || $item->total_amount == '0') {
                                $item->total_amount = 1;
                            }

                            return [
                                'id' => "TRX{$datePart}{$kodeDinas}{$nomorUrut}",
                                'price' => $item->total_amount, // or amount field
                                'quantity' => $item->quantity, // or qty field
                                'name' => $item->object->sub_rincian_objek, // or description field
                            ];
                        })->toArray(),
                    ];

                    $curl = curl_init();
                    curl_setopt_array($curl, [
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => json_encode($payload),
                        CURLOPT_HTTPHEADER => [
                            'x-api-key: CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9PYUUYUY',
                            'Content-Type: application/json',
                        ],
                    ]);

                    $response = curl_exec($curl);
                    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    $curlErrno = curl_errno($curl);
                    $curlError = curl_error($curl);
                    curl_close($curl);

                    $this->logPayment('info', 'Kode Bayar Response: ' . $response);

                    $data = json_decode($response);

                    if ($data && isset($data->codemessage) && $data->codemessage == '200') {
                        Transaksi::where('reference_no', $request->id)->update([
                            'due_date' => Carbon::now('Asia/Jakarta')->addDays(1),
                            'kode_bayar' => substr($data->data->kodeva, 5),
                            'master_tipe_pembayaran_id' => $request->masterTipePembayaranId,
                        ]);
                        $transaksiObjek = Transaksi::where('reference_no', $request->id)
                            ->first();

                        return response()->json(['status' => 200, 'message' => 'Successfully Change Status', 'data' => $transaksiObjek]);
                    } else {
                        // Generate UUID baru seperti di createTransaksiObjek
                        $uuid1 = Uuid::uuid1();
                        $newUuid = substr($uuid1, 0, 22);

                        // Update reference_no dengan UUID baru
                        Transaksi::where('reference_no', $request->id)->update([
                            'reference_no' => $newUuid,
                        ]);

                        // Update transaction_id di TransaksiObjek
                        TransaksiObjek::where('transaction_id', $request->id)->update([
                            'transaction_id' => $newUuid,
                        ]);

                        $errorMessage = $data->message ?? ($curlError ?: 'Gagal memproses pembayaran Kode Bayar');

                        $this->logPayment('error', 'setPayment [Kode Bayar] Failed: ' . $errorMessage, [
                            'user_id' => $user->id,
                            'reference_no' => $request->id,
                            'new_reference_no' => $newUuid,
                            'master_tipe_pembayaran_id' => $request->masterTipePembayaranId,
                            'url' => $url,
                            'payload' => $payload,
                            'http_code' => $httpCode,
                            'curl_errno' => $curlErrno,
                            'curl_error' => $curlError,
                            'raw_response' => $response,
                            'response_data' => $data,
                            'error_message' => $errorMessage,
                        ]);

                        return response()->json(['status' => 500, 'message' => $errorMessage, 'new_reference_no' => $newUuid]);
                    }
                }
                // Create SKRD
            }
            $transaksiObjek = Transaksi::with(['transaksiObject.object.opd', 'transaksiObject.object.retribusi', 'transaksiObject.object'])
                ->where('reference_no', $request->id)
                ->first();

            return response()->json(['status' => 200, 'message' => 'Successfully Change Status', 'data' => $transaksiObjek]);
        }

        return response()->json(['status' => 201, 'message' => "You're not authenticated"]);
    }

    public function downloadPrivateFile($filename)
    {
        $user = JWTAuth::parseToken()->authenticate(); // Optional if you need auth
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $filePath = 'private/documents/' . $filename;

        if (! Storage::disk('local')->exists($filePath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return response()->file(storage_path('app/' . $filePath));
    }

    public function getTransaksi()
    {

        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            $draft = Transaksi::where('subject_id', $user->id)
                ->where('master_status_pembayaran_id', 1)->where('is_installment', '!=', 1)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage = 8, $columns = ['*'], $pageName = 'draft');
            $active = Transaksi::where('subject_id', $user->id)
                ->where('master_status_pembayaran_id', 2)->where('is_installment', '!=', 1)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage = 8, $columns = ['*'], $pageName = 'active');
            $paid = Transaksi::where('subject_id', $user->id)
                ->where('master_status_pembayaran_id', 3)->where('is_installment', '!=', 1)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage = 8, $columns = ['*'], $pageName = 'paid');
            $cancelledByUser = Transaksi::where('subject_id', $user->id)
                ->where('master_status_pembayaran_id', 4)->where('is_installment', '!=', 1)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage = 8, $columns = ['*'], $pageName = 'cancelled_by_user');
            $cancelledByOPD = Transaksi::where('subject_id', $user->id)
                ->where('master_status_pembayaran_id', 5)->where('is_installment', '!=', 1)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage = 8, $columns = ['*'], $pageName = 'cancelled_by_opd');
            $overdue = Transaksi::where('subject_id', $user->id)
                ->where('master_status_pembayaran_id', 6)->where('is_installment', '!=', 1)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage = 8, $columns = ['*'], $pageName = 'overdue');

            return response()->json(['status' => 200, 'message' => 'Successfully get the data', 'draft' => $draft, 'active' => $active, 'paid' => $paid, 'cancelled_by_user' => $cancelledByUser, 'overdue' => $overdue, 'cancelled_by_opd' => $cancelledByOPD]);
        }

        return response()->json(['status' => 201, 'message' => "You're not authenticated", 'data' => []]);
    }

    public function getInstallment()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            $data = Transaksi::with(['installmentTransaction'])->where('subject_id', $user->id)->where('is_installment', 1)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage = 8, $columns = ['*'], $pageName = 'installment');

            return response()->json(['status' => 200, 'message' => 'Successfully get the data', 'installment' => $data]);
        }

        return response()->json(['status' => 401, 'message' => "You're not authenticated", 'data' => []]);
    }

    public function getOpdInstallment()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            $masterOpdId = $user->master_opd_id;
            $masterLokasiId = $user->master_lokasi_id;
            $data = Transaksi::whereHas('transaksiObject.object', function ($query) use ($masterOpdId, $masterLokasiId) {
                $query->where('master_opd_id', $masterOpdId);
                if ($masterLokasiId != 0) {
                    $query->where('master_lokasi_id', $masterLokasiId);
                }
            })->with(['installmentTransaction'])->where('is_installment', 1)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage = 8, $columns = ['*'], $pageName = 'installmentopd');

            // Count installment transactions by status from Transaksi table
            $baseQuery = Transaksi::whereHas('transaksiObject.object', function ($query) use ($masterOpdId, $masterLokasiId) {
                $query->where('master_opd_id', $masterOpdId);
                if ($masterLokasiId != 0) {
                    $query->where('master_lokasi_id', $masterLokasiId);
                }
            })->where('is_installment', 1);

            $countMenunggu = (clone $baseQuery)->where('master_status_pembayaran_id', 1)->count();
            $countDibatalkan = (clone $baseQuery)->where('master_status_pembayaran_id', 4)->count();
            $countDitolak = (clone $baseQuery)->where('master_status_pembayaran_id', 5)->count();
            $countAngsuranBerjalan = (clone $baseQuery)->where('master_status_pembayaran_id', 8)->count();
            $countAngsuranSelesai = (clone $baseQuery)->where('master_status_pembayaran_id', 9)->count();

            return response()->json([
                'status' => 200,
                'message' => 'Successfully get the data',
                'installment' => $data,
                'count_menunggu' => $countMenunggu,
                'count_dibatalkan' => $countDibatalkan,
                'count_ditolak' => $countDitolak,
                'count_angsuran_berjalan' => $countAngsuranBerjalan,
                'count_angsuran_selesai' => $countAngsuranSelesai,
            ]);
        }

        return response()->json(['status' => 401, 'message' => "You're not authenticated", 'data' => []]);
    }

    public function searchOpdInstallment(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            $masterOpdId = $user->master_opd_id;
            $masterLokasiId = $user->master_lokasi_id;
            $searchTerm = $request->input('search', '');

            $query = Transaksi::whereHas('transaksiObject.object', function ($q) use ($masterOpdId, $masterLokasiId) {
                $q->where('master_opd_id', $masterOpdId);
                if ($masterLokasiId != 0) {
                    $q->where('master_lokasi_id', $masterLokasiId);
                }
            })->with(['installmentTransaction', 'subject', 'transaksiObject.object'])
                ->where('is_installment', 1);

            // Add search filter if search term is provided
            if (! empty($searchTerm)) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('reference_no', 'like', "%{$searchTerm}%")
                        ->orWhereHas('subject', function ($subQuery) use ($searchTerm) {
                            $subQuery->where('name', 'like', "%{$searchTerm}%")
                                ->orWhere('npwrd', 'like', "%{$searchTerm}%");
                        })
                        ->orWhereHas('transaksiObject.object', function ($objQuery) use ($searchTerm) {
                            $objQuery->where('uraian_objek', 'like', "%{$searchTerm}%")
                                ->orWhere('rincian_objek', 'like', "%{$searchTerm}%")
                                ->orWhere('sub_rincian_objek', 'like', "%{$searchTerm}%");
                        });
                });
            }

            $data = $query->orderBy('created_at', 'desc')
                ->paginate($perPage = 8, $columns = ['*'], $pageName = 'installment_search');

            return response()->json([
                'status' => 200,
                'message' => 'Successfully search installment transactions',
                'installment' => $data,
                'search_term' => $searchTerm,
            ]);
        }

        return response()->json(['status' => 401, 'message' => "You're not authenticated", 'data' => []]);
    }

    public function setPaymentInstallment(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated", 'data' => []]);
        }

        $transaksi = Transaksi::with(['transaksiObject.object.opd', 'transaksiObject.object.retribusi', 'transaksiObject.object'])
            ->where('reference_no', $request->reference_no)
            ->first();

        $installment = InstallmentTransaction::where('installment_ref_id', $request->installment_ref_id)->first();

        if (!$transaksi) {
            $this->logPayment('error', 'setPaymentInstallment: Transaksi not found', [
                'user_id' => $user->id,
                'reference_no' => $request->reference_no,
                'installment_ref_id' => $request->installment_ref_id,
            ]);
            return response()->json(['status' => 404, 'message' => 'Transaksi tidak ditemukan']);
        }

        if (!$installment) {
            $this->logPayment('error', 'setPaymentInstallment: Installment not found', [
                'user_id' => $user->id,
                'reference_no' => $request->reference_no,
                'installment_ref_id' => $request->installment_ref_id,
            ]);
            return response()->json(['status' => 404, 'message' => 'Data cicilan tidak ditemukan']);
        }

        $firstObjek = $transaksi->transaksiObject->first();
        $firstObjectDetails = $firstObjek?->object;
        $datePart = Carbon::now('Asia/Jakarta')->format('dmy');
        $kodeDinas = str_pad($firstObjectDetails?->master_opd_id ?? 0, 2, '0', STR_PAD_LEFT);
        $namaDinas = $firstObjectDetails?->opd?->nama_opd ?? '';
        $subRincianObjek = $firstObjectDetails?->sub_rincian_objek ?? '';
        $nomorUrut = str_pad(1, 3, '0', STR_PAD_LEFT);

        if ($request->masterTipePembayaranId == 1) {
            $url = 'https://api-retribusi.bapendalampung.cloud/paymentprod/create_qris/CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9P';
            $payload = [
                'no_reference' => $installment->installment_ref_id,
                'kode_dinas' => $firstObjectDetails?->master_opd_id,
                'nama_dinas' => $namaDinas,
                'tgl_bayar' => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'total_bayar' => $installment->grand_total,
                'items' => [[
                    'id' => "TRX{$datePart}{$kodeDinas}{$nomorUrut}",
                    'price' => $installment->grand_total,
                    'quantity' => 1,
                    'name' => $subRincianObjek,
                ]],
            ];

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => ['x-api-key: CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9PYUUYUY', 'Content-Type: application/json'],
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlErrno = curl_errno($curl);
            $curlError = curl_error($curl);
            curl_close($curl);

            $data = json_decode($response);

            if ($data && isset($data->codemessage) && $data->codemessage == '200') {
                $uuid1 = Uuid::uuid1();
                $uuid = substr($uuid1, 0, 22);
                $installment->update([
                    'due_date' => Carbon::now('Asia/Jakarta')->addMinutes(10),
                    'qr_url' => $data->data->qrimage,
                    'master_tipe_pembayaran_id' => $request->masterTipePembayaranId,
                    'installment_ref_id' => $uuid,
                    'installment_old_ref_id' => $request->installment_ref_id,
                ]);
                $installment = InstallmentTransaction::where('installment_ref_id', $uuid)->first();

                $this->logPayment('info', 'setPaymentInstallment [QRIS] Success', [
                    'user_id' => $user->id,
                    'reference_no' => $request->reference_no,
                    'installment_ref_id' => $uuid,
                    'installment_old_ref_id' => $request->installment_ref_id,
                ]);

                return response()->json(['status' => 200, 'message' => 'Successfully Set Payment', 'data' => $installment]);
            } else {
                $uuid1 = Uuid::uuid1();
                $uuid = substr($uuid1, 0, 22);
                $installment->update([
                    'installment_ref_id' => $uuid,
                    'installment_old_ref_id' => $request->installment_ref_id,
                ]);

                $errorMessage = $data->message ?? ($curlError ?: 'Gagal memproses pembayaran QRIS');

                $this->logPayment('error', 'setPaymentInstallment [QRIS] Failed: ' . $errorMessage, [
                    'user_id' => $user->id,
                    'reference_no' => $request->reference_no,
                    'installment_ref_id' => $request->installment_ref_id,
                    'new_installment_ref_id' => $uuid,
                    'master_tipe_pembayaran_id' => $request->masterTipePembayaranId,
                    'url' => $url,
                    'payload' => $payload,
                    'http_code' => $httpCode,
                    'curl_errno' => $curlErrno,
                    'curl_error' => $curlError,
                    'raw_response' => $response,
                    'response_data' => $data,
                    'error_message' => $errorMessage,
                ]);

                return response()->json(['status' => 500, 'message' => $errorMessage, 'new_installment_ref_id' => $uuid]);
            }
        }

        if ($request->masterTipePembayaranId == 2) {
            $url = 'https://api-retribusi.bapendalampung.cloud/paymentprod-va/create/CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9P';
            $payload = [
                'no_reference' => $installment->installment_ref_id,
                'kode_dinas' => $firstObjectDetails?->master_opd_id,
                'nama_dinas' => $namaDinas,
                'tgl_bayar' => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'total_bayar' => $installment->grand_total,
                'items' => [[
                    'id' => "TRX{$datePart}{$kodeDinas}{$nomorUrut}",
                    'price' => $installment->grand_total,
                    'quantity' => 1,
                    'name' => $subRincianObjek,
                ]],
            ];

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'x-api-key: CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9PYUUYUY',
                    'Content-Type: application/json',
                ],
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlErrno = curl_errno($curl);
            $curlError = curl_error($curl);
            curl_close($curl);

            $data = json_decode($response);

            if ($data && isset($data->codemessage) && $data->codemessage == '200') {
                $uuid1 = Uuid::uuid1();
                $uuid = substr($uuid1, 0, 22);
                $installment->update([
                    'due_date' => Carbon::now('Asia/Jakarta')->addMinutes(60),
                    'va' => $data->data->kodeva,
                    'master_tipe_pembayaran_id' => $request->masterTipePembayaranId,
                    'installment_ref_id' => $uuid,
                    'installment_old_ref_id' => $request->installment_ref_id,
                ]);
                $installment = InstallmentTransaction::where('installment_ref_id', $uuid)->first();

                $this->logPayment('info', 'setPaymentInstallment [VA] Success', [
                    'user_id' => $user->id,
                    'reference_no' => $request->reference_no,
                    'installment_ref_id' => $uuid,
                    'installment_old_ref_id' => $request->installment_ref_id,
                ]);

                return response()->json(['status' => 200, 'message' => 'Successfully Set Payment', 'data' => $installment]);
            } else {
                $uuid1 = Uuid::uuid1();
                $uuid = substr($uuid1, 0, 22);
                $installment->update([
                    'installment_ref_id' => $uuid,
                    'installment_old_ref_id' => $request->installment_ref_id,
                ]);

                $errorMessage = $data->message ?? ($curlError ?: 'Gagal memproses pembayaran Virtual Account');

                $this->logPayment('error', 'setPaymentInstallment [VA] Failed: ' . $errorMessage, [
                    'user_id' => $user->id,
                    'reference_no' => $request->reference_no,
                    'installment_ref_id' => $request->installment_ref_id,
                    'new_installment_ref_id' => $uuid,
                    'master_tipe_pembayaran_id' => $request->masterTipePembayaranId,
                    'url' => $url,
                    'payload' => $payload,
                    'http_code' => $httpCode,
                    'curl_errno' => $curlErrno,
                    'curl_error' => $curlError,
                    'raw_response' => $response,
                    'response_data' => $data,
                    'error_message' => $errorMessage,
                ]);

                return response()->json(['status' => 500, 'message' => $errorMessage, 'new_installment_ref_id' => $uuid]);
            }
        }

        if ($request->masterTipePembayaranId == 3) {
            $url = 'https://api-retribusi.bapendalampung.cloud/paymentprod-va/create/CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9P';
            $payload = [
                'no_reference' => $installment->installment_ref_id,
                'kode_dinas' => $firstObjectDetails?->master_opd_id,
                'nama_dinas' => $namaDinas,
                'tgl_bayar' => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'total_bayar' => $installment->grand_total,
                'items' => [[
                    'id' => "TRX{$datePart}{$kodeDinas}{$nomorUrut}",
                    'price' => $installment->grand_total,
                    'quantity' => 1,
                    'name' => $subRincianObjek,
                ]],
            ];

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'x-api-key: CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9PYUUYUY',
                    'Content-Type: application/json',
                ],
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlErrno = curl_errno($curl);
            $curlError = curl_error($curl);
            curl_close($curl);

            $data = json_decode($response);

            if ($data && isset($data->codemessage) && $data->codemessage == '200') {
                $uuid1 = Uuid::uuid1();
                $uuid = substr($uuid1, 0, 22);
                $installment->update([
                    'due_date' => Carbon::now('Asia/Jakarta')->addDays(1),
                    'kode_bayar' => substr($data->data->kodeva, 5),
                    'master_tipe_pembayaran_id' => $request->masterTipePembayaranId,
                    'installment_ref_id' => $uuid,
                    'installment_old_ref_id' => $request->installment_ref_id,
                ]);
                $installment = InstallmentTransaction::where('installment_ref_id', $uuid)->first();

                $this->logPayment('info', 'setPaymentInstallment [Kode Bayar] Success', [
                    'user_id' => $user->id,
                    'reference_no' => $request->reference_no,
                    'installment_ref_id' => $uuid,
                    'installment_old_ref_id' => $request->installment_ref_id,
                ]);

                return response()->json(['status' => 200, 'message' => 'Successfully Set Payment', 'data' => $installment]);
            } else {
                $uuid1 = Uuid::uuid1();
                $uuid = substr($uuid1, 0, 22);
                $installment->update([
                    'installment_ref_id' => $uuid,
                    'installment_old_ref_id' => $request->installment_ref_id,
                ]);

                $errorMessage = $data->message ?? ($curlError ?: 'Gagal memproses pembayaran Kode Bayar');

                $this->logPayment('error', 'setPaymentInstallment [Kode Bayar] Failed: ' . $errorMessage, [
                    'user_id' => $user->id,
                    'reference_no' => $request->reference_no,
                    'installment_ref_id' => $request->installment_ref_id,
                    'new_installment_ref_id' => $uuid,
                    'master_tipe_pembayaran_id' => $request->masterTipePembayaranId,
                    'url' => $url,
                    'payload' => $payload,
                    'http_code' => $httpCode,
                    'curl_errno' => $curlErrno,
                    'curl_error' => $curlError,
                    'raw_response' => $response,
                    'response_data' => $data,
                    'error_message' => $errorMessage,
                ]);

                return response()->json(['status' => 500, 'message' => $errorMessage, 'new_installment_ref_id' => $uuid]);
            }
        }

        return response()->json(['status' => 400, 'message' => 'Tipe pembayaran tidak valid']);
    }

    public function getTransaksiOpd($masterOpdId)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            $masterLokasiId = $user->master_lokasi_id;
            $draft = Transaksi::whereHas('transaksiObject.object', function ($query) use ($masterOpdId, $masterLokasiId) {
                $query->where('master_opd_id', $masterOpdId);
                if ($masterLokasiId != 0 || $masterLokasiId != null) {
                    $query->where('master_lokasi_id', $masterLokasiId);
                }
            })
                ->with(['transaksiObject.object.opd', 'transaksiObject.object.retribusi'])
                ->where('master_status_pembayaran_id', 1)->where('is_installment', '!=', 1)
                ->orderBy('created_at', 'desc')
                ->paginate(8, ['*'], 'draft');
            $active = Transaksi::whereHas('transaksiObject.object', function ($query) use ($masterOpdId, $masterLokasiId) {
                $query->where('master_opd_id', $masterOpdId);
                if ($masterLokasiId != 0 || $masterLokasiId != null) {
                    $query->where('master_lokasi_id', $masterLokasiId);
                }
            })
                ->with(['transaksiObject.object.opd', 'transaksiObject.object.retribusi'])
                ->where('master_status_pembayaran_id', 2)->where('is_installment', '!=', 1)
                ->orderBy('created_at', 'desc')
                ->paginate(8, ['*'], 'active');
            $paid = Transaksi::whereHas('transaksiObject.object', function ($query) use ($masterOpdId, $masterLokasiId) {
                $query->where('master_opd_id', $masterOpdId);
                if ($masterLokasiId != 0 || $masterLokasiId != null) {
                    $query->where('master_lokasi_id', $masterLokasiId);
                }
            })
                ->with(['transaksiObject.object.opd', 'transaksiObject.object.retribusi'])
                ->where('master_status_pembayaran_id', 3)->where('is_installment', '!=', 1)
                ->orderBy('created_at', 'desc')
                ->paginate(8, ['*'], 'paid');
            $cancelledByUser = Transaksi::whereHas('transaksiObject.object', function ($query) use ($masterOpdId, $masterLokasiId) {
                $query->where('master_opd_id', $masterOpdId);
                if ($masterLokasiId != 0 || $masterLokasiId != null) {
                    $query->where('master_lokasi_id', $masterLokasiId);
                }
            })
                ->with(['transaksiObject.object.opd', 'transaksiObject.object.retribusi'])
                ->where('master_status_pembayaran_id', 4)->where('is_installment', '!=', 1)
                ->orderBy('created_at', 'desc')
                ->paginate(8, ['*'], 'cancelled_by_user');
            $cancelledByOPD = Transaksi::whereHas('transaksiObject.object', function ($query) use ($masterOpdId, $masterLokasiId) {
                $query->where('master_opd_id', $masterOpdId);
                if ($masterLokasiId != 0 || $masterLokasiId != null) {
                    $query->where('master_lokasi_id', $masterLokasiId);
                }
            })
                ->with(['transaksiObject.object.opd', 'transaksiObject.object.retribusi'])
                ->where('master_status_pembayaran_id', 5)->where('is_installment', '!=', 1)
                ->orderBy('created_at', 'desc')
                ->paginate(8, ['*'], 'cancelled_by_opd');
            $overdue = Transaksi::whereHas('transaksiObject.object', function ($query) use ($masterOpdId, $masterLokasiId) {
                $query->where('master_opd_id', $masterOpdId);
                if ($masterLokasiId != 0 || $masterLokasiId != null) {
                    $query->where('master_lokasi_id', $masterLokasiId);
                }
            })
                ->with(['transaksiObject.object.opd', 'transaksiObject.object.retribusi'])
                ->where('master_status_pembayaran_id', 6)->where('is_installment', '!=', 1)
                ->orderBy('created_at', 'desc')
                ->paginate(8, ['*'], 'overdue');

            return response()->json(['status' => 200, 'message' => 'Successfully get the data', 'draft' => $draft, 'active' => $active, 'paid' => $paid, 'cancelled_by_user' => $cancelledByUser, 'cancelled_by_opd' => $cancelledByOPD, 'overdue' => $overdue]);
        }
    }

    public function getTransaksiGroupedByRetribusi(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            $opdId = $request->query('opd_id');

            // Get totals using efficient aggregation queries (paid transactions + installments with at least one paid)
            $countJasaUmum = Transaksi::whereHas('transaksiObject.object', function ($query) use ($opdId) {
                $query->where('master_retribusi_id', 1);
                if ($opdId) {
                    $query->where('master_opd_id', $opdId);
                }
            })
                ->where(function ($query) {
                    $query->where('master_status_pembayaran_id', 3)
                        ->orWhere(function ($subQ) {
                            $subQ->where('is_installment', 1)
                                ->whereHas('installmentTransaction', function ($instQ) {
                                    $instQ->where('master_status_pembayaran_id', 9);
                                });
                        });
                })
                ->when($request->has('start_date') && $request->start_date != null, function ($query) use ($request) {
                    $query->whereRaw('DATE(COALESCE(payment_date, registration_date)) >= ?', [$request->start_date]);
                })
                ->when($request->has('end_date') && $request->end_date != null, function ($query) use ($request) {
                    $query->whereRaw('DATE(COALESCE(payment_date, registration_date)) <= ?', [$request->end_date]);
                })
                ->count();

            $countJasaUsaha = Transaksi::whereHas('transaksiObject.object', function ($query) use ($opdId) {
                $query->where('master_retribusi_id', 2);
                if ($opdId) {
                    $query->where('master_opd_id', $opdId);
                }
            })
                ->where(function ($query) {
                    $query->where('master_status_pembayaran_id', 3)
                        ->orWhere(function ($subQ) {
                            $subQ->where('is_installment', 1)
                                ->whereHas('installmentTransaction', function ($instQ) {
                                    $instQ->where('master_status_pembayaran_id', 9);
                                });
                        });
                })
                ->when($request->has('start_date') && $request->start_date != null, function ($query) use ($request) {
                    $query->whereRaw('DATE(COALESCE(payment_date, registration_date)) >= ?', [$request->start_date]);
                })
                ->when($request->has('end_date') && $request->end_date != null, function ($query) use ($request) {
                    $query->whereRaw('DATE(COALESCE(payment_date, registration_date)) <= ?', [$request->end_date]);
                })
                ->count();

            $countPerizinanTertentu = Transaksi::whereHas('transaksiObject.object', function ($query) use ($opdId) {
                $query->where('master_retribusi_id', 3);
                if ($opdId) {
                    $query->where('master_opd_id', $opdId);
                }
            })
                ->where(function ($query) {
                    $query->where('master_status_pembayaran_id', 3)
                        ->orWhere(function ($subQ) {
                            $subQ->where('is_installment', 1)
                                ->whereHas('installmentTransaction', function ($instQ) {
                                    $instQ->where('master_status_pembayaran_id', 9);
                                });
                        });
                })
                ->when($request->has('start_date') && $request->start_date != null, function ($query) use ($request) {
                    $query->whereRaw('DATE(COALESCE(payment_date, registration_date)) >= ?', [$request->start_date]);
                })
                ->when($request->has('end_date') && $request->end_date != null, function ($query) use ($request) {
                    $query->whereRaw('DATE(COALESCE(payment_date, registration_date)) <= ?', [$request->end_date]);
                })
                ->count();

            // Calculate total amounts for each retribusi type (from transaction_objects for paid + installment_transactions for paid installments)
            // Jasa Umum - Regular paid transactions
            $pendapatanJasaUmum = TransaksiObjek::join('master_objek', 'transaction_objects.object_id', '=', 'master_objek.id')
                ->join('transactions', 'transaction_objects.transaction_id', '=', 'transactions.reference_no')
                ->where('master_objek.master_retribusi_id', 1)
                ->where('transactions.master_status_pembayaran_id', 3)
                ->whereNotNull('transactions.payment_date')
                ->when($opdId, function ($query) use ($opdId) {
                    $query->where('master_objek.master_opd_id', $opdId);
                })
                ->when($request->has('start_date') && $request->has('end_date') && $request->start_date != null && $request->end_date != null, function ($query) use ($request) {
                    $query->whereBetween('transactions.payment_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
                })
                ->sum('transaction_objects.total_amount');

            // Jasa Umum - Paid installments
            $pendapatanInstallmentJasaUmum = InstallmentTransaction::join('transactions', 'installment_transactions.transaction_id', '=', 'transactions.reference_no')
                ->join('transaction_objects', function ($join) {
                    $join->on('transactions.reference_no', '=', 'transaction_objects.transaction_id')
                        ->whereRaw('transaction_objects.id = (SELECT MIN(id) FROM transaction_objects WHERE transaction_id = transactions.reference_no)');
                })
                ->join('master_objek', 'transaction_objects.object_id', '=', 'master_objek.id')
                ->where('master_objek.master_retribusi_id', 1)
                ->where('installment_transactions.master_status_pembayaran_id', 9)
                ->whereNotNull('installment_transactions.paid_date')
                ->when($opdId, function ($query) use ($opdId) {
                    $query->where('master_objek.master_opd_id', $opdId);
                })
                ->when($request->has('start_date') && $request->has('end_date') && $request->start_date != null && $request->end_date != null, function ($query) use ($request) {
                    $query->whereBetween('installment_transactions.paid_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
                })
                ->sum('installment_transactions.grand_total');

            $totalAmountJasaUmum = $pendapatanJasaUmum + $pendapatanInstallmentJasaUmum;

            // Jasa Usaha - Regular paid transactions
            $pendapatanJasaUsaha = TransaksiObjek::join('master_objek', 'transaction_objects.object_id', '=', 'master_objek.id')
                ->join('transactions', 'transaction_objects.transaction_id', '=', 'transactions.reference_no')
                ->where('master_objek.master_retribusi_id', 2)
                ->where('transactions.master_status_pembayaran_id', 3)
                ->whereNotNull('transactions.payment_date')
                ->when($opdId, function ($query) use ($opdId) {
                    $query->where('master_objek.master_opd_id', $opdId);
                })
                ->when($request->has('start_date') && $request->has('end_date') && $request->start_date != null && $request->end_date != null, function ($query) use ($request) {
                    $query->whereBetween('transactions.payment_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
                })
                ->sum('transaction_objects.total_amount');

            // Jasa Usaha - Paid installments
            $pendapatanInstallmentJasaUsaha = InstallmentTransaction::join('transactions', 'installment_transactions.transaction_id', '=', 'transactions.reference_no')
                ->join('transaction_objects', function ($join) {
                    $join->on('transactions.reference_no', '=', 'transaction_objects.transaction_id')
                        ->whereRaw('transaction_objects.id = (SELECT MIN(id) FROM transaction_objects WHERE transaction_id = transactions.reference_no)');
                })
                ->join('master_objek', 'transaction_objects.object_id', '=', 'master_objek.id')
                ->where('master_objek.master_retribusi_id', 2)
                ->where('installment_transactions.master_status_pembayaran_id', 9)
                ->whereNotNull('installment_transactions.paid_date')
                ->when($opdId, function ($query) use ($opdId) {
                    $query->where('master_objek.master_opd_id', $opdId);
                })
                ->when($request->has('start_date') && $request->has('end_date') && $request->start_date != null && $request->end_date != null, function ($query) use ($request) {
                    $query->whereBetween('installment_transactions.paid_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
                })
                ->sum('installment_transactions.grand_total');

            $totalAmountJasaUsaha = $pendapatanJasaUsaha + $pendapatanInstallmentJasaUsaha;

            // Perizinan Tertentu - Regular paid transactions
            $pendapatanPerizinanTertentu = TransaksiObjek::join('master_objek', 'transaction_objects.object_id', '=', 'master_objek.id')
                ->join('transactions', 'transaction_objects.transaction_id', '=', 'transactions.reference_no')
                ->where('master_objek.master_retribusi_id', 3)
                ->where('transactions.master_status_pembayaran_id', 3)
                ->whereNotNull('transactions.payment_date')
                ->when($opdId, function ($query) use ($opdId) {
                    $query->where('master_objek.master_opd_id', $opdId);
                })
                ->when($request->has('start_date') && $request->has('end_date') && $request->start_date != null && $request->end_date != null, function ($query) use ($request) {
                    $query->whereBetween('transactions.payment_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
                })
                ->sum('transaction_objects.total_amount');

            // Perizinan Tertentu - Paid installments
            $pendapatanInstallmentPerizinanTertentu = InstallmentTransaction::join('transactions', 'installment_transactions.transaction_id', '=', 'transactions.reference_no')
                ->join('transaction_objects', function ($join) {
                    $join->on('transactions.reference_no', '=', 'transaction_objects.transaction_id')
                        ->whereRaw('transaction_objects.id = (SELECT MIN(id) FROM transaction_objects WHERE transaction_id = transactions.reference_no)');
                })
                ->join('master_objek', 'transaction_objects.object_id', '=', 'master_objek.id')
                ->where('master_objek.master_retribusi_id', 3)
                ->where('installment_transactions.master_status_pembayaran_id', 9)
                ->whereNotNull('installment_transactions.paid_date')
                ->when($opdId, function ($query) use ($opdId) {
                    $query->where('master_objek.master_opd_id', $opdId);
                })
                ->when($request->has('start_date') && $request->has('end_date') && $request->start_date != null && $request->end_date != null, function ($query) use ($request) {
                    $query->whereBetween('installment_transactions.paid_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
                })
                ->sum('installment_transactions.grand_total');

            $totalAmountPerizinanTertentu = $pendapatanPerizinanTertentu + $pendapatanInstallmentPerizinanTertentu;

            // Build grouped_data array matching Flutter model
            $groupedData = [
                [
                    'master_retribusi_id' => 1,
                    'nama_retribusi' => 'Retribusi Jasa Umum',
                    'total_transactions' => $countJasaUmum,
                    'total_amount' => $totalAmountJasaUmum,
                    'transactions' => [],
                ],
                [
                    'master_retribusi_id' => 2,
                    'nama_retribusi' => 'Retribusi Jasa Usaha',
                    'total_transactions' => $countJasaUsaha,
                    'total_amount' => $totalAmountJasaUsaha,
                    'transactions' => [],
                ],
                [
                    'master_retribusi_id' => 3,
                    'nama_retribusi' => 'Retribusi Perizinan Tertentu',
                    'total_transactions' => $countPerizinanTertentu,
                    'total_amount' => $totalAmountPerizinanTertentu,
                    'transactions' => [],
                ],

            ];

            return response()->json([
                'status' => 200,
                'message' => 'Successfully get the data',
                'data' => [
                    'grouped_data' => $groupedData,

                ],
            ]);
        }

        return response()->json(['status' => 401, 'message' => "You're not authenticated", 'data' => []]);
    }

    public function getTransaksiByRetribusiId(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            $masterRetribusiId = (int) $request->master_retribusi_id;
            $opdId = $request->query('opd_id');

            if (! in_array($masterRetribusiId, [1, 2, 3], true)) {
                return response()->json([
                    'status' => 422,
                    'message' => 'master_retribusi_id must be 1, 2, or 3',
                ], 422);
            }

            // Build query based on retribusi type
            $query = Transaksi::with([
                'transaksiObject.object.opd',
                'transaksiObject.object.retribusi',
                'transaksiObject.object.rincianRetribusi',
                'transaksiObject.object.lokasi',
                'transaksiObject.object.satuan',
                'transaksiObject.object.tpj',
                'transaksiObject.object.statusObjek',
                'transaksiObject.object.typeObject',
                'statusPembayaran',
                'tipePembayaran',
                'installmentTransaction' => function ($query) {
                    $query->where('master_status_pembayaran_id', 9);
                },
                'subject',
            ]);

            // Filter by retribusi type (1, 2, or 3)
            $query->whereHas('transaksiObject.object', function ($q) use ($masterRetribusiId, $opdId) {
                $q->where('master_retribusi_id', $masterRetribusiId);
                if ($opdId) {
                    $q->where('master_opd_id', $opdId);
                }
            });

            // Filter paid transactions (status 3) OR installment transactions with at least one paid installment
            $query->where(function ($q) {
                $q->where('master_status_pembayaran_id', 3)
                    ->orWhere(function ($subQ) {
                        $subQ->where('is_installment', 1)
                            ->whereHas('installmentTransaction', function ($instQ) {
                                $instQ->where('master_status_pembayaran_id', 9);
                            });
                    });
            });

            // Filter date range if provided
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereRaw('DATE(COALESCE(payment_date, registration_date)) BETWEEN ? AND ?', [$request->start_date, $request->end_date]);
            }

            // Calculate total amount correctly:
            // 1. For regular paid transactions (status 3): sum transaction_objects.total_amount
            // 2. For installment transactions: sum only paid installments (installment_transactions.grand_total)

            $totalAmount = Revenue::where('master_retribusi_id', $masterRetribusiId)
                ->when($opdId, function ($q) use ($opdId) {
                    $q->where('master_opd_id', $opdId);
                })
                ->when($request->has('start_date') && $request->has('end_date'), function ($q) use ($request) {
                    $q->whereBetween('date', [$request->start_date, $request->end_date]);
                })
                ->sum('total_revenue');

            $totalTransaction = Revenue::where('master_retribusi_id', $masterRetribusiId)
                ->when($opdId, function ($q) use ($opdId) {
                    $q->where('master_opd_id', $opdId);
                })
                ->when($request->has('start_date') && $request->has('end_date'), function ($q) use ($request) {
                    $q->whereBetween('date', [$request->start_date, $request->end_date]);
                })
                ->sum('total_transactions');

            $transactions = $query->orderBy('created_at', 'desc')->paginate(8);

            return response()->json([
                'status' => 200,
                'message' => 'Successfully get the data',
                'data' => $transactions,
                'total_amount' => number_format($totalAmount, 2, '.', ''),
                'total_transaction' => (int) $totalTransaction,
            ]);
        }

        return response()->json(['status' => 401, 'message' => "You're not authenticated", 'data' => []]);
    }

    public function getDetailTransaksi(Request $request)
    {
        $transaksi = Transaksi::where('reference_no', $request->id)->first();

        return response()->json(['status' => 200, 'message' => 'Successfully get the data', 'data' => $transaksi]);
    }

    public function checkPaymentStatus(Request $request)
    {

        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            $transaksi = Transaksi::where('reference_no', $request->reference_no)->first();

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
                CURLOPT_HTTPHEADER => ['x-api-key: CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9PYUUYUY', 'Content-Type: application/json'],
            ]);

            $response = curl_exec($curl);

            $data = json_decode($response);
            if ($data->codemessage == '200') {
                if ($data->data->lasttrxstatus == '00') {
                    $transaksi = Transaksi::with(['subject'])->where('reference_no', $request->reference_no)->first();

                    // Cek apakah transaksi sudah paid sebelumnya
                    if ($transaksi->master_status_pembayaran_id == 3) {
                        return response()->json(['status' => 200, 'message' => 'Pembayaran sudah diproses sebelumnya', 'data' => $transaksi]);
                    }

                    // Gunakan DB transaction untuk mencegah race condition
                    DB::transaction(function () use ($transaksi) {
                        $transaksi->update([
                            'payment_date' => Carbon::now('Asia/Jakarta'),

                            'master_status_pembayaran_id' => 3,
                        ]);

                        // Cek apakah dokumen SPdORD sudah ada
                        $documentspdord = Document::where('transaksi_id', $transaksi->reference_no)
                            ->where('master_tipe_dokumen_id', 1)
                            ->first();

                        if ($documentspdord == null) {
                            $opd = $transaksi->transaksiObject->first()->object->master_opd_id;
                            $lokasi = $transaksi->transaksiObject->first()->object->master_lokasi_id;
                            $kaban = MasterChief::with('masterOpd', 'masterLokasi')->where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('master_lokasi_id', $transaksi->transaksiObject->first()->object->master_lokasi_id)->where('is_active', 1)->first();
                            if ($kaban == null) {
                                $kaban = MasterChief::where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('is_active', 1)->first();
                            }
                            $documentspdord = Document::create([
                                'master_tipe_dokumen_id' => 1,
                                'transaksi_id' => $transaksi->reference_no,
                                'document_number' => 'SPDORD',
                                'linked_document_number' => 'SKRD',
                                'issue_date' => Carbon::now('Asia/Jakarta'),
                                'master_chief_id' => $kaban->id,
                            ]);

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
                            $opd = $transaksi->transaksiObject->first()->object->master_opd_id;
                            $lokasi = $transaksi->transaksiObject->first()->object->master_lokasi_id;
                            $kaban = MasterChief::with('masterOpd', 'masterLokasi')->where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('master_lokasi_id', $transaksi->transaksiObject->first()->object->master_lokasi_id)->where('is_active', 1)->first();
                            if ($kaban == null) {
                                $kaban = MasterChief::where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('is_active', 1)->first();
                            }
                            $documentskrd = Document::create([
                                'master_tipe_dokumen_id' => 2,
                                'transaksi_id' => $transaksi->reference_no,
                                'document_number' => 'SKRD',
                                'linked_document_number' => $documentspdord->document_number,
                                'issue_date' => Carbon::now('Asia/Jakarta'),
                                'master_chief_id' => $kaban->id,
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

                    $transaksi = Transaksi::with(['transaksiObject.object.opd', 'subject', 'transaksiObject.object.lokasi', 'transaksiObject.object.retribusi', 'transaksiObject.object'])->where('reference_no', $request->reference_no)->first();

                    // Send OneSignal notification

                    $master_location_id = $transaksi->transaksiObject->first()->object->master_lokasi_id ?? null;
                    $type = ($transaksi->is_installment == 1) ? 'new_installment' : 'new_transaction';
                    $this->sendNotificationtoAdmin(
                        $transaksi->reference_no,
                        $master_location_id,
                        5,
                        $type,
                        'Status Transaksi Berubah',
                        'Transaction Status Changed',
                        'Transaksi dengan nomor ' . $transaksi->reference_no . ' telah dilakukan pembayaran.',
                        'New Transaction with reference number ' . $transaksi->reference_no . ' has been paid.',
                    );
                    $this->sendNotificationtoUser(
                        $transaksi->reference_no,
                        $transaksi->subject_id,
                        $type,
                        'Status Transaksi Berubah',
                        'Transaction Status Changed',
                        'Transaksi dengan nomor ' . $transaksi->reference_no . ' telah berhasil dibayar.',
                        'New Transaction with reference number ' . $transaksi->reference_no . ' has been paid.',
                    );

                    return response()->json(['status' => 200, 'message' => 'Pembayaran Berhasil', 'data' => $transaksi]);
                } elseif ($data->data->lasttrxstatus == '06') {
                    Transaksi::where('reference_no', $request->reference_no)->update([
                        'master_status_pembayaran_id' => 6,
                    ]);
                    $transaksiObjek = Transaksi::where('reference_no', $request->reference_no)
                        ->first();
                    $type = ($transaksi->is_installment == 1) ? 'new_installment' : 'new_transaction';

                    $this->sendNotificationtoUser(
                        $transaksi->reference_no,
                        $transaksi->subject_id,
                        $type,
                        'Status Transaksi Berubah',
                        'Transaction Status Changed',
                        'Transaksi dengan nomor ' . $request->reference_no . ' telah kedaluwarsa.',
                        'New Transaction with reference number ' . $request->reference_no . ' has been expired.',
                    );

                    return response()->json(['status' => 200, 'message' => 'Document Kadaluwarsa', 'data' => $transaksiObjek]);
                } else {
                    $transaksiObjek = Transaksi::where('reference_no', $request->reference_no)
                        ->first();
                    if (Carbon::now('Asia/Jakarta')->addSeconds(10)->gte($transaksiObjek->due_date)) {
                        Transaksi::where('reference_no', $request->reference_no)->update([
                            'master_status_pembayaran_id' => 6,
                        ]);
                        $type = ($transaksi->is_installment == 1) ? 'new_installment' : 'new_transaction';
                        $this->sendNotificationtoUser(
                            $transaksi->reference_no,
                            $transaksi->subject_id,
                            $type,
                            'Status Transaksi Berubah',
                            'Transaction Status Changed',
                            'Transaksi dengan nomor ' . $request->reference_no . ' telah kedaluwarsa.',
                            'New Transaction with reference number ' . $request->reference_no . ' has been expired.',
                        );
                    }
                    $transaksiObjek = Transaksi::where('reference_no', $request->reference_no)
                        ->first();

                    return response()->json(['status' => 200, 'message' => 'Pembayaran belum dilakukan', 'data' => $transaksiObjek]);
                }
            } else {
                $transaksiObjek = Transaksi::where('reference_no', $request->reference_no)
                    ->first();
                if (Carbon::now('Asia/Jakarta')->addSeconds(10)->gte($transaksiObjek->due_date)) {
                    $transaksi = Transaksi::where('reference_no', $request->reference_no)
                        ->first();
                    $transaksi->update([
                        'master_status_pembayaran_id' => 6,
                    ]);
                    $type = ($transaksi->is_installment == 1) ? 'new_installment' : 'new_transaction';

                    $this->sendNotificationtoUser(
                        $transaksi->reference_no,
                        $transaksi->subject_id,
                        $type,
                        'Status Transaksi Berubah',
                        'Transaction Status Changed',
                        'Transaksi dengan nomor ' . $transaksi->reference_no . ' telah kedaluwarsa.',
                        'New Transaction with reference number ' . $transaksi->reference_no . ' has been expired.',
                    );
                }
                $transaksiObjek = Transaksi::where('reference_no', $request->reference_no)
                    ->first();

                return response()->json(['status' => 200, 'message' => $data->message, 'data' => $transaksiObjek]);
            }
        }

        return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
    }

    public function checkPaymentVA(Request $request)
    {

        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            $transaksi = Transaksi::with(['subject'])->where('va', $request->va)->first();
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
                    'kodeBilling' => $request->va,
                ]),
                CURLOPT_HTTPHEADER => [
                    'x-api-key: CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9PYUUYUY',
                    'Content-Type: application/json',
                ],
            ]);

            $response = curl_exec($curl);

            $data = json_decode($response);
            if ($data->data->statusbayar == 'Payment') {
                // Cek apakah transaksi sudah paid sebelumnya
                if ($transaksi->master_status_pembayaran_id == 3) {
                    return response()->json(['status' => 200, 'message' => 'Pembayaran sudah diproses sebelumnya', 'data' => $transaksi]);
                }

                // Gunakan DB transaction untuk mencegah race condition
                DB::transaction(function () use ($transaksi) {
                    $transaksi->update([
                        'master_status_pembayaran_id' => 3,
                        'payment_date' => Carbon::now('Asia/Jakarta'),
                    ]);

                    // Cek apakah dokumen SPdORD sudah ada
                    $documentspdord = Document::where('transaksi_id', $transaksi->reference_no)
                        ->where('master_tipe_dokumen_id', 1)
                        ->first();

                    if ($documentspdord == null) {
                        $opd = $transaksi->transaksiObject->first()->object->master_opd_id;
                        $lokasi = $transaksi->transaksiObject->first()->object->master_lokasi_id;
                        $kaban = MasterChief::with('masterOpd', 'masterLokasi')->where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('master_lokasi_id', $transaksi->transaksiObject->first()->object->master_lokasi_id)->where('is_active', 1)->first();
                        if ($kaban == null) {
                            $kaban = MasterChief::where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('is_active', 1)->first();
                        }
                        $documentspdord = Document::create([
                            'master_tipe_dokumen_id' => 1,
                            'transaksi_id' => $transaksi->reference_no,
                            'document_number' => 'SPDORD',
                            'linked_document_number' => 'SKRD',
                            'issue_date' => Carbon::now('Asia/Jakarta'),
                            'master_chief_id' => $kaban->id,
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
                        $opd = $transaksi->transaksiObject->first()->object->master_opd_id;
                        $lokasi = $transaksi->transaksiObject->first()->object->master_lokasi_id;

                        $kaban = MasterChief::with('masterOpd', 'masterLokasi')->where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('master_lokasi_id', $transaksi->transaksiObject->first()->object->master_lokasi_id)->where('is_active', 1)->first();
                        if ($kaban == null) {
                            $kaban = MasterChief::where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('is_active', 1)->first();
                        }
                        if ($kaban == null) {
                            throw new \Exception('Master Chief tidak ditemukan untuk OPD: ' . $opd);
                        }
                        $documentskrd = Document::create([
                            'master_tipe_dokumen_id' => 2,
                            'transaksi_id' => $transaksi->reference_no,
                            'document_number' => 'SKRD',
                            'linked_document_number' => $documentspdord->document_number,
                            'issue_date' => Carbon::now('Asia/Jakarta'),
                            'master_chief_id' => $kaban->id,
                        ]);
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

                $transaksi = Transaksi::with(['transaksiObject.object.opd', 'subject', 'transaksiObject.object.lokasi', 'transaksiObject.object.retribusi', 'transaksiObject.object'])->where('va', $request->va)->first();

                // Send OneSignal notification

                $master_location_id = $transaksi->transaksiObject->first()->object->master_lokasi_id ?? null;
                $type = ($transaksi->is_installment == 1) ? 'new_installment' : 'new_transaction';
                $this->sendNotificationtoAdmin(
                    $transaksi->reference_no,
                    $master_location_id,
                    5,
                    $type,
                    'Status Transaksi Berubah',
                    'Transaction Status Changed',
                    'Transaksi dengan nomor ' . $transaksi->reference_no . ' telah dilakukan pembayaran.',
                    'New Transaction with reference number ' . $transaksi->reference_no . ' has been paid.',
                );
                $this->sendNotificationtoUser(
                    $transaksi->reference_no,
                    $transaksi->subject_id,
                    $type,
                    'Status Angsuran Berubah',
                    'Installment Status Changed',
                    'Angsuran pada transaksi dengan nomor ' . $transaksi->reference_no . ' telah dilakukan pembayaran.',
                    'Installment with reference number ' . $transaksi->reference_no . ' has been paid.',
                );

                return response()->json(['status' => 200, 'message' => 'Pembayaran Berhasil', 'data' => $transaksi]);
            } else {
                if (Carbon::now('Asia/Jakarta')->addSeconds(10)->gte($transaksi->due_date)) {
                    $transaksi->update([
                        'master_status_pembayaran_id' => 6,
                    ]);
                    $type = ($transaksi->is_installment == 1) ? 'new_installment' : 'new_transaction';

                    $this->sendNotificationtoUser(
                        $transaksi->reference_no,
                        $transaksi->subject_id,
                        $type,
                        'Status Transaksi Berubah',
                        'Transaction Status Changed',
                        'Transaksi dengan nomor ' . $transaksi->reference_no . ' telah kedaluwarsa.',
                        'New Transaction with reference number ' . $transaksi->reference_no . ' has been expired.',
                    );
                }
                $transaksi = Transaksi::where('va', $request->va)
                    ->first();

                return response()->json(['status' => 200, 'message' => $data->message, 'data' => $transaksi]);
            }
        }

        return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
    }

    public function checkPaymentKodeBayar(Request $request)
    {

        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            $transaksi = Transaksi::with(['subject'])->where('kode_bayar', $request->kode_bayar)->first();
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
                    'kodeBilling' => '18003' . $request->kode_bayar,
                ]),
                CURLOPT_HTTPHEADER => [
                    'x-api-key: CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9PYUUYUY',
                    'Content-Type: application/json',
                ],
            ]);

            $response = curl_exec($curl);

            $data = json_decode($response);

            if ($data->data->statusbayar == 'Payment') {
                // Cek apakah transaksi sudah paid sebelumnya
                if ($transaksi->master_status_pembayaran_id == 3) {
                    return response()->json(['status' => 200, 'message' => 'Pembayaran sudah diproses sebelumnya', 'data' => $transaksi]);
                }

                // Gunakan DB transaction untuk mencegah race condition
                DB::transaction(function () use ($transaksi) {
                    $transaksi->update([
                        'master_status_pembayaran_id' => 3,
                        'payment_date' => Carbon::now('Asia/Jakarta'),
                    ]);

                    // Cek apakah dokumen SPdORD sudah ada
                    $documentspdord = Document::where('transaksi_id', $transaksi->reference_no)
                        ->where('master_tipe_dokumen_id', 1)
                        ->first();

                    if ($documentspdord == null) {
                        $opd = $transaksi->transaksiObject->first()->object->master_opd_id;
                        $lokasi = $transaksi->transaksiObject->first()->object->master_lokasi_id;
                        $kaban = MasterChief::with('masterOpd', 'masterLokasi')->where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('master_lokasi_id', $transaksi->transaksiObject->first()->object->master_lokasi_id)->where('is_active', 1)->first();
                        if ($kaban == null) {
                            $kaban = MasterChief::where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('is_active', 1)->first();
                        }
                        $documentspdord = Document::create([
                            'master_tipe_dokumen_id' => 1,
                            'transaksi_id' => $transaksi->reference_no,
                            'document_number' => 'SPDORD',
                            'linked_document_number' => 'SKRD',
                            'issue_date' => Carbon::now('Asia/Jakarta'),
                            'master_chief_id' => $kaban->id,
                        ]);


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
                        $opd = $transaksi->transaksiObject->first()->object->master_opd_id;
                        $lokasi = $transaksi->transaksiObject->first()->object->master_lokasi_id;
                        $kaban = MasterChief::with('masterOpd', 'masterLokasi')->where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('master_lokasi_id', $transaksi->transaksiObject->first()->object->master_lokasi_id)->where('is_active', 1)->first();
                        if ($kaban == null) {
                            $kaban = MasterChief::where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('is_active', 1)->first();
                        }
                        $documentskrd = Document::create([
                            'master_tipe_dokumen_id' => 2,
                            'transaksi_id' => $transaksi->reference_no,
                            'document_number' => 'SKRD',
                            'linked_document_number' => $documentspdord->document_number,
                            'issue_date' => Carbon::now('Asia/Jakarta'),
                            'master_chief_id' => $kaban->id,
                        ]);

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

                $transaksi = Transaksi::with(['transaksiObject.object.opd', 'subject', 'transaksiObject.object.lokasi', 'transaksiObject.object.retribusi', 'transaksiObject.object'])->where('kode_bayar', $request->kode_bayar)->first();

                // Send OneSignal notification

                $master_location_id = $transaksi->transaksiObject->first()->object->master_lokasi_id ?? null;
                $type = ($transaksi->is_installment == 1) ? 'new_installment' : 'new_transaction';
                $this->sendNotificationtoAdmin(
                    $transaksi->reference_no,
                    $master_location_id,
                    5,
                    $type,
                    'Status Transaksi Berubah',
                    'Transaction Status Changed',
                    'Transaksi dengan nomor ' . $request->id . ' telah dilakukan pembayaran.',
                    'New Transaction with reference number ' . $request->id . ' has been paid.',
                );

                $this->sendNotificationtoUser(
                    $transaksi->reference_no,
                    $transaksi->subject_id,
                    $type,
                    'Status Transaksi Berubah',
                    'Transaction Status Changed',
                    'Transaksi anda dengan nomor ' . $transaksi->reference_no . ' telah dilakukan pembayaran.',
                    'New Transaction with reference number ' . $transaksi->reference_no . ' has been paid.',
                );

                return response()->json(['status' => 200, 'message' => 'Pembayaran Berhasil', 'data' => $transaksi]);
            } else {
                if (Carbon::now('Asia/Jakarta')->addSeconds(10)->gte($transaksi->due_date)) {
                    $transaksi->update([
                        'master_status_pembayaran_id' => 6,
                    ]);
                }
                $transaksi = Transaksi::where('kode_bayar', $request->kode_bayar)
                    ->first();

                return response()->json(['status' => 200, 'message' => $data->message, 'data' => $transaksi]);
            }
        }

        return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
    }

    public function checkInstallmentPayment(Request $request)
    {

        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            $installment = InstallmentTransaction::where('installment_old_ref_id', $request->installment_ref_id)->first();

            // Cek apakah installment sudah kadaluwarsa
            if ($installment && $installment->due_date && Carbon::now('Asia/Jakarta')->gte($installment->due_date)) {
                // Reset field untuk installment yang kadaluwarsa
                $installment->update([
                    'due_date' => null,
                    'master_tipe_pembayaran_id' => null,
                    'qr_url' => null,
                ]);

                $transaksi = Transaksi::with(['installmentTransaction'])->where('reference_no', $installment->transaction_id)->first();

                return response()->json(['status' => 200, 'message' => 'Pembayaran telah kadaluwarsa. Silakan buat pembayaran baru.', 'data' => $transaksi]);
            }

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
                    'no_reference' => $request->installment_ref_id,
                ]),
                CURLOPT_HTTPHEADER => ['x-api-key: CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9PYUUYUY', 'Content-Type: application/json'],
            ]);
            $response = curl_exec($curl);
            $data = json_decode($response);
            $transaksi = Transaksi::with(['subject'])->where('reference_no', $installment->transaction_id)->first();
            if ($data->codemessage == '200') {
                if ($data->data->lasttrxstatus == '00') {
                    // Cek apakah installment sudah paid sebelumnya
                    if ($installment->master_status_pembayaran_id == 9) {
                        $transaksi = Transaksi::with(['installmentTransaction'])->where('reference_no', $installment->transaction_id)->first();

                        return response()->json(['status' => 200, 'message' => 'Pembayaran cicilan sudah diproses sebelumnya', 'installment_old_ref_id' => $request->installment_ref_id, 'data' => $transaksi]);
                    }

                    // Gunakan DB transaction untuk mencegah race condition
                    DB::transaction(function () use ($installment, $transaksi) {
                        $installment->update([
                            'master_status_pembayaran_id' => 9,
                            'paid_date' => Carbon::now('Asia/Jakarta'),

                        ]);

                        // Check if all installments are paid and update transaction status to 3 (paid)
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
                            $opd = $transaksi->transaksiObject->first()->object->master_opd_id;
                            $lokasi = $transaksi->transaksiObject->first()->object->master_lokasi_id;
                            $kaban = MasterChief::with('masterOpd', 'masterLokasi')->where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('master_lokasi_id', $transaksi->transaksiObject->first()->object->master_lokasi_id)->where('is_active', 1)->first();
                            if ($kaban == null) {
                                $kaban = MasterChief::where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('is_active', 1)->first();
                            }
                            $documentspdord = Document::create([
                                'master_tipe_dokumen_id' => 1,
                                'transaksi_id' => $transaksi->reference_no,
                                'document_number' => 'SPDORD',
                                'linked_document_number' => 'SKRD',
                                'issue_date' => Carbon::now('Asia/Jakarta'),
                                'master_chief_id' => $kaban->id,
                            ]);


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
                            $opd = $transaksi->transaksiObject->first()->object->master_opd_id;
                            $lokasi = $transaksi->transaksiObject->first()->object->master_lokasi_id;
                            $kaban = MasterChief::with('masterOpd', 'masterLokasi')->where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('master_lokasi_id', $transaksi->transaksiObject->first()->object->master_lokasi_id)->where('is_active', 1)->first();
                            if ($kaban == null) {
                                $kaban = MasterChief::where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('is_active', 1)->first();
                            }
                            $documentskrd = Document::create([
                                'master_tipe_dokumen_id' => 2,
                                'transaksi_id' => $installment->transaction_id,
                                'installment_id' => $installment->id,
                                'document_number' => 'SKRD',
                                'linked_document_number' => $documentspdord->document_number,
                                'issue_date' => Carbon::now('Asia/Jakarta'),
                                'master_chief_id' => $kaban->id,
                            ]);

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

                    $transaksi = Transaksi::with(['transaksiObject.object.opd', 'installmentTransaction', 'transaksiObject.object.lokasi', 'transaksiObject.object.retribusi', 'transaksiObject.object'])->where('reference_no', $installment->transaction_id)->first();

                    // Send OneSignal notification

                    $master_location_id = $transaksi->transaksiObject->first()->object->master_lokasi_id ?? null;
                    $type = ($transaksi->is_installment == 1) ? 'new_installment' : 'new_transaction';
                    $this->sendNotificationtoAdmin(
                        $transaksi->reference_no,
                        $master_location_id,
                        5,
                        $type,
                        'Status Angsuran Berubah',
                        'Installment Status Changed',
                        'Angsuran pada transaksi dengan nomor ' . $request->installment_ref_id . ' telah dilakukan pembayaran.',
                        'Installment with reference number ' . $request->installment_ref_id . ' has been paid.',
                    );
                    $this->sendNotificationtoUser(
                        $transaksi->reference_no,
                        $transaksi->subject_id,
                        $type,
                        'Status Angsuran Berubah',
                        'Installment Status Changed',
                        'Angsuran pada transaksi dengan nomor ' . $request->installment_ref_id . ' telah dilakukan pembayaran.',
                        'Installment with reference number ' . $request->installment_ref_id . ' has been paid.',
                    );

                    return response()->json(['status' => 200, 'message' => 'Pembayaran Berhasil', 'installment_old_ref_id' => $request->installment_ref_id, 'data' => $transaksi]);
                }
            } else {
                $transaksi = Transaksi::with(['installmentTransaction'])->where('reference_no', $installment->transaction_id)->first();

                return response()->json(['status' => 200, 'message' => 'Pembayaran belum dilakukan', 'installment_old_ref_id' => $request->installment_ref_id, 'data' => $transaksi]);
            }
        }

        return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
    }

    public function checkInstallmentPaymentVA(Request $request)
    {

        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            $installment = InstallmentTransaction::where('va', $request->va)->first();

            // Cek apakah installment sudah kadaluwarsa
            if (Carbon::now('Asia/Jakarta')->gte($installment->due_date)) {
                // Reset field untuk installment yang kadaluwarsa
                $installment->update([
                    'due_date' => null,
                    'master_tipe_pembayaran_id' => null,
                    'va' => null,
                ]);

                $transaksi = Transaksi::with(['installmentTransaction'])->where('reference_no', $installment->transaction_id)->first();

                return response()->json(['status' => 200, 'message' => 'Pembayaran telah kadaluwarsa. Silakan buat pembayaran baru.', 'data' => $transaksi]);
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
                    'kodeBilling' => $request->va,
                ]),
                CURLOPT_HTTPHEADER => [
                    'x-api-key: CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9PYUUYUY',
                    'Content-Type: application/json',
                ],
            ]);
            $response = curl_exec($curl);
            $data = json_decode($response);
            $transaksi = Transaksi::with(['subject'])->where('reference_no', $installment->transaction_id)->first();

            if ($data->data->statusbayar == 'Payment') {
                // Cek apakah installment sudah paid sebelumnya
                if ($installment->master_status_pembayaran_id == 9) {
                    $transaksi = Transaksi::with(['installmentTransaction'])->where('reference_no', $installment->transaction_id)->first();

                    return response()->json(['status' => 200, 'message' => 'Pembayaran cicilan sudah diproses sebelumnya', 'installment_old_ref_id' => $installment->installment_old_ref_id, 'data' => $transaksi]);
                }

                // Gunakan DB transaction untuk mencegah race condition
                DB::transaction(function () use ($installment, $transaksi) {
                    $installment->update([
                        'master_status_pembayaran_id' => 9,
                        'paid_date' => Carbon::now('Asia/Jakarta'),

                    ]);

                    // Check if all installments are paid and update transaction status to 3 (paid)
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
                        $opd = $transaksi->transaksiObject->first()->object->master_opd_id;
                        $lokasi = $transaksi->transaksiObject->first()->object->master_lokasi_id;
                        $kaban = MasterChief::with('masterOpd', 'masterLokasi')->where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('master_lokasi_id', $transaksi->transaksiObject->first()->object->master_lokasi_id)->where('is_active', 1)->first();
                        if ($kaban == null) {
                            $kaban = MasterChief::where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('is_active', 1)->first();
                        }
                        $documentspdord = Document::create([
                            'master_tipe_dokumen_id' => 1,
                            'transaksi_id' => $transaksi->reference_no,
                            'document_number' => 'SPDORD',
                            'linked_document_number' => 'SKRD',
                            'issue_date' => Carbon::now('Asia/Jakarta'),
                            'master_chief_id' => $kaban->id,
                        ]);


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
                        $opd = $transaksi->transaksiObject->first()->object->master_opd_id;
                        $lokasi = $transaksi->transaksiObject->first()->object->master_lokasi_id;
                        $kaban = MasterChief::with('masterOpd', 'masterLokasi')->where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('master_lokasi_id', $transaksi->transaksiObject->first()->object->master_lokasi_id)->where('is_active', 1)->first();
                        if ($kaban == null) {
                            $kaban = MasterChief::where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('is_active', 1)->first();
                        }
                        $documentskrd = Document::create([
                            'master_tipe_dokumen_id' => 2,
                            'transaksi_id' => $installment->transaction_id,
                            'installment_id' => $installment->id,
                            'document_number' => 'SKRD',
                            'linked_document_number' => $documentspdord->document_number,
                            'issue_date' => Carbon::now('Asia/Jakarta'),
                            'master_chief_id' => $kaban->id,
                        ]);

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

                $installment = InstallmentTransaction::where('va', $request->va)->first();
                $transaksi = Transaksi::with(['transaksiObject.object.opd', 'installmentTransaction', 'transaksiObject.object.lokasi', 'transaksiObject.object.retribusi', 'transaksiObject.object'])->where('reference_no', $installment->transaction_id)->first();

                // Send OneSignal notification

                $master_location_id = $transaksi->transaksiObject->first()->object->master_lokasi_id ?? null;
                $type = ($transaksi->is_installment == 1) ? 'new_installment' : 'new_transaction';
                $this->sendNotificationtoAdmin(
                    $transaksi->reference_no,
                    $master_location_id,
                    5,
                    $type,
                    'Status Angsuran Berubah',
                    'Installment Status Changed',
                    'Angsuran pada transaksi dengan nomor ' . $installment->installment_old_ref_id . ' telah dilakukan pembayaran.',
                    'Installment with reference number ' . $installment->installment_old_ref_id . ' has been paid.',
                );

                $this->sendNotificationtoUser(
                    $transaksi->reference_no,
                    $transaksi->subject_id,
                    $type,
                    'Status Angsuran Berubah',
                    'Installment Status Changed',
                    'Angsuran pada transaksi dengan nomor ' . $installment->installment_old_ref_id . ' telah dilakukan pembayaran.',
                    'Installment with reference number ' . $installment->installment_old_ref_id . ' has been paid.',
                );

                return response()->json(['status' => 200, 'message' => 'Pembayaran Berhasil', 'installment_old_ref_id' => $installment->installment_old_ref_id, 'data' => $transaksi]);
            } else {
                $transaksi = Transaksi::with(['installmentTransaction'])->where('reference_no', $installment->transaction_id)->first();

                return response()->json(['status' => 200, 'message' => 'Pembayaran belum dilakukan', 'installment_old_ref_id' => $installment->installment_old_ref_id, 'data' => $transaksi]);
            }
        }

        return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
    }

    public function checkInstallmentPaymentKodeBayar(Request $request)
    {

        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            $installment = InstallmentTransaction::where('kode_bayar', $request->kode_bayar)->first();

            // Cek apakah installment sudah kadaluwarsa
            if (Carbon::now('Asia/Jakarta')->gte($installment->due_date)) {
                // Reset field untuk installment yang kadaluwarsa
                $installment->update([
                    'due_date' => null,
                    'master_tipe_pembayaran_id' => null,
                    'kode_bayar' => null,
                ]);

                $transaksi = Transaksi::with(['installmentTransaction'])->where('reference_no', $installment->transaction_id)->first();

                return response()->json(['status' => 200, 'message' => 'Pembayaran telah kadaluwarsa. Silakan buat pembayaran baru.', 'data' => $transaksi]);
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
                    'kodeBilling' => '18003' . $request->kode_bayar,
                ]),
                CURLOPT_HTTPHEADER => [
                    'x-api-key: CNtVA26CRRqdd293H5rdWq81Opa4UCi2GEYyulTV0zAjIytTDQ86vTvAExKqhU9PYUUYUY',
                    'Content-Type: application/json',
                ],
            ]);
            $response = curl_exec($curl);
            $data = json_decode($response);
            $transaksi = Transaksi::with(['subject'])->where('reference_no', $installment->transaction_id)->first();

            if ($data->data->statusbayar == 'Payment') {
                // Cek apakah installment sudah paid sebelumnya
                if ($installment->master_status_pembayaran_id == 9) {
                    $transaksi = Transaksi::with(['installmentTransaction'])->where('reference_no', $installment->transaction_id)->first();

                    return response()->json(['status' => 200, 'message' => 'Pembayaran cicilan sudah diproses sebelumnya', 'installment_old_ref_id' => $installment->installment_old_ref_id, 'data' => $transaksi]);
                }

                // Gunakan DB transaction untuk mencegah race condition
                DB::transaction(function () use ($installment, $transaksi) {
                    $installment->update([
                        'master_status_pembayaran_id' => 9,
                        'paid_date' => Carbon::now('Asia/Jakarta'),

                    ]);

                    // Check if all installments are paid and update transaction status to 3 (paid)
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
                        $opd = $transaksi->transaksiObject->first()->object->master_opd_id;
                        $lokasi = $transaksi->transaksiObject->first()->object->master_lokasi_id;
                        $kaban = MasterChief::with('masterOpd', 'masterLokasi')->where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('master_lokasi_id', $transaksi->transaksiObject->first()->object->master_lokasi_id)->where('is_active', 1)->first();
                        if ($kaban == null) {
                            $kaban = MasterChief::where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('is_active', 1)->first();
                        }
                        $documentspdord = Document::create([
                            'master_tipe_dokumen_id' => 1,
                            'transaksi_id' => $transaksi->reference_no,
                            'document_number' => 'SPDORD',
                            'linked_document_number' => 'SKRD',
                            'issue_date' => Carbon::now('Asia/Jakarta'),
                            'master_chief_id' => $kaban->id,
                        ]);
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
                        $opd = $transaksi->transaksiObject->first()->object->master_opd_id;
                        $lokasi = $transaksi->transaksiObject->first()->object->master_lokasi_id;
                        $kaban = MasterChief::with('masterOpd', 'masterLokasi')->where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('master_lokasi_id', $transaksi->transaksiObject->first()->object->master_lokasi_id)->where('is_active', 1)->first();
                        if ($kaban == null) {
                            $kaban = MasterChief::where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('is_active', 1)->first();
                        }
                        $documentskrd = Document::create([
                            'master_tipe_dokumen_id' => 2,
                            'transaksi_id' => $installment->transaction_id,
                            'installment_id' => $installment->id,
                            'document_number' => 'SKRD',
                            'linked_document_number' => $documentspdord->document_number,
                            'issue_date' => Carbon::now('Asia/Jakarta'),
                            'master_chief_id' => $kaban->id,
                        ]);

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

                $installment = InstallmentTransaction::where('kode_bayar', $request->kode_bayar)->first();
                $transaksi = Transaksi::with(['transaksiObject.object.opd', 'installmentTransaction', 'transaksiObject.object.lokasi', 'transaksiObject.object.retribusi', 'transaksiObject.object'])->where('reference_no', $installment->transaction_id)->first();

                // Send OneSignal notification

                $master_location_id = $transaksi->transaksiObject->first()->object->master_lokasi_id ?? null;
                $type = ($transaksi->is_installment == 1) ? 'new_installment' : 'new_transaction';
                $this->sendNotificationtoAdmin(
                    $transaksi->reference_no,
                    $master_location_id,
                    5,
                    $type,
                    'Status Angsuran Berubah',
                    'Installment Status Changed',
                    'Angsuran pada transaksi dengan nomor ' . $installment->installment_old_ref_id . ' telah dilakukan pembayaran.',
                    'Installment with reference number ' . $installment->installment_old_ref_id . ' has been paid.',
                );
                $this->sendNotificationtoUser(
                    $transaksi->reference_no,
                    $transaksi->subject_id,
                    $type,
                    'Status Angsuran Berubah',
                    'Installment Status Changed',
                    'Angsuran pada transaksi dengan nomor ' . $installment->installment_old_ref_id . ' telah dilakukan pembayaran.',
                    'Installment with reference number ' . $installment->installment_old_ref_id . ' has been paid.',
                );

                return response()->json(['status' => 200, 'message' => 'Pembayaran Berhasil', 'installment_old_ref_id' => $installment->installment_old_ref_id, 'data' => $transaksi]);
            } else {
                $transaksi = Transaksi::with(['installmentTransaction'])->where('reference_no', $installment->transaction_id)->first();

                return response()->json(['status' => 200, 'message' => 'Pembayaran belum dilakukan', 'installment_old_ref_id' => $installment->installment_old_ref_id, 'data' => $transaksi]);
            }
        }

        return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
    }

    public function generateFullReport(Request $request)
    {
        // Increase limits for large reports
        set_time_limit(300);
        ini_set('memory_limit', '1024M');
        ini_set('pcre.backtrack_limit', '10000000');

        $user = JWTAuth::parseToken()->authenticate();
        if (! $user) {
            return response()->json(['status' => 201, 'message' => "You're not authenticated"]);
        }

        $userData = User::with('masterOpd')->find($user->id);
        $masterOpdId = $request->opd_id;

        try {
            // Load template Excel
            $templatePath = storage_path('app/templates/laporan_template.xlsx');
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Data OPD
            $this->replaceTag($sheet, '{start_date}', $request->start_date);
            $this->replaceTag($sheet, '{end_date}', $request->end_date);

            // Get transactions with cart and objects (status 3 = lunas)
            $transactionsQuery = Transaksi::with([
                'transaksiObject.object.opd:id,nama_opd',
                'transaksiObject.object.lokasi:id,nama_lokasi',
                'transaksiObject.object.retribusi:id,nama_retribusi',
                'transaksiObject.object.rincianRetribusi:id,rincian_retribusi',
                'transaksiObject.object.tpj:id,nama_tpj',
                'transaksiObject.object.satuan:id,nama_satuan',
                'subject',
                'subject.masterOpd:id,nama_opd',
                'tipePembayaran',
                'installmentTransaction',
                'document',
            ])
                ->where('master_status_pembayaran_id', 3)
                ->whereBetween('payment_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);

            // Filter by OPD if provided
            if ($masterOpdId) {
                $transactionsQuery->whereHas('transaksiObject.object', function ($query) use ($masterOpdId) {
                    $query->where('master_opd_id', $masterOpdId);
                });
            }

            // Filter by location for role 5 users with non-zero location
            if ($user->master_role_id == 5 && $user->master_lokasi_id != 0) {
                $transactionsQuery->whereHas('transaksiObject.object', function ($query) use ($user) {
                    $query->where('master_lokasi_id', $user->master_lokasi_id);
                });
            }

            $transactions = $transactionsQuery->orderBy('registration_date', 'desc')->get();

            // Get paid installment transactions (status 9 = lunas untuk installment)
            $paidInstallmentsQuery = InstallmentTransaction::with([
                'transaksi.transaksiObject.object.opd:id,nama_opd',
                'transaksi.transaksiObject.object.lokasi:id,nama_lokasi',
                'transaksi.transaksiObject.object.retribusi:id,nama_retribusi',
                'transaksi.transaksiObject.object.rincianRetribusi:id,rincian_retribusi',
                'transaksi.transaksiObject.object.tpj:id,nama_tpj',
                'transaksi.transaksiObject.object.satuan:id,nama_satuan',
                'transaksi.subject',
                'transaksi.subject.masterOpd:id,nama_opd',
                'tipePembayaran',
            ])
                ->where('master_status_pembayaran_id', 9)
                ->whereBetween('paid_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);

            // Filter by OPD if provided
            if ($masterOpdId) {
                $paidInstallmentsQuery->whereHas('transaksi.transaksiObject.object', function ($query) use ($masterOpdId) {
                    $query->where('master_opd_id', $masterOpdId);
                });
            }

            // Filter by location for role 5 users with non-zero location
            if ($user->master_role_id == 5 && $user->master_lokasi_id != 0) {
                $paidInstallmentsQuery->whereHas('transaksi.transaksiObject.object', function ($query) use ($user) {
                    $query->where('master_lokasi_id', $user->master_lokasi_id);
                });
            }

            $paidInstallments = $paidInstallmentsQuery->orderBy('created_at', 'desc')->get();

            // Get OPD name - query directly from MasterOpd
            $opdName = 'Semua OPD';
            if ($masterOpdId) {
                $masterOpd = MasterOpd::find($masterOpdId);
                $opdName = $masterOpd ? $masterOpd->nama_opd : '-';
            }

            $this->replaceTag($sheet, '{opd}', $opdName);

            $startRow = 6;

            // Header table
            $this->createTableHeader($sheet, $startRow);

            // Fill data - now each object in cart gets its own row
            $this->fillTransactionDataWithMultipleObjects($sheet, $transactions, $startRow + 1);

            // Fill installment data - continue serial number from transactions
            $currentRow = $startRow + 1 + $this->countTotalRows($transactions);
            $startingSerialNumber = $this->countTotalRows($transactions) + 1;
            $this->fillInstallmentData($sheet, $paidInstallments, $currentRow, $startingSerialNumber);

            // Hitung total dan summary
            $totalRows = $this->countTotalRows($transactions) + $paidInstallments->count();
            $this->addSummary($sheet, $transactions, $paidInstallments, $startRow + $totalRows + 2);

            // Page setup for PDF
            $pageSetup = $sheet->getPageSetup();
            $pageSetup->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
            $pageSetup->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
            $pageSetup->setFitToWidth(1);
            $pageSetup->setFitToHeight(0);

            $sheet->getPageMargins()
                ->setTop(0.5)
                ->setBottom(0.5)
                ->setLeft(0.5)
                ->setRight(0.5);

            // Convert Excel to HTML
            $spreadsheet->setActiveSheetIndex(0);
            $htmlWriter = new \PhpOffice\PhpSpreadsheet\Writer\Html($spreadsheet);
            ob_start();
            $htmlWriter->save('php://output');
            $excelHtml = ob_get_clean();

            // Remove borders in HTML
            $excelHtml = preg_replace('/\s*border="[^"]*"/i', '', $excelHtml);
            $excelHtml = preg_replace('/border\s*:\s*[^;\"]+;?/i', '', $excelHtml);

            // Prepare temp dir for mPDF
            $preferredTemp = storage_path('app/mpdf_temp');
            $mpdfTemp = null;
            try {
                if (! file_exists($preferredTemp)) {
                    mkdir($preferredTemp, 0777, true);
                }
                if (! file_exists($preferredTemp . '/mpdf')) {
                    mkdir($preferredTemp . '/mpdf', 0777, true);
                }
                @chmod($preferredTemp, 0777);
                @chmod($preferredTemp . '/mpdf', 0777);
                if (is_writable($preferredTemp . '/mpdf')) {
                    $mpdfTemp = $preferredTemp;
                }
            } catch (\Throwable $t) {
                $mpdfTemp = null;
            }
            if ($mpdfTemp === null || ! is_writable($mpdfTemp . '/mpdf')) {
                $sys = sys_get_temp_dir();
                if (! file_exists($sys . '/mpdf')) {
                    @mkdir($sys . '/mpdf', 0777, true);
                    @chmod($sys . '/mpdf', 0777);
                }
                if (is_writable($sys . '/mpdf')) {
                    $mpdfTemp = $sys;
                } else {
                    if (is_writable($preferredTemp)) {
                        $mpdfTemp = $preferredTemp;
                    }
                }
            }
            if ($mpdfTemp === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No writable temp directory available for mPDF. Please ensure storage/app/mpdf_temp is writable.',
                ], 500);
            }

            // Create PDF with optimized settings for large documents
            $mpdf = new \Mpdf\Mpdf([
                'format' => 'A4-L',
                'margin_top' => 5,
                'margin_bottom' => 5,
                'margin_left' => 5,
                'margin_right' => 5,
                'tempDir' => $mpdfTemp,
                'mode' => 'utf-8',
                'default_font' => 'arial',
                'simpleTables' => true,    // Faster table rendering
                'packTableData' => true,   // Reduce memory for large tables
            ]);

            $mpdf->WriteHTML('<style>
                body, table, td { font-family: Arial, sans-serif; font-size: 8pt; line-height: 1.2; }
                table { border-collapse: collapse; width: 100%; }
                td { padding: 2px 4px; }
            </style>', \Mpdf\HTMLParserMode::HEADER_CSS);
            $mpdf->WriteHTML($excelHtml);

            // Simpan file
            $filename = 'full_report_' . date('Ymd_His') . '.pdf';
            $outputPath = storage_path('app/private/documents/' . $filename);

            if (! file_exists(dirname($outputPath))) {
                Storage::makeDirectory('private/documents');
            }

            $mpdf->Output($outputPath, 'F');

            $filePath = 'private/documents/' . $filename;
            $fullPath = storage_path('app/' . $filePath);

            if (! Storage::disk('local')->exists($filePath)) {
                return response()->json(['message' => 'File not found'], 404);
            }

            return response()->file($outputPath);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error generating report: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile()),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function generateFullReportExcel(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');
        ini_set('pcre.backtrack_limit', '5000000');
        $user = JWTAuth::parseToken()->authenticate();
        if (! $user) {
            return response()->json(['status' => 201, 'message' => "You're not authenticated"]);
        }

        $userData = User::with('masterOpd')->find($user->id);
        $masterOpdId = $request->opd_id;

        try {
            // Load template Excel
            $templatePath = storage_path('app/templates/laporan_template.xlsx');
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Data OPD
            $this->replaceTag($sheet, '{start_date}', $request->start_date);
            $this->replaceTag($sheet, '{end_date}', $request->end_date);

            // Get transactions with cart and objects (status 3 = lunas)

            $transactionsQuery = Transaksi::with([
                'transaksiObject.object.opd:id,nama_opd',
                'transaksiObject.object.lokasi:id,nama_lokasi',
                'transaksiObject.object.retribusi:id,nama_retribusi',
                'transaksiObject.object.rincianRetribusi:id,rincian_retribusi',
                'transaksiObject.object.tpj:id,nama_tpj',
                'transaksiObject.object.satuan:id,nama_satuan',
                'subject',
                'subject.masterOpd:id,nama_opd',
                'tipePembayaran',
                'installmentTransaction',
                'document',

            ])
                ->where('master_status_pembayaran_id', 3)
                ->whereBetween('payment_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);

            // Filter by OPD if provided
            if ($masterOpdId) {
                $transactionsQuery->whereHas('transaksiObject.object', function ($query) use ($masterOpdId) {
                    $query->where('master_opd_id', $masterOpdId);
                });
            }

            // Filter by location for role 5 users with non-zero location
            if ($user->master_role_id == 5 && $user->master_lokasi_id != 0) {
                $transactionsQuery->whereHas('transaksiObject.object', function ($query) use ($user) {
                    $query->where('master_lokasi_id', $user->master_lokasi_id);
                });
            }

            $transactions = $transactionsQuery->orderBy('registration_date', 'desc')->get();

            // Get paid installment transactions (status 9 = lunas untuk installment)
            $paidInstallmentsQuery = InstallmentTransaction::with([
                'transaksi.transaksiObject.object.opd:id,nama_opd',
                'transaksi.transaksiObject.object.lokasi:id,nama_lokasi',
                'transaksi.transaksiObject.object.retribusi:id,nama_retribusi',
                'transaksi.transaksiObject.object.rincianRetribusi:id,rincian_retribusi',
                'transaksi.transaksiObject.object.tpj:id,nama_tpj',
                'transaksi.transaksiObject.object.satuan:id,nama_satuan',
                'transaksi.subject',
                'transaksi.subject.masterOpd:id,nama_opd',
                'tipePembayaran',
            ])
                ->where('master_status_pembayaran_id', 9)
                ->whereBetween('paid_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);

            // Filter by OPD if provided
            if ($masterOpdId) {
                $paidInstallmentsQuery->whereHas('transaksi.transaksiObject.object', function ($query) use ($masterOpdId) {
                    $query->where('master_opd_id', $masterOpdId);
                });
            }

            // Filter by location for role 5 users with non-zero location
            if ($user->master_role_id == 5 && $user->master_lokasi_id != 0) {
                $paidInstallmentsQuery->whereHas('transaksi.transaksiObject.object', function ($query) use ($user) {
                    $query->where('master_lokasi_id', $user->master_lokasi_id);
                });
            }

            $paidInstallments = $paidInstallmentsQuery->orderBy('created_at', 'desc')->get();

            // Get OPD name - query directly from MasterOpd
            $opdName = 'Semua OPD';
            if ($masterOpdId) {
                $masterOpd = MasterOpd::find($masterOpdId);
                $opdName = $masterOpd ? $masterOpd->nama_opd : '-';
            }

            $this->replaceTag($sheet, '{opd}', $opdName);

            $startRow = 6;

            // Header table
            $this->createTableHeader($sheet, $startRow);

            // Fill data - now each object in cart gets its own row
            $this->fillTransactionDataWithMultipleObjects($sheet, $transactions, $startRow + 1);

            // Fill installment data - continue serial number from transactions
            $currentRow = $startRow + 1 + $this->countTotalRows($transactions);
            $startingSerialNumber = $this->countTotalRows($transactions) + 1;
            $this->fillInstallmentData($sheet, $paidInstallments, $currentRow, $startingSerialNumber);

            // Hitung total dan summary
            $totalRows = $this->countTotalRows($transactions) + $paidInstallments->count();
            $this->addSummary($sheet, $transactions, $paidInstallments, $startRow + $totalRows + 2);

            // Page setup for Excel
            $pageSetup = $sheet->getPageSetup();
            $pageSetup->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
            $pageSetup->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
            $pageSetup->setFitToWidth(1);
            $pageSetup->setFitToHeight(0);

            $sheet->getPageMargins()
                ->setTop(0.5)
                ->setBottom(0.5)
                ->setLeft(0.5)
                ->setRight(0.5);

            // Save as Excel file
            $filename = 'full_report_' . date('Ymd_His') . '.xlsx';
            $outputPath = storage_path('app/private/documents/' . $filename);

            if (! file_exists(dirname($outputPath))) {
                Storage::makeDirectory('private/documents');
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($outputPath);

            return response()->download($outputPath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error generating report: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile()),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    private function fillTransactionDataWithMultipleObjects($sheet, $transactions, $startRow)
    {
        $row = $startRow;
        $serialNumber = 1;

        foreach ($transactions as $transaction) {
            $objectCount = count($transaction->transaksiObject ?? []);

            // If no objects, still create one row for the transaction
            if ($objectCount === 0) {
                $this->addTransactionRow($sheet, $transaction, null, $row, $serialNumber);
                $row++;
                $serialNumber++;

                continue;
            }

            // Create a row for each object in the cart
            foreach ($transaction->transaksiObject as $objectIndex => $transaksiObject) {
                $this->addTransactionRow($sheet, $transaction, $transaksiObject, $row, $serialNumber);

                // Apply alternating row colors
                $rowStyle = [
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $row % 2 == 0 ? 'E7E6E6' : 'FFFFFF'],
                    ],
                ];

                $sheet->getStyle('A' . $row . ':V' . $row)->applyFromArray($rowStyle);
                $row++;
                $serialNumber++;
            }
        }

        return $row;
    }

    private function fillInstallmentData($sheet, $paidInstallments, $startRow, $startingSerialNumber = 1)
    {
        $row = $startRow;
        $serialNumber = $startingSerialNumber;

        foreach ($paidInstallments as $installment) {
            $transaction = $installment->transaksi;

            // Skip if transaction or objects don't exist
            if (! $transaction || ! $transaction->transaksiObject || $transaction->transaksiObject->isEmpty()) {
                continue;
            }

            // Get the first object for installment data
            $transaksiObject = $transaction->transaksiObject->first();

            $this->addInstallmentRow($sheet, $installment, $transaction, $transaksiObject, $row, $serialNumber);

            // Apply alternating row colors
            $rowStyle = [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $row % 2 == 0 ? 'E7E6E6' : 'FFFFFF'],
                ],
            ];

            $sheet->getStyle('A' . $row . ':V' . $row)->applyFromArray($rowStyle);
            $row++;
            $serialNumber++;
        }

        return $row;
    }

    private function addTransactionRow($sheet, $transaction, $object, $row, $serialNumber)
    {
        // Use object data if available, otherwise use transaction data
        $lokasiName = $object->object->lokasi->nama_lokasi ?? '-';
        $uraianObjek = $object->object->uraian_objek ?? '-';
        $payment = $transaction->tipePembayaran->tipe_pembayaran ?? '-';
        $jenisRetribusi = $object->object->retribusi->nama_retribusi ?? '-';
        $rincianRetribusi = $object->object->rincianRetribusi->rincian_retribusi ?? '-';
        $subRincianRetribusi = $object->object->sub_rincian_retribusi ?? '-';
        $rincianObjek = $object->object->rincian_objek ?? '-';
        $subRincianObjek = $object->object->sub_rincian_objek ?? '-';
        $tpj = $object->object->tpj->nama_tpj ?? '-';
        $satuan = $object->object->satuan->nama_satuan ?? '-';

        // For transaction-specific data
        $jumlah = $object->quantity ?? 0;
        $totalPrice = $object ? $object->total_amount : ($object->object->harga_objek * $jumlah);
        $price = $object->object->harga_objek;
        $subjectName = '-';
        $subjectName = $transaction->subject_name ?? '-';
        $subjectAddress = $transaction->subject->address ?? '-';
        $subjectOpd = $transaction->transaction_type ?? '-';
        $spdord = Document::where('transaksi_id', $transaction->reference_no)->where('master_tipe_dokumen_id', 1)->first();
        $skrd = Document::where('transaksi_id', $transaction->reference_no)->where('master_tipe_dokumen_id', 2)->whereNull('installment_id')->first();
        $sheet->setCellValue('A' . $row, $serialNumber);
        $sheet->setCellValue('B' . $row, $lokasiName);
        $sheet->setCellValue('C' . $row, $uraianObjek);
        $sheet->setCellValue('D' . $row, $subjectName);
        $sheet->setCellValue('E' . $row, $subjectOpd);
        $sheet->setCellValue('F' . $row, $subjectAddress);
        $sheet->setCellValue('G' . $row, $spdord->document_number ?? '-');
        $sheet->setCellValue('H' . $row, 'RETRIBUSI');
        $sheet->setCellValue('I' . $row, $skrd->document_number ?? '-');
        $sheet->setCellValue('J' . $row, $jenisRetribusi);
        $sheet->setCellValue('K' . $row, $rincianRetribusi);
        $sheet->setCellValue('L' . $row, $subRincianRetribusi);
        $sheet->setCellValue('M' . $row, $rincianObjek);
        $sheet->setCellValue('N' . $row, $subRincianObjek);
        $sheet->setCellValue('O' . $row, $tpj);
        $sheet->setCellValue('P' . $row, $satuan);
        $sheet->setCellValue('Q' . $row, $jumlah);
        $sheet->setCellValue('R' . $row, $price);
        $sheet->setCellValue('S' . $row, $totalPrice);

        // Determine payment status for row T based on installment conditions
        if ($transaction->is_installment == 1 && $transaction->installment_completed == 1) {
            $paymentStatus = 'Lunas';
        } elseif ($transaction->is_installment == 1) {
            $paymentStatus = 'ANGSURAN';
        } else {
            $paymentStatus = $payment;
        }

        $sheet->setCellValue('T' . $row, $paymentStatus);
        $sheet->setCellValue('U' . $row, $transaction->payment_date ? date('d-m-Y', strtotime($transaction->payment_date)) : '-');
        $sheet->setCellValue('V' . $row, '');
    }

    private function addInstallmentRow($sheet, $installment, $transaction, $object, $row, $serialNumber)
    {
        // Use object data from the parent transaction
        $lokasiName = $object->object->lokasi->nama_lokasi ?? '-';
        $uraianObjek = $object->object->uraian_objek ?? '-';
        $payment = $installment->tipePembayaran->tipe_pembayaran ?? '-';
        $jenisRetribusi = $object->object->retribusi->nama_retribusi ?? '-';
        $rincianRetribusi = $object->object->rincianRetribusi->rincian_retribusi ?? '-';
        $subRincianRetribusi = $object->object->sub_rincian_retribusi ?? '-';
        $rincianObjek = $object->object->rincian_objek ?? '-';
        $subRincianObjek = $object->object->sub_rincian_objek ?? '-';
        $tpj = $object->object->tpj->nama_tpj ?? '-';
        $satuan = $object->object->satuan->nama_satuan ?? '-';

        // For installment-specific data
        $subjectName = $transaction->subject_name ?? '-';
        $subjectAddress = $transaction->subject->address ?? '-';
        $subjectOpd = $transaction->transaction_type ?? '-';

        // Installment specific values
        $installmentAmount = $installment->grand_total;
        $price = $installment->payment_amount;
        $jumlah = 1; // Installment is counted as 1 unit

        // Reference number with installment info
        $referenceNo = $transaction->reference_no . ' (Angsuran ' . $installment->installment_index . '/' . $installment->installment_total . ')';
        $spdord = Document::where('transaksi_id', $installment->transaction_id)->where('master_tipe_dokumen_id', 1)->first();
        $skrd = Document::where('transaksi_id', $installment->transaction_id)->where('master_tipe_dokumen_id', 2)->where('installment_id', $installment->id)->first();
        $sheet->setCellValue('A' . $row, $serialNumber);
        $sheet->setCellValue('B' . $row, $lokasiName);
        $sheet->setCellValue('C' . $row, $uraianObjek);
        $sheet->setCellValue('D' . $row, $subjectName);
        $sheet->setCellValue('E' . $row, $subjectOpd);
        $sheet->setCellValue('F' . $row, $subjectAddress);
        $sheet->setCellValue('G' . $row, $spdord->document_number ?? '-');
        $sheet->setCellValue('H' . $row, 'RETRIBUSI');
        $sheet->setCellValue('I' . $row, $skrd->document_number ?? '-');
        $sheet->setCellValue('J' . $row, $jenisRetribusi);
        $sheet->setCellValue('K' . $row, $rincianRetribusi);
        $sheet->setCellValue('L' . $row, $subRincianRetribusi);
        $sheet->setCellValue('M' . $row, $rincianObjek);
        $sheet->setCellValue('N' . $row, $subRincianObjek);
        $sheet->setCellValue('O' . $row, $tpj);
        $sheet->setCellValue('P' . $row, $satuan);
        $sheet->setCellValue('Q' . $row, $jumlah);
        $sheet->setCellValue('R' . $row, $price);
        $sheet->setCellValue('S' . $row, $installmentAmount);
        $sheet->setCellValue('T' . $row, $payment);
        $sheet->setCellValue('U' . $row, $installment->paid_date ? date('d-m-Y', strtotime($installment->paid_date)) : '-');
        $sheet->setCellValue('V' . $row, '');
    }

    private function countTotalRows($transactions)
    {
        $totalRows = 0;

        foreach ($transactions as $transaction) {

            $objectCount = count($transaction->transaksiObject ?? []);
            $totalRows += $objectCount > 0 ? $objectCount : 1;
        }

        return $totalRows;
    }

    private function addSummary($sheet, $transactions, $paidInstallments, $summaryRow)
    {
        $totalAmount = 0;

        // Calculate total from all objects in all transactions
        foreach ($transactions as $transaction) {
            $totalAmount += $transaction->total_amount;
        }

        // Add total from paid installments
        foreach ($paidInstallments as $installment) {
            $totalAmount += $installment->grand_total;
        }

        $sheet->setCellValue('A' . $summaryRow, 'TOTAL');
        $sheet->setCellValue('S' . $summaryRow, $totalAmount); // Changed to column S for amount

        // Style untuk summary
        $summaryStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        $sheet->getStyle('A' . $summaryRow . ':S' . $summaryRow)->applyFromArray($summaryStyle);
    }

    private function replaceTag($sheet, $tag, $value)
    {
        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                // PASTIKAN ini single cell, bukan range
                $cellCoordinate = $cell->getCoordinate();

                // Debug: Cek jika cell coordinate mengandung ":"
                if (str_contains($cellCoordinate, ':')) {
                    continue; // Skip ranges
                }

                $cellValue = $cell->getValue();

                if (is_string($cellValue) && str_contains($cellValue, $tag)) {
                    $newValue = str_replace($tag, $value, $cellValue);
                    $cell->setValue($newValue);

                    return true;
                }
            }
        }

        return false;
    }

    private function createTableHeader($sheet, $startRow)
    {
        $headers = [
            'A' => 'No',
            'B' => 'LOKASI OBJEK',
            'C' => 'URAIAN OBJEK',
            'D' => 'NAMA SUBJEK / WAJIB RETRIBUSI',
            'E' => 'INSTANSI SUBJEK / WAJIB RETRIBUSI',
            'F' => 'ALAMAT SUBJEK / WAJIB RETRIBUSI',
            'G' => 'NOMOR SPDORD',
            'H' => 'JENIS PENETAPAN',
            'I' => 'NO SURAT KETETAPAN',
            'J' => 'JENIS RETRIBUSI',
            'K' => 'RINCIAN RETRIBUSI',
            'L' => 'SUB RINCIAN RETRIBUSI',
            'M' => 'RINCIAN OBJEK',
            'N' => 'SUB RINCIAN OBJEK',
            'O' => 'TPJ',
            'P' => 'SATUAN',
            'Q' => 'JUMLAH',
            'R' => 'TARIF',
            'S' => 'JUMLAH RETRIBUSI',
            'T' => 'KANAL PEMBAYARAN',
            'U' => 'TANGGAL PEMBAYARAN',
            'V' => 'NO STS',
        ];

        // Set header values
        foreach ($headers as $column => $header) {
            $sheet->setCellValue($column . $startRow, $header);
        }

        // Apply header style
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        $sheet->getStyle('A' . $startRow . ':V' . $startRow)->applyFromArray($headerStyle);

        // Auto width columns
        foreach (range('A', 'V') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Send OneSignal push notification
     */
    private function sendNotificationtoAdmin($referenceNo, $objectLocationId, $userRoleId, $type, $headerId, $headerEng, $messageId, $messageEng)
    {
        try {
            // Validasi data
            if (! $objectLocationId) {
                error_log('OneSignal Error: Object Location ID tidak ditemukan');

                return false;
            }

            $curl = curl_init();

            $payload = [
                'app_id' => '1139d32b-e6bd-419c-a2b0-71d6849614cb',
                'target_channel' => 'push',
                'filters' => [
                    [
                        'field' => 'tag',
                        'relation' => '=',
                        'key' => 'location',
                        'value' => (string) $objectLocationId,
                    ],
                    ['operator' => 'AND'],
                    [
                        'field' => 'tag',
                        'relation' => '=',
                        'key' => 'role',
                        'value' => (string) $userRoleId,
                    ],
                ],
                'contents' => [
                    'id' => $messageId,
                    'en' => $messageEng,
                ],
                'headings' => [
                    'id' => $headerId,
                    'en' => $headerEng,
                ],
                'data' => [
                    'reference_no' => $referenceNo,
                    'object_location_id' => (string) $objectLocationId,
                    'user_role_id' => (string) $userRoleId,
                    'type' => $type,
                ],
                // ✅ Platform flags
                'isIos' => true,
                'isAndroid' => true,
                // ✅ Custom sounds
                'ios_sound' => 'custom_notification.wav',   // iOS bundle file name
                'android_sound' => 'custom_notification.wav',   // Android res/raw name (no .wav)
                'android_channel_id' => '8c85dbce-e916-438d-9d43-a4de505d23b1',
            ];

            // Log payload untuk debugging
            error_log('OneSignal Payload: ' . json_encode($payload));

            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.onesignal.com/notifications',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Key ' . config('services.onesignal.rest_api_key'),
                    'Content-Type: application/json',
                ],
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $err = curl_error($curl);

            if ($err) {
                error_log('OneSignal cURL Error: ' . $err);

                return false;
            }

            $responseData = json_decode($response, true);
            error_log("OneSignal Response (HTTP $httpCode): " . $response);

            // Check if notification was sent successfully
            if ($httpCode == 200 && isset($responseData['id'])) {
                error_log('OneSignal Success: Notification sent with ID ' . $responseData['id']);

                return $responseData;
            } else {
                error_log('OneSignal Failed: ' . ($responseData['errors'] ?? 'Unknown error'));

                return false;
            }
        } catch (\Exception $e) {
            error_log('OneSignal Exception: ' . $e->getMessage());

            return false;
        }
    }

    private function sendNotificationPaidtoAdmin($referenceNo, $objectLocationId, $userRoleId, $type, $headerId, $headerEng, $messageId, $messageEng)
    {
        try {
            // Validasi data
            if (! $objectLocationId) {
                error_log('OneSignal Error: Object Location ID tidak ditemukan');

                return false;
            }

            $curl = curl_init();

            $payload = [
                'app_id' => '1139d32b-e6bd-419c-a2b0-71d6849614cb',
                'target_channel' => 'push',
                'filters' => [
                    [
                        'field' => 'tag',
                        'relation' => '=',
                        'key' => 'location',
                        'value' => (string) $objectLocationId,
                    ],
                    ['operator' => 'AND'],
                    [
                        'field' => 'tag',
                        'relation' => '=',
                        'key' => 'role',
                        'value' => (string) $userRoleId,
                    ],
                ],
                'contents' => [
                    'id' => $messageId,
                    'en' => $messageEng,
                ],
                'headings' => [
                    'id' => $headerId,
                    'en' => $headerEng,
                ],
                'data' => [
                    'reference_no' => $referenceNo,
                    'object_location_id' => (string) $objectLocationId,
                    'user_role_id' => (string) $userRoleId,
                    'notification_type' => 'payment_status_update',
                    'transaction_status' => '3',
                ],
                // ✅ Platform flags
                'isIos' => true,
                'isAndroid' => true,
                // ✅ Custom sounds
                'ios_sound' => 'custom_notification.wav',   // iOS bundle file name
                'android_sound' => 'custom_notification.wav',   // Android res/raw name (no .wav)
                'android_channel_id' => '8c85dbce-e916-438d-9d43-a4de505d23b1',
            ];

            // Log payload untuk debugging
            error_log('OneSignal Payload: ' . json_encode($payload));

            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.onesignal.com/notifications',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Key ' . config('services.onesignal.rest_api_key'),
                    'Content-Type: application/json',
                ],
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $err = curl_error($curl);

            if ($err) {
                error_log('OneSignal cURL Error: ' . $err);

                return false;
            }

            $responseData = json_decode($response, true);
            error_log("OneSignal Response (HTTP $httpCode): " . $response);

            // Check if notification was sent successfully
            if ($httpCode == 200 && isset($responseData['id'])) {
                error_log('OneSignal Success: Notification sent with ID ' . $responseData['id']);

                return $responseData;
            } else {
                error_log('OneSignal Failed: ' . ($responseData['errors'] ?? 'Unknown error'));

                return false;
            }
        } catch (\Exception $e) {
            error_log('OneSignal Exception: ' . $e->getMessage());

            return false;
        }
    }

    private function sendNotificationPaidInstallmenttoAdmin($referenceNo, $objectLocationId, $userRoleId, $type, $headerId, $headerEng, $messageId, $messageEng)
    {
        try {
            // Validasi data
            if (! $objectLocationId) {
                error_log('OneSignal Error: Object Location ID tidak ditemukan');

                return false;
            }

            $curl = curl_init();

            $payload = [
                'app_id' => '1139d32b-e6bd-419c-a2b0-71d6849614cb',
                'target_channel' => 'push',
                'filters' => [
                    [
                        'field' => 'tag',
                        'relation' => '=',
                        'key' => 'location',
                        'value' => (string) $objectLocationId,
                    ],
                    ['operator' => 'AND'],
                    [
                        'field' => 'tag',
                        'relation' => '=',
                        'key' => 'role',
                        'value' => (string) $userRoleId,
                    ],
                ],
                'contents' => [
                    'id' => $messageId,
                    'en' => $messageEng,
                ],
                'headings' => [
                    'id' => $headerId,
                    'en' => $headerEng,
                ],
                'data' => [
                    'reference_no' => $referenceNo,
                    'object_location_id' => (string) $objectLocationId,
                    'user_role_id' => (string) $userRoleId,
                    'notification_type' => 'installment_status_update',
                    'transaction_status' => '9',
                ],
                // ✅ Platform flags
                'isIos' => true,
                'isAndroid' => true,
                // ✅ Custom sounds
                'ios_sound' => 'custom_notification.wav',   // iOS bundle file name
                'android_sound' => 'custom_notification.wav',   // Android res/raw name (no .wav)
                'android_channel_id' => '8c85dbce-e916-438d-9d43-a4de505d23b1',
            ];

            // Log payload untuk debugging
            error_log('OneSignal Payload: ' . json_encode($payload));

            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.onesignal.com/notifications',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Key ' . config('services.onesignal.rest_api_key'),
                    'Content-Type: application/json',
                ],
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $err = curl_error($curl);

            if ($err) {
                error_log('OneSignal cURL Error: ' . $err);

                return false;
            }

            $responseData = json_decode($response, true);
            error_log("OneSignal Response (HTTP $httpCode): " . $response);
            Log::info("OneSignal Response (HTTP $httpCode): " . $response);
            // Check if notification was sent successfully
            if ($httpCode == 200 && isset($responseData['id'])) {
                error_log('OneSignal Success: Notification sent with ID ' . $responseData['id']);

                return $responseData;
            } else {
                error_log('OneSignal Failed: ' . ($responseData['errors'] ?? 'Unknown error'));

                return false;
            }
        } catch (\Exception $e) {
            error_log('OneSignal Exception: ' . $e->getMessage());

            return false;
        }
    }

    private function sendNotificationtoUser($referenceNo, $userId, $type, $headerId, $headerEng, $messageId, $messageEng)
    {
        try {

            $curl = curl_init();

            $payload = [
                'app_id' => '1139d32b-e6bd-419c-a2b0-71d6849614cb',
                'target_channel' => 'push',
                'filters' => [
                    [
                        'field' => 'tag',
                        'relation' => '=',
                        'key' => 'user',
                        'value' => (string) $userId,
                    ],
                ],
                'contents' => [
                    'id' => $messageId,
                    'en' => $messageEng,
                ],
                'headings' => [
                    'id' => $headerId,
                    'en' => $headerEng,
                ],
                'data' => [
                    'reference_no' => $referenceNo,
                    'user_id' => (string) $userId,
                    'type' => $type,
                ],
                // ✅ Platform flags
                'isIos' => true,
                'isAndroid' => true,

                // ✅ Custom sounds
                'ios_sound' => 'custom_notification.wav',   // iOS bundle file name
                'android_sound' => 'custom_notification.wav',   // Android res/raw name (no .wav)
                'android_channel_id' => '8c85dbce-e916-438d-9d43-a4de505d23b1',
            ];

            // Log payload untuk debugging
            error_log('OneSignal Payload: ' . json_encode($payload));

            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.onesignal.com/notifications',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Key ' . config('services.onesignal.rest_api_key'),
                    'Content-Type: application/json',
                ],
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $err = curl_error($curl);

            if ($err) {
                error_log('OneSignal cURL Error: ' . $err);

                return false;
            }

            $responseData = json_decode($response, true);
            error_log("OneSignal Response (HTTP $httpCode): " . $response);

            // Check if notification was sent successfully
            if ($httpCode == 200 && isset($responseData['id'])) {
                error_log('OneSignal Success: Notification sent with ID ' . $responseData['id']);

                return $responseData;
            } else {
                error_log('OneSignal Failed: ' . ($responseData['errors'] ?? 'Unknown error'));

                return false;
            }
        } catch (\Exception $e) {
            error_log('OneSignal Exception: ' . $e->getMessage());

            return false;
        }
    }

    private function sendNotificationPaidtoUser($referenceNo, $userId, $type, $headerId, $headerEng, $messageId, $messageEng)
    {
        try {
            $curl = curl_init();

            $payload = [
                'app_id' => '1139d32b-e6bd-419c-a2b0-71d6849614cb',
                'target_channel' => 'push',
                'filters' => [
                    [
                        'field' => 'tag',
                        'relation' => '=',
                        'key' => 'user',
                        'value' => (string) $userId,
                    ],
                ],
                'contents' => [
                    'id' => $messageId,
                    'en' => $messageEng,
                ],
                'headings' => [
                    'id' => $headerId,
                    'en' => $headerEng,
                ],
                'data' => [
                    'reference_no' => $referenceNo,
                    'user_id' => (string) $userId,
                    'notification_type' => 'payment_status_update',
                    'transaction_status' => '3',
                ],
                // ✅ Platform flags
                'isIos' => true,
                'isAndroid' => true,

                // ✅ Custom sounds
                'ios_sound' => 'custom_notification.wav',   // iOS bundle file name
                'android_sound' => 'custom_notification.wav',   // Android res/raw name (no .wav)
                'android_channel_id' => '8c85dbce-e916-438d-9d43-a4de505d23b1',
            ];

            // Log payload untuk debugging
            error_log('OneSignal Payload: ' . json_encode($payload));

            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.onesignal.com/notifications',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Key ' . config('services.onesignal.rest_api_key'),
                    'Content-Type: application/json',
                ],
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $err = curl_error($curl);

            if ($err) {
                error_log('OneSignal cURL Error: ' . $err);

                return false;
            }

            $responseData = json_decode($response, true);
            error_log("OneSignal Response (HTTP $httpCode): " . $response);

            // Check if notification was sent successfully
            if ($httpCode == 200 && isset($responseData['id'])) {
                error_log('OneSignal Success: Notification sent with ID ' . $responseData['id']);

                return $responseData;
            } else {
                error_log('OneSignal Failed: ' . ($responseData['errors'] ?? 'Unknown error'));

                return false;
            }
        } catch (\Exception $e) {
            error_log('OneSignal Exception: ' . $e->getMessage());

            return false;
        }
    }

    private function sendNotificationPaidInstallmenttoUser($referenceNo, $userId, $type, $headerId, $headerEng, $messageId, $messageEng)
    {
        try {
            $curl = curl_init();

            $payload = [
                'app_id' => '1139d32b-e6bd-419c-a2b0-71d6849614cb',
                'target_channel' => 'push',
                'filters' => [
                    [
                        'field' => 'tag',
                        'relation' => '=',
                        'key' => 'user',
                        'value' => (string) $userId,
                    ],
                ],
                'contents' => [
                    'id' => $messageId,
                    'en' => $messageEng,
                ],
                'headings' => [
                    'id' => $headerId,
                    'en' => $headerEng,
                ],
                'data' => [
                    'reference_no' => $referenceNo,
                    'user_id' => (string) $userId,
                    'notification_type' => 'installment_status_update',
                    'transaction_status' => '9',
                ],
                // ✅ Platform flags
                'isIos' => true,
                'isAndroid' => true,

                // ✅ Custom sounds
                'ios_sound' => 'custom_notification.wav',   // iOS bundle file name
                'android_sound' => 'custom_notification.wav',   // Android res/raw name (no .wav)
                'android_channel_id' => '8c85dbce-e916-438d-9d43-a4de505d23b1',
            ];

            // Log payload untuk debugging
            error_log('OneSignal Payload: ' . json_encode($payload));

            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.onesignal.com/notifications',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Key ' . config('services.onesignal.rest_api_key'),
                    'Content-Type: application/json',
                ],
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $err = curl_error($curl);

            if ($err) {
                error_log('OneSignal cURL Error: ' . $err);

                return false;
            }

            $responseData = json_decode($response, true);
            error_log("OneSignal Response (HTTP $httpCode): " . $response);

            // Check if notification was sent successfully
            if ($httpCode == 200 && isset($responseData['id'])) {
                error_log('OneSignal Success: Notification sent with ID ' . $responseData['id']);

                return $responseData;
            } else {
                error_log('OneSignal Failed: ' . ($responseData['errors'] ?? 'Unknown error'));

                return false;
            }
        } catch (\Exception $e) {
            error_log('OneSignal Exception: ' . $e->getMessage());

            return false;
        }
    }

    public function generateSKRDReport(Request $request)
    {
        // Increase limits for large reports
        set_time_limit(300);
        ini_set('memory_limit', '512M');
        ini_set('pcre.backtrack_limit', '5000000');

        $user = JWTAuth::parseToken()->authenticate();
        if (! $user) {
            return response()->json(['status' => 201, 'message' => "You're not authenticated"]);
        }

        $userData = User::with('masterOpd')->find($user->id);
        $masterOpdId = $request->opd_id;
        try {
            // Load template Excel
            $templatePath = storage_path('app/templates/laporan_template_report_skrd.xlsx');
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Data OPD
            $this->replaceTag($sheet, '{start_date}', $request->start_date);
            $this->replaceTag($sheet, '{end_date}', $request->end_date);

            // Get transactions with cart and objects (status 3 = lunas)
            $transactionsQuery = Transaksi::with([
                'transaksiObject.object.opd:id,nama_opd',
                'transaksiObject.object.lokasi:id,nama_lokasi',
                'transaksiObject.object.retribusi:id,nama_retribusi',
                'transaksiObject.object.rincianRetribusi:id,rincian_retribusi',
                'subject',
                'subject.masterOpd:id,nama_opd',
                'tipePembayaran',
                'installmentTransaction',
                'document',
            ])
                ->where('master_status_pembayaran_id', 3)
                ->whereBetween('payment_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);

            // Filter by OPD if provided
            if ($masterOpdId) {
                $transactionsQuery->whereHas('transaksiObject.object', function ($query) use ($masterOpdId) {
                    $query->where('master_opd_id', $masterOpdId);
                });
            }

            // Filter by location for role 5 users with non-zero location
            if ($user->master_role_id == 5 && $user->master_lokasi_id != 0) {
                $transactionsQuery->whereHas('transaksiObject.object', function ($query) use ($user) {
                    $query->where('master_lokasi_id', $user->master_lokasi_id);
                });
            }

            $transactions = $transactionsQuery->orderBy('payment_date', 'desc')->get();

            // Get paid installment transactions (status 9 = lunas untuk installment)
            $paidInstallmentsQuery = InstallmentTransaction::with([
                'transaksi.transaksiObject.object.opd:id,nama_opd',
                'transaksi.transaksiObject.object.lokasi:id,nama_lokasi',
                'transaksi.transaksiObject.object.retribusi:id,nama_retribusi',
                'transaksi.transaksiObject.object.rincianRetribusi:id,rincian_retribusi',
                'transaksi.subject',
                'transaksi.subject.masterOpd:id,nama_opd',
                'tipePembayaran',
                'transaksi.document',
            ])
                ->where('master_status_pembayaran_id', 9)
                ->whereBetween('paid_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);

            // Filter by OPD if provided
            if ($masterOpdId) {
                $paidInstallmentsQuery->whereHas('transaksi.transaksiObject.object', function ($query) use ($masterOpdId) {
                    $query->where('master_opd_id', $masterOpdId);
                });
            }

            // Filter by location for role 5 users with non-zero location
            if ($user->master_role_id == 5 && $user->master_lokasi_id != 0) {
                $paidInstallmentsQuery->whereHas('transaksi.transaksiObject.object', function ($query) use ($user) {
                    $query->where('master_lokasi_id', $user->master_lokasi_id);
                });
            }

            $paidInstallments = $paidInstallmentsQuery->orderBy('created_at', 'desc')->get();

            // Get OPD name - query directly from MasterOpd
            $opdName = 'Semua OPD';
            if ($masterOpdId) {
                $masterOpd = MasterOpd::find($masterOpdId);
                $opdName = $masterOpd ? $masterOpd->nama_opd : '-';
            }

            $this->replaceTag($sheet, '{opd}', $opdName);

            $startRow = 6;

            // Header table
            $this->createSKRDTableHeader($sheet, $startRow);

            // Fill data
            $this->fillSKRDTransactionData($sheet, $transactions, $startRow + 1);

            // Fill installment data
            $currentRow = $startRow + 1 + $transactions->count();
            $startingSerialNumber = $transactions->count() + 1;
            $this->fillSKRDInstallmentData($sheet, $paidInstallments, $currentRow, $startingSerialNumber);

            // Page setup for PDF
            $pageSetup = $sheet->getPageSetup();
            $pageSetup->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
            $pageSetup->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
            $pageSetup->setFitToWidth(1);
            $pageSetup->setFitToHeight(0);

            $sheet->getPageMargins()
                ->setTop(0.5)
                ->setBottom(0.5)
                ->setLeft(0.5)
                ->setRight(0.5);

            // Convert Excel to HTML
            $spreadsheet->setActiveSheetIndex(0);
            $htmlWriter = new \PhpOffice\PhpSpreadsheet\Writer\Html($spreadsheet);
            ob_start();
            $htmlWriter->save('php://output');
            $excelHtml = ob_get_clean();

            // Remove borders in HTML
            $excelHtml = preg_replace('/\s*border="[^"]*"/i', '', $excelHtml);
            $excelHtml = preg_replace('/border\s*:\s*[^;\"]+;?/i', '', $excelHtml);

            // Prepare temp dir for mPDF
            $preferredTemp = storage_path('app/mpdf_temp');
            $mpdfTemp = null;
            try {
                if (! file_exists($preferredTemp)) {
                    mkdir($preferredTemp, 0777, true);
                }
                if (! file_exists($preferredTemp . '/mpdf')) {
                    mkdir($preferredTemp . '/mpdf', 0777, true);
                }
                @chmod($preferredTemp, 0777);
                @chmod($preferredTemp . '/mpdf', 0777);
                if (is_writable($preferredTemp . '/mpdf')) {
                    $mpdfTemp = $preferredTemp;
                }
            } catch (\Throwable $t) {
                $mpdfTemp = null;
            }
            if ($mpdfTemp === null || ! is_writable($mpdfTemp . '/mpdf')) {
                $sys = sys_get_temp_dir();
                if (! file_exists($sys . '/mpdf')) {
                    @mkdir($sys . '/mpdf', 0777, true);
                    @chmod($sys . '/mpdf', 0777);
                }
                if (is_writable($sys . '/mpdf')) {
                    $mpdfTemp = $sys;
                } else {
                    if (is_writable($preferredTemp)) {
                        $mpdfTemp = $preferredTemp;
                    }
                }
            }
            if ($mpdfTemp === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No writable temp directory available for mPDF. Please ensure storage/app/mpdf_temp is writable.',
                ], 500);
            }

            // Create PDF with optimized settings for large documents
            $mpdf = new \Mpdf\Mpdf([
                'format' => 'A4-L',
                'margin_top' => 5,
                'margin_bottom' => 5,
                'margin_left' => 5,
                'margin_right' => 5,
                'tempDir' => $mpdfTemp,
                'mode' => 'utf-8',
                'default_font' => 'arial',
                'simpleTables' => true,    // Faster table rendering
                'packTableData' => true,   // Reduce memory for large tables
            ]);

            $mpdf->WriteHTML('<style>
                body, table, td { font-family: Arial, sans-serif; font-size: 8pt; line-height: 1.2; }
                table { border-collapse: collapse; width: 100%; }
                td { padding: 2px 4px; }
            </style>', \Mpdf\HTMLParserMode::HEADER_CSS);
            $mpdf->WriteHTML($excelHtml);

            // Simpan file
            $filename = 'skrd_report_' . date('Ymd_His') . '.pdf';
            $outputPath = storage_path('app/private/documents/' . $filename);

            if (! file_exists(dirname($outputPath))) {
                Storage::makeDirectory('private/documents');
            }

            $mpdf->Output($outputPath, 'F');

            $filePath = 'private/documents/' . $filename;
            $fullPath = storage_path('app/' . $filePath);

            if (! Storage::disk('local')->exists($filePath)) {
                return response()->json(['message' => 'File not found'], 404);
            }

            return response()->file($outputPath);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error generating SKRD report: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile()),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function generateSKRDReportExcel(Request $request)
    {
        // Increase limits for large reports
        set_time_limit(300);
        ini_set('memory_limit', '512M');
        ini_set('pcre.backtrack_limit', '5000000');

        $user = JWTAuth::parseToken()->authenticate();
        if (! $user) {
            return response()->json(['status' => 201, 'message' => "You're not authenticated"]);
        }

        $userData = User::with('masterOpd')->find($user->id);
        $masterOpdId = $request->opd_id;

        try {
            // Load template Excel
            $templatePath = storage_path('app/templates/laporan_template_report_skrd.xlsx');
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Data OPD
            $this->replaceTag($sheet, '{start_date}', $request->start_date);
            $this->replaceTag($sheet, '{end_date}', $request->end_date);

            // Get transactions with cart and objects (status 3 = lunas)
            $transactionsQuery = Transaksi::with([
                'transaksiObject.object.opd:id,nama_opd',
                'transaksiObject.object.lokasi:id,nama_lokasi',
                'transaksiObject.object.retribusi:id,nama_retribusi',
                'transaksiObject.object.rincianRetribusi:id,rincian_retribusi',
                'subject',
                'subject.masterOpd:id,nama_opd',
                'tipePembayaran',
                'installmentTransaction',
                'document',
            ])
                ->where('master_status_pembayaran_id', 3)
                ->whereBetween('payment_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);

            // Filter by OPD if provided
            if ($masterOpdId) {
                $transactionsQuery->whereHas('transaksiObject.object', function ($query) use ($masterOpdId) {
                    $query->where('master_opd_id', $masterOpdId);
                });
            }

            // Filter by location for role 5 users with non-zero location
            if ($user->master_role_id == 5 && $user->master_lokasi_id != 0) {
                $transactionsQuery->whereHas('transaksiObject.object', function ($query) use ($user) {
                    $query->where('master_lokasi_id', $user->master_lokasi_id);
                });
            }

            $transactions = $transactionsQuery->orderBy('payment_date', 'desc')->get();

            // Get paid installment transactions (status 9 = lunas untuk installment)
            $paidInstallmentsQuery = InstallmentTransaction::with([
                'transaksi.transaksiObject.object.opd:id,nama_opd',
                'transaksi.transaksiObject.object.lokasi:id,nama_lokasi',
                'transaksi.transaksiObject.object.retribusi:id,nama_retribusi',
                'transaksi.transaksiObject.object.rincianRetribusi:id,rincian_retribusi',
                'transaksi.subject',
                'transaksi.subject.masterOpd:id,nama_opd',
                'transaksi.document',
                'tipePembayaran',
            ])
                ->where('master_status_pembayaran_id', 9)
                ->whereBetween('paid_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);

            // Filter by OPD if provided
            if ($masterOpdId) {
                $paidInstallmentsQuery->whereHas('transaksi.transaksiObject.object', function ($query) use ($masterOpdId) {
                    $query->where('master_opd_id', $masterOpdId);
                });
            }

            // Filter by location for role 5 users with non-zero location
            if ($user->master_role_id == 5 && $user->master_lokasi_id != 0) {
                $paidInstallmentsQuery->whereHas('transaksi.transaksiObject.object', function ($query) use ($user) {
                    $query->where('master_lokasi_id', $user->master_lokasi_id);
                });
            }

            $paidInstallments = $paidInstallmentsQuery->orderBy('created_at', 'desc')->get();

            // Get OPD name - query directly from MasterOpd
            $opdName = 'Semua OPD';
            if ($masterOpdId) {
                $masterOpd = MasterOpd::find($masterOpdId);
                $opdName = $masterOpd ? $masterOpd->nama_opd : '-';
            }

            $this->replaceTag($sheet, '{opd}', $opdName);

            $startRow = 6;

            // Header table
            $this->createSKRDTableHeader($sheet, $startRow);

            // Fill data
            $this->fillSKRDTransactionData($sheet, $transactions, $startRow + 1);

            // Fill installment data
            $currentRow = $startRow + 1 + $transactions->count();
            $startingSerialNumber = $transactions->count() + 1;
            $this->fillSKRDInstallmentData($sheet, $paidInstallments, $currentRow, $startingSerialNumber);

            // Page setup for Excel
            $pageSetup = $sheet->getPageSetup();
            $pageSetup->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
            $pageSetup->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
            $pageSetup->setFitToWidth(1);
            $pageSetup->setFitToHeight(0);

            $sheet->getPageMargins()
                ->setTop(0.5)
                ->setBottom(0.5)
                ->setLeft(0.5)
                ->setRight(0.5);

            // Save as Excel file
            $filename = 'skrd_report_' . date('Ymd_His') . '.xlsx';
            $outputPath = storage_path('app/private/documents/' . $filename);

            if (! file_exists(dirname($outputPath))) {
                Storage::makeDirectory('private/documents');
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($outputPath);

            return response()->download($outputPath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error generating SKRD report: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile()),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    private function fillSKRDTransactionData($sheet, $transactions, $startRow)
    {
        $row = $startRow;
        $serialNumber = 1;

        foreach ($transactions as $transaction) {
            $this->addSKRDTransactionRow($sheet, $transaction, $row, $serialNumber);

            // Apply alternating row colors
            $rowStyle = [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $row % 2 == 0 ? 'E7E6E6' : 'FFFFFF'],
                ],
            ];

            $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray($rowStyle);
            $row++;
            $serialNumber++;
        }

        return $row;
    }

    private function fillSKRDInstallmentData($sheet, $paidInstallments, $startRow, $startingSerialNumber = 1)
    {
        $row = $startRow;
        $serialNumber = $startingSerialNumber;

        foreach ($paidInstallments as $installment) {
            $transaction = $installment->transaksi;

            // Skip if transaction or objects don't exist
            if (! $transaction || ! $transaction->transaksiObject || $transaction->transaksiObject->isEmpty()) {
                continue;
            }

            $this->addSKRDInstallmentRow($sheet, $installment, $transaction, $row, $serialNumber);

            // Apply alternating row colors
            $rowStyle = [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $row % 2 == 0 ? 'E7E6E6' : 'FFFFFF'],
                ],
            ];

            $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray($rowStyle);
            $row++;
            $serialNumber++;
        }

        return $row;
    }

    private function addSKRDTransactionRow($sheet, $transaction, $row, $serialNumber)
    {
        // Get first object for OPD name
        $opdName = $transaction->transaksiObject->isNotEmpty()
            ? $transaction->transaksiObject->first()->object->opd->nama_opd ?? '-'
            : '-';

        // Get first object for location name
        $lokasiName = $transaction->transaksiObject->isNotEmpty()
            ? $transaction->transaksiObject->first()->object->lokasi->nama_lokasi ?? '-'
            : '-';

        // Get first object for retribusi data
        $jenisRetribusi = $transaction->transaksiObject->isNotEmpty()
            ? $transaction->transaksiObject->first()->object->retribusi->nama_retribusi ?? '-'
            : '-';

        $rincianRetribusi = $transaction->transaksiObject->isNotEmpty()
            ? $transaction->transaksiObject->first()->object->rincianRetribusi->rincian_retribusi ?? '-'
            : '-';

        $subRincianRetribusi = $transaction->transaksiObject->isNotEmpty()
            ? $transaction->transaksiObject->first()->object->sub_rincian_retribusi ?? '-'
            : '-';

        // Get SKRD document (master_tipe_dokumen_id = 2) from eager-loaded relation
        $skrdDocument = $transaction->document->where('master_tipe_dokumen_id', 2)->first();
        $skrdNumber = $skrdDocument ? $skrdDocument->document_number : '-';
        $skrdAmount = $transaction->total_amount;

        // Payment method
        $paymentMethod = $transaction->tipePembayaran->tipe_pembayaran ?? '-';

        // Subject name
        $subjectName = $transaction->subject_name ?? '-';

        // Fill cells
        $sheet->setCellValue('A' . $row, $serialNumber);
        $sheet->setCellValue('B' . $row, $transaction->payment_date ? date('d-m-Y', strtotime($transaction->payment_date)) : '-');
        $sheet->setCellValue('C' . $row, $opdName);
        $sheet->setCellValue('D' . $row, $lokasiName);
        $sheet->setCellValue('E' . $row, $subjectName);
        $sheet->setCellValue('F' . $row, $jenisRetribusi);
        $sheet->setCellValue('G' . $row, $rincianRetribusi);
        $sheet->setCellValue('H' . $row, $subRincianRetribusi);
        $sheet->setCellValue('I' . $row, $skrdNumber);
        $sheet->setCellValue('J' . $row, $skrdAmount);
        $sheet->setCellValue('K' . $row, $paymentMethod);
    }

    private function addSKRDInstallmentRow($sheet, $installment, $transaction, $row, $serialNumber)
    {
        // Get first object for OPD name
        $opdName = $transaction->transaksiObject->isNotEmpty()
            ? $transaction->transaksiObject->first()->object->opd->nama_opd ?? '-'
            : '-';

        // Get first object for location name
        $lokasiName = $transaction->transaksiObject->isNotEmpty()
            ? $transaction->transaksiObject->first()->object->lokasi->nama_lokasi ?? '-'
            : '-';

        // Get first object for retribusi data
        $jenisRetribusi = $transaction->transaksiObject->isNotEmpty()
            ? $transaction->transaksiObject->first()->object->retribusi->nama_retribusi ?? '-'
            : '-';

        $rincianRetribusi = $transaction->transaksiObject->isNotEmpty()
            ? $transaction->transaksiObject->first()->object->rincianRetribusi->rincian_retribusi ?? '-'
            : '-';

        $subRincianRetribusi = $transaction->transaksiObject->isNotEmpty()
            ? $transaction->transaksiObject->first()->object->sub_rincian_retribusi ?? '-'
            : '-';

        // Get SKRD document (master_tipe_dokumen_id = 2) from eager-loaded relation, matching installment_id
        $skrdDocument = $transaction->document
            ->where('master_tipe_dokumen_id', 2)
            ->where('installment_id', $installment->id)
            ->first();
        $skrdNumber = $skrdDocument ? $skrdDocument->document_number : '-';
        $skrdAmount = $installment->grand_total;

        // Payment method from installment
        $paymentMethod = $installment->tipePembayaran->tipe_pembayaran ?? '-';

        // Subject name
        $subjectName = $transaction->subject_name ?? '-';

        // Fill cells
        $sheet->setCellValue('A' . $row, $serialNumber);
        $sheet->setCellValue('B' . $row, $installment->paid_date ? date('d-m-Y', strtotime($installment->paid_date)) : '-');
        $sheet->setCellValue('C' . $row, $opdName);
        $sheet->setCellValue('D' . $row, $lokasiName);
        $sheet->setCellValue('E' . $row, $subjectName);
        $sheet->setCellValue('F' . $row, $jenisRetribusi);
        $sheet->setCellValue('G' . $row, $rincianRetribusi);
        $sheet->setCellValue('H' . $row, $subRincianRetribusi);
        $sheet->setCellValue('I' . $row, $skrdNumber);
        $sheet->setCellValue('J' . $row, $skrdAmount);
        $sheet->setCellValue('K' . $row, $paymentMethod);
    }

    private function createSKRDTableHeader($sheet, $startRow)
    {
        $headers = [
            'A' => 'No.',
            'B' => 'TANGGAL',
            'C' => 'NAMA OPD',
            'D' => 'NAMA LOKASI',
            'E' => 'NAMA SUBJEK / WAJIB RETRIBUSI',
            'F' => 'JENIS RETRIBUSI',
            'G' => 'RINCIAN RETRIBUSI',
            'H' => 'SUB RINCIAN RETRIBUSI',
            'I' => 'NO. SKRD',
            'J' => 'NOMINA SKRD',
            'K' => 'METODE PEMBAYARAN',
        ];

        // Set header values
        foreach ($headers as $column => $header) {
            $sheet->setCellValue($column . $startRow, $header);
        }

        // Apply header style
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        $sheet->getStyle('A' . $startRow . ':K' . $startRow)->applyFromArray($headerStyle);

        // Auto width columns
        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    public function updateUsedTime(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            $request->validate([
                'reference_no' => 'required|string',
            ]);

            $transaksi = Transaksi::with([
                'transaksiObject.object.opd',
                'transaksiObject.object.retribusi',
                'transaksiObject.object.rincianRetribusi',
                'transaksiObject.object.lokasi',
                'transaksiObject.object.satuan',
                'transaksiObject.object.tpj',
                'transaksiObject.object.statusObjek',
                'transaksiObject.object.typeObject',
                'statusPembayaran',
                'tipePembayaran',
                'subject',
                'installmentTransaction',
            ])->where('reference_no', $request->reference_no)->first();

            if (! $transaksi) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Transaction not found',
                ]);
            }

            $transaksi->update([
                'used_time' => Carbon::now(),
            ]);

            // Refresh to get updated data with relations
            $transaksi->refresh();

            return response()->json([
                'status' => 200,
                'message' => 'Used time updated successfully',
                'data' => $transaksi,
            ]);
        }

        return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
    }

    /**
     * External payment callback - updates transaction status to paid (3)
     * Called by external website with secret token authentication (via middleware)
     */
    public function externalPaymentCallback(Request $request)
    {

        $request->validate([
            'no_reference' => 'required|string',
        ]);

        $transaksi = Transaksi::with(['subject', 'transaksiObject.object.opd', 'installmentTransaction', 'transaksiObject.object.lokasi', 'transaksiObject.object.retribusi', 'transaksiObject.object'])->where('reference_no', $request->no_reference)->first();
        $type = 'Transaction';
        if (! $transaksi) {
            $installmentTransaction = InstallmentTransaction::where('installment_old_ref_id', $request->no_reference)->first();
            $type = 'Installment';

            if (! $installmentTransaction) {
                Log::info('Callback Response: Failed');
                Log::info('Transaction ID : ' . $request->no_reference);
                Log::info('Transaction Type : ' . $type);

                return response()->json([
                    'status' => 404,
                    'message' => 'Transaction not found',
                    'data' => null,
                ], 404);
            }

            if ($installmentTransaction->master_status_pembayaran_id == 9) {
                return response()->json([
                    'status' => 201,
                    'message' => 'Pembayaran sudah diproses sebelumnya',
                    'data' => $installmentTransaction,
                ]);
            }

            DB::transaction(function () use ($installmentTransaction, $request, $type) {
                $installmentTransaction->update([
                    'paid_date' => Carbon::now('Asia/Jakarta'),
                    'master_status_pembayaran_id' => 9,
                ]);
                Log::info('Callback Response: Paid');
                Log::info('Transaction ID : ' . $request->no_reference);
                Log::info('Transaction Type : ' . $type);

                $transaksi = Transaksi::with(['subject', 'transaksiObject.object.opd', 'installmentTransaction', 'transaksiObject.object.lokasi', 'transaksiObject.object.retribusi', 'transaksiObject.object'])->where('reference_no', $installmentTransaction->transaction_id)->first();
                $master_location_id = $transaksi->transaksiObject->first()->object->master_lokasi_id ?? null;

                $this->sendNotificationPaidInstallmenttoUser(
                    $installmentTransaction->reference_no,
                    $transaksi->subject_id,
                    'paid_transaction',
                    'Pembayaran Berhasil',
                    'Payment Success',
                    'Angsuran dengan nomor ' . $installmentTransaction->installment_old_ref_id . ' telah berhasil dibayar.',
                    'New Transaction with reference number ' . $installmentTransaction->installment_old_ref_id . ' has been paid.',
                );
                $this->sendNotificationPaidInstallmenttoAdmin(
                    $transaksi->reference_no,
                    $master_location_id,
                    5,
                    'paid_transaction',
                    'Status Angsuran Berubah',
                    'Installment Status Changed',
                    'Angsuran pada transaksi dengan nomor ' . $installmentTransaction->installment_old_ref_id . ' telah dilakukan pembayaran.',
                    'Installment with reference number ' . $installmentTransaction->installment_old_ref_id . ' has been paid.',
                );

                // Cek apakah dokumen SPDORD untuk installment ini sudah ada
                $documentspdord = Document::where('transaksi_id', $installmentTransaction->transaction_id)
                    ->where('master_tipe_dokumen_id', 1)
                    ->where('installment_id', $installmentTransaction->id)
                    ->first();

                if ($documentspdord == null) {
                    $opd = $transaksi->transaksiObject->first()->object->master_opd_id;
                    $lokasi = $transaksi->transaksiObject->first()->object->master_lokasi_id;
                    $kaban = MasterChief::with('masterOpd', 'masterLokasi')->where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('master_lokasi_id', $transaksi->transaksiObject->first()->object->master_lokasi_id)->where('is_active', 1)->first();
                    if ($kaban == null) {
                        $kaban = MasterChief::where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('is_active', 1)->first();
                    }
                    if ($kaban == null) {
                        throw new \Exception('Master Chief tidak ditemukan untuk OPD: ' . $opd);
                    }
                    $documentspdord = Document::create([
                        'master_tipe_dokumen_id' => 1,
                        'transaksi_id' => $installmentTransaction->transaction_id,
                        'installment_id' => $installmentTransaction->id,
                        'document_number' => 'SPDORD',
                        'linked_document_number' => 'SKRD',
                        'issue_date' => Carbon::now('Asia/Jakarta'),
                        'master_chief_id' => $kaban->id,
                    ]);

                    $time = Carbon::now('Asia/Jakarta')->format('dmy');
                    $formattedId = str_pad($documentspdord->id, 4, '0', STR_PAD_LEFT);
                    $documentspdord->update([
                        'document_number' => 'SPdORD' . '-' . $opd . '-' . $lokasi . '-' . $time . '-' . $formattedId,
                    ]);
                }

                // Cek apakah dokumen SKRD untuk installment ini sudah ada
                if ($transaksi) {
                    $documentskrd = Document::where('transaksi_id', $installmentTransaction->transaction_id)
                        ->where('master_tipe_dokumen_id', 2)
                        ->where('installment_id', $installmentTransaction->id)
                        ->first();

                    if ($documentskrd == null) {
                        $opd = $transaksi->transaksiObject->first()->object->master_opd_id;
                        $lokasi = $transaksi->transaksiObject->first()->object->master_lokasi_id;
                        $kaban = MasterChief::with('masterOpd', 'masterLokasi')->where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('master_lokasi_id', $transaksi->transaksiObject->first()->object->master_lokasi_id)->where('is_active', 1)->first();
                        if ($kaban == null) {
                            $kaban = MasterChief::where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('is_active', 1)->first();
                        }
                        $documentskrd = Document::create([
                            'master_tipe_dokumen_id' => 2,
                            'transaksi_id' => $installmentTransaction->transaction_id,
                            'installment_id' => $installmentTransaction->id,
                            'document_number' => 'SKRD',
                            'linked_document_number' => $documentspdord->document_number,
                            'issue_date' => Carbon::now('Asia/Jakarta'),
                            'master_chief_id' => $kaban->id,
                        ]);

                        $rincianRetribusi = $transaksi->transaksiObject->first()->object->rincianRetribusi->rincian_retribusi;
                        $subRincianRetribusi = $transaksi->transaksiObject->first()->object->sub_rincian_retribusi;
                        $totalAmount = $installmentTransaction->payment_amount;
                        $time = Carbon::now('Asia/Jakarta')->format('dmy');
                        $formattedId = str_pad($documentskrd->id, 4, '0', STR_PAD_LEFT);
                        $documentskrd->update([
                            'document_number' => 'SKRD-' . $opd . '-' . $lokasi . '-' . $rincianRetribusi . '-' . $subRincianRetribusi . '-' . 'Rp. ' . $totalAmount . '-' . $time . '-' . $formattedId,
                        ]);
                        $documentspdord->update([
                            'linked_document_number' => $documentskrd->document_number,
                        ]);
                    }
                }

                // Check if all installments are paid and update transaction status to 9 (paid)
                $allInstallments = InstallmentTransaction::where('transaction_id', $transaksi->reference_no)->get();
                $paidInstallmentsCount = $allInstallments->where('master_status_pembayaran_id', 9)->count();
                $totalInstallments = $allInstallments->first()->installment_total ?? 0;

                if ($paidInstallmentsCount == $totalInstallments && $totalInstallments > 0) {
                    if ($transaksi) {
                        $transaksi->update([
                            'master_status_pembayaran_id' => 9,
                            'payment_date' => Carbon::now('Asia/Jakarta'),
                        ]);
                    }
                }
            });

            $installmentTransaction->refresh();

            return response()->json([
                'status' => 200,
                'message' => 'Pembayaran Berhasil',
                'data' => $installmentTransaction,
            ]);
        }

        // Check if already paid
        if ($transaksi->master_status_pembayaran_id == 3) {
            return response()->json([
                'status' => 201,
                'message' => 'Pembayaran sudah diproses sebelumnya',
                'data' => $transaksi,
            ]);
        }

        // Use DB transaction to prevent race condition
        DB::transaction(function () use ($transaksi) {
            $transaksi->update([
                'payment_date' => Carbon::now('Asia/Jakarta'),
                'master_status_pembayaran_id' => 3,
            ]);

            $this->sendNotificationPaidtoUser(
                $transaksi->reference_no,
                $transaksi->subject_id,
                'paid_transaction',
                'Pembayaran Berhasil',
                'Payment Success',
                'Transaksi dengan nomor ' . $transaksi->reference_no . ' telah berhasil dibayar.',
                'New Transaction with reference number ' . $transaksi->reference_no . ' has been paid.',
            );
            $master_location_id = $transaksi->transaksiObject->first()->object->master_lokasi_id ?? null;
            $this->sendNotificationPaidtoAdmin(
                $transaksi->reference_no,
                $master_location_id,
                5,
                'paid_transaction',
                'Status Transaksi Berubah',
                'Transaction Status Changed',
                'Transaksi dengan nomor ' . $transaksi->reference_no . ' telah dilakukan pembayaran.',
                'Transaction with reference number ' . $transaksi->reference_no . ' has been paid.',
            );
            // Check if SPdORD document already exists
            $documentspdord = Document::where('transaksi_id', $transaksi->reference_no)
                ->where('master_tipe_dokumen_id', 1)
                ->first();

            if ($documentspdord == null) {
                $opd = $transaksi->transaksiObject->first()->object->master_opd_id;
                $lokasi = $transaksi->transaksiObject->first()->object->master_lokasi_id;
                $kaban = MasterChief::with('masterOpd', 'masterLokasi')->where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('master_lokasi_id', $transaksi->transaksiObject->first()->object->master_lokasi_id)->where('is_active', 1)->first();
                if ($kaban == null) {
                    $kaban = MasterChief::where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('is_active', 1)->first();
                }
                if ($kaban == null) {
                    throw new \Exception('Master Chief tidak ditemukan untuk OPD: ' . $opd);
                }
                $documentspdord = Document::create([
                    'master_tipe_dokumen_id' => 1,
                    'transaksi_id' => $transaksi->reference_no,
                    'document_number' => 'SPDORD',
                    'linked_document_number' => 'SKRD',
                    'issue_date' => Carbon::now('Asia/Jakarta'),
                    'master_chief_id' => $kaban->id,
                ]);


                $time = Carbon::now('Asia/Jakarta')->format('dmy');
                $formattedId = str_pad($documentspdord->id, 4, '0', STR_PAD_LEFT);
                $documentspdord->update([
                    'document_number' => 'SPdORD' . '-' . $opd . '-' . $lokasi . '-' . $time . '-' . $formattedId,
                ]);
            }

            // Check if SKRD document already exists
            $documentskrd = Document::where('transaksi_id', $transaksi->reference_no)
                ->where('master_tipe_dokumen_id', 2)
                ->whereNull('installment_id')
                ->first();

            if ($documentskrd == null) {
                $opd = $transaksi->transaksiObject->first()->object->master_opd_id;
                $lokasi = $transaksi->transaksiObject->first()->object->master_lokasi_id;
                $kaban = MasterChief::with('masterOpd', 'masterLokasi')->where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('master_lokasi_id', $transaksi->transaksiObject->first()->object->master_lokasi_id)->where('is_active', 1)->first();
                if ($kaban == null) {
                    $kaban = MasterChief::where('master_opd_id', $transaksi->transaksiObject->first()->object->master_opd_id)->where('is_active', 1)->first();
                }
                if ($kaban == null) {
                    throw new \Exception('Master Chief tidak ditemukan untuk OPD: ' . $opd);
                }
                $documentskrd = Document::create([
                    'master_tipe_dokumen_id' => 2,
                    'transaksi_id' => $transaksi->reference_no,
                    'document_number' => 'SKRD',
                    'linked_document_number' => $documentspdord->document_number,
                    'issue_date' => Carbon::now('Asia/Jakarta'),
                    'master_chief_id' => $kaban->id,
                ]);

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

        $transaksi = Transaksi::with([
            'transaksiObject.object.opd',
            'subject',
            'transaksiObject.object.lokasi',
            'transaksiObject.object.retribusi',
            'transaksiObject.object',
        ])->where('reference_no', $request->no_reference)->first();
        Log::info('Callback Response: Paid');
        Log::info('Transaction ID : ' . $request->no_reference);
        Log::info('Transaction Type : ' . $type);

        return response()->json([
            'status' => 200,
            'message' => 'Pembayaran Berhasil',
            'data' => $transaksi,
        ]);
    }

    public function generateRevenueRecap(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');
        ini_set('pcre.backtrack_limit', '5000000');

        $user = JWTAuth::parseToken()->authenticate();
        if (! $user) {
            return response()->json(['status' => 201, 'message' => "You're not authenticated"]);
        }

        $userData = User::with('masterOpd')->find($user->id);
        $masterOpdId = $request->opd_id;

        try {
            $templatePath = storage_path('app/templates/revenue_template.xlsx');
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            $this->replaceTag($sheet, '{start_date}', $request->start_date);
            $this->replaceTag($sheet, '{end_date}', $request->end_date);

            // Get transactions with all relations
            $transactionsQuery = Transaksi::with([
                'transaksiObject.object.opd:id,nama_opd',
                'transaksiObject.object.lokasi:id,nama_lokasi,master_opd_id',
                'transaksiObject.object.retribusi:id,nama_retribusi',
                'transaksiObject.object.subRincianRetribusi:id,nama_sub_rincian_retribusi',
            ])
                ->where('master_status_pembayaran_id', 3)
                ->whereBetween('payment_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);

            if ($masterOpdId) {
                $transactionsQuery->whereHas('transaksiObject.object', function ($query) use ($masterOpdId) {
                    $query->where('master_opd_id', $masterOpdId);
                });
            }

            if ($user->master_role_id == 5 && $user->master_lokasi_id != 0) {
                $transactionsQuery->whereHas('transaksiObject.object', function ($query) use ($user) {
                    $query->where('master_lokasi_id', $user->master_lokasi_id);
                });
            }

            $transactions = $transactionsQuery->get();

            // Get installments with all relations
            $paidInstallmentsQuery = InstallmentTransaction::with([
                'transaksi.transaksiObject.object.opd:id,nama_opd',
                'transaksi.transaksiObject.object.lokasi:id,nama_lokasi,master_opd_id',
                'transaksi.transaksiObject.object.retribusi:id,nama_retribusi',
                'transaksi.transaksiObject.object.subRincianRetribusi:id,nama_sub_rincian_retribusi',
                'transaksi.subject',
                'transaksi.subject.masterOpd:id,nama_opd',
                'tipePembayaran',
                'transaksi.document',
            ])
                ->where('master_status_pembayaran_id', 9)
                ->whereBetween('paid_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);

            if ($masterOpdId) {
                $paidInstallmentsQuery->whereHas('transaksi.transaksiObject.object', function ($query) use ($masterOpdId) {
                    $query->where('master_opd_id', $masterOpdId);
                });
            }

            if ($user->master_role_id == 5 && $user->master_lokasi_id != 0) {
                $paidInstallmentsQuery->whereHas('transaksi.transaksiObject.object', function ($query) use ($user) {
                    $query->where('master_lokasi_id', $user->master_lokasi_id);
                });
            }

            $paidInstallments = $paidInstallmentsQuery->orderBy('created_at', 'desc')->get();
            $opdName = 'Semua OPD';
            if ($masterOpdId) {
                $masterOpd = MasterOpd::find($masterOpdId);
                $opdName = $masterOpd ? $masterOpd->nama_opd : '-';
            }

            $this->replaceTag($sheet, '{opd}', $opdName);

            $startRow = 6;

            $this->createRevenueRecapTableHeader($sheet, $startRow);

            // Build hierarchical data structure
            $hierarchicalData = $this->buildHierarchicalRevenueData($transactions, $paidInstallments);

            $currentRow = $this->fillRevenueRecapData($sheet, $hierarchicalData, $startRow + 1);

            $this->addRevenueRecapSummary($sheet, $transactions, $paidInstallments, $currentRow + 1);

            $pageSetup = $sheet->getPageSetup();
            $pageSetup->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
            $pageSetup->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
            $pageSetup->setFitToWidth(1);
            $pageSetup->setFitToHeight(0);

            $sheet->getPageMargins()
                ->setTop(0.5)
                ->setBottom(0.5)
                ->setLeft(0.5)
                ->setRight(0.5);

            $spreadsheet->setActiveSheetIndex(0);
            $htmlWriter = new \PhpOffice\PhpSpreadsheet\Writer\Html($spreadsheet);
            ob_start();
            $htmlWriter->save('php://output');
            $excelHtml = ob_get_clean();

            $excelHtml = preg_replace('/\s*border="[^"]*"/i', '', $excelHtml);
            $excelHtml = preg_replace('/border\s*:\s*[^;\"]+;?/i', '', $excelHtml);

            $preferredTemp = storage_path('app/mpdf_temp');
            $mpdfTemp = null;
            try {
                if (! file_exists($preferredTemp)) {
                    mkdir($preferredTemp, 0777, true);
                }
                if (! file_exists($preferredTemp . '/mpdf')) {
                    mkdir($preferredTemp . '/mpdf', 0777, true);
                }
                @chmod($preferredTemp, 0777);
                @chmod($preferredTemp . '/mpdf', 0777);
                if (is_writable($preferredTemp . '/mpdf')) {
                    $mpdfTemp = $preferredTemp;
                }
            } catch (\Throwable $t) {
                $mpdfTemp = null;
            }
            if ($mpdfTemp === null || ! is_writable($mpdfTemp . '/mpdf')) {
                $sys = sys_get_temp_dir();
                if (! file_exists($sys . '/mpdf')) {
                    @mkdir($sys . '/mpdf', 0777, true);
                    @chmod($sys . '/mpdf', 0777);
                }
                if (is_writable($sys . '/mpdf')) {
                    $mpdfTemp = $sys;
                } else {
                    if (is_writable($preferredTemp)) {
                        $mpdfTemp = $preferredTemp;
                    }
                }
            }
            if ($mpdfTemp === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No writable temp directory available for mPDF. Please ensure storage/app/mpdf_temp is writable.',
                ], 500);
            }

            $mpdf = new \Mpdf\Mpdf([
                'format' => 'A4-L',
                'margin_top' => 5,
                'margin_bottom' => 5,
                'margin_left' => 5,
                'margin_right' => 5,
                'tempDir' => $mpdfTemp,
                'mode' => 'utf-8',
                'default_font' => 'arial',
                'simpleTables' => true,
                'packTableData' => true,
            ]);

            $mpdf->WriteHTML('<style>
                body, table, td { font-family: Arial, sans-serif; font-size: 8pt; line-height: 1.2; }
                table { border-collapse: collapse; width: 100%; }
                td { padding: 2px 4px; }
            </style>', \Mpdf\HTMLParserMode::HEADER_CSS);
            $mpdf->WriteHTML($excelHtml);

            $filename = 'revenue_recap_' . date('Ymd_His') . '.pdf';
            $outputPath = storage_path('app/private/documents/' . $filename);

            if (! file_exists(dirname($outputPath))) {
                Storage::makeDirectory('private/documents');
            }

            $mpdf->Output($outputPath, 'F');

            $filePath = 'private/documents/' . $filename;

            if (! Storage::disk('local')->exists($filePath)) {
                return response()->json(['message' => 'File not found'], 404);
            }

            return response()->file($outputPath);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error generating revenue recap: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile()),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function generateRevenueRecapExcel(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');
        ini_set('pcre.backtrack_limit', '5000000');
        $user = JWTAuth::parseToken()->authenticate();
        if (! $user) {
            return response()->json(['status' => 201, 'message' => "You're not authenticated"]);
        }

        $userData = User::with('masterOpd')->find($user->id);
        $masterOpdId = $request->opd_id;

        try {
            $templatePath = storage_path('app/templates/revenue_template.xlsx');
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            $this->replaceTag($sheet, '{start_date}', $request->start_date);
            $this->replaceTag($sheet, '{end_date}', $request->end_date);

            // Get transactions with all relations
            $transactionsQuery = Transaksi::with([
                'transaksiObject.object.opd:id,nama_opd',
                'transaksiObject.object.lokasi:id,nama_lokasi,master_opd_id',
                'transaksiObject.object.retribusi:id,nama_retribusi',
                'transaksiObject.object.subRincianRetribusi:id,nama_sub_rincian_retribusi',
            ])
                ->where('master_status_pembayaran_id', 3)
                ->whereBetween('payment_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);

            if ($masterOpdId) {
                $transactionsQuery->whereHas('transaksiObject.object', function ($query) use ($masterOpdId) {
                    $query->where('master_opd_id', $masterOpdId);
                });
            }

            if ($user->master_role_id == 5 && $user->master_lokasi_id != 0) {
                $transactionsQuery->whereHas('transaksiObject.object', function ($query) use ($user) {
                    $query->where('master_lokasi_id', $user->master_lokasi_id);
                });
            }

            $transactions = $transactionsQuery->get();

            // Get installments with all relations
            $paidInstallmentsQuery = InstallmentTransaction::with([
                'transaksi.transaksiObject.object.opd:id,nama_opd',
                'transaksi.transaksiObject.object.lokasi:id,nama_lokasi,master_opd_id',
                'transaksi.transaksiObject.object.retribusi:id,nama_retribusi',
                'transaksi.transaksiObject.object.subRincianRetribusi:id,nama_sub_rincian_retribusi',
                'transaksi.subject',
                'transaksi.subject.masterOpd:id,nama_opd',
                'tipePembayaran',
                'transaksi.document',
            ])
                ->where('master_status_pembayaran_id', 9)
                ->whereBetween('paid_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);

            if ($masterOpdId) {
                $paidInstallmentsQuery->whereHas('transaksi.transaksiObject.object', function ($query) use ($masterOpdId) {
                    $query->where('master_opd_id', $masterOpdId);
                });
            }

            if ($user->master_role_id == 5 && $user->master_lokasi_id != 0) {
                $paidInstallmentsQuery->whereHas('transaksi.transaksiObject.object', function ($query) use ($user) {
                    $query->where('master_lokasi_id', $user->master_lokasi_id);
                });
            }

            $paidInstallments = $paidInstallmentsQuery->orderBy('created_at', 'desc')->get();

            $opdName = 'Semua OPD';
            if ($masterOpdId) {
                $masterOpd = MasterOpd::find($masterOpdId);
                $opdName = $masterOpd ? $masterOpd->nama_opd : '-';
            }

            $this->replaceTag($sheet, '{opd}', $opdName);

            $startRow = 6;

            $this->createRevenueRecapTableHeader($sheet, $startRow);

            // Build hierarchical data structure
            $hierarchicalData = $this->buildHierarchicalRevenueData($transactions, $paidInstallments);

            $currentRow = $this->fillRevenueRecapData($sheet, $hierarchicalData, $startRow + 1);

            $this->addRevenueRecapSummary($sheet, $transactions, $paidInstallments, $currentRow + 1);

            $pageSetup = $sheet->getPageSetup();
            $pageSetup->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
            $pageSetup->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
            $pageSetup->setFitToWidth(1);
            $pageSetup->setFitToHeight(0);

            $sheet->getPageMargins()
                ->setTop(0.5)
                ->setBottom(0.5)
                ->setLeft(0.5)
                ->setRight(0.5);

            $filename = 'revenue_recap_' . date('Ymd_His') . '.xlsx';
            $outputPath = storage_path('app/private/documents/' . $filename);

            if (! file_exists(dirname($outputPath))) {
                Storage::makeDirectory('private/documents');
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($outputPath);

            return response()->download($outputPath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error generating revenue recap: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile()),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    private function createRevenueRecapTableHeader($sheet, $startRow)
    {
        $headers = [
            'A' => 'No.',
            'B' => 'TANGGAL',
            'C' => 'NAMA OPD',
            'D' => 'LOKASI',
            'E' => 'JENIS RETRIBUSI',
            'F' => 'SUB RINCIAN RETRIBUSI',
            'G' => 'NOMINAL SKRD',
        ];

        foreach ($headers as $column => $header) {
            $sheet->setCellValue($column . $startRow, $header);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        $sheet->getStyle('A' . $startRow . ':G' . $startRow)->applyFromArray($headerStyle);

        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    private function buildHierarchicalRevenueData($transactions, $paidInstallments)
    {
        $data = [];

        // Process regular transactions
        foreach ($transactions as $transaction) {
            $transaksiObject = $transaction->transaksiObject->first();
            if (! $transaksiObject || ! $transaksiObject->object) {
                continue;
            }

            $object = $transaksiObject->object;
            $opdId = $object->master_opd_id;
            $opdName = $object->opd->nama_opd ?? '-';
            $lokasiId = $object->master_lokasi_id;
            $lokasiName = $object->lokasi->nama_lokasi ?? '-';
            $jenisRetribusi = $object->retribusi->nama_retribusi ?? '-';
            $subRincianRetribusi = $object->subRincianRetribusi->nama_sub_rincian_retribusi ?? '-';
            $paymentDate = $transaction->payment_date ? date('Y-m-d', strtotime($transaction->payment_date)) : '-';

            $key = $opdId . '|' . $lokasiId . '|' . $jenisRetribusi . '|' . $subRincianRetribusi . '|' . $paymentDate;

            if (! isset($data[$key])) {
                $data[$key] = [
                    'opd_id' => $opdId,
                    'opd_name' => $opdName,
                    'lokasi_id' => $lokasiId,
                    'lokasi_name' => $lokasiName,
                    'jenis_retribusi' => $jenisRetribusi,
                    'sub_rincian_retribusi' => $subRincianRetribusi,
                    'payment_date' => $paymentDate,
                    'total_amount' => 0,
                ];
            }

            $data[$key]['total_amount'] += $transaction->total_amount;
        }

        // Process installment transactions
        foreach ($paidInstallments as $installment) {
            $transaction = $installment->transaksi;
            if (! $transaction) {
                continue;
            }

            $transaksiObject = $transaction->transaksiObject->first();
            if (! $transaksiObject || ! $transaksiObject->object) {
                continue;
            }

            $object = $transaksiObject->object;
            $opdId = $object->master_opd_id;
            $opdName = $object->opd->nama_opd ?? '-';
            $lokasiId = $object->master_lokasi_id;
            $lokasiName = $object->lokasi->nama_lokasi ?? '-';
            $jenisRetribusi = $object->retribusi->nama_retribusi ?? '-';
            $subRincianRetribusi = $object->subRincianRetribusi->nama_sub_rincian_retribusi ?? '-';
            $paymentDate = $installment->paid_date ? date('Y-m-d', strtotime($installment->paid_date)) : '-';

            $key = $opdId . '|' . $lokasiId . '|' . $jenisRetribusi . '|' . $subRincianRetribusi . '|' . $paymentDate;

            if (! isset($data[$key])) {
                $data[$key] = [
                    'opd_id' => $opdId,
                    'opd_name' => $opdName,
                    'lokasi_id' => $lokasiId,
                    'lokasi_name' => $lokasiName,
                    'jenis_retribusi' => $jenisRetribusi,
                    'sub_rincian_retribusi' => $subRincianRetribusi,
                    'payment_date' => $paymentDate,
                    'total_amount' => 0,
                ];
            }

            $data[$key]['total_amount'] += $installment->grand_total;
        }

        // Sort data hierarchically
        usort($data, function ($a, $b) {
            if ($a['opd_id'] != $b['opd_id']) {
                return $a['opd_id'] <=> $b['opd_id'];
            }
            if ($a['lokasi_id'] != $b['lokasi_id']) {
                return $a['lokasi_id'] <=> $b['lokasi_id'];
            }
            if ($a['jenis_retribusi'] != $b['jenis_retribusi']) {
                return strcmp($a['jenis_retribusi'], $b['jenis_retribusi']);
            }
            if ($a['sub_rincian_retribusi'] != $b['sub_rincian_retribusi']) {
                return strcmp($a['sub_rincian_retribusi'], $b['sub_rincian_retribusi']);
            }

            return strcmp($a['payment_date'], $b['payment_date']);
        });

        return $data;
    }

    private function fillRevenueRecapData($sheet, $hierarchicalData, $startRow)
    {
        $row = $startRow;
        $serialNumber = 1;
        $lastOpd = null;
        $lastLokasi = null;
        $lastJenisRetribusi = null;
        $lastSubRincianRetribusi = null;

        foreach ($hierarchicalData as $item) {
            $sheet->setCellValue('A' . $row, $serialNumber);

            // Always show date
            $sheet->setCellValue('B' . $row, $item['payment_date']);

            // Only show OPD if it's different from the last row
            if ($lastOpd !== $item['opd_name']) {
                $sheet->setCellValue('C' . $row, $item['opd_name']);
                $lastOpd = $item['opd_name'];
                $lastLokasi = null; // Reset lower levels
                $lastJenisRetribusi = null;
                $lastSubRincianRetribusi = null;
            } else {
                $sheet->setCellValue('C' . $row, '');
            }

            // Only show Lokasi if it's different from the last row
            if ($lastLokasi !== $item['lokasi_name']) {
                $sheet->setCellValue('D' . $row, $item['lokasi_name']);
                $lastLokasi = $item['lokasi_name'];
                $lastJenisRetribusi = null; // Reset lower levels
                $lastSubRincianRetribusi = null;
            } else {
                $sheet->setCellValue('D' . $row, '');
            }

            // Only show Jenis Retribusi if it's different from the last row
            if ($lastJenisRetribusi !== $item['jenis_retribusi']) {
                $sheet->setCellValue('E' . $row, $item['jenis_retribusi']);
                $lastJenisRetribusi = $item['jenis_retribusi'];
                $lastSubRincianRetribusi = null; // Reset lower level
            } else {
                $sheet->setCellValue('E' . $row, '');
            }

            // Only show Sub Rincian Retribusi if it's different from the last row
            if ($lastSubRincianRetribusi !== $item['sub_rincian_retribusi']) {
                $sheet->setCellValue('F' . $row, $item['sub_rincian_retribusi']);
                $lastSubRincianRetribusi = $item['sub_rincian_retribusi'];
            } else {
                $sheet->setCellValue('F' . $row, '');
            }

            // Always show amount
            $sheet->setCellValue('G' . $row, $item['total_amount']);

            $rowStyle = [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $row % 2 == 0 ? 'E7E6E6' : 'FFFFFF'],
                ],
            ];

            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($rowStyle);
            $row++;
            $serialNumber++;
        }

        return $row;
    }

    private function addRevenueRecapSummary($sheet, $transactions, $paidInstallments, $summaryRow)
    {
        $totalAmount = 0;

        foreach ($transactions as $transaction) {
            $totalAmount += $transaction->total_amount;
        }

        foreach ($paidInstallments as $installment) {
            $totalAmount += $installment->grand_total;
        }

        $sheet->setCellValue('A' . $summaryRow, 'TOTAL');
        $sheet->setCellValue('G' . $summaryRow, $totalAmount);

        $summaryStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        $sheet->getStyle('A' . $summaryRow . ':G' . $summaryRow)->applyFromArray($summaryStyle);
    }

    public function getRetributionTargets(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (! $user) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        // Validate request
        $request->validate([
            'master_opd_id' => 'required|integer|exists:master_opd,id',
            'tahun' => 'required|integer|digits:4|min:2020|max:2099',
        ]);

        try {
            // Get OPD info
            $opd = MasterOpd::find($request->master_opd_id);

            // Define the three retribution types
            $retributionTypes = [1, 2, 3]; // Jasa Umum, Jasa Usaha, Perizinan Tertentu

            // Get all targets for this OPD and year in one query
            $targets = RetributionTargets::with(['retribusi'])
                ->where('master_opd_id', $request->master_opd_id)
                ->where('tahun', $request->tahun)
                ->whereIn('master_retribution_id', $retributionTypes)
                ->get()
                ->keyBy('master_retribution_id');

            // Calculate actual amounts for all retribution types in 2 queries (instead of 6)
            // Regular paid transactions - grouped by retribution type
            $regularPaidByType = TransaksiObjek::join('master_objek', 'transaction_objects.object_id', '=', 'master_objek.id')
                ->join('transactions', 'transaction_objects.transaction_id', '=', 'transactions.reference_no')
                ->where('master_objek.master_opd_id', $request->master_opd_id)
                ->whereIn('master_objek.master_retribusi_id', $retributionTypes)
                ->where('transactions.master_status_pembayaran_id', 3)
                ->whereNotNull('transactions.payment_date')
                ->whereYear('transactions.payment_date', $request->tahun)
                ->selectRaw('master_objek.master_retribusi_id, SUM(transaction_objects.total_amount) as total')
                ->groupBy('master_objek.master_retribusi_id')
                ->pluck('total', 'master_retribusi_id');

            // Paid installments - grouped by retribution type
            $installmentPaidByType = InstallmentTransaction::join('transactions', 'installment_transactions.transaction_id', '=', 'transactions.reference_no')
                ->join('transaction_objects', function ($join) {
                    $join->on('transactions.reference_no', '=', 'transaction_objects.transaction_id')
                        ->whereRaw('transaction_objects.id = (SELECT MIN(id) FROM transaction_objects WHERE transaction_id = transactions.reference_no)');
                })
                ->join('master_objek', 'transaction_objects.object_id', '=', 'master_objek.id')
                ->where('master_objek.master_opd_id', $request->master_opd_id)
                ->whereIn('master_objek.master_retribusi_id', $retributionTypes)
                ->where('installment_transactions.master_status_pembayaran_id', 9)
                ->whereNotNull('installment_transactions.paid_date')
                ->whereYear('installment_transactions.paid_date', $request->tahun)
                ->selectRaw('master_objek.master_retribusi_id, SUM(installment_transactions.grand_total) as total')
                ->groupBy('master_objek.master_retribusi_id')
                ->pluck('total', 'master_retribusi_id');

            // Build response for all 3 retribution types
            $targetsWithAchievements = collect();

            foreach ($retributionTypes as $retribusiId) {
                $target = $targets->get($retribusiId);
                $regularPaid = $regularPaidByType->get($retribusiId, 0);
                $installmentPaid = $installmentPaidByType->get($retribusiId, 0);
                $actualAmount = $regularPaid + $installmentPaid;
                $targetAmount = $target ? $target->target_amount : 0;

                $targetsWithAchievements->push([
                    'id' => $target ? $target->id : null,
                    'master_opd_id' => $request->master_opd_id,
                    'master_retribusi_id' => $retribusiId,
                    'target_amount' => $targetAmount,
                    'tahun' => $request->tahun,
                    'actual_amount' => $actualAmount,
                    'achievement_percentage' => $targetAmount > 0
                        ? round(($actualAmount / $targetAmount) * 100, 2)
                        : 0,
                    'remaining_amount' => max(0, $targetAmount - $actualAmount),
                    'retribusi' => $target ? $target->retribusi : null,
                    'created_at' => $target ? $target->created_at : null,
                    'updated_at' => $target ? $target->updated_at : null,
                ]);
            }

            // Calculate totals
            $totalTarget = $targetsWithAchievements->sum('target_amount');
            $totalActual = $targetsWithAchievements->sum('actual_amount');
            $totalAchievementPercentage = $totalTarget > 0
                ? round(($totalActual / $totalTarget) * 100, 2)
                : 0;

            return response()->json([
                'status' => 200,
                'message' => 'Success',
                'data' => [
                    'opd' => $opd,
                    'tahun' => $request->tahun,
                    'targets' => $targetsWithAchievements,
                    'summary' => [
                        'total_target' => $totalTarget,
                        'total_actual' => $totalActual,
                        'total_achievement_percentage' => $totalAchievementPercentage,
                        'total_remaining' => max(0, $totalTarget - $totalActual),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting retribution targets: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
