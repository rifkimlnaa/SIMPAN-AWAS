<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const MAX_ACCOUNT_IP_ATTEMPTS = 5;
    private const MAX_IP_ATTEMPTS = 30;
    private const DECAY_SECONDS = 60;

    public function showLogin(): View
    {
        // rescue(): jika tabel pengaturan belum tersedia, login tetap tampil dengan latar bawaan.
        $mediaLatar = rescue(fn () => [
            'latarUrl' => \App\Models\Pengaturan::latarLoginUrl(),
            'latarIsVideo' => \App\Models\Pengaturan::latarLoginIsVideo(),
            'latarMimeType' => \App\Models\Pengaturan::latarLoginMimeType(),
        ], [
            'latarUrl' => null,
            'latarIsVideo' => false,
            'latarMimeType' => null,
        ], false);

        return view('auth.login', $mediaLatar);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        // Batasi percobaan untuk username+IP dan juga IP secara keseluruhan.
        // Hash mencegah username/IP mentah menjadi bagian dari key cache.
        $ip = (string) $request->ip();
        $accountKey = 'login:account-ip:'.hash('sha256', Str::lower(trim($credentials['username'])).'|'.$ip);
        $ipKey = 'login:ip:'.hash('sha256', $ip);

        $accountLocked = RateLimiter::tooManyAttempts($accountKey, self::MAX_ACCOUNT_IP_ATTEMPTS);
        $ipLocked = RateLimiter::tooManyAttempts($ipKey, self::MAX_IP_ATTEMPTS);

        if ($accountLocked || $ipLocked) {
            $seconds = max(
                $accountLocked ? RateLimiter::availableIn($accountKey) : 0,
                $ipLocked ? RateLimiter::availableIn($ipKey) : 0
            );

            return back()
                ->withInput($request->only('username'))
                ->with('error', "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.");
        }

        if (Auth::attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ])) {
            RateLimiter::clear($accountKey);
            RateLimiter::clear($ipKey);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Selamat datang, '.Auth::user()->name.'.');
        }

        RateLimiter::hit($accountKey, self::DECAY_SECONDS);
        RateLimiter::hit($ipKey, self::DECAY_SECONDS);

        return back()
            ->withInput($request->only('username'))
            ->with('error', 'Login gagal. Username atau password salah.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda berhasil logout.');
    }
}
