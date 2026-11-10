<?php

use App\Http\Controllers\ArsipController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\RiwayatController;
use Illuminate\Support\Facades\Route;

// ID di URL harus angka.
Route::pattern('arsip', '[0-9]+');
Route::pattern('kategori', '[0-9]+');
Route::pattern('petugas', '[0-9]+');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profil (admin & petugas)
    Route::get('/profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::post('/profil/foto', [ProfilController::class, 'updateFoto'])->name('profil.foto');
    Route::delete('/profil/foto', [ProfilController::class, 'destroyFoto'])->name('profil.foto.destroy');
    Route::put('/profil/password', [ProfilController::class, 'updatePassword'])->name('profil.password');

    // Pengaturan: tampilan/mode gelap (semua pengguna). Foto latar login khusus admin, di grup bawah.
    Route::get('/pengaturan', [PengaturanController::class, 'edit'])->name('pengaturan.edit');

    // Lihat data kategori & petugas (admin & petugas)
    Route::get('/kategori', [KategoriController::class, 'index'])->name('kategori.index');
    Route::get('/petugas', [PetugasController::class, 'index'])->name('petugas.index');

    // Kelola kategori & petugas (hanya admin)
    Route::middleware('role:admin')->group(function () {
        Route::post('/pengaturan/latar-login', [PengaturanController::class, 'updateLatarLogin'])->name('pengaturan.latar');
        Route::delete('/pengaturan/latar-login', [PengaturanController::class, 'destroyLatarLogin'])->name('pengaturan.latar.destroy');

        Route::get('/kategori/tambah', [KategoriController::class, 'create'])->name('kategori.create');
        Route::post('/kategori', [KategoriController::class, 'store'])->name('kategori.store');
        Route::get('/kategori/{kategori}/edit', [KategoriController::class, 'edit'])->name('kategori.edit');
        Route::put('/kategori/{kategori}', [KategoriController::class, 'update'])->name('kategori.update');
        Route::delete('/kategori/{kategori}', [KategoriController::class, 'destroy'])->name('kategori.destroy');

        Route::get('/petugas/tambah', [PetugasController::class, 'create'])->name('petugas.create');
        Route::post('/petugas', [PetugasController::class, 'store'])->name('petugas.store');
        Route::get('/petugas/{petugas}/edit', [PetugasController::class, 'edit'])->name('petugas.edit');
        Route::put('/petugas/{petugas}', [PetugasController::class, 'update'])->name('petugas.update');
        Route::delete('/petugas/{petugas}', [PetugasController::class, 'destroy'])->name('petugas.destroy');
    });

    // Arsip: hak akses per aksi diatur di ArsipPolicy.
    Route::get('/arsip', [ArsipController::class, 'index'])->name('arsip.index');
    Route::get('/arsip/tambah', [ArsipController::class, 'create'])->name('arsip.create');
    Route::post('/arsip', [ArsipController::class, 'store'])->name('arsip.store');
    Route::get('/arsip/{arsip}', [ArsipController::class, 'show'])->name('arsip.show');
    Route::get('/arsip/{arsip}/edit', [ArsipController::class, 'edit'])->name('arsip.edit');
    Route::put('/arsip/{arsip}', [ArsipController::class, 'update'])->name('arsip.update');
    Route::delete('/arsip/{arsip}', [ArsipController::class, 'destroy'])->name('arsip.destroy');
    Route::get('/arsip/{arsip}/berkas', [ArsipController::class, 'berkas'])->name('arsip.berkas');
    Route::get('/arsip/{arsip}/unduh', [ArsipController::class, 'unduh'])->name('arsip.unduh');
    Route::get('/arsip/{arsip}/formulir', [ArsipController::class, 'formulir'])->name('arsip.formulir');

    Route::get('/riwayat-download', [RiwayatController::class, 'index'])->name('riwayat.index');
});
