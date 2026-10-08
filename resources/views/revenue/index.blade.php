@extends('layouts.app')
@section('title', 'Data Pendapatan')
@section('content')
<div class="page-container glass-page">
    <div class="page-header">
        <div class="page-title-wrapper">
            <div class="title-icon-wrap"><span class="title-icon">💰</span></div>
            <div>
                <h1 class="page-title">Data Pendapatan (Agregat)</h1>
                <p class="page-subtitle">Data pendapatan hasil agregasi otomatis dari transaksi</p>
            </div>
        </div>
        <div class="page-actions">
            <span class="info-badge">📊 Data Auto-Generated</span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="glass-filter-bar">
        <form method="GET" action="{{ route('revenue.index') }}" class="filter-form">
            <div class="filter-group">
                <select name="year" class="glass-input-sm" onchange="this.form.submit()">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $yearFilter == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <select name="opd" class="glass-input-sm" onchange="this.form.submit()">
                    <option value="">Semua OPD</option>
                    @foreach($opds as $opd)
                        <option value="{{ $opd->id }}" {{ $opdFilter == $opd->id ? 'selected' : '' }}>{{ $opd->nama_opd }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <select name="retribusi" class="glass-input-sm" onchange="this.form.submit()">
                    <option value="">Semua Retribusi</option>
                    @foreach($retribusis as $r)
                        <option value="{{ $r->id }}" {{ $retribusiFilter == $r->id ? 'selected' : '' }}>{{ $r->nama_retribusi }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <select name="lokasi" class="glass-input-sm" onchange="this.form.submit()">
                    <option value="">Semua Lokasi</option>
                    @foreach($lokasis as $lok)
                        <option value="{{ $lok->id }}" {{ $lokasiFilter == $lok->id ? 'selected' : '' }}>{{ $lok->nama_lokasi }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <select name="month" class="glass-input-sm" onchange="this.form.submit()">
                    <option value="">Semua Bulan</option>
                    @foreach($months as $m => $name)
                        <option value="{{ $m }}" {{ $monthFilter == $m ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <select name="is_installment" class="glass-input-sm" onchange="this.form.submit()">
                    <option value="">Semua Tipe</option>
                    <option value="0" {{ $isInstallment === '0' ? 'selected' : '' }}>Regular</option>
                    <option value="1" {{ $isInstallment === '1' ? 'selected' : '' }}>Angsuran</option>
                </select>
            </div>
            @if($opdFilter || $retribusiFilter || $lokasiFilter || $monthFilter || $isInstallment !== null)
                <a href="{{ route('revenue.index') }}" class="btn-ghost-sm">✕ Reset</a>
            @endif
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="summary-row">
        <div class="summary-card">
            <span class="summary-icon">📊</span>
            <div>
                <p class="summary-label">Total Record</p>
                <p class="summary-value">{{ $data->total() }}</p>
            </div>
        </div>
        <div class="summary-card">
            <span class="summary-icon">💵</span>
            <div>
                <p class="summary-label">Total Pendapatan</p>
                <p class="summary-value">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
            </div>
        </div>
        <div class="summary-card">
            <span class="summary-icon">🧾</span>
            <div>
                <p class="summary-label">Total Transaksi</p>
                <p class="summary-value">{{ number_format($totalTransactions, 0, ',', '.') }}</p>
            </div>
        </div>
        <div class="summary-card">
            <span class="summary-icon">💳</span>
            <div>
                <p class="summary-label">Regular</p>
                <p class="summary-value">Rp {{ number_format($regularRevenue, 0, ',', '.') }}</p>
            </div>
        </div>
        <div class="summary-card">
            <span class="summary-icon">📅</span>
            <div>
                <p class="summary-label">Angsuran</p>
                <p class="summary-value">Rp {{ number_format($installmentRevenue, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="glass-card">
        <div class="glass-card-header">
            <span class="glass-card-icon">💰</span>
            <h3 class="glass-card-title">Daftar Pendapatan</h3>
        </div>
        <div class="glass-card-body p-0">
            @if($data->count() > 0)
                <table class="glass-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>OPD</th>
                            <th>Retribusi</th>
                            <th>Lokasi</th>
                            <th>Objek</th>
                            <th class="text-right">Transaksi</th>
                            <th class="text-right">Pendapatan</th>
                            <th class="text-center">Tipe</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $index => $item)
                        <tr>
                            <td class="text-muted">{{ $data->firstItem() + $index }}</td>
                            <td><span class="date-badge">{{ $item->date->format('d M Y') }}</span></td>
                            <td><strong>{{ $item->opd->nama_opd ?? '-' }}</strong></td>
                            <td>{{ $item->retribusi->nama_retribusi ?? '-' }}</td>
                            <td>{{ $item->lokasi->nama_lokasi ?? '-' }}</td>
                            <td>{{ $item->objek->sub_rincian_objek ?? '-' }}</td>
                            <td class="text-right">{{ number_format($item->total_transactions, 0, ',', '.') }}</td>
                            <td class="text-right"><strong class="text-blue">Rp {{ number_format($item->total_revenue, 0, ',', '.') }}</strong></td>
                            <td class="text-center">
                                @if($item->is_installment)
                                    <span class="badge-yes">Angsuran</span>
                                @else
                                    <span class="badge-no">Regular</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('revenue.show', $item->id) }}" class="btn-xs-glass" title="Detail">👁️</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="pagination-wrap">{{ $data->appends(request()->query())->links() }}</div>
            @else
                <div class="glass-empty-state">
                    <span class="empty-icon">💰</span>
                    <h3>Belum Ada Data Pendapatan</h3>
                    <p>Data pendapatan akan muncul setelah cron job revenue:generate dijalankan.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
@push('styles')
<style>
.glass-page{padding:1.5rem}
.glass-filter-bar{background:rgba(255,255,255,0.7);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(37,99,235,0.1);border-radius:.875rem;padding:.875rem 1.25rem;margin-bottom:1.25rem;box-shadow:0 4px 16px rgba(37,99,235,0.06)}
.filter-form{display:flex;flex-wrap:wrap;gap:.75rem;align-items:center}
.filter-group{display:flex;align-items:center}
.flex-1{flex:1;min-width:200px}
.glass-input-sm{padding:.5rem .875rem;background:rgba(255,255,255,0.8);border:1.5px solid rgba(37,99,235,0.18);border-radius:.5rem;font-size:.83rem;color:#1f2937;outline:none;width:100%;transition:all .2s}
.glass-input-sm:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,0.1)}
.btn-ghost-sm{padding:.5rem 1rem;background:rgba(255,255,255,0.7);color:#374151;border:1px solid rgba(37,99,235,0.15);border-radius:.5rem;font-size:.83rem;font-weight:600;cursor:pointer;text-decoration:none;transition:all .2s;white-space:nowrap}
.btn-ghost-sm:hover{background:rgba(255,255,255,0.95)}
.info-badge{padding:.5rem 1rem;background:rgba(59,130,246,0.1);color:#2563eb;border:1px solid rgba(59,130,246,0.2);border-radius:.5rem;font-size:.83rem;font-weight:600}
.summary-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.25rem}
.summary-card{background:rgba(255,255,255,0.75);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(37,99,235,0.12);border-radius:.875rem;padding:1.25rem 1.5rem;display:flex;align-items:center;gap:1rem;box-shadow:0 4px 16px rgba(37,99,235,0.07)}
.summary-icon{font-size:2rem}
.summary-label{font-size:.75rem;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.04em;margin:0 0 .2rem}
.summary-value{font-size:1.2rem;font-weight:800;color:#1e40af;margin:0}
.glass-card{background:rgba(255,255,255,0.75);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(37,99,235,0.12);border-radius:1rem;box-shadow:0 8px 32px rgba(37,99,235,0.08);overflow:hidden}
.glass-card-header{display:flex;align-items:center;gap:.75rem;padding:1.25rem 1.5rem;border-bottom:1px solid rgba(37,99,235,0.08);background:rgba(255,255,255,0.5)}
.glass-card-icon{font-size:1.25rem}.glass-card-title{font-size:1rem;font-weight:700;color:#1e40af;flex:1;margin:0}
.glass-card-body.p-0{padding:0}
.glass-table{width:100%;border-collapse:collapse}
.glass-table th{padding:.75rem 1.25rem;background:rgba(37,99,235,0.06);font-size:.72rem;font-weight:700;text-transform:uppercase;color:#1e40af;text-align:left;border-bottom:1px solid rgba(37,99,235,0.1)}
.glass-table td{padding:.875rem 1.25rem;border-bottom:1px solid rgba(37,99,235,0.06);font-size:.875rem;color:#374151}
.glass-table tr:last-child td{border-bottom:none}
.glass-table tr:hover td{background:rgba(37,99,235,0.025)}
.text-right{text-align:right!important}.text-center{text-align:center!important}
.text-muted{color:#9ca3af}.text-blue{color:#2563eb}
.date-badge{background:rgba(37,99,235,0.07);color:#1e40af;padding:.2rem .6rem;border-radius:.375rem;font-size:.78rem;font-weight:600;white-space:nowrap}
.badge-yes{background:rgba(16,185,129,0.1);color:#059669;padding:.2rem .6rem;border-radius:9999px;font-size:.75rem;font-weight:600}
.badge-no{background:rgba(107,114,128,0.1);color:#6b7280;padding:.2rem .6rem;border-radius:9999px;font-size:.75rem;font-weight:600}
.action-btns{display:flex;justify-content:center;gap:.4rem}
.btn-xs-glass{display:inline-flex;padding:.3rem .6rem;background:rgba(37,99,235,0.08);color:#2563eb;border-radius:.375rem;font-size:.75rem;text-decoration:none;transition:all .2s;border:none;cursor:pointer}
.btn-xs-glass:hover{background:rgba(37,99,235,0.18)}
.btn-xs-warn{display:inline-flex;padding:.3rem .6rem;background:rgba(245,158,11,0.1);color:#d97706;border-radius:.375rem;font-size:.75rem;text-decoration:none;transition:all .2s;border:none;cursor:pointer}
.btn-xs-warn:hover{background:rgba(245,158,11,0.2)}
.btn-xs-danger{display:inline-flex;padding:.3rem .6rem;background:rgba(239,68,68,0.08);color:#dc2626;border-radius:.375rem;font-size:.75rem;text-decoration:none;transition:all .2s;border:none;cursor:pointer}
.btn-xs-danger:hover{background:rgba(239,68,68,0.18)}
.pagination-wrap{padding:1rem 1.25rem;border-top:1px solid rgba(37,99,235,0.08)}
.glass-empty-state{display:flex;flex-direction:column;align-items:center;padding:3rem;gap:.75rem;text-align:center}
.empty-icon{font-size:3rem;opacity:.5}
.glass-empty-state h3{font-size:1.1rem;font-weight:700;color:#374151;margin:0}
.glass-empty-state p{font-size:.875rem;color:#9ca3af;margin:0}
.btn-primary-glass{display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:.5rem;font-size:.875rem;font-weight:600;text-decoration:none;box-shadow:0 4px 15px rgba(37,99,235,0.3);transition:all .2s;cursor:pointer}
.btn-primary-glass:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(37,99,235,0.4)}
@media(max-width:768px){.summary-row{grid-template-columns:1fr}.filter-form{flex-direction:column}.flex-1{min-width:100%}}
</style>
@endpush
