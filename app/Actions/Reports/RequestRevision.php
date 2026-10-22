<?php

namespace App\Actions\Reports;

use App\Enums\ReportStatus;
use App\Models\MonthlyReport;
use App\Models\User;
use App\Services\ReportStateMachine;

class RequestRevision
{
    public function __construct(private readonly ReportStateMachine $machine) {}

    public function __invoke(MonthlyReport $report, User $actor): MonthlyReport
    {
        return $this->machine->transition($report, ReportStatus::PerluPerbaikan, $actor);
    }
}
