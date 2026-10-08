<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;

class VerificationController extends Controller
{
    public function verify(Request $request)
    {
        if (!$request->hasValidSignature()) {
            return response()->json(["message" => "Invalid/Expired verification link"], 401);
        }

        $user = User::findOrFail($request->id);

        if ($user->hasVerifiedEmail()) {
            return response()->json(["message" => "Email already verified"], 400);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return view('email_verified', ['status' => 'success', 'message' => 'Email successfully verified!']);
        // return response()->json(["message" => "Email successfully verified"]);
    }

    public function resend(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    "status" => 404,
                    "message" => "User not found"
                ], 404);
            }

            try {
                $user->sendEmailVerificationNotification();
            } catch (\Exception $mailException) {
                \Log::error('Email sending failed: ' . $mailException->getMessage());
                return response()->json([
                    "status" => 500,
                    "message" => "Failed to send email",
                    "error" => $mailException->getMessage()
                ], 500);
            }

            return response()->json([
                "status" => 200,
                "message" => "Verification link sent to " . $request->email
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                "status" => 422,
                "message" => "Validation failed",
                "errors" => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Resend verification error: ' . $e->getMessage());
            return response()->json([
                "status" => 500,
                "message" => "Failed to process request",
                "error" => $e->getMessage()
            ], 500);
        }
    }
}
