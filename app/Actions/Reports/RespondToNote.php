<?php

namespace App\Actions\Reports;

use App\Enums\ReportStatus;
use App\Exceptions\DomainRuleException;
use App\Models\ReviewNote;

class RespondToNote
{
    public function __invoke(ReviewNote $note, string $response): ReviewNote
    {
        if ($note->report->status !== ReportStatus::PerluPerbaikan) {
            throw new DomainRuleException([__('domain.note.not_open')]);
        }
        if (! $note->isOpen()) {
            throw new DomainRuleException([__('domain.note.already_resolved')]);
        }

        $note->update(['response_body' => $response, 'responded_at' => now()]);

        return $note;
    }
}
