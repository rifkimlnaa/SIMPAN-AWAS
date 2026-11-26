@extends('layouts.app')

@section('title', 'Backup Media')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="mb-1 text-sm font-medium text-brand-700">Keamanan & pemeliharaan</p>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Backup Media</h1>
        <p class="mt-1 max-w-2xl text-sm text-slate-500">Unduh seluruh isi web (database dan semua berkas arsip) menjadi satu file ZIP.</p>
    </div>
    <form data-turbo="false" method="POST" action="{{ route('backup.unduh') }}">
        @csrf
        <button type="submit" class="btn btn-primary"><i class="ti ti-download h-4 w-4"></i> Unduh Backup (ZIP)</button>
    </form>
</div>

@if (session('success'))
    <div class="mb-5 flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
        <i class="ti ti-circle-check mt-0.5 h-4 w-4 shrink-0"></i>{{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="mb-5 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
        <i class="ti ti-alert-circle mt-0.5 h-4 w-4 shrink-0"></i>{{ session('error') }}
    </div>
@endif
@if ($errors->any())
    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="mb-6 grid gap-6 xl:grid-cols-3">
    <section class="card p-6 xl:col-span-2" x-data="{ f: '{{ old('frekuensi', $p['frekuensi']) }}' }">
        <h2 class="text-base font-semibold text-slate-900">Jadwal unduh otomatis</h2>
        <p class="mt-1 text-sm text-slate-500">Saat jadwal tiba, ZIP akan terunduh otomatis ketika admin membuka web ini di browser.</p>

        <form data-turbo="false" method="POST" action="{{ route('backup.jadwal') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
            @csrf
            @method('PUT')
            <div>
                <label class="label" for="frekuensi">Jadwal</label>
                <select id="frekuensi" name="frekuensi" class="input" x-model="f">
                    <option value="off">Nonaktif</option>
                    <option value="harian">Setiap hari</option>
                    <option value="mingguan">Setiap minggu</option>
                    <option value="bulanan">Setiap bulan</option>
                </select>
            </div>
            <div>
                <label class="label" for="jam">Pukul (WIB)</label>
                <input id="jam" name="jam" type="time" class="input" value="{{ old('jam', $p['jam']) }}" required>
            </div>
            <div x-show="f === 'mingguan'" x-cloak>
                <label class="label" for="hari">Hari</label>
                <select id="hari" name="hari" class="input">
                    @foreach (['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $i => $h)
                        <option value="{{ $i }}" @selected((int) old('hari', $p['hari']) === $i)>{{ $h }}</option>
                    @endforeach
                </select>
            </div>
            <div x-show="f === 'bulanan'" x-cloak>
                <label class="label" for="tanggal">Tanggal (1-28)</label>
                <input id="tanggal" name="tanggal" type="number" min="1" max="28" class="input" value="{{ old('tanggal', $p['tanggal']) }}">
            </div>
            <div class="sm:col-span-2">
                <label class="label" for="tujuan">Hasil backup</label>
                <select id="tujuan" name="tujuan" class="input">
                    <option value="keduanya" @selected(old('tujuan', $p['tujuan']) === 'keduanya')>Unduh ke komputer + kirim ke Google Drive</option>
                    <option value="unduh" @selected(old('tujuan', $p['tujuan']) === 'unduh')>Unduh ke komputer saja</option>
                    <option value="drive" @selected(old('tujuan', $p['tujuan']) === 'drive')>Kirim ke Google Drive saja</option>
                </select>
                <p class="mt-1 text-xs text-slate-500">Pilihan Google Drive hanya berjalan jika Drive sudah terhubung (lihat kotak Google Drive).</p>
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="btn btn-primary"><i class="ti ti-check h-4 w-4"></i> Simpan jadwal</button>
            </div>
        </form>

        <dl class="mt-6 grid gap-4 border-t border-slate-100 pt-5 text-sm sm:grid-cols-3">
            <div><dt class="text-slate-500">Jadwal aktif</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $labelJadwal }}</dd></div>
            <div><dt class="text-slate-500">Jadwal berikutnya</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $berikutnya ? $berikutnya->translatedFormat('d M Y, H:i').' WIB' : '—' }}</dd></div>
            <div><dt class="text-slate-500">Terakhir diunduh</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $p['terakhir_unduh'] ? \Carbon\Carbon::parse($p['terakhir_unduh'])->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i').' WIB' : 'Belum pernah' }}</dd></div>
        </dl>
    </section>

    <div class="space-y-6 self-start">
    <section class="card p-6">
        <h2 class="text-base font-semibold text-slate-900">Google Drive</h2>
        @if (! $driveSiap)
            <p class="mt-2 text-sm text-slate-500">Belum diatur. Isi <code>GOOGLE_DRIVE_CLIENT_ID</code>, <code>GOOGLE_DRIVE_CLIENT_SECRET</code>, dan <code>GOOGLE_DRIVE_REDIRECT_URI</code> di <code>.env</code>, lalu bersihkan cache.</p>
            <p class="mt-2 text-xs text-slate-500">Redirect URI yang dipakai: <code>{{ url('/backup/google/callback') }}</code></p>
        @elseif ($driveTerhubung)
            <p class="mt-2 flex items-center gap-2 text-sm font-medium text-emerald-700"><i class="ti ti-circle-check h-4 w-4"></i> Terhubung. Folder: SIMPAN-AWAS Backup</p>
            @if ($p['drive_status'])
                <p class="mt-2 text-xs {{ $p['drive_status'] === 'berhasil' ? 'text-slate-500' : 'text-red-600' }}">Kiriman terakhir ({{ \Carbon\Carbon::parse($p['drive_waktu'])->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB): {{ $p['drive_pesan'] }}</p>
            @endif
            <form data-turbo="false" method="POST" action="{{ route('backup.google.putus') }}" class="mt-3">
                @csrf
                <button type="submit" class="btn btn-light">Putuskan Google Drive</button>
            </form>
        @else
            <p class="mt-2 text-sm text-slate-500">Belum terhubung. Backup akan dikirim ke folder "SIMPAN-AWAS Backup" di Google Drive-mu.</p>
            <a data-turbo="false" href="{{ route('backup.google.hubungkan') }}" class="btn btn-primary mt-3"><i class="ti ti-link h-4 w-4"></i> Hubungkan Google Drive</a>
        @endif
    </section>

    <section class="card p-6">
        <h2 class="text-base font-semibold text-slate-900">Isi file ZIP</h2>
        <ul class="mt-3 space-y-2 text-sm text-slate-600">
            <li class="flex gap-2"><i class="ti ti-check mt-0.5 h-4 w-4 text-emerald-600"></i> Database aplikasi</li>
            <li class="flex gap-2"><i class="ti ti-check mt-0.5 h-4 w-4 text-emerald-600"></i> Seluruh berkas arsip</li>
            <li class="flex gap-2"><i class="ti ti-check mt-0.5 h-4 w-4 text-emerald-600"></i> Foto profil dan media web</li>
        </ul>
        <p class="mt-4 text-xs text-slate-500">ZIP tidak dienkripsi dan berisi data asli. Simpan di tempat aman (komputer kantor atau Google Drive). File <code>.env</code> tidak ikut.</p>
    </section>
    </div>
</div>

<section class="card mb-6 overflow-hidden" id="hosting-storage-panel" data-endpoint="{{ $storageUsageUrl }}" aria-live="polite">
    <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <span class="rounded-xl bg-brand-50 p-2.5 text-brand-700"><i class="ti ti-server h-5 w-5"></i></span>
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
                <i class="ti ti-refresh h-4 w-4"></i>
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
