<?php

namespace App\Http\Controllers;

use App\Models\CartObject;
use App\Http\Requests\StoreCartObjectRequest;
use App\Http\Requests\UpdateCartObjectRequest;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class CartObjectController extends Controller
{
    public function deleteObject(Request $request)
    {
        $user = JWTAutH::parseToken()->authenticate();
        if ($user) {
            $cartObject = CartObject::find($request->id);
            $cartObject->delete();
            return response()->json(['status' => 200, 'message' => 'Successfully delete the data', 'cart_object' => $cartObject]);
        }
        return response()->json(['status' => 201, 'message' => "You're not authenticated"]);
    }

    public function addQuantity(Request $request)
    {
        $user = JWTAutH::parseToken()->authenticate();
        if ($user) {
            $cartObject = CartObject::find($request->id);
            if ($request->quantity_tpj != null) {
                $cartObject->quantity_tpj = $request->quantity_tpj;
                $cartObject->save();
            }
            if ($request->quantity_satuan != null) {
                $cartObject->quantity_satuan = $request->quantity_satuan;
                $cartObject->save();
            }

            $cartObject->total_amount = $cartObject->quantity_tpj * $cartObject->quantity_satuan * $cartObject->object->harga_objek;
            $cartObject->save();
            $cartObject = CartObject::find($request->id);

            return response()->json(['status' => 200, 'message' => 'Successfully update the data', 'cart_object' => $cartObject]);
        }
        return response()->json(['status' => 201, 'message' => "You're not authenticated"]);
    }
}
