<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MasterCurrency;
use App\Models\MasterLokasiObjek;
use App\Models\MasterObjek;
use App\Models\MasterObjectType;
use App\Models\MasterOpd;
use App\Models\MasterRetribusi;
use App\Models\MasterRincianRetribusi;
use App\Models\MasterRules;
use App\Models\MasterSatuan;
use App\Models\MasterStatusObjek;
use App\Models\MasterTpj;
use Illuminate\Http\Request;

class MasterObjekController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $opdFilter = $request->get('opd');
        $lokasiFilter = $request->get('lokasi');
        $retribusiFilter = $request->get('retribusi');
        $rincianFilter = $request->get('rincian');
        
        $data = MasterObjek::with(['opd', 'retribusi', 'lokasi', 'rincianRetribusi', 'typeObject', 'statusObjek'])
            ->when($opdFilter, function ($query) use ($opdFilter) {
                $query->where('master_opd_id', $opdFilter);
            })
            ->when($lokasiFilter, function ($query) use ($lokasiFilter) {
                $query->where('master_lokasi_id', $lokasiFilter);
            })
            ->when($retribusiFilter, function ($query) use ($retribusiFilter) {
                $query->where('master_retribusi_id', $retribusiFilter);
            })
            ->when($rincianFilter, function ($query) use ($rincianFilter) {
                $query->where('master_rincian_retribusi_id', $rincianFilter);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('sub_rincian_objek', 'like', '%' . $search . '%')
                      ->orWhere('rincian_objek', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('sub_rincian_objek')
            ->paginate(15);
        
        // Get filter options
        $opds = MasterOpd::orderBy('nama_opd')->get();
        $retribusis = MasterRetribusi::orderBy('nama_retribusi')->get();
        
        // Lokasi filtered by OPD if selected
        $lokasis = MasterLokasiObjek::when($opdFilter, function ($query) use ($opdFilter) {
                return $query->where('master_opd_id', $opdFilter);
            })
            ->orderBy('nama_lokasi')
            ->get();
        
        // Rincian filtered by Retribusi if selected
        $rincians = MasterRincianRetribusi::when($retribusiFilter, function ($query) use ($retribusiFilter) {
                return $query->where('master_tipe_retribusi_id', $retribusiFilter);
            })
            ->orderBy('rincian_retribusi')
            ->get();
        
        return view('master.objek.index', compact(
            'data', 'search', 'opds', 'lokasis', 'retribusis', 'rincians',
            'opdFilter', 'lokasiFilter', 'retribusiFilter', 'rincianFilter'
        ));
    }

    public function create()
    {
        $opds = MasterOpd::orderBy('nama_opd')->get();
        $retribusis = MasterRetribusi::orderBy('nama_retribusi')->get();
        $lokasis = MasterLokasiObjek::orderBy('nama_lokasi')->get();
        $rincians = MasterRincianRetribusi::orderBy('id')->get();
        $types = MasterObjectType::orderBy('type')->get();
        $statuses = MasterStatusObjek::orderBy('status_objek')->get();
        $satuans = MasterSatuan::orderBy('nama_satuan')->get();
        $rules = MasterRules::orderBy('id')->get();
        $tpjs = MasterTpj::orderBy('id')->get();
        $currencies = MasterCurrency::orderBy('id')->get();
        
        return view('master.objek.create', compact(
            'opds', 'retribusis', 'lokasis', 'rincians', 'types', 
            'statuses', 'satuans', 'rules', 'tpjs', 'currencies'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'master_opd_id' => 'required|exists:master_opd,id',
            'master_retribusi_id' => 'required|exists:master_retribusi,id',
            'master_lokasi_id' => 'required|exists:master_lokasi_objek,id',
            'master_rincian_retribusi_id' => 'required|exists:master_rincian_retribusi,id',
            'master_type_object_id' => 'required|exists:master_type_object,id',
            'master_status_objek_id' => 'required|exists:master_status_objek,id',
            'master_satuan_id' => 'required|exists:master_satuan,id',
            'master_tpj_id' => 'required|exists:master_tpj,id',
            'master_currency_id' => 'required|exists:master_currencies,id',
            'master_role_id' => 'nullable|exists:master_role,id',
            'sub_rincian_objek' => 'required|string|max:255',
            'rincian_objek' => 'required|string|max:255',
            'uraian_objek' => 'required|string',
            'harga_objek' => 'required|numeric|min:0',
            'harga_objek_asing' => 'required|numeric|min:0',
            'images' => 'nullable|string',
        ]);

        // Set default value for master_role_id if not provided
        if (!isset($validated['master_role_id']) || $validated['master_role_id'] === '') {
            $validated['master_role_id'] = 1; // Default role ID
        }

        MasterObjek::create($validated);

        return redirect()->route('master.objek.index')
            ->with('success', 'Data objek berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = MasterObjek::with([
            'opd', 'retribusi', 'lokasi', 'rincianRetribusi', 
            'typeObject', 'statusObjek', 'satuan', 'rules', 'tpj', 'currency'
        ])->findOrFail($id);
        
        return view('master.objek.show', compact('data'));
    }

    public function edit($id)
    {
        $data = MasterObjek::findOrFail($id);
        $opds = MasterOpd::orderBy('nama_opd')->get();
        $retribusis = MasterRetribusi::orderBy('nama_retribusi')->get();
        $lokasis = MasterLokasiObjek::orderBy('nama_lokasi')->get();
        $rincians = MasterRincianRetribusi::orderBy('id')->get();
        $types = MasterObjectType::orderBy('type')->get();
        $statuses = MasterStatusObjek::orderBy('status_objek')->get();
        $satuans = MasterSatuan::orderBy('nama_satuan')->get();
        $rules = MasterRules::orderBy('id')->get();
        $tpjs = MasterTpj::orderBy('id')->get();
        $currencies = MasterCurrency::orderBy('id')->get();
        
        return view('master.objek.edit', compact(
            'data', 'opds', 'retribusis', 'lokasis', 'rincians', 'types', 
            'statuses', 'satuans', 'rules', 'tpjs', 'currencies'
        ));
    }

    public function update(Request $request, $id)
    {
        $data = MasterObjek::findOrFail($id);

        $validated = $request->validate([
            'master_opd_id' => 'required|exists:master_opd,id',
            'master_retribusi_id' => 'required|exists:master_retribusi,id',
            'master_lokasi_id' => 'required|exists:master_lokasi_objek,id',
            'master_rincian_retribusi_id' => 'required|exists:master_rincian_retribusi,id',
            'master_type_object_id' => 'required|exists:master_type_object,id',
            'master_status_objek_id' => 'required|exists:master_status_objek,id',
            'master_satuan_id' => 'required|exists:master_satuan,id',
            'master_tpj_id' => 'required|exists:master_tpj,id',
            'master_currency_id' => 'required|exists:master_currencies,id',
            'master_role_id' => 'nullable|exists:master_role,id',
            'sub_rincian_objek' => 'required|string|max:255',
            'rincian_objek' => 'required|string|max:255',
            'uraian_objek' => 'required|string',
            'harga_objek' => 'required|numeric|min:0',
            'harga_objek_asing' => 'required|numeric|min:0',
            'images' => 'nullable|string',
        ]);

        // Set default value for master_role_id if not provided
        if (!isset($validated['master_role_id']) || $validated['master_role_id'] === '') {
            $validated['master_role_id'] = 1; // Default role ID
        }

        $data->update($validated);

        return redirect()->route('master.objek.index')
            ->with('success', 'Data objek berhasil diperbarui');
    }

    public function destroy($id)
    {
        $data = MasterObjek::findOrFail($id);
        $data->delete();
        
        return redirect()->route('master.objek.index')
            ->with('success', 'Data objek berhasil dihapus');
    }

    // AJAX endpoint for getting lokasi by OPD
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

    // AJAX endpoint for getting rincian by Retribusi
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
}
