<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class ReportGeneratorExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $data;
    protected $columns;
    protected $headings;

    public function __construct($data, $columns, $headings = [])
    {
        $this->data = collect($data);
        $this->columns = $columns;
        $this->headings = $headings;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->headings ?: $this->columns;
    }

    public function map($item): array
    {
        $mappedData = [];
        foreach ($this->columns as $column) {
            if (is_array($item)) {
                $mappedData[] = $item[$column] ?? '';
            } else {
                $mappedData[] = $item->{$column} ?? '';
            }
        }
        return $mappedData;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4A6CF7']
                ],
                'font' => [
                    'color' => ['rgb' => 'FFFFFF'],
                    'bold' => true
                ]
            ],
        ];
    }
}
