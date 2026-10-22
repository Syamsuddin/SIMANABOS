# 06 — BUSINESS_PROCESS

Peran: `docs/05_USER_ROLE.md`. Istilah dan rumus: `docs/04_DOMAIN_MODEL.md`. Tabel: `docs/07_DATA_MODEL.md`.

## A. Persiapan tahun anggaran (admin_dinas)
1. Buka tahun anggaran: `default_deadline_day` (default 10), tahap (`fiscal_year_stages`), tenggat 12 bulan dibuat dari default.
2. Salin jenis belanja dan aturan dokumen dari tahun sebelumnya (boleh diubah selama belum dipakai laporan).
3. Isi pagu per sekolah × sumber dana dengan `active_from_month`.
4. Sistem membuat laporan `draf` untuk bulan `active_from_month`..12 tiap pasangan sekolah × sumber dana (idempoten).
- Gagal: jenis belanja yang sudah dipakai laporan tidak dapat diubah atau dihapus, hanya dinonaktifkan.

## B. Anggaran sekolah (operator_sekolah)
1. Isi RKAS versi 1 per jenis belanja; unggah PDF RKAS disahkan (dokumen periodik `rkas`).
2. Tahun pertama: isi saldo awal Januari (dana dan pajak) → pemeriksa memverifikasi.
3. Perubahan RKAS: versi baru dengan `effective_date`, alasan, dan PDF perubahan. Versi lama tetap ada.
- Peringatan (tidak memblokir): total anggaran versi berlaku > pagu + saldo awal tahun.

## C. Laporan bulanan
1. Operator membuka bulan M; `opening_*` terisi otomatis dan baca-saja.
2. Isi penerimaan, rincian bruto dan pengembalian per jenis belanja, penyetoran, pajak, saldo bank dan tunai BKU, penyesuaian bila ada.
3. Ringkasan dan selisih dihitung server setiap simpan (pratinjau Alpine.js hanya bantuan).
4. Unggah PDF, tandai jenis dokumen.
5. (Opsional) kepala sekolah menyetujui; perubahan angka setelahnya membatalkan persetujuan.
6. Ajukan.

Validasi pengajuan (ditolak dengan pesan spesifik bila gagal):
- Laporan M−1 sumber dana sama berstatus ≥ `diajukan` (kecuali bulan pertama aktif).
- Semua field wajib terisi; dokumen wajib lengkap menurut `document_requirements`.
- Realisasi neto kumulatif setiap jenis belanja ≥ 0.
- Selisih ≠ 0 → `difference_note` wajib (pengajuan tetap diizinkan).
- Persetujuan kepala sekolah ada bila fitur aktif.

## D. Mesin status (rumah tunggal di kode: `App\Services\ReportStateMachine`)

| Dari | Ke | Aktor | Syarat |
|---|---|---|---|
| `draf` | `diajukan` | operator | Validasi pengajuan lulus |
| `diajukan` | `draf` | operator | `reviewer_id` masih null |
| `diajukan` | `dalam_pemeriksaan` | pemeriksa | Klaim atomik (lock_version); isi `reviewer_id` |
| `dalam_pemeriksaan` | `diajukan` | pemeriksa (yang mengklaim) | Lepas tanpa keputusan |
| `dalam_pemeriksaan` | `perlu_perbaikan` | pemeriksa | ≥ 1 catatan baru |
| `dalam_pemeriksaan` | `selesai_diperiksa` | pemeriksa | Tak ada catatan terbuka; saldo awal tahun pertama terverifikasi |
| `perlu_perbaikan` | `diajukan` | operator | Semua catatan terbuka ditanggapi; validasi lulus |
| `selesai_diperiksa` | `perlu_perbaikan` | admin | Buka kunci: alasan wajib; tahun belum ditutup |

Transisi lain ditolak. Setiap transisi menulis `status_histories` + log audit dalam satu transaksi DB.

Bendera bukan status: `terlambat` (lewat `due_date` dan masih `draf`), `needs_rereview`.

## E. Pemeriksaan dan perbaikan
1. Pemeriksa mengambil laporan dari antrean, membandingkan rekonsiliasi saldo dan rincian dengan PDF.
2. Catatan menunjuk `field`, `document_type`, atau `general`. Minta perbaikan → operator menanggapi tiap catatan → ajukan ulang.
3. Pemeriksa menandai catatan selesai lalu menyatakan `selesai_diperiksa`. Laporan terkunci.

## F. Koreksi setelah terkunci
- Jalur normal: penyesuaian di bulan berjalan dengan rujukan bulan asal.
- Jalur pengecualian: buka kunci (D). Setelah diajukan ulang, sistem menghitung ulang `opening_*` bulan-bulan sesudahnya; laporan sesudahnya yang sudah selesai **tidak** berubah status, tetapi diberi `needs_rereview=true`.

## G. Tutup tahun (admin_dinas)
1. Prasyarat: semua laporan Desember `selesai_diperiksa`; bila belum, admin wajib mengetik ulang tahun anggaran sebagai konfirmasi.
2. `fiscal_years.status=closed`; saldo akhir Desember (dana dan pajak) per sekolah × sumber dana ditulis ke `opening_balances` tahun berikut dengan `origin=carried_over`.
3. Setelah ditutup: buka kunci laporan tahun itu ditolak; koreksi lewat penyesuaian Januari tahun berikut.

## H. Tenggat dan pengingat (penjadwal harian)
- H-3 tenggat: notifikasi ke operator laporan yang masih `draf`.
- H+1: tandai `terlambat`, notifikasi operator dan kepala sekolah.
