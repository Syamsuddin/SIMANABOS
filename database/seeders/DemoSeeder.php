<?php

namespace Database\Seeders;

use App\Actions\Budget\SaveAllocation;
use App\Actions\Budget\SaveBudgetVersion;
use App\Actions\Budget\SaveOpeningBalance;
use App\Actions\Budget\SavePeriodicReport;
use App\Actions\Budget\VerifyOpeningBalance;
use App\Actions\Deadlines\ProcessDeadlines;
use App\Actions\Documents\ReplaceDocument;
use App\Actions\Documents\UploadDocument;
use App\Actions\FiscalYears\OpenFiscalYear;
use App\Actions\Reports\AddReviewNote;
use App\Actions\Reports\CompleteReview;
use App\Actions\Reports\RequestRevision;
use App\Actions\Reports\RespondToNote;
use App\Actions\Reports\SaveReportFigures;
use App\Actions\Reports\StartReview;
use App\Actions\Reports\SubmitReport;
use App\Actions\Reports\UnlockReport;
use App\Actions\Schools\ProposeOfficial;
use App\Enums\AdjustmentKind;
use App\Enums\PeriodicKind;
use App\Enums\RoleName;
use App\Models\District;
use App\Models\DocumentRequirement;
use App\Models\DocumentType;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\MonthlyReport;
use App\Models\OpeningBalance;
use App\Models\PeriodicReport;
use App\Models\ReportAdjustment;
use App\Models\School;
use App\Models\User;
use App\Services\ReportBalanceCalculator;
use Brick\Math\BigDecimal;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Data uji lengkap untuk mencoba setiap menu secara lokal (docs/10). Semua data dibuat lewat Action aplikasi
 * agar rantai saldo, riwayat status, notifikasi, dan jejak audit terbentuk seperti pemakaian nyata.
 * Garis waktu: tahun anggaran 2026, "hari ini" 23 Oktober 2026 WITA. Nama sekolah fiktif (docs/13).
 */
class DemoSeeder extends Seeder
{
    public const PASSWORD = 'password';

    private const TODAY = '2026-10-23 09:00:00';

    private FiscalYear $year;

    private FundSource $reguler;

    private FundSource $kinerja;

    /** @var array<string, int> kode jenis belanja → id */
    private array $types = [];

    /** @var array<string, User> */
    private array $users = [];

    public function run(): void
    {
        try {
            $this->at('2026-01-02 08:00');
            $this->reference();
            $this->dinasAccounts();

            $this->sukamaju();
            $this->muhammadiyah();
            $this->smpn3();
            $this->mekarsari();
            $this->bina();
            $this->inactiveSchool();

            // Persyaratan baru di tengah tahun: laporan BOS Kinerja yang sudah diajukan kini "dokumen kurang".
            $this->at('2026-10-01 08:00');
            $this->requirement('LAMPIRAN', 'all', $this->kinerja);

            $this->at(self::TODAY);
            Auth::forgetUser();
            app(ProcessDeadlines::class)->handle(Carbon::parse(self::TODAY));
        } finally {
            Auth::forgetUser();
            Carbon::setTestNow();
        }
    }

    private function reference(): void
    {
        foreach ([['KDG', 'Kandangan'], ['AGK', 'Angkinang'], ['SMP', 'Simpur'], ['TLS', 'Telaga Langsat'], ['PBT', 'Padang Batung'], ['DHU', 'Daha Utara']] as [$code, $name]) {
            District::query()->firstOrCreate(['code' => $code], ['name' => $name]);
        }

        $this->reguler = FundSource::query()->where('code', 'REGULER')->firstOrFail();
        $this->kinerja = FundSource::query()->where('code', 'KINERJA')->firstOrFail();

        $this->year = app(OpenFiscalYear::class)->handle(2026, 10, [
            ['stage_no' => 1, 'start_month' => 1, 'end_month' => 6, 'label' => 'Tahap I'],
            ['stage_no' => 2, 'start_month' => 7, 'end_month' => 12, 'label' => 'Tahap II'],
        ]);

        foreach ([
            ['BARJAS', '5.1.02', 'Belanja Barang dan Jasa', 1, true],
            ['MODAL_PM', '5.2.02', 'Belanja Modal Peralatan dan Mesin', 2, true],
            ['MODAL_ATL', '5.2.05', 'Belanja Modal Aset Tetap Lainnya', 3, true],
            ['HONOR', '5.1.01', 'Honorarium (tidak dipakai lagi)', 4, false],
        ] as [$code, $account, $name, $order, $active]) {
            $this->types[$code] = $this->year->expenseTypes()->create([
                'code' => $code, 'arkas_account_code' => $account, 'name' => $name, 'sort_order' => $order, 'is_active' => $active,
            ])->id;
        }

        foreach (['REALISASI', 'BKU', 'SPTJM'] as $code) {
            $this->requirement($code, 'all');
        }
        $this->requirement('REKAP_BMD', 'negeri');
    }

    private function dinasAccounts(): void
    {
        $this->users['admin'] = $this->user('admin', 'Rahmat Hidayat (Admin Uji)', RoleName::AdminDinas);
        $this->users['pemeriksa'] = $this->user('pemeriksa', 'Siti Aminah (Pemeriksa Uji)', RoleName::PemeriksaDinas);
        $this->users['pemeriksa2'] = $this->user('pemeriksa2', 'Budi Santoso (Pemeriksa Uji)', RoleName::PemeriksaDinas);
        $this->users['pimpinan'] = $this->user('pimpinan', 'Hj. Nurul Huda (Pimpinan Uji)', RoleName::PimpinanDinas);
        $this->user('pemeriksa.baru', 'Akun Baru Wajib Ganti Sandi', RoleName::PemeriksaDinas, null, ['must_change_password' => true]);
    }

    /**
     * Skenario lengkap: REGULER Jan–Jul selesai (Maret dibuka kunci dan dikoreksi → Apr–Jul tinjau ulang),
     * Agustus dalam pemeriksaan, September diajukan; RKAS dua versi; BOS Kinerja tertunda.
     */
    private function sukamaju(): void
    {
        [$school, $operator] = $this->school('sukamaju', 'Sukamaju', '30310001', 'SD Negeri 1 Sukamaju', 'SD', 'negeri', 'KDG');
        $this->user('nonaktif', 'Operator Lama (Nonaktif)', RoleName::OperatorSekolah, $school, ['is_active' => false]);
        $examiner = $this->users['pemeriksa'];

        $this->budget($school, $this->reguler, 120_000_000, $operator, verified: true);
        $this->at('2026-07-15 10:00');
        $this->actingAs($operator);
        app(SaveBudgetVersion::class)($school, $this->year, $this->reguler, '2026-07-15', 'Perubahan RKAS: tambahan pengadaan buku perpustakaan', [
            $this->types['BARJAS'] => '80000000.00', $this->types['MODAL_PM'] => '28000000.00', $this->types['MODAL_ATL'] => '12000000.00',
        ]);

        foreach (range(1, 7) as $month) {
            $report = $this->monthly($school, $this->reguler, $month, $operator, 120_000_000);
            if ($month === 2) {
                $this->at('2026-03-04 09:00');
                $document = $report->documents()->firstOrFail();
                app(ReplaceDocument::class)($report, $document, $this->pdf('BKU Februari (perbaikan halaman 3)'), $operator, 'Halaman 3 BKU terlewat saat pemindaian');
            }
            if ($month === 4) {
                $report = $this->adjust($report, $operator, 120_000_000, -250_000, 'Koreksi realisasi Maret: kuitansi ganda');
            }
            $this->review($this->submit($report, $operator), $examiner);
        }

        $august = $this->submit($this->monthly($school, $this->reguler, 8, $operator, 120_000_000), $operator);
        $this->at('2026-09-12 10:00');
        $this->actingAs($examiner);
        $august = app(StartReview::class)($august, $examiner);
        $this->at('2026-09-12 10:30');
        app(AddReviewNote::class)($august, $examiner, 'field', 'bank_balance_bku', 'Cocokkan saldo bank dengan rekening koran Agustus.');

        // Admin membuka kunci Maret (salah input jasa giro); koreksi menggeser saldo awal Apr–Jul → tinjau ulang.
        $this->at('2026-09-20 13:00');
        $this->actingAs($this->users['admin']);
        app(UnlockReport::class)($this->find($school, $this->reguler, 3), $this->users['admin'], 'Sekolah melaporkan jasa giro Maret belum dicatat.');
        $this->at('2026-09-22 08:30');
        $march = $this->find($school, $this->reguler, 3);
        $this->actingAs($operator);
        app(SaveReportFigures::class)($march, ['receipt_other' => '35000.00', 'receipt_other_note' => 'Jasa giro Maret', 'bank_balance_bku' => BigDecimal::of($march->bank_balance_bku)->plus('35000.00')->toScale(2)->__toString()], $this->linesOf($march), $march->lock_version);

        $this->submit($this->monthly($school, $this->reguler, 9, $operator, 120_000_000), $operator);

        $this->periodic($school, $this->reguler, PeriodicKind::Stage, 1, $operator, 'Laporan Tahap I', delta: 125_000);
        $this->periodic($school, $this->reguler, PeriodicKind::Minutes, 0, $operator, 'Berita Acara Pemeriksaan Kas Semester I');

        // BOS Kinerja: Jan–Jun selesai, Juli lama di antrean, Agustus–September belum dilaporkan.
        $this->budget($school, $this->kinerja, 30_000_000, $operator, verified: true);
        foreach (range(1, 6) as $month) {
            $this->review($this->submit($this->monthly($school, $this->kinerja, $month, $operator, 30_000_000), $operator), $this->users['pemeriksa2']);
        }
        $this->submit($this->monthly($school, $this->kinerja, 7, $operator, 30_000_000), $operator);
    }

    /** Swasta: Jan–Jul selesai, Agustus perlu perbaikan (satu catatan ditanggapi), September draf terisi tapi belum diajukan. */
    private function muhammadiyah(): void
    {
        [$school, $operator] = $this->school('muhammadiyah', 'Muhammadiyah', '30310002', 'SD Muhammadiyah 2 Contoh', 'SD', 'swasta', 'AGK');
        $examiner = $this->users['pemeriksa'];
        $this->budget($school, $this->reguler, 80_000_000, $operator, verified: true);

        foreach (range(1, 7) as $month) {
            $this->review($this->submit($this->monthly($school, $this->reguler, $month, $operator, 80_000_000), $operator), $examiner);
        }

        $august = $this->submit($this->monthly($school, $this->reguler, 8, $operator, 80_000_000), $operator);
        $this->at('2026-09-10 09:00');
        $this->actingAs($examiner);
        $august = app(StartReview::class)($august, $examiner);
        $this->at('2026-09-11 10:00');
        $first = app(AddReviewNote::class)($august, $examiner, 'field', 'receipt_bos', 'Penerimaan BOS tidak sesuai mutasi rekening tanggal 14 Agustus.');
        app(AddReviewNote::class)($august, $examiner, 'document_type', 'BKU', 'BKU belum ditandatangani kepala sekolah.');
        app(RequestRevision::class)($august, $examiner);
        $this->at('2026-09-14 15:00');
        $this->actingAs($operator);
        app(RespondToNote::class)($first, 'Sudah dicek ulang, angka sesuai mutasi setelah dikurangi biaya administrasi.');

        $this->at('2026-10-08 09:00');
        $this->fill($this->find($school, $this->reguler, 9), $operator, 80_000_000);
    }

    /** SMP negeri: Juni selesai dengan selisih saldo, Jul–Sep menunggu di antrean, RKAS melebihi pagu. */
    private function smpn3(): void
    {
        [$school, $operator] = $this->school('smpn3', 'SMPN 3', '30310003', 'SMP Negeri 3 Contoh', 'SMP', 'negeri', 'SMP');
        $this->budget($school, $this->reguler, 150_000_000, $operator, verified: true, rkasTotal: 165_000_000);

        foreach (range(1, 6) as $month) {
            $report = $this->monthly($school, $this->reguler, $month, $operator, 150_000_000, $month === 6 ? 175_000 : 0);
            $this->review($this->submit($report, $operator), $this->users['pemeriksa2']);
        }
        foreach (range(7, 9) as $month) {
            $this->submit($this->monthly($school, $this->reguler, $month, $operator, 150_000_000), $operator);
        }

        $this->periodic($school, $this->reguler, PeriodicKind::Stage, 1, $operator, 'Laporan Tahap I');
    }

    /** Saldo awal manual belum diverifikasi: Januari tertahan di pemeriksaan, bulan lain belum dilaporkan. */
    private function mekarsari(): void
    {
        [$school, $operator] = $this->school('mekarsari', 'Mekarsari', '30310004', 'SD Negeri 5 Mekarsari', 'SD', 'negeri', 'TLS');
        $this->budget($school, $this->reguler, 90_000_000, $operator, verified: false);

        $january = $this->submit($this->monthly($school, $this->reguler, 1, $operator, 90_000_000), $operator);
        $this->at('2026-02-10 09:00');
        $this->actingAs($this->users['pemeriksa2']);
        app(StartReview::class)($january, $this->users['pemeriksa2']);
    }

    /** Sekolah baru: hanya pagu; belum ada RKAS, saldo awal, maupun laporan; ada usulan pengelola menunggu pengesahan. */
    private function bina(): void
    {
        [$school, $operator] = $this->school('bina', 'Bina', '30310005', 'SMP Swasta Bina Contoh', 'SMP', 'swasta', 'PBT');
        $this->at('2026-01-05 09:00');
        $this->actingAs($this->users['admin']);
        (new SaveAllocation)($school, $this->year, $this->reguler, '60000000.00', 1);

        $this->at('2026-10-05 10:00');
        $this->actingAs($operator);
        app(ProposeOfficial::class)($school, ['role' => 'bendahara', 'name' => 'Dewi Lestari', 'nip' => null, 'started_at' => '2026-10-01'], $operator);
    }

    private function inactiveSchool(): void
    {
        School::query()->create([
            'npsn' => '30310006', 'name' => 'SD Negeri 7 Contoh (digabung)', 'level' => 'SD', 'status' => 'negeri',
            'district_id' => District::query()->where('code', 'DHU')->value('id'), 'address' => 'Jl. Contoh No. 7', 'is_active' => false,
        ]);
    }

    /** @return array{0: School, 1: User} sekolah dan operatornya; kepala sekolah dan pengelola ikut dibuat. */
    private function school(string $slug, string $label, string $npsn, string $name, string $level, string $status, string $district): array
    {
        $this->at('2026-01-02 09:00');
        $this->actingAs($this->users['admin']);
        $school = School::query()->create([
            'npsn' => $npsn, 'name' => $name, 'level' => $level, 'status' => $status,
            'district_id' => District::query()->where('code', $district)->value('id'),
            'address' => 'Jl. Pendidikan No. '.substr($npsn, -1).', '.District::query()->where('code', $district)->value('name'),
            'phone' => '0517-2100'.substr($npsn, -2), 'is_active' => true,
        ]);

        $operator = $this->user("operator.{$slug}", "Operator {$label} (Uji)", RoleName::OperatorSekolah, $school);
        $this->user("kepsek.{$slug}", "Kepala Sekolah {$label} (Uji)", RoleName::KepalaSekolah, $school);

        foreach ([['kepala_sekolah', "Drs. Kepala {$label}"], ['bendahara', "Bendahara {$label}"], ['operator', "Operator {$label}"]] as [$role, $official]) {
            app(ProposeOfficial::class)($school, ['role' => $role, 'name' => $official, 'nip' => null, 'started_at' => '2024-01-01'], $this->users['admin']);
        }

        return [$school, $operator];
    }

    /** Pagu, RKAS versi 1, dan saldo awal manual (diverifikasi pemeriksa atau belum). */
    private function budget(School $school, FundSource $fund, int $allocation, User $operator, bool $verified, ?int $rkasTotal = null): void
    {
        $this->at('2026-01-05 09:00');
        $this->actingAs($this->users['admin']);
        (new SaveAllocation)($school, $this->year, $fund, $this->rp($allocation), 1);

        $total = $rkasTotal ?? $allocation;
        $this->at('2026-01-10 10:00');
        $this->actingAs($operator);
        app(SaveBudgetVersion::class)($school, $this->year, $fund, '2026-01-10', null, [
            $this->types['BARJAS'] => $this->rp(intdiv($total * 70, 100)),
            $this->types['MODAL_PM'] => $this->rp(intdiv($total * 20, 100)),
            $this->types['MODAL_ATL'] => $this->rp($total - intdiv($total * 70, 100) - intdiv($total * 20, 100)),
        ]);
        $balance = app(SaveOpeningBalance::class)($school, $this->year, $fund, $this->rp(intdiv($allocation, 40)), '0.00');

        if ($verified) {
            $this->at('2026-01-20 11:00');
            $this->actingAs($this->users['pemeriksa']);
            app(VerifyOpeningBalance::class)(OpeningBalance::query()->withoutGlobalScopes()->findOrFail($balance->id), $this->users['pemeriksa']);
        }
    }

    /** Isi angka bulan M pada tanggal 3 bulan berikutnya lalu unggah satu PDF gabungan bertanda semua jenis wajib. */
    private function monthly(School $school, FundSource $fund, int $month, User $operator, int $allocation, int $difference = 0): MonthlyReport
    {
        $this->at(sprintf('2026-%02d-03 09:00', $month + 1));
        $report = $this->fill($this->find($school, $fund, $month), $operator, $allocation, $difference);

        $this->at(sprintf('2026-%02d-04 10:00', $month + 1));
        $codes = $school->status->value === 'negeri' ? ['REALISASI', 'BKU', 'SPTJM', 'REKAP_BMD'] : ['REALISASI', 'BKU', 'SPTJM'];
        app(UploadDocument::class)($report, $this->pdf("Laporan bulan {$month} {$school->name} {$fund->code}"), $operator, $this->typeIds($codes));

        return $report->refresh();
    }

    /**
     * Angka contoh dari pagu (bilangan bulat rupiah, tanpa float): BOS masuk Januari dan Juli, belanja barang tiap bulan,
     * belanja modal Maret/September dan Mei, pengembalian belanja April, jasa giro Juni. Saldo BKU dibuat cocok
     * kecuali $difference ≠ 0 (selisih disertai catatan).
     */
    private function fill(MonthlyReport $report, User $operator, int $allocation, int $difference = 0): MonthlyReport
    {
        $this->actingAs($operator);
        $month = $report->month;
        $barjas = intdiv($allocation * 6, 100);
        $tax = intdiv($barjas * 2, 100);

        $lines = [$this->types['BARJAS'] => ['gross_amount' => $this->rp($barjas), 'refund_amount' => $this->rp($month === 4 ? 200_000 : 0)]];
        if (in_array($month, [3, 9], true)) {
            $lines[$this->types['MODAL_PM']] = ['gross_amount' => $this->rp(intdiv($allocation * 10, 100)), 'refund_amount' => '0.00'];
        }
        if ($month === 5) {
            $lines[$this->types['MODAL_ATL']] = ['gross_amount' => $this->rp(intdiv($allocation * 3, 100)), 'refund_amount' => '0.00'];
        }

        $figures = [
            'is_nil' => false,
            'receipt_bos' => $this->rp(in_array($month, [1, 7], true) ? intdiv($allocation, 2) : 0),
            'receipt_other' => $this->rp($month === 6 ? 12_500 : 0),
            'receipt_other_note' => $month === 6 ? 'Jasa giro bank' : null,
            'treasury_deposit' => '0.00',
            'tax_withheld' => $this->rp($tax),
            'tax_remitted' => $this->rp($tax),
            'difference_note' => $difference !== 0 ? 'Biaya administrasi bank belum dicatat di BKU.' : null,
        ];
        $report = app(SaveReportFigures::class)($report, $figures, $lines, $report->lock_version);

        $cash = 250_000;
        $bank = BigDecimal::of($report->computed_ending_fund)->plus($report->computed_ending_tax)
            ->minus($cash)->plus($difference)->toScale(2);

        return app(SaveReportFigures::class)($report, [...$figures, 'bank_balance_bku' => (string) $bank, 'cash_balance_bku' => $this->rp($cash)], $lines, $report->lock_version);
    }

    private function submit(MonthlyReport $report, User $operator): MonthlyReport
    {
        $this->at(sprintf('2026-%02d-05 14:00', $report->month + 1));
        $this->actingAs($operator);

        return app(SubmitReport::class)($report->refresh(), $operator);
    }

    /** Diambil tanggal 12 dan dinyatakan selesai tanggal 14 bulan berikutnya. */
    private function review(MonthlyReport $report, User $examiner): MonthlyReport
    {
        $this->actingAs($examiner);
        $this->at(sprintf('2026-%02d-12 09:00', $report->month + 1));
        $report = app(StartReview::class)($report, $examiner);
        $this->at(sprintf('2026-%02d-14 15:00', $report->month + 1));

        return app(CompleteReview::class)($report, $examiner);
    }

    /** Penyesuaian realisasi (tanpa UI di MVP) lalu simpan ulang agar rantai saldo dan saldo BKU ikut menyesuaikan. */
    private function adjust(MonthlyReport $report, User $operator, int $allocation, int $amount, string $reason): MonthlyReport
    {
        ReportAdjustment::query()->create([
            'monthly_report_id' => $report->id, 'kind' => AdjustmentKind::Realisasi, 'expense_type_id' => $this->types['BARJAS'],
            'origin_fiscal_year_id' => $this->year->id, 'origin_month' => $report->month - 1, 'amount' => $this->rp($amount), 'reason' => $reason,
        ]);

        return $this->fill($report->refresh(), $operator, $allocation);
    }

    /** Laporan periodik dengan PDF; $delta membuat angka sekolah berbeda dari Σ bulanan agar selisih tampil. */
    private function periodic(School $school, FundSource $fund, PeriodicKind $kind, int $stageNo, User $operator, string $title, int $delta = 0): void
    {
        $this->at('2026-07-20 10:00');
        $this->actingAs($operator);
        $total = null;
        $ending = null;

        if ($kind === PeriodicKind::Stage) {
            $reports = MonthlyReport::query()->withoutGlobalScopes()->where('school_id', $school->id)->where('fiscal_year_id', $this->year->id)
                ->where('fund_source_id', $fund->id)->whereBetween('month', [1, 6])->with(['lines', 'adjustments'])->orderBy('month')->get();
            $net = $reports->reduce(fn (BigDecimal $sum, MonthlyReport $r) => $sum->plus(app(ReportBalanceCalculator::class)->forReport($r)['total_net_realization']), BigDecimal::zero());
            $total = (string) $net->plus($delta)->toScale(2);
            $ending = (string) BigDecimal::of($reports->last()->computed_ending_fund)->toScale(2);
        }

        $report = app(SavePeriodicReport::class)($school, $this->year, $fund, $kind, $stageNo, $total, $ending, $delta !== 0 ? 'Angka tahap mengikuti laporan ARKAS per 30 Juni.' : null);
        $code = $kind === PeriodicKind::Minutes ? 'BA' : 'TAHAP';
        app(UploadDocument::class)(PeriodicReport::query()->withoutGlobalScopes()->findOrFail($report->id), $this->pdf("{$title} {$school->name}"), $operator, $this->typeIds([$code]));
    }

    private function requirement(string $code, string $schoolStatus, ?FundSource $fund = null): void
    {
        DocumentRequirement::query()->create([
            'fiscal_year_id' => $this->year->id, 'document_type_id' => DocumentType::query()->where('code', $code)->value('id'),
            'report_kind' => 'monthly', 'school_status' => $schoolStatus, 'fund_source_id' => $fund?->id, 'is_required' => true,
        ]);
    }

    /** @param  array<string, mixed>  $attributes */
    private function user(string $username, string $name, RoleName $role, ?School $school = null, array $attributes = []): User
    {
        $user = User::query()->create([
            'username' => $username, 'name' => $name, 'password' => self::PASSWORD, 'school_id' => $school?->id,
            'must_change_password' => false, 'is_active' => true, ...$attributes,
        ]);
        $user->assignRole($role->value);

        return $user;
    }

    private function find(School $school, FundSource $fund, int $month): MonthlyReport
    {
        return MonthlyReport::query()->withoutGlobalScopes()->where('school_id', $school->id)
            ->where('fiscal_year_id', $this->year->id)->where('fund_source_id', $fund->id)->where('month', $month)->firstOrFail();
    }

    /** @return array<int, array{gross_amount: string, refund_amount: string}> */
    private function linesOf(MonthlyReport $report): array
    {
        return $report->lines()->get()->mapWithKeys(fn ($line) => [$line->expense_type_id => ['gross_amount' => $line->gross_amount, 'refund_amount' => $line->refund_amount]])->all();
    }

    /** @return list<int> */
    private function typeIds(array $codes): array
    {
        return DocumentType::query()->whereIn('code', $codes)->pluck('id')->all();
    }

    /** PDF satu halaman yang valid dan dapat dibuka penampil PDF; isinya hanya teks penanda data uji. */
    private function pdf(string $title): UploadedFile
    {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], 'DATA UJI SIMANABOS - '.$title);
        $stream = "BT /F1 14 Tf 60 780 Td ({$text}) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref
0 '.(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= 'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        $path = tempnam(sys_get_temp_dir(), 'simanabos-uji-');
        file_put_contents($path, $pdf);

        return new UploadedFile($path, str($title)->slug().'.pdf', 'application/pdf', null, true);
    }

    private function actingAs(User $user): void
    {
        Auth::setUser($user);
    }

    private function at(string $moment): void
    {
        Carbon::setTestNow(Carbon::parse($moment, 'Asia/Makassar'));
    }

    private function rp(int $rupiah): string
    {
        return (string) BigDecimal::of($rupiah)->toScale(2);
    }
}
