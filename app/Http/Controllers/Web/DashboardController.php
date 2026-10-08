<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MasterLokasiObjek;
use App\Models\MasterObjek;
use App\Models\MasterOpd;
use App\Models\MasterRetribusi;
use App\Models\RetributionTargets;
use App\Models\Revenue;
// Note: MasterObjek kept for objekList() page reference only
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * DashboardController
 *
 * Handles main dashboard for web interface
 */
class DashboardController extends Controller
{
    /**
     * Display dashboard
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Full dashboard with all sections - optimized queries using computed columns

        // Date range filters
        $startDate = $request->filled('start_date')
            ? $request->start_date
            : now()->startOfMonth()->toDateString();

        $endDate = $request->filled('end_date')
            ? $request->end_date
            : now()->endOfMonth()->toDateString();

        $dashboardVersion = Cache::get('dashboard_cache_version', 1);
        $rangeKey = $startDate . '_' . $endDate;

        // --- Summary metrics cache ---
        $summary = Cache::remember("dashboard:summary:v{$dashboardVersion}:{$rangeKey}", 21600, function () use ($startDate, $endDate) {
            $regular = Revenue::where('is_installment', 0)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('total_transactions');

            $installment = Revenue::where('is_installment', 1)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('total_transactions');

            $pendapatan = Revenue::whereBetween('date', [$startDate, $endDate])
                ->sum('total_revenue');

            return [
                'regular' => (int) $regular,
                'installment' => (int) $installment,
                'pendapatan' => (float) $pendapatan,
            ];
        });

        $totalTransaksiRegular = $summary['regular'];
        $totalTransaksiInstallment = $summary['installment'];
        $grandTotalPendapatan = $summary['pendapatan'];

        // Total Transaksi
        $grandTotalTransaksi = $totalTransaksiRegular + $totalTransaksiInstallment;
        $transaksiTerbayar = $totalTransaksiRegular;
        $angsuranTerbayar = $totalTransaksiInstallment;

        // Simplified variables
        $grandTotalInstallment = $totalTransaksiInstallment;
        $totalSemuaNonInstallment = $totalTransaksiRegular;
        $totalSemuaTransaksi = $grandTotalTransaksi;
        $revenueContributingTransactions = $grandTotalTransaksi;

        // Not tracked - removed to avoid heavy queries
        $transaksiMenunggu = 0;
        $transaksiAktif = 0;
        $totalTransaksiPerStatus = collect();

        // --- Retribusi breakdown cache ---
        $retribusiBreakdown = Cache::remember("dashboard:retribusi:v{$dashboardVersion}:{$rangeKey}", 21600, function () use ($startDate, $endDate) {
            $retribusiTypes = [
                1 => 'Retribusi Jasa Umum',
                2 => 'Retribusi Jasa Usaha',
                3 => 'Retribusi Perizinan Tertentu',
            ];

            $result = [];
            $retribusiData = Revenue::whereBetween('date', [$startDate, $endDate])
                ->selectRaw('master_retribusi_id, SUM(total_transactions) as transaction_count, SUM(total_revenue) as total_revenue')
                ->groupBy('master_retribusi_id')
                ->get()
                ->keyBy('master_retribusi_id');

            foreach ($retribusiTypes as $retribusiId => $retribusiName) {
                $data = $retribusiData->get($retribusiId);
                $result[$retribusiId] = [
                    'name' => $retribusiName,
                    'transaction_count' => (int) ($data->transaction_count ?? 0),
                    'total_revenue' => (float) ($data->total_revenue ?? 0),
                ];
            }

            return $result;
        });

        // 5. Recent Activity
        $recentTransaksi = Cache::remember("dashboard:recent:v{$dashboardVersion}:{$rangeKey}", 3600, function () use ($startDate, $endDate) {
            return Revenue::with(['opd:id,nama_opd', 'retribusi:id,nama_retribusi'])
                ->whereBetween('date', [$startDate, $endDate])
                ->orderByDesc('date')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        });

        // --- OPD Performance vs Targets - with date range filter ---
        $targetStartDate = $request->filled('target_start_date') ? $request->target_start_date : now()->startOfYear()->toDateString();
        $targetEndDate = $request->filled('target_end_date') ? $request->target_end_date : now()->endOfYear()->toDateString();

        // Validate that date range is within same year
        $targetStartYear = date('Y', strtotime($targetStartDate));
        $targetEndYear = date('Y', strtotime($targetEndDate));
        $targetYearError = null;

        if ($targetStartYear != $targetEndYear) {
            $targetYearError = 'Rentang tanggal harus dalam tahun yang sama. Silakan pilih tanggal dengan tahun yang sama.';
            // Reset to current year if invalid
            $targetStartDate = now()->startOfYear()->toDateString();
            $targetEndDate = now()->endOfYear()->toDateString();
            $targetStartYear = now()->year;
        }

        $targetYear = $targetStartYear;
        $targetRangeKey = $targetStartDate . '_' . $targetEndDate;

        $opdPerformance = Cache::remember("dashboard:opd-performance:v{$dashboardVersion}:{$targetRangeKey}", 21600, function () use ($targetYear, $targetStartDate, $targetEndDate) {
            $targetByOpd = DB::table('retribution_targets')
                ->where('tahun', $targetYear)
                ->selectRaw('master_opd_id, SUM(target_amount) as total_target')
                ->groupBy('master_opd_id');

            $revenueByOpd = DB::table('revenues')
                ->where('year', $targetYear)
                ->whereBetween('date', [$targetStartDate, $targetEndDate])
                ->selectRaw('master_opd_id, SUM(total_revenue) as actual_revenue')
                ->groupBy('master_opd_id');

            return DB::query()
                ->fromSub($targetByOpd, 't')
                ->join('master_opd as mo', 't.master_opd_id', '=', 'mo.id')
                ->leftJoinSub($revenueByOpd, 'r', function ($join) {
                    $join->on('r.master_opd_id', '=', 't.master_opd_id');
                })
                ->select(
                    'mo.id',
                    'mo.nama_opd',
                    't.total_target',
                    DB::raw('COALESCE(r.actual_revenue, 0) as actual_revenue')
                )
                ->get()
                ->map(function ($item) {
                    $percentage = $item->total_target > 0 ? ($item->actual_revenue / $item->total_target) * 100 : 0;

                    return [
                        'opd' => (object) ['id' => $item->id, 'nama_opd' => $item->nama_opd],
                        'total_target' => (float) $item->total_target,
                        'actual_revenue' => (float) $item->actual_revenue,
                        'percentage' => round($percentage, 2),
                        'status' => $percentage >= 100 ? 'achieved' : ($percentage >= 75 ? 'on-track' : 'below-target'),
                    ];
                })
                ->sortByDesc('percentage')
                ->values();
        });

        $availableYears = $this->getAvailableYears();

        // --- Master data counts - cached ---
        $masterCounts = Cache::remember('master_data_counts', 3600, function () {
            return [
                'totalOpd' => MasterOpd::count(),
                'totalRetribusi' => MasterRetribusi::count(),
                'totalLokasi' => MasterLokasiObjek::count(),
                'totalObjek' => MasterObjek::count(),
            ];
        });
        extract($masterCounts);

        // --- Realisasi vs Target Data - with date range filter ---
        $realisasiStartDate = $request->filled('realisasi_start_date') ? $request->realisasi_start_date : now()->startOfYear()->toDateString();
        $realisasiEndDate = $request->filled('realisasi_end_date') ? $request->realisasi_end_date : now()->endOfYear()->toDateString();

        // Validate that date range is within same year
        $realisasiStartYear = date('Y', strtotime($realisasiStartDate));
        $realisasiEndYear = date('Y', strtotime($realisasiEndDate));
        $realisasiYearError = null;

        if ($realisasiStartYear != $realisasiEndYear) {
            $realisasiYearError = 'Rentang tanggal harus dalam tahun yang sama. Silakan pilih tanggal dengan tahun yang sama.';
            // Reset to current year if invalid
            $realisasiStartDate = now()->startOfYear()->toDateString();
            $realisasiEndDate = now()->endOfYear()->toDateString();
            $realisasiStartYear = now()->year;
        }

        $realisasiYear = $realisasiStartYear;
        $realisasiRangeKey = $realisasiStartDate . '_' . $realisasiEndDate;

        $realisasiPayload = Cache::remember("dashboard:realisasi:v{$dashboardVersion}:{$realisasiRangeKey}", 21600, function () use ($realisasiYear, $realisasiStartDate, $realisasiEndDate) {
            $targetByOpd = DB::table('retribution_targets')
                ->where('tahun', $realisasiYear)
                ->selectRaw('master_opd_id, SUM(target_amount) as target')
                ->groupBy('master_opd_id');

            $revenueByOpd = DB::table('revenues')
                ->where('year', $realisasiYear)
                ->whereBetween('date', [$realisasiStartDate, $realisasiEndDate])
                ->selectRaw('master_opd_id, SUM(total_revenue) as realisasi')
                ->groupBy('master_opd_id');

            $realisasiByOpd = DB::query()
                ->fromSub($targetByOpd, 't')
                ->join('master_opd as mo', 't.master_opd_id', '=', 'mo.id')
                ->leftJoinSub($revenueByOpd, 'r', function ($join) {
                    $join->on('r.master_opd_id', '=', 't.master_opd_id');
                })
                ->select(
                    'mo.nama_opd',
                    DB::raw('t.target as target'),
                    DB::raw('COALESCE(r.realisasi, 0) as realisasi')
                )
                ->orderByDesc(DB::raw('COALESCE(r.realisasi, 0)'))
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    $percentage = $item->target > 0 ? ($item->realisasi / $item->target) * 100 : 0;

                    return [
                        'nama' => $item->nama_opd,
                        'target' => (float) $item->target,
                        'realisasi' => (float) $item->realisasi,
                        'percentage' => round($percentage, 1),
                        'status' => $percentage >= 100 ? 'achieved' : ($percentage >= 75 ? 'on-track' : 'below-target'),
                    ];
                });

            $targetByRetribusi = DB::table('retribution_targets')
                ->where('tahun', $realisasiYear)
                ->selectRaw('master_retribusi_id, SUM(target_amount) as target')
                ->groupBy('master_retribusi_id');

            $revenueByRetribusi = DB::table('revenues')
                ->where('year', $realisasiYear)
                ->whereBetween('date', [$realisasiStartDate, $realisasiEndDate])
                ->selectRaw('master_retribusi_id, SUM(total_revenue) as realisasi')
                ->groupBy('master_retribusi_id');

            $realisasiByRetribusi = DB::query()
                ->fromSub($targetByRetribusi, 't')
                ->join('master_retribusi as mr', 't.master_retribusi_id', '=', 'mr.id')
                ->leftJoinSub($revenueByRetribusi, 'r', function ($join) {
                    $join->on('r.master_retribusi_id', '=', 't.master_retribusi_id');
                })
                ->select(
                    'mr.nama_retribusi',
                    DB::raw('t.target as target'),
                    DB::raw('COALESCE(r.realisasi, 0) as realisasi')
                )
                ->get()
                ->map(function ($item) {
                    $percentage = $item->target > 0 ? ($item->realisasi / $item->target) * 100 : 0;

                    return [
                        'nama' => $item->nama_retribusi,
                        'target' => (float) $item->target,
                        'realisasi' => (float) $item->realisasi,
                        'percentage' => round($percentage, 1),
                        'status' => $percentage >= 100 ? 'achieved' : ($percentage >= 75 ? 'on-track' : 'below-target'),
                    ];
                });

            $totalTarget = RetributionTargets::where('tahun', $realisasiYear)->sum('target_amount');
            $totalRealisasi = Revenue::where('year', $realisasiYear)
                ->whereBetween('date', [$realisasiStartDate, $realisasiEndDate])
                ->sum('total_revenue');
            $realisasiPercentage = $totalTarget > 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;

            return [
                'totalTarget' => (float) $totalTarget,
                'totalRealisasi' => (float) $totalRealisasi,
                'realisasiPercentage' => (float) $realisasiPercentage,
                'realisasiByOpd' => $realisasiByOpd,
                'realisasiByRetribusi' => $realisasiByRetribusi,
            ];
        });

        $totalTarget = $realisasiPayload['totalTarget'];
        $totalRealisasi = $realisasiPayload['totalRealisasi'];
        $realisasiPercentage = $realisasiPayload['realisasiPercentage'];
        $realisasiByOpd = $realisasiPayload['realisasiByOpd'];
        $realisasiByRetribusi = $realisasiPayload['realisasiByRetribusi'];

        return view('dashboard.index', compact(
            'grandTotalTransaksi',
            'totalSemuaTransaksi',
            'grandTotalInstallment',
            'transaksiTerbayar',
            'angsuranTerbayar',
            'transaksiMenunggu',
            'transaksiAktif',
            'totalTransaksiPerStatus',
            'grandTotalPendapatan',
            'revenueContributingTransactions',
            'retribusiBreakdown',
            'recentTransaksi',
            'opdPerformance',
            'targetYear',
            'targetStartDate',
            'targetEndDate',
            'targetYearError',
            'availableYears',
            'totalOpd',
            'totalRetribusi',
            'totalLokasi',
            'totalObjek',
            'startDate',
            'endDate',
            'realisasiYear',
            'realisasiStartDate',
            'realisasiEndDate',
            'realisasiYearError',
            'totalTarget',
            'totalRealisasi',
            'realisasiPercentage',
            'realisasiByOpd',
            'realisasiByRetribusi'
        ));
    }

    /**
     * Display OPD list with pagination
     *
     * @return \Illuminate\View\View
     */
    public function opdList(Request $request)
    {
        $search = $request->get('search');

        $opds = MasterOpd::when($search, function ($query) use ($search) {
            $query->where('nama_opd', 'like', '%' . $search . '%')
                ->orWhere('kode_opd', 'like', '%' . $search . '%');
        })
            ->orderBy('nama_opd')
            ->paginate(12);

        return view('dashboard.opd-list', compact('opds', 'search'));
    }

    /**
     * Display Retribusi list with pagination
     *
     * @return \Illuminate\View\View
     */
    public function retribusiList(Request $request)
    {
        $search = $request->get('search');

        $retribusis = MasterRetribusi::when($search, function ($query) use ($search) {
            $query->where('nama_retribusi', 'like', '%' . $search . '%');
        })
            ->orderBy('nama_retribusi')
            ->paginate(12);

        return view('dashboard.retribusi-list', compact('retribusis', 'search'));
    }

    /**
     * Display Lokasi list with pagination
     *
     * @return \Illuminate\View\View
     */
    public function lokasiList(Request $request)
    {
        $search = $request->get('search');

        $lokasis = MasterLokasiObjek::when($search, function ($query) use ($search) {
            $query->where('nama_lokasi', 'like', '%' . $search . '%');
        })
            ->orderBy('nama_lokasi')
            ->paginate(12);

        return view('dashboard.lokasi-list', compact('lokasis', 'search'));
    }

    /**
     * Display Objek list with pagination and images
     *
     * @return \Illuminate\View\View
     */
    public function objekList(Request $request)
    {
        $search = $request->get('search');
        $opdFilter = $request->get('opd_id');
        $retribusiFilter = $request->get('retribusi_id');
        $lokasiFilter = $request->get('lokasi_id');
        $rincianFilter = $request->get('rincian_id');

        $objeks = MasterObjek::with(['opd', 'retribusi', 'lokasi', 'typeObject', 'statusObjek', 'rincianRetribusi'])
            ->when($opdFilter, function ($query) use ($opdFilter) {
                $query->where('master_opd_id', $opdFilter);
            })
            ->when($retribusiFilter, function ($query) use ($retribusiFilter) {
                $query->where('master_retribusi_id', $retribusiFilter);
            })
            ->when($lokasiFilter, function ($query) use ($lokasiFilter) {
                $query->where('master_lokasi_id', $lokasiFilter);
            })
            ->when($rincianFilter, function ($query) use ($rincianFilter) {
                $query->where('master_rincian_retribusi_id', $rincianFilter);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('sub_rincian_objek', 'like', '%' . $search . '%')
                        ->orWhere('rincian_objek', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('sub_rincian_objek')
            ->paginate(12)
            ->appends($request->except('page'));

        // Get filter options with hierarchical filtering
        $opds = MasterOpd::orderBy('nama_opd')->get();
        $retribusis = MasterRetribusi::orderBy('nama_retribusi')->get();

        // Lokasi: filter by OPD if selected
        $lokasis = MasterLokasiObjek::when($opdFilter, function ($query) use ($opdFilter) {
            return $query->whereHas('objek', function ($q) use ($opdFilter) {
                $q->where('master_opd_id', $opdFilter);
            });
        })
            ->orderBy('nama_lokasi')
            ->distinct()
            ->get();

        // Rincian Retribusi: load all for JavaScript filtering
        // Server-side filtering is handled by JavaScript on change event
        $rincians = \App\Models\MasterRincianRetribusi::orderBy('id')->get();

        return view('dashboard.objek-list', compact(
            'objeks',
            'search',
            'opds',
            'retribusis',
            'lokasis',
            'rincians',
            'opdFilter',
            'retribusiFilter',
            'lokasiFilter',
            'rincianFilter'
        ));
    }

    /**
     * Realisasi per Lokasi by OPD (Drill-down from OPD card)
     *
     * @param int $opdId
     * @return \Illuminate\View\View
     */
    public function realisasiLokasiByOpd(Request $request, $opdId)
    {
        $tahun = $request->get('tahun', now()->year);

        // Get OPD info
        $opd = MasterOpd::findOrFail($opdId);

        // Get all lokasi IDs that have either target OR realisasi
        $lokasiIds = DB::table('retribution_targets')
            ->where('master_opd_id', $opdId)
            ->where('tahun', $tahun)
            ->select('master_lokasi_id')
            ->union(
                DB::table('revenues')
                    ->where('master_opd_id', $opdId)
                    ->where('year', $tahun)
                    ->select('master_lokasi_id')
            )
            ->distinct()
            ->pluck('master_lokasi_id');

        // Get target by lokasi
        $targetByLokasi = DB::table('retribution_targets')
            ->where('master_opd_id', $opdId)
            ->where('tahun', $tahun)
            ->selectRaw('master_lokasi_id, SUM(target_amount) as total_target')
            ->groupBy('master_lokasi_id')
            ->pluck('total_target', 'master_lokasi_id');

        // Get realisasi and total_transaksi by lokasi
        $realisasiByLokasi = DB::table('revenues')
            ->where('master_opd_id', $opdId)
            ->where('year', $tahun)
            ->selectRaw('master_lokasi_id, SUM(total_revenue) as total_realisasi, SUM(total_transactions) as total_transaksi')
            ->groupBy('master_lokasi_id')
            ->get()
            ->keyBy('master_lokasi_id');

        // Combine with lokasi info - always show even if target or realisasi is 0
        $data = MasterLokasiObjek::whereIn('id', $lokasiIds)
            ->get()
            ->map(function ($lokasi) use ($targetByLokasi, $realisasiByLokasi) {
                $rev = $realisasiByLokasi->get($lokasi->id);
                $target = (float) ($targetByLokasi[$lokasi->id] ?? 0);
                $realisasi = (float) ($rev->total_realisasi ?? 0);
                $percentage = $target > 0 ? ($realisasi / $target) * 100 : 0;
                return [
                    'lokasi_id' => $lokasi->id,
                    'nama_lokasi' => $lokasi->nama_lokasi,
                    'target' => $target,
                    'realisasi' => $realisasi,
                    'total_transaksi' => (int) ($rev->total_transaksi ?? 0),
                    'percentage' => round($percentage, 1),
                    'status' => $percentage >= 100 ? 'achieved' : ($percentage >= 75 ? 'on-track' : 'below-target'),
                ];
            })
            ->sortByDesc('realisasi')
            ->values();

        // Calculate totals
        $totalTarget = $data->sum('target');
        $totalRealisasi = $data->sum('realisasi');
        $totalPercentage = $totalTarget > 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;

        // Get retribution breakdown for this OPD
        $retribusiBreakdown = $this->getRetribusiBreakdownForOpd($opdId, $tahun);

        $availableYears = $this->getAvailableYears();

        return view('dashboard.realisasi-lokasi-by-opd', compact(
            'opd',
            'data',
            'tahun',
            'totalTarget',
            'totalRealisasi',
            'totalPercentage',
            'retribusiBreakdown',
            'availableYears'
        ));
    }

    /**
     * Get retribution breakdown (jenis, rincian, sub rincian) for an OPD
     * Cached for 1 hour for consistency with other dashboard queries
     *
     * @param int $opdId
     * @param int $tahun
     * @return array
     */
    private function getRetribusiBreakdownForOpd($opdId, $tahun)
    {
        $dashboardVersion = Cache::get('dashboard_cache_version', 1);
        
        return Cache::remember("retribusi_breakdown:v{$dashboardVersion}:{$opdId}_{$tahun}", 3600, function () use ($opdId, $tahun) {
            // Get all retribution targets for this OPD grouped by hierarchy
            $targets = DB::table('retribution_targets')
                ->where('master_opd_id', $opdId)
                ->where('tahun', $tahun)
                ->selectRaw('
                    master_retribusi_id,
                    master_rincian_retribusi_id,
                    master_sub_rincian_retribusi_id,
                    SUM(target_amount) as target_amount
                ')
                ->groupBy('master_retribusi_id', 'master_rincian_retribusi_id', 'master_sub_rincian_retribusi_id')
                ->get();

            // Get all revenues for this OPD grouped by hierarchy
            $revenues = DB::table('revenues')
                ->where('master_opd_id', $opdId)
                ->where('year', $tahun)
                ->selectRaw('
                    master_retribusi_id,
                    master_rincian_retribusi_id,
                    master_sub_rincian_retribusi_id,
                    SUM(total_revenue) as total_revenue,
                    SUM(total_transactions) as total_transactions
                ')
                ->groupBy('master_retribusi_id', 'master_rincian_retribusi_id', 'master_sub_rincian_retribusi_id')
                ->get()
                ->keyBy(function ($item) {
                    return "{$item->master_retribusi_id}_{$item->master_rincian_retribusi_id}_{$item->master_sub_rincian_retribusi_id}";
                });

            // Optimize: Only load master data for IDs that exist in targets
            $retribusiIds = $targets->pluck('master_retribusi_id')->unique();
            $rincianIds = $targets->pluck('master_rincian_retribusi_id')->unique();
            $subRincianIds = $targets->pluck('master_sub_rincian_retribusi_id')->unique();

            $retribusiTypes = MasterRetribusi::whereIn('id', $retribusiIds)->get()->keyBy('id');
            $rincianRetribusi = \App\Models\MasterRincianRetribusi::whereIn('id', $rincianIds)->get()->keyBy('id');
            $subRincianRetribusi = \App\Models\MasterSubRincianRetribusi::whereIn('id', $subRincianIds)->get()->keyBy('id');

            // Build hierarchical data structure
            $data = [];
            foreach ($targets as $target) {
                $key = "{$target->master_retribusi_id}_{$target->master_rincian_retribusi_id}_{$target->master_sub_rincian_retribusi_id}";
                $revenue = $revenues->get($key);

                $realisasi = (float) ($revenue->total_revenue ?? 0);
                $targetAmount = (float) $target->target_amount;
                $percentage = $targetAmount > 0 ? ($realisasi / $targetAmount) * 100 : 0;

                $retribusiId = $target->master_retribusi_id;
                if (!isset($data[$retribusiId])) {
                    $data[$retribusiId] = [
                        'retribusi' => $retribusiTypes->get($retribusiId),
                        'target' => 0,
                        'realisasi' => 0,
                        'total_transaksi' => 0,
                        'rincian' => [],
                    ];
                }

                $rincianId = $target->master_rincian_retribusi_id;
                if (!isset($data[$retribusiId]['rincian'][$rincianId])) {
                    $data[$retribusiId]['rincian'][$rincianId] = [
                        'rincian' => $rincianRetribusi->get($rincianId),
                        'target' => 0,
                        'realisasi' => 0,
                        'total_transaksi' => 0,
                        'sub_rincian' => [],
                    ];
                }

                $subRincianId = $target->master_sub_rincian_retribusi_id;
                $data[$retribusiId]['rincian'][$rincianId]['sub_rincian'][$subRincianId] = [
                    'sub_rincian' => $subRincianRetribusi->get($subRincianId),
                    'target' => $targetAmount,
                    'realisasi' => $realisasi,
                    'total_transaksi' => (int) ($revenue->total_transactions ?? 0),
                    'percentage' => round($percentage, 1),
                    'status' => $percentage >= 100 ? 'achieved' : ($percentage >= 75 ? 'on-track' : 'below-target'),
                ];

                // Aggregate to rincian level
                $data[$retribusiId]['rincian'][$rincianId]['target'] += $targetAmount;
                $data[$retribusiId]['rincian'][$rincianId]['realisasi'] += $realisasi;
                $data[$retribusiId]['rincian'][$rincianId]['total_transaksi'] += (int) ($revenue->total_transactions ?? 0);

                // Aggregate to retribusi level
                $data[$retribusiId]['target'] += $targetAmount;
                $data[$retribusiId]['realisasi'] += $realisasi;
                $data[$retribusiId]['total_transaksi'] += (int) ($revenue->total_transactions ?? 0);
            }

            // Calculate percentages for aggregated levels
            foreach ($data as &$retribusi) {
                $retribusi['percentage'] = $retribusi['target'] > 0 ? ($retribusi['realisasi'] / $retribusi['target']) * 100 : 0;
                $retribusi['status'] = $retribusi['percentage'] >= 100 ? 'achieved' : ($retribusi['percentage'] >= 75 ? 'on-track' : 'below-target');

                foreach ($retribusi['rincian'] as &$rincian) {
                    $rincian['percentage'] = $rincian['target'] > 0 ? ($rincian['realisasi'] / $rincian['target']) * 100 : 0;
                    $rincian['status'] = $rincian['percentage'] >= 100 ? 'achieved' : ($rincian['percentage'] >= 75 ? 'on-track' : 'below-target');
                }
            }

            return $data;
        });
    }

    /**
     * Display OPD Performance Detail Page (Heavy Query)
     *
     * @return \Illuminate\View\View
     */
    public function performanceDetail(Request $request)
    {
        $targetYear = $request->filled('tahun') ? $request->tahun : now()->year;

        // Cache performance data for 15 minutes
        $cacheKey = "opd_performance_{$targetYear}";
        $opdPerformance = Cache::remember($cacheKey, 900, function () use ($targetYear) {
            // Optimized query: Use computed year column instead of YEAR() function
            return DB::table('retribution_targets as rt')
                ->join('master_opd as mo', 'rt.master_opd_id', '=', 'mo.id')
                ->leftJoin('revenues as r', function ($join) use ($targetYear) {
                    $join->on('r.master_opd_id', '=', 'rt.master_opd_id')
                        ->where('r.year', '=', $targetYear); // Use computed column
                })
                ->where('rt.tahun', $targetYear)
                ->select(
                    'mo.id',
                    'mo.nama_opd',
                    DB::raw('SUM(rt.target_amount) as total_target'),
                    DB::raw('COALESCE(SUM(r.total_revenue), 0) as actual_revenue')
                )
                ->groupBy('mo.id', 'mo.nama_opd')
                ->get()
                ->map(function ($item) {
                    $percentage = $item->total_target > 0 ? ($item->actual_revenue / $item->total_target) * 100 : 0;
                    return [
                        'opd_id' => $item->id,
                        'nama_opd' => $item->nama_opd,
                        'total_target' => (float) $item->total_target,
                        'actual_revenue' => (float) $item->actual_revenue,
                        'percentage' => round($percentage, 2),
                        'status' => $percentage >= 100 ? 'achieved' : ($percentage >= 75 ? 'on-track' : 'below-target'),
                    ];
                })
                ->sortByDesc('percentage')
                ->values();
        });

        $availableYears = $this->getAvailableYears();

        return view('dashboard.performance-detail', compact('opdPerformance', 'targetYear', 'availableYears'));
    }

    /**
     * Display Realisasi Detail Page (Heavy Query)
     *
     * @return \Illuminate\View\View
     */
    public function realisasiDetail(Request $request)
    {
        $realisasiYear = $request->filled('tahun') ? $request->tahun : now()->year;

        // Cache realisasi data for 15 minutes
        $cacheKey = "realisasi_detail_{$realisasiYear}";
        $data = Cache::remember($cacheKey, 900, function () use ($realisasiYear) {
            // Total target and realisasi for the year - using computed column
            $totalTarget = RetributionTargets::where('tahun', $realisasiYear)->sum('target_amount');
            $totalRealisasi = Revenue::where('year', $realisasiYear)->sum('total_revenue');
            $realisasiPercentage = $totalTarget > 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;

            // Breakdown by OPD - using computed year column
            $realisasiByOpd = DB::table('retribution_targets as rt')
                ->join('master_opd as mo', 'rt.master_opd_id', '=', 'mo.id')
                ->leftJoin('revenues as r', function ($join) use ($realisasiYear) {
                    $join->on('r.master_opd_id', '=', 'rt.master_opd_id')
                        ->where('r.year', '=', $realisasiYear); // Use computed column
                })
                ->where('rt.tahun', $realisasiYear)
                ->select(
                    'mo.id',
                    'mo.nama_opd',
                    DB::raw('SUM(rt.target_amount) as target'),
                    DB::raw('COALESCE(SUM(r.total_revenue), 0) as realisasi')
                )
                ->groupBy('mo.id', 'mo.nama_opd')
                ->get()
                ->map(function ($item) {
                    $percentage = $item->target > 0 ? ($item->realisasi / $item->target) * 100 : 0;
                    return [
                        'opd_id' => $item->id,
                        'nama' => $item->nama_opd,
                        'target' => (float) $item->target,
                        'realisasi' => (float) $item->realisasi,
                        'percentage' => round($percentage, 1),
                        'status' => $percentage >= 100 ? 'achieved' : ($percentage >= 75 ? 'on-track' : 'below-target')
                    ];
                });

            // Breakdown by Retribusi Type - using computed year column
            $realisasiByRetribusi = DB::table('retribution_targets as rt')
                ->join('master_retribusi as mr', 'rt.master_retribusi_id', '=', 'mr.id')
                ->leftJoin('revenues as r', function ($join) use ($realisasiYear) {
                    $join->on('r.master_retribusi_id', '=', 'rt.master_retribusi_id')
                        ->where('r.year', '=', $realisasiYear); // Use computed column
                })
                ->where('rt.tahun', $realisasiYear)
                ->select(
                    'mr.id',
                    'mr.nama_retribusi',
                    DB::raw('SUM(rt.target_amount) as target'),
                    DB::raw('COALESCE(SUM(r.total_revenue), 0) as realisasi')
                )
                ->groupBy('mr.id', 'mr.nama_retribusi')
                ->get()
                ->map(function ($item) {
                    $percentage = $item->target > 0 ? ($item->realisasi / $item->target) * 100 : 0;
                    return [
                        'nama' => $item->nama_retribusi,
                        'target' => (float) $item->target,
                        'realisasi' => (float) $item->realisasi,
                        'percentage' => round($percentage, 1),
                        'status' => $percentage >= 100 ? 'achieved' : ($percentage >= 75 ? 'on-track' : 'below-target')
                    ];
                });

            return compact(
                'totalTarget',
                'totalRealisasi',
                'realisasiPercentage',
                'realisasiByOpd',
                'realisasiByRetribusi'
            );
        });

        extract($data);

        $availableYears = $this->getAvailableYears();

        return view('dashboard.realisasi-detail', compact(
            'realisasiYear',
            'totalTarget',
            'totalRealisasi',
            'realisasiPercentage',
            'realisasiByOpd',
            'realisasiByRetribusi',
            'availableYears'
        ));
    }

    private function getAvailableYears()
    {
        // Simple range: 2025 (app release year) to current year
        // No DB query needed - fast and consistent
        $currentYear = now()->year;
        $startYear = 2025;

        return collect(range($currentYear, $startYear))
            ->values();
    }

    /**
     * Get filtered locations based on OPD (AJAX)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLokasiByOpd(Request $request)
    {
        $opdId = $request->get('opd_id');

        $lokasis = MasterLokasiObjek::when($opdId, function ($query) use ($opdId) {
            return $query->whereHas('objek', function ($q) use ($opdId) {
                $q->where('master_opd_id', $opdId);
            });
        })
            ->orderBy('nama_lokasi')
            ->distinct()
            ->get(['id', 'nama_lokasi']);

        return response()->json($lokasis);
    }

    /**
     * Get filtered rincian retribusi based on retribusi (AJAX)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRincianByRetribusi(Request $request)
    {
        $retribusiId = $request->get('retribusi_id');

        $rincians = \App\Models\MasterRincianRetribusi::when($retribusiId, function ($query) use ($retribusiId) {
            return $query->where('master_tipe_retribusi_id', $retribusiId);
        })
            ->orderBy('id')
            ->get(['id', 'rincian_retribusi']);

        return response()->json($rincians);
    }

    /**
     * Display detailed retribution breakdown by OPD and Lokasi
     * Shows realization vs target for each retribusi type, rincian, and sub rincian
     *
     * @param int $opdId
     * @param int $lokasiId
     * @return \Illuminate\View\View
     */
    public function realisasiRetribusiByLokasi(Request $request, $opdId, $lokasiId)
    {
        $tahun = $request->filled('tahun') ? $request->tahun : now()->year;

        $opd = MasterOpd::findOrFail($opdId);
        $lokasi = MasterLokasiObjek::findOrFail($lokasiId);

        // Get all retribution targets for this OPD and Lokasi grouped by hierarchy
        $targets = DB::table('retribution_targets')
            ->where('master_opd_id', $opdId)
            ->where('master_lokasi_id', $lokasiId)
            ->where('tahun', $tahun)
            ->selectRaw('
                master_retribusi_id,
                master_rincian_retribusi_id,
                master_sub_rincian_retribusi_id,
                SUM(target_amount) as target_amount
            ')
            ->groupBy('master_retribusi_id', 'master_rincian_retribusi_id', 'master_sub_rincian_retribusi_id')
            ->get();

        // Get all revenues for this OPD and Lokasi grouped by hierarchy
        $revenues = DB::table('revenues')
            ->where('master_opd_id', $opdId)
            ->where('master_lokasi_id', $lokasiId)
            ->where('year', $tahun)
            ->selectRaw('
                master_retribusi_id,
                master_rincian_retribusi_id,
                master_sub_rincian_retribusi_id,
                SUM(total_revenue) as total_revenue,
                SUM(total_transactions) as total_transactions
            ')
            ->groupBy('master_retribusi_id', 'master_rincian_retribusi_id', 'master_sub_rincian_retribusi_id')
            ->get()
            ->keyBy(function ($item) {
                return "{$item->master_retribusi_id}_{$item->master_rincian_retribusi_id}_{$item->master_sub_rincian_retribusi_id}";
            });

        // Load master data for display
        $retribusiTypes = MasterRetribusi::whereIn('id', [1, 2, 3])->get()->keyBy('id');
        $rincianRetribusi = \App\Models\MasterRincianRetribusi::all()->keyBy('id');
        $subRincianRetribusi = \App\Models\MasterSubRincianRetribusi::all()->keyBy('id');

        // Build hierarchical data structure
        $data = [];
        foreach ($targets as $target) {
            $key = "{$target->master_retribusi_id}_{$target->master_rincian_retribusi_id}_{$target->master_sub_rincian_retribusi_id}";
            $revenue = $revenues->get($key);

            $realisasi = (float) ($revenue->total_revenue ?? 0);
            $targetAmount = (float) $target->target_amount;
            $percentage = $targetAmount > 0 ? ($realisasi / $targetAmount) * 100 : 0;

            $retribusiId = $target->master_retribusi_id;
            if (!isset($data[$retribusiId])) {
                $data[$retribusiId] = [
                    'retribusi' => $retribusiTypes->get($retribusiId),
                    'target' => 0,
                    'realisasi' => 0,
                    'total_transaksi' => 0,
                    'rincian' => [],
                ];
            }

            $rincianId = $target->master_rincian_retribusi_id;
            if (!isset($data[$retribusiId]['rincian'][$rincianId])) {
                $data[$retribusiId]['rincian'][$rincianId] = [
                    'rincian' => $rincianRetribusi->get($rincianId),
                    'target' => 0,
                    'realisasi' => 0,
                    'total_transaksi' => 0,
                    'sub_rincian' => [],
                ];
            }

            $subRincianId = $target->master_sub_rincian_retribusi_id;
            $data[$retribusiId]['rincian'][$rincianId]['sub_rincian'][$subRincianId] = [
                'sub_rincian' => $subRincianRetribusi->get($subRincianId),
                'target' => $targetAmount,
                'realisasi' => $realisasi,
                'total_transaksi' => (int) ($revenue->total_transactions ?? 0),
                'percentage' => round($percentage, 1),
                'status' => $percentage >= 100 ? 'achieved' : ($percentage >= 75 ? 'on-track' : 'below-target'),
            ];

            // Aggregate to rincian level
            $data[$retribusiId]['rincian'][$rincianId]['target'] += $targetAmount;
            $data[$retribusiId]['rincian'][$rincianId]['realisasi'] += $realisasi;
            $data[$retribusiId]['rincian'][$rincianId]['total_transaksi'] += (int) ($revenue->total_transactions ?? 0);

            // Aggregate to retribusi level
            $data[$retribusiId]['target'] += $targetAmount;
            $data[$retribusiId]['realisasi'] += $realisasi;
            $data[$retribusiId]['total_transaksi'] += (int) ($revenue->total_transactions ?? 0);
        }

        // Calculate percentages for aggregated levels
        foreach ($data as &$retribusi) {
            $retribusi['percentage'] = $retribusi['target'] > 0 ? ($retribusi['realisasi'] / $retribusi['target']) * 100 : 0;
            $retribusi['status'] = $retribusi['percentage'] >= 100 ? 'achieved' : ($retribusi['percentage'] >= 75 ? 'on-track' : 'below-target');

            foreach ($retribusi['rincian'] as &$rincian) {
                $rincian['percentage'] = $rincian['target'] > 0 ? ($rincian['realisasi'] / $rincian['target']) * 100 : 0;
                $rincian['status'] = $rincian['percentage'] >= 100 ? 'achieved' : ($rincian['percentage'] >= 75 ? 'on-track' : 'below-target');
            }
        }

        // Calculate totals
        $totalTarget = collect($data)->sum('target');
        $totalRealisasi = collect($data)->sum('realisasi');
        $totalTransaksi = collect($data)->sum('total_transaksi');
        $totalPercentage = $totalTarget > 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;

        $availableYears = $this->getAvailableYears();

        return view('dashboard.realisasi-retribusi-by-lokasi', compact(
            'opd',
            'lokasi',
            'data',
            'tahun',
            'totalTarget',
            'totalRealisasi',
            'totalTransaksi',
            'totalPercentage',
            'availableYears'
        ));
    }
}
