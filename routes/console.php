<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Perintah dan jadwal backup PHP-native; tidak menjalankan proses shell dari PHP.
require_once __DIR__.'/backup-schedule.php';
