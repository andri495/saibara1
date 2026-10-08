@extends('layouts.app')
@section('title', 'Edit Lokasi Objek')
@section('content')
<div class="page-container glass-page">
    <div class="page-header">
        <div class="page-title-wrapper">
            <div class="title-icon-wrap"><span class="title-icon">📍</span></div>
            <div>
                <h1 class="page-title">Edit Lokasi Objek</h1>
                <p class="page-subtitle">Perbarui data lokasi objek retribusi</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('master.lokasi-objek.show', $data->id) }}" class="btn btn-ghost">👁️ Detail</a>
            <a href="{{ route('master.lokasi-objek.index') }}" class="btn btn-ghost">← Kembali</a>
        </div>
    </div>
    <div class="glass-card">
        <div class="glass-card-header">
            <span class="glass-card-icon">✏️</span>
            <h3 class="glass-card-title">Form Edit Lokasi</h3>
            <span class="badge-glass">ID #{{ $data->id }}</span>
        </div>
        <div class="glass-card-body">
            <form action="{{ route('master.lokasi-objek.update', $data->id) }}" method="POST" id="lokasiForm">
                @csrf
                @method('PUT')
                <div class="form-grid-2">
                    <div class="form-group-glass">
                        <label class="glass-label" for="nama_lokasi">Nama Lokasi <span class="required-star">*</span></label>
                        <input type="text" id="nama_lokasi" name="nama_lokasi"
                            class="glass-input @error('nama_lokasi') is-invalid @enderror"
                            value="{{ old('nama_lokasi', $data->nama_lokasi) }}"
                            placeholder="Contoh: Pasar Tradisional Tamin" required autofocus>
                        @error('nama_lokasi')<span class="glass-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group-glass">
                        <label class="glass-label" for="master_opd_id">OPD <span class="required-star">*</span></label>
                        <select id="master_opd_id" name="master_opd_id"
                            class="glass-input @error('master_opd_id') is-invalid @enderror" required>
                            <option value="">-- Pilih OPD --</option>
                            @foreach($opds as $opd)
                                @php $selected = old('master_opd_id', $data->master_opd_id) == $opd->id ? 'selected' : ''; @endphp
                                <option value="{{ $opd->id }}" {{ $selected }}>{{ $opd->nama_opd }}</option>
                            @endforeach
                        </select>
                        @error('master_opd_id')<span class="glass-error">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="glass-metadata">
                    <span>📅 Dibuat: {{ $data->created_at->format('d M Y H:i') }}</span>
                    <span>🔄 Diubah: {{ $data->updated_at->format('d M Y H:i') }}</span>
                </div>
                <div class="glass-form-actions">
                    <button type="submit" class="btn btn-primary-glass">💾 Update Lokasi</button>
                    <a href="{{ route('master.lokasi-objek.index') }}" class="btn btn-ghost">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@push('styles')
<style>
.glass-page{padding:1.5rem}
.glass-card{background:rgba(255,255,255,0.75);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(37,99,235,0.12);border-radius:1rem;box-shadow:0 8px 32px rgba(37,99,235,0.08);overflow:hidden}
.glass-card-header{display:flex;align-items:center;gap:.75rem;padding:1.25rem 1.5rem;border-bottom:1px solid rgba(37,99,235,0.08);background:rgba(255,255,255,0.5)}
.glass-card-icon{font-size:1.25rem}
.glass-card-title{font-size:1rem;font-weight:700;color:#1e40af;flex:1;margin:0}
.badge-glass{padding:.25rem .75rem;background:rgba(37,99,235,0.1);color:#2563eb;border-radius:9999px;font-size:.75rem;font-weight:600}
.glass-card-body{padding:1.5rem}
.form-grid-2{display:grid;grid-template-columns:repeat(2,1fr);gap:1.25rem;margin-bottom:1.25rem}
.form-group-glass{display:flex;flex-direction:column;gap:.5rem}
.glass-label{font-size:.8rem;font-weight:700;color:#1e40af;text-transform:uppercase;letter-spacing:.04em}
.required-star{color:#ef4444}
.glass-input{width:100%;padding:.75rem 1rem;background:rgba(255,255,255,0.8);border:1.5px solid rgba(37,99,235,0.2);border-radius:.625rem;font-size:.9rem;color:#1f2937;transition:all .2s;outline:none;box-sizing:border-box}
.glass-input:focus{border-color:#2563eb;background:rgba(255,255,255,0.95);box-shadow:0 0 0 3px rgba(37,99,235,0.1)}
.glass-input.is-invalid{border-color:#ef4444}
.glass-error{font-size:.78rem;color:#ef4444}
.glass-metadata{display:flex;gap:1.5rem;padding:.875rem 1.25rem;background:rgba(37,99,235,0.04);border-radius:.75rem;margin-bottom:1.25rem;font-size:.8rem;color:#6b7280;border:1px solid rgba(37,99,235,0.08)}
.glass-form-actions{display:flex;gap:.75rem;padding-top:1.25rem;border-top:1px solid rgba(37,99,235,0.1)}
.btn-primary-glass{display:inline-flex;align-items:center;gap:.5rem;padding:.675rem 1.5rem;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:.5rem;font-size:.875rem;font-weight:600;cursor:pointer;box-shadow:0 4px 15px rgba(37,99,235,0.3);transition:all .2s;text-decoration:none}
.btn-primary-glass:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(37,99,235,0.4)}
.btn-ghost{display:inline-flex;align-items:center;gap:.5rem;padding:.675rem 1.25rem;background:rgba(255,255,255,0.7);color:#374151;border:1px solid rgba(37,99,235,0.15);border-radius:.5rem;font-size:.875rem;font-weight:600;text-decoration:none;transition:all .2s}
.btn-ghost:hover{background:rgba(255,255,255,0.95)}
@media(max-width:768px){.form-grid-2{grid-template-columns:1fr}.glass-metadata{flex-direction:column;gap:.5rem}}
</style>
@endpush
