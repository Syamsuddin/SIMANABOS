# 16 — DEBUGGING_GUIDE

Log: `docs/15_OBSERVABILITY.md`. Perintah: `docs/11_COMMANDS.md`.

## Gejala → diagnosa

| Gejala | Langkah |
|---|---|
| Saldo di dasbor beda dengan halaman laporan | Cache `computed_*` basi. Jalankan `simanabos:recompute {npsn} {year}`; bila terulang, cari jalur penyimpanan yang tidak memanggil `ReportBalanceCalculator` |
| Saldo awal bulan tidak sama dengan saldo akhir bulan sebelumnya | Cek sumber dana sama; cek `active_from_month`; cek apakah buka kunci terjadi tanpa hitung ulang berantai |
| Selisih muncul tiap bulan pemungutan pajak | Operator memasukkan pajak ke realisasi atau tidak mengisi `tax_withheld` — periksa kantong pajak |
| Tombol Ajukan nonaktif tanpa alasan jelas | Lihat bag error `domain`; cek status M−1 dan `DocumentCompletenessChecker` |
| Pemeriksa gagal mengambil laporan | Konflik `lock_version` atau status sudah berubah; muat ulang |
| PDF tidak bisa diunduh | Policy menolak (404 untuk sekolah lain), atau `storage_path` hilang di disk `private` |
| Laporan tidak muncul di antrean | Status bukan `diajukan`; atau scope pemeriksa (lihat `[TERBUKA]` pembagian wilayah) |
| Tenggat tampak bergeser sehari | Zona waktu: pastikan `APP_TIMEZONE=Asia/Makassar` dan pembanding `due_date` memakai tanggal lokal |

## Jebakan proyek (dari `landmines` manifest)
1. **Pengembalian belanja ≠ penyetoran kas negara.** Yang pertama mengurangi realisasi per jenis belanja; yang kedua mengurangi saldo tanpa menyentuh realisasi. Menyamakan keduanya menghitung ganda.
2. **Saldo tidak dijumlahkan antarbulan.** Rekap triwulan memakai posisi akhir bulan terakhir; `SUM(computed_ending_fund)` lintas bulan selalu salah.
3. **Saldo awal berantai.** Setiap perubahan bulan lama wajib memicu hitung ulang bulan sesudahnya dalam transaksi yang sama.
4. **Pratinjau Alpine.js bukan nilai resmi.** Angka di formulir yang dihitung di browser hanya bantuan; yang disimpan, ditampilkan di ringkasan, dan diekspor selalu dari server.
5. **Zona waktu WITA.** Tenggat dan "hari ini" dihitung di `Asia/Makassar`; `now()` UTC di server tanpa konfigurasi akan menandai terlambat sehari lebih awal atau lambat.
6. **Format angka Indonesia.** Titik = ribuan, koma = desimal. Parse lewat `Money`; `(float)` atau `floatval` pada input pengguna merusak nilai.
