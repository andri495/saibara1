<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MasterObjectType;
use Illuminate\Http\Request;

class MasterTypeObjectController extends Controller
{
    public function index()
    {
        $data = MasterObjectType::paginate(10);

        return view('master.type-object.index', compact('data'));
    }

    public function create()
    {
        return view('master.type-object.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|max:255',
        ]);

        MasterObjectType::create($validated);

        return redirect()->route('master.type-object.index')
            ->with('success', 'Data tipe objek berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = MasterObjectType::findOrFail($id);

        return view('master.type-object.show', compact('data'));
    }

    public function edit($id)
    {
        $data = MasterObjectType::findOrFail($id);

        return view('master.type-object.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $data = MasterObjectType::findOrFail($id);

        $validated = $request->validate([
            'type' => 'required|string|max:255',
        ]);

        $data->update($validated);

        return redirect()->route('master.type-object.index')
            ->with('success', 'Data tipe objek berhasil diperbarui');
    }

    public function destroy($id)
    {
        $data = MasterObjectType::findOrFail($id);
        $data->delete();

        return redirect()->route('master.type-object.index')
            ->with('success', 'Data tipe objek berhasil dihapus');
    }
}
