@extends('layouts.app')

@section('title', 'Tambah Retribusi')

@section('content')
<div class="page-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title-wrapper">
            <h1 class="page-title">Tambah Retribusi</h1>
            <p class="page-subtitle">Tambahkan data retribusi baru</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('master.retribusi.index') }}" class="btn btn-secondary">
                <span class="icon">←</span> Kembali
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Form Retribusi</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('master.retribusi.store') }}" method="POST" id="retribusiForm">
                @csrf

                <!-- Nama Retribusi -->
                <div class="form-group">
                    <label for="nama_retribusi" class="form-label">
                        Nama Retribusi <span class="text-error">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="nama_retribusi" 
                        name="nama_retribusi" 
                        class="form-input @error('nama_retribusi') is-invalid @enderror" 
                        value="{{ old('nama_retribusi') }}"
                        placeholder="Contoh: Retribusi Jasa Umum"
                        required
                        autofocus
                    >
                    @error('nama_retribusi')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                    <small class="form-help">Masukkan nama retribusi dengan jelas dan deskriptif</small>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span class="icon">💾</span> Simpan
                    </button>
                    <a href="{{ route('master.retribusi.index') }}" class="btn btn-secondary">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.form-help {
    display: block;
    margin-top: var(--spacing-xs);
    font-size: var(--font-size-sm);
    color: var(--gray-500);
}

.text-error {
    color: var(--error-color);
}

.form-actions {
    display: flex;
    gap: var(--spacing-md);
    margin-top: var(--spacing-xl);
    padding-top: var(--spacing-lg);
    border-top: 1px solid var(--gray-200);
}

@media (max-width: 768px) {
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
    }
}
</style>
@endpush

@push('scripts')
<script>
// Form validation
document.getElementById('retribusiForm').addEventListener('submit', function(e) {
    const namaRetribusi = document.getElementById('nama_retribusi').value.trim();
    
    if (!namaRetribusi) {
        e.preventDefault();
        showNotification('Nama retribusi harus diisi', 'error');
        document.getElementById('nama_retribusi').focus();
        return false;
    }
    
    if (namaRetribusi.length < 3) {
        e.preventDefault();
        showNotification('Nama retribusi minimal 3 karakter', 'error');
        document.getElementById('nama_retribusi').focus();
        return false;
    }
});
</script>
@endpush
