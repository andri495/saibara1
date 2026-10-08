<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MasterLokasiObjek;
use App\Models\MasterOpd;
use Illuminate\Http\Request;

class MasterLokasiObjekController extends Controller
{
    public function index()
    {
        $data = MasterLokasiObjek::paginate(10);
        return view('master.lokasi-objek.index', compact('data'));
    }

    public function create()
    {
        $opds = MasterOpd::orderBy('nama_opd')->get();
        return view('master.lokasi-objek.create', compact('opds'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_lokasi' => 'required|string|max:255',
            'master_opd_id' => 'required|exists:master_opd,id',
        ]);

        MasterLokasiObjek::create($validated);

        return redirect()->route('master.lokasi-objek.index')
            ->with('success', 'Data lokasi berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = MasterLokasiObjek::findOrFail($id);
        return view('master.lokasi-objek.show', compact('data'));
    }

    public function edit($id)
    {
        $data = MasterLokasiObjek::findOrFail($id);
        $opds = MasterOpd::orderBy('nama_opd')->get();
        return view('master.lokasi-objek.edit', compact('data', 'opds'));
    }

    public function update(Request $request, $id)
    {
        $data = MasterLokasiObjek::findOrFail($id);

        $validated = $request->validate([
            'nama_lokasi' => 'required|string|max:255',
            'master_opd_id' => 'required|exists:master_opd,id',
        ]);

        $data->update($validated);

        return redirect()->route('master.lokasi-objek.index')
            ->with('success', 'Data lokasi berhasil diperbarui');
    }

    public function destroy($id)
    {
        $data = MasterLokasiObjek::findOrFail($id);
        $data->delete();

        return redirect()->route('master.lokasi-objek.index')
            ->with('success', 'Data lokasi berhasil dihapus');
    }
}
