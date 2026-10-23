<?php

namespace Tests\Feature\Smoke;

use App\Actions\Budget\SaveAllocation;
use App\Actions\Documents\UploadDocument;
use App\Enums\RoleName;
use App\Models\District;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\MonthlyReport;
use App\Models\School;
use App\Models\User;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Data minimum agar setiap halaman GET punya objek nyata untuk dirender (docs/13 UI smoke). */
class SmokeFixtures
{
    /** @var array<string, User> */
    public array $users = [];

    public School $school;

    public School $otherSchool;

    public FiscalYear $year;

    public MonthlyReport $report;

    public MonthlyReport $otherReport;

    public Document $document;

    public Document $otherDocument;

    public District $district;

    public FundSource $fund;

    public static function build(): self
    {
        Storage::fake('private');
        (new DocumentTypeSeeder)->run();
        $f = new self;
        $f->district = District::query()->create(['code' => 'KDG', 'name' => 'Kandangan']);
        $f->school = School::factory()->create(['district_id' => $f->district->id]);
        $f->otherSchool = School::factory()->create(['district_id' => $f->district->id]);
        $f->year = FiscalYear::factory()->create(['year' => (int) now()->year]);
        $f->year->expenseTypes()->create(['code' => 'BARJAS', 'name' => 'Barang dan Jasa']);
        $f->fund = FundSource::query()->create(['code' => 'BOS_REG', 'name' => 'BOS Reguler']);
        (new SaveAllocation)($f->school, $f->year, $f->fund, '100000000.00', 1);
        (new SaveAllocation)($f->otherSchool, $f->year, $f->fund, '50000000.00', 1);

        $schoolUser = ['school_id' => $f->school->id, 'must_change_password' => false];
        $f->users = [
            'admin' => self::user(RoleName::AdminDinas, ['must_change_password' => false]),
            'pemeriksa' => self::user(RoleName::PemeriksaDinas, ['must_change_password' => false]),
            'pimpinan' => self::user(RoleName::PimpinanDinas, ['must_change_password' => false]),
            'operator' => self::user(RoleName::OperatorSekolah, $schoolUser),
            'kepsek' => self::user(RoleName::KepalaSekolah, $schoolUser),
        ];
        $otherOperator = self::user(RoleName::OperatorSekolah, ['school_id' => $f->otherSchool->id, 'must_change_password' => false]);

        $f->report = self::report($f->school, $f->year);
        $f->otherReport = self::report($f->otherSchool, $f->year);
        $bku = DocumentType::query()->where('code', 'BKU')->value('id');
        $pdf = fn () => UploadedFile::fake()->createWithContent('bku.pdf', "%PDF-1.4\nsmoke\n%%EOF");
        $f->document = app(UploadDocument::class)($f->report, $pdf(), $f->users['operator'], [$bku])['document'];
        $f->otherDocument = app(UploadDocument::class)($f->otherReport, $pdf(), $otherOperator, [$bku])['document'];

        return $f;
    }

    private static function user(RoleName $role, array $attributes): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($role->value);

        return $user;
    }

    private static function report(School $school, FiscalYear $year): MonthlyReport
    {
        return MonthlyReport::query()->withoutGlobalScopes()->where('school_id', $school->id)
            ->where('fiscal_year_id', $year->id)->where('month', 1)->firstOrFail();
    }
}
