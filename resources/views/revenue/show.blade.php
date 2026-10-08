@extends('layouts.app')
@section('title', 'Detail Pendapatan')
@section('content')
<div class="page-container glass-page">
    <div class="page-header">
        <div class="page-title-wrapper">
            <div class="title-icon-wrap"><span class="title-icon">💰</span></div>
            <div>
                <h1 class="page-title">Detail Pendapatan</h1>
                <p class="page-subtitle">Informasi lengkap realisasi pendapatan</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('revenue.index') }}" class="btn-ghost">← Kembali</a>
        </div>
    </div>

    <!-- Revenue Hero Card -->
    <div class="glass-card revenue-hero-card">
        <div class="revenue-hero-body">
            <div class="hero-stat main-stat">
                <span class="hero-stat-icon">💵</span>
                <div>
                    <p class="hero-stat-label">Total Pendapatan</p>
                    <p class="hero-stat-value">Rp {{ number_format($data->total_revenue, 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="hero-divider"></div>
            <div class="hero-stat">
                <span class="hero-stat-icon">🧾</span>
                <div>
                    <p class="hero-stat-label">Jumlah Transaksi</p>
                    <p class="hero-stat-value-sm">{{ number_format($data->total_transactions, 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="hero-stat">
                <span class="hero-stat-icon">📅</span>
                <div>
                    <p class="hero-stat-label">Tanggal</p>
                    <p class="hero-stat-value-sm">{{ $data->date->format('d M Y') }}</p>
                </div>
            </div>
            <div class="hero-stat">
                <span class="hero-stat-icon">🔄</span>
                <div>
                    <p class="hero-stat-label">Jenis Pembayaran</p>
                    @if($data->is_installment)
                        <span class="badge-installment">Angsuran</span>
                    @else
                        <span class="badge-regular">Reguler</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Card -->
    <div class="glass-card mt-lg">
        <div class="glass-card-header">
            <span class="glass-card-icon">📋</span>
            <h3 class="glass-card-title">Detail Informasi</h3>
            <span class="badge-glass">ID #{{ $data->id }}</span>
        </div>
        <div class="glass-card-body">
            <div class="detail-grid-2">
                <div class="glass-detail-item accent-blue">
                    <span class="detail-label-sm">OPD</span>
                    <span class="detail-value-lg">{{ $data->opd->nama_opd ?? '-' }}</span>
                </div>
                <div class="glass-detail-item accent-blue">
                    <span class="detail-label-sm">Jenis Retribusi</span>
                    <span class="detail-value-lg">{{ $data->retribusi->nama_retribusi ?? '-' }}</span>
                </div>
                <div class="glass-detail-item">
                    <span class="detail-label-sm">Tanggal Pencatatan</span>
                    <span class="detail-value-md">{{ $data->date->format('d F Y') }}</span>
                </div>
                <div class="glass-detail-item">
                    <span class="detail-label-sm">Jenis Pembayaran</span>
                    <span class="detail-value-md">{{ $data->is_installment ? 'Angsuran / Cicilan' : 'Pembayaran Reguler' }}</span>
                </div>
                <div class="glass-detail-item">
                    <span class="detail-label-sm">Dicatat Pada</span>
                    <span class="detail-value-md">{{ $data->created_at->format('d F Y, H:i') }} WIB</span>
                </div>
                <div class="glass-detail-item">
                    <span class="detail-label-sm">Terakhir Diubah</span>
                    <span class="detail-value-md">{{ $data->updated_at->format('d F Y, H:i') }} WIB</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Danger Zone -->
    <div class="glass-card danger-card mt-lg">
        <div class="glass-card-header">
            <span class="glass-card-icon">⚠️</span>
            <h3 class="glass-card-title text-danger">Zona Berbahaya</h3>
        </div>
        <div class="glass-card-body">
            <p class="text-muted-sm">Menghapus data pendapatan ini bersifat permanen dan tidak dapat dibatalkan.</p>
            <form action="{{ route('revenue.destroy', $data->id) }}" method="POST" style="display:inline-block;">
                @csrf @method('DELETE')
                <button type="submit" class="btn-danger-glass" onclick="return confirm('Yakin ingin menghapus data pendapatan ini?')">🗑️ Hapus</button>
            </form>
        </div>
    </div>
</div>
@endsection
@push('styles')
<style>
.glass-page{padding:1.5rem}
.glass-card{background:rgba(255,255,255,0.75);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(37,99,235,0.12);border-radius:1rem;box-shadow:0 8px 32px rgba(37,99,235,0.08);overflow:hidden}
.revenue-hero-card{background:linear-gradient(135deg,rgba(37,99,235,0.08),rgba(59,130,246,0.05));border-color:rgba(37,99,235,0.2)}
.revenue-hero-body{display:flex;align-items:center;gap:2rem;padding:1.75rem 2rem;flex-wrap:wrap}
.hero-divider{width:1px;height:60px;background:rgba(37,99,235,0.2);flex-shrink:0}
.hero-stat{display:flex;align-items:center;gap:1rem}
.hero-stat-icon{font-size:2rem}
.main-stat{flex:1}
.hero-stat-label{font-size:.75rem;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.04em;margin:0 0 .25rem}
.hero-stat-value{font-size:1.75rem;font-weight:900;color:#1e40af;margin:0}
.hero-stat-value-sm{font-size:1.1rem;font-weight:700;color:#1e40af;margin:0}
.badge-installment{background:rgba(245,158,11,0.12);color:#d97706;padding:.3rem .875rem;border-radius:9999px;font-size:.8rem;font-weight:700}
.badge-regular{background:rgba(16,185,129,0.1);color:#059669;padding:.3rem .875rem;border-radius:9999px;font-size:.8rem;font-weight:700}
.glass-card-header{display:flex;align-items:center;gap:.75rem;padding:1.25rem 1.5rem;border-bottom:1px solid rgba(37,99,235,0.08);background:rgba(255,255,255,0.5)}
.glass-card-icon{font-size:1.25rem}.glass-card-title{font-size:1rem;font-weight:700;color:#1e40af;flex:1;margin:0}
.badge-glass{padding:.25rem .75rem;background:rgba(37,99,235,0.1);color:#2563eb;border-radius:9999px;font-size:.75rem;font-weight:600}
.glass-card-body{padding:1.5rem}
.detail-grid-2{display:grid;grid-template-columns:repeat(2,1fr);gap:1rem}
.glass-detail-item{padding:1rem 1.25rem;background:rgba(255,255,255,0.6);border-radius:.75rem;border-left:3px solid rgba(37,99,235,0.2);display:flex;flex-direction:column;gap:.35rem}
.glass-detail-item.accent-blue{border-left-color:#2563eb;background:rgba(37,99,235,0.04)}
.detail-label-sm{font-size:.7rem;text-transform:uppercase;font-weight:700;letter-spacing:.05em;color:#6b7280}
.detail-value-lg{font-size:1.1rem;font-weight:700;color:#1e40af}
.detail-value-md{font-size:.9rem;font-weight:500;color:#374151}
.mt-lg{margin-top:1.25rem}
.danger-card{border-color:rgba(239,68,68,0.2)}.danger-card .glass-card-header{background:rgba(239,68,68,0.04)}
.text-danger{color:#dc2626!important}.text-muted-sm{font-size:.875rem;color:#6b7280;margin-bottom:1rem}
.btn-primary-glass{display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:.5rem;font-size:.875rem;font-weight:600;text-decoration:none;box-shadow:0 4px 15px rgba(37,99,235,0.3);transition:all .2s;cursor:pointer}
.btn-primary-glass:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(37,99,235,0.4)}
.btn-ghost{display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:rgba(255,255,255,0.7);color:#374151;border:1px solid rgba(37,99,235,0.15);border-radius:.5rem;font-size:.875rem;font-weight:600;text-decoration:none;transition:all .2s}
.btn-ghost:hover{background:rgba(255,255,255,0.95)}
.btn-danger-glass{display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:rgba(239,68,68,0.1);color:#dc2626;border:1px solid rgba(239,68,68,0.3);border-radius:.5rem;font-size:.875rem;font-weight:600;cursor:pointer;transition:all .2s}
.btn-danger-glass:hover{background:#dc2626;color:#fff}
@media(max-width:768px){.revenue-hero-body{flex-direction:column;gap:1rem}.hero-divider{display:none}.detail-grid-2{grid-template-columns:1fr}}
</style>
@endpush
