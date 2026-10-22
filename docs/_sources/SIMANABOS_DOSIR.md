# DOSIR APLIKASI — SIMANABOS

**Sistem Informasi Manajemen BOS**
Dosir masukan untuk skill `vcbd` (blueprint 28 dokumen) · Versi dosir 1.0 · 22 Oktober 2026
Pemilik rancangan: Syamsuddin, S.Pd, MM

---

## 0. Cara membaca dosir ini

Dosir ini disusun mengikuti kategori wawancara VCBD (K1–K9) agar skill `vcbd` dapat memakainya sebagai **fakta diketahui** pada Fase 0 (Triase) dan hanya menanyakan celah yang tersisa.

Label yang dipakai:

| Label | Arti |
|---|---|
| (tanpa label) | Keputusan yang sudah disepakati pemilik rancangan |
| `[ASUMSI]` | Default yang diusulkan; berlaku bila tidak dikoreksi saat konfirmasi VCBD |
| `[TERBUKA]` | Sengaja ditunda; harus diputuskan sebelum fitur terkait dikerjakan |

Ringkasan klasifikasi Triase:

| Butir | Nilai |
|---|---|
| Jenis proyek | Greenfield |
| Ber-UI | Ya (`ui.enabled = true`) |
| Mode | Tunggal, bukan split. Monolit Laravel + Blade; satu deployable |
| Profil framework | Laravel (bukan profil native) |

---

## 1. K1 — Identitas dan Tujuan → dokumen 00, 01

### 1.1 Identitas

| Butir | Nilai |
|---|---|
| Nama | SIMANABOS |
| Kepanjangan | Sistem Informasi Manajemen BOS |
| Lingkup wilayah | Satu kabupaten (satu instalasi = satu kabupaten) |
| Satuan pendidikan | SD dan SMP, negeri dan swasta |
| Pemilik produk | Dinas Pendidikan kabupaten; perancang: Syamsuddin, S.Pd, MM |
| Kabupaten implementasi pertama | `[TERBUKA]` |

### 1.2 Masalah inti

Dinas pendidikan kabupaten tidak memiliki satu tempat untuk memantau penerimaan dan penggunaan dana BOS seluruh SD dan SMP secara bulanan. Laporan sekolah tersebar dalam bentuk PDF, angka sulit direkap, dan tindak lanjut perbaikan tidak tercatat.

### 1.3 Tujuan

SIMANABOS membantu dinas memantau dana BOS melalui:

1. angka agregat bulanan per sekolah, sumber dana, dan jenis belanja;
2. arsip PDF laporan resmi sekolah yang berversi;
3. pemeriksaan kesesuaian angka terhadap PDF; dan
4. siklus tindak lanjut perbaikan yang tercatat.

### 1.4 Posisi terhadap ARKAS (prinsip pembatas)

- Pencatatan transaksi dan pelaporan resmi sekolah **tetap di ARKAS**.
- SIMANABOS adalah sarana **pengawasan dan penyimpanan** tingkat kabupaten.
- SIMANABOS **tidak** membuat BKU, SPTJM, atau laporan resmi apa pun. Ia hanya menerima angka ringkasan dan PDF hasil ARKAS.

### 1.5 Metrik sukses `[ASUMSI]`

Metrik utama: **persentase laporan bulanan berstatus *selesai diperiksa* paling lambat 30 hari setelah tenggat**, target ≥ 90% pada tahun anggaran pertama penuh.

Metrik pendukung:
- persentase sekolah yang mengajukan laporan tepat waktu;
- median waktu dari *diajukan* sampai *selesai diperiksa*;
- jumlah laporan dengan selisih saldo yang belum terjelaskan.

### 1.6 Batas makna "selesai diperiksa"

Status *selesai diperiksa* berarti pemeriksaan administratif selesai sesuai cakupannya: angka agregat cocok dengan PDF dan dokumen wajib lengkap. Status ini **bukan** pernyataan bahwa setiap transaksi sah. Keabsahan transaksi memerlukan pemeriksaan bukti tersendiri di luar aplikasi. Kalimat ini wajib tampil di halaman detail laporan berstatus selesai.

---

## 2. K2 — Fitur dan Lingkup → dokumen 01, 02, 03

### 2.1 Fitur MVP

| Kode | Fitur | Ringkas |
|---|---|---|
| F-01 | Autentikasi dan akun | Login username + kata sandi; akun dibuat admin dinas; wajib ganti sandi saat login pertama |
| F-02 | Data referensi | Kecamatan, sumber dana, jenis belanja per tahun, jenis dokumen, aturan dokumen wajib, tahun anggaran |
| F-03 | Profil sekolah dan pengelola | NPSN, identitas, status; riwayat kepala sekolah, bendahara, operator |
| F-04 | Anggaran tahunan | Pagu alokasi, RKAS per jenis belanja berversi, saldo awal tahun |
| F-05 | Laporan bulanan | Input agregat, rumus saldo dua kantong, selisih terhadap BKU |
| F-06 | Dokumen PDF | Unggah banyak berkas atau satu gabungan, penandaan jenis dokumen, versi, cek kelengkapan |
| F-07 | Pengajuan dan pemeriksaan | Mesin status, catatan perbaikan per butir, persetujuan kepala sekolah opsional |
| F-08 | Penguncian periode | Laporan selesai terkunci; buka kunci hanya admin dinas dengan alasan |
| F-09 | Dokumen periodik | RKAS disahkan, laporan tahap, laporan tahunan, berita acara; angka pembanding opsional |
| F-10 | Dasbor kabupaten | Penerimaan, realisasi, saldo, kelengkapan, dengan filter |
| F-11 | Daftar perhatian | Belum melapor, dokumen kurang, selisih, perbaikan menggantung, terlambat |
| F-12 | Tenggat dan pengingat | Tenggat per bulan dapat diatur; notifikasi dalam aplikasi |
| F-13 | Rekap dan ekspor | Rekap triwulan, semester, tahunan per sekolah dan kabupaten; ekspor XLSX |
| F-14 | Jejak audit | Setiap pengajuan, perubahan angka, penggantian PDF, perubahan status, dan buka kunci tercatat |
| F-15 | Tutup tahun anggaran | Mengunci tahun dan menetapkan saldo awal Januari tahun berikutnya |

### 2.2 Fitur tahap berikutnya (bukan MVP)

- Rincian realisasi per **komponen penggunaan BOS** (perpustakaan, pembelajaran, pemeliharaan, honor, dll.) sebagai pengelompokan kedua.
- Notifikasi surel atau WhatsApp.
- Sinkronisasi profil sekolah dari Dapodik.
- Analitik tren lintas tahun.

### 2.3 Di luar lingkup (eksplisit TIDAK dibangun)

1. Input transaksi satu per satu dan unggah kuitansi per transaksi.
2. OCR atau ekstraksi angka otomatis dari PDF.
3. Integrasi otomatis dengan ARKAS, Dapodik, bank, atau SIPD.
4. Pembuatan dokumen resmi (BKU, SPTJM, laporan realisasi). Aplikasi hanya menerima PDF jadi.
5. Jenjang selain SD dan SMP (PAUD, SMA, SMK, SLB).
6. Multi-kabupaten dalam satu instalasi (multi-tenant).
7. Aplikasi mobile native. Akses ponsel cukup lewat web responsif.
8. Tanda tangan elektronik.
9. Penilaian keabsahan transaksi.

### 2.4 Urutan rilis (vertical slice)

| Fase | Isi | Hasil yang bisa didemokan |
|---|---|---|
| 0 | Fondasi: auth, peran, referensi, sekolah, akun, audit | Admin membuat sekolah dan akun operator |
| 1 | Anggaran tahunan: pagu, RKAS berversi, saldo awal | Sekolah memiliki anggaran per jenis belanja |
| 2 | Laporan bulanan: input + rumus saldo + selisih | Operator mengisi Januari dan melihat saldo akhir |
| 3 | Dokumen PDF + kelengkapan | Operator mengunggah dan menandai dokumen |
| 4 | Pengajuan, pemeriksaan, penguncian | Siklus draf → perbaikan → selesai berjalan penuh |
| 5 | Dasbor, daftar perhatian, tenggat | Dinas melihat kondisi kabupaten |
| 6 | Dokumen periodik + pembanding tahap | Laporan Tahap I dibandingkan dengan Σ bulanan |
| 7 | Rekap, ekspor, tutup tahun | Rekap tahunan dan saldo Januari tahun berikutnya |
| 8 | Pengerasan dan rilis | Uji beban ringan, cadangan, rilis produksi |

---

## 3. K3 — Aktor dan Peran → dokumen 05, 06, 21

### 3.1 Peran

| Kode peran | Nama | Cakupan data |
|---|---|---|
| `admin_dinas` | Admin dinas | Seluruh kabupaten; mengelola referensi, akun, sekolah, periode, aturan dokumen; buka kunci; tutup tahun |
| `pemeriksa_dinas` | Petugas pemeriksa dinas | Seluruh sekolah atau sekolah yang ditugaskan `[ASUMSI: seluruh sekolah di MVP]`; memeriksa laporan |
| `pimpinan_dinas` | Pimpinan dinas `[ASUMSI]` | Seluruh kabupaten, baca saja: dasbor, rekap, daftar perhatian |
| `operator_sekolah` | Operator/pengelola BOS sekolah | Hanya sekolahnya sendiri; mengisi dan mengajukan |
| `kepala_sekolah` | Kepala sekolah `[ASUMSI]` | Hanya sekolahnya sendiri; baca semua laporan; menyetujui pengajuan bila fitur persetujuan aktif |

Satu akun hanya memiliki satu peran. Akun peran sekolah terikat ke tepat satu `school_id`.

### 3.2 Matriks akses ringkas

| Aksi | admin | pemeriksa | pimpinan | operator | kepsek |
|---|---|---|---|---|---|
| Kelola referensi, akun, sekolah | ✓ | – | – | – | – |
| Ubah profil sekolah sendiri (alamat, kontak) | ✓ | – | – | ✓ | – |
| Catat pergantian pengelola | ✓ | – | – | ✓ (diajukan, disahkan admin) `[ASUMSI]` | – |
| Isi pagu alokasi | ✓ | – | – | – | – |
| Isi RKAS, saldo awal tahun pertama | – | – | – | ✓ | – |
| Verifikasi saldo awal tahun pertama | – | ✓ | – | – | – |
| Isi dan ubah laporan bulanan (status draf / perlu perbaikan) | – | – | – | ✓ | – |
| Setujui pengajuan (bila persetujuan aktif) | – | – | – | – | ✓ |
| Ajukan laporan | – | – | – | ✓ | – |
| Tarik kembali pengajuan sebelum diperiksa | – | – | – | ✓ | – |
| Ambil, periksa, beri catatan, nyatakan selesai | – | ✓ | – | – | – |
| Buka kunci laporan selesai | ✓ | – | – | – | – |
| Tutup tahun anggaran | ✓ | – | – | – | – |
| Lihat dasbor kabupaten dan daftar perhatian | ✓ | ✓ | ✓ | – | – |
| Lihat dasbor sekolah sendiri | – | – | – | ✓ | ✓ |
| Unduh PDF | ✓ | ✓ | ✓ | ✓ (sekolah sendiri) | ✓ (sekolah sendiri) |
| Lihat jejak audit | ✓ | ✓ (laporan yang diperiksa) | – | ✓ (laporan sendiri) | ✓ (laporan sendiri) |

Penegakan: setiap akses data sekolah melalui Policy Laravel yang membandingkan `user.school_id` dengan `school_id` objek. Pemeriksaan di level query (global scope untuk peran sekolah) **dan** di Policy. Tidak cukup menyembunyikan menu.

### 3.3 Alur bisnis utama

**A. Persiapan tahun anggaran (admin dinas)**
1. Membuka tahun anggaran baru: tenggat default, rentang bulan tiap tahap, salinan referensi jenis belanja dari tahun sebelumnya (dapat diubah sebelum ada laporan).
2. Mengisi pagu alokasi per sekolah per sumber dana, termasuk bulan mulai sumber dana aktif.
3. Sistem membuat 12 laporan bulanan berstatus draf untuk tiap pasangan sekolah × sumber dana aktif.

**B. Anggaran sekolah (operator)**
1. Mengisi anggaran RKAS per jenis belanja per sumber dana.
2. Mengunggah PDF RKAS yang disahkan.
3. Tahun pertama saja: mengisi saldo awal Januari (kantong dana dan kantong pajak); pemeriksa memverifikasi.
4. Bila ada perubahan RKAS: membuat versi baru dengan tanggal berlaku dan PDF perubahan.

**C. Laporan bulanan (happy path)**
1. Operator membuka laporan bulan M untuk satu sumber dana.
2. Saldo awal terisi otomatis dan tidak dapat diubah.
3. Operator mengisi penerimaan, realisasi bruto per jenis belanja, pengembalian belanja, penyetoran kas, pajak, penyesuaian (bila ada), serta saldo bank dan saldo tunai menurut BKU.
4. Sistem menghitung saldo akhir dan selisih secara langsung.
5. Operator mengunggah PDF dan menandai jenis dokumen.
6. Operator meninjau ringkasan, lalu mengajukan.
7. Pemeriksa mengambil laporan (status *dalam pemeriksaan*), membandingkan angka dengan PDF, lalu menyatakan *selesai diperiksa*.
8. Laporan terkunci.

**D. Cabang perbaikan**
1. Pemeriksa memberi satu atau lebih catatan yang menunjuk butir tertentu (field angka, jenis dokumen, atau umum), lalu mengubah status ke *perlu perbaikan*.
2. Operator memperbaiki dan menanggapi tiap catatan.
3. Operator mengajukan ulang. Riwayat catatan dan tanggapan tetap tersimpan.

**E. Kondisi gagal utama**

| Kondisi | Perilaku sistem |
|---|---|
| Bulan sebelumnya belum diajukan | Tombol Ajukan nonaktif; pesan menyebut bulan yang harus diajukan dulu |
| Dokumen wajib belum lengkap | Pengajuan ditolak; daftar jenis dokumen yang kurang ditampilkan |
| Field wajib kosong | Pengajuan ditolak; field disorot |
| Selisih saldo ≠ 0 | Pengajuan **diizinkan** asal kolom penjelasan selisih diisi; laporan masuk daftar perhatian |
| Realisasi kumulatif neto suatu jenis belanja < 0 | Pengajuan ditolak |
| Persetujuan kepsek aktif tetapi belum disetujui | Pengajuan ditolak |
| Unggah bukan PDF atau melebihi batas ukuran | Berkas ditolak dengan pesan jelas |
| Operator mencoba membuka laporan sekolah lain | 404 (bukan 403, agar keberadaan data tidak bocor) |
| Dua pemeriksa mengambil laporan yang sama | Hanya yang pertama berhasil (penguncian optimistis); yang kedua mendapat pesan |

---

## 4. K4 — Domain dan Data → dokumen 04, 07

### 4.1 Glosarium domain (rumah: dokumen 04)

| Istilah | Definisi kanonik |
|---|---|
| Sumber dana | Jenis dana BOS yang diterima sekolah, mis. BOS Reguler, BOS Kinerja. Setiap sumber dana dilaporkan terpisah |
| Laporan bulanan | Satu laporan untuk kombinasi unik sekolah × tahun anggaran × bulan × sumber dana |
| Laporan nihil | Laporan bulanan tanpa penerimaan dan tanpa realisasi; tetap wajib diajukan |
| Pagu alokasi | Jumlah dana yang dialokasikan untuk sekolah pada satu tahun dan sumber dana |
| Anggaran RKAS | Rencana belanja per jenis belanja yang disahkan; dapat mencakup sisa dana tahun lalu. Disimpan terpisah dari pagu |
| Versi RKAS | Satu set anggaran per jenis belanja dengan tanggal berlaku; perubahan membuat versi baru |
| Jenis belanja | Klasifikasi belanja sesuai RKAS dan ARKAS (mis. barang dan jasa, modal peralatan dan mesin, modal aset tetap lainnya). Daftarnya per tahun anggaran |
| Realisasi bruto | Belanja yang dibukukan pada bulan itu per jenis belanja, sebelum pengembalian |
| Pengembalian belanja | Uang belanja yang kembali ke rekening sekolah (mis. kelebihan bayar dari penyedia). **Mengurangi realisasi** pada jenis belanja asalnya |
| Realisasi neto | Realisasi bruto − pengembalian belanja |
| Penyetoran ke kas negara/daerah | Penyetoran sisa dana atau temuan. **Mengurangi saldo, bukan belanja** |
| Penyesuaian | Koreksi bertanda (±) dengan alasan, untuk kesalahan pada bulan yang sudah terkunci; dicatat di bulan berjalan dengan rujukan bulan asal |
| Kantong dana | Saldo dana BOS milik sekolah |
| Kantong pajak (titipan pajak) | Pajak yang dipungut bendahara dan belum disetor ke kas negara; bukan milik sekolah |
| Saldo kas hitung | Kantong dana + kantong pajak menurut rumus sistem |
| Saldo kas BKU | Saldo bank + saldo tunai yang diisi operator dari BKU |
| Selisih saldo | Saldo kas BKU − saldo kas hitung |
| Sisa anggaran | Anggaran RKAS berlaku − realisasi neto kumulatif. **Berbeda dari saldo kas** dan selalu ditampilkan terpisah |
| Dokumen periodik | Dokumen yang tidak bulanan: RKAS disahkan, laporan tahap, laporan tahunan, berita acara |
| Terkunci | Laporan berstatus *selesai diperiksa*; tidak dapat diubah kecuali dibuka admin dinas |
| Tutup tahun | Tindakan admin yang mengunci seluruh tahun anggaran dan menetapkan saldo awal Januari tahun berikutnya |

### 4.2 Rumus saldo (aturan inti — WAJIB diuji)

Semua nilai per laporan bulanan (satu sekolah, satu bulan, satu sumber dana). Semua uang memakai presisi 2 desimal tanpa floating point.

```
realisasi_neto[j]     = realisasi_bruto[j] − pengembalian_belanja[j]        // per jenis belanja j
total_realisasi_neto  = Σ realisasi_neto[j]

saldo_akhir_dana  = saldo_awal_dana
                  + penerimaan_bos
                  + penerimaan_lain
                  − total_realisasi_neto
                  − penyetoran_kas_negara
                  + total_penyesuaian            // Σ penyesuaian bertanda

saldo_akhir_pajak = saldo_awal_pajak
                  + pajak_dipungut
                  − pajak_disetor

saldo_kas_hitung  = saldo_akhir_dana + saldo_akhir_pajak
saldo_kas_bku     = saldo_bank_bku + saldo_tunai_bku
selisih_saldo     = saldo_kas_bku − saldo_kas_hitung
```

Alasan pemisahan kantong pajak: realisasi dicatat bruto (termasuk pajak), sedangkan pajak yang dipungut tetap berada di kas sekolah sampai disetor. Tanpa kantong pajak, saldo hitung akan berselisih dengan BKU pada setiap bulan pajak dipungut tetapi belum disetor.

Aturan turunan:
1. `saldo_awal_dana` dan `saldo_awal_pajak` bulan M = saldo akhir bulan M−1 pada sumber dana yang sama. Tidak dapat diketik.
2. Januari tahun pertama: diisi manual operator, wajib diverifikasi pemeriksa sebelum laporan Januari dapat dinyatakan selesai.
3. Januari tahun berikutnya: sama dengan saldo akhir Desember tahun sebelumnya; ditetapkan saat tutup tahun dan bersifat baca-saja.
4. `pengembalian_belanja[j]` hanya dapat diisi pada jenis belanja yang memiliki realisasi kumulatif. Realisasi neto bulanan boleh negatif, tetapi **realisasi neto kumulatif per jenis tidak boleh negatif**.
5. Toleransi selisih = 0,00. Selisih apa pun ≠ 0 memerlukan penjelasan teks dan masuk daftar perhatian.
6. Penyesuaian wajib memiliki: jenis (`realisasi`, `penerimaan`, `lainnya`), jenis belanja bila jenisnya `realisasi`, rujukan bulan asal, nilai bertanda, dan alasan. Penyesuaian jenis `realisasi` ikut dihitung dalam realisasi neto kumulatif jenis belanja terkait.
7. Total dihitung dari rincian. Operator tidak pernah mengetik total.

**Contoh terhitung (golden test — Februari, BOS Reguler):**

| Butir | Nilai (Rp) |
|---|---|
| Saldo awal dana | 50.000.000,00 |
| Saldo awal pajak | 0,00 |
| Penerimaan BOS | 0,00 |
| Penerimaan lain (jasa giro) | 12.500,00 |
| Realisasi bruto barang dan jasa | 18.000.000,00 |
| Realisasi bruto modal | 6.000.000,00 |
| Pengembalian belanja barang dan jasa | 500.000,00 |
| Total realisasi neto | 23.500.000,00 |
| Penyetoran kas negara | 0,00 |
| Penyesuaian | 0,00 |
| Pajak dipungut | 1.200.000,00 |
| Pajak disetor | 900.000,00 |
| **Saldo akhir dana** | **26.512.500,00** |
| **Saldo akhir pajak** | **300.000,00** |
| **Saldo kas hitung** | **26.812.500,00** |
| Saldo bank BKU | 26.000.000,00 |
| Saldo tunai BKU | 812.500,00 |
| **Selisih** | **0,00** |

### 4.3 Agregasi antarperiode

| Jenis data | Cara agregasi triwulan / semester / tahun / kabupaten |
|---|---|
| Penerimaan, realisasi, pengembalian, penyetoran, pajak dipungut/disetor, penyesuaian | Dijumlahkan antarbulan |
| Saldo awal | Saldo awal bulan pertama periode |
| Saldo akhir, saldo kas | Posisi akhir bulan terakhir periode (tidak dijumlahkan antarbulan) |
| Agregat kabupaten untuk saldo | Σ saldo akhir tiap sekolah pada bulan akhir periode |
| Anggaran untuk persentase | Versi RKAS yang berlaku pada hari terakhir bulan akhir periode |
| Persentase realisasi | Realisasi neto kumulatif ÷ anggaran berlaku; tampil "–" bila anggaran 0 |

Rekap hanya menghitung laporan berstatus ≥ *diajukan* `[ASUMSI]`. Dasbor menampilkan label jumlah laporan yang belum masuk agar angka tidak disalahpahami sebagai lengkap.

### 4.4 Penguncian periode

1. Laporan *selesai diperiksa* = terkunci. Semua field angka dan dokumen tidak dapat diubah.
2. Laporan bulan M hanya dapat diajukan jika laporan bulan M−1 (sumber dana sama) berstatus ≥ *diajukan*. Bulan pertama sumber dana aktif dikecualikan.
3. Kesalahan yang ditemukan setelah terkunci **diperbaiki lewat penyesuaian di bulan berjalan**, bukan dengan mengubah bulan lama. Inilah jalur normal.
4. Buka kunci adalah jalur pengecualian: hanya `admin_dinas`, alasan wajib, status kembali ke *perlu perbaikan*.
5. Setelah laporan yang dibuka diajukan ulang, sistem menghitung ulang saldo awal bulan-bulan berikutnya. Laporan bulan berikutnya yang sudah selesai **tidak** berubah status otomatis, tetapi diberi bendera `perlu_tinjau_ulang` dan masuk daftar perhatian.
6. Tahun yang sudah ditutup tidak dapat dibuka kunci laporannya `[ASUMSI]`. Koreksi masuk sebagai penyesuaian Januari tahun berikutnya.

### 4.5 Entitas inti dan skema awal (rumah: dokumen 07)

Konvensi: nama tabel dan kolom bahasa Inggris snake_case; uang `DECIMAL(15,2)`; semua tabel punya `id` BIGINT, `created_at`, `updated_at`; soft delete hanya di tabel yang disebut.

**Referensi**

| Tabel | Kolom kunci | Catatan |
|---|---|---|
| `districts` | `name`, `code` | Kecamatan |
| `fund_sources` | `code` (unik), `name`, `is_active` | BOS Reguler, BOS Kinerja, dst. |
| `fiscal_years` | `year` (unik), `status` enum(`open`,`closed`), `default_deadline_day` (default 10), `closed_at`, `closed_by` | Tahun anggaran |
| `fiscal_year_stages` | `fiscal_year_id`, `stage_no`, `start_month`, `end_month`, `label` | Rentang bulan tiap tahap; untuk pembanding laporan tahap |
| `monthly_deadlines` | `fiscal_year_id`, `month`, `due_date` | Diisi dari default, dapat diubah per bulan |
| `expense_types` | `fiscal_year_id`, `code`, `arkas_account_code` nullable, `name`, `sort_order`, `is_active` | Unik (`fiscal_year_id`,`code`). **Tidak boleh diubah atau dihapus bila sudah dipakai laporan**; hanya dapat dinonaktifkan |
| `document_types` | `code` (unik), `name`, `scope` enum(`monthly`,`periodic`) | |
| `document_requirements` | `fiscal_year_id`, `document_type_id`, `report_kind` enum(`monthly`,`rkas`,`stage`,`annual`), `school_status` enum(`negeri`,`swasta`,`all`), `fund_source_id` nullable (null = semua), `is_required` | Matriks dokumen wajib |

**Sekolah dan akun**

| Tabel | Kolom kunci | Catatan |
|---|---|---|
| `schools` | `npsn` (unik, 8 digit), `name`, `level` enum(`SD`,`SMP`), `status` enum(`negeri`,`swasta`), `district_id`, `address`, `phone` nullable, `is_active` | Soft delete; tidak dapat dihapus bila punya laporan, hanya dinonaktifkan |
| `school_officials` | `school_id`, `role` enum(`kepala_sekolah`,`bendahara`,`operator`), `name`, `nip` nullable, `started_at`, `ended_at` nullable, `approved_by` nullable | Riwayat pengelola. Maksimal satu baris aktif (`ended_at` null) per `school_id` + `role` |
| `users` | `username` (unik), `name`, `password`, `school_id` nullable, `must_change_password`, `is_active`, `last_login_at` | Peran lewat paket peran |

**Anggaran**

| Tabel | Kolom kunci | Catatan |
|---|---|---|
| `allocations` | `school_id`, `fiscal_year_id`, `fund_source_id`, `amount`, `active_from_month` (1–12) | Unik (sekolah, tahun, sumber dana). Keberadaannya menentukan sumber dana aktif |
| `opening_balances` | `school_id`, `fiscal_year_id`, `fund_source_id`, `fund_amount`, `tax_amount`, `origin` enum(`manual`,`carried_over`), `verified_by`, `verified_at` | Saldo awal Januari |
| `budget_versions` | `school_id`, `fiscal_year_id`, `fund_source_id`, `version_no`, `effective_date`, `reason`, `document_id` nullable | Versi RKAS |
| `budget_lines` | `budget_version_id`, `expense_type_id`, `amount` | Unik (versi, jenis belanja) |

**Laporan bulanan**

| Tabel | Kolom kunci | Catatan |
|---|---|---|
| `monthly_reports` | `school_id`, `fiscal_year_id`, `fund_source_id`, `month`, `status`, `is_nil`, `opening_fund`, `opening_tax`, `receipt_bos`, `receipt_other`, `receipt_other_note`, `treasury_deposit`, `tax_withheld`, `tax_remitted`, `bank_balance_bku`, `cash_balance_bku`, `difference_note`, `principal_approved_by`, `principal_approved_at`, `submitted_at`, `reviewer_id`, `review_started_at`, `completed_at`, `needs_rereview`, `identity_snapshot` JSON, `lock_version` | Unik (sekolah, tahun, sumber dana, bulan). Nilai hitungan (saldo akhir, selisih) **tidak disimpan sebagai sumber kebenaran**; dihitung oleh satu kelas layanan. Boleh di-cache di kolom `computed_*` untuk dasbor, diperbarui setiap simpan |
| `report_expense_lines` | `monthly_report_id`, `expense_type_id`, `gross_amount`, `refund_amount` | Unik (laporan, jenis belanja) |
| `report_adjustments` | `monthly_report_id`, `kind` enum(`realisasi`,`penerimaan`,`lainnya`), `expense_type_id` nullable, `origin_month`, `origin_fiscal_year_id`, `amount` (bertanda), `reason` | |
| `status_histories` | `monthly_report_id`, `from_status`, `to_status`, `actor_id`, `note`, `created_at` | Hanya tambah, tidak pernah diubah |
| `review_notes` | `monthly_report_id`, `reviewer_id`, `target_type` enum(`field`,`document_type`,`general`), `target_key`, `body`, `response_body` nullable, `responded_at`, `resolved_at` | Catatan per butir dan tanggapan sekolah |

`identity_snapshot` menyimpan nama sekolah, NPSN, kepala sekolah, bendahara, dan operator **pada saat pengajuan**, sehingga laporan lama tetap menampilkan pengelola yang benar setelah pergantian.

**Dokumen**

| Tabel | Kolom kunci | Catatan |
|---|---|---|
| `periodic_reports` | `school_id`, `fiscal_year_id`, `fund_source_id`, `kind` enum(`rkas`,`stage`,`annual`,`minutes`), `stage_no` nullable, `reported_total_realization` nullable, `reported_ending_balance` nullable, `note` | Angka pembanding opsional; tidak pernah ditambahkan ke realisasi bulanan |
| `documents` | `documentable_type`, `documentable_id` (laporan bulanan / laporan periodik / versi RKAS), `current_version_id`, `uploaded_by` | Satu berkas logis |
| `document_versions` | `document_id`, `version_no`, `storage_path`, `original_name`, `size_bytes`, `sha256`, `uploaded_by`, `replaced_reason` nullable | Penggantian = versi baru; versi lama tidak pernah dihapus |
| `document_tags` | `document_id`, `document_type_id` | Satu PDF gabungan dapat bertanda beberapa jenis dokumen |

**Audit dan notifikasi**: tabel log aktivitas dari paket audit dan tabel `notifications` bawaan Laravel.

### 4.6 Data sensitif dan aturan data

| Data | Perlakuan |
|---|---|
| Kata sandi | Hash bawaan Laravel (bcrypt/argon2) |
| NIP pengelola | Data pribadi; tampil hanya untuk pengguna berhak, tidak masuk ekspor publik |
| PDF laporan keuangan | Disimpan di disk privat; diunduh hanya lewat controller ber-Policy; tidak ada URL publik langsung |
| Angka laporan | Tidak pernah dihapus permanen; perubahan tercatat di audit dengan nilai lama dan baru |
| Jejak audit | Hanya tambah; tidak ada UI hapus |
| Retensi | Tidak ada penghapusan otomatis `[ASUMSI]`; kebijakan arsip mengikuti ketentuan kearsipan daerah `[TERBUKA]` |

---

## 5. K5 — Stack dan Arsitektur → dokumen 08, 09, 12

### 5.1 Stack

| Lapisan | Pilihan |
|---|---|
| Bahasa | PHP ≥ 8.3 |
| Framework | Laravel versi stabil terbaru saat scaffold `[ASUMSI]`; versi dikunci dari `composer.json` |
| Basis data | MySQL 8.0, InnoDB, `utf8mb4` |
| Tampilan | Blade + Tailwind CSS v4 + Vite |
| Interaktivitas | Alpine.js untuk perhitungan langsung di formulir dan dialog `[ASUMSI]` |
| Peran dan izin | `spatie/laravel-permission` `[ASUMSI]` |
| Audit | `spatie/laravel-activitylog` `[ASUMSI]` |
| Aritmetika uang | `brick/math` (BigDecimal) `[ASUMSI]` |
| Ekspor XLSX | `openspout/openspout` `[ASUMSI]` |
| Antrean dan penjadwal | Driver `database` + `schedule:run` via cron |
| Tes | Pest `[ASUMSI]` (atau PHPUnit bila pemilik memilih) |

### 5.2 Teknologi terlarang

- jQuery.
- Framework JS SPA (React, Vue, Svelte) dan Livewire `[ASUMSI]`.
- Pustaka komponen UI eksternal di luar token desain.
- Tipe `float`/`double` untuk uang, baik di kolom basis data maupun aritmetika PHP.
- Penyimpanan PDF di folder publik.

### 5.3 Pola arsitektur

Monolit modular berlapis:
- **Controller tipis**: validasi lewat Form Request, otorisasi lewat Policy, lalu memanggil Action/Service.
- **Logika domain di Service/Action**, terutama:
  - `ReportBalanceCalculator` — satu-satunya tempat rumus §4.2;
  - `ReportStateMachine` — satu-satunya tempat transisi status §7;
  - `DocumentCompletenessChecker` — menilai dokumen wajib terhadap `document_requirements`;
  - `FiscalYearCloser` — tutup tahun dan pemindahan saldo.
- Status memakai PHP enum (`ReportStatus`); transisi tanpa paket state machine eksternal `[ASUMSI]`.
- Operasi multi-tabel (ajukan, buka kunci, tutup tahun, ganti dokumen) dibungkus transaksi basis data.

### 5.4 Integrasi eksternal

Tidak ada di MVP. Struktur `schools` disiapkan agar kelak dapat disinkronkan dengan Dapodik melalui NPSN.

### 5.5 Struktur folder dan penamaan `[ASUMSI]`

```
app/
  Enums/            ReportStatus, SchoolLevel, SchoolStatus, ...
  Http/Controllers/ Admin/, Review/, School/, Dashboard/
  Http/Requests/
  Models/
  Policies/
  Services/         ReportBalanceCalculator, ReportStateMachine, ...
  Actions/          SubmitReport, StartReview, RequestRevision, CompleteReview,
                    UnlockReport, ReplaceDocument, CloseFiscalYear
resources/views/
  layouts/  components/  admin/  review/  school/  dashboard/
```

Model PascalCase bahasa Inggris; kolom snake_case; label antarmuka bahasa Indonesia.

---

## 6. K6 — Lingkungan dan Operasi → dokumen 10, 11, 15

| Butir | Nilai |
|---|---|
| Server | Ubuntu Server 24.04 LTS, Nginx, PHP-FPM, MySQL 8 `[ASUMSI]` |
| Hosting | Server milik pemda `[TERBUKA]` |
| Penyimpanan berkas | Disk lokal `storage/app/private/documents/{tahun}/{npsn}/` |
| Cadangan | Basis data harian + direktori dokumen harian, retensi 30 hari `[ASUMSI]` |
| Antrean | Driver `database`; worker dijalankan supervisor |
| Penjadwal | Cron `* * * * * php artisan schedule:run`; tugas harian: tandai laporan terlambat, kirim notifikasi tenggat H-3 dan H+1 |
| Log | Log harian Laravel; log error terpisah; tidak mencatat isi PDF atau kata sandi |
| Batas unggah | 20 MB per berkas PDF `[ASUMSI]`; Nginx `client_max_body_size` disesuaikan |

Perintah utama:

```
composer install && npm install
php artisan migrate --seed
npm run dev            # pengembangan
npm run build          # produksi
php artisan serve
php artisan test
php artisan queue:work
php artisan schedule:run
```

Seeder wajib menyediakan: satu tahun anggaran, sumber dana BOS Reguler dan BOS Kinerja, contoh jenis belanja, jenis dokumen, matriks dokumen wajib, satu akun per peran, dua sekolah contoh (satu negeri, satu swasta). Data contoh berlabel jelas sebagai data uji.

---

## 7. Mesin status laporan bulanan

| Dari | Ke | Aktor | Syarat |
|---|---|---|---|
| `draf` | `diajukan` | operator | Validasi pengajuan lulus (§3.3 E); persetujuan kepsek bila aktif |
| `diajukan` | `draf` | operator | Belum diambil pemeriksa |
| `diajukan` | `dalam_pemeriksaan` | pemeriksa | Mengambil laporan; `reviewer_id` diisi |
| `dalam_pemeriksaan` | `perlu_perbaikan` | pemeriksa | Minimal satu catatan baru |
| `dalam_pemeriksaan` | `selesai_diperiksa` | pemeriksa | Semua catatan sebelumnya ditandai selesai; saldo awal Januari tahun pertama sudah terverifikasi |
| `dalam_pemeriksaan` | `diajukan` | pemeriksa | Melepas laporan tanpa keputusan |
| `perlu_perbaikan` | `diajukan` | operator | Setiap catatan terbuka sudah ditanggapi; validasi pengajuan lulus |
| `selesai_diperiksa` | `perlu_perbaikan` | admin_dinas | Buka kunci; alasan wajib; tahun belum ditutup |

Transisi lain ditolak oleh `ReportStateMachine`. Setiap transisi menulis `status_histories` dan log audit dalam satu transaksi.

Persetujuan kepala sekolah: pengaturan `require_principal_approval` per instalasi, default **mati** `[ASUMSI]`. Bila aktif, kepala sekolah menyetujui laporan berstatus draf; perubahan angka setelah disetujui membatalkan persetujuan.

Bendera tambahan (bukan status): `terlambat` (melewati `due_date` dan belum diajukan), `needs_rereview` (§4.4 butir 5).

---

## 8. Dokumen PDF — aturan perilaku

1. Satu laporan dapat menerima beberapa PDF. Setiap berkas wajib diberi minimal satu tanda jenis dokumen.
2. Satu PDF gabungan boleh diberi beberapa tanda sekaligus. Penandaan per halaman tidak didukung.
3. Nama berkas bebas. Identitas sekolah dan periode diambil dari konteks laporan yang sedang dibuka.
4. Validasi: MIME `application/pdf` diperiksa dari isi berkas (bukan hanya ekstensi), ukuran ≤ batas, berkas tidak kosong.
5. Hash SHA-256 dihitung. Bila hash sama sudah ada di laporan lain sekolah yang sama, tampilkan peringatan kemungkinan salah unggah (tidak memblokir).
6. Penggantian membuat versi baru dengan alasan opsional. Versi lama tetap dapat diunduh oleh pengguna berhak.
7. Berkas tidak dapat dihapus setelah laporan pernah diajukan. Sebelum pengajuan pertama, berkas dapat dibuang (soft delete).
8. Kelengkapan dinilai dari `document_requirements` sesuai jenis laporan, status sekolah, dan sumber dana.
9. Daftar jenis dokumen bulanan awal: laporan realisasi penerimaan dan belanja; Buku Kas Umum; SPTJM; rekapitulasi pembelian barang milik daerah; lampiran rekonsiliasi atau klarifikasi (tidak wajib).
10. Dokumen periodik: RKAS disahkan dan perubahannya; laporan realisasi Tahap I (dan tahap berikutnya bila ada); laporan keseluruhan tahun dan sisa dana; berita acara rekonsiliasi atau hasil pemeriksaan.
11. Angka di dokumen periodik **tidak** ditambahkan ke realisasi bulanan. Bila angka pembanding diisi, sistem menampilkan selisihnya terhadap Σ laporan bulanan pada rentang bulan tahap yang ditetapkan di `fiscal_year_stages`.

Matriks dokumen wajib final per status sekolah dan sumber dana: `[TERBUKA]`, diputuskan dinas sebelum Fase 3.

---

## 9. Pengawasan dinas

### 9.1 Dasbor kabupaten

- Kartu ringkas: pagu, penerimaan, realisasi neto, sisa anggaran, saldo kas, jumlah laporan per status.
- Tabel per sekolah: status bulan berjalan, realisasi kumulatif, persentase, selisih, kelengkapan dokumen.
- Filter: sekolah, jenjang, status negeri/swasta, kecamatan, tahun, bulan, sumber dana.
- Label cakupan data: "Berdasarkan X dari Y laporan yang sudah diajukan".

### 9.2 Daftar perhatian (aturan pemicu)

| Pemicu | Kondisi |
|---|---|
| Belum melapor | Lewat tenggat dan status masih `draf` |
| Dokumen kurang | Laporan diajukan tetapi pemeriksa menandai kurang, atau kelengkapan < 100% |
| Selisih saldo | `selisih_saldo` ≠ 0 |
| Perbaikan menggantung | Status `perlu_perbaikan` lebih dari 14 hari `[ASUMSI]` |
| Antrean pemeriksaan | Status `diajukan` lebih dari 14 hari tanpa diambil `[ASUMSI]` |
| Perlu tinjau ulang | `needs_rereview` = true |
| Anggaran melebihi dana | Anggaran RKAS berlaku > pagu + saldo awal tahun |
| Saldo awal belum diverifikasi | Tahun pertama, saldo awal Januari belum diverifikasi |

### 9.3 Rekap dan ekspor

- Rekap per sekolah dan kabupaten untuk triwulan, semester, dan tahun, mengikuti §4.3.
- Rekap penyelesaian pemeriksaan per bulan: jumlah diajukan, selesai, perlu perbaikan, rata-rata hari pemeriksaan.
- Ekspor XLSX untuk semua tabel rekap. Ekspor PDF tidak termasuk MVP `[ASUMSI]`.

---

## 10. K7 — Kualitas dan Keamanan → dokumen 13, 14, 21, 23, 24

### 10.1 Strategi tes

| Lapisan | Wajib diuji |
|---|---|
| Unit | `ReportBalanceCalculator` dengan contoh terhitung §4.2 dan kasus tepi: realisasi neto negatif bulanan, kumulatif negatif ditolak, penyesuaian bertanda, laporan nihil, sumber dana mulai pertengahan tahun |
| Unit | `ReportStateMachine`: setiap transisi sah lolos, setiap transisi tidak sah ditolak |
| Unit | Agregasi §4.3: saldo tidak dijumlahkan antarbulan |
| Feature | Isolasi sekolah: operator sekolah A mendapat 404 untuk laporan, PDF, dan rekap sekolah B |
| Feature | Siklus penuh draf → diajukan → dalam pemeriksaan → perlu perbaikan → diajukan → selesai |
| Feature | Buka kunci memicu hitung ulang saldo awal dan bendera tinjau ulang bulan berikutnya |
| Feature | Unggah PDF: tipe salah ditolak, versi baru tersimpan, versi lama tetap ada |
| Feature | Tutup tahun menghasilkan saldo awal Januari = saldo akhir Desember |

### 10.2 Penanganan galat

Pengguna melihat pesan ramah bahasa Indonesia tanpa jejak tumpukan. Detail teknis ditulis ke log. Galat validasi angka ditampilkan pada field terkait.

### 10.3 Aturan keamanan wajib

- HTTPS di produksi; cookie sesi `secure`, `httponly`, `samesite=lax`.
- Tidak ada registrasi mandiri. Akun dibuat admin dinas; sandi awal wajib diganti.
- Pembatasan percobaan login (mis. 5 kali per menit per username + IP).
- Sesi kedaluwarsa setelah 120 menit tidak aktif `[ASUMSI]`.
- Seluruh input lewat Form Request; seluruh akses objek lewat Policy.
- Rahasia hanya di `.env`; tidak pernah di repositori.
- PDF hanya dari disk privat lewat controller ber-otorisasi, dengan header `Content-Disposition` aman.
- Penguncian optimistis (`lock_version`) pada laporan untuk mencegah tulis-timpa saat dua orang membuka laporan yang sama.

### 10.4 Definisi diterima (pola per fitur)

Setiap fitur MVP dianggap diterima bila: kriteria terima di dokumen 23 lulus, tes terkait hijau, tidak ada regresi, Policy diuji untuk peran yang tidak berhak, dan keempat state antarmuka (kosong, memuat, gagal, sukses) tersedia.

---

## 11. K8 — Kebijakan Perubahan dan Risiko → dokumen 18, 20, 22, 25

### 11.1 Git

Branch per fitur; dilarang commit langsung ke `main`; merge setelah tes hijau `[ASUMSI]`.

### 11.2 Operasi irreversibel (wajib konfirmasi manusia)

| Operasi | Pengaman |
|---|---|
| Tutup tahun anggaran | Dialog konfirmasi dengan ringkasan; diblokir bila masih ada laporan Desember belum selesai, kecuali admin mengetik ulang tahun anggaran sebagai konfirmasi `[ASUMSI]` |
| Buka kunci laporan | Alasan wajib; tercatat di audit |
| Nonaktifkan sekolah atau sumber dana | Dialog konfirmasi; data tidak dihapus |
| Ubah jenis belanja yang sudah dipakai | Diblokir; hanya dapat dinonaktifkan |
| Migrasi destruktif basis data | Cadangan dulu, persetujuan pemilik |
| Hapus data laporan atau versi dokumen | Tidak tersedia di aplikasi |

### 11.3 Guardrail agen coding

- Rumus saldo hanya boleh ada di `ReportBalanceCalculator`. Menduplikasi rumus di Blade, JS, atau query rekap terlarang. Perhitungan langsung di formulir (Alpine.js) hanya pratinjau; nilai resmi selalu dari server.
- Tidak menyimpan saldo hitungan sebagai sumber kebenaran.
- Tidak memakai float untuk uang.
- Tidak menambah dependensi tanpa alasan tertulis.
- Tidak menonaktifkan Policy atau validasi demi tes hijau.

### 11.4 Langkah rilis

Tes hijau → migrasi diuji di salinan basis data → cadangan basis data dan dokumen → deploy → `migrate --force` → `optimize` → smoke test (login tiap peran, buka satu laporan, unduh satu PDF) → rencana rollback tersedia.

---

## 12. K9 — Antarmuka dan Pengalaman Pengguna → dokumen 26

Bagian ini adalah usulan awal untuk dikunci melalui wawancara K9 atau skill `desainer-vcbd`.

### 12.1 Jangkar desain `[ASUMSI]`

Tailwind CSS v4 dengan token sendiri; struktur **dasbor admin** untuk dinas dan **formulir terfokus** untuk sekolah; gaya netral formal pemerintahan, satu warna aksen, tanpa gradien.

### 12.2 Token awal `[ASUMSI]`

| Token | Nilai |
|---|---|
| Primer | `#1D4ED8` |
| Netral | skala slate Tailwind (`#F8FAFC` … `#0F172A`) |
| Sukses | `#15803D` |
| Peringatan | `#B45309` |
| Bahaya | `#B91C1C` |
| Info | `#0369A1` |
| Huruf | Inter; basis 14/20; angka tabel memakai `tabular-nums` |
| Radius | 8 px |
| Kepadatan | Ringkas untuk tabel dinas, nyaman untuk formulir sekolah |

Warna badge status: draf = netral; diajukan = info; dalam pemeriksaan = primer; perlu perbaikan = peringatan; selesai diperiksa = sukses; terlambat = bahaya.

### 12.3 Responsif

- Halaman sekolah (operator, kepsek): **mobile-first**, wajib layak di lebar 360 px. Formulir angka satu kolom di ponsel; input angka memakai `inputmode="decimal"`.
- Halaman dinas: desktop-first, tetap layak di ≤ 768 px; tabel lebar boleh gulir horizontal di dalam kontainernya.

### 12.4 Inventaris halaman → pola

| Halaman | Peran | Pola |
|---|---|---|
| Login, ganti sandi | semua | autentikasi |
| Beranda sekolah (12 bulan × sumber dana, status, tenggat) | operator, kepsek | dasbor |
| Formulir laporan bulanan | operator | formulir |
| Ringkasan dan ajukan | operator | detail |
| Dokumen laporan | operator | daftar + unggah |
| Anggaran RKAS dan versi | operator | daftar + formulir |
| Profil sekolah dan pengelola | operator, admin | detail + formulir |
| Dasbor kabupaten | admin, pemeriksa, pimpinan | dasbor |
| Daftar perhatian | admin, pemeriksa, pimpinan | daftar + filter |
| Antrean pemeriksaan | pemeriksa | daftar + filter |
| Layar pemeriksaan (angka di kiri, PDF di kanan di desktop; bertumpuk di ponsel) | pemeriksa | detail |
| Rekap dan ekspor | admin, pemeriksa, pimpinan | daftar + filter |
| Referensi, akun, sekolah, tahun anggaran, aturan dokumen | admin | daftar + formulir |

### 12.5 Mikroteks

- Bahasa Indonesia, nada formal dinas yang ramah.
- Rupiah: `Rp 26.812.500,00`. Tanggal: `22 Okt 2026`.
- Kata kerja tombol baku: Simpan, Ajukan, Tarik Kembali, Ambil untuk Diperiksa, Minta Perbaikan, Nyatakan Selesai, Buka Kunci, Unggah, Ganti Berkas, Ekspor.
- Sisa anggaran dan saldo kas selalu berlabel lengkap dan tidak pernah ditampilkan dalam satu angka gabungan.

### 12.6 Larangan UI

Nilai di luar token; gradien atau efek kaca; emoji; ikon dari set berbeda; animasi dekoratif; teks placeholder tersisa; aksi destruktif tanpa dialog konfirmasi.

---

## 13. Daftar `[TERBUKA]` yang perlu diputuskan

| No | Butir | Dibutuhkan sebelum |
|---|---|---|
| 1 | Kabupaten implementasi pertama dan nama resmi dinas | Fase 0 (seeder, kop tampilan) |
| 2 | Daftar jenis belanja resmi per tahun beserta kode rekening ARKAS | Fase 1 |
| 3 | Matriks dokumen wajib final per status sekolah dan sumber dana | Fase 3 |
| 4 | Tanggal tenggat resmi laporan bulanan | Fase 5 |
| 5 | Rentang bulan tiap tahap pada tahun anggaran berjalan | Fase 6 |
| 6 | Persetujuan kepala sekolah diaktifkan atau tidak | Fase 4 |
| 7 | Server hosting dan domain | Fase 8 |
| 8 | Kebijakan retensi arsip PDF | Sebelum produksi |
| 9 | Apakah pemeriksa dibagi per wilayah/sekolah atau melihat semua | Fase 4 |

---

## 14. Catatan perubahan dari rancangan awal

Perbaikan berikut sudah dimasukkan ke dosir ini hasil telaah rancangan awal:

1. **Kantong pajak** ditambahkan (pajak dipungut, pajak disetor, saldo pajak) agar saldo hitung sebanding dengan BKU.
2. **Saldo BKU dipecah** menjadi saldo bank dan saldo tunai.
3. **"Pengembalian dana" dipecah dua**: pengembalian belanja (mengurangi realisasi per jenis belanja) dan penyetoran ke kas negara/daerah (mengurangi saldo, bukan belanja). Rumus saldo ditulis eksplisit dengan contoh terhitung.
4. **Aturan penguncian periode**: laporan selesai terkunci; koreksi lewat penyesuaian bulan berjalan; buka kunci hanya admin dengan alasan; hitung ulang saldo dan bendera tinjau ulang.
5. **Saldo awal Januari**: manual dan diverifikasi hanya tahun pertama; selanjutnya otomatis lewat tutup tahun.
6. **Jenis belanja** menjadi referensi berversi per tahun yang dipetakan ke kode rekening ARKAS.
7. **Peran kepala sekolah** dan persetujuan pengajuan opsional ditambahkan.
8. **Tenggat per periode** dapat diatur dan menjadi dasar daftar perhatian.
9. **PDF**: hash SHA-256, deteksi unggahan ganda, penandaan di tingkat berkas, disk privat.
10. **Snapshot identitas pengelola** saat pengajuan agar laporan lama tidak berubah ketika pengelola berganti.
11. **Teknis**: uang `DECIMAL(15,2)` tanpa float, mesin status eksplisit, isolasi sekolah lewat Policy, jejak audit hanya tambah.
