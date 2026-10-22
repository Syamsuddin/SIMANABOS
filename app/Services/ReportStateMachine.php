<?php

namespace App\Services;

use App\Enums\OfficialRole;
use App\Enums\ReportStatus;
use App\Enums\RoleName;
use App\Exceptions\DomainRuleException;
use App\Exceptions\InvalidTransitionException;
use App\Models\AppSetting;
use App\Models\MonthlyReport;
use App\Models\OpeningBalance;
use App\Models\StatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Rumah tunggal transisi status laporan (docs/06 §D). Tabel transisi, aktor, dan guard ada di sini;
 * Action hanya menerjemahkan niat pengguna ke pemanggilan `transition()`.
 */
class ReportStateMachine
{
    /** @var array<string, RoleName> dari→ke ⇒ aktor yang berwenang */
    private const TRANSITIONS = [
        'draf>diajukan' => RoleName::OperatorSekolah,
        'diajukan>draf' => RoleName::OperatorSekolah,
        'diajukan>dalam_pemeriksaan' => RoleName::PemeriksaDinas,
        'dalam_pemeriksaan>diajukan' => RoleName::PemeriksaDinas,
        'dalam_pemeriksaan>perlu_perbaikan' => RoleName::PemeriksaDinas,
        'dalam_pemeriksaan>selesai_diperiksa' => RoleName::PemeriksaDinas,
        'perlu_perbaikan>diajukan' => RoleName::OperatorSekolah,
        'selesai_diperiksa>perlu_perbaikan' => RoleName::AdminDinas,
    ];

    public function __construct(private readonly ReportSubmissionValidator $submissionValidator) {}

    public function canTransition(ReportStatus $from, ReportStatus $to): bool
    {
        return isset(self::TRANSITIONS[$from->value.'>'.$to->value]);
    }

    /**
     * @throws InvalidTransitionException transisi tidak ada di tabel atau status sudah berubah
     * @throws DomainRuleException guard gagal atau laporan diubah pihak lain
     */
    public function transition(MonthlyReport $report, ReportStatus $to, User $actor, ?string $note = null): MonthlyReport
    {
        $from = $report->status;
        $role = self::TRANSITIONS[$from->value.'>'.$to->value] ?? throw new InvalidTransitionException($from, $to);

        if (! $actor->hasRole($role->value)) {
            throw new DomainRuleException([__('domain.report.unauthorized_actor')]);
        }

        return DB::transaction(function () use ($report, $from, $to, $actor, $note) {
            // Klaim atomik: hanya satu penulis yang lolos untuk (status, lock_version) yang sama.
            $claimed = MonthlyReport::query()->withoutGlobalScopes()
                ->whereKey($report->id)->where('status', $from->value)->where('lock_version', $report->lock_version)
                ->update(['lock_version' => $report->lock_version + 1]);

            if ($claimed === 0) {
                $current = MonthlyReport::query()->withoutGlobalScopes()->whereKey($report->id)->toBase()->value('status');
                if ($current !== $from->value) {
                    throw new InvalidTransitionException($from, $to);
                }
                throw new DomainRuleException([__('domain.report.stale')]);
            }

            $fresh = MonthlyReport::query()->withoutGlobalScopes()->with('fiscalYear')->findOrFail($report->id);
            $changes = $this->guardAndPrepare($fresh, $from, $to, $actor, $note);

            $fresh->forceFill(['status' => $to->value, ...$changes])->save();

            StatusHistory::query()->create([
                'monthly_report_id' => $fresh->id,
                'from_status' => $from->value,
                'to_status' => $to->value,
                'actor_id' => $actor->id,
                'note' => $note,
            ]);

            return $fresh;
        });
    }

    /** @return array<string, mixed> kolom tambahan yang ditulis bersama perubahan status */
    private function guardAndPrepare(MonthlyReport $report, ReportStatus $from, ReportStatus $to, User $actor, ?string $note): array
    {
        return match ($from->value.'>'.$to->value) {
            'draf>diajukan', 'perlu_perbaikan>diajukan' => $this->prepareSubmit($report, $from, $actor),
            'diajukan>draf' => $this->prepareWithdraw($report),
            'diajukan>dalam_pemeriksaan' => $this->prepareClaim($report, $actor),
            'dalam_pemeriksaan>diajukan' => $this->prepareRelease($report, $actor),
            'dalam_pemeriksaan>perlu_perbaikan' => $this->prepareRevision($report, $actor),
            'dalam_pemeriksaan>selesai_diperiksa' => $this->prepareComplete($report, $actor),
            'selesai_diperiksa>perlu_perbaikan' => $this->prepareUnlock($report, $note),
        };
    }

    private function prepareSubmit(MonthlyReport $report, ReportStatus $from, User $actor): array
    {
        if ($actor->school_id !== $report->school_id) {
            throw new DomainRuleException([__('domain.report.unauthorized_actor')]);
        }

        $errors = $this->submissionValidator->errors($report);

        if (AppSetting::requiresPrincipalApproval() && $report->principal_approved_at === null) {
            $errors[] = __('domain.report.principal_approval_missing');
        }
        if ($from === ReportStatus::PerluPerbaikan && $report->reviewNotes()->get()->contains->needsResponse()) {
            $errors[] = __('domain.report.notes_unanswered');
        }
        if ($errors !== []) {
            throw new DomainRuleException($errors);
        }

        return [
            'submitted_at' => now(),
            'reviewer_id' => null,
            'identity_snapshot' => $this->identitySnapshot($report, $actor),
        ];
    }

    private function prepareWithdraw(MonthlyReport $report): array
    {
        if ($report->reviewer_id !== null) {
            throw new DomainRuleException([__('domain.report.withdraw_claimed')]);
        }

        return ['submitted_at' => null];
    }

    private function prepareClaim(MonthlyReport $report, User $actor): array
    {
        if ($report->reviewer_id !== null) {
            throw new DomainRuleException([__('domain.report.already_claimed')]);
        }

        return ['reviewer_id' => $actor->id, 'review_started_at' => now()];
    }

    private function prepareRelease(MonthlyReport $report, User $actor): array
    {
        $this->assertClaimer($report, $actor);

        return ['reviewer_id' => null, 'review_started_at' => null];
    }

    private function prepareRevision(MonthlyReport $report, User $actor): array
    {
        $this->assertClaimer($report, $actor);

        $hasNewNote = $report->reviewNotes()->where('created_at', '>=', $report->review_started_at)->exists();
        if (! $hasNewNote) {
            throw new DomainRuleException([__('domain.report.no_new_note')]);
        }

        return [];
    }

    private function prepareComplete(MonthlyReport $report, User $actor): array
    {
        $this->assertClaimer($report, $actor);

        $errors = [];
        if ($report->reviewNotes()->whereNull('resolved_at')->exists()) {
            $errors[] = __('domain.report.notes_open');
        }
        if ($this->blockedByUnverifiedOpeningBalance($report)) {
            $errors[] = __('domain.report.opening_balance_unverified');
        }
        if ($errors !== []) {
            throw new DomainRuleException($errors);
        }

        return ['completed_at' => now(), 'needs_rereview' => false];
    }

    private function prepareUnlock(MonthlyReport $report, ?string $note): array
    {
        if (blank($note)) {
            throw new DomainRuleException([__('domain.report.unlock_reason_required')]);
        }
        if ($report->fiscalYear->status->value === 'closed') {
            throw new DomainRuleException([__('domain.report.year_closed')]);
        }

        return ['completed_at' => null, 'principal_approved_by' => null, 'principal_approved_at' => null];
    }

    private function assertClaimer(MonthlyReport $report, User $actor): void
    {
        if ($report->reviewer_id !== $actor->id) {
            throw new DomainRuleException([__('domain.report.not_claimer')]);
        }
    }

    /** Saldo awal manual tahun pertama yang belum diverifikasi menghalangi bulan pertama dinyatakan selesai (docs/06 §B). */
    private function blockedByUnverifiedOpeningBalance(MonthlyReport $report): bool
    {
        $isFirstActiveMonth = ! MonthlyReport::query()->withoutGlobalScopes()
            ->where('school_id', $report->school_id)
            ->where('fiscal_year_id', $report->fiscal_year_id)
            ->where('fund_source_id', $report->fund_source_id)
            ->where('month', '<', $report->month)->exists();

        if (! $isFirstActiveMonth) {
            return false;
        }

        $opening = OpeningBalance::query()->withoutGlobalScopes()
            ->where('school_id', $report->school_id)
            ->where('fiscal_year_id', $report->fiscal_year_id)
            ->where('fund_source_id', $report->fund_source_id)
            ->first();

        return $opening?->blocksJanuaryCompletion() ?? false;
    }

    /** @return array<string, mixed> */
    private function identitySnapshot(MonthlyReport $report, User $actor): array
    {
        $school = $report->school()->first();
        $officials = $school->officials()->withoutGlobalScopes()->active()->get()->keyBy(fn ($o) => $o->role->value);

        return [
            'school_name' => $school->name,
            'npsn' => $school->npsn,
            'principal' => $officials->get(OfficialRole::KepalaSekolah->value)?->name,
            'treasurer' => $officials->get(OfficialRole::Bendahara->value)?->name,
            'operator' => $actor->name,
        ];
    }
}
