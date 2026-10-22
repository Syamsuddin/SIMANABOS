<?php

namespace App\Actions\Reports;

use App\Enums\ReportStatus;
use App\Exceptions\DomainRuleException;
use App\Models\MonthlyReport;
use App\Models\ReviewNote;
use App\Models\User;

class AddReviewNote
{
    public function __invoke(MonthlyReport $report, User $reviewer, string $targetType, ?string $targetKey, string $body): ReviewNote
    {
        if ($report->status !== ReportStatus::DalamPemeriksaan) {
            throw new DomainRuleException([__('domain.note.not_open')]);
        }
        if ($report->reviewer_id !== $reviewer->id) {
            throw new DomainRuleException([__('domain.report.not_claimer')]);
        }

        return $report->reviewNotes()->create([
            'reviewer_id' => $reviewer->id,
            'target_type' => $targetType,
            'target_key' => $targetType === 'general' ? null : $targetKey,
            'body' => $body,
        ]);
    }
}
