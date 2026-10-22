# CLAUDE.md

File ini selalu aktif. Untuk hal di luar ini → buka INDEX.md.

## Proyek
SIMANABOS — aplikasi web Laravel pengawasan dana BOS SD/SMP untuk Disdikbud Kab. Hulu Sungai Selatan (agregat bulanan, arsip PDF, pemeriksaan dinas; ARKAS tetap sistem resmi). Detail: docs/00_EXECUTIVE_SUMMARY.md. Scope: docs/02_SCOPE.md.

## Prinsip kerja agen (non-negotiable)
1. Friksi sebanding irreversibilitas.
2. Lapisan deterministik (validasi, constraint, tes) di bawah penalaran.
3. Human-in-the-loop untuk high-stakes (migrasi DB, hapus data, keamanan, rilis).
4. Jangan refactor keputusan yang disengaja diam-diam.
5. Hormati scope (docs/02). Out-of-scope → berhenti & tanya.

## Stack  (sumber: docs/09)
PHP ≥ 8.3, Laravel, MySQL 8, Blade + Tailwind v4 + Alpine.js, spatie/permission, spatie/activitylog, brick/math, Pest — Terlarang: jQuery, SPA/Inertia, Livewire, float untuk uang, pustaka UI eksternal.

## Struktur & konvensi  (sumber: docs/12)
- Rumus saldo HANYA di `app/Services/ReportBalanceCalculator.php`; transisi status HANYA di `app/Services/ReportStateMachine.php`.
- Controller tipis → FormRequest + Policy → `app/Actions/**`.
- Uang: string decimal + `Money`/brick/math; format Indonesia `26.512.500,00`.

## Perintah penting  (sumber: docs/11)
`composer run dev` · `php artisan test` · `php artisan test --filter=GoldenFebruary` · `php artisan migrate` ⚠️

## Guardrail inti  (penuh: docs/20, 21, 22)
- JANGAN ubah status laporan selain lewat ReportStateMachine; JANGAN hapus laporan, versi dokumen, atau audit.
- JANGAN jumlahkan saldo antarbulan; JANGAN anggap `computed_*` sumber kebenaran.
- JANGAN lemahkan Policy/SchoolScope (sekolah lain = 404) atau tes; JANGAN ubah golden case.
- JANGAN simpan PDF di disk publik; JANGAN tambah dependensi tanpa izin.
- Ubah rumus, transisi, skema destruktif, rilis → berhenti & minta konfirmasi (docs/22).

## Antarmuka  (sumber: docs/26_UI_CONVENTIONS.md)
Token Pamong tema Terang di `@theme` Tailwind + komponen Blade `x-pm.*`, identitas Disdikbud HSS. Larangan: nilai di luar tabel token; status tanpa label; >1 tombol primary per area.

## Alur per-task  (penuh: docs/17, 19)
Baca INDEX.md → muat dokumen relevan → konfirmasi scope → vertical slice → tes (docs/13) → DoD (docs/24).

## Definisi selesai (ringkas; penuh: docs/24)
Kriteria docs/23 lulus + `php artisan test` hijau + pint lulus + Policy diuji + empat state UI.

## Untuk apa pun di luar ini → INDEX.md
