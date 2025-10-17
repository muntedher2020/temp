<?php

namespace App\Http\Livewire\ReportGenerator;

use App\Models\ReportGenerator\ReportGenerator;
use App\Models\Tracking\Tracking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportGeneratorExport;

class ReportGeneratorManager extends Component
{
    use WithPagination;

    // خصائص المكون
    public $modules = [];
    public $selectedModule = '';
    public $moduleFields = [];
    public $availableColumns = [];
    public $selectedColumns = [];
    public $numericColumns = [];

    // إعدادات التقرير
    public $reportTitle = '';
    public $description = '';
    public $isPublic = false;

    // فلاتر البيانات
    public $filterColumns = [];
    public $filterValues = [];
    public $sortColumn = '';
    public $sortDirection = 'asc';

    // إعدادات المخططات
    public $chartSettings = [];
    public $enableCharts = false;
    public $chartColumns = [];

    // حالة التطبيق
    public $currentStep = 1;
    public $reportData = [];
    public $savedReports = [];
    public $selectedReport = null;
    public $showReportModal = false;
    public $isLoading = false;
    public $errorMessage = '';
    public $successMessage = '';
    public $reportToDelete = null;

    protected $rules = [
        'reportTitle' => 'required|string|max:255',
        'selectedModule' => 'required|string',
        'selectedColumns' => 'required|array|min:1',
        'description' => 'nullable|string',
    ];

    protected $messages = [
        'reportTitle.required' => 'اسم التقرير مطلوب',
        'selectedModule.required' => 'يجب اختيار وحدة',
        'selectedColumns.required' => 'يجب اختيار عمود واحد على الأقل',
        'selectedColumns.min' => 'يجب اختيار عمود واحد على الأقل',
    ];

    protected $listeners = [
        'getReportForDeletion' => 'getReportForDeletion'
    ];

    public function mount()
    {
        $this->loadModules();
        $this->loadSavedReports();
    }

    /**
     * تحميل الوحدات المتاحة
     */
    public function loadModules()
    {
        try {
            $this->modules = ReportGenerator::getAvailableModules();
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('error', [
                'message' => 'خطأ في تحميل الوحدات المتاحة',
                'title' => 'خطأ في التحميل'
            ]);
        }
    }

    /**
     * تحميل التقارير المحفوظة
     */
    public function loadSavedReports()
    {
        $this->savedReports = ReportGenerator::with('creator')
            ->where(function($query) {
                $query->where('is_public', true)
                      ->orWhere('created_by', Auth::id());
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * الحصول على الأعمدة الرقمية فقط للمخططات
     */
    public function getNumericColumnsProperty()
    {
        if (!$this->selectedModule || !$this->moduleFields) {
            return collect([]);
        }

        $tableName = ReportGenerator::getModuleTableName($this->selectedModule);
        if (!$tableName) {
            return collect([]);
        }

        return collect($this->moduleFields)->filter(function ($field) use ($tableName) {
            $fieldName = is_object($field) ? $field->field_name : $field['field_name'];
            return ReportGenerator::isNumericColumn($tableName, $fieldName);
        });
    }

    /**
     * عند تغيير الوحدة المحددة
     */
    public function updatedSelectedModule()
    {
        if ($this->selectedModule) {
            $this->loadModuleFields();
            $this->resetFilters();
            $this->updateNumericColumns();
        }
    }

    /**
     * تحديث الأعمدة الرقمية
     */
    public function updateNumericColumns()
    {
        if (!$this->selectedModule || !$this->moduleFields) {
            $this->numericColumns = [];
            return;
        }

        $tableName = ReportGenerator::getModuleTableName($this->selectedModule);
        if (!$tableName) {
            $this->numericColumns = [];
            return;
        }

        $this->numericColumns = collect($this->moduleFields)->filter(function ($field) use ($tableName) {
            $fieldName = is_object($field) ? $field->field_name : $field['field_name'];
            return ReportGenerator::isNumericColumn($tableName, $fieldName);
        })->pluck('field_name')->toArray();
    }

    /**
     * تحميل حقول الوحدة
     */
    public function loadModuleFields($preserveSelections = false)
    {
        try {
            $this->isLoading = true;

            // التحقق من وجود اسم الوحدة
            if (empty($this->selectedModule)) {
                $this->dispatchBrowserEvent('error', [
                    'message' => 'لم يتم تحديد اسم الوحدة',
                    'title' => 'خطأ في التحميل'
                ]);
                return;
            }

            $fields = ReportGenerator::getModuleFields($this->selectedModule);
            $tableName = ReportGenerator::getModuleTableName($this->selectedModule);

            // التحقق من وجود الحقول
            if (empty($fields) || $fields->isEmpty()) {
                $this->dispatchBrowserEvent('error', [
                    'message' => "لا توجد حقول نشطة للوحدة: {$this->selectedModule}",
                    'title' => 'لا توجد حقول'
                ]);
                return;
            }

            if (!ReportGenerator::checkTableExists($tableName)) {
                $this->dispatchBrowserEvent('error', [
                    'message' => 'الجدول المحدد غير موجود في قاعدة البيانات',
                    'title' => 'خطأ في قاعدة البيانات'
                ]);
                return;
            }

            $this->moduleFields = $fields;
            $this->availableColumns = ReportGenerator::getTableColumns($tableName);
            $this->numericColumns = ReportGenerator::getNumericColumns($tableName);

            // إعادة تعيين الأعمدة المحددة فقط إذا لم نكن نريد الحفاظ على التحديدات
            if (!$preserveSelections) {
                $this->selectedColumns = [];
                $this->filterColumns = [];
                $this->chartColumns = [];
            }

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('error', [
                'message' => 'خطأ في تحميل حقول الوحدة: ' . $e->getMessage(),
                'title' => 'خطأ في التحميل'
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * إضافة عمود للفلترة
     */
    public function addFilterColumn()
    {
        $this->filterColumns[] = [
            'column' => '',
            'operator' => '=',
            'value' => ''
        ];
    }

    /**
     * حذف عمود فلترة
     */
    public function removeFilterColumn($index)
    {
        unset($this->filterColumns[$index]);
        $this->filterColumns = array_values($this->filterColumns);
    }

    /**
     * إضافة مخطط
     */
    public function addChart()
    {
        $this->chartSettings[] = [
            'type' => 'bar',
            'columns' => [], // تغيير من column واحد إلى مجموعة أعمدة
            'xAxisField' => '', // حقل المحور X
            'title' => '',
            'colors' => ['#696CFF', '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD', '#98D8C8'], // ألوان متعددة
            'showLegend' => true,
            'chartHeight' => 300
        ];
    }

    /**
     * حذف مخطط
     */
    public function removeChart($index)
    {
        unset($this->chartSettings[$index]);
        $this->chartSettings = array_values($this->chartSettings);
    }

    /**
     * إضافة حقل للمخطط
     */
    public function addColumnToChart($chartIndex)
    {
        if (isset($this->chartSettings[$chartIndex])) {
            $this->chartSettings[$chartIndex]['columns'][] = [
                'field' => '',
                'label' => '',
                'color' => $this->getNextColor($chartIndex)
            ];
        }
    }

    /**
     * حذف حقل من المخطط
     */
    public function removeColumnFromChart($chartIndex, $columnIndex)
    {
        if (isset($this->chartSettings[$chartIndex]['columns'][$columnIndex])) {
            unset($this->chartSettings[$chartIndex]['columns'][$columnIndex]);
            $this->chartSettings[$chartIndex]['columns'] = array_values($this->chartSettings[$chartIndex]['columns']);
        }
    }

    /**
     * الحصول على اللون التالي للحقل الجديد
     */
    private function getNextColor($chartIndex)
    {
        $colors = $this->chartSettings[$chartIndex]['colors'] ?? ['#696CFF', '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD', '#98D8C8'];
        $currentColumnsCount = count($this->chartSettings[$chartIndex]['columns'] ?? []);
        return $colors[$currentColumnsCount % count($colors)];
    }

    /**
     * الانتقال للخطوة التالية
     */
    public function nextStep()
    {
        if ($this->currentStep == 1) {
            if (empty($this->selectedModule)) {
                $this->dispatchBrowserEvent('warning', [
                    'message' => 'يجب اختيار وحدة أولاً',
                    'title' => 'تحذير'
                ]);
                return;
            }
        } elseif ($this->currentStep == 2) {
            if (empty($this->selectedColumns)) {
                $this->dispatchBrowserEvent('warning', [
                    'message' => 'يجب اختيار عمود واحد على الأقل',
                    'title' => 'تحذير'
                ]);
                return;
            }
        }

        $this->currentStep++;
        $this->errorMessage = '';
    }

    /**
     * العودة للخطوة السابقة
     */
    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    /**
     * تحديد جميع الحقول
     */
    public function selectAllFields()
    {
        if ($this->moduleFields && count($this->moduleFields) > 0) {
            $allFieldNames = [];
            foreach ($this->moduleFields as $field) {
                $fieldName = is_object($field) ? $field->field_name : (isset($field['field_name']) ? $field['field_name'] : '');
                if ($fieldName) {
                    $allFieldNames[] = $fieldName;
                }
            }
            $this->selectedColumns = $allFieldNames;

            // رسالة نجاح
            $this->successMessage = 'تم تحديد جميع الحقول (' . count($allFieldNames) . ' حقل)';
            $this->dispatchBrowserEvent('show-message', [
                'type' => 'success',
                'message' => $this->successMessage
            ]);
        }
    }

    /**
     * إلغاء تحديد جميع الحقول
     */
    public function deselectAllFields()
    {
        $previousCount = count($this->selectedColumns);
        $this->selectedColumns = [];

        // رسالة تأكيد
        $this->successMessage = 'تم إلغاء تحديد جميع الحقول (' . $previousCount . ' حقل)';
        $this->dispatchBrowserEvent('show-message', [
            'type' => 'info',
            'message' => $this->successMessage
        ]);
    }

    /**
     * تحديد الحقول الأساسية فقط (id, user_id, created_at, updated_at)
     */
    public function selectBasicFields()
    {
        if ($this->moduleFields && count($this->moduleFields) > 0) {
            $basicFieldNames = ['id', 'user_id', 'created_at', 'updated_at'];
            $selectedBasicFields = [];

            foreach ($this->moduleFields as $field) {
                $fieldName = is_object($field) ? $field->field_name : (isset($field['field_name']) ? $field['field_name'] : '');
                if ($fieldName && in_array($fieldName, $basicFieldNames)) {
                    $selectedBasicFields[] = $fieldName;
                }
            }

            $this->selectedColumns = $selectedBasicFields;

            // رسالة نجاح
            $this->successMessage = 'تم تحديد الحقول الأساسية (' . count($selectedBasicFields) . ' حقل)';
            $this->dispatchBrowserEvent('show-message', [
                'type' => 'success',
                'message' => $this->successMessage
            ]);
        }
    }

    /**
     * حذف حقل واحد من الحقول المحددة
     */
    public function removeField($fieldName)
    {
        $this->selectedColumns = array_values(array_diff($this->selectedColumns, [$fieldName]));

        // رسالة تأكيد
        $this->successMessage = 'تم حذف الحقل من القائمة المحددة';
        $this->dispatchBrowserEvent('show-message', [
            'type' => 'info',
            'message' => $this->successMessage
        ]);
    }

    /**
     * تحريك حقل للأعلى في الترتيب
     */
    public function moveFieldUp($index)
    {
        if ($index > 0 && $index < count($this->selectedColumns)) {
            $temp = $this->selectedColumns[$index];
            $this->selectedColumns[$index] = $this->selectedColumns[$index - 1];
            $this->selectedColumns[$index - 1] = $temp;
        }
    }

    /**
     * تحريك حقل للأسفل في الترتيب
     */
    public function moveFieldDown($index)
    {
        if ($index >= 0 && $index < count($this->selectedColumns) - 1) {
            $temp = $this->selectedColumns[$index];
            $this->selectedColumns[$index] = $this->selectedColumns[$index + 1];
            $this->selectedColumns[$index + 1] = $temp;
        }
    }

    /**
     * تشغيل التقرير
     */
    public function runReport()
    {
        try {
            $this->isLoading = true;
            $this->errorMessage = '';

            // إنشاء تقرير مؤقت
            $tempReport = new ReportGenerator([
                'module_name' => $this->selectedModule,
                'table_name' => ReportGenerator::getModuleTableName($this->selectedModule),
                'selected_columns' => $this->selectedColumns,
                'filter_columns' => $this->filterColumns,
                'filter_values' => $this->filterValues,
                'sort_column' => $this->sortColumn,
                'sort_direction' => $this->sortDirection
            ]);

            // جلب البيانات
            $this->reportData = $tempReport->generateReportData();

            // تسجيل البيانات للتطوير
            Log::info('Report Data Count: ' . count($this->reportData));
            if (!empty($this->reportData)) {
                Log::info('First Report Data Item: ' . json_encode($this->reportData[0]));
                Log::info('Data Type: ' . gettype($this->reportData[0]));
            }

            if (empty($this->reportData)) {
                $this->dispatchBrowserEvent('warning', [
                    'message' => 'لا توجد بيانات تطابق معايير البحث',
                    'title' => 'لا توجد نتائج'
                ]);
            } else {
                // تسجيل العملية في نظام التتبع
                Tracking::create([
                    'user_id' => Auth::user()->id,
                    'page_name' => 'مولد التقارير',
                    'operation_type' => 'تشغيل تقرير',
                    'operation_time' => now()->format('Y-m-d H:i:s'),
                    'details' => "تم تشغيل تقرير - عدد النتائج: " . count($this->reportData),
                ]);

                $this->dispatchBrowserEvent('success', [
                    'message' => 'تم تشغيل التقرير بنجاح - عدد النتائج: ' . count($this->reportData),
                    'title' => 'تشغيل التقرير'
                ]);

                // إطلاق حدث JavaScript لتحديث المخططات مع البيانات
                $this->dispatchBrowserEvent('livewire-chart-data-ready', [
                    'reportData' => $this->reportData,
                    'chartSettings' => $this->prepareChartSettingsForJs(),
                    'enableCharts' => $this->enableCharts,
                    'currentStep' => $this->currentStep,
                    'numericColumns' => $this->numericColumns
                ]);
            }

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('error', [
                'message' => 'خطأ في تشغيل التقرير: ' . $e->getMessage(),
                'title' => 'خطأ في التشغيل'
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * حفظ التقرير
     */
    public function saveReport()
    {
        $this->validate();

        try {
            $this->isLoading = true;

            $report = ReportGenerator::create([
                'title' => $this->reportTitle,
                'description' => $this->description,
                'module_name' => $this->selectedModule,
                'table_name' => ReportGenerator::getModuleTableName($this->selectedModule),
                'selected_columns' => $this->selectedColumns,
                'filter_columns' => $this->filterColumns,
                'filter_values' => $this->filterValues,
                'chart_settings' => $this->chartSettings,
                'sort_column' => $this->sortColumn,
                'sort_direction' => $this->sortDirection,
                'is_public' => $this->isPublic,
                'created_by' => Auth::id()
            ]);

            // تسجيل العملية في نظام التتبع
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => 'مولد التقارير',
                'operation_type' => 'إنشاء',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => "تم إنشاء تقرير جديد: {$this->reportTitle}",
            ]);

            $this->dispatchBrowserEvent('success', [
                'message' => 'تم حفظ التقرير بنجاح',
                'title' => 'حفظ التقرير'
            ]);

            $this->loadSavedReports();

            // إعادة تعيين النموذج
            $this->resetForm();

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('error', [
                'message' => 'خطأ في حفظ التقرير: ' . $e->getMessage(),
                'title' => 'خطأ في الحفظ'
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * تحميل تقرير محفوظ
     */
    public function loadReport($reportId)
    {
        try {
            $report = ReportGenerator::findOrFail($reportId);

            // فحص الصلاحيات
            if (!$report->is_public && $report->created_by !== Auth::id()) {
                $this->dispatchBrowserEvent('error', [
                    'message' => 'غير مسموح لك بتحميل هذا التقرير',
                    'title' => 'خطأ في الصلاحية'
                ]);
                return;
            }

            // تحميل الوحدة أولاً
            $this->selectedModule = $report->module_name;

            // تحميل حقول الوحدة أولاً (بدون الحفاظ على التحديدات)
            $this->loadModuleFields(false);

            // التأكد من تحميل الحقول
            if (empty($this->moduleFields)) {
                $this->dispatchBrowserEvent('error', [
                    'message' => 'لم يتم العثور على حقول للوحدة المحددة',
                    'title' => 'خطأ في تحميل الحقول'
                ]);
                return;
            }

            // ثم تحميل بيانات التقرير المحفوظة
            $this->reportTitle = $report->title;
            $this->description = $report->description;
            $this->selectedColumns = $report->selected_columns;
            $this->filterColumns = $report->filter_columns ?: [];
            $this->filterValues = $report->filter_values ?: [];
            $this->chartSettings = $report->chart_settings ?: [];
            $this->sortColumn = $report->sort_column;
            $this->sortDirection = $report->sort_direction;
            $this->isPublic = $report->is_public;

            $this->dispatchBrowserEvent('success', [
                'message' => 'تم تحميل التقرير بنجاح',
                'title' => 'تحميل التقرير'
            ]);

            $this->currentStep = 2;

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('error', [
                'message' => 'خطأ في تحميل التقرير',
                'title' => 'خطأ في التحميل'
            ]);
        }
    }

    /**
     * تشغيل تقرير محفوظ مباشرة
     */
    public function runSavedReport($reportId)
    {
        try {
            Log::info('Starting runSavedReport for ID: ' . $reportId);
            $report = ReportGenerator::findOrFail($reportId);

            // فحص الصلاحيات
            if (!$report->is_public && $report->created_by !== Auth::id()) {
                $this->dispatchBrowserEvent('error', [
                    'message' => 'غير مسموح لك بتشغيل هذا التقرير',
                    'title' => 'خطأ في الصلاحية'
                ]);
                return;
            }

            // تحميل بيانات التقرير
            $this->reportTitle = $report->title;
            $this->description = $report->description;
            $this->selectedModule = $report->module_name;
            $this->selectedColumns = $report->selected_columns;
            $this->filterColumns = $report->filter_columns ?: [];
            $this->filterValues = $report->filter_values ?: [];
            $this->chartSettings = $report->chart_settings ?: [];
            $this->sortColumn = $report->sort_column;
            $this->sortDirection = $report->sort_direction;
            $this->isPublic = $report->is_public;

            Log::info('Selected Module: ' . $this->selectedModule);
            Log::info('Selected Columns: ' . json_encode($this->selectedColumns));
            Log::info('Report table_name: ' . $report->table_name);

            // تحميل حقول الوحدة (مع الحفاظ على التحديدات المحفوظة)
            $this->loadModuleFields(true);

            // الانتقال للخطوة الأخيرة وتشغيل التقرير
            $this->currentStep = 4;
            $this->runReport();

            Log::info('Report Data after runReport: ' . count($this->reportData) . ' rows');

            // تمكين المخططات إذا كانت معرّفة
            if (!empty($this->chartSettings)) {
                $this->enableCharts = true;
                // إطلاق الحدث لإنشاء المخططات مع البيانات
                $this->dispatchBrowserEvent('livewire-chart-data-ready', [
                    'reportData' => $this->reportData,
                    'chartSettings' => $this->prepareChartSettingsForJs(),
                    'enableCharts' => $this->enableCharts,
                    'currentStep' => $this->currentStep,
                    'numericColumns' => $this->numericColumns
                ]);
            }

            $this->dispatchBrowserEvent('success', [
                'message' => 'تم تشغيل التقرير بنجاح - عدد النتائج: ' . count($this->reportData),
                'title' => 'تشغيل التقرير'
            ]);

        } catch (\Exception $e) {
            Log::error('Error in runSavedReport: ' . $e->getMessage());
            $this->dispatchBrowserEvent('error', [
                'message' => 'خطأ في تشغيل التقرير: ' . $e->getMessage(),
                'title' => 'خطأ في التشغيل'
            ]);
        }
    }

    /**
     * تحديد التقرير المراد حذفه
     */
    public function getReportForDeletion($reportId)
    {
        $this->reportToDelete = $reportId;
    }

    /**
     * تأكيد حذف التقرير
     */
    public function confirmDeleteReport()
    {
        if (!$this->reportToDelete) {
            $this->dispatchBrowserEvent('error', [
                'message' => 'لم يتم تحديد التقرير المراد حذفه',
                'title' => 'خطأ في الحذف'
            ]);
            return;
        }

        try {
            $report = ReportGenerator::findOrFail($this->reportToDelete);

            // فحص الصلاحيات
            if ($report->created_by !== Auth::id()) {
                $this->dispatchBrowserEvent('error', [
                    'message' => 'غير مسموح لك بحذف هذا التقرير',
                    'title' => 'خطأ في الصلاحية'
                ]);
                $this->reportToDelete = null;
                return;
            }

            // حذف نهائي من قاعدة البيانات (ليس soft delete)
            $reportTitle = $report->title; // حفظ الاسم للرسالة

            // تسجيل العملية في نظام التتبع
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => 'مولد التقارير',
                'operation_type' => 'حذف',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => "تم حذف التقرير: {$reportTitle}",
            ]);

            $report->forceDelete(); // حذف نهائي بدلاً من delete()

            // إعادة تعيين المتغير
            $this->reportToDelete = null;

            // إعادة تحميل التقارير المحفوظة
            $this->loadSavedReports();

            // إغلاق المودال
            $this->dispatchBrowserEvent('hide-modal', ['modalId' => 'removeReportModal']);

            $this->dispatchBrowserEvent('success', [
                'message' => "تم حذف التقرير '{$reportTitle}' نهائياً من قاعدة البيانات",
                'title' => 'تم الحذف بنجاح'
            ]);

        } catch (\Exception $e) {
            $this->reportToDelete = null;
            $this->dispatchBrowserEvent('error', [
                'message' => 'خطأ في حذف التقرير: ' . $e->getMessage(),
                'title' => 'خطأ في الحذف'
            ]);
        }
    }    /**
     * تصدير التقرير
     */
    /**
     * تصدير التقرير كملف Excel
     */
    public function exportReport()
    {
        if (empty($this->reportData)) {
            $this->dispatchBrowserEvent('warning', [
                'message' => 'يجب تشغيل التقرير أولاً',
                'title' => 'تحذير'
            ]);
            return;
        }

        try {
            // تسجيل العملية في نظام التتبع
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => 'مولد التقارير',
                'operation_type' => 'تصدير Excel',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => "تم تصدير تقرير Excel - الوحدة: {$this->selectedModule}",
            ]);

            // تحديد الأعمدة والفلاتر لإرسالها للـ controller
            $columns = implode(',', $this->selectedColumns);
            $filters = json_encode($this->filterValues ?? []);

            // استخدام redirect بدلاً من Excel::download للحفاظ على الحالة
            return redirect()->route('report-generator.export.excel', [
                'module' => $this->selectedModule,
                'columns' => $columns,
                'filters' => $filters
            ]);

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('error', [
                'message' => 'خطأ في تصدير الملف: ' . $e->getMessage(),
                'title' => 'خطأ في التصدير'
            ]);
        }
    }

    /**
     * تصدير التقرير كملف PDF
     */
    public function exportPdf()
    {
        if (empty($this->reportData)) {
            $this->dispatchBrowserEvent('warning', [
                'message' => 'يجب تشغيل التقرير أولاً',
                'title' => 'تحذير'
            ]);
            return;
        }

        // سيتم التنفيذ عبر redirect إلى Controller منفصل
        return redirect()->route('report-generator.export.pdf', [
            'module' => $this->selectedModule,
            'columns' => implode(',', $this->selectedColumns)
        ]);
    }

    /**
     * تحضير إعدادات المخطط للجافا سكريبت
     */
    private function prepareChartSettingsForJs()
    {
        $preparedCharts = [];

        foreach ($this->chartSettings as $index => $chart) {
            if (!empty($chart['columns']) && count($chart['columns']) > 0) {
                // التأكد من أن كل الحقول المحددة رقمية
                $validColumns = [];
                foreach ($chart['columns'] as $columnData) {
                    if (!empty($columnData['field']) && in_array($columnData['field'], $this->numericColumns)) {
                        $validColumns[] = $columnData;
                    }
                }

                if (!empty($validColumns)) {
                    $preparedCharts[] = [
                        'type' => $chart['type'],
                        'columns' => $validColumns,
                        'title' => $chart['title'] ?: 'مخطط ' . ($index + 1),
                        'showLegend' => $chart['showLegend'] ?? true,
                        'chartHeight' => $chart['chartHeight'] ?? 300,
                        'colors' => $chart['colors'] ?? ['#696CFF', '#FF6B6B', '#4ECDC4', '#45B7D1'],
                        'xAxisField' => $chart['xAxisField'] ?? null
                    ];
                }
            }
        }

        return $preparedCharts;
    }

    /**
     * إعادة تعيين النموذج
     */
    public function resetForm()
    {
        $this->reportTitle = '';
        $this->description = '';
        $this->selectedModule = '';
        $this->selectedColumns = [];
        $this->filterColumns = [];
        $this->filterValues = [];
        $this->chartSettings = [];
        $this->sortColumn = '';
        $this->sortDirection = 'asc';
        $this->isPublic = false;
        $this->currentStep = 1;
        $this->reportData = [];
        $this->moduleFields = [];
        $this->availableColumns = [];
        $this->numericColumns = [];
    }

    /**
     * إعادة تعيين الفلاتر
     */
    public function resetFilters()
    {
        $this->filterColumns = [];
        $this->filterValues = [];
        $this->sortColumn = '';
        $this->sortDirection = 'asc';
    }

    /**
     * إغلاق الرسائل
     */
    public function closeMessage()
    {
        $this->errorMessage = '';
        $this->successMessage = '';
    }

    /**
     * عرض المكون
     */
    public function render()
    {
        return view('livewire.report-generator.report-generator-manager');
    }
}
