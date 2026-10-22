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
- Given Tahap I = Jan–Jun dan `reported_total_realization` diisi, Then selisih terhadap Σ realisasi neto Jan–Jun ditampilkan.
- Angka periodik tidak mengubah realisasi bulanan atau dasbor.
- Verifikasi: `php artisan test --filter=Periodic` → hijau.

## F-10 Dasbor kabupaten
- Saldo kabupaten September = Σ `computed_ending_*` laporan September, bukan jumlah antarbulan.
- Filter jenjang, status, kecamatan, sumber dana, bulan/periode mengubah semua kartu dan grafik secara konsisten.
- Label "X dari Y laporan" tampil.
- Verifikasi: `php artisan test --filter=Dashboard` → hijau; buka `/dasbor` sebagai pimpinan → 200.

## F-11 Daftar perhatian
- Setiap pemicu (belum melapor, dokumen kurang, selisih, perbaikan > 14 hari, antrean > 14 hari, tinjau ulang, anggaran > dana, saldo awal belum diverifikasi) menghasilkan baris dengan badge pemicu.
- Verifikasi: `php artisan test --filter=Attention` → hijau.

## F-12 Tenggat dan pengingat
- Given hari ini (WITA) = due_date − 3 dan laporan draf, Then notifikasi terkirim sekali.
- Given hari ini = due_date + 1 dan masih draf, Then `is_late=true` dan notifikasi ke operator dan kepsek.
- Verifikasi: `php artisan test --filter=Deadline` → hijau (pakai `Carbon::setTestNow`).

## F-13 Rekap dan ekspor
- Rekap triwulan III: penerimaan = Σ Jul–Sep; saldo = posisi September.
- Ekspor XLSX terunduh, kolom angka bertipe numerik, tanpa kolom NIP.
- Verifikasi: `php artisan test --filter=Export` → hijau.

## F-14 Jejak audit
- Ubah `receipt_bos` tercatat dengan nilai lama dan baru, pelaku, waktu.
- Tidak ada route ubah/hapus audit (`php artisan route:list | grep -i activity` kosong).
- Verifikasi: `php artisan test --filter=Audit` → hijau.

## F-15 Tutup tahun
- Given semua Desember selesai, When tutup, Then `opening_balances` tahun berikut = saldo akhir Desember per sekolah × sumber dana dengan `origin=carried_over`.
- Given ada Desember belum selesai, Then tutup hanya bisa setelah mengetik ulang tahun anggaran.
- Verifikasi: `php artisan test --filter=CloseFiscalYear` → hijau.
