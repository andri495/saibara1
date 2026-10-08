<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MasterOpd;
use App\Models\MasterRetribusi;
use App\Models\MasterLokasiObjek;
use App\Models\MasterRincianRetribusi;
use App\Models\MasterSubRincianRetribusi;
use App\Models\RetributionTargets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RetributionTargetWebController extends Controller
{
    public function index(Request $request)
    {
        $opdFilter = $request->get('opd');
        $retribusiFilter = $request->get('retribusi');
        $lokasiFilter = $request->get('lokasi');
        $tahunFilter = $request->get('tahun', date('Y'));

        $baseQuery = RetributionTargets::query()
            ->when($opdFilter, fn($q) => $q->where('master_opd_id', $opdFilter))
            ->when($retribusiFilter, fn($q) => $q->where('master_retribusi_id', $retribusiFilter))
            ->when($lokasiFilter, fn($q) => $q->where('master_lokasi_id', $lokasiFilter))
            ->when($tahunFilter, fn($q) => $q->where('tahun', $tahunFilter));

        // Aggregate totals across ALL filtered records (not just current page)
        $totalTarget = (clone $baseQuery)->sum('target_amount');
        $countRecords = (clone $baseQuery)->count();

        $data = (clone $baseQuery)
            ->with(['opd', 'retribusi', 'lokasi', 'rincianRetribusi', 'subRincianRetribusi'])
            ->orderBy('tahun', 'desc')
            ->orderBy('master_opd_id')
            ->paginate(20);

        $opds = MasterOpd::orderBy('nama_opd')->get();
        $retribusis = MasterRetribusi::orderBy('nama_retribusi')->get();
        $lokasis = MasterLokasiObjek::orderBy('nama_lokasi')->get();
        $tahuns = range(date('Y') + 1, date('Y') - 5);

        return view('retribution-target.index', compact(
            'data',
            'opds',
            'retribusis',
            'lokasis',
            'tahuns',
            'opdFilter',
            'retribusiFilter',
            'lokasiFilter',
            'tahunFilter',
            'totalTarget',
            'countRecords'
        ));
    }

    public function create()
    {
        $opds = MasterOpd::orderBy('nama_opd')->get();
        $retribusis = MasterRetribusi::orderBy('nama_retribusi')->get();
        $lokasis = MasterLokasiObjek::orderBy('nama_lokasi')->get();
        $tahuns = range(date('Y') + 1, date('Y') - 5);
        return view('retribution-target.create', compact('opds', 'retribusis', 'lokasis', 'tahuns'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'master_opd_id' => 'required|exists:master_opd,id',
            'master_retribusi_id' => 'required|exists:master_retribusi,id',
            'master_lokasi_id' => 'required|exists:master_lokasi_objek,id',
            'master_rincian_retribusi_id' => 'required|exists:master_rincian_retribusi,id',
            'master_sub_rincian_retribusi_id' => 'required|exists:master_sub_rincian_retribusi,id',
            'target_amount' => 'required|numeric|min:1',
            'tahun' => 'required|integer|min:2000|max:2100',
        ], [
            'master_opd_id.required' => 'OPD wajib dipilih',
            'master_lokasi_id.required' => 'Lokasi wajib dipilih',
            'master_retribusi_id.required' => 'Jenis Retribusi wajib dipilih',
            'master_rincian_retribusi_id.required' => 'Rincian Retribusi wajib dipilih',
            'master_sub_rincian_retribusi_id.required' => 'Sub Rincian Retribusi wajib dipilih',
            'target_amount.required' => 'Target Pendapatan wajib diisi',
            'target_amount.min' => 'Target Pendapatan harus lebih dari 0',
            'tahun.required' => 'Tahun wajib dipilih',
        ]);

        RetributionTargets::create($validated);
        $this->bumpDashboardCacheVersion();

        return redirect()->route('retribution-target.index')
            ->with('success', 'Target retribusi berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = RetributionTargets::with(['opd', 'retribusi', 'lokasi', 'rincianRetribusi', 'subRincianRetribusi'])->findOrFail($id);
        return view('retribution-target.show', compact('data'));
    }

    public function edit($id)
    {
        $data = RetributionTargets::findOrFail($id);
        $opds = MasterOpd::orderBy('nama_opd')->get();
        $retribusis = MasterRetribusi::orderBy('nama_retribusi')->get();
        $lokasis = MasterLokasiObjek::orderBy('nama_lokasi')->get();
        $tahuns = range(date('Y') + 1, date('Y') - 5);
        return view('retribution-target.edit', compact('data', 'opds', 'retribusis', 'lokasis', 'tahuns'));
    }

    public function update(Request $request, $id)
    {
        $data = RetributionTargets::findOrFail($id);

        $validated = $request->validate([
            'master_opd_id' => 'required|exists:master_opd,id',
            'master_retribusi_id' => 'required|exists:master_retribusi,id',
            'master_lokasi_id' => 'required|exists:master_lokasi_objek,id',
            'master_rincian_retribusi_id' => 'required|exists:master_rincian_retribusi,id',
            'master_sub_rincian_retribusi_id' => 'required|exists:master_sub_rincian_retribusi,id',
            'target_amount' => 'required|numeric|min:1',
            'tahun' => 'required|integer|min:2000|max:2100',
        ], [
            'master_opd_id.required' => 'OPD wajib dipilih',
            'master_lokasi_id.required' => 'Lokasi wajib dipilih',
            'master_retribusi_id.required' => 'Jenis Retribusi wajib dipilih',
            'master_rincian_retribusi_id.required' => 'Rincian Retribusi wajib dipilih',
            'master_sub_rincian_retribusi_id.required' => 'Sub Rincian Retribusi wajib dipilih',
            'target_amount.required' => 'Target Pendapatan wajib diisi',
            'target_amount.min' => 'Target Pendapatan harus lebih dari 0',
            'tahun.required' => 'Tahun wajib dipilih',
        ]);

        $data->update($validated);
        $this->bumpDashboardCacheVersion();

        return redirect()->route('retribution-target.index')
            ->with('success', 'Target retribusi berhasil diperbarui');
    }

    public function destroy($id)
    {
        $data = RetributionTargets::findOrFail($id);
        $data->delete();
        $this->bumpDashboardCacheVersion();

        return redirect()->route('retribution-target.index')
            ->with('success', 'Target retribusi berhasil dihapus');
    }

    private function bumpDashboardCacheVersion(): void
    {
        $currentVersion = (int) Cache::get('dashboard_cache_version', 1);
        Cache::forever('dashboard_cache_version', $currentVersion + 1);
    }

    public function getLokasiByOpd(Request $request)
    {
        $opdId = $request->get('opd_id');
        $lokasis = MasterLokasiObjek::when($opdId, function ($query) use ($opdId) {
            return $query->where('master_opd_id', $opdId);
        })
            ->orderBy('nama_lokasi')
            ->get(['id', 'nama_lokasi']);

        return response()->json($lokasis);
    }

    public function getRincianByRetribusi(Request $request)
    {
        $retribusiId = $request->get('retribusi_id');
        $rincians = MasterRincianRetribusi::when($retribusiId, function ($query) use ($retribusiId) {
            return $query->where('master_tipe_retribusi_id', $retribusiId);
        })
            ->orderBy('rincian_retribusi')
            ->get(['id', 'rincian_retribusi']);

        return response()->json($rincians);
    }

    public function getSubRincianByRincian(Request $request)
    {
        $rincianId = $request->get('rincian_id');
        $subRincians = MasterSubRincianRetribusi::when($rincianId, function ($query) use ($rincianId) {
            return $query->where('master_rincian_retribusi_id', $rincianId);
        })
            ->orderBy('nama_sub_rincian_retribusi')
            ->get(['id', 'nama_sub_rincian_retribusi']);

        return response()->json($subRincians);
    }

}
