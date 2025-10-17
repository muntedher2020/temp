{{-- وضع إعادة إنشاء الوحدة - Livewire Style --}}
<div class="fade-in">
    <div class="row mb-4">
        <!-- تحذير ومعاينة الإجراءات - مدمج -->
        <div class="col-12">
            <div class="card border-0 shadow-lg rounded-4">
            <div class="card-header border-0 rounded-top-4" style="background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);">
                <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm me-3">
                    <div class="avatar-title bg-white text-danger rounded-circle">
                        <i class="bx bx-error-circle fs-6"></i>
                    </div>
                    </div>
                    <h6 class="mb-0 text-white fw-bold">تحذير: إعادة إنشاء كاملة للوحدة</h6>
                </div>
                <div class="badge bg-white text-warning fs-8">
                    <i class="bx bx-shield-x me-1"></i>
                    عملية خطيرة
                </div>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                <!-- التحذيرات -->
                <div class="col-md-6">
                    <div class="alert alert-warning border-0 rounded-4 bg-warning-subtle">
                    <h6 class="alert-heading text-warning fw-bold mb-3">
                        <i class="bx bx-shield-x me-2"></i>
                        تحذيرات مهمة
                    </h6>
                    <p class="mb-2 text-dark fw-semibold">إعادة الإنشاء ستؤدي إلى:</p>
                    <ul class="mb-0 small">
                        <li class="mb-2"><i class="bx bx-x-circle me-2 text-danger"></i>الكتابة فوق الملفات الموجودة</li>
                        <li class="mb-2"><i class="bx bx-x-circle me-2 text-danger"></i>فقدان أي تعديلات يدوية</li>
                        <li class="mb-0"><i class="bx bx-x-circle me-2 text-danger"></i>إعادة تعيين جميع الإعدادات</li>
                    </ul>
                    </div>
                </div>

                <!-- الإجراءات -->
                <div class="col-md-6">
                    <div class="alert alert-info border-0 rounded-4 bg-info-subtle">
                    <h6 class="alert-heading text-info fw-bold mb-3">
                        <i class="bx bx-list-check me-2"></i>
                        الإجراءات المخططة
                    </h6>
                    <div class="row g-3">
                        <div class="col-12">
                        <p class="mb-2 fw-semibold text-success small">
                            <i class="bx bx-check-circle me-1"></i> سيتم إنشاؤها:
                        </p>
                        <div class="d-flex flex-wrap gap-1 mb-3">
                            <span class="badge bg-success-subtle text-success">Controllers</span>
                            <span class="badge bg-success-subtle text-success">Livewire</span>
                            <span class="badge bg-success-subtle text-success">Models</span>
                            <span class="badge bg-success-subtle text-success">Views</span>
                            <span class="badge bg-success-subtle text-success">Migration</span>
                        </div>

                        <p class="mb-2 fw-semibold text-info small">
                            <i class="bx bx-shield-check me-1"></i> سيتم الاحتفاظ بها:
                        </p>
                        <div class="d-flex flex-wrap gap-1">
                            <span class="badge bg-info-subtle text-info">البيانات</span>
                            <span class="badge bg-info-subtle text-info">الصلاحيات</span>
                            <span class="badge bg-info-subtle text-info">القوائم</span>
                            <span class="badge bg-info-subtle text-info">المسارات</span>
                        </div>
                        </div>
                    </div>
                    </div>
                </div>
                </div>
            </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                            <div class="card-header border-0 rounded-top-4" style="background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);">

                    <h6 class="mb-0">
                        <i class="bx bx-edit me-1"></i>
                        معلومات الوحدة
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="row mb-4">
                            <div class="form-floating form-floating-outline">
                                <input type="text" wire:model="arabicName" class="form-control" id="moduleArabicName"
                                    placeholder="الاسم العربي للوحدة">
                                <label for="moduleArabicName">الاسم العربي للوحدة</label>
                            </div>
                            <div class="form-floating form-floating-outline">
                                <textarea wire:model="newFields" class="form-control" id="additionalFields" rows="4" style="height: 120px;"
                                    placeholder="مثال: name:text:required, email:email, age:number"></textarea>
                                <label for="additionalFields">حقول إضافية (اختياري)</label>
                            </div>

                        <small class="text-muted mt-2 d-block">
                            <i class="bx bx-info-circle me-1"></i>
                            استخدم تنسيق: اسم_الحقل:نوع_الحقل:خيارات
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                            <div class="card-header border-0 rounded-top-4" style="background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);">

                    <h6 class="mb-0">
                        <i class="bx bx-list-ul me-1"></i>
                        الحقول المحفوظة
                    </h6>
                </div>
                <div class="card-body">
                    @if (!empty($moduleFields))
                        <p class="text-success mb-3">
                            <i class="bx bx-check-circle me-1"></i>
                            سيتم الاحتفاظ بـ <strong>{{ count($moduleFields) }}</strong> حقل موجود
                        </p>

                        <div class="row g-2">
                            @foreach ($moduleFields as $field)
                                <div class="col-md-6">
                                    <div class="badge bg-light text-dark border w-100 text-start">
                                        <i class="bx bx-check me-1 text-success"></i>
                                        {{ $field['name'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-3">
                            <i class="bx bx-info-circle fs-2 text-muted"></i>
                            <p class="text-muted mt-2 mb-0">لا توجد حقول محفوظة</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- تأكيد العملية -->
    <div class="card border-danger shadow-sm mt-4">
        <div class="card-header bg-danger text-white">
            <h6 class="mb-0">
                <i class="bx bx-error me-1"></i>
                تأكيد الإجراء
            </h6>
        </div>
        <div class="card-body">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" wire:model="confirmRegeneration"
                    id="confirmRegeneration">
                <label class="form-check-label" for="confirmRegeneration">
                    <strong>أؤكد أنني أفهم أن هذا الإجراء سيقوم بالكتابة فوق الملفات الموجودة</strong>
                </label>
            </div>
        </div>
    </div>
</div>

<style>
    .fade-in {
        animation: fadeIn 0.6s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(30px); }
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

    .rounded-4 {
        border-radius: 20px !important;
    }

    .rounded-top-4 {
        border-top-left-radius: 20px !important;
        border-top-right-radius: 20px !important;
    }

    .shadow-lg {
        box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
    }

    .avatar-xs {
        width: 1.5rem;
        height: 1.5rem;
    }

    .avatar-sm {
        width: 2rem;
        height: 2rem;
    }

    .avatar-lg {
        width: 4rem;
        height: 4rem;
    }

    .avatar-title {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        font-size: inherit;
    }

    /* تحسينات إضافية للألوان */
    .bg-warning-subtle {
        background-color: rgba(255, 180, 0, 0.1) !important;
    }

    .bg-success-subtle {
        background-color: rgba(113, 221, 55, 0.1) !important;
    }

    .bg-info-subtle {
        background-color: rgba(3, 195, 236, 0.1) !important;
    }

    .bg-danger-subtle {
        background-color: rgba(255, 99, 132, 0.1) !important;
    }

    .fs-7 {
        font-size: 0.875rem !important;
    }

    .fs-8 {
        font-size: 0.75rem !important;
    }

    /* تأثيرات التمرير */
    .card:hover {
        transform: translateY(-2px);
        transition: all 0.3s ease;
    }

    .alert:hover {
        transform: translateY(-1px);
        transition: all 0.3s ease;
    }
</style>
