<?php

use App\Actions\Deadlines\ProcessDeadlines;
use App\Enums\ReportStatus;
use App\Enums\RoleName;
use App\Models\AppSetting;
use App\Models\FiscalYear;
use App\Models\MonthlyDeadline;
use App\Models\MonthlyReport;
use App\Models\School;
use Illuminate\Support\Carbon;

function deadlineFixture(string $due = '2026-03-10'): array
{
    $year = FiscalYear::factory()->create(['year' => 2026]);
    MonthlyDeadline::query()->create(['fiscal_year_id' => $year->id, 'month' => 2, 'due_date' => $due]);
    $school = School::factory()->create();
    $operator = userWithRole(RoleName::OperatorSekolah, ['school_id' => $school->id]);
    $principal = userWithRole(RoleName::KepalaSekolah, ['school_id' => $school->id]);
    $report = MonthlyReport::factory()->create(['school_id' => $school->id, 'fiscal_year_id' => $year->id, 'month' => 2]);

    return [$report, $operator, $principal];
}

afterEach(fn () => Carbon::setTestNow());

it('H-3 mengirim pengingat sekali ke operator untuk laporan draf', function () {
    [$report, $operator, $principal] = deadlineFixture();

    Carbon::setTestNow('2026-03-07 08:00:00');
    $result = app(ProcessDeadlines::class)->handle();
    app(ProcessDeadlines::class)->handle(); // dijalankan ulang → tidak ganda

    expect($result)->toBe(['reminded' => 1, 'late' => 0])
        ->and($operator->notifications()->count())->toBe(1)
        ->and($principal->notifications()->count())->toBe(0)
        ->and($report->fresh()->is_late)->toBeFalse();
});

it('H+1 menandai terlambat dan memberi tahu operator dan kepala sekolah', function () {
    [$report, $operator, $principal] = deadlineFixture();

    Carbon::setTestNow('2026-03-11 00:30:00');
    $result = app(ProcessDeadlines::class)->handle();
    app(ProcessDeadlines::class)->handle();

    expect($result)->toBe(['reminded' => 0, 'late' => 1])
        ->and($report->fresh()->is_late)->toBeTrue()
        ->and($operator->notifications()->count())->toBe(1)
        ->and($principal->notifications()->count())->toBe(1);
});

it('laporan yang sudah diajukan tidak diingatkan atau ditandai terlambat', function () {
    [$report, $operator] = deadlineFixture();
    $report->forceFill(['status' => ReportStatus::Diajukan])->save();

    foreach (['2026-03-07', '2026-03-11'] as $day) {
        Carbon::setTestNow("$day 08:00:00");
        app(ProcessDeadlines::class)->handle();
    }

    expect($operator->notifications()->count())->toBe(0)->and($report->fresh()->is_late)->toBeFalse();
});

it('jumlah hari pengingat mengikuti pengaturan', function () {
    [, $operator] = deadlineFixture();
    AppSetting::put('reminder_days_before', 5);

    Carbon::setTestNow('2026-03-07 08:00:00');
    expect(app(ProcessDeadlines::class)->handle()['reminded'])->toBe(0);
    Carbon::setTestNow('2026-03-05 08:00:00');
    expect(app(ProcessDeadlines::class)->handle()['reminded'])->toBe(1);
});

it('perintah artisan menjalankan proses', function () {
    deadlineFixture();
    Carbon::setTestNow('2026-03-11 00:30:00');

    $this->artisan('simanabos:process-deadlines')->expectsOutputToContain('ditandai terlambat: 1')->assertSuccessful();
});

it('pengguna melihat dan menandai notifikasinya sendiri', function () {
    [, $operator] = deadlineFixture();
    Carbon::setTestNow('2026-03-11 00:30:00');
    app(ProcessDeadlines::class)->handle();

    $this->actingAs($operator)->get(route('notifications.index'))->assertOk()->assertSee('terlambat');
    $this->post(route('notifications.read-all'))->assertRedirect(route('notifications.index'));
    expect($operator->unreadNotifications()->count())->toBe(0);
});

it('halaman notifikasi kosong menampilkan state kosong', function () {
    $this->actingAs(userWithRole(RoleName::OperatorSekolah))->get(route('notifications.index'))->assertOk()->assertSee('Belum ada notifikasi');
});

it('admin mengubah tanggal tenggat bawaan dan form tahun baru memakainya', function () {
    $admin = userWithRole(RoleName::AdminDinas);
    $this->actingAs($admin)->put(route('admin.settings.update'), [
        'require_principal_approval' => '0', 'default_deadline_day' => '15', 'reminder_days_before' => '4',
    ])->assertRedirect();

    expect(AppSetting::defaultDeadlineDay())->toBe(15)->and(AppSetting::reminderDaysBefore())->toBe(4);
    $this->get(route('admin.fiscal-years.create'))->assertSee('value="15"', false);
});

it('pengaturan tenggat di luar rentang ditolak', function () {
    $this->actingAs(userWithRole(RoleName::AdminDinas))
        ->put(route('admin.settings.update'), ['require_principal_approval' => '0', 'default_deadline_day' => '31', 'reminder_days_before' => '0'])
        ->assertSessionHasErrors(['default_deadline_day', 'reminder_days_before']);
});
