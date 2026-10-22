# Arsip 23 — F-02 Data referensi (bagian dasar, diterima 2026-10-22)

Penegakan kini di test suite: `tests/Feature/Reference/ReferenceDataTest.php`.

## F-02 (kecamatan, sumber dana, tahun anggaran, tahap, tenggat)
- Given tahun baru dibuka, Then 12 baris `monthly_deadlines` tercipta dari `default_deadline_day` (tanggal itu di bulan berikutnya).
- Given sumber dana dibuat, Then `code` tidak dapat diubah; menonaktifkan diizinkan.
- Given tahun sudah ditutup, Then tahap dan tenggat tidak dapat diubah.
- Verifikasi: `php artisan test --filter=Reference` → hijau.
