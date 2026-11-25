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
        $admin = $user->isAdmin();

        // Admin melihat angka keseluruhan; petugas hanya angka miliknya sendiri.
        $arsipQuery = Arsip::query()->visibleTo($user);
        $logQuery = DownloadLog::query()
            ->when(! $admin, fn ($q) => $q->where('user_id', $user->id));

        $totalArsip = (clone $arsipQuery)->count();
        $totalDownload = (clone $logQuery)->count();
        $totalPetugas = User::where('role', User::ROLE_PETUGAS)->count();
        $totalAdmin = User::where('role', User::ROLE_ADMIN)->count();

        $perKategori = Kategori::query()
            ->withCount(['arsips' => fn ($q) => $q->when(! $admin, fn ($q) => $q->where('user_id', $user->id))])
            ->urut()
            ->get();

        // Per jenis arsip (kolom jenis_arsip); arsip sederhana digabung per kategorinya.
        $perJenis = (clone $arsipQuery)
            ->selectRaw('jenis_arsip, COUNT(*) as total')
            ->groupBy('jenis_arsip')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                $kunci = (string) $row->jenis_arsip;
                $label = $kunci === Arsip::JENIS_SEDERHANA || $kunci === ''
                    ? 'Arsip umum'
                    : (string) (Arsip::definisi($kunci)['label'] ?? $kunci);

                return ['label' => $label, 'count' => (int) $row->total];
            })
            ->values()
            ->all();

        // Deret harian 30 hari terakhir (hari tanpa kejadian = 0).
        $start = now()->subDays(self::CHART_DAYS - 1)->startOfDay();

        $downloadPerHari = (clone $logQuery)
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as tanggal, COUNT(*) as total')
            ->groupBy('tanggal')
            ->pluck('total', 'tanggal');

        $unggahPerHari = (clone $arsipQuery)
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as tanggal, COUNT(*) as total')
            ->groupBy('tanggal')
            ->pluck('total', 'tanggal');

        $labels = [];
        $tanggal = [];
        $downloads = [];
        $uploads = [];
        foreach (CarbonPeriod::create($start, now()->startOfDay()) as $date) {
            $key = $date->format('Y-m-d');
            $labels[] = $date->translatedFormat('d M');
            $tanggal[] = $key;
            $downloads[] = (int) ($downloadPerHari[$key] ?? 0);
            $uploads[] = (int) ($unggahPerHari[$key] ?? 0);
        }

        $terbaru = (clone $logQuery)
            ->with('user:id,name')
            ->latest('created_at')
            ->limit(8)
            ->get();

        $jumlahKategori = $perKategori->count();
        $kategoriUtama = $perKategori->filter(fn ($k) => $k->isUtama())->count();

        return view('dashboard', [
            'admin' => $admin,
            'totalArsip' => $totalArsip,
            'totalDownload' => $totalDownload,
            'totalPetugas' => $totalPetugas,
            'totalAdmin' => $totalAdmin,
            'perKategori' => $perKategori,
            'jumlahKategori' => $jumlahKategori,
            'kategoriUtama' => $kategoriUtama,
            'terbaru' => $terbaru,
            'chartDays' => self::CHART_DAYS,
            'chart' => [
                'labels' => $labels,
                'dates' => $tanggal,
                'downloads' => $downloads,
                'uploads' => $uploads,
                'kategori' => $perKategori->map(fn ($k) => ['label' => $k->nama, 'count' => (int) $k->arsips_count])->values()->all(),
                'jenis' => $perJenis,
            ],
        ]);
    }
}
