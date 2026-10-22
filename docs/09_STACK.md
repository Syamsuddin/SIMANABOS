# 09 — STACK

| Lapisan | Teknologi | Versi |
|---|---|---|
| Bahasa | PHP | ≥ 8.3 (ekstensi: bcmath, intl, mbstring, pdo_mysql, fileinfo, zip) |
| Framework | Laravel | Rilis stabil terbaru saat `composer create-project`; dikunci di `composer.lock` `[ASUMSI]` |
| Basis data | MySQL | 8.0, InnoDB, `utf8mb4_unicode_ci` |
| Tampilan | Blade + Tailwind CSS | Tailwind 4.x via `@tailwindcss/vite` |
| Bundler | Vite | Bawaan Laravel |
| Interaktivitas | Alpine.js | 3.x (via npm) |
| Huruf | Plus Jakarta Sans, JetBrains Mono | Di-host sendiri via `@fontsource-variable/plus-jakarta-sans`, `@fontsource/jetbrains-mono` |
| Peran | spatie/laravel-permission | Versi stabil terbaru |
| Audit | spatie/laravel-activitylog | Versi stabil terbaru |
| Aritmetika uang | brick/math | Versi stabil terbaru |
| Ekspor XLSX | openspout/openspout | Versi stabil terbaru |
| Tes | Pest | Versi yang kompatibel dengan Laravel terpasang |
| Gaya kode | Laravel Pint | Bawaan |
| Queue / cache / sesi | Driver `database` | — |

## Teknologi terlarang

| Terlarang | Alasan |
|---|---|
| jQuery | Alpine.js sudah cukup; dua pustaka DOM menambah kerumitan |
| React, Vue, Svelte, Inertia, SPA apa pun | Satu deployable server-rendered; komponen Pamong React tidak dipakai di runtime |
| Livewire | Keputusan sederhana: Blade + Alpine; hindari dua model reaktivitas |
| `float`/`double` untuk uang (kolom atau PHP) | Galat pembulatan saldo |
| Pustaka komponen UI eksternal (Flowbite, DaisyUI, AdminLTE, Bootstrap) | Token dan komponen hanya dari `docs/26_UI_CONVENTIONS.md` |
| CDN runtime untuk aset atau huruf | Server pemda; aset di-bundle Vite |
| Paket baru di luar tabel ini | Wajib alasan tertulis dan lewat `docs/22_CHANGE_POLICY.md` |
