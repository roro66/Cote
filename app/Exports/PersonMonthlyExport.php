<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class PersonMonthlyExport implements FromArray, WithHeadings, WithTitle
{
    /** @var array<int,string> */
    private array $labels;
    /** @var array<int,float|int> */
    private array $data;
    private string $sheetTitle;

    /**
     * @param array<int,string> $labels
     * @param array<int,float|int> $data
     */
    public function __construct(array $labels, array $data, string $sheetTitle = 'Gasto mensual')
    {
        $this->labels = $labels;
        $this->data = $data;
        $this->sheetTitle = $sheetTitle;
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->labels as $i => $label) {
            $rows[] = [
                (string) $label,
                (int) round($this->data[$i] ?? 0),
            ];
        }
        return $rows;
    }

    public function headings(): array
    {
        return ['Mes', 'Total (CLP)'];
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }
}
