<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\MasterRetribusiController;
use App\Http\Controllers\Web\MasterRincianRetribusiController;
use App\Http\Controllers\Web\MasterSubRincianRetribusiController;
use App\Http\Controllers\Web\MasterChiefWebController;
use App\Http\Controllers\Web\MasterOpdWebController;
use App\Http\Controllers\Web\MasterLokasiObjekController;
use App\Http\Controllers\Web\MasterTypeObjectController;
use App\Http\Controllers\Web\MasterObjekController;
use App\Http\Controllers\Web\MasterPaymentWebController;
use App\Http\Controllers\Web\MasterPaymentStatusController;
use App\Http\Controllers\Web\MasterDocumentWebController;
use App\Http\Controllers\Web\MasterSatuanController;
use App\Http\Controllers\Web\MasterTpjController;
use App\Http\Controllers\Web\MasterRoleWebController;
use App\Http\Controllers\Web\RevenueWebController;
use App\Http\Controllers\Web\RetributionTargetWebController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Web\ExternalPaymentWebController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Routes untuk web interface (session-based authentication)
| Terpisah dari API routes yang menggunakan JWT
|
*/

// =========================
// PUBLIC ROUTES
// =========================
Route::get('/', function () {
    return redirect()->route('login');
});

// External Payment UI (no login, read-only UI that calls client.secret-protected APIs)
Route::get('/external-payments/{opd_id?}/{lokasi_id?}', [ExternalPaymentWebController::class, 'index'])->name('external.payments');

// External endpoints (no JWT), protected by client.secret
Route::prefix('external')->group(function () {
    Route::get('/objects', [\App\Http\Controllers\Web\ExternalPaymentWebController::class, 'getObjects']);
    Route::get('/objects/{id}', [\App\Http\Controllers\Web\ExternalPaymentWebController::class, 'showObject']);
    Route::post('/transactions', [\App\Http\Controllers\Web\ExternalPaymentWebController::class, 'createTransaction']);
    Route::get('/pay/{reference_no}', [\App\Http\Controllers\Web\ExternalPaymentWebController::class, 'paymentPage']);
    Route::post('/payment/transaction', [\App\Http\Controllers\Web\ExternalPaymentWebController::class, 'setPaymentTransaction']);
});

// Email Verification
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
    ->middleware(['signed'])
    ->name('verification.verify');

// =========================
// AUTH ROUTES (GUEST ONLY)
// =========================
Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthWebController::class, 'login'])->name('login.post');

    // Register
    Route::get('/register', [AuthWebController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthWebController::class, 'register'])->name('register.post');

    // Forgot Password
    Route::get('/forgot-password', [AuthWebController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthWebController::class, 'sendResetLink'])->name('password.email');

    // Reset Password
    // Password Reset Routes
    Route::get('/reset-password', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::get('/reset-password-success', [AuthController::class, 'showResetSucess'])->name('password.reset.success');
});

// =========================
// AUTHENTICATED ROUTES
// =========================
Route::middleware(['auth:web'])->group(function () {

    // Logout
    Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout');

    // Welcome page for non-admin users
    Route::get('/welcome', function () {
        return view('welcome');
    })->name('welcome');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Dashboard List Pages
    Route::get('/dashboard/opd', [DashboardController::class, 'opdList'])->name('dashboard.opd.list');
    Route::get('/dashboard/retribusi', [DashboardController::class, 'retribusiList'])->name('dashboard.retribusi.list');
    Route::get('/dashboard/lokasi', [DashboardController::class, 'lokasiList'])->name('dashboard.lokasi.list');
    Route::get('/dashboard/objek', [DashboardController::class, 'objekList'])->name('dashboard.objek.list');

    // Dashboard Detail Pages (Heavy Queries)
    Route::get('/dashboard/performance', [DashboardController::class, 'performanceDetail'])->name('dashboard.performance');
    Route::get('/dashboard/realisasi', [DashboardController::class, 'realisasiDetail'])->name('dashboard.realisasi');

    // Realisasi Drill-down Pages (terminal level: Lokasi)
    Route::get('/dashboard/realisasi/opd/{opdId}/lokasi', [DashboardController::class, 'realisasiLokasiByOpd'])->name('dashboard.realisasi.lokasi-by-opd');

    // Retribution breakdown by lokasi (shows jenis, rincian, sub rincian with realization vs target)
    Route::get('/dashboard/realisasi/opd/{opdId}/lokasi/{lokasiId}/retribusi', [DashboardController::class, 'realisasiRetribusiByLokasi'])->name('dashboard.realisasi.retribusi-by-lokasi');

    // AJAX Endpoints for Hierarchical Filters
    Route::get('/dashboard/ajax/lokasi-by-opd', [DashboardController::class, 'getLokasiByOpd'])->name('dashboard.ajax.lokasi');
    Route::get('/dashboard/ajax/rincian-by-retribusi', [DashboardController::class, 'getRincianByRetribusi'])->name('dashboard.ajax.rincian');

    // =========================
    // MASTER DATA ROUTES
    // =========================
    Route::prefix('master')->name('master.')->group(function () {

        // Master Retribusi
        Route::resource('retribusi', MasterRetribusiController::class);

        // Master Rincian Retribusi
        Route::resource('rincian-retribusi', MasterRincianRetribusiController::class);

        // Master Sub Rincian Retribusi
        Route::resource('sub-rincian-retribusi', MasterSubRincianRetribusiController::class);

        // Master Chief
        Route::resource('chief', MasterChiefWebController::class);

        // Master OPD
        Route::resource('opd', MasterOpdWebController::class);

        // Master Lokasi Objek
        Route::resource('lokasi-objek', MasterLokasiObjekController::class);

        // Master Type Object
        Route::resource('type-object', MasterTypeObjectController::class);

        // Master Objek
        Route::get('objek/ajax/lokasi', [MasterObjekController::class, 'getLokasiByOpd'])->name('objek.ajax.lokasi');
        Route::get('objek/ajax/rincian', [MasterObjekController::class, 'getRincianByRetribusi'])->name('objek.ajax.rincian');
        Route::resource('objek', MasterObjekController::class);

        // Master Payment
        Route::resource('payment', MasterPaymentWebController::class);

        // Master Payment Status
        Route::resource('payment-status', MasterPaymentStatusController::class);

        // Master Document
        Route::resource('document', MasterDocumentWebController::class);

        // Master Satuan
        Route::resource('satuan', MasterSatuanController::class);

        // Master TPJ
        Route::resource('tpj', MasterTpjController::class);

        // Master Role
        Route::resource('role', MasterRoleWebController::class);
    });

    // Revenue
    Route::resource('revenue', RevenueWebController::class);

    // Retribution Targets
    Route::get('retribution-target/ajax/lokasi', [RetributionTargetWebController::class, 'getLokasiByOpd'])->name('retribution-target.ajax.lokasi');
    Route::get('retribution-target/ajax/rincian', [RetributionTargetWebController::class, 'getRincianByRetribusi'])->name('retribution-target.ajax.rincian');
    Route::get('retribution-target/ajax/sub-rincian', [RetributionTargetWebController::class, 'getSubRincianByRincian'])->name('retribution-target.ajax.sub-rincian');
    Route::resource('retribution-target', RetributionTargetWebController::class);
});
