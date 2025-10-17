<?php

namespace App\Exports;

use App\Models\JobTitles\JobTitles;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JobTitlesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function collection()
    {
        return JobTitles::all();
    }

    public function headings(): array
    {
        return [
            'name' => 'العنوان الوظيفي'
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