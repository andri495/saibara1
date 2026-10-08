@extends('layouts.app')

@section('title', 'Daftar Akun')

@section('content')
<div class="auth-container">

    <!-- Left Panel (Branding) - desktop only -->
    <div class="auth-left-panel">
        <div class="auth-branding">
            <img src="{{ asset('assets/icons/ic_ps_saibara.png') }}" alt="Logo" class="auth-brand-logo">
            <h2 class="brand-title">SAIBARA</h2>
            <p class="brand-desc">Satu Aplikasi Belanja Retribusi Daerah</p>
            <div class="brand-features">
                <div class="brand-feature-item">
                    <span class="brand-feature-icon">📊</span>
                    <span>Pengelolaan Retribusi Daerah</span>
                </div>
                <div class="brand-feature-item">
                    <span class="brand-feature-icon">📋</span>
                    <span>Manajemen Data Terpadu</span>
                </div>
                <div class="brand-feature-item">
                    <span class="brand-feature-icon">🔒</span>
                    <span>Sistem Aman & Terpercaya</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Panel (Form) -->
    <div class="auth-right-panel">
        <div class="auth-card auth-card-register">
            <!-- Logo (mobile only) -->
            <div class="auth-logo-mobile">
                <img src="{{ asset('assets/icons/ic_ps_saibara.png') }}" alt="Logo" class="logo-image">
            </div>

            <!-- Header -->
            <div class="auth-header">
                <h1 class="auth-title">Buat Akun</h1>
                <p class="auth-subtitle">Daftarkan diri Anda ke Sistem SAIBARA</p>
            </div>

            <!-- Register Form -->
            <form action="{{ route('register.post') }}" method="POST" id="registerForm" class="auth-form">
                @csrf

                <!-- Tipe Pendaftar Toggle -->
                <div class="form-group">
                    <label class="form-label">Tipe Pendaftar</label>
                    <div class="tipe-toggle">
                        <input type="hidden" name="tipe_pendaftar" id="tipe_pendaftar" value="perorangan">
                        <button type="button" class="tipe-btn active" id="btnPerorangan" onclick="setTipe('perorangan')">
                            👤 Perorangan
                        </button>
                        <button type="button" class="tipe-btn" id="btnInstansi" onclick="setTipe('instansi')">
                            🏢 Instansi
                        </button>
                    </div>
                </div>

                <!-- Name -->
                <div class="form-group">
                    <label for="name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-input @error('name') is-invalid @enderror"
                        value="{{ old('name') }}"
                        placeholder="Masukkan nama lengkap"
                        required
                        autofocus
                    >
                    @error('name')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-input @error('email') is-invalid @enderror"
                        value="{{ old('email') }}"
                        placeholder="nama@email.com"
                        required
                    >
                    @error('email')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Two columns: NPWRD & No. Telp -->
                <div class="form-row-2col">
                    <div class="form-group">
                        <label for="npwrd" class="form-label">NPWRD</label>
                        <input
                            type="text"
                            id="npwrd"
                            name="npwrd"
                            class="form-input @error('npwrd') is-invalid @enderror"
                            value="{{ old('npwrd') }}"
                            placeholder="Nomor NPWRD"
                        >
                        @error('npwrd')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="no_telp" class="form-label">No. Telepon</label>
                        <input
                            type="text"
                            id="no_telp"
                            name="no_telp"
                            class="form-input @error('no_telp') is-invalid @enderror"
                            value="{{ old('no_telp') }}"
                            placeholder="08xxxxxxxxxx"
                        >
                        @error('no_telp')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Nama Instansi (tampil jika pilih Instansi) -->
                <div class="form-group" id="fieldInstansi" style="display:none;">
                    <label for="nama_instansi" class="form-label">Nama Instansi <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        id="nama_instansi"
                        name="nama_instansi"
                        class="form-input @error('nama_instansi') is-invalid @enderror"
                        value="{{ old('nama_instansi') }}"
                        placeholder="Nama instansi / perusahaan"
                    >
                    @error('nama_instansi')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Alamat -->
                <div class="form-group">
                    <label for="address" class="form-label">Alamat</label>
                    <textarea
                        id="address"
                        name="address"
                        class="form-input @error('address') is-invalid @enderror"
                        placeholder="Masukkan alamat lengkap"
                        rows="2"
                    >{{ old('address') }}</textarea>
                    @error('address')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input @error('password') is-invalid @enderror"
                            placeholder="Minimal 8 karakter"
                            required
                        >
                        <button type="button" class="password-toggle" id="togglePassword">
                            <span class="icon-eye">👁️</span>
                        </button>
                    </div>
                    @error('password')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="password_confirmation" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="form-input"
                            placeholder="Ulangi password"
                            required
                        >
                        <button type="button" class="password-toggle" id="togglePasswordConfirm">
                            <span class="icon-eye">👁️</span>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary btn-block" id="registerBtn">
                    <span class="btn-text">Daftar Sekarang</span>
                </button>
            </form>

            <!-- Login Link -->
            <div class="auth-footer">
                <p>Sudah punya akun? <a href="{{ route('login') }}" class="link-register">Masuk di sini</a></p>
            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
.logo-image {
    width: 90px;
    height: 90px;
    object-fit: contain;
    border-radius: 18px;
    filter: drop-shadow(0 6px 20px rgba(0, 0, 0, 0.2));
}

.auth-card-register {
    max-width: 420px;
}

/* Tipe Toggle */
.tipe-toggle {
    display: flex;
    background: #f1f5f9;
    border-radius: 10px;
    padding: 4px;
    gap: 4px;
    border: 1px solid rgba(37, 99, 235, 0.1);
}

.tipe-btn {
    flex: 1;
    padding: 10px 12px;
    border: none;
    border-radius: 8px;
    font-size: var(--font-size-sm);
    font-weight: 500;
    cursor: pointer;
    background: transparent;
    color: var(--gray-500);
    transition: all 0.2s ease;
    letter-spacing: 0.02em;
}

.tipe-btn.active {
    background: white;
    color: var(--primary-color);
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.15);
}

.tipe-btn:hover:not(.active) {
    background: rgba(37, 99, 235, 0.06);
    color: var(--primary-color);
}

.form-row-2col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-md);
}

.text-danger {
    color: #ef4444;
}

textarea.form-input {
    resize: vertical;
    min-height: 64px;
}

@media (max-width: 480px) {
    .form-row-2col {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.getElementById('togglePassword').addEventListener('click', function () {
    const input = document.getElementById('password');
    input.type = input.type === 'password' ? 'text' : 'password';
});
document.getElementById('togglePasswordConfirm').addEventListener('click', function () {
    const input = document.getElementById('password_confirmation');
    input.type = input.type === 'password' ? 'text' : 'password';
});

function setTipe(tipe) {
    document.getElementById('tipe_pendaftar').value = tipe;

    const btnPerorangan = document.getElementById('btnPerorangan');
    const btnInstansi   = document.getElementById('btnInstansi');
    const fieldInstansi = document.getElementById('fieldInstansi');
    const inputInstansi = document.getElementById('nama_instansi');

    if (tipe === 'instansi') {
        btnInstansi.classList.add('active');
        btnPerorangan.classList.remove('active');
        fieldInstansi.style.display = 'block';
        inputInstansi.required = true;
    } else {
        btnPerorangan.classList.add('active');
        btnInstansi.classList.remove('active');
        fieldInstansi.style.display = 'none';
        inputInstansi.required = false;
        inputInstansi.value = '';
    }
}

// Restore state jika ada old value setelah validasi gagal
@if(old('tipe_pendaftar') === 'instansi')
    setTipe('instansi');
@endif
</script>
@endpush
