<div>
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">
                        <i class="menu-icon tf-icons mdi mdi-chart-box-multiple-outline me-2"></i>
                        مولد التقارير المتقدم
                    </h4>
                    <div class="card-header-elements">
                        <button type="button" class="btn btn-primary" onclick="window.location.reload()">
                            <i class="mdi mdi-refresh me-1"></i>
                            تحديث
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">
                        إنشاء تقارير تفاعلية ومخططات بيانية من جميع الوحدات المتاحة في النظام
                    </p>
                </div>
            </div>
        </div>
    </div>
    <!-- مؤشر الخطوات -->
    <div class="step-indicator">
        <div class="step {{ $currentStep >= 1 ? 'active' : '' }}">
            <span class="step-number">1</span>
            اختيار الوحدة
        </div>
        <div class="step {{ $currentStep >= 2 ? 'active' : '' }}">
            <span class="step-number">2</span>
            تحديد الحقول
        </div>
        <div class="step {{ $currentStep >= 3 ? 'active' : '' }}">
            <span class="step-number">3</span>
            الفلاتر والترتيب
        </div>
        <div class="step {{ $currentStep >= 4 ? 'active' : '' }}">
            <span class="step-number">4</span>
            النتائج والمخططات
        </div>
    </div><!-- عرض الرسائل -->
    @if ($errorMessage)
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="btn-close" wire:click="closeMessage"></button>
            <strong>خطأ!</strong> {{ $errorMessage }}
        </div>
    @endif

    @if ($successMessage)
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="btn-close" wire:click="closeMessage"></button>
            <strong>نجح!</strong> {{ $successMessage }}
        </div>
    @endif

    <!-- مؤشر التحميل -->
    @if ($isLoading)
        <div class="alert alert-info">
            <div class="d-flex align-items-center">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                جارٍ التحميل...
            </div>
        </div>
    @endif

    <!-- الخطوة الأولى: اختيار الوحدة -->
    @if ($currentStep == 1)
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">الخطوة 1: اختيار الوحدة</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- اختيار الوحدة -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="module-select">اختر الوحدة</label>
                            <select wire:model.live="selectedModule" id="module-select" class="form-select">
                                <option value="">-- اختر الوحدة --</option>
                                @foreach ($modules as $module)
                                    @if (is_array($module))
                                        <option value="{{ $module['name'] }}">{{ $module['arabic_name'] }}</option>
                                    @else
                                        <option value="{{ $module }}">
                                            {{ ucfirst(str_replace('_', ' ', $module)) }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- معلومات الوحدة -->
                    @if ($selectedModule)
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <h6 class="card-title text-primary">معلومات الوحدة</h6>
                                    <p class="card-text">
                                        <strong>اسم الوحدة:</strong> {{ $selectedModule }}<br>
                                        <strong>الاسم العربي:</strong> {{ $selectedModule ? \App\Models\ReportGenerator\ReportGenerator::getModuleArabicName($selectedModule) : '' }}<br>
                                        <strong>عدد الحقول:</strong> {{ $moduleFields ? count($moduleFields) : 0 }}<br>
                                        <strong>الجدول:</strong>
                                        {{ $selectedModule ? \App\Models\ReportGenerator\ReportGenerator::getModuleTableName($selectedModule) : '' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- أزرار التنقل -->
                <div class="d-flex justify-content-end mt-4">
                    <button wire:click="nextStep" class="btn btn-primary" {{ !$selectedModule ? 'disabled' : '' }}>
                        التالي <i class="mdi mdi-arrow-left ms-1"></i>
                    </button>
                </div>

                <!-- التقارير المحفوظة -->
                @if (count($savedReports) > 0)
                    <div class="mt-4">
                        <h6>التقارير المحفوظة</h6>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>اسم التقرير</th>
                                        <th>الوحدة</th>
                                        <th>تاريخ الإنشاء</th>
                                        <th>المنشئ</th>
                                        <th>عام</th>
                                        <th>العمليات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($savedReports as $report)
                                        <tr>
                                            <td>{{ $report->title }}</td>
                                            <td>{{ \App\Models\ReportGenerator\ReportGenerator::getModuleArabicName($report->module_name) }}</td>
                                            <td>{{ $report->created_at->format('Y-m-d H:i') }}</td>
                                            <td>{{ $report->creator->name ?? 'غير محدد' }}</td>
                                            <td>
                                                @if ($report->is_public)
                                                    <span class="badge bg-success">نعم</span>
                                                @else
                                                    <span class="badge bg-secondary">لا</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button wire:click="runSavedReport({{ $report->id }})"
                                                        class="btn btn-sm btn-success" title="تشغيل التقرير مباشرة">
                                                        <i class="mdi mdi-play"></i>
                                                    </button>
                                                    <button wire:click="loadReport({{ $report->id }})"
                                                        class="btn btn-sm btn-primary" title="تحميل التقرير للتعديل">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </button>
                                                    @if ($report->created_by == auth()->id())
                                                        <button onclick="prepareDeleteReport({{ $report->id }})"
                                                            class="btn btn-sm btn-danger" title="حذف التقرير">
                                                            <i class="mdi mdi-delete"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif


            </div>
        </div>
    @endif

    <!-- الخطوة الثانية: تحديد الحقول -->
    @if ($currentStep == 2)
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">الخطوة 2: تحديد الحقول</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- الحقول المتاحة -->
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">الحقول المتاحة</h6>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                        wire:click="selectAllFields"
                                        title="تحديد جميع الحقول">
                                    <i class="mdi mdi-checkbox-multiple-marked-outline me-1"></i>
                                    تحديد الكل
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm"
                                        wire:click="selectBasicFields"
                                        title="تحديد الحقول الأساسية فقط">
                                    <i class="mdi mdi-checkbox-marked-circle-outline me-1"></i>
                                    الأساسية
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                        wire:click="deselectAllFields"
                                        title="إلغاء تحديد جميع الحقول">
                                    <i class="mdi mdi-checkbox-multiple-blank-outline me-1"></i>
                                    إلغاء الكل
                                </button>
                            </div>
                        </div>
                        <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                            @if ($moduleFields && count($moduleFields) > 0)
                                @foreach ($moduleFields as $field)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox"
                                            value="{{ is_object($field) ? $field->field_name : (isset($field['field_name']) ? $field['field_name'] : '') }}"
                                            wire:model.live="selectedColumns"
                                            id="field_{{ is_object($field) ? $field->field_name : (isset($field['field_name']) ? $field['field_name'] : '') }}">
                                        <label class="form-check-label"
                                            for="field_{{ is_object($field) ? $field->field_name : (isset($field['field_name']) ? $field['field_name'] : '') }}">
                                            <strong>{{ is_object($field) ? $field->arabic_name : (isset($field['arabic_name']) ? $field['arabic_name'] : 'غير محدد') }}</strong>
                                            <small class="text-muted d-block">
                                                {{ is_object($field) ? $field->field_name : (isset($field['field_name']) ? $field['field_name'] : '') }}
                                                ({{ is_object($field) ? $field->field_type : (isset($field['field_type']) ? $field['field_type'] : '') }})
                                            </small>
                                        </label>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center text-muted">
                                    <i class="mdi mdi-database-search display-4"></i>
                                    <p>لا توجد حقول متاحة لهذه الوحدة</p>
                                </div>
                            @endif
                        </div>

                        @if ($moduleFields && count($moduleFields) > 0)
                            <!-- مؤشر التقدم -->
                            <div class="mt-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">تقدم التحديد</small>
                                    <small class="text-muted">
                                        {{ count($selectedColumns) }}/{{ count($moduleFields) }}
                                        ({{ count($moduleFields) > 0 ? round((count($selectedColumns) / count($moduleFields)) * 100, 1) : 0 }}%)
                                    </small>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-primary" role="progressbar"
                                         style="width: {{ count($moduleFields) > 0 ? (count($selectedColumns) / count($moduleFields)) * 100 : 0 }}%"></div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- الحقول المحددة -->
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">الحقول المحددة</h6>
                            <span class="badge bg-info">
                                {{ count($selectedColumns) }} من {{ $moduleFields ? count($moduleFields) : 0 }}
                            </span>
                        </div>
                        <div class="border rounded p-3" style="min-height: 280px;">
                            @if (count($selectedColumns) > 0)
                                <div class="row g-2">
                                    @foreach ($selectedColumns as $index => $column)
                                        @php
                                            $field = $moduleFields ? $moduleFields->where('field_name', $column)->first() : null;
                                            $fieldName = $field ? (is_object($field) ? $field->arabic_name : (isset($field['arabic_name']) ? $field['arabic_name'] : $column)) : $column;
                                            $fieldType = $field ? (is_object($field) ? $field->field_type : (isset($field['field_type']) ? $field['field_type'] : '')) : '';
                                        @endphp
                                        <div class="col-12">
                                            <div class="card card-body p-2 mb-1">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="flex-grow-1">
                                                        <strong class="text-primary">{{ $fieldName }}</strong>
                                                        <small class="text-muted d-block">{{ $column }} ({{ $fieldType }})</small>
                                                    </div>
                                                    <div class="btn-group btn-group-sm">
                                                        @if ($index > 0)
                                                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                                                    wire:click="moveFieldUp({{ $index }})"
                                                                    title="تحريك للأعلى">
                                                                <i class="mdi mdi-arrow-up"></i>
                                                            </button>
                                                        @endif
                                                        @if ($index < count($selectedColumns) - 1)
                                                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                                                    wire:click="moveFieldDown({{ $index }})"
                                                                    title="تحريك للأسفل">
                                                                <i class="mdi mdi-arrow-down"></i>
                                                            </button>
                                                        @endif
                                                        <button type="button" class="btn btn-outline-danger btn-sm"
                                                                wire:click="removeField('{{ $column }}')"
                                                                title="حذف الحقل">
                                                            <i class="mdi mdi-close"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center text-muted">
                                    <i class="mdi mdi-checkbox-blank-outline display-4"></i>
                                    <p>لم يتم تحديد أي حقول بعد</p>
                                    <small>اختر الحقول من القائمة اليسرى أو استخدم أزرار التحديد السريع</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- أزرار التنقل -->
                <div class="d-flex justify-content-between mt-4">
                    <button wire:click="previousStep" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-right me-1"></i> السابق
                    </button>
                    <button wire:click="nextStep" class="btn btn-primary"
                        {{ count($selectedColumns) == 0 ? 'disabled' : '' }}>
                        التالي <i class="mdi mdi-arrow-left ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- الخطوة الثالثة: الفلاتر والترتيب -->
    @if ($currentStep == 3)
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">الخطوة 3: الفلاتر والترتيب</h5>
            </div>
            <div class="card-body">
                <!-- الفلاتر -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6>فلاتر البيانات</h6>
                        <button wire:click="addFilterColumn" class="btn btn-sm btn-outline-primary">
                            <i class="mdi mdi-plus me-1"></i>إضافة فلتر
                        </button>
                    </div>

                    @foreach ($filterColumns as $index => $filter)
                        <div class="filter-group">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">الحقل</label>
                                    <select wire:model.live="filterColumns.{{ $index }}.column"
                                        class="form-select">
                                        <option value="">-- اختر الحقل --</option>
                                        @foreach ($selectedColumns as $column)
                                            @php
                                                $field = $moduleFields ? $moduleFields->where('field_name', $column)->first() : null;
                                            @endphp
                                            <option value="{{ $column }}">
                                                {{ $field ? (is_object($field) ? $field->arabic_name : (isset($field['arabic_name']) ? $field['arabic_name'] : $column)) : $column }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">المشغل</label>
                                    <select wire:model.live="filterColumns.{{ $index }}.operator"
                                        class="form-select">
                                        <option value="=">=</option>
                                        <option value="!=">!=</option>
                                        <option value=">">></option>
                                        <option value="<"><</option>
                                        <option value=">=">>=</option>
                                        <option value="<="><=</option>
                                        <option value="LIKE">يحتوي على</option>
                                        <option value="NOT LIKE">لا يحتوي على</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">القيمة</label>
                                    <input type="text" wire:model.live="filterColumns.{{ $index }}.value"
                                        class="form-control">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label">&nbsp;</label>
                                    <button wire:click="removeFilterColumn({{ $index }})"
                                        class="btn btn-danger btn-sm d-block">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- إعدادات الترتيب -->
                <div class="mb-4">
                    <h6>إعدادات الترتيب</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">ترتيب حسب</label>
                            <select wire:model.live="sortColumn" class="form-select">
                                <option value="">-- اختر الحقل --</option>
                                @foreach ($selectedColumns as $column)
                                    @php
                                        $field = $moduleFields ? $moduleFields->where('field_name', $column)->first() : null;
                                    @endphp
                                    <option value="{{ $column }}">
                                        {{ $field ? (is_object($field) ? $field->arabic_name : (isset($field['arabic_name']) ? $field['arabic_name'] : $column)) : $column }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">اتجاه الترتيب</label>
                            <select wire:model.live="sortDirection" class="form-select">
                                <option value="asc">تصاعدي</option>
                                <option value="desc">تنازلي</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- إعدادات المخططات -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6>إعدادات المخططات</h6>
                        <div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" wire:model.live="enableCharts"
                                    id="enableCharts">
                                <label class="form-check-label" for="enableCharts">تفعيل المخططات</label>
                            </div>
                        </div>
                    </div>

                    @if ($enableCharts)
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span>مخططات البيانات</span>
                            <button wire:click="addChart" class="btn btn-sm btn-outline-success">
                                <i class="mdi mdi-plus me-1"></i>إضافة مخطط
                            </button>
                        </div>

                        @foreach ($chartSettings as $index => $chart)
                            <div class="filter-group border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">مخطط {{ $index + 1 }}</h6>
                                    <button wire:click="removeChart({{ $index }})"
                                        class="btn btn-danger btn-sm">
                                        <i class="mdi mdi-delete"></i> حذف المخطط
                                    </button>
                                </div>

                                <!-- إعدادات المخطط الأساسية -->
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label class="form-label">نوع المخطط</label>
                                        <select wire:model.live="chartSettings.{{ $index }}.type"
                                            class="form-select"
                                            onchange="showChartTypeInfo({{ $index }}, this.value)">
                                            <option value="bar">أعمدة</option>
                                            <option value="line">خطي</option>
                                            <option value="pie">دائري</option>
                                            <option value="doughnut">كعكة</option>
                                        </select>
                                        <small class="text-muted mt-1">
                                            <span id="chart-type-info-{{ $index }}">
                                                @if(isset($chart['type']))
                                                    @if($chart['type'] == 'pie' || $chart['type'] == 'doughnut')
                                                        📊 المخططات الدائرية تعرض مجموع كل حقل كقطعة منفصلة
                                                    @else
                                                        📈 المخططات الخطية والأعمدة تعرض قيم كل سجل منفصل
                                                    @endif
                                                @endif
                                            </span>
                                        </small>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">عنوان المخطط</label>
                                        <input type="text"
                                            wire:model.live="chartSettings.{{ $index }}.title"
                                            class="form-control" placeholder="اكتب عنوان المخطط">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">حقل المحور X (اختياري)</label>
                                        <select wire:model.live="chartSettings.{{ $index }}.xAxisField" class="form-select">
                                            <option value="">-- ترقيم تلقائي --</option>
                                            @foreach ($selectedColumns as $column)
                                                @php
                                                    $field = $moduleFields ? collect($moduleFields)->where('field_name', $column)->first() : null;
                                                    $arabicName = '';
                                                    if ($field) {
                                                        $arabicName = is_object($field) ? $field->arabic_name ?? $column : ($field['arabic_name'] ?? $column);
                                                    } else {
                                                        $arabicName = $column;
                                                    }
                                                @endphp
                                                <option value="{{ $column }}">{{ $arabicName }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">مثل: الشهر، السنة، التاريخ</small>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="row">
                                            <div class="col-6">
                                                <label class="form-label">إظهار الدليل</label>
                                                <div class="form-check form-switch mt-2">
                                                    <input class="form-check-input" type="checkbox"
                                                        wire:model.live="chartSettings.{{ $index }}.showLegend"
                                                        id="showLegend{{ $index }}">
                                                    <label class="form-check-label" for="showLegend{{ $index }}"></label>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">ارتفاع المخطط</label>
                                                <input type="number" min="200" max="600" step="50"
                                                    wire:model.live="chartSettings.{{ $index }}.chartHeight"
                                                    class="form-control" value="300">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- الحقول المضافة للمخطط -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label mb-0">الحقول الرقمية للمقارنة</label>
                                        <button wire:click="addColumnToChart({{ $index }})"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="mdi mdi-plus me-1"></i>إضافة حقل
                                        </button>
                                    </div>

                                    @if (isset($chart['columns']) && count($chart['columns']) > 0)
                                        @foreach ($chart['columns'] as $colIndex => $columnData)
                                            <div class="row mb-2 align-items-end border-bottom pb-2">
                                                <div class="col-md-5">
                                                    <label class="form-label small">الحقل الرقمي</label>
                                                    <select wire:model.live="chartSettings.{{ $index }}.columns.{{ $colIndex }}.field"
                                                        class="form-select form-select-sm">
                                                        <option value="">-- اختر الحقل --</option>
                                                        @if ($numericColumns && count($numericColumns) > 0)
                                                            @foreach ($numericColumns as $column)
                                                                @php
                                                                    $field = collect($moduleFields)
                                                                        ->where('field_name', $column)
                                                                        ->first();
                                                                    $arabicName = '';
                                                                    if ($field) {
                                                                        $arabicName = is_object($field)
                                                                            ? $field->arabic_name ?? $column
                                                                            : ($field['arabic_name'] ?? $column);
                                                                    } else {
                                                                        $arabicName = $column;
                                                                    }
                                                                @endphp
                                                                <option value="{{ $column }}">{{ $arabicName }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small">تسمية الحقل في المخطط</label>
                                                    <input type="text"
                                                        wire:model.live="chartSettings.{{ $index }}.columns.{{ $colIndex }}.label"
                                                        class="form-control form-control-sm"
                                                        placeholder="تسمية الحقل">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">اللون</label>
                                                    <input type="color"
                                                        wire:model.live="chartSettings.{{ $index }}.columns.{{ $colIndex }}.color"
                                                        class="form-control form-control-sm">
                                                </div>
                                                <div class="col-md-1">
                                                    <button wire:click="removeColumnFromChart({{ $index }}, {{ $colIndex }})"
                                                        class="btn btn-outline-danger btn-sm">
                                                        <i class="mdi mdi-close"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="text-center text-muted py-3">
                                            <i class="mdi mdi-chart-line display-6"></i>
                                            <p class="mb-0">لا توجد حقول مضافة للمخطط بعد</p>
                                            <small>اضغط على "إضافة حقل" لبدء المقارنة</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <!-- أزرار التنقل -->
                <div class="d-flex justify-content-between mt-4">
                    <button wire:click="previousStep" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-right me-1"></i> السابق
                    </button>
                    <button wire:click="nextStep" class="btn btn-primary">
                        التالي <i class="mdi mdi-arrow-left ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- الخطوة الرابعة: النتائج والمخططات -->
    @if ($currentStep == 4)
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">الخطوة 4: النتائج والمخططات</h5>
                    <div class="btn-group-actions">
                        @if (count($reportData) == 0)
                            <button wire:click="runReport" class="btn btn-success">
                                <i class="mdi mdi-play me-1"></i>تشغيل التقرير
                            </button>
                        @else
                            <!-- أزرار ما بعد تشغيل التقرير -->
                            <button wire:click="runReport" class="btn btn-outline-success me-2">
                                <i class="mdi mdi-refresh me-1"></i>تشغيل مرة أخرى
                            </button>
                            <button wire:click="exportReport" class="btn btn-info me-2">
                                <i class="mdi mdi-file-excel me-1"></i>تصدير Excel
                            </button>
                            <button wire:click="exportPdf" class="btn btn-danger me-2">
                                <i class="mdi mdi-file-pdf me-1"></i>تصدير PDF
                            </button>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- نموذج حفظ التقرير -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">اسم التقرير</label>
                        <input type="text" wire:model.live="reportTitle" class="form-control"
                            placeholder="اكتب اسم التقرير">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الوصف</label>
                        <input type="text" wire:model.live="description" class="form-control"
                            placeholder="وصف مختصر للتقرير">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" wire:model.live="isPublic"
                                id="isPublic">
                            <label class="form-check-label" for="isPublic">تقرير عام</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button wire:click="saveReport" class="btn btn-primary d-block w-100">
                            <i class="mdi mdi-content-save me-1"></i>حفظ
                        </button>
                    </div>
                </div>

                <!-- عرض البيانات -->
                @if (isset($reportData) && count($reportData) > 0)
                    <div class="mb-4">
                        <h6>نتائج التقرير ({{ count($reportData) }} صف)</h6>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        @foreach ($selectedColumns as $column)
                                            @php
                                                $field = $moduleFields ? collect($moduleFields)->where('field_name', $column)->first() : null;
                                            @endphp
                                            <th>{{ $field ? (is_object($field) ? $field->arabic_name : (isset($field['arabic_name']) ? $field['arabic_name'] : $column)) : $column }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($reportData as $row)
                                        <tr>
                                            @foreach ($selectedColumns as $column)
                                                <td>
                                                    @if (is_array($row))
                                                        {{ $row[$column] ?? '' }}
                                                    @elseif(is_object($row))
                                                        {{ $row->$column ?? '' }}
                                                    @else
                                                        {{ $row }}
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <!-- Debug Info عندما لا توجد بيانات -->
                    <div class="alert alert-info">
                        <h6>معلومات التشخيص:</h6>
                        <p><strong>عدد صفوف البيانات:</strong>
                            {{ isset($reportData) ? count($reportData) : 'غير محدد' }}</p>
                        <p><strong>الوحدة المحددة:</strong> {{ $selectedModule ?? 'غير محددة' }}</p>
                        <p><strong>الأعمدة المحددة:</strong>
                            {{ isset($selectedColumns) ? (is_array($selectedColumns) ? implode(', ', $selectedColumns) : json_encode($selectedColumns)) : 'غير محددة' }}
                        </p>
                        <p><strong>الخطوة الحالية:</strong> {{ $currentStep ?? 'غير محددة' }}</p>
                        @if (isset($reportData) && is_array($reportData))
                            <p><strong>نوع البيانات:</strong> Array</p>
                        @elseif(isset($reportData))
                            <p><strong>نوع البيانات:</strong> {{ gettype($reportData) }}</p>
                        @else
                            <p><strong>نوع البيانات:</strong> غير محدد</p>
                        @endif
                    </div>
                @endif

                <!-- عرض المخططات -->
                @if ($enableCharts && count($chartSettings) > 0)
                    <div class="mb-4">
                        <h6>المخططات البيانية للمقارنة</h6>
                        <div class="row">
                            @foreach ($chartSettings as $index => $chart)
                                @if (isset($chart['columns']) && count($chart['columns']) > 0)
                                    @php
                                        $hasValidColumns = false;
                                        foreach ($chart['columns'] as $col) {
                                            if (!empty($col['field'])) {
                                                $hasValidColumns = true;
                                                break;
                                            }
                                        }
                                    @endphp

                                    @if ($hasValidColumns)
                                        <div class="col-md-6 mb-4">
                                            <div class="card card-chart">
                                                <div class="card-header">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h6 class="card-title mb-0">
                                                            {{ $chart['title'] ?: 'مخطط ' . ($index + 1) }}
                                                        </h6>
                                                        <div class="btn-group btn-group-sm">
                                                            <button
                                                                onclick="downloadChart('chart_{{ $index }}', '{{ $chart['title'] ?: 'مخطط ' . ($index + 1) }}')"
                                                                class="btn btn-outline-primary">
                                                                <i class="mdi mdi-download"></i>
                                                            </button>
                                                            <button
                                                                onclick="toggleChartFullscreen('chart_container_{{ $index }}')"
                                                                class="btn btn-outline-secondary">
                                                                <i class="mdi mdi-fullscreen"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <!-- عرض أسماء الحقول المتضمنة في المخطط -->
                                                    <div class="mt-2">
                                                        <small class="text-muted">
                                                            <strong>الحقول المقارنة:</strong>
                                                            @foreach ($chart['columns'] as $colData)
                                                                @if (!empty($colData['field']))
                                                                    <span class="badge bg-light text-dark me-1">
                                                                        {{ $colData['label'] ?: $colData['field'] }}
                                                                    </span>
                                                                @endif
                                                            @endforeach
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="card-body" id="chart_container_{{ $index }}">
                                                    <div class="chart-container" style="height: {{ $chart['chartHeight'] ?? 300 }}px;">
                                                        <canvas id="chart_{{ $index }}"
                                                                data-chart-type="{{ $chart['type'] }}"
                                                                data-show-legend="{{ $chart['showLegend'] ?? true }}"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                <div class="chart-preview">
                    <i class="mdi mdi-chart-line display-4"></i>
                    <p>اضغط على "تشغيل التقرير" لعرض النتائج والمخططات</p>
                </div>
    @endif

    <!-- أزرار التنقل -->
    <div class="d-flex justify-content-between mt-4">
        <button wire:click="previousStep" class="btn btn-secondary">
            <i class="mdi mdi-arrow-right me-1"></i> السابق
        </button>
        <button wire:click="resetForm" class="btn btn-outline-primary">
            <i class="mdi mdi-refresh me-1"></i> تقرير جديد
        </button>
    </div>
</div>

<!-- Include Remove Report Modal -->
@include('livewire.report-generator.modals.remove-report')

<!-- تحميل تنسيقات المخططات المتقدمة -->
<link rel="stylesheet" href="{{ asset('css/advanced-charts.css') }}">

<!-- تحميل مكتبات المخططات -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('js/advanced-charts.js') }}"></script>

<script>
    // دالة تحضير حذف التقرير
    function prepareDeleteReport(reportId) {
        console.log('prepareDeleteReport called with ID:', reportId);

        // حفظ ID التقرير في متغير عام
        window.reportToDeleteId = reportId;

        // استدعاء دالة Livewire لتحديد التقرير المراد حذفه
        Livewire.emit('getReportForDeletion', reportId);

        // فتح المودال بعد تحديد التقرير
        setTimeout(() => {
            const modal = new bootstrap.Modal(document.getElementById('removeReportModal'));
            modal.show();
        }, 100);
    }

    // دالة تأكيد حذف التقرير مباشرة
    function confirmDeleteReportDirect() {
        console.log('confirmDeleteReportDirect called with ID:', window.reportToDeleteId);

        if (!window.reportToDeleteId) {
            alert('لم يتم تحديد التقرير المراد حذفه');
            return;
        }

        // استدعاء دالة Livewire مباشرة
        @this.call('confirmDeleteReport');
    }

    // دالة لإظهار معلومات نوع المخطط
    function showChartTypeInfo(chartIndex, chartType) {
        const infoElement = document.getElementById('chart-type-info-' + chartIndex);
        if (infoElement) {
            let message = '';
            switch(chartType) {
                case 'pie':
                case 'doughnut':
                    message = '📊 المخططات الدائرية تعرض مجموع كل حقل كقطعة منفصلة';
                    break;
                case 'bar':
                case 'line':
                    message = '📈 المخططات الخطية والأعمدة تعرض قيم كل سجل منفصل';
                    break;
                default:
                    message = '📊 اختر نوع المخطط المناسب لبياناتك';
            }
            infoElement.textContent = message;
        }
    }    // تسجيل أحداث Livewire
    document.addEventListener('livewire:load', function () {
        console.log('Livewire loaded');

        // إضافة event listener لتأكيد الحذف
        Livewire.on('confirmDeleteReport', function(data) {
            console.log('confirmDeleteReport event received:', data);
        });
    });

    // إغلاق المودال عند استقبال الحدث
    window.addEventListener('hide-modal', event => {
        console.log('hide-modal event received:', event.detail);
        const modal = document.getElementById(event.detail.modalId);
        if (modal) {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        }
    });

    // تسجيل الأحداث عند النقر على الأزرار
    document.addEventListener('click', function(e) {
        if (e.target.closest('[wire\\:click*="getReportForDeletion"]')) {
            console.log('getReportForDeletion button clicked');
        }
        if (e.target.closest('[wire\\:click*="confirmDeleteReport"]')) {
            console.log('confirmDeleteReport button clicked');
        }
    });
</script>
