@extends('layouts.app')

@section('title', 'Edit Objek')

@section('content')
<div class="modern-form-container">
    <!-- Animated Background -->
    <div class="animated-bg">
        <div class="gradient-orb orb-1"></div>
        <div class="gradient-orb orb-2"></div>
        <div class="gradient-orb orb-3"></div>
    </div>

    <!-- Header Section -->
    <div class="form-header glass-card">
        <div class="header-content">
            <div class="icon-wrapper">
                <span class="header-icon">✏️</span>
            </div>
            <div>
                <h1 class="form-title">Edit Objek Retribusi</h1>
                <p class="form-subtitle">Perbarui informasi objek retribusi dengan data yang akurat</p>
            </div>
        </div>
        <a href="{{ route('master.objek.index') }}" class="btn-back glass-btn">
            <span>←</span> Kembali
        </a>
    </div>

    <!-- Form Section -->
    <form action="{{ route('master.objek.update', $data->id) }}" method="POST" class="modern-form">
        @csrf
        @method('PUT')
        
        <!-- Section 1: Informasi Dasar -->
        <div class="form-section glass-card">
            <div class="section-header">
                <div class="section-icon">📋</div>
                <h2 class="section-title">Informasi Dasar</h2>
            </div>
            
            <div class="form-grid">
                <div class="input-group">
                    <label class="modern-label">
                        <span class="label-icon">🏢</span>
                        <span>OPD</span>
                        <span class="required">*</span>
                    </label>
                    <select name="master_opd_id" class="modern-select @error('master_opd_id') error @enderror" required>
                        <option value="">Pilih OPD</option>
                        @foreach($opds as $opd)
                        <option value="{{ $opd->id }}" {{ old('master_opd_id', $data->master_opd_id) == $opd->id ? 'selected' : '' }}>
                            {{ $opd->nama_opd }}
                        </option>
                        @endforeach
                    </select>
                    @error('master_opd_id')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="input-group">
                    <label class="modern-label">
                        <span class="label-icon">📋</span>
                        <span>Jenis Retribusi</span>
                        <span class="required">*</span>
                    </label>
                    <select name="master_retribusi_id" class="modern-select @error('master_retribusi_id') error @enderror" required>
                        <option value="">Pilih Retribusi</option>
                        @foreach($retribusis as $retribusi)
                        <option value="{{ $retribusi->id }}" {{ old('master_retribusi_id', $data->master_retribusi_id) == $retribusi->id ? 'selected' : '' }}>
                            {{ $retribusi->nama_retribusi }}
                        </option>
                        @endforeach
                    </select>
                    @error('master_retribusi_id')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="input-group">
                    <label class="modern-label">
                        <span class="label-icon">📍</span>
                        <span>Lokasi</span>
                        <span class="required">*</span>
                    </label>
                    <select name="master_lokasi_id" class="modern-select @error('master_lokasi_id') error @enderror" required>
                        <option value="">Pilih Lokasi</option>
                        @foreach($lokasis as $lokasi)
                        <option value="{{ $lokasi->id }}" {{ old('master_lokasi_id', $data->master_lokasi_id) == $lokasi->id ? 'selected' : '' }}>
                            {{ $lokasi->nama_lokasi }}
                        </option>
                        @endforeach
                    </select>
                    @error('master_lokasi_id')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="input-group">
                    <label class="modern-label">
                        <span class="label-icon">📑</span>
                        <span>Rincian Retribusi</span>
                        <span class="required">*</span>
                    </label>
                    <select name="master_rincian_retribusi_id" class="modern-select @error('master_rincian_retribusi_id') error @enderror" required>
                        <option value="">Pilih Rincian</option>
                        @foreach($rincians as $rincian)
                        <option value="{{ $rincian->id }}" {{ old('master_rincian_retribusi_id', $data->master_rincian_retribusi_id) == $rincian->id ? 'selected' : '' }}>
                            {{ $rincian->rincian_retribusi }}
                        </option>
                        @endforeach
                    </select>
                    @error('master_rincian_retribusi_id')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Section 2: Detail Objek -->
        <div class="form-section glass-card">
            <div class="section-header">
                <div class="section-icon">🏷️</div>
                <h2 class="section-title">Detail Objek</h2>
            </div>
            
            <div class="form-grid">
                <div class="input-group input-group-full">
                    <label class="modern-label">
                        <span class="label-icon">✏️</span>
                        <span>Nama Objek</span>
                        <span class="required">*</span>
                    </label>
                    <input type="text" name="sub_rincian_objek" 
                           class="modern-input @error('sub_rincian_objek') error @enderror" 
                           value="{{ old('sub_rincian_objek', $data->sub_rincian_objek) }}" 
                           placeholder="Masukkan nama objek retribusi" required>
                    @error('sub_rincian_objek')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="input-group">
                    <label class="modern-label">
                        <span class="label-icon">🔖</span>
                        <span>Kode/Rincian Objek</span>
                        <span class="required">*</span>
                    </label>
                    <input type="text" name="rincian_objek" 
                           class="modern-input @error('rincian_objek') error @enderror" 
                           value="{{ old('rincian_objek', $data->rincian_objek) }}" 
                           placeholder="Kode identifikasi" required>
                    @error('rincian_objek')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="input-group">
                    <label class="modern-label">
                        <span class="label-icon">🗂️</span>
                        <span>Tipe Objek</span>
                        <span class="required">*</span>
                    </label>
                    <select name="master_type_object_id" class="modern-select @error('master_type_object_id') error @enderror" required>
                        <option value="">Pilih Tipe</option>
                        @foreach($types as $type)
                        <option value="{{ $type->id }}" {{ old('master_type_object_id', $data->master_type_object_id) == $type->id ? 'selected' : '' }}>
                            {{ $type->type }}
                        </option>
                        @endforeach
                    </select>
                    @error('master_type_object_id')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="input-group">
                    <label class="modern-label">
                        <span class="label-icon">✅</span>
                        <span>Status</span>
                        <span class="required">*</span>
                    </label>
                    <select name="master_status_objek_id" class="modern-select @error('master_status_objek_id') error @enderror" required>
                        <option value="">Pilih Status</option>
                        @foreach($statuses as $status)
                        <option value="{{ $status->id }}" {{ old('master_status_objek_id', $data->master_status_objek_id) == $status->id ? 'selected' : '' }}>
                            {{ $status->status_objek }}
                        </option>
                        @endforeach
                    </select>
                    @error('master_status_objek_id')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="input-group input-group-full">
                    <label class="modern-label">
                        <span class="label-icon">📝</span>
                        <span>Uraian/Deskripsi</span>
                        <span class="required">*</span>
                    </label>
                    <textarea name="uraian_objek" rows="3" 
                              class="modern-textarea @error('uraian_objek') error @enderror" 
                              placeholder="Deskripsi lengkap objek retribusi" required>{{ old('uraian_objek', $data->uraian_objek) }}</textarea>
                    @error('uraian_objek')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Section 3: Harga & Satuan -->
        <div class="form-section glass-card">
            <div class="section-header">
                <div class="section-icon">💰</div>
                <h2 class="section-title">Harga & Satuan</h2>
            </div>
            
            <div class="form-grid">
                <div class="input-group">
                    <label class="modern-label">
                        <span class="label-icon">💵</span>
                        <span>Harga (IDR)</span>
                        <span class="required">*</span>
                    </label>
                    <div class="input-with-prefix">
                        <span class="input-prefix">Rp</span>
                        <input type="number" name="harga_objek" 
                               class="modern-input with-prefix @error('harga_objek') error @enderror" 
                               value="{{ old('harga_objek', $data->harga_objek) }}" 
                               min="0" step="0.01" required>
                    </div>
                    @error('harga_objek')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="input-group">
                    <label class="modern-label">
                        <span class="label-icon">💱</span>
                        <span>Harga Asing</span>
                        <span class="required">*</span>
                    </label>
                    <input type="number" name="harga_objek_asing" 
                           class="modern-input @error('harga_objek_asing') error @enderror" 
                           value="{{ old('harga_objek_asing', $data->harga_objek_asing) }}" 
                           min="0" step="0.01" required>
                    @error('harga_objek_asing')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="input-group">
                    <label class="modern-label">
                        <span class="label-icon">💰</span>
                        <span>Mata Uang</span>
                        <span class="required">*</span>
                    </label>
                    <select name="master_currency_id" class="modern-select @error('master_currency_id') error @enderror" required>
                        <option value="">Pilih Mata Uang</option>
                        @foreach($currencies as $currency)
                        <option value="{{ $currency->id }}" {{ old('master_currency_id', $data->master_currency_id) == $currency->id ? 'selected' : '' }}>
                            {{ $currency->currency_name }} ({{ $currency->currency }})
                        </option>
                        @endforeach
                    </select>
                    @error('master_currency_id')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="input-group">
                    <label class="modern-label">
                        <span class="label-icon">📏</span>
                        <span>Satuan</span>
                        <span class="required">*</span>
                    </label>
                    <select name="master_satuan_id" class="modern-select @error('master_satuan_id') error @enderror" required>
                        <option value="">Pilih Satuan</option>
                        @foreach($satuans as $satuan)
                        <option value="{{ $satuan->id }}" {{ old('master_satuan_id', $data->master_satuan_id) == $satuan->id ? 'selected' : '' }}>
                            {{ $satuan->nama_satuan }}
                        </option>
                        @endforeach
                    </select>
                    @error('master_satuan_id')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="input-group">
                    <label class="modern-label">
                        <span class="label-icon">👤</span>
                        <span>TPJ (Tempat Pembayaran Jasa)</span>
                        <span class="required">*</span>
                    </label>
                    <select name="master_tpj_id" class="modern-select @error('master_tpj_id') error @enderror" required>
                        <option value="">Pilih TPJ</option>
                        @foreach($tpjs as $tpj)
                        <option value="{{ $tpj->id }}" {{ old('master_tpj_id', $data->master_tpj_id) == $tpj->id ? 'selected' : '' }}>
                            {{ $tpj->nama_tpj }}
                        </option>
                        @endforeach
                    </select>
                    @error('master_tpj_id')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="input-group input-group-full">
                    <label class="modern-label">
                        <span class="label-icon">🖼️</span>
                        <span>URL Gambar</span>
                    </label>
                    <input type="text" name="images" 
                           class="modern-input @error('images') error @enderror" 
                           value="{{ old('images', $data->images) }}" 
                           placeholder="https://example.com/image.jpg">
                    @error('images')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Submit Section -->
        <div class="form-actions glass-card">
            <button type="submit" class="btn-submit">
                <span class="btn-icon">💾</span>
                <span>Update Objek</span>
                <span class="btn-shine"></span>
            </button>
            <a href="{{ route('master.objek.index') }}" class="btn-cancel">
                <span>Batal</span>
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const opdSelect = document.querySelector('select[name="master_opd_id"]');
    const lokasiSelect = document.querySelector('select[name="master_lokasi_id"]');
    const retribusiSelect = document.querySelector('select[name="master_retribusi_id"]');
    const rincianSelect = document.querySelector('select[name="master_rincian_retribusi_id"]');

    // Store current values
    const currentLokasiId = lokasiSelect ? lokasiSelect.value : null;
    const currentRincianId = rincianSelect ? rincianSelect.value : null;

    // Filter Lokasi by OPD
    if (opdSelect && lokasiSelect) {
        opdSelect.addEventListener('change', function() {
            const opdId = this.value;
            const selectedLokasiId = lokasiSelect.value;
            
            // Reset lokasi dropdown
            lokasiSelect.innerHTML = '<option value="">Pilih Lokasi</option>';
            
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
                            lokasiSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error fetching lokasi:', error));
            }
        });
    }

    // Filter Rincian by Retribusi
    if (retribusiSelect && rincianSelect) {
        retribusiSelect.addEventListener('change', function() {
            const retribusiId = this.value;
            const selectedRincianId = rincianSelect.value;
            
            // Reset rincian dropdown
            rincianSelect.innerHTML = '<option value="">Pilih Rincian</option>';
            
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
                            rincianSelect.appendChild(option);
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
/* Modern Form Container */
.modern-form-container {
    position: relative;
    min-height: 100vh;
    padding: 2rem;
    overflow: hidden;
}

/* Animated Background */
.animated-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 0;
    background: #ffffff;
    pointer-events: none;
}

.gradient-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(100px);
    opacity: 0.15;
    animation: float 20s ease-in-out infinite;
}

.orb-1 {
    width: 400px;
    height: 400px;
    background: linear-gradient(45deg, #f093fb 0%, #f5576c 100%);
    top: -100px;
    left: -100px;
    animation-delay: 0s;
}

.orb-2 {
    width: 500px;
    height: 500px;
    background: linear-gradient(45deg, #4facfe 0%, #00f2fe 100%);
    bottom: -150px;
    right: -150px;
    animation-delay: 7s;
}

.orb-3 {
    width: 350px;
    height: 350px;
    background: linear-gradient(45deg, #43e97b 0%, #38f9d7 100%);
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    animation-delay: 14s;
}

@keyframes float {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -30px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
}

/* Glass Card Effect */
.glass-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    position: relative;
    z-index: 1;
}

.glass-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

/* Form Header */
.form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 2rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.icon-wrapper {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.header-icon {
    font-size: 2.5rem;
}

.form-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.form-subtitle {
    color: #6b7280;
    margin: 0.5rem 0 0 0;
    font-size: 0.95rem;
}

.btn-back {
    padding: 0.75rem 1.5rem;
    color: #667eea;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: 2px solid #667eea;
}

.btn-back:hover {
    transform: translateX(-5px);
    background: #667eea;
    color: white;
}

/* Form Section */
.modern-form {
    max-width: 1200px;
    margin: 0 auto;
}

.form-section {
    padding: 2rem;
    margin-bottom: 2rem;
    animation: slideUp 0.5s ease forwards;
    opacity: 0;
}

.form-section:nth-child(1) { animation-delay: 0.1s; }
.form-section:nth-child(2) { animation-delay: 0.2s; }
.form-section:nth-child(3) { animation-delay: 0.3s; }
.form-section:nth-child(4) { animation-delay: 0.4s; }

@keyframes slideUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.section-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e5e7eb;
}

.section-icon {
    font-size: 2rem;
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.1));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

/* Form Grid */
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.input-group-full {
    grid-column: 1 / -1;
}

/* Input Group */
.input-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.modern-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #374151;
    font-size: 0.95rem;
}

.label-icon {
    font-size: 1.2rem;
}

.required {
    color: #ff6b6b;
    font-weight: 700;
}

/* Modern Inputs */
.modern-input,
.modern-select,
.modern-textarea {
    padding: 0.875rem 1.25rem;
    background: #ffffff;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    color: #1f2937;
    font-size: 1rem;
    transition: all 0.3s ease;
    width: 100%;
    max-width: 100%;
}

.modern-select {
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
    padding-right: 2.5rem;
}

.modern-input::placeholder,
.modern-textarea::placeholder {
    color: #9ca3af;
}

.modern-input:focus,
.modern-select:focus,
.modern-textarea:focus {
    outline: none;
    background: #ffffff;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.modern-select option {
    background: #ffffff;
    color: #1f2937;
    white-space: normal;
    overflow: hidden;
    text-overflow: ellipsis;
}

.modern-textarea {
    resize: vertical;
    min-height: 100px;
}

/* Input with Prefix */
.input-with-prefix {
    position: relative;
    display: flex;
    align-items: center;
}

.input-prefix {
    position: absolute;
    left: 1.25rem;
    color: #6b7280;
    font-weight: 600;
    pointer-events: none;
}

.modern-input.with-prefix {
    padding-left: 3.5rem;
}

/* Error State */
.modern-input.error,
.modern-select.error,
.modern-textarea.error {
    border-color: #ff6b6b;
    background: rgba(255, 107, 107, 0.1);
}

.error-message {
    color: #ff6b6b;
    font-size: 0.875rem;
    margin-top: 0.25rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.error-message::before {
    content: "⚠️";
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 1rem;
    padding: 2rem;
    justify-content: center;
}

.btn-submit {
    position: relative;
    padding: 1rem 3rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 30px rgba(102, 126, 234, 0.6);
}

.btn-submit:active {
    transform: translateY(0);
}

.btn-icon {
    font-size: 1.5rem;
}

.btn-shine {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s;
}

.btn-submit:hover .btn-shine {
    left: 100%;
}

.btn-cancel {
    padding: 1rem 2rem;
    background: #ffffff;
    color: #6b7280;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
}

.btn-cancel:hover {
    background: #f9fafb;
    border-color: #d1d5db;
    color: #374151;
}

/* Responsive */
@media (max-width: 768px) {
    .modern-form-container {
        padding: 1rem;
    }
    
    .form-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .header-content {
        flex-direction: column;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-title {
        font-size: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn-submit,
    .btn-cancel {
        width: 100%;
        justify-content: center;
    }
}
</style>
@endpush
@endsection
