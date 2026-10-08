<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MasterRole;
use Illuminate\Http\Request;

class MasterRoleWebController extends Controller
{
    public function index()
    {
        $data = MasterRole::paginate(10);
        return view('master.role.index', compact('data'));
    }

    public function create()
    {
        return view('master.role.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_role' => 'required|string|max:255',
        ]);

        MasterRole::create($validated);

        return redirect()->route('master.role.index')
            ->with('success', 'Data role berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = MasterRole::findOrFail($id);
        return view('master.role.show', compact('data'));
    }

    public function edit($id)
    {
        $data = MasterRole::findOrFail($id);
        return view('master.role.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $data = MasterRole::findOrFail($id);

        $validated = $request->validate([
            'nama_role' => 'required|string|max:255',
        ]);

        $data->update($validated);

        return redirect()->route('master.role.index')
            ->with('success', 'Data role berhasil diperbarui');
    }

    public function destroy($id)
    {
        $data = MasterRole::findOrFail($id);
        $data->delete();

        return redirect()->route('master.role.index')
            ->with('success', 'Data role berhasil dihapus');
    }
}
