<div class="mt-n4">
    <div class="row">
        <!-- Module Generator Form -->
        <div class="col-lg-8 col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="d-flex align-items-center gap-2">
                        <span class="text-muted d-flex align-items-center">
                            <i class="mdi mdi-auto-fix fs-4"></i>
                            <span class="ms-1">مولد الوحدات</span>
                        </span>
                        <i class="mdi mdi-chevron-left text-primary"></i>
                        <span class="fw-bold text-primary d-flex align-items-center">
                            <i class="mdi mdi-cog-outline me-1"></i>
                            <span class="ms-1">إنشاء وحدة HMVC جديدة</span>
                        </span>
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Module Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-floating form-floating-outline">
                                <input type="text" wire:model="moduleName"
                                    class="form-control @error('moduleName') is-invalid @enderror" id="moduleName"
                                    placeholder="اسم الوحدة بالإنجليزية">
                                <label for="moduleName">اسم الوحدة (بالإنجليزية)</label>
                            </div>
                            @error('moduleName')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating form-floating-outline">
                                <input type="text" wire:model="moduleArName"
                                    class="form-control @error('moduleArName') is-invalid @enderror" id="moduleArName"
                                    placeholder="اسم الوحدة بالعربية">
                                <label for="moduleArName">اسم الوحدة (بالعربية)</label>
                            </div>
                            @error('moduleArName')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <!-- Module Type Selection -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3">
                                <i class="mdi mdi-folder-outline text-info me-1"></i>
                                نوع الوحدة
                            </h5>
                            <div class="alert alert-info">
                                <i class="mdi mdi-information me-1"></i>
                                <strong>اختر نوع الوحدة:</strong> هل تريد إنشاء وحدة فرعية تحت مجموعة موجودة أم إنشاء
                                مجموعة أب جديدة؟
                            </div>
                        </div>

                        <!-- Module Type Radio Buttons -->
                        <div class="col-12 mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check form-check-primary">
                                        <input class="form-check-input" type="radio" wire:model="moduleType"
                                            value="item" id="moduleTypeItem">
                                        <label class="form-check-label" for="moduleTypeItem">
                                            <i class="mdi mdi-file-plus-outline me-2 text-primary"></i>
                                            <strong>عنصر</strong>
                                            <br><small class="text-muted">إضافة عنصر (تحت مجموعة أو مستقل)</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-check-primary">
                                        <input class="form-check-input" type="radio" wire:model="moduleType"
                                            value="group" id="moduleTypeGroup">
                                        <label class="form-check-label" for="moduleTypeGroup">
                                            <i class="mdi mdi-folder-star-outline me-2 text-success"></i>
                                            <strong>مجموعة أساسية</strong>
                                            <br><small class="text-muted">إنشاء مجموعة رئيسية جديدة</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Parent Group Selection (only for item elements under a group) -->
                        @if ($moduleType === 'item')
                            <div class="col-md-12">
                                <div class="form-floating form-floating-outline mb-3">
                                    <select wire:model="parentGroup"
                                        class="form-select @error('parentGroup') is-invalid @enderror" id="parentGroup">
                                        <option value="">-- عنصر مستقل (بدون مجموعة أب) --</option>
                                        @foreach ($availableGroups as $group)
                                            <option value="{{ $group['value'] }}">{{ $group['label'] }}</option>
                                        @endforeach
                                    </select>
                                    <label for="parentGroup">المجموعة الأصلية (اختياري)</label>
                                </div>
                                @error('parentGroup')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror

                                <!-- Item Icon Configuration -->
                                <div class="alert alert-info">
                                    <i class="mdi mdi-information me-1"></i>
                                    <strong>إعدادات العنصر:</strong> يمكنك اختيار أيقونة مخصصة للعنصر لتظهر في القائمة
                                    الجانبية
                                </div>

                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="text-primary mb-3">
                                            <i class="mdi mdi-palette-outline me-2"></i>
                                            إعدادات أيقونة العنصر
                                        </h6>
                                    </div>

                                    <!-- Icon Input -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">أيقونة العنصر</label>
                                            <div class="input-group">
                                                <input type="text" wire:model="itemIcon"
                                                    class="form-control @error('itemIcon') is-invalid @enderror"
                                                    id="itemIcon"
                                                    placeholder="أيقونة العنصر (مثل: mdi mdi-file-outline)">
                                                <button type="button" class="btn btn-outline-secondary"
                                                    wire:click="openIconPicker">
                                                    <i class="mdi mdi-palette me-1"></i>
                                                    اختيار أيقونة
                                                </button>
                                            </div>
                                            @error('itemIcon')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                            <small class="text-muted">
                                                إذا لم تختر أيقونة، ستستخدم الأيقونة الافتراضية (mdi mdi-circle)
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Item Preview -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">معاينة العرض في القائمة</label>
                                            <div class="border rounded p-3 bg-light" style="min-height: 80px;">
                                                <div
                                                    class="d-flex align-items-center justify-content-start p-2 rounded bg-white shadow-sm">
                                                    @if ($itemIcon)
                                                        <i class="{{ $itemIcon }} text-primary me-2 fs-5"></i>
                                                    @else
                                                        <i class="mdi mdi-circle text-muted me-2 fs-5"></i>
                                                    @endif
                                                    <span class="text-dark fw-semibold">
                                                        {{ $moduleArName ?: ($moduleName ?: 'اسم العنصر') }}
                                                    </span>
                                                </div>
                                                <small class="text-muted mt-2 d-block text-center">
                                                    هكذا سيظهر في القائمة الجانبية
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif <!-- Group Configuration (only for group type modules) -->
                        @if ($moduleType === 'group')
                            <div class="col-md-12">
                                <div class="alert alert-success">
                                    <i class="mdi mdi-check-circle me-1"></i>
                                    <strong>إنشاء مجموعة أساسية جديدة:</strong> ستتم إضافة هذه الوحدة كمجموعة رئيسية
                                    جديدة في القائمة الجانبية
                                </div>

                                <!-- Group Icon Section -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="text-primary mb-3">
                                            <i class="mdi mdi-palette-outline me-2"></i>
                                            إعدادات المجموعة الأساسية
                                        </h6>
                                    </div>

                                    <!-- Icon Input and Order -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">أيقونة المجموعة</label>
                                            <div class="input-group">
                                                <input type="text" wire:model="parentGroupIcon"
                                                    class="form-control @error('parentGroupIcon') is-invalid @enderror"
                                                    id="parentGroupIcon" placeholder="أيقونة المجموعة">
                                                <button type="button" class="btn btn-outline-secondary"
                                                    wire:click="openIconPicker">
                                                    <i class="mdi mdi-palette me-1"></i>
                                                    اختيار أيقونة
                                                </button>
                                            </div>
                                            @error('parentGroupIcon')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">ترتيب العرض في القائمة</label>
                                            <div class="input-group">
                                                <input type="number" wire:model="parentGroupOrder"
                                                    class="form-control @error('parentGroupOrder') is-invalid @enderror"
                                                    id="parentGroupOrder" placeholder="ترتيب العرض في القائمة"
                                                    min="0">
                                                <button type="button" class="btn btn-outline-primary"
                                                    wire:click="suggestParentGroupOrder">
                                                    <i class="mdi mdi-auto-fix me-1"></i>
                                                    اقتراح رقم متاح
                                                </button>
                                            </div>
                                            @error('parentGroupOrder')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                            <small class="text-muted">
                                                رقم ترتيب العرض في القائمة الجانبية (يجب أن يكون فريداً)
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Menu Preview -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">معاينة العرض في القائمة</label>
                                            <div class="border rounded p-3 bg-light" style="min-height: 80px;">
                                                <div
                                                    class="d-flex align-items-center justify-content-start p-2 rounded bg-white shadow-sm">
                                                    @if ($iconPreview)
                                                        <i class="{{ $iconPreview }} text-primary me-2 fs-5"></i>
                                                    @else
                                                        <i class="mdi mdi-folder text-muted me-2 fs-5"></i>
                                                    @endif
                                                    <span class="text-dark fw-semibold">
                                                        {{ $moduleArName ?: ($moduleName ?: 'اسم المجموعة') }}
                                                    </span>
                                                </div>
                                                <small class="text-muted mt-2 d-block text-center">
                                                    هكذا ستظهر في القائمة الجانبية
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <hr>

                    <!-- Advanced Features Section -->
                    <h5 class="mb-3">
                        <i class="mdi mdi-cog text-info me-1"></i>
                        الميزات المتقدمة
                    </h5>

                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="form-check form-check-primary">
                                <input class="form-check-input" type="checkbox" wire:model="enableExcelExport"
                                    id="enableExcelExport">
                                <label class="form-check-label" for="enableExcelExport">
                                    <i class="mdi mdi-file-excel text-success me-1"></i>
                                    تصدير Excel
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check form-check-primary">
                                <input class="form-check-input" type="checkbox" wire:model="enablePdfExport"
                                    id="enablePdfExport">
                                <label class="form-check-label" for="enablePdfExport">
                                    <i class="mdi mdi-file-pdf text-danger me-1"></i>
                                    طباعة PDF
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check form-check-primary">
                                <input class="form-check-input" type="checkbox" wire:model="enableFlatpickr"
                                    id="enableFlatpickr">
                                <label class="form-check-label" for="enableFlatpickr">
                                    <i class="mdi mdi-calendar text-primary me-1"></i>
                                    Flatpickr للتاريخ
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check form-check-primary">
                                <input class="form-check-input" type="checkbox" wire:model="enableSelect2"
                                    id="enableSelect2">
                                <label class="form-check-label" for="enableSelect2">
                                    <i class="mdi mdi-format-list-bulleted text-warning me-1"></i>
                                    Select للقوائم
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Add New Field Section -->
                    <h5 class="mb-3">
                        <i class="mdi mdi-plus-circle text-primary me-1"></i>
                        إضافة حقل جديد
                    </h5>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-floating form-floating-outline">
                                <input type="text" wire:model="newField.name"
                                    class="form-control @error('newField.name') is-invalid @enderror" id="fieldName"
                                    placeholder="اسم الحقل">
                                <label for="fieldName">اسم الحقل (English)</label>
                            </div>
                            @error('newField.name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating form-floating-outline">
                                <input type="text" wire:model="newField.ar_name"
                                    class="form-control @error('newField.ar_name') is-invalid @enderror"
                                    id="fieldArName" placeholder="الاسم العربي">
                                <label for="fieldArName">الاسم العربي</label>
                            </div>
                            @error('newField.ar_name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating form-floating-outline">
                                <select wire:model="newField.type"
                                    class="form-select @error('newField.type') is-invalid @enderror" id="fieldType"
                                    onchange="handleFieldTypeChange()">
                                    @foreach ($fieldTypes as $type => $label)
                                        <option value="{{ $type }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <label for="fieldType">نوع الحقل</label>
                            </div>
                            @error('newField.type')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <!-- Field Options -->
                    <div class="row mb-3">
                        @if ($newField['type'] === 'string')
                            <div class="col-md-3">
                                <div class="form-floating form-floating-outline">
                                    <input type="number" wire:model="newField.size" class="form-control"
                                        id="fieldSize" placeholder="الحجم">
                                    <label for="fieldSize">حجم الحقل</label>
                                </div>
                            </div>
                        @endif

                        <div class="col-md-3">
                            <div class="form-check form-check-primary mt-3">
                                <input class="form-check-input" type="checkbox" wire:model="newField.required"
                                    id="fieldRequired">
                                <label class="form-check-label" for="fieldRequired">مطلوب</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check form-check-primary mt-3">
                                <input class="form-check-input" type="checkbox" wire:model="newField.unique"
                                    id="fieldUnique">
                                <label class="form-check-label" for="fieldUnique">فريد</label>
                            </div>
                        </div>
                        @if ($newField['type'] !== 'file')
                            <div class="col-md-3">
                                <div class="form-check form-check-primary mt-3">
                                    <input class="form-check-input" type="checkbox" wire:model="newField.searchable"
                                        id="fieldSearchable">
                                    <label class="form-check-label" for="fieldSearchable">قابل للبحث</label>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Additional Options Row -->
                    <div class="row mb-3">
                        @if ($newField['type'] === 'string')
                            <div class="col-md-4">
                                <div class="form-floating form-floating-outline">
                                    <select wire:model="newField.text_content_type" class="form-select" id="fieldTextContentType">
                                        <option value="any">جميع الأحرف</option>
                                        <option value="arabic_only">عربي فقط</option>
                                        <option value="numeric_only">أرقام فقط</option>
                                        <option value="english_only">إنجليزي فقط</option>
                                    </select>
                                    <label for="fieldTextContentType">نوع المحتوى المسموح</label>
                                </div>
                            </div>
                        @endif

                        @if ($newField['type'] === 'integer')
                            <div class="col-md-4">
                                <div class="form-floating form-floating-outline">
                                    <select wire:model="newField.integer_type" class="form-select" id="fieldIntegerType">
                                        <option value="int">INT (عادي)</option>
                                        <option value="bigint">BIGINT (كبير)</option>
                                        <option value="smallint">SMALLINT (صغير)</option>
                                        <option value="tinyint">TINYINT (صغير جداً)</option>
                                    </select>
                                    <label for="fieldIntegerType">نوع الرقم الصحيح</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-check-primary mt-3">
                                    <input class="form-check-input" type="checkbox" wire:model="newField.unsigned" id="fieldUnsigned">
                                    <label class="form-check-label" for="fieldUnsigned">موجب فقط (UNSIGNED)</label>
                                </div>
                            </div>
                        @endif

                        @if ($newField['type'] === 'decimal')
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input type="number" wire:model="newField.decimal_precision" class="form-control"
                                           id="fieldDecimalPrecision" min="1" max="65" value="15">
                                    <label for="fieldDecimalPrecision">إجمالي الأرقام (Precision)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input type="number" wire:model="newField.decimal_scale" class="form-control"
                                           id="fieldDecimalScale" min="0" max="30" value="2">
                                    <label for="fieldDecimalScale">المراتب العشرية (Scale)</label>
                                </div>
                            </div>
                        @endif

                        @if ($newField['type'] === 'file')
                            <div class="col-md-4">
                                <div class="form-floating form-floating-outline">
                                    <select wire:model="newField.file_types" class="form-select" id="fieldFileTypes">
                                        <option value="">جميع الأنواع</option>
                                        <option value="image">صور فقط</option>
                                        <option value="pdf">PDF فقط</option>
                                        <option value="document">مستندات</option>
                                    </select>
                                    <label for="fieldFileTypes">نوع الملفات المسموحة</label>
                                </div>
                            </div>
                        @endif

                        @if ($newField['type'] === 'checkbox')
                            <div class="col-md-12 mt-3">
                                <h6 class="text-primary">إعداد تسميات الحقل</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating form-floating-outline">
                                            <input type="text" wire:model="newField.checkbox_true_label"
                                                class="form-control" id="checkboxTrueLabel"
                                                placeholder="التسمية عند التفعيل">
                                            <label for="checkboxTrueLabel">التسمية عند التفعيل (مثل: مفعل، نعم،
                                                موافق)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating form-floating-outline">
                                            <input type="text" wire:model="newField.checkbox_false_label"
                                                class="form-control" id="checkboxFalseLabel"
                                                placeholder="التسمية عند عدم التفعيل">
                                            <label for="checkboxFalseLabel">التسمية عند عدم التفعيل (مثل: غير مفعل، لا،
                                                لا أوافق)</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($newField['type'] === 'select')
                            <div class="col-md-12 mt-3">
                                <h6 class="text-primary">إعداد القائمة المنسدلة</h6>

                                <!-- نوع مصدر البيانات -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="form-check form-check-primary">
                                            <input class="form-check-input" type="radio"
                                                wire:model="newField.select_source" value="manual"
                                                id="selectSourceManual">
                                            <label class="form-check-label" for="selectSourceManual">
                                                <i class="mdi mdi-keyboard me-1"></i>
                                                إدخال يدوي للخيارات
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-check-primary">
                                            <input class="form-check-input" type="radio"
                                                wire:model="newField.select_source" value="database"
                                                id="selectSourceDatabase">
                                            <label class="form-check-label" for="selectSourceDatabase">
                                                <i class="mdi mdi-database me-1"></i>
                                                ربط بجدول آخر
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                @if ($newField['select_source'] === 'manual')
                                    <!-- الإدخال اليدوي -->
                                    <div class="row mb-2">
                                        <div class="col-md-10">
                                            <div class="form-floating form-floating-outline">
                                                <input type="text" class="form-control" id="newSelectOption"
                                                    placeholder="أدخل خيار جديد"
                                                    onkeypress="if(event.key === 'Enter') { event.preventDefault(); addNewSelectOption(); }">
                                                <label for="newSelectOption">خيار جديد</label>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-primary h-100"
                                                onclick="addNewSelectOption()">
                                                <i class="mdi mdi-plus"></i> إضافة
                                            </button>
                                        </div>
                                    </div>

                                    <!-- عرض الخيارات المضافة -->
                                    @if (count($newField['select_options']) > 0)
                                        <div class="mt-3">
                                            <h6 class="text-muted">الخيارات المضافة:</h6>
                                            <div class="row">
                                                @foreach ($newField['select_options'] as $index => $option)
                                                    <div class="col-md-6 mb-2">
                                                        <div
                                                            class="alert alert-info d-flex justify-content-between align-items-center py-2">
                                                            <span>{{ $option }}</span>
                                                            <button type="button"
                                                                class="btn btn-sm btn-outline-danger"
                                                                wire:click="removeSelectOption({{ $index }})">
                                                                <i class="mdi mdi-delete-outline"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    <!-- ربط بجدول -->
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-floating form-floating-outline">
                                                <select wire:model="newField.related_table"
                                                    onchange="updateTableColumns(this.value)" class="form-select"
                                                    id="relatedTable">
                                                    <option value="">اختر الجدول</option>
                                                    @if (count($availableTables) > 0)
                                                        @foreach ($availableTables as $table)
                                                            <option value="{{ $table }}">{{ $table }}
                                                            </option>
                                                        @endforeach
                                                    @else
                                                        <option disabled>لا توجد جداول متاحة</option>
                                                    @endif
                                                </select>
                                                <label for="relatedTable">اسم الجدول</label>
                                            </div>
                                            @if (count($availableTables) == 0)
                                                <small class="text-warning mt-1">
                                                    <i class="mdi mdi-alert"></i>
                                                    لا توجد جداول متاحة في قاعدة البيانات
                                                </small>
                                            @endif
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-floating form-floating-outline">
                                                <select wire:model="newField.related_key" class="form-select"
                                                    id="relatedKey">
                                                    <option value="">اختر المفتاح</option>
                                                    <option value="id">id</option>
                                                    @foreach ($selectedTableColumns as $column)
                                                        <option value="{{ $column }}">{{ $column }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <label for="relatedKey">حقل المفتاح</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-floating form-floating-outline">
                                                <select wire:model="newField.related_display" class="form-select"
                                                    id="relatedDisplay">
                                                    <option value="">اختر حقل العرض</option>
                                                    @foreach ($selectedTableColumns as $column)
                                                        <option value="{{ $column }}">{{ $column }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <label for="relatedDisplay">حقل العرض</label>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- خيار للقيم الرقمية في select -->
                                <div class="col-md-12 mt-3">
                                    <div class="form-check form-check-primary">
                                        <input class="form-check-input" type="checkbox" wire:model="newField.select_numeric_values"
                                            id="selectNumericValues">
                                        <label class="form-check-label" for="selectNumericValues">
                                            <i class="mdi mdi-numeric text-primary me-1"></i>
                                            قيم رقمية (سيتم معاملة قيم هذه القائمة كأرقام في العمليات الحسابية)
                                        </label>
                                        <small class="text-muted d-block mt-1">
                                            <i class="mdi mdi-information-outline me-1"></i>
                                            فعّل هذا الخيار إذا كانت قيم القائمة المنسدلة أرقام (مثل: نسب، مبالغ، درجات)
                                        </small>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- نظام العمليات الحسابية للحقول الرقمية -->
                        @if ($newField['type'] === 'integer' || $newField['type'] === 'decimal')
                            <div class="col-12 mt-4">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-gradient-primary text-white">
                                        <h6 class="mb-0 d-flex align-items-center fs-6">
                                            <i class="mdi mdi-calculator me-2"></i>
                                            العمليات الحسابية المتقدمة
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check form-switch mb-4">
                                            <input class="form-check-input" type="checkbox" wire:model="newField.is_calculated"
                                                id="fieldIsCalculated" style="transform: scale(1.1);">
                                            <label class="form-check-label fw-semibold text-dark fs-6" for="fieldIsCalculated">
                                                <i class="mdi mdi-function-variant text-info me-2"></i>
                                                تفعيل الحساب التلقائي للحقل
                                                <small class="d-block text-muted mt-1">سيتم حساب قيمة هذا الحقل تلقائياً بناءً على حقول أخرى</small>
                                            </label>
                                        </div>

                                        @if ($newField['is_calculated'] ?? false)
                                            <!-- نوع الحساب -->
                                            <div class="calculation-type-selection mb-4">
                                                <h6 class="text-primary mb-3">
                                                    <i class="mdi mdi-cog me-2"></i>
                                                    نوع الحساب
                                                    @if ($newField['type'] === 'integer')
                                                        <span class="badge bg-success ms-2">رقم صحيح - جميع الخيارات متاحة</span>
                                                    @elseif ($newField['type'] === 'decimal')
                                                        <span class="badge bg-warning ms-2">رقم عشري - بدون فرق التواريخ</span>
                                                    @endif
                                                </h6>
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" wire:model="newField.calculation_type"
                                                                   value="formula" id="calcTypeFormula">
                                                            <label class="form-check-label fw-semibold" for="calcTypeFormula">
                                                                <i class="mdi mdi-function-variant text-primary me-2"></i>
                                                                معادلة حسابية
                                                                <small class="d-block text-muted">عمليات رياضية بين الحقول</small>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    @if ($newField['type'] === 'integer')
                                                        <div class="col-md-4">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" wire:model="newField.calculation_type"
                                                                       value="date_diff" id="calcTypeDateDiff">
                                                                <label class="form-check-label fw-semibold" for="calcTypeDateDiff">
                                                                    <i class="mdi mdi-calendar-clock text-success me-2"></i>
                                                                    فرق التواريخ
                                                                    <small class="d-block text-muted">حساب الفرق بين تاريخين (للأرقام الصحيحة فقط)</small>
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" wire:model="newField.calculation_type"
                                                                       value="time_diff" id="calcTypeTimeDiff">
                                                                <label class="form-check-label fw-semibold" for="calcTypeTimeDiff">
                                                                    <i class="mdi mdi-clock-outline text-warning me-2"></i>
                                                                    فرق الأوقات
                                                                    <small class="d-block text-muted">حساب الفرق بين وقتين (للأرقام الصحيحة فقط)</small>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    @endif
                                                    <div class="col-md-4">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" wire:model="newField.calculation_type"
                                                                   value="none" id="calcTypeNone">
                                                            <label class="form-check-label fw-semibold" for="calcTypeNone">
                                                                <i class="mdi mdi-numeric text-secondary me-2"></i>
                                                                رقم عادي
                                                                <small class="d-block text-muted">حقل رقمي عادي بدون حساب</small>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- ملاحظة توضيحية -->
                                                <div class="alert alert-info border-0 mb-3">
                                                    <small class="d-block">
                                                        <i class="mdi mdi-information me-1"></i>
                                                        <strong>ملاحظة:</strong>
                                                        @if ($newField['type'] === 'integer')
                                                            خيار "فرق التواريخ" متاح فقط للحقول الصحيحة لأنها تحتاج لإرجاع أرقام صحيحة (مثل عدد الأيام/الأشهر/السنوات).
                                                        @elseif ($newField['type'] === 'decimal')
                                                            خيار "فرق التواريخ" غير متاح للحقول العشرية حيث أن حساب فرق التواريخ يرجع أرقام صحيحة فقط.
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>

                                            <!-- واجهة المعادلة الحسابية -->
                                            @if(($newField['calculation_type'] ?? 'none') === 'formula')
                                                <div class="calculation-builder">
                                                    <!-- معاينة المعادلة -->
                                                    <div class="alert alert-primary border-0 mb-3">
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <div class="w-100">
                                                                <h6 class="alert-heading mb-2 fs-6">
                                                                    <i class="mdi mdi-function me-2"></i>
                                                                    المعادلة الحالية
                                                                </h6>
                                                                <div class="formula-preview bg-white rounded p-2 border">
                                                                    <code class="text-dark fw-semibold fs-6" id="calculationPreview">
                                                                        {{ $newField['calculation_formula'] ?? 'قم ببناء المعادلة باستخدام الأدوات أدناه' }}
                                                                    </code>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- واجهة حساب فرق التواريخ -->
                                            @if(($newField['calculation_type'] ?? 'none') === 'date_diff')
                                                <div class="date-calculation-builder">
                                                    <!-- معاينة حساب التاريخ -->
                                                    <div class="alert alert-primary border-0 mb-3">
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <div class="w-100">
                                                                <h6 class="alert-heading mb-2 fs-6">
                                                                    <i class="mdi mdi-calendar-clock me-2"></i>
                                                                    معاينة حساب التاريخ
                                                                </h6>
                                                                <div class="preview-text bg-white rounded p-2 border">
                                                                    @if($newField['date_from_field'] && $newField['date_to_field'])
                                                                        <code class="text-dark fw-semibold fs-6">
                                                                            حساب الفرق بين {{ $newField['date_from_field'] }} و {{ $newField['date_to_field'] }}
                                                                            @if($newField['remaining_only'] ?? false)
                                                                                @if(($newField['date_diff_unit'] ?? 'days') === 'days')
                                                                                    - الأيام المتبقية (بعد استخراج السنوات والأشهر)
                                                                                @elseif(($newField['date_diff_unit'] ?? 'days') === 'months')
                                                                                    - الأشهر المتبقية (بعد استخراج السنوات)
                                                                                @else
                                                                                    - السنوات فقط
                                                                                @endif
                                                                            @else
                                                                                @if(($newField['date_diff_unit'] ?? 'days') === 'days')
                                                                                    - إجمالي عدد الأيام
                                                                                @elseif(($newField['date_diff_unit'] ?? 'days') === 'months')
                                                                                    - إجمالي عدد الأشهر
                                                                                @else
                                                                                    - إجمالي عدد السنوات
                                                                                @endif
                                                                            @endif
                                                                            @if($newField['include_end_date'] ?? false) + شمل اليوم النهائي @endif
                                                                            @if($newField['absolute_value'] ?? false) (قيمة مطلقة) @endif
                                                                        </code>
                                                                    @else
                                                                        <span class="text-muted">اختر حقول التاريخ لرؤية معاينة الحساب</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- أدوات بناء حساب التاريخ -->
                                                    <div class="row g-3 mb-4">
                                                        <!-- اختيار حقول التاريخ -->
                                                        <div class="col-lg-6">
                                                            <div class="card border-success h-100">
                                                                <div class="card-header bg-light-success">
                                                                    <h6 class="mb-0 text-success fs-6">
                                                                        <i class="mdi mdi-calendar-range me-2"></i>
                                                                        حقول التاريخ
                                                                    </h6>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="row g-3">
                                                                        <div class="col-12">
                                                                            <label class="form-label fw-semibold text-success">
                                                                                <i class="mdi mdi-calendar-start me-1"></i>
                                                                                التاريخ من:
                                                                            </label>
                                                                            <select class="form-select" wire:model="newField.date_from_field">
                                                                                <option value="">اختر حقل التاريخ من</option>
                                                                                @foreach($fields as $field)
                                                                                    @if($field['type'] === 'date' || $field['type'] === 'datetime')
                                                                                        <option value="{{ $field['name'] }}">{{ $field['ar_name'] }} ({{ $field['name'] }}){{ $field['type'] === 'datetime' ? ' - تاريخ ووقت' : '' }}</option>
                                                                                    @endif
                                                                                @endforeach
                                                                            </select>
                                                                        </div>
                                                                        <div class="col-12">
                                                                            <label class="form-label fw-semibold text-success">
                                                                                <i class="mdi mdi-calendar-end me-1"></i>
                                                                                التاريخ إلى:
                                                                            </label>
                                                                            <select class="form-select" wire:model="newField.date_to_field">
                                                                                <option value="">اختر حقل التاريخ إلى</option>
                                                                                @foreach($fields as $field)
                                                                                    @if($field['type'] === 'date' || $field['type'] === 'datetime')
                                                                                        <option value="{{ $field['name'] }}">{{ $field['ar_name'] }} ({{ $field['name'] }}){{ $field['type'] === 'datetime' ? ' - تاريخ ووقت' : '' }}</option>
                                                                                    @endif
                                                                                @endforeach
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- إعدادات وخيارات الحساب -->
                                                        <div class="col-lg-6">
                                                            <div class="card border-info h-100">
                                                                <div class="card-header bg-light-info">
                                                                    <h6 class="mb-0 text-info fs-6">
                                                                        <i class="mdi mdi-cog me-2"></i>
                                                                        إعدادات وخيارات الحساب
                                                                    </h6>
                                                                </div>
                                                                <div class="card-body">
                                                                    <!-- وحدة القياس -->
                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-semibold text-info">
                                                                            <i class="mdi mdi-ruler me-1"></i>
                                                                            وحدة القياس:
                                                                        </label>
                                                                        <select class="form-select" wire:model="newField.date_diff_unit">
                                                                            <option value="days">أيام</option>
                                                                            <option value="months">أشهر</option>
                                                                            <option value="years">سنوات</option>
                                                                        </select>
                                                                    </div>

                                                                    <!-- الخيارات الإضافية -->
                                                                    <div class="border-top pt-3 date-diff-options">
                                                                        <h6 class="text-warning mb-2 fs-6">
                                                                            <i class="mdi mdi-tune me-1"></i>
                                                                            خيارات إضافية:
                                                                        </h6>

                                                                        <!-- المتبقي فقط - يظهر للأيام والأشهر -->
                                                                        @if(($newField['date_diff_unit'] ?? 'days') === 'days' || ($newField['date_diff_unit'] ?? 'days') === 'months')
                                                                            <div class="form-check mb-2 available">
                                                                                <input class="form-check-input" type="checkbox" wire:model="newField.remaining_only" id="remainingOnly">
                                                                                <label class="form-check-label fw-semibold" for="remainingOnly">
                                                                                    <i class="mdi mdi-clock-time-four text-warning me-1"></i>
                                                                                    المتبقي فقط
                                                                                    <small class="d-block text-muted">
                                                                                        @if(($newField['date_diff_unit'] ?? 'days') === 'days')
                                                                                            الأيام المتبقية من الشهر
                                                                                        @elseif(($newField['date_diff_unit'] ?? 'days') === 'months')
                                                                                            الأشهر المتبقية من السنة
                                                                                        @endif
                                                                                    </small>
                                                                                </label>
                                                                            </div>
                                                                        @endif

                                                                        <!-- شمل التاريخ النهائي - يظهر للأيام فقط -->
                                                                        @if(($newField['date_diff_unit'] ?? 'days') === 'days')
                                                                            <div class="form-check mb-2 available">
                                                                                <input class="form-check-input" type="checkbox" wire:model="newField.include_end_date" id="includeEndDate">
                                                                                <label class="form-check-label fw-semibold" for="includeEndDate">
                                                                                    <i class="mdi mdi-calendar-plus text-success me-1"></i>
                                                                                    شمل التاريخ النهائي
                                                                                    <small class="d-block text-muted">يؤثر على دقة الحساب</small>
                                                                                </label>
                                                                            </div>
                                                                        @endif

                                                                        <!-- قيمة مطلقة - يظهر لجميع الوحدات -->
                                                                        <div class="form-check mb-0 available">
                                                                            <input class="form-check-input" type="checkbox" wire:model="newField.absolute_value" id="absoluteValue">
                                                                            <label class="form-check-label fw-semibold" for="absoluteValue">
                                                                                <i class="mdi mdi-plus-minus text-primary me-1"></i>
                                                                                قيمة مطلقة (موجبة دائماً)
                                                                                <small class="d-block text-muted">تجاهل الإشارة السالبة</small>
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- واجهة حساب فرق الأوقات -->
                                            @if(($newField['calculation_type'] ?? 'none') === 'time_diff')
                                                <div class="time-calculation-builder">
                                                    <!-- معاينة حساب الوقت -->
                                                    <div class="alert alert-warning border-0 mb-4">
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <div class="w-100">
                                                                <h6 class="alert-heading mb-2">
                                                                    <i class="mdi mdi-clock-outline me-2"></i>
                                                                    معاينة حساب فرق الأوقات
                                                                </h6>
                                                                <div class="time-preview bg-white rounded p-2 border">
                                                                    @if (!empty($newField['time_from_field']) && !empty($newField['time_to_field']))
                                                                        <strong class="text-warning">
                                                                            حساب الفرق بين {{ $newField['time_from_field'] }} و {{ $newField['time_to_field'] }}
                                                                            @if ($newField['time_diff_unit'] === 'hours')
                                                                                بالساعات
                                                                            @else
                                                                                بالدقائق
                                                                            @endif
                                                                            @if ($newField['absolute_value'])
                                                                                (قيمة مطلقة)
                                                                            @endif

                                                                            @if ($newField['remaining_only'] && $newField['time_diff_unit'] === 'minutes')
                                                                                - الدقائق المتبقية فقط
                                                                            @endif

                                                                        </strong>
                                                                    @else
                                                                        <span class="text-muted">اختر حقلي الوقت للمعاينة</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- أدوات بناء حساب الوقت -->
                                                    <div class="row g-3">
                                                        <!-- اختيار حقول الوقت -->
                                                        <div class="col-lg-6">
                                                            <div class="card border-warning h-100">
                                                                <div class="card-header bg-light-warning py-2">
                                                                    <h6 class="mb-0 text-warning small">
                                                                        <i class="mdi mdi-clock me-2"></i>
                                                                        اختيار حقول الوقت
                                                                    </h6>
                                                                </div>
                                                                <div class="card-body">
                                                                    <!-- الوقت من -->
                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-semibold text-warning small">
                                                                            <i class="mdi mdi-clock-start me-1"></i>
                                                                            الوقت من:
                                                                        </label>
                                                                        <select class="form-select form-select-sm"
                                                                            wire:model="newField.time_from_field">
                                                                            <option value="">-- اختر حقل الوقت الأول --</option>
                                                                            @foreach ($fields as $field)
                                                                                @if (($field['type'] === 'time' || $field['type'] === 'datetime') && $field['name'] !== ($newField['name'] ?? ''))
                                                                                    <option value="{{ $field['name'] }}">
                                                                                        {{ $field['ar_name'] }} ({{ $field['name'] }}){{ $field['type'] === 'datetime' ? ' - تاريخ ووقت' : '' }}
                                                                                    </option>
                                                                                @endif
                                                                            @endforeach
                                                                        </select>
                                                                        <small class="text-muted">حقل الوقت المرجعي الأول</small>
                                                                    </div>

                                                                    <!-- الوقت إلى -->
                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-semibold text-warning small">
                                                                            <i class="mdi mdi-clock-end me-1"></i>
                                                                            الوقت إلى:
                                                                        </label>
                                                                        <select class="form-select form-select-sm"
                                                                            wire:model="newField.time_to_field">
                                                                            <option value="">-- اختر حقل الوقت الثاني --</option>
                                                                            @foreach ($fields as $field)
                                                                                @if (($field['type'] === 'time' || $field['type'] === 'datetime') && $field['name'] !== ($newField['name'] ?? ''))
                                                                                    <option value="{{ $field['name'] }}">
                                                                                        {{ $field['ar_name'] }} ({{ $field['name'] }}){{ $field['type'] === 'datetime' ? ' - تاريخ ووقت' : '' }}
                                                                                    </option>
                                                                                @endif
                                                                            @endforeach
                                                                        </select>
                                                                        <small class="text-muted">حقل الوقت المرجعي الثاني</small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- إعدادات وخيارات الحساب -->
                                                        <div class="col-lg-6">
                                                            <div class="card border-info h-100">
                                                                <div class="card-header bg-light-info py-2">
                                                                    <h6 class="mb-0 text-info small">
                                                                        <i class="mdi mdi-cog me-2"></i>
                                                                        إعدادات وخيارات الحساب
                                                                    </h6>
                                                                </div>
                                                                <div class="card-body">
                                                                    <!-- وحدة القياس -->
                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-semibold text-info small">
                                                                            <i class="mdi mdi-ruler me-1"></i>
                                                                            وحدة القياس:
                                                                        </label>
                                                                        <select class="form-select form-select-sm"
                                                                            wire:model="newField.time_diff_unit">
                                                                            <option value="minutes">دقائق</option>
                                                                            <option value="hours">ساعات</option>
                                                                        </select>
                                                                    </div>

                                                                    <!-- الخيارات الإضافية -->
                                                                    <div class="border-top pt-3">
                                                                        <h6 class="text-warning mb-2 small">
                                                                            <i class="mdi mdi-tune me-1"></i>
                                                                            خيارات إضافية:
                                                                        </h6>

                                                                        <!-- قيمة مطلقة -->
                                                                        <div class="form-check mb-2">
                                                                            <input class="form-check-input" type="checkbox"
                                                                                wire:model="newField.absolute_value"
                                                                                id="timeAbsoluteValue">
                                                                            <label class="form-check-label fw-semibold small"
                                                                                for="timeAbsoluteValue">
                                                                                <i class="mdi mdi-plus-minus text-primary me-1"></i>
                                                                                قيمة مطلقة (موجبة دائماً)
                                                                                <small class="d-block text-muted">تجاهل الإشارة السالبة</small>
                                                                            </label>
                                                                        </div>

                                                                        <!-- المتبقي فقط - للدقائق فقط -->
                                                                        @if(($newField['time_diff_unit'] ?? 'minutes') === 'minutes')
                                                                            <div class="form-check mb-2">
                                                                                <input class="form-check-input" type="checkbox"
                                                                                    wire:model="newField.remaining_only"
                                                                                    id="timeRemainingOnly">
                                                                                <label class="form-check-label fw-semibold small" for="timeRemainingOnly">
                                                                                    <i class="mdi mdi-clock-time-four text-warning me-1"></i>
                                                                                    الدقائق المتبقية فقط
                                                                                    <small class="d-block text-muted">
                                                                                        الدقائق المتبقية بعد استخراج الساعات الكاملة
                                                                                        <br><strong class="text-info">مثال:</strong> 75 دقيقة → 15 دقيقة
                                                                                    </small>
                                                                                </label>
                                                                            </div>
                                                                        @endif


                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- أمثلة وإرشادات -->
                                                    <div class="mt-3">
                                                        <div class="card border-0 bg-light">
                                                            <div class="card-body py-2">
                                                                <h6 class="text-primary mb-2 small">
                                                                    <i class="mdi mdi-lightbulb-outline me-2"></i>
                                                                    أمثلة على حسابات الوقت
                                                                </h6>
                                                                <div class="row g-2">
                                                                    <div class="col-md-4">
                                                                        <div class="example-card p-2 bg-white rounded border">
                                                                            <div class="fw-bold text-success mb-1 small">ساعات العمل</div>
                                                                            <code class="d-block small">start_time → end_time (ساعات)</code>
                                                                            <small class="text-muted">حساب ساعات العمل اليومية</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <div class="example-card p-2 bg-white rounded border">
                                                                            <div class="fw-bold text-info mb-1 small">فترة الاستراحة</div>
                                                                            <code class="d-block small">break_start → break_end (دقائق)</code>
                                                                            <small class="text-muted">حساب دقائق الاستراحة</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <div class="example-card p-2 bg-white rounded border">
                                                                            <div class="fw-bold text-warning mb-1 small">وقت التأخير</div>
                                                                            <code class="d-block small">scheduled_time → actual_time (دقائق)</code>
                                                                            <small class="text-muted">حساب دقائق التأخير</small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            @if(($newField['calculation_type'] ?? 'none') === 'formula')
                                                <div class="calculation-builder">
                                                    <!-- أدوات بناء المعادلة -->
                                                    <div class="row g-3">
                                                        <!-- اختيار الحقول -->
                                                        <div class="col-lg-4">
                                                            <div class="card border-info h-100">
                                                                <div class="card-header bg-light-info">
                                                                    <h6 class="mb-0 text-info fs-6">
                                                                        <i class="mdi mdi-database me-2"></i>
                                                                        الحقول المتاحة
                                                                    </h6>
                                                                </div>
                                                                <div class="card-body">
                                                                    <select class="form-select" id="availableFields"
                                                                            onchange="addFieldToFormula(this.value)">
                                                                        <option value="">-- اختر حقل رقمي --</option>
                                                                        @foreach ($fields as $index => $field)
                                                                            @if (
                                                                                (($field['type'] === 'integer' || $field['type'] === 'decimal') ||
                                                                                ($field['type'] === 'select' && isset($field['select_numeric_values']) && $field['select_numeric_values'])) &&
                                                                                $field['name'] !== ($newField['name'] ?? '')
                                                                            )
                                                                                <option value="{{ $field['name'] }}">
                                                                                    {{ $field['ar_name'] }} ({{ $field['name'] }})
                                                                                    @if($field['type'] === 'select')
                                                                                        - قائمة منسدلة رقمية
                                                                                    @endif
                                                                                </option>
                                                                            @endif
                                                                        @endforeach
                                                                    </select>
                                                                    <small class="text-muted mt-1 d-block">انقر لإضافة الحقل للمعادلة</small>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- العمليات الحسابية -->
                                                        <div class="col-lg-4">
                                                            <div class="card border-success h-100">
                                                                <div class="card-header bg-light-success">
                                                                    <h6 class="mb-0 text-success fs-6">
                                                                        <i class="mdi mdi-calculator-variant me-2"></i>
                                                                        العمليات الحسابية
                                                                    </h6>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="d-grid gap-2">
                                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                                                onclick="addOperatorToFormula('+')">
                                                                            <i class="mdi mdi-plus-circle me-1"></i>
                                                                            جمع (+)
                                                                        </button>
                                                                        <button type="button" class="btn btn-outline-danger btn-sm"
                                                                                onclick="addOperatorToFormula('-')">
                                                                            <i class="mdi mdi-minus-circle me-1"></i>
                                                                            طرح (-)
                                                                        </button>
                                                                        <button type="button" class="btn btn-outline-success btn-sm"
                                                                                onclick="addOperatorToFormula('*')">
                                                                            <i class="mdi mdi-close-circle me-1"></i>
                                                                            ضرب (×)
                                                                        </button>
                                                                        <button type="button" class="btn btn-outline-warning btn-sm"
                                                                                onclick="addOperatorToFormula('/')">
                                                                            <i class="mdi mdi-division me-1"></i>
                                                                            قسمة (÷)
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- أدوات إضافية -->
                                                        <div class="col-lg-4">
                                                            <div class="card border-warning h-100">
                                                                <div class="card-header bg-light-warning">
                                                                    <h6 class="mb-0 text-warning fs-6">
                                                                        <i class="mdi mdi-tools me-2"></i>
                                                                        أدوات إضافية
                                                                    </h6>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="d-grid gap-2">
                                                                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                                                                onclick="addOperatorToFormula('(')">
                                                                            <i class="mdi mdi-code-parentheses me-1"></i>
                                                                            قوس فتح (
                                                                        </button>
                                                                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                                                                onclick="addOperatorToFormula(')')">
                                                                            <i class="mdi mdi-code-parentheses me-1"></i>
                                                                            قوس إغلاق )
                                                                        </button>
                                                                        <button type="button" class="btn btn-outline-info btn-sm"
                                                                                onclick="showNumberInputModal()">
                                                                            <i class="mdi mdi-numeric me-1"></i>
                                                                            رقم ثابت
                                                                        </button>
                                                                        <button type="button" class="btn btn-outline-danger btn-sm"
                                                                                onclick="showClearConfirmModal()">
                                                                            <i class="mdi mdi-delete-sweep me-1"></i>
                                                                            مسح الكل
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- حقل المعادلة المخفي -->
                                                    <input type="hidden" wire:model="newField.calculation_formula" id="calculationFormula">

                                                    <!-- عرض أخطاء المعادلة -->
                                                    @error('newField.calculation_formula')
                                                        <div class="alert alert-danger border-0 mt-3">
                                                            <i class="mdi mdi-alert-circle me-2"></i>
                                                            {{ $message }}
                                                        </div>
                                                    @enderror

                                                    <!-- أمثلة وإرشادات -->
                                                    <div class="mt-3">
                                                        <div class="card border-0 bg-light">
                                                            <div class="card-body">
                                                                <h6 class="text-primary mb-2 fs-6">
                                                                    <i class="mdi mdi-lightbulb-outline me-2"></i>
                                                                    أمثلة على المعادلات
                                                                </h6>
                                                                <div class="row g-2">
                                                                    <div class="col-md-4">
                                                                        <div class="example-card p-2 bg-white rounded border">
                                                                            <div class="fw-semibold text-success mb-1 fs-6">جمع بسيط</div>
                                                                            <code class="d-block fs-6">field1 + field2</code>
                                                                            <small class="text-muted">جمع حقلين معاً</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <div class="example-card p-2 bg-white rounded border">
                                                                            <div class="fw-semibold text-info mb-1 fs-6">ضرب وجمع</div>
                                                                            <code class="d-block fs-6">(field1 + field2) * 0.1</code>
                                                                            <small class="text-muted">جمع وضرب في نسبة</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <div class="example-card p-2 bg-white rounded border">
                                                                            <div class="fw-semibold text-warning mb-1 fs-6">معادلة معقدة</div>
                                                                            <code class="d-block fs-6">field1 - (field2 * field3)</code>
                                                                            <small class="text-muted">عمليات متعددة مع أقواس</small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- خيارات العرض والإظهار -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-primary mb-3">
                                <i class="mdi mdi-eye-settings me-2"></i>
                                خيارات العرض والإظهار
                            </h6>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-check-primary">
                                <input class="form-check-input" type="checkbox" wire:model="newField.show_in_table"
                                    id="fieldShowInTable">
                                <label class="form-check-label" for="fieldShowInTable">
                                    <i class="mdi mdi-table text-success me-1"></i>
                                    ظهور في جدول العرض
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-check-primary">
                                <input class="form-check-input" type="checkbox" wire:model="newField.show_in_search"
                                    id="fieldShowInSearch">
                                <label class="form-check-label" for="fieldShowInSearch">
                                    <i class="mdi mdi-magnify text-info me-1"></i>
                                    ظهور في رأس البحث
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-check-primary">
                                <input class="form-check-input" type="checkbox" wire:model="newField.show_in_forms"
                                    id="fieldShowInForms">
                                <label class="form-check-label" for="fieldShowInForms">
                                    <i class="mdi mdi-form-select text-warning me-1"></i>
                                    ظهور في نوافذ الإضافة/التعديل
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <button type="button" wire:click="addField" class="btn btn-primary">
                            <i class="mdi mdi-plus me-1"></i>
                            إضافة الحقل
                        </button>
                    </div>

                    <hr>

                    <!-- Generate Module Button -->
                    <div class="text-center">
                        <button type="button" wire:click="generateModule" class="btn btn-success btn-lg"
                            wire:loading.attr="disabled"
                            @if (count($fields) == 0) disabled title="أضف حقل واحد على الأقل" @endif>
                            <span wire:loading.remove>
                                <i class="mdi mdi-auto-fix me-1"></i>
                                إنشاء الوحدة
                                @if (count($fields) == 0)
                                    <small class="d-block text-muted">(أضف حقل واحد على الأقل)</small>
                                @endif
                            </span>
                            <span wire:loading>
                                <i class="mdi mdi-loading mdi-spin me-1"></i>
                                جاري الإنشاء...
                            </span>
                        </button>
                    </div>

                    @error('fields')
                        <div class="alert alert-danger mt-3">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Fields List -->
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="mdi mdi-format-list-bulleted text-primary me-1"></i>
                        الحقول المضافة ({{ count($fields) }})
                    </h5>
                </div>
                <div class="card-body">
                    @if (count($fields) > 0)
                        <div class="list-group list-group-flush">
                            @foreach ($fields as $index => $field)
                                <div
                                    class="list-group-item d-flex justify-content-between align-items-start border rounded mb-2">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-bold">{{ $field['ar_name'] }}</div>
                                        <small class="text-muted">{{ $field['name'] }}
                                            ({{ $fieldTypes[$field['type']] }})</small>
                                        <div class="mt-1">
                                            @if ($field['required'])
                                                <span class="badge bg-danger rounded-pill me-1">مطلوب</span>
                                            @endif
                                            @if ($field['unique'])
                                                <span class="badge bg-warning rounded-pill me-1">فريد</span>
                                            @endif
                                            @if ($field['searchable'])
                                                <span class="badge bg-info rounded-pill me-1">قابل للبحث</span>
                                            @endif
                                            @if (!($field['show_in_table'] ?? true))
                                                <span class="badge bg-secondary rounded-pill me-1">مخفي من
                                                    الجدول</span>
                                            @endif
                                            @if (!($field['show_in_search'] ?? true))
                                                <span class="badge bg-secondary rounded-pill me-1">مخفي من البحث</span>
                                            @endif
                                            @if (!($field['show_in_forms'] ?? true))
                                                <span class="badge bg-secondary rounded-pill me-1">مخفي من
                                                    النماذج</span>
                                            @endif
                                            @if ($field['type'] === 'string' && isset($field['text_content_type']))
                                                @if ($field['text_content_type'] === 'arabic_only')
                                                    <span class="badge bg-secondary rounded-pill me-1">عربي فقط</span>
                                                @elseif ($field['text_content_type'] === 'numeric_only')
                                                    <span class="badge bg-secondary rounded-pill me-1">أرقام فقط</span>
                                                @elseif ($field['text_content_type'] === 'english_only')
                                                    <span class="badge bg-secondary rounded-pill me-1">إنجليزي فقط</span>
                                                @endif
                                            @endif
                                            @if ($field['type'] === 'integer')
                                                <span class="badge bg-info rounded-pill me-1">{{ strtoupper($field['integer_type'] ?? 'INT') }}</span>
                                                @if ($field['unsigned'] ?? false)
                                                    <span class="badge bg-warning rounded-pill me-1">UNSIGNED</span>
                                                @endif
                                            @endif
                                            @if ($field['type'] === 'decimal')
                                                <span class="badge bg-info rounded-pill me-1">DECIMAL({{ $field['decimal_precision'] ?? 15 }},{{ $field['decimal_scale'] ?? 2 }})</span>
                                            @endif
                                            @if ($field['arabic_only'])
                                                <span class="badge bg-secondary rounded-pill me-1">عربي فقط</span>
                                            @endif
                                            @if ($field['numeric_only'])
                                                <span class="badge bg-secondary rounded-pill me-1">أرقام فقط</span>
                                            @endif
                                            @if ($field['is_calculated'] ?? false)
                                                <span class="badge bg-success rounded-pill me-1">
                                                    <i class="mdi mdi-calculator"></i> محسوب
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <button type="button" wire:click="removeField({{ $index }})"
                                        class="btn btn-outline-danger btn-sm">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="mdi mdi-format-list-bulleted fs-1 text-warning"></i>
                            <p class="mb-2 fw-bold text-warning">لا توجد حقول مضافة</p>
                            <p class="mb-3">يجب إضافة حقل واحد على الأقل لتفعيل زر إنشاء الوحدة</p>
                            <small class="text-muted">قم بإضافة الحقول من النموذج على اليسار أو استخدم "تحميل حقول
                                تجريبية"</small>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Instructions -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="mdi mdi-help-circle text-info me-1"></i>
                        إرشادات الاستخدام
                    </h5>
                </div>
                <div class="card-body">
                    <ol class="list-unstyled">
                        <li class="mb-2">
                            <i class="mdi mdi-numeric-1-circle text-primary me-1"></i>
                            ادخل اسم الوحدة بالإنجليزية والعربية
                        </li>
                        <li class="mb-2">
                            <i class="mdi mdi-numeric-2-circle text-primary me-1"></i>
                            أضف الحقول المطلوبة مع تحديد خصائص كل حقل
                        </li>
                        <li class="mb-2">
                            <i class="mdi mdi-numeric-3-circle text-primary me-1"></i>
                            اضغط على "إنشاء الوحدة" لتوليد الملفات
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Icon Picker Modal --}}
    @if ($showIconPicker)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">اختيار أيقونة من Material Design Icons</h5>
                        <button type="button" class="btn-close" wire:click="closeModal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-4">
                            يمكنك اختيار أيقونة من الأيقونات التالية أو زيارة
                            <a href="https://pictogrammers.com/library/mdi/" target="_blank" class="text-primary">
                                Material Design Icons
                            </a>
                            للمزيد من الأيقونات
                        </p>

                        @foreach ($this->getIconCategories() as $category => $icons)
                            <div class="mb-8">
                                <h6 class="fw-bold text-gray-800 mb-4">{{ $category }}</h6>
                                <div class="row g-3">
                                    @foreach ($icons as $iconClass)
                                        <div class="col-lg-2 col-md-3 col-sm-4">
                                            <div class="card card-flush h-100 cursor-pointer {{ $iconClass === $parentGroupIcon ? 'border-primary bg-light-primary' : '' }}"
                                                wire:click="selectIcon('{{ $iconClass }}')"
                                                style="transition: all 0.3s;">
                                                <div class="card-body text-center p-3">
                                                    <i
                                                        class="{{ $iconClass }} fs-2x {{ $iconClass === $parentGroupIcon ? 'text-primary' : 'text-gray-600' }} mb-2"></i>
                                                    <div class="text-gray-700 fs-7 fw-bold">
                                                        {{ str_replace(['mdi mdi-', '-'], ['', ' '], $iconClass) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" wire:click="closeModal">إغلاق</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- مودال إدخال رقم ثابت -->
<div class="modal fade" id="numberInputModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-gradient-info text-white border-0">
                <h6 class="modal-title mb-0">
                    <i class="mdi mdi-numeric me-2"></i>
                    إدخال رقم ثابت
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-3">
                    <div class="bg-light-info rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="mdi mdi-numeric text-info" style="font-size: 2rem;"></i>
                    </div>
                </div>
                <div class="form-floating mb-3">
                    <input type="number" class="form-control form-control-lg text-center" id="numberInput"
                           placeholder="أدخل الرقم" step="any" autofocus
                           onkeypress="if(event.key === 'Enter') { event.preventDefault(); addNumberFromModal(); }">
                    <label for="numberInput">الرقم الثابت</label>
                </div>
                <small class="text-muted d-block text-center">
                    <i class="mdi mdi-information me-1"></i>
                    يمكن إدخال أرقام صحيحة أو عشرية
                </small>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <div class="d-grid gap-2 d-md-flex justify-content-md-center w-100">
                    <button type="button" class="btn btn-light flex-fill" data-bs-dismiss="modal">
                        <i class="mdi mdi-close me-1"></i>
                        إلغاء
                    </button>
                    <button type="button" class="btn btn-info flex-fill" onclick="addNumberFromModal()">
                        <i class="mdi mdi-check me-1"></i>
                        إضافة
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- مودال تأكيد مسح المعادلة -->
<div class="modal fade" id="clearConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-gradient-warning text-white border-0">
                <h6 class="modal-title mb-0">
                    <i class="mdi mdi-alert-circle me-2"></i>
                    تأكيد المسح
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-3">
                    <div class="bg-light-warning rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="mdi mdi-delete-sweep text-warning" style="font-size: 2rem;"></i>
                    </div>
                </div>
                <h6 class="text-center mb-3">هل أنت متأكد من مسح المعادلة؟</h6>
                <p class="text-muted text-center mb-0">
                    <i class="mdi mdi-information me-1"></i>
                    سيتم حذف جميع العناصر من المعادلة الحالية
                </p>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <div class="d-grid gap-2 d-md-flex justify-content-md-center w-100">
                    <button type="button" class="btn btn-light flex-fill" data-bs-dismiss="modal">
                        <i class="mdi mdi-close me-1"></i>
                        إلغاء
                    </button>
                    <button type="button" class="btn btn-warning flex-fill" onclick="confirmClearFormula()">
                        <i class="mdi mdi-delete-sweep me-1"></i>
                        مسح الكل
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// دوال العمليات الحسابية للحقول
function addFieldToFormula(fieldName) {
    if (!fieldName) return;

    const formulaInput = document.getElementById('calculationFormula');
    const preview = document.getElementById('calculationPreview');

    if (formulaInput) {
        let currentFormula = formulaInput.value || '';

        // إضافة فراغ قبل اسم الحقل إذا لم تكن المعادلة فارغة
        if (currentFormula && !currentFormula.endsWith(' ')) {
            currentFormula += ' ';
        }

        currentFormula += fieldName;
        formulaInput.value = currentFormula;

        // تحديث المعاينة
        updateFormulaPreview(currentFormula);

        // إرسال التحديث إلى Livewire
        @this.set('newField.calculation_formula', currentFormula);

        // إعادة تعيين القائمة المنسدلة
        document.getElementById('availableFields').value = '';
    }
}

function addOperatorToFormula(operator) {
    const formulaInput = document.getElementById('calculationFormula');
    const preview = document.getElementById('calculationPreview');

    if (formulaInput) {
        let currentFormula = formulaInput.value || '';

        // إضافة فراغات حول العامل (ما عدا الأقواس)
        if (operator === '(' || operator === ')') {
            currentFormula += operator;
        } else {
            // إضافة فراغ قبل العامل إذا لم تكن المعادلة فارغة
            if (currentFormula && !currentFormula.endsWith(' ')) {
                currentFormula += ' ';
            }
            currentFormula += operator + ' ';
        }

        formulaInput.value = currentFormula;

        // تحديث المعاينة
        updateFormulaPreview(currentFormula);

        // إرسال التحديث إلى Livewire
        @this.set('newField.calculation_formula', currentFormula);
    }
}

function addNumberToFormula() {
    // فتح المودال بدلاً من prompt
    showNumberInputModal();
}

function clearFormula() {
    // فتح مودال التأكيد بدلاً من confirm
    showClearConfirmModal();
}

// دوال المودالات الجديدة
function showNumberInputModal() {
    const modal = new bootstrap.Modal(document.getElementById('numberInputModal'));
    modal.show();

    // تركيز على حقل الإدخال بعد فتح المودال
    setTimeout(() => {
        document.getElementById('numberInput').focus();
    }, 300);
}

function showClearConfirmModal() {
    const modal = new bootstrap.Modal(document.getElementById('clearConfirmModal'));
    modal.show();
}

function addNumberFromModal() {
    const numberInput = document.getElementById('numberInput');
    const number = numberInput.value.trim();

    if (number !== '') {
        // التحقق من أن المدخل رقم صحيح
        if (!isNaN(number)) {
            const formulaInput = document.getElementById('calculationFormula');

            if (formulaInput) {
                let currentFormula = formulaInput.value || '';

                // إضافة فراغ قبل الرقم إذا لم تكن المعادلة فارغة
                if (currentFormula && !currentFormula.endsWith(' ')) {
                    currentFormula += ' ';
                }

                currentFormula += number;
                formulaInput.value = currentFormula;

                // تحديث المعاينة
                updateFormulaPreview(currentFormula);

                // إرسال التحديث إلى Livewire
                @this.set('newField.calculation_formula', currentFormula);

                // إغلاق المودال وتنظيف الحقل
                const modal = bootstrap.Modal.getInstance(document.getElementById('numberInputModal'));
                modal.hide();
                numberInput.value = '';

                // إظهار Toast للنجاح
                if (window.Toast) {
                    Toast.fire({
                        icon: 'success',
                        title: 'تم إضافة الرقم بنجاح',
                        text: `تم إضافة الرقم ${number} إلى المعادلة`
                    });
                }
            }
        } else {
            // إظهار Toast للخطأ
            if (window.Toast) {
                Toast.fire({
                    icon: 'error',
                    title: 'خطأ في الإدخال',
                    text: 'يرجى إدخال رقم صحيح فقط!'
                });
            } else {
                alert('يرجى إدخال رقم صحيح فقط!');
            }
        }
    }
}

function confirmClearFormula() {
    const formulaInput = document.getElementById('calculationFormula');
    const preview = document.getElementById('calculationPreview');

    if (formulaInput) {
        formulaInput.value = '';

        // تحديث المعاينة
        updateFormulaPreview('');

        // إرسال التحديث إلى Livewire
        @this.set('newField.calculation_formula', '');

        // إغلاق المودال
        const modal = bootstrap.Modal.getInstance(document.getElementById('clearConfirmModal'));
        modal.hide();

        // إظهار Toast للنجاح
        if (window.Toast) {
            Toast.fire({
                icon: 'success',
                title: 'تم مسح المعادلة',
                text: 'تم حذف جميع عناصر المعادلة بنجاح'
            });
        }
    }
}

function updateFormulaPreview(formula) {
    const preview = document.getElementById('calculationPreview');
    if (preview) {
        if (formula.trim()) {
            preview.textContent = formula;
            preview.className = 'd-block mt-1 text-success';
        } else {
            preview.textContent = 'لم يتم إنشاء معادلة بعد';
            preview.className = 'd-block mt-1 text-muted';
        }
    }
}

// تحديث المعاينة عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    const formulaInput = document.getElementById('calculationFormula');
    if (formulaInput && formulaInput.value) {
        updateFormulaPreview(formulaInput.value);
    }

    // إضافة مؤثرات للمودالات
    const numberModal = document.getElementById('numberInputModal');
    const clearModal = document.getElementById('clearConfirmModal');

    if (numberModal) {
        numberModal.addEventListener('shown.bs.modal', function() {
            // تركيز على حقل الإدخال عند فتح المودال
            document.getElementById('numberInput').focus();
        });

        numberModal.addEventListener('hidden.bs.modal', function() {
            // تنظيف حقل الإدخال عند إغلاق المودال
            document.getElementById('numberInput').value = '';
        });
    }
});

// مراقبة تغييرات checkbox الحقل المحسوب
document.addEventListener('livewire:load', function () {
    Livewire.hook('message.processed', (message, component) => {
        // تحديث المعاينة بعد كل تحديث Livewire
        const formulaInput = document.getElementById('calculationFormula');
        if (formulaInput) {
            updateFormulaPreview(formulaInput.value);
        }
    });
});
</script>

<style>
/* مؤثرات بصرية للخيارات المتاحة/غير المتاحة */
.date-diff-options .form-check {
    transition: all 0.3s ease;
}

.date-diff-options .form-check.available {
    opacity: 1;
    animation: slideInFromRight 0.4s ease-out;
}

.date-diff-options .form-check.unavailable {
    opacity: 0.3;
    pointer-events: none;
    animation: slideOutToRight 0.4s ease-out;
}

@keyframes slideInFromRight {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideOutToRight {
    from {
        opacity: 1;
        transform: translateX(0);
    }
    to {
        opacity: 0.3;
        transform: translateX(20px);
    }
}

/* تحسين أداء الانتقالات */
.calculation-type-selection,
.date-calculation-builder {
    transition: all 0.3s ease;
}

/* إبراز الخيارات المتاحة */
.form-check-label.fw-semibold {
    position: relative;
}

.form-check-label.fw-semibold::before {
    content: '';
    position: absolute;
    left: -5px;
    top: 0;
    bottom: 0;
    width: 3px;
    background: linear-gradient(180deg, #28a745, #20c997);
    opacity: 0;
    border-radius: 2px;
    transition: opacity 0.3s ease;
}

.form-check:hover .form-check-label.fw-semibold::before {
    opacity: 1;
}

/* تحسين عرض النص التوضيحي */
.text-muted.mt-1 {
    font-size: 0.825rem;
    line-height: 1.4;
    padding: 5px 10px;
    background: rgba(108, 117, 125, 0.1);
    border-radius: 4px;
    border-left: 3px solid #6c757d;
}
</style>
