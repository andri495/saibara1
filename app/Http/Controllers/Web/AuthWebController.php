<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

/**
 * AuthWebController
 * 
 * Handles web-based authentication (session-based)
 * Terpisah dari AuthController yang menggunakan JWT untuk API
 * 
 * @package App\Http\Controllers\Web
 */
class AuthWebController extends Controller
{
    /**
     * Show login form
     * 
     * @return \Illuminate\View\View
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Handle login request
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('web')->attempt($credentials, $request->filled('remember'))) {
            $user = Auth::guard('web')->user();

            if ($user->master_role_id != 6) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withInput($request->only('email'))
                    ->with('error', 'Akses ditolak. Akun Anda tidak memiliki izin untuk masuk ke panel ini.')
                    ->with('toast_type', 'error');
            }

            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Selamat datang, ' . $user->name . '!');
        }

        return back()
            ->withInput($request->only('email'))
            ->with('error', 'Email atau password salah.')
            ->with('toast_type', 'error');
    }

    /**
     * Show registration form
     * 
     * @return \Illuminate\View\View
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Handle registration request
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'npwrd' => 'nullable|string|max:50',
            'no_telp' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'nama_instansi' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'npwrd' => $validated['npwrd'] ?? null,
            'no_telp' => $validated['no_telp'] ?? null,
            'address' => $validated['address'] ?? null,
            'nama_instansi' => $validated['nama_instansi'] ?? null,
            'master_role_id' => 2, // Default role: user
        ]);

        Auth::guard('web')->login($user);

        if ($user->master_role_id == 6) {
            return redirect()->route('dashboard')
                ->with('success', 'Registrasi berhasil! Selamat datang, ' . $user->name);
        } else {
            return redirect()->route('welcome')
                ->with('success', 'Registrasi berhasil! Selamat datang, ' . $user->name);
        }
    }

    /**
     * Show forgot password form
     * 
     * @return \Illuminate\View\View
     */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send password reset link
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    /**
     * Show reset password form
     * 
     * @param string $token
     * @return \Illuminate\View\View
     */
    public function showResetForm($token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    /**
     * Handle password reset
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('password.reset.success')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }

    /**
     * Show reset password success page
     * 
     * @return \Illuminate\View\View
     */
    public function showResetSuccess()
    {
        return view('auth.reset-success');
    }

    /**
     * Handle logout
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Logout berhasil. Sampai jumpa!');
    }
}
