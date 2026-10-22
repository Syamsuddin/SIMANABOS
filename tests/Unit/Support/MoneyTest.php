<?php

use App\Support\Money;

it('menormalkan masukan format Indonesia', function (string $input, string $expected) {
    expect(Money::parse($input))->toBe($expected);
})->with([
    ['26.512.500,00', '26512500.00'],
    ['26.512.500', '26512500.00'],
    ['1000', '1000.00'],
    ['1000,5', '1000.50'],
    ['0', '0.00'],
]);

it('mengembalikan masukan tidak sah apa adanya dan null untuk kosong', function () {
    expect(Money::parse('abc'))->toBe('abc')
        ->and(Money::parse('-5'))->toBe('-5')
        ->and(Money::parse('1.5'))->toBe('1.5')
        ->and(Money::parse('  '))->toBeNull();
});

it('memformat ke gaya Indonesia', function () {
    expect(Money::format('26512500.00'))->toBe('26.512.500,00')
        ->and(Money::format('0'))->toBe('0,00')
        ->and(Money::format(null))->toBe('0,00')
        ->and(Money::format('999.5'))->toBe('999,50');
});
