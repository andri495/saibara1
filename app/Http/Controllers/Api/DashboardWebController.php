<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterLokasiObjek;
use App\Models\MasterObjek;
use App\Models\MasterOpd;
use App\Models\MasterRetribusi;
use App\Models\MasterRincianRetribusi;
use App\Models\RetributionTargets;
use App\Models\Revenue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * DashboardWebController (API Version)
 *
 * API version of Web DashboardController for integration with external web applications
 * Returns JSON instead of HTML views
 * Uses JWT authentication instead of session
 */
class DashboardWebController extends Controller
{
    /**
     * Get dashboard summary only (lightweight)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSummary(Request $request)
    {
        try {
            $startDate = $request->filled('start_date')
                ? $request->start_date
                : now()->startOfMonth()->toDateString();

            $endDate = $request->filled('end_date')
                ? $request->end_date
                : now()->endOfMonth()->toDateString();

            $dashboardVersion = Cache::get('dashboard_cache_version', 1);
            $rangeKey = $startDate . '_' . $endDate;

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

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved summary data',
                'data' => [
                    'total_transaksi_regular' => $summary['regular'],
                    'total_transaksi_installment' => $summary['installment'],
                    'grand_total_transaksi' => $summary['regular'] + $summary['installment'],
                    'grand_total_pendapatan' => number_format($summary['pendapatan'], 2, '.', ''),
                    'filters' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving summary data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get retribusi breakdown only (lightweight)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRetribusiBreakdown(Request $request)
    {
        try {
            $startDate = $request->filled('start_date')
                ? $request->start_date
                : now()->startOfMonth()->toDateString();

            $endDate = $request->filled('end_date')
                ? $request->end_date
                : now()->endOfMonth()->toDateString();

            $dashboardVersion = Cache::get('dashboard_cache_version', 1);
            $rangeKey = $startDate . '_' . $endDate;

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
                    $result[] = [
                        'retribusi_id' => $retribusiId,
                        'nama_retribusi' => $retribusiName,
                        'total_transaksi' => $data ? (int) $data->transaction_count : 0,
                        'total_pendapatan' => $data ? number_format((float) $data->total_revenue, 2, '.', '') : '0.00',
                    ];
                }

                return $result;
            });

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved retribusi breakdown',
                'data' => $retribusiBreakdown,
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving retribusi breakdown',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get OPD performance only (lightweight)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOpdPerformance(Request $request)
    {
        try {
            $targetStartDate = $request->filled('start_date') ? $request->start_date : now()->startOfYear()->toDateString();
            $targetEndDate = $request->filled('end_date') ? $request->end_date : now()->endOfYear()->toDateString();
            $targetYearError = null;

            if (date('Y', strtotime($targetStartDate)) != date('Y', strtotime($targetEndDate))) {
                $targetYearError = 'Rentang tanggal harus dalam tahun yang sama. Data direset ke tahun berjalan.';
                $targetStartDate = now()->startOfYear()->toDateString();
                $targetEndDate = now()->endOfYear()->toDateString();
            }

            $targetYear = date('Y', strtotime($targetStartDate));
            $dashboardVersion = Cache::get('dashboard_cache_version', 1);
            $targetRangeKey = $targetStartDate . '_' . $targetEndDate;

            $opdPerformance = Cache::remember("dashboard:opd_performance:v{$dashboardVersion}:{$targetRangeKey}", 21600, function () use ($targetYear, $targetStartDate, $targetEndDate) {
                $targets = RetributionTargets::select('master_opd_id', DB::raw('SUM(target_amount) as total_target'))
                    ->where('tahun', $targetYear)
                    ->groupBy('master_opd_id')
                    ->get()
                    ->keyBy('master_opd_id');

                $revenues = Revenue::select('master_opd_id', DB::raw('SUM(total_revenue) as total_revenue'))
                    ->where('year', $targetYear)
                    ->whereBetween('date', [$targetStartDate, $targetEndDate])
                    ->groupBy('master_opd_id')
                    ->get()
                    ->keyBy('master_opd_id');

                $opdIds = $targets->keys()->merge($revenues->keys())->unique();

                $data = $opdIds->map(function ($opdId) use ($targets, $revenues) {
                    $opd = MasterOpd::find($opdId);
                    if (!$opd) return null;

                    $target = $targets->get($opdId);
                    $revenue = $revenues->get($opdId);

                    $targetAmount = $target ? (float) $target->total_target : 0;
                    $revenueAmount = $revenue ? (float) $revenue->total_revenue : 0;
                    $percentage = $targetAmount > 0 ? ($revenueAmount / $targetAmount) * 100 : 0;

                    return [
                        'opd_id' => $opdId,
                        'nama_opd' => $opd->nama_opd,
                        'target' => number_format($targetAmount, 2, '.', ''),
                        'realisasi' => number_format($revenueAmount, 2, '.', ''),
                        'percentage' => number_format($percentage, 2, '.', ''),
                        'status' => $percentage >= 100 ? 'achieved' : ($percentage >= 75 ? 'on-track' : 'below-target'),
                    ];
                })->filter()->sortByDesc(function ($item) {
                    return (float) $item['percentage'];
                })->values()->all();

                return $data;
            });

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved OPD performance',
                'data' => $opdPerformance,
                'filters' => [
                    'start_date' => $targetStartDate,
                    'end_date' => $targetEndDate,
                    'year' => $targetYear,
                ],
                'error' => $targetYearError,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving OPD performance',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get realisasi vs target only (lightweight)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRealisasiVsTarget(Request $request)
    {
        try {
            $realisasiStartDate = $request->filled('start_date') ? $request->start_date : now()->startOfYear()->toDateString();
            $realisasiEndDate = $request->filled('end_date') ? $request->end_date : now()->endOfYear()->toDateString();
            $realisasiYearError = null;

            if (date('Y', strtotime($realisasiStartDate)) != date('Y', strtotime($realisasiEndDate))) {
                $realisasiYearError = 'Rentang tanggal harus dalam tahun yang sama. Data direset ke tahun berjalan.';
                $realisasiStartDate = now()->startOfYear()->toDateString();
                $realisasiEndDate = now()->endOfYear()->toDateString();
            }

            $realisasiYear = date('Y', strtotime($realisasiStartDate));
            $dashboardVersion = Cache::get('dashboard_cache_version', 1);
            $realisasiRangeKey = $realisasiStartDate . '_' . $realisasiEndDate;

            $realisasiPayload = Cache::remember("dashboard:realisasi:v{$dashboardVersion}:{$realisasiRangeKey}", 21600, function () use ($realisasiYear, $realisasiStartDate, $realisasiEndDate) {
                $retribusiTypes = [
                    1 => 'Retribusi Jasa Umum',
                    2 => 'Retribusi Jasa Usaha',
                    3 => 'Retribusi Perizinan Tertentu',
                ];

                $targetsByOpd = RetributionTargets::select('master_opd_id', DB::raw('SUM(target_amount) as total_target'))
                    ->where('tahun', $realisasiYear)
                    ->groupBy('master_opd_id')
                    ->get()
                    ->keyBy('master_opd_id');

                $revenuesByOpd = Revenue::select('master_opd_id', DB::raw('SUM(total_revenue) as total_revenue'))
                    ->where('year', $realisasiYear)
                    ->whereBetween('date', [$realisasiStartDate, $realisasiEndDate])
                    ->groupBy('master_opd_id')
                    ->get()
                    ->keyBy('master_opd_id');

                $targetsByRetribusi = RetributionTargets::select('master_retribusi_id', DB::raw('SUM(target_amount) as total_target'))
                    ->where('tahun', $realisasiYear)
                    ->groupBy('master_retribusi_id')
                    ->get()
                    ->keyBy('master_retribusi_id');

                $revenuesByRetribusi = Revenue::select('master_retribusi_id', DB::raw('SUM(total_revenue) as total_revenue'))
                    ->where('year', $realisasiYear)
                    ->whereBetween('date', [$realisasiStartDate, $realisasiEndDate])
                    ->groupBy('master_retribusi_id')
                    ->get()
                    ->keyBy('master_retribusi_id');

                $opdIds = $targetsByOpd->keys()->merge($revenuesByOpd->keys())->unique();
                $realisasiByOpd = $opdIds->map(function ($opdId) use ($targetsByOpd, $revenuesByOpd) {
                    $opd = MasterOpd::find($opdId);
                    if (!$opd) return null;

                    $target = $targetsByOpd->get($opdId);
                    $revenue = $revenuesByOpd->get($opdId);

                    $targetAmount = $target ? (float) $target->total_target : 0;
                    $revenueAmount = $revenue ? (float) $revenue->total_revenue : 0;
                    $percentage = $targetAmount > 0 ? ($revenueAmount / $targetAmount) * 100 : 0;

                    return [
                        'opd_id' => $opdId,
                        'nama_opd' => $opd->nama_opd,
                        'target' => number_format($targetAmount, 2, '.', ''),
                        'realisasi' => number_format($revenueAmount, 2, '.', ''),
                        'percentage' => number_format($percentage, 2, '.', ''),
                        'status' => $percentage >= 100 ? 'achieved' : ($percentage >= 75 ? 'on-track' : 'below-target'),
                    ];
                })->filter()->sortByDesc(function ($item) {
                    return (float) $item['percentage'];
                })->take(10)->values()->all();

                $realisasiByRetribusi = collect($retribusiTypes)->map(function ($name, $id) use ($targetsByRetribusi, $revenuesByRetribusi) {
                    $target = $targetsByRetribusi->get($id);
                    $revenue = $revenuesByRetribusi->get($id);

                    $targetAmount = $target ? (float) $target->total_target : 0;
                    $revenueAmount = $revenue ? (float) $revenue->total_revenue : 0;
                    $percentage = $targetAmount > 0 ? ($revenueAmount / $targetAmount) * 100 : 0;

                    return [
                        'retribusi_id' => $id,
                        'nama_retribusi' => $name,
                        'target' => number_format($targetAmount, 2, '.', ''),
                        'realisasi' => number_format($revenueAmount, 2, '.', ''),
                        'percentage' => number_format($percentage, 2, '.', ''),
                        'status' => $percentage >= 100 ? 'achieved' : ($percentage >= 75 ? 'on-track' : 'below-target'),
                    ];
                })->values()->all();

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

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved realisasi vs target',
                'data' => $realisasiPayload,
                'filters' => [
                    'start_date' => $realisasiStartDate,
                    'end_date' => $realisasiEndDate,
                    'year' => $realisasiYear,
                ],
                'error' => $realisasiYearError,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving realisasi vs target',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get dashboard data (API version of index) - DEPRECATED: Use separate endpoints instead
     * This endpoint is kept for backward compatibility but returns lightweight summary only
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDashboardData(Request $request)
    {
        // No JWT auth needed - using EXTERNAL_API_TOKEN middleware
        try {
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
            $grandTotalTransaksi = $totalTransaksiRegular + $totalTransaksiInstallment;

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
                    $result[] = [
                        'retribusi_id' => $retribusiId,
                        'nama_retribusi' => $retribusiName,
                        'total_transaksi' => $data ? (int) $data->transaction_count : 0,
                        'total_pendapatan' => $data ? number_format((float) $data->total_revenue, 2, '.', '') : '0.00',
                    ];
                }

                return $result;
            });

            // --- OPD Performance vs Target ---
            $targetStartDate = $request->filled('target_start_date') ? $request->target_start_date : now()->startOfYear()->toDateString();
            $targetEndDate = $request->filled('target_end_date') ? $request->target_end_date : now()->endOfYear()->toDateString();
            $targetYearError = null;

            if (date('Y', strtotime($targetStartDate)) != date('Y', strtotime($targetEndDate))) {
                $targetYearError = 'Rentang tanggal harus dalam tahun yang sama. Data direset ke tahun berjalan.';
                $targetStartDate = now()->startOfYear()->toDateString();
                $targetEndDate = now()->endOfYear()->toDateString();
            }

            $targetYear = date('Y', strtotime($targetStartDate));
            $targetRangeKey = $targetStartDate . '_' . $targetEndDate;

            $opdPerformance = Cache::remember("dashboard:opd_performance:v{$dashboardVersion}:{$targetRangeKey}", 21600, function () use ($targetYear, $targetStartDate, $targetEndDate) {
                $targets = RetributionTargets::select('master_opd_id', DB::raw('SUM(target_amount) as total_target'))
                    ->where('tahun', $targetYear)
                    ->groupBy('master_opd_id')
                    ->get()
                    ->keyBy('master_opd_id');

                $revenues = Revenue::select('master_opd_id', DB::raw('SUM(total_revenue) as total_revenue'))
                    ->where('year', $targetYear)
                    ->whereBetween('date', [$targetStartDate, $targetEndDate])
                    ->groupBy('master_opd_id')
                    ->get()
                    ->keyBy('master_opd_id');

                $opdIds = $targets->keys()->merge($revenues->keys())->unique();

                $data = $opdIds->map(function ($opdId) use ($targets, $revenues) {
                    $opd = MasterOpd::find($opdId);
                    if (!$opd) return null;

                    $target = $targets->get($opdId);
                    $revenue = $revenues->get($opdId);

                    $targetAmount = $target ? (float) $target->total_target : 0;
                    $revenueAmount = $revenue ? (float) $revenue->total_revenue : 0;
                    $percentage = $targetAmount > 0 ? ($revenueAmount / $targetAmount) * 100 : 0;

                    return [
                        'opd_id' => $opdId,
                        'nama_opd' => $opd->nama_opd,
                        'target' => number_format($targetAmount, 2, '.', ''),
                        'realisasi' => number_format($revenueAmount, 2, '.', ''),
                        'percentage' => number_format($percentage, 2, '.', ''),
                        'status' => $percentage >= 100 ? 'achieved' : ($percentage >= 75 ? 'on-track' : 'below-target'),
                    ];
                })->filter()->sortByDesc(function ($item) {
                    return (float) $item['percentage'];
                })->values()->all();

                return $data;
            });

            // --- Realisasi vs Target Retribusi ---
            $realisasiStartDate = $request->filled('realisasi_start_date') ? $request->realisasi_start_date : now()->startOfYear()->toDateString();
            $realisasiEndDate = $request->filled('realisasi_end_date') ? $request->realisasi_end_date : now()->endOfYear()->toDateString();
            $realisasiYearError = null;

            if (date('Y', strtotime($realisasiStartDate)) != date('Y', strtotime($realisasiEndDate))) {
                $realisasiYearError = 'Rentang tanggal harus dalam tahun yang sama. Data direset ke tahun berjalan.';
                $realisasiStartDate = now()->startOfYear()->toDateString();
                $realisasiEndDate = now()->endOfYear()->toDateString();
            }

            $realisasiYear = date('Y', strtotime($realisasiStartDate));
            $realisasiRangeKey = $realisasiStartDate . '_' . $realisasiEndDate;

            $realisasiPayload = Cache::remember("dashboard:realisasi:v{$dashboardVersion}:{$realisasiRangeKey}", 21600, function () use ($realisasiYear, $realisasiStartDate, $realisasiEndDate) {
                $retribusiTypes = [
                    1 => 'Retribusi Jasa Umum',
                    2 => 'Retribusi Jasa Usaha',
                    3 => 'Retribusi Perizinan Tertentu',
                ];

                // Targets by OPD
                $targetsByOpd = RetributionTargets::select('master_opd_id', DB::raw('SUM(target_amount) as total_target'))
                    ->where('tahun', $realisasiYear)
                    ->groupBy('master_opd_id')
                    ->get()
                    ->keyBy('master_opd_id');

                // Revenues by OPD
                $revenuesByOpd = Revenue::select('master_opd_id', DB::raw('SUM(total_revenue) as total_revenue'))
                    ->where('year', $realisasiYear)
                    ->whereBetween('date', [$realisasiStartDate, $realisasiEndDate])
                    ->groupBy('master_opd_id')
                    ->get()
                    ->keyBy('master_opd_id');

                // Targets by Retribusi
                $targetsByRetribusi = RetributionTargets::select('master_retribusi_id', DB::raw('SUM(target_amount) as total_target'))
                    ->where('tahun', $realisasiYear)
                    ->groupBy('master_retribusi_id')
                    ->get()
                    ->keyBy('master_retribusi_id');

                // Revenues by Retribusi
                $revenuesByRetribusi = Revenue::select('master_retribusi_id', DB::raw('SUM(total_revenue) as total_revenue'))
                    ->where('year', $realisasiYear)
                    ->whereBetween('date', [$realisasiStartDate, $realisasiEndDate])
                    ->groupBy('master_retribusi_id')
                    ->get()
                    ->keyBy('master_retribusi_id');

                // Build OPD data
                $opdIds = $targetsByOpd->keys()->merge($revenuesByOpd->keys())->unique();
                $realisasiByOpd = $opdIds->map(function ($opdId) use ($targetsByOpd, $revenuesByOpd) {
                    $opd = MasterOpd::find($opdId);
                    if (!$opd) return null;

                    $target = $targetsByOpd->get($opdId);
                    $revenue = $revenuesByOpd->get($opdId);

                    $targetAmount = $target ? (float) $target->total_target : 0;
                    $revenueAmount = $revenue ? (float) $revenue->total_revenue : 0;
                    $percentage = $targetAmount > 0 ? ($revenueAmount / $targetAmount) * 100 : 0;

                    return [
                        'opd_id' => $opdId,
                        'nama_opd' => $opd->nama_opd,
                        'target' => number_format($targetAmount, 2, '.', ''),
                        'realisasi' => number_format($revenueAmount, 2, '.', ''),
                        'percentage' => number_format($percentage, 2, '.', ''),
                        'status' => $percentage >= 100 ? 'achieved' : ($percentage >= 75 ? 'on-track' : 'below-target'),
                    ];
                })->filter()->sortByDesc(function ($item) {
                    return (float) $item['percentage'];
                })->take(10)->values()->all();

                // Build Retribusi data
                $realisasiByRetribusi = collect($retribusiTypes)->map(function ($name, $id) use ($targetsByRetribusi, $revenuesByRetribusi) {
                    $target = $targetsByRetribusi->get($id);
                    $revenue = $revenuesByRetribusi->get($id);

                    $targetAmount = $target ? (float) $target->total_target : 0;
                    $revenueAmount = $revenue ? (float) $revenue->total_revenue : 0;
                    $percentage = $targetAmount > 0 ? ($revenueAmount / $targetAmount) * 100 : 0;

                    return [
                        'retribusi_id' => $id,
                        'nama_retribusi' => $name,
                        'target' => number_format($targetAmount, 2, '.', ''),
                        'realisasi' => number_format($revenueAmount, 2, '.', ''),
                        'percentage' => number_format($percentage, 2, '.', ''),
                        'status' => $percentage >= 100 ? 'achieved' : ($percentage >= 75 ? 'on-track' : 'below-target'),
                    ];
                })->values()->all();

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

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved dashboard data',
                'data' => [
                    'summary' => [
                        'total_transaksi_regular' => $totalTransaksiRegular,
                        'total_transaksi_installment' => $totalTransaksiInstallment,
                        'grand_total_transaksi' => $grandTotalTransaksi,
                        'grand_total_pendapatan' => number_format($grandTotalPendapatan, 2, '.', ''),
                    ],
                    'retribusi_breakdown' => $retribusiBreakdown,
                    'opd_performance' => [
                        'data' => $opdPerformance,
                        'filters' => [
                            'start_date' => $targetStartDate,
                            'end_date' => $targetEndDate,
                            'year' => $targetYear,
                        ],
                        'error' => $targetYearError,
                    ],
                    'realisasi_vs_target' => [
                        'summary' => [
                            'total_target' => number_format($realisasiPayload['totalTarget'], 2, '.', ''),
                            'total_realisasi' => number_format($realisasiPayload['totalRealisasi'], 2, '.', ''),
                            'percentage' => number_format($realisasiPayload['realisasiPercentage'], 2, '.', ''),
                        ],
                        'by_opd' => $realisasiPayload['realisasiByOpd'],
                        'by_retribusi' => $realisasiPayload['realisasiByRetribusi'],
                        'filters' => [
                            'start_date' => $realisasiStartDate,
                            'end_date' => $realisasiEndDate,
                            'year' => $realisasiYear,
                        ],
                        'error' => $realisasiYearError,
                    ],
                    'metadata' => [
                        'date_range' => [
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                        ],
                        'cache_version' => $dashboardVersion,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving dashboard data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get OPD list with search
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOpdList(Request $request)
    {
        // No JWT auth needed - using EXTERNAL_API_TOKEN middleware
        try {
            $search = $request->get('search');
            $perPage = $request->get('per_page', 15);

            $query = MasterOpd::query();

            if ($search) {
                $query->where('nama_opd', 'like', "%{$search}%");
            }

            $opds = $query->orderBy('nama_opd')->paginate($perPage);

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved OPD list',
                'data' => $opds->items(),
                'pagination' => [
                    'current_page' => $opds->currentPage(),
                    'last_page' => $opds->lastPage(),
                    'per_page' => $opds->perPage(),
                    'total' => $opds->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving OPD list',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Retribusi list with search
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRetribusiList(Request $request)
    {
        // No JWT auth needed - using EXTERNAL_API_TOKEN middleware
        try {
            $search = $request->get('search');
            $perPage = $request->get('per_page', 15);

            $query = MasterRetribusi::query();

            if ($search) {
                $query->where('nama_retribusi', 'like', "%{$search}%");
            }

            $retribusi = $query->orderBy('nama_retribusi')->paginate($perPage);

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved Retribusi list',
                'data' => $retribusi->items(),
                'pagination' => [
                    'current_page' => $retribusi->currentPage(),
                    'last_page' => $retribusi->lastPage(),
                    'per_page' => $retribusi->perPage(),
                    'total' => $retribusi->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving Retribusi list',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Lokasi list with search
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLokasiList(Request $request)
    {
        // No JWT auth needed - using EXTERNAL_API_TOKEN middleware
        try {
            $search = $request->get('search');
            $perPage = $request->get('per_page', 15);

            $query = MasterLokasiObjek::query();

            if ($search) {
                $query->where('nama_lokasi', 'like', "%{$search}%");
            }

            $lokasi = $query->orderBy('nama_lokasi')->paginate($perPage);

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved Lokasi list',
                'data' => $lokasi->items(),
                'pagination' => [
                    'current_page' => $lokasi->currentPage(),
                    'last_page' => $lokasi->lastPage(),
                    'per_page' => $lokasi->perPage(),
                    'total' => $lokasi->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving Lokasi list',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Objek list with search and filters
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getObjekList(Request $request)
    {
        // No JWT auth needed - using EXTERNAL_API_TOKEN middleware
        try {
            $search = $request->get('search');
            $opdFilter = $request->get('opd_id');
            $retribusiFilter = $request->get('retribusi_id');
            $lokasiFilter = $request->get('lokasi_id');
            $perPage = $request->get('per_page', 15);

            $query = MasterObjek::with(['opd', 'retribusi', 'lokasi']);

            if ($search) {
                $query->where('nama_objek', 'like', "%{$search}%");
            }

            if ($opdFilter) {
                $query->where('master_opd_id', $opdFilter);
            }

            if ($retribusiFilter) {
                $query->where('master_retribusi_id', $retribusiFilter);
            }

            if ($lokasiFilter) {
                $query->where('master_lokasi_id', $lokasiFilter);
            }

            $objek = $query->orderBy('nama_objek')->paginate($perPage);

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved Objek list',
                'data' => $objek->items(),
                'pagination' => [
                    'current_page' => $objek->currentPage(),
                    'last_page' => $objek->lastPage(),
                    'per_page' => $objek->perPage(),
                    'total' => $objek->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving Objek list',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get realisasi lokasi by OPD
     *
     * @param int $opdId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRealisasiLokasiByOpd(Request $request, $opdId)
    {
        // No JWT auth needed - using EXTERNAL_API_TOKEN middleware
        try {
            $opd = MasterOpd::find($opdId);
            if (!$opd) {
                return response()->json([
                    'status' => 404,
                    'message' => 'OPD not found',
                ], 404);
            }

            $year = $request->get('tahun', now()->year);

            // Get all lokasi IDs for this OPD
            $lokasiIds = Revenue::where('master_opd_id', $opdId)
                ->where('year', $year)
                ->distinct()
                ->pluck('master_lokasi_id');

            // Get revenue by lokasi
            $revenueByLokasi = Revenue::where('master_opd_id', $opdId)
                ->where('year', $year)
                ->selectRaw('master_lokasi_id, SUM(total_revenue) as total_realisasi, SUM(total_transactions) as total_transaksi')
                ->groupBy('master_lokasi_id')
                ->get()
                ->keyBy('master_lokasi_id');

            // Get targets by lokasi
            $targetByLokasi = RetributionTargets::where('master_opd_id', $opdId)
                ->where('tahun', $year)
                ->selectRaw('master_lokasi_id, SUM(target_amount) as total_target')
                ->groupBy('master_lokasi_id')
                ->get()
                ->keyBy('master_lokasi_id');

            // Combine data
            $data = MasterLokasiObjek::whereIn('id', $lokasiIds)
                ->get()
                ->map(function ($lokasi) use ($revenueByLokasi, $targetByLokasi) {
                    $revenue = $revenueByLokasi->get($lokasi->id);
                    $target = $targetByLokasi->get($lokasi->id);

                    $realisasi = $revenue ? $revenue->total_realisasi : 0;
                    $targetAmount = $target ? $target->total_target : 0;
                    $percentage = $targetAmount > 0 ? ($realisasi / $targetAmount) * 100 : 0;

                    return [
                        'lokasi_id' => $lokasi->id,
                        'nama_lokasi' => $lokasi->nama_lokasi,
                        'target' => number_format($targetAmount, 2, '.', ''),
                        'realisasi' => number_format($realisasi, 2, '.', ''),
                        'total_transaksi' => $revenue ? (int) $revenue->total_transaksi : 0,
                        'percentage' => number_format($percentage, 2, '.', ''),
                        'status' => $percentage >= 100 ? 'achieved' : ($percentage >= 75 ? 'on-track' : 'below-target'),
                    ];
                })
                ->sortByDesc(fn($item) => (float) str_replace(',', '', $item['realisasi']))
                ->values();

            // Calculate totals
            $totalTarget = $data->sum(fn($item) => (float) str_replace(',', '', $item['target']));
            $totalRealisasi = $data->sum(fn($item) => (float) str_replace(',', '', $item['realisasi']));
            $totalPercentage = $totalTarget > 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved realisasi by lokasi for OPD',
                'data' => [
                    'opd_info' => [
                        'id' => $opd->id,
                        'nama_opd' => $opd->nama_opd,
                    ],
                    'year' => (int) $year,
                    'summary' => [
                        'total_target' => number_format($totalTarget, 2, '.', ''),
                        'total_realisasi' => number_format($totalRealisasi, 2, '.', ''),
                        'total_percentage' => number_format($totalPercentage, 2, '.', ''),
                    ],
                    'lokasi_data' => $data,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving realisasi by lokasi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get performance detail (full OPD performance data)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPerformanceDetail(Request $request)
    {
        // No JWT auth needed - using EXTERNAL_API_TOKEN middleware
        try {
            $targetYear = $request->filled('tahun') ? $request->tahun : now()->year;
            $startDate = $request->filled('start_date') ? $request->start_date : now()->startOfYear()->toDateString();
            $endDate = $request->filled('end_date') ? $request->end_date : now()->endOfYear()->toDateString();
            $error = null;

            if (date('Y', strtotime($startDate)) != date('Y', strtotime($endDate))) {
                $error = 'Rentang tanggal harus dalam tahun yang sama. Data direset ke tahun berjalan.';
                $startDate = now()->startOfYear()->toDateString();
                $endDate = now()->endOfYear()->toDateString();
                $targetYear = now()->year;
            }

            $targets = RetributionTargets::select('master_opd_id', DB::raw('SUM(target_amount) as total_target'))
                ->where('tahun', $targetYear)
                ->groupBy('master_opd_id')
                ->get()
                ->keyBy('master_opd_id');

            $revenues = Revenue::select('master_opd_id', DB::raw('SUM(total_revenue) as total_revenue'))
                ->where('year', $targetYear)
                ->whereBetween('date', [$startDate, $endDate])
                ->groupBy('master_opd_id')
                ->get()
                ->keyBy('master_opd_id');

            $opdIds = $targets->keys()->merge($revenues->keys())->unique();

            $data = $opdIds->map(function ($opdId) use ($targets, $revenues) {
                $opd = MasterOpd::find($opdId);
                if (!$opd) return null;

                $target = $targets->get($opdId);
                $revenue = $revenues->get($opdId);

                $targetAmount = $target ? (float) $target->total_target : 0;
                $revenueAmount = $revenue ? (float) $revenue->total_revenue : 0;
                $percentage = $targetAmount > 0 ? ($revenueAmount / $targetAmount) * 100 : 0;

                return [
                    'opd_id' => $opdId,
                    'nama_opd' => $opd->nama_opd,
                    'target' => number_format($targetAmount, 2, '.', ''),
                    'realisasi' => number_format($revenueAmount, 2, '.', ''),
                    'percentage' => number_format($percentage, 2, '.', ''),
                    'status' => $percentage >= 100 ? 'achieved' : ($percentage >= 75 ? 'on-track' : 'below-target'),
                ];
            })->filter()->sortByDesc(function ($item) {
                return (float) $item['percentage'];
            })->values();

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved performance detail',
                'data' => [
                    'opd_performance' => $data,
                    'filters' => [
                        'tahun' => (int) $targetYear,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ],
                    'error' => $error,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving performance detail',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get realisasi detail (full realisasi vs target data)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRealisasiDetail(Request $request)
    {
        // No JWT auth needed - using EXTERNAL_API_TOKEN middleware
        try {
            $realisasiYear = $request->filled('tahun') ? $request->tahun : now()->year;
            $startDate = $request->filled('start_date') ? $request->start_date : now()->startOfYear()->toDateString();
            $endDate = $request->filled('end_date') ? $request->end_date : now()->endOfYear()->toDateString();
            $error = null;

            if (date('Y', strtotime($startDate)) != date('Y', strtotime($endDate))) {
                $error = 'Rentang tanggal harus dalam tahun yang sama. Data direset ke tahun berjalan.';
                $startDate = now()->startOfYear()->toDateString();
                $endDate = now()->endOfYear()->toDateString();
                $realisasiYear = now()->year;
            }

            $retribusiTypes = [
                1 => 'Retribusi Jasa Umum',
                2 => 'Retribusi Jasa Usaha',
                3 => 'Retribusi Perizinan Tertentu',
            ];

            // Targets by OPD
            $targetsByOpd = RetributionTargets::select('master_opd_id', DB::raw('SUM(target_amount) as total_target'))
                ->where('tahun', $realisasiYear)
                ->groupBy('master_opd_id')
                ->get()
                ->keyBy('master_opd_id');

            // Revenues by OPD
            $revenuesByOpd = Revenue::select('master_opd_id', DB::raw('SUM(total_revenue) as total_revenue'))
                ->where('year', $realisasiYear)
                ->whereBetween('date', [$startDate, $endDate])
                ->groupBy('master_opd_id')
                ->get()
                ->keyBy('master_opd_id');

            // Targets by Retribusi
            $targetsByRetribusi = RetributionTargets::select('master_retribusi_id', DB::raw('SUM(target_amount) as total_target'))
                ->where('tahun', $realisasiYear)
                ->groupBy('master_retribusi_id')
                ->get()
                ->keyBy('master_retribusi_id');

            // Revenues by Retribusi
            $revenuesByRetribusi = Revenue::select('master_retribusi_id', DB::raw('SUM(total_revenue) as total_revenue'))
                ->where('year', $realisasiYear)
                ->whereBetween('date', [$startDate, $endDate])
                ->groupBy('master_retribusi_id')
                ->get()
                ->keyBy('master_retribusi_id');

            // Build OPD data
            $opdIds = $targetsByOpd->keys()->merge($revenuesByOpd->keys())->unique();
            $realisasiByOpd = $opdIds->map(function ($opdId) use ($targetsByOpd, $revenuesByOpd) {
                $opd = MasterOpd::find($opdId);
                if (!$opd) return null;

                $target = $targetsByOpd->get($opdId);
                $revenue = $revenuesByOpd->get($opdId);

                $targetAmount = $target ? (float) $target->total_target : 0;
                $revenueAmount = $revenue ? (float) $revenue->total_revenue : 0;
                $percentage = $targetAmount > 0 ? ($revenueAmount / $targetAmount) * 100 : 0;

                return [
                    'opd_id' => $opdId,
                    'nama_opd' => $opd->nama_opd,
                    'target' => number_format($targetAmount, 2, '.', ''),
                    'realisasi' => number_format($revenueAmount, 2, '.', ''),
                    'percentage' => number_format($percentage, 2, '.', ''),
                    'status' => $percentage >= 100 ? 'achieved' : ($percentage >= 75 ? 'on-track' : 'below-target'),
                ];
            })->filter()->sortByDesc(function ($item) {
                return (float) $item['percentage'];
            })->values();

            // Build Retribusi data
            $realisasiByRetribusi = collect($retribusiTypes)->map(function ($name, $id) use ($targetsByRetribusi, $revenuesByRetribusi) {
                $target = $targetsByRetribusi->get($id);
                $revenue = $revenuesByRetribusi->get($id);

                $targetAmount = $target ? (float) $target->total_target : 0;
                $revenueAmount = $revenue ? (float) $revenue->total_revenue : 0;
                $percentage = $targetAmount > 0 ? ($revenueAmount / $targetAmount) * 100 : 0;

                return [
                    'retribusi_id' => $id,
                    'nama_retribusi' => $name,
                    'target' => number_format($targetAmount, 2, '.', ''),
                    'realisasi' => number_format($revenueAmount, 2, '.', ''),
                    'percentage' => number_format($percentage, 2, '.', ''),
                    'status' => $percentage >= 100 ? 'achieved' : ($percentage >= 75 ? 'on-track' : 'below-target'),
                ];
            })->values();

            $totalTarget = RetributionTargets::where('tahun', $realisasiYear)->sum('target_amount');
            $totalRealisasi = Revenue::where('year', $realisasiYear)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('total_revenue');
            $realisasiPercentage = $totalTarget > 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved realisasi detail',
                'data' => [
                    'summary' => [
                        'total_target' => number_format($totalTarget, 2, '.', ''),
                        'total_realisasi' => number_format($totalRealisasi, 2, '.', ''),
                        'percentage' => number_format($realisasiPercentage, 2, '.', ''),
                    ],
                    'by_opd' => $realisasiByOpd,
                    'by_retribusi' => $realisasiByRetribusi,
                    'filters' => [
                        'tahun' => (int) $realisasiYear,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ],
                    'error' => $error,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving realisasi detail',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get lokasi by OPD (for dropdown)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLokasiByOpd(Request $request)
    {
        // No JWT auth needed - using EXTERNAL_API_TOKEN middleware
        try {
            $opdId = $request->get('opd_id');

            if (!$opdId) {
                return response()->json([
                    'status' => 400,
                    'message' => 'OPD ID is required',
                ], 400);
            }

            $lokasi = MasterLokasiObjek::whereHas('objek', function ($query) use ($opdId) {
                $query->where('master_opd_id', $opdId);
            })->distinct()->get(['id', 'nama_lokasi']);

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved lokasi list',
                'data' => $lokasi,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving lokasi list',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get rincian by retribusi (for dropdown)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRincianByRetribusi(Request $request)
    {
        // No JWT auth needed - using EXTERNAL_API_TOKEN middleware
        try {
            $retribusiId = $request->get('retribusi_id');

            if (!$retribusiId) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Retribusi ID is required',
                ], 400);
            }

            $rincian = MasterRincianRetribusi::where('master_retribusi_id', $retribusiId)
                ->get(['id', 'nama_rincian']);

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved rincian list',
                'data' => $rincian,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving rincian list',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get realisasi retribusi by lokasi
     *
     * @param int $opdId
     * @param int $lokasiId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRealisasiRetribusiByLokasi(Request $request, $opdId, $lokasiId)
    {
        // No JWT auth needed - using EXTERNAL_API_TOKEN middleware
        try {
            $opd = MasterOpd::find($opdId);
            $lokasi = MasterLokasiObjek::find($lokasiId);

            if (!$opd || !$lokasi) {
                return response()->json([
                    'status' => 404,
                    'message' => 'OPD or Lokasi not found',
                ], 404);
            }

            $year = $request->filled('tahun') ? $request->tahun : now()->year;

            $retribusiTypes = [
                1 => 'Retribusi Jasa Umum',
                2 => 'Retribusi Jasa Usaha',
                3 => 'Retribusi Perizinan Tertentu',
            ];

            // Revenue per retribusi type for this lokasi
            $revenueByRetribusi = Revenue::where('master_lokasi_id', $lokasiId)
                ->where('master_opd_id', $opdId)
                ->where('year', $year)
                ->selectRaw('master_retribusi_id, SUM(total_revenue) as total_realisasi, SUM(total_transactions) as total_transaksi')
                ->groupBy('master_retribusi_id')
                ->get()
                ->keyBy('master_retribusi_id');

            // Target per retribusi type for this lokasi
            $targetByRetribusi = RetributionTargets::where('master_lokasi_id', $lokasiId)
                ->where('master_opd_id', $opdId)
                ->where('tahun', $year)
                ->selectRaw('master_retribusi_id, SUM(target_amount) as total_target')
                ->groupBy('master_retribusi_id')
                ->get()
                ->keyBy('master_retribusi_id');

            // Always return all 3 retribusi types with default zero values
            $retribusiBreakdown = collect($retribusiTypes)->map(function ($name, $id) use ($revenueByRetribusi, $targetByRetribusi) {
                $rev = $revenueByRetribusi->get($id);
                $tgt = $targetByRetribusi->get($id);
                $realisasi = (float) ($rev->total_realisasi ?? 0);
                $targetAmount = (float) ($tgt->total_target ?? 0);
                $percentage = $targetAmount > 0 ? ($realisasi / $targetAmount) * 100 : 0;

                return [
                    'retribusi_id' => $id,
                    'nama_retribusi' => $name,
                    'target' => number_format($targetAmount, 2, '.', ''),
                    'realisasi' => number_format($realisasi, 2, '.', ''),
                    'total_transaksi' => (int) ($rev->total_transaksi ?? 0),
                    'percentage' => number_format($percentage, 2, '.', ''),
                    'status' => $percentage >= 100 ? 'achieved' : ($percentage >= 75 ? 'on-track' : 'below-target'),
                ];
            })->values();

            // Overall totals for the lokasi
            $totalTarget = $retribusiBreakdown->sum(fn($item) => (float) $item['target']);
            $totalRealisasi = $retribusiBreakdown->sum(fn($item) => (float) $item['realisasi']);
            $totalTransaksi = $retribusiBreakdown->sum('total_transaksi');
            $totalPercentage = $totalTarget > 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved retribusi breakdown for lokasi',
                'data' => [
                    'opd_info' => [
                        'id' => $opd->id,
                        'nama_opd' => $opd->nama_opd,
                    ],
                    'lokasi_info' => [
                        'id' => $lokasi->id,
                        'nama_lokasi' => $lokasi->nama_lokasi,
                    ],
                    'year' => (int) $year,
                    'summary' => [
                        'total_target' => number_format($totalTarget, 2, '.', ''),
                        'total_realisasi' => number_format($totalRealisasi, 2, '.', ''),
                        'total_transaksi' => $totalTransaksi,
                        'total_percentage' => number_format($totalPercentage, 2, '.', ''),
                        'status' => $totalPercentage >= 100 ? 'achieved' : ($totalPercentage >= 75 ? 'on-track' : 'below-target'),
                    ],
                    'retribusi_breakdown' => $retribusiBreakdown,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving retribusi breakdown for lokasi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
