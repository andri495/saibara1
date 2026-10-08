<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    public function profile()
    {
        $user = JWTAutH::parseToken()->authenticate();
        if ($user) {
            $profile = User::with(['masterOpd', 'masterRole'])->where('email', $user->email)->first();

            return response()->json(['status' => 200, 'message' => 'Get Data Successfu', 'data' => $profile]);
        } else {
            return response()->json(['status' => 401, 'message' => "You're not authenticated"]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['status' => 401, 'message' => "You're not authenticated", 'data' => []], 401);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'address' => 'sometimes|string|max:500',
                'no_telp' => 'sometimes|string|max:20',
                'nama_instansi' => 'sometimes|string|max:255',
                'profile_pict' => 'sometimes|file|image|mimes:jpeg,png,jpg,gif|max:10240',
                'password' => 'sometimes|string|min:6|confirmed',
            ]);

            // Remove unchangeable fields if they were sent
            unset($validated['email']);
            unset($validated['master_opd_id']);
            unset($validated['master_role_id']);

            // Handle profile picture upload
            if ($request->hasFile('profile_pict')) {
                $file = $request->file('profile_pict');
                $extension = $file->getClientOriginalExtension();
                $filename = Str::random(40) . '.' . $extension;
                $path = 'private/profile_pictures/' . $filename;

                // Delete old profile picture if exists
                if ($user->profile_pict && Storage::disk('local')->exists($user->profile_pict)) {
                    Storage::disk('local')->delete($user->profile_pict);
                }

                // Store new profile picture in private storage
                Storage::disk('local')->put($path, file_get_contents($file));
                $validated['profile_pict'] = $path;
            } else {
                // Remove profile_pict from validated if no file uploaded
                unset($validated['profile_pict']);
            }

            // Hash password if provided
            if (isset($validated['password'])) {
                $validated['password'] = bcrypt($validated['password']);
            }

            // Update user profile
            $user->update($validated);

            return response()->json([
                'status' => 200,
                'message' => 'Profile updated successfully',
                'data' => $user->fresh()->load(['masterOpd', 'masterRole']),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return response()->json([
                'status' => 422,
                'message' => $firstError,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }

    /**
     * Get profile picture from private storage
     */
    public function getProfilePicture($filename)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $path = 'private/profile_pictures/' . $filename;

        if (! Storage::disk('local')->exists($path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $file = Storage::disk('local')->get($path);
        $mimeType = mime_content_type(storage_path('app/' . $path));

        return response($file, 200)->header('Content-Type', $mimeType);
    }
}
