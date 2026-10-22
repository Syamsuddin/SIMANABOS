# Changelog

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/) dan versi mengikuti [Semantic Versioning](https://semver.org/lang/id/). Selama versi masih `0.x`, antarmuka dan skema dapat berubah. Versi `1.0.0` adalah rilis MVP penuh (seluruh fase `docs/03_ROADMAP.md` selesai).

## [Unreleased]

### Ditambahkan
- **F-08 Penguncian periode**: admin dinas membuka kunci laporan selesai dari layar pemeriksaan dengan alasan wajib (ditolak bila tahun ditutup); `needs_rereview` pada bulan sesudahnya yang selesai.
- **F-12 Tenggat dan pengingat**: penjadwal harian `simanabos:process-deadlines` (00.30 WITA) mengirim pengingat H-N ke operator untuk laporan draf dan menandai `is_late` + notifikasi ke operator dan kepala sekolah pada H+1; halaman dan lonceng notifikasi; tanggal tenggat bawaan dan jumlah hari pengingat dapat diatur admin di Pengaturan Aplikasi.
- **F-10 Dasbor kabupaten** (`/dasbor`): kartu penerimaan, realisasi neto, saldo dana dan pajak (posisi bulan terakhir periode), grafik per bulan dengan tabel alternatif, status laporan, ringkasan per kecamatan; filter tahun, rentang bulan, jenjang, kecamatan, sumber dana, status; label "X dari Y laporan". Agregasi di `ReportAggregator`.
- **F-11 Daftar perhatian** (`/perhatian`): delapan pemicu (belum melapor, dokumen kurang, selisih saldo, perbaikan > 14 hari, antrean > 14 hari, tinjau ulang, anggaran > dana, saldo awal belum diverifikasi) dengan badge pemicu dan filter pemicu, jenjang, kecamatan. Logika di `AttentionListBuilder`.

### Direncanakan
- Fase 6–8: dokumen periodik, rekap dan ekspor XLSX, tutup tahun, pengerasan dan rilis.

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

[Unreleased]: https://github.com/Syamsuddin/SIMANABOS/compare/v0.4.0...HEAD
[0.4.0]: https://github.com/Syamsuddin/SIMANABOS/releases/tag/v0.4.0
