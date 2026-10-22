# 10 — DEV_ENV

## Prasyarat lokal
PHP ≥ 8.3 dengan ekstensi di `docs/09_STACK.md`, Composer 2, Node.js LTS + npm, MySQL 8.

## Setup lokal
1. Salin `.env.example` → `.env`; isi variabel di bawah.
2. Jalankan urutan setup di `docs/11_COMMANDS.md` (bagian Setup).
3. Seeder `DatabaseSeeder` mengisi data uji: tahun anggaran 2026 berstatus open, sumber dana `REGULER` dan `KINERJA`, contoh jenis belanja (barang dan jasa; modal peralatan dan mesin), jenis dokumen, matriks dokumen wajib contoh, dua sekolah contoh (SDN 1 Sukamaju negeri, SD Muhammadiyah 2 swasta), satu akun per role dengan sandi `password` dan `must_change_password=false`. Semua data seeder berlabel data uji dan tidak boleh dijalankan di produksi (`DatabaseSeeder` menolak bila `APP_ENV=production`).

## Variabel `.env`

| Variabel | Nilai lokal | Catatan |
|---|---|---|
| APP_NAME | SIMANABOS | |
| APP_ENV / APP_DEBUG | local / true | Produksi: production / false |
| APP_URL | http://localhost:8000 | |
| APP_TIMEZONE | Asia/Makassar | Dibaca `config/app.php` |
| APP_LOCALE / APP_FAKER_LOCALE | id / id_ID | |
| DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD | mysql, … | |
| SESSION_DRIVER / SESSION_LIFETIME | database / 120 | |
| SESSION_SECURE_COOKIE | false | Produksi: true |
| QUEUE_CONNECTION / CACHE_STORE | database / database | |
| FILESYSTEM_DISK | local | Disk PDF: `private` |
| SIMANABOS_MAX_UPLOAD_KB | 20480 | Batas PDF |
| SIMANABOS_INSTITUTION | Dinas Pendidikan dan Kebudayaan Kabupaten Hulu Sungai Selatan | Teks footer dan judul |
| SIMANABOS_INSTITUTION_SHORT | Disdikbud Kab. HSS | Subjudul sidebar |

Aset identitas: `public/img/logo-hss.png` (lambang Kabupaten HSS, 241×320 PNG) disediakan pemilik proyek.
