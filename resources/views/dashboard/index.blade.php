@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="page-container">

    <!-- Welcome Header -->
    <div class="welcome-section">
        <div class="welcome-content">
            <h1 class="welcome-title">Selamat Datang, {{ Auth::user()->name }}! 👋</h1>
            <p class="welcome-subtitle">{{ now()->translatedFormat('l, d F Y') }} &mdash; Satu Aplikasi Belanja Retribusi Daerah</p>
        </div>
        <div class="welcome-period">
            <form method="GET" action="{{ route('dashboard') }}" class="date-filter-form">
                <div class="date-filter-group">
                    <label class="date-filter-label">Dari</label>
                    <input type="date" name="start_date" class="date-filter-input" value="{{ $startDate }}">
                </div>
                <div class="date-filter-group">
                    <label class="date-filter-label">Sampai</label>
                    <input type="date" name="end_date" class="date-filter-input" value="{{ $endDate }}">
                </div>
                <button type="submit" class="date-filter-btn">
                    <span>🔍</span> Filter
                </button>
                @if(request()->filled('start_date') || request()->filled('end_date'))
                <a href="{{ route('dashboard') }}" class="date-filter-reset" title="Reset ke bulan ini">✕</a>
                @endif
            </form>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card stat-primary">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <h3 class="stat-title">Total Transaksi Retribusi</h3>
                <p class="stat-value">{{ number_format($grandTotalTransaksi) }}</p>
                <p class="stat-label">Transaksi Terbayar + Angsuran Terbayar &bull; {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} &ndash; {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
            </div>
        </div>

        <div class="stat-card stat-success">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <h3 class="stat-title">Transaksi Terbayar Penuh</h3>
                <p class="stat-value">{{ number_format($transaksiTerbayar) }}</p>
                <p class="stat-label">Non-installment Status 3 &bull; {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} &ndash; {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
            </div>
        </div>

        <div class="stat-card stat-purple">
            <div class="stat-icon">🔄</div>
            <div class="stat-content">
                <h3 class="stat-title">Transaksi Angsuran Terbayar</h3>
                <p class="stat-value">{{ number_format($angsuranTerbayar) }}</p>
                <p class="stat-label">Installment dengan minimal 1 cicilan lunas &bull; {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} &ndash; {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
            </div>
        </div>

        <div class="stat-card stat-info">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <h3 class="stat-title">Total Pendapatan</h3>
                <p class="stat-value">Rp {{ number_format($grandTotalPendapatan, 0, ',', '.') }}</p>
                <p class="stat-label">Dari Transaksi Status 3 + Installment Terbayar &bull; {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} &ndash; {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
            </div>
        </div>
    </div>

    <!-- Status Breakdown (Hidden - Revenue table doesn't track status breakdown) -->
    @if($totalTransaksiPerStatus->isNotEmpty())
    <div class="status-breakdown-grid">
        @php
        $statusConfig = [
            1 => ['icon' => '⏳', 'color' => 'status-menunggu'],
            2 => ['icon' => '▶️', 'color' => 'status-aktif'],
            3 => ['icon' => '✅', 'color' => 'status-terbayar'],
            4 => ['icon' => '❌', 'color' => 'status-batal-user'],
            5 => ['icon' => '🚫', 'color' => 'status-ditolak'],
            6 => ['icon' => '⌛', 'color' => 'status-expired'],
            7 => ['icon' => '🏁', 'color' => 'status-selesai'],
            8 => ['icon' => '💳', 'color' => 'status-angsuran'],
            9 => ['icon' => '🎉', 'color' => 'status-angsuran-selesai'],
        ];
        @endphp
        @foreach($totalTransaksiPerStatus as $statusId => $statusData)
        @php $cfg = $statusConfig[$statusId] ?? ['icon' => '•', 'color' => 'status-default']; @endphp
        <div class="status-item {{ $cfg['color'] }}">
            <span class="status-item-icon">{{ $cfg['icon'] }}</span>
            <div class="status-item-body">
                <p class="status-item-count">{{ number_format($statusData['count']) }}</p>
                <p class="status-item-name">{{ $statusData['nama'] }}</p>
            </div>
        </div>
        @endforeach
    </div>
    @endif



    <!-- Retribution Type Breakdown -->
    <div class="card" style="margin-top: 2rem;">
        <div class="card-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
            <h3 class="card-title" style="color: white; display: flex; align-items: center; gap: 0.5rem;">
                <span>📑</span>
                <span>Breakdown per Jenis Retribusi</span>
            </h3>
            <p style="font-size: 0.875rem; opacity: 0.9; margin-top: 0.25rem;">Detail transaksi dan pendapatan berdasarkan jenis retribusi</p>
        </div>
        <div class="card-body">
            <div class="retribusi-breakdown-grid">
                @php
                $retribusiColors = [
                    1 => ['from' => '#3b82f6', 'to' => '#2563eb', 'icon' => '🏛️'],
                    2 => ['from' => '#8b5cf6', 'to' => '#7c3aed', 'icon' => '💼'],
                    3 => ['from' => '#ec4899', 'to' => '#db2777', 'icon' => '📜'],
                ];
                @endphp
                @foreach($retribusiBreakdown as $id => $data)
                @php $color = $retribusiColors[$id]; @endphp
                <div class="retribusi-card">
                    <div class="retribusi-card-header" style="background: linear-gradient(135deg, {{ $color['from'] }}, {{ $color['to'] }});">
                        <span class="retribusi-icon">{{ $color['icon'] }}</span>
                        <h4 class="retribusi-name">{{ $data['name'] }}</h4>
                    </div>
                    <div class="retribusi-card-body">
                        <div class="retribusi-stat">
                            <span class="retribusi-stat-icon">📊</span>
                            <div>
                                <p class="retribusi-stat-value">{{ number_format($data['transaction_count']) }}</p>
                                <p class="retribusi-stat-label">Transaksi</p>
                            </div>
                        </div>
                        <div class="retribusi-stat">
                            <span class="retribusi-stat-icon">💰</span>
                            <div>
                                <p class="retribusi-stat-value">Rp {{ number_format($data['total_revenue'], 0, ',', '.') }}</p>
                                <p class="retribusi-stat-label">Total Pendapatan</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Realisasi vs Target Section (Glassmorphism) -->
    <div class="realisasi-section">
        <div class="realisasi-header">
            <div>
                <h2 class="realisasi-title">🎯 Realisasi vs Target Retribusi</h2>
                <p class="realisasi-subtitle">Pencapaian pendapatan terhadap target tahun {{ $realisasiYear }}</p>
            </div>
            <form method="GET" action="{{ route('dashboard') }}" class="date-filter-form" style="margin: 0;">
                <input type="hidden" name="start_date" value="{{ $startDate }}">
                <input type="hidden" name="end_date" value="{{ $endDate }}">
                <input type="hidden" name="target_start_date" value="{{ $targetStartDate }}">
                <input type="hidden" name="target_end_date" value="{{ $targetEndDate }}">
                
                <div class="date-filter-group">
                    <label class="date-filter-label">Dari</label>
                    <input type="date" name="realisasi_start_date" class="date-filter-input" value="{{ $realisasiStartDate }}">
                </div>
                <div class="date-filter-group">
                    <label class="date-filter-label">Sampai</label>
                    <input type="date" name="realisasi_end_date" class="date-filter-input" value="{{ $realisasiEndDate }}">
                </div>
                <button type="submit" class="date-filter-btn">
                    <span>🔍</span> Filter
                </button>
                @if(request()->filled('realisasi_start_date') || request()->filled('realisasi_end_date'))
                <a href="{{ route('dashboard', ['start_date' => $startDate, 'end_date' => $endDate, 'target_start_date' => $targetStartDate, 'target_end_date' => $targetEndDate]) }}" class="date-filter-reset" title="Reset ke tahun ini">✕</a>
                @endif
            </form>
        </div>
        @if($realisasiYearError)
        <div style="margin: 1rem 0; padding: 0.75rem 1rem; background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444; border-radius: 0.5rem;">
            <p style="color: #ef4444; font-size: 0.875rem; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <span>⚠️</span>
                <strong>{{ $realisasiYearError }}</strong>
            </p>
        </div>
        @endif

        <!-- Total Overview Card -->
        <div class="realisasi-overview-card">
            <div class="realisasi-overview-content">
                <div class="realisasi-overview-icon">🎯</div>
                <div class="realisasi-overview-stats">
                    <div class="realisasi-stat-group">
                        <span class="realisasi-stat-label">Target Tahun {{ $realisasiYear }}</span>
                        <span class="realisasi-stat-value realisasi-stat-target">Rp {{ number_format($totalTarget, 0, ',', '.') }}</span>
                    </div>
                    <div class="realisasi-stat-divider"></div>
                    <div class="realisasi-stat-group">
                        <span class="realisasi-stat-label">Realisasi s/d Sekarang</span>
                        <span class="realisasi-stat-value realisasi-stat-realisasi">Rp {{ number_format($totalRealisasi, 0, ',', '.') }}</span>
                    </div>
                    <div class="realisasi-stat-divider"></div>
                    <div class="realisasi-stat-group">
                        <span class="realisasi-stat-label">Pencapaian</span>
                        <span class="realisasi-stat-value realisasi-stat-percentage">{{ number_format($realisasiPercentage, 1) }}%</span>
                    </div>
                </div>
            </div>
            <div class="realisasi-progress-container">
                <div class="realisasi-progress-bar realisasi-progress-{{ $realisasiPercentage >= 100 ? 'achieved' : ($realisasiPercentage >= 75 ? 'on-track' : 'below-target') }}" 
                     style="width: {{ min($realisasiPercentage, 100) }}%">
                </div>
            </div>
        </div>

        <!-- Breakdown Grid -->
        <div class="realisasi-breakdown-grid">
            <!-- By OPD -->
            <div class="realisasi-breakdown-card">
                <div class="realisasi-breakdown-header">
                    <h3 class="realisasi-breakdown-title">
                        <span class="realisasi-breakdown-icon">🏢</span>
                        Top 5 OPD
                    </h3>
                </div>
                <div class="realisasi-breakdown-body">
                    @forelse($realisasiByOpd as $item)
                    <div class="realisasi-item">
                        <div class="realisasi-item-header">
                            <span class="realisasi-item-name">{{ $item['nama'] }}</span>
                            <span class="realisasi-item-badge realisasi-badge-{{ $item['status'] }}">
                                {{ number_format($item['percentage'], 1) }}%
                            </span>
                        </div>
                        <div class="realisasi-item-stats">
                            <div class="realisasi-item-stat">
                                <span class="realisasi-item-stat-label">Target</span>
                                <span class="realisasi-item-stat-value">Rp {{ number_format($item['target'], 0, ',', '.') }}</span>
                            </div>
                            <div class="realisasi-item-stat">
                                <span class="realisasi-item-stat-label">Realisasi</span>
                                <span class="realisasi-item-stat-value">Rp {{ number_format($item['realisasi'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                        <div class="realisasi-item-progress">
                            <div class="realisasi-item-progress-bar realisasi-progress-{{ $item['status'] }}" 
                                 style="width: {{ min($item['percentage'], 100) }}%">
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="realisasi-empty">
                        <span class="realisasi-empty-icon">📊</span>
                        <p class="realisasi-empty-text">Belum ada data</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- By Retribusi Type -->
            <div class="realisasi-breakdown-card">
                <div class="realisasi-breakdown-header">
                    <h3 class="realisasi-breakdown-title">
                        <span class="realisasi-breakdown-icon">📋</span>
                        Per Jenis Retribusi
                    </h3>
                </div>
                <div class="realisasi-breakdown-body">
                    @forelse($realisasiByRetribusi as $item)
                    <div class="realisasi-item">
                        <div class="realisasi-item-header">
                            <span class="realisasi-item-name">{{ $item['nama'] }}</span>
                            <span class="realisasi-item-badge realisasi-badge-{{ $item['status'] }}">
                                {{ number_format($item['percentage'], 1) }}%
                            </span>
                        </div>
                        <div class="realisasi-item-stats">
                            <div class="realisasi-item-stat">
                                <span class="realisasi-item-stat-label">Target</span>
                                <span class="realisasi-item-stat-value">Rp {{ number_format($item['target'], 0, ',', '.') }}</span>
                            </div>
                            <div class="realisasi-item-stat">
                                <span class="realisasi-item-stat-label">Realisasi</span>
                                <span class="realisasi-item-stat-value">Rp {{ number_format($item['realisasi'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                        <div class="realisasi-item-progress">
                            <div class="realisasi-item-progress-bar realisasi-progress-{{ $item['status'] }}" 
                                 style="width: {{ min($item['percentage'], 100) }}%">
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="realisasi-empty">
                        <span class="realisasi-empty-icon">📊</span>
                        <p class="realisasi-empty-text">Belum ada data</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

<!-- OPD Performance vs Targets -->
    <div class="card" style="margin-top: 2rem;">
        <div class="card-header" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color: white;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
                <div>
                    <h3 class="card-title" style="color: white; display: flex; align-items: center; gap: 0.5rem;">
                        <span>🎯</span>
                        <span>Performa OPD vs Target Retribusi</span>
                    </h3>
                    <p style="font-size: 0.875rem; opacity: 0.9; margin-top: 0.25rem;">Pencapaian pendapatan OPD terhadap target tahun {{ $targetYear }}</p>
                </div>
                <form method="GET" action="{{ route('dashboard') }}" class="date-filter-form" style="margin: 0;">
                    <!-- Preserve existing filters -->
                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                    <input type="hidden" name="realisasi_year" value="{{ $realisasiYear }}">
                    
                    <div class="date-filter-group">
                        <label class="date-filter-label">Dari</label>
                        <input type="date" name="target_start_date" class="date-filter-input" value="{{ $targetStartDate }}">
                    </div>
                    <div class="date-filter-group">
                        <label class="date-filter-label">Sampai</label>
                        <input type="date" name="target_end_date" class="date-filter-input" value="{{ $targetEndDate }}">
                    </div>
                    <button type="submit" class="date-filter-btn">
                        <span>🔍</span> Filter
                    </button>
                    @if(request()->filled('target_start_date') || request()->filled('target_end_date'))
                    <a href="{{ route('dashboard', ['start_date' => $startDate, 'end_date' => $endDate, 'realisasi_year' => $realisasiYear]) }}" class="date-filter-reset" title="Reset ke tahun ini">✕</a>
                    @endif
                </form>
            </div>
            @if($targetYearError)
            <div style="margin-top: 1rem; padding: 0.75rem 1rem; background: rgba(239, 68, 68, 0.2); border-left: 4px solid #ef4444; border-radius: 0.5rem;">
                <p style="color: white; font-size: 0.875rem; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <span>⚠️</span>
                    <strong>{{ $targetYearError }}</strong>
                </p>
            </div>
            @endif
        </div>
        <div class="card-body">
            @if($opdPerformance->isEmpty())
                <div class="empty-state">
                    <div class="empty-icon">📊</div>
                    <p>Belum ada data target untuk tahun {{ $targetYear }}</p>
                </div>
            @else
                <div class="opd-performance-grid">
                    @foreach($opdPerformance as $performance)
                    <a href="{{ route('dashboard.realisasi.lokasi-by-opd', ['opdId' => $performance['opd']->id, 'tahun' => $targetYear]) }}" class="opd-performance-card opd-performance-card-link">
                        <div class="opd-performance-header">
                            <h4 class="opd-performance-name">{{ $performance['opd']->nama_opd }}</h4>
                            <span class="opd-performance-badge opd-badge-{{ $performance['status'] }}">
                                @if($performance['status'] == 'achieved')
                                    ✅ Tercapai
                                @elseif($performance['status'] == 'on-track')
                                    ⚡ On Track
                                @else
                                    ⚠️ Below Target
                                @endif
                            </span>
                        </div>
                        
                        <div class="opd-performance-stats">
                            <div class="opd-stat-item">
                                <span class="opd-stat-label">Target</span>
                                <span class="opd-stat-value">Rp {{ number_format($performance['total_target'], 0, ',', '.') }}</span>
                            </div>
                            <div class="opd-stat-item">
                                <span class="opd-stat-label">Realisasi</span>
                                <span class="opd-stat-value">Rp {{ number_format($performance['actual_revenue'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                        
                        <div class="opd-performance-progress">
                            <div class="progress-bar-container">
                                <div class="progress-bar progress-{{ $performance['status'] }}" 
                                     style="width: {{ min($performance['percentage'], 100) }}%">
                                </div>
                            </div>
                            <span class="progress-percentage">{{ number_format($performance['percentage'], 1) }}%</span>
                        </div>
                        
                        <div class="opd-card-footer">
                            <span class="opd-card-link-text">Lihat Detail per Lokasi →</span>
                        </div>
                    </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    <!-- Master Data Info Row -->
    <div class="master-info-grid">
        <a href="{{ route('dashboard.opd.list') }}" class="master-info-card master-info-card-link">
            <span class="master-info-icon">🏢</span>
            <div>
                <p class="master-info-value">{{ $totalOpd }}</p>
                <p class="master-info-label">OPD</p>
            </div>
        </a>
        <a href="{{ route('dashboard.retribusi.list') }}" class="master-info-card master-info-card-link">
            <span class="master-info-icon">📋</span>
            <div>
                <p class="master-info-value">{{ $totalRetribusi }}</p>
                <p class="master-info-label">Retribusi</p>
            </div>
        </a>
        <a href="{{ route('dashboard.lokasi.list') }}" class="master-info-card master-info-card-link">
            <span class="master-info-icon">📍</span>
            <div>
                <p class="master-info-value">{{ $totalLokasi }}</p>
                <p class="master-info-label">Lokasi</p>
            </div>
        </a>
        <a href="{{ route('dashboard.objek.list') }}" class="master-info-card master-info-card-link">
            <span class="master-info-icon">🗂️</span>
            <div>
                <p class="master-info-value">{{ $totalObjek }}</p>
                <p class="master-info-label">Objek</p>
            </div>
        </a>
    </div>


    

    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Aktivitas Pendapatan Terbaru</h3>
            <span class="card-subtitle">10 entri pendapatan terakhir (dari tabel Revenue)</span>
        </div>
        <div class="card-body">
            @if($recentTransaksi->isEmpty())
                <div class="empty-state">
                    <div class="empty-icon">📝</div>
                    <p>Belum ada aktivitas terbaru</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>OPD</th>
                                <th>Jenis Retribusi</th>
                                <th>Total Objek</th>
                                <th>Total Pendapatan</th>
                                <th>Tipe</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentTransaksi as $revenue)
                            <tr>
                                <td>
                                    <span class="ref-no">{{ \Carbon\Carbon::parse($revenue->date)->format('d M Y') }}</span>
                                </td>
                                <td>{{ $revenue->opd->nama_opd ?? '-' }}</td>
                                <td>{{ $revenue->retribusi->nama_retribusi ?? '-' }}</td>
                                <td>
                                    <span style="font-weight: 600; color: var(--primary-color);">{{ number_format($revenue->total_transactions) }}</span>
                                </td>
                                <td>Rp {{ number_format($revenue->total_revenue, 0, ',', '.') }}</td>
                                <td>
                                    @if($revenue->is_installment)
                                        <span class="status-badge badge-purple">Angsuran</span>
                                    @else
                                        <span class="status-badge badge-success">Regular</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
/* Dashboard Specific Styles */
.welcome-section {
    margin-bottom: var(--spacing-xl);
    padding: var(--spacing-xl);
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(37, 99, 235, 0.15) 100%);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(37, 99, 235, 0.2);
    border-radius: var(--radius-lg);
    color: var(--gray-800);
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-md);
}

.welcome-section::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(59, 130, 246, 0.08) 0%, transparent 70%);
    animation: float 20s ease-in-out infinite;
}

.welcome-content {
    position: relative;
    z-index: 1;
}

.welcome-title {
    font-size: var(--font-size-3xl);
    font-weight: 700;
    margin-bottom: var(--spacing-sm);
    color: var(--primary-color);
}

.welcome-subtitle {
    font-size: var(--font-size-lg);
    color: var(--gray-600);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
}

.stat-card {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-md);
    display: flex;
    gap: var(--spacing-md);
    border-left: 4px solid;
    transition: all var(--transition-fast);
    border: 1px solid rgba(37, 99, 235, 0.1);
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-xl);
}

.stat-card.stat-primary { 
    border-left-color: var(--primary-color);
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(255, 255, 255, 0.9) 100%);
}
.stat-card.stat-success { 
    border-left-color: var(--success-color);
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(255, 255, 255, 0.9) 100%);
}
.stat-card.stat-warning { 
    border-left-color: var(--warning-color);
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.08) 0%, rgba(255, 255, 255, 0.9) 100%);
}
.stat-card.stat-info { 
    border-left-color: var(--info-color);
    background: linear-gradient(135deg, rgba(6, 182, 212, 0.08) 0%, rgba(255, 255, 255, 0.9) 100%);
}

.stat-icon {
    font-size: 3rem;
    opacity: 0.8;
}

.stat-content {
    flex: 1;
}

.stat-title {
    font-size: var(--font-size-sm);
    color: var(--gray-700);
    margin-bottom: var(--spacing-xs);
}

.stat-value {
    font-size: var(--font-size-2xl);
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: var(--spacing-xs);
}

.stat-label {
    font-size: var(--font-size-xs);
    color: var(--gray-600);
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-md);
}

.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--spacing-sm);
    padding: var(--spacing-lg);
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 2px solid rgba(37, 99, 235, 0.15);
    border-radius: var(--radius-lg);
    transition: all var(--transition-fast);
    text-decoration: none;
}

.quick-action-btn:hover {
    background: var(--primary-gradient);
    border-color: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.quick-action-btn:hover .action-icon,
.quick-action-btn:hover .action-text {
    color: var(--white);
}

.action-icon {
    font-size: var(--font-size-3xl);
    transition: transform var(--transition-fast);
}

.quick-action-btn:hover .action-icon {
    transform: scale(1.1);
}

.action-text {
    font-weight: 500;
    color: var(--gray-700);
    transition: color var(--transition-fast);
}

/* Date filter form */
.date-filter-form {
    display: flex;
    align-items: flex-end;
    gap: var(--spacing-sm);
    flex-wrap: wrap;
    position: relative;
    z-index: 10;
}

.date-filter-group {
    display: flex;
    flex-direction: column;
    gap: 3px;
    position: relative;
    z-index: 10;
}

.date-filter-label {
    font-size: 10px;
    font-weight: 600;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.date-filter-input {
    border: 1px solid rgba(37, 99, 235, 0.25);
    border-radius: var(--radius-md);
    padding: 6px 10px;
    font-size: var(--font-size-sm);
    color: var(--gray-700);
    background: white;
    outline: none;
    transition: border-color var(--transition-fast);
    height: 34px;
    cursor: pointer;
    pointer-events: auto;
    position: relative;
    z-index: 10;
}

.date-filter-input:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.date-filter-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: var(--radius-md);
    padding: 6px 14px;
    font-size: var(--font-size-sm);
    font-weight: 600;
    cursor: pointer;
    height: 34px;
    transition: background var(--transition-fast), transform var(--transition-fast);
}

.date-filter-btn:hover {
    background: var(--primary-dark, #1d4ed8);
    transform: translateY(-1px);
}

.date-filter-reset {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: var(--radius-md);
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    border: 1px solid rgba(239, 68, 68, 0.2);
    transition: all var(--transition-fast);
}

.date-filter-reset:hover {
    background: #ef4444;
    color: white;
}

/* Stat card purple */
.stat-card.stat-purple {
    border-left-color: #7c3aed;
    background: linear-gradient(135deg, rgba(124, 58, 237, 0.08) 0%, rgba(255, 255, 255, 0.9) 100%);
}

/* Status Breakdown Grid */
.status-breakdown-grid {
    display: grid;
    grid-template-columns: repeat(9, 1fr);
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-xl);
}

.status-item {
    background: white;
    border-radius: var(--radius-lg);
    padding: var(--spacing-md);
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    box-shadow: var(--shadow-sm);
    border-left: 3px solid transparent;
    transition: all var(--transition-fast);
}

.status-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.status-item-icon {
    font-size: 1.4rem;
    flex-shrink: 0;
    line-height: 1;
}

.status-item-body {
    min-width: 0;
}

.status-item-count {
    font-size: var(--font-size-lg);
    font-weight: 700;
    line-height: 1;
    margin-bottom: 2px;
}

.status-item-name {
    font-size: 10px;
    color: var(--gray-500);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}

/* Status item color variants */
.status-menunggu      { border-left-color: #f59e0b; }
.status-menunggu .status-item-count      { color: #92400e; }
.status-aktif         { border-left-color: #3b82f6; }
.status-aktif .status-item-count         { color: #1e3a8a; }
.status-terbayar      { border-left-color: #10b981; }
.status-terbayar .status-item-count      { color: #065f46; }
.status-batal-user    { border-left-color: #ef4444; }
.status-batal-user .status-item-count    { color: #7f1d1d; }
.status-ditolak       { border-left-color: #f97316; }
.status-ditolak .status-item-count       { color: #7c2d12; }
.status-expired       { border-left-color: #6b7280; }
.status-expired .status-item-count       { color: #374151; }
.status-selesai       { border-left-color: #0d9488; }
.status-selesai .status-item-count       { color: #134e4a; }
.status-angsuran      { border-left-color: #7c3aed; }
.status-angsuran .status-item-count      { color: #4c1d95; }
.status-angsuran-selesai { border-left-color: #059669; }
.status-angsuran-selesai .status-item-count { color: #064e3b; }
.status-default       { border-left-color: var(--gray-300); }

/* New badge colors */
.badge-info   { background: rgba(59, 130, 246, 0.12); color: #1e40af; border: 1px solid rgba(59, 130, 246, 0.25); }
.badge-danger { background: rgba(239, 68, 68, 0.12); color: #7f1d1d; border: 1px solid rgba(239, 68, 68, 0.25); }
.badge-dark   { background: rgba(107, 114, 128, 0.15); color: #1f2937; border: 1px solid rgba(107, 114, 128, 0.3); }
.badge-teal   { background: rgba(13, 148, 136, 0.12); color: #134e4a; border: 1px solid rgba(13, 148, 136, 0.25); }
.badge-purple { background: rgba(124, 58, 237, 0.12); color: #4c1d95; border: 1px solid rgba(124, 58, 237, 0.25); }

/* Welcome period badge */
.welcome-section {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: var(--spacing-md);
}

.period-badge {
    display: inline-block;
    background: rgba(37, 99, 235, 0.12);
    color: var(--primary-color);
    border: 1px solid rgba(37, 99, 235, 0.2);
    border-radius: 20px;
    padding: 6px 16px;
    font-size: var(--font-size-sm);
    font-weight: 600;
    white-space: nowrap;
    position: relative;
    z-index: 1;
}

/* Master Info Grid */
.master-info-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-xl);
}

.master-info-card {
    background: white;
    border: 1px solid rgba(37, 99, 235, 0.1);
    border-radius: var(--radius-lg);
    padding: var(--spacing-md) var(--spacing-lg);
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    box-shadow: var(--shadow-sm);
    transition: all var(--transition-fast);
    text-decoration: none;
    color: inherit;
}

.master-info-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    border-color: rgba(37, 99, 235, 0.25);
    cursor: pointer;
}

.master-info-card-link {
    display: flex;
}

.master-info-icon {
    font-size: 1.8rem;
    flex-shrink: 0;
}

.master-info-value {
    font-size: var(--font-size-xl);
    font-weight: 700;
    color: var(--primary-color);
    line-height: 1;
    margin-bottom: 2px;
}

.master-info-label {
    font-size: var(--font-size-xs);
    color: var(--gray-500);
}

/* Card subtitle */
.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: var(--spacing-sm);
}

.card-subtitle {
    font-size: var(--font-size-xs);
    color: var(--gray-500);
    font-weight: 400;
}

/* Revenue Contribution Stats */
.revenue-contribution-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--spacing-lg);
}

.revenue-stat-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-lg);
    background: rgba(255, 255, 255, 0.6);
    border-radius: var(--radius-lg);
    border: 2px solid rgba(0, 0, 0, 0.05);
    transition: all var(--transition-fast);
}

.revenue-stat-item:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(0, 0, 0, 0.1);
}

.revenue-stat-icon {
    width: 70px;
    height: 70px;
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: var(--shadow-md);
}

.revenue-stat-content {
    flex: 1;
}

.revenue-stat-value {
    font-size: var(--font-size-2xl);
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 4px;
    line-height: 1;
}

.revenue-stat-label {
    font-size: var(--font-size-sm);
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 2px;
}

.revenue-stat-sublabel {
    font-size: var(--font-size-xs);
    color: var(--gray-500);
}

/* Retribution Type Breakdown */
.retribusi-breakdown-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: var(--spacing-lg);
}

.retribusi-card {
    background: white;
    border-radius: var(--radius-lg);
    overflow: hidden;
    border: 2px solid rgba(0, 0, 0, 0.05);
    transition: all var(--transition-fast);
}

.retribusi-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-xl);
    border-color: rgba(0, 0, 0, 0.1);
}

.retribusi-card-header {
    padding: var(--spacing-lg);
    color: white;
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.retribusi-icon {
    font-size: 2rem;
}

.retribusi-name {
    font-size: var(--font-size-lg);
    font-weight: 700;
    margin: 0;
    color: white;
}

.retribusi-card-body {
    padding: var(--spacing-lg);
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
}

.retribusi-stat {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    padding: var(--spacing-md);
    background: rgba(0, 0, 0, 0.02);
    border-radius: var(--radius-md);
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.retribusi-stat-icon {
    font-size: 1.5rem;
}

.retribusi-stat-value {
    font-size: var(--font-size-lg);
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 2px;
    line-height: 1;
}

.retribusi-stat-label {
    font-size: var(--font-size-sm);
    color: var(--gray-600);
    margin: 0;
}

/* OPD Performance */
.year-filter-select {
    padding: 0.5rem 1rem;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: var(--radius-md);
    background: rgba(255, 255, 255, 0.2);
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all var(--transition-fast);
}

.year-filter-select:hover {
    background: rgba(255, 255, 255, 0.3);
}

.year-filter-select option {
    color: var(--gray-900);
}

.opd-performance-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: var(--spacing-lg);
}

.opd-performance-card {
    background: linear-gradient(135deg, rgba(249, 250, 251, 1) 0%, rgba(255, 255, 255, 1) 100%);
    border: 2px solid rgba(209, 213, 219, 0.6);
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    transition: all var(--transition-fast);
    display: block;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.opd-performance-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-xl);
    border-color: var(--primary-color);
    cursor: pointer;
    background: linear-gradient(135deg, rgba(239, 246, 255, 1) 0%, rgba(255, 255, 255, 1) 100%);
}

.opd-performance-card-link {
    position: relative;
}

.opd-card-footer {
    margin-top: var(--spacing-md);
    padding-top: var(--spacing-md);
    border-top: 1px solid var(--gray-200);
    text-align: right;
}

.opd-card-link-text {
    color: var(--primary-color);
    font-weight: 600;
    font-size: var(--font-size-sm);
    transition: all var(--transition-fast);
}

.opd-performance-card:hover .opd-card-link-text {
    text-decoration: underline;
    color: var(--primary-dark, #1d4ed8);
}

.opd-performance-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--spacing-md);
    gap: var(--spacing-sm);
}

.opd-performance-name {
    font-size: var(--font-size-lg);
    font-weight: 700;
    color: var(--gray-900);
    margin: 0;
    flex: 1;
}

.opd-performance-badge {
    padding: 0.375rem 0.75rem;
    border-radius: var(--radius-full);
    font-size: var(--font-size-xs);
    font-weight: 700;
    white-space: nowrap;
}

.opd-badge-achieved {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.opd-badge-on-track {
    background: rgba(59, 130, 246, 0.1);
    color: #2563eb;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.opd-badge-below-target {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.opd-performance-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-md);
}

.opd-stat-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding: var(--spacing-md);
    background: var(--gray-50);
    border-radius: var(--radius-md);
}

.opd-stat-label {
    font-size: var(--font-size-xs);
    color: var(--gray-600);
    font-weight: 600;
    text-transform: uppercase;
}

.opd-stat-value {
    font-size: var(--font-size-base);
    font-weight: 700;
    color: var(--gray-900);
}

.opd-performance-progress {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
}

.progress-bar-container {
    flex: 1;
    height: 1.5rem;
    background: var(--gray-200);
    border-radius: var(--radius-full);
    overflow: hidden;
    position: relative;
}

.progress-bar {
    height: 100%;
    border-radius: var(--radius-full);
    transition: width 0.6s ease;
    position: relative;
}

.progress-bar::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.progress-achieved {
    background: linear-gradient(90deg, #10b981, #059669);
}

.progress-on-track {
    background: linear-gradient(90deg, #3b82f6, #2563eb);
}

.progress-below-target {
    background: linear-gradient(90deg, #ef4444, #dc2626);
}

.progress-percentage {
    font-size: var(--font-size-lg);
    font-weight: 700;
    color: var(--gray-900);
    min-width: 4rem;
    text-align: right;
}

/* Quick actions: 4 per row on desktop */
.quick-actions {
    grid-template-columns: repeat(4, 1fr);
}

/* Status badges */
.status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: var(--font-size-xs);
    font-weight: 600;
    white-space: nowrap;
}

.badge-success {
    background: rgba(16, 185, 129, 0.12);
    color: #065f46;
    border: 1px solid rgba(16, 185, 129, 0.25);
}

.badge-warning {
    background: rgba(245, 158, 11, 0.12);
    color: #92400e;
    border: 1px solid rgba(245, 158, 11, 0.25);
}

.badge-secondary {
    background: rgba(107, 114, 128, 0.1);
    color: var(--gray-600);
    border: 1px solid rgba(107, 114, 128, 0.2);
}

/* Reference number */
.ref-no {
    font-family: monospace;
    font-size: var(--font-size-xs);
    background: rgba(37, 99, 235, 0.07);
    color: var(--primary-color);
    padding: 2px 8px;
    border-radius: 6px;
    letter-spacing: 0.03em;
}

@media (max-width: 1400px) {
    .status-breakdown-grid {
        grid-template-columns: repeat(5, 1fr);
    }
}

@media (max-width: 1024px) {
    .master-info-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .quick-actions {
        grid-template-columns: repeat(3, 1fr);
    }
    .status-breakdown-grid {
        grid-template-columns: repeat(5, 1fr);
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
    .master-info-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .quick-actions {
        grid-template-columns: repeat(2, 1fr);
    }
    .welcome-section {
        flex-direction: column;
        align-items: flex-start;
    }
    .status-breakdown-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    .date-filter-form {
        width: 100%;
    }
    .date-filter-group {
        flex: 1;
        min-width: 120px;
    }
    .date-filter-input {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    .status-breakdown-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Realisasi vs Target Section - Glassmorphism */
.realisasi-section {
    margin: 2rem 0;
    padding: 2rem;
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(59, 130, 246, 0.1) 100%);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(139, 92, 246, 0.2);
    border-radius: var(--radius-lg);
    box-shadow: 0 8px 32px rgba(139, 92, 246, 0.15);
    position: relative;
    overflow: hidden;
}

.realisasi-section::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(139, 92, 246, 0.1) 0%, transparent 70%);
    animation: float 15s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translate(0, 0) rotate(0deg); }
    33% { transform: translate(30px, -30px) rotate(120deg); }
    66% { transform: translate(-20px, 20px) rotate(240deg); }
}

.realisasi-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    position: relative;
    z-index: 1;
    flex-wrap: wrap;
    gap: 1rem;
}

.realisasi-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.realisasi-subtitle {
    font-size: 0.875rem;
    color: var(--gray-600);
    margin: 0.25rem 0 0 0;
}

.realisasi-year-filter {
    position: relative;
    z-index: 10;
}

.realisasi-year-select {
    padding: 0.5rem 1rem;
    border: 2px solid rgba(139, 92, 246, 0.3);
    border-radius: var(--radius-md);
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(10px);
    color: var(--gray-900);
    font-weight: 600;
    cursor: pointer;
    transition: all var(--transition-fast);
    box-shadow: 0 2px 8px rgba(139, 92, 246, 0.1);
}

.realisasi-year-select:hover {
    background: rgba(255, 255, 255, 0.95);
    border-color: rgba(139, 92, 246, 0.5);
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.2);
}

.realisasi-overview-card {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 2px solid rgba(255, 255, 255, 0.5);
    border-radius: var(--radius-lg);
    padding: 2rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
    position: relative;
    z-index: 1;
    transition: all var(--transition-fast);
}

.realisasi-overview-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(31, 38, 135, 0.25);
}

.realisasi-overview-content {
    display: flex;
    align-items: center;
    gap: 2rem;
    margin-bottom: 1.5rem;
}

.realisasi-overview-icon {
    font-size: 4rem;
    flex-shrink: 0;
    filter: drop-shadow(0 4px 8px rgba(139, 92, 246, 0.3));
}

.realisasi-overview-stats {
    display: flex;
    gap: 2rem;
    flex: 1;
    flex-wrap: wrap;
}

.realisasi-stat-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    flex: 1;
    min-width: 200px;
}

.realisasi-stat-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--gray-600);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.realisasi-stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
}

.realisasi-stat-target {
    color: #f59e0b;
}

.realisasi-stat-realisasi {
    color: #3b82f6;
}

.realisasi-stat-percentage {
    color: #8b5cf6;
}

.realisasi-stat-divider {
    width: 2px;
    background: linear-gradient(to bottom, transparent, rgba(139, 92, 246, 0.3), transparent);
}

.realisasi-progress-container {
    height: 2rem;
    background: rgba(139, 92, 246, 0.1);
    border-radius: var(--radius-full);
    overflow: hidden;
    position: relative;
    border: 1px solid rgba(139, 92, 246, 0.2);
}

.realisasi-progress-bar {
    height: 100%;
    border-radius: var(--radius-full);
    transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.realisasi-progress-bar::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    animation: shimmer 2s infinite;
}

.realisasi-progress-achieved {
    background: linear-gradient(90deg, #10b981, #059669);
    box-shadow: 0 0 20px rgba(16, 185, 129, 0.5);
}

.realisasi-progress-on-track {
    background: linear-gradient(90deg, #3b82f6, #2563eb);
    box-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
}

.realisasi-progress-below-target {
    background: linear-gradient(90deg, #ef4444, #dc2626);
    box-shadow: 0 0 20px rgba(239, 68, 68, 0.5);
}

.realisasi-breakdown-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
    position: relative;
    z-index: 1;
}

.realisasi-breakdown-card {
    background: rgba(255, 255, 255, 0.6);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.4);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(31, 38, 135, 0.1);
    transition: all var(--transition-fast);
}

.realisasi-breakdown-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 32px rgba(31, 38, 135, 0.2);
    border-color: rgba(139, 92, 246, 0.4);
}

.realisasi-breakdown-header {
    padding: 1.25rem 1.5rem;
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(59, 130, 246, 0.15) 100%);
    border-bottom: 1px solid rgba(139, 92, 246, 0.2);
}

.realisasi-breakdown-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.realisasi-breakdown-icon {
    font-size: 1.5rem;
}

.realisasi-breakdown-body {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.realisasi-item {
    background: rgba(255, 255, 255, 0.5);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(139, 92, 246, 0.15);
    border-radius: var(--radius-md);
    padding: 1rem;
    transition: all var(--transition-fast);
}

.realisasi-item:hover {
    background: rgba(255, 255, 255, 0.7);
    border-color: rgba(139, 92, 246, 0.3);
    transform: translateX(4px);
}

.realisasi-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    gap: 0.5rem;
}

.realisasi-item-name {
    font-weight: 600;
    color: var(--gray-900);
    font-size: 0.9375rem;
}

.realisasi-item-badge {
    padding: 0.25rem 0.75rem;
    border-radius: var(--radius-full);
    font-size: 0.8125rem;
    font-weight: 700;
    white-space: nowrap;
}

.realisasi-badge-achieved {
    background: rgba(16, 185, 129, 0.15);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.realisasi-badge-on-track {
    background: rgba(59, 130, 246, 0.15);
    color: #2563eb;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.realisasi-badge-below-target {
    background: rgba(239, 68, 68, 0.15);
    color: #dc2626;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.realisasi-item-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}

.realisasi-item-stat {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.realisasi-item-stat-label {
    font-size: 0.75rem;
    color: var(--gray-600);
    font-weight: 600;
    text-transform: uppercase;
}

.realisasi-item-stat-value {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--gray-900);
}

.realisasi-item-progress {
    height: 0.5rem;
    background: rgba(139, 92, 246, 0.1);
    border-radius: var(--radius-full);
    overflow: hidden;
}

.realisasi-item-progress-bar {
    height: 100%;
    border-radius: var(--radius-full);
    transition: width 0.6s ease;
}

.realisasi-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    gap: 0.5rem;
}

.realisasi-empty-icon {
    font-size: 3rem;
    opacity: 0.3;
}

.realisasi-empty-text {
    color: var(--gray-500);
    font-size: 0.875rem;
    margin: 0;
}

@media (max-width: 1024px) {
    .realisasi-breakdown-grid {
        grid-template-columns: 1fr;
    }
    .realisasi-overview-stats {
        flex-direction: column;
        gap: 1rem;
    }
    .realisasi-stat-divider {
        display: none;
    }
}

@media (max-width: 768px) {
    .realisasi-section {
        padding: 1.5rem;
    }
    .realisasi-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .realisasi-overview-content {
        flex-direction: column;
        gap: 1rem;
    }
    .realisasi-overview-icon {
        font-size: 3rem;
    }
    .realisasi-stat-value {
        font-size: 1.25rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.__retainScrollInitialized) {
        return;
    }

    window.__retainScrollInitialized = true;

    const storageKey = 'scroll-retain:' + window.location.pathname;
    const forms = document.querySelectorAll('form.year-filter-form');

    forms.forEach(function (form) {
        form.addEventListener('submit', function () {
            try {
                sessionStorage.setItem(storageKey, window.scrollY.toString());
            } catch (err) {
                // ignore storage errors (e.g., private mode)
            }
        }, { capture: true });
    });

    let storedScroll = null;
    try {
        storedScroll = sessionStorage.getItem(storageKey);
    } catch (err) {
        storedScroll = null;
    }

    if (storedScroll !== null) {
        const top = parseInt(storedScroll, 10);
        if (!Number.isNaN(top)) {
            window.scrollTo({ top: top, behavior: 'auto' });
        }

        try {
            sessionStorage.removeItem(storageKey);
        } catch (err) {
            // ignore
        }
    }
});
</script>
@endpush
