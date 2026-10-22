<?php

use App\Services\ReportBalanceCalculator;

// Golden case docs/04 §Golden case — tidak boleh diubah angkanya.
it('GoldenFebruary: menghasilkan angka persis docs/04', function () {
    $result = (new ReportBalanceCalculator)->calculate([
        'opening_fund' => '50000000.00', 'opening_tax' => '0.00',
        'receipt_bos' => '0.00', 'receipt_other' => '12500.00',
        'lines' => [
            1 => ['gross_amount' => '18000000.00', 'refund_amount' => '500000.00'],
            2 => ['gross_amount' => '6000000.00', 'refund_amount' => '0.00'],
        ],
        'treasury_deposit' => '0.00',
        'tax_withheld' => '1200000.00', 'tax_remitted' => '900000.00',
        'bank_balance_bku' => '26000000.00', 'cash_balance_bku' => '812500.00',
    ]);

    expect($result['total_net_realization'])->toBe('23500000.00')
        ->and($result['ending_fund'])->toBe('26512500.00')
        ->and($result['ending_tax'])->toBe('300000.00')
        ->and($result['cash_calculated'])->toBe('26812500.00')
        ->and($result['cash_bku'])->toBe('26812500.00')
        ->and($result['difference'])->toBe('0.00');
});
