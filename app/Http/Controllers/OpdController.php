<?php

namespace App\Http\Controllers;

use App\Models\MasterOpd;
use Illuminate\Http\Request;

class OpdController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getOpd()
    {
        $opd = MasterOpd::with(['lokasi'])->where('id', '!=', 22)->get();

        return response()->json([
            'status' => 200,
            'message' => 'Get Data Success',
            'data' => $opd,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(MasterOpd $masterOpd)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MasterOpd $masterOpd)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MasterOpd $masterOpd)
    {
        //
    }
}
