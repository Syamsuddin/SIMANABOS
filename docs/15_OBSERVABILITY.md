# 15 — OBSERVABILITY

## Log aplikasi
- Channel `daily` di `storage/logs/laravel-YYYY-MM-DD.log`, retensi 30 hari; channel `errors` (level ≥ error) di `storage/logs/errors-*.log`.
- Format: pesan singkat + konteks array `{user_id, school_id, report_id, request_id}`. `request_id` dibuat middleware dan dikirim di header `X-Request-Id`.
- Tidak pernah mencatat: kata sandi, isi PDF, token sesi, NIP.

| Level | Kapan |
|---|---|
| error | Exception tak terduga, kegagalan job |
| warning | Transisi tidak sah, otorisasi ditolak, buka kunci laporan |
| info | Pelanggaran aturan domain, konflik penguncian, tutup tahun |

## Jejak audit (fungsi bisnis, bukan log teknis)
`spatie/laravel-activitylog` mencatat untuk model `MonthlyReport`, `ReportExpenseLine`, `ReportAdjustment`, `BudgetVersion`, `BudgetLine`, `OpeningBalance`, `Allocation`, `DocumentVersion`, `SchoolOfficial`, `User` (tanpa password): pelaku, waktu, atribut lama dan baru. Riwayat status tambahan di `status_histories`. Ditampilkan di halaman detail laporan sesuai akses `docs/05_USER_ROLE.md`.

## Pekerjaan latar
- Job gagal → `failed_jobs`; dicek harian lewat `php artisan queue:failed`.
- Scheduler mencatat `info` jumlah laporan yang ditandai terlambat dan notifikasi terkirim.

## Metrik
Metrik operasional dan monitoring server `[TERBUKA]` pasca-MVP. Metrik bisnis (`docs/00_EXECUTIVE_SUMMARY.md`) dihitung dari data dan tampil di dasbor.
