<?php

namespace App\Services;

use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Menulis rekap ke XLSX. Kolom angka bertipe numerik (bukan teks); tanpa kolom NIP atau data pribadi.
 * Float hanya muncul di batas penulisan sel (XLSX tidak punya tipe desimal presisi); hitungan tetap string/brick.
 */
class RecapXlsxWriter
{
    private const NUMBER_FORMAT = '#,##0.00';

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array<string, string>  $totals
     */
    public function write(string $path, string $title, Collection $rows, array $totals): void
    {
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->getCurrentSheet()->setName('Rekap');

        $bold = new Style(fontBold: true);
        $money = new Style(format: self::NUMBER_FORMAT);
        $moneyBold = new Style(fontBold: true, format: self::NUMBER_FORMAT);

        $writer->addRow(new Row([Cell::fromValue($title, $bold)]));
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(new Row(array_map(
            fn (string $h) => Cell::fromValue($h, $bold),
            ['NPSN', 'Sekolah', 'Jenjang', 'Kecamatan', 'Laporan', 'Saldo awal', 'Penerimaan', 'Realisasi neto', 'Penyetoran ke kas daerah', 'Saldo dana akhir', 'Saldo pajak akhir'],
        )));

        foreach ($rows as $row) {
            $writer->addRow(new Row([
                Cell::fromValue($row['npsn']),
                Cell::fromValue($row['school']),
                Cell::fromValue($row['level']),
                Cell::fromValue($row['district']),
                Cell::fromValue($row['reports']),
                ...$this->moneyCells([$row['opening_fund'], $row['receipt'], $row['realization'], $row['treasury_deposit'], $row['ending_fund'], $row['ending_tax']], $money),
            ]));
        }

        $writer->addRow(new Row([
            Cell::fromValue('Total', $bold), Cell::fromValue(''), Cell::fromValue(''), Cell::fromValue(''),
            Cell::fromValue($totals['reports'], $bold),
            ...$this->moneyCells([$totals['opening_fund'], $totals['receipt'], $totals['realization'], $totals['treasury_deposit'], $totals['ending_fund'], $totals['ending_tax']], $moneyBold),
        ]));

        $writer->close();
    }

    /**
     * @param  array<int, string>  $values
     * @return array<int, Cell>
     */
    private function moneyCells(array $values, Style $style): array
    {
        return array_map(fn (string $v) => Cell::fromValue((float) $v, $style), $values);
    }
}
