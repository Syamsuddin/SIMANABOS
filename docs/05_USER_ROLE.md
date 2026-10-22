# 05 — USER_ROLE

## Peran (nama role di `spatie/laravel-permission`)

| Role | Cakupan data | Keterangan |
|---|---|---|
| `admin_dinas` | Seluruh kabupaten | Mengelola referensi, akun, sekolah, pagu, tahun anggaran; buka kunci; tutup tahun |
| `pemeriksa_dinas` | Seluruh sekolah `[ASUMSI]` | Memeriksa laporan; memverifikasi saldo awal tahun pertama |
| `pimpinan_dinas` | Seluruh kabupaten, baca saja | Dasbor, rekap, daftar perhatian |
| `operator_sekolah` | Satu `school_id` | Mengisi dan mengajukan |
| `kepala_sekolah` | Satu `school_id` | Membaca; menyetujui pengajuan bila `require_principal_approval=true` |

Satu akun = satu role. Role sekolah wajib punya `users.school_id`; role dinas wajib `school_id = null`.

## Matriks akses

| Kapabilitas | admin | pemeriksa | pimpinan | operator | kepsek |
|---|---|---|---|---|---|
| Kelola referensi, akun, sekolah, tahun anggaran, aturan dokumen | ✓ | ✗ | ✗ | ✗ | ✗ |
| Ubah alamat/kontak sekolah sendiri | ✓ | ✗ | ✗ | ✓ | ✗ |
| Usulkan pergantian pengelola | ✓ | ✗ | ✗ | ✓ | ✗ |
| Sahkan pergantian pengelola | ✓ | ✗ | ✗ | ✗ | ✗ |
| Isi pagu alokasi | ✓ | ✗ | ✗ | ✗ | ✗ |
| Isi RKAS, saldo awal tahun pertama | ✗ | ✗ | ✗ | ✓ | ✗ |
| Verifikasi saldo awal tahun pertama | ✗ | ✓ | ✗ | ✗ | ✗ |
| Ubah laporan (status `draf` / `perlu_perbaikan`) | ✗ | ✗ | ✗ | ✓ | ✗ |
| Setujui pengajuan (fitur opsional) | ✗ | ✗ | ✗ | ✗ | ✓ |
| Ajukan / tarik kembali laporan | ✗ | ✗ | ✗ | ✓ | ✗ |
| Ambil, catat, minta perbaikan, nyatakan selesai | ✗ | ✓ | ✗ | ✗ | ✗ |
| Buka kunci laporan | ✓ | ✗ | ✗ | ✗ | ✗ |
| Tutup tahun anggaran | ✓ | ✗ | ✗ | ✗ | ✗ |
| Dasbor kabupaten, daftar perhatian, rekap kabupaten, ekspor | ✓ | ✓ | ✓ | ✗ | ✗ |
| Beranda dan rekap sekolah sendiri | ✗ | ✗ | ✗ | ✓ | ✓ |
| Unduh PDF | ✓ | ✓ | ✓ | sendiri | sendiri |
| Lihat jejak audit laporan | ✓ | ✓ | ✗ | sendiri | sendiri |

## Aturan penegakan
- Setiap aksi memakai Policy (`app/Policies/*Policy.php`); role sekolah juga dibatasi global scope `SchoolScope` pada model bermuatan `school_id`.
- Akses objek sekolah lain oleh role sekolah menghasilkan **404**, bukan 403.
- Menyembunyikan menu tidak dihitung sebagai otorisasi.
- Aturan keamanan pendukung: `docs/21_SECURITY_RULES.md`.
