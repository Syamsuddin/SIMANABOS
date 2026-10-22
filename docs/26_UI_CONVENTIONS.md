# 26 — UI_CONVENTIONS

## Jangkar desain
Sistem desain **Pamong** tema Terang (artifact `75fRYpQ6PWFYRk7GkjDXnc`, versi `1792459369-8bac`) dan kanvas acuan **SIMANABOS — Desain UI** (artifact `J9kQVAY7TeLE4CnzCnJSC6`: Dasbor Kabupaten, Layar Pemeriksaan, Formulir Laporan Bulanan, Beranda Sekolah ponsel). Pamong berbasis React dan **tidak** dipakai di runtime: token diporting ke `@theme` Tailwind v4 di `resources/css/app.css`, komponen dibuat ulang sebagai Blade `x-pm.*`. Bila kanvas dan dokumen ini berbeda, **dokumen ini menang**.

Identitas: lambang Kabupaten HSS (`public/img/logo-hss.png`), nama aplikasi "SIMANABOS", subjudul "Disdikbud Kab. Hulu Sungai Selatan".

## Tabel token (nilai di luar tabel ini TERLARANG)

| Kategori | Nama token (`--color-*` / utility) | Nilai | Pakai untuk |
|---|---|---|---|
| Permukaan | surface | `#f3f4f1` | Latar halaman |
| | surface-raised | `#ffffff` | Kartu, tabel, topbar, modal |
| | surface-sunken | `#eaece7` | Kepala tabel, input nonaktif, track progress, nilai baca-saja |
| Garis | line | `#d8dbd3` | Pemisah, border kartu |
| | line-strong | `#8e958c` | Border input/select/checkbox |
| Teks | ink | `#1a2127` | Teks utama |
| | ink-muted | `#525c64` | Label, teks sekunder |
| | ink-subtle | `#6a737a` | Placeholder (hanya di surface-raised) |
| Sidebar | sidebar-bg / sidebar-ink / sidebar-ink-muted / sidebar-active | `#13294b` / `#e6ecf4` / `#a9b8cc` / `#244677` | Navigasi utama dan header ponsel |
| Primer | primary / primary-hover / on-primary / primary-soft | `#1d4f91` / `#163f75` / `#ffffff` / `#e3ebf6` | Aksi utama, tautan, tab aktif, baris terpilih |
| Aksen | accent / on-accent / accent-soft | `#8a5d12` / `#ffffff` / `#f6ecd7` | Penanda tahun anggaran, badge jumlah sidebar; bukan status |
| Status | success / success-soft | `#17693a` / `#e1f1e7` | Selesai diperiksa, valid |
| | warning / warning-soft | `#7d5100` / `#fbeed3` | Perlu perbaikan, selisih, menunggu |
| | danger / danger-soft / on-danger | `#ae2318` / `#fbe3df` / `#ffffff` | Terlambat, belum ada, aksi merusak |
| | info / info-soft | `#1a5a9e` / `#e1ecf8` | Diajukan, informasi |
| Fokus | focus | `#b7791f` | Cincin fokus 2px, offset 2px |
| Grafik (urutan tetap) | chart-1 … chart-4 | `#1d4f91`, `#b7791f`, `#5b8fc9`, `#1f8a5a` | Seri grafik; seri ke-5+ digabung "Lainnya" |
| Overlay | overlay | `rgba(8,14,20,0.55)` | Tirai modal |
| Huruf | font-sans / font-mono | Plus Jakarta Sans / JetBrains Mono | Mono untuk NPSN, NIP, nominal dalam tabel |
| Skala teks | display / h1 / h2 / h3 | 32/40 700 · 24/32 700 · 18/26 650 · 15/22 650 | Login · judul halaman · judul kartu · subjudul |
| | body / small / caption / overline / stat / mono | 14/22 400 · 13/20 400 · 12/16 600 · 11/16 700 +0.08em kapital · 28/34 700 · 13/20 500 | |
| Jarak | space-1…12 | 4, 8, 12, 16, 20, 24, 32, 48 px | Padding kartu 20; celah grid 20; padding halaman 24 (16 ponsel) |
| Radius | radius-sm / md / lg / full | 4 / 6 / 10 / 999 px | Badge · tombol & input · kartu & modal · avatar |
| Bayangan | shadow-sm / md / lg | `0 1px 2px rgba(16,24,32,.06)` / `0 4px 12px rgba(16,24,32,.10)` / `0 16px 40px rgba(16,24,32,.18)` | Kartu · dropdown · modal |
| Tata letak | sidebar-width / sidebar-collapsed / topbar-height / content-max / control-height | 264 / 72 / 64 / 1440 / 38 px | |

Semua angka memakai `font-variant-numeric: tabular-nums`. Tema gelap ditunda (L-04).

## Pemetaan status laporan ke badge

| Status SIMANABOS | Label | Tone |
|---|---|---|
| `draf` | Draf | netral (surface-sunken + ink-muted) |
| `diajukan` | Diajukan | info |
| `dalam_pemeriksaan` | Dalam pemeriksaan | primary-soft + primary |
| `perlu_perbaikan` | Perlu perbaikan | warning |
| `selesai_diperiksa` | Selesai diperiksa | success |
| bendera terlambat | Terlambat | danger |
| bendera tinjau ulang | Perlu tinjau ulang | accent |

Badge selalu berisi teks dan ikon; warna tidak pernah satu-satunya pembeda.

## Inventaris komponen (`resources/views/components/pm/`)

| Komponen | Varian / prop utama |
|---|---|
| `app-shell` | Sidebar + topbar + slot konten + footer |
| `sidebar` | brand (logo, nama, sub), grup menu, item aktif, badge jumlah, mode ciut |
| `topbar` | tombol menu (ponsel), pencarian, penanda tahun anggaran (accent), notifikasi, pengguna |
| `page-header` | breadcrumb, judul, deskripsi, slot aksi |
| `button` | primary · secondary · ghost · danger; sm 32 / md 38 / lg 44; ikon kiri/kanan; loading |
| `icon-button` | ghost · outline; `label` wajib |
| `icon` | 44 ikon garis Pamong, inline SVG `currentColor`, 16/18/20 px |
| `badge`, `status-badge` | tone sesuai tabel status |
| `alert` | info · success · warning · danger; judul, isi, aksi |
| `toast` | sama dengan alert; muncul kanan atas, hilang 5 detik |
| `card` | judul, subjudul, slot aksi, flush (tanpa padding untuk tabel), footer |
| `stat-card` | label, nilai, satuan, ikon, caption, progress |
| `progress-bar` | nilai, target (garis), tone, label nilai |
| `data-table` | kolom (align, mono), baris, kosong, footer, gulir horizontal di wadah |
| `tabs` | jumlah per tab |
| `toolbar` | filter select + aksi |
| `text-field` | label, wajib/opsional, hint, error, mono, multiline, `money` (inputmode decimal, rata kanan, mono) |
| `select`, `checkbox`, `radio-group`, `switch` | label, hint, error |
| `file-upload` | multiple, daftar berkas, progres, error; pilih jenis dokumen per berkas |
| `description-list` | 1 atau 2 kolom, nilai mono |
| `stepper` | langkah, current, error |
| `timeline` | judul, meta, waktu, tone, ikon |
| `modal` | info · danger; aksi destruktif wajib `tone=danger` + alasan bila diminta |
| `empty-state` | ikon, judul, deskripsi, aksi |
| `skeleton` | baris |
| `bar-chart`, `donut-chart` | SVG server-side dari data; legenda + tabel alternatif |

## Peta halaman → pola

| Halaman | Peran | Pola | Acuan kanvas |
|---|---|---|---|
| Login, ganti sandi | semua | auth | — |
| Dasbor kabupaten | admin, pemeriksa, pimpinan | dasbor | Dasbor Kabupaten |
| Daftar perhatian | admin, pemeriksa, pimpinan | daftar + filter | Dasbor Kabupaten (tabel) |
| Antrean pemeriksaan, semua laporan | pemeriksa, admin | daftar + filter | — |
| Pemeriksaan laporan | pemeriksa | detail (kiri angka+catatan, kanan PDF+kelengkapan+riwayat) | Layar Pemeriksaan |
| Dokumen periodik | dinas, sekolah | daftar + detail | — |
| Pagu, RKAS, tahun anggaran, referensi, akun, sekolah | admin | daftar + formulir | — |
| Beranda sekolah (status 12 bulan) | operator, kepsek | dasbor | Beranda Sekolah ponsel |
| Formulir laporan bulanan | operator | formulir (kiri isian, kanan ringkasan+dokumen) | Formulir Laporan Bulanan |
| RKAS sekolah, profil sekolah | operator | formulir | — |
| Rekap dan ekspor | dinas | daftar + filter | — |

Navigasi: sidebar kiri + topbar. Grup menu dinas: Utama, Pemeriksaan, Anggaran, Pengaturan. Grup menu sekolah: Sekolah, Akun.

Aksi destruktif atau irreversibel (buka kunci, tutup tahun, nonaktifkan, minta perbaikan, nyatakan selesai) selalu lewat `x-pm.modal` konfirmasi.

## Perangkat dan breakpoint
- Halaman sekolah: **mobile-first**, wajib layak di lebar **360 px**; formulir satu kolom; target sentuh ≥ 44 px.
- Halaman dinas: desktop-first, tetap layak di **≤ 768 px**.
- < 1100 px: grid 4 kolom → 2; grid 2:1 → 1 kolom. < 760 px: sidebar tersembunyi, dibuka tombol menu (drawer). Tabel lebar bergulir horizontal di dalam kartu.

## Empat state wajib (setiap halaman dan komponen ber-data)

| State | Tampilan |
|---|---|
| Kosong | `x-pm.empty-state` dengan kalimat penjelas dan aksi berikutnya (mis. "Belum ada penyesuaian") |
| Memuat | `x-pm.skeleton` untuk bagian yang dimuat terpisah; tombol `loading` saat submit |
| Gagal | `x-pm.alert tone=danger` dengan pesan per `docs/14_ERROR_HANDLING.md` |
| Sukses | `x-pm.toast tone=success` setelah aksi; data tampil |

## Bahasa dan mikroteks
- Bahasa Indonesia baku, sapaan "Anda", sistem pasif ("Laporan tersimpan").
- Huruf kapital hanya di awal kalimat untuk tombol, label, judul kartu; judul halaman Huruf Kapital Setiap Kata.
- Tombol = kata kerja + objek; kata baku: Simpan draf, Ajukan, Tarik kembali, Ambil untuk diperiksa, Minta perbaikan, Nyatakan selesai diperiksa, Buka kunci, Unggah, Ganti berkas, Ekspor Excel, Tutup tahun anggaran.
- Rupiah: `Rp 26.812.500,00` di detail; `Rp 38,6 M` / `Rp 26,8 jt` di kartu ringkas. Persen: `68,2%`. Tanggal: `22 Okt 2026` di tabel, `22 Oktober 2026, 10.12 WITA` di detail.
- Sisa anggaran dan saldo kas selalu berlabel lengkap dan tidak pernah digabung.
- Halaman laporan selesai memuat kalimat batas makna: "Pemeriksaan administratif: angka agregat cocok dengan PDF dan dokumen wajib lengkap. Status ini bukan pernyataan keabsahan setiap transaksi."

## Aksesibilitas dasar
- Semua pasangan teks pada tabel token lulus WCAG AA (≥ 4,5:1) di tema Terang.
- Setiap input punya `<label>`; error dihubungkan `aria-describedby`.
- Cincin fokus `focus` 2px terlihat di semua elemen interaktif, termasuk di sidebar.
- `x-pm.icon-button` wajib `aria-label`. Grafik memiliki tabel alternatif.
- Lanjutan (pembaca layar penuh, audit WCAG) `[TERBUKA]` pasca-MVP.

## Larangan UI
1. Nilai warna, ukuran, radius, bayangan, atau huruf di luar tabel token.
2. Pustaka UI eksternal atau komponen di luar inventaris tanpa lewat `docs/22_CHANGE_POLICY.md`.
3. Gradien, efek kaca, ilustrasi dekoratif, emoji.
4. Ikon dari set lain selain ikon garis Pamong.
5. Status hanya dengan warna tanpa label.
6. Lebih dari satu tombol `primary` per area.
7. Teks placeholder tersisa ("Lorem", "TODO") di halaman.
8. Animasi dekoratif; transisi hanya 120–180 ms pada warna dan lebar sidebar.

Verifikasi UI per fitur: `docs/23_ACCEPTANCE_CRITERIA.md`.
