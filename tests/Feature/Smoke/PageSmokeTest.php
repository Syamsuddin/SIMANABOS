<?php

use Illuminate\Support\Facades\Route;
use Tests\Feature\Smoke\SmokeFixtures;

/*
 * UI smoke (docs/13): setiap halaman GET dirender untuk peran berhak dan ditolak untuk yang tidak (docs/05).
 * Kode harapan: 200 boleh · 403 dilarang · 404 objek/area di luar cakupan peran · 302 dialihkan.
 * Route GET baru wajib ditambahkan ke matriks ini (tes terakhir menjaganya).
 */

const SMOKE_ROLES = ['admin', 'pemeriksa', 'pimpinan', 'operator', 'kepsek'];

function smokeMatrix(): array
{
    $all = [200, 200, 200, 200, 200];
    $dinas = [200, 200, 200, 404, 404];
    $admin = [200, 403, 403, 403, 403];
    $school = [404, 404, 404, 200, 200];

    return [
        'login' => [302, 302, 302, 302, 302],
        'password.change' => $all,
        'home' => $all,
        'notifications.index' => $all,
        'periodic.index' => $all,
        'school.periodic.index' => $all,
        'school.reports.edit' => $all,
        'documents.download' => $all,
        'dashboard.index' => $dinas,
        'recap.index' => $dinas,
        'recap.export' => $dinas,
        'attention.index' => $dinas,
        'review.reports.index' => $dinas,
        'review.reports.show' => $dinas,
        'examiner.opening-balances.index' => $dinas,
        'admin.settings.index' => $admin,
        'admin.districts.index' => $admin,
        'admin.districts.create' => $admin,
        'admin.districts.edit' => $admin,
        'admin.fund-sources.index' => $admin,
        'admin.fund-sources.create' => $admin,
        'admin.fund-sources.edit' => $admin,
        'admin.fiscal-years.index' => $admin,
        'admin.fiscal-years.create' => $admin,
        'admin.fiscal-years.show' => $admin,
        'admin.fiscal-years.expense-types.index' => $admin,
        'admin.fiscal-years.expense-types.create' => $admin,
        'admin.fiscal-years.expense-types.edit' => $admin,
        'admin.fiscal-years.document-requirements.index' => $admin,
        'admin.allocations.index' => $admin,
        'admin.schools.index' => $admin,
        'admin.schools.create' => $admin,
        'admin.schools.show' => $admin,
        'admin.schools.edit' => $admin,
        'admin.users.index' => $admin,
        'admin.users.create' => $admin,
        'admin.users.edit' => $admin,
        'school.profile.show' => $school,
        'school.budget.index' => $school,
        'school.opening-balance.index' => $school,
        'school.reports.index' => $school,
        'school.profile.edit' => [404, 404, 404, 200, 403],
        'school.budget.create' => [403, 403, 403, 200, 403],
    ];
}

function smokeUrl(SmokeFixtures $f, string $name): string
{
    $params = [
        'district' => $f->district->id, 'fiscal_year' => $f->year->id, 'expense_type' => $f->year->expenseTypes()->value('id'),
        'fund_source' => $f->fund->id, 'school' => $f->school->id, 'user' => $f->users['operator']->id,
        'report' => $f->report->id, 'document' => $f->document->id,
    ];
    $route = Route::getRoutes()->getByName($name);
    $query = $name === 'school.budget.create' ? ['tahun' => $f->year->year, 'sumber_dana' => $f->fund->id] : [];

    return route($name, array_intersect_key($params, array_flip($route->parameterNames())) + $query);
}

it('merender setiap halaman sesuai matriks akses docs/05', function () {
    $f = SmokeFixtures::build();
    $mismatches = [];

    foreach (smokeMatrix() as $name => $expected) {
        $url = smokeUrl($f, $name);
        foreach (SMOKE_ROLES as $i => $role) {
            auth()->logout();
            $status = $this->actingAs($f->users[$role])->get($url)->getStatusCode();
            if ($status !== $expected[$i]) {
                $mismatches[] = "{$name} [{$role}] harap {$expected[$i]}, dapat {$status}";
            }
        }
    }

    expect($mismatches)->toBe([]);
});

it('tamu dialihkan ke halaman masuk untuk setiap halaman terautentikasi', function () {
    $f = SmokeFixtures::build();

    foreach (array_keys(smokeMatrix()) as $name) {
        if ($name === 'login') {
            continue;
        }
        $this->get(smokeUrl($f, $name))->assertRedirect(route('login'));
    }
});

it('role sekolah tidak dapat membuka laporan atau PDF sekolah lain (404)', function () {
    $f = SmokeFixtures::build();

    foreach (['operator', 'kepsek'] as $role) {
        $this->actingAs($f->users[$role])->get(route('school.reports.edit', $f->otherReport))->assertNotFound();
        $this->actingAs($f->users[$role])->get(route('documents.download', $f->otherDocument))->assertNotFound();
    }
});

it('setiap route GET aplikasi tercakup matriks smoke', function () {
    $names = collect(Route::getRoutes())
        ->filter(fn ($r) => in_array('GET', $r->methods()) && $r->getName() !== null && $r->getActionName() !== 'Closure')
        ->filter(fn ($r) => str_starts_with($r->getActionName(), 'App\\') || $r->getActionName() === '\\Illuminate\\Routing\\ViewController')
        ->map(fn ($r) => $r->getName())
        ->values();

    expect($names)->toContain('home', 'dashboard.index', 'school.reports.edit')
        ->and($names->diff(array_keys(smokeMatrix()))->values()->all())->toBe([]);
});
