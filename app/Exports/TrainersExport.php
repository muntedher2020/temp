<?php

namespace App\Exports;

use App\Models\Trainers\Trainers;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrainersExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function collection()
    {
        return Trainers::all();
    }

    public function headings(): array
    {
        return [
            'trainer_name' => 'اسم المدرب',
            'institution_id' => 'مؤسسة المدرب',
            'ed_level_id' => 'التحصيل العلمي',
            'domain_id' => 'المجال التدريبي',
            'phone' => 'رقم الهاتف',
            'email' => 'البريد الالكتروني',
            'notes' => 'ملاحظات'
        ];
    }

    public function map($item): array
    {
        return [
            $item->trainer_name,
            $item->institution_id,
            $item->ed_level_id,
            $item->domain_id,
            $item->phone,
            $item->email,
            $item->notes
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}