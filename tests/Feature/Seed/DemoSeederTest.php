<?php

use App\Enums\AttentionTrigger;
use App\Enums\RoleName;
use App\Models\FiscalYear;
use App\Models\MonthlyReport;
use App\Models\User;
use App\Services\AttentionListBuilder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

it('menolak berjalan di produksi', function () {
    $this->app['env'] = 'production';

    // Dipanggil langsung: `db:seed` di produksi meminta konfirmasi interaktif lebih dulu.
    (new DatabaseSeeder)->setContainer($this->app)->run();

    expect(User::query()->count())->toBe(0)->and(FiscalYear::query()->count())->toBe(0);
});

it('mengisi data uji untuk setiap peran, status laporan, dan pemicu daftar perhatian', function () {
    Storage::fake('private');

    $this->seed(DatabaseSeeder::class);

    foreach (RoleName::cases() as $role) {
        $user = User::role($role->value)->where('is_active', true)->where('must_change_password', false)->first();
        expect($user)->not->toBeNull()->and(Hash::check(DemoSeeder::PASSWORD, $user->password))->toBeTrue();
    }

    $statuses = MonthlyReport::query()->withoutGlobalScopes()->distinct()->pluck('status')->map->value->sort()->values()->all();
    expect($statuses)->toBe(['dalam_pemeriksaan', 'diajukan', 'draf', 'perlu_perbaikan', 'selesai_diperiksa']);

    $triggers = app(AttentionListBuilder::class)->build(FiscalYear::query()->firstOrFail(), [], Carbon::parse('2026-10-23'))
        ->map(fn ($row) => $row['trigger'])->unique()->count();
    expect($triggers)->toBe(count(AttentionTrigger::cases()))
        ->and(Carbon::hasTestNow())->toBeFalse();
});
