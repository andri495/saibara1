@extends('layouts.app')
@section('title', 'Detail Realisasi vs Target')
@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="page-header">
        <div class="page-title-wrapper">
            <div class="title-icon-wrap"><span class="title-icon">📈</span></div>
            <div>
                <h1 class="page-title">Realisasi vs Target Retribusi</h1>
                <p class="page-subtitle">Detail breakdown realisasi dan target untuk tahun {{ $realisasiYear }}</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('dashboard') }}" class="btn-ghost">← Kembali ke Dashboard</a>
        </div>
    </div>

    <!-- Year Filter -->
    <div class="filter-card">
        <form method="GET" action="{{ route('dashboard.realisasi') }}" class="year-filter-form">
            <label for="tahun" class="filter-label">Tahun:</label>
            <select name="tahun" id="tahun" class="filter-select" onchange="this.form.submit()">
                @foreach($availableYears as $year)
                    <option value="{{ $year }}" {{ $realisasiYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-icon">🎯</div>
            <div class="summary-content">
                <span class="summary-label">Total Target</span>
                <span class="summary-value">Rp {{ number_format($totalTarget, 0, ',', '.') }}</span>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon">💰</div>
            <div class="summary-content">
                <span class="summary-label">Total Realisasi</span>
                <span class="summary-value">Rp {{ number_format($totalRealisasi, 0, ',', '.') }}</span>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon">📊</div>
            <div class="summary-content">
                <span class="summary-label">Persentase Pencapaian</span>
                <span class="summary-value summary-value-{{ $realisasiPercentage >= 100 ? 'success' : ($realisasiPercentage >= 75 ? 'warning' : 'danger') }}">
                    {{ number_format($realisasiPercentage, 1) }}%
                </span>
            </div>
        </div>
    </div>

    <!-- Breakdown by Retribusi Type -->
    <div class="section-card">
        <div class="section-header">
            <h2 class="section-title">Breakdown per Jenis Retribusi</h2>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Jenis Retribusi</th>
                        <th class="text-right">Target</th>
                        <th class="text-right">Realisasi</th>
                        <th class="text-right">Pencapaian</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($realisasiByRetribusi as $item)
                    <tr>
                        <td><strong>{{ $item['nama'] }}</strong></td>
                        <td class="text-right">Rp {{ number_format($item['target'], 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($item['realisasi'], 0, ',', '.') }}</td>
                        <td class="text-right"><strong>{{ number_format($item['percentage'], 1) }}%</strong></td>
                        <td class="text-center">
                            <span class="status-badge status-{{ $item['status'] }}">
                                @if($item['status'] === 'achieved')
                                    ✓ Tercapai
                                @elseif($item['status'] === 'on-track')
                                    ⚡ On Track
                                @else
                                    ⚠ Below
                                @endif
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Breakdown by OPD -->
    <div class="section-card">
        <div class="section-header">
            <h2 class="section-title">Breakdown per OPD</h2>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>OPD</th>
                        <th class="text-right">Target</th>
                        <th class="text-right">Realisasi</th>
                        <th class="text-right">Pencapaian</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($realisasiByOpd as $item)
                    <tr>
                        <td><strong>{{ $item['nama'] }}</strong></td>
                        <td class="text-right">Rp {{ number_format($item['target'], 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($item['realisasi'], 0, ',', '.') }}</td>
                        <td class="text-right"><strong>{{ number_format($item['percentage'], 1) }}%</strong></td>
                        <td class="text-center">
                            <span class="status-badge status-{{ $item['status'] }}">
                                @if($item['status'] === 'achieved')
                                    ✓ Tercapai
                                @elseif($item['status'] === 'on-track')
                                    ⚡ On Track
                                @else
                                    ⚠ Below
                                @endif
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('dashboard.realisasi.lokasi-by-opd', ['opdId' => $item['opd_id'], 'tahun' => $realisasiYear]) }}" 
                               class="btn-detail">Detail →</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
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
.summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;margin-bottom:2rem}
.summary-card{background:linear-gradient(135deg,rgba(249,250,251,1) 0%,rgba(255,255,255,1) 100%);border:2px solid rgba(209,213,219,0.6);border-radius:1rem;padding:1.5rem;display:flex;align-items:center;gap:1rem;box-shadow:0 1px 3px rgba(0,0,0,0.05)}
.summary-icon{font-size:2.5rem}
.summary-content{display:flex;flex-direction:column;gap:.25rem}
.summary-label{font-size:.75rem;color:#6b7280;font-weight:600;text-transform:uppercase}
.summary-value{font-size:1.5rem;font-weight:800;color:#1e40af}
.summary-value-success{color:#10b981}
.summary-value-warning{color:#f59e0b}
.summary-value-danger{color:#ef4444}
.section-card{background:rgba(255,255,255,0.9);border:1px solid #e5e7eb;border-radius:1rem;padding:1.5rem;margin-bottom:2rem;box-shadow:0 1px 3px rgba(0,0,0,0.05)}
.section-header{margin-bottom:1.5rem}
.section-title{font-size:1.25rem;font-weight:700;color:#1f2937;margin:0}
.table-responsive{overflow-x:auto}
.data-table{width:100%;border-collapse:collapse}
.data-table thead{background:rgba(59,130,246,0.05)}
.data-table th{padding:.875rem 1rem;text-align:left;font-size:.75rem;font-weight:700;color:#1e40af;text-transform:uppercase;border-bottom:2px solid #e5e7eb}
.data-table td{padding:.875rem 1rem;border-bottom:1px solid #e5e7eb;font-size:.9rem;color:#374151}
.data-table tbody tr:hover{background:rgba(59,130,246,0.02)}
.text-right{text-align:right}
.text-center{text-align:center}
.status-badge{padding:.375rem .75rem;border-radius:.5rem;font-size:.75rem;font-weight:600;white-space:nowrap;display:inline-block}
.status-achieved{background:#d1fae5;color:#065f46}
.status-on-track{background:#fef3c7;color:#92400e}
.status-below-target{background:#fee2e2;color:#991b1b}
.btn-detail{padding:.375rem .875rem;background:#3b82f6;color:#fff;border-radius:.375rem;text-decoration:none;font-size:.8rem;font-weight:600;transition:all .2s;display:inline-block}
.btn-detail:hover{background:#2563eb;transform:translateY(-1px)}
</style>
@endpush
@endsection
