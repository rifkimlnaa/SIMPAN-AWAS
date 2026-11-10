<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_PETUGAS = 'petugas';

    // 'role' sengaja tidak boleh di-mass-assign.
    protected $fillable = ['name', 'username', 'password', 'foto'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function roleLabel(): string
    {
        return $this->isAdmin() ? 'Administrator' : 'Petugas';
    }

    public function arsips(): HasMany
    {
        return $this->hasMany(Arsip::class);
    }

    public function fotoUrl(): ?string
    {
        // asset() ikut host yang sedang dipakai, jadi tidak tergantung APP_URL.
        return $this->foto ? asset('storage/'.$this->foto) : null;
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/', trim($this->name)) ?: [];
        $letters = collect($words)->filter()->take(2)->map(fn ($w) => Str::upper(Str::substr($w, 0, 1)));

        return $letters->implode('') ?: '?';
    }
}
