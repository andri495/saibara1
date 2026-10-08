<?php

namespace App\Http\Controllers;

use App\Models\MasterChief;
use App\Models\MasterDocument;
use App\Models\MasterLokasiObjek;
use App\Models\MasterObjek;
use App\Models\MasterObjectType;
use App\Models\MasterOpd;
use App\Models\MasterPayment;
use App\Models\MasterPaymentStatus;
use App\Models\MasterRetribusi;
use App\Models\MasterRincianRetribusi;
use App\Models\MasterSubRincianRetribusi;
use App\Models\MasterRole;
use App\Models\MasterSatuan;
use App\Models\MasterTpj;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class MasterDataController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    // ==================== MASTER RETRIBUSI ====================
    public function getRetribusi()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $data = MasterRetribusi::all();
        return response()->json([
            'status' => 200,
            'message' => 'Get Master Retribusi Success',
            'data' => $data,
        ]);
    }

    public function createRetribusi(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'nama_retribusi' => 'required|string',
        ]);

        $data = MasterRetribusi::create([
            'nama_retribusi' => $request->nama_retribusi,
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'Master Retribusi created successfully',
            'data' => $data,
        ]);
    }

    public function updateRetribusi(Request $request, $id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'nama_retribusi' => 'required|string',
        ]);

        $data = MasterRetribusi::find($id);
        if (!$data) {
            return response()->json(['status' => 404, 'message' => 'Master Retribusi not found']);
        }

        $data->update([
            'nama_retribusi' => $request->nama_retribusi,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Master Retribusi updated successfully',
            'data' => $data,
        ]);
    }

    // ==================== MASTER RINCIAN RETRIBUSI ====================
    public function getRincianRetribusi(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $query = MasterRincianRetribusi::query();

        if ($request->has('master_retribusi_id') && $request->master_retribusi_id != null) {
            $query->where('master_tipe_retribusi_id', $request->master_retribusi_id);
        }

        $data = $query->get();
        return response()->json([
            'status' => 200,
            'message' => 'Get Master Rincian Retribusi Success',
            'data' => $data,
        ]);
    }

    public function createRincianRetribusi(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'rincian_retribusi' => 'required|string',
            'master_tipe_retribusi_id' => 'required|integer|exists:master_retribusi,id',
        ]);

        $data = MasterRincianRetribusi::create([
            'rincian_retribusi' => $request->rincian_retribusi,
            'master_tipe_retribusi_id' => $request->master_tipe_retribusi_id,
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'Master Rincian Retribusi created successfully',
            'data' => $data,
        ]);
    }

    public function updateRincianRetribusi(Request $request, $id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'rincian_retribusi' => 'required|string',
            'master_tipe_retribusi_id' => 'required|integer|exists:master_retribusi,id',
        ]);

        $data = MasterRincianRetribusi::find($id);
        if (!$data) {
            return response()->json(['status' => 404, 'message' => 'Master Rincian Retribusi not found']);
        }

        $data->update([
            'rincian_retribusi' => $request->rincian_retribusi,
            'master_tipe_retribusi_id' => $request->master_tipe_retribusi_id,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Master Rincian Retribusi updated successfully',
            'data' => $data,
        ]);
    }

    // ==================== MASTER SUB RINCIAN RETRIBUSI ====================
    public function getSubRincianRetribusi(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $query = MasterSubRincianRetribusi::with(['rincianRetribusi']);

        if ($request->has('master_rincian_retribusi_id') && $request->master_rincian_retribusi_id != null) {
            $query->where('master_rincian_retribusi_id', $request->master_rincian_retribusi_id);
        }

        $data = $query->get();
        return response()->json([
            'status' => 200,
            'message' => 'Get Master Sub Rincian Retribusi Success',
            'data' => $data,
        ]);
    }

    public function createSubRincianRetribusi(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'nama_sub_rincian_retribusi' => 'required|string',
            'master_rincian_retribusi_id' => 'required|integer|exists:master_rincian_retribusi,id',
        ]);

        $data = MasterSubRincianRetribusi::create([
            'nama_sub_rincian_retribusi' => $request->nama_sub_rincian_retribusi,
            'master_rincian_retribusi_id' => $request->master_rincian_retribusi_id,
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'Master Sub Rincian Retribusi created successfully',
            'data' => $data,
        ]);
    }

    public function updateSubRincianRetribusi(Request $request, $id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'nama_sub_rincian_retribusi' => 'required|string',
            'master_rincian_retribusi_id' => 'required|integer|exists:master_rincian_retribusi,id',
        ]);

        $data = MasterSubRincianRetribusi::find($id);
        if (!$data) {
            return response()->json(['status' => 404, 'message' => 'Master Sub Rincian Retribusi not found']);
        }

        $data->update([
            'nama_sub_rincian_retribusi' => $request->nama_sub_rincian_retribusi,
            'master_rincian_retribusi_id' => $request->master_rincian_retribusi_id,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Master Sub Rincian Retribusi updated successfully',
            'data' => $data,
        ]);
    }

    // ==================== MASTER CHIEF ====================
    public function getChief()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $data = MasterChief::with(['masterOpd', 'masterRole'])->get();
        return response()->json([
            'status' => 200,
            'message' => 'Get Master Chief Success',
            'data' => $data,
        ]);
    }

    public function createChief(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'chief_name' => 'required|string',
            'chief_nip' => 'required|string',
            'master_opd_id' => 'required|integer|exists:master_opd,id',
            'master_role_id' => 'required|integer|exists:master_role,id',
        ]);

        $data = MasterChief::create([
            'chief_name' => $request->chief_name,
            'chief_nip' => $request->chief_nip,
            'master_opd_id' => $request->master_opd_id,
            'master_role_id' => $request->master_role_id,
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'Master Chief created successfully',
            'data' => $data,
        ]);
    }

    public function updateChief(Request $request, $id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'chief_name' => 'required|string',
            'chief_nip' => 'required|string',
            'master_opd_id' => 'required|integer|exists:master_opd,id',
            'master_role_id' => 'required|integer|exists:master_role,id',
        ]);

        $data = MasterChief::find($id);
        if (!$data) {
            return response()->json(['status' => 404, 'message' => 'Master Chief not found']);
        }

        $data->update([
            'chief_name' => $request->chief_name,
            'chief_nip' => $request->chief_nip,
            'master_opd_id' => $request->master_opd_id,
            'master_role_id' => $request->master_role_id,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Master Chief updated successfully',
            'data' => $data,
        ]);
    }

    // ==================== MASTER OPD ====================
    public function getOpd()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $data = MasterOpd::with(['lokasi'])->get();
        return response()->json([
            'status' => 200,
            'message' => 'Get Master OPD Success',
            'data' => $data,
        ]);
    }

    public function createOpd(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'nama_opd' => 'required|string',
        ]);

        $data = MasterOpd::create([
            'nama_opd' => $request->nama_opd,
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'Master OPD created successfully',
            'data' => $data,
        ]);
    }

    public function updateOpd(Request $request, $id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'nama_opd' => 'required|string',
        ]);

        $data = MasterOpd::find($id);
        if (!$data) {
            return response()->json(['status' => 404, 'message' => 'Master OPD not found']);
        }

        $data->update([
            'nama_opd' => $request->nama_opd,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Master OPD updated successfully',
            'data' => $data,
        ]);
    }

    // ==================== MASTER LOKASI OBJEK ====================
    public function getLokasiObjek(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $query = MasterLokasiObjek::with(['masterOpd']);

        if ($request->has('master_opd_id') && $request->master_opd_id != null) {
            $query->where('master_opd_id', $request->master_opd_id);
        }

        $data = $query->get();
        return response()->json([
            'status' => 200,
            'message' => 'Get Master Lokasi Objek Success',
            'data' => $data,
        ]);
    }

    public function createLokasiObjek(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'nama_lokasi' => 'required|string',
            'master_opd_id' => 'required|integer|exists:master_opd,id',
        ]);

        $data = MasterLokasiObjek::create([
            'nama_lokasi' => $request->nama_lokasi,
            'master_opd_id' => $request->master_opd_id,
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'Master Lokasi Objek created successfully',
            'data' => $data,
        ]);
    }

    public function updateLokasiObjek(Request $request, $id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'nama_lokasi' => 'required|string',
            'master_opd_id' => 'required|integer|exists:master_opd,id',
        ]);

        $data = MasterLokasiObjek::find($id);
        if (!$data) {
            return response()->json(['status' => 404, 'message' => 'Master Lokasi Objek not found']);
        }

        $data->update([
            'nama_lokasi' => $request->nama_lokasi,
            'master_opd_id' => $request->master_opd_id,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Master Lokasi Objek updated successfully',
            'data' => $data,
        ]);
    }

    // ==================== MASTER TYPE OBJECT ====================
    public function getTypeObject()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $data = MasterObjectType::all();
        return response()->json([
            'status' => 200,
            'message' => 'Get Master Type Object Success',
            'data' => $data,
        ]);
    }

    public function createTypeObject(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'type' => 'required|string',
        ]);

        $data = MasterObjectType::create([
            'type' => $request->type,
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'Master Type Object created successfully',
            'data' => $data,
        ]);
    }

    public function updateTypeObject(Request $request, $id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'type' => 'required|string',
        ]);

        $data = MasterObjectType::find($id);
        if (!$data) {
            return response()->json(['status' => 404, 'message' => 'Master Type Object not found']);
        }

        $data->update([
            'type' => $request->type,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Master Type Object updated successfully',
            'data' => $data,
        ]);
    }

    // ==================== MASTER PAYMENT ====================
    public function getPayment()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $data = MasterPayment::all();
        return response()->json([
            'status' => 200,
            'message' => 'Get Master Payment Success',
            'data' => $data,
        ]);
    }

    public function createPayment(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'tipe_pembayaran' => 'required|string',
        ]);

        $data = MasterPayment::create([
            'tipe_pembayaran' => $request->tipe_pembayaran,
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'Master Payment created successfully',
            'data' => $data,
        ]);
    }

    public function updatePayment(Request $request, $id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'tipe_pembayaran' => 'required|string',
        ]);

        $data = MasterPayment::find($id);
        if (!$data) {
            return response()->json(['status' => 404, 'message' => 'Master Payment not found']);
        }

        $data->update([
            'tipe_pembayaran' => $request->tipe_pembayaran,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Master Payment updated successfully',
            'data' => $data,
        ]);
    }

    // ==================== MASTER PAYMENT STATUS ====================
    public function getPaymentStatus()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $data = MasterPaymentStatus::all();
        return response()->json([
            'status' => 200,
            'message' => 'Get Master Payment Status Success',
            'data' => $data,
        ]);
    }

    public function createPaymentStatus(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'status_pembayaran' => 'required|string',
        ]);

        $data = MasterPaymentStatus::create([
            'status_pembayaran' => $request->status_pembayaran,
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'Master Payment Status created successfully',
            'data' => $data,
        ]);
    }

    public function updatePaymentStatus(Request $request, $id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'status_pembayaran' => 'required|string',
        ]);

        $data = MasterPaymentStatus::find($id);
        if (!$data) {
            return response()->json(['status' => 404, 'message' => 'Master Payment Status not found']);
        }

        $data->update([
            'status_pembayaran' => $request->status_pembayaran,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Master Payment Status updated successfully',
            'data' => $data,
        ]);
    }

    // ==================== MASTER DOCUMENT ====================
    public function getDocument()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $data = MasterDocument::all();
        return response()->json([
            'status' => 200,
            'message' => 'Get Master Document Success',
            'data' => $data,
        ]);
    }

    public function createDocument(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'nama_document' => 'required|string',
        ]);

        $data = MasterDocument::create([
            'nama_document' => $request->nama_document,
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'Master Document created successfully',
            'data' => $data,
        ]);
    }

    public function updateDocument(Request $request, $id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'nama_document' => 'required|string',
        ]);

        $data = MasterDocument::find($id);
        if (!$data) {
            return response()->json(['status' => 404, 'message' => 'Master Document not found']);
        }

        $data->update([
            'nama_document' => $request->nama_document,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Master Document updated successfully',
            'data' => $data,
        ]);
    }

    // ==================== MASTER SATUAN ====================
    public function getSatuan()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $data = MasterSatuan::all();
        return response()->json([
            'status' => 200,
            'message' => 'Get Master Satuan Success',
            'data' => $data,
        ]);
    }

    public function createSatuan(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'nama_satuan' => 'required|string',
        ]);

        $data = MasterSatuan::create([
            'nama_satuan' => $request->nama_satuan,
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'Master Satuan created successfully',
            'data' => $data,
        ]);
    }

    public function updateSatuan(Request $request, $id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'nama_satuan' => 'required|string',
        ]);

        $data = MasterSatuan::find($id);
        if (!$data) {
            return response()->json(['status' => 404, 'message' => 'Master Satuan not found']);
        }

        $data->update([
            'nama_satuan' => $request->nama_satuan,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Master Satuan updated successfully',
            'data' => $data,
        ]);
    }

    // ==================== MASTER TPJ ====================
    public function getTpj()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $data = MasterTpj::all();
        return response()->json([
            'status' => 200,
            'message' => 'Get Master TPJ Success',
            'data' => $data,
        ]);
    }

    public function createTpj(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'nama_tpj' => 'required|string',
        ]);

        $data = MasterTpj::create([
            'nama_tpj' => $request->nama_tpj,
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'Master TPJ created successfully',
            'data' => $data,
        ]);
    }

    public function updateTpj(Request $request, $id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'nama_tpj' => 'required|string',
        ]);

        $data = MasterTpj::find($id);
        if (!$data) {
            return response()->json(['status' => 404, 'message' => 'Master TPJ not found']);
        }

        $data->update([
            'nama_tpj' => $request->nama_tpj,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Master TPJ updated successfully',
            'data' => $data,
        ]);
    }

    // ==================== MASTER ROLE ====================
    public function getRole()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $data = MasterRole::all();
        return response()->json([
            'status' => 200,
            'message' => 'Get Master Role Success',
            'data' => $data,
        ]);
    }

    public function createRole(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'nama_role' => 'required|string',
        ]);

        $data = MasterRole::create([
            'nama_role' => $request->nama_role,
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'Master Role created successfully',
            'data' => $data,
        ]);
    }

    public function updateRole(Request $request, $id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'nama_role' => 'required|string',
        ]);

        $data = MasterRole::find($id);
        if (!$data) {
            return response()->json(['status' => 404, 'message' => 'Master Role not found']);
        }

        $data->update([
            'nama_role' => $request->nama_role,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Master Role updated successfully',
            'data' => $data,
        ]);
    }

    // ==================== MASTER OBJEK ====================
    public function getObjek(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $query = MasterObjek::query();

        if ($request->has('master_opd_id') && $request->master_opd_id != null) {
            $query->where('master_opd_id', $request->master_opd_id);
        }

        if ($request->has('master_lokasi_id') && $request->master_lokasi_id != null) {
            $query->where('master_lokasi_id', $request->master_lokasi_id);
        }

        if ($request->has('master_retribusi_id') && $request->master_retribusi_id != null) {
            $query->where('master_retribusi_id', $request->master_retribusi_id);
        }

        $data = $query->get();
        return response()->json([
            'status' => 200,
            'message' => 'Get Master Objek Success',
            'data' => $data,
        ]);
    }

    public function createObjek(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (! $user) {
            return response()->json([
                'status' => 401,
                'message' => "You're not authenticated",
            ]);
        }

        try {
            // Validate request
            $validated = $request->validate([
                'master_opd_id' => 'required|integer|exists:master_opd,id',
                'master_lokasi_id' => 'required|integer|exists:master_lokasi_objek,id',
                'uraian_objek' => 'required|string|max:255',
                'master_retribusi_id' => 'required|integer|exists:master_retribusi,id',
                'master_rincian_retribusi_id' => 'required|integer|exists:master_rincian_retribusi,id',
                'sub_rincian_retribusi' => 'nullable|integer|exists:master_sub_rincian_retribusi,id',
                'rincian_objek' => 'required|string|max:255',
                'sub_rincian_objek' => 'required|string|max:255',
                'master_role_id' => 'nullable|integer|exists:master_role,id',
                'master_satuan_id' => 'required|integer|exists:master_satuan,id',
                'master_rules_id' => 'required|integer|exists:master_rules,id',
                'master_tpj_id' => 'required|integer|exists:master_tpj,id',
                'harga_objek' => 'required|numeric|min:0',
                'harga_objek_asing' => 'nullable|numeric|min:0',
                'master_status_objek_id' => 'required|integer|exists:master_status_objek,id',
                'required_document' => 'nullable|string',
                'master_type_object_id' => 'required|integer|exists:master_type_object,id',
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
                'master_currency_id' => 'required|integer|exists:master_currencies,id',
            ]);

            // Handle image uploads
            $imagePaths = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('objects', 'public');
                    $imagePaths[] = $path;
                }
                $validated['images'] = json_encode($imagePaths);
            }

            // Create object
            $object = MasterObjek::create($validated);

            // Load relationships
            $object->load([
                'lokasi',
                'rules',
                'opd',
                'retribusi',
                'rincianRetribusi',
                'subRincianRetribusi',
                'satuan',
                'tpj',
                'statusObjek',
                'typeObject',
                'currency',
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Object created successfully',
                'data' => $object,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to create object',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function searchObjek(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $query = MasterObjek::query();

        if ($request->has('search') && $request->search != null) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('uraian_objek', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('rincian_objek', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('sub_rincian_objek', 'LIKE', "%{$searchTerm}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $data = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'status' => 200,
            'message' => 'Search Master Objek Success',
            'data' => $data->items(),
            'pagination' => [
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
                'has_more' => $data->hasMorePages(),
            ],
        ]);
    }
    public function updateObjek(Request $request, $id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }

        $request->validate([
            'master_opd_id' => 'sometimes|integer|exists:master_opd,id',
            'master_lokasi_id' => 'sometimes|integer|exists:master_lokasi_objek,id',
            'uraian_objek' => 'sometimes|string',
            'master_retribusi_id' => 'sometimes|integer|exists:master_retribusi,id',
            'master_rincian_retribusi_id' => 'sometimes|integer|exists:master_rincian_retribusi,id',
            'sub_rincian_retribusi' => 'sometimes|integer|exists:master_sub_rincian_retribusi,id',
            'rincian_objek' => 'sometimes|string',
            'sub_rincian_objek' => 'sometimes|string',
            'master_role_id' => 'sometimes|integer|exists:master_role,id',
            'master_satuan_id' => 'sometimes|integer|exists:master_satuan,id',
            'master_rules_id' => 'sometimes|integer',
            'master_tpj_id' => 'sometimes|integer|exists:master_tpj,id',
            'harga_objek' => 'sometimes|numeric',
            'harga_objek_asing' => 'sometimes|numeric',
            'master_status_objek_id' => 'sometimes|integer',
            'required_document' => 'sometimes|string',
            'master_type_object_id' => 'sometimes|integer|exists:master_type_object,id',
            'images' => 'sometimes|string',
            'master_currency_id' => 'sometimes|integer',
        ]);

        $data = MasterObjek::find($id);
        if (!$data) {
            return response()->json(['status' => 404, 'message' => 'Master Objek not found']);
        }

        $data->update($request->only([
            'master_opd_id',
            'master_lokasi_id',
            'uraian_objek',
            'master_retribusi_id',
            'master_rincian_retribusi_id',
            'sub_rincian_retribusi',
            'rincian_objek',
            'sub_rincian_objek',
            'master_role_id',
            'master_satuan_id',
            'master_rules_id',
            'master_tpj_id',
            'harga_objek',
            'harga_objek_asing',
            'master_status_objek_id',
            'required_document',
            'master_type_object_id',
            'images',
            'master_currency_id',
        ]));

        return response()->json([
            'status' => 200,
            'message' => 'Master Objek updated successfully',
            'data' => $data,
        ]);
    }
}
