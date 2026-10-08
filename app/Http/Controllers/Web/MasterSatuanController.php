<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MasterSatuan;
use Illuminate\Http\Request;

class MasterSatuanController extends Controller
{
    public function index()
    {
        $data = MasterSatuan::paginate(10);
        return view('master.satuan.index', compact('data'));
    }

    public function create()
    {
        return view('master.satuan.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_satuan' => 'required|string|max:255',
        ]);

        MasterSatuan::create($validated);

        return redirect()->route('master.satuan.index')
            ->with('success', 'Data satuan berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = MasterSatuan::findOrFail($id);
        return view('master.satuan.show', compact('data'));
    }

    public function edit($id)
    {
        $data = MasterSatuan::findOrFail($id);
        return view('master.satuan.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $data = MasterSatuan::findOrFail($id);

        $validated = $request->validate([
            'nama_satuan' => 'required|string|max:255',
        ]);

        $data->update($validated);

        return redirect()->route('master.satuan.index')
            ->with('success', 'Data satuan berhasil diperbarui');
    }

    public function destroy($id)
    {
        $data = MasterSatuan::findOrFail($id);
        $data->delete();

        return redirect()->route('master.satuan.index')
            ->with('success', 'Data satuan berhasil dihapus');
    }
}
