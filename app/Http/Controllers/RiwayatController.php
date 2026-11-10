<?php

namespace App\Http\Controllers;

use App\Models\DownloadLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RiwayatController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Admin melihat riwayat semua pengguna; petugas hanya riwayat unduhannya sendiri.
        $riwayat = DownloadLog::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->with(['user:id,name,username', 'arsip:id,user_id'])
            ->latest('created_at')
            ->latest('id')
            ->limit(2000)
            ->get();

        return view('riwayat.index', compact('riwayat'));
    }
}
