<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MasterRincianRetribusi;
use App\Models\MasterRetribusi;
use App\Models\MasterSubRincianRetribusi;
use Illuminate\Http\Request;

class MasterSubRincianRetribusiController extends Controller
{
    public function index()
    {
        $data = MasterSubRincianRetribusi::paginate(10);
        return view('master.sub-rincian-retribusi.index', compact('data'));
    }

    public function create()
    {
        $retribusis = MasterRetribusi::orderBy('nama_retribusi')->get();
        $rincians = MasterRincianRetribusi::orderBy('rincian_retribusi')->get();
        return view('master.sub-rincian-retribusi.create', compact('retribusis', 'rincians'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_sub_rincian_retribusi' => 'required|string|max:255',
            'master_rincian_retribusi_id' => 'required|exists:master_rincian_retribusi,id',
        ]);

        MasterSubRincianRetribusi::create($validated);

        return redirect()->route('master.sub-rincian-retribusi.index')
            ->with('success', 'Data berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = MasterSubRincianRetribusi::findOrFail($id);
        return view('master.sub-rincian-retribusi.show', compact('data'));
    }

    public function edit($id)
    {
        $data = MasterSubRincianRetribusi::with('rincianRetribusi.tipeRetribusi')->findOrFail($id);
        $retribusis = MasterRetribusi::orderBy('nama_retribusi')->get();
        $rincians = MasterRincianRetribusi::orderBy('rincian_retribusi')->get();
        return view('master.sub-rincian-retribusi.edit', compact('data', 'retribusis', 'rincians'));
    }

    public function update(Request $request, $id)
    {
        $data = MasterSubRincianRetribusi::findOrFail($id);

        $validated = $request->validate([
            'nama_sub_rincian_retribusi' => 'required|string|max:255',
            'master_rincian_retribusi_id' => 'required|exists:master_rincian_retribusi,id',
        ]);

        $data->update($validated);

        return redirect()->route('master.sub-rincian-retribusi.index')
            ->with('success', 'Data berhasil diperbarui');
    }

    public function destroy($id)
    {
        $data = MasterSubRincianRetribusi::findOrFail($id);
        $data->delete();

        return redirect()->route('master.sub-rincian-retribusi.index')
            ->with('success', 'Data berhasil dihapus');
    }
}
