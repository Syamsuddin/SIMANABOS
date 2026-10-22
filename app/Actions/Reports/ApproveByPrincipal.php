<?php

namespace App\Actions\Reports;

use App\Exceptions\DomainRuleException;
use App\Models\AppSetting;
use App\Models\MonthlyReport;
use App\Models\User;

/** Persetujuan kepala sekolah (opsional, docs/06 §C.5). Bukan transisi status. */
class ApproveByPrincipal
{
    public function __invoke(MonthlyReport $report, User $principal): MonthlyReport
    {
        if (! AppSetting::requiresPrincipalApproval()) {
            throw new DomainRuleException([__('domain.principal.disabled')]);
        }
        if (! $report->isEditable()) {
            throw new DomainRuleException([__('domain.principal.not_editable')]);
        }

        $report->forceFill(['principal_approved_by' => $principal->id, 'principal_approved_at' => now()])->save();

        return $report;
    }
}
