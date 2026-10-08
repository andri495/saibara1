<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MasterOpd;
use Illuminate\Http\Request;

class MasterOpdWebController extends Controller
{
    public function index()
    {
        $data = MasterOpd::paginate(10);
        return view('master.opd.index', compact('data'));
    }

    public function create()
    {
        return view('master.opd.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_opd' => 'required|string|max:255',
        ]);

        MasterOpd::create($validated);

        return redirect()->route('master.opd.index')
            ->with('success', 'Data OPD berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = MasterOpd::findOrFail($id);
        return view('master.opd.show', compact('data'));
    }

    public function edit($id)
    {
        $data = MasterOpd::findOrFail($id);
        return view('master.opd.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $data = MasterOpd::findOrFail($id);

        $validated = $request->validate([
            'nama_opd' => 'required|string|max:255',
        ]);

        $data->update($validated);

        return redirect()->route('master.opd.index')
            ->with('success', 'Data OPD berhasil diperbarui');
    }

    public function destroy($id)
    {
        $data = MasterOpd::findOrFail($id);
        $data->delete();

        return redirect()->route('master.opd.index')
            ->with('success', 'Data OPD berhasil dihapus');
    }
}
