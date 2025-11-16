<div class="data-management-container">
    @can('data-management-view')
        <!-- Navigation Tabs -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-pills nav-fill" role="tablist">
                            @can('data-management-tables')
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $currentView === 'tables' ? 'active' : '' }}"
                                        wire:click="setView('tables')" type="button">
                                        <i class="mdi mdi-table-large me-2"></i>
                                        اختيار الجدول
                                    </button>
                                </li>
                            @endcan
                            @can('data-management-manage')
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $currentView === 'manage' ? 'active' : '' }}"
                                        wire:click="setView('manage')" type="button" {{ !$selectedTable ? 'disabled' : '' }}>
                                        <i class="mdi mdi-cog me-2"></i>
                                        إدارة البيانات
                                    </button>
                                </li>
                            @endcan
                            @can('data-management-export')
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $currentView === 'export' ? 'active' : '' }}"
                                        wire:click="setView('export')" type="button" {{ !$selectedTable ? 'disabled' : '' }}>
                                        <i class="mdi mdi-file-export me-2"></i>
                                        تصدير البيانات
                                    </button>
                                </li>
                            @endcan
                            @can('data-management-import')
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $currentView === 'import' ? 'active' : '' }}"
                                        wire:click="setView('import')" type="button" {{ !$selectedTable ? 'disabled' : '' }}>
                                        <i class="mdi mdi-file-import me-2"></i>
                                        استيراد البيانات
                                    </button>
                                </li>
                            @endcan
                            @can('data-management-templates')
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $currentView === 'templates' ? 'active' : '' }}"
                                        wire:click="setView('templates')" type="button">
                                        <i class="mdi mdi-file-document-multiple me-2"></i>
                                        القوالب المحفوظة
                                    </button>
                                </li>
                            @endcan
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tables Selection View -->
        @can('data-management-tables')
            @if ($currentView === 'tables')
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="mdi mdi-table-large me-2"></i>
                                    اختر الجدول للعمل عليه
                                </h5>
                                <small class="text-muted">انقر على أي جدول لبدء إدارة بياناته</small>
                            </div>

                            <div class="card-body">
                                <div class="row g-4">
                                    @if (count($availableTables) > 0)
                                        @foreach ($availableTables as $table)
                                            <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                                                <div class="card h-100 cursor-pointer position-relative overflow-hidden border-0 shadow-sm"
                                                    wire:click="selectTable('{{ $table['name'] }}')"
                                                    style="transition: all 0.3s ease; background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);"
                                                    onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.1)'"
                                                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.08)'">

                                                    <!-- Decorative top border -->
                                                    <div class="position-absolute top-0 start-0 w-100"
                                                        style="height: 4px; background: linear-gradient(90deg, #696cff 0%, #8b5cf6 50%, #06b6d4 100%);">
                                                    </div>

                                                    <div class="card-body text-center p-4">
                                                        <!-- Table name with modern typography -->
                                                        <h6 class="mb-3 fw-bold mt-3"
                                                            style="color: #2c3e50; font-size: 1.1rem;">
                                                            {{ $table['display_name'] }}
                                                        </h6>

                                                        <!-- Technical name with badge style -->
                                                        <div class="mb-3">
                                                            <span class="badge bg-light text-dark px-3 py-2 rounded-pill"
                                                                style="font-family: 'Monaco', 'Menlo', monospace; font-size: 0.75rem; border: 1px solid #e9ecef;">
                                                                {{ $table['name'] }}
                                                            </span>
                                                        </div>

                                                        <!-- Statistics with modern cards -->
                                                        <div class="row g-2">
                                                            <div class="col-6">
                                                                <div class="p-3 rounded-3"
                                                                    style="background: linear-gradient(135deg, rgba(40, 199, 111, 0.1) 0%, rgba(40, 199, 111, 0.05) 100%); border: 1px solid rgba(40, 199, 111, 0.2);">
                                                                    <div
                                                                        class="d-flex align-items-center justify-content-center mb-1">
                                                                        <i class="mdi mdi-table-row text-success me-1"
                                                                            style="font-size: 1.2rem;"></i>
                                                                    </div>
                                                                    <span class="d-block fw-bold text-success"
                                                                        style="font-size: 1.1rem;">{{ number_format($table['row_count']) }}</span>
                                                                    <small class="text-muted fw-medium">سجل</small>
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="p-3 rounded-3"
                                                                    style="background: linear-gradient(135deg, rgba(105, 108, 255, 0.1) 0%, rgba(105, 108, 255, 0.05) 100%); border: 1px solid rgba(105, 108, 255, 0.2);">
                                                                    <div
                                                                        class="d-flex align-items-center justify-content-center mb-1">
                                                                        <i class="mdi mdi-view-column text-primary me-1"
                                                                            style="font-size: 1.2rem;"></i>
                                                                    </div>
                                                                    <span class="d-block fw-bold text-primary"
                                                                        style="font-size: 1.1rem;">{{ $table['columns_count'] }}</span>
                                                                    <small class="text-muted fw-medium">عمود</small>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Hover effect indicator -->
                                                        <div class="mt-3 opacity-50" style="transition: all 0.3s ease;">
                                                            <i class="mdi mdi-arrow-left text-primary"></i>
                                                            <small class="text-muted ms-1">انقر للاختيار</small>
                                                        </div>
                                                    </div>

                                                    <!-- Subtle pattern overlay -->
                                                    <div class="position-absolute top-0 start-0 w-100 h-100 opacity-3"
                                                        style="background-image: radial-gradient(circle at 1px 1px, rgba(105, 108, 255, 0.1) 1px, transparent 0); background-size: 20px 20px; pointer-events: none;">
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="col-12">
                                            <div class="text-center py-5">
                                                <!-- Empty state with modern design -->
                                                <div class="position-relative mb-4">
                                                    <div class="avatar avatar-2xl mx-auto">
                                                        <div class="avatar-initial rounded-circle"
                                                            style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: 2px dashed #dee2e6;">
                                                            <i class="mdi mdi-table-remove mdi-48px text-muted"></i>
                                                        </div>
                                                    </div>
                                                    <!-- Decorative elements -->
                                                    <div class="position-absolute"
                                                        style="top: 10px; right: 30%; width: 12px; height: 12px; background: #ff6b6b; border-radius: 50%; opacity: 0.3;">
                                                    </div>
                                                    <div class="position-absolute"
                                                        style="bottom: 15px; left: 25%; width: 8px; height: 8px; background: #4ecdc4; border-radius: 50%; opacity: 0.4;">
                                                    </div>
                                                    <div class="position-absolute"
                                                        style="top: 40px; left: 20%; width: 6px; height: 6px; background: #45b7d1; border-radius: 50%; opacity: 0.5;">
                                                    </div>
                                                </div>

                                                <h5 class="text-muted mb-3 fw-bold">لا توجد جداول متاحة</h5>
                                                <p class="text-muted mb-4" style="max-width: 400px; margin: 0 auto;">
                                                    يبدو أنه لا توجد جداول في قاعدة البيانات متاحة للعرض في الوقت الحالي
                                                </p>

                                                <!-- Action button with modern style -->
                                                <button class="btn btn-outline-primary rounded-pill px-4 py-2"
                                                    style="border: 2px solid; transition: all 0.3s ease;"
                                                    onmouseover="this.style.transform='scale(1.05)'"
                                                    onmouseout="this.style.transform='scale(1)'">
                                                    <i class="mdi mdi-refresh me-2"></i>
                                                    إعادة تحميل الجداول
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endcan

        <!-- Data Management View -->
        @can('data-management-manage')
            @if ($currentView === 'manage' && $selectedTable)
                <div class="row">
                    <!-- Table Info -->
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">
                                        <i class="mdi mdi-table me-2"></i>
                                        جدول: {{ $this->getTableDisplayName($selectedTable) }}
                                    </h5>
                                    <small class="text-muted">{{ $selectedTable }} - {{ number_format($recordsCount) }}
                                        سجل</small>
                                </div>
                                <button class="btn btn-outline-secondary btn-sm" wire:click="setView('tables')">
                                    <i class="mdi mdi-arrow-left me-1"></i>
                                    تغيير الجدول
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Filters and Search -->
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="mdi mdi-filter me-2"></i>
                                    البحث والتصفية
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">البحث العام</label>
                                        <input type="text" class="form-control" wire:model.debounce.500ms="search"
                                            placeholder="ابحث في جميع الأعمدة...">
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">عمود التصفية</label>
                                        <select class="form-select" wire:model="filterColumn">
                                            <option value="">اختر العمود</option>
                                            @if (isset($previewColumns) && is_array($previewColumns))
                                                @foreach ($previewColumns as $column)
                                                    <option value="{{ $column }}">
                                                        {{ $this->getColumnDisplayName($column) }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">المقارنة</label>
                                        <select class="form-select" wire:model="filterOperator">
                                            <option value="=">=</option>
                                            <option value="!=">≠</option>
                                            <option value="LIKE">يحتوي على</option>
                                            <option value=">">&gt;</option>
                                            <option value="<">&lt;</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">القيمة</label>
                                        <input type="text" class="form-control" wire:model="filterValue"
                                            placeholder="القيمة للمقارنة">
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-primary btn-sm" wire:click="applyFilter"
                                                {{ !$filterColumn || !$filterValue ? 'disabled' : '' }}>
                                                <i class="mdi mdi-magnify"></i>
                                                تطبيق
                                            </button>
                                            <button class="btn btn-outline-secondary btn-sm" wire:click="clearFilter">
                                                <i class="mdi mdi-close"></i>
                                                إلغاء
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- عرض الفلاتر المطبقة -->
                                @if (count($appliedFilters) > 0)
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <h6 class="mb-2">الفلاتر المطبقة:</h6>
                                            <div class="d-flex flex-wrap gap-2">
                                                @foreach ($appliedFilters as $index => $filter)
                                                    <span class="badge bg-primary d-flex align-items-center">
                                                        {{ $filter['display'] }}
                                                        <button type="button" class="btn-close btn-close-white ms-2"
                                                            wire:click="removeFilter({{ $index }})"
                                                            aria-label="إزالة"></button>
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Data Preview -->
                    @if ($showPreview && !empty($previewData))
                        <div class="col-12 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="mdi mdi-eye me-2"></i>
                                        معاينة البيانات (أول 10 سجلات)
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover preview-table">
                                            <thead>
                                                <tr>
                                                    @foreach ($previewColumns as $column)
                                                        <th wire:click="sortBy('{{ $column }}')"
                                                            style="cursor: pointer;">
                                                            {{ $this->getColumnDisplayName($column) }}
                                                            @if ($sortColumn === $column)
                                                                <i
                                                                    class="mdi mdi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                                            @endif
                                                        </th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($previewData as $row)
                                                    <tr>
                                                        @foreach ($previewColumns as $column)
                                                            <td>
                                                                @php
                                                                    $value = $row->{$column} ?? '-';
                                                                    if (strlen($value) > 50) {
                                                                        $value = substr($value, 0, 50) . '...';
                                                                    }
                                                                @endphp
                                                                {{ $value }}
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        @endcan

        <!-- Export View -->
        @can('data-management-export')
            @if ($currentView === 'export' && $selectedTable)
                <div class="row">
                    <div class="col-lg-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="mdi mdi-file-export me-2"></i>
                                    تصدير بيانات: {{ $this->getTableDisplayName($selectedTable) }}
                                </h5>
                                <small class="text-muted">اختر إما تحميل قالب فارغ للتعبئة أو تصدير البيانات الحالية</small>
                            </div>
                            <div class="card-body">
                                <form wire:submit.prevent="exportData">
                                    <!-- Export Format -->
                                    <div class="mb-4">
                                        <label class="form-label">اختر نوع الملف</label>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <div class="card h-100 cursor-pointer position-relative {{ $exportFormat === 'xlsx' ? 'border-primary shadow-sm' : '' }}"
                                                    wire:click="$set('exportFormat', 'xlsx')"
                                                    style="transition: all 0.3s ease; {{ $exportFormat === 'xlsx' ? 'background: linear-gradient(135deg, rgba(105, 108, 255, 0.05) 0%, rgba(105, 108, 255, 0.1) 100%);' : '' }}">
                                                    <div class="card-body text-center">
                                                        <div class="avatar avatar-lg mx-auto mb-3">
                                                            <span class="avatar-initial rounded-circle bg-label-success">
                                                                <i class="mdi mdi-file-excel mdi-24px"></i>
                                                            </span>
                                                        </div>
                                                        <h6 class="mb-2">Excel (XLSX)</h6>
                                                        <p class="text-muted small mb-3">الأفضل للبيانات الكبيرة والتحليل</p>
                                                        <div class="d-flex gap-1 justify-content-center">
                                                            <span class="badge bg-label-primary">تنسيق متقدم</span>
                                                            <span class="badge bg-label-info">جداول ذكية</span>
                                                        </div>
                                                        @if ($exportFormat === 'xlsx')
                                                            <div class="position-absolute top-0 end-0 m-2">
                                                                <div class="badge bg-primary rounded-circle p-1">
                                                                    <i class="mdi mdi-check mdi-16px text-white"></i>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card h-100 cursor-pointer position-relative {{ $exportFormat === 'csv' ? 'border-primary shadow-sm' : '' }}"
                                                    wire:click="$set('exportFormat', 'csv')"
                                                    style="transition: all 0.3s ease; {{ $exportFormat === 'csv' ? 'background: linear-gradient(135deg, rgba(105, 108, 255, 0.05) 0%, rgba(105, 108, 255, 0.1) 100%);' : '' }}">
                                                    <div class="card-body text-center">
                                                        <div class="avatar avatar-lg mx-auto mb-3">
                                                            <span class="avatar-initial rounded-circle bg-label-info">
                                                                <i class="mdi mdi-file-delimited mdi-24px"></i>
                                                            </span>
                                                        </div>
                                                        <h6 class="mb-2">CSV</h6>
                                                        <p class="text-muted small mb-3">متوافق مع جميع البرامج والأنظمة</p>
                                                        <div class="d-flex gap-1 justify-content-center">
                                                            <span class="badge bg-label-success">سهل الاستيراد</span>
                                                            <span class="badge bg-label-warning">حجم صغير</span>
                                                        </div>
                                                        @if ($exportFormat === 'csv')
                                                            <div class="position-absolute top-0 end-0 m-2">
                                                                <div class="badge bg-primary rounded-circle p-1">
                                                                    <i class="mdi mdi-check mdi-16px text-white"></i>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card h-100 cursor-pointer position-relative {{ $exportFormat === 'pdf' ? 'border-primary shadow-sm' : '' }}"
                                                    wire:click="$set('exportFormat', 'pdf')"
                                                    style="transition: all 0.3s ease; {{ $exportFormat === 'pdf' ? 'background: linear-gradient(135deg, rgba(105, 108, 255, 0.05) 0%, rgba(105, 108, 255, 0.1) 100%);' : '' }}">
                                                    <div class="card-body text-center">
                                                        <div class="avatar avatar-lg mx-auto mb-3">
                                                            <span class="avatar-initial rounded-circle bg-label-danger">
                                                                <i class="mdi mdi-file-pdf-box mdi-24px"></i>
                                                            </span>
                                                        </div>
                                                        <h6 class="mb-2">PDF</h6>
                                                        <p class="text-muted small mb-3">مثالي للطباعة والعرض الرسمي</p>
                                                        <div class="d-flex gap-1 justify-content-center">
                                                            <span class="badge bg-label-success">جودة عالية</span>
                                                            <span class="badge bg-label-secondary">آمن</span>
                                                        </div>
                                                        @if ($exportFormat === 'pdf')
                                                            <div class="position-absolute top-0 end-0 m-2">
                                                                <div class="badge bg-primary rounded-circle p-1">
                                                                    <i class="mdi mdi-check mdi-16px text-white"></i>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Export Options -->
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label">عدد السجلات (اختياري)</label>
                                            <input type="number" class="form-control" wire:model="exportLimit"
                                                placeholder="جميع السجلات" min="1" max="50000">
                                            <small class="text-muted">اتركه فارغاً لتصدير جميع السجلات</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">اسم الملف (اختياري)</label>
                                            <input type="text" class="form-control" wire:model="customFileName"
                                                placeholder="سيتم إنشاء اسم تلقائياً">
                                            <small class="text-muted">بدون امتداد الملف</small>
                                        </div>
                                    </div>

                                    <!-- Save as Template -->
                                    <div class="mb-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" wire:model="saveAsTemplate"
                                                id="saveAsTemplate">
                                            <label class="form-check-label" for="saveAsTemplate">
                                                حفظ هذا التكوين كقالب للاستخدام المستقبلي
                                            </label>
                                        </div>
                                        @if ($saveAsTemplate)
                                            <div class="mt-3">
                                                <div class="row">
                                                    <div class="col-md-6 mb-2">
                                                        <input type="text" class="form-control" wire:model="templateName"
                                                            placeholder="اسم القالب" required>
                                                    </div>
                                                    <div class="col-md-6 mb-2">
                                                        <input type="text" class="form-control"
                                                            wire:model="templateDescription"
                                                            placeholder="وصف القالب (اختياري)">
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="d-flex gap-2 align-items-center flex-wrap">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="mdi mdi-download me-2"></i>
                                            تصدير البيانات
                                        </button>

                                        <div class="d-flex gap-2 align-items-center flex-wrap">
                                            <button type="button" class="btn btn-outline-info" wire:click="downloadTemplate"
                                                data-bs-toggle="tooltip"
                                                title="تحميل ملف Excel فارغ يحتوي على أسماء الأعمدة فقط - جاهز لتعبئة البيانات الجديدة">
                                                <i class="mdi mdi-file-download-outline me-2"></i>
                                                تحميل قالب فارغ للتعبئة
                                            </button>

                                            <!-- Export Progress - Shows when exporting data -->
                                            <div wire:loading wire:target="exportData" class="flex-grow-1"
                                                style="min-width: 300px;">
                                                <div class="alert alert-primary d-flex align-items-center mb-0 py-2"
                                                    role="alert">
                                                    <div class="spinner-border spinner-border-sm me-3" role="status">
                                                        <span class="visually-hidden">جاري التصدير...</span>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <strong>جاري تصدير البيانات...</strong>
                                                        <div class="progress mt-2" style="height: 18px;">
                                                            <div class="progress-bar progress-bar-striped progress-bar-animated"
                                                                role="progressbar" style="width: 100%" aria-valuenow="100"
                                                                aria-valuemin="0" aria-valuemax="100">
                                                                <small>معالجة البيانات</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <small class="text-muted d-block mt-1 ms-2">
                                                    <i class="mdi mdi-information-outline me-1"></i>
                                                    يرجى الانتظار حتى اكتمال العملية
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Column Selector -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="mdi mdi-view-column me-2"></i>
                                    اختيار الأعمدة
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="column-selector">
                                    @foreach ($exportColumns as $index => $column)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox"
                                                wire:model="exportColumns.{{ $index }}.selected"
                                                wire:change="updateSelectedColumns" id="column{{ $index }}">
                                            <label class="form-check-label" for="column{{ $index }}">
                                                <strong>{{ $column['display_name'] }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $column['name'] }}
                                                    ({{ $column['type'] }})
                                                </small>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="mdi mdi-information me-1"></i>
                                        {{ count(array_filter($exportColumns, fn($col) => $col['selected'])) }} عمود محدد
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endcan

        <!-- Import View -->
        @can('data-management-import')
            @if ($currentView === 'import' && $selectedTable)
                <div class="row">
                    <div class="col-lg-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="mdi mdi-file-import me-2"></i>
                                    استيراد بيانات إلى: {{ $this->getTableDisplayName($selectedTable) }}
                                </h5>
                                <small class="text-muted">ارفع ملف Excel المُعبأ بالبيانات الجديدة</small>
                            </div>
                            <div class="card-body">
                                <form wire:submit.prevent="importData">
                                    <!-- File Upload -->
                                    <div class="mb-4">
                                        <label class="form-label">اختر ملف البيانات</label>
                                        <div class="file-upload-area border-2 border-dashed {{ $importFile ? 'border-success bg-light-success' : 'border-primary' }} rounded p-4 text-center"
                                            id="fileDropArea" style="transition: all 0.3s ease;">
                                            <input type="file" class="form-control" wire:model.defer="importFile"
                                                accept=".xlsx,.csv,.xls" style="display: none;" id="importFile">
                                            <label for="importFile" style="cursor: pointer; display: block;">
                                                @if ($importFile)
                                                    <i class="mdi mdi-file-check mdi-48px text-success mb-3"></i>
                                                    <h6 id="dropAreaText" class="text-success">✅ تم رفع الملف بنجاح</h6>
                                                    <small class="text-success" id="dropAreaSubtext">الملف جاهز للاستيراد -
                                                        يمكنك اختيار ملف آخر إذا أردت</small>
                                                @else
                                                    <i class="mdi mdi-cloud-upload mdi-48px text-primary mb-3"></i>
                                                    <h6 id="dropAreaText">انقر لاختيار الملف أو اسحبه هنا</h6>
                                                    <small class="text-muted" id="dropAreaSubtext">يدعم ملفات Excel (XLSX,
                                                        XLS) و CSV - حد أقصى 10 ميجابايت</small>
                                                @endif
                                            </label>
                                        </div> <!-- مؤشر التحميل -->
                                        <div wire:loading wire:target="importFile" class="mt-2">
                                            <div class="alert alert-info">
                                                <i class="mdi mdi-loading mdi-spin me-2"></i>
                                                جاري تحميل الملف...
                                            </div>
                                        </div>

                                        @if ($importFile)
                                            <div class="alert alert-success mt-3" role="alert">
                                                <i class="mdi mdi-file-check me-2"></i>
                                                @php
                                                    $file = is_array($importFile)
                                                        ? (isset($importFile[0])
                                                            ? $importFile[0]
                                                            : null)
                                                        : $importFile;
                                                @endphp
                                                @if ($file && method_exists($file, 'getClientOriginalName'))
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            ملف محدد: <strong>{{ $file->getClientOriginalName() }}</strong>
                                                            ({{ number_format($file->getSize() / 1024, 2) }} KB)
                                                        </div>
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-sm btn-outline-info"
                                                                wire:click="testFile" title="اختبار الملف">
                                                                <i class="mdi mdi-file-check"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                                wire:click="$set('importFile', null)" title="إزالة الملف">
                                                                <i class="mdi mdi-close"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                @else
                                                    ملف محدد:
                                                    <strong>{{ is_string($importFile) ? basename($importFile) : 'ملف غير معروف' }}</strong>
                                                @endif
                                            </div>
                                        @endif
                                        @error('importFile')
                                            <div class="text-danger mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Import Mode -->
                                    <div class="mb-4">
                                        <label class="form-label">نوع الاستيراد</label>
                                        <div class="row">
                                            <div class="col-md-4 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" wire:model="importMode"
                                                        value="insert" id="modeInsert">
                                                    <label class="form-check-label" for="modeInsert">
                                                        <strong>إدراج جديد</strong>
                                                        <br>
                                                        <small class="text-muted">إضافة السجلات الجديدة فقط</small>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" wire:model="importMode"
                                                        value="update" id="modeUpdate">
                                                    <label class="form-check-label" for="modeUpdate">
                                                        <strong>تحديث</strong>
                                                        <br>
                                                        <small class="text-muted">تحديث السجلات الموجودة</small>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" wire:model="importMode"
                                                        value="replace" id="modeReplace">
                                                    <label class="form-check-label" for="modeReplace">
                                                        <strong>استبدال</strong>
                                                        <br>
                                                        <small class="text-muted">حذف الموجود وإدراج الجديد</small>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Import Progress -->
                                    @if ($importStatus)
                                        <div class="progress-container mb-4">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong>{{ $importStatus }}</strong>
                                                <div class="d-flex align-items-center">
                                                    <span class="me-2">{{ $importProgress }}%</span>
                                                    @if (str_contains($importStatus, 'فشل') || $importProgress == 0)
                                                        <button type="button" class="btn btn-sm btn-outline-warning"
                                                            onclick="retryImport()" title="إعادة المحاولة">
                                                            <i class="mdi mdi-refresh"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar" role="progressbar"
                                                    style="width: {{ $importProgress }}%"
                                                    aria-valuenow="{{ $importProgress }}" aria-valuemin="0"
                                                    aria-valuemax="100"></div>
                                            </div>

                                            @if (!empty($importResults))
                                                <div class="mt-3">
                                                    @php
                                                        $allSkipped =
                                                            ($importResults['skipped_count'] ?? 0) ==
                                                                ($importResults['total_processed'] ?? 0) &&
                                                            ($importResults['success_count'] ?? 0) == 0;
                                                    @endphp

                                                    <div class="alert {{ $allSkipped ? 'alert-warning' : 'alert-info' }}">
                                                        <h6>نتائج الاستيراد:</h6>
                                                        <ul class="mb-0">
                                                            <li>تم بنجاح: {{ $importResults['success_count'] ?? 0 }} سجل</li>
                                                            <li>فشل: {{ $importResults['error_count'] ?? 0 }} سجل</li>
                                                            @if (isset($importResults['skipped_count']) && $importResults['skipped_count'] > 0)
                                                                <li>تم تخطيه: {{ $importResults['skipped_count'] }} سجل</li>
                                                            @endif
                                                        </ul>

                                                        @if ($allSkipped)
                                                            <hr>
                                                            <div class="text-warning">
                                                                <strong>⚠️ تحذير:</strong> تم تخطي جميع السجلات!
                                                                <br>
                                                                <small>
                                                                    السبب المحتمل: أسماء الأعمدة في الملف لا تتطابق مع أسماء
                                                                    الأعمدة في الجدول.
                                                                    <br>
                                                                    يرجى التأكد من استخدام نفس أسماء الأعمدة المعروضة أعلاه.
                                                                </small>
                                                            </div>
                                                        @endif

                                                        @if (isset($importResults['summary']))
                                                            <hr>
                                                            <small class="text-muted">{{ $importResults['summary'] }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    <!-- Action Buttons -->
                                    <div class="d-flex gap-2 align-items-center flex-wrap">
                                        <button type="submit" class="btn btn-success" {{ !$importFile ? 'disabled' : '' }}>
                                            <i class="mdi mdi-upload me-2"></i>
                                            بدء الاستيراد
                                        </button>

                                        <div class="d-flex gap-2 align-items-center flex-grow-1 flex-wrap">
                                            <button type="button" class="btn btn-outline-info"
                                                wire:click="downloadTemplate">
                                                <i class="mdi mdi-download me-2"></i>
                                                تحميل قالب للبيانات
                                            </button>

                                            <!-- Import Progress - Shows when importing -->
                                            <div wire:loading wire:target="importData" class="flex-grow-1"
                                                style="min-width: 300px;">
                                                <div class="alert alert-success d-flex align-items-center mb-0 py-2"
                                                    role="alert">
                                                    <div class="spinner-border spinner-border-sm me-3" role="status">
                                                        <span class="visually-hidden">جاري الاستيراد...</span>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <strong>جاري استيراد البيانات...</strong>
                                                        <div class="progress mt-2" style="height: 18px;">
                                                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                                                                role="progressbar" style="width: 100%" aria-valuenow="100"
                                                                aria-valuemin="0" aria-valuemax="100">
                                                                <small>معالجة البيانات</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <small class="text-muted d-block mt-1 ms-2">
                                                    <i class="mdi mdi-information-outline me-1"></i>
                                                    يرجى الانتظار حتى اكتمال العملية
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Import Instructions -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="mdi mdi-information me-2"></i>
                                    تعليمات الاستيراد
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning" role="alert">
                                    <h6>إرشادات مهمة:</h6>
                                    <ul class="mb-0">
                                        <li>استخدم القالب المحمل لضمان التوافق</li>
                                        <li>تأكد من صحة أسماء الأعمدة</li>
                                        <li>احفظ نسخة احتياطية قبل الاستيراد</li>
                                        <li>تحقق من البيانات قبل الرفع</li>
                                    </ul>
                                </div>

                                <h6 class="mt-4 mb-3">الأعمدة المتاحة:</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>العمود</th>
                                                <th>النوع</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($exportColumns as $column)
                                                <tr>
                                                    <td>{{ $column['display_name'] }}</td>
                                                    <td><span
                                                            class="badge badge-custom bg-light text-dark">{{ $column['type'] }}</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endcan

        <!-- Templates View -->
        @can('data-management-templates')
            @if ($currentView === 'templates')
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">
                                        <i class="mdi mdi-file-document-multiple me-2"></i>
                                        القوالب المحفوظة
                                    </h5>
                                    <small class="text-muted">قوالب البيانات المحفوظة للاستخدام السريع</small>
                                </div>
                                @if ($selectedTable)
                                    <div class="d-flex align-items-center">
                                        <i class="mdi mdi-table me-1 text-primary"></i>
                                        <span
                                            class="badge bg-label-primary">{{ $this->getTableDisplayName($selectedTable) }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="card-body">
                                @if (empty($templates))
                                    <div class="text-center py-5">
                                        <div class="avatar avatar-xl mx-auto mb-3">
                                            <span class="avatar-initial rounded-circle bg-label-secondary">
                                                <i class="mdi mdi-file-document-multiple-outline mdi-36px"></i>
                                            </span>
                                        </div>
                                        <h6 class="mb-2">لا توجد قوالب محفوظة</h6>
                                        <p class="text-muted mb-3">قم بإنشاء قالب من صفحة التصدير لحفظ الإعدادات</p>
                                        @if ($selectedTable)
                                            <button class="btn btn-primary btn-sm" wire:click="setView('export')">
                                                <i class="mdi mdi-plus me-1"></i>
                                                إنشاء قالب جديد
                                            </button>
                                        @else
                                            <button class="btn btn-outline-primary btn-sm" wire:click="setView('tables')">
                                                <i class="mdi mdi-table me-1"></i>
                                                اختر جدول أولاً
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    <!-- Templates Grid -->
                                    <div class="row">
                                        @foreach ($templates as $template)
                                            <div class="col-md-6 col-lg-4 col-xl-3 mb-4">
                                                <div class="card h-100 cursor-pointer card-action {{ $selectedTemplate == $template['id'] ? 'border-primary' : '' }}"
                                                    wire:click="loadTemplate({{ $template['id'] }})">
                                                    <div class="card-body">
                                                        <!-- Template Header -->
                                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                                            <div class="avatar avatar-md">
                                                                <span
                                                                    class="avatar-initial rounded-circle bg-label-{{ $template['export_settings']['format'] == 'xlsx'
                                                                        ? 'success'
                                                                        : ($template['export_settings']['format'] == 'pdf'
                                                                            ? 'danger'
                                                                            : 'info') }}">
                                                                    <i
                                                                        class="mdi mdi-file-{{ $template['export_settings']['format'] == 'xlsx'
                                                                            ? 'excel'
                                                                            : ($template['export_settings']['format'] == 'pdf'
                                                                                ? 'pdf-box'
                                                                                : 'delimited') }} mdi-24px"></i>
                                                                </span>
                                                            </div>
                                                            <div class="dropdown">
                                                                <button
                                                                    class="btn btn-sm btn-outline-secondary dropdown-toggle hide-arrow"
                                                                    type="button" data-bs-toggle="dropdown"
                                                                    aria-expanded="false" wire:click.stop>
                                                                    <i class="mdi mdi-dots-vertical"></i>
                                                                </button>
                                                                <ul class="dropdown-menu dropdown-menu-end">
                                                                    <li>
                                                                        <a class="dropdown-item" href="#"
                                                                            wire:click.prevent.stop="loadTemplate({{ $template['id'] }})">
                                                                            <i class="mdi mdi-play me-2"></i>
                                                                            استخدام القالب
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <a class="dropdown-item" href="#"
                                                                            wire:click.prevent.stop="editTemplate({{ $template['id'] }})">
                                                                            <i class="mdi mdi-pencil me-2"></i>
                                                                            تعديل القالب
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <hr class="dropdown-divider">
                                                                    </li>
                                                                    <li>
                                                                        <a class="dropdown-item text-danger" href="#"
                                                                            wire:click.prevent.stop="deleteTemplate({{ $template['id'] }})">
                                                                            <i class="mdi mdi-delete-outline me-2"></i>
                                                                            حذف القالب
                                                                        </a>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>

                                                        <!-- Template Name & Format -->
                                                        <div class="mb-3">
                                                            <h6 class="card-title mb-1">{{ $template['name'] }}</h6>
                                                            <span
                                                                class="badge bg-{{ $template['export_settings']['format'] == 'xlsx'
                                                                    ? 'success'
                                                                    : ($template['export_settings']['format'] == 'pdf'
                                                                        ? 'danger'
                                                                        : 'info') }}">
                                                                {{ strtoupper($template['export_settings']['format'] ?? 'xlsx') }}
                                                            </span>
                                                        </div>

                                                        <!-- Template Description -->
                                                        @if ($template['description'])
                                                            <p class="text-muted small mb-3">{{ $template['description'] }}
                                                            </p>
                                                        @endif

                                                        <!-- Template Stats -->
                                                        <div class="row text-center mb-3">
                                                            <div class="col-6">
                                                                <div class="d-flex flex-column">
                                                                    <span
                                                                        class="fw-semibold">{{ $this->getTableDisplayName($template['table_name']) }}</span>
                                                                    <small class="text-muted">الجدول</small>
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="d-flex flex-column">
                                                                    <span
                                                                        class="fw-semibold">{{ count($template['columns_config']['selected'] ?? []) }}</span>
                                                                    <small class="text-muted">عمود</small>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Usage Info -->
                                                        <div class="row text-center">
                                                            <div class="col-6">
                                                                <div class="d-flex flex-column">
                                                                    <span
                                                                        class="fw-semibold text-primary">{{ $template['usage_count'] ?? 0 }}</span>
                                                                    <small class="text-muted">استخدام</small>
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="d-flex flex-column">
                                                                    <span
                                                                        class="fw-semibold">{{ $template['creator'] ?? 'غير محدد' }}</span>
                                                                    <small class="text-muted">المنشئ</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Card Footer -->
                                                    <div class="card-footer bg-transparent">
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <small class="text-muted">
                                                                <i class="mdi mdi-clock-outline me-1"></i>
                                                                {{ $template['last_used_at'] ? \Carbon\Carbon::parse($template['last_used_at'])->diffForHumans() : 'لم يستخدم' }}
                                                            </small>
                                                            @if ($selectedTemplate == $template['id'])
                                                                <span class="badge bg-primary">
                                                                    <i class="mdi mdi-check me-1"></i>
                                                                    محدد
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <!-- Templates Actions -->
                                    @if ($selectedTemplate)
                                        <div class="row mt-4">
                                            <div class="col-12">
                                                <div class="alert alert-primary d-flex align-items-center" role="alert">
                                                    <i class="mdi mdi-information-outline me-2"></i>
                                                    <div class="flex-grow-1">
                                                        <strong>قالب محدد:</strong>
                                                        {{ collect($templates)->firstWhere('id', $selectedTemplate)['name'] ?? 'غير محدد' }}
                                                    </div>
                                                    <div class="ms-3">
                                                        <button class="btn btn-primary btn-sm me-2"
                                                            wire:click="useSelectedTemplate">
                                                            <i class="mdi mdi-play me-1"></i>
                                                            استخدام القالب
                                                        </button>
                                                        <button class="btn btn-outline-secondary btn-sm"
                                                            wire:click="clearSelectedTemplate">
                                                            <i class="mdi mdi-close me-1"></i>
                                                            إلغاء التحديد
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endcan

        <!-- Error Messages -->
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h6>يرجى تصحيح الأخطاء التالية:</h6>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Success Messages -->
        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="mdi mdi-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Error Messages -->
        @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="mdi mdi-alert-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    @endcan
</div>

@push('scripts')
    <script>
        // معالجة أحداث الاستيراد
        window.addEventListener('livewire:load', function() {
            // حدث بدء الاستيراد
            Livewire.on('importStarted', function() {
                console.log('بدء عملية الاستيراد');
                // يمكن إضافة loading spinner هنا
            });

            // حدث تحديث التقدم
            Livewire.on('importProgress', function(progress) {
                console.log('تقدم الاستيراد: ' + progress + '%');
                // تحديث عرض التقدم إذا كان موجوداً
                const progressElement = document.querySelector('.display-3.text-success');
                if (progressElement) {
                    progressElement.textContent = progress + '%';
                }
            });

            // حدث تحديث تقدم التصدير
            Livewire.on('exportProgress', function(progress) {
                console.log('تقدم التصدير: ' + progress + '%');
                // تحديث عرض التقدم إذا كان موجوداً
                const progressElement = document.querySelector('.display-3.text-primary');
                if (progressElement) {
                    progressElement.textContent = progress + '%';
                }
            });

            // حدث انتهاء الاستيراد
            Livewire.on('importCompleted', function() {
                console.log('انتهت عملية الاستيراد');
                // يمكن إضافة إشعار نجاح هنا
            });
        });

        // معالجة أحداث تحديث التقدم من الـ Browser Events
        window.addEventListener('export-progress-update', function(event) {
            console.log('تحديث تقدم التصدير:', event.detail.progress + '%');
            const progressNumber = document.getElementById('export-progress-number');
            const progressBar = document.getElementById('export-progress-bar');

            if (progressNumber) {
                progressNumber.textContent = event.detail.progress + '%';
            }
            if (progressBar) {
                progressBar.style.width = event.detail.progress + '%';
                progressBar.setAttribute('aria-valuenow', event.detail.progress);
                const span = progressBar.querySelector('span');
                if (span) {
                    span.textContent = event.detail.progress > 0 ? 'جاري المعالجة...' : 'بدء العملية...';
                }
            }
        });

        window.addEventListener('import-progress-update', function(event) {
            console.log('تحديث تقدم الاستيراد:', event.detail.progress + '%');
            const progressNumber = document.getElementById('import-progress-number');
            const progressBar = document.getElementById('import-progress-bar');

            if (progressNumber) {
                progressNumber.textContent = event.detail.progress + '%';
            }
            if (progressBar) {
                progressBar.style.width = event.detail.progress + '%';
                progressBar.setAttribute('aria-valuenow', event.detail.progress);
                const span = progressBar.querySelector('span');
                if (span) {
                    span.textContent = event.detail.progress > 0 ? 'جاري المعالجة...' : 'بدء العملية...';
                }
            }
        });

        // معالجة الأحداث المخصصة
        window.addEventListener('import-status-update', function(event) {
            console.log('تحديث حالة الاستيراد:', event.detail);
        });

        window.addEventListener('import-error', function(event) {
            console.log('خطأ في الاستيراد:', event.detail);
            // عرض رسالة خطأ فورية
            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'فشل الاستيراد',
                    text: event.detail.message,
                    confirmButtonText: 'موافق',
                    toast: true,
                    position: 'top-start', // تغيير الموضع أيضاً
                    width: '400px'
                });
            } else {
                // إشعار بديل
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed';
                alertDiv.style.cssText =
                    'top: 20px; left: 280px; z-index: 9999; min-width: 350px; max-width: 500px;';
                alertDiv.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="mdi mdi-alert-circle me-3" style="font-size: 2rem;"></i>
                    <div>
                        <strong>فشل الاستيراد</strong><br>
                        <small>${event.detail.message}</small>
                    </div>
                </div>
                <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
            `;
                document.body.appendChild(alertDiv);

                setTimeout(() => {
                    if (alertDiv.parentElement) {
                        alertDiv.remove();
                    }
                }, 8000);
            }
        });

        // معالجة حدث رفع الملف
        window.addEventListener('file-uploaded', function(event) {
            console.log('تم رفع الملف:', event.detail);

            // عرض رسالة نجاح فورية
            if (window.Swal) {
                Swal.fire({
                    icon: 'success',
                    title: 'تم رفع الملف بنجاح!',
                    text: event.detail.message || ('تم رفع الملف "' + event.detail.name + '" بنجاح'),
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-start' // تغيير الموضع إلى الأعلى اليسار (فوق السايد بار)
                });
            } else {
                // إشعار بديل إذا لم يكن SweetAlert متاح
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
                alertDiv.style.cssText =
                    'top: 20px; left: 280px; z-index: 9999; min-width: 350px; max-width: 500px;'; // تغيير الموضع
                alertDiv.innerHTML = `
                <i class="mdi mdi-check-circle me-2"></i>
                ${event.detail.message || 'تم رفع الملف بنجاح'}
                <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
            `;
                document.body.appendChild(alertDiv);

                // إزالة الإشعار تلقائياً بعد 4 ثوانٍ
                setTimeout(() => {
                    if (alertDiv.parentElement) {
                        alertDiv.remove();
                    }
                }, 4000);
            }

            // تحديث نص منطقة الرفع
            const dropAreaText = document.getElementById('dropAreaText');
            const dropAreaSubtext = document.getElementById('dropAreaSubtext');
            if (dropAreaText) {
                dropAreaText.textContent = '✅ تم رفع الملف بنجاح';
                dropAreaText.className = 'text-success';
            }
            if (dropAreaSubtext) {
                dropAreaSubtext.textContent = 'الملف جاهز للاستيراد';
                dropAreaSubtext.className = 'text-success';
            }
        });

        // معالجة حدث نجاح الاستيراد
        window.addEventListener('import-success', function(event) {
            console.log('تم الاستيراد بنجاح:', event.detail);

            // عرض رسالة نجاح مفصلة
            if (window.Swal) {
                let htmlContent = '<div class="text-start">';
                htmlContent += '<p><strong>' + event.detail.message + '</strong></p>';
                if (event.detail.success_count !== undefined) {
                    htmlContent += '<hr><div class="row text-center">';
                    htmlContent +=
                        '<div class="col-6"><div class="text-success"><i class="mdi mdi-check-circle mdi-24px"></i><br><strong>' +
                        event.detail.success_count + '</strong><br><small>نجح</small></div></div>';
                    if (event.detail.error_count > 0) {
                        htmlContent +=
                            '<div class="col-6"><div class="text-warning"><i class="mdi mdi-alert-circle mdi-24px"></i><br><strong>' +
                            event.detail.error_count + '</strong><br><small>خطأ</small></div></div>';
                    }
                    htmlContent += '</div>';
                }
                htmlContent += '</div>';

                Swal.fire({
                    icon: 'success',
                    title: '🎉 اكتمل الاستيراد!',
                    html: htmlContent,
                    timer: 5000,
                    timerProgressBar: true,
                    showConfirmButton: true,
                    confirmButtonText: 'ممتاز!',
                    toast: true,
                    position: 'top-start', // فوق السايد بار
                    width: '400px'
                });
            } else {
                // إشعار بديل
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
                alertDiv.style.cssText =
                    'top: 20px; left: 280px; z-index: 9999; min-width: 350px; max-width: 500px;';
                alertDiv.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="mdi mdi-check-circle me-3" style="font-size: 2rem;"></i>
                    <div>
                        <strong>🎉 اكتمل الاستيراد!</strong><br>
                        <small>${event.detail.message}</small>
                    </div>
                </div>
                <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
            `;
                document.body.appendChild(alertDiv);

                setTimeout(() => {
                    if (alertDiv.parentElement) {
                        alertDiv.remove();
                    }
                }, 6000);
            }
        }); // وظيفة لإعادة المحاولة
        function retryImport() {
            if (confirm('هل تريد إعادة محاولة الاستيراد؟')) {
                @this.importData();
            }
        }
    </script>

    <style>
        .bg-light-success {
            background-color: rgba(40, 199, 111, 0.1) !important;
        }

        .border-success {
            border-color: #28c76f !important;
        }

        .file-upload-area:hover {
            border-color: #28c76f !important;
            background-color: rgba(40, 199, 111, 0.05) !important;
        }

        .file-upload-area.border-success {
            animation: pulse-success 2s ease-in-out;
        }

        @keyframes pulse-success {
            0% {
                box-shadow: 0 0 0 0 rgba(40, 199, 111, 0.7);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(40, 199, 111, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(40, 199, 111, 0);
            }
        }

        /* تحسين مظهر الإشعارات المنبثقة */
        .swal2-toast.swal2-show {
            margin-top: 70px !important;
            /* ترك مسافة للهيدر */
            margin-left: 20px !important;
            border-radius: 12px !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12) !important;
        }

        /* تحسين الإشعارات المخصصة */
        .position-fixed.alert {
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            border: none;
            backdrop-filter: blur(10px);
        }

        .position-fixed.alert.alert-success {
            background: linear-gradient(135deg, rgba(40, 199, 111, 0.95), rgba(40, 199, 111, 0.9)) !important;
            color: white;
        }

        .position-fixed.alert.alert-danger {
            background: linear-gradient(135deg, rgba(255, 62, 29, 0.95), rgba(255, 62, 29, 0.9)) !important;
            color: white;
        }

        .position-fixed.alert .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
        }

        .position-fixed.alert .btn-close:hover {
            opacity: 1;
        }

        /* تأثيرات الظهور */
        @keyframes slideInFromLeft {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .position-fixed.alert {
            animation: slideInFromLeft 0.4s ease-out;
        }

        /* تنسيقات Progress Bars للتصدير والاستيراد */
        .card.border-primary {
            border-width: 2px !important;
            animation: pulseCard 2s infinite;
        }

        .card.border-success {
            border-width: 2px !important;
            animation: pulseCardSuccess 2s infinite;
        }

        @keyframes pulseCard {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4);
            }

            50% {
                box-shadow: 0 0 0 10px rgba(13, 110, 253, 0);
            }
        }

        @keyframes pulseCardSuccess {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4);
            }

            50% {
                box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
            }
        }

        .progress {
            border-radius: 15px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .progress-bar {
            border-radius: 15px;
            font-size: 1rem;
            transition: width 0.6s ease;
        }

        .spinner-border {
            animation: spinner-border 0.75s linear infinite;
        }

        /* تحسين عرض الأيقونات في Progress */
        .alert-info i {
            animation: iconPulse 2s infinite;
        }

        @keyframes iconPulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }
        }

        /* تنسيق نسبة التقدم الكبيرة */
        .display-3 {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            animation: progressNumberPulse 1.5s infinite;
        }

        @keyframes progressNumberPulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        /* تحسين مظهر Progress Bar */
        .progress {
            background-color: rgba(0, 0, 0, 0.05);
            overflow: visible;
        }

        .progress-bar {
            position: relative;
            overflow: visible;
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(100%);
            }
        }

        /* تحسين الاستجابة للشاشات الصغيرة */
        @media (max-width: 768px) {
            .alert-info .row>div {
                margin-bottom: 1rem;
            }

            .progress {
                height: 25px !important;
            }

            .spinner-border {
                width: 2rem !important;
                height: 2rem !important;
            }
        }
    </style>
@endpush
