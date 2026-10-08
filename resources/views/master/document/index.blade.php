@extends('layouts.app')
@section('title', 'Master Dokumen')
@section('content')
<div class="page-container glass-page">
    <div class="page-header">
        <div class="page-title-wrapper">
            <div class="title-icon-wrap"><span class="title-icon">📄</span></div>
            <div>
                <h1 class="page-title">Master Dokumen</h1>
                <p class="page-subtitle">Kelola data tipe dokumen pendukung</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('master.document.create') }}" class="btn-primary-glass">➕ Tambah Dokumen</a>
        </div>
    </div>
    <div class="glass-card">
        <div class="glass-card-header">
            <span class="glass-card-icon">📄</span>
            <h3 class="glass-card-title">Daftar Tipe Dokumen</h3>
            <span class="badge-glass">{{ $data->total() }} data</span>
        </div>
        <div class="glass-card-body p-0">
            @if($data->count() > 0)
                <table class="glass-table">
                    <thead>
                        <tr>
                            <th width="60">No</th>
                            <th>Nama Dokumen</th>
                            <th>Tanggal Dibuat</th>
                            <th class="text-center" width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $index => $item)
                        <tr>
                            <td class="text-muted">{{ $data->firstItem() + $index }}</td>
                            <td><strong>{{ $item->nama_document }}</strong></td>
                            <td>{{ $item->created_at->format('d M Y') }}</td>
                            <td class="text-center">
                                <div class="action-btns">
                                    <a href="{{ route('master.document.show', $item->id) }}" class="btn-xs-glass" title="Detail">👁️</a>
                                    <a href="{{ route('master.document.edit', $item->id) }}" class="btn-xs-warn" title="Edit">✏️</a>
                                    <form action="{{ route('master.document.destroy', $item->id) }}" method="POST" style="display:inline">
                                        @csrf @method('DELETE')
                                        <button class="btn-xs-danger" onclick="return confirm('Hapus dokumen ini?')" title="Hapus">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="pagination-wrap">{{ $data->links() }}</div>
            @else
                <div class="glass-empty-state">
                    <span class="empty-icon">📄</span>
                    <h3>Belum Ada Data Dokumen</h3>
                    <p>Klik tombol "Tambah Dokumen" untuk menambahkan data baru.</p>
                    <a href="{{ route('master.document.create') }}" class="btn-primary-glass">➕ Tambah Dokumen</a>
                </div>
            @endif
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
.glass-card-body.p-0{padding:0}
.glass-table{width:100%;border-collapse:collapse}
.glass-table th{padding:.75rem 1.25rem;background:rgba(37,99,235,0.06);font-size:.72rem;font-weight:700;text-transform:uppercase;color:#1e40af;text-align:left;border-bottom:1px solid rgba(37,99,235,0.1)}
.glass-table td{padding:.875rem 1.25rem;border-bottom:1px solid rgba(37,99,235,0.06);font-size:.875rem;color:#374151}
.glass-table tr:last-child td{border-bottom:none}.glass-table tr:hover td{background:rgba(37,99,235,0.025)}
.text-center{text-align:center!important}.text-muted{color:#9ca3af}
.action-btns{display:flex;justify-content:center;gap:.4rem}
.btn-xs-glass{display:inline-flex;padding:.3rem .6rem;background:rgba(37,99,235,0.08);color:#2563eb;border-radius:.375rem;font-size:.75rem;text-decoration:none;border:none;cursor:pointer;transition:all .2s}
.btn-xs-glass:hover{background:rgba(37,99,235,0.18)}
.btn-xs-warn{display:inline-flex;padding:.3rem .6rem;background:rgba(245,158,11,0.1);color:#d97706;border-radius:.375rem;font-size:.75rem;text-decoration:none;border:none;cursor:pointer;transition:all .2s}
.btn-xs-warn:hover{background:rgba(245,158,11,0.2)}
.btn-xs-danger{display:inline-flex;padding:.3rem .6rem;background:rgba(239,68,68,0.08);color:#dc2626;border-radius:.375rem;font-size:.75rem;border:none;cursor:pointer;transition:all .2s}
.btn-xs-danger:hover{background:rgba(239,68,68,0.18)}
.pagination-wrap{padding:1rem 1.25rem;border-top:1px solid rgba(37,99,235,0.08)}
.glass-empty-state{display:flex;flex-direction:column;align-items:center;padding:3rem;gap:.75rem;text-align:center}
.empty-icon{font-size:3rem;opacity:.5}
.glass-empty-state h3{font-size:1.1rem;font-weight:700;color:#374151;margin:0}
.glass-empty-state p{font-size:.875rem;color:#9ca3af;margin:0}
.btn-primary-glass{display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:.5rem;font-size:.875rem;font-weight:600;text-decoration:none;box-shadow:0 4px 15px rgba(37,99,235,0.3);transition:all .2s;cursor:pointer}
.btn-primary-glass:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(37,99,235,0.4)}
</style>
@endpush
