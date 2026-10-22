# 11 — COMMANDS

| Tujuan | Perintah |
|---|---|
| Setup awal | `composer install && npm install && php artisan key:generate && php artisan storage:link` |
| Migrasi + data uji (lokal) | `php artisan migrate:fresh --seed` ⚠️ lokal saja |
| Migrasi | `php artisan migrate` ⚠️ |
| Status migrasi | `php artisan migrate:status` |
| Jalankan lokal (server + queue + Vite) | `composer run dev` |
| Server saja | `php artisan serve` |
| Build aset | `npm run build` |
| Tes semua | `php artisan test` |
| Tes satu berkas/filter | `php artisan test --filter=ReportBalanceCalculator` |
| Tes golden rumus | `php artisan test --filter=GoldenFebruary` |
| Cakupan tes | `php artisan test --coverage --min=80` (butuh Xdebug/PCOV) |
| Gaya kode (cek) | `./vendor/bin/pint --test` |
| Gaya kode (perbaiki) | `./vendor/bin/pint` |
| Worker queue | `php artisan queue:work --tries=3` |
| Penjadwal (sekali jalan) | `php artisan schedule:run` |
| Daftar route | `php artisan route:list --except-vendor` |
| Hitung ulang cache saldo satu sekolah | `php artisan simanabos:recompute {npsn} {year}` |
| Bendera terlambat manual | `php artisan simanabos:flag-late` |
| Kirim pengingat tenggat manual | `php artisan simanabos:remind-deadlines` |
| Buat admin pertama (produksi) | `php artisan simanabos:create-admin` |
| Bersihkan cache | `php artisan optimize:clear` |
| Optimasi produksi | `php artisan optimize` |
| Migrasi produksi | `php artisan migrate --force` ⚠️ setelah backup (`docs/25_RELEASE_CHECKLIST.md`) |
| Validasi paket blueprint | `bash scripts/validate.sh` |

Perintah `simanabos:*` adalah Artisan command proyek di `app/Console/Commands/`.
