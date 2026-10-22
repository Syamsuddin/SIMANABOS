# 24 — DEFINITION_OF_DONE

Sebuah task selesai bila SEMUA terpenuhi:

- [ ] Kriteria terima fitur terkait di `docs/23_ACCEPTANCE_CRITERIA.md` terpenuhi.
- [ ] `php artisan test` hijau seluruhnya; tes baru ditambahkan untuk perilaku baru (`docs/13_TESTING.md`).
- [ ] Setiap Policy yang disentuh diuji untuk peran berhak dan tak berhak.
- [ ] `./vendor/bin/pint --test` lulus.
- [ ] Tidak ada regresi pada golden case Februari.
- [ ] Struktur dan penamaan sesuai `docs/12_PROJECT_STRUCTURE.md`.
- [ ] Tidak melanggar `docs/20_GUARDRAILS.md` dan `docs/21_SECURITY_RULES.md`.
- [ ] Halaman baru memakai token dan komponen `docs/26_UI_CONVENTIONS.md`, dengan empat state.
- [ ] Migrasi baru memiliki `down()` dan sudah dijalankan bersih dengan `php artisan migrate:fresh --seed` lokal.
- [ ] Dokumen pemilik diperbarui bila fakta berubah, lalu `bash scripts/validate.sh` tanpa `[FAIL]`.
