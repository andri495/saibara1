<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterRetribusi;
use Tymon\JWTAuth\Facades\JWTAuth;

class RetributionController extends Controller
{
    public function getRetribution()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if ($user) {
            $retributions = MasterRetribusi::get();
            return response()->json(['status' => 200, 'message' => 'Successfully get the data', 'data' => $retributions]);
        }
        return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
    }
    public function getRetributionByLocation(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'lokasi_id' => 'required|exists:master_lokasi_objek,id'
        ]);

        $retributions = MasterRetribusi::whereHas('objek', function ($query) use ($request) {
            $query->where('master_lokasi_id', $request->lokasi_id);
        })
            ->with('rincianRetribusi')
            ->get();

        return response()->json([
            'status' => 200,
            'message' => 'Successfully get retribusi data for lokasi',
            'data' => $retributions
        ]);
    }
}
