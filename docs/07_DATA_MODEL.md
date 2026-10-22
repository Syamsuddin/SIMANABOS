# 07 — DATA_MODEL

Sumber kebenaran skema. Konvensi: MySQL 8 InnoDB `utf8mb4_unicode_ci`; semua tabel punya `id BIGINT UNSIGNED PK` dan `created_at`/`updated_at` kecuali disebut lain; uang `DECIMAL(15,2) NOT NULL DEFAULT 0` (tanda boleh negatif hanya di kolom yang disebut); enum disimpan sebagai `VARCHAR` + PHP enum di `app/Enums`. Istilah: `docs/04_DOMAIN_MODEL.md`.

## Referensi

### districts
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| code | VARCHAR(10) | UNIQUE | Kode kecamatan |
| name | VARCHAR(100) | NOT NULL | |

### fund_sources
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| code | VARCHAR(20) | UNIQUE | `REGULER`, `KINERJA` |
| name | VARCHAR(100) | NOT NULL | |
| is_active | BOOLEAN | DEFAULT 1 | |

### fiscal_years
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| year | SMALLINT UNSIGNED | UNIQUE | |
| status | VARCHAR(10) | `open`/`closed` | |
| default_deadline_day | TINYINT UNSIGNED | DEFAULT 10 | 1–28 |
| closed_at | TIMESTAMP | NULL | |
| closed_by | FK users | NULL | |

### fiscal_year_stages
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| fiscal_year_id | FK fiscal_years | NOT NULL | |
| stage_no | TINYINT UNSIGNED | UNIQUE(fiscal_year_id, stage_no) | |
| start_month / end_month | TINYINT UNSIGNED | 1–12, start ≤ end | |
| label | VARCHAR(50) | | "Tahap I" |

### monthly_deadlines
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| fiscal_year_id | FK | UNIQUE(fiscal_year_id, month) | |
| month | TINYINT UNSIGNED | 1–12 | Bulan laporan |
| due_date | DATE | NOT NULL | Zona Asia/Makassar |

### expense_types
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| fiscal_year_id | FK | UNIQUE(fiscal_year_id, code) | |
| code | VARCHAR(30) | NOT NULL | |
| arkas_account_code | VARCHAR(50) | NULL | `[TERBUKA]` daftar resmi |
| name | VARCHAR(150) | NOT NULL | |
| sort_order | SMALLINT | DEFAULT 0 | |
| is_active | BOOLEAN | DEFAULT 1 | Tidak boleh dihapus/ubah `code` bila dipakai |

### document_types
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| code | VARCHAR(30) | UNIQUE | `REALISASI`, `BKU`, `SPTJM`, `REKAP_BMD`, `LAMPIRAN`, `RKAS`, `TAHAP`, `TAHUNAN`, `BA` |
| name | VARCHAR(150) | NOT NULL | |
| scope | VARCHAR(10) | `monthly`/`periodic` | |

### document_requirements
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| fiscal_year_id | FK | NOT NULL | |
| document_type_id | FK | NOT NULL | |
| report_kind | VARCHAR(10) | `monthly`/`rkas`/`stage`/`annual` | |
| school_status | VARCHAR(10) | `negeri`/`swasta`/`all` | |
| fund_source_id | FK | NULL = semua | |
| is_required | BOOLEAN | DEFAULT 1 | |
UNIQUE(fiscal_year_id, document_type_id, report_kind, school_status, fund_source_id).

## Sekolah dan akun

### schools (soft delete)
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| npsn | CHAR(8) | UNIQUE | Hanya digit |
| name | VARCHAR(150) | NOT NULL | |
| level | VARCHAR(3) | `SD`/`SMP` | |
| status | VARCHAR(6) | `negeri`/`swasta` | |
| district_id | FK districts | NOT NULL | |
| address | VARCHAR(255) | NULL | |
| phone | VARCHAR(30) | NULL | |
| is_active | BOOLEAN | DEFAULT 1 | Nonaktif, bukan hapus, bila punya laporan |

### school_officials
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| school_id | FK | NOT NULL | |
| role | VARCHAR(20) | `kepala_sekolah`/`bendahara`/`operator` | |
| name | VARCHAR(150) | NOT NULL | |
| nip | CHAR(18) | NULL | Data pribadi |
| started_at | DATE | NOT NULL | |
| ended_at | DATE | NULL | |
| approved_by / approved_at | FK users / TIMESTAMP | NULL | Pengesahan admin |
Aturan aplikasi: ≤ 1 baris `ended_at IS NULL` per (school_id, role).

### users
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| username | VARCHAR(50) | UNIQUE | |
| name | VARCHAR(150) | NOT NULL | |
| password | VARCHAR(255) | NOT NULL | Hash |
| school_id | FK schools | NULL | Wajib untuk role sekolah |
| must_change_password | BOOLEAN | DEFAULT 1 | |
| is_active | BOOLEAN | DEFAULT 1 | |
| last_login_at | TIMESTAMP | NULL | |
| remember_token | VARCHAR(100) | NULL | |
Role: tabel bawaan `spatie/laravel-permission`.

## Anggaran

### allocations
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| school_id, fiscal_year_id, fund_source_id | FK | UNIQUE(ketiganya) | |
| amount | DECIMAL(15,2) | ≥ 0 | Pagu |
| active_from_month | TINYINT UNSIGNED | 1–12, DEFAULT 1 | |

### opening_balances
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| school_id, fiscal_year_id, fund_source_id | FK | UNIQUE(ketiganya) | |
| fund_amount | DECIMAL(15,2) | ≥ 0 | |
| tax_amount | DECIMAL(15,2) | ≥ 0 | |
| origin | VARCHAR(15) | `manual`/`carried_over` | `carried_over` baca-saja |
| verified_by / verified_at | FK users / TIMESTAMP | NULL | Wajib terisi untuk `manual` sebelum Januari selesai |

### budget_versions
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| school_id, fiscal_year_id, fund_source_id | FK | | |
| version_no | SMALLINT UNSIGNED | UNIQUE(school, year, fund, version_no) | Mulai 1 |
| effective_date | DATE | NOT NULL | |
| reason | VARCHAR(255) | NULL untuk versi 1 | |

### budget_lines
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| budget_version_id | FK | UNIQUE(budget_version_id, expense_type_id) | |
| expense_type_id | FK | NOT NULL | Tahun sama dengan versi |
| amount | DECIMAL(15,2) | ≥ 0 | |

## Laporan bulanan

### monthly_reports
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| school_id, fiscal_year_id, fund_source_id | FK | | |
| month | TINYINT UNSIGNED | UNIQUE(school, year, fund, month) | |
| status | VARCHAR(20) | `draf`/`diajukan`/`dalam_pemeriksaan`/`perlu_perbaikan`/`selesai_diperiksa` | INDEX(status) |
| is_nil | BOOLEAN | DEFAULT 0 | |
| opening_fund, opening_tax | DECIMAL(15,2) | | Diisi sistem saja |
| receipt_bos, receipt_other | DECIMAL(15,2) | ≥ 0 | |
| receipt_other_note | VARCHAR(255) | NULL | Wajib bila receipt_other > 0 |
| treasury_deposit | DECIMAL(15,2) | ≥ 0 | |
| tax_withheld, tax_remitted | DECIMAL(15,2) | ≥ 0 | |
| bank_balance_bku, cash_balance_bku | DECIMAL(15,2) | ≥ 0 | |
| difference_note | TEXT | NULL | Wajib bila selisih ≠ 0 saat ajukan |
| computed_ending_fund, computed_ending_tax, computed_difference | DECIMAL(15,2) | | **Cache** untuk dasbor; selalu ditulis ulang oleh `ReportBalanceCalculator`; bukan sumber kebenaran |
| principal_approved_by / principal_approved_at | FK users / TIMESTAMP | NULL | Dikosongkan bila angka berubah |
| submitted_at, review_started_at, completed_at | TIMESTAMP | NULL | |
| reviewer_id | FK users | NULL | |
| is_late | BOOLEAN | DEFAULT 0 | Bendera terlambat |
| needs_rereview | BOOLEAN | DEFAULT 0 | |
| identity_snapshot | JSON | NULL | Nama, NPSN, kepsek, bendahara, operator saat diajukan |
| lock_version | INT UNSIGNED | DEFAULT 0 | Penguncian optimistis |
INDEX(fiscal_year_id, month, status), INDEX(school_id, fiscal_year_id).

### report_expense_lines
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| monthly_report_id | FK cascade | UNIQUE(monthly_report_id, expense_type_id) | |
| expense_type_id | FK | NOT NULL | |
| gross_amount | DECIMAL(15,2) | ≥ 0 | |
| refund_amount | DECIMAL(15,2) | ≥ 0 | |

### report_adjustments
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| monthly_report_id | FK | NOT NULL | |
| kind | VARCHAR(12) | `realisasi`/`penerimaan`/`lainnya` | |
| expense_type_id | FK | Wajib bila kind=realisasi | |
| origin_fiscal_year_id | FK | NOT NULL | |
| origin_month | TINYINT UNSIGNED | 1–12 | |
| amount | DECIMAL(15,2) | ≠ 0, boleh negatif | |
| reason | VARCHAR(500) | NOT NULL | |

### status_histories (append-only; tanpa updated_at)
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| monthly_report_id | FK | INDEX | |
| from_status, to_status | VARCHAR(20) | | |
| actor_id | FK users | NOT NULL | |
| note | VARCHAR(500) | NULL | Alasan buka kunci wajib |
| created_at | TIMESTAMP | | |

### review_notes
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| monthly_report_id | FK | INDEX | |
| reviewer_id | FK users | NOT NULL | |
| target_type | VARCHAR(15) | `field`/`document_type`/`general` | |
| target_key | VARCHAR(50) | NULL | Nama field atau kode jenis dokumen |
| body | TEXT | NOT NULL | |
| response_body | TEXT | NULL | |
| responded_at, resolved_at | TIMESTAMP | NULL | |

## Dokumen

### periodic_reports
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| school_id, fiscal_year_id, fund_source_id | FK | | |
| kind | VARCHAR(10) | `rkas`/`stage`/`annual`/`minutes` | |
| stage_no | TINYINT UNSIGNED | Wajib bila kind=stage | |
| reported_total_realization | DECIMAL(15,2) | NULL | Pembanding; tidak masuk realisasi bulanan |
| reported_ending_balance | DECIMAL(15,2) | NULL | Pembanding |
| note | TEXT | NULL | |

### documents
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| documentable_type, documentable_id | morph | INDEX | MonthlyReport / PeriodicReport / BudgetVersion |
| current_version_id | FK document_versions | NULL | |
| uploaded_by | FK users | | |
| deleted_at | TIMESTAMP | NULL | Soft delete hanya sebelum pengajuan pertama |

### document_versions (append-only)
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| document_id | FK | UNIQUE(document_id, version_no) | |
| version_no | SMALLINT UNSIGNED | | |
| storage_path | VARCHAR(255) | NOT NULL | Disk `private` |
| original_name | VARCHAR(255) | | |
| size_bytes | INT UNSIGNED | | |
| sha256 | CHAR(64) | INDEX | Deteksi unggah ganda |
| uploaded_by | FK users | | |
| replaced_reason | VARCHAR(255) | NULL | |

### document_tags
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| document_id | FK cascade | UNIQUE(document_id, document_type_id) | |
| document_type_id | FK | | |

## Sistem
- `activity_log` (spatie/laravel-activitylog): perubahan angka simpan nilai lama dan baru.
- `notifications` (bawaan Laravel), `jobs`, `failed_jobs`, `sessions`, `cache`.
- `app_settings`: `key` UNIQUE, `value` JSON — memuat `require_principal_approval` (default false).

## Penyimpanan berkas
Disk `private` → `storage/app/private/documents/{year}/{npsn}/{uuid}.pdf`. Nama asli hanya di `original_name`.
