@extends('layouts.app')
@section('title', 'Detail Retribusi - ' . $lokasi->nama_lokasi)
@section('content')
<div class="page-container">
    <div class="page-header">
        <div class="page-title-wrapper">
            <div class="title-icon-wrap"><span class="title-icon">📊</span></div>
            <div>
                <h1 class="page-title">Detail Realisasi Retribusi</h1>
                <p class="page-subtitle">{{ $opd->nama_opd }} - {{ $lokasi->nama_lokasi }}</p>
            </div>
        </div>
        <div class="page-actions">
            <select id="yearFilter" class="year-select" onchange="changeYear(this.value)">
                @foreach($availableYears as $year)
                    <option value="{{ $year }}" {{ $tahun == $year ? 'selected' : '' }}>{{ $year }}</option>
                @endforeach
            </select>
            <a href="{{ route('dashboard.realisasi.lokasi-by-opd', ['opdId' => $opd->id, 'tahun' => $tahun]) }}" class="btn-back">← Kembali</a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card">
            <div class="summary-icon">🎯</div>
            <div class="summary-content">
                <div class="summary-label">Total Target</div>
                <div class="summary-value">Rp {{ number_format($totalTarget, 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon">💰</div>
            <div class="summary-content">
                <div class="summary-label">Total Realisasi</div>
                <div class="summary-value">Rp {{ number_format($totalRealisasi, 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon">📈</div>
            <div class="summary-content">
                <div class="summary-label">Pencapaian</div>
                <div class="summary-value">{{ number_format($totalPercentage, 1) }}%</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon">📝</div>
            <div class="summary-content">
                <div class="summary-label">Total Transaksi</div>
                <div class="summary-value">{{ number_format($totalTransaksi, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <!-- Retribution Breakdown -->
    @if(count($data) > 0)
        @foreach($data as $retribusiId => $retribusiData)
        <div class="retribusi-section">
            <div class="retribusi-header">
                <div class="retribusi-info">
                    <h3 class="retribusi-title">{{ $retribusiData['retribusi']->nama_retribusi ?? 'Retribusi #' . $retribusiId }}</h3>
                    <div class="retribusi-stats">
                        <span class="stat-item">Target: Rp {{ number_format($retribusiData['target'], 0, ',', '.') }}</span>
                        <span class="stat-item">Realisasi: Rp {{ number_format($retribusiData['realisasi'], 0, ',', '.') }}</span>
                        <span class="stat-item">Transaksi: {{ number_format($retribusiData['total_transaksi'], 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="retribusi-percentage">
                    <div class="percentage-circle {{ $retribusiData['status'] }}">
                        <span class="percentage-value">{{ number_format($retribusiData['percentage'], 1) }}%</span>
                    </div>
                </div>
            </div>

            @foreach($retribusiData['rincian'] as $rincianId => $rincianData)
            <div class="rincian-section">
                <div class="rincian-header" onclick="toggleRincian({{ $retribusiId }}_{{ $rincianId }})">
                    <div class="rincian-info">
                        <h4 class="rincian-title">
                            <span class="toggle-icon" id="icon_{{ $retribusiId }}_{{ $rincianId }}">▶</span>
                            {{ $rincianData['rincian']->rincian_retribusi ?? 'Rincian #' . $rincianId }}
                        </h4>
                        <div class="rincian-stats">
                            <span class="stat-badge">Target: Rp {{ number_format($rincianData['target'], 0, ',', '.') }}</span>
                            <span class="stat-badge">Realisasi: Rp {{ number_format($rincianData['realisasi'], 0, ',', '.') }}</span>
                            <span class="stat-badge status-{{ $rincianData['status'] }}">{{ number_format($rincianData['percentage'], 1) }}%</span>
                        </div>
                    </div>
                </div>

                <div class="sub-rincian-list" id="content_{{ $retribusiId }}_{{ $rincianId }}" style="display: none;">
                    <table class="sub-rincian-table">
                        <thead>
                            <tr>
                                <th>Sub Rincian Retribusi</th>
                                <th class="text-right">Target</th>
                                <th class="text-right">Realisasi</th>
                                <th class="text-center">Transaksi</th>
                                <th class="text-center">Pencapaian</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rincianData['sub_rincian'] as $subRincianId => $subRincianData)
                            <tr>
                                <td class="sub-rincian-name">{{ $subRincianData['sub_rincian']->nama_sub_rincian_retribusi ?? 'Sub Rincian #' . $subRincianId }}</td>
                                <td class="text-right">Rp {{ number_format($subRincianData['target'], 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($subRincianData['realisasi'], 0, ',', '.') }}</td>
                                <td class="text-center">{{ number_format($subRincianData['total_transaksi'], 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <div class="progress-bar-container">
                                        <div class="progress-bar {{ $subRincianData['status'] }}" style="width: {{ min($subRincianData['percentage'], 100) }}%"></div>
                                        <span class="progress-text">{{ number_format($subRincianData['percentage'], 1) }}%</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="status-badge {{ $subRincianData['status'] }}">
                                        @if($subRincianData['status'] == 'achieved')
                                            ✓ Tercapai
                                        @elseif($subRincianData['status'] == 'on-track')
                                            ↗ On Track
                                        @else
                                            ⚠ Below Target
                                        @endif
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach
        </div>
        @endforeach
    @else
        <div class="empty-state">
            <div class="empty-icon">📊</div>
            <h3>Tidak Ada Data Retribusi</h3>
            <p>Belum ada target atau realisasi retribusi untuk lokasi ini pada tahun {{ $tahun }}</p>
        </div>
    @endif
</div>

<script>
function changeYear(year) {
    window.location.href = "{{ route('dashboard.realisasi.retribusi-by-lokasi', ['opdId' => $opd->id, 'lokasiId' => $lokasi->id]) }}?tahun=" + year;
}

function toggleRincian(id) {
    const content = document.getElementById('content_' + id);
    const icon = document.getElementById('icon_' + id);
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.textContent = '▼';
    } else {
        content.style.display = 'none';
        icon.textContent = '▶';
    }
}
</script>

<style>
.page-container {
    padding: 1.5rem;
    max-width: 1400px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e5e7eb;
}

.page-title-wrapper {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.title-icon-wrap {
    width: 3rem;
    height: 3rem;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.title-icon {
    font-size: 1.5rem;
}

.page-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.page-subtitle {
    font-size: 0.95rem;
    color: #64748b;
    margin: 0.25rem 0 0 0;
}

.page-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.year-select {
    padding: 0.5rem 1rem;
    border: 1.5px solid #e5e7eb;
    border-radius: 0.5rem;
    font-size: 0.9rem;
    background: white;
    cursor: pointer;
    transition: all 0.2s;
}

.year-select:hover {
    border-color: #3b82f6;
}

.btn-back {
    padding: 0.5rem 1rem;
    background: white;
    border: 1.5px solid #e5e7eb;
    border-radius: 0.5rem;
    color: #64748b;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-back:hover {
    border-color: #3b82f6;
    color: #3b82f6;
}

.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.25rem;
    margin-bottom: 2rem;
}

.summary-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.summary-icon {
    width: 3rem;
    height: 3rem;
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.summary-content {
    flex: 1;
}

.summary-label {
    font-size: 0.85rem;
    color: #64748b;
    margin-bottom: 0.25rem;
}

.summary-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
}

.retribusi-section {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    margin-bottom: 1.5rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.retribusi-header {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    padding: 1.25rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.retribusi-title {
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
}

.retribusi-stats {
    display: flex;
    gap: 1.5rem;
    font-size: 0.85rem;
    opacity: 0.95;
}

.stat-item {
    display: inline-flex;
    align-items: center;
}

.percentage-circle {
    width: 4rem;
    height: 4rem;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid rgba(255, 255, 255, 0.5);
}

.percentage-value {
    font-size: 1rem;
    font-weight: 700;
}

.rincian-section {
    border-top: 1px solid #e5e7eb;
}

.rincian-header {
    padding: 1rem 1.5rem;
    background: #f8fafc;
    cursor: pointer;
    transition: background 0.2s;
}

.rincian-header:hover {
    background: #f1f5f9;
}

.rincian-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.toggle-icon {
    font-size: 0.75rem;
    color: #64748b;
    transition: transform 0.2s;
}

.rincian-stats {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.stat-badge {
    font-size: 0.8rem;
    color: #64748b;
    padding: 0.25rem 0.75rem;
    background: white;
    border-radius: 0.375rem;
    border: 1px solid #e5e7eb;
}

.stat-badge.status-achieved {
    background: #dcfce7;
    color: #166534;
    border-color: #86efac;
}

.stat-badge.status-on-track {
    background: #fef3c7;
    color: #92400e;
    border-color: #fde047;
}

.stat-badge.status-below-target {
    background: #fee2e2;
    color: #991b1b;
    border-color: #fca5a5;
}

.sub-rincian-list {
    padding: 1rem 1.5rem;
}

.sub-rincian-table {
    width: 100%;
    border-collapse: collapse;
}

.sub-rincian-table thead th {
    background: #f8fafc;
    padding: 0.75rem;
    text-align: left;
    font-size: 0.85rem;
    font-weight: 600;
    color: #475569;
    border-bottom: 2px solid #e5e7eb;
}

.sub-rincian-table tbody td {
    padding: 0.875rem 0.75rem;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.9rem;
}

.sub-rincian-name {
    font-weight: 500;
    color: #1e293b;
}

.text-right {
    text-align: right;
}

.text-center {
    text-align: center;
}

.progress-bar-container {
    position: relative;
    width: 100%;
    max-width: 150px;
    height: 1.5rem;
    background: #f1f5f9;
    border-radius: 0.375rem;
    overflow: hidden;
    margin: 0 auto;
}

.progress-bar {
    height: 100%;
    transition: width 0.3s;
    border-radius: 0.375rem;
}

.progress-bar.achieved {
    background: linear-gradient(90deg, #10b981, #059669);
}

.progress-bar.on-track {
    background: linear-gradient(90deg, #f59e0b, #d97706);
}

.progress-bar.below-target {
    background: linear-gradient(90deg, #ef4444, #dc2626);
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 0.75rem;
    font-weight: 600;
    color: #1e293b;
}

.status-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-block;
}

.status-badge.achieved {
    background: #dcfce7;
    color: #166534;
}

.status-badge.on-track {
    background: #fef3c7;
    color: #92400e;
}

.status-badge.below-target {
    background: #fee2e2;
    color: #991b1b;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1.25rem;
    color: #1e293b;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: #64748b;
    font-size: 0.95rem;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .summary-cards {
        grid-template-columns: 1fr;
    }

    .retribusi-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .retribusi-stats {
        flex-direction: column;
        gap: 0.5rem;
    }

    .rincian-stats {
        flex-direction: column;
        gap: 0.5rem;
    }

    .sub-rincian-table {
        font-size: 0.8rem;
    }

    .sub-rincian-table thead th,
    .sub-rincian-table tbody td {
        padding: 0.5rem;
    }
}
</style>
@endsection
