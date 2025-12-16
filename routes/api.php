<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('throttle:60,1')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Api\AuthController::class, 'login'])->name('api.login');
    Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login'])->name('login');
// });


Route::middleware(['auth:sanctum', 'throttle:siaptek'])->get('/user', function (Request $request) {
    $request->user()->versi = "1.0.2";
    return $request->user();
});

Route::middleware(['auth:sanctum', 'throttle:300:1'])->group(function () {
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    
    Route::middleware(['auth:sanctum','throttle:siaptek_post'])->group(function () {
        Route::post('/tescompress', [\App\Http\Controllers\Api\PegawaiController::class, 'test_compres']);
        Route::post('/testcheckin', [\App\Http\Controllers\Api\TestController::class, 'test_clockin']);
        Route::post('/testcheckout', [\App\Http\Controllers\Api\TestController::class, 'test_clockout']);
        Route::post('/checkin', [\App\Http\Controllers\Api\PegawaiController::class, 'clock_in']);
        Route::post('/checkout', [\App\Http\Controllers\Api\PegawaiController::class, 'clock_out']);
        Route::apiResource('/daftar_hadir_apel', \App\Http\Controllers\Api\DaftarHadirApelController::class);
        Route::post('/checkinnew', [\App\Http\Controllers\Api\PegawaiController::class, 'clock_in_new']);
        Route::post('/checkoutnew', [\App\Http\Controllers\Api\PegawaiController::class, 'clock_out_new']);
        Route::post('/upload_foto_absen', [\App\Http\Controllers\Api\PegawaiController::class, 'uploadFoto']);

    });
    
    
    Route::get('/persensi', [\App\Http\Controllers\Api\PegawaiController::class, 'persensi']);
    Route::get('/user1', [\App\Http\Controllers\Api\PegawaiController::class, 'detail']);
    Route::apiResource('/religion', \App\Http\Controllers\Api\ReligionController::class);
    Route::apiResource('/marriage', \App\Http\Controllers\Api\MarriageController::class);
    Route::apiResource('/pangkat', \App\Http\Controllers\Api\PangkatGolController::class);
    Route::apiResource('/jenjang', \App\Http\Controllers\Api\JenjangController::class);
    Route::apiResource('/dinas', \App\Http\Controllers\Api\DinasController::class);
    Route::apiResource('/pengumuman', \App\Http\Controllers\Api\PengumumanController::class);
    Route::post('/izinUpdate', [\App\Http\Controllers\Api\IzinController::class, 'updateIzin']);
    Route::apiResource('/izin', \App\Http\Controllers\Api\IzinController::class);
    
    Route::apiResource('/kalender', \App\Http\Controllers\Api\KalendarController::class);
    Route::apiResource('/jenis_izin', \App\Http\Controllers\Api\JenisIzinController::class);
    Route::apiResource('/teacher', \App\Http\Controllers\Api\PegawaiController::class);
    Route::apiResource('/jam_absen', \App\Http\Controllers\Api\JamAbsenController::class);
    Route::apiResource('/apel', \App\Http\Controllers\Api\ApelController::class);
    Route::post('/updatePP', [\App\Http\Controllers\Api\PegawaiController::class, 'update_pp']);
    Route::post('/updatePW', [\App\Http\Controllers\Api\PegawaiController::class, 'update_pw']);
    Route::post('/blockFakeGps', [\App\Http\Controllers\Api\PegawaiController::class, 'block_fake_gps']);
    
});
