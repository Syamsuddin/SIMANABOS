<?php

return [

    /** Batas ukuran unggahan PDF, dalam KB (docs/10_DEV_ENV.md). */
    'max_upload_kb' => (int) env('SIMANABOS_MAX_UPLOAD_KB', 20480),

    /** Cadangan harian `simanabos:backup` (docs/22 Rollback); simpan di luar disk publik. */
    'backup' => [
        'path' => env('SIMANABOS_BACKUP_PATH', storage_path('app/backups')),
        'retention_days' => (int) env('SIMANABOS_BACKUP_RETENTION_DAYS', 30),
        'mysqldump' => env('SIMANABOS_MYSQLDUMP', 'mysqldump'),
    ],

];
