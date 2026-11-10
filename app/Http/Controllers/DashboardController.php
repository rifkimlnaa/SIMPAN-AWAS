<?php

namespace App\Http\Controllers;

use App\Models\Arsip;
use App\Models\DownloadLog;
use App\Models\Kategori;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const CHART_DAYS = 30;

    public function index(Request $request): View
    {
        $user = $request->user();

        // Admin melihat angka keseluruhan; petugas hanya angka miliknya sendiri.
        $arsipQuery = Arsip::query()->visibleTo($user);
        $logQuery = DownloadLog::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('user_id', $user->id));

        $totalArsip = (clone $arsipQuery)->count();
        $totalDownload = (clone $logQuery)->count();
        $totalPetugas = User::where('role', User::ROLE_PETUGAS)->count();

        $perKategori = Kategori::query()
            ->withCount(['arsips' => fn ($q) => $q->when(! $user->isAdmin(), fn ($q) => $q->where('user_id', $user->id))])
            ->orderBy('nama')
            ->get();

        // Jumlah download per tanggal, 30 hari terakhir (hari tanpa download = 0).
        $start = now()->subDays(self::CHART_DAYS - 1)->startOfDay();

        $perHari = (clone $logQuery)
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as tanggal, COUNT(*) as total')
            ->groupBy('tanggal')
            ->pluck('total', 'tanggal');

        $labels = [];
        $values = [];
        foreach (CarbonPeriod::create($start, now()->startOfDay()) as $date) {
            $labels[] = $date->translatedFormat('d M');
            $values[] = (int) ($perHari[$date->format('Y-m-d')] ?? 0);
        }

        $terbaru = (clone $logQuery)
            ->with('user:id,name')
            ->latest('created_at')
            ->limit(5)
            ->get();

        return view('dashboard', [
            'totalArsip' => $totalArsip,
            'totalDownload' => $totalDownload,
            'totalPetugas' => $totalPetugas,
            'perKategori' => $perKategori,
            'labels' => $labels,
            'values' => $values,
            'terbaru' => $terbaru,
            'chartDays' => self::CHART_DAYS,
        ]);
    }
}
