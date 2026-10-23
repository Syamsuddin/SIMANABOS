<?php

use App\Actions\Budget\SaveAllocation;
use App\Actions\Reports\AddReviewNote;
use App\Actions\Reports\ApproveByPrincipal;
use App\Actions\Reports\CompleteReview;
use App\Actions\Reports\ReleaseReview;
use App\Actions\Reports\RequestRevision;
use App\Actions\Reports\ResolveNote;
use App\Actions\Reports\RespondToNote;
use App\Actions\Reports\SaveReportFigures;
use App\Actions\Reports\StartReview;
use App\Actions\Reports\SubmitReport;
use App\Actions\Reports\UnlockReport;
use App\Actions\Reports\WithdrawReport;
use App\Actions\Schools\ProposeOfficial;
use App\Enums\ReportStatus;
use App\Enums\RoleName;
use App\Exceptions\DomainRuleException;
use App\Exceptions\InvalidTransitionException;
use App\Models\AppSetting;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\MonthlyReport;
use App\Models\OpeningBalance;
use App\Models\School;
use App\Models\SchoolOfficial;
use App\Services\ReportStateMachine;

beforeEach(function () {
    $this->school = School::factory()->create();
    $this->year = FiscalYear::factory()->create(['year' => 2034]);
    $this->fund = FundSource::query()->create(['code' => 'BOS_REG', 'name' => 'BOS Reguler']);
    (new SaveAllocation)($this->school, $this->year, $this->fund, '100000000.00', 1);

    $this->operator = userWithRole(RoleName::OperatorSekolah, ['school_id' => $this->school->id]);
    $this->principal = userWithRole(RoleName::KepalaSekolah, ['school_id' => $this->school->id]);
    $this->examiner = userWithRole(RoleName::PemeriksaDinas);
    $this->admin = userWithRole(RoleName::AdminDinas);
});

function smReport($test, int $month = 1): MonthlyReport
{
    $report = MonthlyReport::query()->withoutGlobalScopes()->where('fiscal_year_id', $test->year->id)->where('month', $month)->firstOrFail();

    return app(SaveReportFigures::class)($report, ['bank_balance_bku' => '0.00', 'cash_balance_bku' => '0.00'], [], $report->lock_version);
}

function smFresh(MonthlyReport $report): MonthlyReport
{
    return MonthlyReport::query()->withoutGlobalScopes()->findOrFail($report->id);
}

function smToReview($test, MonthlyReport $report): MonthlyReport
{
    $report = app(SubmitReport::class)($report, $test->operator);

    return app(StartReview::class)($report, $test->examiner);
}

it('StateMachine: siklus lengkap draf → selesai → buka kunci menulis riwayat tiap langkah', function () {
    $report = app(SubmitReport::class)(smReport($this), $this->operator);
    expect($report->status)->toBe(ReportStatus::Diajukan)
        ->and($report->submitted_at)->not->toBeNull()
        ->and($report->identity_snapshot['npsn'])->toBe($this->school->npsn)
        ->and($report->identity_snapshot['operator'])->toBe($this->operator->name);

    $report = app(StartReview::class)($report, $this->examiner);
    expect($report->reviewer_id)->toBe($this->examiner->id);

    $note = app(AddReviewNote::class)($report, $this->examiner, 'field', 'receipt_bos', 'Cocokkan dengan BKU.');
    $report = app(RequestRevision::class)($report, $this->examiner);
    expect($report->status)->toBe(ReportStatus::PerluPerbaikan)->and($report->isEditable())->toBeTrue();

    app(RespondToNote::class)($note, 'Sudah diperbaiki.');
    $report = app(SubmitReport::class)($report, $this->operator);
    $report = app(StartReview::class)($report, $this->examiner);
    app(ResolveNote::class)($note->refresh(), $this->examiner);
    $report = app(CompleteReview::class)($report, $this->examiner);
    expect($report->status)->toBe(ReportStatus::SelesaiDiperiksa)->and($report->completed_at)->not->toBeNull();

    $report = app(UnlockReport::class)($report, $this->admin, 'Salah input penerimaan.');
    expect($report->status)->toBe(ReportStatus::PerluPerbaikan)->and($report->completed_at)->toBeNull();

    $history = $report->statusHistories()->get();
    expect($history->map(fn ($h) => $h->from_status->value.'>'.$h->to_status->value)->all())->toBe([
        'draf>diajukan', 'diajukan>dalam_pemeriksaan', 'dalam_pemeriksaan>perlu_perbaikan', 'perlu_perbaikan>diajukan',
        'diajukan>dalam_pemeriksaan', 'dalam_pemeriksaan>selesai_diperiksa', 'selesai_diperiksa>perlu_perbaikan',
    ])->and($history->last()->note)->toBe('Salah input penerimaan.');
});

it('StateMachine: semua kombinasi di luar tabel melempar InvalidTransitionException', function () {
    $machine = app(ReportStateMachine::class);
    $valid = [
        'draf>diajukan', 'diajukan>draf', 'diajukan>dalam_pemeriksaan', 'dalam_pemeriksaan>diajukan',
        'dalam_pemeriksaan>perlu_perbaikan', 'dalam_pemeriksaan>selesai_diperiksa', 'perlu_perbaikan>diajukan', 'selesai_diperiksa>perlu_perbaikan',
    ];
    $report = smReport($this);

    foreach (ReportStatus::cases() as $from) {
        foreach (ReportStatus::cases() as $to) {
            if (in_array($from->value.'>'.$to->value, $valid, true)) {
                expect($machine->canTransition($from, $to))->toBeTrue();

                continue;
            }
            $report->status = $from;
            expect(fn () => $machine->transition($report, $to, $this->admin))->toThrow(InvalidTransitionException::class);
        }
    }
});

it('StateMachine: aktor berperan salah ditolak', function () {
    $report = smReport($this);

    expect(fn () => app(SubmitReport::class)($report, $this->examiner))->toThrow(DomainRuleException::class);
    expect(fn () => app(SubmitReport::class)($report, userWithRole(RoleName::OperatorSekolah, ['school_id' => School::factory()->create()->id])))->toThrow(DomainRuleException::class);
    expect(smFresh($report)->status)->toBe(ReportStatus::Draf);
});

it('StateMachine: pengajuan ditolak bila validasi gagal dan status tetap draf', function () {
    $report = MonthlyReport::query()->withoutGlobalScopes()->where('fiscal_year_id', $this->year->id)->where('month', 2)->firstOrFail();

    expect(fn () => app(SubmitReport::class)($report, $this->operator))->toThrow(DomainRuleException::class);
    expect(smFresh($report)->status)->toBe(ReportStatus::Draf)->and($report->statusHistories()->count())->toBe(0);
});

it('Klaim bersamaan: dua pemeriksa mengambil laporan sama, hanya satu berhasil', function () {
    $submitted = app(SubmitReport::class)(smReport($this), $this->operator);
    $other = userWithRole(RoleName::PemeriksaDinas);
    $copyA = smFresh($submitted);
    $copyB = smFresh($submitted);

    app(StartReview::class)($copyA, $this->examiner);

    expect(fn () => app(StartReview::class)($copyB, $other))->toThrow(InvalidTransitionException::class);
    expect(smFresh($submitted)->reviewer_id)->toBe($this->examiner->id);
});

it('Klaim: lock_version basi dengan status sama ditolak sebagai konflik', function () {
    $submitted = app(SubmitReport::class)(smReport($this), $this->operator);
    $stale = smFresh($submitted);
    MonthlyReport::query()->withoutGlobalScopes()->whereKey($submitted->id)->increment('lock_version');

    expect(fn () => app(StartReview::class)($stale, $this->examiner))->toThrow(DomainRuleException::class);
});

it('Tarik kembali: boleh sebelum diambil, ditolak sesudahnya', function () {
    $report = app(SubmitReport::class)(smReport($this), $this->operator);
    expect(app(WithdrawReport::class)($report, $this->operator)->status)->toBe(ReportStatus::Draf);

    $report = smToReview($this, smFresh($report));
    expect(fn () => app(WithdrawReport::class)($report, $this->operator))->toThrow(InvalidTransitionException::class);
});

it('Pemeriksa lain tidak dapat melepas, mencatat, atau menyelesaikan laporan yang bukan miliknya', function () {
    $report = smToReview($this, smReport($this));
    $other = userWithRole(RoleName::PemeriksaDinas);

    expect(fn () => app(ReleaseReview::class)($report, $other))->toThrow(DomainRuleException::class);
    expect(fn () => app(AddReviewNote::class)($report, $other, 'general', null, 'x'))->toThrow(DomainRuleException::class);
    expect(fn () => app(CompleteReview::class)(smFresh($report), $other))->toThrow(DomainRuleException::class);

    $released = app(ReleaseReview::class)(smFresh($report), $this->examiner);
    expect($released->status)->toBe(ReportStatus::Diajukan)->and($released->reviewer_id)->toBeNull();
});

it('Minta perbaikan tanpa catatan baru ditolak', function () {
    $report = smToReview($this, smReport($this));

    expect(fn () => app(RequestRevision::class)($report, $this->examiner))->toThrow(DomainRuleException::class);
});

it('Ajukan ulang ditolak selama catatan terbuka belum ditanggapi', function () {
    $report = smToReview($this, smReport($this));
    $note = app(AddReviewNote::class)($report, $this->examiner, 'general', null, 'Lengkapi.');
    $report = app(RequestRevision::class)(smFresh($report), $this->examiner);

    expect(fn () => app(SubmitReport::class)($report, $this->operator))->toThrow(DomainRuleException::class);

    app(RespondToNote::class)($note, 'Sudah.');
    expect(app(SubmitReport::class)(smFresh($report), $this->operator)->status)->toBe(ReportStatus::Diajukan);
});

it('Nyatakan selesai ditolak selama masih ada catatan terbuka', function () {
    $report = smToReview($this, smReport($this));
    $note = app(AddReviewNote::class)($report, $this->examiner, 'general', null, 'Cek lagi.');

    expect(fn () => app(CompleteReview::class)($report, $this->examiner))->toThrow(DomainRuleException::class);

    app(ResolveNote::class)($note, $this->examiner);
    expect(app(CompleteReview::class)(smFresh($report), $this->examiner)->status)->toBe(ReportStatus::SelesaiDiperiksa);
});

it('Bulan pertama tidak selesai bila saldo awal manual belum diverifikasi', function () {
    $opening = OpeningBalance::query()->withoutGlobalScopes()->create([
        'school_id' => $this->school->id, 'fiscal_year_id' => $this->year->id, 'fund_source_id' => $this->fund->id,
        'fund_amount' => '0.00', 'tax_amount' => '0.00', 'origin' => OpeningBalance::ORIGIN_MANUAL,
    ]);
    $report = smToReview($this, smReport($this));

    expect(fn () => app(CompleteReview::class)($report, $this->examiner))->toThrow(DomainRuleException::class);

    $opening->forceFill(['verified_by' => $this->examiner->id, 'verified_at' => now()])->save();
    expect(app(CompleteReview::class)(smFresh($report), $this->examiner)->status)->toBe(ReportStatus::SelesaiDiperiksa);
});

it('Buka kunci: alasan wajib dan tahun tertutup ditolak', function () {
    $report = app(CompleteReview::class)(smToReview($this, smReport($this)), $this->examiner);

    expect(fn () => app(UnlockReport::class)($report, $this->admin, ''))->toThrow(DomainRuleException::class);

    $this->year->forceFill(['status' => 'closed'])->save();
    expect(fn () => app(UnlockReport::class)(smFresh($report), $this->admin, 'Koreksi'))->toThrow(DomainRuleException::class);
});

it('Persetujuan kepsek aktif: ajukan ditolak tanpa persetujuan, perubahan angka membatalkannya', function () {
    AppSetting::put('require_principal_approval', true);
    $report = smReport($this);

    expect(fn () => app(SubmitReport::class)($report, $this->operator))->toThrow(DomainRuleException::class);

    $report = app(ApproveByPrincipal::class)($report, $this->principal);
    expect($report->principal_approved_at)->not->toBeNull();

    $changed = app(SaveReportFigures::class)(smFresh($report), ['receipt_bos' => '5.00', 'difference_note' => 'x'], [], smFresh($report)->lock_version);
    expect($changed->principal_approved_at)->toBeNull()->and($changed->principal_approved_by)->toBeNull();

    $same = app(ApproveByPrincipal::class)($changed, $this->principal);
    $kept = app(SaveReportFigures::class)(smFresh($same), ['receipt_bos' => '5.00'], [], smFresh($same)->lock_version);
    expect($kept->principal_approved_at)->not->toBeNull();

    expect(app(SubmitReport::class)($kept, $this->operator)->status)->toBe(ReportStatus::Diajukan);
});

it('Persetujuan kepsek nonaktif: tombol setuju ditolak', function () {
    expect(fn () => app(ApproveByPrincipal::class)(smReport($this), $this->principal))->toThrow(DomainRuleException::class);
});

it('StateMachine: identity_snapshot tetap memuat bendahara lama setelah pergantian (docs/23 F-03)', function () {
    SchoolOfficial::factory()->create(['school_id' => $this->school->id, 'name' => 'Bendahara Lama', 'started_at' => '2020-01-01']);
    $report = app(SubmitReport::class)(smReport($this), $this->operator);
    expect($report->identity_snapshot['treasurer'])->toBe('Bendahara Lama');

    app(ProposeOfficial::class)($this->school, ['role' => 'bendahara', 'name' => 'Bendahara Baru', 'started_at' => now()->toDateString()], $this->admin);

    expect($this->school->officials()->withoutGlobalScopes()->active()->where('role', 'bendahara')->value('name'))->toBe('Bendahara Baru')
        ->and(smFresh($report)->identity_snapshot['treasurer'])->toBe('Bendahara Lama');
});
