<?php

use App\Events\SendGlobalNotification;
use App\Http\Controllers\AdminDinas;
use App\Http\Controllers\ApelController;
use App\Http\Controllers\RadiusController;
use App\Http\Controllers\ConfigTppController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Dinas;
use App\Http\Controllers\Home;
use App\Http\Controllers\IzinPegawai;
use App\Http\Controllers\JadwalApelController;
use App\Http\Controllers\JamAbsenController;
use App\Http\Controllers\JamApelController;
use App\Http\Controllers\JumlahHariKerjaController;
use App\Http\Controllers\KalendarLiburController;
use App\Http\Controllers\Login;
use App\Http\Controllers\Passwd;
use App\Http\Controllers\Pegawai;
use App\Http\Controllers\Pengumuman;
use App\Http\Controllers\PresensiPegawai;
use App\Http\Controllers\ResetImeiController;
use App\Models\KalendarLibur;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::prefix('login')->group(function () {
    Route::get('', [Login::class, 'Index'])->name('user.login')->middleware('throttle:30,1');;
    Route::post('', [Login::class, 'Action'])->name('login.action')->middleware('throttle:30,1');;
    Route::post('logout', [Login::class, 'Logout'])->name('logout')->middleware('throttle:30,1');;
});

Route::middleware('auth')->group(function () {
    Route::get('/', [Home::class, 'Index'])->name('user.home');
    Route::get('home', [Home::class, 'Index'])->name('home');
    Route::get('passwd', [Passwd::class, 'Index'])->name('passwd');
    Route::patch('passwd', [Passwd::class, 'Action'])->name('passwd.action');
    Route::get('send-notif', function () {
        event(new SendGlobalNotification('tess', '22'));
        return "Event has been sent! ";
    });

    Route::get('dinas/select2', [Dinas::class, 'select2'])->name('dinas.select2');
    Route::resource('web-dinas', Dinas::class);

    Route::get('roles/select2', [RoleController::class, 'select2'])->name('roles.select2');
    Route::resource('roles', RoleController::class);
    Route::resource('jumlah-hari-kerja', JumlahHariKerjaController::class);
    Route::resource('config-tpp', ConfigTppController::class);
    Route::resource('reset-imei', ResetImeiController::class);
    Route::resource('web-apel', ApelController::class);
    Route::resource('radius', RadiusController::class);
    Route::resource('kalendar-libur', KalendarLiburController::class);
    Route::resource('jam-absen', JamAbsenController::class);
    Route::resource('jam-apel', JamApelController::class);
    Route::get('jadwal-apel/retrive', [JadwalApelController::class, 'retrive'])->name('jadwal-apel.retrive');
    Route::resource('jadwal-apel', JadwalApelController::class);
    Route::resource('admin', AdminDinas::class);
    Route::get('pegawai/select2', [Pegawai::class, 'select2'])->name('pegawai.select2');
    Route::get('export', [Pegawai::class, 'export'])->name('pegawai.export');
    Route::resource('pegawai', Pegawai::class);
    Route::resource('web-pengumuman', Pengumuman::class);
    Route::get('log-viewers', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index']);
    Route::resource('web-izin', IzinPegawai::class);

    Route::prefix('presensi-pegawai')->group(function () {
        Route::get('', [PresensiPegawai::class, 'Index'])->name('presensi-pegawai.index');
        Route::get('export', [PresensiPegawai::class, 'export'])->name('presensi-pegawai.export');
        Route::get('exportPdf', [PresensiPegawai::class, 'exportPdf'])->name('presensi-pegawai.exportPdf');
        Route::get('updateTidakMasuk', [PresensiPegawai::class, 'updateTidakMasuk'])->name('presensi-pegawai.updateTidakMasuk');
        Route::post('datatable', [PresensiPegawai::class, 'Datatable'])->name('presensi-pegawai.datatable');
    });
    
    Route::get('phpmyinfo', function () {
        phpinfo(); 
    })->name('phpmyinfo');


});
