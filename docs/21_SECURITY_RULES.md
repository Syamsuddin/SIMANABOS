# 21 — SECURITY_RULES

## Autentikasi
- Login username + kata sandi; tanpa registrasi mandiri, tanpa reset sandi via surel di MVP (admin mereset dan menyetel `must_change_password=true`).
- Middleware `must.change.password` mengalihkan semua route ke halaman ganti sandi sampai diganti.
- Sandi minimal 10 karakter, kombinasi huruf dan angka (`Password::min(10)->letters()->numbers()`).
- Rate limit login: 5 percobaan per menit per username + IP (`RateLimiter::for('login', …)` di `AppServiceProvider`).
- Sesi: driver database, `SESSION_LIFETIME=120` menit idle, regenerasi ID saat login, `secure`, `httponly`, `same_site=lax`.
- Akun `is_active=false` ditolak saat login dan sesi aktifnya diakhiri.

## Otorisasi
- Matriks: `docs/05_USER_ROLE.md`. Setiap route ber-middleware `auth` + `role:` dan setiap aksi objek memakai `$this->authorize()` / Policy.
- `SchoolScope` aktif untuk role sekolah pada semua model bermuatan `school_id`; objek sekolah lain → 404.
- Aksi tulis memeriksa status laporan di Policy (operator hanya `draf`/`perlu_perbaikan`).

## Validasi input
- Semua input lewat FormRequest. Angka uang: regex format Indonesia, diparse `Money::parse`, batas 0 ≤ x ≤ 9.999.999.999.999,99 (kecuali `amount` penyesuaian boleh negatif).
- `month` 1–12; ID asing divalidasi `exists` dan cocok `school_id`/`fiscal_year_id`.
- Teks bebas dibatasi panjang sesuai `docs/07_DATA_MODEL.md`; keluaran Blade selalu `{{ }}` (escape), tidak ada `{!! !!}` untuk data pengguna.

## Berkas PDF
- Validasi `mimetypes:application/pdf` (isi berkas) + `max:` dari `SIMANABOS_MAX_UPLOAD_KB`; tolak berkas 0 byte.
- Simpan di disk `private` dengan nama UUID; unduh lewat `DocumentDownloadController` ber-Policy dengan `Content-Disposition: attachment` atau `inline` + `X-Content-Type-Options: nosniff`.
- Tidak pernah menjalankan atau mem-parse isi PDF di server.

## Data sensitif
- Kata sandi di-hash (bawaan Laravel). NIP hanya tampil bagi pengguna yang berhak melihat profil sekolah terkait; tidak masuk ekspor rekap kabupaten.
- Rahasia hanya di `.env`; `.env` tidak masuk repositori.
- Audit append-only; tidak ada route untuk menghapus atau mengubahnya.

## Header dan transport
- Produksi HTTPS saja (redirect HTTP → HTTPS di Nginx), HSTS, `X-Frame-Options: DENY`, `Referrer-Policy: same-origin`.
- CSRF aktif untuk semua form.

## Kepatuhan
Tidak ada regulasi khusus di luar praktik baik umum dan ketentuan kearsipan daerah `[TERBUKA]`.
