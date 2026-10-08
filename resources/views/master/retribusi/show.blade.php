@extends('layouts.app')

@section('title', 'Detail Retribusi')

@section('content')
<div class="page-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title-wrapper">
            <h1 class="page-title">Detail Retribusi</h1>
            <p class="page-subtitle">Informasi lengkap retribusi</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('master.retribusi.edit', $retribusi->id) }}" class="btn btn-warning">
                <span class="icon">✏️</span> Edit
            </a>
            <a href="{{ route('master.retribusi.index') }}" class="btn btn-secondary">
                <span class="icon">←</span> Kembali
            </a>
        </div>
    </div>

    <!-- Detail Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Informasi Retribusi</h3>
        </div>
        <div class="card-body">
            <div class="detail-grid">
                <!-- ID -->
                <div class="detail-item">
                    <span class="detail-label">ID</span>
                    <span class="detail-value">{{ $retribusi->id }}</span>
                </div>

                <!-- Nama Retribusi -->
                <div class="detail-item">
                    <span class="detail-label">Nama Retribusi</span>
                    <span class="detail-value detail-highlight">{{ $retribusi->nama_retribusi }}</span>
                </div>

                <!-- Tanggal Dibuat -->
                <div class="detail-item">
                    <span class="detail-label">Tanggal Dibuat</span>
                    <span class="detail-value">{{ $retribusi->created_at->format('d F Y, H:i') }} WIB</span>
                </div>

                <!-- Terakhir Diubah -->
                <div class="detail-item">
                    <span class="detail-label">Terakhir Diubah</span>
                    <span class="detail-value">{{ $retribusi->updated_at->format('d F Y, H:i') }} WIB</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Data (if any) -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Data Terkait</h3>
        </div>
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-icon">📊</div>
                <p>Belum ada data terkait dengan retribusi ini</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.detail-grid {
    display: grid;
    gap: var(--spacing-lg);
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xs);
    padding: var(--spacing-md);
    background-color: var(--gray-50);
    border-radius: var(--radius-md);
    border-left: 3px solid var(--primary-color);
}

.detail-label {
    font-size: var(--font-size-xs);
    color: var(--gray-500);
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.detail-value {
    font-size: var(--font-size-base);
    color: var(--gray-900);
    font-weight: 500;
}

.detail-highlight {
    font-size: var(--font-size-lg);
    color: var(--primary-color);
    font-weight: 600;
}

@media (min-width: 768px) {
    .detail-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
@endpush
