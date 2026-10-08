@extends('layouts.app')

@section('title', 'Daftar Lokasi')

@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">📍 Daftar Lokasi</h1>
            <p class="page-subtitle">Lokasi Retribusi</p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">
            ← Kembali ke Dashboard
        </a>
    </div>

    <!-- Search Bar -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-body">
            <form method="GET" action="{{ route('dashboard.lokasi.list') }}" class="search-form">
                <div class="search-input-group">
                    <input 
                        type="text" 
                        name="search" 
                        class="search-input" 
                        placeholder="Cari nama lokasi..." 
                        value="{{ $search ?? '' }}"
                    >
                    <button type="submit" class="btn btn-primary">
                        🔍 Cari
                    </button>
                    @if($search)
                    <a href="{{ route('dashboard.lokasi.list') }}" class="btn btn-secondary">
                        ✕ Reset
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Results Info -->
    <div class="results-info">
        <p>Menampilkan <strong>{{ $lokasis->count() }}</strong> dari <strong>{{ $lokasis->total() }}</strong> Lokasi</p>
    </div>

    <!-- Lokasi Grid -->
    @if($lokasis->isEmpty())
        <div class="empty-state-card">
            <div class="empty-icon">📍</div>
            <h3>Tidak ada Lokasi ditemukan</h3>
            <p>{{ $search ? 'Coba kata kunci pencarian lain' : 'Belum ada data Lokasi' }}</p>
        </div>
    @else
        <div class="data-grid">
            @foreach($lokasis as $lokasi)
            <div class="data-card">
                <div class="data-card-icon">📍</div>
                <div class="data-card-content">
                    <h3 class="data-card-title">{{ $lokasi->nama_lokasi }}</h3>
                    @if($lokasi->alamat)
                    <p class="data-card-info">📮 {{ $lokasi->alamat }}</p>
                    @endif
                    @if($lokasi->keterangan)
                    <p class="data-card-description">{{ Str::limit($lokasi->keterangan, 100) }}</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="pagination-wrapper">
            {{ $lokasis->links() }}
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
.search-form {
    display: flex;
    gap: 1rem;
}

.search-input-group {
    display: flex;
    gap: 0.5rem;
    flex: 1;
}

.search-input {
    flex: 1;
    padding: 0.75rem 1rem;
    border: 2px solid var(--gray-300);
    border-radius: var(--radius-md);
    font-size: var(--font-size-base);
    transition: border-color var(--transition-fast);
}

.search-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.results-info {
    margin-bottom: 1.5rem;
    padding: 0.75rem 1rem;
    background: rgba(59, 130, 246, 0.05);
    border-radius: var(--radius-md);
    border-left: 4px solid var(--primary-color);
}

.results-info p {
    margin: 0;
    color: var(--gray-700);
    font-size: var(--font-size-sm);
}

.data-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.data-card {
    background: white;
    border: 2px solid var(--gray-200);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    transition: all var(--transition-fast);
    display: flex;
    gap: 1rem;
}

.data-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: var(--primary-color);
}

.data-card-icon {
    font-size: 3rem;
    flex-shrink: 0;
}

.data-card-content {
    flex: 1;
    min-width: 0;
}

.data-card-title {
    font-size: var(--font-size-lg);
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 0.5rem;
    line-height: 1.3;
}

.data-card-info {
    font-size: var(--font-size-sm);
    color: var(--gray-600);
    margin: 0.25rem 0;
}

.data-card-description {
    font-size: var(--font-size-sm);
    color: var(--gray-600);
    margin: 0.5rem 0 0 0;
    line-height: 1.5;
}

.empty-state-card {
    background: white;
    border: 2px dashed var(--gray-300);
    border-radius: var(--radius-lg);
    padding: 4rem 2rem;
    text-align: center;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state-card h3 {
    font-size: var(--font-size-xl);
    color: var(--gray-700);
    margin-bottom: 0.5rem;
}

.empty-state-card p {
    color: var(--gray-500);
}

.pagination-wrapper {
    display: flex;
    justify-content: center;
    margin-top: 2rem;
}
</style>
@endpush
