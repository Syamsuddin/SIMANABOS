# 23 — ACCEPTANCE_CRITERIA

Fitur: `docs/01_PRD.md`. Aturan domain: `docs/04_DOMAIN_MODEL.md`, `docs/06_BUSINESS_PROCESS.md`. Setiap fitur juga wajib memenuhi `docs/24_DEFINITION_OF_DONE.md`. Fitur yang sudah diterima diarsipkan ke `docs/_archive/23-{fitur}.md`.

Kriteria antarmuka umum untuk setiap fitur ber-UI (pola di `docs/26_UI_CONVENTIONS.md`): empat state tampil; pesan validasi muncul di field terkait; layak di 360 px (halaman sekolah) atau 768 px (halaman dinas); aksi destruktif meminta konfirmasi modal.

## F-02 Data referensi
- Given jenis belanja dipakai laporan, When admin mengubah `code` atau menghapus, Then ditolak; menonaktifkan diizinkan.
- CRUD jenis belanja per tahun (kode tak berubah, salin dari tahun lalu, terkunci bila tahun ditutup) selesai Fase 1 — `tests/Feature/Reference/ExpenseTypeTest.php`; penolakan ubah `code`/hapus saat dipakai laporan *menunggu F-05* (laporan belum ada).
- Jenis dokumen dan aturan dokumen wajib diarsipkan di `docs/_archive/23-F-06.md`; bagian kecamatan, sumber dana, tahun anggaran, tahap, tenggat diarsipkan di `docs/_archive/23-F-02-dasar.md`.
- Verifikasi: `php artisan test --filter=Reference` → hijau.

## F-03 Sekolah dan pengelola
- Bagian sekolah, pengelola berwaktu, dan akun diarsipkan di `docs/_archive/23-F-03.md`.
- Given laporan diajukan sebelum pergantian, Then `identity_snapshot` tetap menampilkan bendahara lama. *(menunggu F-05; uji saat laporan ada)*

## F-04 Anggaran tahunan
- Diarsipkan di `docs/_archive/23-F-04.md`. Sisa: guard "Januari tidak dapat dinyatakan selesai bila saldo awal manual belum diverifikasi" sudah tersedia sebagai `OpeningBalance::blocksJanuaryCompletion()`; penegakannya sebagai guard `selesai_diperiksa` sudah ada di `ReportStateMachine` (F-07).

## F-05 Laporan bulanan
- Diarsipkan di `docs/_archive/23-F-05.md`. Sisa: butir "ditolak saat ajukan" diwujudkan sebagai `App\Services\ReportSubmissionValidator` (diuji langsung); dipanggil `ReportStateMachine` saat `draf → diajukan` (F-07). Kelengkapan dokumen sudah tercakup (F-06). Input penyesuaian (UI) dan pratinjau Alpine ditunda.

## F-06 Dokumen PDF
- Diarsipkan di `docs/_archive/23-F-06.md`. Sisa: `ReportSubmissionValidator` kini dipanggil saat `draf → diajukan` (F-07). Dokumen untuk RKAS/periodik (`documentable` selain `MonthlyReport`) menunggu F-09.

## F-07 Pengajuan dan pemeriksaan
- Diarsipkan di `docs/_archive/23-F-07.md`. Sisa: tombol/halaman buka kunci admin (F-08).

## F-08 Penguncian periode
- Diarsipkan di `docs/_archive/23-F-08.md`.

## F-09 Dokumen periodik
- Diarsipkan di `docs/_archive/23-F-09.md`.

## F-10 Dasbor kabupaten
- Diarsipkan di `docs/_archive/23-F-10.md`.

## F-11 Daftar perhatian
- Diarsipkan di `docs/_archive/23-F-11.md`.

## F-12 Tenggat dan pengingat
- Diarsipkan di `docs/_archive/23-F-12.md`.

## F-13 Rekap dan ekspor
- Diarsipkan di `docs/_archive/23-F-13.md`.

## F-14 Jejak audit
- Diarsipkan di `docs/_archive/23-F-14.md`.
