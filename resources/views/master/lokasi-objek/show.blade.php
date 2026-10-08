@extends('layouts.app')

@section('title', 'Detail Lokasi Objek')

@section('content')
<div class="page-container glass-page">
    <div class="page-header">
        <div class="page-title-wrapper">
            <div class="title-icon-wrap">
                <span class="title-icon">📍</span>
            </div>
            <div>
                <h1 class="page-title">Detail Lokasi Objek</h1>
                <p class="page-subtitle">Informasi lengkap lokasi objek retribusi</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('master.lokasi-objek.edit', $data->id) }}" class="btn btn-primary-glass">
                ✏️ Edit
            </a>
            <a href="{{ route('master.lokasi-objek.index') }}" class="btn btn-ghost">
                ← Kembali
            </a>
        </div>
    </div>

    <div class="glass-grid-2">
        <!-- Main Info Card -->
        <div class="glass-card span-2">
            <div class="glass-card-header">
                <span class="glass-card-icon">🏢</span>
                <h3 class="glass-card-title">Informasi Lokasi</h3>
                <span class="badge-glass">ID #{{ $data->id }}</span>
            </div>
            <div class="glass-card-body">
                <div class="detail-grid-2">
                    <div class="glass-detail-item accent-blue">
                        <span class="detail-label-sm">Nama Lokasi</span>
                        <span class="detail-value-lg">{{ $data->nama_lokasi }}</span>
                    </div>
                    <div class="glass-detail-item accent-blue">
                        <span class="detail-label-sm">OPD</span>
                        <span class="detail-value-lg">{{ $data->masterOpd->nama_opd ?? '-' }}</span>
                    </div>
                    <div class="glass-detail-item">
                        <span class="detail-label-sm">Tanggal Dibuat</span>
                        <span class="detail-value-md">{{ $data->created_at->format('d F Y, H:i') }} WIB</span>
                    </div>
                    <div class="glass-detail-item">
                        <span class="detail-label-sm">Terakhir Diubah</span>
                        <span class="detail-value-md">{{ $data->updated_at->format('d F Y, H:i') }} WIB</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Operational Hours Card -->
        <div class="glass-card span-2">
            <div class="glass-card-header">
                <span class="glass-card-icon">🕒</span>
                <h3 class="glass-card-title">Jam Operasional</h3>
            </div>
            <div class="glass-card-body">
                @if($data->operationalTime && $data->operationalTime->count() > 0)
                    <div class="ops-grid">
                        @foreach($data->operationalTime as $ops)
                        <div class="ops-item">
                            <span class="ops-rule">{{ $ops->rule_name ?? 'Jadwal ' . $loop->iteration }}</span>
                            <span class="ops-time">{{ $ops->open_time ?? '--:--' }} - {{ $ops->close_time ?? '--:--' }}</span>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="glass-empty">
                        <span class="glass-empty-icon">🕒</span>
                        <p>Belum ada jam operasional terdaftar</p>
                    </div>
                @endif
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
            <p class="text-muted-sm">Menghapus lokasi ini akan menghapus semua data terkait secara permanen.</p>
            <form action="{{ route('master.lokasi-objek.destroy', $data->id) }}" method="POST" style="display:inline-block;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger-glass" onclick="return confirm('Yakin ingin menghapus lokasi ini?')">
                    🗑️ Hapus Lokasi
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.glass-page { padding: 1.5rem; }
.glass-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.25rem; margin-bottom: 1.25rem; }
.span-2 { grid-column: span 2; }
.glass-card { background: rgba(255,255,255,0.75); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(37,99,235,0.12); border-radius: 1rem; box-shadow: 0 8px 32px rgba(37,99,235,0.08); overflow: hidden; }
.glass-card-header { display: flex; align-items: center; gap: .75rem; padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(37,99,235,0.08); background: rgba(255,255,255,0.5); }
.glass-card-icon { font-size: 1.25rem; }
.glass-card-title { font-size: 1rem; font-weight: 700; color: #1e40af; flex: 1; margin: 0; }
.badge-glass { padding: .25rem .75rem; background: rgba(37,99,235,0.1); color: #2563eb; border-radius: 9999px; font-size: .75rem; font-weight: 600; }
.glass-card-body { padding: 1.5rem; }
.detail-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
.glass-detail-item { padding: 1rem 1.25rem; background: rgba(255,255,255,0.6); border-radius: .75rem; border-left: 3px solid rgba(37,99,235,0.2); display: flex; flex-direction: column; gap: .35rem; }
.glass-detail-item.accent-blue { border-left-color: #2563eb; background: rgba(37,99,235,0.04); }
.detail-label-sm { font-size: .7rem; text-transform: uppercase; font-weight: 700; letter-spacing: .05em; color: #6b7280; }
.detail-value-lg { font-size: 1.1rem; font-weight: 700; color: #1e40af; }
.detail-value-md { font-size: .9rem; font-weight: 500; color: #374151; }
.ops-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: .75rem; }
.ops-item { padding: .75rem 1rem; background: rgba(37,99,235,0.06); border-radius: .5rem; display: flex; flex-direction: column; gap: .25rem; }
.ops-rule { font-size: .75rem; font-weight: 700; color: #2563eb; }
.ops-time { font-size: .875rem; color: #374151; }
.glass-empty { display: flex; flex-direction: column; align-items: center; padding: 2rem; gap: .75rem; }
.glass-empty-icon { font-size: 2rem; opacity: .5; }
.glass-empty p { color: #9ca3af; font-size: .875rem; margin: 0; }
.danger-card { border-color: rgba(239,68,68,0.2); }
.danger-card .glass-card-header { background: rgba(239,68,68,0.04); }
.text-danger { color: #dc2626 !important; }
.text-muted-sm { font-size: .875rem; color: #6b7280; margin-bottom: 1rem; }
.mt-lg { margin-top: 1.25rem; }
.btn-primary-glass { display: inline-flex; align-items: center; gap: .5rem; padding: .625rem 1.25rem; background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; border: none; border-radius: .5rem; font-size: .875rem; font-weight: 600; text-decoration: none; cursor: pointer; box-shadow: 0 4px 15px rgba(37,99,235,0.3); transition: all .2s; }
.btn-primary-glass:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(37,99,235,0.4); }
.btn-ghost { display: inline-flex; align-items: center; gap: .5rem; padding: .625rem 1.25rem; background: rgba(255,255,255,0.7); color: #374151; border: 1px solid rgba(37,99,235,0.15); border-radius: .5rem; font-size: .875rem; font-weight: 600; text-decoration: none; backdrop-filter: blur(10px); transition: all .2s; }
.btn-ghost:hover { background: rgba(255,255,255,0.9); }
.btn-danger-glass { display: inline-flex; align-items: center; gap: .5rem; padding: .625rem 1.25rem; background: rgba(239,68,68,0.1); color: #dc2626; border: 1px solid rgba(239,68,68,0.3); border-radius: .5rem; font-size: .875rem; font-weight: 600; cursor: pointer; transition: all .2s; }
.btn-danger-glass:hover { background: #dc2626; color: #fff; }
@media(max-width:768px) { .glass-grid-2, .detail-grid-2 { grid-template-columns: 1fr; } .span-2 { grid-column: span 1; } }
</style>
@endpush
