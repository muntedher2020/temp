<?php

namespace App\Http\Controllers\ReportGenerator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ReportGenerator\ReportGenerator;
use Illuminate\Support\Facades\Auth;

class ReportGeneratorPdfController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Export PDF for Report Generator using TCPDF
     */
    public function exportPdf(Request $request)
    {
        try {
            $module = $request->get('module');
            $columns = explode(',', $request->get('columns', ''));
            $filters = json_decode($request->get('filters', '{}'), true);

            if (empty($module) || empty($columns)) {
                return response()->json(['error' => 'بيانات التصدير غير مكتملة'], 400);
            }

            // إنشاء كائن تقرير مؤقت لجلب البيانات
            $tempReport = new ReportGenerator([
                'module_name' => $module,
                'table_name' => ReportGenerator::getModuleTableName($module),
                'selected_columns' => $columns,
                'filter_values' => $filters,
                'sort_column' => 'id',
                'sort_direction' => 'desc'
            ]);

            // جلب البيانات
            $reportData = $tempReport->generateReportData();
            $moduleFields = ReportGenerator::getModuleFields($module);

            // إنشاء PDF جديد
            $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

            // إعدادات PDF
            $pdf->SetCreator('Laravel System');
            $pdf->SetAuthor('إدارة النظام');
            $pdf->SetTitle('تقرير ' . $module);
            $pdf->SetSubject('تقرير شامل لـ ' . $module);

            // إعدادات اللغة العربية
            $pdf->setLanguageArray([
                'a_meta_charset' => 'UTF-8',
                'a_meta_dir' => 'rtl',
                'a_meta_language' => 'ar',
                'w_page' => 'صفحة'
            ]);

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
            $pdf->Cell(0, 15, 'تقرير ' . $module, 0, 1, 'C');
            $pdf->Ln(5);

            // تاريخ التقرير
            $pdf->SetFont('dejavusans', '', 12);
            $pdf->Cell(0, 10, 'تاريخ التقرير: ' . now()->format('Y-m-d H:i:s'), 0, 1, 'C');
            $pdf->Ln(10);

            // رؤوس الجدول
            $pdf->SetFont('dejavusans', 'B', 10);
            $pdf->SetFillColor(74, 108, 247);
            $pdf->SetTextColor(255, 255, 255);

            // حساب عرض الأعمدة
            $cellWidth = 180 / count($columns);

            // إضافة عناوين الأعمدة
            foreach ($columns as $column) {
                $field = $moduleFields->where('field_name', $column)->first();
                $arabicName = $field ? (is_object($field) ? $field->arabic_name : $field['arabic_name']) : $column;
                $pdf->Cell($cellWidth, 10, $arabicName, 1, 0, 'C', 1);
            }
            $pdf->Ln();

            // بيانات الجدول
            $pdf->SetFont('dejavusans', '', 9);
            $pdf->SetTextColor(0, 0, 0);
            $fill = false;

            foreach($reportData as $item) {
                if($fill) {
                    $pdf->SetFillColor(248, 249, 250);
                } else {
                    $pdf->SetFillColor(255, 255, 255);
                }

                foreach ($columns as $column) {
                    $value = is_array($item) ? ($item[$column] ?? '') : ($item->$column ?? '');
                    $pdf->Cell($cellWidth, 8, $value, 1, 0, 'C', 1);
                }
                $pdf->Ln();

                $fill = !$fill;
            }

            // فوتر التقرير
            $pdf->Ln(10);
            $pdf->SetFont('dejavusans', '', 10);
            $pdf->Cell(0, 10, 'إجمالي عدد السجلات: ' . count($reportData), 0, 1, 'C');
            $pdf->Cell(0, 10, 'تم إنشاء هذا التقرير بواسطة نظام إدارة البيانات', 0, 1, 'C');
            $pdf->Cell(0, 10, '© ' . date('Y') . ' - جميع الحقوق محفوظة', 0, 1, 'C');

            return $pdf->Output('تقرير_' . $module . '_' . now()->format('Y_m_d_H_i_s') . '.pdf', 'D');

        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء إنشاء PDF: ' . $e->getMessage()], 500);
        }
    }
}
