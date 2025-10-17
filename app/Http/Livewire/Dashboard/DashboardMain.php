<?php

namespace App\Http\Livewire\Dashboard;

use Carbon\Carbon;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;

class DashboardMain extends Component
{
    public $widgets = [];
    public $widgetData = [];

    // خصائص البحث والفلترة لكل ويدجت
    public $searchTerms = [];
    public $dateFilters = [];
    public $statusFilters = [];

    protected $listeners = ['dashboardChanged' => 'refreshWidgets'];

    /**
     * دعم المخططات متعددة الأعمدة (مستوحاة من مولد التقارير)
     */
    private function getMultiColumnChartData($widget)
    {
        if (!isset($widget['chart_columns']) || !is_array($widget['chart_columns'])) {
            return $this->getChartData($widget);
        }

        $module = $widget['module'];
        $chartType = $widget['chart_type'] ?? 'bar';
        $labelField = $widget['label_field'] ?? 'month';
        $maxItems = $widget['max_items'] ?? 10;
        $tableName = $this->getTableNameFromModule($module);

        if (!Schema::hasTable($tableName)) {
            return ['labels' => [], 'datasets' => []];
        }

        $labels = DB::table($tableName)
            ->select($labelField)
            ->whereNotNull($labelField)
            ->distinct()
            ->orderBy($labelField)
            ->limit($maxItems)
            ->pluck($labelField)
            ->toArray();

        $datasets = [];
        // استخدام ألوان Widget أو الألوان الافتراضية
        $widgetColors = $widget['colors'] ?? [
            '#696CFF', '#FF6B6B', '#4ECDC4', '#45B7D1',
            '#96CEB4', '#FFEAA7', '#DDA0DD', '#98D8C8'
        ];
        $colors = is_array($widgetColors) ? $widgetColors : [
            '#696cff', '#8592a3', '#71dd37', '#ffab00', '#ff6b6b', '#1abc9c', '#34495e'
        ];

        foreach ($widget['chart_columns'] as $index => $column) {
            $dataField = $column['field'];
            $aggregation = $column['aggregation'] ?? 'sum';

            if (!Schema::hasColumn($tableName, $dataField)) {
                continue;
            }

            $data = [];
            foreach ($labels as $label) {
                $query = DB::table($tableName)->where($labelField, $label);

                $value = match($aggregation) {
                    'sum' => $query->sum($dataField),
                    'avg' => $query->avg($dataField),
                    'max' => $query->max($dataField),
                    'min' => $query->min($dataField),
                    'count' => $query->count(),
                    default => $query->sum($dataField)
                };

                $data[] = round($value ?? 0, 2);
            }

            $datasets[] = [
                'label' => $column['label'] ?? $dataField,
                'data' => $data,
                'backgroundColor' => $colors[$index % count($colors)],
                'borderColor' => $colors[$index % count($colors)],
                'borderWidth' => 1
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets
        ];
    }

    /**
     * معالجة النظام المتقدم للمخططات (النظام الجديد)
     */
    private function getAdvancedChartData($widget)
    {
        $module = $widget['module'];
        $chartSettings = $widget['chart_settings'];
        $tableName = $this->getTableNameFromModule($module);

        Log::info("معالجة مخطط متقدم", [
            'module' => $module,
            'table' => $tableName,
            'charts_count' => count($chartSettings)
        ]);

        if (!Schema::hasTable($tableName)) {
            Log::error("جدول غير موجود: $tableName");
            return ['labels' => [], 'datasets' => []];
        }

        // إذا كان هناك مخطط واحد فقط، نرجع بيانات بسيطة
        if (count($chartSettings) === 1) {
            $chart = $chartSettings[0];
            $result = $this->buildSingleAdvancedChart($chart, $tableName);

            Log::info("نتيجة المخطط الواحد", [
                'labels_count' => count($result['labels'] ?? []),
                'datasets_count' => count($result['datasets'] ?? []),
                'first_dataset_data_count' => count($result['datasets'][0]['data'] ?? [])
            ]);

            return $result;
        }

        // إذا كان هناك عدة مخططات، نجمع بياناتها
        $allDatasets = [];
        $commonLabels = [];

        foreach ($chartSettings as $index => $chart) {
            $chartData = $this->buildSingleAdvancedChart($chart, $tableName);

            if (!empty($chartData['labels'])) {
                if (empty($commonLabels)) {
                    $commonLabels = $chartData['labels'];
                }

                // إضافة البيانات من هذا المخطط
                if (isset($chartData['datasets'])) {
                    foreach ($chartData['datasets'] as $dataset) {
                        $dataset['label'] = ($chart['title'] ?? 'مخطط ' . ($index + 1)) . ' - ' . $dataset['label'];
                        $allDatasets[] = $dataset;
                    }
                }
            }
        }

        return [
            'labels' => $commonLabels,
            'datasets' => $allDatasets
        ];
    }

    /**
     * بناء مخطط واحد من النظام المتقدم
     */
    private function buildSingleAdvancedChart($chart, $tableName)
    {
        $xAxisField = $chart['xAxisField'] ?? 'id';
        $groupByField = $chart['groupByField'] ?? null;
        $aggregationType = $chart['aggregationType'] ?? 'count';
        $maxItems = 10;

        // إذا لم تكن هناك أعمدة محددة، استخدم العد البسيط
        if (!isset($chart['columns']) || empty($chart['columns'])) {
            $query = DB::table($tableName);
            return $this->buildChartQuery($query, $xAxisField, 'id', $aggregationType, $maxItems);
        }

        // معالجة الأعمدة المتعددة
        $columns = $chart['columns'];
        $labels = [];
        $datasets = [];

        // جمع التصنيفات (Labels) - إعطاء أولوية للتجميع
        $labelField = $groupByField ?: $xAxisField;

        if ($labelField && Schema::hasColumn($tableName, $labelField)) {
            $labels = DB::table($tableName)
                ->select($labelField)
                ->whereNotNull($labelField)
                ->distinct()
                ->orderBy($labelField)
                ->limit($maxItems)
                ->pluck($labelField)
                ->toArray();
        }

        // إذا لم نحصل على تصنيفات، استخدم الترقيم التلقائي
        if (empty($labels)) {
            $labels = ['البيانات'];
        }

        // بناء datasets لكل عمود
        foreach ($columns as $column) {
            $field = $column['field'];
            $label = $column['label'] ?? $field;
            $color = $column['color'] ?? '#696CFF';

            if (!Schema::hasColumn($tableName, $field)) {
                continue;
            }

            $data = [];

            // إذا كان لدينا تجميع، نعالج كل تصنيف
            if (count($labels) > 1 && $labelField) {
                foreach ($labels as $labelValue) {
                    $query = DB::table($tableName)->where($labelField, $labelValue);

                    $value = match($aggregationType) {
                        'sum' => $query->sum($field),
                        'avg' => $query->avg($field),
                        'max' => $query->max($field),
                        'min' => $query->min($field),
                        'count' => $query->count(),
                        default => $query->sum($field)
                    };

                    $data[] = round($value ?? 0, 2);
                }
            } else {
                // بيانات إجمالية بدون تجميع
                $query = DB::table($tableName);

                $value = match($aggregationType) {
                    'sum' => $query->sum($field),
                    'avg' => $query->avg($field),
                    'max' => $query->max($field),
                    'min' => $query->min($field),
                    'count' => $query->count(),
                    default => $query->sum($field)
                };

                $data[] = round($value ?? 0, 2);
            }

            $datasets[] = [
                'label' => $label,
                'data' => $data,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'borderWidth' => 1
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets
        ];
    }

    public function mount()
    {
        $this->loadWidgets();
        $this->loadWidgetData();
    }

    /**
     * إعادة تحميل الويدجتس والبيانات (يستخدم عند تغيير الحالة)
     */
    public function refreshWidgets()
    {
        $this->loadWidgets();
        $this->loadWidgetData();
        $this->emit('widgetsRefreshed');
    }

    /**
     * تحديث ويدجت محدد (خاص بالمخططات)
     */
    public function refreshSpecificWidget($widgetId)
    {
        // البحث عن الويدجت المحدد
        $widget = collect($this->widgets)->firstWhere('id', $widgetId);

        if ($widget && $widget['type'] === 'chart') {
            // تحميل بيانات المخطط المحدد فقط
            if ($widget['chart_type'] === 'multi_column') {
                $chartData = $this->getMultiColumnChartData($widget);
            } else {
                $chartData = $this->getChartData($widget);
            }

            // تحديث بيانات الويدجت في المصفوفة
            $this->widgetData[$widgetId] = $chartData;

            // إرسال البيانات الجديدة للمخطط
            $this->emit('specificWidgetRefreshed', [
                'widgetId' => $widgetId,
                'widgetData' => $chartData
            ]);
        }
    }

    private function loadWidgets()
    {
        $configPath = storage_path('app/dashboard_config.json');

        if (file_exists($configPath)) {
            $config = json_decode(file_get_contents($configPath), true);
            $allWidgets = $config['widgets'] ?? [];

            // تنظيف وتحديث الويدجتس
            $allWidgets = $this->normalizeWidgets($allWidgets);

            // تصفية الويدجتس المفعلة فقط
            $this->widgets = array_filter($allWidgets, function($widget) {
                return $widget['active'] ?? true;
            });

        } else {
            // إعدادات افتراضية
            $this->widgets = [
                [
                    'id' => uniqid(),
                    'type' => 'stat',
                    'title' => 'إجمالي المستخدمين',
                    'module' => 'users',
                    'stat_type' => 'count',
                    'icon' => 'users',
                    'color' => 'primary',
                    'active' => true,
                    'order' => 1,
                    'width' => 'quarter',
                    'height' => 'auto',
                    'border' => 'card',
                    'show_header' => true,
                    'show_border' => true,
                    'refresh_interval' => 'manual',
                    'cache_time' => '0',
                    'stat_period' => '',
                    'visibility_level' => 'public',
                    'export_permission' => 'none'
                ],
                [
                    'id' => uniqid(),
                    'type' => 'stat',
                    'title' => 'التسجيلات اليوم',
                    'module' => 'users',
                    'stat_type' => 'count_today',
                    'icon' => 'user-plus',
                    'color' => 'success',
                    'active' => true,
                    'order' => 2,
                    'width' => 'quarter',
                    'height' => 'auto',
                    'border' => 'card',
                    'show_header' => true,
                    'show_border' => true,
                    'refresh_interval' => 'manual',
                    'cache_time' => '0',
                    'stat_period' => '',
                    'visibility_level' => 'public',
                    'export_permission' => 'none'
                ]
            ];
        }

        // ترتيب الويدجتس حسب الترتيب المحدد
        usort($this->widgets, function ($a, $b) {
            $orderA = $a['order'] ?? 999;
            $orderB = $b['order'] ?? 999;
            return $orderA - $orderB;
        });
    }

    /**
     * تطبيع الويدجتس وإضافة الخصائص المفقودة
     */
    private function normalizeWidgets($widgets)
    {
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

        foreach ($widgets as $index => $widget) {
            foreach ($defaultProperties as $property => $defaultValue) {
                if (!array_key_exists($property, $widget)) {
                    $widgets[$index][$property] = $defaultValue;
                }
            }
        }

        return $widgets;
    }    private function loadWidgetData()
    {
        foreach ($this->widgets as $widget) {
            // التحقق من أن الويدجت مفعل قبل تحميل البيانات
            if (!($widget['active'] ?? true)) {
                continue;
            }

            $widgetId = $widget['id'];

            try {
                switch ($widget['type']) {
                    case 'stat':
                        $this->widgetData[$widgetId] = $this->calculateStatValue($widget);
                        break;

                    case 'table':
                        $this->widgetData[$widgetId] = $this->getTableData($widget);
                        break;

                    case 'chart':
                        $this->widgetData[$widgetId] = $this->getChartData($widget);
                        break;
                }
            } catch (\Exception $e) {
                $this->widgetData[$widgetId] = null;
            }
        }
    }

    private function calculateStatValue($widget)
    {
        $module = $widget['module'];
        $statType = $widget['stat_type'] ?? 'count';
        $statPeriod = $widget['stat_period'] ?? '';
        $cacheTime = (int) ($widget['cache_time'] ?? 0);

        // إنشاء مفتاح التخزين المؤقت
        $cacheKey = "widget_stat_{$widget['id']}_" . md5(json_encode($widget));

        // استخدام التخزين المؤقت إذا كان مفعلاً
        if ($cacheTime > 0) {
            $cachedValue = Cache::get($cacheKey);
            if ($cachedValue !== null) {
                return $cachedValue;
            }
        }

        // تحويل اسم الوحدة إلى اسم الجدول الصحيح
        $tableName = $this->getTableNameFromModule($module);

        // التحقق من وجود الجدول
        if (!Schema::hasTable($tableName)) {
            return 0;
        }

        $query = DB::table($tableName);

        // تطبيق الفلترة المخصصة إذا كانت محددة
        if (!empty($widget['filter_column']) && Schema::hasColumn($tableName, $widget['filter_column'])) {
            $filterColumn = $widget['filter_column'];
            $filterOperator = $widget['filter_operator'] ?? '=';
            $filterValue = $widget['filter_value'] ?? '';

            // معالجة العمليات الخاصة
            if ($filterOperator === 'IS NULL') {
                $query->whereNull($filterColumn);
            } elseif ($filterOperator === 'IS NOT NULL') {
                $query->whereNotNull($filterColumn);
            } elseif ($filterOperator === 'LIKE' || $filterOperator === 'NOT LIKE') {
                // إضافة علامات % للبحث
                $query->where($filterColumn, $filterOperator, '%' . $filterValue . '%');
            } else {
                // العمليات العادية: =, !=, >, <, >=, <=
                $query->where($filterColumn, $filterOperator, $filterValue);
            }
        }

        // تطبيق فلتر الفترة الزمنية
        if ($statPeriod && Schema::hasColumn($tableName, 'created_at')) {
            switch ($statPeriod) {
                case 'today':
                    $query->whereDate('created_at', Carbon::today());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('created_at', Carbon::now()->month)
                          ->whereYear('created_at', Carbon::now()->year);
                    break;
                case 'year':
                    $query->whereYear('created_at', Carbon::now()->year);
                    break;
            }
        }

        $result = 0;
        switch ($statType) {
            case 'count':
                $result = $query->count();
                break;

            case 'count_today':
                $result = $query->whereDate('created_at', Carbon::today())->count();
                break;

            case 'count_month':
                $result = $query->whereMonth('created_at', Carbon::now()->month)
                               ->whereYear('created_at', Carbon::now()->year)
                               ->count();
                break;

            case 'sum':
                $column = $widget['stat_field'] ?? 'id';
                if (Schema::hasColumn($tableName, $column)) {
                    $result = $query->sum($column) ?? 0;
                }
                break;

            case 'avg':
                $column = $widget['stat_field'] ?? 'id';
                if (Schema::hasColumn($tableName, $column)) {
                    $result = round($query->avg($column) ?? 0, 2);
                }
                break;

            case 'max':
                $column = $widget['stat_field'] ?? 'id';
                if (Schema::hasColumn($tableName, $column)) {
                    $result = $query->max($column) ?? 0;
                }
                break;

            case 'min':
                $column = $widget['stat_field'] ?? 'id';
                if (Schema::hasColumn($tableName, $column)) {
                    $result = $query->min($column) ?? 0;
                }
                break;

            default:
                $result = $query->count();
        }

        // حفظ النتيجة في التخزين المؤقت إذا كان مفعلاً
        if ($cacheTime > 0) {
            Cache::put($cacheKey, $result, $cacheTime);
        }

        return $result;
    }

    private function getTableData($widget)
    {
        $module = $widget['module'];
        $limit = $widget['limit'] ?? 10; // تحديث من table_limit إلى limit
        $columns = $widget['columns'] ?? ['*']; // تحديث من table_columns إلى columns
        $orderBy = $widget['order_by'] ?? 'id';
        $orderDirection = $widget['order_direction'] ?? 'desc';

        // تحويل اسم الوحدة إلى اسم الجدول الصحيح
        $tableName = $this->getTableNameFromModule($module);

        if (!Schema::hasTable($tableName)) {
            return [];
        }

        $query = DB::table($tableName);

        // تطبيق فلتر الحقل المخصص (الجديد)
        if (!empty($widget['filter_column']) && Schema::hasColumn($tableName, $widget['filter_column'])) {
            $filterColumn = $widget['filter_column'];
            $filterOperator = $widget['filter_operator'] ?? '=';
            $filterValue = $widget['filter_value'] ?? '';

            // معالجة العمليات الخاصة
            if ($filterOperator === 'IS NULL') {
                $query->whereNull($filterColumn);
            } elseif ($filterOperator === 'IS NOT NULL') {
                $query->whereNotNull($filterColumn);
            } elseif ($filterOperator === 'LIKE' || $filterOperator === 'NOT LIKE') {
                $query->where($filterColumn, $filterOperator, '%' . $filterValue . '%');
            } else {
                $query->where($filterColumn, $filterOperator, $filterValue);
            }
        }

        // تطبيق فلتر التاريخ (الجديد)
        if (!empty($widget['date_filter']) && Schema::hasColumn($tableName, 'created_at')) {
            $dateFilter = $widget['date_filter'];

            switch ($dateFilter) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'yesterday':
                    $query->whereDate('created_at', today()->subDay());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [now()->subDays(7), now()]);
                    break;
                case 'month':
                    $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                    break;
                case 'last_month':
                    $query->whereBetween('created_at', [
                        now()->subMonth()->startOfMonth(),
                        now()->subMonth()->endOfMonth()
                    ]);
                    break;
                case 'year':
                    $query->whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()]);
                    break;
                case 'custom':
                    if (!empty($widget['date_from']) && !empty($widget['date_to'])) {
                        $query->whereBetween('created_at', [
                            $widget['date_from'] . ' 00:00:00',
                            $widget['date_to'] . ' 23:59:59'
                        ]);
                    }
                    break;
            }
        }

        // إضافة الأعمدة المطلوبة
        if ($columns !== ['*'] && is_array($columns) && !empty($columns)) {
            $validColumns = array_filter($columns, function($column) use ($tableName) {
                return Schema::hasColumn($tableName, $column);
            });
            if (!empty($validColumns)) {
                // إضافة id إذا لم يكن موجوداً لضمان الترتيب
                if (!in_array('id', $validColumns) && Schema::hasColumn($tableName, 'id')) {
                    $validColumns[] = 'id';
                }
                $query->select($validColumns);
            }
        }

        // تطبيق الترتيب
        if (!empty($orderBy) && Schema::hasColumn($tableName, $orderBy)) {
            $query->orderBy($orderBy, $orderDirection);
        } else {
            $query->orderBy('id', 'desc');
        }

        // تطبيق فلاتر الفترة الزمنية إذا كانت متوفرة (القديمة - للتوافق)
        if (isset($widget['stat_period']) && !empty($widget['stat_period'])) {
            $period = $widget['stat_period'];
            $dateColumn = 'created_at'; // يمكن تخصيصه لاحقاً

            if (Schema::hasColumn($tableName, $dateColumn)) {
                switch ($period) {
                    case 'today':
                        $query->whereDate($dateColumn, today());
                        break;
                    case 'week':
                        $query->whereBetween($dateColumn, [now()->startOfWeek(), now()->endOfWeek()]);
                        break;
                    case 'month':
                        $query->whereBetween($dateColumn, [now()->startOfMonth(), now()->endOfMonth()]);
                        break;
                    case 'year':
                        $query->whereBetween($dateColumn, [now()->startOfYear(), now()->endOfYear()]);
                        break;
                }
            }
        }

        // تطبيق البحث إذا كان مفعلاً
        $widgetId = $widget['id'];
        if (($widget['searchable'] ?? false) && !empty($this->searchTerms[$widgetId])) {
            $searchTerm = $this->searchTerms[$widgetId];
            $searchColumns = $columns !== ['*'] && is_array($columns) ? $columns : [];

            if (!empty($searchColumns)) {
                $query->where(function($q) use ($searchColumns, $searchTerm, $tableName) {
                    foreach ($searchColumns as $column) {
                        if (Schema::hasColumn($tableName, $column)) {
                            $q->orWhere($column, 'LIKE', '%' . $searchTerm . '%');
                        }
                    }
                });
            }
        }

        // تطبيق فلتر التاريخ المخصص
        if (($widget['with_filters'] ?? false) && !empty($this->dateFilters[$widgetId])) {
            $dateFilter = $this->dateFilters[$widgetId];
            $dateColumn = 'created_at';

            if (Schema::hasColumn($tableName, $dateColumn)) {
                switch ($dateFilter) {
                    case 'today':
                        $query->whereDate($dateColumn, today());
                        break;
                    case 'yesterday':
                        $query->whereDate($dateColumn, today()->subDay());
                        break;
                    case 'this_week':
                        $query->whereBetween($dateColumn, [now()->startOfWeek(), now()->endOfWeek()]);
                        break;
                    case 'last_week':
                        $query->whereBetween($dateColumn, [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()]);
                        break;
                    case 'this_month':
                        $query->whereBetween($dateColumn, [now()->startOfMonth(), now()->endOfMonth()]);
                        break;
                    case 'last_month':
                        $query->whereBetween($dateColumn, [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()]);
                        break;
                }
            }
        }

        // تطبيق فلتر الحالة
        if (($widget['with_filters'] ?? false) && !empty($this->statusFilters[$widgetId])) {
            $statusFilter = $this->statusFilters[$widgetId];
            $statusColumn = 'status'; // يمكن تخصيصه لاحقاً

            if (Schema::hasColumn($tableName, $statusColumn)) {
                $query->where($statusColumn, $statusFilter);
            }
        }

        return $query->limit($limit)->get()->toArray();
    }

    private function getChartData($widget)
    {
        // دعم النظام الجديد البسيط - نسخ من مولد التقارير
        if (isset($widget['enable_charts']) && $widget['enable_charts'] &&
            isset($widget['chart_settings']) && is_array($widget['chart_settings'])) {
            return $this->getNewChartData($widget);
        }

        // النظام القديم للتوافق العكسي
        if (isset($widget['chart_columns']) && is_array($widget['chart_columns'])) {
            return $this->getMultiColumnChartData($widget);
        }

        $module = $widget['module'];
        $chartType = $widget['chart_type'] ?? 'bar';
        $dataField = $widget['data_field'] ?? 'id';
        $labelField = $widget['label_field'] ?? 'created_at';
        $aggregation = $widget['aggregation'] ?? 'count';
        $maxItems = $widget['max_items'] ?? 10;

        // تحويل اسم الوحدة إلى اسم الجدول الصحيح
        $tableName = $this->getTableNameFromModule($module);

        if (!Schema::hasTable($tableName)) {
            Log::error("الجدول غير موجود: $tableName للوحدة: $module");
            return ['labels' => [], 'data' => []];
        }

        // تتبع للتأكد من وصول البيانات
        Log::info("بداية تحميل بيانات المخطط", [
            'module' => $module,
            'table' => $tableName,
            'data_field' => $dataField,
            'label_field' => $labelField,
            'aggregation' => $aggregation
        ]);

        $query = DB::table($tableName);

        // التحقق من وجود الحقول المطلوبة
        if (!Schema::hasColumn($tableName, $labelField)) {
            Log::error("الحقل غير موجود: $labelField في الجدول: $tableName");
            return ['labels' => [], 'data' => []];
        }

        if ($aggregation !== 'count' && !Schema::hasColumn($tableName, $dataField)) {
            Log::error("حقل البيانات غير موجود: $dataField في الجدول: $tableName");
            return ['labels' => [], 'data' => []];
        }

        try {
            // بناء الاستعلام بطريقة مولد التقارير المحسّنة
            $data = $this->buildChartQuery($query, $labelField, $dataField, $aggregation, $maxItems);

            Log::info("بيانات المخطط للوحدة $module:", [
                'table' => $tableName,
                'data_count' => count($data),
                'labels' => array_column($data, 'label'),
                'values' => array_column($data, 'value')
            ]);

            return [
                'labels' => array_column($data, 'label'),
                'data' => array_map(function($item) {
                    // تحويل البيانات للنوع الصحيح في حال كانت stdClass
                    $value = is_array($item) ? $item['value'] : $item->value;
                    return is_numeric($value) ? round($value, 2) : 0;
                }, $data)
            ];

        } catch (\Exception $e) {
            Log::error("خطأ في جلب بيانات المخطط: " . $e->getMessage(), [
                'module' => $module,
                'table' => $tableName,
                'error' => $e->getTraceAsString()
            ]);

            return ['labels' => [], 'data' => []];
        }
    }

    /**
     * بناء استعلام المخطط بطريقة محسّنة (مستوحاة من مولد التقارير)
     */
    private function buildChartQuery($query, $labelField, $dataField, $aggregation, $maxItems)
    {
        switch ($aggregation) {
            case 'sum':
                return $query->select(
                    DB::raw("{$labelField} as label"),
                    DB::raw("SUM({$dataField}) as value")
                )
                ->whereNotNull($labelField)
                ->whereNotNull($dataField)
                ->groupBy($labelField)
                ->orderBy('value', 'desc')
                ->limit($maxItems)
                ->get()
                ->toArray();

            case 'avg':
                return $query->select(
                    DB::raw("{$labelField} as label"),
                    DB::raw("AVG({$dataField}) as value")
                )
                ->whereNotNull($labelField)
                ->whereNotNull($dataField)
                ->groupBy($labelField)
                ->orderBy('value', 'desc')
                ->limit($maxItems)
                ->get()
                ->toArray();

            case 'count':
                return $query->select(
                    DB::raw("{$labelField} as label"),
                    DB::raw("COUNT(*) as value")
                )
                ->whereNotNull($labelField)
                ->groupBy($labelField)
                ->orderBy('value', 'desc')
                ->limit($maxItems)
                ->get()
                ->toArray();

            case 'max':
                return $query->select(
                    DB::raw("{$labelField} as label"),
                    DB::raw("MAX({$dataField}) as value")
                )
                ->whereNotNull($labelField)
                ->whereNotNull($dataField)
                ->groupBy($labelField)
                ->orderBy('value', 'desc')
                ->limit($maxItems)
                ->get()
                ->toArray();

            case 'min':
                return $query->select(
                    DB::raw("{$labelField} as label"),
                    DB::raw("MIN({$dataField}) as value")
                )
                ->whereNotNull($labelField)
                ->whereNotNull($dataField)
                ->groupBy($labelField)
                ->orderBy('value', 'asc')
                ->limit($maxItems)
                ->get()
                ->toArray();

            default:
                // fallback للعدّ
                return $query->select(
                    DB::raw("{$labelField} as label"),
                    DB::raw("COUNT(*) as value")
                )
                ->whereNotNull($labelField)
                ->groupBy($labelField)
                ->orderBy('value', 'desc')
                ->limit($maxItems)
                ->get()
                ->toArray();
        }
    }

    /**
     * جلب أسماء الجداول من dynamic-menu.php وتحويل اسم الوحدة إلى اسم الجدول
     */
    private function getTableNameFromModule($moduleName)
    {
        // جلب قائمة الوحدات من dynamic-menu.php
        $dynamicMenu = config('dynamic-menu.menu_items', []);
        $moduleRoutes = $this->extractItemRoutes($dynamicMenu);

        // البحث عن الوحدة في القائمة
        if (in_array($moduleName, $moduleRoutes)) {
            // تحويل اسم الوحدة إلى اسم الجدول (تحويل إلى أحرف صغيرة)
            return strtolower($moduleName);
        }

        // إضافة جداول أساسية إضافية
        $systemTables = [
            'Users' => 'users',
            'Roles' => 'roles',
            'Permissions' => 'permissions',
        ];

        return $systemTables[$moduleName] ?? strtolower($moduleName);
    }

    /**
     * استخراج routes من عناصر القائمة من نوع item فقط
     */
    private function extractItemRoutes($menuItems)
    {
        $routes = [];

        foreach ($menuItems as $item) {
            if ($item['type'] === 'item' && isset($item['route'])) {
                $routes[] = $item['route'];
            } elseif ($item['type'] === 'group' && isset($item['children'])) {
                // البحث في العناصر الفرعية
                $routes = array_merge($routes, $this->extractItemRoutes($item['children']));
            }
        }

        return array_unique($routes);
    }

    public function searchWidget($widgetId)
    {
        $this->loadWidgetData();
    }

    public function clearSearch($widgetId)
    {
        $this->searchTerms[$widgetId] = '';
        $this->loadWidgetData();
    }

    public function applyDateFilter($widgetId)
    {
        $this->loadWidgetData();
    }

    public function clearDateFilter($widgetId)
    {
        $this->dateFilters[$widgetId] = '';
        $this->loadWidgetData();
    }

    public function applyStatusFilter($widgetId)
    {
        $this->loadWidgetData();
    }

    public function clearStatusFilter($widgetId)
    {
        $this->statusFilters[$widgetId] = '';
        $this->loadWidgetData();
    }

    /**
     * معالجة النظام الجديد البسيط للمخططات - نسخ من مولد التقارير
     */
    private function getNewChartData($widget)
    {
        $module = $widget['module'];
        $chartSettings = $widget['chart_settings'];
        $tableName = $this->getTableNameFromModule($module);

        if (!Schema::hasTable($tableName)) {
            return ['labels' => [], 'datasets' => []];
        }

        // إذا كان هناك مخطط واحد فقط
        if (count($chartSettings) === 1) {
            $chart = $chartSettings[0];
            return $this->buildSimpleChart($chart, $tableName);
        }

        // معالجة عدة مخططات
        $allDatasets = [];
        $commonLabels = [];

        foreach ($chartSettings as $index => $chart) {
            $chartData = $this->buildSimpleChart($chart, $tableName);

            if (!empty($chartData['labels'])) {
                if (empty($commonLabels)) {
                    $commonLabels = $chartData['labels'];
                }

                if (isset($chartData['datasets'])) {
                    foreach ($chartData['datasets'] as $dataset) {
                        $dataset['label'] = ($chart['title'] ?? 'مخطط ' . ($index + 1)) . ' - ' . $dataset['label'];
                        $allDatasets[] = $dataset;
                    }
                }
            }
        }

        return [
            'labels' => $commonLabels,
            'datasets' => $allDatasets
        ];
    }

    /**
     * بناء مخطط بسيط
     */
    private function buildSimpleChart($chart, $tableName)
    {
        $xAxisField = $chart['xAxisField'] ?? 'id';
        $maxItems = 10;

        // إذا لم تكن هناك أعمدة محددة، استخدم العد البسيط
        if (!isset($chart['columns']) || empty($chart['columns'])) {
            $query = DB::table($tableName);
            return $this->buildChartQuery($query, $xAxisField, 'id', 'count', $maxItems);
        }

        // معالجة الأعمدة المتعددة مثل مولد التقارير
        $columns = $chart['columns'];
        $labels = [];
        $datasets = [];

        // جمع التصنيفات (Labels)
        $labels = DB::table($tableName)
            ->select($xAxisField)
            ->whereNotNull($xAxisField)
            ->distinct()
            ->orderBy($xAxisField)
            ->limit($maxItems)
            ->pluck($xAxisField)
            ->toArray();

        // بناء datasets لكل عمود
        foreach ($columns as $column) {
            $field = $column['field'];
            $label = $column['label'] ?? $field;
            $color = $column['color'] ?? '#696CFF';

            if (!Schema::hasColumn($tableName, $field)) {
                continue;
            }

            $data = [];
            foreach ($labels as $labelValue) {
                $query = DB::table($tableName)->where($xAxisField, $labelValue);
                $value = $query->sum($field); // استخدام sum بشكل افتراضي
                $data[] = round($value ?? 0, 2);
            }

            $datasets[] = [
                'label' => $label,
                'data' => $data,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'borderWidth' => 1
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets
        ];
    }

    public function render()
    {
        return view('livewire.dashboard.dashboard-main');
    }
}
