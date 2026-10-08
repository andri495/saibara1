<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MasterTpj;
use Illuminate\Http\Request;

class MasterTpjController extends Controller
{
    public function index()
    {
        $data = MasterTpj::paginate(10);
        return view('master.tpj.index', compact('data'));
    }

    public function create()
    {
        return view('master.tpj.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_tpj' => 'required|string|max:255',
        ]);

        MasterTpj::create($validated);

        return redirect()->route('master.tpj.index')
            ->with('success', 'Data TPJ berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = MasterTpj::findOrFail($id);
        return view('master.tpj.show', compact('data'));
    }

    public function edit($id)
    {
        $data = MasterTpj::findOrFail($id);
        return view('master.tpj.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $data = MasterTpj::findOrFail($id);

        $validated = $request->validate([
            'nama_tpj' => 'required|string|max:255',
        ]);

        $data->update($validated);

        return redirect()->route('master.tpj.index')
            ->with('success', 'Data TPJ berhasil diperbarui');
    }

    public function destroy($id)
    {
        $data = MasterTpj::findOrFail($id);
        $data->delete();

        return redirect()->route('master.tpj.index')
            ->with('success', 'Data TPJ berhasil dihapus');
    }
}
