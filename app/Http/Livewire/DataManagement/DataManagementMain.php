<?php

namespace App\Http\Livewire\DataManagement;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\DataManagement\DataTemplate;
use App\Exports\DataManagement\DataExport;
use App\Imports\DataManagement\DataImport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class DataManagementMain extends Component
{
    use WithFileUploads;

    // View State
    public $currentView = 'tables';
    public $selectedTable = null;
    public $showPreview = false;

    // Table Data
    public $availableTables = [];
    public $tableColumns = [];
    public $previewData = [];
    public $previewColumns = [];
    public $recordsCount = 0;

    // Search and Filter
    public $search = '';
    public $filterColumn = '';
    public $filterOperator = '=';
    public $filterValue = '';
    public $sortColumn = 'id';
    public $sortDirection = 'asc';
    public $appliedFilters = [];

    // Export Settings
    public $exportFormat = 'xlsx';
    public $exportLimit = null;
    public $customFileName = '';
    public $exportColumns = [];
    public $selectedColumns = [];
    public $saveAsTemplate = false;
    public $templateName = '';
    public $templateDescription = '';

    // Import Settings
    public $importFile = null;
    public $importMode = 'insert';
    public $importStatus = '';
    public $importProgress = 0;
    public $importResults = [];
    public $isImporting = false;

    // Export Progress
    public $exportProgress = 0;
    public $isExporting = false;

    // Templates
    public $templates = [];
    public $selectedTemplate = null;

    // معالجة رفع الملف
    public function updatedImportFile()
    {
        Log::info('LIVEWIRE: تم رفع ملف جديد');

        if ($this->importFile) {
            try {
                // التعامل مع حالة كون الملف مصفوفة
                $file = is_array($this->importFile) ? $this->importFile[0] : $this->importFile;

                // إعادة تشغيل الملف إذا لم يكن صالحاً
                if (method_exists($file, 'isValid') && !$file->isValid()) {
                    Log::warning('LIVEWIRE: الملف غير صالح في البداية، محاولة إعادة التشغيل');
                    // في بعض الأحيان يحتاج Livewire لوقت إضافي
                    sleep(1);
                }

                Log::info('LIVEWIRE: تفاصيل الملف المرفوع في updatedImportFile', [
                    'original_name' => method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : 'غير متاح',
                    'size' => method_exists($file, 'getSize') ? $file->getSize() : 'غير متاح',
                    'extension' => method_exists($file, 'getClientOriginalExtension') ? $file->getClientOriginalExtension() : 'غير متاح',
                    'mime_type' => method_exists($file, 'getMimeType') ? $file->getMimeType() : 'غير متاح',
                    'is_valid' => method_exists($file, 'isValid') ? $file->isValid() : 'غير متاح',
                    'error' => method_exists($file, 'getError') ? $file->getError() : 'غير متاح',
                    'file_class' => get_class($file)
                ]);

                // إعادة تعيين حالة الاستيراد السابقة
                $this->importStatus = '';
                $this->importProgress = 0;
                $this->importResults = [];

                // إشعار المستخدم بنجاح الرفع عبر طرق متعددة
                $fileName = method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : 'ملف';

                // JavaScript Event
                $this->dispatchBrowserEvent('file-uploaded', [
                    'name' => $fileName,
                    'message' => "تم رفع الملف '{$fileName}' بنجاح"
                ]);

                // Session Flash Message
                session()->flash('success', "✅ تم رفع الملف '{$fileName}' بنجاح! يمكنك الآن بدء عملية الاستيراد.");

            } catch (\Exception $e) {
                Log::error('LIVEWIRE: خطأ في معالجة الملف المرفوع', [
                    'error' => $e->getMessage(),
                    'file_type' => gettype($this->importFile),
                    'trace' => $e->getTraceAsString()
                ]);

                session()->flash('error', 'حدث خطأ في معالجة الملف: ' . $e->getMessage());
            }
        } else {
            Log::info('LIVEWIRE: تم إزالة الملف أو إلغاء الرفع');
        }
    }

    // Component Lifecycle
    public function mount()
    {
        try {
            Log::info('DataManagement component mount started');
            $this->availableTables = []; // تهيئة فارغة أولاً
            $this->loadAvailableTables();
            $this->loadTemplates();
            Log::info('DataManagement component mount completed');
        } catch (\Exception $e) {
            Log::error('Error in DataManagement mount: ' . $e->getMessage());
            session()->flash('error', 'خطأ في تحميل النظام: ' . $e->getMessage());
        }
    }

    // View Management
    public function setView($view)
    {
        $this->currentView = $view;

        if ($view === 'manage' && $this->selectedTable) {
            $this->loadTableData();
            $this->loadPreviewData();
        } elseif ($view === 'export' && $this->selectedTable) {
            $this->loadExportColumns();
        } elseif ($view === 'templates' && $this->selectedTable) {
            $this->loadTemplates();
        }
    }

    // Table Operations
    public function loadAvailableTables()
    {
        try {
            Log::info('بدء تحميل الجداول المتاحة');

            $tables = DB::select('SHOW TABLES');
            $databaseName = config('database.connections.mysql.database');
            $this->availableTables = [];

            // قائمة الجداول النظام المخفية
            $hiddenSystemTables = [
                'basic_groups',
                'data_templates',
                'data_template_usages',
                'failed_jobs',
                'migrations',
                'model_has_permissions',
                'model_has_roles',
                'module_fields',
                'online_sessions',
                'password_reset_tokens',
                'permissions',
                'personal_access_tokens',
                'roles',
                'role_has_permissions',
                'sessions',
                'users',
                // إضافة جداول نظام أخرى محتملة
                'password_resets',
                'cache',
                'cache_locks',
                'job_batches',
                'telescope_entries',
                'telescope_entries_tags',
                'telescope_monitoring'
            ];

            Log::info('تم العثور على ' . count($tables) . ' جدول');

            foreach ($tables as $table) {
                $tableName = $table->{"Tables_in_{$databaseName}"};

                // تخطي الجداول النظام المخفية
                if (in_array($tableName, $hiddenSystemTables)) {
                    Log::info('تخطي الجدول النظام: ' . $tableName);
                    continue;
                }

                Log::info('معالجة الجدول: ' . $tableName);

                // حساب عدد السجلات
                $rowCount = DB::table($tableName)->count();

                // حساب عدد الأعمدة
                $columns = Schema::getColumnListing($tableName);

                $this->availableTables[] = [
                    'name' => $tableName,
                    'display_name' => $this->getTableDisplayName($tableName),
                    'row_count' => $rowCount,
                    'columns_count' => count($columns),
                ];
            }

            Log::info('تم الانتهاء من تحميل ' . count($this->availableTables) . ' جدول (مع إخفاء جداول النظام)');

        } catch (\Exception $e) {
            Log::error('خطأ في تحميل الجداول: ' . $e->getMessage());
            session()->flash('error', 'خطأ في تحميل قائمة الجداول: ' . $e->getMessage());
        }
    }

    public function selectTable($tableName)
    {
        try {
            Log::info('selectTable called with: ' . $tableName);
            $this->selectedTable = $tableName;
            $this->currentView = 'manage';

            // تحميل بيانات الجدول والمعاينة
            $this->loadTableData();
            $this->loadPreviewData();
            $this->loadExportColumns(); // إضافة هذا للتأكد من تحميل أعمدة التصدير
            $this->loadTemplates(); // تحميل القوالب الخاصة بالجدول المحدد

            Log::info('Table selected successfully: ' . $tableName);
            session()->flash('success', 'تم اختيار الجدول: ' . $tableName);
        } catch (\Exception $e) {
            Log::error('Error selecting table: ' . $e->getMessage());
            session()->flash('error', 'خطأ في اختيار الجدول: ' . $e->getMessage());
        }
    }    public function loadTableData()
    {
        if (!$this->selectedTable) return;

        try {
            $this->tableColumns = Schema::getColumnListing($this->selectedTable);
            $this->recordsCount = DB::table($this->selectedTable)->count();
            $this->previewColumns = array_slice($this->tableColumns, 0, 8); // أول 8 أعمدة للمعاينة
            $this->showPreview = true;
        } catch (\Exception $e) {
            session()->flash('error', 'خطأ في تحميل بيانات الجدول: ' . $e->getMessage());
        }
    }

    public function loadPreviewData()
    {
        if (!$this->selectedTable || !$this->showPreview) return;

        try {
            $query = DB::table($this->selectedTable);

            // تطبيق البحث العام
            if (!empty($this->search)) {
                $query->where(function($q) {
                    foreach ($this->previewColumns as $column) {
                        $q->orWhere($column, 'LIKE', '%' . $this->search . '%');
                    }
                });
            }

            // تطبيق الفلاتر المحددة
            if (!empty($this->appliedFilters)) {
                foreach ($this->appliedFilters as $filter) {
                    if (isset($filter['column']) && isset($filter['operator']) && isset($filter['value'])) {
                        if ($filter['operator'] === 'LIKE') {
                            $query->where($filter['column'], 'LIKE', '%' . $filter['value'] . '%');
                        } else {
                            $query->where($filter['column'], $filter['operator'], $filter['value']);
                        }
                    }
                }
            }

            // ترتيب البيانات
            if (in_array($this->sortColumn, $this->previewColumns)) {
                $query->orderBy($this->sortColumn, $this->sortDirection);
            } else {
                // استخدام أول عمود كبديل إذا كان العمود المحدد غير متاح
                $query->orderBy($this->previewColumns[0] ?? 'id', $this->sortDirection);
            }

            $this->previewData = $query->select($this->previewColumns)->limit(10)->get();

            Log::info('تم تحميل معاينة البيانات', [
                'table' => $this->selectedTable,
                'search' => $this->search,
                'filters' => $this->appliedFilters,
                'results_count' => count($this->previewData)
            ]);

        } catch (\Exception $e) {
            Log::error('خطأ في تحميل معاينة البيانات: ' . $e->getMessage());
            session()->flash('error', 'خطأ في تحميل معاينة البيانات: ' . $e->getMessage());
        }
    }

    // Filter Operations
    public function applyFilter()
    {
        try {
            // التحقق من وجود البيانات المطلوبة
            if (empty($this->filterColumn) || empty($this->filterValue)) {
                session()->flash('error', 'يجب اختيار العمود وإدخال القيمة للتصفية');
                return;
            }

            // التحقق من عدم وجود نفس الفلتر مسبقاً
            $existingFilter = array_filter($this->appliedFilters, function($filter) {
                return $filter['column'] === $this->filterColumn &&
                       $filter['operator'] === $this->filterOperator &&
                       $filter['value'] === $this->filterValue;
            });

            if (count($existingFilter) > 0) {
                session()->flash('error', 'هذا الفلتر مطبق بالفعل');
                return;
            }

            // إضافة الفلتر الجديد
            $this->appliedFilters[] = [
                'column' => $this->filterColumn,
                'operator' => $this->filterOperator,
                'value' => $this->filterValue,
                'display' => $this->getColumnDisplayName($this->filterColumn) . ' ' .
                            $this->getOperatorDisplayName($this->filterOperator) . ' ' .
                            $this->filterValue
            ];

            // إعادة تعيين حقول الإدخال
            $this->filterColumn = '';
            $this->filterValue = '';
            $this->filterOperator = '=';

            // إعادة تحميل البيانات
            $this->loadPreviewData();

            session()->flash('success', 'تم تطبيق الفلتر بنجاح');

            Log::info('تم تطبيق فلتر جديد', [
                'filters_count' => count($this->appliedFilters),
                'latest_filter' => end($this->appliedFilters)
            ]);

        } catch (\Exception $e) {
            Log::error('خطأ في تطبيق الفلتر: ' . $e->getMessage());
            session()->flash('error', 'خطأ في تطبيق الفلتر: ' . $e->getMessage());
        }
    }

    public function clearFilter()
    {
        try {
            $this->appliedFilters = [];
            $this->filterColumn = '';
            $this->filterValue = '';
            $this->filterOperator = '=';
            $this->search = '';

            $this->loadPreviewData();
            session()->flash('success', 'تم إلغاء جميع الفلاتر');

            Log::info('تم إلغاء جميع الفلاتر');

        } catch (\Exception $e) {
            Log::error('خطأ في إلغاء الفلاتر: ' . $e->getMessage());
            session()->flash('error', 'خطأ في إلغاء الفلاتر: ' . $e->getMessage());
        }
    }

    public function removeFilter($index)
    {
        try {
            if (isset($this->appliedFilters[$index])) {
                $removedFilter = $this->appliedFilters[$index];
                unset($this->appliedFilters[$index]);
                $this->appliedFilters = array_values($this->appliedFilters);

                $this->loadPreviewData();
                session()->flash('success', 'تم إزالة الفلتر');

                Log::info('تم إزالة فلتر', [
                    'removed_filter' => $removedFilter,
                    'remaining_filters' => count($this->appliedFilters)
                ]);
            }
        } catch (\Exception $e) {
            Log::error('خطأ في إزالة الفلتر: ' . $e->getMessage());
            session()->flash('error', 'خطأ في إزالة الفلتر: ' . $e->getMessage());
        }
    }

    public function sortBy($column)
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
        $this->loadPreviewData();
    }

    // Export Operations
    public function loadExportColumns()
    {
        if (!$this->selectedTable) return;

        try {
            $columns = Schema::getColumnListing($this->selectedTable);
            $this->exportColumns = [];

            foreach ($columns as $column) {
                $this->exportColumns[] = [
                    'name' => $column,
                    'display_name' => $this->getColumnDisplayName($column),
                    'type' => $this->getColumnType($column),
                    'selected' => true
                ];
            }

            $this->updateSelectedColumns();
        } catch (\Exception $e) {
            session()->flash('error', 'خطأ في تحميل أعمدة التصدير: ' . $e->getMessage());
        }
    }

    public function updateSelectedColumns()
    {
        $this->selectedColumns = array_column(
            array_filter($this->exportColumns, fn($col) => $col['selected']),
            'name'
        );
    }

    public function exportData()
    {
        try {
            $this->validate([
                'exportFormat' => 'required|in:xlsx,csv,pdf',
                'templateName' => $this->saveAsTemplate ? 'required|string|max:255' : 'nullable',
            ]);

            // حفظ القالب إذا طُلب ذلك
            if ($this->saveAsTemplate && $this->templateName) {
                $this->saveCurrentTemplate();
            }

            // إعداد الملف
            $fileName = $this->customFileName ?:
                       $this->getTableDisplayName($this->selectedTable) . '_' . Carbon::now()->format('Y-m-d_H-i-s');

            $export = new DataExport(
                $this->selectedTable,
                $this->selectedColumns,
                $this->appliedFilters,
                $this->exportLimit
            );

            return Excel::download($export, $fileName . '.' . $this->exportFormat);

        } catch (\Exception $e) {
            session()->flash('error', 'خطأ في تصدير البيانات: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        try {
            if (!$this->selectedTable) {
                session()->flash('error', 'يرجى اختيار جدول أولاً');
                return;
            }

            // الحصول على أعمدة الجدول الفعلية (بدون id و timestamps)
            $tableColumns = Schema::getColumnListing($this->selectedTable);
            $templateColumns = array_filter($tableColumns, function($column) {
                return !in_array($column, ['id', 'created_at', 'updated_at']);
            });

            // إضافة معلومات إضافية للمستخدم حول المفاتيح الخارجية
            $additionalInfo = $this->getTableConstraintsInfo($this->selectedTable);

            Log::info('تحميل قالب للجدول', [
                'table' => $this->selectedTable,
                'all_columns' => $tableColumns,
                'template_columns' => array_values($templateColumns),
                'constraints_info' => $additionalInfo
            ]);

            $fileName = $this->getTableDisplayName($this->selectedTable) . '_template_' . Carbon::now()->format('Y-m-d');

            $export = new DataExport(
                $this->selectedTable,
                array_values($templateColumns), // استخدام أعمدة الجدول الفعلية
                [],
                0,
                true // template mode
            );

            return Excel::download($export, $fileName . '.xlsx');

        } catch (\Exception $e) {
            Log::error('خطأ في تحميل القالب', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'خطأ في تحميل القالب: ' . $e->getMessage());
        }
    }

    // Import Operations
    public function importData()
    {
        // إضافة log في بداية الوظيفة
        Log::info('=== بدء عملية الاستيراد ===');

        try {
            // بدء العملية
            $this->isImporting = true;

            // تسجيل معلومات الجلسة
            Log::info('LIVEWIRE: معلومات الجلسة', [
                'selected_table' => $this->selectedTable,
                'import_mode' => $this->importMode,
                'has_file' => !empty($this->importFile),
                'file_type' => is_array($this->importFile) ? 'array' : gettype($this->importFile)
            ]);

            // إعادة تعيين النتائج السابقة
            $this->importResults = [];
            $this->importStatus = 'بدء المعالجة...';

            // فرض تحديث الواجهة
            $this->dispatchBrowserEvent('import-status-update', [
                'status' => $this->importStatus
            ]);

            // التحقق من وجود جدول محدد
            if (!$this->selectedTable) {
                Log::error('LIVEWIRE: لا يوجد جدول محدد');
                $this->importStatus = 'فشل: لا يوجد جدول محدد';
                session()->flash('error', 'يجب اختيار جدول للاستيراد');
                return;
            }

            // التحقق من وجود ملف
            if (!$this->importFile) {
                Log::error('LIVEWIRE: لا يوجد ملف مرفوع');
                $this->importStatus = 'فشل: لا يوجد ملف مرفوع';
                session()->flash('error', 'يجب اختيار ملف للاستيراد');
                return;
            }

            // التعامل مع حالة كون الملف مصفوفة (في حالات نادرة)
            $file = is_array($this->importFile) ? $this->importFile[0] : $this->importFile;

            if (!$file) {
                Log::error('LIVEWIRE: الملف فارغ بعد المعالجة');
                $this->importStatus = 'فشل: ملف غير صحيح';
                session()->flash('error', 'الملف المرفوع غير صحيح');
                return;
            }

            // التحقق من طرق الملف
            if (!method_exists($file, 'getClientOriginalName')) {
                Log::error('LIVEWIRE: الملف لا يحتوي على الطرق المطلوبة', [
                    'file_class' => get_class($file),
                    'file_methods' => get_class_methods($file)
                ]);
                $this->importStatus = 'فشل: نوع ملف غير مدعوم';
                session()->flash('error', 'نوع الملف المرفوع غير مدعوم');
                return;
            }

            Log::info('LIVEWIRE: تفاصيل الملف المرفوع', [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'extension' => $file->getClientOriginalExtension(),
                'mime_type' => $file->getMimeType(),
                'path' => $file->getRealPath(),
                'is_valid' => $file->isValid(),
                'error' => $file->getError()
            ]);

            // التحقق من صحة الملف يدوياً بدلاً من استخدام validate
            $validationErrors = $this->validateImportFile($file);

            if (!empty($validationErrors)) {
                Log::error('LIVEWIRE: أخطاء في التحقق من الملف', $validationErrors);
                $this->importStatus = 'فشل: ' . implode(', ', $validationErrors);
                session()->flash('error', implode('<br>', $validationErrors));
                return;
            }

            // التحقق من صحة نوع الاستيراد
            if (!in_array($this->importMode, ['insert', 'update', 'replace'])) {
                Log::error('LIVEWIRE: نوع استيراد غير صحيح', ['mode' => $this->importMode]);
                $this->importStatus = 'فشل: نوع استيراد غير صحيح';
                session()->flash('error', 'نوع الاستيراد غير صحيح');
                return;
            }

            Log::info('LIVEWIRE: بدء عملية الاستيراد', [
                'table' => $this->selectedTable,
                'mode' => $this->importMode,
                'file' => $file->getClientOriginalName(),
                'file_size' => $file->getSize()
            ]);

            $this->importStatus = 'جاري بدء عملية الاستيراد...';
            $this->emit('importStarted'); // إرسال حدث للواجهة

            Log::info('LIVEWIRE: إنشاء DataImport');
            $import = new DataImport(
                $this->selectedTable,
                $this->importMode
            );

            $this->importStatus = 'جاري معالجة الملف...';
            $this->emit('importProgress', 30); // إرسال تحديث التقدم

            Log::info('LIVEWIRE: بدء استدعاء Excel::import');
            Excel::import($import, $file);

            $this->emit('importProgress', 70);

            Log::info('LIVEWIRE: انتهى استدعاء Excel::import');

            $this->importResults = $import->getResults();
            $this->importStatus = 'تم الانتهاء من الاستيراد بنجاح';
            $this->emit('importCompleted'); // إرسال حدث الانتهاء

            Log::info('LIVEWIRE: نتائج الاستيراد', $this->importResults);

            // عرض رسالة نجاح مفصلة
            if ($this->importResults && isset($this->importResults['success_count'])) {
                $message = "تم استيراد {$this->importResults['success_count']} سجل بنجاح";
                if ($this->importResults['error_count'] > 0) {
                    $message .= " مع {$this->importResults['error_count']} أخطاء";
                }
                session()->flash('success', $message);

                // إشعار JavaScript للنجاح
                $this->dispatchBrowserEvent('import-success', [
                    'message' => $message,
                    'success_count' => $this->importResults['success_count'],
                    'error_count' => $this->importResults['error_count'] ?? 0,
                    'status' => $this->importStatus
                ]);
            } else {
                $successMessage = 'تم استيراد البيانات بنجاح';
                session()->flash('success', $successMessage);

                // إشعار JavaScript للنجاح
                $this->dispatchBrowserEvent('import-success', [
                    'message' => $successMessage,
                    'status' => $this->importStatus
                ]);
            }

            // إعادة تحميل البيانات
            $this->loadTableData();
            $this->loadPreviewData();

            // إعادة تعيين متغيرات الاستيراد
            $this->resetImportVariables();

        } catch (\Exception $e) {
            Log::error('LIVEWIRE: خطأ في الاستيراد', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'selected_table' => $this->selectedTable,
                'import_mode' => $this->importMode,
                'trace' => $e->getTraceAsString()
            ]);

            $this->importStatus = 'فشل في عملية الاستيراد: ' . $e->getMessage();

            // تحديد نوع الخطأ لعرض رسالة مناسبة
            $errorMessage = 'خطأ في استيراد البيانات';
            if (strpos($e->getMessage(), 'timeout') !== false) {
                $errorMessage = 'انتهت مهلة العملية - الملف كبير جداً أو يحتوي على بيانات معقدة';
            } elseif (strpos($e->getMessage(), 'memory') !== false) {
                $errorMessage = 'نفدت ذاكرة الخادم - الملف كبير جداً';
            } elseif (strpos($e->getMessage(), 'permission') !== false) {
                $errorMessage = 'مشكلة في صلاحيات الملف أو قاعدة البيانات';
            } else {
                $errorMessage .= ': ' . $e->getMessage();
            }

            session()->flash('error', $errorMessage);

            // فرض تحديث الواجهة
            $this->dispatchBrowserEvent('import-error', [
                'message' => $errorMessage,
                'status' => $this->importStatus
            ]);

            // إعادة تعيين متغيرات الاستيراد حتى في حالة الخطأ
            $this->resetImportVariables();
        }

        Log::info('=== انتهاء عملية الاستيراد ===');
    }

    // التحقق من صحة الملف يدوياً
    private function validateImportFile($file)
    {
        $errors = [];

        // التحقق من صحة الملف
        if (!$file || !is_object($file)) {
            $errors[] = 'لم يتم رفع ملف صحيح';
            return $errors;
        }

        // التحقق من أن الملف صحيح
        if (!method_exists($file, 'isValid') || !$file->isValid()) {
            $errors[] = 'الملف المرفوع تالف أو غير صحيح';
            return $errors;
        }

        // التحقق من حجم الملف
        if ($file->getSize() > 10485760) { // 10MB
            $errors[] = 'حجم الملف كبير جداً (الحد الأقصى 10 ميجابايت)';
        }

        // التحقق من نوع الملف
        $allowedExtensions = ['xlsx', 'csv', 'xls'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = 'نوع الملف غير مدعوم. الأنواع المدعومة: ' . implode(', ', $allowedExtensions);
        }

        // التحقق من MIME type
        $allowedMimes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // xlsx
            'application/vnd.ms-excel', // xls
            'text/csv', // csv
            'application/csv',
            'text/plain'
        ];

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            Log::warning('MIME type غير متوقع', ['mime' => $file->getMimeType()]);
            // لا نضيف خطأ هنا لأن بعض الخوادم قد تعطي MIME types مختلفة
        }

        return $errors;
    }

    // إعادة تعيين متغيرات الاستيراد
    private function resetImportVariables()
    {
        $this->importFile = null;
        $this->importMode = 'insert';
        $this->importStatus = '';
        $this->importProgress = 0;
    }

    // اختبار الملف المرفوع
    public function testFile()
    {
        try {
            Log::info('=== بدء اختبار الملف ===');

            if (!$this->importFile) {
                Log::error('TEST: لا يوجد ملف مرفوع');
                session()->flash('error', 'لا يوجد ملف مرفوع للاختبار');
                return;
            }

            if (!$this->selectedTable) {
                Log::error('TEST: لا يوجد جدول محدد');
                session()->flash('error', 'يجب اختيار جدول أولاً');
                return;
            }

            $file = is_array($this->importFile) ? $this->importFile[0] : $this->importFile;

            Log::info('TEST: تفاصيل الملف', [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'extension' => $file->getClientOriginalExtension(),
                'mime' => $file->getMimeType(),
                'path' => $file->getRealPath(),
                'selected_table' => $this->selectedTable,
                'file_exists' => file_exists($file->getRealPath()),
                'file_readable' => is_readable($file->getRealPath())
            ]);

            // التحقق من وجود الجدول
            if (!Schema::hasTable($this->selectedTable)) {
                Log::error('TEST: الجدول غير موجود', ['table' => $this->selectedTable]);
                session()->flash('error', 'الجدول المحدد غير موجود: ' . $this->selectedTable);
                return;
            }

            // قراءة محتويات الملف للاختبار
            Log::info('TEST: محاولة قراءة الملف');
            $testImport = new DataImport($this->selectedTable, 'insert');
            $data = Excel::toArray($testImport, $file);

            $sheetsCount = count($data);
            $rowsCount = isset($data[0]) ? count($data[0]) : 0;
            $headers = isset($data[0][0]) ? array_keys($data[0][0]) : [];

            Log::info('TEST: محتويات الملف', [
                'sheets_count' => $sheetsCount,
                'first_sheet_rows' => $rowsCount,
                'headers' => $headers,
                'sample_data' => isset($data[0]) ? array_slice($data[0], 0, 2) : []
            ]);

            // التحقق من أعمدة الجدول
            $tableColumns = Schema::getColumnListing($this->selectedTable);
            $matchingColumns = array_intersect($headers, $tableColumns);

            Log::info('TEST: مطابقة الأعمدة', [
                'table_columns' => $tableColumns,
                'file_headers' => $headers,
                'matching_columns' => $matchingColumns
            ]);

            // اختبار معالجة صف واحد
            if (isset($data[0][0])) {
                Log::info('TEST: اختبار معالجة صف واحد');
                try {
                    $testRow = $data[0][0];
                    Log::info('TEST: صف للاختبار', $testRow);
                    // يمكن إضافة المزيد من الاختبارات هنا
                } catch (\Exception $rowError) {
                    Log::error('TEST: خطأ في معالجة الصف', ['error' => $rowError->getMessage()]);
                }
            }

            $message = "✅ الملف صالح ويحتوي على:<br>";
            $message .= "📊 {$rowsCount} صف و {$sheetsCount} ورقة<br>";
            $message .= "📋 أعمدة متطابقة: " . implode(', ', $matchingColumns) . "<br>";

            if (count($matchingColumns) < count($headers)) {
                $unmatchedHeaders = array_diff($headers, $tableColumns);
                $message .= "⚠️ أعمدة غير متطابقة: " . implode(', ', $unmatchedHeaders) . "<br>";
            }

            $message .= "🎯 الجدول المستهدف: {$this->selectedTable}<br>";
            $message .= "📈 نوع الاستيراد: {$this->importMode}";

            session()->flash('success', $message);
            Log::info('TEST: اكتمل الاختبار بنجاح');

        } catch (\Exception $e) {
            Log::error('TEST: خطأ في اختبار الملف', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'خطأ في قراءة الملف: ' . $e->getMessage());
        } finally {
            Log::info('=== انتهاء اختبار الملف ===');
        }
    }    // Template Operations
    public function loadTemplates()
    {
        try {
            Log::info('بدء تحميل القوالب');
            $this->templates = [];

            $this->templates = DataTemplate::with('creator')
                ->where('is_active', true)
                ->where('table_name', $this->selectedTable)
                ->orderBy('last_used_at', 'desc')
                ->orderBy('usage_count', 'desc')
                ->get()
                ->map(function ($template) {
                    return [
                        'id' => $template->id,
                        'name' => $template->name,
                        'description' => $template->description,
                        'table_name' => $template->table_name,
                        'columns_config' => $template->columns_config,
                        'export_settings' => $template->export_settings,
                        'last_used_at' => $template->last_used_at,
                        'usage_count' => $template->usage_count,
                        'creator' => $template->creator->name ?? 'غير محدد'
                    ];
                })
                ->toArray();

            Log::info('تم الانتهاء من تحميل القوالب');
        } catch (\Exception $e) {
            Log::error('خطأ في تحميل القوالب: ' . $e->getMessage());
            session()->flash('error', 'خطأ في تحميل القوالب: ' . $e->getMessage());
        }
    }

    public function saveCurrentTemplate()
    {
        try {
            DataTemplate::create([
                'name' => $this->templateName,
                'description' => $this->templateDescription,
                'table_name' => $this->selectedTable,
                'columns_config' => [
                    'selected' => $this->selectedColumns,
                    'all_columns' => $this->exportColumns
                ],
                'export_settings' => [
                    'format' => $this->exportFormat,
                    'limit' => $this->exportLimit,
                    'custom_filename_pattern' => $this->customFileName
                ],
                'filter_settings' => [
                    'filters' => $this->appliedFilters,
                    'search' => $this->search,
                    'sort_column' => $this->sortColumn,
                    'sort_direction' => $this->sortDirection
                ],
                'created_by' => auth()->id(),
            ]);

            $this->loadTemplates();
            session()->flash('success', 'تم حفظ القالب بنجاح');

        } catch (\Exception $e) {
            session()->flash('error', 'خطأ في حفظ القالب: ' . $e->getMessage());
        }
    }

    public function loadTemplate($templateId)
    {
        try {
            $template = DataTemplate::findOrFail($templateId);

            // تحديث آخر استخدام
            $template->increment('usage_count');
            $template->update(['last_used_at' => now()]);

            // تحميل إعدادات القالب
            $this->selectedTable = $template->table_name;
            $this->exportFormat = $template->export_settings['format'] ?? 'xlsx';
            $this->exportLimit = $template->export_settings['limit'] ?? null;
            $this->customFileName = $template->export_settings['custom_filename_pattern'] ?? '';

            // تحميل الأعمدة والفلاتر
            $this->loadExportColumns();
            $selectedColumns = $template->columns_config['selected'] ?? [];

            foreach ($this->exportColumns as &$column) {
                $column['selected'] = in_array($column['name'], $selectedColumns);
            }

            $this->updateSelectedColumns();
            $this->appliedFilters = $template->filter_settings['filters'] ?? [];
            $this->search = $template->filter_settings['search'] ?? '';

            $this->selectedTemplate = $templateId;
            $this->currentView = 'export';

            session()->flash('success', 'تم تحميل القالب بنجاح');

        } catch (\Exception $e) {
            session()->flash('error', 'خطأ في تحميل القالب: ' . $e->getMessage());
        }
    }

    /**
     * الحصول على معلومات قيود الجدول
     */
    protected function getTableConstraintsInfo($tableName)
    {
        try {
            $constraints = DB::select("
                SELECT
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$tableName]);

            $info = [];
            foreach ($constraints as $constraint) {
                $info[$constraint->COLUMN_NAME] = [
                    'references' => $constraint->REFERENCED_TABLE_NAME,
                    'column' => $constraint->REFERENCED_COLUMN_NAME
                ];
            }

            return $info;
        } catch (\Exception $e) {
            return [];
        }
    }

    // Helper Methods
    public function getTableDisplayName($tableName)
    {
        try {
            // الحصول على COMMENT من قاعدة البيانات للجدول
            $comment = $this->getTableComment($tableName);

            // إذا كان هناك COMMENT وليس فارغاً، استخدمه
            if (!empty($comment)) {
                return $comment;
            }

            // إذا لم يوجد COMMENT، استخدم الترجمات الثابتة كبديل (فقط للجداول المُنشأة من المولد)
            $translations = [
                // تم إزالة ترجمات الجداول النظام المخفية
                // إضافة ترجمات للجداول المُنشأة من المولد حسب الحاجة
                'emps' => 'الموظفين',
                'departments' => 'الأقسام',
                'projects' => 'المشاريع',
                'employees' => 'الموظفين',
                // يمكن إضافة المزيد حسب الوحدات المُنشأة
            ];

            return $translations[$tableName] ?? ucwords(str_replace('_', ' ', $tableName));

        } catch (\Exception $e) {
            Log::warning("خطأ في الحصول على اسم الجدول المعروض: " . $e->getMessage());
            return ucwords(str_replace('_', ' ', $tableName));
        }
    }

    /**
     * الحصول على COMMENT الخاص بالجدول من قاعدة البيانات
     */
    private function getTableComment($tableName)
    {
        try {
            $databaseName = config('database.connections.mysql.database');

            $result = DB::select("
                SELECT TABLE_COMMENT
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
            ", [$databaseName, $tableName]);

            return $result[0]->TABLE_COMMENT ?? '';

        } catch (\Exception $e) {
            Log::warning("خطأ في الحصول على COMMENT للجدول {$tableName}: " . $e->getMessage());
            return '';
        }
    }

    public function getColumnDisplayName($columnName)
    {
        try {
            // الحصول على COMMENT من قاعدة البيانات
            $comment = $this->getColumnComment($columnName);

            // إذا كان هناك COMMENT وليس فارغاً، استخدمه
            if (!empty($comment)) {
                return $comment;
            }

            // إذا لم يوجد COMMENT، استخدم الترجمات الثابتة كبديل
            $translations = [
                'id' => 'المعرف',
                'name' => 'الاسم',
                'email' => 'البريد الإلكتروني',
                'created_at' => 'تاريخ الإنشاء',
                'updated_at' => 'تاريخ التحديث',
                'deleted_at' => 'تاريخ الحذف',
                'description' => 'الوصف',
                'status' => 'الحالة',
                'type' => 'النوع',
                'active' => 'نشط',
                'password' => 'كلمة المرور',
            ];

            return $translations[$columnName] ?? ucwords(str_replace('_', ' ', $columnName));

        } catch (\Exception $e) {
            Log::warning("خطأ في الحصول على اسم العمود المعروض: " . $e->getMessage());
            return ucwords(str_replace('_', ' ', $columnName));
        }
    }

    /**
     * الحصول على COMMENT الخاص بالعمود من قاعدة البيانات
     */
    private function getColumnComment($columnName)
    {
        try {
            if (!$this->selectedTable) {
                return '';
            }

            $databaseName = config('database.connections.mysql.database');

            $result = DB::select("
                SELECT COLUMN_COMMENT
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?
            ", [$databaseName, $this->selectedTable, $columnName]);

            return $result[0]->COLUMN_COMMENT ?? '';

        } catch (\Exception $e) {
            Log::warning("خطأ في الحصول على COMMENT للعمود {$columnName}: " . $e->getMessage());
            return '';
        }
    }

    public function getColumnType($columnName)
    {
        try {
            $columnType = Schema::getColumnType($this->selectedTable, $columnName);
            return $columnType;
        } catch (\Exception $e) {
            return 'string';
        }
    }

    public function getOperatorDisplayName($operator)
    {
        $operators = [
            '=' => 'يساوي',
            '!=' => 'لا يساوي',
            'LIKE' => 'يحتوي على',
            '>' => 'أكبر من',
            '<' => 'أصغر من',
        ];

        return $operators[$operator] ?? $operator;
    }

    // Livewire Hooks
    public function updatedSearch()
    {
        Log::info('تم تحديث البحث', ['search_term' => $this->search]);
        $this->loadPreviewData();
    }

    public function updatedFilterColumn()
    {
        // إعادة تعيين القيمة عند تغيير العمود
        $this->filterValue = '';
    }

    public function updatedFilterOperator()
    {
        // يمكن إضافة منطق إضافي هنا عند الحاجة
    }

    public function updatedFilterValue()
    {
        // يمكن إضافة تطبيق الفلتر التلقائي هنا إذا رغبت
    }

    // Template Management Functions
    public function editTemplate($templateId)
    {
        try {
            $template = DataTemplate::findOrFail($templateId);

            // تحميل بيانات القالب للتعديل
            $this->selectedTemplate = $templateId;
            $this->templateName = $template->name;
            $this->templateDescription = $template->description;
            $this->exportFormat = $template->export_settings['format'] ?? 'xlsx';

            // الانتقال لصفحة التصدير للتعديل
            $this->setView('export');

            session()->flash('success', 'تم تحميل القالب للتعديل');

        } catch (\Exception $e) {
            session()->flash('error', 'خطأ في تحميل القالب: ' . $e->getMessage());
        }
    }

    public function deleteTemplate($templateId)
    {
        try {
            $template = DataTemplate::findOrFail($templateId);

            // التحقق من الصلاحيات (اختياري)
            if ($template->created_by !== auth()->id()) {
                session()->flash('error', 'ليس لديك صلاحية لحذف هذا القالب');
                return;
            }

            $templateName = $template->name;
            $template->delete();

            // تحديث قائمة القوالب
            $this->loadTemplates();

            // إلغاء التحديد إذا كان هذا القالب محدداً
            if ($this->selectedTemplate == $templateId) {
                $this->selectedTemplate = null;
            }

            session()->flash('success', "تم حذف القالب '{$templateName}' بنجاح");

        } catch (\Exception $e) {
            session()->flash('error', 'خطأ في حذف القالب: ' . $e->getMessage());
        }
    }

    public function useSelectedTemplate()
    {
        if ($this->selectedTemplate) {
            $this->loadTemplate($this->selectedTemplate);
            $this->setView('export');
        }
    }

    public function clearSelectedTemplate()
    {
        $this->selectedTemplate = null;
    }

    public function render()
    {
        return view('livewire.data-management.data-management-main');
    }
}
