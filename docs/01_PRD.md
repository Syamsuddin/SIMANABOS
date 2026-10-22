# 01 — PRD

## Daftar fitur

| ID | Fitur | Deskripsi | Prioritas |
|---|---|---|---|
| F-01 | Autentikasi dan akun | Login username + sandi; akun dibuat admin; wajib ganti sandi saat login pertama | MVP |
| F-02 | Data referensi | Kecamatan, sumber dana, tahun anggaran, tahap, tenggat, jenis belanja per tahun, jenis dokumen, aturan dokumen wajib | MVP |
| F-03 | Sekolah dan pengelola | Profil NPSN; riwayat kepala sekolah, bendahara, operator | MVP |
| F-04 | Anggaran tahunan | Pagu alokasi, RKAS per jenis belanja berversi, saldo awal Januari | MVP |
| F-05 | Laporan bulanan | Input agregat; saldo dua kantong dan selisih terhadap BKU dihitung sistem | MVP |
| F-06 | Dokumen PDF | Unggah banyak atau gabungan, penandaan jenis, versi, cek kelengkapan | MVP |
| F-07 | Pengajuan dan pemeriksaan | Mesin status, catatan per butir, persetujuan kepala sekolah opsional | MVP |
| F-08 | Penguncian periode | Laporan selesai terkunci; buka kunci oleh admin dengan alasan | MVP |
| F-09 | Dokumen periodik | RKAS disahkan, laporan tahap, laporan tahunan, berita acara; angka pembanding opsional | MVP |
| F-10 | Dasbor kabupaten | Penerimaan, realisasi, saldo, status laporan, dengan filter | MVP |
| F-11 | Daftar perhatian | Daftar sekolah per pemicu masalah | MVP |
| F-12 | Tenggat dan pengingat | Tenggat per bulan dapat diatur; notifikasi in-app H-3 dan H+1 | MVP |
| F-13 | Rekap dan ekspor | Rekap triwulan, semester, tahun; ekspor XLSX | MVP |
| F-14 | Jejak audit | Semua perubahan angka, dokumen, status, buka kunci tercatat | MVP |
| F-15 | Tutup tahun | Mengunci tahun dan menetapkan saldo awal Januari tahun berikut | MVP |
| L-01 | Komponen penggunaan BOS | Pengelompokan realisasi kedua (perpustakaan, honor, dst.) | Nanti |
| L-02 | Notifikasi surel/WA | Pengingat di luar aplikasi | Nanti |
| L-03 | Sinkron Dapodik | Profil sekolah otomatis via NPSN | Nanti |
| L-04 | Tema gelap | Token gelap Pamong | Nanti |

## User story MVP

| ID | User story |
|---|---|
| F-01 | Sebagai admin dinas, saya ingin membuat akun per sekolah agar hanya pengelola resmi yang masuk. |
| F-02 | Sebagai admin dinas, saya ingin mengatur jenis belanja dan dokumen wajib per tahun agar aplikasi mengikuti juknis tahun berjalan. |
| F-03 | Sebagai operator, saya ingin mencatat pergantian bendahara agar laporan memuat pengelola yang benar pada waktunya. |
| F-04 | Sebagai operator, saya ingin mengisi anggaran RKAS per jenis belanja agar sisa anggaran dan persentase realisasi terhitung. |
| F-05 | Sebagai operator, saya ingin mengisi angka bulanan dan langsung melihat saldo hitung serta selisihnya terhadap BKU agar kesalahan ketahuan sebelum mengajukan. |
| F-06 | Sebagai operator, saya ingin mengunggah satu PDF gabungan dan menandai isinya agar tidak perlu memecah berkas. |
| F-07 | Sebagai pemeriksa, saya ingin memberi catatan pada butir tertentu agar sekolah tahu persis yang harus diperbaiki. |
| F-08 | Sebagai admin dinas, saya ingin laporan selesai tidak bisa diubah agar angka yang sudah diperiksa tetap stabil. |
| F-09 | Sebagai pemeriksa, saya ingin membandingkan total laporan Tahap I dengan jumlah laporan bulanan agar ketidaksesuaian terlihat. |
| F-10 | Sebagai pimpinan dinas, saya ingin melihat posisi dana BOS kabupaten per bulan agar dapat mengambil keputusan. |
| F-11 | Sebagai pemeriksa, saya ingin daftar sekolah yang bermasalah agar tindak lanjut terarah. |
| F-12 | Sebagai operator, saya ingin diingatkan menjelang tenggat agar tidak terlambat. |
| F-13 | Sebagai admin dinas, saya ingin mengekspor rekap triwulan ke Excel untuk bahan rapat. |
| F-14 | Sebagai pemeriksa, saya ingin melihat siapa mengubah angka apa dan kapan agar klarifikasi berdasar bukti. |
| F-15 | Sebagai admin dinas, saya ingin menutup tahun agar saldo Januari tahun berikut terisi otomatis dan tidak diketik ulang. |

## Kebutuhan non-fungsional
- Skala: ratusan sekolah × 12 bulan × ≤ 2 sumber dana per tahun; tidak ada kebutuhan skala khusus.
- Responsif: halaman sekolah layak di 360 px, halaman dinas desktop-first (rincian `docs/26_UI_CONVENTIONS.md`).
- Bahasa antarmuka: Bahasa Indonesia baku.
- Zona waktu aplikasi: `Asia/Makassar` (WITA).
- Kriteria terima per fitur: `docs/23_ACCEPTANCE_CRITERIA.md`.
