<?php

namespace App\Actions\Reports;

use App\Enums\ReportStatus;
use App\Models\MonthlyReport;
use App\Models\User;
use App\Services\ReportStateMachine;

class UnlockReport
{
    public function __construct(private readonly ReportStateMachine $machine) {}

    public function __invoke(MonthlyReport $report, User $actor, string $reason): MonthlyReport
    {
        return $this->machine->transition($report, ReportStatus::PerluPerbaikan, $actor, $reason);
    }
}
