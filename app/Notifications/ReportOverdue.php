<?php

namespace App\Notifications;

use App\Models\MonthlyReport;
use Illuminate\Notifications\Notification;

class ReportOverdue extends Notification
{
    public function __construct(private readonly MonthlyReport $report, private readonly string $dueDate) {}

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
            'message' => "Laporan bulan ke-{$this->report->month} terlambat; tenggat {$this->dueDate} sudah lewat dan laporan masih draf.",
        ];
    }
}
