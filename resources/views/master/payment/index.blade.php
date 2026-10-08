@extends('layouts.app')

@section('title', 'Master Tipe Pembayaran')

@section('content')
<div class="page-container">
    <div class="page-header">
        <div class="page-title-wrapper">
            <h1 class="page-title">Master Tipe Pembayaran</h1>
            <p class="page-subtitle">Kelola data tipe pembayaran retribusi</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('master.payment.create') }}" class="btn btn-primary">
                <span class="icon">➕</span> Tambah Tipe Pembayaran
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Tipe Pembayaran</h3>
            <div class="card-tools">
                <div class="search-box">
                    <input type="text" id="searchInput" class="search-input" placeholder="Cari tipe pembayaran...">
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
                                <th>Tipe Pembayaran</th>
                                <th>Tanggal Dibuat</th>
                                <th width="200" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $index => $item)
                                <tr>
                                    <td>{{ $data->firstItem() + $index }}</td>
                                    <td><strong>{{ $item->tipe_pembayaran ?? '-' }}</strong></td>
                                    <td>{{ $item->created_at->format('d M Y') }}</td>
                                    <td class="text-center">
                                        <div class="action-buttons">
                                            <a href="{{ route('master.payment.show', $item->id) }}" class="btn btn-sm btn-info" title="Lihat Detail">👁️</a>
                                            <a href="{{ route('master.payment.edit', $item->id) }}" class="btn btn-sm btn-warning" title="Edit">✏️</a>
                                            <form action="{{ route('master.payment.destroy', $item->id) }}" method="POST" style="display: inline-block;">
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
                    <div class="empty-icon">💳</div>
                    <h3>Belum Ada Data</h3>
                    <p>Belum ada data tipe pembayaran. Klik tombol "Tambah Tipe Pembayaran" untuk menambahkan data baru.</p>
                    <a href="{{ route('master.payment.create') }}" class="btn btn-primary">Tambah Tipe Pembayaran Pertama</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
