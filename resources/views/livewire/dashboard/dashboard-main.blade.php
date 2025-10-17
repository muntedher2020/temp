<div class="row">
    @if(empty($widgets))
        {{-- رسالة ترحيبية في حالة عدم وجود ويدجتس --}}
        <div class="col-12">
            <div class="card h-100">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <img src="{{ asset('assets/img/illustrations/faq-illustration.png') }}" alt="Welcome" class="img-fluid" style="max-height: 200px;">
                    </div>
                    <h2 class="mb-3 text-primary">مرحباً بك في لوحة التحكم!</h2>
                    <p class="fs-5 text-muted">يمكنك إدارة محتوى الداشبورد من خلال أدوات التطوير</p>
                    @can('dashboard-builder-access')
                        <a href="{{ route('dashboard-builder.index') }}" class="btn btn-primary mt-3">
                            <i class="mdi mdi-view-dashboard-edit me-1"></i>
                            إدارة الداشبورد
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    @else
        {{-- عرض الويدجتس الديناميكية --}}
        @foreach($widgets as $widget)
            @if($widget['type'] === 'stat')
                @php
                    // تحديد عرض العنصر
                    $widthClass = match($widget['width'] ?? 'quarter') {
                        'full' => 'col-12',
                        'half' => 'col-xl-6 col-md-6',
                        'third' => 'col-xl-4 col-md-6',
                        'quarter' => 'col-xl-3 col-md-6',
                        default => 'col-xl-3 col-md-6'
                    };

                    // تحديد ارتفاع العنصر
                    $heightStyle = match($widget['height'] ?? 'auto') {
                        'small' => 'min-height: 200px;',
                        'medium' => 'min-height: 300px;',
                        'large' => 'min-height: 400px;',
                        default => ''
                    };

                    // تحديد نوع الإطار
                    $borderClass = match($widget['border'] ?? 'card') {
                        'shadow' => 'card shadow-lg border-0',
                        'borderless' => 'card border-0',
                        'minimal' => 'card border-0 bg-transparent',
                        default => 'card border-0 shadow-sm'
                    };

                    // تحديد إظهار الحدود
                    if (!($widget['show_border'] ?? true)) {
                        $borderClass .= ' border-0';
                    }

                    // تحديد لون الويدجت
                    $isCustomColor = str_starts_with($widget['color'] ?? 'primary', '#');
                    $colorClass = $isCustomColor ? '' : ($widget['color'] ?? 'primary');
                    $colorStyle = $isCustomColor ? ($widget['color'] ?? '#696CFF') : '';
                @endphp

                <div class="{{ $widthClass }} mb-4">
                    <div class="{{ $borderClass }} h-100" style="{{ $heightStyle }}">
                        @if($widget['show_header'] ?? true)
                            <div class="card-header bg-transparent border-0 pb-0">
                                <h6 class="card-title mb-0 @if($isCustomColor) text-dark @else text-{{ $colorClass }} @endif" @if($isCustomColor) style="color: {{ $colorStyle }} !important;" @endif>
                                    <i class="mdi {{ $widget['icon'] ?? 'mdi-chart-line' }} me-2"></i>
                                    {{ $widget['title'] }}
                                </h6>
                                @if(!empty($widget['label']) && $widget['label'] !== $widget['title'])
                                    <small class="text-muted">{{ $widget['label'] }}</small>
                                @endif
                            </div>
                        @endif
                        <div class="card-body {{ ($widget['show_header'] ?? true) ? 'pt-2' : '' }}">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-md">
                                        <div class="avatar-initial @if($isCustomColor) text-white @else bg-{{ $colorClass }} @endif rounded" @if($isCustomColor) style="background-color: {{ $colorStyle }} !important;" @endif>
                                            <i class="mdi {{ $widget['icon'] ?? 'mdi-chart-line' }} mdi-24px"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="mt-2">
                                        <h4 class="mb-0 @if($isCustomColor) text-dark @else text-{{ $colorClass }} @endif" @if($isCustomColor) style="color: {{ $colorStyle }} !important;" @endif>
                                            {{ number_format($widgetData[$widget['id']] ?? 0) }}
                                        </h4>
                                        @if(!($widget['show_header'] ?? true))
                                            <small class="text-muted">{{ $widget['title'] }}</small>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if(isset($widget['refresh_interval']) && $widget['refresh_interval'] !== 'manual' && $widget['refresh_interval'] !== '0')
                                <small class="text-muted d-block mt-2">
                                    <i class="mdi mdi-refresh me-1"></i>
                                    يتم التحديث كل {{ $widget['refresh_interval'] }} ثانية
                                </small>
                            @endif
                        </div>
                    </div>
                </div>
            @elseif($widget['type'] === 'table')
                @php
                    // تحديد عرض العنصر
                    $widthClass = match($widget['width'] ?? 'full') {
                        'full' => 'col-12',
                        'half' => 'col-xl-6 col-md-12',
                        'third' => 'col-xl-4 col-md-6',
                        'quarter' => 'col-xl-3 col-md-6',
                        default => 'col-12'
                    };

                    // تحديد ارتفاع العنصر
                    $heightStyle = match($widget['height'] ?? 'auto') {
                        'small' => 'max-height: 300px; overflow-y: auto;',
                        'medium' => 'max-height: 400px; overflow-y: auto;',
                        'large' => 'max-height: 500px; overflow-y: auto;',
                        default => ''
                    };

                    // تحديد نوع الإطار
                    $borderClass = match($widget['border'] ?? 'card') {
                        'shadow' => 'card shadow-lg border-0',
                        'borderless' => 'card border-0',
                        'minimal' => 'card border-0 bg-transparent',
                        default => 'card border-0 shadow-sm'
                    };

                    // تحديد إظهار الحدود
                    if (!($widget['show_border'] ?? true)) {
                        $borderClass .= ' border-0';
                    }

                    // إعدادات الجدول
                    $tableClasses = ['table', 'table-sm'];
                    if ($widget['striped'] ?? true) $tableClasses[] = 'table-striped';
                    if ($widget['hover'] ?? true) $tableClasses[] = 'table-hover';
                    if ($widget['bordered'] ?? true) $tableClasses[] = 'table-bordered';

                    // تحديد لون الجدول
                    $colorScheme = $widget['color_scheme'] ?? 'default';
                    $headerClass = 'table-light';
                    $customStyle = '';

                    if ($colorScheme !== 'default' && $colorScheme !== 'custom') {
                        $headerClass = 'table-' . $colorScheme;
                    } elseif ($colorScheme === 'custom') {
                        $customColor = $widget['custom_color'] ?? '#696CFF';
                        $customStyle = "style=\"background-color: {$customColor}; color: white;\"";
                    }
                @endphp

                <div class="{{ $widthClass }} mb-4">
                    <div class="{{ $borderClass }}" style="{{ $heightStyle }}">
                        @if($widget['show_header'] ?? true)
                            <div class="card-header bg-transparent border-0 pb-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0 text-primary">
                                        <i class="mdi mdi-table me-2"></i>
                                        {{ $widget['title'] }}
                                    </h6>
                                    <small class="text-muted">
                                        آخر {{ $widget['limit'] ?? 10 }} سجلات
                                        @if($widget['searchable'] ?? false)
                                            <i class="mdi mdi-magnify ms-1" title="قابل للبحث"></i>
                                        @endif
                                    </small>
                                </div>
                            </div>
                        @endif


                        <div class="card-body {{ ($widget['show_header'] ?? true) ? 'pt-2' : '' }}">
                            <!-- شريط البحث والفلاتر -->
                            @if(($widget['searchable'] ?? false) || ($widget['with_filters'] ?? false))
                                <div class="row mb-3">
                                    @if($widget['searchable'] ?? false)
                                        <div class="col-md-6">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">
                                                    <i class="mdi mdi-magnify"></i>
                                                </span>
                                                <input type="text"
                                                       class="form-control"
                                                       placeholder="البحث في الجدول..."
                                                       wire:model.debounce.300ms="searchTerms.{{ $widget['id'] }}"
                                                       wire:keydown.enter="searchWidget('{{ $widget['id'] }}')">
                                                @if(!empty($searchTerms[$widget['id']] ?? ''))
                                                    <button class="btn btn-outline-secondary"
                                                            type="button"
                                                            wire:click="clearSearch('{{ $widget['id'] }}')"
                                                            title="مسح البحث">
                                                        <i class="mdi mdi-close"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    @if($widget['with_filters'] ?? false)
                                        <div class="col-md-6">
                                            <div class="d-flex gap-2">
                                                <!-- فلتر التاريخ -->
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">
                                                        <i class="mdi mdi-calendar"></i>
                                                    </span>
                                                    <select class="form-select"
                                                            wire:model="dateFilters.{{ $widget['id'] }}"
                                                            wire:change="applyDateFilter('{{ $widget['id'] }}')">
                                                        <option value="">كل الفترات</option>
                                                        <option value="today">اليوم</option>
                                                        <option value="yesterday">أمس</option>
                                                        <option value="this_week">هذا الأسبوع</option>
                                                        <option value="last_week">الأسبوع الماضي</option>
                                                        <option value="this_month">هذا الشهر</option>
                                                        <option value="last_month">الشهر الماضي</option>
                                                    </select>
                                                    @if(!empty($dateFilters[$widget['id']] ?? ''))
                                                        <button class="btn btn-outline-secondary"
                                                                type="button"
                                                                wire:click="clearDateFilter('{{ $widget['id'] }}')"
                                                                title="مسح فلتر التاريخ">
                                                            <i class="mdi mdi-close"></i>
                                                        </button>
                                                    @endif
                                                </div>

                                                <!-- فلتر الحالة -->
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">
                                                        <i class="mdi mdi-format-list-bulleted"></i>
                                                    </span>
                                                    <select class="form-select"
                                                            wire:model="statusFilters.{{ $widget['id'] }}"
                                                            wire:change="applyStatusFilter('{{ $widget['id'] }}')">
                                                        <option value="">كل الحالات</option>
                                                        <option value="active">مفعل</option>
                                                        <option value="inactive">غير مفعل</option>
                                                        <option value="pending">في الانتظار</option>
                                                        <option value="completed">مكتمل</option>
                                                    </select>
                                                    @if(!empty($statusFilters[$widget['id']] ?? ''))
                                                        <button class="btn btn-outline-secondary"
                                                                type="button"
                                                                wire:click="clearStatusFilter('{{ $widget['id'] }}')"
                                                                title="مسح فلتر الحالة">
                                                            <i class="mdi mdi-close"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            @if(!empty($widgetData[$widget['id']]))
                                <!-- مؤشر النتائج والفلاتر المطبقة -->
                                @if(($widget['searchable'] ?? false) || ($widget['with_filters'] ?? false))
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <small class="text-muted">
                                                <i class="mdi mdi-format-list-numbered me-1"></i>
                                                عرض {{ count($widgetData[$widget['id']]) }} من النتائج
                                            </small>
                                        </div>
                                        <div class="d-flex gap-2">
                                            @if(!empty($searchTerms[$widget['id']] ?? ''))
                                                <span class="badge bg-primary">
                                                    <i class="mdi mdi-magnify me-1"></i>
                                                    "{{ $searchTerms[$widget['id']] }}"
                                                </span>
                                            @endif
                                            @if(!empty($dateFilters[$widget['id']] ?? ''))
                                                <span class="badge bg-info">
                                                    <i class="mdi mdi-calendar me-1"></i>
                                                    {{
                                                        match($dateFilters[$widget['id']]) {
                                                            'today' => 'اليوم',
                                                            'yesterday' => 'أمس',
                                                            'this_week' => 'هذا الأسبوع',
                                                            'last_week' => 'الأسبوع الماضي',
                                                            'this_month' => 'هذا الشهر',
                                                            'last_month' => 'الشهر الماضي',
                                                            default => $dateFilters[$widget['id']]
                                                        }
                                                    }}
                                                </span>
                                            @endif
                                            @if(!empty($statusFilters[$widget['id']] ?? ''))
                                                <span class="badge bg-warning">
                                                    <i class="mdi mdi-format-list-bulleted me-1"></i>
                                                    {{
                                                        match($statusFilters[$widget['id']]) {
                                                            'active' => 'مفعل',
                                                            'inactive' => 'غير مفعل',
                                                            'pending' => 'في الانتظار',
                                                            'completed' => 'مكتمل',
                                                            default => $statusFilters[$widget['id']]
                                                        }
                                                    }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <div class="table-responsive">
                                    <table class="{{ implode(' ', $tableClasses) }}">
                                        <thead class="{{ $headerClass }}" {!! $customStyle !!}>
                                            <tr>
                                                @if(isset($widget['columns']) && is_array($widget['columns']))
                                                    @foreach($widget['columns'] as $column)
                                                        @php
                                                            // البحث عن التسمية العربية للعمود
                                                            $columnLabel = $column;

                                                            // محاولة جلب التسمية من قاعدة البيانات إذا كان الجدول محدد
                                                            if (isset($widget['module'])) {
                                                                try {
                                                                    $tableInfo = DB::select("
                                                                        SELECT COLUMN_COMMENT
                                                                        FROM information_schema.COLUMNS
                                                                        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
                                                                    ", [config('database.connections.mysql.database'), $widget['module'], $column]);

                                                                    if (!empty($tableInfo) && !empty($tableInfo[0]->COLUMN_COMMENT)) {
                                                                        $columnLabel = $tableInfo[0]->COLUMN_COMMENT;
                                                                    } else {
                                                                        $columnLabel = ucwords(str_replace('_', ' ', $column));
                                                                    }
                                                                } catch (Exception $e) {
                                                                    $columnLabel = ucwords(str_replace('_', ' ', $column));
                                                                }
                                                            }
                                                        @endphp
                                                        <th>{{ $columnLabel }}</th>
                                                    @endforeach
                                                @else
                                                    @if(!empty($widgetData[$widget['id']]))
                                                        @foreach(array_keys((array)$widgetData[$widget['id']][0]) as $column)
                                                            <th>{{ ucfirst(str_replace('_', ' ', $column)) }}</th>
                                                        @endforeach
                                                    @endif
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($widgetData[$widget['id']] as $row)
                                                <tr>
                                                    @if(isset($widget['columns']) && is_array($widget['columns']))
                                                        @foreach($widget['columns'] as $column)
                                                            <td>{{ data_get($row, $column, '') }}</td>
                                                        @endforeach
                                                    @else
                                                        @foreach((array)$row as $value)
                                                            <td>{{ $value }}</td>
                                                        @endforeach
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    @if(!empty($searchTerms[$widget['id']] ?? '') || !empty($dateFilters[$widget['id']] ?? '') || !empty($statusFilters[$widget['id']] ?? ''))
                                        <i class="mdi mdi-filter-remove mdi-48px mb-2 d-block"></i>
                                        <p class="mb-2">لا توجد نتائج تطابق الفلاتر المطبقة</p>
                                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                                            @if(!empty($searchTerms[$widget['id']] ?? ''))
                                                <button class="btn btn-sm btn-outline-primary" wire:click="clearSearch('{{ $widget['id'] }}')">
                                                    <i class="mdi mdi-close me-1"></i>
                                                    مسح البحث
                                                </button>
                                            @endif
                                            @if(!empty($dateFilters[$widget['id']] ?? ''))
                                                <button class="btn btn-sm btn-outline-info" wire:click="clearDateFilter('{{ $widget['id'] }}')">
                                                    <i class="mdi mdi-close me-1"></i>
                                                    مسح فلتر التاريخ
                                                </button>
                                            @endif
                                            @if(!empty($statusFilters[$widget['id']] ?? ''))
                                                <button class="btn btn-sm btn-outline-warning" wire:click="clearStatusFilter('{{ $widget['id'] }}')">
                                                    <i class="mdi mdi-close me-1"></i>
                                                    مسح فلتر الحالة
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        <i class="mdi mdi-database-remove mdi-48px mb-2 d-block"></i>
                                        <p class="mb-0">لا توجد بيانات للعرض</p>
                                    @endif
                                    @if(!($widget['show_header'] ?? true))
                                        <small class="text-muted d-block mt-1">{{ $widget['title'] }}</small>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @elseif($widget['type'] === 'chart')
                <div class="col-xl-6 col-lg-8 col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ $widget['title'] }}</h5>
                            @if(!empty($widget['description']))
                                <small class="text-muted">{{ $widget['description'] }}</small>
                            @endif
                        </div>
                        <div class="card-body">
                            @if(!empty($widgetData[$widget['id']]['labels']))
                                @php
                                    // تحديد ارتفاع المخطط - دعم النظام الجديد والقديم
                                    $chartHeight = $widget['chart_height'] ?? $widget['height'] ?? 300;
                                    if (isset($widget['enable_charts']) && $widget['enable_charts'] &&
                                        isset($widget['chart_settings']) && !empty($widget['chart_settings'])) {
                                        $chartHeight = $widget['chart_settings'][0]['chartHeight'] ?? 300;
                                    }
                                @endphp
                                <div class="chart-container">
                                    <canvas id="chart-{{ $widget['id'] }}" style="height: {{ $chartHeight }}px;"></canvas>
                                </div>
                            @else
                                <div class="text-center py-5 text-muted">
                                    <i class="mdi mdi-chart-{{ $widget['chart_type'] ?? 'bar' }} mdi-48px mb-2"></i>
                                    <p class="mb-0">لا توجد بيانات للرسم البياني</p>
                                    <small>تحقق من إعدادات المخطط والبيانات المصدر</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    @endif
</div>

@if(!empty($widgets))
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @foreach($widgets as $widget)
                @if($widget['type'] === 'chart' && !empty($widgetData[$widget['id']]['labels']))
                    {
                        const ctx{{ $widget['id'] }} = document.getElementById('chart-{{ $widget['id'] }}');
                        if (ctx{{ $widget['id'] }}) {
                            // تحديد نوع المخطط - دعم النظام الجديد والقديم
                            let chartType = '{{ $widget['chart_type'] ?? 'bar' }}';

                            @if(isset($widget['enable_charts']) && $widget['enable_charts'] && isset($widget['chart_settings']) && !empty($widget['chart_settings']))
                                // النظام الجديد - استخدام نوع المخطط الأول
                                chartType = '{{ $widget['chart_settings'][0]['type'] ?? 'bar' }}';
                            @endif

                            // إعداد الألوان
                            let colors = ['#696cff', '#8592a3', '#71dd37', '#ffab00', '#ff6b6b', '#00d4bd', '#826af9', '#2b9bf4', '#f1416c', '#50cd89'];
                            @if(!empty($widget['colors']))
                                @if(is_array($widget['colors']))
                                    colors = {!! json_encode($widget['colors']) !!};
                                @elseif(is_string($widget['colors']))
                                    colors = '{{ $widget['colors'] }}'.split(',');
                                @endif
                            @endif

                            // إعداد البيانات
                            const widgetData = {!! json_encode($widgetData[$widget['id']]) !!};
                            let chartData;

                            // التحقق من نوع البيانات (عادي أم متعدد الأعمدة)
                            if (widgetData.datasets && Array.isArray(widgetData.datasets)) {
                                // مخططات متعددة الأعمدة
                                // إصلاح خاص للمخططات الدائرية والكعكية: كل حلقة يجب أن يكون لها ألوان متعددة
                                if (chartType === 'pie' || chartType === 'doughnut') {
                                    const processedDatasets = widgetData.datasets.map((dataset, datasetIndex) => {
                                        const dataLength = dataset.data ? dataset.data.length : 0;
                                        const backgroundColors = [];
                                        const borderColors = [];

                                        // إنشاء لون مختلف لكل قطعة في كل حلقة
                                        for (let i = 0; i < dataLength; i++) {
                                            backgroundColors.push(colors[i % colors.length]);
                                            borderColors.push('#ffffff');
                                        }

                                        return {
                                            ...dataset,
                                            backgroundColor: backgroundColors,
                                            borderColor: borderColors,
                                            borderWidth: 2
                                        };
                                    });

                                    chartData = {
                                        labels: widgetData.labels,
                                        datasets: processedDatasets
                                    };
                                } else {
                                    // المخططات الأخرى تبقى كما هي
                                    chartData = {
                                        labels: widgetData.labels,
                                        datasets: widgetData.datasets
                                    };
                                }
                            } else {
                                // مخططات عادية
                                // للمخططات الدائرية والكعكية: استخدام لون مختلف لكل قطعة (segment)
                                let backgroundColors, borderColors;
                                if (chartType === 'pie' || chartType === 'doughnut') {
                                    const dataLength = (widgetData.data || []).length;
                                    backgroundColors = [];
                                    borderColors = [];

                                    // إنشاء لون لكل قطعة
                                    for (let i = 0; i < dataLength; i++) {
                                        backgroundColors.push(colors[i % colors.length]);
                                        borderColors.push('#ffffff'); // حدود بيضاء للفصل بين القطع
                                    }
                                } else {
                                    // للمخططات الأخرى: لون واحد
                                    backgroundColors = colors[0];
                                    borderColors = colors[0];
                                }

                                chartData = {
                                    labels: widgetData.labels || [],
                                    datasets: [{
                                        label: '{{ $widget['title'] }}',
                                        data: widgetData.data || [],
                                        backgroundColor: backgroundColors,
                                        borderColor: borderColors,
                                        borderWidth: chartType === 'pie' || chartType === 'doughnut' ? 2 : 1,
                                        tension: chartType === 'line' ? 0.4 : 0
                                    }]
                                };
                            }

                            // إعداد الخيارات - دعم النظام الجديد والقديم
                            let showLegend = {{ $widget['show_legend'] ?? 'true' }};
                            @if(isset($widget['enable_charts']) && $widget['enable_charts'] && isset($widget['chart_settings']) && !empty($widget['chart_settings']))
                                showLegend = {{ $widget['chart_settings'][0]['showLegend'] ?? 'true' }};
                            @endif

                            const chartOptions = {
                                responsive: {{ $widget['responsive'] ?? 'true' }},
                                maintainAspectRatio: {{ $widget['maintain_aspect_ratio'] ?? 'false' }},
                                interaction: {
                                    intersect: {{ $widget['interaction'] ?? 'true' }}
                                },
                                animation: {{ $widget['animation'] ?? 'true' }},
                                plugins: {
                                    legend: {
                                        display: showLegend,
                                        position: 'top'
                                    },
                                    tooltip: {
                                        enabled: {{ $widget['show_tooltip'] ?? 'true' }}
                                    }
                                }
                            };

                            // إضافة إعدادات المحاور للمخططات غير الدائرية
                            if (chartType !== 'pie' && chartType !== 'doughnut') {
                                chartOptions.scales = {
                                    y: {
                                        beginAtZero: true,
                                        display: {{ $widget['show_y_axis'] ?? 'true' }},
                                        title: {
                                            display: {{ $widget['show_y_axis_title'] ?? 'false' }},
                                            text: '{{ $widget['y_axis_title'] ?? '' }}'
                                        }
                                    },
                                    x: {
                                        display: {{ $widget['show_x_axis'] ?? 'true' }},
                                        title: {
                                            display: {{ $widget['show_x_axis_title'] ?? 'false' }},
                                            text: '{{ $widget['x_axis_title'] ?? '' }}'
                                        }
                                    }
                                };
                            }

                            new Chart(ctx{{ $widget['id'] }}, {
                                type: chartType === 'doughnut' ? 'doughnut' : chartType,
                                data: chartData,
                                options: chartOptions
                            });
                        }
                    }
                @endif
            @endforeach
        });

        // نظام التحديث التلقائي للويدجتس (محسن للمخططات)
        @foreach($widgets as $widget)
            @if(isset($widget['refresh_interval']) && $widget['refresh_interval'] !== 'manual' && is_numeric($widget['refresh_interval']) && $widget['refresh_interval'] > 0)
                @if($widget['type'] !== 'chart')
                    // إعداد التحديث التلقائي للويدجت {{ $widget['id'] }} (باستثناء المخططات)
                    setInterval(function() {
                        console.log('تحديث تلقائي للويدجت: {{ $widget['title'] }}');
                        // إعادة تحديث البيانات عبر Livewire
                        @this.call('refreshWidgets');
                    }, {{ $widget['refresh_interval'] * 1000 }});
                @else
                    // إعداد التحديث التلقائي للمخطط {{ $widget['id'] }} (بدون إعادة تحميل الصفحة)
                    setInterval(function() {
                        console.log('تحديث تلقائي للمخطط: {{ $widget['title'] }}');
                        // تحديث بيانات المخطط فقط
                        @this.call('refreshSpecificWidget', '{{ $widget['id'] }}');
                    }, {{ $widget['refresh_interval'] * 1000 }});
                @endif
            @endif
        @endforeach

        // مراقبة تغييرات الويدجتس
        window.addEventListener('livewire:load', function () {
            Livewire.on('widgetsRefreshed', function () {
                console.log('تم تحديث بيانات الويدجتس');
                // تحديث القيم بدون إعادة تحميل كاملة للمحافظة على المخططات
                setTimeout(() => {
                    // تحديث فقط البيانات المرئية للويدجتس غير المخططات
                    location.reload();
                }, 100);
            });

            // مراقبة تحديث ويدجت محدد (للمخططات)
            Livewire.on('specificWidgetRefreshed', function (data) {
                console.log('تم تحديث ويدجت محدد:', data.widgetId);
                if (data.widgetData && data.widgetData.labels) {
                    // تحديث بيانات المخطط بدون إعادة إنشاء
                    updateChartData(data.widgetId, data.widgetData);
                }
            });
        });

        // دالة تحديث بيانات المخطط
        function updateChartData(widgetId, newData) {
            const chartElement = document.getElementById('chart-' + widgetId);
            if (chartElement) {
                const chart = Chart.getChart(chartElement);
                if (chart) {
                    // تحديث التسميات والبيانات
                    chart.data.labels = newData.labels;
                    if (newData.datasets && Array.isArray(newData.datasets)) {
                        chart.data.datasets = newData.datasets;
                    } else {
                        chart.data.datasets[0].data = newData.data || [];
                    }
                    chart.update('none'); // تحديث بدون رسوم متحركة للأداء
                    console.log('تم تحديث بيانات المخطط:', widgetId);
                }
            }
        }
    </script>
    @endpush
@endif
