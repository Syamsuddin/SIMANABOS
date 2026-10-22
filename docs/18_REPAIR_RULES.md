# 18 — REPAIR_RULES

## Alur perbaikan bug
1. **Reproduksi** dengan tes gagal (Pest) yang menggambarkan bug; untuk bug angka, tulis kasus dengan nilai Rupiah konkret.
2. **Akar masalah**: telusuri sampai lapisan pemilik (`docs/08_ARCHITECTURE.md`). Bug angka hampir selalu berakar di `ReportBalanceCalculator`, `ReportAggregator`, atau `Money`.
3. **Perbaikan minimal** di lapisan pemilik, bukan tambalan di Blade atau Controller.
4. **Tes regresi** tetap di suite; jalankan seluruh tes.
5. Bila bug membuat cache `computed_*` salah di data yang sudah ada, tulis langkah `simanabos:recompute` di catatan rilis; jangan mengubah data produksi dengan SQL manual.

## Larangan
- Perbaikan tidak melebihi scope bug; refactor luas butuh izin.
- Jangan mengubah golden case atau rumus di `docs/04_DOMAIN_MODEL.md` untuk membuat tes lulus. Bila rumus memang salah, berhenti dan minta keputusan pemilik.
- Jangan membuka laporan terkunci atau mengubah status langsung lewat DB untuk "memperbaiki" data; gunakan alur buka kunci.
- Jangan mengubah skema (`docs/07_DATA_MODEL.md`) sebagai bagian perbaikan bug tanpa mengikuti rute ubah skema.
