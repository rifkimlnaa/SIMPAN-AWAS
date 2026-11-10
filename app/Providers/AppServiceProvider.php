<?php

namespace App\Providers;

use App\Models\Kategori;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $timezone = config('arsip.timezone', 'Asia/Jakarta');
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);

        // Nama hari & bulan berbahasa Indonesia (mis. "Jumat, 30 Oktober 2026"), terlepas dari APP_LOCALE.
        Carbon::setLocale('id');

        // Jika APP_URL memakai https, semua URL (aset, redirect) dibuat https. Penting di hosting yang
        // menghentikan SSL di proxy, supaya halaman tidak memuat aset lewat http (mixed content).
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        View::composer('partials.sidebar', function ($view) {
            $view->with('sidebarKategori', Kategori::orderBy('nama')->get(['id', 'nama', 'slug']));
        });
    }
}
