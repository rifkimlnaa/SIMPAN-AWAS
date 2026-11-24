<?php

return [
    /*
    | Backup files are encrypted before they are written to this disk.
    | Use "local" for private hosting storage or "s3" for off-site object storage.
    */
    'disk' => env('BACKUP_DISK', 'local'),
    'path' => env('BACKUP_PATH', 'backups'),

    // Generate with: php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
    // Keep this key outside the repository and keep a separate secure copy.
    'encryption_key' => env('BACKUP_ENCRYPTION_KEY'),

    'mysql_dump_binary' => env('BACKUP_MYSQLDUMP_BINARY', 'mysqldump'),
    'mysql_binary' => env('BACKUP_MYSQL_BINARY', 'mysql'),
    'process_timeout' => (int) env('BACKUP_PROCESS_TIMEOUT', 1800),
    'chunk_size' => 1048576,

    'retention' => [
        'daily_days' => (int) env('BACKUP_KEEP_DAILY_DAYS', 7),
        'weekly_weeks' => (int) env('BACKUP_KEEP_WEEKLY_WEEKS', 4),
        'monthly_months' => (int) env('BACKUP_KEEP_MONTHLY_MONTHS', 12),
        'pre_restore_days' => (int) env('BACKUP_KEEP_PRE_RESTORE_DAYS', 14),
    ],
];
