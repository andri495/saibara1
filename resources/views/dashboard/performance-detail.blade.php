@extends('layouts.app')
@section('title', 'Performa OPD vs Target')
@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="page-header">
        <div class="page-title-wrapper">
            <div class="title-icon-wrap"><span class="title-icon">📊</span></div>
            <div>
                <h1 class="page-title">Performa OPD vs Target Retribusi</h1>
                <p class="page-subtitle">Detail performa pencapaian target per OPD untuk tahun {{ $targetYear }}</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('dashboard') }}" class="btn-ghost">← Kembali ke Dashboard</a>
        </div>
    </div>

    <!-- Year Filter -->
    <div class="filter-card">
        <form method="GET" action="{{ route('dashboard.performance') }}" class="year-filter-form">
            <label for="tahun" class="filter-label">Tahun:</label>
            <select name="tahun" id="tahun" class="filter-select" onchange="this.form.submit()">
                @foreach($availableYears as $year)
                    <option value="{{ $year }}" {{ $targetYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- OPD Performance Grid -->
    <div class="performance-grid">
        @forelse($opdPerformance as $item)
            <a href="{{ route('dashboard.realisasi.lokasi-by-opd', ['opdId' => $item['opd_id'], 'tahun' => $targetYear]) }}" 
               class="performance-card performance-card-{{ $item['status'] }}">
                <div class="performance-header">
                    <h3 class="performance-opd-name">{{ $item['nama_opd'] }}</h3>
                    <span class="performance-badge badge-{{ $item['status'] }}">
                        @if($item['status'] === 'achieved')
                            ✓ Tercapai
                        @elseif($item['status'] === 'on-track')
                            ⚡ On Track
                        @else
                            ⚠ Below Target
                        @endif
                    </span>
                </div>
                <div class="performance-stats">
                    <div class="stat-item">
                        <span class="stat-label">Target</span>
                        <span class="stat-value">Rp {{ number_format($item['total_target'], 0, ',', '.') }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Realisasi</span>
                        <span class="stat-value">Rp {{ number_format($item['actual_revenue'], 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="performance-progress">
                    <div class="progress-bar">
                        <div class="progress-fill progress-fill-{{ $item['status'] }}" 
                             style="width: {{ min($item['percentage'], 100) }}%"></div>
                    </div>
                    <span class="progress-text">{{ number_format($item['percentage'], 1) }}%</span>
                </div>
                <div class="performance-footer">
                    <span class="link-text">Lihat Detail Lokasi →</span>
                </div>
            </a>
        @empty
            <div class="empty-state">
                <span class="empty-icon">📊</span>
                <h3>Belum Ada Data</h3>
                <p>Tidak ada data performa OPD untuk tahun {{ $targetYear }}</p>
            </div>
        @endforelse
    </div>
</div>

@push('styles')
<style>
.page-container{padding:2rem;max-width:1400px;margin:0 auto}
.page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:2rem}
.page-title-wrapper{display:flex;gap:1rem;align-items:center}
.title-icon-wrap{font-size:2.5rem}
.page-title{font-size:1.75rem;font-weight:700;color:#1e40af;margin:0}
.page-subtitle{color:#6b7280;margin:.25rem 0 0}
.btn-ghost{padding:.625rem 1.25rem;background:rgba(255,255,255,0.9);color:#374151;border:1px solid #e5e7eb;border-radius:.5rem;text-decoration:none;font-weight:600;transition:all .2s}
.btn-ghost:hover{background:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.1)}
.filter-card{background:rgba(255,255,255,0.9);border:1px solid #e5e7eb;border-radius:.875rem;padding:1.25rem;margin-bottom:2rem}
.year-filter-form{display:flex;align-items:center;gap:1rem}
.filter-label{font-weight:600;color:#374151}
.filter-select{padding:.5rem 1rem;border:1.5px solid #d1d5db;border-radius:.5rem;font-size:.9rem;min-width:120px}
.performance-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.5rem}
.performance-card{background:linear-gradient(135deg,rgba(249,250,251,1) 0%,rgba(255,255,255,1) 100%);border:2px solid rgba(209,213,219,0.6);border-radius:1rem;padding:1.5rem;transition:all .3s;text-decoration:none;color:inherit;display:block;box-shadow:0 1px 3px rgba(0,0,0,0.05)}
.performance-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(0,0,0,0.12);border-color:#3b82f6}
.performance-card-achieved{border-left:4px solid #10b981}
.performance-card-on-track{border-left:4px solid #f59e0b}
.performance-card-below-target{border-left:4px solid #ef4444}
.performance-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem}
.performance-opd-name{font-size:1.1rem;font-weight:700;color:#1f2937;margin:0;flex:1}
.performance-badge{padding:.375rem .75rem;border-radius:.5rem;font-size:.75rem;font-weight:600;white-space:nowrap}
.badge-achieved{background:#d1fae5;color:#065f46}
.badge-on-track{background:#fef3c7;color:#92400e}
.badge-below-target{background:#fee2e2;color:#991b1b}
.performance-stats{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem}
.stat-item{display:flex;flex-direction:column;gap:.25rem}
.stat-label{font-size:.75rem;color:#6b7280;font-weight:600;text-transform:uppercase}
.stat-value{font-size:.95rem;font-weight:700;color:#1f2937}
.performance-progress{margin-bottom:1rem}
.progress-bar{height:8px;background:#e5e7eb;border-radius:999px;overflow:hidden;margin-bottom:.5rem}
.progress-fill{height:100%;transition:width .3s;border-radius:999px}
.progress-fill-achieved{background:#10b981}
.progress-fill-on-track{background:#f59e0b}
.progress-fill-below-target{background:#ef4444}
.progress-text{font-size:.875rem;font-weight:700;color:#1f2937}
.performance-footer{padding-top:1rem;border-top:1px solid #e5e7eb}
.link-text{color:#3b82f6;font-size:.875rem;font-weight:600}
.empty-state{grid-column:1/-1;text-align:center;padding:4rem 2rem;background:rgba(255,255,255,0.9);border:2px dashed #d1d5db;border-radius:1rem}
.empty-icon{font-size:4rem;display:block;margin-bottom:1rem}
.empty-state h3{font-size:1.25rem;color:#374151;margin:.5rem 0}
.empty-state p{color:#6b7280}
</style>
@endpush
@endsection
