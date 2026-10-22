# 14 — ERROR_HANDLING

## Klasifikasi

| Kelas | Contoh | Ke pengguna | Ke log |
|---|---|---|---|
| Validasi input | Format angka salah, field wajib kosong | Pesan per field di bawah input (`x-pm.text-field` state error) | Tidak |
| Aturan domain | Bulan sebelumnya belum diajukan, neto kumulatif negatif, dokumen wajib kurang | Alert `danger` di atas formulir berisi daftar alasan spesifik; tetap di halaman | `info` |
| Transisi tidak sah | `InvalidTransitionException` | Toast `danger`: "Status laporan sudah berubah. Muat ulang halaman." | `warning` + id laporan |
| Konflik penguncian | `lock_version` berbeda | Toast `warning`: "Laporan diubah orang lain. Perubahan Anda belum tersimpan." | `info` |
| Otorisasi | Policy menolak | 404 untuk objek sekolah lain; 403 halaman umum untuk aksi lain | `warning` |
| Berkas | Bukan PDF, terlalu besar | Pesan di komponen unggah | `info` |
| Tak terduga | Exception lain | Halaman 500 ramah dengan kode referensi (UUID request) | `error` + stack trace + kode referensi |

## Pola implementasi
- Aturan domain dilempar sebagai `App\Exceptions\DomainRuleException` (membawa daftar pesan) dari Service/Action, ditangkap di `bootstrap/app.php` → redirect back dengan `errors` bag `domain`.
- Pesan galat menyebut **apa yang salah dan cara memperbaikinya**, contoh: "Laporan Februari belum diajukan. Ajukan Februari terlebih dahulu."
- Pesan di `lang/id/domain.php`; tidak ada string pesan di Service.
- Operasi multi-tabel gagal → transaksi di-rollback; tidak ada penulisan sebagian.

## Larangan
- Jangan menampilkan stack trace, query SQL, atau path server ke pengguna (`APP_DEBUG=false` di produksi).
- Jangan menelan exception dengan `catch` kosong.
- Jangan mengubah 404 isolasi sekolah menjadi 403.
