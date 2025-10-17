<?php

namespace App\Exports;

use App\Models\Employees\Employees;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function collection()
    {
        return Employees::all();
    }

    public function headings(): array
    {
        return [
            'employee_name' => 'اسم الموظف',
            'gender' => 'الجنس',
            'ed_level_id' => 'التحصيل العلمي',
            'department_id' => 'القسم',
            'job_title_id' => 'العنوان الوظيفي',
            'job_grade_id' => 'الدرجة الوظيفية',
            'notes' => 'ملاحظات'
        ];
    }

    public function map($item): array
    {
        return [
            $item->employee_name,
            $item->gender,
            $item->ed_level_id,
            $item->department_id,
            $item->job_title_id,
            $item->job_grade_id,
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