<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Dua kategori utama. Nama bisa diubah admin lewat menu Kategori.
        $kategori = [
            ['nama' => 'PSAP', 'keterangan' => 'Penyelesaian Sengketa Antar Peserta Pemilu'],
            ['nama' => 'PSPP', 'keterangan' => 'Penyelesaian Sengketa Peserta Pemilu dengan Penyelenggara Pemilu'],
        ];

        foreach ($kategori as $item) {
            Kategori::firstOrCreate(
                ['slug' => Str::slug($item['nama'])],
                ['nama' => $item['nama'], 'keterangan' => $item['keterangan'], 'jenis' => Kategori::JENIS_FORMULIR]
            );
        }

        if (User::where('role', User::ROLE_ADMIN)->exists()) {
            return;
        }

        // Password acak, ditampilkan sekali di terminal. Tidak ada password bawaan yang bisa ditebak.
        $password = env('SEED_ADMIN_PASSWORD') ?: Str::password(14, symbols: false);

        $admin = new User([
            'name' => 'Administrator',
            'username' => 'admin',
            'password' => $password,
        ]);
        $admin->role = User::ROLE_ADMIN;
        $admin->save();

        $this->command?->warn('Akun admin dibuat. Catat sekarang, tidak ditampilkan lagi:');
        $this->command?->line('  username : admin');
        $this->command?->line('  password : '.$password);
    }
}
