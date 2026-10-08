<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MasterRincianRetribusi;
use App\Models\MasterRetribusi;
use Illuminate\Http\Request;

/**
 * MasterRincianRetribusiController
 * 
 * Controller untuk mengelola Master Rincian Retribusi
 * Template untuk master data CRUD
 * 
 * @package App\Http\Controllers\Web
 */
class MasterRincianRetribusiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $data = MasterRincianRetribusi::paginate(10);
        return view('master.rincian-retribusi.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $retribusis = MasterRetribusi::orderBy('nama_retribusi')->get();
        return view('master.rincian-retribusi.create', compact('retribusis'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'rincian_retribusi' => 'required|string|max:255',
            'master_tipe_retribusi_id' => 'required|exists:master_retribusi,id',
        ]);

        MasterRincianRetribusi::create($validated);

        return redirect()->route('master.rincian-retribusi.index')
            ->with('success', 'Data berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $data = MasterRincianRetribusi::findOrFail($id);
        return view('master.rincian-retribusi.show', compact('data'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $data = MasterRincianRetribusi::findOrFail($id);
        $retribusis = MasterRetribusi::orderBy('nama_retribusi')->get();
        return view('master.rincian-retribusi.edit', compact('data', 'retribusis'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $data = MasterRincianRetribusi::findOrFail($id);

        $validated = $request->validate([
            'rincian_retribusi' => 'required|string|max:255',
            'master_tipe_retribusi_id' => 'required|exists:master_retribusi,id',
        ]);

        $data->update($validated);

        return redirect()->route('master.rincian-retribusi.index')
            ->with('success', 'Data berhasil diperbarui');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $data = MasterRincianRetribusi::findOrFail($id);
        $data->delete();

        return redirect()->route('master.rincian-retribusi.index')
            ->with('success', 'Data berhasil dihapus');
    }
}
