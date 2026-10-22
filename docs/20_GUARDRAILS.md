# 20 — GUARDRAILS

Larangan keras untuk agen. Keamanan rinci: `docs/21_SECURITY_RULES.md`.

1. JANGAN menulis rumus saldo atau agregasi di luar `ReportBalanceCalculator` / `ReportAggregator` (termasuk Blade, JS, query rekap, ekspor).
2. JANGAN mengubah `status` laporan kecuali lewat `ReportStateMachine`.
3. JANGAN memakai `float`, `double`, `(float)`, `round()` pada uang; pakai `Money` + `brick/math`.
4. JANGAN memperlakukan `computed_*` sebagai sumber kebenaran atau mengisinya dari selain kalkulator.
5. JANGAN menghapus laporan, versi dokumen, riwayat status, atau log audit; tidak ada fitur hapus permanen.
6. JANGAN mengubah atau menghapus `expense_types` yang sudah dipakai laporan.
7. JANGAN menonaktifkan Policy, global scope, validasi, atau CSRF demi tes hijau.
8. JANGAN menghapus atau melemahkan tes; JANGAN mengubah golden case.
9. JANGAN menambah dependensi di luar `docs/09_STACK.md` tanpa alasan tertulis dan persetujuan.
10. JANGAN menulis nilai warna, ukuran, huruf, atau komponen UI di luar `docs/26_UI_CONVENTIONS.md`.
11. JANGAN menyimpan PDF di disk publik atau membuat URL unduh tanpa Policy.
12. JANGAN membangun fitur out-of-scope (`docs/02_SCOPE.md`), termasuk impor ARKAS dan OCR.
13. JANGAN menjalankan `migrate:fresh`, `db:wipe`, atau seeder di lingkungan selain lokal.
14. JANGAN memakai nama sekolah atau NIP nyata dalam seeder, factory, atau tes.
