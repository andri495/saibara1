@extends('layouts.app')

@section('title', 'Daftar Objek')

@section('content')
<div class="page-container">
    <!-- Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">🗂️ Daftar Objek</h1>
            <p class="page-subtitle">Objek Retribusi Daerah</p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">
            ← Kembali ke Dashboard
        </a>
    </div>

    <!-- Search & Filter Bar -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-body">
            <form method="GET" action="{{ route('dashboard.objek.list') }}" class="filter-form">
                <!-- Search Input -->
                <div class="filter-row">
                    <div class="search-input-group">
                        <input 
                            type="text" 
                            name="search" 
                            class="search-input" 
                            placeholder="Cari nama atau kode objek..." 
                            value="{{ $search ?? '' }}"
                        >
                    </div>
                </div>

                <!-- Filters -->
                <div class="filter-grid">
                    <div class="filter-group">
                        <label class="filter-label">🏢 OPD</label>
                        <select name="opd_id" class="filter-select">
                            <option value="">Semua OPD</option>
                            @foreach($opds as $opd)
                            <option value="{{ $opd->id }}" {{ $opdFilter == $opd->id ? 'selected' : '' }}>
                                {{ $opd->nama_opd }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">📋 Jenis Retribusi</label>
                        <select name="retribusi_id" class="filter-select">
                            <option value="">Semua Retribusi</option>
                            @foreach($retribusis as $retribusi)
                            <option value="{{ $retribusi->id }}" {{ $retribusiFilter == $retribusi->id ? 'selected' : '' }}>
                                {{ $retribusi->nama_retribusi }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">📍 Lokasi</label>
                        <select name="lokasi_id" class="filter-select">
                            <option value="">Semua Lokasi</option>
                            @foreach($lokasis as $lokasi)
                            <option value="{{ $lokasi->id }}" {{ $lokasiFilter == $lokasi->id ? 'selected' : '' }}>
                                {{ $lokasi->nama_lokasi }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">📑 Rincian Retribusi</label>
                        <select name="rincian_id" class="filter-select">
                            <option value="">Semua Rincian</option>
                            @foreach($rincians as $rincian)
                            <option value="{{ $rincian->id }}" {{ $rincianFilter == $rincian->id ? 'selected' : '' }}>
                                {{ $rincian->rincian_retribusi }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        🔍 Terapkan Filter
                    </button>
                    @if($search || $opdFilter || $retribusiFilter || $lokasiFilter || $rincianFilter)
                    <a href="{{ route('dashboard.objek.list') }}" class="btn btn-secondary">
                        ✕ Reset Semua
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Results Info -->
    <div class="results-info">
        <p>Menampilkan <strong>{{ $objeks->count() }}</strong> dari <strong>{{ $objeks->total() }}</strong> Objek</p>
    </div>

    <!-- Objek Grid -->
    @if($objeks->isEmpty())
        <div class="empty-state-card">
            <div class="empty-icon">🗂️</div>
            <h3>Tidak ada Objek ditemukan</h3>
            <p>{{ $search ? 'Coba kata kunci pencarian lain' : 'Belum ada data Objek' }}</p>
        </div>
    @else
        <div class="objek-grid">
            @foreach($objeks as $objek)
            <div class="objek-card">
                <!-- Image -->
                <div class="objek-image-container">
                    @if($objek->images && trim($objek->images) !== '')
                        @php
                            // Handle different image URL formats
                            $imageUrl = $objek->images;
                            if (!str_starts_with($imageUrl, 'http') && !str_starts_with($imageUrl, '/')) {
                                $imageUrl = asset('storage/' . $imageUrl);
                            }
                        @endphp
                        <img 
                            src="{{ $imageUrl }}" 
                            alt="{{ $objek->sub_rincian_objek }}"
                            class="objek-image"
                            onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'objek-no-image\'><span class=\'objek-no-image-icon\'>🗂️</span><p>Gambar tidak dapat dimuat</p></div>';"
                        >
                    @else
                        <div class="objek-no-image">
                            <span class="objek-no-image-icon">🗂️</span>
                            <p>Tidak ada gambar</p>
                        </div>
                    @endif
                </div>

                <!-- Content -->
                <div class="objek-card-content">
                    <h3 class="objek-card-title">{{ $objek->sub_rincian_objek }}</h3>
                    
                    @if($objek->rincian_objek)
                    <p class="objek-card-code">{{ $objek->rincian_objek }}</p>
                    @endif

                    <!-- Price Badge -->
                    @if($objek->harga_objek)
                    <div class="objek-price-badge">
                        <span class="price-label">Harga:</span>
                        <span class="price-value">Rp {{ number_format($objek->harga_objek, 0, ',', '.') }}</span>
                        @if($objek->satuan)
                        <span class="price-unit">/ {{ $objek->satuan->nama_satuan ?? 'unit' }}</span>
                        @endif
                    </div>
                    @endif

                    <!-- Metadata Grid -->
                    <div class="objek-meta-grid">
                        @if($objek->opd)
                        <div class="objek-meta-item">
                            <span class="objek-meta-label">🏢 OPD</span>
                            <span class="objek-meta-value">{{ $objek->opd->nama_opd }}</span>
                        </div>
                        @endif

                        @if($objek->retribusi)
                        <div class="objek-meta-item">
                            <span class="objek-meta-label">📋 Jenis Retribusi</span>
                            <span class="objek-meta-value">{{ $objek->retribusi->nama_retribusi }}</span>
                        </div>
                        @endif

                        @if($objek->rincianRetribusi)
                        <div class="objek-meta-item">
                            <span class="objek-meta-label">� Rincian</span>
                            <span class="objek-meta-value">{{ $objek->rincianRetribusi->rincian_retribusi }}</span>
                        </div>
                        @endif

                        @if($objek->lokasi)
                        <div class="objek-meta-item">
                            <span class="objek-meta-label">📍 Lokasi</span>
                            <span class="objek-meta-value">{{ $objek->lokasi->nama_lokasi }}</span>
                        </div>
                        @endif

                        @if($objek->typeObject)
                        <div class="objek-meta-item">
                            <span class="objek-meta-label">🗂️ Tipe</span>
                            <span class="objek-meta-value">{{ $objek->typeObject->type ?? 'Type ' . $objek->typeObject->id }}</span>
                        </div>
                        @endif

                        @if($objek->statusObjek)
                        <div class="objek-meta-item">
                            <span class="objek-meta-label">✅ Status</span>
                            <span class="objek-meta-value">{{ $objek->statusObjek->status_objek ?? 'Status ' . $objek->statusObjek->id }}</span>
                        </div>
                        @endif
                    </div>

                    @if($objek->uraian_objek)
                    <p class="objek-card-description">{{ Str::limit($objek->uraian_objek, 100) }}</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="pagination-wrapper">
            {{ $objeks->links() }}
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
.filter-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.filter-row {
    display: flex;
    gap: 0.5rem;
}

.search-input-group {
    flex: 1;
}

.search-input {
    width: 100%;
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

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-label {
    font-size: var(--font-size-sm);
    font-weight: 600;
    color: var(--gray-700);
}

.filter-select {
    padding: 0.75rem 1rem;
    border: 2px solid var(--gray-300);
    border-radius: var(--radius-md);
    font-size: var(--font-size-base);
    background: white;
    cursor: pointer;
    transition: border-color var(--transition-fast);
}

.filter-select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.filter-select:hover {
    border-color: var(--gray-400);
}

.filter-actions {
    display: flex;
    gap: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid var(--gray-200);
}

@media (max-width: 768px) {
    .filter-grid {
        grid-template-columns: 1fr;
    }
}

.filter-select:disabled {
    background-color: var(--gray-100);
    cursor: not-allowed;
    opacity: 0.6;
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

.objek-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.objek-card {
    background: white;
    border: 2px solid var(--gray-200);
    border-radius: var(--radius-lg);
    overflow: hidden;
    transition: all var(--transition-fast);
}

.objek-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-xl);
    border-color: var(--primary-color);
}

.objek-image-container {
    width: 100%;
    height: 200px;
    background: var(--gray-100);
    position: relative;
    overflow: hidden;
}

.objek-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.objek-no-image {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--gray-100) 0%, var(--gray-200) 100%);
}

.objek-no-image-icon {
    font-size: 3rem;
    opacity: 0.3;
    margin-bottom: 0.5rem;
}

.objek-no-image p {
    color: var(--gray-500);
    font-size: var(--font-size-sm);
    margin: 0;
}

.objek-card-content {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.objek-card-title {
    font-size: var(--font-size-lg);
    font-weight: 700;
    color: var(--gray-900);
    margin: 0;
    line-height: 1.3;
}

.objek-card-code {
    font-size: var(--font-size-sm);
    color: var(--gray-600);
    margin: 0;
    font-family: monospace;
    background: var(--gray-100);
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius-sm);
    display: inline-block;
    width: fit-content;
}

.objek-price-badge {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
    padding: 0.75rem;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0.05) 100%);
    border-left: 3px solid var(--primary-color);
    border-radius: var(--radius-md);
    margin: 0.5rem 0;
}

.price-label {
    font-size: var(--font-size-sm);
    color: var(--gray-600);
    font-weight: 500;
}

.price-value {
    font-size: var(--font-size-lg);
    font-weight: 700;
    color: var(--primary-color);
}

.price-unit {
    font-size: var(--font-size-sm);
    color: var(--gray-500);
}

.objek-meta-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.5rem;
    padding: 0.75rem;
    background: var(--gray-50);
    border-radius: var(--radius-md);
}

.objek-meta-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.objek-meta-label {
    font-size: var(--font-size-xs);
    color: var(--gray-500);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.objek-meta-value {
    font-size: var(--font-size-sm);
    color: var(--gray-800);
    font-weight: 500;
    line-height: 1.4;
}

.objek-card-description {
    font-size: var(--font-size-sm);
    margin: 0;
    line-height: 1.5;
    padding-top: 0.75rem;
    border-top: 1px solid var(--gray-200);
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const opdSelect = document.querySelector('select[name="opd_id"]');
    const lokasiSelect = document.querySelector('select[name="lokasi_id"]');
    const retribusiSelect = document.querySelector('select[name="retribusi_id"]');
    const rincianSelect = document.querySelector('select[name="rincian_id"]');

    // Store original options
    const originalLokasiOptions = Array.from(lokasiSelect.options).map(opt => ({
        value: opt.value,
        text: opt.text
    }));
    const originalRincianOptions = Array.from(rincianSelect.options).map(opt => ({
        value: opt.value,
        text: opt.text
    }));

    // Hierarchical Filter 1: OPD → Lokasi
    opdSelect.addEventListener('change', function() {
        const opdId = this.value;
        const currentLokasiValue = lokasiSelect.value;

        if (!opdId) {
            // Reset to all locations
            lokasiSelect.innerHTML = '';
            originalLokasiOptions.forEach(opt => {
                const option = new Option(opt.text, opt.value);
                lokasiSelect.add(option);
            });
            lokasiSelect.value = currentLokasiValue;
            return;
        }

        // Fetch filtered locations
        fetch(`{{ route('dashboard.ajax.lokasi') }}?opd_id=${opdId}`)
            .then(response => response.json())
            .then(data => {
                lokasiSelect.innerHTML = '<option value="">Semua Lokasi</option>';
                data.forEach(lokasi => {
                    const option = new Option(lokasi.nama_lokasi, lokasi.id);
                    if (lokasi.id == currentLokasiValue) {
                        option.selected = true;
                    }
                    lokasiSelect.add(option);
                });
                
                // If current value not in filtered list, reset
                if (!data.find(l => l.id == currentLokasiValue)) {
                    lokasiSelect.value = '';
                }
            })
            .catch(error => {
                console.error('Error fetching locations:', error);
            });
    });

    // Hierarchical Filter 2: Retribusi → Rincian Retribusi
    retribusiSelect.addEventListener('change', function() {
        const retribusiId = this.value;
        const currentRincianValue = rincianSelect.value;

        if (!retribusiId) {
            // Reset to all rincian
            rincianSelect.innerHTML = '';
            originalRincianOptions.forEach(opt => {
                const option = new Option(opt.text, opt.value);
                rincianSelect.add(option);
            });
            rincianSelect.value = currentRincianValue;
            return;
        }

        // Fetch filtered rincian retribusi
        fetch(`{{ route('dashboard.ajax.rincian') }}?retribusi_id=${retribusiId}`)
            .then(response => response.json())
            .then(data => {
                rincianSelect.innerHTML = '<option value="">Semua Rincian</option>';
                data.forEach(rincian => {
                    const option = new Option(rincian.rincian_retribusi, rincian.id);
                    if (rincian.id == currentRincianValue) {
                        option.selected = true;
                    }
                    rincianSelect.add(option);
                });
                
                // If current value not in filtered list, reset
                if (!data.find(r => r.id == currentRincianValue)) {
                    rincianSelect.value = '';
                }
            })
            .catch(error => {
                console.error('Error fetching rincian retribusi:', error);
            });
    });

    // Trigger initial load if filters are pre-selected
    if (opdSelect.value) {
        opdSelect.dispatchEvent(new Event('change'));
    }
    if (retribusiSelect.value) {
        retribusiSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endpush
