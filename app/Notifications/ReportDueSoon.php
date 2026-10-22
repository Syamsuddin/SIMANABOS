<?php

namespace App\Notifications;

use App\Models\MonthlyReport;
use Illuminate\Notifications\Notification;

class ReportDueSoon extends Notification
{
    public function __construct(private readonly MonthlyReport $report, private readonly string $dueDate, private readonly int $daysLeft) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'report_id' => $this->report->id,
            'message' => "Laporan bulan ke-{$this->report->month} masih draf; tenggat {$this->dueDate} ({$this->daysLeft} hari lagi).",
        ];
    }
}
