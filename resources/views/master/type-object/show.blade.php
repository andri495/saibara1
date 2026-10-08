@extends('layouts.app')

@section('title', 'Detail Tipe Objek')

@section('content')
<div class="page-container">
    <div class="page-header">
        <div class="page-title-wrapper">
            <h1 class="page-title">Detail Tipe Objek</h1>
            <p class="page-subtitle">Informasi lengkap tipe objek</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('master.type-object.edit', $data->id) }}" class="btn btn-warning">✏️ Edit</a>
            <a href="{{ route('master.type-object.index') }}" class="btn btn-secondary">← Kembali</a>
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
                    <label class="detail-label">Nama Tipe</label>
                    <p class="detail-value">{{ $data->type }}</p>
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
@endsection
