<?php

namespace App\Http\Controllers\Admin;

use App\Actions\FiscalYears\CopyDocumentRequirements;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DocumentRequirementRequest;
use App\Models\DocumentRequirement;
use App\Models\DocumentType;
use App\Models\FiscalYear;
use App\Models\FundSource;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DocumentRequirementController extends Controller
{
    public function index(FiscalYear $fiscalYear): View
    {
        Gate::authorize('viewAny', DocumentRequirement::class);

        return view('admin.document-requirements.index', [
            'fiscalYear' => $fiscalYear,
            'requirements' => $fiscalYear->documentRequirements()->with(['documentType', 'fundSource'])->orderBy('report_kind')->orderBy('document_type_id')->get(),
            'documentTypes' => DocumentType::query()->orderBy('id')->get(),
            'fundSources' => FundSource::query()->where('is_active', true)->orderBy('code')->get(),
            'canEdit' => request()->user()->can('create', [DocumentRequirement::class, $fiscalYear]),
        ]);
    }

    public function store(DocumentRequirementRequest $request, FiscalYear $fiscalYear): RedirectResponse
    {
        $fiscalYear->documentRequirements()->create($request->validated());

        return redirect()->route('admin.fiscal-years.document-requirements.index', $fiscalYear)->with('success', 'Aturan dokumen tersimpan.');
    }

    public function destroy(FiscalYear $fiscalYear, DocumentRequirement $documentRequirement): RedirectResponse
    {
        Gate::authorize('delete', $documentRequirement);
        $documentRequirement->delete();

        return redirect()->route('admin.fiscal-years.document-requirements.index', $fiscalYear)->with('success', 'Aturan dokumen dihapus.');
    }

    public function copy(FiscalYear $fiscalYear, CopyDocumentRequirements $copy): RedirectResponse
    {
        Gate::authorize('create', [DocumentRequirement::class, $fiscalYear]);
        $copied = $copy->handle($fiscalYear);

        return redirect()->route('admin.fiscal-years.document-requirements.index', $fiscalYear)->with(
            'success',
            $copied > 0 ? "{$copied} aturan dokumen disalin dari tahun sebelumnya." : 'Tidak ada aturan baru untuk disalin.'
        );
    }
}
