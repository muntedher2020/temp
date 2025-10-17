<?php

namespace App\Http\Controllers\Courses;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Courses\Courses as CourseModel;
use Elibyy\TCPDF\Facades\TCPDF;

class CourseTcpdfExportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:course-export-pdf');
    }

    /**
     * Export PDF for Courses using TCPDF
     */
    public function exportPdf()
    {
        try {
            $data = CourseModel::all();

            // إنشاء PDF جديد
            $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

            // إعدادات PDF
            $pdf->SetCreator('Laravel System');
            $pdf->SetAuthor('إدارة النظام');
            $pdf->SetTitle('تقرير الدورات التدريبية');
            $pdf->SetSubject('تقرير شامل لـ الدورات التدريبية');

            // إعدادات اللغة العربية
            $pdf->setLanguageArray(array(
                'a_meta_charset' => 'UTF-8',
                'a_meta_dir' => 'rtl',
                'a_meta_language' => 'ar',
                'w_page' => 'صفحة'
            ));

            // إعدادات الخط العربي
            $pdf->SetFont('dejavusans', '', 12);

            // إعدادات الهوامش
            $pdf->SetMargins(15, 20, 15);
            $pdf->SetHeaderMargin(10);
            $pdf->SetFooterMargin(10);

            // تعطيل الهيدر والفوتر الافتراضي
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // إضافة صفحة
            $pdf->AddPage();

            // العنوان الرئيسي
            $pdf->SetFont('dejavusans', 'B', 20);
            $pdf->setRTL(true);
            $pdf->Cell(0, 15, 'تقرير الدورات التدريبية', 0, 1, 'C');
            $pdf->Ln(5);

            // تاريخ التقرير
            $pdf->SetFont('dejavusans', '', 12);
            $pdf->Cell(0, 10, 'تاريخ التقرير: ' . now()->format('Y-m-d H:i:s'), 0, 1, 'C');
            $pdf->Ln(10);

            // رؤوس الجدول
            $pdf->SetFont('dejavusans', 'B', 10);
            $pdf->SetFillColor(74, 108, 247);
            $pdf->SetTextColor(255, 255, 255);

            // Add table headers dynamically based on fields
            
            $pdf->Cell(20, 10, 'الرقم', 1, 0, 'C', 1);
            $pdf->Cell(13, 10, 'عنوان الدورة', 1, 0, 'C', 1);
            $pdf->Cell(13, 10, 'اسم المدرب', 1, 0, 'C', 1);
            $pdf->Cell(13, 10, 'المجال التدريبي', 1, 0, 'C', 1);
            $pdf->Cell(13, 10, 'مدير البرنامج التدريبي', 1, 0, 'C', 1);
            $pdf->Cell(13, 10, 'مكان انعقاد الدورة', 1, 0, 'C', 1);
            $pdf->Cell(13, 10, 'مدة الدورة', 1, 0, 'C', 1);
            $pdf->Cell(13, 10, 'رقم كتاب الدورة', 1, 0, 'C', 1);
            $pdf->Cell(13, 10, 'تاريخ كتاب الدورة', 1, 0, 'C', 1);
            $pdf->Cell(13, 10, 'ملف كتاب الدورة', 1, 0, 'C', 1);
            $pdf->Cell(13, 10, 'رقم كتاب التاجيل', 1, 0, 'C', 1);
            $pdf->Cell(13, 10, 'تاريخ كتاب التاجيل', 1, 0, 'C', 1);
            $pdf->Cell(13, 10, 'ملف كتاب التاجيل', 1, 0, 'C', 1);
            $pdf->Cell(13, 10, 'ملاحظات', 1, 1, 'C', 1);

            // بيانات الجدول
            $pdf->SetFont('dejavusans', '', 9);
            $pdf->SetTextColor(0, 0, 0);
            $fill = false;

            foreach($data as $item) {
                if($fill) {
                    $pdf->SetFillColor(248, 249, 250);
                } else {
                    $pdf->SetFillColor(255, 255, 255);
                }

                // Add table data dynamically based on fields
                
                $pdf->Cell(20, 8, $item->id ?? '', 1, 0, 'C', 1);
                $pdf->Cell(13, 8, $item->course_title ?? 'غير محدد', 1, 0, 'C', 1);
                $pdf->Cell(13, 8, $item->trainer_id ?? 'غير محدد', 1, 0, 'C', 1);
                $pdf->Cell(13, 8, $item->domain_id ?? 'غير محدد', 1, 0, 'C', 1);
                $pdf->Cell(13, 8, $item->program_manager_id ?? 'غير محدد', 1, 0, 'C', 1);
                $pdf->Cell(13, 8, $item->venue_id ?? 'غير محدد', 1, 0, 'C', 1);
                $pdf->Cell(13, 8, $item->duration_days ?? 'غير محدد', 1, 0, 'C', 1);
                $pdf->Cell(13, 8, $item->course_book_no ?? 'غير محدد', 1, 0, 'C', 1);
                $pdf->Cell(13, 8, $item->course_book_date ? \Carbon\Carbon::parse($item->course_book_date)->format('Y/m/d') : 'غير محدد', 1, 0, 'C', 1);
                $pdf->Cell(13, 8, $item->course_book_image_path ?? 'غير محدد', 1, 0, 'C', 1);
                $pdf->Cell(13, 8, $item->postpone_book_no ?? 'غير محدد', 1, 0, 'C', 1);
                $pdf->Cell(13, 8, $item->postpone_book_date ? \Carbon\Carbon::parse($item->postpone_book_date)->format('Y/m/d') : 'غير محدد', 1, 0, 'C', 1);
                $pdf->Cell(13, 8, $item->postpone_book_image_path ?? 'غير محدد', 1, 0, 'C', 1);
                $pdf->Cell(13, 8, $item->notes ?? 'غير محدد', 1, 1, 'C', 1);

                $fill = !$fill;
            }

            // فوتر التقرير
            $pdf->Ln(10);
            $pdf->SetFont('dejavusans', '', 10);
            $pdf->Cell(0, 10, 'إجمالي عدد السجلات: ' . count($data), 0, 1, 'C');
            $pdf->Cell(0, 10, 'تم إنشاء هذا التقرير بواسطة نظام إدارة البيانات', 0, 1, 'C');
            $pdf->Cell(0, 10, '© ' . date('Y') . ' - جميع الحقوق محفوظة', 0, 1, 'C');

            return $pdf->Output('تقرير_الدورات التدريبية_' . now()->format('Y_m_d_H_i_s') . '.pdf', 'D');

        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء إنشاء PDF: ' . $e->getMessage()], 500);
        }
    }
}