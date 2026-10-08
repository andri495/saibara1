@extends('layouts.app')
@section('title', 'Detail Target Retribusi')
@section('content')
<div class="page-container glass-page">
    <div class="page-header">
        <div class="page-title-wrapper">
            <div class="title-icon-wrap"><span class="title-icon">🎯</span></div>
            <div>
                <h1 class="page-title">Detail Target Retribusi</h1>
                <p class="page-subtitle">Informasi lengkap target pendapatan retribusi</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('retribution-target.edit', $data->id) }}" class="btn-primary-glass">✏️ Edit</a>
            <a href="{{ route('retribution-target.index') }}" class="btn-ghost">← Kembali</a>
        </div>
    </div>

    <!-- Hero Target Card -->
    <div class="glass-card target-hero-card">
        <div class="target-hero-body">
            <div class="target-year-circle">
                <span class="target-year-num">{{ $data->tahun }}</span>
                <span class="target-year-label">Tahun</span>
            </div>
            <div class="target-hero-info">
                <p class="target-hero-opd">{{ $data->opd->nama_opd ?? '-' }}</p>
                <p class="target-hero-retribusi">{{ $data->retribusi->nama_retribusi ?? '-' }}</p>
                <div class="target-amount-display">
                    <span class="target-amount-label">Target Pendapatan</span>
                    <span class="target-amount-value">Rp {{ number_format($data->target_amount, 0, ',', '.') }}</span>
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
                    <span class="detail-label-sm">Tahun Anggaran</span>
                    <span class="detail-value-lg">{{ $data->tahun }}</span>
                </div>
                <div class="glass-detail-item accent-blue">
                    <span class="detail-label-sm">Target Pendapatan</span>
                    <span class="detail-value-lg">Rp {{ number_format($data->target_amount, 0, ',', '.') }}</span>
                </div>
                <div class="glass-detail-item">
                    <span class="detail-label-sm">OPD</span>
                    <span class="detail-value-md">{{ $data->opd->nama_opd ?? '-' }}</span>
                </div>
                <div class="glass-detail-item">
                    <span class="detail-label-sm">Jenis Retribusi</span>
                    <span class="detail-value-md">{{ $data->retribusi->nama_retribusi ?? '-' }}</span>
                </div>
                <div class="glass-detail-item">
                    <span class="detail-label-sm">Lokasi</span>
                    <span class="detail-value-md">{{ $data->lokasi->nama_lokasi ?? '-' }}</span>
                </div>
                <div class="glass-detail-item">
                    <span class="detail-label-sm">Rincian Retribusi</span>
                    <span class="detail-value-md">{{ $data->rincianRetribusi->rincian_retribusi ?? '-' }}</span>
                </div>
                <div class="glass-detail-item">
                    <span class="detail-label-sm">Sub Rincian Retribusi</span>
                    <span class="detail-value-md">{{ $data->subRincianRetribusi->nama_sub_rincian_retribusi ?? '-' }}</span>
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
            <p class="text-muted-sm">Menghapus target retribusi ini bersifat permanen dan tidak dapat dibatalkan.</p>
            <form action="{{ route('retribution-target.destroy', $data->id) }}" method="POST" style="display:inline-block;">
                @csrf @method('DELETE')
                <button type="submit" class="btn-danger-glass" onclick="return confirm('Yakin ingin menghapus target ini?')">🗑️ Hapus</button>
            </form>
        </div>
    </div>
</div>
@endsection
@push('styles')
<style>
.glass-page{padding:1.5rem}
.glass-card{background:rgba(255,255,255,0.75);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(37,99,235,0.12);border-radius:1rem;box-shadow:0 8px 32px rgba(37,99,235,0.08);overflow:hidden}
.target-hero-card{background:linear-gradient(135deg,rgba(37,99,235,0.08),rgba(59,130,246,0.04));border-color:rgba(37,99,235,0.2)}
.target-hero-body{display:flex;align-items:center;gap:2rem;padding:2rem}
.target-year-circle{width:90px;height:90px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#3b82f6);display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 8px 24px rgba(37,99,235,0.35)}
.target-year-num{color:#fff;font-size:1.4rem;font-weight:900;line-height:1}
.target-year-label{color:rgba(255,255,255,0.8);font-size:.6rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em}
.target-hero-info{flex:1}
.target-hero-opd{font-size:.875rem;color:#6b7280;margin:0 0 .25rem;font-weight:500}
.target-hero-retribusi{font-size:1.2rem;font-weight:800;color:#1e40af;margin:0 0 1rem}
.target-amount-display{display:inline-flex;flex-direction:column;gap:.15rem;padding:.875rem 1.25rem;background:rgba(255,255,255,0.7);border-radius:.75rem;border:1px solid rgba(37,99,235,0.15)}
.target-amount-label{font-size:.7rem;text-transform:uppercase;font-weight:700;color:#6b7280;letter-spacing:.05em}
.target-amount-value{font-size:1.5rem;font-weight:900;color:#1e40af}
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
@media(max-width:768px){.target-hero-body{flex-direction:column;align-items:flex-start;gap:1.25rem}.detail-grid-2{grid-template-columns:1fr}}
</style>
@endpush
