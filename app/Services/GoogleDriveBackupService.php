<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\Pengaturan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleDriveBackupService
{
    private const TOKEN_SETTING = 'backup_google_drive_refresh_token';
    private const FOLDER_SETTING = 'backup_google_drive_folder_id';
    private const SCOPE = 'https://www.googleapis.com/auth/drive.file';

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
        $query = http_build_query([
            'client_id' => config('services.google_drive.client_id'),
            'redirect_uri' => config('services.google_drive.redirect_uri'),
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'include_granted_scopes' => 'true',
            'prompt' => 'consent',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.$query;
    }

    public function completeAuthorization(string $authorizationCode): void
    {
        $this->assertConfigured();
        $response = Http::asForm()->timeout(30)->post('https://oauth2.googleapis.com/token', [
            'code' => $authorizationCode,
            'client_id' => config('services.google_drive.client_id'),
            'client_secret' => config('services.google_drive.client_secret'),
            'redirect_uri' => config('services.google_drive.redirect_uri'),
            'grant_type' => 'authorization_code',
        ]);

        if (! $response->successful()) {
            Log::warning('Google Drive OAuth code exchange failed.', ['status' => $response->status()]);
            throw new RuntimeException('Google tidak berhasil menghubungkan akun. Periksa OAuth Client dan Redirect URI.');
        }

        $token = $response->json('refresh_token');
        if (filled($token)) {
            Pengaturan::simpan(self::TOKEN_SETTING, Crypt::encryptString((string) $token));
        } elseif (! $this->isConnected()) {
            throw new RuntimeException('Google tidak mengirim refresh token. Cabut izin aplikasi lama di Google Account, lalu hubungkan ulang.');
        }

        // Folder private dibuat oleh aplikasi dan disimpan terpisah dari token rahasia.
        $this->ensureBackupFolder($this->accessToken());
    }

    public function disconnect(): void
    {
        Pengaturan::simpan(self::TOKEN_SETTING, null);
        Pengaturan::simpan(self::FOLDER_SETTING, null);
    }

    public function uploadBackup(Backup $backup, BackupPackageService $packages): array
    {
        if (! $backup->isComplete()) {
            throw new RuntimeException('Hanya backup yang selesai yang dapat dikirim ke Google Drive.');
        }

        $package = $packages->build($backup);
        try {
            $token = $this->accessToken();
            $folderId = $this->ensureBackupFolder($token);
            $fileSize = (int) filesize($package['path']);
            $metadata = [
                'name' => $package['filename'],
                'mimeType' => 'application/zip',
                'parents' => [$folderId],
                'description' => 'Backup terenkripsi SIMPAN-AWAS. Snapshot SHA-256: '.$backup->checksum,
            ];

            // Resumable upload cocok untuk file backup besar dan koneksi hosting yang dapat terputus.
            $start = Http::withToken($token)
                ->acceptJson()
                ->withHeaders([
                    'X-Upload-Content-Type' => 'application/zip',
                    'X-Upload-Content-Length' => (string) $fileSize,
                ])
                ->timeout(45)
                ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&fields=id,name,size,mimeType,webViewLink,createdTime', $metadata);

            if (! $start->successful() || ! $start->header('Location')) {
                Log::warning('Google Drive resumable session could not be started.', ['status' => $start->status()]);
                throw new RuntimeException('Google Drive tidak dapat memulai upload. Periksa izin dan kuota Drive.');
            }

            $stream = fopen($package['path'], 'rb');
            if (! is_resource($stream)) {
                throw new RuntimeException('Paket ZIP tidak dapat dibaca untuk dikirim ke Google Drive.');
            }
            try {
                $uploaded = Http::withToken($token)
                    ->withOptions(['connect_timeout' => 30, 'timeout' => (int) config('backup.google_drive.upload_timeout', 1800), 'verify' => true])
                    ->send('PUT', $start->header('Location'), [
                        'headers' => [
                            'Content-Type' => 'application/zip',
                            'Content-Length' => (string) $fileSize,
                        ],
                        'body' => $stream,
                    ]);
            } finally {
                fclose($stream);
            }

            if (! $uploaded->successful()) {
                Log::warning('Google Drive upload did not complete.', ['status' => $uploaded->status(), 'backup_id' => $backup->id]);
                throw new RuntimeException('Upload ke Google Drive belum berhasil diselesaikan. Coba ulang dari riwayat backup.');
            }

            $driveData = [
                'status' => 'uploaded',
                'file_id' => (string) $uploaded->json('id'),
                'name' => (string) $uploaded->json('name', $package['filename']),
                'web_view_link' => $uploaded->json('webViewLink'),
                'uploaded_at' => now()->toIso8601String(),
                'size' => $fileSize,
            ];
            $metadataCurrent = $backup->metadata ?? [];
            $backup->update(['metadata' => array_merge($metadataCurrent, ['google_drive' => $driveData])]);

            return $driveData;
        } catch (\Throwable $exception) {
            $current = $backup->fresh()->metadata ?? [];
            $current['google_drive'] = [
                'status' => 'failed',
                'error' => mb_substr($exception->getMessage(), 0, 240),
                'attempted_at' => now()->toIso8601String(),
            ];
            $backup->fresh()->update(['metadata' => $current]);
            throw $exception;
        } finally {
            $packages->cleanup($package);
        }
    }

    public function connectedFolderName(): string
    {
        return 'SIMPAN-AWAS Backup';
    }

    private function accessToken(): string
    {
        $this->assertConfigured();
        $refreshToken = $this->refreshToken();
        if (! $refreshToken) {
            throw new RuntimeException('Google Drive belum terhubung. Hubungkan akun Google terlebih dahulu.');
        }

        $response = Http::asForm()->timeout(30)->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google_drive.client_id'),
            'client_secret' => config('services.google_drive.client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful() || ! $response->json('access_token')) {
            Log::warning('Google Drive refresh token could not be exchanged.', ['status' => $response->status()]);
            throw new RuntimeException('Koneksi Google Drive perlu dihubungkan ulang.');
        }

        return (string) $response->json('access_token');
    }

    private function refreshToken(): ?string
    {
        $encrypted = Pengaturan::ambil(self::TOKEN_SETTING);
        if (blank($encrypted)) return null;
        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            return null;
        }
    }

    private function ensureBackupFolder(string $accessToken): string
    {
        $saved = Pengaturan::ambil(self::FOLDER_SETTING);
        if (filled($saved)) return $saved;

        $response = Http::withToken($accessToken)->acceptJson()->timeout(45)->post(
            'https://www.googleapis.com/drive/v3/files?fields=id,name,mimeType',
            ['name' => $this->connectedFolderName(), 'mimeType' => 'application/vnd.google-apps.folder']
        );
        if (! $response->successful() || ! $response->json('id')) {
            Log::warning('Google Drive backup folder could not be created.', ['status' => $response->status()]);
            throw new RuntimeException('Folder SIMPAN-AWAS Backup tidak dapat dibuat di Google Drive.');
        }

        $folderId = (string) $response->json('id');
        Pengaturan::simpan(self::FOLDER_SETTING, $folderId);
        return $folderId;
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('OAuth Google Drive belum dikonfigurasi di file .env.');
        }
    }
}
