<?php

namespace App\Http\Livewire\DashboardBuilder;

use Livewire\Component;
use App\Models\System\ModuleField;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class DashboardBuilderMain extends Component
{
    public $widgets = [];
    public $availableModules = [];
    public $selectedModule = '';
    public $widgetType = 'stat';
    public $widgetTitle = '';
    public $widgetLimit = 10;
    public $widgetColumns = [];
    public $availableColumns = [];
    public $numericColumns = [];
    public $showAddWidget = false;
    public $showDeleteModal = false;
    public $widgetToDelete = null;
    public $editingWidget = null;
    public $activeTab = 'basic'; // التبويب النشط

    // خصائص الإحصائيات
    public $statType = 'count';
    public $statLabel = '';
    public $statIcon = 'mdi-chart-line';
    public $statColor = 'primary';
    public $customColor = '#696CFF';
    public $statField = '';

    // إعدادات الفلترة للإحصائيات
    public $statFilterColumn = '';
    public $statFilterOperator = '=';
    public $statFilterValue = '';

    // إعدادات المخططات - نسخ من مولد التقارير
    public $chartSettings = [];
    public $enableCharts = false;

    // خصائص الجداول
    public $tableColumns = [];
    public $tableOrderBy = '';
    public $tableOrderDirection = 'desc';
    public $tableWithFilters = false;
    public $tableSearchable = true;
    public $tableColorScheme = 'default'; // default, primary, success, info, warning, danger, custom
    public $tableCustomColor = '#696CFF';
    public $tableStriped = true;
    public $tableHover = true;
    public $tableBordered = true;

    // خصائص فلترة الجداول المتقدمة
    public $tableFilterColumn = '';
    public $tableFilterOperator = '=';
    public $tableFilterValue = '';
    public $tableDateFilter = ''; // '', today, yesterday, week, month, last_month, year, custom
    public $tableDateFrom = '';
    public $tableDateTo = '';

    // خصائص التصميم والعرض
    public $widgetWidth = 'quarter'; // full, half, third, quarter
    public $widgetHeight = 'auto'; // auto, small, medium, large
    public $widgetBorder = 'card'; // card, shadow, borderless, minimal
    public $showHeader = true;
    public $showBorder = true;

    // خصائص التحديث والأداء
    public $refreshInterval = 'manual'; // manual, 30, 60, 300, 900
    public $cacheTime = '0'; // 0, 300, 900, 3600
    public $paginationType = 'none'; // none, simple, full
    public $statPeriod = ''; // '', today, week, month, year

    // خصائص الصلاحيات
    public $visibilityLevel = 'public'; // public, authenticated, admin, owner
    public $exportPermission = 'none'; // none, excel, pdf, both

    public function mount()
    {
        try {
            $this->loadAvailableModules();
            $this->loadSavedWidgets();
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('error', [
                'message' => 'حدث خطأ أثناء تحميل مصمم الداشبورد: ' . $e->getMessage(),
                'title' => 'خطأ في التحميل'
            ]);
        }
    }

    public function loadAvailableModules()
    {
        try {
            // جلب الوحدات من جدول module_fields بنفس طريقة مولد التقارير
            // استخدام: module_name (اسم الوحدة), table_name (اسم الجدول), module_arabic_name (الاسم العربي)
            $modules = ModuleField::select('module_name', 'table_name', 'module_arabic_name')
                ->groupBy('module_name', 'table_name', 'module_arabic_name')
                ->orderBy('module_name')
                ->get();

            $this->availableModules = [];

            foreach ($modules as $module) {
                // التأكد من أن الجدول موجود بالفعل في قاعدة البيانات
                if (Schema::hasTable($module->table_name)) {
                    $this->availableModules[] = [
                        'name' => $module->table_name, // اسم الجدول الفعلي
                        'label' => $module->module_arabic_name ?: $module->module_name, // الاسم العربي (يظهر في القائمة)
                        'module_name' => $module->module_name // اسم الوحدة الأصلي
                    ];
                }
            }
        } catch (\Exception $e) {
            // في حالة حدوث خطأ، تعيين مصفوفة فارغة
            $this->availableModules = [];

            // تسجيل الخطأ في اللوج
            Log::error('خطأ في تحميل الوحدات في مصمم الداشبورد: ' . $e->getMessage());
        }
    }

    public function loadSavedWidgets()
    {
        $this->widgets = $this->getWidgetsFromStorage();

        // تحديث الويدجتس القديمة بالخصائص الجديدة
        $this->updateLegacyWidgets();
    }

    /**
     * تحديث الويدجتس القديمة بالخصائص الجديدة
     */
    private function updateLegacyWidgets()
    {
        $updated = false;

        foreach ($this->widgets as $index => $widget) {
            $originalWidget = $widget;

            // إضافة الخصائص المفقودة بالقيم الافتراضية
            $defaultProperties = [
                'width' => 'quarter',
                'height' => 'auto',
                'border' => 'card',
                'show_header' => true,
                'show_border' => true,
                'refresh_interval' => 'manual',
                'cache_time' => '0',
                'pagination_type' => 'none',
                'stat_period' => '',
                'visibility_level' => 'public',
                'export_permission' => 'none'
            ];

            foreach ($defaultProperties as $property => $defaultValue) {
                if (!array_key_exists($property, $widget)) {
                    $this->widgets[$index][$property] = $defaultValue;
                    $updated = true;
                }
            }
        }

        // حفظ التحديثات إذا كانت هناك تغييرات
        if ($updated) {
            $this->saveWidgets();
        }
    }

    private function getWidgetsFromStorage()
    {
        $configFile = storage_path('app/dashboard_config.json');
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            return $config['widgets'] ?? [];
        }

        return [];
    }

    public function updatedSelectedModule()
    {
        if ($this->selectedModule) {
            $this->loadModuleColumns();
        }
    }

    public function loadModuleColumns()
    {
        if (!$this->selectedModule) return;

        try {
            $columns = Schema::getColumnListing($this->selectedModule);
            $this->availableColumns = [];
            $this->numericColumns = [];

            // جلب معلومات الحقول من module_fields
            $moduleFields = ModuleField::where('table_name', $this->selectedModule)->get();

            // جلب معلومات الحقول من جدول module_fields
            $moduleFields = ModuleField::where('table_name', $this->selectedModule)
                ->get();

            // جلب معلومات الأعمدة مع التعليقات (Comments)
            $tableInfo = DB::select("
                SELECT COLUMN_NAME, DATA_TYPE, COLUMN_COMMENT
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ", [config('database.connections.mysql.database'), $this->selectedModule]);

            $columnsInfo = collect($tableInfo)->keyBy('COLUMN_NAME');

            foreach ($columns as $column) {
                if (!in_array($column, ['created_at', 'updated_at', 'deleted_at'])) {
                    // البحث عن معلومات الحقل في module_fields
                    $moduleField = $moduleFields->where('field_name', $column)->first();
                    $columnType = Schema::getColumnType($this->selectedModule, $column);

                    // تحضير البيانات الأساسية للعمود
                    $columnData = [
                        'name' => $column,
                        'label' => $moduleField ? $moduleField->arabic_name : $column,
                        'type' => $columnType,
                        'has_relation' => false
                    ];

                    // إذا كان الحقل موجود في module_fields
                    if ($moduleField) {
                        // إذا كان هناك جدول مرتبط
                        if ($moduleField->related_table) {
                            $columnData['has_relation'] = true;
                            $columnData['relation'] = [
                                'type' => 'table',
                                'table' => $moduleField->related_table,
                                'field' => $moduleField->related_field ?: 'id',
                                'display' => $moduleField->related_display ?: 'name'
                            ];
                            $columnData['label'] = $moduleField->arabic_name ?: $column;
                        }
                        // إذا كان هناك مصدر للقائمة المنسدلة
                        elseif ($moduleField->select_source) {
                            $columnData['has_relation'] = true;
                            $columnData['relation'] = [
                                'type' => 'select',
                                'source' => $moduleField->select_source
                            ];
                        }

                        // إضافة المعرف العربي إذا كان متوفراً
                        if ($moduleField->arabic_name) {
                            $columnData['label'] = $moduleField->arabic_name;
                        }
                    }

                    $this->availableColumns[] = $columnData;

                    // طباعة معلومات التصحيح
                    if ($columnData['has_relation']) {
                        Log::info('Column with relation: ' . json_encode($columnData));
                    }

                    if (in_array($columnType, ['integer', 'bigint', 'decimal', 'float', 'double'])) {
                        $this->numericColumns[] = $column;
                    }
                }
            }

            // إعادة تعيين المتغيرات المرتبطة
            $this->widgetColumns = [];
            $this->tableColumns = [];
        } catch (\Exception $e) {
            $this->availableColumns = [];
            $this->numericColumns = [];
        }
    }

    public function addWidget()
    {
        try {
            $this->validate([
                'widgetTitle' => 'required|string|max:255',
                'selectedModule' => 'required|string',
                'widgetType' => 'required|in:stat,table,chart'
            ]);

            // التحقق من وجود الوحدة المحددة
            if (!$this->isModuleValid($this->selectedModule)) {
                $this->dispatchBrowserEvent('error', [
                    'message' => 'الوحدة المحددة غير صالحة أو غير موجودة',
                    'title' => 'خطأ في الوحدة'
                ]);
                return;
            }

            $widget = [
                'id' => $this->editingWidget['id'] ?? uniqid(),
                'type' => $this->widgetType,
                'title' => $this->widgetTitle,
                'module' => $this->selectedModule,
                'order' => $this->editingWidget['order'] ?? (count($this->widgets) + 1),
                'active' => true,
                'created_at' => $this->editingWidget['created_at'] ?? now()->toISOString(),
                'updated_at' => now()->toISOString(),
                // إعدادات التصميم والعرض
                'width' => $this->widgetWidth,
                'height' => $this->widgetHeight,
                'border' => $this->widgetBorder,
                'show_header' => $this->showHeader,
                'show_border' => $this->showBorder,
                // إعدادات التحديث والأداء
                'refresh_interval' => $this->widgetType === 'chart' ? 'manual' : $this->refreshInterval,
                'cache_time' => $this->cacheTime,
                'pagination_type' => $this->paginationType,
                'stat_period' => $this->statPeriod,
                // إعدادات الصلاحيات
                'visibility_level' => $this->visibilityLevel,
                'export_permission' => $this->exportPermission
            ];

            switch ($this->widgetType) {
                case 'stat':
                    $widget = array_merge($widget, [
                        'stat_type' => $this->statType,
                        'stat_field' => $this->statField,
                        'label' => $this->statLabel ?: $this->widgetTitle,
                        'icon' => $this->statIcon,
                        'color' => $this->statColor === 'custom' ? $this->customColor : $this->statColor,
                        'custom_color' => $this->customColor,
                        'value' => $this->calculateStatValue(),
                        // إعدادات الفلترة
                        'filter_column' => $this->statFilterColumn,
                        'filter_operator' => $this->statFilterOperator,
                        'filter_value' => $this->statFilterValue
                    ]);
                    break;

                case 'table':
                    $widget = array_merge($widget, [
                        'columns' => $this->tableColumns,
                        'limit' => $this->widgetLimit,
                        'order_by' => $this->tableOrderBy,
                        'order_direction' => $this->tableOrderDirection,
                        'with_filters' => $this->tableWithFilters,
                        'searchable' => $this->tableSearchable,
                        'color_scheme' => $this->tableColorScheme,
                        'custom_color' => $this->tableCustomColor,
                        'striped' => $this->tableStriped,
                        'hover' => $this->tableHover,
                        'bordered' => $this->tableBordered,
                        // إعدادات الفلترة المتقدمة
                        'filter_column' => $this->tableFilterColumn,
                        'filter_operator' => $this->tableFilterOperator,
                        'filter_value' => $this->tableFilterValue,
                        'date_filter' => $this->tableDateFilter,
                        'date_from' => $this->tableDateFrom,
                        'date_to' => $this->tableDateTo
                    ]);
                    break;

                case 'chart':
                    $widget = array_merge($widget, [
                        'enable_charts' => $this->enableCharts,
                        'chart_settings' => $this->chartSettings, // النظام الجديد البسيط
                    ]);
                    break;
            }

            if ($this->editingWidget) {
                // تحديث Widget موجود
                $index = array_search($this->editingWidget['id'], array_column($this->widgets, 'id'));
                if ($index !== false) {
                    $this->widgets[$index] = $widget;
                }
            } else {
                // إضافة Widget جديد
                $this->widgets[] = $widget;
            }

            $this->saveWidgets();
            $this->resetForm();
            $this->showAddWidget = false;
            $isEditing = $this->editingWidget ? true : false;
            $this->editingWidget = null;

            $this->dispatchBrowserEvent('success', [
                'message' => $isEditing ? 'تم تحديث العنصر بنجاح' : 'تم إضافة العنصر بنجاح',
                'title' => $isEditing ? 'تحديث عنصر' : 'إضافة عنصر'
            ]);
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('error', [
                'message' => 'حدث خطأ أثناء حفظ العنصر: ' . $e->getMessage(),
                'title' => 'خطأ في الحفظ'
            ]);
        }
    }

    /**
     * التحقق من صحة الوحدة المحددة
     */
    private function isModuleValid($moduleName)
    {
        try {
            return collect($this->availableModules)->pluck('name')->contains($moduleName);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function editWidget($widgetId)
    {
        $widget = collect($this->widgets)->firstWhere('id', $widgetId);
        if (!$widget) return;

        $this->editingWidget = $widget;
        $this->selectedModule = $widget['module'];
        $this->loadModuleColumns();

        $this->widgetType = $widget['type'];
        $this->widgetTitle = $widget['title'];
        $this->activeTab = 'basic';

        // تحميل إعدادات التصميم والعرض
        $this->widgetWidth = $widget['width'] ?? 'quarter';
        $this->widgetHeight = $widget['height'] ?? 'auto';
        $this->widgetBorder = $widget['border'] ?? 'card';
        $this->showHeader = $widget['show_header'] ?? true;
        $this->showBorder = $widget['show_border'] ?? true;

        // تحميل إعدادات التحديث والأداء
        $this->refreshInterval = $widget['refresh_interval'] ?? 'manual';
        $this->cacheTime = $widget['cache_time'] ?? '0';
        $this->paginationType = $widget['pagination_type'] ?? 'none';
        $this->statPeriod = $widget['stat_period'] ?? '';

        // تحميل إعدادات الصلاحيات
        $this->visibilityLevel = $widget['visibility_level'] ?? 'public';
        $this->exportPermission = $widget['export_permission'] ?? 'none';

        switch ($widget['type']) {
            case 'stat':
                $this->statType = $widget['stat_type'] ?? 'count';
                $this->statField = $widget['stat_field'] ?? '';
                $this->statLabel = $widget['label'] ?? '';
                $this->statIcon = $widget['icon'] ?? 'mdi-chart-line';
                $this->statColor = $widget['color'] ?? 'primary';
                $this->customColor = $widget['custom_color'] ?? '#696CFF';

                // تحميل إعدادات الفلترة
                $this->statFilterColumn = $widget['filter_column'] ?? '';
                $this->statFilterOperator = $widget['filter_operator'] ?? '=';
                $this->statFilterValue = $widget['filter_value'] ?? '';

                // إذا كان اللون hex color، نعتبره custom
                if (isset($widget['color']) && str_starts_with($widget['color'], '#')) {
                    $this->statColor = 'custom';
                    $this->customColor = $widget['color'];
                }
                break;

            case 'table':
                $this->tableColumns = $widget['columns'] ?? [];
                $this->widgetLimit = $widget['limit'] ?? 10;
                $this->tableOrderBy = $widget['order_by'] ?? '';
                $this->tableOrderDirection = $widget['order_direction'] ?? 'desc';
                $this->tableWithFilters = $widget['with_filters'] ?? false;
                $this->tableSearchable = $widget['searchable'] ?? true;
                $this->tableColorScheme = $widget['color_scheme'] ?? 'default';
                $this->tableCustomColor = $widget['custom_color'] ?? '#696CFF';
                $this->tableStriped = $widget['striped'] ?? true;
                $this->tableHover = $widget['hover'] ?? true;
                $this->tableBordered = $widget['bordered'] ?? true;
                // تحميل إعدادات الفلترة المتقدمة
                $this->tableFilterColumn = $widget['filter_column'] ?? '';
                $this->tableFilterOperator = $widget['filter_operator'] ?? '=';
                $this->tableFilterValue = $widget['filter_value'] ?? '';
                $this->tableDateFilter = $widget['date_filter'] ?? '';
                $this->tableDateFrom = $widget['date_from'] ?? '';
                $this->tableDateTo = $widget['date_to'] ?? '';
                break;

            case 'chart':
                // النظام الجديد البسيط - نسخ من مولد التقارير
                $this->enableCharts = $widget['enable_charts'] ?? false;
                $this->chartSettings = $widget['chart_settings'] ?? [];
                break;
        }

        $this->showAddWidget = true;
    }

    public function toggleWidgetStatus($widgetId)
    {
        try {
            $index = array_search($widgetId, array_column($this->widgets, 'id'));

            if ($index === false) {
                $this->dispatchBrowserEvent('warning', [
                    'message' => 'العنصر المراد تغيير حالته غير موجود',
                    'title' => 'تحذير'
                ]);
                return;
            }

            $this->widgets[$index]['active'] = !($this->widgets[$index]['active'] ?? true);
            $this->widgets[$index]['updated_at'] = now()->toISOString();
            $this->saveWidgets();

            $status = $this->widgets[$index]['active'] ? 'تم تفعيل' : 'تم إلغاء تفعيل';
            $widgetTitle = $this->widgets[$index]['title'] ?? 'العنصر';

            // إرسال إشعار نجاح
            $this->dispatchBrowserEvent('success', [
                'message' => $status . ' "' . $widgetTitle . '" بنجاح',
                'title' => 'تحديث حالة العنصر'
            ]);
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('error', [
                'message' => 'حدث خطأ أثناء تغيير حالة العنصر: ' . $e->getMessage(),
                'title' => 'خطأ في التحديث'
            ]);
        }
    }

    public function removeWidget($widgetId)
    {
        $this->widgetToDelete = $widgetId;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->widgetToDelete = null;
        $this->showDeleteModal = false;
    }

    public function confirmDeleteWidget()
    {
        try {
            // البحث عن العنصر أولاً للتأكد من وجوده
            $widgetToRemove = collect($this->widgets)->firstWhere('id', $this->widgetToDelete);

            if (!$widgetToRemove) {
                $this->dispatchBrowserEvent('warning', [
                    'message' => 'العنصر المراد حذفه غير موجود',
                    'title' => 'تحذير'
                ]);
                $this->closeDeleteModal();
                return;
            }

            $this->widgets = array_filter($this->widgets, function ($widget) {
                return $widget['id'] !== $this->widgetToDelete;
            });

            $this->widgets = array_values($this->widgets);
            $this->saveWidgets();

            $this->dispatchBrowserEvent('success', [
                'message' => 'تم حذف العنصر "' . $widgetToRemove['title'] . '" بنجاح',
                'title' => 'حذف عنصر'
            ]);

            $this->closeDeleteModal();
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('error', [
                'message' => 'حدث خطأ أثناء حذف العنصر: ' . $e->getMessage(),
                'title' => 'خطأ في الحذف'
            ]);
            $this->closeDeleteModal();
        }
    }

    public function moveWidgetUp($index)
    {
        if ($index > 0) {
            $temp = $this->widgets[$index];
            $this->widgets[$index] = $this->widgets[$index - 1];
            $this->widgets[$index - 1] = $temp;
            $this->updateWidgetOrder();
            $this->saveWidgets();
        }
    }

    public function moveWidgetDown($index)
    {
        if ($index < count($this->widgets) - 1) {
            $temp = $this->widgets[$index];
            $this->widgets[$index] = $this->widgets[$index + 1];
            $this->widgets[$index + 1] = $temp;
            $this->updateWidgetOrder();
            $this->saveWidgets();
        }
    }
    private function updateWidgetOrder()
    {
        foreach ($this->widgets as $index => $widget) {
            $this->widgets[$index]['order'] = $index + 1;
            $this->widgets[$index]['updated_at'] = now()->toISOString();
        }
    }

    private function calculateStatValue()
    {
        try {
            if (!$this->selectedModule || !Schema::hasTable($this->selectedModule)) {
                return 0;
            }

            $query = DB::table($this->selectedModule);

            // تطبيق الفلترة إذا كانت محددة
            if ($this->statFilterColumn && Schema::hasColumn($this->selectedModule, $this->statFilterColumn)) {
                $operator = $this->statFilterOperator;
                $value = $this->statFilterValue;

                // معالجة العمليات الخاصة
                if ($operator === 'IS NULL') {
                    $query->whereNull($this->statFilterColumn);
                } elseif ($operator === 'IS NOT NULL') {
                    $query->whereNotNull($this->statFilterColumn);
                } elseif ($operator === 'LIKE' || $operator === 'NOT LIKE') {
                    // إضافة علامات % للبحث
                    $query->where($this->statFilterColumn, $operator, '%' . $value . '%');
                } else {
                    // العمليات العادية: =, !=, >, <, >=, <=
                    $query->where($this->statFilterColumn, $operator, $value);
                }
            }

            switch ($this->statType) {
                case 'count':
                    return $query->count();
                case 'sum':
                    return $this->statField ? $query->sum($this->statField) : 0;
                case 'avg':
                    return $this->statField ? round($query->avg($this->statField), 2) : 0;
                case 'max':
                    return $this->statField ? $query->max($this->statField) : 0;
                case 'min':
                    return $this->statField ? $query->min($this->statField) : 0;
                default:
                    return 0;
            }
        } catch (\Exception $e) {
            Log::error('خطأ في حساب قيمة الإحصائية: ' . $e->getMessage());
            return 0;
        }
    }

    private function saveWidgets()
    {
        $configFile = storage_path('app/dashboard_config.json');
        $data = ['widgets' => $this->widgets];
        file_put_contents($configFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function resetForm()
    {
        $this->selectedModule = '';
        $this->widgetType = 'stat';
        $this->widgetTitle = '';
        $this->widgetLimit = 10;
        $this->availableColumns = [];
        $this->numericColumns = [];
        $this->activeTab = 'basic';

        // إعادة تعيين الإحصائيات
        $this->statType = 'count';
        $this->statField = '';
        $this->statLabel = '';
        $this->statIcon = 'mdi-chart-line';
        $this->statColor = 'primary';

        // إعادة تعيين إعدادات الفلترة للإحصائيات
        $this->statFilterColumn = '';
        $this->statFilterOperator = '=';
        $this->statFilterValue = '';

        // إعادة تعيين الجداول
        $this->tableColumns = [];
        $this->tableOrderBy = '';
        $this->tableOrderDirection = 'desc';
        $this->tableWithFilters = false;
        $this->tableSearchable = true;
        $this->tableColorScheme = 'default';
        $this->tableCustomColor = '#696CFF';
        $this->tableStriped = true;
        $this->tableHover = true;
        $this->tableBordered = true;
        // إعادة تعيين فلترة الجداول المتقدمة
        $this->tableFilterColumn = '';
        $this->tableFilterOperator = '=';
        $this->tableFilterValue = '';
        $this->tableDateFilter = '';
        $this->tableDateFrom = '';
        $this->tableDateTo = '';

        // إعادة تعيين المخططات - النظام الجديد فقط
        $this->enableCharts = false;
        $this->chartSettings = [];

        // إعادة تعيين إعدادات التصميم والعرض
        $this->widgetWidth = 'quarter';
        $this->widgetHeight = 'auto';
        $this->widgetBorder = 'card';
        $this->showHeader = true;
        $this->showBorder = true;

        // إعادة تعيين إعدادات التحديث والأداء
        $this->refreshInterval = 'manual';
        $this->cacheTime = '0';
        $this->paginationType = 'none';
        $this->statPeriod = '';

        // إعادة تعيين إعدادات الصلاحيات
        $this->visibilityLevel = 'public';
        $this->exportPermission = 'none';
    }

    public function setCustomColorQuick($colorCode)
    {
        $this->customColor = $colorCode;
        $this->statColor = 'custom';
        $this->emit('colorChanged', $colorCode);
    }

    public function selectAllTableColumns()
    {
        $this->tableColumns = collect($this->availableColumns)->pluck('name')->toArray();
    }

    public function unselectAllTableColumns()
    {
        $this->tableColumns = [];
    }

    public function setTableCustomColor($colorCode)
    {
        $this->tableCustomColor = $colorCode;
        $this->tableColorScheme = 'custom';
    }

    /**
     * إضافة مخطط - نسخ من مولد التقارير
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
     * تحضير إعدادات المخططات للـ JavaScript - نسخ من مولد التقارير
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
     * تفعيل أو إلغاء تفعيل المخططات
     */
    public function updatedEnableCharts()
    {
        if ($this->enableCharts && empty($this->chartSettings)) {
            // إضافة مخطط افتراضي عند التفعيل لأول مرة
            $this->addChart();
        } elseif (!$this->enableCharts) {
            // مسح المخططات عند إلغاء التفعيل
            $this->chartSettings = [];
        }
    }

    /**
     * إعادة تعيين إعدادات المخططات عند تغيير نوع العنصر
     */
    public function resetChartSettings()
    {
        $this->enableCharts = false;
        $this->chartSettings = [];
    }
    /**
     * مراقبة تغيير نوع العنصر
     */
    public function updatedWidgetType()
    {
        // إعادة تعيين إعدادات المخططات عند تغيير النوع من chart إلى أي شيء آخر
        if ($this->widgetType !== 'chart') {
            $this->resetChartSettings();
        }
    }

    /**
     * الحصول على القيمة المرتبطة للحقل
     */
    private function getRelatedValue($column, $value)
    {
        if (!isset($column['relation'])) {
            return $value;
        }

        try {
            if ($column['relation']['type'] === 'select') {
                // معالجة القوائم المنسدلة
                $options = json_decode($column['relation']['source'], true);
                return $options[$value] ?? $value;
            } else {
                // معالجة العلاقات مع الجداول
                $relatedRecord = DB::table($column['relation']['table'])
                    ->where('id', $value)
                    ->first();

                if ($relatedRecord) {
                    return $relatedRecord->{$column['relation']['display']} ?? $value;
                }
            }
        } catch (\Exception $e) {
            Log::error('Error getting related value: ' . $e->getMessage());
        }

        return $value;
    }

    public function render()
    {
        return view('livewire.dashboard-builder.dashboard-builder-main');
    }
}
