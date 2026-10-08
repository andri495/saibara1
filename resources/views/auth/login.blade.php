@extends('layouts.app')

@section('title', 'Login')

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
        <div class="auth-card">
            <!-- Logo (mobile only) -->
            <div class="auth-logo-mobile">
                <img src="{{ asset('assets/icons/ic_ps_saibara.png') }}" alt="Logo" class="logo-image">
            </div>

            <!-- Header -->
            <div class="auth-header">
                <h1 class="auth-title">Welcome Back</h1>
                <p class="auth-subtitle">Masuk ke Sistem SAIBARA</p>
            </div>

            <!-- Login Form -->
            <form action="{{ route('login.post') }}" method="POST" id="loginForm" class="auth-form">
                @csrf

                <!-- Email Field -->
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input @error('email') is-invalid @enderror" 
                        value="{{ old('email') }}"
                        placeholder="nama@email.com"
                        required
                        autofocus
                    >
                    @error('email')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input @error('password') is-invalid @enderror"
                            placeholder="Masukkan password"
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

                <!-- Remember Me -->
                <div class="form-group-inline">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" id="remember">
                        <span>Ingat Saya</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="link-forgot">Lupa Password?</a>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary btn-block" id="loginBtn">
                    <span class="btn-text">Login</span>
                </button>
            </form>

            <!-- Register Link -->
            <div class="auth-footer">
                <p>Belum punya akun? <a href="{{ route('register') }}" class="link-register">Daftar Sekarang</a></p>
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
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/auth/login.js') }}"></script>
@endpush
