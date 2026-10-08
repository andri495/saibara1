@extends('layouts.app')
@section('title', 'Tambah Target Retribusi')
@section('content')
<div class="page-container glass-page">
    <div class="page-header">
        <div class="page-title-wrapper">
            <div class="title-icon-wrap"><span class="title-icon">🎯</span></div>
            <div>
                <h1 class="page-title">Tambah Target Retribusi</h1>
                <p class="page-subtitle">Tetapkan target pendapatan retribusi per lokasi dengan detail hierarki retribusi</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('retribution-target.index') }}" class="btn-ghost">← Kembali</a>
        </div>
    </div>
    <div class="glass-card">
        <div class="glass-card-header">
            <span class="glass-card-icon">➕</span>
            <h3 class="glass-card-title">Form Target Retribusi</h3>
        </div>
        <div class="glass-card-body">
            <form action="{{ route('retribution-target.store') }}" method="POST" id="targetForm">
                @csrf
                <div class="form-sections">
                    <!-- Section: Tahun -->
                    <div class="form-section">
                        <h4 class="section-title">📅 Periode Target</h4>
                        <div class="form-grid-1">
                            <div class="form-group-glass">
                                <label class="glass-label" for="tahun">Tahun <span class="required-star">*</span></label>
                                <select id="tahun" name="tahun"
                                    class="glass-input @error('tahun') is-invalid @enderror" required>
                                    <option value="">-- Pilih Tahun --</option>
                                    @foreach($tahuns as $t)
                                        <option value="{{ $t }}" {{ old('tahun') == $t ? 'selected' : '' }}>{{ $t }}</option>
                                    @endforeach
                                </select>
                                @error('tahun')<span class="glass-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <!-- Section: OPD & Lokasi -->
                    <div class="form-section">
                        <h4 class="section-title">🏢 OPD & Lokasi</h4>
                        <div class="form-grid-2">
                            <div class="form-group-glass">
                                <label class="glass-label" for="master_opd_id">OPD <span class="required-star">*</span></label>
                                <select id="master_opd_id" name="master_opd_id"
                                    class="glass-input @error('master_opd_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih OPD --</option>
                                    @foreach($opds as $opd)
                                        <option value="{{ $opd->id }}" {{ old('master_opd_id') == $opd->id ? 'selected' : '' }}>{{ $opd->nama_opd }}</option>
                                    @endforeach
                                </select>
                                @error('master_opd_id')<span class="glass-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="form-group-glass">
                                <label class="glass-label" for="master_lokasi_id">Lokasi <span class="required-star">*</span></label>
                                <select id="master_lokasi_id" name="master_lokasi_id"
                                    class="glass-input @error('master_lokasi_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih Lokasi --</option>
                                </select>
                                @error('master_lokasi_id')<span class="glass-error">{{ $message }}</span>@enderror
                                <small class="glass-help">Pilih OPD terlebih dahulu</small>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Retribusi Hierarchy -->
                    <div class="form-section">
                        <h4 class="section-title">📋 Jenis Retribusi</h4>
                        <div class="form-grid-3">
                            <div class="form-group-glass">
                                <label class="glass-label" for="master_retribusi_id">Jenis Retribusi <span class="required-star">*</span></label>
                                <select id="master_retribusi_id" name="master_retribusi_id"
                                    class="glass-input @error('master_retribusi_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih Jenis Retribusi --</option>
                                    @foreach($retribusis as $r)
                                        <option value="{{ $r->id }}" {{ old('master_retribusi_id') == $r->id ? 'selected' : '' }}>{{ $r->nama_retribusi }}</option>
                                    @endforeach
                                </select>
                                @error('master_retribusi_id')<span class="glass-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="form-group-glass">
                                <label class="glass-label" for="master_rincian_retribusi_id">Rincian Retribusi <span class="required-star">*</span></label>
                                <select id="master_rincian_retribusi_id" name="master_rincian_retribusi_id"
                                    class="glass-input @error('master_rincian_retribusi_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih Rincian --</option>
                                </select>
                                @error('master_rincian_retribusi_id')<span class="glass-error">{{ $message }}</span>@enderror
                                <small class="glass-help">Pilih jenis retribusi terlebih dahulu</small>
                            </div>
                            <div class="form-group-glass">
                                <label class="glass-label" for="master_sub_rincian_retribusi_id">Sub Rincian Retribusi <span class="required-star">*</span></label>
                                <select id="master_sub_rincian_retribusi_id" name="master_sub_rincian_retribusi_id"
                                    class="glass-input @error('master_sub_rincian_retribusi_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih Sub Rincian --</option>
                                </select>
                                @error('master_sub_rincian_retribusi_id')<span class="glass-error">{{ $message }}</span>@enderror
                                <small class="glass-help">Pilih rincian retribusi terlebih dahulu</small>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Target -->
                    <div class="form-section">
                        <h4 class="section-title">💰 Target Pendapatan</h4>
                        <div class="form-grid-1">
                            <div class="form-group-glass">
                                <label class="glass-label" for="target_amount">Target Pendapatan (Rp) <span class="required-star">*</span></label>
                                <input type="number" id="target_amount" name="target_amount"
                                    class="glass-input @error('target_amount') is-invalid @enderror"
                                    value="{{ old('target_amount', 0) }}" min="0" step="0.01" required>
                                @error('target_amount')<span class="glass-error">{{ $message }}</span>@enderror
                                <small class="glass-help" id="amountPreview">Masukkan jumlah target dalam Rupiah</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="glass-form-actions">
                    <button type="submit" class="btn-primary-glass">💾 Simpan Target</button>
                    <a href="{{ route('retribution-target.index') }}" class="btn-ghost">Batal</a>
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
.glass-card-icon{font-size:1.25rem}.glass-card-title{font-size:1rem;font-weight:700;color:#1e40af;flex:1;margin:0}
.glass-card-body{padding:1.5rem}
.form-sections{display:flex;flex-direction:column;gap:1.5rem}
.form-section{padding:1.25rem;background:rgba(37,99,235,0.02);border:1px solid rgba(37,99,235,0.08);border-radius:.75rem}
.section-title{font-size:.95rem;font-weight:700;color:#1e40af;margin:0 0 1rem 0;padding-bottom:.75rem;border-bottom:2px solid rgba(37,99,235,0.15)}
.form-grid-1{display:grid;grid-template-columns:1fr;gap:1.25rem}
.form-grid-2{display:grid;grid-template-columns:repeat(2,1fr);gap:1.25rem}
.form-grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem}
.form-group-glass{display:flex;flex-direction:column;gap:.5rem}
.glass-label{font-size:.8rem;font-weight:700;color:#1e40af;text-transform:uppercase;letter-spacing:.04em}
.required-star{color:#ef4444}
.glass-input{width:100%;padding:.75rem 1rem;background:rgba(255,255,255,0.8);border:1.5px solid rgba(37,99,235,0.2);border-radius:.625rem;font-size:.9rem;color:#1f2937;transition:all .2s;outline:none;box-sizing:border-box}
.glass-input:focus{border-color:#2563eb;background:rgba(255,255,255,0.95);box-shadow:0 0 0 3px rgba(37,99,235,0.1)}
.glass-input.is-invalid{border-color:#ef4444}.glass-error{font-size:.78rem;color:#ef4444}
.glass-help{font-size:.78rem;color:#2563eb;font-weight:500}
.glass-form-actions{display:flex;gap:.75rem;padding-top:1.25rem;border-top:1px solid rgba(37,99,235,0.1)}
.btn-primary-glass{display:inline-flex;align-items:center;gap:.5rem;padding:.675rem 1.5rem;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:.5rem;font-size:.875rem;font-weight:600;cursor:pointer;box-shadow:0 4px 15px rgba(37,99,235,0.3);transition:all .2s;text-decoration:none}
.btn-primary-glass:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(37,99,235,0.4)}
.btn-ghost{display:inline-flex;align-items:center;gap:.5rem;padding:.675rem 1.25rem;background:rgba(255,255,255,0.7);color:#374151;border:1px solid rgba(37,99,235,0.15);border-radius:.5rem;font-size:.875rem;font-weight:600;text-decoration:none;transition:all .2s}
.btn-ghost:hover{background:rgba(255,255,255,0.95)}
.validation-notification{position:fixed;top:20px;right:20px;background:#fff;border-left:4px solid #ef4444;border-radius:.75rem;box-shadow:0 10px 40px rgba(0,0,0,0.15);padding:1rem 1.5rem;display:flex;align-items:center;gap:1rem;max-width:400px;z-index:9999;animation:slideIn .3s ease-out}
.validation-icon{font-size:2rem;line-height:1}
.validation-content{flex:1}
.validation-title{font-weight:700;color:#1f2937;font-size:.95rem;margin-bottom:.25rem}
.validation-message{color:#6b7280;font-size:.85rem}
.validation-close{background:none;border:none;color:#9ca3af;font-size:1.25rem;cursor:pointer;padding:0;width:24px;height:24px;display:flex;align-items:center;justify-content:center;border-radius:50%;transition:all .2s}
.validation-close:hover{background:#f3f4f6;color:#374151}
.validation-notification.fade-out{animation:slideOut .3s ease-in forwards}
@keyframes slideIn{from{transform:translateX(400px);opacity:0}to{transform:translateX(0);opacity:1}}
@keyframes slideOut{from{transform:translateX(0);opacity:1}to{transform:translateX(400px);opacity:0}}
@keyframes shake{0%,100%{transform:translateX(0)}10%,30%,50%,70%,90%{transform:translateX(-5px)}20%,40%,60%,80%{transform:translateX(5px)}}
.shake-error{animation:shake .5s;border-color:#ef4444!important}
@media(max-width:768px){.form-grid-2,.form-grid-3{grid-template-columns:1fr}.validation-notification{left:20px;right:20px;max-width:none}}
</style>
@endpush
@push('scripts')
<script>
// Custom validation notification
function showValidationNotification(message) {
    // Remove existing notification if any
    const existing = document.querySelector('.validation-notification');
    if (existing) existing.remove();
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'validation-notification';
    notification.innerHTML = `
        <div class="validation-icon">⚠️</div>
        <div class="validation-content">
            <div class="validation-title">Oops! Ada yang terlewat</div>
            <div class="validation-message">${message}</div>
        </div>
        <button class="validation-close" onclick="this.parentElement.remove()">✕</button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// Form validation on submit
document.getElementById('targetForm').addEventListener('submit', function(e) {
    const requiredFields = [
        { id: 'tahun', label: 'Tahun' },
        { id: 'master_opd_id', label: 'OPD' },
        { id: 'master_lokasi_id', label: 'Lokasi' },
        { id: 'master_retribusi_id', label: 'Jenis Retribusi' },
        { id: 'master_rincian_retribusi_id', label: 'Rincian Retribusi' },
        { id: 'master_sub_rincian_retribusi_id', label: 'Sub Rincian Retribusi' },
        { id: 'target_amount', label: 'Target Pendapatan' }
    ];
    
    for (let field of requiredFields) {
        const element = document.getElementById(field.id);
        if (!element.value || element.value === '' || element.value === '0') {
            e.preventDefault();
            showValidationNotification(`Field <strong>${field.label}</strong> wajib diisi!`);
            element.focus();
            element.classList.add('shake-error');
            setTimeout(() => element.classList.remove('shake-error'), 500);
            return false;
        }
    }
});

// Amount preview
document.getElementById('target_amount').addEventListener('input', function() {
    const val = parseFloat(this.value);
    const preview = document.getElementById('amountPreview');
    if (!isNaN(val) && val > 0) {
        preview.textContent = 'Target: Rp ' + new Intl.NumberFormat('id-ID').format(val);
    } else {
        preview.textContent = 'Masukkan jumlah target dalam Rupiah';
    }
});

// Cascading: OPD -> Lokasi
document.getElementById('master_opd_id').addEventListener('change', function() {
    const opdId = this.value;
    const lokasiSelect = document.getElementById('master_lokasi_id');
    
    lokasiSelect.innerHTML = '<option value="">-- Semua Lokasi --</option>';
    
    if (opdId) {
        fetch(`{{ route('retribution-target.ajax.lokasi') }}?opd_id=${opdId}`)
            .then(response => response.json())
            .then(data => {
                data.forEach(lokasi => {
                    const option = document.createElement('option');
                    option.value = lokasi.id;
                    option.textContent = lokasi.nama_lokasi;
                    lokasiSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error loading lokasi:', error));
    }
});

// Cascading: Retribusi -> Rincian
document.getElementById('master_retribusi_id').addEventListener('change', function() {
    const retribusiId = this.value;
    const rincianSelect = document.getElementById('master_rincian_retribusi_id');
    const subRincianSelect = document.getElementById('master_sub_rincian_retribusi_id');
    
    rincianSelect.innerHTML = '<option value="">-- Pilih Rincian --</option>';
    subRincianSelect.innerHTML = '<option value="">-- Pilih Sub Rincian --</option>';
    
    if (retribusiId) {
        fetch(`{{ route('retribution-target.ajax.rincian') }}?retribusi_id=${retribusiId}`)
            .then(response => response.json())
            .then(data => {
                data.forEach(rincian => {
                    const option = document.createElement('option');
                    option.value = rincian.id;
                    option.textContent = rincian.rincian_retribusi;
                    rincianSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error loading rincian:', error));
    }
});

// Cascading: Rincian -> Sub Rincian
document.getElementById('master_rincian_retribusi_id').addEventListener('change', function() {
    const rincianId = this.value;
    const subRincianSelect = document.getElementById('master_sub_rincian_retribusi_id');
    
    subRincianSelect.innerHTML = '<option value="">-- Pilih Sub Rincian --</option>';
    
    if (rincianId) {
        fetch(`{{ route('retribution-target.ajax.sub-rincian') }}?rincian_id=${rincianId}`)
            .then(response => response.json())
            .then(data => {
                data.forEach(subRincian => {
                    const option = document.createElement('option');
                    option.value = subRincian.id;
                    option.textContent = subRincian.nama_sub_rincian_retribusi;
                    subRincianSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error loading sub rincian:', error));
    }
});
</script>
@endpush
