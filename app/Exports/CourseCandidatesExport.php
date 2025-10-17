<?php

namespace App\Exports;

use App\Models\CourseCandidates\CourseCandidates;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CourseCandidatesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function collection()
    {
        return CourseCandidates::all();
    }

    public function headings(): array
    {
        return [
            'employee_id' => 'اسم الموظف',
            'course_id' => 'عنوان الدورة',
            'nomination_book_no' => 'رقم كتاب الترشيح',
            'nomination_book_date' => 'تاريخ كتاب الترشيح',
            'pre_training_level' => 'المستوى قبل التدريب',
            'passed' => 'هل اجتاز الدورة',
            'post_training_level' => 'المستوى بعد التدرب',
            'attendance_days' => 'عدد ايام الحضور',
            'absence_days' => 'عدد ايام الغياب',
            'notes' => 'ملاحظات'
        ];
    }

    public function map($item): array
    {
        return [
            $item->employee_id,
            $item->course_id,
            $item->nomination_book_no,
            $item->nomination_book_date,
            $item->pre_training_level,
            $item->passed,
            $item->post_training_level,
            $item->attendance_days,
            $item->absence_days,
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