<?php

namespace App\Actions\Reports;

use App\Enums\ReportStatus;
use App\Exceptions\DomainRuleException;
use App\Models\ReviewNote;
use App\Models\User;

class ResolveNote
{
    public function __invoke(ReviewNote $note, User $reviewer): ReviewNote
    {
        $report = $note->report;
        if ($report->status !== ReportStatus::DalamPemeriksaan) {
            throw new DomainRuleException([__('domain.note.not_open')]);
        }
        if ($report->reviewer_id !== $reviewer->id) {
            throw new DomainRuleException([__('domain.report.not_claimer')]);
        }
        if (! $note->isOpen()) {
            throw new DomainRuleException([__('domain.note.already_resolved')]);
        }

        $note->update(['resolved_at' => now()]);

        return $note;
    }
}
