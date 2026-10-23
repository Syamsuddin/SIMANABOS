# Changelog

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/) dan versi mengikuti [Semantic Versioning](https://semver.org/lang/id/). Selama versi masih `0.x`, antarmuka dan skema dapat berubah. Versi `1.0.0` adalah rilis MVP penuh (seluruh fase `docs/03_ROADMAP.md` selesai).

## [Unreleased]

### Ditambahkan
- **Data uji lengkap (`DemoSeeder`)** lewat `php artisan migrate:fresh --seed`: enam sekolah fiktif dengan skenario laporan 2026 (selesai, dalam pemeriksaan, perlu perbaikan dengan catatan, buka kunci + tinjau ulang, selisih saldo, antrean lama, terlambat, saldo awal belum diverifikasi, RKAS melebihi pagu, dokumen kurang), RKAS berversi, PDF contoh berversi, laporan periodik, notifikasi, dan jejak audit; 14 akun aktif bersandi `password`. Daftar di `USERS.md`.

### Diubah
- `AdminSeeder` dihapus; akun admin uji kini `admin` / `password` dari `DemoSeeder` (sesuai `docs/10`). `DatabaseSeeder` menolak berjalan di produksi.

## [1.0.0-rc.1] - 2026-10-23

Kandidat rilis MVP. Seluruh fitur F-01–F-15 selesai dan `docs/23_ACCEPTANCE_CRITERIA.md` tidak lagi memuat fitur aktif; 308 tes hijau. Gladi `docs/25_RELEASE_CHECKLIST.md` lulus di salinan lokal mode produksi (migrasi, cadangan, down/up, optimize, smoke HTTP 52/52 untuk kelima peran, uji pemulihan cadangan). Versi `1.0.0` diterbitkan setelah checklist dijalankan di server produksi.

### Ditambahkan
- **F-08 Penguncian periode**: admin dinas membuka kunci laporan selesai dari layar pemeriksaan dengan alasan wajib (ditolak bila tahun ditutup); `needs_rereview` pada bulan sesudahnya yang selesai.
- **F-09 Dokumen periodik**: laporan Tahap, Tahunan, dan Berita Acara per sumber dana dengan unggah PDF; angka Tahap/Tahunan dibandingkan dengan Σ realisasi bulanan pada rentang tahap (dibaca dari `fiscal_year_stages`). Angka periodik tidak mengubah laporan bulanan atau dasbor.
- **F-10 Dasbor kabupaten** (`/dasbor`): kartu penerimaan, realisasi neto, saldo dana dan pajak (posisi bulan terakhir periode), grafik per bulan dengan tabel alternatif, status laporan, ringkasan per kecamatan; filter tahun, rentang bulan, jenjang, kecamatan, sumber dana, status. Agregasi di `ReportAggregator`.
- **F-11 Daftar perhatian** (`/perhatian`): delapan pemicu (belum melapor, dokumen kurang, selisih saldo, perbaikan > 14 hari, antrean > 14 hari, tinjau ulang, anggaran > dana, saldo awal belum diverifikasi) dengan filter pemicu, jenjang, kecamatan.
- **F-12 Tenggat dan pengingat**: penjadwal harian `simanabos:process-deadlines` (00.30 WITA) mengirim pengingat H-N untuk laporan draf dan menandai `is_late` + notifikasi pada H+1; halaman dan lonceng notifikasi; tanggal tenggat bawaan dan hari pengingat diatur admin.
- **F-13 Rekap dan ekspor** (`/rekap`): rekap bulan, triwulan, semester, dan tahun per sekolah (penerimaan dan realisasi dijumlahkan, saldo memakai posisi akhir periode); ekspor XLSX dengan kolom angka numerik dan tanpa NIP.
- **F-14 Jejak audit penuh**: baris realisasi, penyesuaian, dan versi dokumen ikut tercatat (nilai lama dan baru, pelaku, waktu); kartu "Jejak audit" di detail laporan untuk admin, pemeriksa, dan sekolah sendiri (pimpinan tidak).
- **F-15 Tutup tahun anggaran**: saldo akhir Desember menjadi saldo awal tahun berikut (`origin=carried_over`) per sekolah × sumber dana; bila ada Desember belum selesai, admin wajib mengetik ulang tahun anggaran.
- **Perintah operasional**: `simanabos:create-admin` (admin pertama, wajib ganti sandi), `simanabos:recompute {npsn} {year}`, dan `simanabos:backup` (dump basis data + arsip `storage/app/private`, terjadwal 01.30 WITA, retensi 30 hari).
- **Observabilitas**: header `X-Request-Id` dan konteks log `request_id`/`user_id`; kanal log `errors` (level ≥ error) di `storage/logs/errors-*.log`.
- **Smoke test**: matriks akses semua halaman GET × lima peran sesuai `docs/05` (sekolah lain = 404, tamu dialihkan); tes gagal bila ada route GET baru yang belum dipetakan.

### Diubah
- Simpan angka laporan memperbarui baris realisasi per model (bukan hapus massal lalu buat ulang); isi akhir identik, tetapi ubah dan hapus baris kini tercatat di audit.
- Retensi log harian bawaan 30 hari (sebelumnya 14); `.env.example` memakai `LOG_STACK=daily,errors`.
- `docs/11_COMMANDS.md`: `simanabos:flag-late` dan `simanabos:remind-deadlines` diganti `simanabos:process-deadlines` sesuai implementasi; ditambah `simanabos:backup`.

### Keamanan
- Header `X-Frame-Options: DENY`, `Referrer-Policy: same-origin`, `X-Content-Type-Options: nosniff` di setiap respons; `Strict-Transport-Security` saat HTTPS.
- Berkas cadangan berizin `0640`; sandi basis data dikirim ke `mysqldump` lewat `MYSQL_PWD`, bukan argumen baris perintah.

### Dependensi
- `openspout/openspout` ^5.12 untuk ekspor XLSX (terdaftar di `docs/09_STACK.md`).

### Catatan pemutakhiran dari 0.4.0
- Dua migrasi baru: `create_notifications_table`, `create_periodic_reports_table`. Jalankan `php artisan simanabos:backup` lalu `php artisan migrate --force`.
- Jadwalkan `php artisan schedule:run` tiap menit (cron) agar pengingat tenggat dan cadangan harian berjalan.

### Diketahui / belum ada
- Belum dideploy ke produksi: server, domain, Nginx, dan HTTPS masih `[TERBUKA]`. Produksi wajib `APP_DEBUG=false` dan `SESSION_SECURE_COOKIE=true`.
- Cadangan tersimpan di server yang sama (`storage/app/backups` atau `SIMANABOS_BACKUP_PATH`); salinan ke luar server belum diatur.
- `docs/21` menyebut middleware `role:` per route, sedangkan otorisasi diterapkan lewat Policy/Gate per aksi dan dijaga tes smoke; dokumen dan kode perlu diselaraskan.
- `activitylog:clean` tersedia dari paket dan tidak dijadwalkan; jangan dijadwalkan karena audit append-only (`docs/20`).
- Input penyesuaian lewat antarmuka dan pratinjau saldo Alpine.js belum tersedia.

## [0.4.0] - 2026-10-23

Rilis pra-MVP pertama: Fase 0–3 selesai dan Fase 4 baru F-07. Ini adalah commit pertama kode aplikasi, jadi entri di bawah merangkum seluruh isi rilis.

### Ditambahkan
- **F-01 Autentikasi dan akun**: login dengan pembatasan percobaan, lima role (`admin_dinas`, `pemeriksa_dinas`, `pimpinan_dinas`, `operator_sekolah`, `kepala_sekolah`), wajib ganti sandi pertama, akun nonaktif ditolak, pengelolaan akun oleh admin.
- **F-02 Data referensi**: kecamatan, sumber dana, tahun anggaran beserta tahap dan tenggat bulanan, jenis belanja per tahun (salin dari tahun lalu), jenis dokumen dan aturan dokumen wajib.
- **F-03 Sekolah dan pengelola**: profil sekolah, pengelola berwaktu (kepala sekolah, bendahara, operator) dengan usulan dan pengesahan admin. Role sekolah dibatasi ke sekolahnya sendiri (sekolah lain = 404).
- **F-04 Anggaran tahunan**: pagu alokasi (membuat 12 laporan draf), RKAS berversi, saldo awal tahun pertama dan verifikasinya oleh pemeriksa.
- **F-05 Laporan bulanan**: rumus saldo dua kantong (dana dan pajak) di `ReportBalanceCalculator`, saldo berantai antarbulan, penyesuaian, penguncian optimistis (`lock_version`), perhitungan sisa anggaran.
- **F-06 Dokumen PDF**: unggah PDF ke disk privat, versi berurutan, penandaan jenis dokumen, kelengkapan dokumen wajib, unduh lewat controller dengan otorisasi.
- **F-07 Pengajuan dan pemeriksaan**:
  - `ReportStateMachine` sebagai satu-satunya jalur ubah status, dengan klaim atomik pemeriksa dan riwayat status (`status_histories`).
  - Catatan pemeriksa per butir beserta tanggapan sekolah.
  - Antrean dan layar pemeriksaan untuk pemeriksa; tombol ajukan, tarik kembali, dan tanggapi catatan untuk sekolah.
  - Persetujuan kepala sekolah opsional; perubahan angka membatalkannya.
  - Snapshot identitas sekolah saat diajukan.
- **Pengaturan aplikasi**: halaman admin untuk mewajibkan persetujuan kepala sekolah (nonaktif secara bawaan), perubahannya tercatat di audit.
- **F-14 Jejak audit dasar**: perubahan angka dan status tercatat dengan nilai lama dan baru, tanpa rute ubah atau hapus.
- Antarmuka Blade + Tailwind v4 + Alpine.js dengan komponen `x-pm.*` bertema Pamong.
- Suite tes Pest (221 tes) dan dokumen blueprint di `docs/`.

### Keamanan
- PDF disimpan di disk privat, tidak pernah di `public/`.
- Setiap aksi melewati Policy; role sekolah dibatasi `SchoolScope`.
- Seeder akun contoh hanya berjalan di lingkungan `local` dan `testing`; akun contoh tidak pernah dibuat di produksi.

### Diketahui / belum ada
- Perintah `simanabos:create-admin` (pembuat admin produksi, `docs/11_COMMANDS.md`) belum diimplementasikan; wajib ada sebelum rilis produksi.
- Buka kunci laporan oleh admin (F-08) belum memiliki antarmuka; transisinya sudah ada di mesin status.
- Dokumen periodik (RKAS/tahap/tahunan), dasbor, tenggat dan notifikasi, rekap dan ekspor, serta tutup tahun belum dibangun.

[Unreleased]: https://github.com/Syamsuddin/SIMANABOS/compare/v1.0.0-rc.1...HEAD
[1.0.0-rc.1]: https://github.com/Syamsuddin/SIMANABOS/compare/v0.4.0...v1.0.0-rc.1
[0.4.0]: https://github.com/Syamsuddin/SIMANABOS/releases/tag/v0.4.0
