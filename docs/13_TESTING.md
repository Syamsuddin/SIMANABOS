# 13 — TESTING

Runner: Pest. Perintah: `docs/11_COMMANDS.md`. Basis data tes: MySQL terpisah (`DB_DATABASE=simanabos_test`) dengan `RefreshDatabase`; SQLite tidak dipakai karena perilaku DECIMAL dan JSON berbeda.

## Jenis tes dan target

| Jenis | Lokasi | Target |
|---|---|---|
| Unit domain | `tests/Unit/Services/` | 100% cabang `ReportBalanceCalculator`, `ReportStateMachine`, `Money`, aturan agregasi |
| Feature | `tests/Feature/` | Setiap Action dan route; setiap Policy diuji untuk peran berhak dan tak berhak |
| Cakupan total | — | ≥ 80% baris (`--min=80`) |

## Wajib dites (alur kritikal)

| Tes | Isi |
|---|---|
| `GoldenFebruaryTest` | Golden case `docs/04_DOMAIN_MODEL.md` → hasil persis sama |
| Kalkulator tepi | Laporan nihil; neto bulanan negatif diterima; neto kumulatif negatif ditolak; penyesuaian bertanda tiga jenis; sumber dana aktif mulai Juli |
| Saldo berantai | Saldo awal Maret = saldo akhir Februari; ubah Februari (buka kunci) → Maret–Desember terhitung ulang, laporan selesai mendapat `needs_rereview` |
| Mesin status | Semua transisi sah di `docs/06_BUSINESS_PROCESS.md` lolos; semua kombinasi lain melempar `InvalidTransitionException` |
| Klaim bersamaan | Dua pemeriksa mengambil laporan sama → satu berhasil, satu gagal karena `lock_version` |
| Validasi pengajuan | Bulan sebelumnya draf; dokumen wajib kurang; selisih tanpa catatan; persetujuan kepsek aktif tanpa persetujuan |
| Isolasi sekolah | Operator sekolah A → 404 untuk laporan, PDF, rekap, dokumen periodik sekolah B |
| PDF | Bukan PDF (ekstensi diganti) ditolak; >20 MB ditolak; ganti → versi 2, versi 1 tetap terunduh; hapus setelah diajukan ditolak |
| Agregasi | Saldo triwulan = posisi September, bukan jumlah Jul–Sep; cakupan hanya ≥ diajukan |
| Tutup tahun | Saldo Januari tahun berikut = akhir Desember (dana dan pajak); buka kunci tahun tertutup ditolak |
| Audit | Ubah angka tercatat dengan nilai lama dan baru; transisi menulis `status_histories` |
| UI smoke | Setiap halaman di inventaris `docs/26_UI_CONVENTIONS.md` merender 200 untuk peran berhak, termasuk state kosong |

## Aturan
- Uang dalam tes ditulis sebagai string (`'26512500.00'`), dibandingkan dengan `BigDecimal::isEqualTo`.
- Factory memakai NPSN 8 digit dan nama contoh; tidak memakai nama sekolah nyata.
- Tes tidak dihapus atau dilemahkan agar hijau (`docs/20_GUARDRAILS.md`).
