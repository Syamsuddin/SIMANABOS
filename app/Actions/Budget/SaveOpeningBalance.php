<?php

namespace App\Actions\Budget;

use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\OpeningBalance;
use App\Models\School;

/** Saldo awal Januari tahun pertama diisi manual operator; verifikasi oleh pemeriksa terpisah. */
class SaveOpeningBalance
{
    public function __invoke(School $school, FiscalYear $fiscalYear, FundSource $fundSource, string $fundAmount, string $taxAmount): OpeningBalance
    {
        return OpeningBalance::query()->withoutGlobalScopes()->updateOrCreate(
            [
                'school_id' => $school->id,
                'fiscal_year_id' => $fiscalYear->id,
                'fund_source_id' => $fundSource->id,
            ],
            [
                'fund_amount' => $fundAmount,
                'tax_amount' => $taxAmount,
                'origin' => OpeningBalance::ORIGIN_MANUAL,
            ],
        );
    }
}
