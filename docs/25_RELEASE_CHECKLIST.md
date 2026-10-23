# 25 — RELEASE_CHECKLIST

Jalankan berurutan. Berhenti pada langkah yang gagal. Perintah: `docs/11_COMMANDS.md`. Gerbang: `docs/22_CHANGE_POLICY.md`.

| # | Langkah | Lulus bila |
|---|---|---|
| 1 | `php artisan test` dan `./vendor/bin/pint --test` di commit rilis | Exit 0 |
| 2 | Buat tag `vX.Y.Z` di `main` | Tag ada |
| 3 | Uji migrasi pada salinan basis data produksi terbaru | `php artisan migrate --force` exit 0; `migrate:status` tanpa Pending |
| 4 | Backup produksi: `php artisan simanabos:backup` (`mysqldump` basis data + arsip `storage/app/private`) | Kedua berkas ada, ukuran > 0, dicatat lokasinya |
| 5 | Mode pemeliharaan: `php artisan down` | Situs menampilkan 503 |
| 6 | Deploy kode tag; `composer install --no-dev --optimize-autoloader`; `npm ci && npm run build` | Exit 0 |
| 7 | `php artisan migrate --force` ⚠️ | Exit 0 |
| 8 | `php artisan optimize` lalu restart worker queue (`php artisan queue:restart`) | Exit 0 |
| 9 | Bila catatan rilis memintanya: `php artisan simanabos:recompute` untuk sekolah terdampak | Selesai tanpa error |
| 10 | `php artisan up` | Situs 200 |
| 11 | Smoke test: login tiap role; buka dasbor; buka satu laporan; unduh satu PDF; buka antrean pemeriksaan | Semua 200, tanpa entri baru di log `errors` |
| 12 | Rollback siap | Tag sebelumnya dan backup langkah 4 tercatat; prosedur di `docs/22_CHANGE_POLICY.md` |

Rilis produksi pertama tambahan: `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, HTTPS aktif, seeder tidak dijalankan, akun admin pertama dibuat lewat `php artisan simanabos:create-admin` dengan `must_change_password=true`.
