<?php

namespace App\Http\Controllers;

use App\Models\MasterChief;
use Tymon\JWTAuth\Facades\JWTAuth;

class MasterChiefController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getChief()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (! $user) {
            return response()->json(['status' => 201, 'message' => "You're not authenticated"]);
        }
        $chief = MasterChief::where('is_active', 1)->get();

        return response()->json(['status' => 200, 'message' => 'Successfully get chiefs', 'data' => $chief]);
    }

    public function showChief($id)
    {

        $chief = MasterChief::findOrFail($id);

        return view('chief_display', ['chief' => $chief]);
    }
}
