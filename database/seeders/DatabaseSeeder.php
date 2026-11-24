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
        // Dua kategori utama: Penanganan Pelanggaran dan Penyelesaian Sengketa.
        // Keduanya tidak bisa dihapus; admin boleh mengubah nama/keterangannya lewat menu Kategori.
        foreach (config('arsip.kategori_utama') as $jenis => $item) {
            // firstOrCreate: nama yang sudah diubah admin tidak ditimpa saat seeder dijalankan ulang.
            Kategori::firstOrCreate(
                ['jenis' => $jenis],
                ['nama' => $item['nama'], 'slug' => Str::slug($item['nama']), 'keterangan' => $item['keterangan']]
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
