<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MasterRetribusi;
use Illuminate\Http\Request;

/**
 * MasterRetribusiController
 * 
 * Handles CRUD operations for Master Retribusi via web interface
 * Uses RESTful resource controller pattern
 * 
 * @package App\Http\Controllers\Web
 */
class MasterRetribusiController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $retribusi = MasterRetribusi::paginate(10);
        
        return view('master.retribusi.index', compact('retribusi'));
    }

    /**
     * Show the form for creating a new resource.
     * 
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('master.retribusi.create');
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
            'nama_retribusi' => 'required|string|max:255',
        ]);

        MasterRetribusi::create($validated);

        return redirect()->route('master.retribusi.index')
            ->with('success', 'Data retribusi berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     * 
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $retribusi = MasterRetribusi::findOrFail($id);
        
        return view('master.retribusi.show', compact('retribusi'));
    }

    /**
     * Show the form for editing the specified resource.
     * 
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $retribusi = MasterRetribusi::findOrFail($id);
        
        return view('master.retribusi.edit', compact('retribusi'));
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
        $validated = $request->validate([
            'nama_retribusi' => 'required|string|max:255',
        ]);

        $retribusi = MasterRetribusi::findOrFail($id);
        $retribusi->update($validated);

        return redirect()->route('master.retribusi.index')
            ->with('success', 'Data retribusi berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $retribusi = MasterRetribusi::findOrFail($id);
        $retribusi->delete();

        return redirect()->route('master.retribusi.index')
            ->with('success', 'Data retribusi berhasil dihapus.');
    }
}
