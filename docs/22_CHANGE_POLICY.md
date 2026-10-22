# 22 — CHANGE_POLICY

Prinsip: friksi sebanding irreversibilitas.

## Git
- Branch per fitur atau perbaikan: `feat/F-05-laporan-bulanan`, `fix/selisih-pajak`.
- Dilarang commit langsung ke `main`. Merge hanya bila `php artisan test` dan `./vendor/bin/pint --test` hijau.
- Merge ke `main` oleh pemilik proyek (Syamsuddin) `[ASUMSI]`.
- Commit pesan singkat Bahasa Indonesia, diawali tipe: `feat:`, `fix:`, `docs:`, `test:`, `refactor:`.

## Operasi irreversibel dan gerbang manusia

| Operasi | Gerbang |
|---|---|
| Migrasi yang menghapus/mengganti kolom atau tabel | Agen berhenti dan minta konfirmasi; backup sebelum di produksi |
| Perubahan rumus saldo, golden case, atau aturan agregasi | Konfirmasi pemilik; perbarui `docs/04_DOMAIN_MODEL.md` dulu |
| Perubahan transisi status | Konfirmasi pemilik; perbarui `docs/06_BUSINESS_PROCESS.md` dulu |
| Tutup tahun (fitur) | Dialog konfirmasi dengan ringkasan; bila ada Desember belum selesai, admin mengetik ulang tahun |
| Buka kunci laporan (fitur) | Dialog konfirmasi + alasan wajib; tercatat audit |
| Nonaktifkan sekolah atau sumber dana (fitur) | Dialog konfirmasi; data tidak dihapus |
| Menambah dependensi | Alasan tertulis + persetujuan |
| Rilis ke produksi | Ikuti `docs/25_RELEASE_CHECKLIST.md` berurutan |
| Perintah data di produksi (`tinker`, SQL manual) | Dilarang tanpa persetujuan tertulis pemilik |

## Rollback
- Kode: deploy ulang tag rilis sebelumnya.
- Skema: setiap migrasi punya `down()` yang benar; migrasi destruktif dianggap tidak bisa di-rollback → pulihkan dari backup.
- Data: pulihkan dari backup harian; berkas PDF dipulihkan dari backup direktori `storage/app/private`.
