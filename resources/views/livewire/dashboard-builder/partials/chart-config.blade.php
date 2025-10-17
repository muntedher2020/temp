<!-- إعدادات المخططات - نسخ من مولد التقارير -->
<div class="card border-success">
    <div class="card-header bg-light">
        <h6 class="mb-0 text-success">
            <i class="mdi mdi-chart-bar me-2"></i>
            إعدادات المخططات المتقدمة
        </h6>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6>إعدادات المخططات</h6>
            <div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" wire:model.live="enableCharts" id="enableCharts">
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
                        <button wire:click="removeChart({{ $index }})" class="btn btn-danger btn-sm">
                            <i class="mdi mdi-delete"></i> حذف المخطط
                        </button>
                    </div>

                    <!-- إعدادات المخطط الأساسية -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">نوع المخطط</label>
                            <select wire:model.live="chartSettings.{{ $index }}.type" class="form-select">
                                <option value="bar">أعمدة</option>
                                <option value="line">خطي</option>
                                <option value="pie">دائري</option>
                                <option value="doughnut">كعكة</option>
                            </select>
                            <small class="text-muted mt-1">
                                @if(isset($chart['type']))
                                    @if($chart['type'] == 'pie' || $chart['type'] == 'doughnut')
                                        📊 المخططات الدائرية تعرض مجموع كل حقل كقطعة منفصلة
                                    @else
                                        📈 المخططات الخطية والأعمدة تعرض قيم كل سجل منفصل
                                    @endif
                                @endif
                            </small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">عنوان المخطط</label>
                            <input type="text" wire:model.live="chartSettings.{{ $index }}.title"
                                class="form-control" placeholder="اكتب عنوان المخطط">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">حقل المحور X (اختياري)</label>
                            <select wire:model.live="chartSettings.{{ $index }}.xAxisField" class="form-select">
                                <option value="">-- ترقيم تلقائي --</option>
                                @foreach ($availableColumns ?? [] as $column)
                                    <option value="{{ $column['name'] ?? $column }}">
                                        {{ $column['label'] ?? $column }}
                                    </option>
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
                            <button wire:click="addColumnToChart({{ $index }})" class="btn btn-sm btn-outline-primary">
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
                                            @foreach($numericColumns ?? [] as $column)
                                                @php
                                                    $field = collect($availableColumns ?? [])->firstWhere('name', $column);
                                                    $arabicName = $field['label'] ?? $column;
                                                @endphp
                                                <option value="{{ $column }}">{{ $arabicName }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">تسمية الحقل في المخطط</label>
                                        <input type="text"
                                            wire:model.live="chartSettings.{{ $index }}.columns.{{ $colIndex }}.label"
                                            class="form-control form-control-sm" placeholder="تسمية الحقل">
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
        @else
            <div class="text-center text-muted py-4">
                <i class="mdi mdi-chart-bar-stacked display-4 mb-3"></i>
                <h6>المخططات غير مفعلة</h6>
                <p class="mb-0">قم بتفعيل المخططات لإضافة مخططات بيانية تفاعلية لهذا العنصر</p>
            </div>
        @endif
    </div>
</div>