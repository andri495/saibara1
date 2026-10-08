<?php

namespace App\Http\Controllers;

use App\Models\MasterRole;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getRole()
    {
        $role = MasterRole::all();
        return response()->json([
            'status' => 200,
            'message' => 'Get Data Success',
            'data' => $role
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
    public function show(MasterRole $masterRole)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MasterRole $masterRole)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MasterRole $masterRole)
    {
        //
    }
}
