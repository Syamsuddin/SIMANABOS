# 12 — PROJECT_STRUCTURE

## Pohon folder penting

```
app/
  Actions/        Reports/{SubmitReport,WithdrawReport,StartReview,ReleaseReview,RequestRevision,
                  CompleteReview,UnlockReport,SaveReportFigures}.php
                  Documents/{UploadDocument,ReplaceDocument,TagDocument}.php
                  Budget/{CreateBudgetVersion,SetOpeningBalance,VerifyOpeningBalance}.php
                  FiscalYears/{OpenFiscalYear,CloseFiscalYear,GenerateMonthlyReports}.php
  Console/Commands/  RecomputeBalances.php, FlagLateReports.php, SendDeadlineReminders.php, CreateAdmin.php
  Enums/          ReportStatus, SchoolLevel, SchoolStatus, AdjustmentKind, ReportKind, OfficialRole
  Exceptions/     DomainRuleException.php, InvalidTransitionException.php
  Http/Controllers/  Admin/, Review/, School/, Dashboard/, DocumentDownloadController.php
  Http/Requests/
  Models/         + Scopes/SchoolScope.php
  Policies/
  Services/       ReportBalanceCalculator, ReportStateMachine, DocumentCompletenessChecker,
                  ReportAggregator, FiscalYearCloser, Money (format/parse Rupiah)
  Exports/        XLSX via openspout
resources/
  css/app.css     @import "tailwindcss" + @theme token (docs/26)
  js/app.js       Alpine
  views/
    components/pm/   komponen Blade Pamong (inventaris: docs/26)
    layouts/         app.blade.php (AppShell), guest.blade.php (login)
    admin/ review/ school/ dashboard/ auth/
public/img/logo-hss.png
database/migrations/ database/seeders/ database/factories/
tests/Unit/ tests/Feature/
```

## Konvensi penamaan
- Model, Action, Service: PascalCase bahasa Inggris (`MonthlyReport`, `SubmitReport`). Tabel dan kolom snake_case Inggris sesuai `docs/07_DATA_MODEL.md`.
- Nilai enum status memakai istilah Indonesia persis (`draf`, `dalam_pemeriksaan`, …) karena tampil di audit dan ekspor.
- Route bernama `{area}.{resource}.{aksi}`: `school.reports.edit`, `review.reports.complete`, `admin.fiscal-years.close`.
- Komponen Blade Pamong: `<x-pm.nama>` (`resources/views/components/pm/nama.blade.php`).
- Label antarmuka, pesan validasi, dan notifikasi: Bahasa Indonesia, di `lang/id/*.php`.

## Lokasi jenis kode
- Rumus saldo → `app/Services/ReportBalanceCalculator.php` saja.
- Transisi status → `app/Services/ReportStateMachine.php` saja.
- Operasi bisnis → `app/Actions/**`; Controller hanya memanggil Action.
- Format dan parse Rupiah (`26.512.500,00` ↔ `"26512500.00"`) → `app/Services/Money.php` saja.
- Query agregat dasbor dan rekap → `app/Services/ReportAggregator.php` saja.
