<?php

namespace App\Services;

use App\Models\Pengaturan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/** Unggah file backup ke Google Drive (OAuth, scope drive.file, folder "SIMPAN-AWAS Backup"). */
class DriveUploader
{
    private const TOKEN_SETTING = 'backup_google_drive_refresh_token';
    private const FOLDER_SETTING = 'backup_google_drive_folder_id';
    private const SCOPE = 'https://www.googleapis.com/auth/drive.file';
    public const FOLDER_NAME = 'SIMPAN-AWAS Backup';

    public function isConfigured(): bool
    {
        return filled(config('services.google_drive.client_id'))
            && filled(config('services.google_drive.client_secret'))
            && filled(config('services.google_drive.redirect_uri'));
    }

    public function isConnected(): bool
    {
        return $this->refreshToken() !== null;
    }

    public function authorizationUrl(string $state): string
    {
        $this->assertConfigured();

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google_drive.client_id'),
            'redirect_uri' => config('services.google_drive.redirect_uri'),
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function completeAuthorization(string $code): void
    {
        $this->assertConfigured();
        $r = Http::asForm()->timeout(30)->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => config('services.google_drive.client_id'),
            'client_secret' => config('services.google_drive.client_secret'),
            'redirect_uri' => config('services.google_drive.redirect_uri'),
            'grant_type' => 'authorization_code',
        ]);

        if (! $r->successful()) {
            Log::warning('Google Drive OAuth gagal.', ['status' => $r->status()]);
            throw new RuntimeException('Google tidak berhasil menghubungkan akun. Periksa Client ID, Client Secret, dan Redirect URI.');
        }

        $token = $r->json('refresh_token');
        if (filled($token)) {
            Pengaturan::simpan(self::TOKEN_SETTING, Crypt::encryptString((string) $token));
        } elseif (! $this->isConnected()) {
            throw new RuntimeException('Google tidak mengirim izin jangka panjang. Cabut izin aplikasi lama di akun Google, lalu hubungkan ulang.');
        }

        Pengaturan::simpan(self::FOLDER_SETTING, null);
        $this->folderId($this->accessToken());
    }

    public function disconnect(): void
    {
        Pengaturan::simpan(self::TOKEN_SETTING, null);
        Pengaturan::simpan(self::FOLDER_SETTING, null);
    }

    /** Kirim satu file ke Drive. Mengembalikan ['id' =>, 'name' =>, 'link' =>]. */
    public function upload(string $path, string $name): array
    {
        if (! is_file($path)) {
            throw new RuntimeException('File backup tidak ditemukan untuk dikirim ke Google Drive.');
        }

        $token = $this->accessToken();
        $folder = $this->folderId($token);
        $size = (int) filesize($path);

        $start = Http::withToken($token)->acceptJson()
            ->withHeaders(['X-Upload-Content-Type' => 'application/zip', 'X-Upload-Content-Length' => (string) $size])
            ->timeout(45)
            ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&fields=id,name,webViewLink', [
                'name' => $name,
                'mimeType' => 'application/zip',
                'parents' => [$folder],
            ]);

        if (! $start->successful() || ! $start->header('Location')) {
            Log::warning('Google Drive: sesi upload gagal dibuka.', ['status' => $start->status()]);
            throw new RuntimeException('Google Drive tidak dapat memulai unggahan. Periksa izin dan sisa ruang Drive.');
        }

        $h = fopen($path, 'rb');
        if (! is_resource($h)) {
            throw new RuntimeException('File backup tidak dapat dibaca.');
        }
        try {
            $done = Http::withToken($token)
                ->withOptions(['connect_timeout' => 30, 'timeout' => 1800])
                ->send('PUT', $start->header('Location'), [
                    'headers' => ['Content-Type' => 'application/zip', 'Content-Length' => (string) $size],
                    'body' => $h,
                ]);
        } finally {
            fclose($h);
        }

        if (! $done->successful()) {
            Log::warning('Google Drive: unggahan tidak selesai.', ['status' => $done->status()]);
            throw new RuntimeException('Unggahan ke Google Drive belum selesai. Coba lagi nanti.');
        }

        return ['id' => (string) $done->json('id'), 'name' => (string) $done->json('name', $name), 'link' => $done->json('webViewLink')];
    }

    private function refreshToken(): ?string
    {
        $v = Pengaturan::ambil(self::TOKEN_SETTING);
        if (blank($v)) {
            return null;
        }
        try {
            return Crypt::decryptString($v);
        } catch (\Throwable) {
            return null;
        }
    }

    private function accessToken(): string
    {
        $this->assertConfigured();
        $refresh = $this->refreshToken();
        if (! $refresh) {
            throw new RuntimeException('Google Drive belum terhubung. Hubungkan akun Google terlebih dahulu.');
        }

        $r = Http::asForm()->timeout(30)->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google_drive.client_id'),
            'client_secret' => config('services.google_drive.client_secret'),
            'refresh_token' => $refresh,
            'grant_type' => 'refresh_token',
        ]);

        if (! $r->successful() || blank($r->json('access_token'))) {
            throw new RuntimeException('Izin Google Drive tidak berlaku lagi. Hubungkan ulang akun Google.');
        }

        return (string) $r->json('access_token');
    }

    private function folderId(string $token): string
    {
        $id = Pengaturan::ambil(self::FOLDER_SETTING);
        if (filled($id)) {
            $cek = Http::withToken($token)->acceptJson()->timeout(30)->get('https://www.googleapis.com/drive/v3/files/'.$id, ['fields' => 'id,trashed']);
            if ($cek->successful() && ! $cek->json('trashed')) {
                return $id;
            }
        }

        $r = Http::withToken($token)->acceptJson()->timeout(45)->post('https://www.googleapis.com/drive/v3/files?fields=id', [
            'name' => self::FOLDER_NAME,
            'mimeType' => 'application/vnd.google-apps.folder',
        ]);
        if (! $r->successful() || blank($r->json('id'))) {
            throw new RuntimeException('Folder "'.self::FOLDER_NAME.'" tidak dapat dibuat di Google Drive.');
        }
        Pengaturan::simpan(self::FOLDER_SETTING, (string) $r->json('id'));

        return (string) $r->json('id');
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Google Drive belum diatur di file .env (Client ID, Client Secret, Redirect URI).');
        }
    }
}
