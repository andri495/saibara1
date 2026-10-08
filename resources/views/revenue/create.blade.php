@extends('layouts.app')
@section('title', 'Tambah Pendapatan')
@section('content')
<div class="page-container glass-page">
    <div class="page-header">
        <div class="page-title-wrapper">
            <div class="title-icon-wrap"><span class="title-icon">💰</span></div>
            <div>
                <h1 class="page-title">Tambah Pendapatan</h1>
                <p class="page-subtitle">Input data realisasi pendapatan retribusi</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('revenue.index') }}" class="btn-ghost">← Kembali</a>
        </div>
    </div>
    <div class="glass-card">
        <div class="glass-card-header">
            <span class="glass-card-icon">➕</span>
            <h3 class="glass-card-title">Form Data Pendapatan</h3>
        </div>
        <div class="glass-card-body">
            <form action="{{ route('revenue.store') }}" method="POST" id="revenueForm">
                @csrf
                <div class="form-grid-2">
                    <div class="form-group-glass">
                        <label class="glass-label" for="date">Tanggal <span class="required-star">*</span></label>
                        <input type="date" id="date" name="date"
                            class="glass-input @error('date') is-invalid @enderror"
                            value="{{ old('date', date('Y-m-d')) }}" required>
                        @error('date')<span class="glass-error">{{ $message }}</span>@enderror
                    </div>
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
                        <label class="glass-label" for="total_transactions">Jumlah Transaksi <span class="required-star">*</span></label>
                        <input type="number" id="total_transactions" name="total_transactions"
                            class="glass-input @error('total_transactions') is-invalid @enderror"
                            value="{{ old('total_transactions', 0) }}" min="0" required>
                        @error('total_transactions')<span class="glass-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group-glass">
                        <label class="glass-label" for="total_revenue">Total Pendapatan (Rp) <span class="required-star">*</span></label>
                        <input type="number" id="total_revenue" name="total_revenue"
                            class="glass-input @error('total_revenue') is-invalid @enderror"
                            value="{{ old('total_revenue', 0) }}" min="0" step="0.01" required>
                        @error('total_revenue')<span class="glass-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group-glass align-center">
                        <label class="glass-label">Jenis Pembayaran</label>
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_installment" id="is_installment" value="1"
                                {{ old('is_installment') ? 'checked' : '' }}>
                            <span class="toggle-track"></span>
                            <span class="toggle-label">Angsuran / Cicilan</span>
                        </label>
                    </div>
                </div>
                <div class="glass-form-actions">
                    <button type="submit" class="btn-primary-glass">💾 Simpan</button>
                    <a href="{{ route('revenue.index') }}" class="btn-ghost">Batal</a>
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
.form-grid-2{display:grid;grid-template-columns:repeat(2,1fr);gap:1.25rem;margin-bottom:1.25rem}
.form-group-glass{display:flex;flex-direction:column;gap:.5rem}
.form-group-glass.align-center{justify-content:center}
.glass-label{font-size:.8rem;font-weight:700;color:#1e40af;text-transform:uppercase;letter-spacing:.04em}
.required-star{color:#ef4444}
.glass-input{width:100%;padding:.75rem 1rem;background:rgba(255,255,255,0.8);border:1.5px solid rgba(37,99,235,0.2);border-radius:.625rem;font-size:.9rem;color:#1f2937;transition:all .2s;outline:none;box-sizing:border-box}
.glass-input:focus{border-color:#2563eb;background:rgba(255,255,255,0.95);box-shadow:0 0 0 3px rgba(37,99,235,0.1)}
.glass-input.is-invalid{border-color:#ef4444}.glass-error{font-size:.78rem;color:#ef4444}
.toggle-switch{display:flex;align-items:center;gap:.75rem;cursor:pointer;padding:.75rem 1rem;background:rgba(255,255,255,0.6);border:1.5px solid rgba(37,99,235,0.15);border-radius:.625rem;transition:all .2s}
.toggle-switch:hover{border-color:#2563eb;background:rgba(37,99,235,0.04)}
.toggle-switch input{display:none}
.toggle-track{width:42px;height:24px;background:#d1d5db;border-radius:9999px;position:relative;transition:all .3s;flex-shrink:0}
.toggle-track::after{content:'';position:absolute;top:3px;left:3px;width:18px;height:18px;background:#fff;border-radius:50%;transition:all .3s;box-shadow:0 1px 4px rgba(0,0,0,0.2)}
.toggle-switch input:checked + .toggle-track{background:#2563eb}
.toggle-switch input:checked + .toggle-track::after{left:21px}
.toggle-label{font-size:.875rem;color:#374151;font-weight:500}
.glass-form-actions{display:flex;gap:.75rem;padding-top:1.25rem;border-top:1px solid rgba(37,99,235,0.1)}
.btn-primary-glass{display:inline-flex;align-items:center;gap:.5rem;padding:.675rem 1.5rem;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:.5rem;font-size:.875rem;font-weight:600;cursor:pointer;box-shadow:0 4px 15px rgba(37,99,235,0.3);transition:all .2s;text-decoration:none}
.btn-primary-glass:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(37,99,235,0.4)}
.btn-ghost{display:inline-flex;align-items:center;gap:.5rem;padding:.675rem 1.25rem;background:rgba(255,255,255,0.7);color:#374151;border:1px solid rgba(37,99,235,0.15);border-radius:.5rem;font-size:.875rem;font-weight:600;text-decoration:none;transition:all .2s}
.btn-ghost:hover{background:rgba(255,255,255,0.95)}
@media(max-width:768px){.form-grid-2{grid-template-columns:1fr}}
</style>
@endpush
