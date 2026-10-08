@extends('layouts.app')

@section('title', 'Realisasi per Lokasi - ' . $opd->nama_opd)

@section('content')
<div class="page-container">
    
    <!-- Breadcrumb & Header -->
    <div class="page-header">
        <div class="breadcrumb">
            <a href="{{ route('dashboard') }}" class="breadcrumb-item">📊 Dashboard</a>
            <span class="breadcrumb-separator">›</span>
            <span class="breadcrumb-item active">Realisasi per Lokasi - {{ $opd->nama_opd }}</span>
        </div>
        <div class="page-header-actions">
            <form method="GET" action="{{ route('dashboard.realisasi.lokasi-by-opd', $opd->id) }}" class="year-filter-form">
                <select name="tahun" class="year-filter-select" onchange="this.form.submit()">
                    @foreach($availableYears as $year)
                    <option value="{{ $year }}" {{ $tahun == $year ? 'selected' : '' }}>
                        Tahun {{ $year }}
                    </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <h1 class="page-title">🏢 {{ $opd->nama_opd }}</h1>
    <p class="page-subtitle">Realisasi vs Target per Lokasi - Tahun {{ $tahun }}</p>

    <!-- Total Overview Card -->
    <div class="realisasi-overview-card">
        <div class="realisasi-overview-content">
            <div class="realisasi-overview-icon">🎯</div>
            <div class="realisasi-overview-stats">
                <div class="realisasi-stat-group">
                    <span class="realisasi-stat-label">Total Target</span>
                    <span class="realisasi-stat-value realisasi-stat-target">Rp {{ number_format($totalTarget, 0, ',', '.') }}</span>
                </div>
                <div class="realisasi-stat-divider"></div>
                <div class="realisasi-stat-group">
                    <span class="realisasi-stat-label">Total Realisasi</span>
                    <span class="realisasi-stat-value realisasi-stat-realisasi">Rp {{ number_format($totalRealisasi, 0, ',', '.') }}</span>
                </div>
                <div class="realisasi-stat-divider"></div>
                <div class="realisasi-stat-group">
                    <span class="realisasi-stat-label">Pencapaian</span>
                    <span class="realisasi-stat-value realisasi-stat-percentage">{{ number_format($totalPercentage, 1) }}%</span>
                </div>
            </div>
        </div>
        <div class="realisasi-progress-container">
            <div class="realisasi-progress-bar realisasi-progress-{{ $totalPercentage >= 100 ? 'achieved' : ($totalPercentage >= 75 ? 'on-track' : 'below-target') }}" 
                 style="width: {{ min($totalPercentage, 100) }}%">
            </div>
        </div>
    </div>

    <!-- Retribution Breakdown Section -->
    @if(!empty($retribusiBreakdown))
    <div class="retribusi-breakdown-section">
        <h2 class="section-title">📋 Breakdown Retribusi</h2>
        <p class="section-subtitle">Realisasi vs Target per Jenis, Rincian, dan Sub Rincian Retribusi</p>
        
        @foreach($retribusiBreakdown as $retribusiId => $retribusiData)
        <div class="breakdown-card">
            <div class="breakdown-header" onclick="toggleBreakdown('retribusi_{{ $retribusiId }}')">
                <div class="breakdown-header-left">
                    <span class="toggle-icon" id="icon_retribusi_{{ $retribusiId }}">▼</span>
                    <h3 class="breakdown-title">{{ $retribusiData['retribusi']->nama_retribusi ?? 'Retribusi #' . $retribusiId }}</h3>
                </div>
                <div class="breakdown-header-right">
                    <div class="breakdown-stats-inline">
                        <span class="stat-inline">Target: <strong>Rp {{ number_format($retribusiData['target'], 0, ',', '.') }}</strong></span>
                        <span class="stat-inline">Realisasi: <strong>Rp {{ number_format($retribusiData['realisasi'], 0, ',', '.') }}</strong></span>
                        <span class="stat-badge-inline status-{{ $retribusiData['status'] }}">{{ number_format($retribusiData['percentage'], 1) }}%</span>
                    </div>
                </div>
            </div>
            
            <div class="breakdown-content" id="content_retribusi_{{ $retribusiId }}">
                @foreach($retribusiData['rincian'] as $rincianId => $rincianData)
                <div class="rincian-breakdown">
                    <div class="rincian-breakdown-header" onclick="toggleBreakdown('rincian_{{ $retribusiId }}_{{ $rincianId }}')">
                        <div class="rincian-breakdown-left">
                            <span class="toggle-icon-sm" id="icon_rincian_{{ $retribusiId }}_{{ $rincianId }}">▶</span>
                            <h4 class="rincian-breakdown-title">{{ $rincianData['rincian']->rincian_retribusi ?? 'Rincian #' . $rincianId }}</h4>
                        </div>
                        <div class="rincian-breakdown-right">
                            <span class="stat-sm">Target: Rp {{ number_format($rincianData['target'], 0, ',', '.') }}</span>
                            <span class="stat-sm">Realisasi: Rp {{ number_format($rincianData['realisasi'], 0, ',', '.') }}</span>
                            <span class="stat-badge-sm status-{{ $rincianData['status'] }}">{{ number_format($rincianData['percentage'], 1) }}%</span>
                        </div>
                    </div>
                    
                    <div class="sub-rincian-breakdown-list" id="content_rincian_{{ $retribusiId }}_{{ $rincianId }}" style="display: none;">
                        @foreach($rincianData['sub_rincian'] as $subRincianId => $subRincianData)
                        <div class="sub-rincian-item">
                            <div class="sub-rincian-name">{{ $subRincianData['sub_rincian']->nama_sub_rincian_retribusi ?? 'Sub Rincian #' . $subRincianId }}</div>
                            <div class="sub-rincian-stats">
                                <span class="sub-stat">Target: Rp {{ number_format($subRincianData['target'], 0, ',', '.') }}</span>
                                <span class="sub-stat">Realisasi: Rp {{ number_format($subRincianData['realisasi'], 0, ',', '.') }}</span>
                                <span class="sub-stat">Transaksi: {{ number_format($subRincianData['total_transaksi'], 0, ',', '.') }}</span>
                                <div class="sub-progress-bar">
                                    <div class="sub-progress-fill status-{{ $subRincianData['status'] }}" style="width: {{ min($subRincianData['percentage'], 100) }}%"></div>
                                    <span class="sub-progress-text">{{ number_format($subRincianData['percentage'], 1) }}%</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Lokasi Cards Grid -->
    <h2 class="section-title" style="margin-top: 2rem;">📍 Realisasi per Lokasi</h2>
    @if($data->isEmpty())
        <div class="empty-state-card">
            <div class="empty-icon">📍</div>
            <h3>Tidak Ada Data Lokasi</h3>
            <p>Belum ada data target atau realisasi untuk OPD ini pada tahun {{ $tahun }}</p>
            <a href="{{ route('dashboard') }}" class="btn btn-primary">Kembali ke Dashboard</a>
        </div>
    @else
        <div class="lokasi-grid">
            @foreach($data as $item)
            <a href="{{ route('dashboard.realisasi.retribusi-by-lokasi', ['opdId' => $opd->id, 'lokasiId' => $item['lokasi_id'], 'tahun' => $tahun]) }}" class="lokasi-card-link">
                <div class="lokasi-card">
                    <div class="lokasi-card-header">
                        <div class="lokasi-card-icon">📍</div>
                        <div class="lokasi-card-info">
                            <h3 class="lokasi-card-title">{{ $item['nama_lokasi'] }}</h3>
                            <span class="lokasi-card-badge lokasi-badge-{{ $item['status'] }}">
                                {{ number_format($item['percentage'], 1) }}%
                            </span>
                        </div>
                    </div>
                    
                    <div class="lokasi-card-stats">
                        <div class="lokasi-stat">
                            <span class="lokasi-stat-label">Target</span>
                            <span class="lokasi-stat-value">Rp {{ number_format($item['target'], 0, ',', '.') }}</span>
                        </div>
                        <div class="lokasi-stat">
                            <span class="lokasi-stat-label">Realisasi</span>
                            <span class="lokasi-stat-value">Rp {{ number_format($item['realisasi'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                    
                    <div class="lokasi-card-progress">
                        <div class="lokasi-progress-bar lokasi-progress-{{ $item['status'] }}" 
                             style="width: {{ min($item['percentage'], 100) }}%">
                        </div>
                    </div>

                    <div class="lokasi-card-footer">
                        <span class="lokasi-transaksi">🔄 {{ $item['total_transaksi'] }} transaksi</span>
                        <span class="lokasi-status-label lokasi-status-{{ $item['status'] }}">
                            @if($item['status'] === 'achieved') ✅ Tercapai
                            @elseif($item['status'] === 'on-track') ⚡ On Track
                            @else ⚠️ Below Target
                            @endif
                        </span>
                    </div>
                    
                    <div class="lokasi-card-action">
                        <span class="action-text">Lihat Detail Retribusi</span>
                        <span class="action-icon">→</span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    @endif

</div>
@endsection

@push('styles')
<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.breadcrumb-item {
    color: var(--gray-600);
    text-decoration: none;
    transition: color var(--transition-fast);
}

.breadcrumb-item:hover {
    color: var(--primary-color);
}

.breadcrumb-item.active {
    color: var(--gray-900);
    font-weight: 600;
}

.breadcrumb-separator {
    color: var(--gray-400);
}

.page-header-actions {
    display: flex;
    gap: 0.5rem;
}

.year-filter-form {
    display: flex;
    gap: 0.5rem;
}

.year-filter-select {
    padding: 0.5rem 1rem;
    border: 2px solid var(--gray-300);
    border-radius: var(--radius-md);
    background: white;
    color: var(--gray-900);
    font-weight: 600;
    cursor: pointer;
    transition: all var(--transition-fast);
}

.year-filter-select:hover {
    border-color: var(--primary-color);
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 0.5rem;
}

.page-subtitle {
    font-size: 1rem;
    color: var(--gray-600);
    margin-bottom: 2rem;
}

.realisasi-overview-card {
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(59, 130, 246, 0.1) 100%);
    backdrop-filter: blur(12px);
    border: 2px solid rgba(139, 92, 246, 0.2);
    border-radius: var(--radius-lg);
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(139, 92, 246, 0.15);
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
}

.realisasi-stat-value {
    font-size: 1.5rem;
    font-weight: 700;
}

.realisasi-stat-target { color: #f59e0b; }
.realisasi-stat-realisasi { color: #3b82f6; }
.realisasi-stat-percentage { color: #8b5cf6; }

.realisasi-stat-divider {
    width: 2px;
    background: linear-gradient(to bottom, transparent, rgba(139, 92, 246, 0.3), transparent);
}

.realisasi-progress-container {
    height: 2rem;
    background: rgba(139, 92, 246, 0.1);
    border-radius: var(--radius-full);
    overflow: hidden;
    border: 1px solid rgba(139, 92, 246, 0.2);
}

.realisasi-progress-bar {
    height: 100%;
    border-radius: var(--radius-full);
    transition: width 0.8s ease;
}

.realisasi-progress-achieved {
    background: linear-gradient(90deg, #10b981, #059669);
}

.realisasi-progress-on-track {
    background: linear-gradient(90deg, #3b82f6, #2563eb);
}

.realisasi-progress-below-target {
    background: linear-gradient(90deg, #ef4444, #dc2626);
}

.lokasi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.lokasi-card {
    background: white;
    border: 2px solid var(--gray-200);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    display: block;
}

.lokasi-card-header {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.lokasi-card-icon {
    font-size: 2.5rem;
    flex-shrink: 0;
}

.lokasi-card-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.lokasi-card-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0;
}

.lokasi-card-badge {
    padding: 0.25rem 0.75rem;
    border-radius: var(--radius-full);
    font-size: 0.875rem;
    font-weight: 700;
    align-self: flex-start;
}

.lokasi-badge-achieved {
    background: rgba(16, 185, 129, 0.15);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.lokasi-badge-on-track {
    background: rgba(59, 130, 246, 0.15);
    color: #2563eb;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.lokasi-badge-below-target {
    background: rgba(239, 68, 68, 0.15);
    color: #dc2626;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.lokasi-card-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.lokasi-stat {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.lokasi-stat-label {
    font-size: 0.75rem;
    color: var(--gray-600);
    font-weight: 600;
    text-transform: uppercase;
}

.lokasi-stat-value {
    font-size: 1rem;
    font-weight: 700;
    color: var(--gray-900);
}

.lokasi-card-progress {
    height: 0.75rem;
    background: var(--gray-200);
    border-radius: var(--radius-full);
    overflow: hidden;
    margin-bottom: 1rem;
}

.lokasi-progress-bar {
    height: 100%;
    border-radius: var(--radius-full);
    transition: width 0.6s ease;
}

.lokasi-progress-achieved {
    background: linear-gradient(90deg, #10b981, #059669);
}

.lokasi-progress-on-track {
    background: linear-gradient(90deg, #3b82f6, #2563eb);
}

.lokasi-progress-below-target {
    background: linear-gradient(90deg, #ef4444, #dc2626);
}

.lokasi-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.5rem;
}

.lokasi-transaksi {
    font-size: 0.8125rem;
    color: var(--gray-500);
    font-weight: 500;
}

.lokasi-status-label {
    font-size: 0.8125rem;
    font-weight: 700;
    padding: 0.2rem 0.6rem;
    border-radius: var(--radius-full);
}

.lokasi-status-achieved {
    color: #059669;
    background: rgba(16, 185, 129, 0.1);
}

.lokasi-status-on-track {
    color: #2563eb;
    background: rgba(59, 130, 246, 0.1);
}

.lokasi-status-below-target {
    color: #dc2626;
    background: rgba(239, 68, 68, 0.1);
}

.lokasi-card-link {
    text-decoration: none;
    color: inherit;
    display: block;
    transition: transform 0.2s ease;
}

.lokasi-card-link:hover {
    transform: translateY(-4px);
}

.lokasi-card-link:hover .lokasi-card {
    border-color: var(--primary-color);
    box-shadow: 0 12px 40px rgba(59, 130, 246, 0.15);
}

.lokasi-card-action {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--gray-200);
}

.action-text {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--primary-color);
}

.action-icon {
    font-size: 1.25rem;
    color: var(--primary-color);
    transition: transform 0.2s ease;
}

.lokasi-card-link:hover .action-icon {
    transform: translateX(4px);
}

/* Retribution Breakdown Section */
.retribusi-breakdown-section {
    margin-bottom: 2rem;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 0.5rem;
}

.section-subtitle {
    font-size: 0.95rem;
    color: var(--gray-600);
    margin-bottom: 1.5rem;
}

.breakdown-card {
    background: white;
    border: 2px solid var(--gray-200);
    border-radius: var(--radius-lg);
    margin-bottom: 1rem;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.breakdown-header {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    padding: 1.25rem 1.5rem;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background 0.2s;
}

.breakdown-header:hover {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
}

.breakdown-header-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.toggle-icon {
    font-size: 0.875rem;
    transition: transform 0.2s;
}

.breakdown-title {
    font-size: 1.125rem;
    font-weight: 700;
    margin: 0;
}

.breakdown-header-right {
    display: flex;
    align-items: center;
}

.breakdown-stats-inline {
    display: flex;
    gap: 1.5rem;
    align-items: center;
}

.stat-inline {
    font-size: 0.875rem;
    opacity: 0.95;
}

.stat-badge-inline {
    padding: 0.375rem 0.875rem;
    border-radius: var(--radius-full);
    font-weight: 700;
    font-size: 0.875rem;
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.4);
}

.breakdown-content {
    padding: 1rem;
    background: #f8fafc;
}

.rincian-breakdown {
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-md);
    margin-bottom: 0.75rem;
    overflow: hidden;
}

.rincian-breakdown-header {
    padding: 1rem 1.25rem;
    background: #f1f5f9;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background 0.2s;
}

.rincian-breakdown-header:hover {
    background: #e2e8f0;
}

.rincian-breakdown-left {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.toggle-icon-sm {
    font-size: 0.75rem;
    color: var(--gray-600);
    transition: transform 0.2s;
}

.rincian-breakdown-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--gray-900);
    margin: 0;
}

.rincian-breakdown-right {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.stat-sm {
    font-size: 0.8125rem;
    color: var(--gray-600);
}

.stat-badge-sm {
    padding: 0.25rem 0.625rem;
    border-radius: var(--radius-full);
    font-weight: 700;
    font-size: 0.8125rem;
}

.stat-badge-sm.status-achieved,
.stat-badge-inline.status-achieved {
    background: #dcfce7;
    color: #166534;
}

.stat-badge-sm.status-on-track,
.stat-badge-inline.status-on-track {
    background: #fef3c7;
    color: #92400e;
}

.stat-badge-sm.status-below-target,
.stat-badge-inline.status-below-target {
    background: #fee2e2;
    color: #991b1b;
}

.sub-rincian-breakdown-list {
    padding: 1rem;
    background: white;
}

.sub-rincian-item {
    padding: 0.875rem;
    border-bottom: 1px solid var(--gray-100);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sub-rincian-item:last-child {
    border-bottom: none;
}

.sub-rincian-name {
    font-weight: 600;
    color: var(--gray-900);
    font-size: 0.9375rem;
    flex: 0 0 30%;
}

.sub-rincian-stats {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex: 1;
    justify-content: flex-end;
}

.sub-stat {
    font-size: 0.8125rem;
    color: var(--gray-600);
}

.sub-progress-bar {
    position: relative;
    width: 120px;
    height: 1.5rem;
    background: var(--gray-200);
    border-radius: var(--radius-md);
    overflow: hidden;
}

.sub-progress-fill {
    height: 100%;
    transition: width 0.3s;
}

.sub-progress-fill.status-achieved {
    background: linear-gradient(90deg, #10b981, #059669);
}

.sub-progress-fill.status-on-track {
    background: linear-gradient(90deg, #f59e0b, #d97706);
}

.sub-progress-fill.status-below-target {
    background: linear-gradient(90deg, #ef4444, #dc2626);
}

.sub-progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--gray-900);
}

.empty-state-card {
    background: white;
    border: 2px dashed var(--gray-300);
    border-radius: var(--radius-lg);
    padding: 4rem 2rem;
    text-align: center;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state-card h3 {
    font-size: 1.5rem;
    color: var(--gray-900);
    margin-bottom: 0.5rem;
}

.empty-state-card p {
    color: var(--gray-600);
    margin-bottom: 1.5rem;
}

@media (max-width: 768px) {
    .realisasi-overview-content {
        flex-direction: column;
        gap: 1rem;
    }
    
    .realisasi-overview-stats {
        flex-direction: column;
        gap: 1rem;
    }
    
    .realisasi-stat-divider {
        display: none;
    }
    
    .lokasi-grid {
        grid-template-columns: 1fr;
    }
    
    /* Breakdown responsive */
    .breakdown-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .breakdown-stats-inline {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
        width: 100%;
    }
    
    .rincian-breakdown-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .rincian-breakdown-right {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
        width: 100%;
    }
    
    .sub-rincian-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .sub-rincian-name {
        flex: 1;
        width: 100%;
    }
    
    .sub-rincian-stats {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
        width: 100%;
    }
    
    .sub-progress-bar {
        width: 100%;
    }
}
</style>
@endpush

@push('scripts')
<script>
function toggleBreakdown(id) {
    const content = document.getElementById('content_' + id);
    const icon = document.getElementById('icon_' + id);
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.textContent = '▼';
    } else {
        content.style.display = 'none';
        icon.textContent = '▶';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const storageKey = 'scroll-position:' + window.location.pathname;
    const yearForm = document.querySelector('.year-filter-form');
    
    if (yearForm) {
        yearForm.addEventListener('submit', function () {
            sessionStorage.setItem(storageKey, window.scrollY.toString());
        });
    }
    
    const savedScroll = sessionStorage.getItem(storageKey);
    if (savedScroll) {
        window.scrollTo(0, parseInt(savedScroll));
        sessionStorage.removeItem(storageKey);
    }
});
</script>
@endpush
