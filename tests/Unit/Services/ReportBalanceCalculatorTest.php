<?php

use App\Services\ReportBalanceCalculator;

beforeEach(fn () => $this->calc = new ReportBalanceCalculator);

it('ReportBalance: pengembalian belanja mengurangi realisasi, penyetoran mengurangi saldo bukan belanja', function () {
    $r = $this->calc->calculate([
        'opening_fund' => '1000.00',
        'lines' => [1 => ['gross_amount' => '300.00', 'refund_amount' => '100.00']],
        'treasury_deposit' => '50.00',
    ]);

    expect($r['total_net_realization'])->toBe('200.00')->and($r['ending_fund'])->toBe('750.00');
});

it('ReportBalance: penyesuaian realisasi memengaruhi neto jenis, penerimaan/lainnya memengaruhi saldo', function () {
    $r = $this->calc->calculate([
        'opening_fund' => '1000.00',
        'lines' => [1 => ['gross_amount' => '100.00', 'refund_amount' => '0.00']],
        'adjustments' => [
            ['kind' => 'realisasi', 'amount' => '25.00', 'expense_type_id' => 1],
            ['kind' => 'penerimaan', 'amount' => '10.00'],
            ['kind' => 'lainnya', 'amount' => '-5.00'],
        ],
    ]);

    expect($r['net_by_type'][1])->toBe('125.00')
        ->and($r['total_net_realization'])->toBe('125.00')
        ->and($r['ending_fund'])->toBe('880.00');
});

it('ReportBalance: tanpa saldo BKU selisih null; tidak memakai float', function () {
    $r = $this->calc->calculate(['opening_fund' => '0.10', 'receipt_bos' => '0.20']);

    expect($r['ending_fund'])->toBe('0.30')->and($r['cash_bku'])->toBeNull()->and($r['difference'])->toBeNull();
});

it('ReportBalance: selisih = kas BKU - kas hitung', function () {
    $r = $this->calc->calculate(['opening_fund' => '100.00', 'bank_balance_bku' => '90.00', 'cash_balance_bku' => '0.00']);

    expect($r['difference'])->toBe('-10.00');
});
