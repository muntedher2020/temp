{{-- وضع عرض معلومات الوحدة - Livewire Style --}}
<div class="fade-in">
    <!-- إحصائيات سريعة -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-gradient-primary text-white">
                <div class="card-body text-center py-3">
                    <i class="bx bx-code-alt fs-4 mb-1"></i>
                    <h5 class="mb-1">{{ count($moduleFields) }}</h5>
                    <small class="fs-7">الحقول الحالية</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-gradient-success text-white">
                <div class="card-body text-center py-3">
                    <i class="bx bx-check-circle fs-4 mb-1"></i>
                    <h5 class="mb-1">
                        @if(!empty($selectedModuleData['has_model']) && !empty($selectedModuleData['has_controller']) && !empty($selectedModuleData['has_views']))
                            100%
                        @else
                            75%
                        @endif
                    </h5>
                    <small class="fs-7">اكتمال الوحدة</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-gradient-info text-white">
                <div class="card-body text-center py-3">
                    <i class="bx bx-time fs-4 mb-1"></i>
                    <h5 class="mb-1">{{ date('Y-m-d') }}</h5>
                    <small class="fs-7">آخر تحديث</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-gradient-warning text-white">
                <div class="card-body text-center py-3">
                    <i class="bx bx-cog fs-4 mb-1"></i>
                    <h5 class="mb-1">{{ $selectedModuleData['type'] ?? 'نشط' }}</h5>
                    <small class="fs-7">حالة النظام</small>
                </div>
            </div>
        </div>
    </div>

    <!-- معلومات تفصيلية -->
    <div class="row">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-light border-0 pb-2">
                <h6 class="mb-0 text-dark">
                <i class="mdi mdi-information-outline me-2"></i>
                معلومات عامة
                </h6>
            </div>
            <div class="card-body pt-2">
                <div class="row g-2">
                <div class="col-6">
                    <div class="text-center p-2 bg-primary-subtle rounded">
                    <div class="text-primary mb-1">
                        <i class="mdi mdi-cube-outline fs-5"></i>
                    </div>
                    <h6 class="mb-1 text-dark fs-7">اسم الوحدة</h6>
                    <code class="text-primary bg-white px-2 py-1 rounded small">{{ $editingModule }}</code>
                    </div>
                </div>

                <div class="col-6">
                    <div class="text-center p-2 bg-success-subtle rounded">
                    <div class="text-success mb-1">
                        <i class="mdi mdi-translate fs-5"></i>
                    </div>
                    <h6 class="mb-1 text-dark fs-7">الاسم العربي</h6>
                    <small class="text-muted">{{ $arabicName ?: 'غير محدد' }}</small>
                    </div>
                </div>

                <div class="col-6">
                    <div class="text-center p-2 bg-info-subtle rounded">
                    <div class="text-info mb-1">
                        <i class="mdi mdi-format-list-numbered fs-5"></i>
                    </div>
                    <h5 class="mb-1 text-dark">{{ count($moduleFields) }}</h5>
                    <small class="text-muted">عدد الحقول</small>
                    </div>
                </div>

                <div class="col-6">
                    <div class="text-center p-2 rounded {{ (!empty($selectedModuleData['has_model']) && !empty($selectedModuleData['has_controller']) && !empty($selectedModuleData['has_views'])) ? 'bg-success-subtle' : 'bg-warning-subtle' }}">
                    <div class="{{ (!empty($selectedModuleData['has_model']) && !empty($selectedModuleData['has_controller']) && !empty($selectedModuleData['has_views'])) ? 'text-success' : 'text-warning' }} mb-1">
                        <i class="mdi {{ (!empty($selectedModuleData['has_model']) && !empty($selectedModuleData['has_controller']) && !empty($selectedModuleData['has_views'])) ? 'mdi-check-circle' : 'mdi-clock-outline' }} fs-5"></i>
                    </div>
                    <h6 class="mb-1 text-dark fs-7">
                        @if(!empty($selectedModuleData['has_model']) && !empty($selectedModuleData['has_controller']) && !empty($selectedModuleData['has_views']))
                        مكتملة
                        @else
                        ناقصة
                        @endif
                    </h6>
                    <small class="text-muted">حالة الوحدة</small>
                    </div>
                </div>
                </div>
            </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light border-0">
                    <h6 class="mb-0 text-dark">
                        <i class="mdi mdi-puzzle-outline me-2"></i>
                        المكونات المتاحة
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <!-- Model Component -->
                        <div class="list-group-item border-0 py-2">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-2">
                                    <div class="avatar-sm">
                                        <div class="avatar-title bg-primary-subtle text-primary rounded-circle">
                                            <i class="mdi mdi-database fs-6"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 text-dark fs-7">Model</h6>
                                    <p class="text-muted mb-0 small">نموذج البيانات وقواعد العمل</p>
                                </div>
                                <div class="flex-shrink-0">
                                    @if(!empty($selectedModuleData['has_model']))
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fs-8">
                                            <i class="mdi mdi-check-circle me-1"></i>متوفر
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-8">
                                            <i class="mdi mdi-close-circle me-1"></i>غير متوفر
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Controller Component -->
                        <div class="list-group-item border-0 py-2">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-2">
                                    <div class="avatar-sm">
                                        <div class="avatar-title bg-info-subtle text-info rounded-circle">
                                            <i class="mdi mdi-cog-outline fs-6"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 text-dark fs-7">Controller</h6>
                                    <p class="text-muted mb-0 small">منطق التحكم والعمليات</p>
                                </div>
                                <div class="flex-shrink-0">
                                    @if(!empty($selectedModuleData['has_controller']))
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fs-8">
                                            <i class="mdi mdi-check-circle me-1"></i>متوفر
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-8">
                                            <i class="mdi mdi-close-circle me-1"></i>غير متوفر
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Livewire Component -->
                        <div class="list-group-item border-0 py-2">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-2">
                                    <div class="avatar-sm">
                                        <div class="avatar-title bg-warning-subtle text-warning rounded-circle">
                                            <i class="mdi mdi-lightning-bolt fs-6"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 text-dark fs-7">Livewire</h6>
                                    <p class="text-muted mb-0 small">التفاعل المباشر بدون JavaScript</p>
                                </div>
                                <div class="flex-shrink-0">
                                    @if(!empty($selectedModuleData['has_livewire']))
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fs-8">
                                            <i class="mdi mdi-check-circle me-1"></i>متوفر
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-8">
                                            <i class="mdi mdi-close-circle me-1"></i>غير متوفر
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Views Component -->
                        <div class="list-group-item border-0 py-2">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-2">
                                    <div class="avatar-sm">
                                        <div class="avatar-title bg-secondary-subtle text-secondary rounded-circle">
                                            <i class="mdi mdi-monitor-dashboard fs-6"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 text-dark fs-7">Views</h6>
                                    <p class="text-muted mb-0 small">واجهات المستخدم والقوالب</p>
                                </div>
                                <div class="flex-shrink-0">
                                    @if(!empty($selectedModuleData['has_views']))
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fs-8">
                                            <i class="mdi mdi-check-circle me-1"></i>متوفر
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-8">
                                            <i class="mdi mdi-close-circle me-1"></i>غير متوفر
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- عرض الحقول الحالية -->
    @if(!empty($moduleFields))
        <div class="card border-info mt-4">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">
                    <i class="bx bx-list-ul me-1"></i>
                    حقول الوحدة الحالية ({{ count($moduleFields) }})
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 30%;">اسم الحقل</th>
                                <th style="width: 20%;">النوع</th>
                                <th style="width: 12%;">مطلوب</th>
                                <th style="width: 15%;">حالة</th>
                                <th style="width: 18%;">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($moduleFields as $index => $field)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <code class="text-primary">{{ $field['name'] }}</code>
                                        @if(!empty($field['ar_name']))
                                            <small class="d-block text-muted">{{ $field['ar_name'] }}</small>
                                        @endif
                                        @if($field['is_calculated'] ?? false)
                                            <small class="d-block text-success">
                                                <i class="mdi mdi-function"></i>
                                                معادلة: <code>{{ $field['calculation_formula'] ?? 'غير محددة' }}</code>
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($field['is_calculated'] ?? false)
                                            <span class="badge bg-success">
                                                <i class="mdi mdi-calculator"></i>
                                                محسوب - {{ $fieldTypes[$field['type']] ?? $field['type'] }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">{{ $fieldTypes[$field['type']] ?? $field['type'] }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($field['required'] ?? false)
                                            <span class="badge bg-danger">
                                                <i class="bx bx-error-circle me-1"></i>
                                                مطلوب
                                            </span>
                                        @else
                                            <span class="badge bg-success">
                                                <i class="bx bx-check-circle me-1"></i>
                                                اختياري
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <i class="bx bx-check me-1"></i>
                                            فعال
                                        </span>
                                    </td>
                                    <td>
                                        @if(!in_array($field['name'], ['id', 'created_at', 'updated_at', 'deleted_at']))
                                            <div class="d-flex gap-1">
                                                <!-- الحذف المحسن فقط -->
                                                <button type="button"
                                                        class="btn btn-outline-success btn-sm"
                                                        wire:click="confirmDeleteField({{ $index }})"
                                                        title="حذف الحقل وإعادة إنشاء النافذة (طريقة محسنة)">
                                                    <i class="bx bx-refresh me-1"></i>
                                                    <span class="d-none d-md-inline">حذف محسن</span>
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-muted small">
                                                <i class="bx bx-lock me-1"></i>
                                                محمي
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-warning mt-4">
            <div class="d-flex align-items-center">
                <i class="bx bx-info-circle fs-4 me-3"></i>
                <div>
                    <h6 class="alert-heading mb-1">لا توجد حقول محددة</h6>
                    <p class="mb-0">لم يتم العثور على حقول للوحدة، قد تكون وحدة نظام أو تحتاج لإعادة تحليل</p>
                </div>
            </div>
        </div>
    @endif

    <!-- مشاكل الكود المكتشفة -->
    @if(!empty($detectedSyntaxIssues))
        <div class="alert alert-warning mt-4">
            <div class="d-flex align-items-start">
                <i class="bx bx-error fs-4 me-3 text-warning"></i>
                <div class="flex-grow-1">
                    <h6 class="alert-heading mb-2">تم اكتشاف مشاكل في الكود</h6>
                    <ul class="mb-0">
                        @foreach($detectedSyntaxIssues as $issue)
                            <li>{{ $issue }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- مودال تأكيد حذف الحقل -->
@if($showFieldDeleteConfirm)
<div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5); z-index: 1050;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h6 class="modal-title">
                    <i class="bx bx-refresh me-1"></i>
                    حذف محسن
                </h6>
                <button type="button" class="btn-close btn-close-white" wire:click="cancelDeleteField"></button>
            </div>
            <div class="modal-body text-center py-3">
                @if($fieldToDelete)
                <div class="mb-3">
                    <i class="bx bx-error-circle text-success" style="font-size: 2.5rem;"></i>
                </div>
                <h6 class="mb-2">هل تريد حذف الحقل وإعادة إنشاء النافذة؟</h6>

                <div class="text-start bg-light p-2 rounded mb-3" style="font-size: 0.85rem;">
                    <div><strong>الحقل:</strong> {{ $fieldToDelete['ar_name'] ?? $fieldToDelete['name'] ?? '' }}</div>
                    <div class="text-success mt-1">
                        <strong>الطريقة المحسنة:</strong>
                        <ul class="mb-0 mt-1" style="font-size: 0.8rem;">
                            <li>يحذف الحقل نهائياً من قاعدة البيانات</li>
                            <li>يعيد إنشاء النافذة بالحقول المتبقية</li>
                            <li>ينظف جميع المراجع للحقل</li>
                        </ul>
                    </div>
                </div>
                @endif
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary btn-sm" wire:click="cancelDeleteField">إلغاء</button>
                <button type="button" class="btn btn-success btn-sm" wire:click="confirmDeleteFieldAction">
                    تأكيد
                </button>
            </div>
        </div>
    </div>
</div>
@endif<style>
.fade-in {
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #696eff 0%, #f8acff 100%);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #71dd37 0%, #a1e86c 100%);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #03c3ec 0%, #7dd3fc 100%);
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #ffb400 0%, #ffd966 100%);
}

/* تحسينات إضافية للمكونات */
.avatar-sm {
    width: 2.5rem;
    height: 2.5rem;
}

.avatar-title {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

/* خلفيات ناعمة */
.bg-primary-subtle {
    background-color: rgba(105, 110, 255, 0.1) !important;
    color: #696eff !important;
}

.bg-success-subtle {
    background-color: rgba(113, 221, 55, 0.1) !important;
    color: #71dd37 !important;
}

.bg-info-subtle {
    background-color: rgba(3, 195, 236, 0.1) !important;
    color: #03c3ec !important;
}

.bg-warning-subtle {
    background-color: rgba(255, 180, 0, 0.1) !important;
    color: #ffb400 !important;
}

.bg-danger-subtle {
    background-color: rgba(255, 99, 132, 0.1) !important;
    color: #ff6384 !important;
}

.bg-secondary-subtle {
    background-color: rgba(108, 117, 125, 0.1) !important;
    color: #6c757d !important;
}

/* ألوان النص */
.text-primary-subtle {
    color: #696eff !important;
}

.text-success-subtle {
    color: #71dd37 !important;
}

.text-info-subtle {
    color: #03c3ec !important;
}

.text-warning-subtle {
    color: #ffb400 !important;
}

.text-danger-subtle {
    color: #ff6384 !important;
}

.text-secondary-subtle {
    color: #6c757d !important;
}

/* حدود ناعمة */
.border-primary-subtle {
    border-color: rgba(105, 110, 255, 0.2) !important;
}

.border-success-subtle {
    border-color: rgba(113, 221, 55, 0.2) !important;
}

.border-info-subtle {
    border-color: rgba(3, 195, 236, 0.2) !important;
}

.border-warning-subtle {
    border-color: rgba(255, 180, 0, 0.2) !important;
}

.border-danger-subtle {
    border-color: rgba(255, 99, 132, 0.2) !important;
}

.border-secondary-subtle {
    border-color: rgba(108, 117, 125, 0.2) !important;
}
</style>
