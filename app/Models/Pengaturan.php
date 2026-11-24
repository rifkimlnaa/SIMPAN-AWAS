<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Pengaturan extends Model
{
    public const LATAR_LOGIN = 'latar_login';

    protected $table = 'pengaturans';

    protected $fillable = ['kunci', 'nilai'];

    public static function ambil(string $kunci, ?string $default = null): ?string
    {
        return static::where('kunci', $kunci)->value('nilai') ?? $default;
    }

    public static function simpan(string $kunci, ?string $nilai): void
    {
        static::updateOrCreate(['kunci' => $kunci], ['nilai' => $nilai]);
    }

    /** Path media latar login jika file masih tersedia di disk publik. */
    public static function latarLoginPath(): ?string
    {
        $path = static::ambil(self::LATAR_LOGIN);

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return $path;
    }

    /** URL media latar halaman login, atau null jika belum diatur. */
    public static function latarLoginUrl(): ?string
    {
        $path = static::latarLoginPath();

        return $path ? asset('storage/'.$path) : null;
    }

    /** Menentukan apakah media latar adalah video yang didukung browser. */
    public static function latarLoginIsVideo(): bool
    {
        $path = static::latarLoginPath();
        $extension = $path ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : '';

        return in_array($extension, ['mp4', 'webm'], true);
    }

    /** MIME type yang sesuai untuk elemen video HTML. */
    public static function latarLoginMimeType(): ?string
    {
        $path = static::latarLoginPath();
        if (! $path) {
            return null;
        }

        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            default => null,
        };
    }
}
