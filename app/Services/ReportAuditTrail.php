<?php

namespace App\Services;

use App\Enums\ReportStatus;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\ExpenseType;
use App\Models\MonthlyReport;
use App\Models\ReportAdjustment;
use App\Models\ReportExpenseLine;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * Menyusun jejak audit satu laporan bulanan dari `activity_log` (docs/15): laporan, baris realisasi,
 * penyesuaian, dokumen, dan versinya. Hanya membaca — audit append-only (docs/21).
 */
class ReportAuditTrail
{
    /**
     * @return Collection<int, array{at: Carbon, actor: string, event: string, subject: string, changes: list<array{field: string, old: ?string, new: ?string}>}>
     */
    public function forReport(MonthlyReport $report): Collection
    {
        // Baris dapat sudah terhapus; pemiliknya dikenali dari atribut saat dibuat.
        $lineIds = $this->childIds(ReportExpenseLine::class, 'monthly_report_id', [$report->id])
            ->merge($report->lines()->pluck('id'))->unique();
        $adjustmentIds = $this->childIds(ReportAdjustment::class, 'monthly_report_id', [$report->id])
            ->merge($report->adjustments()->pluck('id'))->unique();
        $documentIds = Document::withTrashed()->whereMorphedTo('documentable', $report)->pluck('id');
        $versionIds = DocumentVersion::query()->whereIn('document_id', $documentIds)->pluck('id');

        $activities = Activity::query()
            ->where(fn ($q) => $q
                ->where(fn ($s) => $s->where('subject_type', MonthlyReport::class)->where('subject_id', $report->id))
                ->orWhere(fn ($s) => $s->where('subject_type', ReportExpenseLine::class)->whereIn('subject_id', $lineIds))
                ->orWhere(fn ($s) => $s->where('subject_type', ReportAdjustment::class)->whereIn('subject_id', $adjustmentIds))
                ->orWhere(fn ($s) => $s->where('subject_type', Document::class)->whereIn('subject_id', $documentIds))
                ->orWhere(fn ($s) => $s->where('subject_type', DocumentVersion::class)->whereIn('subject_id', $versionIds)))
            ->with('causer')
            ->orderByDesc('id')
            ->get();

        $lineTypes = $this->lineExpenseTypes($activities);

        return $activities->map(fn (Activity $activity) => [
            'at' => $activity->created_at,
            'actor' => $activity->causer?->name ?? __('audit.system'),
            'event' => __('audit.events.'.$activity->event),
            'subject' => $this->subjectLabel($activity, $lineTypes),
            'changes' => $this->changes($activity),
            'is_update' => $activity->event === 'updated',
        ])
            // Pembaruan yang hanya menyentuh cache `computed_*` (hitung ulang rantai) tidak ditampilkan; log tetap utuh.
            ->reject(fn (array $entry) => $entry['is_update'] && $entry['changes'] === [])
            ->map(fn (array $entry) => collect($entry)->except('is_update')->all())
            ->values();
    }

    /** @return Collection<int, int> */
    private function childIds(string $subjectType, string $foreignKey, array $parentIds): Collection
    {
        return Activity::query()
            ->where('subject_type', $subjectType)
            ->whereIn('attribute_changes->attributes->'.$foreignKey, $parentIds)
            ->pluck('subject_id');
    }

    /** @return array<int, string> id baris → nama jenis belanja */
    private function lineExpenseTypes(Collection $activities): array
    {
        $typeIds = $activities->where('subject_type', ReportExpenseLine::class)
            ->mapWithKeys(fn (Activity $a) => [$a->subject_id => $a->attribute_changes['attributes']['expense_type_id'] ?? $a->attribute_changes['old']['expense_type_id'] ?? null])
            ->filter();
        $names = ExpenseType::query()->whereIn('id', $typeIds->unique())->pluck('name', 'id');

        return $typeIds->map(fn ($typeId) => $names[$typeId] ?? '')->all();
    }

    private function subjectLabel(Activity $activity, array $lineTypes): string
    {
        return match ($activity->subject_type) {
            MonthlyReport::class => __('audit.subjects.report'),
            ReportExpenseLine::class => trim(__('audit.subjects.line', ['type' => $lineTypes[$activity->subject_id] ?? ''])),
            ReportAdjustment::class => __('audit.subjects.adjustment'),
            Document::class => __('audit.subjects.document', ['id' => $activity->subject_id]),
            DocumentVersion::class => __('audit.subjects.version', ['id' => $activity->subject_id]),
            default => class_basename($activity->subject_type),
        };
    }

    /** @return list<array{field: string, old: ?string, new: ?string}> */
    private function changes(Activity $activity): array
    {
        $new = $activity->attribute_changes['attributes'] ?? [];
        $old = $activity->attribute_changes['old'] ?? [];
        $labels = __('audit.fields');
        $rows = [];

        foreach (array_unique([...array_keys($new), ...array_keys($old)]) as $field) {
            if (! isset($labels[$field])) {
                continue;
            }
            $rows[] = [
                'field' => $labels[$field],
                'old' => array_key_exists($field, $old) ? $this->display($field, $old[$field]) : null,
                'new' => array_key_exists($field, $new) ? $this->display($field, $new[$field]) : null,
            ];
        }

        return $rows;
    }

    private function display(string $field, mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            in_array($field, __('audit.money'), true) => Money::format((string) $value),
            $field === 'status' => ReportStatus::tryFrom((string) $value)?->label() ?? (string) $value,
            in_array($field, ['is_nil', 'is_late', 'needs_rereview'], true) => $value ? __('audit.yes') : __('audit.no'),
            default => (string) $value,
        };
    }
}
