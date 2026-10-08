<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuktiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Guru\AbsenMandiriController;
use App\Http\Controllers\Guru\AbsenSiswaController;
use App\Http\Controllers\Master\KelasController;
use App\Http\Controllers\Master\RelasiController;
use App\Http\Controllers\Master\SiswaController;
use App\Http\Controllers\Master\TahunAjaranController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\Validasi\ValidasiGuruController;
use App\Http\Controllers\Validasi\ValidasiSiswaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Publik
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.proses');
});

/*
|--------------------------------------------------------------------------
| Terautentikasi
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'redirect'])->name('dashboard');

    // Semua role
    Route::get('/profil', [ProfilController::class, 'index'])->name('profil');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::put('/profil/password', [ProfilController::class, 'password'])->name('profil.password');
    Route::get('/bukti/guru/{absensiGuru}', [BuktiController::class, 'guru'])->name('bukti.guru'); // dijaga Policy

    // Admin saja
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
        Route::resource('users', UserController::class)->except('show');
    });

    // Kesiswaan saja (dashboard)
    Route::middleware('role:kesiswaan')->prefix('kesiswaan')->name('kesiswaan.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'kesiswaan'])->name('dashboard');
    });

    // Dipakai bersama Admin + Kesiswaan
    Route::middleware('role:admin,kesiswaan')->group(function () {
        Route::prefix('master')->name('master.')->group(function () {
            Route::get('siswa/template', [SiswaController::class, 'template'])->name('siswa.template');
            Route::post('siswa/import', [SiswaController::class, 'import'])->name('siswa.import');
            Route::resource('siswa', SiswaController::class)->except('show')->parameters(['siswa' => 'siswa']);

            Route::resource('kelas', KelasController::class)->parameters(['kelas' => 'kelas']);

            Route::get('tahun-ajaran', [TahunAjaranController::class, 'index'])->name('tahun-ajaran.index');
            Route::post('tahun-ajaran', [TahunAjaranController::class, 'store'])->name('tahun-ajaran.store');
            Route::put('tahun-ajaran/{tahunAjaran}', [TahunAjaranController::class, 'update'])->name('tahun-ajaran.update');
            Route::patch('tahun-ajaran/{tahunAjaran}/aktifkan', [TahunAjaranController::class, 'aktifkan'])->name('tahun-ajaran.aktifkan');

            Route::resource('relasi', RelasiController::class)->except('show')->parameters(['relasi' => 'relasi']);
        });

        Route::prefix('validasi')->name('validasi.')->group(function () {
            Route::get('guru', [ValidasiGuruController::class, 'index'])->name('guru');
            Route::post('guru/setujui-semua', [ValidasiGuruController::class, 'setujuiSemua'])->name('guru.setujui-semua');
            Route::post('guru/koreksi', [ValidasiGuruController::class, 'koreksi'])->name('guru.koreksi');
            Route::post('guru/{absensiGuru}/setujui', [ValidasiGuruController::class, 'setujui'])->name('guru.setujui');
            Route::post('guru/{absensiGuru}/tolak', [ValidasiGuruController::class, 'tolak'])->name('guru.tolak');

            Route::get('siswa', [ValidasiSiswaController::class, 'index'])->name('siswa');
            Route::get('siswa/input-manual', [ValidasiSiswaController::class, 'inputManualForm'])->name('siswa.input-manual');
            Route::post('siswa/input-manual', [ValidasiSiswaController::class, 'inputManual'])->name('siswa.input-manual.simpan');
            Route::post('siswa/setujui-semua', [ValidasiSiswaController::class, 'setujuiSemua'])->name('siswa.setujui-semua');
            Route::get('siswa/{absensiKelas}', [ValidasiSiswaController::class, 'show'])->name('siswa.show');
            Route::put('siswa/{absensiKelas}/koreksi', [ValidasiSiswaController::class, 'koreksi'])->name('siswa.koreksi');
            Route::post('siswa/{absensiKelas}/setujui', [ValidasiSiswaController::class, 'setujui'])->name('siswa.setujui');
            Route::post('siswa/{absensiKelas}/tolak', [ValidasiSiswaController::class, 'tolak'])->name('siswa.tolak');
        });

        Route::get('rekap', [RekapController::class, 'index'])->name('rekap');
        Route::get('rekap/pdf', [RekapController::class, 'pdf'])->name('rekap.pdf');
        Route::get('rekap/csv', [RekapController::class, 'csv'])->name('rekap.csv');
    });

    // Guru saja
    Route::middleware('role:guru')->prefix('guru')->name('guru.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'guru'])->name('dashboard');

        Route::get('absen-mandiri', [AbsenMandiriController::class, 'index'])->name('absen-mandiri');
        Route::post('absen-mandiri', [AbsenMandiriController::class, 'store'])->name('absen-mandiri.store');
        Route::get('absen-mandiri/{absensiGuru}/perbaiki', [AbsenMandiriController::class, 'edit'])->name('absen-mandiri.edit');
        Route::put('absen-mandiri/{absensiGuru}', [AbsenMandiriController::class, 'update'])->name('absen-mandiri.update');

        Route::get('absen-siswa', [AbsenSiswaController::class, 'index'])->name('absen-siswa');
        Route::post('absen-siswa', [AbsenSiswaController::class, 'store'])->name('absen-siswa.store');

        Route::get('rekap', [RekapController::class, 'index'])->name('rekap');
        Route::get('rekap/pdf', [RekapController::class, 'pdf'])->name('rekap.pdf');
        Route::get('rekap/csv', [RekapController::class, 'csv'])->name('rekap.csv');
    });
});
