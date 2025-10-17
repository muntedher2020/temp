<?php

namespace App\Exports;

use App\Models\Courses\Courses;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CoursesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function collection()
    {
        return Courses::all();
    }

    public function headings(): array
    {
        return [
            'course_title' => 'عنوان الدورة',
            'trainer_id' => 'اسم المدرب',
            'domain_id' => 'المجال التدريبي',
            'program_manager_id' => 'مدير البرنامج التدريبي',
            'venue_id' => 'مكان انعقاد الدورة',
            'duration_days' => 'مدة الدورة',
            'course_book_no' => 'رقم كتاب الدورة',
            'course_book_date' => 'تاريخ كتاب الدورة',
            'course_book_image_path' => 'ملف كتاب الدورة',
            'postpone_book_no' => 'رقم كتاب التاجيل',
            'postpone_book_date' => 'تاريخ كتاب التاجيل',
            'postpone_book_image_path' => 'ملف كتاب التاجيل',
            'notes' => 'ملاحظات'
        ];
    }

    public function map($item): array
    {
        return [
            $item->course_title,
            $item->trainer_id,
            $item->domain_id,
            $item->program_manager_id,
            $item->venue_id,
            $item->duration_days,
            $item->course_book_no,
            $item->course_book_date,
            $item->course_book_image_path,
            $item->postpone_book_no,
            $item->postpone_book_date,
            $item->postpone_book_image_path,
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