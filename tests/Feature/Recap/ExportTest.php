<?php

use App\Enums\RecapPeriod;
use App\Enums\ReportStatus;
use App\Enums\RoleName;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\MonthlyReport;
use App\Models\School;
use App\Services\RecapBuilder;
use OpenSpout\Reader\XLSX\Reader;

beforeEach(function () {
    $this->year = FiscalYear::factory()->create(['year' => 2037]);
    $this->fund = FundSource::query()->create(['code' => 'BOS_REG', 'name' => 'BOS Reguler']);
    $this->school = School::factory()->create();
});

function recapReport($test, int $month, ReportStatus $status, array $attrs = []): MonthlyReport
{
    $report = MonthlyReport::factory()->create([
        'school_id' => $test->school->id, 'fiscal_year_id' => $test->year->id, 'fund_source_id' => $test->fund->id, 'month' => $month,
    ]);
    $report->forceFill(['status' => $status, ...$attrs])->save();

    return $report;
}

function recapTriwulanTiga($test): void
{
    recapReport($test, 6, ReportStatus::Diajukan, ['receipt_bos' => '9999.00', 'computed_ending_fund' => '9999.00']);
    recapReport($test, 7, ReportStatus::Diajukan, ['opening_fund' => '100.00', 'receipt_bos' => '1000.00', 'receipt_other' => '10.00', 'computed_ending_fund' => '700.00']);
    recapReport($test, 8, ReportStatus::SelesaiDiperiksa, ['receipt_bos' => '2000.00', 'computed_ending_fund' => '1200.00']);
    recapReport($test, 9, ReportStatus::Diajukan, ['receipt_bos' => '3000.00', 'computed_ending_fund' => '1500.00', 'computed_ending_tax' => '30.00']);
    recapReport($test, 10, ReportStatus::Diajukan, ['receipt_bos' => '8888.00']);
}

it('Export: rekap triwulan III = Σ Jul–Sep untuk arus dan posisi September untuk saldo', function () {
    recapTriwulanTiga($this);

    $rows = app(RecapBuilder::class)->bySchool($this->year->id, RecapPeriod::Tw3);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['receipt'])->toBe('6010.00')
        ->and($rows[0]['opening_fund'])->toBe('100.00')
        ->and($rows[0]['ending_fund'])->toBe('1500.00')
        ->and($rows[0]['ending_tax'])->toBe('30.00')
        ->and(app(RecapBuilder::class)->totals($rows)['receipt'])->toBe('6010.00');
});

it('Export: draf tidak dihitung dalam rekap', function () {
    recapReport($this, 7, ReportStatus::Draf, ['receipt_bos' => '5000.00']);

    expect(app(RecapBuilder::class)->bySchool($this->year->id, RecapPeriod::Tw3))->toBeEmpty();
});

it('Export: XLSX terunduh, kolom angka numerik, tanpa kolom NIP', function () {
    recapTriwulanTiga($this);
    $admin = userWithRole(RoleName::AdminDinas);

    $response = $this->actingAs($admin)->get(route('recap.export', ['tahun' => 2037, 'periode' => 'tw3']));
    $response->assertOk()->assertDownload();

    $reader = new Reader;
    $reader->open($response->baseResponse->getFile()->getPathname());
    $rows = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $rows[] = $row->toArray();
        }
    }
    $reader->close();

    expect(strtoupper(implode('|', $rows[2])))->not->toContain('NIP')
        ->and($rows[3][6])->toBeNumeric()->and(is_string($rows[3][6]))->toBeFalse()
        ->and((float) $rows[3][6])->toBe(6010.0)
        ->and((float) $rows[3][9])->toBe(1500.0);
});

it('Export: role sekolah mendapat 404, pemeriksa dan pimpinan boleh', function () {
    $this->actingAs(userWithRole(RoleName::OperatorSekolah, ['school_id' => $this->school->id]))->get(route('recap.export'))->assertNotFound();
    $this->actingAs(userWithRole(RoleName::PimpinanDinas))->get(route('recap.index', ['tahun' => 2037]))->assertOk();
    $this->actingAs(userWithRole(RoleName::PemeriksaDinas))->get(route('recap.index', ['tahun' => 2037]))->assertOk();
});

it('Export: halaman rekap menampilkan keadaan kosong dan terisi', function () {
    $admin = userWithRole(RoleName::AdminDinas);
    $this->actingAs($admin)->get(route('recap.index', ['tahun' => 2037, 'periode' => 'tw3']))->assertOk()->assertSee('Tidak ada laporan terajukan');

    recapTriwulanTiga($this);
    $this->actingAs($admin)->get(route('recap.index', ['tahun' => 2037, 'periode' => 'tw3']))->assertOk()->assertSee('Total kabupaten')->assertSee('6.010,00');
});
