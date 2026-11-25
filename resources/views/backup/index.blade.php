@extends('layouts.app')

@section('title', 'Backup & Pemulihan Data')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="mb-1 text-sm font-medium text-brand-700">Keamanan & pemeliharaan</p>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Backup &amp; Pemulihan Data</h1>
        <p class="mt-1 max-w-2xl text-sm text-slate-500">Cadangkan database dan berkas arsip SIMPAN-AWAS, lalu pulihkan ketika diperlukan.</p>
    </div>
    <form method="POST" action="{{ route('backup.store') }}">
        @csrf
        <button type="submit" class="btn btn-primary" @disabled(!$keyReady)>
            <i data-lucide="cloud-upload" class="h-4 w-4"></i>
            Buat Backup Sekarang
        </button>
    </form>
</div>

<section class="card mb-6 overflow-hidden" id="hosting-storage-panel" data-endpoint="{{ $storageUsageUrl }}" aria-live="polite">
    <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <span class="rounded-xl bg-brand-50 p-2.5 text-brand-700"><i data-lucide="hard-drive" class="h-5 w-5"></i></span>
            <div>
                <h2 class="font-semibold text-slate-900">Live Storage Hosting</h2>
                <p class="text-xs text-slate-500">Pemakaian dan kuota disk akun cPanel, termasuk file dan database yang dihitung oleh hosting.</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-2 text-xs font-medium text-slate-500" id="hosting-storage-status">
                <span class="h-2 w-2 animate-pulse rounded-full bg-amber-400" id="hosting-storage-status-dot"></span>
                <span id="hosting-storage-status-label">Menghubungkan ke cPanel…</span>
            </span>
            <button type="button" id="hosting-storage-refresh" class="btn btn-secondary">
                <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                Perbarui
            </button>
        </div>
    </div>

    <div class="p-5">
        <div class="mb-2 flex flex-wrap items-end justify-between gap-2">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Ruang terpakai</p>
                <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900" id="hosting-storage-used">Memuat…</p>
            </div>
            <p class="text-sm font-semibold text-slate-600" id="hosting-storage-percent">—</p>
        </div>

        <div class="h-3 overflow-hidden rounded-full bg-slate-100" id="hosting-storage-progress-track">
            <div class="h-full w-0 rounded-full bg-brand-600 transition-[width] duration-500 ease-out" id="hosting-storage-progress-bar"></div>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                <p class="text-xs text-slate-500">Kuota hosting</p>
                <p class="mt-1 text-base font-semibold text-slate-900" id="hosting-storage-quota">Memuat…</p>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                <p class="text-xs text-slate-500">Sisa ruang</p>
                <p class="mt-1 text-base font-semibold text-slate-900" id="hosting-storage-free">Memuat…</p>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                <p class="text-xs text-slate-500">Sumber data</p>
                <p class="mt-1 text-base font-semibold text-slate-900">cPanel UAPI</p>
            </div>
        </div>

        <div class="mt-4 flex flex-col gap-1 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <p id="hosting-storage-message">Data sedang diminta dari cPanel.</p>
            <p>Terakhir diperbarui: <span id="hosting-storage-updated">—</span></p>
        </div>
    </div>
</section>

@if (session('success'))
    <div class="mb-5 flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
        <i data-lucide="check-circle-2" class="mt-0.5 h-4 w-4 shrink-0"></i>{{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="mb-5 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
        <i data-lucide="alert-circle" class="mt-0.5 h-4 w-4 shrink-0"></i>{{ session('error') }}
    </div>
@endif
@if ($errors->any())
    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <p class="font-semibold">Periksa kembali konfirmasi:</p>
        <ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

@if ($diskName !== 's3')
    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
        <div class="flex items-start gap-3">
            <i data-lucide="cloud-off" class="mt-0.5 h-5 w-5 shrink-0"></i>
            <div><p class="font-semibold">Backup masih berada di server yang sama</p><p class="mt-1">Jika server/hosting rusak total, backup lokal bisa ikut hilang. Untuk salinan off-site, konfigurasi disk S3-compatible yang privat lalu atur <code>BACKUP_DISK=s3</code>.</p></div>
        </div>
    </div>
@endif

@if (!$keyReady)
    <div class="mb-6 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
        <div class="flex items-start gap-3">
            <i data-lucide="key-round" class="mt-0.5 h-5 w-5 shrink-0"></i>
            <div>
                <p class="font-semibold">Kunci enkripsi belum dikonfigurasi</p>
                <p class="mt-1">Backup belum dapat dibuat sebelum <code>BACKUP_ENCRYPTION_KEY</code> diisi dengan kunci acak minimal 32 karakter di file <code>.env</code>. Simpan salinan kunci di tempat aman terpisah; backup terenkripsi tidak bisa dipulihkan tanpa kunci yang sama.</p>
            </div>
        </div>
    </div>
@endif

<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="card p-4">
        <div class="flex items-center justify-between"><span class="text-sm text-slate-500">Backup berhasil</span><span class="rounded-lg bg-emerald-50 p-2 text-emerald-700"><i data-lucide="check-check" class="h-5 w-5"></i></span></div>
        <p class="mt-3 text-2xl font-bold text-slate-900">{{ number_format($totalComplete) }}</p>
        <p class="mt-1 text-xs text-slate-500">Seluruh backup yang tersedia</p>
    </div>
    <div class="card p-4">
        <div class="flex items-center justify-between"><span class="text-sm text-slate-500">Backup gagal</span><span class="rounded-lg bg-red-50 p-2 text-red-700"><i data-lucide="alert-triangle" class="h-5 w-5"></i></span></div>
        <p class="mt-3 text-2xl font-bold text-slate-900">{{ number_format($totalFailed) }}</p>
        <p class="mt-1 text-xs text-slate-500">Perlu diperiksa sebelum dicoba ulang</p>
    </div>
    <div class="card p-4">
        <div class="flex items-center justify-between"><span class="text-sm text-slate-500">Backup terakhir</span><span class="rounded-lg bg-blue-50 p-2 text-blue-700"><i data-lucide="clock-3" class="h-5 w-5"></i></span></div>
        <p class="mt-3 text-base font-bold text-slate-900">{{ $lastBackup?->completed_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') ?? 'Belum tersedia' }}</p>
        <p class="mt-1 text-xs text-slate-500">{{ $lastBackup?->sizeForHumans() ?? 'Belum ada backup berhasil' }}</p>
    </div>
    <div class="card p-4">
        <div class="flex items-center justify-between"><span class="text-sm text-slate-500">Lokasi penyimpanan</span><span class="rounded-lg bg-violet-50 p-2 text-violet-700"><i data-lucide="database" class="h-5 w-5"></i></span></div>
        <p class="mt-3 text-base font-bold text-slate-900">{{ $diskName === 's3' ? 'Object storage S3' : 'Storage privat server' }}</p>
        <p class="mt-1 text-xs text-slate-500">Disk: {{ $diskName }}</p>
    </div>
</div>

<div class="mb-6 grid gap-4 xl:grid-cols-3">
    <section class="card p-5 xl:col-span-2">
        <div class="mb-4 flex items-center gap-2">
            <span class="rounded-lg bg-brand-50 p-2 text-brand-700"><i data-lucide="calendar-clock" class="h-5 w-5"></i></span>
            <div><h2 class="font-semibold text-slate-900">Jadwal backup otomatis</h2><p class="text-xs text-slate-500">Dijalankan oleh Laravel Scheduler di server hosting.</p></div>
        </div>
        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-lg border border-slate-200 p-3"><p class="text-sm font-semibold text-slate-800">Harian</p><p class="mt-1 text-xs text-slate-500">Setiap hari · 01.00 WIB</p><p class="mt-2 text-xs text-slate-600">Retensi {{ config('backup.retention.daily_days', 7) }} hari</p></div>
            <div class="rounded-lg border border-slate-200 p-3"><p class="text-sm font-semibold text-slate-800">Mingguan</p><p class="mt-1 text-xs text-slate-500">Minggu · 02.00 WIB</p><p class="mt-2 text-xs text-slate-600">Retensi {{ config('backup.retention.weekly_weeks', 4) }} minggu</p></div>
            <div class="rounded-lg border border-slate-200 p-3"><p class="text-sm font-semibold text-slate-800">Bulanan</p><p class="mt-1 text-xs text-slate-500">Tanggal 1 · 03.00 WIB</p><p class="mt-2 text-xs text-slate-600">Retensi {{ config('backup.retention.monthly_months', 12) }} bulan</p></div>
        </div>
        <div class="mt-4 rounded-lg bg-slate-50 px-3 py-2.5 text-xs leading-relaxed text-slate-600">
            Jadwal hanya berjalan setelah cron hosting menjalankan <code>php artisan schedule:run</code> setiap menit. Lihat petunjuk instalasi untuk konfigurasi cron.
        </div>
    </section>
    <section class="card p-5">
        <div class="mb-3 flex items-center gap-2"><span class="rounded-lg bg-amber-50 p-2 text-amber-700"><i data-lucide="shield-check" class="h-5 w-5"></i></span><h2 class="font-semibold text-slate-900">Isi backup</h2></div>
        <ul class="space-y-2.5 text-sm text-slate-600">
            <li class="flex items-center gap-2"><i data-lucide="check" class="h-4 w-4 text-emerald-600"></i> Database aplikasi</li>
            <li class="flex items-center gap-2"><i data-lucide="check" class="h-4 w-4 text-emerald-600"></i> Dokumen di storage privat</li>
            <li class="flex items-center gap-2"><i data-lucide="check" class="h-4 w-4 text-emerald-600"></i> Foto profil dan media publik</li>
            <li class="flex items-center gap-2"><i data-lucide="check" class="h-4 w-4 text-emerald-600"></i> Enkripsi AES-256-GCM</li>
        </ul>
        <p class="mt-4 text-xs leading-relaxed text-slate-500">Backup tidak berisi file <code>.env</code> atau kredensial hosting. Simpan kunci enkripsi di luar server.</p>
    </section>
</div>

<section class="card overflow-hidden">
    <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div><h2 class="font-semibold text-slate-900">Riwayat backup</h2><p class="text-xs text-slate-500">Backup manual, otomatis, dan backup pengaman sebelum restore.</p></div>
        <span class="text-xs text-slate-500">{{ $backups->total() }} catatan</span>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-5 py-3">Backup</th><th class="px-4 py-3">Jenis</th><th class="px-4 py-3">Ukuran</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Dibuat</th><th class="px-5 py-3 text-right">Tindakan</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($backups as $backup)
                    <tr class="align-top hover:bg-slate-50/70">
                        <td class="max-w-xs px-5 py-4"><p class="break-all font-medium text-slate-800">{{ $backup->filename }}</p><p class="mt-1 text-xs text-slate-500">{{ $backup->creator?->name ?? 'Otomatis / Sistem' }}</p>@if($backup->error_message)<p class="mt-1 text-xs text-red-600">{{ $backup->error_message }}</p>@endif</td>
                        <td class="px-4 py-4"><span class="badge badge-gray">{{ ['manual'=>'Manual','daily'=>'Harian','weekly'=>'Mingguan','monthly'=>'Bulanan','pre-restore'=>'Pengaman restore'][$backup->type] ?? $backup->type }}</span></td>
                        <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $backup->sizeForHumans() }}</td>
                        <td class="px-4 py-4">
                            @if($backup->status === 'complete')<span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700"><i data-lucide="check-circle-2" class="h-3.5 w-3.5"></i> Berhasil</span>
                            @elseif($backup->status === 'running')<span class="badge">Berjalan</span>
                            @else<span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700"><i data-lucide="x-circle" class="h-3.5 w-3.5"></i> Gagal</span>@endif
                            @if($backup->restored_at)<p class="mt-1 text-xs text-slate-500">Dipulihkan {{ $backup->restored_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</p>@endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $backup->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap justify-end gap-2">
                                @if($backup->isComplete())
                                    <a href="{{ route('backup.download', $backup) }}" class="btn-icon" title="Unduh backup"><i data-lucide="download" class="h-4 w-4"></i></a>
                                    <details class="relative">
                                        <summary class="btn-icon cursor-pointer list-none" title="Pulihkan backup"><i data-lucide="refresh-ccw" class="h-4 w-4"></i></summary>
                                        <div class="absolute right-0 z-20 mt-2 w-80 rounded-xl border border-amber-200 bg-white p-4 text-left shadow-xl">
                                            <p class="font-semibold text-slate-900">Pulihkan backup ini?</p>
                                            <p class="mt-1 text-xs leading-relaxed text-amber-800">Database dan berkas aktif akan diganti oleh isi snapshot. Sistem membuat backup pengaman terlebih dahulu.</p>
                                            <form method="POST" action="{{ route('backup.restore', $backup) }}" class="mt-3 space-y-2">
                                                @csrf
                                                <label class="block text-xs font-medium text-slate-700">Password administrator</label>
                                                <input type="password" name="current_password" class="input" autocomplete="current-password" required>
                                                <label class="block text-xs font-medium text-slate-700">Ketik PULIHKAN DATA</label>
                                                <input type="text" name="confirmation" class="input" autocomplete="off" required>
                                                <button type="submit" class="btn w-full bg-amber-600 text-white hover:bg-amber-700" onclick="return confirm('Pemulihan akan mengganti data aktif. Lanjutkan hanya jika backup sudah diverifikasi.');">Pulihkan sekarang</button>
                                            </form>
                                        </div>
                                    </details>
                                @endif
                                <details class="relative">
                                    <summary class="btn-icon btn-icon-danger cursor-pointer list-none" title="Hapus backup"><i data-lucide="trash-2" class="h-4 w-4"></i></summary>
                                    <div class="absolute right-0 z-20 mt-2 w-72 rounded-xl border border-red-200 bg-white p-4 text-left shadow-xl">
                                        <p class="font-semibold text-slate-900">Hapus backup?</p>
                                        <p class="mt-1 text-xs text-slate-500">File backup akan dihapus permanen.</p>
                                        <form method="POST" action="{{ route('backup.destroy', $backup) }}" class="mt-3 space-y-2">
                                            @csrf @method('DELETE')
                                            <label class="block text-xs font-medium text-slate-700">Password administrator</label>
                                            <input type="password" name="current_password" class="input" autocomplete="current-password" required>
                                            <button type="submit" class="btn w-full bg-red-600 text-white hover:bg-red-700" onclick="return confirm('Hapus file backup ini secara permanen?');">Hapus backup</button>
                                        </form>
                                    </div>
                                </details>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center"><i data-lucide="archive" class="mx-auto h-9 w-9 text-slate-300"></i><p class="mt-3 font-medium text-slate-700">Belum ada backup</p><p class="mt-1 text-xs text-slate-500">Buat backup pertama setelah kunci enkripsi dikonfigurasi.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($backups->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $backups->links() }}</div>@endif
</section>

@push('scripts')
<script>
(function () {
    const panel = document.getElementById('hosting-storage-panel');
    if (!panel) return;

    const endpoint = panel.dataset.endpoint;
    const status = document.getElementById('hosting-storage-status');
    const dot = document.getElementById('hosting-storage-status-dot');
    const used = document.getElementById('hosting-storage-used');
    const quota = document.getElementById('hosting-storage-quota');
    const free = document.getElementById('hosting-storage-free');
    const percent = document.getElementById('hosting-storage-percent');
    const message = document.getElementById('hosting-storage-message');
    const updated = document.getElementById('hosting-storage-updated');
    const bar = document.getElementById('hosting-storage-progress-bar');
    const track = document.getElementById('hosting-storage-progress-track');
    const refreshButton = document.getElementById('hosting-storage-refresh');
    let loading = false;

    function setStatus(label, color) {
        const labelElement = document.getElementById('hosting-storage-status-label');
        if (labelElement) labelElement.textContent = label;
        dot.className = 'h-2 w-2 rounded-full ' + color;
    }

    function showUnavailable(data) {
        setStatus('Belum terhubung', 'bg-rose-500');
        used.textContent = 'Tidak tersedia';
        quota.textContent = '—';
        free.textContent = '—';
        percent.textContent = '—';
        bar.style.width = '0%';
        track.classList.add('opacity-40');
        message.textContent = data && data.message ? data.message : 'Data storage hosting belum dapat diambil.';
        updated.textContent = data && data.checked_at ? new Date(data.checked_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) : '—';
    }

    async function loadStorage(force) {
        if (loading) return;
        loading = true;
        refreshButton.disabled = true;
        refreshButton.classList.add('opacity-60', 'cursor-wait');
        setStatus(force ? 'Memperbarui…' : 'Menghubungkan…', 'bg-amber-400 animate-pulse');

        try {
            const url = endpoint + (force ? '?refresh=1' : '');
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            if (!response.ok || !data.available) {
                showUnavailable(data);
                return;
            }

            setStatus('Terhubung ke cPanel', 'bg-emerald-500');
            used.textContent = data.used_display || '—';
            quota.textContent = data.quota_display || '—';
            free.textContent = data.free_display || '—';
            percent.textContent = data.percent_display || '—';
            message.textContent = data.is_unlimited
                ? 'Paket hosting tidak menetapkan batas kuota disk.'
                : 'Statistik mengikuti data Disk Space Usage dari cPanel; pembaruan cPanel dapat memiliki jeda.';
            updated.textContent = data.checked_at
                ? new Date(data.checked_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' })
                : '—';

            if (data.percent !== null && Number.isFinite(Number(data.percent))) {
                bar.style.width = Math.max(0, Math.min(100, Number(data.percent))) + '%';
                track.classList.remove('opacity-40');
            } else {
                bar.style.width = '0%';
                track.classList.add('opacity-40');
            }
        } catch (error) {
            showUnavailable({ message: 'Gagal mengambil data storage. Periksa koneksi hosting dan konfigurasi API cPanel.' });
        } finally {
            loading = false;
            refreshButton.disabled = false;
            refreshButton.classList.remove('opacity-60', 'cursor-wait');
        }
    }

    refreshButton.addEventListener('click', function () { loadStorage(true); });
    loadStorage(false);
    window.setInterval(function () { loadStorage(false); }, 60000);
})();
</script>
@endpush

@endsection
