<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartObject;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CartController extends Controller
{

    /*************  ✨ Windsurf Command ⭐  *************/
    /**
     * Get the cart of a user
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */

    /*******  c5660db2-b2ed-4f73-ada1-b440e9a3f1cd  *******/
    public function getCart(Request $request)
    {
        $user = JWTAutH::parseToken()->authenticate();
        if ($user) {

            $opd_id = $request->opd_id;
            $retribusi_id = $request->retribusi_id;
            $cart = Cart::with(['cartObject' => function ($query) use ($opd_id) {
                $query->whereHas('object', function ($q) use ($opd_id) {
                    $q->where('master_opd_id', $opd_id);
                });
            }])
                ->where('user_id', $user->id)->where('object_retribusi_id', $retribusi_id)
                ->where('object_opd_id', $opd_id)
                ->first();
            if ($cart == null) {
                $cart = Cart::create([
                    'user_id' => $user->id,
                    'object_opd_id' => $request->opd_id,
                    'object_retribusi_id' => $request->retribusi_id
                ]);
                $cart = Cart::with(['cartObject' => function ($query) use ($opd_id) {
                    $query->whereHas('object', function ($q) use ($opd_id) {
                        $q->where('master_opd_id', $opd_id);
                    });
                }])->find($cart->id);
            }

            return response()->json(['status' => 200, 'message' => 'Successfully get the data', 'cart' => $cart]);
        }
        return response()->json(['status' => 201, 'message' => "You're not authenticated"]);
    }

    public function createCart(Request $request)
    {
        $user = JWTAutH::parseToken()->authenticate();
        if ($user) {
            $cart = Cart::where('user_id', $user->id)->where('object_opd_id', $request->object_opd_id)->where('object_retribusi_id', $request->object_retribusi_id)->first();
            if ($cart == null) {
                $cart = Cart::create([
                    'user_id' => $user->id,
                    'object_opd_id' => $request->object_opd_id,
                    'object_retribusi_id' => $request->object_retribusi_id
                ]);
            }
            // Handle file upload
            $documentPath = null;
            if ($request->hasFile('document_support_link')) {
                $file = $request->file('document_support_link');
                $extension = $file->getClientOriginalExtension();
                $filename = Str::random(40) . '.' . $extension;
                $documentPath = 'private/documents/' . $filename;
                Storage::disk('local')->put($documentPath, file_get_contents($file));
            }
            $cartObject = CartObject::create([
                'cart_id' => $cart->id,
                'object_id' => $request->object_id,
                'quantity_tpj' => $request->quantity_tpj,
                'quantity_satuan' => $request->quantity_satuan,
                'total_amount' => $request->total_amount,
                'document_result' => null,
                'document_payment_link' => null,
                'document_support_link' => $documentPath,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);

            return response()->json(['status' => 200, 'message' => 'Successfully add to cart', 'cart_object' => $cartObject]);
        }
        return response()->json(['status' => 201, 'message' => "You're not authenticated"]);
    }
}
