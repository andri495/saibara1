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
    </div>

    <!-- Main Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card stat-primary">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <h3 class="stat-title">Total Pendapatan Tahun Ini</h3>
                <p class="stat-value">Rp {{ number_format($grandTotalPendapatan, 0, ',', '.') }}</p>
                <p class="stat-label">Tahun {{ $currentYear }}</p>
            </div>
        </div>

        <div class="stat-card stat-success">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <h3 class="stat-title">Transaksi Bulan Ini</h3>
                <p class="stat-value">{{ number_format($totalTransaksiBulanIni) }}</p>
                <p class="stat-label">{{ now()->translatedFormat('F Y') }}</p>
            </div>
        </div>

        <div class="stat-card stat-info">
            <div class="stat-icon">🎯</div>
            <div class="stat-content">
                <h3 class="stat-title">Target Tahun Ini</h3>
                <p class="stat-value">Rp {{ number_format($totalTarget, 0, ',', '.') }}</p>
                <p class="stat-label">Tahun {{ $currentYear }}</p>
            </div>
        </div>

        <div class="stat-card stat-{{ $realisasiPercentage >= 100 ? 'success' : ($realisasiPercentage >= 75 ? 'warning' : 'danger') }}">
            <div class="stat-icon">📈</div>
            <div class="stat-content">
                <h3 class="stat-title">Pencapaian Target</h3>
                <p class="stat-value">{{ number_format($realisasiPercentage, 1) }}%</p>
                <p class="stat-label">
                    @if($realisasiPercentage >= 100)
                        ✓ Target Tercapai
                    @elseif($realisasiPercentage >= 75)
                        ⚡ On Track
                    @else
                        ⚠ Below Target
                    @endif
                </p>
            </div>
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

    <!-- Breakdown per Jenis Retribusi -->
    <div class="section-card">
        <div class="section-header">
            <h2 class="section-title">Breakdown Pendapatan per Jenis Retribusi</h2>
            <span class="section-subtitle">Tahun {{ $currentYear }}</span>
        </div>
        <div class="retribusi-grid">
            @foreach($retribusiBreakdown as $id => $item)
            <div class="retribusi-card">
                <div class="retribusi-header">
                    <span class="retribusi-icon">
                        @if($id == 1) 🏛️
                        @elseif($id == 2) 🏪
                        @else 📜
                        @endif
                    </span>
                    <h3 class="retribusi-name">{{ $item['name'] }}</h3>
                </div>
                <div class="retribusi-stats">
                    <div class="retribusi-stat">
                        <span class="retribusi-stat-label">Transaksi</span>
                        <span class="retribusi-stat-value">{{ number_format($item['transaction_count']) }}</span>
                    </div>
                    <div class="retribusi-stat">
                        <span class="retribusi-stat-label">Pendapatan</span>
                        <span class="retribusi-stat-value">Rp {{ number_format($item['total_revenue'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Quick Links to Detail Pages -->
    <div class="quick-links-grid">
        <a href="{{ route('dashboard.performance') }}" class="quick-link-card">
            <div class="quick-link-icon">📊</div>
            <div class="quick-link-content">
                <h3 class="quick-link-title">Performa OPD</h3>
                <p class="quick-link-desc">Lihat detail performa pencapaian target per OPD</p>
                <span class="quick-link-arrow">→</span>
            </div>
        </a>
        <a href="{{ route('dashboard.realisasi') }}" class="quick-link-card">
            <div class="quick-link-icon">📈</div>
            <div class="quick-link-content">
                <h3 class="quick-link-title">Realisasi vs Target</h3>
                <p class="quick-link-desc">Lihat breakdown detail realisasi dan target</p>
                <span class="quick-link-arrow">→</span>
            </div>
        </a>
    </div>

    <!-- Recent Activity -->
    <div class="section-card">
        <div class="section-header">
            <h2 class="section-title">Aktivitas Pendapatan Terbaru</h2>
            <span class="section-subtitle">5 entri terakhir bulan ini</span>
        </div>
        @if($recentTransaksi->count() > 0)
        <div class="activity-list">
            @foreach($recentTransaksi as $item)
            <div class="activity-item">
                <div class="activity-date">
                    <span class="activity-day">{{ $item->date->format('d') }}</span>
                    <span class="activity-month">{{ $item->date->translatedFormat('M') }}</span>
                </div>
                <div class="activity-content">
                    <h4 class="activity-title">{{ $item->opd->nama_opd ?? '-' }}</h4>
                    <p class="activity-desc">{{ $item->retribusi->nama_retribusi ?? '-' }}</p>
                    <div class="activity-meta">
                        <span class="activity-badge">{{ number_format($item->total_transactions) }} transaksi</span>
                        @if($item->is_installment)
                        <span class="activity-badge activity-badge-installment">Angsuran</span>
                        @endif
                    </div>
                </div>
                <div class="activity-amount">
                    <span class="activity-revenue">Rp {{ number_format($item->total_revenue, 0, ',', '.') }}</span>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="empty-state">
            <span class="empty-icon">📊</span>
            <p>Belum ada aktivitas pendapatan bulan ini</p>
        </div>
        @endif
    </div>

</div>

@push('styles')
<style>
.page-container{padding:2rem;max-width:1400px;margin:0 auto}
.welcome-section{background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%);border-radius:1rem;padding:2rem;margin-bottom:2rem;color:#fff;box-shadow:0 4px 16px rgba(37,99,235,0.2)}
.welcome-title{font-size:1.75rem;font-weight:700;margin:0 0 .5rem}
.welcome-subtitle{font-size:1rem;opacity:.9;margin:0}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.5rem;margin-bottom:2rem}
.stat-card{background:linear-gradient(135deg,rgba(249,250,251,1) 0%,rgba(255,255,255,1) 100%);border:2px solid rgba(209,213,219,0.6);border-radius:1rem;padding:1.5rem;display:flex;align-items:center;gap:1rem;box-shadow:0 1px 3px rgba(0,0,0,0.05);transition:all .3s}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,0.1)}
.stat-icon{font-size:2.5rem}
.stat-content{flex:1}
.stat-title{font-size:.85rem;color:#6b7280;font-weight:600;margin:0 0 .5rem;text-transform:uppercase}
.stat-value{font-size:1.75rem;font-weight:800;color:#1f2937;margin:0 0 .25rem}
.stat-label{font-size:.8rem;color:#9ca3af;margin:0}
.stat-primary{border-left:4px solid #3b82f6}
.stat-success{border-left:4px solid #10b981}
.stat-info{border-left:4px solid #06b6d4}
.stat-warning{border-left:4px solid #f59e0b}
.stat-danger{border-left:4px solid #ef4444}
.master-info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:2rem}
.master-info-card{background:rgba(255,255,255,0.9);border:1px solid #e5e7eb;border-radius:.75rem;padding:1.25rem;display:flex;align-items:center;gap:.75rem;transition:all .2s}
.master-info-card-link{text-decoration:none;color:inherit}
.master-info-card-link:hover{background:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.08);transform:translateY(-2px)}
.master-info-icon{font-size:1.75rem}
.master-info-value{font-size:1.5rem;font-weight:800;color:#1e40af;margin:0}
.master-info-label{font-size:.75rem;color:#6b7280;font-weight:600;margin:0;text-transform:uppercase}
.section-card{background:rgba(255,255,255,0.9);border:1px solid #e5e7eb;border-radius:1rem;padding:1.5rem;margin-bottom:2rem;box-shadow:0 1px 3px rgba(0,0,0,0.05)}
.section-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:2px solid #e5e7eb}
.section-title{font-size:1.25rem;font-weight:700;color:#1f2937;margin:0}
.section-subtitle{font-size:.85rem;color:#6b7280;font-weight:600}
.retribusi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.25rem}
.retribusi-card{background:linear-gradient(135deg,rgba(249,250,251,1) 0%,rgba(255,255,255,1) 100%);border:2px solid rgba(209,213,219,0.6);border-radius:.875rem;padding:1.25rem;transition:all .2s}
.retribusi-card:hover{box-shadow:0 4px 12px rgba(0,0,0,0.08);transform:translateY(-2px)}
.retribusi-header{display:flex;align-items:center;gap:.75rem;margin-bottom:1rem}
.retribusi-icon{font-size:1.75rem}
.retribusi-name{font-size:1rem;font-weight:700;color:#1f2937;margin:0}
.retribusi-stats{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.retribusi-stat{display:flex;flex-direction:column;gap:.25rem}
.retribusi-stat-label{font-size:.75rem;color:#6b7280;font-weight:600;text-transform:uppercase}
.retribusi-stat-value{font-size:.95rem;font-weight:700;color:#1f2937}
.quick-links-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1.5rem;margin-bottom:2rem}
.quick-link-card{background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%);border-radius:1rem;padding:1.5rem;display:flex;align-items:center;gap:1rem;text-decoration:none;color:#fff;transition:all .3s;box-shadow:0 4px 12px rgba(37,99,235,0.3)}
.quick-link-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(37,99,235,0.4)}
.quick-link-icon{font-size:2.5rem}
.quick-link-content{flex:1}
.quick-link-title{font-size:1.1rem;font-weight:700;margin:0 0 .25rem}
.quick-link-desc{font-size:.85rem;opacity:.9;margin:0}
.quick-link-arrow{font-size:1.5rem;font-weight:700}
.activity-list{display:flex;flex-direction:column;gap:.75rem}
.activity-item{display:flex;align-items:center;gap:1rem;padding:1rem;background:rgba(249,250,251,0.5);border:1px solid #e5e7eb;border-radius:.75rem;transition:all .2s}
.activity-item:hover{background:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.05)}
.activity-date{display:flex;flex-direction:column;align-items:center;padding:.75rem;background:#fff;border-radius:.5rem;min-width:60px;border:1px solid #e5e7eb}
.activity-day{font-size:1.25rem;font-weight:800;color:#1f2937;line-height:1}
.activity-month{font-size:.75rem;color:#6b7280;font-weight:600;text-transform:uppercase}
.activity-content{flex:1}
.activity-title{font-size:.95rem;font-weight:700;color:#1f2937;margin:0 0 .25rem}
.activity-desc{font-size:.85rem;color:#6b7280;margin:0 0 .5rem}
.activity-meta{display:flex;gap:.5rem}
.activity-badge{padding:.25rem .625rem;background:#e5e7eb;color:#374151;border-radius:.375rem;font-size:.75rem;font-weight:600}
.activity-badge-installment{background:#dbeafe;color:#1e40af}
.activity-amount{text-align:right}
.activity-revenue{font-size:1rem;font-weight:800;color:#10b981}
.empty-state{text-align:center;padding:3rem 1rem;color:#9ca3af}
.empty-icon{font-size:3rem;display:block;margin-bottom:.5rem}
</style>
@endpush
@endsection
