<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MasterChief;
use App\Models\MasterOpd;
use Illuminate\Http\Request;

class MasterChiefWebController extends Controller
{
    public function index()
    {
        $data = MasterChief::paginate(10);
        return view('master.chief.index', compact('data'));
    }

    public function create()
    {
        $opds = MasterOpd::orderBy('nama_opd')->get();
        return view('master.chief.create', compact('opds'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'chief_name' => 'required|string|max:255',
            'chief_nip' => 'nullable|string|max:50',
            'master_opd_id' => 'required|exists:master_opd,id',
        ]);

        MasterChief::create($validated);

        return redirect()->route('master.chief.index')
            ->with('success', 'Data berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = MasterChief::findOrFail($id);
        return view('master.chief.show', compact('data'));
    }

    public function edit($id)
    {
        $data = MasterChief::findOrFail($id);
        $opds = MasterOpd::orderBy('nama_opd')->get();
        return view('master.chief.edit', compact('data', 'opds'));
    }

    public function update(Request $request, $id)
    {
        $data = MasterChief::findOrFail($id);

        $validated = $request->validate([
            'chief_name' => 'required|string|max:255',
            'chief_nip' => 'nullable|string|max:50',
            'master_opd_id' => 'required|exists:master_opd,id',
        ]);

        $data->update($validated);

        return redirect()->route('master.chief.index')
            ->with('success', 'Data berhasil diperbarui');
    }

    public function destroy($id)
    {
        $data = MasterChief::findOrFail($id);
        $data->delete();

        return redirect()->route('master.chief.index')
            ->with('success', 'Data berhasil dihapus');
    }
}
