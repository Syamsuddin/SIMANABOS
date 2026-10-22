# 00 — EXECUTIVE_SUMMARY

## Masalah
Disdikbud Kabupaten Hulu Sungai Selatan belum memiliki satu tempat untuk memantau penerimaan dan penggunaan dana BOS seluruh SD dan SMP setiap bulan. Laporan sekolah tersebar sebagai PDF, angka sulit direkap, dan tindak lanjut perbaikan tidak tercatat.

## Solusi
SIMANABOS (Sistem Informasi Manajemen BOS) adalah aplikasi web tingkat kabupaten. Operator sekolah mengisi angka agregat bulanan per sumber dana dan jenis belanja, lalu mengunggah PDF hasil ARKAS. Petugas dinas memeriksa kesesuaian angka dengan PDF, meminta perbaikan, dan menyatakan laporan selesai diperiksa. Dasbor dan daftar perhatian menunjukkan sekolah yang terlambat, dokumennya kurang, atau memiliki selisih saldo.

ARKAS tetap menjadi sistem pencatatan dan pelaporan resmi sekolah. SIMANABOS hanya mengawasi dan mengarsipkan.

## Nilai dan metrik sukses
- Metrik utama: ≥ 90% laporan bulanan berstatus selesai diperiksa paling lambat 30 hari setelah tenggat, pada tahun anggaran pertama penuh.
- Pendukung: persentase pengajuan tepat waktu; median hari dari diajukan ke selesai; jumlah selisih saldo yang belum terjelaskan.

## Pengguna sasaran
Admin dinas, petugas pemeriksa dinas, pimpinan dinas, operator BOS sekolah, dan kepala sekolah (peran lengkap: `docs/05_USER_ROLE.md`).

## Status dan cakupan
Greenfield. Satu instalasi untuk satu kabupaten (Disdikbud Kab. HSS). Aplikasi web responsif. Fitur MVP di `docs/01_PRD.md`, batas di `docs/02_SCOPE.md`, urutan fase di `docs/03_ROADMAP.md`.
