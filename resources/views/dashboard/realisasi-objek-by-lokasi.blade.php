@extends('layouts.app')

@section('title', 'Realisasi per Objek - ' . $lokasi->nama_lokasi)

@section('content')
<div class="page-container">
    
    <!-- Breadcrumb & Header -->
    <div class="page-header">
        <div class="breadcrumb">
            <a href="{{ route('dashboard') }}" class="breadcrumb-item">📊 Dashboard</a>
            <span class="breadcrumb-separator">›</span>
            @if($opd)
            <a href="{{ route('dashboard.realisasi.lokasi-by-opd', ['opdId' => $opd->id, 'tahun' => $tahun]) }}" class="breadcrumb-item">{{ $opd->nama_opd }}</a>
            <span class="breadcrumb-separator">›</span>
            @endif
            <span class="breadcrumb-item active">{{ $lokasi->nama_lokasi }}</span>
        </div>
        <div class="page-header-actions">
            <form method="GET" action="{{ route('dashboard.realisasi.objek-by-lokasi', $lokasi->id) }}" class="year-filter-form">
                @if($opdId)
                <input type="hidden" name="opd_id" value="{{ $opdId }}">
                @endif
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

    <h1 class="page-title">📍 {{ $lokasi->nama_lokasi }}</h1>
    <p class="page-subtitle">
        Realisasi vs Target per Objek - Tahun {{ $tahun }}
        @if($opd)
        <span class="opd-badge">🏢 {{ $opd->nama_opd }}</span>
        @endif
    </p>

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

    <!-- Objek Table -->
    @if($data->isEmpty())
        <div class="empty-state-card">
            <div class="empty-icon">🏗️</div>
            <h3>Tidak Ada Data Objek</h3>
            <p>Belum ada data target atau realisasi untuk lokasi ini pada tahun {{ $tahun }}</p>
            @if($opd)
            <a href="{{ route('dashboard.realisasi.lokasi-by-opd', ['opdId' => $opd->id, 'tahun' => $tahun]) }}" class="btn btn-primary">Kembali ke Lokasi</a>
            @else
            <a href="{{ route('dashboard') }}" class="btn btn-primary">Kembali ke Dashboard</a>
            @endif
        </div>
    @else
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📋 Daftar Objek Retribusi</h3>
                <span class="card-subtitle">{{ $data->count() }} objek ditemukan</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Objek</th>
                                <th>Rincian Objek</th>
                                @if(!$opd)
                                <th>OPD</th>
                                @endif
                                <th>Target</th>
                                <th>Realisasi</th>
                                <th>Pencapaian</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <div class="objek-name">
                                        <span class="objek-icon">🏗️</span>
                                        <strong>{{ $item['nama_objek'] }}</strong>
                                    </div>
                                </td>
                                <td>{{ $item['rincian_objek'] ?? '-' }}</td>
                                @if(!$opd)
                                <td>{{ $item['nama_opd'] }}</td>
                                @endif
                                <td>
                                    <span class="amount-value">Rp {{ number_format($item['target'], 0, ',', '.') }}</span>
                                </td>
                                <td>
                                    <span class="amount-value">Rp {{ number_format($item['realisasi'], 0, ',', '.') }}</span>
                                </td>
                                <td>
                                    <div class="progress-cell">
                                        <div class="progress-bar-mini">
                                            <div class="progress-fill progress-fill-{{ $item['status'] }}" 
                                                 style="width: {{ min($item['percentage'], 100) }}%">
                                            </div>
                                        </div>
                                        <span class="progress-text">{{ number_format($item['percentage'], 1) }}%</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-badge-{{ $item['status'] }}">
                                        @if($item['status'] == 'achieved')
                                            ✅ Tercapai
                                        @elseif($item['status'] == 'on-track')
                                            ⚡ On Track
                                        @else
                                            ⚠️ Below Target
                                        @endif
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="{{ $opd ? 3 : 4 }}"><strong>TOTAL</strong></td>
                                <td><strong>Rp {{ number_format($totalTarget, 0, ',', '.') }}</strong></td>
                                <td><strong>Rp {{ number_format($totalRealisasi, 0, ',', '.') }}</strong></td>
                                <td colspan="2"><strong>{{ number_format($totalPercentage, 1) }}%</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
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
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.opd-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: rgba(59, 130, 246, 0.1);
    color: var(--primary-color);
    border-radius: var(--radius-full);
    font-size: 0.875rem;
    font-weight: 600;
    border: 1px solid rgba(59, 130, 246, 0.2);
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

.objek-name {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.objek-icon {
    font-size: 1.25rem;
}

.amount-value {
    font-weight: 600;
    color: var(--gray-900);
}

.progress-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.progress-bar-mini {
    flex: 1;
    height: 0.5rem;
    background: var(--gray-200);
    border-radius: var(--radius-full);
    overflow: hidden;
    min-width: 80px;
}

.progress-fill {
    height: 100%;
    border-radius: var(--radius-full);
    transition: width 0.6s ease;
}

.progress-fill-achieved {
    background: linear-gradient(90deg, #10b981, #059669);
}

.progress-fill-on-track {
    background: linear-gradient(90deg, #3b82f6, #2563eb);
}

.progress-fill-below-target {
    background: linear-gradient(90deg, #ef4444, #dc2626);
}

.progress-text {
    font-weight: 600;
    color: var(--gray-900);
    min-width: 50px;
}

.status-badge {
    display: inline-block;
    padding: 0.375rem 0.75rem;
    border-radius: var(--radius-full);
    font-size: 0.8125rem;
    font-weight: 700;
    white-space: nowrap;
}

.status-badge-achieved {
    background: rgba(16, 185, 129, 0.15);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.status-badge-on-track {
    background: rgba(59, 130, 246, 0.15);
    color: #2563eb;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.status-badge-below-target {
    background: rgba(239, 68, 68, 0.15);
    color: #dc2626;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.total-row {
    background: var(--gray-50);
    font-weight: 700;
}

.total-row td {
    border-top: 2px solid var(--gray-300);
    padding: 1rem !important;
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
    
    .table-responsive {
        overflow-x: auto;
    }
}
</style>
@endpush

@push('scripts')
<script>
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
