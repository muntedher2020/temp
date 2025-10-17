<?php

namespace App\Exports;

use App\Models\TrainingDomains\TrainingDomains;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrainingDomainsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function collection()
    {
        return TrainingDomains::all();
    }

    public function headings(): array
    {
        return [
            'name' => 'اسم المجال'
        ];
    }

    public function map($item): array
    {
        return [
            $item->name
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}