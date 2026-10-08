<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MasterDocument;
use Illuminate\Http\Request;

class MasterDocumentWebController extends Controller
{
    public function index()
    {
        $data = MasterDocument::paginate(10);
        return view('master.document.index', compact('data'));
    }

    public function create()
    {
        return view('master.document.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_document' => 'required|string|max:255',
        ]);

        MasterDocument::create($validated);

        return redirect()->route('master.document.index')
            ->with('success', 'Data dokumen berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = MasterDocument::findOrFail($id);
        return view('master.document.show', compact('data'));
    }

    public function edit($id)
    {
        $data = MasterDocument::findOrFail($id);
        return view('master.document.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $data = MasterDocument::findOrFail($id);

        $validated = $request->validate([
            'nama_document' => 'required|string|max:255',
        ]);

        $data->update($validated);

        return redirect()->route('master.document.index')
            ->with('success', 'Data dokumen berhasil diperbarui');
    }

    public function destroy($id)
    {
        $data = MasterDocument::findOrFail($id);
        $data->delete();

        return redirect()->route('master.document.index')
            ->with('success', 'Data dokumen berhasil dihapus');
    }
}
