@extends('layouts.app')

@section('title', 'Master Tipe Objek')

@section('content')
<div class="page-container">
    <div class="page-header">
        <div class="page-title-wrapper">
            <h1 class="page-title">Master Tipe Objek</h1>
            <p class="page-subtitle">Kelola data Tipe Objek Retribusi</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('master.type-object.create') }}" class="btn btn-primary">
                <span class="icon">➕</span> Tambah Tipe Objek
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Tipe Objek</h3>
            <div class="card-tools">
                <div class="search-box">
                    <input type="text" id="searchInput" class="search-input" placeholder="Cari Tipe Objek...">
                    <span class="search-icon">🔍</span>
                </div>
            </div>
        </div>

        <div class="card-body">
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
                                <th>Nama Tipe</th>
                                <th>Tanggal Dibuat</th>
                                <th width="200" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $index => $item)
                                <tr>
                                    <td>{{ $data->firstItem() + $index }}</td>
                                    <td><strong>{{ $item->type ?? '-' }}</strong></td>
                                    <td>{{ $item->created_at ? $item->created_at->format('d M Y') : '-' }}</td>
                                    <td class="text-center">
                                        <div class="action-buttons">
                                            <a href="{{ route('master.type-object.show', $item->id) }}" class="btn btn-sm btn-info" title="Lihat Detail">👁️</a>
                                            <a href="{{ route('master.type-object.edit', $item->id) }}" class="btn btn-sm btn-warning" title="Edit">✏️</a>
                                            <form action="{{ route('master.type-object.destroy', $item->id) }}" method="POST" style="display: inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-error" title="Hapus" onclick="return confirm('Yakin ingin menghapus?')">🗑️</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="pagination-wrapper">
                    {{ $data->links() }}
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-icon">🗂️</div>
                    <h3>Belum Ada Data</h3>
                    <p>Belum ada data Tipe Objek. Klik tombol "Tambah Tipe Objek" untuk menambahkan data baru.</p>
                    <a href="{{ route('master.type-object.create') }}" class="btn btn-primary">Tambah Tipe Objek Pertama</a>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('.data-table tbody tr');

    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();

            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
});
</script>
@endpush
@endsection
