<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;

/** Uang selalu string desimal (brick/math); format tampilan Indonesia `26.512.500,00`. */
final class Money
{
    /**
     * Menormalkan masukan berformat Indonesia ("26.512.500,50", "1000", "1000,5") menjadi "26512500.50".
     * Masukan yang tidak sah dikembalikan apa adanya agar ditolak aturan validasi.
     */
    public static function parse(mixed $input): mixed
    {
        if (! is_string($input)) {
            return $input;
        }

        $value = trim($input);
        if ($value === '') {
            return null;
        }

        if (! preg_match('/^(\d{1,3}(\.\d{3})+|\d+)(,\d{1,2})?$/', $value)) {
            return $input;
        }

        $normalized = str_replace(['.', ','], ['', '.'], $value);

        return (string) BigDecimal::of($normalized)->toScale(2);
    }

    public static function format(string|int|null $amount): string
    {
        try {
            $decimal = BigDecimal::of($amount ?? '0')->toScale(2);
        } catch (MathException) {
            return '0,00';
        }

        [$int, $frac] = explode('.', (string) $decimal->abs());
        $int = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $int);

        return ($decimal->isNegative() ? '-' : '').$int.','.$frac;
    }
}
