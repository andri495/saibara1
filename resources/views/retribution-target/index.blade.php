@extends('layouts.app')
@section('title', 'Target Retribusi')
@section('content')
<div class="page-container glass-page">
    <div class="page-header">
        <div class="page-title-wrapper">
            <div class="title-icon-wrap"><span class="title-icon">🎯</span></div>
            <div>
                <h1 class="page-title">Target Retribusi</h1>
                <p class="page-subtitle">Kelola target pendapatan retribusi per tahun dengan detail OPD, Lokasi, dan Objek</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('retribution-target.create') }}" class="btn-primary-glass">➕ Tambah Target</a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="glass-filter-bar">
        <form method="GET" action="{{ route('retribution-target.index') }}" class="filter-form">
            <div class="filter-group">
                <select name="tahun" class="glass-input-sm" onchange="this.form.submit()">
                    <option value="">Semua Tahun</option>
                    @foreach($tahuns as $t)
                        <option value="{{ $t }}" {{ $tahunFilter == $t ? 'selected' : '' }}>{{ $t }}</option>
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
            @if($opdFilter || $retribusiFilter || $lokasiFilter || $tahunFilter)
                <a href="{{ route('retribution-target.index') }}" class="btn-ghost-sm">✕ Reset</a>
            @endif
        </form>
    </div>

    <!-- Summary -->
    <div class="summary-row">
        <div class="summary-card">
            <span class="summary-icon">🎯</span>
            <div>
                <p class="summary-label">Total Target</p>
                <p class="summary-value">{{ $data->total() }}</p>
            </div>
        </div>
        <div class="summary-card">
            <span class="summary-icon">💰</span>
            <div>
                <p class="summary-label">Total Target Anggaran</p>
                <p class="summary-value">Rp {{ number_format($totalTarget, 0, ',', '.') }}</p>
            </div>
        </div>
        <div class="summary-card">
            <span class="summary-icon">�</span>
            <div>
                <p class="summary-label">Jumlah Record</p>
                <p class="summary-value">{{ number_format($countRecords, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="glass-card">
        <div class="glass-card-header">
            <span class="glass-card-icon">🎯</span>
            <h3 class="glass-card-title">Daftar Target Retribusi</h3>
        </div>
        <div class="glass-card-body p-0">
            @if($data->count() > 0)
                <table class="glass-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tahun</th>
                            <th>OPD</th>
                            <th>Retribusi</th>
                            <th>Lokasi</th>
                            <th>Rincian</th>
                            <th>Sub Rincian</th>
                            <th class="text-right">Target (Rp)</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $index => $item)
                        <tr>
                            <td class="text-muted">{{ $data->firstItem() + $index }}</td>
                            <td><span class="year-badge">{{ $item->tahun }}</span></td>
                            <td><strong>{{ $item->opd->nama_opd ?? '-' }}</strong></td>
                            <td>{{ $item->retribusi->nama_retribusi ?? '-' }}</td>
                            <td>{{ $item->lokasi->nama_lokasi ?? '-' }}</td>
                            <td>{{ $item->rincianRetribusi->rincian_retribusi ?? '-' }}</td>
                            <td>{{ $item->subRincianRetribusi->nama_sub_rincian_retribusi ?? '-' }}</td>
                            <td class="text-right"><strong class="text-blue">Rp {{ number_format($item->target_amount, 0, ',', '.') }}</strong></td>
                            <td class="text-center">
                                <div class="action-btns">
                                    <a href="{{ route('retribution-target.show', $item->id) }}" class="btn-xs-glass" title="Detail">👁️</a>
                                    <a href="{{ route('retribution-target.edit', $item->id) }}" class="btn-xs-warn" title="Edit">✏️</a>
                                    <form action="{{ route('retribution-target.destroy', $item->id) }}" method="POST" style="display:inline">
                                        @csrf @method('DELETE')
                                        <button class="btn-xs-danger" onclick="return confirm('Hapus target ini?')" title="Hapus">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="pagination-wrap">{{ $data->appends(request()->query())->links() }}</div>
            @else
                <div class="glass-empty-state">
                    <span class="empty-icon">🎯</span>
                    <h3>Belum Ada Target Retribusi</h3>
                    <p>Klik tombol "Tambah Target" untuk menambahkan target pendapatan baru.</p>
                    <a href="{{ route('retribution-target.create') }}" class="btn-primary-glass">➕ Tambah Target</a>
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
.glass-input-sm{padding:.5rem .875rem;background:rgba(255,255,255,0.8);border:1.5px solid rgba(37,99,235,0.18);border-radius:.5rem;font-size:.83rem;color:#1f2937;outline:none;transition:all .2s}
.glass-input-sm:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,0.1)}
.btn-ghost-sm{padding:.5rem 1rem;background:rgba(255,255,255,0.7);color:#374151;border:1px solid rgba(37,99,235,0.15);border-radius:.5rem;font-size:.83rem;font-weight:600;cursor:pointer;text-decoration:none;transition:all .2s;white-space:nowrap}
.btn-ghost-sm:hover{background:rgba(255,255,255,0.95)}
.summary-row{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.25rem}
.summary-card{background:rgba(255,255,255,0.75);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(37,99,235,0.12);border-radius:.875rem;padding:1.25rem 1.5rem;display:flex;align-items:center;gap:1rem;box-shadow:0 4px 16px rgba(37,99,235,0.07)}
.summary-icon{font-size:2rem}.summary-label{font-size:.75rem;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.04em;margin:0 0 .2rem}
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
.year-badge{background:linear-gradient(135deg,rgba(37,99,235,0.12),rgba(59,130,246,0.08));color:#1e40af;padding:.25rem .75rem;border-radius:.375rem;font-size:.85rem;font-weight:700;white-space:nowrap;border:1px solid rgba(37,99,235,0.15)}
.action-btns{display:flex;justify-content:center;gap:.4rem}
.btn-xs-glass{display:inline-flex;padding:.3rem .6rem;background:rgba(37,99,235,0.08);color:#2563eb;border-radius:.375rem;font-size:.75rem;text-decoration:none;transition:all .2s;border:none;cursor:pointer}
.btn-xs-glass:hover{background:rgba(37,99,235,0.18)}
.btn-xs-warn{display:inline-flex;padding:.3rem .6rem;background:rgba(245,158,11,0.1);color:#d97706;border-radius:.375rem;font-size:.75rem;text-decoration:none;transition:all .2s;border:none;cursor:pointer}
.btn-xs-warn:hover{background:rgba(245,158,11,0.2)}
.btn-xs-danger{display:inline-flex;padding:.3rem .6rem;background:rgba(239,68,68,0.08);color:#dc2626;border-radius:.375rem;font-size:.75rem;border:none;cursor:pointer;transition:all .2s}
.btn-xs-danger:hover{background:rgba(239,68,68,0.18)}
.pagination-wrap{padding:1rem 1.25rem;border-top:1px solid rgba(37,99,235,0.08)}
.glass-empty-state{display:flex;flex-direction:column;align-items:center;padding:3rem;gap:.75rem;text-align:center}
.empty-icon{font-size:3rem;opacity:.5}
.glass-empty-state h3{font-size:1.1rem;font-weight:700;color:#374151;margin:0}
.glass-empty-state p{font-size:.875rem;color:#9ca3af;margin:0}
.btn-primary-glass{display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:.5rem;font-size:.875rem;font-weight:600;text-decoration:none;box-shadow:0 4px 15px rgba(37,99,235,0.3);transition:all .2s;cursor:pointer}
.btn-primary-glass:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(37,99,235,0.4)}
@media(max-width:768px){.summary-row{grid-template-columns:1fr}.filter-form{flex-direction:column}}
</style>
@endpush
