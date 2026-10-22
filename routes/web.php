<?php

use App\Http\Controllers\Admin\AllocationController;
use App\Http\Controllers\Admin\AppSettingController;
use App\Http\Controllers\Admin\DistrictController;
use App\Http\Controllers\Admin\DocumentRequirementController;
use App\Http\Controllers\Admin\ExpenseTypeController;
use App\Http\Controllers\Admin\FiscalYearController;
use App\Http\Controllers\Admin\FundSourceController;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Admin\SchoolOfficialController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AttentionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\Examiner\OpeningBalanceController as ExaminerOpeningBalanceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RecapController;
use App\Http\Controllers\Review\ReviewController;
use App\Http\Controllers\School\BudgetController;
use App\Http\Controllers\School\DocumentController;
use App\Http\Controllers\School\OpeningBalanceController;
use App\Http\Controllers\School\PeriodicDocumentController;
use App\Http\Controllers\School\PeriodicReportController;
use App\Http\Controllers\School\ProfileController;
use App\Http\Controllers\School\ReportController;
use App\Http\Controllers\School\ReportSubmissionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/beranda');

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [LoginController::class, 'create'])->name('login');
    Route::post('/masuk', [LoginController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/keluar', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/ganti-sandi', [PasswordChangeController::class, 'edit'])->name('password.change');
    Route::put('/ganti-sandi', [PasswordChangeController::class, 'update'])->name('password.update');

    Route::middleware('must.change.password')->group(function () {
        Route::view('/beranda', 'home')->name('home');
        Route::get('/dasbor', [DashboardController::class, 'index'])->name('dashboard.index');
        Route::get('/rekap', [RecapController::class, 'index'])->name('recap.index');
        Route::get('/rekap/ekspor', [RecapController::class, 'export'])->name('recap.export');
        Route::get('/perhatian', [AttentionController::class, 'index'])->name('attention.index');
        Route::get('/periodik', [PeriodicReportController::class, 'index'])->name('periodic.index');
        Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifikasi/baca-semua', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('settings', [AppSettingController::class, 'edit'])->name('settings.index');
            Route::put('settings', [AppSettingController::class, 'update'])->name('settings.update');
            Route::resource('districts', DistrictController::class)->except(['show', 'destroy']);
            Route::resource('fund-sources', FundSourceController::class)->except(['show', 'destroy']);
            Route::resource('fiscal-years', FiscalYearController::class)->only(['index', 'create', 'store', 'show']);
            Route::resource('fiscal-years.expense-types', ExpenseTypeController::class)->except(['show', 'destroy'])->scoped();
            Route::post('fiscal-years/{fiscal_year}/expense-types/copy', [ExpenseTypeController::class, 'copy'])->name('fiscal-years.expense-types.copy');
            Route::get('fiscal-years/{fiscal_year}/document-requirements', [DocumentRequirementController::class, 'index'])->name('fiscal-years.document-requirements.index');
            Route::post('fiscal-years/{fiscal_year}/document-requirements', [DocumentRequirementController::class, 'store'])->name('fiscal-years.document-requirements.store');
            Route::post('fiscal-years/{fiscal_year}/document-requirements/copy', [DocumentRequirementController::class, 'copy'])->name('fiscal-years.document-requirements.copy');
            Route::delete('fiscal-years/{fiscal_year}/document-requirements/{document_requirement}', [DocumentRequirementController::class, 'destroy'])->scopeBindings()->name('fiscal-years.document-requirements.destroy');
            Route::post('fiscal-years/{fiscal_year}/close', [FiscalYearController::class, 'close'])->name('fiscal-years.close');
            Route::put('fiscal-years/{fiscal_year}/deadlines', [FiscalYearController::class, 'updateDeadlines'])->name('fiscal-years.deadlines.update');
            Route::post('fiscal-years/{fiscal_year}/stages', [FiscalYearController::class, 'storeStage'])->name('fiscal-years.stages.store');
            Route::delete('fiscal-years/{fiscal_year}/stages/{stage}', [FiscalYearController::class, 'destroyStage'])->name('fiscal-years.stages.destroy');

            Route::get('allocations', [AllocationController::class, 'index'])->name('allocations.index');
            Route::post('allocations', [AllocationController::class, 'store'])->name('allocations.store');

            Route::resource('schools', SchoolController::class)->except(['destroy']);
            Route::post('schools/{school}/toggle-active', [SchoolController::class, 'toggleActive'])->name('schools.toggle-active');
            Route::post('schools/{school}/officials', [SchoolOfficialController::class, 'store'])->name('schools.officials.store');
            Route::post('officials/{official}/approve', [SchoolOfficialController::class, 'approve'])->name('officials.approve');
            Route::delete('officials/{official}', [SchoolOfficialController::class, 'reject'])->name('officials.reject');

            Route::resource('users', UserController::class)->except(['show', 'destroy']);
            Route::put('users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password.reset');
        });

        Route::prefix('sekolah')->name('school.')->group(function () {
            Route::get('/', [ProfileController::class, 'show'])->name('profile.show');
            Route::get('/ubah', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::put('/', [ProfileController::class, 'update'])->name('profile.update');
            Route::post('/pengelola', [ProfileController::class, 'storeOfficial'])->name('officials.store');

            Route::get('/rkas', [BudgetController::class, 'index'])->name('budget.index');
            Route::get('/rkas/baru', [BudgetController::class, 'create'])->name('budget.create');
            Route::post('/rkas', [BudgetController::class, 'store'])->name('budget.store');

            Route::get('/saldo-awal', [OpeningBalanceController::class, 'index'])->name('opening-balance.index');
            Route::put('/saldo-awal', [OpeningBalanceController::class, 'update'])->name('opening-balance.update');

            Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
            Route::get('/laporan/{report}', [ReportController::class, 'edit'])->name('reports.edit');
            Route::put('/laporan/{report}', [ReportController::class, 'update'])->name('reports.update');

            Route::post('/laporan/{report}/ajukan', [ReportSubmissionController::class, 'submit'])->name('reports.submit');
            Route::post('/laporan/{report}/tarik', [ReportSubmissionController::class, 'withdraw'])->name('reports.withdraw');
            Route::post('/laporan/{report}/setujui', [ReportSubmissionController::class, 'approve'])->name('reports.approve');
            Route::put('/laporan/{report}/catatan/{note}', [ReportSubmissionController::class, 'respond'])->name('reports.notes.respond');

            Route::get('/periodik', [PeriodicReportController::class, 'index'])->name('periodic.index');
            Route::put('/periodik', [PeriodicReportController::class, 'update'])->name('periodic.update');
            Route::scopeBindings()->group(function () {
                Route::post('/periodik/{periodic}/dokumen', [PeriodicDocumentController::class, 'store'])->name('periodic.documents.store');
                Route::post('/periodik/{periodic}/dokumen/{document}/ganti', [PeriodicDocumentController::class, 'replace'])->name('periodic.documents.replace');
                Route::delete('/periodik/{periodic}/dokumen/{document}', [PeriodicDocumentController::class, 'destroy'])->name('periodic.documents.destroy');
            });

            Route::scopeBindings()->group(function () {
                Route::post('/laporan/{report}/dokumen', [DocumentController::class, 'store'])->name('reports.documents.store');
                Route::post('/laporan/{report}/dokumen/{document}/ganti', [DocumentController::class, 'replace'])->name('reports.documents.replace');
                Route::put('/laporan/{report}/dokumen/{document}/jenis', [DocumentController::class, 'tag'])->name('reports.documents.tag');
                Route::delete('/laporan/{report}/dokumen/{document}', [DocumentController::class, 'destroy'])->name('reports.documents.destroy');
            });
        });

        Route::get('/dokumen/{document}/unduh/{version?}', DocumentDownloadController::class)->whereNumber('version')->name('documents.download');

        Route::prefix('pemeriksaan')->name('review.')->group(function () {
            Route::get('/', [ReviewController::class, 'index'])->name('reports.index');
            Route::get('/laporan/{report}', [ReviewController::class, 'show'])->name('reports.show');
            Route::post('/laporan/{report}/ambil', [ReviewController::class, 'claim'])->name('reports.claim');
            Route::post('/laporan/{report}/lepas', [ReviewController::class, 'release'])->name('reports.release');
            Route::post('/laporan/{report}/catatan', [ReviewController::class, 'storeNote'])->name('reports.notes.store');
            Route::post('/laporan/{report}/catatan/{note}/selesai', [ReviewController::class, 'resolveNote'])->name('reports.notes.resolve');
            Route::post('/laporan/{report}/minta-perbaikan', [ReviewController::class, 'requestRevision'])->name('reports.revise');
            Route::post('/laporan/{report}/selesai', [ReviewController::class, 'complete'])->name('reports.complete');
            Route::post('/laporan/{report}/buka-kunci', [ReviewController::class, 'unlock'])->name('reports.unlock');
        });

        Route::prefix('pemeriksa')->name('examiner.')->group(function () {
            Route::get('saldo-awal', [ExaminerOpeningBalanceController::class, 'index'])->name('opening-balances.index');
            Route::post('saldo-awal/{opening_balance}/verifikasi', [ExaminerOpeningBalanceController::class, 'verify'])->name('opening-balances.verify');
        });
    });
});
