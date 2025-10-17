<div class="row">
    <!-- إعدادات الفلترة والبحث -->
    <div class="col-md-6">
        <div class="card border-warning">
            <div class="card-header bg-light">
                <h6 class="mb-0 text-warning">
                    <i class="mdi mdi-filter me-2"></i>
                    إعدادات الفلترة والبحث
                </h6>
            </div>
            <div class="card-body">
                @if($widgetType === 'table')
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" wire:model="tableSearchable" id="tableSearchable">
                        <label class="form-check-label" for="tableSearchable">
                            <strong>تفعيل البحث في الجدول</strong>
                        </label>
                        <small class="d-block text-muted">إضافة مربع بحث فوق الجدول</small>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" wire:model="tableWithFilters" id="tableWithFilters">
                        <label class="form-check-label" for="tableWithFilters">
                            <strong>إضافة فلاتر متقدمة</strong>
                        </label>
                        <small class="d-block text-muted">فلاتر للتاريخ والحالة والفئات</small>
                    </div>
                @endif

                @if($widgetType === 'chart')
                    <div class="form-group mb-3">
                        <label class="form-label fw-semibold">حد البيانات المعروضة</label>
                        <select class="form-select">
                            <option value="">جميع البيانات</option>
                            <option value="10">أفضل 10</option>
                            <option value="20">أفضل 20</option>
                            <option value="50">أفضل 50</option>
                        </select>
                        <small class="text-muted">لتحسين الأداء وسهولة القراءة</small>
                    </div>
                @endif

                @if($widgetType === 'stat')
                    <div class="form-group mb-3">
                        <label class="form-label fw-semibold">فترة الإحصائية</label>
                        <select class="form-select" wire:model="statPeriod">
                            <option value="">جميع البيانات</option>
                            <option value="today">اليوم</option>
                            <option value="week">هذا الأسبوع</option>
                            <option value="month">هذا الشهر</option>
                            <option value="year">هذا العام</option>
                        </select>
                        <small class="text-muted">تحديد الفترة الزمنية للإحصائية</small>
                    </div>

                    <!-- فلترة حسب حقل آخر -->
                    <div class="border-top pt-3 mt-3">
                        <label class="form-label fw-semibold">
                            <i class="mdi mdi-filter-variant me-1"></i>
                            فلترة حسب حقل آخر
                        </label>
                        
                        <div class="form-group mb-2">
                            <label class="form-label text-muted small">اختر الحقل للفلترة</label>
                            <select class="form-select form-select-sm" wire:model="statFilterColumn">
                                <option value="">-- بدون فلترة --</option>
                                @if(is_array($availableColumns))
                                    @foreach($availableColumns as $column)
                                        <option value="{{ $column['name'] }}">{{ $column['label'] }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        @if($statFilterColumn)
                            <div class="form-group mb-2">
                                <label class="form-label text-muted small">نوع المقارنة</label>
                                <select class="form-select form-select-sm" wire:model="statFilterOperator">
                                    <option value="=">يساوي (=)</option>
                                    <option value="!=">لا يساوي (!=)</option>
                                    <option value=">">أكبر من (>)</option>
                                    <option value="<">أصغر من (<)</option>
                                    <option value=">=">أكبر من أو يساوي (>=)</option>
                                    <option value="<=">أصغر من أو يساوي (<=)</option>
                                    <option value="LIKE">يحتوي على (LIKE)</option>
                                    <option value="NOT LIKE">لا يحتوي على (NOT LIKE)</option>
                                    <option value="IS NULL">فارغ (NULL)</option>
                                    <option value="IS NOT NULL">غير فارغ (NOT NULL)</option>
                                </select>
                            </div>

                            @if(!in_array($statFilterOperator, ['IS NULL', 'IS NOT NULL']))
                                <div class="form-group mb-2">
                                    <label class="form-label text-muted small">القيمة</label>
                                    <input type="text" class="form-control form-control-sm" wire:model="statFilterValue" placeholder="أدخل القيمة للمقارنة">
                                </div>
                            @endif

                            @if($statFilterColumn && $statFilterOperator && ($statFilterValue || in_array($statFilterOperator, ['IS NULL', 'IS NOT NULL'])))
                                <div class="alert alert-success alert-sm py-2 mt-2">
                                    <i class="mdi mdi-check-circle me-1"></i>
                                    <small>
                                        <strong>الفلتر المطبق:</strong>
                                        {{ collect($availableColumns)->firstWhere('name', $statFilterColumn)['label'] ?? $statFilterColumn }}
                                        {{ $statFilterOperator }}
                                        @if(!in_array($statFilterOperator, ['IS NULL', 'IS NOT NULL']))
                                            "{{ $statFilterValue }}"
                                        @endif
                                    </small>
                                </div>
                            @endif
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- إعدادات التحديث والأداء -->
    <div class="col-md-6">
        <div class="card border-info">
            <div class="card-header bg-light">
                <h6 class="mb-0 text-info">
                    <i class="mdi mdi-refresh me-2"></i>
                    إعدادات التحديث والأداء
                </h6>
            </div>
            <div class="card-body">
                <div class="form-group mb-3">
                    <label class="form-label fw-semibold">تحديث البيانات</label>
                    @if($widgetType === 'chart')
                        <!-- للمخططات: فقط يدوي أو فترات طويلة -->
                        <select class="form-select" wire:model="refreshInterval">
                            <option value="manual">يدوي عند إعادة تحميل الصفحة (مُوصى به)</option>
                            <option value="300">كل 5 دقائق</option>
                            <option value="900">كل 15 دقيقة</option>
                            <option value="1800">كل 30 دقيقة</option>
                        </select>
                        <div class="alert alert-info mt-2 py-2">
                            <i class="mdi mdi-information me-1"></i>
                            <small><strong>ملاحظة:</strong> المخططات البيانية تعمل بشكل أفضل مع التحديث اليدوي أو الفترات الطويلة لتجنب مشاكل الأداء.</small>
                        </div>
                    @else
                        <!-- للجداول والإحصائيات: جميع الخيارات متاحة -->
                        <select class="form-select" wire:model="refreshInterval">
                            <option value="manual">يدوي عند إعادة تحميل الصفحة</option>
                            <option value="30">كل 30 ثانية</option>
                            <option value="60">كل دقيقة</option>
                            <option value="300">كل 5 دقائق</option>
                            <option value="900">كل 15 دقيقة</option>
                        </select>
                    @endif
                    <small class="text-muted">تكرار تحديث البيانات تلقائياً</small>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label fw-semibold">التخزين المؤقت</label>
                    <select class="form-select" wire:model="cacheTime">
                        <option value="0">بدون تخزين مؤقت</option>
                        <option value="300">5 دقائق</option>
                        <option value="900">15 دقيقة</option>
                        <option value="3600">ساعة واحدة</option>
                    </select>
                    <small class="text-muted">مدة حفظ البيانات في التخزين المؤقت</small>
                </div>

                @if($widgetType === 'table')
                    <div class="form-group mb-3">
                        <label class="form-label fw-semibold">التصفح التفاعلي</label>
                        <select class="form-select" wire:model="paginationType">
                            <option value="none">بدون تصفح</option>
                            <option value="simple">تصفح بسيط (التالي/السابق)</option>
                            <option value="full">تصفح كامل مع أرقام الصفحات</option>
                        </select>
                        <small class="text-muted">نوع نظام التصفح للجدول</small>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- إعدادات التصميم والعرض -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-secondary">
            <div class="card-header bg-light">
                <h6 class="mb-0 text-secondary">
                    <i class="mdi mdi-palette me-2"></i>
                    إعدادات التصميم والعرض
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">عرض العنصر</label>
                        <select class="form-select" wire:model="widgetWidth">
                            <option value="full">عرض كامل (12 عمود)</option>
                            <option value="half">نصف عرض (6 أعمدة)</option>
                            <option value="third">ثلث عرض (4 أعمدة)</option>
                            <option value="quarter">ربع عرض (3 أعمدة)</option>
                        </select>
                        <small class="text-muted">عرض العنصر في الداشبورد</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">ارتفاع العنصر</label>
                        <select class="form-select" wire:model="widgetHeight">
                            <option value="auto">تلقائي</option>
                            <option value="small">صغير (200px)</option>
                            <option value="medium">متوسط (300px)</option>
                            <option value="large">كبير (400px)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">إطار العنصر</label>
                        <select class="form-select" wire:model="widgetBorder">
                            <option value="card">بطاقة مع إطار</option>
                            <option value="shadow">بطاقة مع ظل</option>
                            <option value="borderless">بدون إطار</option>
                            <option value="minimal">تصميم مبسط</option>
                        </select>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model="showHeader" id="showHeader">
                            <label class="form-check-label" for="showHeader">
                                <strong>عرض رأس العنصر</strong>
                            </label>
                            <small class="d-block text-muted">إظهار العنوان والأيقونة في الأعلى</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model="showBorder" id="showBorder">
                            <label class="form-check-label" for="showBorder">
                                <strong>إظهار الحدود</strong>
                            </label>
                            <small class="d-block text-muted">إطار حول العنصر</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- إعدادات الصلاحيات -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-danger">
            <div class="card-header bg-light">
                <h6 class="mb-0 text-danger">
                    <i class="mdi mdi-shield-account me-2"></i>
                    إعدادات الصلاحيات والأمان
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">مستوى الرؤية</label>
                        <select class="form-select" wire:model="visibilityLevel">
                            <option value="public">عام - جميع المستخدمين</option>
                            <option value="authenticated">المستخدمين المسجلين فقط</option>
                            <option value="admin">الإداريين فقط</option>
                            <option value="owner">المالك فقط</option>
                        </select>
                        <small class="text-muted">من يمكنه رؤية هذا العنصر</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">صلاحية التصدير</label>
                        <select class="form-select" wire:model="exportPermission">
                            <option value="none">لا يمكن التصدير</option>
                            <option value="excel">تصدير Excel فقط</option>
                            <option value="pdf">تصدير PDF فقط</option>
                            <option value="both">Excel و PDF</option>
                        </select>
                        <small class="text-muted">إمكانيات التصدير المتاحة</small>
                    </div>
                </div>

                <div class="alert alert-info mt-3">
                    <i class="mdi mdi-information me-2"></i>
                    <strong>ملاحظة:</strong> إعدادات الصلاحيات ستطبق عند عرض الداشبورد للمستخدمين.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- معاينة إعدادات التصميم -->
@if($widgetType === 'stat')
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-success">
            <div class="card-header bg-light">
                <h6 class="mb-0 text-success">
                    <i class="mdi mdi-eye me-2"></i>
                    معاينة حية للعنصر
                </h6>
            </div>
            <div class="card-body">
                @php
                    // محاكاة عرض العنصر
                    $previewWidthClass = match($widgetWidth) {
                        'full' => 'col-12',
                        'half' => 'col-6',
                        'third' => 'col-4',
                        'quarter' => 'col-3',
                        default => 'col-3'
                    };

                    $previewHeightStyle = match($widgetHeight) {
                        'small' => 'min-height: 150px;',
                        'medium' => 'min-height: 200px;',
                        'large' => 'min-height: 250px;',
                        default => ''
                    };

                    $previewBorderClass = match($widgetBorder) {
                        'shadow' => 'card shadow-lg border-0',
                        'borderless' => 'card border-0',
                        'minimal' => 'card border-0 bg-light',
                        default => 'card border shadow-sm'
                    };

                    if (!$showBorder) {
                        $previewBorderClass .= ' border-0';
                    }

                    // تحديد لون الويدجت في المعاينة
                    $isCustomColor = $statColor === 'custom' || str_starts_with($statColor, '#');
                    $colorClass = $isCustomColor ? '' : $statColor;
                    $colorStyle = $isCustomColor ? $customColor : '';
                @endphp

                <div class="row">
                    <div class="{{ $previewWidthClass }}">
                        <div class="{{ $previewBorderClass }}" style="{{ $previewHeightStyle }}">
                            @if($showHeader)
                                <div class="card-header bg-transparent border-0 pb-0">
                                    <h6 class="card-title mb-0 @if($isCustomColor) text-dark @else text-{{ $colorClass }} @endif" @if($isCustomColor) style="color: {{ $colorStyle }} !important;" @endif>
                                        <i class="mdi {{ $statIcon }} me-2"></i>
                                        {{ $widgetTitle ?: 'عنوان العنصر' }}
                                    </h6>
                                    @if($statLabel && $statLabel !== $widgetTitle)
                                        <small class="text-muted">{{ $statLabel }}</small>
                                    @endif
                                </div>
                            @endif
                            <div class="card-body {{ $showHeader ? 'pt-2' : '' }}">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="avatar avatar-md">
                                            <div class="avatar-initial @if($isCustomColor) text-white @else bg-{{ $colorClass }} @endif rounded" @if($isCustomColor) style="background-color: {{ $colorStyle }} !important;" @endif>
                                                <i class="mdi {{ $statIcon }} mdi-24px"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="mt-2">
                                            <h4 class="mb-0 @if($isCustomColor) text-dark @else text-{{ $colorClass }} @endif" @if($isCustomColor) style="color: {{ $colorStyle }} !important;" @endif>
                                                1,234
                                            </h4>
                                            @if(!$showHeader)
                                                <small class="text-muted">{{ $widgetTitle ?: 'عنوان العنصر' }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                @if($refreshInterval !== 'manual' && $refreshInterval !== '0')
                                    <small class="text-muted d-block mt-2">
                                        <i class="mdi mdi-refresh me-1"></i>
                                        يتم التحديث كل {{ $refreshInterval }} ثانية
                                    </small>
                                @endif

                                @if($statPeriod)
                                    <small class="text-info d-block mt-1">
                                        <i class="mdi mdi-calendar me-1"></i>
                                        فترة الإحصائية:
                                        @switch($statPeriod)
                                            @case('today') اليوم @break
                                            @case('week') هذا الأسبوع @break
                                            @case('month') هذا الشهر @break
                                            @case('year') هذا العام @break
                                            @default جميع البيانات
                                        @endswitch
                                    </small>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-9">
                        <div class="alert alert-info">
                            <h6>الإعدادات المطبقة:</h6>
                            <ul class="mb-0">
                                <li><strong>العرض:</strong> {{
                                    match($widgetWidth) {
                                        'full' => 'عرض كامل (12 عمود)',
                                        'half' => 'نصف عرض (6 أعمدة)',
                                        'third' => 'ثلث عرض (4 أعمدة)',
                                        'quarter' => 'ربع عرض (3 أعمدة)',
                                        default => 'ربع عرض'
                                    }
                                }}</li>
                                <li><strong>الارتفاع:</strong> {{
                                    match($widgetHeight) {
                                        'small' => 'صغير (200px)',
                                        'medium' => 'متوسط (300px)',
                                        'large' => 'كبير (400px)',
                                        default => 'تلقائي'
                                    }
                                }}</li>
                                <li><strong>الإطار:</strong> {{
                                    match($widgetBorder) {
                                        'shadow' => 'بطاقة مع ظل',
                                        'borderless' => 'بدون إطار',
                                        'minimal' => 'تصميم مبسط',
                                        default => 'بطاقة مع إطار'
                                    }
                                }}</li>
                                <li><strong>رأس العنصر:</strong> {{ $showHeader ? 'مفعل' : 'معطل' }}</li>
                                <li><strong>الحدود:</strong> {{ $showBorder ? 'مفعلة' : 'معطلة' }}</li>
                                @if($refreshInterval !== 'manual')
                                    <li><strong>التحديث التلقائي:</strong> كل {{ $refreshInterval }} ثانية</li>
                                @endif
                                @if($cacheTime !== '0')
                                    <li><strong>التخزين المؤقت:</strong> {{ $cacheTime }} ثانية</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- معاينة الجدول -->
@if($widgetType === 'table' && count($tableColumns) > 0)
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-success">
            <div class="card-header bg-light">
                <h6 class="mb-0 text-success">
                    <i class="mdi mdi-table-eye me-2"></i>
                    معاينة حية للجدول
                </h6>
            </div>
            <div class="card-body">
                @php
                    // محاكاة عرض الجدول
                    $previewWidthClass = match($widgetWidth) {
                        'full' => 'col-12',
                        'half' => 'col-6',
                        'third' => 'col-4',
                        'quarter' => 'col-3',
                        default => 'col-12'
                    };

                    $previewHeightStyle = match($widgetHeight) {
                        'small' => 'max-height: 200px; overflow-y: auto;',
                        'medium' => 'max-height: 300px; overflow-y: auto;',
                        'large' => 'max-height: 400px; overflow-y: auto;',
                        default => ''
                    };

                    $previewBorderClass = match($widgetBorder) {
                        'shadow' => 'card shadow-lg border-0',
                        'borderless' => 'card border-0',
                        'minimal' => 'card border-0 bg-light',
                        default => 'card border shadow-sm'
                    };

                    if (!$showBorder) {
                        $previewBorderClass .= ' border-0';
                    }

                    // إعدادات الجدول
                    $tableClasses = ['table', 'table-sm'];
                    if ($tableStriped) $tableClasses[] = 'table-striped';
                    if ($tableHover) $tableClasses[] = 'table-hover';
                    if ($tableBordered) $tableClasses[] = 'table-bordered';

                    $headerClass = 'table-light';
                    $customStyle = '';
                    if ($tableColorScheme !== 'default' && $tableColorScheme !== 'custom') {
                        $headerClass = 'table-' . $tableColorScheme;
                    } elseif ($tableColorScheme === 'custom') {
                        $customStyle = "style=\"background-color: {$tableCustomColor}; color: white;\"";
                    }
                @endphp

                <div class="row">
                    <div class="{{ $previewWidthClass }}">
                        <div class="{{ $previewBorderClass }}" style="{{ $previewHeightStyle }}">
                            @if($showHeader)
                                <div class="card-header bg-transparent border-0 pb-0">
                                    <h6 class="card-title mb-0 text-info">
                                        <i class="mdi mdi-table me-2"></i>
                                        {{ $widgetTitle ?: 'جدول البيانات' }}
                                    </h6>
                                </div>
                            @endif
                            <div class="card-body {{ $showHeader ? 'pt-2' : '' }}">
                                <div class="table-responsive">
                                    <table class="{{ implode(' ', $tableClasses) }}">
                                        <thead class="{{ $headerClass }}" {!! $customStyle !!}>
                                            <tr>
                                                @foreach($tableColumns as $columnName)
                                                    @php
                                                        $column = collect($availableColumns)->firstWhere('name', $columnName);
                                                    @endphp
                                                    <th>{{ $column['label'] ?? $columnName }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @for($i = 1; $i <= 5; $i++)
                                                <tr>
                                                    @foreach($tableColumns as $columnName)
                                                        <td>نموذج بيانات {{ $i }}</td>
                                                    @endforeach
                                                </tr>
                                            @endfor
                                        </tbody>
                                    </table>
                                </div>

                                @if($refreshInterval !== 'manual' && $refreshInterval !== '0')
                                    <small class="text-muted d-block mt-2">
                                        <i class="mdi mdi-refresh me-1"></i>
                                        يتم تحديث البيانات كل {{ $refreshInterval }} ثانية
                                    </small>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="alert alert-info">
                            <h6>إعدادات الجدول:</h6>
                            <ul class="mb-0">
                                <li><strong>الأعمدة:</strong> {{ count($tableColumns) }} عمود</li>
                                <li><strong>الحد الأقصى:</strong> {{ $widgetLimit }} صف</li>
                                <li><strong>نظام الألوان:</strong> {{ $tableColorScheme === 'custom' ? 'مخصص (' . $tableCustomColor . ')' : $tableColorScheme }}</li>
                                <li><strong>الصفوف المتداخلة:</strong> {{ $tableStriped ? 'مفعلة' : 'معطلة' }}</li>
                                <li><strong>التفاعل:</strong> {{ $tableHover ? 'مفعل' : 'معطل' }}</li>
                                <li><strong>الحدود:</strong> {{ $tableBordered ? 'مفعلة' : 'معطلة' }}</li>
                                @if($tableSearchable)
                                    <li><strong>البحث:</strong> متاح</li>
                                @endif
                                @if($tableWithFilters)
                                    <li><strong>الفلاتر:</strong> متقدمة</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
