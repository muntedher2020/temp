<?php

namespace App\Http\Controllers\Venues;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Venues\Venues as VenueModel;
use Elibyy\TCPDF\Facades\TCPDF;

class VenueTcpdfExportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:venue-export-pdf');
    }

    /**
     * Export PDF for Venues using TCPDF
     */
    public function exportPdf()
    {
        try {
            $data = VenueModel::all();

            // إنشاء PDF جديد
            $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

            // إعدادات PDF
            $pdf->SetCreator('Laravel System');
            $pdf->SetAuthor('إدارة النظام');
            $pdf->SetTitle('تقرير مكان انعقاد الدورة');
            $pdf->SetSubject('تقرير شامل لـ مكان انعقاد الدورة');

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
            $pdf->Cell(0, 15, 'تقرير مكان انعقاد الدورة', 0, 1, 'C');
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
            $pdf->Cell(170, 10, 'اسم المكان', 1, 1, 'C', 1);

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
                $pdf->Cell(170, 8, $item->name ?? 'غير محدد', 1, 1, 'C', 1);

                $fill = !$fill;
            }

            // فوتر التقرير
            $pdf->Ln(10);
            $pdf->SetFont('dejavusans', '', 10);
            $pdf->Cell(0, 10, 'إجمالي عدد السجلات: ' . count($data), 0, 1, 'C');
            $pdf->Cell(0, 10, 'تم إنشاء هذا التقرير بواسطة نظام إدارة البيانات', 0, 1, 'C');
            $pdf->Cell(0, 10, '© ' . date('Y') . ' - جميع الحقوق محفوظة', 0, 1, 'C');

            return $pdf->Output('تقرير_مكان انعقاد الدورة_' . now()->format('Y_m_d_H_i_s') . '.pdf', 'D');

        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء إنشاء PDF: ' . $e->getMessage()], 500);
        }
    }
}