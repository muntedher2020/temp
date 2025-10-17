<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employees\Employees as EmployeeModel;
use Elibyy\TCPDF\Facades\TCPDF;

class EmployeeTcpdfExportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:employee-export-pdf');
    }

    /**
     * Export PDF for Employees using TCPDF
     */
    public function exportPdf()
    {
        try {
            $data = EmployeeModel::all();

            // إنشاء PDF جديد
            $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

            // إعدادات PDF
            $pdf->SetCreator('Laravel System');
            $pdf->SetAuthor('إدارة النظام');
            $pdf->SetTitle('تقرير الموظفين');
            $pdf->SetSubject('تقرير شامل لـ الموظفين');

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
            $pdf->Cell(0, 15, 'تقرير الموظفين', 0, 1, 'C');
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
            $pdf->Cell(24, 10, 'اسم الموظف', 1, 0, 'C', 1);
            $pdf->Cell(24, 10, 'الجنس', 1, 0, 'C', 1);
            $pdf->Cell(24, 10, 'التحصيل العلمي', 1, 0, 'C', 1);
            $pdf->Cell(24, 10, 'القسم', 1, 0, 'C', 1);
            $pdf->Cell(24, 10, 'العنوان الوظيفي', 1, 0, 'C', 1);
            $pdf->Cell(24, 10, 'الدرجة الوظيفية', 1, 0, 'C', 1);
            $pdf->Cell(24, 10, 'ملاحظات', 1, 1, 'C', 1);

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
                $pdf->Cell(24, 8, $item->employee_name ?? 'غير محدد', 1, 0, 'C', 1);
                $pdf->Cell(24, 8, $item->gender ?? 'غير محدد', 1, 0, 'C', 1);
                $pdf->Cell(24, 8, $item->ed_level_id ?? 'غير محدد', 1, 0, 'C', 1);
                $pdf->Cell(24, 8, $item->department_id ?? 'غير محدد', 1, 0, 'C', 1);
                $pdf->Cell(24, 8, $item->job_title_id ?? 'غير محدد', 1, 0, 'C', 1);
                $pdf->Cell(24, 8, $item->job_grade_id ?? 'غير محدد', 1, 0, 'C', 1);
                $pdf->Cell(24, 8, $item->notes ?? 'غير محدد', 1, 1, 'C', 1);

                $fill = !$fill;
            }

            // فوتر التقرير
            $pdf->Ln(10);
            $pdf->SetFont('dejavusans', '', 10);
            $pdf->Cell(0, 10, 'إجمالي عدد السجلات: ' . count($data), 0, 1, 'C');
            $pdf->Cell(0, 10, 'تم إنشاء هذا التقرير بواسطة نظام إدارة البيانات', 0, 1, 'C');
            $pdf->Cell(0, 10, '© ' . date('Y') . ' - جميع الحقوق محفوظة', 0, 1, 'C');

            return $pdf->Output('تقرير_الموظفين_' . now()->format('Y_m_d_H_i_s') . '.pdf', 'D');

        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء إنشاء PDF: ' . $e->getMessage()], 500);
        }
    }
}