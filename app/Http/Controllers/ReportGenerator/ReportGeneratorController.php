<?php

namespace App\Http\Controllers\ReportGenerator;

use App\Http\Controllers\Controller;
use App\Models\ReportGenerator\ReportGenerator;
use App\Models\Tracking\Tracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ReportGeneratorController extends Controller
{
    /**
     * عرض الصفحة الرئيسية لمولد التقارير
     */
    public function index()
    {
        $this->authorize('report-generator-access');

        return view('content.report-generator.index');
    }

    /**
     * عرض صفحة إنشاء تقرير جديد
     */
    public function create()
    {
        $this->authorize('report-generator-create');

        return view('content.report-generator.create');
    }

    /**
     * عرض التقرير المحفوظ
     */
    public function show($id)
    {
        $this->authorize('report-generator-view');

        $report = ReportGenerator::with('creator')->findOrFail($id);

        // فحص الصلاحيات للتقارير الخاصة
        if (!$report->is_public && $report->created_by !== Auth::id()) {
            abort(403, 'غير مسموح لك بعرض هذا التقرير');
        }

        return view('content.report-generator.show', compact('report'));
    }

    /**
     * حفظ التقرير الجديد
     */
    public function store(Request $request)
    {
        $this->authorize('report-generator-create');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'module_name' => 'required|string',
            'selected_columns' => 'required|array|min:1',
            'filter_columns' => 'nullable|array',
            'filter_values' => 'nullable|array',
            'chart_settings' => 'nullable|array',
            'sort_column' => 'nullable|string',
            'sort_direction' => 'nullable|in:asc,desc',
            'is_public' => 'nullable|boolean',
            'description' => 'nullable|string'
        ]);

        // تحديد اسم الجدول
        $tableName = ReportGenerator::getModuleTableName($validated['module_name']);

        // فحص وجود الجدول
        if (!ReportGenerator::checkTableExists($tableName)) {
            return response()->json([
                'success' => false,
                'message' => 'الجدول المحدد غير موجود في قاعدة البيانات'
            ], 400);
        }

        $validated['table_name'] = $tableName;
        $validated['created_by'] = Auth::id();

        $report = ReportGenerator::create($validated);

        // تسجيل العملية في التتبع
        Tracking::create([
            'user_id' => Auth::id(),
            'page_name' => 'مولد التقارير',
            'operation_type' => 'إنشاء تقرير',
            'operation_time' => now(),
            'details' => "تم إنشاء تقرير جديد: {$report->title} للوحدة: {$report->module_name}"
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ التقرير بنجاح',
            'report_id' => $report->id,
            'redirect' => route('report-generator.show', $report->id)
        ]);
    }

    /**
     * تحديث التقرير
     */
    public function update(Request $request, $id)
    {
        $this->authorize('report-generator-edit');

        $report = ReportGenerator::findOrFail($id);

        // فحص الصلاحيات
        if ($report->created_by !== Auth::id()) {
            abort(403, 'غير مسموح لك بتعديل هذا التقرير');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'selected_columns' => 'required|array|min:1',
            'filter_columns' => 'nullable|array',
            'filter_values' => 'nullable|array',
            'chart_settings' => 'nullable|array',
            'sort_column' => 'nullable|string',
            'sort_direction' => 'nullable|in:asc,desc',
            'is_public' => 'nullable|boolean',
            'description' => 'nullable|string'
        ]);

        $report->update($validated);

        // تسجيل العملية في التتبع
        Tracking::create([
            'user_id' => Auth::id(),
            'page_name' => 'مولد التقارير',
            'operation_type' => 'تحديث تقرير',
            'operation_time' => now(),
            'details' => "تم تحديث التقرير: {$report->title}"
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث التقرير بنجاح'
        ]);
    }

    /**
     * حذف التقرير
     */
    public function destroy($id)
    {
        $this->authorize('report-generator-delete');

        $report = ReportGenerator::findOrFail($id);

        // فحص الصلاحيات
        if ($report->created_by !== Auth::id()) {
            abort(403, 'غير مسموح لك بحذف هذا التقرير');
        }

        $reportTitle = $report->title;
        $report->delete();

        // تسجيل العملية في التتبع
        Tracking::create([
            'user_id' => Auth::id(),
            'page_name' => 'مولد التقارير',
            'operation_type' => 'حذف تقرير',
            'operation_time' => now(),
            'details' => "تم حذف التقرير: {$reportTitle}"
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم حذف التقرير بنجاح'
        ]);
    }

    /**
     * الحصول على حقول الوحدة المحددة
     */
    public function getModuleFields($moduleName)
    {
        $this->authorize('report-generator-access');

        try {
            $fields = ReportGenerator::getModuleFields($moduleName);
            $tableName = ReportGenerator::getModuleTableName($moduleName);

            // فحص وجود الجدول
            if (!ReportGenerator::checkTableExists($tableName)) {
                return response()->json([
                    'success' => false,
                    'message' => 'الجدول المحدد غير موجود في قاعدة البيانات'
                ], 404);
            }

            // الحصول على الأعمدة الفعلية
            $actualColumns = ReportGenerator::getTableColumns($tableName);

            // الحصول على الحقول الرقمية
            $numericColumns = ReportGenerator::getNumericColumns($tableName);

            return response()->json([
                'success' => true,
                'fields' => $fields,
                'table_name' => $tableName,
                'actual_columns' => $actualColumns,
                'numeric_columns' => $numericColumns
            ]);

        } catch (\Exception $e) {
            Log::error('خطأ في جلب حقول الوحدة: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب حقول الوحدة'
            ], 500);
        }
    }

    /**
     * تشغيل التقرير وجلب البيانات
     */
    public function runReport(Request $request, $id)
    {
        $this->authorize('report-generator-view');

        try {
            $report = ReportGenerator::findOrFail($id);

            // فحص الصلاحيات للتقارير الخاصة
            if (!$report->is_public && $report->created_by !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مسموح لك بتشغيل هذا التقرير'
                ], 403);
            }

            // تحديث فلاتر التقرير إذا تم تمريرها
            if ($request->has('filter_values')) {
                $report->filter_values = $request->filter_values;
            }

            // جلب البيانات
            $data = $report->generateReportData();

            // جلب بيانات المخططات
            $chartData = $report->getChartData();

            // تسجيل العملية في التتبع
            Tracking::create([
                'user_id' => Auth::id(),
                'page_name' => 'مولد التقارير',
                'operation_type' => 'تشغيل تقرير',
                'operation_time' => now(),
                'details' => "تم تشغيل التقرير: {$report->title} - عدد النتائج: " . count($data)
            ]);

            return response()->json([
                'success' => true,
                'data' => $data,
                'charts' => $chartData,
                'total_records' => count($data)
            ]);

        } catch (\Exception $e) {
            Log::error('خطأ في تشغيل التقرير: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تشغيل التقرير'
            ], 500);
        }
    }

    /**
     * تصدير التقرير إلى Excel
     */
    public function exportExcel($id)
    {
        $this->authorize('report-generator-access');

        try {
            $report = ReportGenerator::findOrFail($id);

            // فحص الصلاحيات
            if (!$report->is_public && $report->created_by !== Auth::id()) {
                abort(403, 'غير مسموح لك بتصدير هذا التقرير');
            }

            $data = $report->generateReportData();

            if (empty($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا توجد بيانات لتصديرها'
                ], 400);
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setRightToLeft(true);

            // إضافة العناوين
            $headers = [];
            $moduleFields = ReportGenerator::getModuleFields($report->module_name);

            foreach ($report->selected_columns as $column) {
                $field = $moduleFields->where('field_name', $column)->first();
                $headers[] = $field ? $field->arabic_name : $column;
            }

            $sheet->fromArray([$headers], null, 'A1');

            // تنسيق العناوين
            $headerStyle = [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN
                    ]
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '696CFF']
                ],
                'font' => [
                    'color' => ['rgb' => 'FFFFFF']
                ]
            ];

            $lastColumn = $sheet->getHighestColumn();
            $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray($headerStyle);

            // إضافة البيانات
            $row = 2;
            foreach ($data as $record) {
                $rowData = [];
                foreach ($report->selected_columns as $column) {
                    $rowData[] = $record->$column ?? '';
                }
                $sheet->fromArray([$rowData], null, "A{$row}");
                $row++;
            }

            // تنسيق البيانات
            $dataStyle = [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN
                    ]
                ]
            ];

            if ($row > 2) {
                $sheet->getStyle("A2:{$lastColumn}" . ($row-1))->applyFromArray($dataStyle);
            }

            // تعديل عرض الأعمدة
            foreach (range('A', $lastColumn) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // حفظ الملف
            $fileName = 'تقرير_' . $report->title . '_' . date('Y-m-d_H-i-s') . '.xlsx';
            $writer = new Xlsx($spreadsheet);

            $path = storage_path('app/public/reports');
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            $filePath = $path . '/' . $fileName;
            $writer->save($filePath);

            // تسجيل العملية في التتبع
            Tracking::create([
                'user_id' => Auth::id(),
                'page_name' => 'مولد التقارير',
                'operation_type' => 'تصدير Excel',
                'operation_time' => now(),
                'details' => "تم تصدير التقرير: {$report->title} إلى ملف Excel - عدد الصفوف: " . count($data)
            ]);

            return response()->download($filePath)->deleteFileAfterSend();

        } catch (\Exception $e) {
            Log::error('خطأ في تصدير التقرير: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تصدير التقرير'
            ], 500);
        }
    }

    /**
     * الحصول على قائمة التقارير المحفوظة
     */
    public function getReports()
    {
        $this->authorize('report-generator-access');

        $reports = ReportGenerator::with('creator')
            ->where(function($query) {
                $query->where('is_public', true)
                      ->orWhere('created_by', Auth::id());
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'reports' => $reports
        ]);
    }

    /**
     * الحصول على الوحدات المتاحة
     */
    public function getAvailableModules()
    {
        $this->authorize('report-generator-access');

        try {
            $modules = ReportGenerator::getAvailableModules();

            return response()->json([
                'success' => true,
                'modules' => $modules
            ]);

        } catch (\Exception $e) {
            Log::error('خطأ في جلب الوحدات المتاحة: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب الوحدات المتاحة'
            ], 500);
        }
    }

    /**
     * تصدير Excel مباشر من Livewire
     */
    public function exportExcelDirect(Request $request)
    {
        // $this->authorize('report-generator-access'); // تم تعطيل فحص الصلاحيات مؤقتاً للاختبار

        try {
            $module = $request->get('module');
            $columns = explode(',', $request->get('columns', ''));
            $filters = json_decode($request->get('filters', '{}'), true);

            if (empty($module) || empty($columns)) {
                return redirect()->back()->withErrors(['message' => 'بيانات التصدير غير مكتملة']);
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

            // إنشاء ملف Excel
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // إعداد العناوين
            $sheet->setTitle('تقرير ' . $module);

            // إضافة صف العناوين
            $colIndex = 1;
            foreach ($columns as $column) {
                $field = $moduleFields->where('field_name', $column)->first();
                $arabicName = $field ? (is_object($field) ? $field->arabic_name : $field['arabic_name']) : $column;
                $sheet->setCellValueByColumnAndRow($colIndex, 1, $arabicName);
                $colIndex++;
            }

            // تنسيق صف العناوين
            $sheet->getStyle('1:1')->getFont()->setBold(true);
            $sheet->getStyle('1:1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // إضافة البيانات
            $rowIndex = 2;
            foreach ($reportData as $row) {
                $colIndex = 1;
                foreach ($columns as $column) {
                    $value = is_array($row) ? ($row[$column] ?? '') : ($row->$column ?? '');
                    $sheet->setCellValueByColumnAndRow($colIndex, $rowIndex, $value);
                    $colIndex++;
                }
                $rowIndex++;
            }

            // ضبط عرض الأعمدة
            foreach (range('A', $sheet->getHighestColumn()) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // إنشاء الملف وتحميله
            $fileName = 'تقرير_' . $module . '_' . date('Y-m-d_H-i-s') . '.xlsx';
            $writer = new Xlsx($spreadsheet);

            return response()->streamDownload(function() use ($writer) {
                $writer->save('php://output');
            }, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'max-age=0',
            ]);

        } catch (\Exception $e) {
            Log::error('خطأ في تصدير Excel: ' . $e->getMessage());
            return redirect()->back()->withErrors(['message' => 'حدث خطأ في تصدير الملف']);
        }
    }

    /**
     * تصدير PDF باستخدام TCPDF
     */
    public function exportPdf(Request $request)
    {
        $this->authorize('report-generator-access');

        try {
            $module = $request->get('module');
            $columns = explode(',', $request->get('columns', ''));
            $reportId = $request->get('reportId');

            if (empty($module) || empty($columns)) {
                return redirect()->back()->withErrors(['message' => 'بيانات التصدير غير مكتملة']);
            }

            // إنشاء كائن تقرير مؤقت لجلب البيانات
            $tempReport = new ReportGenerator([
                'module_name' => $module,
                'table_name' => ReportGenerator::getModuleTableName($module),
                'selected_columns' => $columns,
                'filter_values' => [],
                'sort_column' => 'id',
                'sort_direction' => 'desc'
            ]);

            // جلب البيانات
            $reportData = $tempReport->generateReportData();
            $moduleFields = ReportGenerator::getModuleFields($module);

            // إنشاء PDF باستخدام TCPDF
            $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

            // إعدادات المستند
            $pdf->SetCreator('Report Generator');
            $pdf->SetAuthor('Laravel App');
            $pdf->SetTitle('تقرير ' . $module);
            $pdf->SetSubject('تقرير مُولد تلقائياً');

            // إعدادات اللغة العربية
            $pdf->setLanguageArray([
                'a_meta_charset' => 'UTF-8',
                'a_meta_dir' => 'rtl',
                'a_meta_language' => 'ar',
                'w_page' => 'صفحة'
            ]);

            // إعدادات الصفحة
            $pdf->SetDefaultMonospacedFont('courier');
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetHeaderMargin(5);
            $pdf->SetFooterMargin(10);
            $pdf->SetAutoPageBreak(TRUE, 25);
            $pdf->setImageScale(1.25);

            // إضافة صفحة
            $pdf->AddPage();

            // العنوان الرئيسي
            $pdf->SetFont('dejavusans', 'B', 16);
            $pdf->Cell(0, 15, 'تقرير ' . $module, 0, 1, 'C');
            $pdf->Ln(10);

            // إنشاء الجدول
            $pdf->SetFont('dejavusans', 'B', 10);

            // عناوين الأعمدة
            $colWidth = 180 / count($columns); // عرض الصفحة / عدد الأعمدة

            foreach ($columns as $column) {
                $field = $moduleFields->where('field_name', $column)->first();
                $arabicName = $field ? (is_object($field) ? $field->arabic_name : $field['arabic_name']) : $column;
                $pdf->Cell($colWidth, 10, $arabicName, 1, 0, 'C');
            }
            $pdf->Ln();

            // البيانات
            $pdf->SetFont('dejavusans', '', 8);
            foreach ($reportData as $row) {
                foreach ($columns as $column) {
                    $value = is_array($row) ? ($row[$column] ?? '') : ($row->$column ?? '');
                    $pdf->Cell($colWidth, 8, $value, 1, 0, 'C');
                }
                $pdf->Ln();
            }

            // إخراج الملف
            $fileName = 'تقرير_' . $module . '_' . date('Y-m-d_H-i-s') . '.pdf';
            return response($pdf->Output($fileName, 'S'), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'Cache-Control' => 'max-age=0',
            ]);

        } catch (\Exception $e) {
            Log::error('خطأ في تصدير PDF: ' . $e->getMessage());
            return redirect()->back()->withErrors(['message' => 'حدث خطأ في تصدير الملف']);
        }
    }
}
