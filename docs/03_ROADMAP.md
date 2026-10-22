# 03 — ROADMAP

Setiap fase adalah vertical slice (migrasi → model → action/service → Blade → tes) yang bisa didemokan. Fase berikutnya dimulai setelah fase sebelumnya memenuhi `docs/24_DEFINITION_OF_DONE.md`.

| Fase | Tujuan yang bisa didemokan | Fitur | Prasyarat `[TERBUKA]` |
|---|---|---|---|
| 0 Fondasi | Admin login, membuat sekolah, kecamatan, sumber dana, tahun anggaran, dan akun operator; AppShell Pamong tampil dengan identitas Disdikbud HSS | F-01, F-02 (minus jenis belanja/dokumen), F-03, F-14 dasar | — |
| 1 Anggaran | Admin mengisi pagu; sistem membuat 12 laporan draf; operator mengisi RKAS berversi dan saldo awal; pemeriksa memverifikasi saldo awal | F-02 jenis belanja, F-04 | Daftar jenis belanja resmi |
| 2 Laporan bulanan | Operator mengisi Januari–Februari; golden test Februari lulus; saldo berantai antarbulan | F-05 | — |
| 3 Dokumen | Operator mengunggah PDF gabungan, menandai jenis, mengganti versi; kelengkapan terhitung | F-06, F-02 dokumen wajib | Matriks dokumen wajib |
| 4 Pemeriksaan | Siklus draf → diajukan → dalam pemeriksaan → perlu perbaikan → diajukan → selesai; laporan terkunci; buka kunci menandai tinjau ulang | F-07, F-08 | Persetujuan kepsek; pembagian pemeriksa |
| 5 Pengawasan | Dasbor kabupaten dan daftar perhatian dengan filter; tenggat dan notifikasi | F-10, F-11, F-12 | Tanggal tenggat resmi |
| 6 Periodik | Laporan Tahap I dibandingkan dengan Σ bulanan pada rentang tahap | F-09 | Rentang bulan tahap |
| 7 Rekap dan tutup tahun | Rekap triwulan/semester/tahun, ekspor XLSX, tutup tahun → saldo Januari berikut | F-13, F-15 | — |
| 8 Pengerasan dan rilis | Audit lengkap, cadangan, smoke test produksi | F-14 penuh, rilis | Server dan domain |
