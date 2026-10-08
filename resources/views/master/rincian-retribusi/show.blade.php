@extends('layouts.app')
@section('title', 'Detail Rincian Retribusi')
@section('content')
<div class="page-container glass-page">
    <div class="page-header">
        <div class="page-title-wrapper">
            <div class="title-icon-wrap"><span class="title-icon">📝</span></div>
            <div>
                <h1 class="page-title">Detail Rincian Retribusi</h1>
                <p class="page-subtitle">Informasi lengkap rincian retribusi</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('master.rincian-retribusi.edit', $data->id) }}" class="btn-primary-glass">✏️ Edit</a>
            <a href="{{ route('master.rincian-retribusi.index') }}" class="btn-ghost">← Kembali</a>
        </div>
    </div>
    <div class="glass-card">
        <div class="glass-card-header">
            <span class="glass-card-icon">📝</span>
            <h3 class="glass-card-title">Informasi Rincian Retribusi</h3>
            <span class="badge-glass">ID #{{ $data->id }}</span>
        </div>
        <div class="glass-card-body">
            <div class="detail-grid-2">
                <div class="glass-detail-item accent-blue">
                    <span class="detail-label-sm">Nama Rincian</span>
                    <span class="detail-value-lg">{{ $data->rincian_retribusi }}</span>
                </div>
                <div class="glass-detail-item accent-blue">
                    <span class="detail-label-sm">Jenis Retribusi</span>
                    <span class="detail-value-lg">{{ $data->retribusi->nama_retribusi ?? '-' }}</span>
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
    @if($data->subRincianRetribusi && $data->subRincianRetribusi->count() > 0)
    <div class="glass-card mt-lg">
        <div class="glass-card-header">
            <span class="glass-card-icon">📄</span>
            <h3 class="glass-card-title">Sub Rincian Terkait</h3>
            <span class="badge-glass">{{ $data->subRincianRetribusi->count() }} item</span>
        </div>
        <div class="glass-card-body p-0">
            <table class="glass-table">
                <thead><tr><th>No</th><th>Nama Sub Rincian</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach($data->subRincianRetribusi as $i => $sub)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $sub->nama_sub_rincian_retribusi }}</td>
                        <td>
                            <a href="{{ route('master.sub-rincian-retribusi.show', $sub->id) }}" class="btn-xs-glass">👁️</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
    <div class="glass-card danger-card mt-lg">
        <div class="glass-card-header">
            <span class="glass-card-icon">⚠️</span>
            <h3 class="glass-card-title text-danger">Zona Berbahaya</h3>
        </div>
        <div class="glass-card-body">
            <p class="text-muted-sm">Menghapus rincian ini akan menghapus semua data terkait secara permanen.</p>
            <form action="{{ route('master.rincian-retribusi.destroy', $data->id) }}" method="POST" style="display:inline-block;">
                @csrf @method('DELETE')
                <button type="submit" class="btn-danger-glass" onclick="return confirm('Yakin ingin menghapus?')">🗑️ Hapus</button>
            </form>
        </div>
    </div>
</div>
@endsection
@push('styles')
<style>
.glass-page{padding:1.5rem}
.glass-card{background:rgba(255,255,255,0.75);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(37,99,235,0.12);border-radius:1rem;box-shadow:0 8px 32px rgba(37,99,235,0.08);overflow:hidden}
.glass-card-header{display:flex;align-items:center;gap:.75rem;padding:1.25rem 1.5rem;border-bottom:1px solid rgba(37,99,235,0.08);background:rgba(255,255,255,0.5)}
.glass-card-icon{font-size:1.25rem}.glass-card-title{font-size:1rem;font-weight:700;color:#1e40af;flex:1;margin:0}
.badge-glass{padding:.25rem .75rem;background:rgba(37,99,235,0.1);color:#2563eb;border-radius:9999px;font-size:.75rem;font-weight:600}
.glass-card-body{padding:1.5rem}.glass-card-body.p-0{padding:0}
.detail-grid-2{display:grid;grid-template-columns:repeat(2,1fr);gap:1rem}
.glass-detail-item{padding:1rem 1.25rem;background:rgba(255,255,255,0.6);border-radius:.75rem;border-left:3px solid rgba(37,99,235,0.2);display:flex;flex-direction:column;gap:.35rem}
.glass-detail-item.accent-blue{border-left-color:#2563eb;background:rgba(37,99,235,0.04)}
.detail-label-sm{font-size:.7rem;text-transform:uppercase;font-weight:700;letter-spacing:.05em;color:#6b7280}
.detail-value-lg{font-size:1.1rem;font-weight:700;color:#1e40af}
.detail-value-md{font-size:.9rem;font-weight:500;color:#374151}
.glass-table{width:100%;border-collapse:collapse}
.glass-table th{padding:.75rem 1.25rem;background:rgba(37,99,235,0.06);font-size:.75rem;font-weight:700;text-transform:uppercase;color:#1e40af;text-align:left;border-bottom:1px solid rgba(37,99,235,0.1)}
.glass-table td{padding:.875rem 1.25rem;border-bottom:1px solid rgba(37,99,235,0.06);font-size:.875rem;color:#374151}
.glass-table tr:last-child td{border-bottom:none}
.glass-table tr:hover td{background:rgba(37,99,235,0.03)}
.btn-xs-glass{display:inline-flex;padding:.3rem .6rem;background:rgba(37,99,235,0.08);color:#2563eb;border-radius:.375rem;font-size:.75rem;text-decoration:none;transition:all .2s}
.btn-xs-glass:hover{background:rgba(37,99,235,0.15)}
.danger-card{border-color:rgba(239,68,68,0.2)}.danger-card .glass-card-header{background:rgba(239,68,68,0.04)}
.text-danger{color:#dc2626!important}.text-muted-sm{font-size:.875rem;color:#6b7280;margin-bottom:1rem}
.mt-lg{margin-top:1.25rem}
.btn-primary-glass{display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:.5rem;font-size:.875rem;font-weight:600;text-decoration:none;box-shadow:0 4px 15px rgba(37,99,235,0.3);transition:all .2s;cursor:pointer}
.btn-primary-glass:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(37,99,235,0.4)}
.btn-ghost{display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:rgba(255,255,255,0.7);color:#374151;border:1px solid rgba(37,99,235,0.15);border-radius:.5rem;font-size:.875rem;font-weight:600;text-decoration:none;transition:all .2s}
.btn-ghost:hover{background:rgba(255,255,255,0.95)}
.btn-danger-glass{display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:rgba(239,68,68,0.1);color:#dc2626;border:1px solid rgba(239,68,68,0.3);border-radius:.5rem;font-size:.875rem;font-weight:600;cursor:pointer;transition:all .2s}
.btn-danger-glass:hover{background:#dc2626;color:#fff}
@media(max-width:768px){.detail-grid-2{grid-template-columns:1fr}}
</style>
@endpush
