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

    /** URL foto latar halaman login, atau null jika belum diatur (panel biru polos). */
    public static function latarLoginUrl(): ?string
    {
        $path = static::ambil(self::LATAR_LOGIN);

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return asset('storage/'.$path);
    }
}
