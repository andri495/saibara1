@extends('layouts.app')

@section('title', 'Edit Retribusi')

@section('content')
<div class="page-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title-wrapper">
            <h1 class="page-title">Edit Retribusi</h1>
            <p class="page-subtitle">Perbarui data retribusi</p>
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
            <h3 class="card-title">Form Edit Retribusi</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('master.retribusi.update', $retribusi->id) }}" method="POST" id="retribusiForm">
                @csrf
                @method('PUT')

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
                        value="{{ old('nama_retribusi', $retribusi->nama_retribusi) }}"
                        placeholder="Contoh: Retribusi Jasa Umum"
                        required
                        autofocus
                    >
                    @error('nama_retribusi')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                    <small class="form-help">Masukkan nama retribusi dengan jelas dan deskriptif</small>
                </div>

                <!-- Info Metadata -->
                <div class="metadata-info">
                    <div class="metadata-item">
                        <span class="metadata-label">Dibuat:</span>
                        <span class="metadata-value">{{ $retribusi->created_at->format('d M Y H:i') }}</span>
                    </div>
                    <div class="metadata-item">
                        <span class="metadata-label">Terakhir diubah:</span>
                        <span class="metadata-value">{{ $retribusi->updated_at->format('d M Y H:i') }}</span>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span class="icon">💾</span> Update
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

.metadata-info {
    background-color: var(--gray-50);
    border-radius: var(--radius-md);
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
    display: flex;
    gap: var(--spacing-xl);
}

.metadata-item {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xs);
}

.metadata-label {
    font-size: var(--font-size-xs);
    color: var(--gray-500);
    text-transform: uppercase;
    font-weight: 600;
}

.metadata-value {
    font-size: var(--font-size-sm);
    color: var(--gray-700);
}

.form-actions {
    display: flex;
    gap: var(--spacing-md);
    margin-top: var(--spacing-xl);
    padding-top: var(--spacing-lg);
    border-top: 1px solid var(--gray-200);
}

@media (max-width: 768px) {
    .metadata-info {
        flex-direction: column;
        gap: var(--spacing-md);
    }
    
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
