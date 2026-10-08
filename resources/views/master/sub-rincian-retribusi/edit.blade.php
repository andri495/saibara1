@extends('layouts.app')
@section('title', 'Edit Sub Rincian Retribusi')
@section('content')
<div class="page-container glass-page">
    <div class="page-header">
        <div class="page-title-wrapper">
            <div class="title-icon-wrap"><span class="title-icon">📄</span></div>
            <div>
                <h1 class="page-title">Edit Sub Rincian Retribusi</h1>
                <p class="page-subtitle">Perbarui data sub rincian retribusi</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('master.sub-rincian-retribusi.show', $data->id) }}" class="btn-ghost">👁️ Detail</a>
            <a href="{{ route('master.sub-rincian-retribusi.index') }}" class="btn-ghost">← Kembali</a>
        </div>
    </div>
    <div class="glass-card">
        <div class="glass-card-header">
            <span class="glass-card-icon">✏️</span>
            <h3 class="glass-card-title">Form Edit Sub Rincian Retribusi</h3>
            <span class="badge-glass">ID #{{ $data->id }}</span>
        </div>
        <div class="glass-card-body">
            <form action="{{ route('master.sub-rincian-retribusi.update', $data->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-sections">
                    <!-- Section: Retribusi Hierarchy -->
                    <div class="form-section">
                        <h4 class="section-title">📋 Hierarki Retribusi</h4>
                        <div class="form-grid-2">
                            <div class="form-group-glass">
                                <label class="glass-label" for="master_tipe_retribusi_id">Jenis Retribusi <span class="required-star">*</span></label>
                                <select id="master_tipe_retribusi_id" name="master_tipe_retribusi_id"
                                    class="glass-input" required>
                                    <option value="">-- Pilih Jenis Retribusi --</option>
                                    @foreach($retribusis as $r)
                                        @php $sel = old('master_tipe_retribusi_id', $data->rincianRetribusi->master_tipe_retribusi_id ?? '') == $r->id ? 'selected' : ''; @endphp
                                        <option value="{{ $r->id }}" {{ $sel }}>{{ $r->nama_retribusi }}</option>
                                    @endforeach
                                </select>
                                <small class="glass-help">Pilih jenis retribusi terlebih dahulu</small>
                            </div>
                            <div class="form-group-glass">
                                <label class="glass-label" for="master_rincian_retribusi_id">Rincian Retribusi <span class="required-star">*</span></label>
                                <select id="master_rincian_retribusi_id" name="master_rincian_retribusi_id"
                                    class="glass-input @error('master_rincian_retribusi_id') is-invalid @enderror"
                                    data-selected="{{ old('master_rincian_retribusi_id', $data->master_rincian_retribusi_id) }}" required>
                                    <option value="">-- Pilih Rincian --</option>
                                </select>
                                @error('master_rincian_retribusi_id')<span class="glass-error">{{ $message }}</span>@enderror
                                <small class="glass-help">Akan muncul setelah pilih jenis retribusi</small>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Sub Rincian -->
                    <div class="form-section">
                        <h4 class="section-title">📝 Detail Sub Rincian</h4>
                        <div class="form-grid-1">
                            <div class="form-group-glass">
                                <label class="glass-label" for="nama_sub_rincian_retribusi">Nama Sub Rincian <span class="required-star">*</span></label>
                                <input type="text" id="nama_sub_rincian_retribusi" name="nama_sub_rincian_retribusi"
                                    class="glass-input @error('nama_sub_rincian_retribusi') is-invalid @enderror"
                                    value="{{ old('nama_sub_rincian_retribusi', $data->nama_sub_rincian_retribusi) }}"
                                    placeholder="Nama sub rincian retribusi" required autofocus>
                                @error('nama_sub_rincian_retribusi')<span class="glass-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="glass-metadata">
                    <span>📅 Dibuat: {{ $data->created_at->format('d M Y H:i') }}</span>
                    <span>🔄 Diubah: {{ $data->updated_at->format('d M Y H:i') }}</span>
                </div>
                <div class="glass-form-actions">
                    <button type="submit" class="btn-primary-glass">💾 Update</button>
                    <a href="{{ route('master.sub-rincian-retribusi.index') }}" class="btn-ghost">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@push('styles')
<style>
.glass-page{padding:1.5rem}.glass-card{background:rgba(255,255,255,0.75);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(37,99,235,0.12);border-radius:1rem;box-shadow:0 8px 32px rgba(37,99,235,0.08);overflow:hidden}
.glass-card-header{display:flex;align-items:center;gap:.75rem;padding:1.25rem 1.5rem;border-bottom:1px solid rgba(37,99,235,0.08);background:rgba(255,255,255,0.5)}
.glass-card-icon{font-size:1.25rem}.glass-card-title{font-size:1rem;font-weight:700;color:#1e40af;flex:1;margin:0}
.badge-glass{padding:.25rem .75rem;background:rgba(37,99,235,0.1);color:#2563eb;border-radius:9999px;font-size:.75rem;font-weight:600}
.glass-card-body{padding:1.5rem}
.form-sections{display:flex;flex-direction:column;gap:1.5rem}
.form-section{padding:1.25rem;background:rgba(37,99,235,0.02);border:1px solid rgba(37,99,235,0.08);border-radius:.75rem}
.section-title{font-size:.95rem;font-weight:700;color:#1e40af;margin:0 0 1rem 0;padding-bottom:.75rem;border-bottom:2px solid rgba(37,99,235,0.15)}
.form-grid-1{display:grid;grid-template-columns:1fr;gap:1.25rem}
.form-grid-2{display:grid;grid-template-columns:repeat(2,1fr);gap:1.25rem}
.form-group-glass{display:flex;flex-direction:column;gap:.5rem}
.glass-label{font-size:.8rem;font-weight:700;color:#1e40af;text-transform:uppercase;letter-spacing:.04em}.required-star{color:#ef4444}
.glass-input{width:100%;padding:.75rem 1rem;background:rgba(255,255,255,0.8);border:1.5px solid rgba(37,99,235,0.2);border-radius:.625rem;font-size:.9rem;color:#1f2937;transition:all .2s;outline:none;box-sizing:border-box}
.glass-input:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,0.1)}.glass-input.is-invalid{border-color:#ef4444}.glass-error{font-size:.78rem;color:#ef4444}
.glass-help{font-size:.78rem;color:#2563eb;font-weight:500}
.glass-metadata{display:flex;gap:1.5rem;padding:.875rem 1.25rem;background:rgba(37,99,235,0.04);border-radius:.75rem;margin-bottom:1.25rem;font-size:.8rem;color:#6b7280;border:1px solid rgba(37,99,235,0.08)}
.glass-form-actions{display:flex;gap:.75rem;padding-top:1.25rem;border-top:1px solid rgba(37,99,235,0.1)}
.btn-primary-glass{display:inline-flex;align-items:center;gap:.5rem;padding:.675rem 1.5rem;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:.5rem;font-size:.875rem;font-weight:600;cursor:pointer;box-shadow:0 4px 15px rgba(37,99,235,0.3);transition:all .2s;text-decoration:none}
.btn-primary-glass:hover{transform:translateY(-1px)}.btn-ghost{display:inline-flex;align-items:center;gap:.5rem;padding:.675rem 1.25rem;background:rgba(255,255,255,0.7);color:#374151;border:1px solid rgba(37,99,235,0.15);border-radius:.5rem;font-size:.875rem;font-weight:600;text-decoration:none;transition:all .2s}
.btn-ghost:hover{background:rgba(255,255,255,0.95)}
@media(max-width:768px){.form-grid-2{grid-template-columns:1fr}.form-group-glass.span-2{grid-column:span 1}.glass-metadata{flex-direction:column;gap:.5rem}}
</style>
@endpush
@push('scripts')
<script>
function loadRincian(retribusiId, selectedId = null) {
    const rincianSelect = document.getElementById('master_rincian_retribusi_id');
    rincianSelect.innerHTML = '<option value="">-- Pilih Rincian --</option>';
    
    if (retribusiId) {
        fetch(`{{ route('retribution-target.ajax.rincian') }}?retribusi_id=${retribusiId}`)
            .then(response => response.json())
            .then(data => {
                data.forEach(rincian => {
                    const option = document.createElement('option');
                    option.value = rincian.id;
                    option.textContent = rincian.rincian_retribusi;
                    if (selectedId && rincian.id == selectedId) {
                        option.selected = true;
                    }
                    rincianSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error loading rincian:', error));
    }
}

document.getElementById('master_tipe_retribusi_id').addEventListener('change', function() {
    loadRincian(this.value);
});

window.addEventListener('DOMContentLoaded', function() {
    const retribusiId = document.getElementById('master_tipe_retribusi_id').value;
    const rincianSelectedId = document.getElementById('master_rincian_retribusi_id').getAttribute('data-selected');
    if (retribusiId) {
        loadRincian(retribusiId, rincianSelectedId);
    }
});
</script>
@endpush
