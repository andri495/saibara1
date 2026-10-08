<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'no_telp' => 'required|string|max:255',
            'master_role_id' => 'required',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        // Generate NPWRD with format: 18 63 dd mm yy xxxx
        $now = now();
        $npwrd = '18' . '63' . $now->format('dmy') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        $user = User::create([
            'name' => $request->name,
            'address' => $request->address,
            'master_role_id' => $request->master_role_id,
            'master_opd_id' => $request->master_opd_id,
            'master_lokasi_id' => 0,
            'nama_instansi' => $request->nama_instansi,
            'no_telp' => $request->no_telp,
            'email' => $request->email,
            'npwrd' => $npwrd,
            'password' => Hash::make($request->password),
        ]);
        event(new Registered($user));

        return response()->json([
            'status' => 200,
            'message' => 'User successfully registered. Please check your email for verification.',
        ]);
    }
    public function registerExternal(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'no_telp' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        // Generate NPWRD with format: 18 63 dd mm yy xxxx
        $now = now();
        $npwrd = '18' . '63' . $now->format('dmy') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        $user = User::create([
            'name' => $request->name,
            'address' => $request->address,
            'master_role_id' => 7,
            'master_opd_id' => 22,
            'master_lokasi_id' => 0,
            'nama_instansi' => $request->nama_instansi,
            'no_telp' => $request->no_telp,
            'email' => $request->email,
            'npwrd' => $npwrd,
            'email_verified_at' => now(),
            'password' => Hash::make($request->password),
        ]);
        event(new Registered($user));
        $user = User::where('email', $request->email)->where('master_role_id', 7)->where('master_opd_id', 22)->first();
        $token = auth()->login($user);

        return $this->respondWithToken($token, $user->email);
    }
    public function login(Request $request)
    {
        $credentials = $request->only(['email', 'password']);
        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['status' => 205, 'message' => 'Email atau Password salah']);
        } else {
            if ($request->user()->email_verified_at == null) {
                $request->user()->sendEmailVerificationNotification();

                return response()->json(['status' => 203, 'message' => 'Email belum diverifikasi. Cek email anda untuk verifikasi email']);
            }

            return $this->respondWithToken($token, $request->email);
        }
    }

    public function loginExternal(Request $request)
    {

        // Validate the request
        $request->validate([
            'email' => 'required|email',
        ]);

        // Find user by email
        $user = User::where('email', $request->email)->where('master_role_id', 7)->where('master_opd_id', 22)->first();

        if (! $user) {
            return response()->json([
                'status' => 404,
                'message' => 'User not found',
            ], 404);
        }

        // Generate JWT token for the user
        $token = auth()->login($user);

        return $this->respondWithToken($token, $user->email);
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['status' => 200, 'message' => 'Successfully logged out']);
    }

    protected function respondWithToken($token, $email)
    {
        $data = User::with(['masterOpd', 'masterRole', 'masterLokasi'])->where('email', $email)->first();

        return response()->json([
            'status' => 200,
            'message' => 'bearer',
            'access_token' => $token,
            'data' => $data,
        ]);
    }

    /**
     * Send password reset link to user's email
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'status' => 200,
                'message' => 'Password reset link has been sent to your email address.',
            ]);
        }

        return response()->json([
            'status' => 400,
            'message' => 'Unable to send reset link. Please try again.',
        ], 400);
    }

    /**
     * Reset user's password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = Hash::make($password);

                // Only set remember token if the column exists
                if (Schema::hasColumn('users', 'remember_token')) {
                    $user->setRememberToken(Str::random(60));
                }

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'status' => 200,
                'message' => 'Password has been reset successfully.',
            ]);
        }

        return response()->json([
            'status' => 400,
            'message' => __($status),
        ], 400);
    }

    /**
     * Show the password reset form
     */
    public function showResetForm(Request $request)
    {
        $token = $request->query('token');
        $email = $request->query('email');

        if (! $token || ! $email) {
            abort(404);
        }

        return view('reset_password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    public function showResetSucess()
    {

        return view('password_reset_success');
    }
}
