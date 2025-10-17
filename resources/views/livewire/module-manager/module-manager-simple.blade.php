<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bx bx-cog me-2"></i>
                        إدارة الوحدات المُنشأة
                    </h5>
                    <div>
                        <button wire:click="refreshModules" class="btn btn-primary btn-sm">
                            <i class="bx bx-refresh me-1"></i>
                            تحديث القائمة
                        </button>

                        <button wire:click="testFunction" class="btn btn-success btn-sm ms-2">
                            <i class="bx bx-check me-1"></i>
                            اختبار الاتصال
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    @if(empty($modules))
                        <div class="text-center py-4">
                            <i class="bx bx-package display-4 text-muted"></i>
                            <p class="text-muted mt-2">لا توجد وحدات مُنشأة حالياً</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>اسم الوحدة</th>
                                        <th>الاسم العربي</th>
                                        <th>نوع الوحدة</th>
                                        <th>المكونات</th>
                                        <th>تاريخ الإنشاء</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($modules as $index => $module)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <strong>{{ $module['name'] }}</strong>
                                            </td>
                                            <td>{{ $module['arabic_name'] }}</td>
                                            <td>
                                                @php
                                                    $typeColors = [
                                                        'main' => 'bg-primary',
                                                        'sub' => 'bg-info',
                                                        'system' => 'bg-warning',
                                                        'standalone' => 'bg-secondary',
                                                        'unknown' => 'bg-dark'
                                                    ];
                                                    $typeLabels = [
                                                        'main' => 'رئيسية',
                                                        'sub' => 'فرعية',
                                                        'system' => 'نظام',
                                                        'standalone' => 'منفصلة',
                                                        'unknown' => 'غير محدد'
                                                    ];
                                                @endphp
                                                <span class="badge {{ $typeColors[$module['type']] ?? 'bg-secondary' }}">
                                                    {{ $typeLabels[$module['type']] ?? $module['type'] }}
                                                </span>
                                                @if($module['type'] === 'sub' && $module['parent_group'])
                                                    <br><small class="text-muted">تحت: {{ $module['parent_group'] }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <span class="badge {{ $module['has_livewire'] ? 'bg-success' : 'bg-danger' }}" title="Livewire">L</span>
                                                    <span class="badge {{ $module['has_model'] ? 'bg-success' : 'bg-danger' }}" title="Model">M</span>
                                                    <span class="badge {{ $module['has_views'] ? 'bg-success' : 'bg-danger' }}" title="Views">V</span>
                                                    @if($module['complete'])
                                                        <span class="badge bg-primary" title="وحدة كاملة">✓</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <small>{{ date('Y-m-d H:i', $module['created_at']) }}</small>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <!-- زر التعديل المباشر -->
                                                    <button class="btn btn-sm btn-primary"
                                                            wire:click="openEditModal('{{ $module['name'] }}')"
                                                            title="تعديل الوحدة">
                                                        <i class="bx bx-edit me-1"></i>
                                                        تعديل
                                                    </button>

                                                    <!-- زر الحذف المباشر -->
                                                    <button class="btn btn-sm btn-danger"
                                                            wire:click="confirmDeleteModule('{{ $module['name'] }}')"
                                                            title="حذف الوحدة">
                                                        <i class="bx bx-trash me-1"></i>
                                                        حذف
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <div class="alert alert-info">
                                <i class="bx bx-info-circle me-2"></i>
                                <strong>إجمالي الوحدات:</strong> {{ count($modules) }} وحدة
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal تأكيد الحذف المتقدم --}}
    @if($showDeleteModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="bx bx-trash me-2"></i>
                        تأكيد الحذف المتقدم
                    </h5>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bx bx-warning me-2"></i>
                        <strong>تحذير:</strong> سيتم حذف جميع مكونات الوحدة
                    </div>

                    <p>هل أنت متأكد من حذف الوحدة <strong>"{{ $moduleToDelete }}"</strong>؟</p>

                    <div class="bg-light p-3 rounded">
                        <small class="text-muted">سيتم حذف:</small>
                        <ul class="mb-0 mt-2" style="font-size: 0.875rem;">
                            <li>جميع ملفات Controllers</li>
                            <li>جميع مكونات Livewire</li>
                            <li>ملفات Models والعلاقات</li>
                            <li>جميع ملفات Views</li>
                            <li>جداول قاعدة البيانات</li>
                            <li>الصلاحيات والأدوار</li>
                            <li>ملفات Migration</li>
                            <li>المسارات (Routes)</li>
                            <li>حقول الوحدة من جدول module_fields</li>
                            <li>تنظيف الكاش</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showDeleteModal', false)">
                        إلغاء
                    </button>
                    <button type="button" class="btn btn-danger" wire:click="deleteModuleWithReport">
                        <i class="bx bx-trash me-1"></i>
                        حذف الوحدة نهائياً
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal تعديل الوحدة المتطور --}}
    @if($showEditModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title text-dark mb-0">
                        <i class="bx bx-edit me-2 text-primary"></i>
                        إدارة الوحدة: <span class="fw-bold">{{ $editingModule }}</span>
                        @if(!empty($arabicName) && $arabicName !== $editingModule)
                            <small class="text-muted ms-2">({{ $arabicName }})</small>
                        @endif
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeEditModal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-0">
                    <!-- الواجهة الحديثة بنظام Nav Pills -->
                    <div class="container-fluid p-4">
                        <!-- أزرار التبديل بين الأوضاع -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <nav class="nav nav-pills nav-fill bg-light rounded p-2"
                                     style="border-radius: 15px !important;">
                                    <button type="button"
                                            class="nav-link {{ $editMode === 'view' ? 'active' : '' }}"
                                            wire:click="setEditMode('view')"
                                            wire:loading.class="disabled"
                                            style="border-radius: 10px !important; transition: all 0.3s;">
                                        <i class="bx bx-info-circle me-2"></i>
                                        عرض المعلومات
                                        @if($editMode === 'view')
                                            <i class="bx bx-check-circle ms-2"></i>
                                        @endif
                                    </button>

                                    <button type="button"
                                            class="nav-link {{ $editMode === 'add_fields' ? 'active' : '' }}"
                                            wire:click="setEditMode('add_fields')"
                                            wire:loading.class="disabled"
                                            style="border-radius: 10px !important; transition: all 0.3s;">
                                        <i class="bx bx-plus me-2"></i>
                                        إضافة حقول
                                        @if($editMode === 'add_fields')
                                            <i class="bx bx-check-circle ms-2"></i>
                                        @endif
                                        @if(!empty($pendingFields))
                                            <span class="badge bg-warning ms-2">{{ count($pendingFields) }}</span>
                                        @endif
                                    </button>

                                    <button type="button"
                                            class="nav-link {{ $editMode === 'edit' ? 'active' : '' }} d-flex align-items-center position-relative"
                                            wire:click="setEditMode('edit')"
                                            wire:loading.class="disabled"
                                            style="border-radius: 15px !important; transition: all 0.3s; background: {{ $editMode === 'edit' ? 'linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%)' : 'transparent' }}; border: 2px solid {{ $editMode === 'edit' ? '#ff6b6b' : '#dee2e6' }}; color: {{ $editMode === 'edit' ? 'white' : '#6c757d' }};">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-xs me-2">
                                                <div class="avatar-title bg-danger-subtle text-danger rounded-circle">
                                                    <i class="bx bx-refresh fs-6"></i>
                                                </div>
                                            </div>
                                            <span class="fw-semibold">إعادة إنشاء الوحدة</span>
                                            @if($editMode === 'edit')
                                                <i class="bx bx-check-circle ms-2 text-white"></i>
                                            @endif
                                        </div>
                                        @if($editMode === 'edit')
                                            <div class="position-absolute top-0 start-0 w-100 h-100 rounded" style="background: rgba(255,255,255,0.1); pointer-events: none;"></div>
                                        @endif
                                    </button>
                                </nav>
                            </div>
                        </div>

                        <!-- محتوى الأوضاع المختلفة -->
                        <div class="tab-content">
                            <!-- Loading State فقط للعمليات المهمة -->
                            <div wire:loading wire:target="loadModuleData,applyPendingFields,recreateModule,fixSyntaxErrors,checkSyntaxIssues" class="text-center py-5">
                                <div class="spinner-border spinner-border-lg text-primary" role="status">
                                    <span class="visually-hidden">جاري التحميل...</span>
                                </div>
                                <p class="mt-3 text-muted">جاري المعالجة...</p>
                            </div>

                            <div wire:loading.remove wire:target="loadModuleData,applyPendingFields,recreateModule,fixSyntaxErrors,checkSyntaxIssues">
                                @if($editMode === 'view')
                                    @include('livewire.module-manager.partials.view-mode')
                                @elseif($editMode === 'add_fields')
                                    @include('livewire.module-manager.partials.add-fields-mode')
                                @elseif($editMode === 'edit')
                                    @include('livewire.module-manager.partials.edit-mode')
                                @endif
                            </div>

                            <!-- Loading State للعمليات البسيطة -->
                            <div wire:loading wire:target.except="loadModuleData,applyPendingFields,recreateModule,fixSyntaxErrors,checkSyntaxIssues" class="position-fixed top-0 end-0 m-3" style="z-index: 9999;">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">جاري التحديث...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer بأزرار التحكم الرئيسية -->
                <div class="modal-footer bg-light d-flex justify-content-between">
                    <!-- Left Side - Close Button -->
                    <div>
                        <button type="button" class="btn btn-secondary" wire:click="closeEditModal">
                            <i class="bx bx-x me-1"></i>
                            إغلاق
                        </button>
                    </div>

                    <!-- Right Side - Action Buttons -->
                    <div class="d-flex gap-2">
                        @if($editMode === 'view')
                            <!-- زر فحص وإصلاح مشاكل Syntax - فقط في وضع العرض -->
                            <button type="button" class="btn btn-outline-warning"
                                    wire:click="checkSyntaxIssues"
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="checkSyntaxIssues">
                                    <i class="bx bx-search me-1"></i>
                                    فحص مشاكل الكود
                                </span>
                                <span wire:loading wire:target="checkSyntaxIssues">
                                    <i class="bx bx-loader-alt bx-spin me-1"></i>
                                    جاري الفحص...
                                </span>
                            </button>

                            @if(!empty($detectedSyntaxIssues))
                                <button type="button" class="btn btn-warning"
                                        wire:click="fixSyntaxErrors"
                                        wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="fixSyntaxErrors">
                                        <i class="bx bx-wrench me-1"></i>
                                        إصلاح {{ count($detectedSyntaxIssues) }} مشكلة
                                    </span>
                                    <span wire:loading wire:target="fixSyntaxErrors">
                                        <i class="bx bx-loader-alt bx-spin me-1"></i>
                                        جاري الإصلاح...
                                    </span>
                                </button>
                            @endif

                        @elseif($editMode === 'add_fields')
                            <button type="button" class="btn btn-success" wire:click="applyPendingFields"
                                    @if(empty($pendingFields)) disabled @endif
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="applyPendingFields">
                                    <i class="mdi mdi-plus me-1"></i>
                                    تطبيق الحقول الجديدة ({{ count($pendingFields ?? []) }})
                                </span>
                                <span wire:loading wire:target="applyPendingFields">
                                    <i class="mdi mdi-loading mdi-spin me-1"></i>
                                    جاري التطبيق...
                                </span>
                            </button>

                        @elseif($editMode === 'edit')
                            <div class="form-check me-3 align-self-center">
                                <input class="form-check-input" type="checkbox"
                                       wire:model="confirmRegeneration"
                                       id="confirmRegeneration">
                                <label class="form-check-label text-warning fw-bold" for="confirmRegeneration">
                                    <i class="bx bx-check-shield me-1"></i>
                                    أوافق على إعادة الإنشاء
                                </label>
                            </div>

                            <button type="button" class="btn btn-warning"
                                    wire:click="recreateModule"
                                    @if(!$confirmRegeneration) disabled @endif
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="recreateModule">
                                    <i class="bx bx-refresh me-1"></i>
                                    إعادة إنشاء الوحدة
                                </span>
                                <span wire:loading wire:target="recreateModule">
                                    <i class="bx bx-loader-alt bx-spin me-1"></i>
                                    جاري إعادة الإنشاء...
                                </span>
                            </button>
                        @endif

                        <!-- زر التوجه لمولد الوحدات -->
                        <a href="{{ route('Module-Generator') }}?module={{ $editingModule }}"
                           class="btn btn-primary" target="_blank">
                            <i class="bx bx-plus-circle me-1"></i>
                            فتح مولد الوحدات
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
