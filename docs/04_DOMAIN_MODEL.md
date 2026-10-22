# 04 — DOMAIN_MODEL

Pemilik istilah domain, rumus saldo, dan aturan agregasi. Skema fisik ada di `docs/07_DATA_MODEL.md`.

## Glosarium

| Istilah | Makna kanonik | Identifier |
|---|---|---|
| Sumber dana | Jenis dana BOS (BOS Reguler, BOS Kinerja); dilaporkan terpisah | `FundSource` |
| Laporan bulanan | Satu laporan per sekolah × tahun anggaran × bulan × sumber dana | `MonthlyReport` |
| Laporan nihil | Laporan tanpa penerimaan dan realisasi; tetap wajib diajukan | `is_nil` |
| Pagu alokasi | Dana yang dialokasikan untuk sekolah per tahun dan sumber dana; menentukan sumber dana aktif | `Allocation` |
| Anggaran RKAS | Rencana belanja per jenis belanja; boleh memakai sisa tahun lalu; terpisah dari pagu | `BudgetVersion`, `BudgetLine` |
| Versi RKAS | Set anggaran dengan tanggal berlaku; perubahan = versi baru | `version_no`, `effective_date` |
| Jenis belanja | Klasifikasi belanja per tahun anggaran, dipetakan ke kode rekening ARKAS | `ExpenseType` |
| Realisasi bruto | Belanja yang dibukukan bulan itu per jenis belanja | `gross_amount` |
| Pengembalian belanja | Uang belanja kembali ke rekening sekolah; **mengurangi realisasi** jenis asal | `refund_amount` |
| Realisasi neto | Bruto − pengembalian belanja (+ penyesuaian jenis `realisasi`) | dihitung |
| Penyetoran ke kas negara/daerah | Setor sisa dana atau temuan; **mengurangi saldo, bukan belanja** | `treasury_deposit` |
| Penyesuaian | Koreksi bertanda dengan alasan untuk kesalahan di bulan terkunci; dicatat di bulan berjalan dengan rujukan bulan asal | `ReportAdjustment` |
| Kantong dana | Saldo dana BOS milik sekolah | `fund` |
| Kantong pajak | Pajak dipungut bendahara, belum disetor; bukan milik sekolah | `tax` |
| Saldo kas hitung | Kantong dana + kantong pajak menurut rumus | dihitung |
| Saldo kas BKU | Saldo bank + saldo tunai dari BKU, diisi operator | `bank_balance_bku`, `cash_balance_bku` |
| Selisih saldo | Saldo kas BKU − saldo kas hitung | dihitung |
| Sisa anggaran | Anggaran RKAS berlaku − realisasi neto kumulatif. **Bukan saldo kas**; selalu ditampilkan terpisah | dihitung |
| Dokumen periodik | Dokumen tidak bulanan: RKAS disahkan, laporan tahap, tahunan, berita acara | `PeriodicReport` |
| Terkunci | Laporan berstatus `selesai_diperiksa` | — |
| Tinjau ulang | Bendera laporan selesai yang saldo awalnya berubah akibat buka kunci bulan sebelumnya | `needs_rereview` |
| Tutup tahun | Mengunci tahun dan menetapkan saldo awal Januari tahun berikut | `FiscalYear.status=closed` |

## Rumus saldo (aturan inti; rumah tunggal di kode: `App\Services\ReportBalanceCalculator`)

Per laporan bulanan. Uang presisi 2 desimal, aritmetika `Brick\Math\BigDecimal`, tanpa float.

```
realisasi_neto[j]    = gross[j] − refund[j] + Σ penyesuaian(kind=realisasi, j)
total_realisasi_neto = Σ_j realisasi_neto[j]
penyesuaian_lain     = Σ penyesuaian(kind ∈ {penerimaan, lainnya})

saldo_akhir_dana  = opening_fund + receipt_bos + receipt_other
                  − total_realisasi_neto − treasury_deposit + penyesuaian_lain
saldo_akhir_pajak = opening_tax + tax_withheld − tax_remitted
saldo_kas_hitung  = saldo_akhir_dana + saldo_akhir_pajak
saldo_kas_bku     = bank_balance_bku + cash_balance_bku
selisih_saldo     = saldo_kas_bku − saldo_kas_hitung
```

Tanda penyesuaian: nilai positif `realisasi` menambah belanja; nilai positif `penerimaan`/`lainnya` menambah saldo.

Aturan:
1. `opening_*` bulan M = saldo akhir bulan M−1 (sumber dana sama). Bulan pertama sumber dana aktif dan Januari mengambil dari `opening_balances`.
2. `opening_balances` tahun pertama diisi manual dan wajib diverifikasi pemeriksa; tahun berikutnya ditetapkan saat tutup tahun (`origin=carried_over`) dan baca-saja.
3. Realisasi neto kumulatif per jenis belanja tidak boleh < 0. Realisasi neto bulanan boleh negatif.
4. Toleransi selisih = 0,00. Selisih ≠ 0 mewajibkan `difference_note`.
5. Total selalu dihitung dari rincian; tidak ada input total.

### Golden case (wajib menjadi tes)

| Masukan Februari, BOS Reguler | Rp |
|---|---|
| opening_fund / opening_tax | 50.000.000,00 / 0,00 |
| receipt_bos / receipt_other | 0,00 / 12.500,00 |
| gross barang-jasa / modal | 18.000.000,00 / 6.000.000,00 |
| refund barang-jasa | 500.000,00 |
| treasury_deposit / penyesuaian | 0,00 / 0,00 |
| tax_withheld / tax_remitted | 1.200.000,00 / 900.000,00 |
| bank_balance_bku / cash_balance_bku | 26.000.000,00 / 812.500,00 |
| **Hasil:** total_realisasi_neto | 23.500.000,00 |
| saldo_akhir_dana / saldo_akhir_pajak | 26.512.500,00 / 300.000,00 |
| saldo_kas_hitung / selisih_saldo | 26.812.500,00 / 0,00 |

## Aturan agregasi antarperiode

| Data | Agregasi triwulan, semester, tahun, kabupaten |
|---|---|
| Penerimaan, realisasi, pengembalian, penyetoran, pajak, penyesuaian | Dijumlahkan antarbulan |
| Saldo awal | Saldo awal bulan pertama periode |
| Saldo akhir, saldo kas, selisih | Posisi bulan terakhir periode; **tidak pernah dijumlahkan antarbulan** |
| Saldo tingkat kabupaten | Σ saldo akhir tiap sekolah pada bulan terakhir periode |
| Anggaran untuk persentase | Versi RKAS berlaku pada hari terakhir bulan akhir periode |
| Persentase realisasi | Neto kumulatif ÷ anggaran; tampil "–" bila anggaran 0 |
| Cakupan | Hanya laporan berstatus ≥ `diajukan`; tampilkan "X dari Y laporan" |

## Entitas dan relasi konseptual
- Sekolah memiliki banyak pengelola (berwaktu), alokasi per tahun × sumber dana, versi RKAS, dan laporan bulanan.
- Laporan bulanan memiliki rincian per jenis belanja, penyesuaian, dokumen, catatan pemeriksaan, dan riwayat status.
- Dokumen milik laporan bulanan, laporan periodik, atau versi RKAS; setiap dokumen memiliki versi dan tanda jenis dokumen.
- Tahun anggaran memiliki tahap, tenggat bulanan, jenis belanja, dan aturan dokumen wajib.
