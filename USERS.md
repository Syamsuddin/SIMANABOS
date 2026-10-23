# Akun Seeder

Data uji lokal dari `database/seeders/DemoSeeder.php`. Muat dengan `php artisan migrate:fresh --seed` (menghapus seluruh isi basis data lokal). Seeder menolak berjalan bila `APP_ENV=production`.

Semua akun memakai sandi `password`. Kecuali disebut lain, akun aktif dan tidak wajib ganti sandi.

| No | Role | Username | Password |
| --- | --- | --- | --- |
| 1 | admin_dinas | admin | password |
| 2 | pemeriksa_dinas | pemeriksa | password |
| 3 | pemeriksa_dinas | pemeriksa2 | password |
| 4 | pimpinan_dinas | pimpinan | password |
| 5 | operator_sekolah | operator.sukamaju | password |
| 6 | kepala_sekolah | kepsek.sukamaju | password |
| 7 | operator_sekolah | operator.muhammadiyah | password |
| 8 | kepala_sekolah | kepsek.muhammadiyah | password |
| 9 | operator_sekolah | operator.smpn3 | password |
| 10 | kepala_sekolah | kepsek.smpn3 | password |
| 11 | operator_sekolah | operator.mekarsari | password |
| 12 | kepala_sekolah | kepsek.mekarsari | password |
| 13 | operator_sekolah | operator.bina | password |
| 14 | kepala_sekolah | kepsek.bina | password |
| 15 | pemeriksa_dinas | pemeriksa.baru | password (wajib ganti sandi saat login) |
| 16 | operator_sekolah | nonaktif | password (akun nonaktif, login ditolak) |

## Skenario per sekolah (tahun anggaran 2026, "hari ini" 23 Oktober 2026)

| Sekolah | Akun | Yang bisa dicoba |
| --- | --- | --- |
| SD Negeri 1 Sukamaju (SD negeri) | operator.sukamaju, kepsek.sukamaju | BOS Reguler: Jan–Jul selesai; Maret dibuka kunci admin dan dikoreksi sehingga Apr–Jul "perlu tinjau ulang"; Agustus dalam pemeriksaan (ada catatan); September diajukan. RKAS dua versi, PDF Februari dua versi, penyesuaian April, laporan Tahap I (ada selisih) dan Berita Acara. BOS Kinerja: Jan–Jun selesai, Juli lama di antrean dan kekurangan dokumen wajib baru, Agustus–September terlambat. |
| SD Muhammadiyah 2 Contoh (SD swasta) | operator.muhammadiyah, kepsek.muhammadiyah | Jan–Jul selesai; Agustus perlu perbaikan sejak 11 September (dua catatan, satu sudah ditanggapi), coba tanggapi lalu ajukan ulang; September draf terisi tapi belum diajukan (terlambat). |
| SMP Negeri 3 Contoh (SMP negeri) | operator.smpn3, kepsek.smpn3 | Jan–Jun selesai (Juni dengan selisih saldo dan catatan); Jul–Sep diajukan dan menunggu diambil pemeriksa; RKAS melebihi pagu; laporan Tahap I. |
| SD Negeri 5 Mekarsari (SD negeri) | operator.mekarsari, kepsek.mekarsari | Saldo awal belum diverifikasi (verifikasi lewat akun pemeriksa); Januari dalam pemeriksaan oleh pemeriksa2 dan tidak bisa diselesaikan sebelum saldo awal diverifikasi; Feb–Sep terlambat. |
| SMP Swasta Bina Contoh (SMP swasta) | operator.bina, kepsek.bina | Sekolah baru: hanya pagu; isi RKAS, saldo awal, dan laporan dari nol; usulan pergantian bendahara menunggu pengesahan admin. |
| SD Negeri 7 Contoh (digabung) | — | Sekolah nonaktif tanpa pagu. |

## Alur dinas yang bisa dicoba

- **pemeriksa**: ambil laporan SMP Negeri 3 di antrean, tambah catatan, minta perbaikan atau nyatakan selesai; verifikasi saldo awal Mekarsari.
- **pemeriksa2**: lanjutkan pemeriksaan Januari Mekarsari setelah saldo awal diverifikasi.
- **admin**: buka kunci laporan selesai, sahkan usulan bendahara Bina, atur dokumen wajib dan tenggat, buka tahun 2027 lalu tutup tahun 2026 (perlu mengetik ulang tahun karena Desember belum selesai).
- **pimpinan**: dasbor, daftar perhatian (kedelapan pemicu terisi), rekap dan ekspor XLSX.

Catatan: berkas PDF contoh disimpan di `storage/app/private/documents/2026/…`; menjalankan ulang seeder menambah berkas baru tanpa menghapus yang lama.
