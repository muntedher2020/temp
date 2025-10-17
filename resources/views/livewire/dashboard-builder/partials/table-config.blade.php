<div class="card border-info">
    <div class="card-header bg-light">
        <h6 class="mb-0 text-info">
            <i class="mdi mdi-table me-2"></i>
            إعدادات الجدول
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <label class="form-label fw-semibold">عدد الصفوف المعروضة</label>
                <input type="number" class="form-control" wire:model="widgetLimit" min="1" max="100" placeholder="10">
                <small class="text-muted">الحد الأقصى للصفوف المعروضة في الجدول</small>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">ترتيب البيانات</label>
                <div class="row">
                    <div class="col-8">
                        <select class="form-select" wire:model="tableOrderBy">
                            <option value="">-- الترتيب الافتراضي --</option>
                            @foreach($availableColumns as $column)
                                <option value="{{ $column['name'] }}">{{ $column['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4">
                        <select class="form-select" wire:model="tableOrderDirection">
                            <option value="desc">تنازلي</option>
                            <option value="asc">تصاعدي</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- إعدادات الفلترة المتقدمة -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 text-warning">
                            <i class="mdi mdi-filter-variant me-2"></i>
                            فلترة البيانات المعروضة
                        </h6>
                    </div>
                    <div class="card-body">
                        <!-- الفلترة حسب الحقل -->
                        <div class="form-group mb-3">
                            <label class="form-label fw-semibold">
                                <i class="mdi mdi-filter me-1"></i>
                                فلترة حسب حقل معين
                            </label>

                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <label class="form-label text-muted small">اختر الحقل</label>
                                    <select class="form-select form-select-sm" wire:model="tableFilterColumn">
                                        <option value="">-- بدون فلترة --</option>
                                        @if(is_array($availableColumns))
                                            @foreach($availableColumns as $column)
                                                <option value="{{ $column['name'] }}">{{ $column['label'] }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                @if($tableFilterColumn)
                                    <div class="col-md-3">
                                        <label class="form-label text-muted small">نوع المقارنة</label>
                                        <select class="form-select form-select-sm" wire:model="tableFilterOperator">
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

                                    @if(!in_array($tableFilterOperator, ['IS NULL', 'IS NOT NULL']))
                                        <div class="col-md-5">
                                            <label class="form-label text-muted small">القيمة</label>
                                            <input type="text" class="form-control form-control-sm" wire:model="tableFilterValue" placeholder="أدخل القيمة للمقارنة">
                                        </div>
                                    @endif
                                @endif
                            </div>

                            @if($tableFilterColumn && $tableFilterOperator && ($tableFilterValue || in_array($tableFilterOperator, ['IS NULL', 'IS NOT NULL'])))
                                <div class="alert alert-success alert-sm py-2 mt-2">
                                    <i class="mdi mdi-check-circle me-1"></i>
                                    <small>
                                        <strong>الفلتر المطبق:</strong>
                                        {{ collect($availableColumns)->firstWhere('name', $tableFilterColumn)['label'] ?? $tableFilterColumn }}
                                        {{ $tableFilterOperator }}
                                        @if(!in_array($tableFilterOperator, ['IS NULL', 'IS NOT NULL']))
                                            "{{ $tableFilterValue }}"
                                        @endif
                                    </small>
                                </div>
                            @endif
                        </div>

                        <!-- الفلترة حسب التاريخ -->
                        @if($selectedModule && \Illuminate\Support\Facades\Schema::hasColumn($selectedModule, 'created_at'))
                            <div class="form-group mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="mdi mdi-calendar me-1"></i>
                                    فلترة حسب التاريخ
                                </label>
                                <select class="form-select form-select-sm" wire:model="tableDateFilter">
                                    <option value="">-- جميع الفترات --</option>
                                    <option value="today">اليوم فقط</option>
                                    <option value="yesterday">أمس</option>
                                    <option value="week">آخر 7 أيام</option>
                                    <option value="month">هذا الشهر</option>
                                    <option value="last_month">الشهر الماضي</option>
                                    <option value="year">هذا العام</option>
                                    <option value="custom">فترة مخصصة</option>
                                </select>

                                @if($tableDateFilter === 'custom')
                                    <div class="row mt-2">
                                        <div class="col-md-6">
                                            <label class="form-label text-muted small">من تاريخ</label>
                                            <input type="date" class="form-control form-control-sm" wire:model="tableDateFrom">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-muted small">إلى تاريخ</label>
                                            <input type="date" class="form-control form-control-sm" wire:model="tableDateTo">
                                        </div>
                                    </div>
                                @endif

                                @if($tableDateFilter && $tableDateFilter !== 'custom')
                                    <small class="text-info d-block mt-1">
                                        <i class="mdi mdi-information me-1"></i>
                                        سيتم عرض البيانات
                                        @switch($tableDateFilter)
                                            @case('today') المضافة اليوم @break
                                            @case('yesterday') المضافة أمس @break
                                            @case('week') من آخر 7 أيام @break
                                            @case('month') من هذا الشهر @break
                                            @case('last_month') من الشهر الماضي @break
                                            @case('year') من هذا العام @break
                                        @endswitch
                                    </small>
                                @elseif($tableDateFilter === 'custom' && $tableDateFrom && $tableDateTo)
                                    <small class="text-info d-block mt-1">
                                        <i class="mdi mdi-information me-1"></i>
                                        من {{ $tableDateFrom }} إلى {{ $tableDateTo }}
                                    </small>
                                @endif
                            </div>
                        @endif

                        <!-- عرض ملخص الفلاتر -->
                        @if($tableFilterColumn || $tableDateFilter)
                            <div class="alert alert-primary py-2 mt-3">
                                <i class="mdi mdi-information-outline me-1"></i>
                                <strong>ملخص الفلاتر المطبقة:</strong>
                                <ul class="mb-0 mt-2 small">
                                    @if($tableFilterColumn)
                                        <li>
                                            <strong>فلترة الحقل:</strong>
                                            {{ collect($availableColumns)->firstWhere('name', $tableFilterColumn)['label'] ?? $tableFilterColumn }}
                                            {{ $tableFilterOperator }}
                                            @if(!in_array($tableFilterOperator, ['IS NULL', 'IS NOT NULL']))
                                                "{{ $tableFilterValue }}"
                                            @endif
                                        </li>
                                    @endif
                                    @if($tableDateFilter)
                                        <li>
                                            <strong>فلترة التاريخ:</strong>
                                            @switch($tableDateFilter)
                                                @case('today') اليوم فقط @break
                                                @case('yesterday') أمس @break
                                                @case('week') آخر 7 أيام @break
                                                @case('month') هذا الشهر @break
                                                @case('last_month') الشهر الماضي @break
                                                @case('year') هذا العام @break
                                                @case('custom') من {{ $tableDateFrom }} إلى {{ $tableDateTo }} @break
                                            @endswitch
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <label class="form-label fw-semibold">الأعمدة المعروضة <span class="text-danger">*</span></label>
                <div class="border rounded p-3 bg-light" style="max-height: 300px; overflow-y: auto;">
                    @if(count($availableColumns) > 0)
                        <div class="row">
                            @foreach($availableColumns as $column)
                                <div class="col-md-6 col-lg-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               wire:model="tableColumns"
                                               value="{{ $column['name'] }}"
                                               id="table_col_{{ $column['name'] }}">
                                        <label class="form-check-label" for="table_col_{{ $column['name'] }}">
                                            <strong>{{ $column['label'] }}</strong>
                                            <small class="d-block text-muted">{{ $column['name'] }} ({{ $column['type'] }})</small>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="mdi mdi-table-off"></i>
                            لا توجد أعمدة متاحة
                        </div>
                    @endif
                </div>
                @if(count($availableColumns) > 0)
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="selectAllTableColumns">
                            <i class="mdi mdi-check-all me-1"></i>
                            تحديد الكل
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-2" wire:click="unselectAllTableColumns">
                            <i class="mdi mdi-close me-1"></i>
                            إلغاء التحديد
                        </button>
                        <span class="text-muted ms-3">
                            <i class="mdi mdi-information me-1"></i>
                            {{ count($tableColumns) }} من {{ count($availableColumns) }} محدد
                        </span>
                    </div>
                @endif
            </div>
        </div>

        @if(count($tableColumns) > 0)
            <div class="row mt-4">
                <div class="col-12">
                    <label class="form-label fw-semibold">معاينة الأعمدة المحددة</label>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-primary">
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
                                <tr>
                                    @foreach($tableColumns as $columnName)
                                        <td class="text-muted">{{ $columnName }}</td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        @if(empty($tableColumns))
            <div class="alert alert-warning mt-3">
                <i class="mdi mdi-alert me-2"></i>
                <strong>تنبيه:</strong> يجب اختيار عمود واحد على الأقل لعرضه في الجدول.
            </div>
        @endif

        <!-- إعدادات الألوان والتصميم -->
        <div class="row mt-4">
            <div class="col-12">
                <h6 class="text-primary mb-3">
                    <i class="mdi mdi-palette me-2"></i>
                    إعدادات الألوان والتصميم
                </h6>

                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">نظام الألوان</label>
                        <select class="form-select" wire:model="tableColorScheme">
                            <option value="default">افتراضي</option>
                            <option value="primary">🔵 أزرق</option>
                            <option value="success">🟢 أخضر</option>
                            <option value="info">🔵 فيروزي</option>
                            <option value="warning">🟡 أصفر</option>
                            <option value="danger">🔴 أحمر</option>
                            <option value="custom">🎨 مخصص</option>
                        </select>
                    </div>

                    @if($tableColorScheme === 'custom')
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">اللون المخصص</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" wire:model="tableCustomColor" value="{{ $tableCustomColor }}">
                                <input type="text" class="form-control" wire:model="tableCustomColor" placeholder="#696CFF" pattern="^#[0-9A-Fa-f]{6}$">
                            </div>

                            <!-- ألوان سريعة للجدول -->
                            <div class="mt-2">
                                <label class="form-label small">ألوان سريعة للجدول:</label>
                                <div class="d-flex flex-wrap gap-1">
                                    @php
                                        $tableQuickColors = [
                                            '#007bff' => 'أزرق كلاسيكي',
                                            '#28a745' => 'أخضر طبيعي',
                                            '#17a2b8' => 'فيروزي',
                                            '#ffc107' => 'أصفر ذهبي',
                                            '#dc3545' => 'أحمر كلاسيكي',
                                            '#6f42c1' => 'بنفسجي',
                                            '#e83e8c' => 'وردي',
                                            '#20c997' => 'نعناعي'
                                        ];
                                    @endphp
                                    @foreach($tableQuickColors as $colorCode => $colorName)
                                        <button type="button"
                                                class="btn btn-sm border-0 rounded-circle position-relative"
                                                style="width: 25px; height: 25px; background-color: {{ $colorCode }}; {{ $tableCustomColor === $colorCode ? 'border: 2px solid #000 !important;' : '' }}"
                                                wire:click="setTableCustomColor('{{ $colorCode }}')"
                                                title="{{ $colorName }}">
                                            @if($tableCustomColor === $colorCode)
                                                <i class="mdi mdi-check position-absolute top-50 start-50 translate-middle text-white" style="font-size: 10px; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);"></i>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                                <small class="text-muted d-block mt-1">انقر لاختيار لون الجدول</small>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model="tableStriped" id="tableStriped">
                            <label class="form-check-label" for="tableStriped">
                                صفوف متداخلة الألوان
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model="tableHover" id="tableHover">
                            <label class="form-check-label" for="tableHover">
                                تفاعل عند التمرير
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model="tableBordered" id="tableBordered">
                            <label class="form-check-label" for="tableBordered">
                                حدود واضحة
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- معاينة الجدول -->
        @if(count($tableColumns) > 0)
            <div class="row mt-4">
                <div class="col-12">
                    <h6 class="text-success mb-3">
                        <i class="mdi mdi-eye me-2"></i>
                        معاينة الجدول
                    </h6>
                    <div class="border rounded p-3">
                        @php
                            $tableClasses = ['table', 'table-sm'];
                            if ($tableStriped) $tableClasses[] = 'table-striped';
                            if ($tableHover) $tableClasses[] = 'table-hover';
                            if ($tableBordered) $tableClasses[] = 'table-bordered';

                            $headerClass = 'table-light';
                            if ($tableColorScheme !== 'default' && $tableColorScheme !== 'custom') {
                                $headerClass = 'table-' . $tableColorScheme;
                            }

                            $customStyle = '';
                            if ($tableColorScheme === 'custom') {
                                $customStyle = "style=\"background-color: {$tableCustomColor}; color: white;\"";
                            }
                        @endphp

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
                                    @for($i = 1; $i <= 3; $i++)
                                        <tr>
                                            @foreach($tableColumns as $columnName)
                                                <td class="text-muted">نموذج {{ $i }}</td>
                                            @endforeach
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>

                        <small class="text-muted">
                            <i class="mdi mdi-information me-1"></i>
                            هذه معاينة للتصميم فقط - البيانات الفعلية ستظهر في الداشبورد
                        </small>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
