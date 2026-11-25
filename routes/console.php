<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daftarkan command dan jadwal Backup & Pemulihan.
require_once __DIR__.'/backup-schedule.php';
