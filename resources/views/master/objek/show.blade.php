@extends('layouts.app')

@section('title', 'Detail Objek')

@section('content')
<div class="page-container">
    <div class="page-header">
        <div class="page-title-wrapper">
            <h1 class="page-title">Detail Objek</h1>
            <p class="page-subtitle">Informasi lengkap objek retribusi</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('master.objek.edit', $data->id) }}" class="btn btn-warning">✏️ Edit</a>
            <a href="{{ route('master.objek.index') }}" class="btn btn-secondary">← Kembali</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="detail-grid">
                <div class="detail-item">
                    <label class="detail-label">ID</label>
                    <p class="detail-value">{{ $data->id }}</p>
                </div>
                <div class="detail-item">
                    <label class="detail-label">Nama Objek</label>
                    <p class="detail-value"><strong>{{ $data->sub_rincian_objek }}</strong></p>
                </div>
                <div class="detail-item">
                    <label class="detail-label">Kode/Rincian Objek</label>
                    <p class="detail-value">{{ $data->rincian_objek ?? '-' }}</p>
                </div>
                <div class="detail-item">
                    <label class="detail-label">OPD</label>
                    <p class="detail-value">{{ $data->opd->nama_opd ?? '-' }}</p>
                </div>
                <div class="detail-item">
                    <label class="detail-label">Jenis Retribusi</label>
                    <p class="detail-value">{{ $data->retribusi->nama_retribusi ?? '-' }}</p>
                </div>
                <div class="detail-item">
                    <label class="detail-label">Rincian Retribusi</label>
                    <p class="detail-value">{{ $data->rincianRetribusi->rincian_retribusi ?? '-' }}</p>
                </div>
                <div class="detail-item">
                    <label class="detail-label">Lokasi</label>
                    <p class="detail-value">{{ $data->lokasi->nama_lokasi ?? '-' }}</p>
                </div>
                <div class="detail-item">
                    <label class="detail-label">Harga</label>
                    <p class="detail-value"><strong>Rp {{ number_format($data->harga_objek, 0, ',', '.') }}</strong></p>
                </div>
                <div class="detail-item">
                    <label class="detail-label">Satuan</label>
                    <p class="detail-value">{{ $data->satuan->nama_satuan ?? '-' }}</p>
                </div>
                <div class="detail-item">
                    <label class="detail-label">Tipe Objek</label>
                    <p class="detail-value">{{ $data->typeObject->type ?? '-' }}</p>
                </div>
                <div class="detail-item">
                    <label class="detail-label">Status</label>
                    <p class="detail-value">
                        @if($data->statusObjek)
                        <span class="badge badge-info">{{ $data->statusObjek->status_objek }}</span>
                        @else
                        -
                        @endif
                    </p>
                </div>
                <div class="detail-item">
                    <label class="detail-label">Gambar</label>
                    <p class="detail-value">
                        @if($data->images)
                        <a href="{{ $data->images }}" target="_blank">Lihat Gambar</a>
                        @else
                        -
                        @endif
                    </p>
                </div>
                <div class="detail-item detail-item-full">
                    <label class="detail-label">Uraian/Deskripsi</label>
                    <p class="detail-value">{{ $data->uraian_objek ?? '-' }}</p>
                </div>
                <div class="detail-item">
                    <label class="detail-label">Dibuat Pada</label>
                    <p class="detail-value">{{ $data->created_at ? $data->created_at->format('d M Y H:i') : '-' }}</p>
                </div>
                <div class="detail-item">
                    <label class="detail-label">Diupdate Pada</label>
                    <p class="detail-value">{{ $data->updated_at ? $data->updated_at->format('d M Y H:i') : '-' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.detail-item-full {
    grid-column: 1 / -1;
}

@media (max-width: 768px) {
    .detail-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush
@endsection
