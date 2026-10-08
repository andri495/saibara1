@extends('layouts.app')

@section('title', 'Master Rincian Retribusi')

@section('content')
<div class="page-container">
    <div class="page-header">
        <div class="page-title-wrapper">
            <h1 class="page-title">Master Rincian Retribusi</h1>
            <p class="page-subtitle">Kelola data rincian retribusi daerah</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('master.rincian-retribusi.create') }}" class="btn btn-primary">
                <span class="icon">➕</span> Tambah Rincian Retribusi
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Rincian Retribusi</h3>
            <div class="card-tools">
                <div class="search-box">
                    <input type="text" id="searchInput" class="search-input" placeholder="Cari rincian...">
                    <span class="search-icon">🔍</span>
                </div>
            </div>
        </div>

        <div class="card-body">
            @if(isset($data) && $data->count() > 0)
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="60">No</th>
                                <th>Nama Rincian</th>
                                <th>Retribusi</th>
                                <th>Tanggal Dibuat</th>
                                <th width="200" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $index => $item)
                                <tr>
                                    <td>{{ $data->firstItem() + $index }}</td>
                                    <td><strong>{{ $item->rincian_retribusi ?? '-' }}</strong></td>
                                    <td>{{ $item->retribusi->nama_retribusi ?? '-' }}</td>
                                    <td>{{ $item->created_at->format('d M Y') }}</td>
                                    <td class="text-center">
                                        <div class="action-buttons">
                                            <a href="{{ route('master.rincian-retribusi.show', $item->id) }}" class="btn btn-sm btn-info" title="Lihat Detail">👁️</a>
                                            <a href="{{ route('master.rincian-retribusi.edit', $item->id) }}" class="btn btn-sm btn-warning" title="Edit">✏️</a>
                                            <form action="{{ route('master.rincian-retribusi.destroy', $item->id) }}" method="POST" style="display: inline-block;">
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
                    <div class="empty-icon">📝</div>
                    <h3>Belum Ada Data</h3>
                    <p>Belum ada data rincian retribusi. Klik tombol "Tambah Rincian Retribusi" untuk menambahkan data baru.</p>
                    <a href="{{ route('master.rincian-retribusi.create') }}" class="btn btn-primary">Tambah Rincian Retribusi Pertama</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
