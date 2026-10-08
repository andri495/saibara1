@extends('layouts.app')

@section('title', 'Master Retribusi')

@section('content')
<div class="page-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title-wrapper">
            <h1 class="page-title">Master Retribusi</h1>
            <p class="page-subtitle">Kelola data master retribusi daerah</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('master.retribusi.create') }}" class="btn btn-primary">
                <span class="icon">➕</span> Tambah Retribusi
            </a>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Retribusi</h3>
            <div class="card-tools">
                <div class="search-box">
                    <input 
                        type="text" 
                        id="searchInput" 
                        class="search-input" 
                        placeholder="Cari retribusi..."
                    >
                    <span class="search-icon">🔍</span>
                </div>
            </div>
        </div>

        <div class="card-body">
            @if($retribusi->count() > 0)
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="60">No</th>
                                <th>Nama Retribusi</th>
                                <th>Tanggal Dibuat</th>
                                <th width="200" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            @foreach($retribusi as $index => $item)
                                <tr data-search="{{ strtolower($item->nama_retribusi) }}">
                                    <td>{{ $retribusi->firstItem() + $index }}</td>
                                    <td>
                                        <strong>{{ $item->nama_retribusi }}</strong>
                                    </td>
                                    <td>{{ $item->created_at->format('d M Y') }}</td>
                                    <td class="text-center">
                                        <div class="action-buttons">
                                            <a 
                                                href="{{ route('master.retribusi.show', $item->id) }}" 
                                                class="btn btn-sm btn-info"
                                                title="Lihat Detail"
                                            >
                                                👁️
                                            </a>
                                            <a 
                                                href="{{ route('master.retribusi.edit', $item->id) }}" 
                                                class="btn btn-sm btn-warning"
                                                title="Edit"
                                            >
                                                ✏️
                                            </a>
                                            <form 
                                                action="{{ route('master.retribusi.destroy', $item->id) }}" 
                                                method="POST" 
                                                class="delete-form"
                                                style="display: inline-block;"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button 
                                                    type="button" 
                                                    class="btn btn-sm btn-error delete-btn"
                                                    data-id="{{ $item->id }}"
                                                    data-name="{{ $item->nama_retribusi }}"
                                                    title="Hapus"
                                                >
                                                    🗑️
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination-wrapper">
                    {{ $retribusi->links() }}
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <h3>Belum Ada Data</h3>
                    <p>Belum ada data retribusi. Klik tombol "Tambah Retribusi" untuk menambahkan data baru.</p>
                    <a href="{{ route('master.retribusi.create') }}" class="btn btn-primary">
                        Tambah Retribusi Pertama
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/master/retribusi.js') }}"></script>
@endpush
