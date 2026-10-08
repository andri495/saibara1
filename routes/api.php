<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CartObjectController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Api\DashboardWebController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\LokasiController;
use App\Http\Controllers\MasterChiefController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\ObjectController;
use App\Http\Controllers\OpdController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RetributionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// =========================
// AUTH (PUBLIC)
// =========================
Route::group([
    'middleware' => ['api', 'client.secret', 'app.version'],
    'prefix' => 'auth',
], function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::group([
    'middleware' => ['api', 'api.token', 'client.secret', 'app.version'],
    'prefix' => 'auth',
], function () {
    Route::post('/external/login', [AuthController::class, 'loginExternal']);
    Route::post('/external/register', [AuthController::class, 'registerExternal']);
});
// =========================
// AUTH (PUBLIC - NO MIDDLEWARE)
// =========================

Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// =========================
// USER (NEEDS LOGIN)
// =========================
Route::group([
    'middleware' => ['api', 'client.secret', 'app.version'],
    'prefix' => 'user',
], function () {
    Route::get('/profile', [ProfileController::class, 'profile']);
    Route::get('/profile/picture/{filename}', [ProfileController::class, 'getProfilePicture']);
});
Route::post('/user/profile/update', [ProfileController::class, 'update']);

// =========================
// OPD (PUBLIC)
// =========================
Route::group([
    'middleware' => ['api', 'client.secret', 'app.version'],
    'prefix' => 'opd',
], function () {
    Route::get('/data', [OpdController::class, 'getOpd']);
    Route::get('/get', [OpdController::class, 'getListOpdAndLocation']);
});

// =========================
// RETRIBUTION (PUBLIC)
// =========================
Route::group([
    'middleware' => ['api', 'client.secret', 'app.version'],
    'prefix' => 'retribution',
], function () {
    Route::get('/get', [RetributionController::class, 'getRetribution']);
    Route::get('/by-location', [RetributionController::class, 'getRetributionByLocation']);
    Route::get('/targets', [TransaksiController::class, 'getRetributionTargets']);
});

// =========================
// LOCATION (PUBLIC)
// =========================
Route::group([
    'middleware' => ['api', 'client.secret', 'app.version'],
    'prefix' => 'location',
], function () {
    Route::get('/list', [LokasiController::class, 'getLocation']);
    Route::get('/parking', [LokasiController::class, 'getParkingLocation']);
    Route::get('/penetapan/khusus', [LokasiController::class, 'getPenetapanKhususLocation']);
});

// =========================
// ROLE (PUBLIC)
// =========================
Route::group([
    'middleware' => ['api', 'client.secret', 'app.version'],
    'prefix' => 'role',
], function () {
    Route::get('/data', [RoleController::class, 'getRole']);
});

// =========================
// OBJECT (PUBLIC + SOME PROTECTED)
// =========================
Route::group([
    'middleware' => ['api', 'client.secret', 'app.version'],
    'prefix' => 'object',
], function () {
    Route::get('/all', [ObjectController::class, 'getAllObjects']);
    Route::get('/master-data', [ObjectController::class, 'getMasterDataForForms']);
    Route::get('/latest', [ObjectController::class, 'getLatestObject']);
    Route::get('/highest/ratings', [ObjectController::class, 'getHighestScoreObject']);
    Route::get('/highest/transactions', [ObjectController::class, 'getMostTransactionObject']);
    Route::get('/location', [ObjectController::class, 'getObjectByLocation']);
    Route::get('/penetapan/khusus', [ObjectController::class, 'getObjectPenetapanKhusus']);
    Route::get('/search', [ObjectController::class, 'searchObject']);
    Route::get('/search/penetapan/khusus', [ObjectController::class, 'searchObjectPenetapanKhusus']);
    Route::get('/image/{filename}', [ObjectController::class, 'getObjectImage']);
    Route::get('/{id}', [ObjectController::class, 'getObjectById']);
});

// PROTECTED CRUD
Route::group([
    'middleware' => ['api', 'client.secret', 'app.version'],
    'prefix' => 'object',
], function () {
    Route::post('/create', [MasterDataController::class, 'createObjek']);
    Route::put('/update/{id}', [ObjectController::class, 'updateObject']);
    Route::post('/update/{id}', [ObjectController::class, 'updateObject']);
    Route::delete('/delete/{id}', [ObjectController::class, 'deleteObject']);
    Route::delete('/delete-images/{id}', [ObjectController::class, 'deleteObjectImages']);
});

// =========================
// CART (PROTECTED)
// =========================
Route::group([
    'middleware' => ['api', 'client.secret', 'app.version'],
    'prefix' => 'cart',
], function () {
    Route::get('/get', [CartController::class, 'getCart']);
    Route::post('/create', [CartController::class, 'createCart']);
    Route::get('/object/delete', [CartObjectController::class, 'deleteObject']);
    Route::post('/update/quantity', [CartObjectController::class, 'addQuantity']);
});

// =========================
// CHIEF (PUBLIC)
// =========================
// Route::group([
//     'middleware' => ['api', 'client.secret', 'app.version'],
//     'prefix' => 'chief',
// ], function () {
// });
Route::get('chief/get', [MasterChiefController::class, 'getChief']);
Route::get('chief/information/{id}', [MasterChiefController::class, 'showChief']);

// =========================
// DOCUMENT (PROTECTED)
// =========================
Route::group([
    'middleware' => ['api', 'client.secret', 'app.version'],
    'prefix' => 'document',
], function () {
    Route::get('/generate/spdord/{no_reference}', [DocumentController::class, 'generateSPDORD']);
    Route::get('/generate/skrd/{no_reference}', [DocumentController::class, 'generateSKRD']);
    Route::get('/generate/skrd/installment/{no_reference}/{id}', [DocumentController::class, 'generateSKRDInstallment']);
    Route::get('/generate/strd/{no_reference}/{id}', [DocumentController::class, 'generateSTRD']);
    Route::get('/getDocument', [DocumentController::class, 'getDocument']);
    Route::get('/getAllDocument', [DocumentController::class, 'getAllDocument']);
    Route::get('/open/{filename}', [DocumentController::class, 'openDocument']);
    Route::get('/search', [DocumentController::class, 'searchDocument']);
});

// =========================
// TRANSAKSI (PROTECTED) — FIXES 401
// =========================
Route::group([
    'middleware' => ['api', 'client.secret', 'app.version'],
    'prefix' => 'transaksi',
], function () {
    Route::get('/without-object', [TransaksiController::class, 'getTransaksiWithoutObject']);
    Route::post('/createTransaction', [DocumentController::class, 'store']);
    Route::post('/create', [TransaksiController::class, 'createTransaksiObjek']);
    Route::post('/change/status', [TransaksiController::class, 'changeStatusTransaksi']);
    Route::get('/getTransaksi', [TransaksiController::class, 'getTransaksi']);
    Route::get('/installment', [TransaksiController::class, 'getInstallment']);
    Route::get('/installment/opd', [TransaksiController::class, 'getOpdInstallment']);
    Route::get('/opd/{masterOpdId}', [TransaksiController::class, 'getTransaksiOpd']);
    Route::get('/grouped-by-retribusi', [TransaksiController::class, 'getTransaksiGroupedByRetribusi']);
    Route::get('/by-retribusi', [TransaksiController::class, 'getTransaksiByRetribusiId']);
    Route::get('/file/{filename}', [TransaksiController::class, 'downloadPrivateFile']);
    Route::get('/generate/qr', [TransaksiController::class, 'getQRImage']);
    Route::get('/generate/va', [TransaksiController::class, 'getVA']);
    Route::post('/create/parking', [TransaksiController::class, 'createParkingTransaction']);
    Route::get('/generate/report', [TransaksiController::class, 'generateFullReport']);
    Route::get('/generate/report/excel', [TransaksiController::class, 'generateFullReportExcel']);
    Route::get('/generate/skrd-report', [TransaksiController::class, 'generateSKRDReport']);
    Route::get('/generate/skrd-report/excel', [TransaksiController::class, 'generateSKRDReportExcel']);
    Route::post('/generate/revenue-recap', [TransaksiController::class, 'generateRevenueRecap']);
    Route::post('/generate/revenue-recap/excel', [TransaksiController::class, 'generateRevenueRecapExcel']);
    Route::get('/payment', [TransaksiController::class, 'setPaymentInstallment']);
    Route::get('/check/status', [TransaksiController::class, 'checkPaymentStatus']);
    Route::get('/check/status/va', [TransaksiController::class, 'checkPaymentVA']);
    Route::get('/check/status/kodebayar', [TransaksiController::class, 'checkPaymentKodeBayar']);
    Route::get('/check/installment', [TransaksiController::class, 'checkInstallmentPayment']);
    Route::get('/check/installment/va', [TransaksiController::class, 'checkInstallmentPaymentVA']);
    Route::get('/check/installment/kodebayar', [TransaksiController::class, 'checkInstallmentPaymentKodeBayar']);
    Route::get('/dashboard', [TransaksiController::class, 'getDashboard']);
    Route::get('/dashboard/opd/{opdId}', [TransaksiController::class, 'getDashboardByOpd']);
    Route::get('/summary/list', [TransaksiController::class, 'getTransaksiByOpdAndStatus']);
    Route::get('/summary/installment/list', [TransaksiController::class, 'getInstallmentTransaksiByOpdAndStatus']);
    Route::get('/summary/opd/list', [TransaksiController::class, 'getTransaksiAllPerOPD']);
    Route::post('/update/used-time', [TransaksiController::class, 'updateUsedTime']);
    Route::get('/search/installment', [TransaksiController::class, 'searchOpdInstallment']);

    // Retribution Targets
});

// =========================
// DASHBOARD (REVENUE-BASED) - NEW OPTIMIZED VERSION
// =========================
Route::group([
    'middleware' => ['api', 'client.secret', 'app.version'],
    'prefix' => 'dashboard',
], function () {
    // Main Dashboard
    Route::get('/revenue', [DashboardController::class, 'getDashboard']);
    Route::get('/revenue/opd/{opdId}', [DashboardController::class, 'getDashboardByOpd']);

    // Targets
    Route::get('/targets', [DashboardController::class, 'getRetributionTargets']);
    Route::get('/targets/opd/{opdId}', [DashboardController::class, 'getRetributionTargetsByOpd']);

    // Drill-down: OPD → Lokasi (terminal) → Retribusi breakdown per lokasi
    Route::get('/opd/{opdId}/lokasi', [DashboardController::class, 'getRevenueByLokasiForOpd']);
    Route::get('/lokasi/{lokasiId}/retribusi', [DashboardController::class, 'getRetribusiBreakdownForLokasi']);
});

// =========================
// DASHBOARD WEB API (FOR EXTERNAL WEB INTEGRATION)
// Mirror of Web DashboardController - PUBLIC with EXTERNAL_API_TOKEN
// =========================
Route::group([
    'middleware' => ['api', 'api.token'],
    'prefix' => 'dashboard-web',
], function () {
    // Main Dashboard Data (DEPRECATED - use separate endpoints below)
    Route::get('/', [DashboardWebController::class, 'getDashboardData']);

    // === LIGHTWEIGHT DASHBOARD COMPONENTS (NEW) ===
    // Separate endpoints for better performance
    Route::get('/summary', [DashboardWebController::class, 'getSummary']);
    Route::get('/retribusi-breakdown', [DashboardWebController::class, 'getRetribusiBreakdown']);
    Route::get('/opd-performance', [DashboardWebController::class, 'getOpdPerformance']);
    Route::get('/realisasi-vs-target', [DashboardWebController::class, 'getRealisasiVsTarget']);

    // Master Data Lists
    Route::get('/opd', [DashboardWebController::class, 'getOpdList']);
    Route::get('/retribusi', [DashboardWebController::class, 'getRetribusiList']);
    Route::get('/lokasi', [DashboardWebController::class, 'getLokasiList']);
    Route::get('/objek', [DashboardWebController::class, 'getObjekList']);

    // Performance & Realisasi (Detail Pages)
    Route::get('/performance-detail', [DashboardWebController::class, 'getPerformanceDetail']);
    Route::get('/realisasi-detail', [DashboardWebController::class, 'getRealisasiDetail']);

    // OPD Drill-down
    Route::get('/opd/{opdId}/lokasi', [DashboardWebController::class, 'getRealisasiLokasiByOpd']);
    Route::get('/opd/{opdId}/lokasi/{lokasiId}/retribusi', [DashboardWebController::class, 'getRealisasiRetribusiByLokasi']);

    // Helper Endpoints (for dropdowns)
    Route::get('/helper/lokasi-by-opd', [DashboardWebController::class, 'getLokasiByOpd']);
    Route::get('/helper/rincian-by-retribusi', [DashboardWebController::class, 'getRincianByRetribusi']);
});

// =========================
// EXTERNAL PAYMENT CALLBACK
// =========================
Route::group([
    'middleware' => ['api', 'client.secret'],
], function () {
    Route::post('/external/payment/callback', [TransaksiController::class, 'externalPaymentCallback']);
});


// =========================
// JWT AUTH ROUTES
// =========================
Route::group([
    'middleware' => ['api', 'client.secret', 'app.version'],
], function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
});

// VERIFY EMAIL
Route::post('/email-verification/resend', [VerificationController::class, 'resend']);
// Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
//     ->name('verification.verify')
//     ->middleware(['signed']);

// =========================
// MASTER (PROTECTED)
// =========================
Route::group([
    'middleware' => ['api', 'client.secret', 'app.version'],
    'prefix' => 'master',
], function () {

    Route::get('/retribusi', [MasterDataController::class, 'getRetribusi']);
    Route::post('/retribusi/create', [MasterDataController::class, 'createRetribusi']);
    Route::put('/retribusi/update/{id}', [MasterDataController::class, 'updateRetribusi']);

    Route::get('/rincian-retribusi', [MasterDataController::class, 'getRincianRetribusi']);
    Route::post('/rincian-retribusi/create', [MasterDataController::class, 'createRincianRetribusi']);
    Route::put('/rincian-retribusi/update/{id}', [MasterDataController::class, 'updateRincianRetribusi']);

    Route::get('/sub-rincian-retribusi', [MasterDataController::class, 'getSubRincianRetribusi']);
    Route::post('/sub-rincian-retribusi/create', [MasterDataController::class, 'createSubRincianRetribusi']);
    Route::put('/sub-rincian-retribusi/update/{id}', [MasterDataController::class, 'updateSubRincianRetribusi']);

    Route::get('/chief', [MasterDataController::class, 'getChief']);
    Route::post('/chief/create', [MasterDataController::class, 'createChief']);
    Route::put('/chief/update/{id}', [MasterDataController::class, 'updateChief']);

    Route::get('/opd', [MasterDataController::class, 'getOpd']);
    Route::post('/opd/create', [MasterDataController::class, 'createOpd']);
    Route::put('/opd/update/{id}', [MasterDataController::class, 'updateOpd']);

    Route::get('/lokasi-objek', [MasterDataController::class, 'getLokasiObjek']);
    Route::post('/lokasi-objek/create', [MasterDataController::class, 'createLokasiObjek']);
    Route::put('/lokasi-objek/update/{id}', [MasterDataController::class, 'updateLokasiObjek']);

    Route::get('/type-object', [MasterDataController::class, 'getTypeObject']);
    Route::post('/type-object/create', [MasterDataController::class, 'createTypeObject']);
    Route::put('/type-object/update/{id}', [MasterDataController::class, 'updateTypeObject']);

    Route::get('/payment', [MasterDataController::class, 'getPayment']);
    Route::post('/payment/create', [MasterDataController::class, 'createPayment']);
    Route::put('/payment/update/{id}', [MasterDataController::class, 'updatePayment']);

    Route::get('/payment-status', [MasterDataController::class, 'getPaymentStatus']);
    Route::post('/payment-status/create', [MasterDataController::class, 'createPaymentStatus']);
    Route::put('/payment-status/update/{id}', [MasterDataController::class, 'updatePaymentStatus']);

    Route::get('/document', [MasterDataController::class, 'getDocument']);
    Route::post('/document/create', [MasterDataController::class, 'createDocument']);
    Route::put('/document/update/{id}', [MasterDataController::class, 'updateDocument']);

    Route::get('/satuan', [MasterDataController::class, 'getSatuan']);
    Route::post('/satuan/create', [MasterDataController::class, 'createSatuan']);
    Route::put('/satuan/update/{id}', [MasterDataController::class, 'updateSatuan']);

    Route::get('/tpj', [MasterDataController::class, 'getTpj']);
    Route::post('/tpj/create', [MasterDataController::class, 'createTpj']);
    Route::put('/tpj/update/{id}', [MasterDataController::class, 'updateTpj']);

    Route::get('/role', [MasterDataController::class, 'getRole']);
    Route::post('/role/create', [MasterDataController::class, 'createRole']);
    Route::put('/role/update/{id}', [MasterDataController::class, 'updateRole']);
    Route::get('/objek/search', [MasterDataController::class, 'searchObjek']);

    // Route::post('/objek/create', [MasterDataController::class, 'createObjek']);
});
