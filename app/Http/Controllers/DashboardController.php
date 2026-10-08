<?php

namespace App\Http\Controllers;

use App\Models\MasterLokasiObjek;
use App\Models\MasterOpd;
use App\Models\MasterRetribusi;
use App\Models\RetributionTargets;
use App\Models\Revenue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class DashboardController extends Controller
{
    /**
     * Get Dashboard Data from Revenue Table
     * Optimized version using pre-aggregated revenue data with comprehensive metrics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDashboard(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (! $user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"], 401);
        }

        try {
            // Date range setup
            $startDate = $request->filled('start_date') ? $request->start_date : null;
            $endDate = $request->filled('end_date') ? $request->end_date : null;

            // 1. Total Transaksi Regular (non-installment)
            $totalTransaksiRegular = Revenue::where('is_installment', 0)
                ->when($startDate, fn($q) => $q->where('date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->where('date', '<=', $endDate))
                ->sum('total_transactions');

            // 2. Total Transaksi Installment
            $totalTransaksiInstallment = Revenue::where('is_installment', 1)
                ->when($startDate, fn($q) => $q->where('date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->where('date', '<=', $endDate))
                ->sum('total_transactions');

            // 3. Grand Total Pendapatan
            $grandTotalPendapatan = Revenue::when($startDate, fn($q) => $q->where('date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->where('date', '<=', $endDate))
                ->sum('total_revenue');

            // 4. Transaction Status Breakdown (from actual transactions table)
            $statusBreakdown = DB::table('transactions as t')
                ->join('master_payment_status as mps', 't.master_status_pembayaran_id', '=', 'mps.id')
                ->select(
                    'mps.id as status_id',
                    'mps.status_pembayaran',
                    DB::raw('COUNT(*) as total_transaksi')
                )
                ->when($startDate, fn($q) => $q->where('t.registration_date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->where('t.registration_date', '<=', $endDate))
                ->groupBy('mps.id', 'mps.status_pembayaran')
                ->get()
                ->map(function ($item) {
                    return [
                        'status_id' => $item->status_id,
                        'status_name' => $item->status_pembayaran,
                        'total' => (int) $item->total_transaksi,
                    ];
                });

            // 5. Pendapatan Per Retribusi Type (breakdown)
            $retribusiTypes = [
                1 => 'Retribusi Jasa Umum',
                2 => 'Retribusi Jasa Usaha',
                3 => 'Retribusi Perizinan Tertentu',
            ];

            $retribusiData = Revenue::when($startDate, fn($q) => $q->where('date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->where('date', '<=', $endDate))
                ->selectRaw('master_retribusi_id, SUM(total_transactions) as transaction_count, SUM(total_revenue) as total_revenue')
                ->groupBy('master_retribusi_id')
                ->get()
                ->keyBy('master_retribusi_id');

            $pendapatanPerRetribusi = collect($retribusiTypes)->map(function ($name, $id) use ($retribusiData) {
                $data = $retribusiData->get($id);

                return [
                    'retribusi_id' => $id,
                    'nama_retribusi' => $name,
                    'total_pendapatan' => number_format($data->total_revenue ?? 0, 2, '.', ''),
                    'total_transaksi' => (int) ($data->transaction_count ?? 0),
                ];
            })->values();

            // 6. Top 10 OPD by Revenue
            $top10OpdByRevenue = Revenue::with('opd:id,nama_opd')
                ->select('master_opd_id', DB::raw('SUM(total_revenue) as total_pendapatan'))
                ->when($startDate, fn($q) => $q->where('date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->where('date', '<=', $endDate))
                ->groupBy('master_opd_id')
                ->orderByDesc('total_pendapatan')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'opd_id' => $item->master_opd_id,
                        'nama_opd' => $item->opd ? $item->opd->nama_opd : null,
                        'total_pendapatan' => number_format($item->total_pendapatan, 2, '.', ''),
                    ];
                });

            // 7. Revenue Trend (daily aggregation)
            $revenueTrend = Revenue::select(
                DB::raw('DATE(date) as tanggal'),
                DB::raw('SUM(total_revenue) as total_pendapatan'),
                DB::raw('SUM(total_transactions) as total_transaksi')
            )
                ->when($startDate, fn($q) => $q->where('date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->where('date', '<=', $endDate))
                ->groupBy('tanggal')
                ->orderBy('tanggal')
                ->get()
                ->map(function ($item) {
                    return [
                        'tanggal' => $item->tanggal,
                        'total_pendapatan' => number_format($item->total_pendapatan, 2, '.', ''),
                        'total_transaksi' => (int) $item->total_transaksi,
                    ];
                });

            // 8. Master Data Counts
            $masterCounts = [
                'total_opd' => MasterOpd::count(),
                'total_retribusi' => MasterRetribusi::count(),
                'total_lokasi' => MasterLokasiObjek::count(),
            ];

            // 9. Recent Activity (last 10 revenue entries)
            $recentActivity = Revenue::with(['opd:id,nama_opd', 'retribusi:id,nama_retribusi'])
                ->when($startDate, fn($q) => $q->where('date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->where('date', '<=', $endDate))
                ->orderByDesc('date')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'date' => $item->date,
                        'opd_name' => $item->opd ? $item->opd->nama_opd : null,
                        'retribusi_name' => $item->retribusi ? $item->retribusi->nama_retribusi : null,
                        'total_revenue' => number_format($item->total_revenue, 2, '.', ''),
                        'total_transactions' => (int) $item->total_transactions,
                        'is_installment' => (bool) $item->is_installment,
                    ];
                });

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved dashboard data',
                'data' => [
                    // Summary Metrics
                    'summary' => [
                        'grand_total_transaksi' => (int) $totalTransaksiRegular,
                        'grand_total_installment' => (int) $totalTransaksiInstallment,
                        'total_semua_transaksi' => (int) ($totalTransaksiRegular + $totalTransaksiInstallment),
                        'grand_total_pendapatan' => number_format($grandTotalPendapatan, 2, '.', ''),
                    ],

                    // Transaction Status Breakdown
                    'status_breakdown' => $statusBreakdown,

                    // Retribusi Breakdown
                    'retribusi_breakdown' => $pendapatanPerRetribusi,

                    // Top Performers
                    'top_10_opd' => $top10OpdByRevenue,

                    // Trends
                    'revenue_trend' => $revenueTrend,

                    // Recent Activity
                    'recent_activity' => $recentActivity,

                    // Master Data Counts
                    'master_data' => $masterCounts,

                    // Metadata
                    'metadata' => [
                        'date_range' => [
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                        ],
                        'available_years' => $this->getAvailableYears(),
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
     * Get Dashboard Data for Specific OPD from Revenue Table
     *
     * @param  int  $opdId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDashboardByOpd(Request $request, $opdId)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (! $user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"], 401);
        }

        try {
            // Validate OPD exists
            $opd = MasterOpd::find($opdId);
            if (! $opd) {
                return response()->json([
                    'status' => 404,
                    'message' => 'OPD not found',
                ], 404);
            }

            // Date range setup
            $startDate = $request->filled('start_date') ? $request->start_date : null;
            $endDate = $request->filled('end_date') ? $request->end_date : null;

            // Base query for this OPD
            $baseQuery = Revenue::where('master_opd_id', $opdId)
                ->when($startDate, fn($q) => $q->where('date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->where('date', '<=', $endDate));

            // 1. Total Transaksi Regular
            $totalTransaksiRegular = (clone $baseQuery)
                ->where('is_installment', 0)
                ->sum('total_transactions');

            // 2. Total Transaksi Installment
            $totalTransaksiInstallment = (clone $baseQuery)
                ->where('is_installment', 1)
                ->sum('total_transactions');

            // 3. Total Pendapatan OPD
            $totalPendapatan = (clone $baseQuery)->sum('total_revenue');

            // 4. Breakdown per Retribusi Type (with default values)
            $retribusiTypes = [
                1 => 'Retribusi Jasa Umum',
                2 => 'Retribusi Jasa Usaha',
                3 => 'Retribusi Perizinan Tertentu',
            ];

            $retribusiData = (clone $baseQuery)
                ->selectRaw('master_retribusi_id, SUM(total_revenue) as total_revenue, SUM(total_transactions) as transaction_count')
                ->groupBy('master_retribusi_id')
                ->get()
                ->keyBy('master_retribusi_id');

            $pendapatanPerRetribusi = collect($retribusiTypes)->map(function ($name, $id) use ($retribusiData) {
                $data = $retribusiData->get($id);

                return [
                    'retribusi_id' => $id,
                    'nama_retribusi' => $name,
                    'total_pendapatan' => number_format($data->total_revenue ?? 0, 2, '.', ''),
                    'total_transaksi' => (int) ($data->transaction_count ?? 0),
                ];
            })->values();

            // 5. Revenue Trend
            $revenueTrend = (clone $baseQuery)
                ->selectRaw('DATE(date) as tanggal, SUM(total_revenue) as total_pendapatan, SUM(total_transactions) as total_transaksi')
                ->groupBy('tanggal')
                ->orderBy('tanggal')
                ->get()
                ->map(fn($item) => [
                    'tanggal' => $item->tanggal,
                    'total_pendapatan' => number_format($item->total_pendapatan, 2, '.', ''),
                    'total_transaksi' => (int) $item->total_transaksi,
                ]);

            // 6. Top 10 Lokasi by Revenue
            $revenueByLokasi = (clone $baseQuery)
                ->with('lokasi:id,nama_lokasi')
                ->selectRaw('master_lokasi_id, SUM(total_revenue) as total_pendapatan, SUM(total_transactions) as total_transaksi')
                ->groupBy('master_lokasi_id')
                ->orderByDesc('total_pendapatan')
                ->limit(10)
                ->get()
                ->map(fn($item) => [
                    'lokasi_id' => $item->master_lokasi_id,
                    'nama_lokasi' => $item->lokasi ? $item->lokasi->nama_lokasi : null,
                    'total_pendapatan' => number_format($item->total_pendapatan, 2, '.', ''),
                    'total_transaksi' => (int) $item->total_transaksi,
                ]);

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved dashboard data for OPD',
                'data' => [
                    'opd_info' => [
                        'id' => $opd->id,
                        'nama_opd' => $opd->nama_opd,
                    ],
                    'summary' => [
                        'grand_total_transaksi' => (int) $totalTransaksiRegular,
                        'grand_total_installment' => (int) $totalTransaksiInstallment,
                        'total_semua_transaksi' => (int) ($totalTransaksiRegular + $totalTransaksiInstallment),
                        'total_pendapatan' => number_format($totalPendapatan, 2, '.', ''),
                    ],
                    'retribusi_breakdown' => $pendapatanPerRetribusi,
                    'lokasi_breakdown' => $revenueByLokasi,
                    'revenue_trend' => $revenueTrend,
                    'metadata' => [
                        'date_range' => [
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                        ],
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving dashboard data for OPD',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Retribution Targets with Revenue Comparison
     * Compares targets from retribution_targets with actual revenue from revenues table
     * Note: Targets are always per year. Revenue can be filtered by date range within that year.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRetributionTargets(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (! $user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"], 401);
        }

        try {
            // Year parameter for targets (single year only, consistent with Web Controller)
            $targetYear = $request->filled('year') ? (int) $request->year : (int) date('Y');

            // Date range for revenue filtering (optional, within the target year)
            $startDate = $request->filled('start_date') ? $request->start_date : null;
            $endDate = $request->filled('end_date') ? $request->end_date : null;

            // Validate that date range is within the same year as target year
            if ($startDate && date('Y', strtotime($startDate)) != $targetYear) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Start date must be in the same year as target year',
                ], 400);
            }
            if ($endDate && date('Y', strtotime($endDate)) != $targetYear) {
                return response()->json([
                    'status' => 400,
                    'message' => 'End date must be in the same year as target year',
                ], 400);
            }

            // Get all OPDs that have either targets or revenues
            $allOpdIds = collect();

            // Get targets for the specified year - AGGREGATED by OPD and retribusi
            $targetsData = \App\Models\RetributionTargets::select(
                'master_opd_id',
                'master_retribusi_id',
                DB::raw('SUM(target_amount) as total_target')
            )
                ->where('tahun', $targetYear)
                ->groupBy('master_opd_id', 'master_retribusi_id')
                ->get();
            $allOpdIds = $allOpdIds->merge($targetsData->pluck('master_opd_id'));

            // Get actual revenue data for the same year (optionally filtered by date range)
            $revenueData = Revenue::select('master_opd_id', 'master_retribusi_id', DB::raw('SUM(total_revenue) as total_pendapatan'))
                ->where('year', $targetYear)
                ->when($startDate, fn($q) => $q->where('date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->where('date', '<=', $endDate))
                ->groupBy('master_opd_id', 'master_retribusi_id')
                ->get();
            $allOpdIds = $allOpdIds->merge($revenueData->pluck('master_opd_id'));

            // Get unique OPD IDs
            $allOpdIds = $allOpdIds->unique()->sort()->values();

            // Create lookup maps
            $targetMap = $targetsData->mapWithKeys(fn($item) => [
                $item->master_opd_id . '_' . $item->master_retribusi_id => $item->total_target,
            ]);

            $revenueMap = $revenueData->mapWithKeys(fn($item) => [
                $item->master_opd_id . '_' . $item->master_retribusi_id => $item->total_pendapatan,
            ]);

            // Define the 3 retribution types
            $retribusiTypes = [
                1 => 'Retribusi Jasa Umum',
                2 => 'Retribusi Jasa Usaha',
                3 => 'Retribusi Perizinan Tertentu',
            ];

            // Build comparison for all OPDs
            $comparisonPerOpd = $allOpdIds->map(function ($opdId) use ($targetMap, $revenueMap, $retribusiTypes) {
                // Get OPD info
                $opd = MasterOpd::find($opdId);

                $totalTarget = 0;
                $totalActual = 0;
                $breakdown = [];

                // Loop through all 3 retribution types
                foreach ($retribusiTypes as $retribusiId => $retribusiName) {
                    $key = $opdId . '_' . $retribusiId;

                    // Get target (if exists) - now returns total_target directly
                    $targetAmount = $targetMap->get($key, 0);

                    // Get actual revenue (if exists)
                    $actualRevenue = $revenueMap->get($key, 0);

                    // Calculate achievement percentage
                    $achievementPercentage = $targetAmount > 0
                        ? round(($actualRevenue / $targetAmount) * 100, 2)
                        : 0;

                    $totalTarget += $targetAmount;
                    $totalActual += $actualRevenue;

                    $breakdown[] = [
                        'retribusi_id' => $retribusiId,
                        'nama_retribusi' => $retribusiName,
                        'target_amount' => number_format($targetAmount, 2, '.', ''),
                        'actual_revenue' => number_format($actualRevenue, 2, '.', ''),
                        'achievement_percentage' => number_format($achievementPercentage, 2, '.', ''),
                        'remaining_amount' => number_format(max(0, $targetAmount - $actualRevenue), 2, '.', ''),
                        'status' => $achievementPercentage >= 100 ? 'achieved' : ($achievementPercentage >= 75 ? 'on-track' : 'below-target'),
                    ];
                }

                // Calculate overall achievement for this OPD
                $overallAchievement = $totalTarget > 0
                    ? round(($totalActual / $totalTarget) * 100, 2)
                    : 0;

                return [
                    'opd_id' => $opdId,
                    'nama_opd' => $opd ? $opd->nama_opd : null,
                    'total_target' => number_format($totalTarget, 2, '.', ''),
                    'total_actual' => number_format($totalActual, 2, '.', ''),
                    'achievement_percentage' => number_format($overallAchievement, 2, '.', ''),
                    'remaining_amount' => number_format(max(0, $totalTarget - $totalActual), 2, '.', ''),
                    'status' => $overallAchievement >= 100 ? 'achieved' : ($overallAchievement >= 75 ? 'on-track' : 'below-target'),
                    'retribusi_breakdown' => $breakdown,
                ];
            });

            // Summary per retribusi type (across all OPDs)
            $comparisonPerRetribusi = $targetsData->groupBy('master_retribusi_id')->map(function ($retribusiTargets, $retribusiId) use ($revenueMap, $retribusiTypes) {
                $totalTarget = $retribusiTargets->sum('total_target');
                $totalActual = $retribusiTargets->sum(fn($target) => $revenueMap->get($target->master_opd_id . '_' . $target->master_retribusi_id, 0));
                $achievementPercentage = $totalTarget > 0 ? round(($totalActual / $totalTarget) * 100, 2) : 0;

                return [
                    'retribusi_id' => $retribusiId,
                    'nama_retribusi' => $retribusiTypes[$retribusiId] ?? null,
                    'total_target' => number_format($totalTarget, 2, '.', ''),
                    'total_actual' => number_format($totalActual, 2, '.', ''),
                    'achievement_percentage' => number_format($achievementPercentage, 2, '.', ''),
                    'remaining_amount' => number_format(max(0, $totalTarget - $totalActual), 2, '.', ''),
                    'status' => $achievementPercentage >= 100 ? 'achieved' : ($achievementPercentage >= 75 ? 'on-track' : 'below-target'),
                    'jumlah_opd' => $retribusiTargets->unique('master_opd_id')->count(),
                ];
            })->values();

            // Grand totals
            $grandTotalTarget = $targetsData->sum('total_target');
            $grandTotalActual = $revenueData->sum('total_pendapatan');
            $grandAchievementPercentage = $grandTotalTarget > 0
                ? round(($grandTotalActual / $grandTotalTarget) * 100, 2)
                : 0;

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved retribution targets with revenue comparison',
                'data' => [
                    'summary' => [
                        'grand_total_target' => number_format($grandTotalTarget, 2, '.', ''),
                        'grand_total_actual' => number_format($grandTotalActual, 2, '.', ''),
                        'grand_achievement_percentage' => number_format($grandAchievementPercentage, 2, '.', ''),
                        'grand_remaining_amount' => number_format(max(0, $grandTotalTarget - $grandTotalActual), 2, '.', ''),
                        'status' => $grandAchievementPercentage >= 100 ? 'achieved' : ($grandAchievementPercentage >= 75 ? 'on-track' : 'below-target'),
                    ],
                    'comparison_per_opd' => $comparisonPerOpd,
                    'comparison_per_retribusi' => $comparisonPerRetribusi,
                    'metadata' => [
                        'target_year' => $targetYear,
                        'revenue_date_range' => [
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                        ],
                        'total_target_records' => $targetsData->count(),
                        'note' => 'Targets are for the entire year. Revenue can be filtered by date range within that year.',
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving retribution targets',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Retribution Targets with Revenue Comparison for Specific OPD
     * Compares targets from retribution_targets with actual revenue from revenues table for one OPD
     * Note: Targets are always per year. Revenue can be filtered by date range within that year.
     *
     * @param  int  $opdId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRetributionTargetsByOpd(Request $request, $opdId)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (! $user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"], 401);
        }

        try {
            // Validate OPD exists
            $opd = MasterOpd::find($opdId);
            if (! $opd) {
                return response()->json([
                    'status' => 404,
                    'message' => 'OPD not found',
                ], 404);
            }

            // Year parameter for targets (single year only)
            $targetYear = $request->filled('year') ? (int) $request->year : (int) date('Y');

            // Date range for revenue filtering (optional, within the target year)
            $startDate = $request->filled('start_date') ? $request->start_date : null;
            $endDate = $request->filled('end_date') ? $request->end_date : null;

            // Validate that date range is within the same year as target year
            if ($startDate && date('Y', strtotime($startDate)) != $targetYear) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Start date must be in the same year as target year',
                ], 400);
            }
            if ($endDate && date('Y', strtotime($endDate)) != $targetYear) {
                return response()->json([
                    'status' => 400,
                    'message' => 'End date must be in the same year as target year',
                ], 400);
            }

            // Get targets for this OPD for the specified year - AGGREGATED by retribusi
            $targetsData = RetributionTargets::select(
                'master_retribusi_id',
                DB::raw('SUM(target_amount) as total_target'),
                DB::raw('MAX(tahun) as tahun')
            )
                ->where('master_opd_id', $opdId)
                ->where('tahun', $targetYear)
                ->groupBy('master_retribusi_id')
                ->get();

            // Get actual revenue data for this OPD (optionally filtered by date range)
            $revenueData = Revenue::select('master_retribusi_id', DB::raw('SUM(total_revenue) as total_pendapatan'))
                ->where('master_opd_id', $opdId)
                ->where('year', $targetYear)
                ->when($startDate, fn($q) => $q->where('date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->where('date', '<=', $endDate))
                ->groupBy('master_retribusi_id')
                ->get();

            // Create lookup maps
            $revenueMap = $revenueData->mapWithKeys(fn($item) => [$item->master_retribusi_id => $item->total_pendapatan]);
            $targetMap = $targetsData->mapWithKeys(fn($item) => [$item->master_retribusi_id => $item]);

            // Define the 3 retribution types
            $retribusiTypes = [
                1 => 'Retribusi Jasa Umum',
                2 => 'Retribusi Jasa Usaha',
                3 => 'Retribusi Perizinan Tertentu',
            ];

            // Build comparison for all 3 retribution types
            $totalTarget = 0;
            $totalActual = 0;
            $retribusiBreakdown = collect($retribusiTypes)->map(function ($retribusiName, $retribusiId) use ($targetMap, $revenueMap, &$totalTarget, &$totalActual) {
                $target = $targetMap->get($retribusiId);
                $targetAmount = $target ? $target->total_target : 0;
                $tahun = $target ? $target->tahun : null;
                $actualRevenue = $revenueMap->get($retribusiId, 0);
                $achievementPercentage = $targetAmount > 0 ? round(($actualRevenue / $targetAmount) * 100, 2) : 0;

                $totalTarget += $targetAmount;
                $totalActual += $actualRevenue;

                return [
                    'retribusi_id' => $retribusiId,
                    'nama_retribusi' => $retribusiName,
                    'target_amount' => number_format($targetAmount, 2, '.', ''),
                    'actual_revenue' => number_format($actualRevenue, 2, '.', ''),
                    'achievement_percentage' => number_format($achievementPercentage, 2, '.', ''),
                    'remaining_amount' => number_format(max(0, $targetAmount - $actualRevenue), 2, '.', ''),
                    'status' => $achievementPercentage >= 100 ? 'achieved' : ($achievementPercentage >= 75 ? 'on-track' : 'below-target'),
                    'tahun' => $tahun,
                ];
            })->values();

            // Calculate overall achievement for this OPD
            $overallAchievement = $totalTarget > 0 ? round(($totalActual / $totalTarget) * 100, 2) : 0;

            return response()->json([
                'status' => 200,
                'message' => 'Successfully retrieved retribution targets for OPD',
                'data' => [
                    'opd_info' => [
                        'id' => $opd->id,
                        'nama_opd' => $opd->nama_opd,
                    ],
                    'summary' => [
                        'total_target' => number_format($totalTarget, 2, '.', ''),
                        'total_actual' => number_format($totalActual, 2, '.', ''),
                        'achievement_percentage' => number_format($overallAchievement, 2, '.', ''),
                        'remaining_amount' => number_format(max(0, $totalTarget - $totalActual), 2, '.', ''),
                        'status' => $overallAchievement >= 100 ? 'achieved' : ($overallAchievement >= 75 ? 'on-track' : 'below-target'),
                    ],
                    'retribusi_breakdown' => $retribusiBreakdown,
                    'metadata' => [
                        'target_year' => $targetYear,
                        'revenue_date_range' => [
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                        ],
                        'note' => 'Targets are for the entire year. Revenue can be filtered by date range within that year.',
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving retribution targets for OPD',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Revenue by Lokasi for Specific OPD
     * Drill-down from OPD to see performance by location
     *
     * @param  int  $opdId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRevenueByLokasiForOpd(Request $request, $opdId)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (! $user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"], 401);
        }

        try {
            // Validate OPD exists
            $opd = MasterOpd::find($opdId);
            if (! $opd) {
                return response()->json([
                    'status' => 404,
                    'message' => 'OPD not found',
                ], 404);
            }

            $year = $request->filled('year') ? $request->year : date('Y');

            // Get all lokasi IDs that have revenue for this OPD
            $lokasiIds = Revenue::where('master_opd_id', $opdId)
                ->where('year', $year)
                ->distinct()
                ->pluck('master_lokasi_id');

            // Get revenue by lokasi
            $revenueByLokasi = Revenue::with('lokasi:id,nama_lokasi')
                ->where('master_opd_id', $opdId)
                ->where('year', $year)
                ->selectRaw('master_lokasi_id, SUM(total_revenue) as total_realisasi, SUM(total_transactions) as total_transaksi')
                ->groupBy('master_lokasi_id')
                ->get()
                ->keyBy('master_lokasi_id');

            // Get targets by lokasi (if exist)
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
                'message' => 'Successfully retrieved revenue by lokasi for OPD',
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
                'message' => 'Error retrieving revenue by lokasi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Retribusi Breakdown for Specific Lokasi
     * Terminal drill-down: Lokasi → Retribusi breakdown (Jenis, Rincian, Sub Rincian)
     * Revenue is aggregated at lokasi level per retribusi hierarchy
     *
     * @param  int  $lokasiId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRetribusiBreakdownForLokasi(Request $request, $lokasiId)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (! $user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"], 401);
        }

        try {
            $lokasi = MasterLokasiObjek::find($lokasiId);
            if (! $lokasi) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Lokasi not found',
                ], 404);
            }

            $year = $request->filled('year') ? (int) $request->year : (int) date('Y');
            $opdId = $request->filled('opd_id') ? $request->opd_id : null;

            $retribusiTypes = [
                1 => 'Retribusi Jasa Umum',
                2 => 'Retribusi Jasa Usaha',
                3 => 'Retribusi Perizinan Tertentu',
            ];

            // Revenue per retribusi type for this lokasi
            $revenueByRetribusi = Revenue::where('master_lokasi_id', $lokasiId)
                ->where('year', $year)
                ->when($opdId, fn($q) => $q->where('master_opd_id', $opdId))
                ->selectRaw('master_retribusi_id, SUM(total_revenue) as total_realisasi, SUM(total_transactions) as total_transaksi')
                ->groupBy('master_retribusi_id')
                ->get()
                ->keyBy('master_retribusi_id');

            // Target per retribusi type for this lokasi
            $targetByRetribusi = RetributionTargets::where('master_lokasi_id', $lokasiId)
                ->where('tahun', $year)
                ->when($opdId, fn($q) => $q->where('master_opd_id', $opdId))
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
                    'lokasi_info' => [
                        'id' => $lokasi->id,
                        'nama_lokasi' => $lokasi->nama_lokasi,
                    ],
                    'year' => $year,
                    'opd_id' => $opdId ? (int) $opdId : null,
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

    /**
     * Get Available Years for filtering
     * Returns years from 2025 (app release) to current year
     *
     * @return array
     */
    private function getAvailableYears()
    {
        $currentYear = date('Y');
        $startYear = 2025;

        return range($currentYear, $startYear);
    }
}
