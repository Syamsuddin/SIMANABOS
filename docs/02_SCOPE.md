# 02 — SCOPE

## In-scope
- Fitur MVP F-01 s.d. F-15 di `docs/01_PRD.md`.
- Satu kabupaten (Disdikbud Kab. Hulu Sungai Selatan), jenjang SD dan SMP, negeri dan swasta.
- Input angka **agregat bulanan** per sekolah × tahun × bulan × sumber dana × jenis belanja.
- Arsip PDF hasil ARKAS, berversi.
- Notifikasi dalam aplikasi saja.
- Ekspor XLSX.

## Out-of-scope (JANGAN dibangun tanpa perubahan scope tertulis)
1. Input transaksi satu per satu dan unggah kuitansi per transaksi.
2. OCR atau ekstraksi angka otomatis dari PDF.
3. Integrasi otomatis dengan ARKAS, Dapodik, bank, atau SIPD (termasuk impor file ekspor ARKAS).
4. Pembuatan dokumen resmi: BKU, SPTJM, laporan realisasi. Aplikasi hanya menerima PDF jadi.
5. Jenjang selain SD dan SMP.
6. Multi-kabupaten atau multi-tenant.
7. Aplikasi mobile native atau PWA offline.
8. Tanda tangan elektronik.
9. Penilaian keabsahan transaksi. Status selesai diperiksa hanya pemeriksaan administratif.
10. Ekspor PDF rekap, notifikasi surel/WA, tema gelap (ditunda, lihat L-01 s.d. L-04).

## Asumsi dan batasan
- Laporan resmi sekolah tetap dibuat di ARKAS; angka di SIMANABOS disalin operator dari laporan tersebut.
- Daftar jenis belanja dan dokumen wajib ditetapkan dinas per tahun; aplikasi tidak menebak isinya.
- Butir `[TERBUKA]` dan `[ASUMSI]` tercatat di `docs/_MANIFEST.json` (`open_questions`, `assumptions`).
