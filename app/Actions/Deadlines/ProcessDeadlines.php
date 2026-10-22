<?php

namespace App\Actions\Deadlines;

use App\Enums\FiscalYearStatus;
use App\Enums\ReportStatus;
use App\Enums\RoleName;
use App\Models\AppSetting;
use App\Models\FiscalYear;
use App\Models\MonthlyReport;
use App\Models\User;
use App\Notifications\ReportDueSoon;
use App\Notifications\ReportOverdue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Penjadwal harian (docs/06 §H): pengingat H-N untuk laporan draf dan penandaan terlambat pada H+1.
 * Aman dijalankan ulang: pengingat tidak digandakan, `is_late` hanya berpindah false → true sekali.
 */
class ProcessDeadlines
{
    /** @return array{reminded: int, late: int} */
    public function handle(?Carbon $today = null): array
    {
        $today = ($today ?? now())->copy()->startOfDay();
        $remindOn = $today->copy()->addDays(AppSetting::reminderDaysBefore());
        $lateBefore = $today->copy()->subDay(); // due_date <= kemarin → H+1 atau lebih

        $reminded = 0;
        $late = 0;

        $years = FiscalYear::query()->where('status', FiscalYearStatus::Open)->with('deadlines')->get();

        foreach ($years as $year) {
            foreach ($year->deadlines as $deadline) {
                $due = $deadline->due_date;
                $isReminderDay = $due->isSameDay($remindOn);
                $isOverdue = $due->lte($lateBefore);

                if (! $isReminderDay && ! $isOverdue) {
                    continue;
                }

                $reports = MonthlyReport::query()->withoutGlobalScopes()
                    ->where('fiscal_year_id', $year->id)
                    ->where('month', $deadline->month)
                    ->where('status', ReportStatus::Draf)
                    ->get();

                foreach ($reports as $report) {
                    if ($isReminderDay && ! $this->alreadySent(ReportDueSoon::class, $report)) {
                        $this->notify($report, [RoleName::OperatorSekolah], new ReportDueSoon($report, $due->format('d-m-Y'), (int) $today->diffInDays($due)));
                        $reminded++;
                    }

                    if ($isOverdue && ! $report->is_late) {
                        DB::transaction(function () use ($report) {
                            $report->forceFill(['is_late' => true])->save();
                        });
                        $this->notify($report, [RoleName::OperatorSekolah, RoleName::KepalaSekolah], new ReportOverdue($report, $due->format('d-m-Y')));
                        $late++;
                    }
                }
            }
        }

        return ['reminded' => $reminded, 'late' => $late];
    }

    private function alreadySent(string $type, MonthlyReport $report): bool
    {
        return DB::table('notifications')
            ->where('type', $type)
            ->where('data->report_id', $report->id)
            ->exists();
    }

    /** @param  array<int, RoleName>  $roles */
    private function notify(MonthlyReport $report, array $roles, object $notification): void
    {
        User::query()
            ->where('school_id', $report->school_id)
            ->where('is_active', true)
            ->role(array_map(fn (RoleName $r) => $r->value, $roles))
            ->get()
            ->each(fn (User $user) => $user->notify($notification));
    }
}
