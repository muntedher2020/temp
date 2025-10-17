{{-- وضع إضافة حقول جديدة - Livewire Style --}}
<div class="fade-in">
    <div class="row">
        <div class="col-lg-8 col-md-12">
            <!-- تغيير حالة الوحدة ومجموعتها الأب -->
            @if ($editingModule)
                <div class="card border-warning mb-4">
                    <div class="card-header bg-warning bg-opacity-10 border-warning">
                        <h6 class="mb-0 text-dark fw-bold">
                            <i class="mdi mdi-repeat me-2 fw-bold"></i>
                            تغيير حالة الوحدة ومجموعتها الأب
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <!-- نوع التغيير -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label fw-bold text-primary">نوع التغيير المطلوب:</label>
                                <div class="d-flex flex-wrap gap-3">
                                    <div class="form-check form-check-primary">
                                        <input class="form-check-input" type="radio" wire:model="changeType"
                                            value="change_parent" id="changeParent">
                                        <label class="form-check-label" for="changeParent">
                                            <i class="mdi mdi-swap-horizontal me-2 text-info"></i>
                                            تغيير المجموعة الأب (تبقى فرعية)
                                        </label>
                                    </div>
                                    <div class="form-check form-check-primary">
                                        <input class="form-check-input" type="radio" wire:model="changeType"
                                            value="make_standalone" id="makeStandalone">
                                        <label class="form-check-label" for="makeStandalone">
                                            <i class="mdi mdi-arrow-up-bold me-2 text-success"></i>
                                            تحويل إلى وحدة رئيسية منفصلة
                                        </label>
                                    </div>
                                    <div class="form-check form-check-primary">
                                        <input class="form-check-input" type="radio" wire:model="changeType"
                                            value="make_sub_module" id="makeSubModule">
                                        <label class="form-check-label" for="makeSubModule">
                                            <i class="mdi mdi-arrow-down-bold me-2 text-warning"></i>
                                            تحويل إلى وحدة فرعية (للوحدات الرئيسية)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- خيارات تغيير المجموعة الأب -->
                        @if ($changeType === 'change_parent')
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <div class="form-floating form-floating-outline">
                                        <select wire:model="selectedParentGroup"
                                            class="form-select @error('selectedParentGroup') is-invalid @enderror"
                                            id="parentGroupSelect">
                                            <option value="">اختر المجموعة الأب الجديدة</option>
                                            @if (isset($availableGroups) && is_array($availableGroups))
                                                @foreach ($availableGroups as $group)
                                                    <option value="{{ $group['name_en'] ?? $group['name'] }}"
                                                        {{ $selectedParentGroup === ($group['name_en'] ?? $group['name']) ? 'selected' : '' }}>
                                                        {{ $group['name_ar'] ?? ($group['name_en'] ?? $group['name']) }}
                                                        @if (isset($group['name_en']))
                                                            ({{ $group['name_en'] }})
                                                        @endif
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <label for="parentGroupSelect">المجموعة الأب الجديدة</label>
                                        @error('selectedParentGroup')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button type="button" wire:click="updateParentGroup" class="btn btn-warning w-100"
                                        {{ empty($selectedParentGroup) || $selectedParentGroup === $currentParentGroup ? 'disabled' : '' }}>
                                        <i class="mdi mdi-content-save me-1"></i>
                                        تطبيق التغيير
                                    </button>
                                </div>
                            </div>
                        @endif

                        <!-- خيار التحويل إلى وحدة رئيسية -->
                        @if ($changeType === 'make_standalone')
                            <div class="alert alert-info">
                                <div class="d-flex align-items-center">
                                    <i class="mdi mdi-information-outline fs-4 me-3 text-info"></i>
                                    <div>
                                        <h6 class="mb-1">تحويل إلى وحدة رئيسية منفصلة</h6>
                                        <p class="mb-2">سيتم تحويل هذه الوحدة من فرعية تحت مجموعة إلى وحدة رئيسية
                                            منفصلة في القائمة.</p>
                                        <small class="text-muted">
                                            <strong>ملاحظة:</strong> ستظهر الوحدة كعنصر رئيسي قابل للنقر في القائمة
                                            وستحتفظ بجميع بياناتها وإعداداتها.
                                        </small>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button type="button" wire:click="makeModuleStandalone" class="btn btn-success">
                                        <i class="mdi mdi-check-circle me-2"></i>
                                        تأكيد التحويل إلى وحدة رئيسية
                                    </button>
                                </div>
                            </div>
                        @endif

                        <!-- خيار التحويل إلى فرعية -->
                        @if ($changeType === 'make_sub_module')
                            <div class="alert alert-warning">
                                <div class="d-flex align-items-center">
                                    <i class="mdi mdi-information-outline fs-4 me-3 text-warning"></i>
                                    <div>
                                        <h6 class="mb-1">تحويل إلى وحدة فرعية</h6>
                                        <p class="mb-2">سيتم تحويل هذه الوحدة من رئيسية منفصلة إلى وحدة فرعية تحت
                                            مجموعة محددة.</p>
                                        <small class="text-muted">
                                            <strong>ملاحظة:</strong> ستظهر الوحدة كعنصر فرعي تحت المجموعة المختارة.
                                        </small>
                                    </div>
                                </div>
                                <div class="row g-3 mt-2">
                                    <div class="col-md-8">
                                        <div class="form-floating form-floating-outline">
                                            <select wire:model="selectedParentGroup"
                                                class="form-select @error('selectedParentGroup') is-invalid @enderror"
                                                id="parentGroupSelectSub">
                                                <option value="">اختر المجموعة الأب</option>
                                                @if (isset($availableGroups) && is_array($availableGroups))
                                                    @foreach ($availableGroups as $group)
                                                        <option value="{{ $group['name_en'] ?? $group['name'] }}">
                                                            {{ $group['name_ar'] ?? ($group['name_en'] ?? $group['name']) }}
                                                            @if (isset($group['name_en']))
                                                                ({{ $group['name_en'] }})
                                                            @endif
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <label for="parentGroupSelectSub">المجموعة الأب</label>
                                            @error('selectedParentGroup')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="button" wire:click="makeModuleSubModule"
                                            class="btn btn-warning w-100"
                                            {{ empty($selectedParentGroup) ? 'disabled' : '' }}>
                                            <i class="mdi mdi-check-circle me-2"></i>
                                            تأكيد التحويل
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($currentParentGroup)
                            <div class="row mt-2">
                                <div class="col-12">
                                    <div class="alert alert-info py-2 mb-0">
                                        <small>
                                            <i class="mdi mdi-information me-1"></i>
                                            <strong>الحالة الحالية:</strong>
                                            @if ($currentParentGroup === 'standalone')
                                                وحدة رئيسية منفصلة
                                            @else
                                                وحدة فرعية تحت مجموعة "{{ $currentParentGroup }}"
                                            @endif

                                            @if ($changeType === 'change_parent' && $selectedParentGroup && $selectedParentGroup !== $currentParentGroup)
                                                <br><strong>المجموعة الجديدة:</strong> {{ $selectedParentGroup }}
                                            @elseif($changeType === 'make_standalone')
                                                <br><strong>التغيير المطلوب:</strong> تحويل إلى وحدة رئيسية منفصلة
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- الميزات المتقدمة -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-gradient-info text-white border-0">
                    <h6 class="mb-0 text-dark fw-bold">
                        <i class="bx bx-cog me-2 fw-bold"></i>
                        الميزات المتقدمة
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" wire:model="enableExcelExport"
                                    id="enableExcelExport" checked>
                                <label class="form-check-label" for="enableExcelExport">
                                    <i class="mdi mdi-file-excel text-success me-2"></i>
                                    تصدير Excel
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" wire:model="enablePdfExport"
                                    id="enablePdfExport" checked>
                                <label class="form-check-label" for="enablePdfExport">
                                    <i class="mdi mdi-file-pdf text-danger me-2"></i>
                                    طباعة PDF
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" wire:model="enableFlatpickr"
                                    id="enableFlatpickr">
                                <label class="form-check-label" for="enableFlatpickr">
                                    <i class="mdi mdi-calendar text-primary me-2"></i>
                                    Flatpickr للتاريخ
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" wire:model="enableSelect2"
                                    id="enableSelect2">
                                <label class="form-check-label" for="enableSelect2">
                                    <i class="mdi mdi-format-list-bulleted text-warning me-2"></i>
                                    Select للقوائم
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" wire:model="enableViewsUpdate"
                            id="enableViewsUpdate" checked>
                        <label class="form-check-label" for="enableViewsUpdate">
                            <i class="mdi mdi-eye text-info me-2"></i>
                            <strong>تحديث ملفات Views تلقائياً</strong>
                            <small class="d-block text-muted mt-2">
                                <i class="mdi mdi-information-outline me-1"></i>
                                <strong>ما يعنيه هذا الخيار:</strong> عند تفعيله، سيقوم النظام بتحديث ملفات العرض
                                تلقائياً
                                لإضافة الحقول الجديدة في جداول العرض ونوافذ الإضافة والتعديل.
                                عند إلغائه، ستحتاج لتحديث ملفات Views يدوياً.
                            </small>
                        </label>
                    </div>
                </div>
            </div>

            <!-- نموذج إضافة حقل جديد -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-gradient-success text-white border-0">
                    <h6 class="mb-0 text-dark fw-bold">
                        <i class="mdi mdi-plus-circle me-2 fw-bold"></i>
                        إضافة حقل جديد
                    </h6>
                </div>
                <div class="card-body p-4">
                    <!-- معلومات أساسية -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="form-floating form-floating-outline">
                                <input type="text" wire:model.lazy="newField.name"
                                    class="form-control @error('newField.name') is-invalid @enderror" id="fieldName"
                                    placeholder="اسم الحقل">
                                <label for="fieldName">اسم الحقل (English)</label>
                            </div>
                            @error('newField.name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating form-floating-outline">
                                <input type="text" wire:model.lazy="newField.ar_name"
                                    class="form-control @error('newField.ar_name') is-invalid @enderror"
                                    id="fieldArName" placeholder="الاسم العربي">
                                <label for="fieldArName">الاسم العربي</label>
                            </div>
                            @error('newField.ar_name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating form-floating-outline">
                                <select wire:model="newField.type"
                                    class="form-select @error('newField.type') is-invalid @enderror" id="fieldType">
                                    @foreach ($fieldTypes as $type => $label)
                                        <option value="{{ $type }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <label for="fieldType">نوع الحقل</label>
                            </div>
                            @error('newField.type')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <small class="text-muted mt-1">
                                @switch($newField['type'])
                                    @case('string')
                                        نص قصير للأسماء والعناوين
                                    @break

                                    @case('text')
                                        نص طويل للوصف والملاحظات
                                    @break

                                    @case('integer')
                                        أرقام صحيحة فقط
                                    @break

                                    @case('decimal')
                                        أرقام عشرية (مع فاصلة)
                                    @break

                                    @case('email')
                                        عناوين البريد الإلكتروني
                                    @break

                                    @case('date')
                                        تاريخ بدون وقت
                                    @break

                                    @case('datetime')
                                        تاريخ ووقت معاً
                                    @break

                                    @case('time')
                                        وقت فقط (ساعة ودقيقة)
                                    @break

                                    @case('month_year')
                                        شهر وسنة فقط
                                    @break

                                    @case('checkbox')
                                        نعم/لا أو صح/خطأ
                                    @break

                                    @case('file')
                                        رفع الملفات والمستندات
                                    @break

                                    @case('select')
                                        قائمة خيارات محددة
                                    @break

                                    @default
                                        اختر نوع الحقل المناسب
                                @endswitch
                            </small>
                        </div>
                    </div>

                    <!-- خيارات متقدمة -->
                    <div class="row mb-4">
                        @if ($newField['type'] === 'string')
                            <div class="col-md-3">
                                <div class="form-floating form-floating-outline">
                                    <input type="number" wire:model.lazy="newField.size" class="form-control"
                                        id="fieldSize" placeholder="الحد الأقصى للأحرف">
                                    <label for="fieldSize">الحد الأقصى للأحرف</label>
                                </div>
                            </div>
                        @endif

                        <div class="col-md-3">
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" wire:model="newField.required"
                                    id="fieldRequired">
                                <label class="form-check-label" for="fieldRequired">
                                    <i class="bx bx-error-circle text-danger me-2"></i>
                                    مطلوب
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" wire:model="newField.unique"
                                    id="fieldUnique">
                                <label class="form-check-label" for="fieldUnique">
                                    <i class="bx bx-star text-warning me-2"></i>
                                    فريد
                                </label>
                            </div>
                        </div>
                        @if ($newField['type'] !== 'file')
                            <div class="col-md-3">
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" wire:model="newField.searchable"
                                        id="fieldSearchable">
                                    <label class="form-check-label" for="fieldSearchable">
                                        <i class="bx bx-search text-info me-2"></i>
                                        قابل للبحث
                                    </label>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- خيارات خاصة لكل نوع حقل -->
                    @if ($newField['type'] === 'string')
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="form-floating form-floating-outline">
                                    <select class="form-select" wire:model="newField.text_content_type" id="textContentType">
                                        <option value="any">جميع الأحرف</option>
                                        <option value="arabic_only">عربي فقط</option>
                                        <option value="numeric_only">أرقام فقط</option>
                                        <option value="english_only">إنجليزي فقط</option>
                                    </select>
                                    <label for="textContentType">نوع محتوى النص</label>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($newField['type'] === 'integer')
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="form-floating form-floating-outline">
                                    <select class="form-select" wire:model="newField.integer_type" id="integerType">
                                        <option value="int">INT (عادي)</option>
                                        <option value="bigint">BIGINT (كبير)</option>
                                        <option value="smallint">SMALLINT (صغير)</option>
                                        <option value="tinyint">TINYINT (صغير جداً)</option>
                                    </select>
                                    <label for="integerType">نوع الرقم الصحيح</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" wire:model="newField.unsigned" id="fieldUnsigned">
                                    <label class="form-check-label" for="fieldUnsigned">
                                        <i class="mdi mdi-plus-circle text-success me-2"></i>
                                        أرقام موجبة فقط (UNSIGNED)
                                    </label>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($newField['type'] === 'decimal')
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input type="number" class="form-control" wire:model="newField.decimal_precision"
                                        min="1" max="65" value="15" placeholder="15" id="decimalPrecision">
                                    <label for="decimalPrecision">إجمالي الأرقام (Precision)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input type="number" class="form-control" wire:model="newField.decimal_scale"
                                        min="0" max="30" value="2" placeholder="2" id="decimalScale">
                                    <label for="decimalScale">المراتب العشرية (Scale)</label>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- خيارات خاصة بنوع الحقل - نسخة من مولد الوحدات -->
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
                        <div class="col-md-12 mt-4 mb-3">
                            <h6 class="text-primary mb-3">
                                <i class="mdi mdi-check-box-outline me-2"></i>
                                إعداد تسميات الحقل
                            </h6>
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
                                        <label for="checkboxFalseLabel">التسمية عند عدم التفعيل (مثل: غير مفعل، لا، لا
                                            أوافق)</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($newField['type'] === 'select')
                        <div class="col-md-12 mt-4 mb-3">
                            <h6 class="text-primary mb-3">
                                <i class="mdi mdi-format-list-bulleted me-2"></i>
                                إعداد القائمة المنسدلة
                            </h6>

                            <!-- نوع مصدر البيانات -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="form-check form-check-primary">
                                        <input class="form-check-input" type="radio"
                                            wire:model="newField.select_source" value="manual"
                                            id="selectSourceManual">
                                        <label class="form-check-label" for="selectSourceManual">
                                            <i class="mdi mdi-keyboard me-2"></i>
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
                                            <i class="mdi mdi-database me-2"></i>
                                            ربط بجدول آخر
                                        </label>
                                    </div>
                                </div>
                            </div>

                            @if ($newField['select_source'] === 'manual')
                                <!-- الإدخال اليدوي -->
                                <div class="row mb-3">
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
                                                        <button type="button" class="btn btn-sm btn-outline-danger"
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
                                            <select wire:model="newField.related_table" class="form-select"
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
                                                    <option value="{{ $column }}">{{ $column }}</option>
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
                                                    <option value="{{ $column }}">{{ $column }}</option>
                                                @endforeach
                                            </select>
                                            <label for="relatedDisplay">حقل العرض</label>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- خيار القيم الرقمية -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="form-check form-check-success">
                                        <input class="form-check-input" type="checkbox"
                                            wire:model="newField.select_numeric_values" id="selectNumericValues">
                                        <label class="form-check-label" for="selectNumericValues">
                                            <i class="mdi mdi-calculator me-2"></i>
                                            الخيارات تحتوي على قيم رقمية (للاستخدام في العمليات الحسابية)
                                        </label>
                                    </div>
                                    <div class="form-text">
                                        <i class="mdi mdi-information-outline me-1"></i>
                                        قم بتفعيل هذا الخيار إذا كانت القائمة المنسدلة تحتوي على قيم رقمية يمكن
                                        استخدامها في الحسابات
                                    </div>
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
                                        <input class="form-check-input" type="checkbox"
                                            wire:model="newField.is_calculated" id="fieldIsCalculated"
                                            style="transform: scale(1.1);">
                                        <label class="form-check-label fw-semibold text-dark fs-6"
                                            for="fieldIsCalculated">
                                            <i class="mdi mdi-function-variant text-info me-2"></i>
                                            تفعيل الحساب التلقائي للحقل
                                            <small class="d-block text-muted mt-1">سيتم حساب قيمة هذا الحقل تلقائياً
                                                بناءً على حقول أخرى</small>
                                        </label>
                                    </div>

                                    @if ($newField['is_calculated'] ?? false)
                                        <!-- نوع الحساب -->
                                        <div class="calculation-type-selection mb-4">
                                            <h6 class="text-primary mb-3">
                                                <i class="mdi mdi-cog me-2"></i>
                                                نوع الحساب
                                                @if ($newField['type'] === 'integer')
                                                    <span class="badge bg-success ms-2">رقم صحيح - جميع الخيارات
                                                        متاحة</span>
                                                @elseif ($newField['type'] === 'decimal')
                                                    <span class="badge bg-warning ms-2">رقم عشري - بدون فرق
                                                        التواريخ</span>
                                                @endif
                                            </h6>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio"
                                                            wire:model="newField.calculation_type" value="formula"
                                                            id="calcTypeFormula">
                                                        <label class="form-check-label fw-semibold"
                                                            for="calcTypeFormula">
                                                            <i class="mdi mdi-function-variant text-primary me-2"></i>
                                                            معادلة حسابية
                                                            <small class="d-block text-muted">عمليات رياضية بين
                                                                الحقول</small>
                                                        </label>
                                                    </div>
                                                </div>
                                                @if ($newField['type'] === 'integer')
                                                    <div class="col-md-4">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio"
                                                                wire:model="newField.calculation_type"
                                                                value="date_diff" id="calcTypeDateDiff">
                                                            <label class="form-check-label fw-semibold"
                                                                for="calcTypeDateDiff">
                                                                <i
                                                                    class="mdi mdi-calendar-clock text-success me-2"></i>
                                                                فرق التواريخ
                                                                <small class="d-block text-muted">حساب الفرق بين
                                                                    تاريخين (للأرقام الصحيحة فقط)</small>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio"
                                                                wire:model="newField.calculation_type"
                                                                value="time_diff" id="calcTypeTimeDiff">
                                                            <label class="form-check-label fw-semibold"
                                                                for="calcTypeTimeDiff">
                                                                <i class="mdi mdi-clock-time-four text-info me-2"></i>
                                                                فرق الأوقات
                                                                <small class="d-block text-muted">حساب الفرق بين
                                                                    وقتين (للأرقام الصحيحة فقط)</small>
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endif
                                                <div class="col-md-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio"
                                                            wire:model="newField.calculation_type" value="none"
                                                            id="calcTypeNone">
                                                        <label class="form-check-label fw-semibold"
                                                            for="calcTypeNone">
                                                            <i class="mdi mdi-numeric text-secondary me-2"></i>
                                                            رقم عادي
                                                            <small class="d-block text-muted">حقل رقمي عادي بدون
                                                                حساب</small>
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
                                                        خيار "فرق التواريخ" متاح فقط للحقول الصحيحة لأنها تحتاج لإرجاع
                                                        أرقام صحيحة (مثل عدد الأيام/الأشهر/السنوات).
                                                    @elseif ($newField['type'] === 'decimal')
                                                        خيار "فرق التواريخ" غير متاح للحقول العشرية حيث أن حساب فرق
                                                        التواريخ يرجع أرقام صحيحة فقط.
                                                    @endif
                                                </small>
                                            </div>
                                        </div>

                                        <!-- واجهة المعادلة الحسابية -->
                                        @if (($newField['calculation_type'] ?? 'none') === 'formula')
                                            <div class="calculation-builder">
                                                <!-- معاينة المعادلة -->
                                                <div class="alert alert-primary border-0 mb-4">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="w-100">
                                                            <h6 class="alert-heading mb-2">
                                                                <i class="mdi mdi-function me-2"></i>
                                                                المعادلة الحالية
                                                            </h6>
                                                            <div class="formula-preview bg-white rounded p-2 border">
                                                                <code class="text-dark fw-bold small"
                                                                    id="calculationPreview">
                                                                    @if (!empty($newField['calculation_formula']))
                                                                        {{ $newField['calculation_formula'] }}
                                                                    @else
                                                                        قم ببناء المعادلة باستخدام الأدوات أدناه
                                                                    @endif
                                                                </code>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- أدوات بناء المعادلة -->
                                                <div class="row g-3">
                                                    <!-- اختيار الحقول -->
                                                    <div class="col-lg-4">
                                                        <div class="card border-info h-100">
                                                            <div class="card-header bg-light-info py-2">
                                                                <h6 class="mb-0 text-info small">
                                                                    <i class="mdi mdi-database me-2"></i>
                                                                    الحقول المتاحة
                                                                </h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <select class="form-select" id="availableFields"
                                                                    wire:change="addFieldToFormula($event.target.value)">
                                                                    <option value="">-- اختر حقل رقمي --</option>
                                                                    @if (!empty($pendingFields))
                                                                        @foreach ($pendingFields as $index => $field)
                                                                            @if (
                                                                                ($field['type'] === 'integer' ||
                                                                                    $field['type'] === 'decimal' ||
                                                                                    ($field['type'] === 'select' &&
                                                                                        isset($field['select_numeric_values']) &&
                                                                                        $field['select_numeric_values'] == true)) &&
                                                                                    $field['name'] !== ($newField['name'] ?? ''))
                                                                                <option value="{{ $field['name'] }}">
                                                                                    {{ $field['ar_name'] }}
                                                                                    ({{ $field['name'] }})
                                                                                </option>
                                                                            @endif
                                                                        @endforeach
                                                                    @endif
                                                                    @if (!empty($moduleFields))
                                                                        @foreach ($moduleFields as $field)
                                                                            @php
                                                                                $isNumericField = false;

                                                                                // حقول رقمية تقليدية
                                                                                if (
                                                                                    in_array($field['type'], [
                                                                                        'integer',
                                                                                        'decimal',
                                                                                    ])
                                                                                ) {
                                                                                    $isNumericField = true;
                                                                                }

                                                                                // حقول select رقمية
                                                                                if ($field['type'] === 'select') {
                                                                                    // إذا كان select_numeric_values موجود ومضبوط على true
                                                                                    if (
                                                                                        isset(
                                                                                            $field[
                                                                                                'select_numeric_values'
                                                                                            ],
                                                                                        ) &&
                                                                                        $field[
                                                                                            'select_numeric_values'
                                                                                        ] == true
                                                                                    ) {
                                                                                        $isNumericField = true;
                                                                                    }
                                                                                    // أو إذا كان الحقل يحتوي على خيارات رقمية (للحقول القديمة)
                                                                                    elseif (
                                                                                        !isset(
                                                                                            $field[
                                                                                                'select_numeric_values'
                                                                                            ],
                                                                                        ) &&
                                                                                        !empty($field['select_options'])
                                                                                    ) {
                                                                                        $allNumeric = true;
                                                                                        foreach (
                                                                                            $field['select_options']
                                                                                            as $option
                                                                                        ) {
                                                                                            if (!is_numeric($option)) {
                                                                                                $allNumeric = false;
                                                                                                break;
                                                                                            }
                                                                                        }
                                                                                        if ($allNumeric) {
                                                                                            $isNumericField = true;
                                                                                        }
                                                                                    }
                                                                                }
                                                                            @endphp
                                                                            @if ($isNumericField && $field['name'] !== ($newField['name'] ?? ''))
                                                                                <option value="{{ $field['name'] }}">
                                                                                    {{ $field['ar_name'] }}
                                                                                    ({{ $field['name'] }})
                                                                                    - موجود
                                                                                </option>
                                                                            @endif
                                                                        @endforeach
                                                                    @endif
                                                                </select>
                                                                <small class="text-muted mt-1 d-block">انقر لإضافة
                                                                    الحقل للمعادلة</small>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- العمليات الحسابية -->
                                                    <div class="col-lg-4">
                                                        <div class="card border-success h-100">
                                                            <div class="card-header bg-light-success py-2">
                                                                <h6 class="mb-0 text-success small">
                                                                    <i class="mdi mdi-calculator-variant me-2"></i>
                                                                    العمليات الحسابية
                                                                </h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="d-grid gap-2">
                                                                    <button type="button"
                                                                        class="btn btn-outline-primary btn-sm"
                                                                        wire:click="addOperatorToFormula('+')">
                                                                        <i class="mdi mdi-plus-circle me-1"></i>
                                                                        جمع (+)
                                                                    </button>
                                                                    <button type="button"
                                                                        class="btn btn-outline-danger btn-sm"
                                                                        wire:click="addOperatorToFormula('-')">
                                                                        <i class="mdi mdi-minus-circle me-1"></i>
                                                                        طرح (-)
                                                                    </button>
                                                                    <button type="button"
                                                                        class="btn btn-outline-success btn-sm"
                                                                        wire:click="addOperatorToFormula('*')">
                                                                        <i class="mdi mdi-close-circle me-1"></i>
                                                                        ضرب (×)
                                                                    </button>
                                                                    <button type="button"
                                                                        class="btn btn-outline-warning btn-sm"
                                                                        wire:click="addOperatorToFormula('/')">
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
                                                            <div class="card-header bg-light-warning py-2">
                                                                <h6 class="mb-0 text-warning small">
                                                                    <i class="mdi mdi-tools me-2"></i>
                                                                    أدوات إضافية
                                                                </h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="d-grid gap-2">
                                                                    <button type="button"
                                                                        class="btn btn-outline-secondary btn-sm"
                                                                        wire:click="addOperatorToFormula('(')">
                                                                        <i class="mdi mdi-code-parentheses me-1"></i>
                                                                        قوس فتح (
                                                                    </button>
                                                                    <button type="button"
                                                                        class="btn btn-outline-secondary btn-sm"
                                                                        wire:click="addOperatorToFormula(')')">
                                                                        <i class="mdi mdi-code-parentheses me-1"></i>
                                                                        قوس إغلاق )
                                                                    </button>
                                                                    <button type="button"
                                                                        class="btn btn-outline-info btn-sm"
                                                                        onclick="showNumberInputModal()">
                                                                        <i class="mdi mdi-numeric me-1"></i>
                                                                        رقم ثابت
                                                                    </button>
                                                                    <button type="button"
                                                                        class="btn btn-outline-danger btn-sm"
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
                                                <input type="hidden" wire:model="newField.calculation_formula"
                                                    id="calculationFormula">

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
                                                        <div class="card-body py-2">
                                                            <h6 class="text-primary mb-2 small">
                                                                <i class="mdi mdi-lightbulb-outline me-2"></i>
                                                                أمثلة على المعادلات
                                                            </h6>
                                                            <div class="row g-2">
                                                                <div class="col-md-4">
                                                                    <div
                                                                        class="example-card p-2 bg-white rounded border">
                                                                        <div class="fw-bold text-success mb-1 small">
                                                                            جمع بسيط</div>
                                                                        <code class="d-block small">field1 +
                                                                            field2</code>
                                                                        <small class="text-muted">جمع حقلين
                                                                            معاً</small>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div
                                                                        class="example-card p-2 bg-white rounded border">
                                                                        <div class="fw-bold text-info mb-1 small">ضرب
                                                                            وجمع</div>
                                                                        <code class="d-block small">(field1 + field2) *
                                                                            0.1</code>
                                                                        <small class="text-muted">جمع وضرب في
                                                                            نسبة</small>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div
                                                                        class="example-card p-2 bg-white rounded border">
                                                                        <div class="fw-bold text-warning mb-1 small">
                                                                            معادلة معقدة</div>
                                                                        <code class="d-block small">field1 - (field2 *
                                                                            field3)</code>
                                                                        <small class="text-muted">عمليات متعددة مع
                                                                            أقواس</small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        <!-- واجهة حساب فرق التواريخ -->
                                        @if (($newField['calculation_type'] ?? 'none') === 'date_diff')
                                            <div class="date-calculation-builder">
                                                <!-- معاينة حساب التاريخ -->
                                                <div class="alert alert-success border-0 mb-4">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="w-100">
                                                            <h6 class="alert-heading mb-2">
                                                                <i class="mdi mdi-calendar-clock me-2"></i>
                                                                معاينة حساب التاريخ
                                                            </h6>
                                                            <div class="formula-preview bg-white rounded p-2 border">
                                                                <code class="text-dark fw-bold small">
                                                                    @if ($newField['date_from_field'] && $newField['date_to_field'])
                                                                        حساب الفرق بين
                                                                        {{ $newField['date_from_field'] }} و
                                                                        {{ $newField['date_to_field'] }}
                                                                        @if ($newField['remaining_only'] ?? false)
                                                                            @if (($newField['date_diff_unit'] ?? 'days') === 'days')
                                                                                (الأيام المتبقية من الشهر)
                                                                            @elseif(($newField['date_diff_unit'] ?? 'days') === 'months')
                                                                                (الأشهر المتبقية من السنة)
                                                                            @endif
                                                                        @else
                                                                            بوحدة
                                                                            {{ $newField['date_diff_unit'] === 'days' ? 'أيام' : ($newField['date_diff_unit'] === 'months' ? 'أشهر' : 'سنوات') }}
                                                                        @endif
                                                                        @if ($newField['include_end_date'] ?? false)
                                                                            + شمل التاريخ النهائي
                                                                        @endif
                                                                        @if ($newField['absolute_value'] ?? false)
                                                                            (قيمة مطلقة)
                                                                        @endif
                                                                    @else
                                                                        اختر حقول التاريخ لرؤية معاينة الحساب
                                                                    @endif
                                                                </code>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- أدوات بناء حساب التاريخ -->
                                                <div class="row g-3">
                                                    <!-- اختيار حقول التاريخ -->
                                                    <div class="col-lg-6">
                                                        <div class="card border-success h-100">
                                                            <div class="card-header bg-light-success py-2">
                                                                <h6 class="mb-0 text-success small">
                                                                    <i class="mdi mdi-calendar-range me-2"></i>
                                                                    حقول التاريخ
                                                                </h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="row g-3">
                                                                    <div class="col-12">
                                                                        <label
                                                                            class="form-label fw-semibold text-success small">
                                                                            <i class="mdi mdi-calendar-start me-1"></i>
                                                                            التاريخ من:
                                                                        </label>
                                                                        <select class="form-select form-select-sm"
                                                                            wire:model="newField.date_from_field">
                                                                            <option value="">اختر حقل التاريخ من
                                                                            </option>
                                                                            @foreach ($moduleFields as $field)
                                                                                @if ($field['type'] === 'date' || $field['type'] === 'datetime')
                                                                                    <option
                                                                                        value="{{ $field['name'] }}">
                                                                                        {{ $field['ar_name'] ?? $field['name'] }}
                                                                                        ({{ $field['name'] }})
                                                                                        {{ $field['type'] === 'datetime' ? ' - تاريخ ووقت' : '' }}
                                                                                    </option>
                                                                                @endif
                                                                            @endforeach
                                                                            @foreach ($pendingFields as $field)
                                                                                @if ($field['type'] === 'date' || $field['type'] === 'datetime')
                                                                                    <option
                                                                                        value="{{ $field['name'] }}">
                                                                                        {{ $field['ar_name'] ?? $field['name'] }}
                                                                                        ({{ $field['name'] }})
                                                                                        {{ $field['type'] === 'datetime' ? ' - تاريخ ووقت جديد' : ' - جديد' }}
                                                                                    </option>
                                                                                @endif
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-12">
                                                                        <label
                                                                            class="form-label fw-semibold text-success small">
                                                                            <i class="mdi mdi-calendar-end me-1"></i>
                                                                            التاريخ إلى:
                                                                        </label>
                                                                        <select class="form-select form-select-sm"
                                                                            wire:model="newField.date_to_field">
                                                                            <option value="">اختر حقل التاريخ إلى
                                                                            </option>
                                                                            @foreach ($moduleFields as $field)
                                                                                @if ($field['type'] === 'date' || $field['type'] === 'datetime')
                                                                                    <option
                                                                                        value="{{ $field['name'] }}">
                                                                                        {{ $field['ar_name'] ?? $field['name'] }}
                                                                                        ({{ $field['name'] }})
                                                                                        {{ $field['type'] === 'datetime' ? ' - تاريخ ووقت' : '' }}
                                                                                    </option>
                                                                                @endif
                                                                            @endforeach
                                                                            @foreach ($pendingFields as $field)
                                                                                @if ($field['type'] === 'date' || $field['type'] === 'datetime')
                                                                                    <option
                                                                                        value="{{ $field['name'] }}">
                                                                                        {{ $field['ar_name'] ?? $field['name'] }}
                                                                                        ({{ $field['name'] }})
                                                                                        {{ $field['type'] === 'datetime' ? ' - تاريخ ووقت جديد' : ' - جديد' }}
                                                                                    </option>
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
                                                            <div class="card-header bg-light-info py-2">
                                                                <h6 class="mb-0 text-info small">
                                                                    <i class="mdi mdi-cog me-2"></i>
                                                                    إعدادات وخيارات الحساب
                                                                </h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <!-- وحدة القياس -->
                                                                <div class="mb-3">
                                                                    <label
                                                                        class="form-label fw-semibold text-info small">
                                                                        <i class="mdi mdi-ruler me-1"></i>
                                                                        وحدة القياس:
                                                                    </label>
                                                                    <select class="form-select form-select-sm"
                                                                        wire:model="newField.date_diff_unit">
                                                                        <option value="days">أيام</option>
                                                                        <option value="months">أشهر</option>
                                                                        <option value="years">سنوات</option>
                                                                    </select>
                                                                </div>

                                                                <!-- الخيارات الإضافية الديناميكية -->
                                                                <div class="border-top pt-3">
                                                                    <h6 class="text-warning mb-2 small">
                                                                        <i class="mdi mdi-tune me-1"></i>
                                                                        خيارات إضافية:
                                                                        <small class="text-muted ms-2">
                                                                            @if (($newField['date_diff_unit'] ?? 'days') === 'days')
                                                                                (3 خيارات متاحة)
                                                                            @elseif(($newField['date_diff_unit'] ?? 'days') === 'months')
                                                                                (خياران متاحان)
                                                                            @else
                                                                                (خيار واحد متاح)
                                                                            @endif
                                                                        </small>
                                                                    </h6>

                                                                    <!-- خيار المتبقي فقط - متاح للأيام والأشهر -->
                                                                    @if (in_array($newField['date_diff_unit'] ?? 'days', ['days', 'months']))
                                                                        <div
                                                                            class="form-check mb-2 option-item {{ ($newField['date_diff_unit'] ?? 'days') === 'days' ? 'days-option' : 'months-option' }}">
                                                                            <input class="form-check-input"
                                                                                type="checkbox"
                                                                                wire:model="newField.remaining_only"
                                                                                id="remainingOnly">
                                                                            <label
                                                                                class="form-check-label fw-semibold small"
                                                                                for="remainingOnly">
                                                                                <i
                                                                                    class="mdi mdi-clock-time-four text-warning me-1"></i>
                                                                                المتبقي فقط
                                                                                <span
                                                                                    class="text-success ms-1">✓</span>
                                                                                <small class="d-block text-muted">
                                                                                    @if (($newField['date_diff_unit'] ?? 'days') === 'days')
                                                                                        الأيام المتبقية من الشهر
                                                                                        باستخدام diff()
                                                                                    @elseif(($newField['date_diff_unit'] ?? 'days') === 'months')
                                                                                        الأشهر المتبقية من السنة
                                                                                        باستخدام diff()
                                                                                    @endif
                                                                                </small>
                                                                            </label>
                                                                        </div>
                                                                    @endif

                                                                    <!-- خيار شمل التاريخ النهائي - متاح للأيام فقط -->
                                                                    @if (($newField['date_diff_unit'] ?? 'days') === 'days')
                                                                        <div
                                                                            class="form-check mb-2 option-item days-option">
                                                                            <input class="form-check-input"
                                                                                type="checkbox"
                                                                                wire:model="newField.include_end_date"
                                                                                id="includeEndDate">
                                                                            <label
                                                                                class="form-check-label fw-semibold small"
                                                                                for="includeEndDate">
                                                                                <i
                                                                                    class="mdi mdi-calendar-plus text-success me-1"></i>
                                                                                شمل التاريخ النهائي
                                                                                <span
                                                                                    class="text-success ms-1">✓</span>
                                                                                <small class="d-block text-muted">يضيف
                                                                                    يوم واحد للحساب لشمل اليوم
                                                                                    الأخير</small>
                                                                            </label>
                                                                        </div>
                                                                    @endif

                                                                    <!-- خيار القيمة المطلقة - متاح لجميع الوحدات -->
                                                                    <div
                                                                        class="form-check mb-0 option-item {{ ($newField['date_diff_unit'] ?? 'days') === 'days' ? 'days-option' : (($newField['date_diff_unit'] ?? 'days') === 'months' ? 'months-option' : 'years-option') }}">
                                                                        <input class="form-check-input"
                                                                            type="checkbox"
                                                                            wire:model="newField.absolute_value"
                                                                            id="absoluteValue">
                                                                        <label
                                                                            class="form-check-label fw-semibold small"
                                                                            for="absoluteValue">
                                                                            <i
                                                                                class="mdi mdi-plus-minus text-primary me-1"></i>
                                                                            قيمة مطلقة (موجبة دائماً)
                                                                            <span class="text-success ms-1">✓</span>
                                                                            <small class="d-block text-muted">تحويل
                                                                                القيم السالبة إلى موجبة باستخدام
                                                                                abs()</small>
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
                                        @if (($newField['calculation_type'] ?? 'none') === 'time_diff')
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
                                                                        حساب الفرق بين
                                                                        {{ $newField['time_from_field'] }} و
                                                                        {{ $newField['time_to_field'] }}
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
                                                                    <span class="text-muted">اختر حقلي الوقت
                                                                        للمعاينة</span>
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
                                                                    <label
                                                                        class="form-label fw-semibold text-warning small">
                                                                        <i class="mdi mdi-clock-start me-1"></i>
                                                                        الوقت من:
                                                                    </label>
                                                                    <select class="form-select form-select-sm"
                                                                        wire:model="newField.time_from_field">
                                                                        <option value="">-- اختر حقل الوقت الأول
                                                                            --</option>
                                                                        @foreach ($moduleFields as $field)
                                                                            @if (($field['type'] === 'time' || $field['type'] === 'datetime') && $field['name'] !== ($newField['name'] ?? ''))
                                                                                <option value="{{ $field['name'] }}">
                                                                                    {{ $field['ar_name'] ?? $field['name'] }}
                                                                                    ({{ $field['name'] }}){{ $field['type'] === 'datetime' ? ' - تاريخ ووقت' : '' }}
                                                                                </option>
                                                                            @endif
                                                                        @endforeach
                                                                        @foreach ($pendingFields as $field)
                                                                            @if (($field['type'] === 'time' || $field['type'] === 'datetime') && $field['name'] !== ($newField['name'] ?? ''))
                                                                                <option value="{{ $field['name'] }}">
                                                                                    {{ $field['ar_name'] ?? $field['name'] }}
                                                                                    ({{ $field['name'] }}){{ $field['type'] === 'datetime' ? ' - تاريخ ووقت جديد' : ' - جديد' }}
                                                                                </option>
                                                                            @endif
                                                                        @endforeach
                                                                    </select>
                                                                    <small class="text-muted">حقل الوقت المرجعي
                                                                        الأول</small>
                                                                </div>

                                                                <!-- الوقت إلى -->
                                                                <div class="mb-3">
                                                                    <label
                                                                        class="form-label fw-semibold text-warning small">
                                                                        <i class="mdi mdi-clock-end me-1"></i>
                                                                        الوقت إلى:
                                                                    </label>
                                                                    <select class="form-select form-select-sm"
                                                                        wire:model="newField.time_to_field">
                                                                        <option value="">-- اختر حقل الوقت الثاني
                                                                            --</option>
                                                                        @foreach ($moduleFields as $field)
                                                                            @if (($field['type'] === 'time' || $field['type'] === 'datetime') && $field['name'] !== ($newField['name'] ?? ''))
                                                                                <option value="{{ $field['name'] }}">
                                                                                    {{ $field['ar_name'] ?? $field['name'] }}
                                                                                    ({{ $field['name'] }}){{ $field['type'] === 'datetime' ? ' - تاريخ ووقت' : '' }}
                                                                                </option>
                                                                            @endif
                                                                        @endforeach
                                                                        @foreach ($pendingFields as $field)
                                                                            @if (($field['type'] === 'time' || $field['type'] === 'datetime') && $field['name'] !== ($newField['name'] ?? ''))
                                                                                <option value="{{ $field['name'] }}">
                                                                                    {{ $field['ar_name'] ?? $field['name'] }}
                                                                                    ({{ $field['name'] }}){{ $field['type'] === 'datetime' ? ' - تاريخ ووقت جديد' : ' - جديد' }}
                                                                                </option>
                                                                            @endif
                                                                        @endforeach
                                                                    </select>
                                                                    <small class="text-muted">حقل الوقت المرجعي
                                                                        الثاني</small>
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
                                                                    <label
                                                                        class="form-label fw-semibold text-info small">
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
                                                                        <input class="form-check-input"
                                                                            type="checkbox"
                                                                            wire:model="newField.absolute_value"
                                                                            id="timeAbsoluteValue">
                                                                        <label
                                                                            class="form-check-label fw-semibold small"
                                                                            for="timeAbsoluteValue">
                                                                            <i
                                                                                class="mdi mdi-plus-minus text-primary me-1"></i>
                                                                            قيمة مطلقة (موجبة دائماً)
                                                                            <small class="d-block text-muted">تجاهل
                                                                                الإشارة السالبة</small>
                                                                        </label>
                                                                    </div>

                                                                    <!-- المتبقي فقط - للدقائق فقط -->
                                                                    @if (($newField['time_diff_unit'] ?? 'minutes') === 'minutes')
                                                                        <div class="form-check mb-2">
                                                                            <input class="form-check-input"
                                                                                type="checkbox"
                                                                                wire:model="newField.remaining_only"
                                                                                id="timeRemainingOnly">
                                                                            <label
                                                                                class="form-check-label fw-semibold small"
                                                                                for="timeRemainingOnly">
                                                                                <i
                                                                                    class="mdi mdi-clock-time-four text-warning me-1"></i>
                                                                                الدقائق المتبقية فقط
                                                                                <small class="d-block text-muted">
                                                                                    الدقائق المتبقية بعد استخراج الساعات
                                                                                    الكاملة
                                                                                    <br><strong
                                                                                        class="text-info">مثال:</strong>
                                                                                    75 دقيقة → 15 دقيقة
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
                                                                    <div
                                                                        class="example-card p-2 bg-white rounded border">
                                                                        <div class="fw-bold text-success mb-1 small">
                                                                            ساعات العمل</div>
                                                                        <code class="d-block small">start_time →
                                                                            end_time (ساعات)</code>
                                                                        <small class="text-muted">حساب ساعات العمل
                                                                            اليومية</small>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div
                                                                        class="example-card p-2 bg-white rounded border">
                                                                        <div class="fw-bold text-info mb-1 small">فترة
                                                                            الاستراحة</div>
                                                                        <code class="d-block small">break_start →
                                                                            break_end (دقائق)</code>
                                                                        <small class="text-muted">حساب دقائق
                                                                            الاستراحة</small>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div
                                                                        class="example-card p-2 bg-white rounded border">
                                                                        <div class="fw-bold text-warning mb-1 small">
                                                                            وقت التأخير</div>
                                                                        <code class="d-block small">scheduled_time →
                                                                            actual_time (دقائق)</code>
                                                                        <small class="text-muted">حساب دقائق
                                                                            التأخير</small>
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

                    <!-- خيارات العرض والإظهار -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary mb-3">
                                <i class="mdi mdi-eye-settings me-2"></i>
                                خيارات العرض والإظهار
                            </h6>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                    wire:model.defer="newField.show_in_table" id="fieldShowInTable">
                                <label class="form-check-label" for="fieldShowInTable">
                                    <i class="mdi mdi-table text-success me-2"></i>
                                    ظهور في جدول العرض
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                    wire:model.defer="newField.show_in_search" id="fieldShowInSearch">
                                <label class="form-check-label" for="fieldShowInSearch">
                                    <i class="mdi mdi-magnify text-info me-2"></i>
                                    ظهور في رأس البحث
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                    wire:model.defer="newField.show_in_forms" id="fieldShowInForms">
                                <label class="form-check-label" for="fieldShowInForms">
                                    <i class="mdi mdi-form-select text-warning me-2"></i>
                                    ظهور في نوافذ الإضافة/التعديل
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Debug Info (يمكن حذفه لاحقاً) --}}
                    <div class="row mb-3">
                        <div class="col-12">
                            <small class="text-muted">Debug Info:</small>
                            <div class="bg-light p-2 rounded">
                                <small>
                                    show_in_table: {{ $newField['show_in_table'] ? 'true' : 'false' }} |
                                    show_in_search: {{ $newField['show_in_search'] ? 'true' : 'false' }} |
                                    show_in_forms: {{ $newField['show_in_forms'] ? 'true' : 'false' }}
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- زر الإضافة -->
                    <div class="text-center mt-4 pt-3">
                        <div class="d-flex justify-content-center gap-3">
                            <button type="button" wire:click="addFieldToModule" class="btn btn-success btn-lg px-5"
                                wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="addFieldToModule">
                                    <i class="mdi mdi-plus me-2"></i>
                                    إضافة الحقل للوحدة
                                </span>
                                <span wire:loading wire:target="addFieldToModule">
                                    <i class="mdi mdi-loading mdi-spin me-2"></i>
                                    جاري الإضافة...
                                </span>
                            </button>

                            <button type="button" wire:click="resetNewFieldForm"
                                class="btn btn-outline-secondary px-4">
                                <i class="mdi mdi-refresh me-2"></i>
                                إعادة تعيين
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- الشريط الجانبي -->
        <div class="col-lg-4 col-md-12">
            <!-- الحقول الحالية -->
            @if (!empty($moduleFields))
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-gradient-warning text-dark border-0 py-2">
                        <h6 class="mb-0 text-dark fw-bold">
                            <i class="mdi mdi-format-list-bulleted me-2 fw-bold"></i>
                            الحقول الحالية ({{ count($moduleFields) }})
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="list-group list-group-flush">
                            @foreach ($moduleFields as $index => $field)
                                <div
                                    class="list-group-item d-flex justify-content-between align-items-start border rounded mb-2 py-2">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-bold small">{{ $field['ar_name'] ?? $field['name'] }}</div>
                                        <small class="text-muted">{{ $field['name'] }}
                                            @if ($field['is_calculated'] ?? false)
                                                (محسوب - {{ $fieldTypes[$field['type']] ?? $field['type'] }})
                                            @else
                                                ({{ $fieldTypes[$field['type']] ?? $field['type'] }})
                                            @endif
                                        </small>
                                        <div class="mt-1">
                                            @if ($field['required'] ?? false)
                                                <span class="badge bg-danger rounded-pill me-1 px-2 py-1"
                                                    style="font-size: 0.7rem;">مطلوب</span>
                                            @endif
                                            @if ($field['unique'] ?? false)
                                                <span class="badge bg-warning rounded-pill me-1 px-2 py-1"
                                                    style="font-size: 0.7rem;">فريد</span>
                                            @endif
                                            @if ($field['searchable'] ?? false)
                                                <span class="badge bg-info rounded-pill me-1 px-2 py-1"
                                                    style="font-size: 0.7rem;">قابل للبحث</span>
                                            @endif
                                            @if (!($field['show_in_table'] ?? true))
                                                <span class="badge bg-secondary rounded-pill me-1 px-2 py-1"
                                                    style="font-size: 0.7rem;">مخفي من الجدول</span>
                                            @endif
                                            @if (!($field['show_in_search'] ?? true))
                                                <span class="badge bg-secondary rounded-pill me-1 px-2 py-1"
                                                    style="font-size: 0.7rem;">مخفي من البحث</span>
                                            @endif
                                            @if (!($field['show_in_forms'] ?? true))
                                                <span class="badge bg-secondary rounded-pill me-1 px-2 py-1"
                                                    style="font-size: 0.7rem;">مخفي من النماذج</span>
                                            @endif
                                            {{-- عرض نوع المحتوى الجديد --}}
                                            @if ($field['type'] === 'string' && isset($field['text_content_type']))
                                                @if ($field['text_content_type'] === 'arabic_only')
                                                    <span class="badge bg-secondary rounded-pill me-1" style="font-size: 0.7rem;">عربي فقط</span>
                                                @elseif ($field['text_content_type'] === 'numeric_only')
                                                    <span class="badge bg-secondary rounded-pill me-1" style="font-size: 0.7rem;">أرقام فقط</span>
                                                @elseif ($field['text_content_type'] === 'english_only')
                                                    <span class="badge bg-secondary rounded-pill me-1" style="font-size: 0.7rem;">إنجليزي فقط</span>
                                                @endif
                                            @endif
                                            {{-- عرض تفاصيل الأرقام الصحيحة --}}
                                            @if ($field['type'] === 'integer')
                                                <span class="badge bg-info rounded-pill me-1" style="font-size: 0.7rem;">{{ strtoupper($field['integer_type'] ?? 'INT') }}</span>
                                                @if ($field['unsigned'] ?? false)
                                                    <span class="badge bg-warning rounded-pill me-1" style="font-size: 0.7rem;">UNSIGNED</span>
                                                @endif
                                            @endif
                                            {{-- عرض تفاصيل الأرقام العشرية --}}
                                            @if ($field['type'] === 'decimal')
                                                <span class="badge bg-info rounded-pill me-1" style="font-size: 0.7rem;">DECIMAL({{ $field['decimal_precision'] ?? 15 }},{{ $field['decimal_scale'] ?? 2 }})</span>
                                            @endif
                                            {{-- إبقاء الخيارات القديمة للتوافق --}}
                                            @if ($field['arabic_only'] ?? false)
                                                <span class="badge bg-secondary rounded-pill me-1 px-2 py-1"
                                                    style="font-size: 0.7rem;">عربي فقط</span>
                                            @endif
                                            @if ($field['numeric_only'] ?? false)
                                                <span class="badge bg-secondary rounded-pill me-1 px-2 py-1"
                                                    style="font-size: 0.7rem;">أرقام فقط</span>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="badge bg-primary rounded-pill">{{ $index + 1 }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body text-center py-3">
                        <i class="mdi mdi-information fs-3 text-muted"></i>
                        <p class="text-muted mt-2 mb-1 small">لا توجد حقول حالية</p>
                        <small class="text-muted">ستظهر الحقول الحالية هنا</small>
                    </div>
                </div>
            @endif

            <!-- الحقول الجديدة المضافة -->
            @if (!empty($pendingFields))
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-gradient-success text-white border-0 py-2">
                        <h6 class="mb-0 text-dark fw-bold">
                            <i class="mdi mdi-plus-circle me-2 fw-bold"></i>
                            الحقول الجديدة المضافة ({{ count($pendingFields) }})
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="list-group list-group-flush">
                            @foreach ($pendingFields as $index => $field)
                                <div
                                    class="list-group-item d-flex justify-content-between align-items-start border rounded mb-2 py-2">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-bold small">{{ $field['ar_name'] ?? $field['name'] }}</div>
                                        <small class="text-muted">{{ $field['name'] }}
                                            @if ($field['is_calculated'] ?? false)
                                                (محسوب - {{ $fieldTypes[$field['type']] ?? $field['type'] }})
                                            @else
                                                ({{ $fieldTypes[$field['type']] ?? $field['type'] }})
                                            @endif
                                        </small>
                                        <div class="mt-1">
                                            @if ($field['required'] ?? false)
                                                <span class="badge bg-danger rounded-pill me-1 px-2 py-1"
                                                    style="font-size: 0.7rem;">مطلوب</span>
                                            @endif
                                            @if ($field['unique'] ?? false)
                                                <span class="badge bg-warning rounded-pill me-1 px-2 py-1"
                                                    style="font-size: 0.7rem;">فريد</span>
                                            @endif
                                            @if ($field['searchable'] ?? false)
                                                <span class="badge bg-info rounded-pill me-1 px-2 py-1"
                                                    style="font-size: 0.7rem;">قابل للبحث</span>
                                            @endif
                                            @if (!($field['show_in_table'] ?? true))
                                                <span class="badge bg-secondary rounded-pill me-1 px-2 py-1"
                                                    style="font-size: 0.7rem;">مخفي من الجدول</span>
                                            @endif
                                            @if (!($field['show_in_search'] ?? true))
                                                <span class="badge bg-secondary rounded-pill me-1 px-2 py-1"
                                                    style="font-size: 0.7rem;">مخفي من البحث</span>
                                            @endif
                                            @if (!($field['show_in_forms'] ?? true))
                                                <span class="badge bg-secondary rounded-pill me-1 px-2 py-1"
                                                    style="font-size: 0.7rem;">مخفي من النماذج</span>
                                            @endif
                                            {{-- عرض نوع المحتوى الجديد --}}
                                            @if ($field['type'] === 'string' && isset($field['text_content_type']))
                                                @if ($field['text_content_type'] === 'arabic_only')
                                                    <span class="badge bg-secondary rounded-pill me-1" style="font-size: 0.7rem;">عربي فقط</span>
                                                @elseif ($field['text_content_type'] === 'numeric_only')
                                                    <span class="badge bg-secondary rounded-pill me-1" style="font-size: 0.7rem;">أرقام فقط</span>
                                                @elseif ($field['text_content_type'] === 'english_only')
                                                    <span class="badge bg-secondary rounded-pill me-1" style="font-size: 0.7rem;">إنجليزي فقط</span>
                                                @endif
                                            @endif
                                            {{-- عرض تفاصيل الأرقام الصحيحة --}}
                                            @if ($field['type'] === 'integer')
                                                <span class="badge bg-info rounded-pill me-1" style="font-size: 0.7rem;">{{ strtoupper($field['integer_type'] ?? 'INT') }}</span>
                                                @if ($field['unsigned'] ?? false)
                                                    <span class="badge bg-warning rounded-pill me-1" style="font-size: 0.7rem;">UNSIGNED</span>
                                                @endif
                                            @endif
                                            {{-- عرض تفاصيل الأرقام العشرية --}}
                                            @if ($field['type'] === 'decimal')
                                                <span class="badge bg-info rounded-pill me-1" style="font-size: 0.7rem;">DECIMAL({{ $field['decimal_precision'] ?? 15 }},{{ $field['decimal_scale'] ?? 2 }})</span>
                                            @endif
                                            {{-- إبقاء الخيارات القديمة للتوافق --}}
                                            @if ($field['arabic_only'] ?? false)
                                                <span class="badge bg-success rounded-pill me-1 px-2 py-1"
                                                    style="font-size: 0.7rem;">عربي فقط</span>
                                            @endif
                                            @if ($field['numeric_only'] ?? false)
                                                <span class="badge bg-primary rounded-pill me-1 px-2 py-1"
                                                    style="font-size: 0.7rem;">أرقام فقط</span>
                                            @endif
                                            @if ($field['is_calculated'] ?? false)
                                                <span class="badge bg-success rounded-pill me-1 px-2 py-1"
                                                    style="font-size: 0.7rem;">
                                                    <i class="mdi mdi-calculator"></i>
                                                    محسوب
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <span class="badge bg-success rounded-pill mb-1">{{ $index + 1 }}</span>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                            wire:click="removePendingField({{ $index }})">
                                            <i class="mdi mdi-delete"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- نصائح الاستخدام -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient-primary text-white border-0 py-2">
                    <h6 class="mb-0 text-dark fw-bold">
                        <i class="mdi mdi-lightbulb me-2 fw-bold"></i>
                        نصائح الاستخدام
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex align-items-start">
                            <i class="mdi mdi-numeric-1-circle text-primary me-2 fs-6"></i>
                            <small>اختر نوع الحقل المناسب لبياناتك</small>
                        </div>
                        <div class="d-flex align-items-start">
                            <i class="mdi mdi-numeric-2-circle text-primary me-2 fs-6"></i>
                            <small>فعل الخصائص حسب احتياجاتك</small>
                        </div>
                        <div class="d-flex align-items-start">
                            <i class="mdi mdi-numeric-3-circle text-primary me-2 fs-6"></i>
                            <small>أضف الحقل ثم اضغط "تطبيق الحقول"</small>
                        </div>
                        <div class="d-flex align-items-start">
                            <i class="mdi mdi-numeric-4-circle text-primary me-2 fs-6"></i>
                            <small>استخدم "عربي فقط" للأسماء والنصوص العربية</small>
                        </div>
                        <div class="d-flex align-items-start">
                            <i class="mdi mdi-numeric-5-circle text-primary me-2 fs-6"></i>
                            <small>استخدم "أرقام فقط" للرموز والمعرفات</small>
                        </div>
                        <div class="d-flex align-items-start">
                            <i class="mdi mdi-table text-success me-2 fs-6"></i>
                            <small><strong>ظهور في الجدول:</strong> يظهر الحقل في جدول العرض الرئيسي</small>
                        </div>
                        <div class="d-flex align-items-start">
                            <i class="mdi mdi-magnify text-info me-2 fs-6"></i>
                            <small><strong>ظهور في البحث:</strong> يظهر مربع بحث للحقل في رأس الجدول</small>
                        </div>
                        <div class="d-flex align-items-start">
                            <i class="mdi mdi-form-select text-warning me-2 fs-6"></i>
                            <small><strong>ظهور في النماذج:</strong> يظهر الحقل في نوافذ الإضافة والتعديل</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
                    <div class="bg-light-info rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width: 60px; height: 60px;">
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
                    <div class="bg-light-warning rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width: 60px; height: 60px;">
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

<style>
    .bg-gradient-info {
        background: linear-gradient(135deg, #03c3ec 0%, #7dd3fc 100%);
    }

    .bg-gradient-success {
        background: linear-gradient(135deg, #71dd37 0%, #a1e86c 100%);
    }

    .bg-gradient-warning {
        background: linear-gradient(135deg, #ffb400 0%, #ffd966 100%);
    }

    .bg-gradient-primary {
        background: linear-gradient(135deg, #696eff 0%, #f8acff 100%);
    }

    .bg-gradient-info {
        background: linear-gradient(135deg, #03c3ec 0%, #7dd3fc 100%);
    }

    .bg-gradient-warning {
        background: linear-gradient(135deg, #ffb400 0%, #ffd966 100%);
    }

    .bg-light-info {
        background-color: rgba(3, 195, 236, 0.1);
    }

    .bg-light-warning {
        background-color: rgba(255, 180, 0, 0.1);
    }

    .modal-content {
        border-radius: 15px;
        overflow: hidden;
    }

    .modal-header {
        border-radius: 15px 15px 0 0;
    }

    .modal-dialog.modal-sm {
        max-width: 400px;
    }

    .form-control:focus {
        box-shadow: 0 0 0 0.2rem rgba(3, 195, 236, 0.25);
        border-color: #03c3ec;
    }

    .btn-info:hover {
        background-color: #029bb8;
        border-color: #029bb8;
    }

    .btn-warning:hover {
        background-color: #e6a300;
        border-color: #e6a300;
    }

    /* تنسيق خيارات حساب التاريخ الديناميكية */
    .option-item {
        transition: all 0.3s ease-in-out;
        opacity: 1;
        transform: translateX(0);
        margin-bottom: 0.5rem;
        border-radius: 8px;
        padding: 8px;
        border: 1px solid transparent;
    }

    .option-item.days-option {
        background: linear-gradient(135deg, rgba(40, 167, 69, 0.05) 0%, rgba(40, 167, 69, 0.1) 100%);
        border-color: rgba(40, 167, 69, 0.2);
    }

    .option-item.months-option {
        background: linear-gradient(135deg, rgba(255, 193, 7, 0.05) 0%, rgba(255, 193, 7, 0.1) 100%);
        border-color: rgba(255, 193, 7, 0.2);
    }

    .option-item.years-option {
        background: linear-gradient(135deg, rgba(13, 110, 253, 0.05) 0%, rgba(13, 110, 253, 0.1) 100%);
        border-color: rgba(13, 110, 253, 0.2);
    }

    .option-item:hover {
        transform: translateX(5px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .option-item .form-check-input:checked {
        background-color: #28a745;
        border-color: #28a745;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }

    .table-responsive .table {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .table.table-bordered {
        border: 1px solid #dee2e6;
    }

    .table-light th {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
    }

    .table-primary {
        background: linear-gradient(135deg, rgba(13, 110, 253, 0.1) 0%, rgba(13, 110, 253, 0.05) 100%);
        animation: highlight-row 0.5s ease-in-out;
    }

    @keyframes highlight-row {
        0% {
            background-color: rgba(13, 110, 253, 0.3);
        }

        100% {
            background-color: rgba(13, 110, 253, 0.1);
        }
    }

    .badge {
        font-size: 0.7rem;
        padding: 0.3rem 0.6rem;
        border-radius: 12px;
        font-weight: 500;
    }

    .text-success .ms-1 {
        font-size: 0.9rem;
        animation: checkmark-pulse 1s ease-in-out infinite alternate;
    }

    @keyframes checkmark-pulse {
        0% {
            opacity: 0.7;
            transform: scale(1);
        }

        100% {
            opacity: 1;
            transform: scale(1.1);
        }
    }

    /* تحسين عرض الجدول على الشاشات الصغيرة */
    @media (max-width: 768px) {
        .table-responsive .table {
            font-size: 0.8rem;
        }

        .badge {
            font-size: 0.6rem;
            padding: 0.2rem 0.4rem;
            margin: 1px;
        }
    }
</style>

<script>
    // إعداد Toast
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-start',
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    // دوال المودالات - النطاق العام
    window.showNumberInputModal = function() {
        console.log('فتح مودال إدخال الرقم');
        const modalElement = document.getElementById('numberInputModal');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();

            // تركيز على حقل الإدخال بعد فتح المودال
            setTimeout(() => {
                const numberInput = document.getElementById('numberInput');
                if (numberInput) {
                    numberInput.focus();
                }
            }, 500);
        } else {
            console.error('لم يتم العثور على مودال إدخال الرقم');
        }
    };

    window.showClearConfirmModal = function() {
        console.log('فتح مودال تأكيد المسح');
        const modalElement = document.getElementById('clearConfirmModal');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        } else {
            console.error('لم يتم العثور على مودال تأكيد المسح');
        }
    };

    window.addNumberFromModal = function() {
        console.log('إضافة رقم من المودال');
        const numberInput = document.getElementById('numberInput');
        if (!numberInput) {
            console.error('لم يتم العثور على حقل إدخال الرقم');
            return;
        }

        const number = numberInput.value.trim();

        if (number !== '') {
            // التحقق من أن المدخل رقم صحيح
            if (!isNaN(number)) {
                // استدعاء دالة Livewire لإضافة الرقم
                if (window.Livewire && @this) {
                    @this.call('addNumberToFormula', number).then(() => {
                        // إغلاق المودال وتنظيف الحقل
                        const modalElement = document.getElementById('numberInputModal');
                        if (modalElement) {
                            const modal = bootstrap.Modal.getInstance(modalElement);
                            if (modal) modal.hide();
                        }
                        numberInput.value = '';

                        // إظهار Toast للنجاح
                        Toast.fire({
                            icon: 'success',
                            title: 'تم إضافة الرقم بنجاح',
                            text: `تم إضافة الرقم ${number} إلى المعادلة`
                        });
                    }).catch(error => {
                        console.error('خطأ في إضافة الرقم:', error);
                        Toast.fire({
                            icon: 'error',
                            title: 'خطأ في إضافة الرقم',
                            text: error.message || 'حدث خطأ غير متوقع'
                        });
                    });
                } else {
                    console.error('Livewire غير متاح');
                }
            } else {
                // إظهار Toast للخطأ
                Toast.fire({
                    icon: 'error',
                    title: 'خطأ في الإدخال',
                    text: 'يرجى إدخال رقم صحيح فقط!'
                });
            }
        } else {
            Toast.fire({
                icon: 'warning',
                title: 'تنبيه',
                text: 'يرجى إدخال رقم'
            });
        }
    };

    window.confirmClearFormula = function() {
        console.log('تأكيد مسح المعادلة');
        // استدعاء دالة Livewire لمسح المعادلة
        if (window.Livewire && @this) {
            @this.call('clearFormula').then(() => {
                // إغلاق المودال
                const modalElement = document.getElementById('clearConfirmModal');
                if (modalElement) {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) modal.hide();
                }

                // إظهار Toast للنجاح
                Toast.fire({
                    icon: 'success',
                    title: 'تم مسح المعادلة',
                    text: 'تم حذف جميع عناصر المعادلة بنجاح'
                });
            }).catch(error => {
                console.error('خطأ في مسح المعادلة:', error);
                Toast.fire({
                    icon: 'error',
                    title: 'خطأ في مسح المعادلة',
                    text: error.message || 'حدث خطأ غير متوقع'
                });
            });
        } else {
            console.error('Livewire غير متاح');
        }
    };

    // التأكد من تحديث حالة الـ checkboxes
    document.addEventListener('DOMContentLoaded', function() {
        console.log('تم تحميل ملف add-fields-mode بنجاح');
        console.log('المودالات متاحة:', {
            numberModal: !!document.getElementById('numberInputModal'),
            clearModal: !!document.getElementById('clearConfirmModal')
        });

        // التحقق من وجود Bootstrap
        if (typeof bootstrap !== 'undefined') {
            console.log('Bootstrap متاح');
        } else {
            console.error('Bootstrap غير متاح');
        }
    });

    document.addEventListener('livewire:load', function() {
        // عند تحديث البيانات من Livewire
        Livewire.hook('message.processed', (message, component) => {
            // التحقق من أن القيم محدثة بشكل صحيح
            const showInTableCheckbox = document.getElementById('fieldShowInTable');
            const showInSearchCheckbox = document.getElementById('fieldShowInSearch');
            const showInFormsCheckbox = document.getElementById('fieldShowInForms');

            if (showInTableCheckbox && component.get('newField.show_in_table') !== undefined) {
                showInTableCheckbox.checked = component.get('newField.show_in_table');
            }
            if (showInSearchCheckbox && component.get('newField.show_in_search') !== undefined) {
                showInSearchCheckbox.checked = component.get('newField.show_in_search');
            }
            if (showInFormsCheckbox && component.get('newField.show_in_forms') !== undefined) {
                showInFormsCheckbox.checked = component.get('newField.show_in_forms');
            }
        });
    });

    // استماع للإشارة من الكود PHP
    window.addEventListener('refreshForm', () => {
        setTimeout(() => {
            // إعادة تعيين حالة الـ checkboxes
            const checkboxes = ['fieldShowInTable', 'fieldShowInSearch', 'fieldShowInForms'];
            checkboxes.forEach(id => {
                const checkbox = document.getElementById(id);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
        }, 100);
    });

    // إعادة تحديث الصفحة عند التحويل الناجح
    window.addEventListener('reload', event => {
        const delay = event.detail.delay || 1000;
        setTimeout(() => {
            window.location.reload();
        }, delay);
    });

    // التعامل مع إدخال الرقم الثابت
    window.addEventListener('showNumberPrompt', event => {
        showNumberInputModal();
    });

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
                // استدعاء دالة Livewire لإضافة الرقم
                @this.call('addNumberToFormula', number).then(() => {
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
                }).catch(error => {
                    console.error('خطأ في إضافة الرقم:', error);
                });
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
        // استدعاء دالة Livewire لمسح المعادلة
        @this.call('clearFormula').then(() => {
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
        }).catch(error => {
            console.error('خطأ في مسح المعادلة:', error);
        });
    }

    // إضافة مؤثرات للمودالات
    document.addEventListener('DOMContentLoaded', function() {
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
</script>
