<?php

namespace App\Http\Controllers;

use App\Models\MasterLokasiObjek;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class LokasiController extends Controller
{
    public function getParkingLocation()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (! $user) {
            return response()->json([
                'status' => 401,
                'message' => "You're not authenticated",
                'data' => [],
            ]);
        }

        $lokasi = MasterLokasiObjek::where('master_opd_id', $user->master_opd_id)->where('location_status', 1)->where('id', $user->master_lokasi_id)
            ->whereHas('objek', function ($query) {
                $query->where('master_type_object_id', 3); // Assuming '3' is the ID for parking
            })
            ->with(['objek' => function ($query) {
                $query->where('master_type_object_id', 3); // Assuming '3' is the ID for parking
            }])
            ->get();

        return response()->json([
            'status' => 200,
            'message' => 'Successfully Get Parking Location',
            'data' => $lokasi,
        ]);
    }

    public function getPenetapanKhususLocation()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (! $user) {
            return response()->json([
                'status' => 401,
                'message' => "You're not authenticated",
                'data' => [],
            ]);
        }

        $lokasi = MasterLokasiObjek::where('master_opd_id', $user->master_opd_id)->where('location_status', 1)
            ->where('id', $user->master_lokasi_id)
            ->whereHas('objek', function ($query) {
                $query->whereIn('master_type_object_id', [5, 13]); // ✅ OR condition
            })
            ->get();

        return response()->json([
            'status' => 200,
            'message' => 'Successfully Get Penetapan Khusus Location',
            'data' => $lokasi,
        ]);
    }

    public function getLocation(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (! $user) {
            return response()->json([
                'status' => 401,
                'message' => "You're not authenticated",
                'data' => [],
            ]);
        }

        $lokasi = MasterLokasiObjek::where('master_opd_id', $request->master_opd_id)->where('location_status', 1)
            ->get();

        return response()->json([
            'status' => 200,
            'message' => 'Successfully Get Location',
            'data' => $lokasi,
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
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
