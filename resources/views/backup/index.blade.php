@extends('layouts.app')

@section('title', 'Backup & Pemulihan Data')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="mb-1 text-sm font-medium text-brand-700">Keamanan & pemeliharaan</p>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Backup &amp; Pemulihan Data</h1>
        <p class="mt-1 max-w-2xl text-sm text-slate-500">Cadangkan database dan berkas arsip SIMPAN-AWAS, lalu pulihkan ketika diperlukan.</p>
    </div>
    <form method="POST" action="{{ route('backup.store') }}" class="flex flex-col items-stretch gap-2 sm:items-end">
        @csrf
        @if($googleDriveConnected)
            <label class="flex items-center gap-2 text-xs text-slate-600">
                <input type="checkbox" name="send_to_drive" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                Kirim salinan ke Google Drive setelah backup
            </label>
        @endif
        <button type="submit" class="btn btn-primary" @disabled(!$keyReady)>
            <i data-lucide="cloud-upload" class="h-4 w-4"></i>
            Buat Backup Sekarang
        </button>
    </form>
</div>

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

@php
    $formatStorageBytes = function ($bytes) {
        $bytes = max(0, (float) ($bytes ?? 0));
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;
        while ($bytes >= 1024 && $index < count($units) - 1) { $bytes /= 1024; $index++; }
        return number_format($bytes, $index === 0 ? 0 : 2, ',', '.') . ' ' . $units[$index];
    };
    $quota = data_get($storageUsage, 'cpanel', []);
    $appUsage = data_get($storageUsage, 'application', []);
    $initialQuotaPercent = data_get($quota, 'percent');
@endphp

<section id="backup-storage-panel" data-endpoint="{{ route('backup.storage-usage') }}" class="card mb-6 p-5">
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2">
            <span class="rounded-lg bg-blue-50 p-2 text-blue-700"><i data-lucide="hard-drive" class="h-5 w-5"></i></span>
            <div><h2 class="font-semibold text-slate-900">Ruang penyimpanan live</h2><p class="text-xs text-slate-500">Penggunaan aplikasi dan kuota akun hosting diperbarui berkala.</p></div>
        </div>
        <span class="text-xs text-slate-400" id="backup-storage-checked">Pembaruan: {{ data_get($storageUsage, 'checked_at') ? \Illuminate\Support\Carbon::parse(data_get($storageUsage, 'checked_at'))->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') . ' WIB' : 'Belum tersedia' }}</span>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 p-4">
            <div class="flex items-center justify-between gap-2">
                <h3 class="text-sm font-semibold text-slate-800">Kuota akun cPanel</h3>
                <span id="cpanel-storage-status" class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ data_get($quota, 'available') ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ data_get($quota, 'available') ? 'Terhubung' : (data_get($quota, 'configured') ? 'Tidak tersedia' : 'Belum dihubungkan') }}</span>
            </div>
            @if(data_get($quota, 'available'))
                <div class="mt-3 flex items-end justify-between gap-3">
                    <div><p class="text-2xl font-bold text-slate-900" id="cpanel-storage-used">{{ $formatStorageBytes(data_get($quota, 'used_bytes')) }}</p><p class="text-xs text-slate-500">Terpakai</p></div>
                    <div class="text-right"><p class="text-sm font-semibold text-slate-800" id="cpanel-storage-total">{{ data_get($quota, 'unlimited') ? 'Unlimited' : (is_numeric(data_get($quota, 'total_bytes')) ? $formatStorageBytes(data_get($quota, 'total_bytes')) : 'Tidak dilaporkan') }}</p><p class="text-xs text-slate-500">Total kuota</p></div>
                </div>
                <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-slate-100"><div id="cpanel-storage-bar" class="h-full rounded-full bg-brand-500 transition-all duration-500" style="width: {{ $initialQuotaPercent ?? 0 }}%"></div></div>
                <p class="mt-2 text-xs text-slate-500" id="cpanel-storage-free">{{ data_get($quota, 'unlimited') ? 'Kuota dilaporkan unlimited oleh cPanel.' : (is_numeric(data_get($quota, 'free_bytes')) ? $formatStorageBytes(data_get($quota, 'free_bytes')) . ' tersedia' : 'Sisa kuota tidak dilaporkan cPanel.') }}</p>
            @else
                <p class="mt-3 text-sm font-semibold text-slate-800" id="cpanel-storage-used">{{ data_get($quota, 'message', 'Kuota akun hosting belum terhubung.') }}</p>
                <p class="mt-2 text-xs leading-relaxed text-slate-500">Untuk angka kuota akun cPanel yang akurat, isi BACKUP_CPANEL_API_URL, BACKUP_CPANEL_USERNAME, dan BACKUP_CPANEL_API_TOKEN di .env. Nilai ini berbeda dari ruang kosong filesystem server.</p>
                <div class="mt-3 h-2.5 rounded-full bg-slate-100"></div>
            @endif
        </div>

        <div class="rounded-xl border border-slate-200 p-4">
            <div class="flex items-center justify-between gap-2"><h3 class="text-sm font-semibold text-slate-800">Pemakaian SIMPAN-AWAS</h3><span class="badge badge-gray">Aplikasi</span></div>
            <div class="mt-3 flex items-end justify-between gap-3">
                <div><p class="text-2xl font-bold text-slate-900" id="app-storage-total">{{ $formatStorageBytes(data_get($appUsage, 'total_bytes')) }}</p><p class="text-xs text-slate-500">Total aplikasi terhitung</p></div>
                <p class="text-xs text-slate-500">Database + arsip + media + backup</p>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                <div><span class="text-slate-500">Database</span><strong id="app-storage-db" class="mt-0.5 block text-slate-800">{{ $formatStorageBytes(data_get($appUsage, 'database_bytes')) }}</strong></div>
                <div><span class="text-slate-500">Dokumen arsip</span><strong id="app-storage-archives" class="mt-0.5 block text-slate-800">{{ $formatStorageBytes(data_get($appUsage, 'archive_bytes')) }}</strong></div>
                <div><span class="text-slate-500">Backup</span><strong id="app-storage-backups" class="mt-0.5 block text-slate-800">{{ $formatStorageBytes(data_get($appUsage, 'backup_bytes')) }}</strong></div>
                <div><span class="text-slate-500">Foto / media</span><strong id="app-storage-media" class="mt-0.5 block text-slate-800">{{ $formatStorageBytes(data_get($appUsage, 'public_media_bytes')) }}</strong></div>
            </div>
        </div>
    </div>
    @if(data_get($storageUsage, 'server_volume'))
        <p class="mt-3 text-xs text-slate-500" id="server-volume-note">Filesystem server: {{ $formatStorageBytes(data_get($storageUsage, 'server_volume.used_bytes')) }} terpakai dari {{ $formatStorageBytes(data_get($storageUsage, 'server_volume.total_bytes')) }}. Ini kapasitas volume server, bukan kuota akun cPanel.</p>
    @endif
    <p class="mt-2 text-[11px] text-slate-400">Pembaruan otomatis setiap 60 detik. Data kuota cPanel mengikuti statistik yang disediakan server cPanel dan dapat memiliki jeda pembaruan.</p>
</section>

<section class="card mb-6 p-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <span class="rounded-xl bg-emerald-50 p-3 text-emerald-700"><i data-lucide="cloud" class="h-6 w-6"></i></span>
            <div><h2 class="font-semibold text-slate-900">Google Drive</h2><p class="text-xs text-slate-500">Salinan backup off-site di folder SIMPAN-AWAS Backup.</p></div>
        </div>
        @if($googleDriveConnected)
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700"><i data-lucide="check-circle-2" class="h-3.5 w-3.5"></i> Terhubung</span>
                <form method="POST" action="{{ route('backup.google.disconnect') }}" onsubmit="return confirm('Putuskan koneksi Google Drive? File yang sudah tersimpan di Drive tidak akan dihapus.');">@csrf<button class="btn btn-secondary" type="submit">Putuskan</button></form>
            </div>
        @elseif($googleDriveConfigured)
            <a href="{{ route('backup.google.connect') }}" class="btn btn-primary"><i data-lucide="link" class="h-4 w-4"></i> Hubungkan Google Drive</a>
        @else
            <span class="badge badge-gray">Belum dikonfigurasi</span>
        @endif
    </div>
    @if(!$googleDriveConfigured)
        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-relaxed text-amber-900">
            <strong>Siapkan OAuth Google terlebih dahulu.</strong> Buat OAuth Client tipe Web Application di Google Cloud, aktifkan Google Drive API, lalu isi GOOGLE_DRIVE_CLIENT_ID, GOOGLE_DRIVE_CLIENT_SECRET, dan GOOGLE_DRIVE_REDIRECT_URI di .env. Redirect URI harus sama persis dengan <code>{{ url('/backup/google/callback') }}</code>.
        </div>
    @elseif($googleDriveConnected)
        <p class="mt-3 text-xs text-slate-500">Backup manual dapat dikirim langsung melalui checkbox saat membuat backup. Backup terjadwal juga akan dikirim otomatis bila BACKUP_GOOGLE_DRIVE_AUTO_UPLOAD=true.</p>
    @else
        <p class="mt-3 text-xs text-slate-500">OAuth sudah dikonfigurasi. Hubungkan akun Google yang akan menjadi pemilik salinan backup.</p>
    @endif
</section>

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
                        <td class="max-w-xs px-5 py-4"><p class="break-all font-medium text-slate-800">{{ $backup->filename }}</p><p class="mt-1 text-xs text-slate-500">{{ $backup->creator?->name ?? 'Otomatis / Sistem' }}</p>@if(data_get($backup->metadata, 'google_drive.status') === 'uploaded')<p class="mt-1 text-xs text-emerald-700">Tersalin ke Google Drive</p>@elseif(data_get($backup->metadata, 'google_drive.status') === 'failed')<p class="mt-1 text-xs text-amber-700">Upload Drive gagal — dapat dicoba ulang</p>@endif @if($backup->error_message)<p class="mt-1 text-xs text-red-600">{{ $backup->error_message }}</p>@endif</td>
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
                                    <a href="{{ route('backup.download', $backup) }}" class="btn-icon" title="Unduh paket ZIP backup"><i data-lucide="download" class="h-4 w-4"></i></a>
                                    @if($googleDriveConnected)
                                        <form method="POST" action="{{ route('backup.google.upload', $backup) }}">@csrf<button type="submit" class="btn-icon" title="Kirim backup ke Google Drive"><i data-lucide="cloud-upload" class="h-4 w-4"></i></button></form>
                                    @endif
                                    @if(data_get($backup->metadata, 'google_drive.status') === 'uploaded' && data_get($backup->metadata, 'google_drive.web_view_link'))
                                        <a href="{{ data_get($backup->metadata, 'google_drive.web_view_link') }}" target="_blank" rel="noopener noreferrer" class="btn-icon" title="Buka salinan Google Drive"><i data-lucide="external-link" class="h-4 w-4"></i></a>
                                    @endif
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
    const panel = document.getElementById('backup-storage-panel');
    if (!panel) return;
    const endpoint = panel.dataset.endpoint;
    const format = (bytes) => {
        let value = Math.max(0, Number(bytes || 0));
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        let index = 0;
        while (value >= 1024 && index < units.length - 1) { value /= 1024; index++; }
        return new Intl.NumberFormat('id-ID', { maximumFractionDigits: index === 0 ? 0 : 2 }).format(value) + ' ' + units[index];
    };
    const setText = (id, text) => { const node = document.getElementById(id); if (node) node.textContent = text; };
    async function refreshUsage() {
        try {
            const response = await fetch(endpoint, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', cache: 'no-store' });
            if (!response.ok) return;
            const data = await response.json();
            const app = data.application || {};
            const quota = data.cpanel || {};
            setText('app-storage-total', format(app.total_bytes));
            setText('app-storage-db', format(app.database_bytes));
            setText('app-storage-archives', format(app.archive_bytes));
            setText('app-storage-backups', format(app.backup_bytes));
            setText('app-storage-media', format(app.public_media_bytes));
            if (quota.available) {
                setText('cpanel-storage-status', 'Terhubung');
                setText('cpanel-storage-used', format(quota.used_bytes));
                setText('cpanel-storage-total', quota.unlimited ? 'Unlimited' : (quota.total_bytes !== null && quota.total_bytes !== undefined && Number.isFinite(Number(quota.total_bytes)) ? format(quota.total_bytes) : 'Tidak dilaporkan'));
                setText('cpanel-storage-free', quota.unlimited ? 'Kuota hosting dilaporkan unlimited oleh cPanel.' : (quota.free_bytes !== null && quota.free_bytes !== undefined && Number.isFinite(Number(quota.free_bytes)) ? format(quota.free_bytes) + ' tersedia' : 'Sisa kuota tidak dilaporkan cPanel.'));
                const bar = document.getElementById('cpanel-storage-bar');
                if (bar) bar.style.width = (quota.percent || 0) + '%';
            } else {
                setText('cpanel-storage-status', quota.configured ? 'Tidak tersedia' : 'Belum dihubungkan');
                if (document.getElementById('cpanel-storage-used')) setText('cpanel-storage-used', quota.message || 'Statistik cPanel belum tersedia.');
            }
            if (data.server_volume) {
                setText('server-volume-note', 'Filesystem server: ' + format(data.server_volume.used_bytes) + ' terpakai dari ' + format(data.server_volume.total_bytes) + '. Ini kapasitas volume server, bukan kuota akun cPanel.');
            }
            if (data.checked_at) {
                const checked = new Date(data.checked_at);
                setText('backup-storage-checked', 'Pembaruan: ' + checked.toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) + ' WIB');
            }
        } catch (error) {
            // Panel tetap menampilkan snapshot terakhir jika koneksi sementara gagal.
        }
    }
    window.setInterval(refreshUsage, 60000);
})();
</script>
@endpush

@endsection
