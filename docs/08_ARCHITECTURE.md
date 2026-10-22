# 08 — ARCHITECTURE

Monolit Laravel modular berlapis, server-rendered Blade. Teknologi dan versi: `docs/09_STACK.md`.

## Lapisan

```
Browser (Blade + Tailwind + Alpine.js)
   │  HTTP (sesi, CSRF)
Route (web.php, middleware auth + role + must.change.password)
   │
Controller tipis ──► FormRequest (validasi)  ──► Policy (otorisasi)
   │
Action (satu kasus pakai, transaksi DB)  ──► Service (logika domain bersama)
   │                                            ├─ ReportBalanceCalculator
   │                                            ├─ ReportStateMachine
   │                                            ├─ DocumentCompletenessChecker
   │                                            ├─ ReportAggregator
   │                                            └─ FiscalYearCloser
Eloquent Model (+ SchoolScope) ──► MySQL
Storage disk private (PDF) · Queue database · Scheduler
```

| Lapisan | Tanggung jawab | Tidak boleh |
|---|---|---|
| Controller | Terima request, panggil Action, kembalikan view/redirect | Logika domain, query agregat, rumus |
| FormRequest | Validasi bentuk input dan format angka Indonesia → decimal string | Memeriksa transisi status |
| Policy | Siapa boleh apa pada objek | Validasi data |
| Action | Satu operasi bisnis, dibungkus `DB::transaction` | Dipanggil dari Blade |
| Service | Aturan domain yang dipakai beberapa Action | Akses `request()` |
| Model | Relasi, cast, scope | Rumus saldo |
| Blade component `x-pm.*` | Tampilan sesuai `docs/26_UI_CONVENTIONS.md` | Query DB |

## Keputusan arsitektural kunci

| Keputusan | Alasan |
|---|---|
| Rumus saldo hanya di `ReportBalanceCalculator` | Mencegah drift antara form, dasbor, rekap, ekspor |
| Transisi status hanya lewat `ReportStateMachine` | Satu tempat menegakkan syarat dan menulis riwayat |
| Saldo hasil hitung di-cache di `computed_*`, bukan sumber kebenaran | Dasbor cepat tanpa menghitung ulang ribuan laporan; selalu ditulis ulang saat simpan atau hitung ulang berantai |
| Hitung ulang berantai (buka kunci) dijalankan sinkron dalam transaksi | Maksimal 11 laporan per sekolah × sumber dana; tidak perlu queue |
| Uang sebagai string decimal + `brick/math` | Hindari galat pembulatan float |
| Server-rendered Blade, bukan SPA | Pengguna ASN, jaringan bervariasi, satu deployable sederhana |
| Komponen Pamong dibuat ulang sebagai Blade, token diporting ke Tailwind | Pamong asli berbasis React; stack melarang SPA |
| Agregasi dasbor via query SQL pada `computed_*` dan rincian, dengan `ReportAggregator` | Satu tempat aturan agregasi (`docs/04_DOMAIN_MODEL.md`) |
| PDF dilayani lewat controller ber-Policy dari disk privat | Mencegah tautan langsung bocor |

## Integrasi eksternal
Tidak ada di MVP. `schools.npsn` disiapkan sebagai kunci sinkron Dapodik kelak (L-03).

## Pekerjaan latar
- Queue `database`: ekspor XLSX besar, kirim notifikasi.
- Scheduler harian 00.30 WITA: bendera `is_late`, notifikasi H-3 dan H+1.
