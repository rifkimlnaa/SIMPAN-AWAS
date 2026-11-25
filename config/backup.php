<?php

return [
    /* File backup dienkripsi sebelum disimpan pada disk ini. */
    'disk' => env('BACKUP_DISK', 'local'),
    'path' => env('BACKUP_PATH', 'backups'),
    'encryption_key' => env('BACKUP_ENCRYPTION_KEY'),

    // Mesin PHP-native untuk hosting yang menonaktifkan seluruh fungsi shell.
    'chunk_size' => 1048576,
    'max_import_bytes' => (int) env('BACKUP_MAX_IMPORT_BYTES', 2147483648),
    'max_restore_entries' => (int) env('BACKUP_MAX_RESTORE_ENTRIES', 50000),
    'max_restore_uncompressed_bytes' => (int) env('BACKUP_MAX_RESTORE_BYTES', 10737418240),

    // API token cPanel untuk membaca kuota akun (opsional).
    'cpanel' => [
        'api_url' => env('BACKUP_CPANEL_API_URL'),
        'username' => env('BACKUP_CPANEL_USERNAME'),
        'api_token' => env('BACKUP_CPANEL_API_TOKEN'),
    ],

    'google_drive' => [
        'auto_upload' => (bool) env('BACKUP_GOOGLE_DRIVE_AUTO_UPLOAD', true),
        'upload_timeout' => (int) env('BACKUP_GOOGLE_DRIVE_UPLOAD_TIMEOUT', 1800),
    ],

    'retention' => [
        'daily_days' => (int) env('BACKUP_KEEP_DAILY_DAYS', 7),
        'weekly_weeks' => (int) env('BACKUP_KEEP_WEEKLY_WEEKS', 4),
        'monthly_months' => (int) env('BACKUP_KEEP_MONTHLY_MONTHS', 12),
        'pre_restore_days' => (int) env('BACKUP_KEEP_PRE_RESTORE_DAYS', 14),
    ],
];
