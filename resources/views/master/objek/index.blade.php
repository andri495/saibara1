@extends('layouts.app')

@section('title', 'Master Objek')

@section('content')
<div class="page-container">
    <div class="page-header">
        <div class="page-title-wrapper">
            <h1 class="page-title">Master Objek</h1>
            <p class="page-subtitle">Kelola data Objek Retribusi</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('master.objek.create') }}" class="btn btn-primary">
                <span class="icon">➕</span> Tambah Objek
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Objek</h3>
        </div>

        <div class="card-body">
            <!-- Filters -->
            <form method="GET" action="{{ route('master.objek.index') }}" class="filter-form" id="filterForm">
                <div class="filter-grid">
                    <div class="filter-item">
                        <label class="filter-label">🏢 OPD</label>
                        <select name="opd" id="opdFilter" class="filter-select">
                            <option value="">Semua OPD</option>
                            @foreach($opds as $opd)
                            <option value="{{ $opd->id }}" {{ $opdFilter == $opd->id ? 'selected' : '' }}>
                                {{ $opd->nama_opd }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-item">
                        <label class="filter-label">📍 Lokasi</label>
                        <select name="lokasi" id="lokasiFilter" class="filter-select">
                            <option value="">Semua Lokasi</option>
                            @foreach($lokasis as $lokasi)
                            <option value="{{ $lokasi->id }}" {{ $lokasiFilter == $lokasi->id ? 'selected' : '' }}>
                                {{ $lokasi->nama_lokasi }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-item">
                        <label class="filter-label">📋 Retribusi</label>
                        <select name="retribusi" id="retribusiFilter" class="filter-select">
                            <option value="">Semua Retribusi</option>
                            @foreach($retribusis as $retribusi)
                            <option value="{{ $retribusi->id }}" {{ $retribusiFilter == $retribusi->id ? 'selected' : '' }}>
                                {{ $retribusi->nama_retribusi }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-item">
                        <label class="filter-label">📑 Rincian</label>
                        <select name="rincian" id="rincianFilter" class="filter-select">
                            <option value="">Semua Rincian</option>
                            @foreach($rincians as $rincian)
                            <option value="{{ $rincian->id }}" {{ $rincianFilter == $rincian->id ? 'selected' : '' }}>
                                {{ $rincian->rincian_retribusi }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-item">
                        <label class="filter-label">🔍 Cari</label>
                        <input type="text" name="search" class="filter-input" 
                               placeholder="Cari objek..." value="{{ $search ?? '' }}">
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                        @if($opdFilter || $lokasiFilter || $retribusiFilter || $rincianFilter || $search)
                        <a href="{{ route('master.objek.index') }}" class="btn btn-secondary btn-sm">Reset</a>
                        @endif
                    </div>
                </div>
            </form>

            <hr style="margin: 1.5rem 0;">
            
            <!-- Results -->
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if(isset($data) && $data->count() > 0)
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="60">No</th>
                                <th>Nama Objek</th>
                                <th>OPD</th>
                                <th>Retribusi</th>
                                <th>Lokasi</th>
                                <th>Harga</th>
                                <th>Status</th>
                                <th width="200" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $index => $item)
                                <tr>
                                    <td>{{ $data->firstItem() + $index }}</td>
                                    <td>
                                        <strong>{{ $item->sub_rincian_objek }}</strong>
                                        @if($item->rincian_objek)
                                        <br><small class="text-muted">{{ $item->rincian_objek }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $item->opd->nama_opd ?? '-' }}</td>
                                    <td>{{ $item->retribusi->nama_retribusi ?? '-' }}</td>
                                    <td>{{ $item->lokasi->nama_lokasi ?? '-' }}</td>
                                    <td>Rp {{ number_format($item->harga_objek, 0, ',', '.') }}</td>
                                    <td>
                                        @if($item->statusObjek)
                                        <span class="badge badge-info">{{ $item->statusObjek->status_objek }}</span>
                                        @else
                                        -
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="action-buttons">
                                            <a href="{{ route('master.objek.show', $item->id) }}" 
                                               class="btn btn-sm btn-info" title="Lihat Detail">👁️</a>
                                            <a href="{{ route('master.objek.edit', $item->id) }}" 
                                               class="btn btn-sm btn-warning" title="Edit">✏️</a>
                                            <form action="{{ route('master.objek.destroy', $item->id) }}" 
                                                  method="POST" style="display: inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-error" 
                                                        title="Hapus" onclick="return confirm('Yakin ingin menghapus?')">🗑️</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="pagination-wrapper">
                    {{ $data->appends([
                        'search' => $search,
                        'opd' => $opdFilter,
                        'lokasi' => $lokasiFilter,
                        'retribusi' => $retribusiFilter,
                        'rincian' => $rincianFilter
                    ])->links() }}
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-icon">🗂️</div>
                    <h3>Belum Ada Data</h3>
                    <p>Belum ada data Objek. Klik tombol "Tambah Objek" untuk menambahkan data baru.</p>
                    <a href="{{ route('master.objek.create') }}" class="btn btn-primary">Tambah Objek Pertama</a>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const opdFilter = document.getElementById('opdFilter');
    const lokasiFilter = document.getElementById('lokasiFilter');
    const retribusiFilter = document.getElementById('retribusiFilter');
    const rincianFilter = document.getElementById('rincianFilter');

    // Store current selected values
    const currentLokasiId = lokasiFilter ? lokasiFilter.value : null;
    const currentRincianId = rincianFilter ? rincianFilter.value : null;

    // Filter Lokasi by OPD
    if (opdFilter && lokasiFilter) {
        opdFilter.addEventListener('change', function() {
            const opdId = this.value;
            const selectedLokasiId = lokasiFilter.value;
            
            // Reset lokasi dropdown
            lokasiFilter.innerHTML = '<option value="">Semua Lokasi</option>';
            
            if (opdId) {
                // Fetch lokasi by OPD
                fetch(`{{ route('master.objek.ajax.lokasi') }}?opd_id=${opdId}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(lokasi => {
                            const option = document.createElement('option');
                            option.value = lokasi.id;
                            option.textContent = lokasi.nama_lokasi;
                            if (lokasi.id == selectedLokasiId) {
                                option.selected = true;
                            }
                            lokasiFilter.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error fetching lokasi:', error));
            }
        });
    }

    // Filter Rincian by Retribusi
    if (retribusiFilter && rincianFilter) {
        retribusiFilter.addEventListener('change', function() {
            const retribusiId = this.value;
            const selectedRincianId = rincianFilter.value;
            
            // Reset rincian dropdown
            rincianFilter.innerHTML = '<option value="">Semua Rincian</option>';
            
            if (retribusiId) {
                // Fetch rincian by Retribusi
                fetch(`{{ route('master.objek.ajax.rincian') }}?retribusi_id=${retribusiId}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(rincian => {
                            const option = document.createElement('option');
                            option.value = rincian.id;
                            option.textContent = rincian.rincian_retribusi;
                            if (rincian.id == selectedRincianId) {
                                option.selected = true;
                            }
                            rincianFilter.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error fetching rincian:', error));
            }
        });
    }
});
</script>
@endpush

@push('styles')
<style>
.filter-form {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    align-items: end;
}

.filter-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #495057;
}

.filter-select,
.filter-input {
    padding: 0.5rem;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 0.875rem;
}

.filter-select:focus,
.filter-input:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.filter-actions {
    display: flex;
    gap: 0.5rem;
    align-items: flex-end;
}

@media (max-width: 768px) {
    .filter-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush
@endsection
